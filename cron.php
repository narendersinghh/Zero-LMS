<?php
/**
 * Zeroes LMS scheduled tasks.
 * cPanel cron (every 5 minutes):  php /home/USER/public_html/cron.php >/dev/null 2>&1
 * Or via URL (if CLI cron is unavailable): https://your-site.com/cron/run?key=CRON_SECRET
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');}
define('BASE_PATH', __DIR__);
require BASE_PATH . '/app/bootstrap.php';
if (!\App\Core\App::installed()) {
    fwrite(STDERR, "Zeroes is not installed.\n");
    exit(1);}
\App\Core\Settings::load();
\App\Core\ModuleManager::boot(\App\Core\App::instance());
$lock = fopen(STORAGE_PATH . '/cache/cron.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    exit(0);}
$r = \App\Core\Scheduler::run();
echo '[' . gmdate('Y-m-d H:i:s') . '] ' . json_encode($r) . "\n";
