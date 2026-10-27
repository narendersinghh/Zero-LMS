<?php
namespace App\Core;

/** Key/value settings stored in the DB and cached as a PHP file (0 queries per request when warm). */
class Settings {
    private static ?array $data = null;
    private const CACHE = STORAGE_PATH . '/cache/settings.php';

    public static function load(): void {
        if (self::$data !== null) {
            return;}
        if (is_file(self::CACHE)) {
            $d = @include self::CACHE;
            if (is_array($d)) {
                self::$data = $d;
                return;}}
        self::$data = [];
        try {
            foreach (DB::all('SELECT `key`, `value` FROM {settings}') as $r) {
                $v = json_decode((string)$r['value'], true);
                self::$data[$r['key']] = json_last_error() === JSON_ERROR_NONE ? $v : $r['value'];}
            self::writeCache();}
        catch (\Throwable $e) {
            Logger::error('Settings load failed: ' . $e->getMessage());}}

    public static function get(string $key, $default = null) {
        if (self::$data === null) {
            if (!App::installed()) {
                return Defaults::settings()[$key] ?? $default;}
            self::load();}
        if (array_key_exists($key, self::$data)) {
            return self::$data[$key];}
        $d = Defaults::settings();
        return array_key_exists($key, $d) ? $d[$key] : $default;}

    public static function all(): array {
        self::load();
        return array_merge(Defaults::settings(), self::$data);}

    public static function set(array $values): void {
        self::load();
        $rows = [];
        foreach ($values as $k => $v) {
            $rows[] = ['key' => $k, 'value' => json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)];
            self::$data[$k] = $v;}
        DB::insertMany('settings', $rows, 'ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
        self::writeCache();}

    public static function clearCache(): void {
        @unlink(self::CACHE);
        self::$data = null;}

    private static function writeCache(): void {
        $tmp = self::CACHE . '.' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmp, '<?php return ' . var_export(self::$data, true) . ';', LOCK_EX) !== false) {
            @rename($tmp, self::CACHE);
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate(self::CACHE, true);}}}}
