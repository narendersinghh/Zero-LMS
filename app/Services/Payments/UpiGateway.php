<?php
namespace App\Services\Payments;

use App\Core\Settings;

/** Built-in UPI payment: QR code + UPI deep link, student submits UTR, admin verifies. */
class UpiGateway implements Gateway {
    public function key(): string {
        return 'upi';}

    public function label(): string {
        return 'UPI (GPay, PhonePe, Paytm, BHIM…)';}

    public function description(): string {
        return 'Scan the QR or tap to pay in any UPI app, then enter the transaction reference (UTR).';}

    public function enabled(): bool {
        return (bool)Settings::get('upi_enabled', true) && trim((string)Settings::get('upi_vpa', '')) !== '';}

    public function begin(array $order, array $course, array $user) {
        redirect('/orders/' . $order['ref'] . '/pay');}

    /** UPI deep link per NPCI spec. */
    public static function uri(array $order, string $courseTitle): string {
        $p = ['pa' => trim((string)Settings::get('upi_vpa')), 'pn' => (string)(Settings::get('upi_payee_name') ?: Settings::get('site_name')), 'am' => number_format((float)$order['amount'], 2, '.', ''),
            'cu' => 'INR', 'tn' => mb_substr($order['ref'] . ' ' . $courseTitle, 0, 60)];
        // 'tr' is omitted on purpose: several UPI apps reject it for non-merchant UPI IDs. The order ref travels in the note (tn).
        return 'upi://pay?' . str_replace(['+', '%40'], ['%20', '@'], http_build_query($p));}}
