<?php
use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\HttpException;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;

function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');}

function app(): App {
    return App::instance();}

function env(string $key, $default = null) {
    return Env::get($key, $default);}

/** Base path of the install (e.g. "" or "/lms"). */
function base_path(): string {
    static $bp = null;
    if ($bp === null) {
        $u = Env::get('APP_URL');
        if ($u) {
            $bp = rtrim((string)parse_url($u, PHP_URL_PATH), '/');}
        else {
            $bp = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');}
        if ($bp === '.') {
            $bp = '';}}
    return $bp;}

function base_url(): string {
    static $b = null;
    if ($b === null) {
        $u = Env::get('APP_URL');
        if ($u) {
            $b = rtrim($u, '/');}
        else {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
            $b = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path();}}
    return $b;}

function url(string $path = '', array $query = [], bool $absolute = false): string {
    $path = '/' . ltrim($path, '/');
    $prefix = Env::bool('APP_PRETTY_URLS', true) ? '' : '/index.php';
    $u = ($absolute ? base_url() : base_path()) . ($path === '/' && !$prefix ? '/' : $prefix . ($path === '/' ? '' : $path));
    if ($query) {
        $u .= '?' . http_build_query($query);}
    return $u;}

function asset(string $path): string {
    $file = BASE_PATH . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? substr(base_convert((string)filemtime($file), 10, 36), -6) : '1';
    return base_path() . '/assets/' . ltrim($path, '/') . '?v=' . $v;}

function upload_url(?string $path): string {
    return $path ? base_path() . '/uploads/' . ltrim($path, '/') : '';}

function setting(string $key, $default = null) {
    return Settings::get($key, $default);}

function csrf_token(): string {
    return Csrf::token();}

function csrf_field(): string {
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';}

function method_field(string $m): string {
    return '<input type="hidden" name="_method" value="' . e(strtoupper($m)) . '">';}

function old(string $key, $default = '') {
    $old = Session::get('_old_now', []);
    return $old[$key] ?? $default;}

function field_error(string $key): string {
    $errs = Session::get('_errors_now', []);
    return isset($errs[$key]) ? '<div class="field-error">' . e($errs[$key]) . '</div>' : '';}

function has_error(string $key): bool {
    return isset(Session::get('_errors_now', [])[$key]);}

function auth(): ?array {
    return Auth::user();}

function can(string $perm): bool {
    return Auth::can($perm);}

function flash(string $type, string $message): void {
    Session::flash($type, $message);}

function redirect(string $to, int $code = 302) {
    if (!preg_match('#^https?://#', $to)) {
        $to = url($to);}
    header('Location: ' . $to, true, $code);
    exit;}

function back(array $errors = [], bool $withInput = true) {
    if ($errors) {
        Session::set('_errors', $errors);}
    if ($withInput && $_POST) {
        $in = $_POST;
        unset($in['password'], $in['password_confirmation'], $in['_token'], $in['current_password']);
        Session::set('_old', $in);}
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = parse_url(base_url(), PHP_URL_HOST);
    if (!$ref || parse_url($ref, PHP_URL_HOST) !== $host) {
        $ref = url('/');}
    header('Location: ' . $ref, true, 302);
    exit;}

function view(string $template, array $data = []): string {
    return View::render($template, $data);}

function partial(string $template, array $data = []): void {
    echo View::partial($template, $data);}

function abort(int $code = 404, string $message = '') {
    throw new HttpException($code, $message);}

function json_out($data, int $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;}

function now(): string {
    return gmdate('Y-m-d H:i:s');}

function site_tz(): DateTimeZone {
    static $tz = null;
    if ($tz === null) {
        try {
            $tz = new DateTimeZone((string)Settings::get('timezone', 'UTC'));}
        catch (\Throwable $e) {
            $tz = new DateTimeZone('UTC');}}
    return $tz;}

/** Format a UTC datetime string/timestamp for display in the site timezone. */
function dt($value, string $format = ''): string {
    if ($value === null || $value === '' || $value === '0000-00-00 00:00:00') {
        return '';}
    $format = $format ?: (string)Settings::get('date_format', 'M j, Y');
    $d = is_numeric($value) ? (new DateTime('@' . (int)$value)) : new DateTime((string)$value, new DateTimeZone('UTC'));
    $d->setTimezone(site_tz());
    return $d->format($format);}

function dtime($value): string {
    return dt($value, (string)Settings::get('date_format', 'M j, Y') . ' ' . (string)Settings::get('time_format', 'g:i A'));}

/** Convert a local (site timezone) datetime string into a UTC "Y-m-d H:i:s". */
function to_utc(?string $local): ?string {
    if (!$local) {
        return null;}
    try {
        $d = new DateTime($local, site_tz());
        $d->setTimezone(new DateTimeZone('UTC'));
        return $d->format('Y-m-d H:i:s');}
    catch (\Throwable $e) {
        return null;}}

/** Convert UTC to a local value suitable for <input type="datetime-local|date">. */
function to_local(?string $utc, string $format = 'Y-m-d\TH:i'): string {
    if (!$utc) {
        return '';}
    $d = new DateTime($utc, new DateTimeZone('UTC'));
    $d->setTimezone(site_tz());
    return $d->format($format);}

function time_ago($utc): string {
    if (!$utc) {
        return '';}
    $diff = time() - strtotime($utc . ' UTC');
    if ($diff < 60) {
        return 'just now';}
    $units = [31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute'];
    foreach ($units as $secs => $name) {
        if ($diff >= $secs) {
            $n = (int)floor($diff / $secs);
            return $n . ' ' . $name . ($n > 1 ? 's' : '') . ' ago';}}
    return '';}

function time_until(int $ts): string {
    $diff = $ts - time();
    if ($diff <= 0) {
        return 'now';}
    $d = intdiv($diff, 86400);
    $h = intdiv($diff % 86400, 3600);
    $m = intdiv($diff % 3600, 60);
    if ($d > 0) {
        return $d . 'd ' . $h . 'h';}
    if ($h > 0) {
        return $h . 'h ' . $m . 'm';}
    return max(1, $m) . 'm';}

function slugify(string $s): string {
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s), '-'));
    return $s !== '' ? substr($s, 0, 120) : 'item';}

function human_size($bytes): string {
    $bytes = (float)$bytes;
    $u = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 4) {
        $bytes /= 1024;
        $i++;}
    return round($bytes, $i ? 1 : 0) . ' ' . $u[$i];}

function human_duration($secs): string {
    $secs = (int)$secs;
    if ($secs <= 0) {
        return '';}
    $h = intdiv($secs, 3600);
    $m = intdiv($secs % 3600, 60);
    $s = $secs % 60;
    return $h ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);}

function initials(string $name): string {
    $p = preg_split('/\s+/', trim($name)) ?: [''];
    return strtoupper(mb_substr($p[0], 0, 1) . (isset($p[1]) ? mb_substr($p[1], 0, 1) : ''));}

function is_path(string $prefix): bool {
    $p = app()->request()->path();
    return $prefix === '/' ? $p === '/' : ($p === $prefix || strpos($p, rtrim($prefix, '/') . '/') === 0);}

function icon(string $name, string $class = ''): string {
    return \App\Core\Icons::svg($name, $class);}

function str_limit(string $s, int $n = 100): string {
    $s = trim(strip_tags($s));
    return mb_strlen($s) > $n ? rtrim(mb_substr($s, 0, $n - 1)) . '…' : $s;}

function selected($a, $b): string {
    return (string)$a === (string)$b ? ' selected' : '';}

function checked($v): string {
    return $v ? ' checked' : '';}

/** Render HTML fragments contributed by modules through a filter (e.g. "lesson.panels"). */
function hook_html(string $name, ...$args): string {
    return implode('', \App\Core\Hooks::filter($name, [], ...$args));}

function money($amount, string $cur = 'INR'): string {
    return \App\Services\Payments\Payments::money($amount, $cur);}

/** Public URL of a module asset: app/Modules/<Module>/assets/<file>. */
function module_asset(string $module, string $file): string {
    $f = APP_PATH . '/Modules/' . $module . '/assets/' . $file;
    return url('/m/' . rawurlencode($module) . '/' . $file, ['v' => is_file($f) ? substr((string)filemtime($f), -6) : '1']);}

const COURSE_TINTS = ['yellow', 'blue', 'pink', 'green', 'purple', 'orange'];
const COURSE_ILLUS = ['rocket', 'bulb', 'laptop', 'palette', 'chart', 'book'];

function course_tint(array $c): string {
    $t = (string)($c['color'] ?? '');
    return in_array($t, COURSE_TINTS, true) ? $t : COURSE_TINTS[((int)($c['id'] ?? 0)) % count(COURSE_TINTS)];}

function course_illus(array $c): string {
    $i = (string)($c['illustration'] ?? '');
    $i = in_array($i, COURSE_ILLUS, true) ? $i : COURSE_ILLUS[((int)($c['id'] ?? 0)) % count(COURSE_ILLUS)];
    return asset('img/illus/course-' . $i . '.svg');}

/** Cover image or tinted illustration panel for a course. */
/**
 * URL of a resized copy of an uploaded image (created once with GD, cached under uploads/_thumbs).
 * Falls back to the original when GD is missing or the file is not a JPEG/PNG/WebP.
 */
function thumb_url(?string $path, int $w = 640): string {
    if (!$path) {
        return '';}
    $rel = ltrim($path, '/');
    $src = BASE_PATH . '/uploads/' . $rel;
    $out = '_thumbs/' . $w . '/' . preg_replace('/\.\w+$/', '', $rel) . '.jpg';
    $dst = BASE_PATH . '/uploads/' . $out;
    if (is_file($dst) && filemtime($dst) >= @filemtime($src)) {
        return upload_url($out);}
    $info = function_exists('imagecreatetruecolor') && is_file($src) ? @getimagesize($src) : false;
    $make = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'][$info[2] ?? 0] ?? null;
    if (!$info || !$make || !function_exists($make) || $info[0] <= $w || $info[0] * $info[1] > 40000000) {
        return upload_url($path);}
    $im = @$make($src);
    if (!$im) {
        return upload_url($path);}
    $h = (int)round($info[1] * $w / $info[0]);
    $t = imagecreatetruecolor($w, $h);
    imagefill($t, 0, 0, imagecolorallocate($t, 255, 255, 255));
    imagecopyresampled($t, $im, 0, 0, 0, 0, $w, $h, $info[0], $info[1]);
    @mkdir(dirname($dst), 0755, true);
    $ok = @imagejpeg($t, $dst, 82);
    imagedestroy($im);
    imagedestroy($t);
    return upload_url($ok ? $out : $path);}

function course_visual(array $c, string $class = '', int $w = 640): string {
    if (!empty($c['cover_image'])) {
        return '<div class="cv ' . e($class) . '" style="background-image:url(\'' . e(thumb_url($c['cover_image'], $w)) . '\')"></div>';}
    return '<div class="cv cv-illus tint-' . e(course_tint($c)) . ' ' . e($class) . '"><img src="' . e(course_illus($c)) . '" alt="" loading="lazy"></div>';}

function illus(string $name, string $class = '', string $alt = '', bool $lazy = true): string {
    return '<img class="illus ' . e($class) . '" src="' . e(asset('img/illus/' . $name . '.svg')) . '" alt="' . e($alt) . '"' . ($lazy ? ' loading="lazy"' : ' fetchpriority="high"') . '>';}
