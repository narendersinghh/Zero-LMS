<?php
namespace App\Core;

/** Symmetric encryption (AES-256-GCM) and HMAC signing keyed by APP_KEY. */
class Crypto {
    private static function key(string $purpose = 'enc'): string {
        $k = (string)Env::get('APP_KEY', '');
        if ($k === '') {
            throw new \RuntimeException('APP_KEY is not configured.');}
        return hash_hmac('sha256', $purpose, $k, true);}

    public static function encrypt(string $plain): string {
        if ($plain === '') {
            return '';}
        $iv = random_bytes(12);
        $tag = '';
        $c = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        return 'enc:' . base64_encode($iv . $tag . $c);}

    public static function decrypt(?string $data): string {
        if (!$data || strpos($data, 'enc:') !== 0) {
            return (string)$data;}
        $raw = base64_decode(substr($data, 4), true);
        if ($raw === false || strlen($raw) < 29) {
            return '';}
        $p = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        return $p === false ? '' : $p;}

    public static function sign(string $data, string $purpose = 'sig'): string {
        return self::b64u(hash_hmac('sha256', $data, self::key($purpose), true));}

    public static function b64u(string $s): string {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');}

    public static function b64uDecode(string $s) {
        return base64_decode(strtr($s, '-_', '+/'), true);}

    public static function token(int $bytes = 32): string {
        return self::b64u(random_bytes($bytes));}}
