<?php
namespace App\Core;

use App\Services\Access;
use App\Services\Courses;
use App\Services\Notifier;
use App\Services\Progress;

/**
 * Background jobs: mail queue, lesson-unlock notifications, expiry reminders, cleanup.
 * Run via real cron (php cron.php) or the built-in "web cron" that piggybacks on page views after the response is sent.
 */
class Scheduler {
    public static bool $mailQueued = false;
    private static bool $busy = false;
    private const STAMP = STORAGE_PATH . '/cache/cron.stamp';

    /**
     * Automatic background work, registered as a shutdown function so it also runs after redirect()/exit.
     * Order of preference: finish the response (PHP-FPM / LiteSpeed) and work in-process; otherwise fire a
     * non-blocking request to /cron/run; if that is impossible, send queued emails inline. No server cron needed.
     */
    public static function afterResponse(): void {
        if (self::$busy || PHP_SAPI === 'cli' || !App::installed()) {
            return;}
        $due = !is_file(self::STAMP) || filemtime(self::STAMP) < time() - 300;
        if (!$due && !self::$mailQueued) {
            return;}
        self::$busy = true;
        @touch(self::STAMP);
        ignore_user_abort(true);
        @set_time_limit(120);
        try {
            if (function_exists('fastcgi_finish_request') || function_exists('litespeed_finish_request')) {
                Session::close();
                function_exists('fastcgi_finish_request') ? fastcgi_finish_request() : litespeed_finish_request();
                $due ? self::run() : Mailer::processQueue(25);}
            elseif (!self::spawn($due ? '' : 'mail')) {
                Session::close();
                $due ? self::run() : Mailer::processQueue(5);}}
        catch (\Throwable $e) {
            Logger::error('Background tasks failed: ' . $e->getMessage());}}

    /** Fire-and-forget loopback request to the cron URL (the WordPress-cron technique). */
    private static function spawn(string $only): bool {
        $u = parse_url(url('/cron/run', ['key' => self::secret()] + ($only ? ['only' => $only] : []), true));
        if (empty($u['host'])) {
            return false;}
        $tls = ($u['scheme'] ?? 'http') === 'https';
        $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'SNI_enabled' => true, 'peer_name' => $u['host']]]);
        $fp = @stream_socket_client(($tls ? 'tls://' : 'tcp://') . $u['host'] . ':' . ($u['port'] ?? ($tls ? 443 : 80)), $no, $err, 2, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            return false;}
        $ok = (bool)@fwrite($fp, 'GET ' . ($u['path'] ?? '/') . '?' . ($u['query'] ?? '') . " HTTP/1.1\r\nHost: " . $u['host'] . "\r\nUser-Agent: Zeroes-Cron\r\nConnection: close\r\n\r\n");
        fclose($fp);
        return $ok;}

    public static function secret(): string {
        $s = (string)Settings::get('cron_secret', '');
        if ($s === '') {
            Settings::set(['cron_secret' => $s = bin2hex(random_bytes(16))]);}
        return $s;}

    /** Called by /cron/run: the caller may already have disconnected, so keep going. */
    public static function runRequest(string $only = ''): array {
        self::$busy = true;
        ignore_user_abort(true);
        @set_time_limit(300);
        Session::close();
        return $only === 'mail' ? ['emails_sent' => Mailer::processQueue()] : self::run();}

    public static function run(): array {
        @touch(self::STAMP);
        $r = [];
        $r['unlock_notifications'] = self::lessonUnlocks();
        $r['expiry_reminders'] = self::expiryReminders();
        $r['emails_sent'] = Mailer::processQueue();
        $r['cleanup'] = self::cleanup();
        Hooks::fire('cron.run', $r);
        Settings::set(['cron_last_run' => now()]);
        return $r;}

    /** Notify students about lessons that unlocked since the last check. */
    public static function lessonUnlocks(): int {
        $last = (int)Settings::get('cron_unlock_checked', 0);
        $now = time();
        if (!$last) {
            Settings::set(['cron_unlock_checked' => $now]);
            return 0;}
        $enrs = DB::all("SELECT e.*, u.name, u.email FROM {enrollments} e JOIN {users} u ON u.id = e.user_id AND u.status = 'active' JOIN {courses} c ON c.id = e.course_id AND c.status = 'published' WHERE e.status = 'active' AND (e.expires_at IS NULL OR e.expires_at > ?)", [now()]);
        if (!$enrs) {
            Settings::set(['cron_unlock_checked' => $now]);
            return 0;}
        $courseIds = array_values(array_unique(array_column($enrs, 'course_id')));
        $courses = array_column(DB::all('SELECT * FROM {courses} WHERE id IN (' . DB::in($courseIds) . ')', $courseIds), null, 'id');
        $lessons = DB::all("SELECT l.id, l.course_id, l.title, l.release_day, l.release_at, l.module_id, m.release_day AS module_release_day FROM {lessons} l LEFT JOIN {modules} m ON m.id = l.module_id WHERE l.status = 'published' AND l.course_id IN (" . DB::in($courseIds) . ') AND (l.release_at IS NOT NULL OR l.release_day > 1 OR m.release_day > 1)', $courseIds);
        $byCourse = [];
        foreach ($lessons as $l) {
            $byCourse[$l['course_id']][] = $l;}
        $notes = [];
        $sendEmail = (bool)Settings::get('email_lesson_unlock', true);
        $notify = (bool)Settings::get('notify_lesson_unlock', true);
        $emails = 0;
        foreach ($enrs as $e) {
            $c = $courses[$e['course_id']] ?? null;
            if (!$c) {
                continue;}
            $fresh = [];
            foreach ($byCourse[$e['course_id']] ?? [] as $l) {
                $at = Access::unlockAt($l, $e, $c);
                if ($at > $last && $at <= $now) {
                    $fresh[] = $l;}}
            if (!$fresh) {
                continue;}
            foreach ($fresh as $l) {
                if ($notify) {
                    $notes[] = ['user_id' => (int)$e['user_id'], 'type' => 'lesson', 'title' => 'New lesson unlocked', 'body' => $l['title'] . ' · ' . $c['title'], 'url' => '/courses/' . $c['slug'] . '/lessons/' . $l['id'], 'dedupe_key' => 'unlock-' . $l['id']];}}
            if ($sendEmail) {
                $l = $fresh[0];
                $q = Courses::quote($c);
                $emails += (int)Mailer::queueTemplate('lesson_unlocked', $e['email'], $e['name'], ['course_title' => $c['title'], 'lesson_title' => count($fresh) > 1 ? $l['title'] . ' (+' . (count($fresh) - 1) . ' more)' : $l['title'], 'lesson_url' => url('/courses/' . $c['slug'] . '/lessons/' . $l['id'], [], true), 'quote' => $q ? $q['text'] . ($q['author'] ? ' - ' . $q['author'] : '') : '']);}}
        if ($notes) {
            Notifier::many($notes);}
        Settings::set(['cron_unlock_checked' => $now]);
        return count($notes) + $emails;}

    public static function expiryReminders(): int {
        $days = (int)Settings::get('expiry_reminder_days', 3);
        if ($days <= 0) {
            return 0;}
        $rows = DB::all("SELECT e.id, e.user_id, e.course_id, e.expires_at, u.name, u.email, c.title, c.slug FROM {enrollments} e JOIN {users} u ON u.id = e.user_id AND u.status = 'active' JOIN {courses} c ON c.id = e.course_id AND c.status = 'published' WHERE e.status = 'active' AND e.reminded_at IS NULL AND e.completed_at IS NULL AND e.expires_at > ? AND e.expires_at <= ? LIMIT 500", [now(), gmdate('Y-m-d H:i:s', time() + $days * 86400)]);
        foreach ($rows as $r) {
            $p = Progress::forCourses((int)$r['user_id'], [(int)$r['course_id']])[(int)$r['course_id']]['percent'] ?? 0;
            Mailer::queueTemplate('access_expiring', $r['email'], $r['name'], ['course_title' => $r['title'], 'course_url' => url('/courses/' . $r['slug'], [], true), 'expires_at' => dt($r['expires_at']), 'progress' => $p]);
            Notifier::notify((int)$r['user_id'], 'Access ending soon', 'Your access to ' . $r['title'] . ' ends ' . dt($r['expires_at']) . '.', '/courses/' . $r['slug'], 'access', 'expiry-' . $r['id']);
            DB::update('enrollments', ['reminded_at' => now()], 'id = ?', [$r['id']]);}
        return count($rows);}

    public static function cleanup(): int {
        $n = DB::run('DELETE FROM {password_tokens} WHERE expires_at < ?', [gmdate('Y-m-d H:i:s', time() - 86400)])->rowCount();
        $n += DB::run('DELETE FROM {throttle} WHERE created_at < ?', [gmdate('Y-m-d H:i:s', time() - 86400)])->rowCount();
        $n += DB::run("DELETE FROM {mail_queue} WHERE status = 'sent' AND sent_at < ?", [gmdate('Y-m-d H:i:s', time() - 90 * 86400)])->rowCount();
        foreach (glob(STORAGE_PATH . '/tmp/*') ?: [] as $f) {
            if (filemtime($f) < time() - 86400) {
                is_dir($f) ? self::rmdir($f) : @unlink($f);
                $n++;}}
        return $n;}

    public static function rmdir(string $dir): void {
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());}
        @rmdir($dir);}}
