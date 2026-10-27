<?php
namespace App\Core;

/** DB backed rate limiter (works on any shared host). */
class Throttle {
    public static function tooMany(string $key, int $max, int $minutes): bool {
        $since = gmdate('Y-m-d H:i:s', time() - $minutes * 60);
        return (int)DB::val('SELECT COUNT(*) FROM {throttle} WHERE `key` = ? AND created_at > ?', [hash('sha256', $key), $since]) >= $max;}

    public static function hit(string $key): void {
        DB::insert('throttle', ['key' => hash('sha256', $key), 'created_at' => now()]);
        if (mt_rand(1, 50) === 1) {
            DB::run('DELETE FROM {throttle} WHERE created_at < ?', [gmdate('Y-m-d H:i:s', time() - 86400)]);}}

    public static function clear(string $key): void {
        DB::run('DELETE FROM {throttle} WHERE `key` = ?', [hash('sha256', $key)]);}}
