<?php
namespace App\Core;

class Request {
    private ?string $path = null;

    public function method(): string {
        $m = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($m === 'POST' && isset($_POST['_method'])) {
            $o = strtoupper((string)$_POST['_method']);
            if (in_array($o, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $o;}}
        return $m;}

    public function isPost(): bool {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';}

    /** Request path relative to the install base, e.g. "/courses/abc". */
    public function path(): string {
        if ($this->path !== null) {
            return $this->path;}
        if (!empty($_SERVER['PATH_INFO'])) {
            $p = $_SERVER['PATH_INFO'];}
        else {
            $p = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
            $bp = base_path();
            if ($bp !== '' && strpos($p, $bp) === 0) {
                $p = substr($p, strlen($bp));}
            if (strpos($p, '/index.php') === 0) {
                $p = substr($p, 10);}}
        $p = '/' . trim($p, '/');
        return $this->path = $p;}

    public function input(string $key, $default = null) {
        return $_POST[$key] ?? $_GET[$key] ?? $default;}

    public function str(string $key, string $default = ''): string {
        $v = $this->input($key, $default);
        return is_string($v) ? trim($v) : $default;}

    public function int(string $key, int $default = 0): int {
        $v = $this->input($key, $default);
        return is_numeric($v) ? (int)$v : $default;}

    public function bool(string $key): bool {
        return !empty($this->input($key));}

    public function arr(string $key): array {
        $v = $this->input($key, []);
        return is_array($v) ? $v : [];}

    public function only(array $keys): array {
        $o = [];
        foreach ($keys as $k) {
            $v = $this->input($k, '');
            $o[$k] = is_string($v) ? trim($v) : $v;}
        return $o;}

    public function query(string $key, $default = null) {
        return $_GET[$key] ?? $default;}

    public function file(string $key): ?array {
        $f = $_FILES[$key] ?? null;
        return ($f && is_array($f) && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) ? $f : null;}

    public function header(string $name): ?string {
        $k = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$k] ?? null;}

    public function ip(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';}

    public function userAgent(): string {
        return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);}

    public function isAjax(): bool {
        return ($this->header('X-Requested-With') === 'XMLHttpRequest') || strpos((string)$this->header('Accept'), 'application/json') !== false;}

    public function isSecure(): bool {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') || (($_SERVER['SERVER_PORT'] ?? '') == 443);}

    public function json(): array {
        static $j = null;
        if ($j === null) {
            $j = json_decode((string)file_get_contents('php://input'), true) ?: [];}
        return $j;}}
