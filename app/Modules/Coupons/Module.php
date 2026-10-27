<?php
namespace App\Modules\Coupons;

use App\Core\App;
use App\Core\Auth;
use App\Core\Hooks;
use App\Core\Module as BaseModule;
use App\Core\Session;

/** Discount coupons for course checkout. */
class Module extends BaseModule {
    public function register(App $app): void {
        $this->permissions(['super_admin' => ['coupons.manage'], 'admin' => ['coupons.manage']]);
        $this->adminNav('Coupons', '/admin/coupons', 'sparkles', 'coupons.manage', 20);
        // Remember ?coupon=CODE from a shared course link so the checkout page is pre-filled.
        $path = $app->request()->path();
        if (!empty($_GET['coupon']) && is_string($_GET['coupon']) && preg_match('#^/c/([^/]+)(/checkout)?$#', $path, $m)) {
            $pre = (array)Session::get('coupon_prefill', []);
            $pre[$m[1]] = CouponService::normalize($_GET['coupon']);
            Session::set('coupon_prefill', $pre);}
        Hooks::on('app.routes', function ($r) {
            $c = 'App\\Modules\\Coupons\\AdminController@';
            $r->group('/admin/coupons', ['auth', 'staff', 'can:coupons.manage'], function ($r) use ($c) {
                $r->get('', $c . 'index');
                $r->get('/create', $c . 'create');
                $r->post('', $c . 'store');
                $r->get('/{id}/edit', $c . 'edit');
                $r->post('/{id}', $c . 'update');
                $r->post('/{id}/toggle', $c . 'toggle');
                $r->post('/{id}/delete', $c . 'destroy');});});
        Hooks::on('checkout.price', function (array $p, array $course, array $ctx) {
            $code = (string)($ctx['coupon'] ?? '');
            // Placing/previewing without an explicit coupon field: fall back to the shared-link prefill.
            if ($code === '' && !array_key_exists('coupon', $_POST) && !isset($_GET['coupon'])) {
                $code = (string)(((array)Session::get('coupon_prefill', []))[$course['slug']] ?? '');}
            $code = CouponService::normalize($code);
            if ($code === '' || $p['list'] <= 0) {
                return $p;}
            [$c, $err] = CouponService::validate($code, $course, $ctx['user'] ?? Auth::user());
            if ($err) {
                $p['error'] = $err;
                return $p;}
            $p['final'] = max(0, $p['final'] - CouponService::discount($c, (float)$p['list']));
            $p['coupon'] = $c['code'];
            $p['label'] = CouponService::label($c);
            return $p;});
        Hooks::on('checkout.fields', function (array $html, array $course, array $price) {
            $pre = (string)($price['coupon'] ?? '');
            if ($pre === '') {
                $pre = CouponService::normalize((string)($_GET['coupon'] ?? (((array)Session::get('coupon_prefill', []))[$course['slug']] ?? '')));}
            $html[] = '<div class="field coupon-field"><label for="coupon">Have a coupon?</label><div class="row gap-2"><input id="coupon" name="coupon" value="' . e($pre) . '" placeholder="Enter code" autocomplete="off" style="text-transform:uppercase"><button type="button" class="btn btn-ghost" data-apply-coupon>Apply</button></div><small data-coupon-msg class="small' . ($price['coupon'] ? ' success">Coupon applied — you save ' . e(money($price['discount'], $price['currency'])) : '">') . '</small></div>';
            return $html;});
        Hooks::on('order.paid', function ($order, $course = null, $user = null) {
            if (!empty($order['coupon_code'])) {
                CouponService::redeem($order['coupon_code'], (int)$order['user_id'], (int)$order['id'], (int)$order['course_id'], (float)$order['discount']);}});
        Hooks::on('coupon.used', function ($code, $user, $course, $order = null) {
            if ($code && $user) {
                $base = (float)$course['price'];
                $list = $course['sale_price'] !== null && $course['sale_price'] !== '' && (float)$course['sale_price'] < $base ? (float)$course['sale_price'] : $base;
                CouponService::redeem((string)$code, (int)$user['id'], null, (int)$course['id'], (float)$list);}});}}
