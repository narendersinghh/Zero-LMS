<?php
namespace App\Core;

/**
 * Tiny router with {param} placeholders, groups and middleware.
 * Middleware: auth, guest, staff, can:<permission>, throttle:<key>,<max>,<mins>
 */
class Router {
    private array $routes = [];
    private array $groupStack = [];
    private array $named = [];

    public function get(string $p, $h, array $mw = [], ?string $name = null): self {
        return $this->add(['GET'], $p, $h, $mw, $name);}

    public function post(string $p, $h, array $mw = [], ?string $name = null): self {
        return $this->add(['POST'], $p, $h, $mw, $name);}

    public function any(string $p, $h, array $mw = [], ?string $name = null): self {
        return $this->add(['GET', 'POST'], $p, $h, $mw, $name);}

    public function group(string $prefix, array $mw, callable $cb): void {
        $this->groupStack[] = ['prefix' => $prefix, 'mw' => $mw];
        $cb($this);
        array_pop($this->groupStack);}

    public function add(array $methods, string $path, $handler, array $mw = [], ?string $name = null): self {
        $prefix = '';
        $gmw = [];
        foreach ($this->groupStack as $g) {
            $prefix .= $g['prefix'];
            $gmw = array_merge($gmw, $g['mw']);}
        $full = '/' . trim($prefix . $path, '/');
        $regex = '#^' . preg_replace('#\\\{([a-z_]+)\\\}#i', '(?P<$1>[^/]+)', preg_quote($full, '#')) . '$#';
        $this->routes[] = ['m' => $methods, 'p' => $full, 'r' => $regex, 'h' => $handler, 'mw' => array_merge($gmw, $mw)];
        if ($name) {
            $this->named[$name] = $full;}
        return $this;}

    public function dispatch(Request $req) {
        $path = $req->path();
        $method = $req->method();
        $allowed = false;
        foreach ($this->routes as $rt) {
            if (!preg_match($rt['r'], $path, $m)) {
                continue;}
            if (!in_array($method, $rt['m'], true) && !($method === 'HEAD' && in_array('GET', $rt['m'], true))) {
                $allowed = true;
                continue;}
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            $this->runMiddleware($rt['mw'], $req);
            return $this->call($rt['h'], $params);}
        throw new HttpException($allowed ? 405 : 404);}

    private function runMiddleware(array $mws, Request $req): void {
        if ($req->isPost()) {
            Csrf::verify($req);}
        foreach ($mws as $mw) {
            [$name, $arg] = array_pad(explode(':', $mw, 2), 2, null);
            switch ($name) {
                case 'auth':
                    if (!Auth::check()) {
                        if ($req->isAjax()) {
                            json_out(['error' => 'Unauthenticated'], 401);}
                        Session::set('_intended', $_SERVER['REQUEST_URI'] ?? '');
                        redirect('/login');}
                    break;
                case 'guest':
                    if (Auth::check()) {
                        redirect(Auth::isStaff() ? '/admin' : '/dashboard');}
                    break;
                case 'staff':
                    if (!Auth::isStaff()) {
                        throw new HttpException(403);}
                    break;
                case 'can':
                    if (!Auth::can((string)$arg)) {
                        throw new HttpException(403);}
                    break;
                default:
                    Hooks::fire('middleware.' . $name, $req, $arg);}}}

    private function call($handler, array $params) {
        if (is_callable($handler) && !is_string($handler)) {
            return $handler(...array_values($params));}
        [$class, $method] = is_array($handler) ? $handler : explode('@', (string)$handler, 2);
        if (strpos($class, 'App\\') !== 0) {
            $class = 'App\\Controllers\\' . $class;}
        $obj = new $class();
        return $obj->$method(...array_values($params));}

    public function routes(): array {
        return $this->routes;}}
