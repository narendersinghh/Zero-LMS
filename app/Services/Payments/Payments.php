<?php
namespace App\Services\Payments;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Hooks;
use App\Core\Mailer;
use App\Core\Settings;
use App\Services\Enrollments;
use App\Services\Notifier;
use App\Services\Users;

class Payments {
    public const STATUSES = ['pending' => 'Awaiting payment', 'submitted' => 'Verifying', 'paid' => 'Paid', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'];

    /** @return Gateway[] keyed by gateway key */
    public static function gateways(bool $enabledOnly = true): array {
        $all = Hooks::filter('payment.gateways', ['upi' => new UpiGateway()]);
        return $enabledOnly ? array_filter($all, fn($g) => $g->enabled()) : $all;}

    public static function symbol(string $cur = 'INR'): string {
        return ['INR' => '₹', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'][$cur] ?? $cur . ' ';}

    public static function money($amount, string $cur = 'INR'): string {
        $a = (float)$amount;
        return self::symbol($cur) . (fmod($a, 1.0) == 0.0 ? number_format($a) : number_format($a, 2));}

    /**
     * Price for a course. Modules (e.g. coupons) adjust it with the "checkout.price" filter.
     * @return array{base:float, final:float, discount:float, currency:string, coupon:?string, free:bool, label:?string}
     */
    public static function price(array $course, array $ctx = []): array {
        $base = (float)($course['price'] ?? 0);
        $sale = $course['sale_price'] !== null && $course['sale_price'] !== '' ? (float)$course['sale_price'] : null;
        $final = $sale !== null && $sale < $base ? $sale : $base;
        $p = ['base' => $base, 'list' => $final, 'final' => $final, 'discount' => 0.0, 'currency' => $course['currency'] ?: 'INR', 'coupon' => null, 'label' => null];
        $p = Hooks::filter('checkout.price', $p, $course, $ctx);
        $p['final'] = max(0, round((float)$p['final'], 2));
        $p['discount'] = round($p['list'] - $p['final'], 2);
        $p['free'] = $p['final'] <= 0;
        return $p;}

    public static function newRef(): string {
        $c = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $r = 'ZX-';
            for ($i = 0; $i < 7; $i++) {
                $r .= $c[random_int(0, strlen($c) - 1)];}}
        while (DB::val('SELECT id FROM {orders} WHERE ref = ?', [$r]));
        return $r;}

    public static function create(array $user, array $course, array $price, string $gateway): array {
        // Reuse an open order for the same course instead of piling up duplicates.
        $open = DB::one("SELECT * FROM {orders} WHERE user_id = ? AND course_id = ? AND status = 'pending' AND gateway = ? ORDER BY id DESC LIMIT 1", [$user['id'], $course['id'], $gateway]);
        $row = ['amount' => $price['final'], 'original_amount' => $price['list'], 'discount' => $price['discount'], 'coupon_code' => $price['coupon'], 'currency' => $price['currency'], 'updated_at' => now()];
        if ($open) {
            DB::update('orders', $row, 'id = ?', [$open['id']]);
            return DB::one('SELECT * FROM {orders} WHERE id = ?', [$open['id']]);}
        $id = DB::insert('orders', $row + ['ref' => self::newRef(), 'user_id' => (int)$user['id'], 'course_id' => (int)$course['id'], 'gateway' => $gateway, 'status' => 'pending', 'created_at' => now()]);
        $order = DB::one('SELECT * FROM {orders} WHERE id = ?', [$id]);
        Audit::log('order.created', 'order', $id, ['ref' => $order['ref'], 'amount' => $order['amount']], (int)$user['id']);
        Hooks::fire('order.created', $order);
        return $order;}

    public static function find(string $ref): ?array {
        return DB::one('SELECT o.*, c.title AS course_title, c.slug AS course_slug, u.name AS user_name, u.email AS user_email FROM {orders} o JOIN {courses} c ON c.id = o.course_id JOIN {users} u ON u.id = o.user_id WHERE o.ref = ?', [$ref]);}

    /** Mark paid, grant access, notify. Idempotent. */
    public static function markPaid(array $order, ?string $gatewayRef = null, string $note = ''): void {
        if ($order['status'] === 'paid') {
            return;}
        DB::update('orders', ['status' => 'paid', 'paid_at' => now(), 'gateway_ref' => $gatewayRef ?: $order['gateway_ref'], 'admin_note' => $note ?: $order['admin_note'], 'verified_by' => Auth::id() ?: null, 'updated_at' => now()], 'id = ?', [$order['id']]);
        $course = DB::one('SELECT * FROM {courses} WHERE id = ?', [$order['course_id']]);
        $user = Users::find((int)$order['user_id']);
        $enr = \App\Services\Access::enrollment((int)$user['id'], (int)$course['id']);
        Enrollments::enroll((int)$user['id'], $course, ['source' => 'purchase', 'notes' => 'Order ' . $order['ref'], 'keep_start' => (bool)$enr]);
        Mailer::queueTemplate('order_paid', $user['email'], $user['name'], self::vars($order, $course));
        Audit::log('order.paid', 'order', (int)$order['id'], ['ref' => $order['ref'], 'gateway_ref' => $gatewayRef]);
        Hooks::fire('order.paid', DB::one('SELECT * FROM {orders} WHERE id = ?', [$order['id']]), $course, $user);}

    public static function reject(array $order, string $reason): void {
        DB::update('orders', ['status' => 'rejected', 'admin_note' => mb_substr($reason, 0, 255), 'verified_by' => Auth::id(), 'updated_at' => now()], 'id = ?', [$order['id']]);
        $course = DB::one('SELECT * FROM {courses} WHERE id = ?', [$order['course_id']]);
        $user = Users::find((int)$order['user_id']);
        // Revoke provisional access granted on submission.
        if (Settings::get('upi_provisional_access')) {
            DB::run("UPDATE {enrollments} SET status = 'suspended' WHERE user_id = ? AND course_id = ? AND source = 'provisional'", [$user['id'], $course['id']]);}
        Notifier::notify((int)$user['id'], 'Payment not verified', 'Order ' . $order['ref'] . ': ' . $reason, '/orders/' . $order['ref'] . '/pay', 'access');
        Mailer::queueTemplate('order_rejected', $user['email'], $user['name'], self::vars($order, $course) + ['reason' => $reason]);
        Audit::log('order.rejected', 'order', (int)$order['id'], ['reason' => $reason]);
        Hooks::fire('order.rejected', $order);}

    public static function vars(array $order, array $course): array {
        return ['course_title' => $course['title'], 'course_url' => url('/courses/' . $course['slug'], [], true), 'order_ref' => $order['ref'], 'amount' => self::money($order['amount'], $order['currency']),
            'orders_url' => url('/orders', [], true), 'pay_url' => url('/orders/' . $order['ref'] . '/pay', [], true)];}}
