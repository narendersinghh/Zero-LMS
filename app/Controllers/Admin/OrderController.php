<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csv;
use App\Core\DB;
use App\Core\Paginator;
use App\Services\Payments\Payments;

class OrderController extends Controller {
    private function filters(): array {
        $w = ['1=1'];
        $p = [];
        $st = $this->req->str('status');
        if (isset(Payments::STATUSES[$st])) {
            $w[] = 'o.status = ?';
            $p[] = $st;}
        if (($q = $this->req->str('q')) !== '') {
            $w[] = '(o.ref LIKE ? OR o.utr LIKE ? OR u.email LIKE ? OR u.name LIKE ?)';
            array_push($p, DB::like($q), DB::like($q), DB::like($q), DB::like($q));}
        if ($cid = $this->req->int('course')) {
            $w[] = 'o.course_id = ?';
            $p[] = $cid;}
        return [implode(' AND ', $w), $p];}

    public function index() {
        [$w, $p] = $this->filters();
        $base = 'FROM {orders} o JOIN {users} u ON u.id = o.user_id JOIN {courses} c ON c.id = o.course_id WHERE ' . $w;
        $pg = new Paginator((int)DB::val("SELECT COUNT(*) $base", $p), 30);
        $rows = DB::all("SELECT o.*, u.name, u.email, u.phone, c.title AS course_title $base ORDER BY (o.status = 'submitted') DESC, o.id DESC" . $pg->limitSql(), $p);
        $counts = DB::pairs('SELECT status, COUNT(*) FROM {orders} GROUP BY status');
        $revenue = DB::one("SELECT COALESCE(SUM(amount),0) total, COALESCE(SUM(CASE WHEN paid_at >= ? THEN amount END),0) month FROM {orders} WHERE status = 'paid'", [gmdate('Y-m-01 00:00:00')]);
        return view('admin/orders/index', ['rows' => $rows, 'pg' => $pg, 'counts' => $counts, 'revenue' => $revenue, 'courses' => DB::all('SELECT id, title FROM {courses} ORDER BY title')]);}

    public function export() {
        [$w, $p] = $this->filters();
        $rows = DB::all("SELECT o.ref, o.created_at, u.name, u.email, u.phone, c.title, o.amount, o.discount, o.coupon_code, o.currency, o.gateway, o.status, o.utr, o.gateway_ref, o.paid_at FROM {orders} o JOIN {users} u ON u.id = o.user_id JOIN {courses} c ON c.id = o.course_id WHERE $w ORDER BY o.id DESC", $p);
        Csv::download('orders-' . gmdate('Y-m-d') . '.csv', ['Order', 'Created (UTC)', 'Name', 'Email', 'Phone', 'Course', 'Amount', 'Discount', 'Coupon', 'Currency', 'Gateway', 'Status', 'UTR', 'Gateway ref', 'Paid (UTC)'], $rows);}

    private function order(string $id): array {
        return DB::one('SELECT * FROM {orders} WHERE id = ?', [(int)$id]) ?? abort(404);}

    public function approve(string $id) {
        $o = $this->order($id);
        Payments::markPaid($o, $o['utr'] ?: null, $this->req->str('note'));
        $this->ok('Payment approved. Access granted and the student was emailed.');}

    public function reject(string $id) {
        $o = $this->order($id);
        $reason = $this->req->str('reason') ?: 'We could not find a matching payment.';
        Payments::reject($o, $reason);
        $this->ok('Order rejected and the student was notified.');}

    public function proof(string $id) {
        $o = $this->order($id);
        $f = STORAGE_PATH . '/private/proofs/' . basename((string)$o['proof']);
        if (!$o['proof'] || !is_file($f)) {
            abort(404);}
        header('Content-Type: ' . \App\Core\Uploads::mime($f));
        header('Cache-Control: private, no-store');
        readfile($f);
        exit;}}
