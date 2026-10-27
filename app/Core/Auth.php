<?php
namespace App\Core;

/**
 * Session authentication + role based access control.
 * Roles: super_admin, admin, student. Modules may register additional permissions via the "auth.permissions" filter.
 */
class Auth {
    private static $user = false;
    public const ROLES = ['super_admin' => 'Super Admin', 'admin' => 'Admin', 'student' => 'Student'];

    public static function permissions(): array {
        static $p = null;
        if ($p === null) {
            $admin = ['admin.access', 'courses.manage', 'videos.manage', 'students.manage', 'enrollments.manage', 'imports.run', 'reports.view', 'emails.manage', 'branding.manage', 'logs.view', 'courses.preview'];
            $super = array_merge($admin, ['courses.delete', 'admins.manage', 'settings.system', 'updates.run', 'modules.manage', 'mail.configure']);
            $p = Hooks::filter('auth.permissions', ['super_admin' => $super, 'admin' => $admin, 'student' => ['learn']]);}
        return $p;}

    public static function user(): ?array {
        if (self::$user !== false) {
            return self::$user;}
        self::$user = null;
        $id = (int)($_SESSION['_uid'] ?? 0);
        if (!$id || !App::installed()) {
            return null;}
        $u = DB::one('SELECT * FROM {users} WHERE id = ? LIMIT 1', [$id]);
        $fp = self::fingerprint();
        if (!$u || $u['status'] !== 'active' || (int)$u['session_version'] !== (int)($_SESSION['_sv'] ?? -1) || !hash_equals((string)($_SESSION['_fp'] ?? ''), $fp)) {
            unset($_SESSION['_uid'], $_SESSION['_sv'], $_SESSION['_fp']);
            return null;}
        return self::$user = $u;}

    public static function check(): bool {
        return self::user() !== null;}

    public static function id(): int {
        return (int)(self::user()['id'] ?? 0);}

    public static function role(): string {
        return (string)(self::user()['role'] ?? 'guest');}

    public static function isStaff(): bool {
        return in_array(self::role(), ['super_admin', 'admin'], true);}

    public static function isSuper(): bool {
        return self::role() === 'super_admin';}

    public static function can(string $perm): bool {
        $role = self::role();
        return in_array($perm, self::permissions()[$role] ?? [], true);}

    public static function fingerprint(): string {
        // Bound to the user agent family to make stolen session cookies harder to reuse.
        $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        $ua = preg_replace('/[\d.]+/', '', $ua);
        return hash('sha256', $ua . '|' . Env::get('APP_KEY', ''));}

    public static function login(array $user): void {
        Session::regenerate();
        $_SESSION['_uid'] = (int)$user['id'];
        $_SESSION['_sv'] = (int)$user['session_version'];
        $_SESSION['_fp'] = self::fingerprint();
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        self::$user = $user;
        DB::update('users', ['last_login_at' => now(), 'last_login_ip' => App::instance()->request()->ip()], 'id = ?', [$user['id']]);
        Audit::log('auth.login', 'user', (int)$user['id']);
        Hooks::fire('auth.login', $user);}

    public static function logout(): void {
        if (self::check()) {
            Audit::log('auth.logout', 'user', self::id());}
        self::$user = null;
        Session::destroy();}

    public static function attempt(string $email, string $password): ?array {
        $u = DB::one('SELECT * FROM {users} WHERE email = ? LIMIT 1', [strtolower(trim($email))]);
        if (!$u || !$u['password_hash'] || !password_verify($password, $u['password_hash'])) {
            // Equalise timing to avoid user enumeration.
            if (!$u) {
                password_hash($password, PASSWORD_DEFAULT);}
            return null;}
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            DB::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$u['id']]);}
        return $u;}

    /** Refresh cached user (after updates). */
    public static function refresh(): void {
        self::$user = false;}

    /** Invalidate all sessions of a user (password change, suspension). */
    public static function bumpSessionVersion(int $userId): void {
        DB::run('UPDATE {users} SET session_version = session_version + 1 WHERE id = ?', [$userId]);
        if ($userId === self::id()) {
            $_SESSION['_sv'] = (int)DB::val('SELECT session_version FROM {users} WHERE id = ?', [$userId]);
            self::$user = false;}}}
