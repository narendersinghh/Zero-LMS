<?php
namespace App\Core;

class ErrorHandler {
    public static function handle(\Throwable $e): void {
        $code = $e instanceof HttpException ? $e->getCode() : 500;
        if ($code >= 500 && !($e instanceof HttpException)) {
            Logger::error(get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());}
        while (ob_get_level() > 0) {
            ob_end_clean();}
        if (!headers_sent()) {
            http_response_code($code);}
        $titles = [400 => 'Bad request', 401 => 'Please sign in', 403 => 'Access denied', 404 => 'Page not found', 405 => 'Method not allowed', 419 => 'Session expired', 429 => 'Too many requests', 503 => 'Down for maintenance'];
        $title = $titles[$code] ?? 'Something went wrong';
        $msg = $e->getMessage();
        if (!($e instanceof HttpException)) {
            $msg = Env::bool('APP_DEBUG') ? $msg . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')' : 'An unexpected error occurred. The issue has been logged.';}
        $req = App::instance()->request();
        if ($req->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $msg ?: $title]);
            return;}
        try {
            echo View::render('errors/error', ['code' => $code, 'title' => $title, 'message' => $msg]);}
        catch (\Throwable $e2) {
            echo '<!doctype html><meta charset="utf-8"><title>' . e($title) . '</title><body style="font-family:sans-serif;background:#0b0b0c;color:#eee;display:grid;place-items:center;height:100vh"><div><h1>' . (int)$code . ' · ' . e($title) . '</h1><p>' . e($msg) . '</p></div>';}}}
