<?php
/**
 * CLI installer (alternative to the web installer).
 * php tools/install.php --url=https://learn.example.com --db-name=zeroes --db-user=u --db-pass=p --admin-email=a@b.com --admin-pass=Secret123 [--db-host=localhost] [--db-prefix=zx_] [--name="Admin"] [--site="Zeroes"] [--tz=UTC] [--demo]
 */
if (PHP_SAPI !== 'cli') {
    exit;}
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';
use App\Core\Installer;
$o = getopt('', ['url:', 'db-host::', 'db-port::', 'db-name:', 'db-user:', 'db-pass::', 'db-prefix::', 'admin-email:', 'admin-pass:', 'name::', 'site::', 'tz::', 'demo']);
foreach (['url', 'db-name', 'db-user', 'admin-email', 'admin-pass'] as $k) {
    if (empty($o[$k])) {
        fwrite(STDERR, "Missing --$k\n");
        exit(1);}}
if (\App\Core\App::installed()) {
    fwrite(STDERR, "Already installed.\n");
    exit(1);}
$db = ['host' => $o['db-host'] ?? 'localhost', 'port' => (int)($o['db-port'] ?? 3306), 'name' => $o['db-name'], 'user' => $o['db-user'], 'pass' => $o['db-pass'] ?? '', 'prefix' => $o['db-prefix'] ?? 'zx_'];
if ($err = Installer::testDb($db)) {
    fwrite(STDERR, "Database error: $err\n");
    exit(1);}
Installer::writeEnv($db, $o['url']);
Installer::install(['name' => $o['name'] ?? 'Administrator', 'email' => $o['admin-email'], 'password' => $o['admin-pass']], ['site_name' => $o['site'] ?? 'Zeroes', 'timezone' => $o['tz'] ?? 'UTC'], isset($o['demo']));
echo "Zeroes installed. Sign in at {$o['url']}/login\n";
