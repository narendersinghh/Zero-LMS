<?php
/**
 * Zeroes LMS - front controller.
 * All requests are routed through this file (see .htaccess).
 */
define('ZEROES_START', microtime(true));
define('BASE_PATH', __DIR__);
require BASE_PATH . '/app/bootstrap.php';
\App\Core\App::instance()->run();
