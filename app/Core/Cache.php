<?php
namespace App\Core;

/**
 * Tiny file cache for expensive aggregates (dashboard and report numbers).
 * Values are stored as PHP files so OPcache can serve repeat reads from memory.
 */
class Cache {
    private const DIR = STORAGE_PATH . '/cache/data';

    private static function file(string $key): string {
        return self::DIR . '/' . preg_replace('/[^a-z0-9_.-]/i', '_', $key) . '.php';}

    /** Returns [value, stored-at timestamp] or null when missing/expired. */
    public static function get(string $key, int $ttl): ?array {
        $f = self::file($key);
        $d = is_file($f) ? @include $f : null;
        return is_array($d) && ($d['t'] ?? 0) > time() - $ttl ? [$d['v'], $d['t']] : null;}

    public static function put(string $key, $value): void {
        if (!is_dir(self::DIR)) {
            @mkdir(self::DIR, 0755, true);}
        $f = self::file($key);
        $tmp = $f . '.' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmp, '<?php return ' . var_export(['t' => time(), 'v' => $value], true) . ';') !== false) {
            @rename($tmp, $f);
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($f, true);}}}

    /** Value from cache or computed by $fn (and stored). $age receives the value's age in seconds. */
    public static function remember(string $key, int $ttl, callable $fn, bool $fresh = false, ?int &$age = null) {
        if (!$fresh && ($hit = self::get($key, $ttl))) {
            $age = time() - $hit[1];
            return $hit[0];}
        $v = $fn();
        self::put($key, $v);
        $age = 0;
        return $v;}

    public static function forget(string $prefix = ''): void {
        foreach (glob(self::DIR . '/' . preg_replace('/[^a-z0-9_.-]/i', '_', $prefix) . '*.php') ?: [] as $f) {
            @unlink($f);}}}
