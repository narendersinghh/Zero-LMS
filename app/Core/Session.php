<?php
namespace App\Core;

/** Hardened native session with flash data support. */
class Session {
    private static bool $started = false;

    public static function start(): void {
        if (self::$started || PHP_SAPI === 'cli') {
            self::$started = true;
            if (!isset($_SESSION)) {
                $_SESSION = [];}
            return;}
        $secure = App::instance()->request()->isSecure();
        $dir = STORAGE_PATH . '/sessions';
        if (is_dir($dir) && is_writable($dir)) {
            session_save_path($dir);
            ini_set('session.gc_probability', '1');
            ini_set('session.gc_divisor', '200');}
        $lifetime = max(15, (int)Env::get('SESSION_LIFETIME', 720)) * 60;
        ini_set('session.gc_maxlifetime', (string)$lifetime);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
        session_name(Env::get('SESSION_NAME', 'zeroes_sid'));
        session_set_cookie_params(['lifetime' => 0, 'path' => (base_path() ?: '/'), 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
        self::$started = true;
        // Idle timeout
        $last = $_SESSION['_last'] ?? time();
        if (time() - $last > $lifetime) {
            $_SESSION = [];
            session_regenerate_id(true);}
        $_SESSION['_last'] = time();
        // Age flash data: values set during the previous request become "now" values.
        $_SESSION['_flash_now'] = $_SESSION['_flash'] ?? [];
        $_SESSION['_errors_now'] = $_SESSION['_errors'] ?? [];
        $_SESSION['_old_now'] = $_SESSION['_old'] ?? [];
        unset($_SESSION['_flash'], $_SESSION['_errors'], $_SESSION['_old']);}

    public static function get(string $k, $default = null) {
        return $_SESSION[$k] ?? $default;}

    public static function set(string $k, $v): void {
        $_SESSION[$k] = $v;}

    public static function forget(string $k): void {
        unset($_SESSION[$k]);}

    public static function pull(string $k, $default = null) {
        $v = $_SESSION[$k] ?? $default;
        unset($_SESSION[$k]);
        return $v;}

    public static function flash(string $type, string $msg): void {
        $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];}

    public static function flashes(): array {
        return $_SESSION['_flash_now'] ?? [];}

    public static function regenerate(): void {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);}}

    public static function destroy(): void {
        $_SESSION = [];
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
            session_destroy();}}

    /** Release the session lock (e.g. before long streaming responses). */
    public static function close(): void {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();}}

    public static function id(): string {
        return PHP_SAPI === 'cli' ? 'cli' : (string)session_id();}}
