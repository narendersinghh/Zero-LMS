<?php
namespace App\Modules\Razorpay;

use App\Core\App;
use App\Core\Hooks;
use App\Core\Module as BaseModule;

/** Razorpay payment gateway (Payment Links + webhook). */
class Module extends BaseModule {
    public function register(App $app): void {
        $this->adminNav('Razorpay', '/admin/razorpay', 'key', 'payments.configure', 60);
        Hooks::on('payment.gateways', function (array $g) {
            $g['razorpay'] = new Gateway();
            return $g;});
        Hooks::on('app.routes', function ($r) {
            $c = 'App\\Modules\\Razorpay\\Controller@';
            $r->get('/razorpay/callback', $c . 'callback');
            $r->post('/razorpay/webhook', $c . 'webhook', ['nocsrf']);
            $r->group('/admin/razorpay', ['auth', 'staff', 'can:payments.configure'], function ($r) use ($c) {
                $r->get('', $c . 'settings');
                $r->post('', $c . 'save');
                $r->post('/test', $c . 'test');});});}}
