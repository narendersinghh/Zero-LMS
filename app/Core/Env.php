<?php
namespace App\Core;

/** Minimal .env reader/writer (KEY=VALUE per line). */
class Env {
    private static array $vars = [];

    public static function load(string $file): bool {
        if (!is_file($file)) {
            return false;}
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;}
            [$k, $v] = explode('=', $line, 2);
            $v = trim($v);
            if (strlen($v) > 1 && $v[0] === '"' && substr($v, -1) === '"') {
                $v = stripcslashes(substr($v, 1, -1));}
            self::$vars[trim($k)] = $v;}
        return true;}

    public static function get(string $key, $default = null) {
        return self::$vars[$key] ?? $default;}

    public static function bool(string $key, bool $default = false): bool {
        $v = self::$vars[$key] ?? null;
        return $v === null ? $default : in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);}

    public static function set(string $key, $value): void {
        self::$vars[$key] = (string)$value;}

    public static function all(): array {
        return self::$vars;}

    /** Write (merge) variables to the .env file. */
    public static function write(string $file, array $vars): bool {
        $all = array_merge(self::$vars, $vars);
        $out = "# Zeroes LMS environment configuration\n# Generated " . gmdate('Y-m-d H:i:s') . " UTC. Keep this file private.\n";
        foreach ($all as $k => $v) {
            $out .= $k . '="' . addcslashes((string)$v, "\"\\\n\r") . "\"\n";}
        $ok = file_put_contents($file, $out, LOCK_EX) !== false;
        if ($ok) {
            @chmod($file, 0640);
            self::$vars = $all;}
        return $ok;}}
