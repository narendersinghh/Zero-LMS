<?php
/** Router for PHP's built-in server (development only): php -S localhost:8000 tools/dev-server.php */
$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('#^/(app|database|storage|tools|\.)#', $path) || preg_match('#\.(env|md|json|log)$#', $path)) {
    http_response_code(403);
    exit('Forbidden');}
$file = dirname(__DIR__) . $path;
if ($path !== '/' && is_file($file) && !preg_match('/\.php$/', $file)) {
    return false;}
$_SERVER['SCRIPT_NAME'] = '/index.php';
// ZX_PROFILE=1 php -S ... writes per-request query timings to storage/logs/profile.log.
if (getenv('ZX_PROFILE')) {
    $GLOBALS['__t0'] = microtime(true);
    register_shutdown_function(function () use ($path) {
        $q = \App\Core\DB::$profile ?? [];
        usort($q, fn($a, $b) => $b[0] <=> $a[0]);
        $line = sprintf("%s %.1fms %d queries (%.1fms SQL)\n", $path, (microtime(true) - $GLOBALS['__t0']) * 1000, count($q), array_sum(array_column($q, 0)));
        foreach (array_slice($q, 0, 8) as $x) {
            $line .= sprintf("   %7.2fms %s\n", $x[0], $x[1]);}
        file_put_contents(dirname(__DIR__) . '/storage/logs/profile.log', $line, FILE_APPEND);});}
require dirname(__DIR__) . '/index.php';
