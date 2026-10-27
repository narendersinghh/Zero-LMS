<?php
namespace App\Core;

/** Installation helpers shared by the web installer and the CLI (tools/install.php). */
class Installer {
    public static function requirements(): array {
        $ext = fn($e) => extension_loaded($e);
        return [
            ['PHP 8.0 or newer', PHP_VERSION_ID >= 80000, PHP_VERSION, true],
            ['PDO MySQL extension', $ext('pdo_mysql'), '', true],
            ['OpenSSL extension', $ext('openssl'), '', true],
            ['Mbstring extension', $ext('mbstring'), '', true],
            ['Fileinfo extension', $ext('fileinfo'), '', true],
            ['JSON extension', function_exists('json_encode'), '', true],
            ['DOM / libxml extension', class_exists('DOMDocument'), '', true],
            ['cURL extension (updates)', $ext('curl'), '', false],
            ['Zip extension (updates & backups)', class_exists('ZipArchive'), '', false],
            ['GD extension (images)', $ext('gd'), '', false],
            ['Root folder writable (.env)', is_writable(BASE_PATH), '', true],
            ['storage/ writable', is_writable(STORAGE_PATH), '', true],
            ['uploads/ writable', is_writable(UPLOAD_PATH), '', true],
            ['upload_max_filesize', true, ini_get('upload_max_filesize') . ' (large videos use chunked upload)', false],];}

    public static function requirementsMet(): bool {
        foreach (self::requirements() as $r) {
            if ($r[3] && !$r[1]) {
                return false;}}
        return true;}

    public static function testDb(array $c): ?string {
        try {
            DB::connect($c);
            DB::pdo()->query('SELECT 1');
            $v = (string)DB::val('SELECT VERSION()');
            if (stripos($v, 'mariadb') === false && version_compare($v, '5.7.0', '<')) {
                return 'MySQL 5.7+ or MariaDB 10.2+ is required (found ' . $v . ').';}
            return null;}
        catch (\Throwable $e) {
            return $e->getMessage();}}

    public static function writeEnv(array $db, string $url, array $extra = []): bool {
        $vars = array_merge([
            'APP_URL' => rtrim($url, '/'), 'APP_KEY' => 'base64:' . base64_encode(random_bytes(32)), 'APP_DEBUG' => 'false', 'APP_PRETTY_URLS' => 'true',
            'DB_HOST' => $db['host'], 'DB_PORT' => (string)$db['port'], 'DB_NAME' => $db['name'], 'DB_USER' => $db['user'], 'DB_PASS' => $db['pass'], 'DB_PREFIX' => $db['prefix'],
            'SESSION_NAME' => 'zeroes_sid', 'SESSION_LIFETIME' => '720', 'MAIL_DRIVER' => 'log',], $extra);
        foreach ($vars as $k => $v) {
            Env::set($k, $v);}
        return Env::write(BASE_PATH . '/.env', []);}

    /** Create schema, default settings, email templates and the super admin. */
    public static function install(array $admin, array $site, bool $demo = false): void {
        Migrator::run();
        $s = ['site_name' => $site['site_name'] ?: 'Zeroes', 'timezone' => $site['timezone'] ?: 'UTC', 'cron_secret' => bin2hex(random_bytes(16)), 'cron_unlock_checked' => time(), 'installed_at' => now()];
        if (!empty($site['mail_from'])) {
            $s['mail_from_email'] = $site['mail_from'];}
        Settings::clearCache();
        Settings::set($s);
        self::seedTemplates();
        $existing = DB::one('SELECT id FROM {users} WHERE email = ?', [strtolower($admin['email'])]);
        if ($existing) {
            DB::update('users', ['role' => 'super_admin', 'name' => $admin['name'], 'password_hash' => password_hash($admin['password'], PASSWORD_DEFAULT), 'status' => 'active', 'password_set_at' => now()], 'id = ?', [$existing['id']]);}
        else {
            DB::insert('users', ['role' => 'super_admin', 'name' => $admin['name'], 'email' => strtolower($admin['email']), 'password_hash' => password_hash($admin['password'], PASSWORD_DEFAULT), 'status' => 'active', 'password_set_at' => now(), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);}
        if ($demo) {
            self::demo();}
        file_put_contents(STORAGE_PATH . '/installed.lock', now());}

    public static function seedTemplates(): void {
        foreach (Defaults::emailTemplates() as $key => $t) {
            DB::run('INSERT IGNORE INTO {email_templates} (`key`, name, subject, body, enabled, updated_at) VALUES (?,?,?,?,1,?)', [$key, $t['name'], $t['subject'], $t['body'], now()]);}}

    public static function demo(): void {
        $cid = DB::insert('courses', ['title' => '21-Day Growth Challenge', 'slug' => 'growth-challenge', 'subtitle' => 'A daily video lesson to build momentum — one small step every day.', 'description' => '<p>Welcome! A new lesson unlocks every morning. Watch it, take one action, and mark it complete.</p>',
            'category' => 'Webinar bonus', 'instructor_name' => 'Zeroes Team', 'status' => 'published', 'access_days' => 60, 'release_time' => '06:00:00', 'quotes' => "Small steps every day add up to big results.\nYou don't have to be great to start, but you have to start to be great. — Zig Ziglar", 'created_at' => now(), 'updated_at' => now()]);
        $lessons = [];
        for ($w = 1; $w <= 3; $w++) {
            $mid = DB::insert('modules', ['course_id' => $cid, 'title' => 'Week ' . $w, 'description' => ['Foundations', 'Momentum', 'Mastery'][$w - 1], 'sort_order' => $w, 'created_at' => now(), 'updated_at' => now()]);
            for ($d = 1; $d <= 3; $d++) {
                $day = ($w - 1) * 7 + $d;
                $lessons[] = ['course_id' => $cid, 'module_id' => $mid, 'title' => 'Day ' . $day . ': ' . ['Set your intention', 'Build the habit', 'Remove friction', 'Track progress', 'Find accountability', 'Level up', 'Teach to learn', 'Review & reflect', 'Plan what\'s next'][($w - 1) * 3 + $d - 1],
                    'type' => 'text', 'summary' => 'A short, practical lesson with one action step.', 'content' => '<h2>Today\'s focus</h2><p>Replace this demo text with your video lesson. Upload videos in <strong>Admin → Videos</strong> and attach them to lessons.</p><ul><li>Watch the lesson</li><li>Complete the action step</li><li>Mark it complete</li></ul>',
                    'release_day' => $day, 'status' => 'published', 'sort_order' => $day, 'duration' => 600, 'created_at' => now(), 'updated_at' => now()];}}
        DB::insertMany('lessons', $lessons);}}
