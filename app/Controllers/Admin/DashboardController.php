<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Cache;
use App\Core\DB;
use App\Core\Mailer;
use App\Core\Settings;

class DashboardController extends Controller {
    /** Fill a date => value map into an ordered series for the last $days days (UTC). */
    private static function series(array $map, int $days): array {
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = gmdate('Y-m-d', time() - $i * 86400);
            $out[$d] = (float)($map[$d] ?? 0);}
        return $out;}

    private static function change(array $series): ?int {
        $v = array_values($series);
        $n = count($v);
        $cur = array_sum(array_slice($v, $n - 7));
        $prev = array_sum(array_slice($v, $n - 14, 7));
        if ($prev <= 0) {
            return $cur > 0 ? 100 : null;}
        return (int)round(($cur - $prev) / $prev * 100);}

    /**
     * Heavy aggregates (KPIs, 30-day series, top courses), cached for 5 minutes and refreshed in the background
     * by the scheduler so the page itself rarely waits on them.
     */
    public static function metrics(bool $fresh = false, ?int &$age = null): array {
        return Cache::remember('dashboard', 300, function () {
            $now = now();
            $since = gmdate('Y-m-d 00:00:00', time() - 29 * 86400);
            $week = gmdate('Y-m-d H:i:s', time() - 7 * 86400);
            $s = DB::one("SELECT
                (SELECT COUNT(*) FROM {users} WHERE role = 'student') AS students,
                (SELECT COUNT(*) FROM {users} WHERE role = 'student' AND created_at > ?) AS new_students,
                (SELECT COUNT(*) FROM {users} WHERE role = 'student' AND password_hash IS NULL AND status = 'active') AS pending_setup,
                (SELECT COUNT(*) FROM {enrollments} WHERE status = 'active' AND (expires_at IS NULL OR expires_at > ?)) AS active_enrollments,
                (SELECT COUNT(*) FROM {enrollments} WHERE status = 'active' AND expires_at > ? AND expires_at <= ?) AS expiring,
                (SELECT COUNT(*) FROM {courses} WHERE status = 'published') AS courses,
                (SELECT COUNT(*) FROM {courses} WHERE status = 'published' AND is_public = 1) AS public_courses,
                (SELECT COUNT(*) FROM {lessons}) AS lessons,
                (SELECT COUNT(*) FROM {enrollments}) AS enrollments,
                (SELECT COUNT(DISTINCT user_id) FROM {lesson_progress} WHERE last_viewed_at > ?) AS active_learners,
                (SELECT COUNT(*) FROM {orders} WHERE status = 'submitted') AS orders_submitted,
                (SELECT COALESCE(SUM(amount), 0) FROM {orders} WHERE status = 'paid' AND paid_at >= ?) AS revenue_month,
                (SELECT COALESCE(SUM(amount), 0) FROM {orders} WHERE status = 'paid' AND paid_at >= ? AND paid_at < ?) AS revenue_prev,
                (SELECT COUNT(*) FROM {mail_queue} WHERE status = 'failed') AS mail_failed,
                (SELECT COUNT(*) FROM {mail_queue} WHERE status = 'pending') AS mail_pending", [
                $week, $now, $now, gmdate('Y-m-d H:i:s', time() + 7 * 86400), $week, gmdate('Y-m-01 00:00:00'),
                gmdate('Y-m-01 00:00:00', strtotime('first day of last month')), gmdate('Y-m-01 00:00:00')]);
            // 30-day daily series (one grouped query each).
            $newStudents = self::series(DB::pairs("SELECT DATE(created_at) d, COUNT(*) FROM {users} WHERE role = 'student' AND created_at >= ? GROUP BY d", [$since]), 30);
            $enrolls = self::series(DB::pairs('SELECT DATE(created_at) d, COUNT(*) FROM {enrollments} WHERE created_at >= ? GROUP BY d', [$since]), 30);
            $completions = self::series(DB::pairs('SELECT DATE(completed_at) d, COUNT(*) FROM {lesson_progress} WHERE completed_at >= ? GROUP BY d', [$since]), 30);
            $learners = self::series(DB::pairs('SELECT DATE(last_viewed_at) d, COUNT(DISTINCT user_id) FROM {lesson_progress} WHERE last_viewed_at >= ? GROUP BY d', [$since]), 30);
            $revenue = self::series(DB::pairs("SELECT DATE(paid_at) d, SUM(amount) FROM {orders} WHERE status = 'paid' AND paid_at >= ? GROUP BY d", [$since]), 30);
            $kpis = [
                ['Students', number_format((int)$s['students']), '+' . (int)$s['new_students'] . ' this week', self::change($newStudents), $newStudents, 'users', 'yellow', '/admin/students'],
                ['Learners · 7 days', number_format((int)$s['active_learners']), number_format((int)$s['active_enrollments']) . ' active enrollments', self::change($learners), $learners, 'activity', 'blue', '/admin/reports'],
                ['Revenue · month', money($s['revenue_month']), 'Last month ' . money($s['revenue_prev']), (float)$s['revenue_prev'] > 0 ? (int)round(((float)$s['revenue_month'] - (float)$s['revenue_prev']) / (float)$s['revenue_prev'] * 100) : null, $revenue, 'key', 'green', '/admin/orders'],
                ['Completions · 7 days', number_format(array_sum(array_slice(array_values($completions), -7))), number_format((int)$s['lessons']) . ' lessons in ' . (int)$s['courses'] . ' courses', self::change($completions), $completions, 'check-circle', 'purple', '/admin/reports'],];
            // 29 points keeps the evenly spaced x-axis labels from colliding with the final label.
            $chart = ['labels' => array_map(fn($d) => dt($d . ' 12:00:00', 'M j'), array_slice(array_keys($enrolls), 1)), 'enrolls' => array_slice(array_values($enrolls), 1), 'completions' => array_slice(array_values($completions), 1)];
            $top = DB::all("SELECT c.id, c.title, c.slug, c.cover_image, c.color, c.illustration, c.status,
                (SELECT COUNT(*) FROM {enrollments} e WHERE e.course_id = c.id) AS enrolled,
                (SELECT COUNT(*) FROM {enrollments} e WHERE e.course_id = c.id AND e.completed_at IS NOT NULL) AS completed,
                (SELECT COUNT(*) FROM {lessons} l WHERE l.course_id = c.id AND l.status = 'published') AS lesson_count,
                (SELECT COALESCE(SUM(e.lessons_done), 0) FROM {enrollments} e WHERE e.course_id = c.id) AS done_lessons
                FROM {courses} c WHERE c.status <> 'archived' ORDER BY enrolled DESC, c.id DESC LIMIT 5");
            return compact('s', 'kpis', 'chart', 'top');}, $fresh, $age);}

    public function index() {
        $m = self::metrics($this->req->bool('fresh'), $age);
        extract($m);
        // Action items stay live: they change when the admin acts on them.
        $s = array_merge($s, DB::one("SELECT (SELECT COUNT(*) FROM {orders} WHERE status = 'submitted') AS orders_submitted, (SELECT COUNT(*) FROM {mail_queue} WHERE status = 'failed') AS mail_failed, (SELECT COUNT(*) FROM {mail_queue} WHERE status = 'pending') AS mail_pending") ?: []);
        // Needs attention
        try {
            $driver = (string)(Mailer::config()['driver'] ?? 'log');}
        catch (\Throwable $e) {
            $driver = 'log';}
        $cronLast = (string)Settings::get('cron_last_run', '');
        $upd = Settings::get('update_last_result');
        $attention = [];
        if ((int)$s['orders_submitted'] && Auth::can('orders.manage')) {
            $attention[] = ['key', 'orange', (int)$s['orders_submitted'] . ' payment' . ((int)$s['orders_submitted'] === 1 ? '' : 's') . ' to verify', 'Students submitted UPI references.', '/admin/orders?status=submitted', 'Verify'];}
        if ($driver === 'log' && Auth::can('mail.configure')) {
            $attention[] = ['mail', 'pink', 'Emails are not being delivered', 'Mail driver is "log". Connect an SMTP or API provider.', '/admin/settings/mail', 'Set up'];}
        if ((int)$s['mail_failed']) {
            $attention[] = ['alert', 'pink', (int)$s['mail_failed'] . ' email' . ((int)$s['mail_failed'] === 1 ? '' : 's') . ' failed', 'Check your email delivery settings, then retry.', '/admin/mail-log?status=failed', 'Review'];}
        if ((int)$s['pending_setup']) {
            $attention[] = ['user', 'blue', (int)$s['pending_setup'] . ' student' . ((int)$s['pending_setup'] === 1 ? '' : 's') . ' haven\'t activated', 'They never set a password. Resend the setup email.', '/admin/students?status=pending', 'View'];}
        if ((int)$s['expiring']) {
            $attention[] = ['calendar', 'purple', (int)$s['expiring'] . ' enrollment' . ((int)$s['expiring'] === 1 ? '' : 's') . ' expiring this week', 'Extend access or follow up with an offer.', '/admin/enrollments?state=expiring', 'Review'];}
        if (is_array($upd) && !empty($upd['available']) && Auth::can('updates.run')) {
            $attention[] = ['refresh', 'green', 'Update available: ' . ($upd['latest'] ?? ''), 'A new version of Zeroes is ready to install.', '/admin/system', 'Update'];}
        // Setup checklist
        $firstStudent = (bool)DB::val("SELECT 1 FROM {enrollments} e JOIN {users} u ON u.id = e.user_id AND u.role = 'student' LIMIT 1");
        $checklist = [
            ['Upload your logo', (bool)Settings::get('logo'), '/admin/settings/branding'],
            ['Connect email delivery', $driver !== 'log', '/admin/settings/mail'],
            ['Add your UPI ID', trim((string)Settings::get('upi_vpa', '')) !== '', '/admin/settings/payments'],
            ['Publish a course', (int)$s['courses'] > 0, '/admin/courses'],
            ['Share a public course link', (int)$s['public_courses'] > 0, '/admin/courses'],
            ['Enroll your first student', $firstStudent, '/admin/students/create'],];
        $orders = DB::all('SELECT STRAIGHT_JOIN o.id, o.ref, o.amount, o.currency, o.status, o.created_at, u.name, c.title FROM {orders} o JOIN {users} u ON u.id = o.user_id JOIN {courses} c ON c.id = o.course_id ORDER BY o.id DESC LIMIT 5');
        $recentStudents = DB::all("SELECT id, name, email, avatar, created_at, password_hash IS NOT NULL AS activated, last_login_at FROM {users} WHERE role = 'student' ORDER BY id DESC LIMIT 5");
        $activity = DB::all('SELECT a.*, u.name FROM {audit_logs} a LEFT JOIN {users} u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 7');
        $share = DB::one("SELECT slug, title FROM {courses} WHERE status = 'published' AND is_public = 1 ORDER BY id DESC LIMIT 1");
        return view('admin/dashboard', ['age' => $age, 's' => $s, 'kpis' => $kpis, 'chart' => $chart, 'attention' => $attention, 'checklist' => $checklist, 'top' => $top, 'orders' => $orders,
            'recentStudents' => $recentStudents, 'activity' => $activity, 'share' => $share, 'cronLast' => $cronLast]);}}
