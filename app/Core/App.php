<?php
namespace App\Core;

/** Application kernel: boots services, modules and dispatches the request. */
class App {
    private static ?App $instance = null;
    private Request $request;
    private Router $router;
    public const VERSION_FILE = BASE_PATH . '/version.json';

    public static function instance(): App {
        return self::$instance ??= new App();}

    private function __construct() {
        $this->request = new Request();
        $this->router = new Router();}

    public function request(): Request {
        return $this->request;}

    public function router(): Router {
        return $this->router;}

    public static function installed(): bool {
        return is_file(STORAGE_PATH . '/installed.lock') && is_file(BASE_PATH . '/.env');}

    public static function version(): string {
        static $v = null;
        if ($v === null) {
            $j = is_file(self::VERSION_FILE) ? json_decode((string)file_get_contents(self::VERSION_FILE), true) : null;
            $v = $j['version'] ?? '0.0.0';}
        return $v;}

    public function run(): void {
        $this->sendSecurityHeaders();
        Session::start();
        $path = $this->request->path();
        if (!self::installed()) {
            if (strpos($path, '/install') !== 0) {
                redirect('/install');}
            require APP_PATH . '/routes_install.php';
            $this->dispatch();
            return;}
        Settings::load();
        Hooks::fire('app.booting', $this);
        ModuleManager::boot($this);
        require APP_PATH . '/routes.php';
        Hooks::fire('app.routes', $this->router);
        if (is_file(STORAGE_PATH . '/maintenance') && !Auth::isStaff() && !preg_match('#^/(login|logout|password)#', $path)) {
            throw new HttpException(503, 'We are performing scheduled maintenance. Please check back shortly.');}
        $this->dispatch();
        Scheduler::afterResponse();}

    private function dispatch(): void {
        $out = $this->router->dispatch($this->request);
        if (is_string($out)) {
            echo $out;}}

    private function sendSecurityHeaders(): void {
        header_remove('X-Powered-By');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; img-src 'self' data: blob: https:; media-src 'self' blob:; frame-src 'self' https:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
        if ($this->request->isSecure()) {
            header('Strict-Transport-Security: max-age=15552000');}}}
