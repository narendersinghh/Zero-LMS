<?php
namespace App\Services\Payments;

/**
 * Payment gateway contract. Register extra gateways via the "payment.gateways" filter:
 * Hooks::on('payment.gateways', fn($g) => $g + ['razorpay' => new RazorpayGateway()]);
 */
interface Gateway {
    public function key(): string;

    public function label(): string;

    /** Short text shown on the checkout option. */
    public function description(): string;

    public function enabled(): bool;

    /** Start payment for a pending order. Must redirect (or return a view string). */
    public function begin(array $order, array $course, array $user);}
