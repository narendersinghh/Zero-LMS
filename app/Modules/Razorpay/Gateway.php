<?php
namespace App\Modules\Razorpay;

use App\Core\Crypto;
use App\Core\DB;
use App\Core\Logger;
use App\Core\Settings;
use App\Services\Payments\Gateway as GatewayContract;

/** Razorpay Payment Links gateway: redirect to a hosted payment page, verify the signed callback. */
class Gateway implements GatewayContract {
    public const API = 'https://api.razorpay.com/v1';

    public function key(): string {
        return 'razorpay';}

    public function label(): string {
        return 'Card, UPI, Netbanking & Wallets';}

    public function description(): string {
        return 'Pay securely on Razorpay. Access is activated automatically after payment.';}

    public function enabled(): bool {
        return (bool)Settings::get('razorpay_enabled', false) && self::keyId() !== '' && self::secret() !== '';}

    public static function keyId(): string {
        return trim((string)Settings::get('razorpay_key_id', ''));}

    public static function secret(): string {
        return Crypto::decrypt((string)Settings::get('razorpay_key_secret', ''));}

    public static function webhookSecret(): string {
        return Crypto::decrypt((string)Settings::get('razorpay_webhook_secret', ''));}

    public function begin(array $order, array $course, array $user) {
        try {
            $link = null;
            // Reuse the existing link for this order when it is still payable.
            if (!empty($order['gateway_ref']) && strpos((string)$order['gateway_ref'], 'plink_') === 0) {
                $old = self::api('GET', '/payment_links/' . rawurlencode($order['gateway_ref']));
                if (($old['status'] ?? '') === 'created' && (int)($old['amount'] ?? 0) === self::paise($order['amount']) && !empty($old['short_url'])) {
                    $link = $old;}}
            if (!$link) {
                $body = ['amount' => self::paise($order['amount']), 'currency' => $order['currency'] ?: 'INR', 'accept_partial' => false, 'reference_id' => self::reference($order),
                    'description' => mb_substr($course['title'] . ' · ' . $order['ref'], 0, 2048), 'customer' => array_filter(['name' => $user['name'], 'email' => $user['email'], 'contact' => $user['phone'] ?? null]),
                    'notify' => ['sms' => false, 'email' => false], 'reminder_enable' => false, 'notes' => ['order_ref' => $order['ref'], 'course_id' => (string)$course['id']],
                    'callback_url' => url('/razorpay/callback', [], true), 'callback_method' => 'get', 'expire_by' => time() + 2 * 86400];
                $link = self::api('POST', '/payment_links', $body);
                DB::update('orders', ['gateway_ref' => $link['id'], 'updated_at' => now()], 'id = ?', [$order['id']]);}
            header('Location: ' . $link['short_url'], true, 302);
            exit;}
        catch (\Throwable $e) {
            Logger::write('payments', 'error', 'Razorpay link for ' . $order['ref'] . ': ' . $e->getMessage());
            flash('error', 'We could not start the Razorpay payment. Please try again or choose UPI.');
            redirect('/c/' . $course['slug'] . '/checkout');}}

    public static function paise($amount): int {
        return (int)round((float)$amount * 100);}

    /** reference_id must be unique per link; add a suffix when re-creating a link for the same order. */
    public static function reference(array $order): string {
        return empty($order['gateway_ref']) ? $order['ref'] : $order['ref'] . '-' . base_convert((string)time(), 10, 36);}

    /** "ZX-ABCDEFG-xyz" -> "ZX-ABCDEFG" */
    public static function orderRef(string $reference): string {
        $p = explode('-', $reference);
        return count($p) >= 2 ? $p[0] . '-' . $p[1] : $reference;}

    public static function verifyCallback(array $q, string $secret): bool {
        foreach (['razorpay_payment_link_id', 'razorpay_payment_link_reference_id', 'razorpay_payment_link_status', 'razorpay_payment_id', 'razorpay_signature'] as $k) {
            if (!isset($q[$k]) || !is_string($q[$k]) || $q[$k] === '') {
                return false;}}
        if ($secret === '') {
            return false;}
        $payload = $q['razorpay_payment_link_id'] . '|' . $q['razorpay_payment_link_reference_id'] . '|' . $q['razorpay_payment_link_status'] . '|' . $q['razorpay_payment_id'];
        return hash_equals(hash_hmac('sha256', $payload, $secret), $q['razorpay_signature']);}

    public static function verifyWebhook(string $body, string $signature, string $secret): bool {
        return $secret !== '' && $signature !== '' && hash_equals(hash_hmac('sha256', $body, $secret), $signature);}

    public static function api(string $method, string $path, ?array $body = null): array {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('PHP cURL extension is required for Razorpay.');}
        $ch = curl_init(self::API . $path);
        $opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_USERPWD => self::keyId() . ':' . self::secret(), CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'], CURLOPT_CUSTOMREQUEST => $method];
        if ($body !== null) {
            $opt[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);}
        curl_setopt_array($ch, $opt);
        $res = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $j = is_string($res) ? json_decode($res, true) : null;
        if ($res === false || $code >= 400 || !is_array($j)) {
            throw new \RuntimeException('Razorpay API ' . $method . ' ' . $path . ' failed (' . ($code ?: $err) . '): ' . ($j['error']['description'] ?? substr((string)$res, 0, 200)));}
        return $j;}}
