<?php
namespace App\Core;

class Logger {
    public static function write(string $channel, string $level, string $msg): void {
        $file = STORAGE_PATH . '/logs/' . $channel . '-' . gmdate('Y-m') . '.log';
        @file_put_contents($file, '[' . gmdate('Y-m-d H:i:s') . '] ' . strtoupper($level) . ': ' . $msg . "\n", FILE_APPEND | LOCK_EX);}

    public static function error(string $msg): void {
        self::write('app', 'error', $msg);}

    public static function info(string $msg): void {
        self::write('app', 'info', $msg);}}
