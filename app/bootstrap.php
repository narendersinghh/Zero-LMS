<?php
/**
 * Zeroes LMS bootstrap: autoloader, environment, error handling.
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));}
if (PHP_VERSION_ID < 80000) {
    http_response_code(500);
    exit('Zeroes requires PHP 8.0 or newer. Current version: ' . PHP_VERSION);}
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('UPLOAD_PATH', BASE_PATH . '/uploads');

spl_autoload_register(function (string $class) {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;}
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;}});

require APP_PATH . '/helpers.php';

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');
\App\Core\Env::load(BASE_PATH . '/.env');

$debug = \App\Core\Env::bool('APP_DEBUG', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');
set_exception_handler([\App\Core\ErrorHandler::class, 'handle']);
