<?php
namespace App\Core;

/**
 * Runs PHP migration files (NNN_name.php) that return an array of SQL statements or a callable.
 * Applied migrations are recorded per scope in the migrations table.
 */
class Migrator {
    public static function ensureTable(): void {
        DB::run('CREATE TABLE IF NOT EXISTS {migrations} (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, scope VARCHAR(80) NOT NULL, migration VARCHAR(190) NOT NULL, ran_at DATETIME NOT NULL, UNIQUE KEY uq_mig (scope, migration)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');}

    public static function pending(string $dir = BASE_PATH . '/database/migrations', string $scope = 'core'): array {
        self::ensureTable();
        $done = DB::col('SELECT migration FROM {migrations} WHERE scope = ?', [$scope]);
        $files = glob(rtrim($dir, '/') . '/*.php') ?: [];
        sort($files, SORT_NATURAL);
        return array_values(array_filter($files, fn($f) => !in_array(basename($f, '.php'), $done, true)));}

    public static function run(string $dir = BASE_PATH . '/database/migrations', string $scope = 'core'): array {
        $ran = [];
        foreach (self::pending($dir, $scope) as $file) {
            $m = require $file;
            if (is_callable($m)) {
                $m();}
            else {
                foreach ((array)$m as $sql) {
                    DB::run($sql);}}
            DB::insert('migrations', ['scope' => $scope, 'migration' => basename($file, '.php'), 'ran_at' => now()]);
            $ran[] = basename($file, '.php');}
        return $ran;}}
