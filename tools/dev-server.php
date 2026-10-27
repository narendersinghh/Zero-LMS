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
require dirname(__DIR__) . '/index.php';
