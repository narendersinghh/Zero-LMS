<?php
namespace App\Core;

use PDO;

/**
 * Thin PDO wrapper. Table names are written as {table} and receive the configured DB_PREFIX.
 */
class DB {
    /** Set to [] to record [ms, sql] for each query (development profiling). */
    public static ?array $profile = null;
    private static ?PDO $pdo = null;
    private static string $prefix = '';
    public static int $queries = 0;

    public static function connect(?array $cfg = null): PDO {
        $cfg = $cfg ?? [
            'host' => Env::get('DB_HOST', 'localhost'), 'port' => Env::get('DB_PORT', '3306'), 'name' => Env::get('DB_NAME', ''),
            'user' => Env::get('DB_USER', ''), 'pass' => Env::get('DB_PASS', ''), 'prefix' => Env::get('DB_PREFIX', 'zx_'),];
        $dsn = 'mysql:host=' . $cfg['host'] . ';port=' . (int)($cfg['port'] ?: 3306) . ';dbname=' . $cfg['name'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_STRINGIFY_FETCHES => false,]);
        $pdo->exec("SET time_zone = '+00:00', sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
        self::$pdo = $pdo;
        self::$prefix = (string)$cfg['prefix'];
        return $pdo;}

    public static function pdo(): PDO {
        return self::$pdo ?? self::connect();}

    public static function prefix(): string {
        if (self::$pdo === null) {
            self::pdo();}
        return self::$prefix;}

    public static function sql(string $sql): string {
        $p = self::prefix();
        return preg_replace_callback('/\{([a-z0-9_]+)\}/', fn($m) => '`' . $p . $m[1] . '`', $sql);}

    public static function table(string $t): string {
        return '`' . self::prefix() . $t . '`';}

    public static function run(string $sql, array $params = []): \PDOStatement {
        self::$queries++;
        if (self::$profile === null && PHP_SAPI === "cli-server" && getenv("ZX_PROFILE")) {
            self::$profile = [];}
        $t = self::$profile !== null ? microtime(true) : 0;
        $st = self::pdo()->prepare(self::sql($sql));
        $st->execute(array_values($params));
        if (self::$profile !== null) {
            self::$profile[] = [round((microtime(true) - $t) * 1000, 2), preg_replace('/\s+/', ' ', substr($sql, 0, 160))];}
        return $st;}

    public static function all(string $sql, array $params = []): array {
        return self::run($sql, $params)->fetchAll();}

    public static function one(string $sql, array $params = []): ?array {
        $r = self::run($sql, $params)->fetch();
        return $r === false ? null : $r;}

    public static function val(string $sql, array $params = []) {
        $r = self::run($sql, $params)->fetchColumn();
        return $r === false ? null : $r;}

    public static function col(string $sql, array $params = []): array {
        return self::run($sql, $params)->fetchAll(PDO::FETCH_COLUMN);}

    /** Key/value pairs from the first two columns. */
    public static function pairs(string $sql, array $params = []): array {
        return self::run($sql, $params)->fetchAll(PDO::FETCH_KEY_PAIR);}

    public static function insert(string $table, array $data): int {
        $cols = array_keys($data);
        $sql = 'INSERT INTO ' . self::table($table) . ' (`' . implode('`,`', $cols) . '`) VALUES (' . rtrim(str_repeat('?,', count($cols)), ',') . ')';
        self::$queries++;
        self::pdo()->prepare($sql)->execute(array_values($data));
        return (int)self::pdo()->lastInsertId();}

    /** Multi-row insert. $rows = list of assoc arrays with identical keys. */
    public static function insertMany(string $table, array $rows, string $suffix = ''): int {
        if (!$rows) {
            return 0;}
        $cols = array_keys($rows[0]);
        $ph = '(' . rtrim(str_repeat('?,', count($cols)), ',') . ')';
        $n = 0;
        foreach (array_chunk($rows, 300) as $chunk) {
            $vals = [];
            foreach ($chunk as $r) {
                foreach ($cols as $c) {
                    $vals[] = $r[$c];}}
            $sql = 'INSERT ' . ($suffix === 'IGNORE' ? 'IGNORE ' : '') . 'INTO ' . self::table($table) . ' (`' . implode('`,`', $cols) . '`) VALUES ' . rtrim(str_repeat($ph . ',', count($chunk)), ',') . ($suffix && $suffix !== 'IGNORE' ? ' ' . $suffix : '');
            self::$queries++;
            $st = self::pdo()->prepare($sql);
            $st->execute($vals);
            $n += $st->rowCount();}
        return $n;}

    public static function update(string $table, array $data, string $where, array $params = []): int {
        $set = implode(',', array_map(fn($c) => '`' . $c . '`=?', array_keys($data)));
        return self::run('UPDATE ' . self::table($table) . ' SET ' . $set . ' WHERE ' . $where, array_merge(array_values($data), $params))->rowCount();}

    public static function delete(string $table, string $where, array $params = []): int {
        return self::run('DELETE FROM ' . self::table($table) . ' WHERE ' . $where, $params)->rowCount();}

    /** Placeholder list for IN (...) clauses. */
    public static function in(array $values): string {
        return $values ? rtrim(str_repeat('?,', count($values)), ',') : 'NULL';}

    public static function transaction(callable $cb) {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $r = $cb();
            $pdo->commit();
            return $r;}
        catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();}
            throw $e;}}

    /** Escape a value for LIKE patterns. */
    public static function like(string $s): string {
        return '%' . addcslashes($s, '%_\\') . '%';}}
