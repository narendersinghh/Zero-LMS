<?php
namespace App\Core;

class Csrf {
    public static function token(): string {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));}
        return $_SESSION['_csrf'];}

    public static function verify(Request $req): void {
        $sent = (string)($_POST['_token'] ?? $req->header('X-CSRF-Token') ?? '');
        if ($sent === '' || empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $sent)) {
            throw new HttpException(419, 'Your session expired or the form is invalid. Please reload the page and try again.');}}}
