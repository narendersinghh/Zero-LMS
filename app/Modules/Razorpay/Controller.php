<?php
namespace App\Modules\Razorpay;

use App\Controllers\Controller as BaseController;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Crypto;
use App\Core\DB;
use App\Core\Logger;
use App\Core\Settings;
use App\Services\Payments\Payments;

class Controller extends BaseController {
    public function settings() {
        return view('razorpay/settings', ['s' => Settings::all(), 'enabled' => (new Gateway())->enabled()]);}

    public function save() {
        $keyId = $this->req->str('razorpay_key_id');
        if ($keyId !== '' && !preg_match('/^rzp_(test|live)_[A-Za-z0-9]+$/', $keyId)) {
            flash('error', 'Key ID should look like rzp_live_XXXX or rzp_test_XXXX.');
            back();}
        $v = ['razorpay_enabled' => $this->req->bool('razorpay_enabled'), 'razorpay_key_id' => $keyId];
        if ($this->req->str('razorpay_key_secret') !== '') {
            $v['razorpay_key_secret'] = Crypto::encrypt($this->req->str('razorpay_key_secret'));}
        if ($this->req->str('razorpay_webhook_secret') !== '') {
            $v['razorpay_webhook_secret'] = Crypto::encrypt($this->req->str('razorpay_webhook_secret'));}
        Settings::set($v);
        Audit::log('settings.razorpay', null, null, ['enabled' => $v['razorpay_enabled'], 'key_id' => $keyId]);
        $this->ok('Razorpay settings saved.');}

    public function test() {
        try {
            Gateway::api('GET', '/payment_links?count=1');
            $this->ok('Connected to Razorpay successfully.');}
        catch (\Throwable $e) {
            flash('error', $e->getMessage());
            back();}}

    /** Browser returns here after paying on the Razorpay page. */
    public function callback() {
        $q = $_GET;
        $order = !empty($q['razorpay_payment_link_reference_id']) && is_string($q['razorpay_payment_link_reference_id']) ? Payments::find(Gateway::orderRef($q['razorpay_payment_link_reference_id'])) : null;
        if (!$order || !Gateway::verifyCallback($q, Gateway::secret()) || $order['gateway_ref'] !== $q['razorpay_payment_link_id']) {
            Logger::write('payments', 'warning', 'Razorpay callback rejected: ' . json_encode(array_intersect_key($q, array_flip(['razorpay_payment_link_id', 'razorpay_payment_link_reference_id', 'razorpay_payment_link_status']))));
            flash('error', 'We could not verify this payment. If money was deducted, it will be confirmed automatically or contact support.');
            redirect($order ? '/c/' . $order['course_slug'] . '/checkout' : '/orders');}
        if ($q['razorpay_payment_link_status'] !== 'paid') {
            flash('error', 'The payment was not completed. You can try again.');
            redirect('/c/' . $order['course_slug'] . '/checkout');}
        Payments::markPaid(DB::one('SELECT * FROM {orders} WHERE id = ?', [$order['id']]), $q['razorpay_payment_id']);
        flash('success', 'Payment successful — welcome to ' . $order['course_title'] . '!');
        redirect(Auth::check() ? '/courses/' . $order['course_slug'] : '/login');}

    /** Server-to-server confirmation (configure in Razorpay Dashboard → Webhooks, event payment_link.paid). */
    public function webhook() {
        $body = (string)file_get_contents('php://input');
        $sig = (string)($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '');
        if (!Gateway::verifyWebhook($body, $sig, Gateway::webhookSecret())) {
            Logger::write('payments', 'warning', 'Razorpay webhook with invalid signature from ' . $this->req->ip());
            json_out(['error' => 'invalid signature'], 400);}
        $j = json_decode($body, true) ?: [];
        if (($j['event'] ?? '') === 'payment_link.paid') {
            $link = $j['payload']['payment_link']['entity'] ?? [];
            $pay = $j['payload']['payment']['entity'] ?? [];
            $order = DB::one('SELECT * FROM {orders} WHERE ref = ?', [Gateway::orderRef((string)($link['reference_id'] ?? ''))]);
            if ($order && $order['gateway_ref'] === ($link['id'] ?? null) && Gateway::paise($order['amount']) === (int)($link['amount_paid'] ?? $link['amount'] ?? -1)) {
                Payments::markPaid($order, (string)($pay['id'] ?? ''));}
            elseif ($order) {
                Logger::write('payments', 'warning', 'Razorpay webhook mismatch for ' . $order['ref']);}}
        json_out(['ok' => true]);}}
