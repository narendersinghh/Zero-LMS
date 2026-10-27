<?php
namespace App\Modules\Coupons;

use App\Controllers\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Paginator;

class AdminController extends Controller {
    public function index() {
        $w = '1=1';
        $p = [];
        if (($q = $this->req->str('q')) !== '') {
            $w = 'c.code LIKE ?';
            $p[] = DB::like(strtoupper($q));}
        $pg = new Paginator((int)DB::val("SELECT COUNT(*) FROM {coupons} c WHERE $w", $p), 30);
        $rows = DB::all("SELECT c.*, co.title AS course_title, co.slug AS course_slug, (SELECT COALESCE(SUM(r.discount),0) FROM {coupon_redemptions} r WHERE r.coupon_id = c.id) AS saved FROM {coupons} c LEFT JOIN {courses} co ON co.id = c.course_id WHERE $w ORDER BY c.id DESC" . $pg->limitSql(), $p);
        return view('coupons/index', ['rows' => $rows, 'pg' => $pg]);}

    private function courses(): array {
        return DB::all("SELECT id, title, slug FROM {courses} WHERE status <> 'archived' ORDER BY title");}

    public function create() {
        return view('coupons/form', ['c' => ['id' => 0, 'code' => strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)), 'type' => 'percent', 'value' => 10, 'course_id' => null, 'max_uses' => null, 'per_user_limit' => 1, 'starts_at' => null, 'expires_at' => null, 'active' => 1, 'note' => '', 'used_count' => 0], 'courses' => $this->courses(), 'uses' => []]);}

    private function data(int $id = 0): array {
        $d = $this->validate(['code' => 'required|max:40', 'type' => 'required|in:percent,fixed', 'value' => 'required|numeric', 'course_id' => 'int', 'max_uses' => 'int', 'per_user_limit' => 'int', 'starts_at' => 'datetime', 'expires_at' => 'datetime', 'note' => 'max:255']);
        $code = CouponService::normalize($d['code']);
        if (!preg_match('/^[A-Z0-9_-]{3,40}$/', $code)) {
            back(['code' => 'Use 3–40 letters, numbers, dashes or underscores.']);}
        if (DB::val('SELECT id FROM {coupons} WHERE code = ? AND id <> ?', [$code, $id])) {
            back(['code' => 'This code already exists.']);}
        $val = (float)$d['value'];
        if ($val <= 0 || ($d['type'] === 'percent' && $val > 100)) {
            back(['value' => $d['type'] === 'percent' ? 'Enter a percentage between 1 and 100.' : 'Enter an amount greater than 0.']);}
        $cid = (int)$d['course_id'];
        if ($cid && !DB::val('SELECT id FROM {courses} WHERE id = ?', [$cid])) {
            $cid = 0;}
        $start = $d['starts_at'] ? to_utc($d['starts_at']) : null;
        $exp = $d['expires_at'] ? to_utc($d['expires_at']) : null;
        if ($start && $exp && $exp <= $start) {
            back(['expires_at' => 'Expiry must be after the start date.']);}
        return ['code' => $code, 'type' => $d['type'], 'value' => round($val, 2), 'course_id' => $cid ?: null, 'max_uses' => $d['max_uses'] !== '' && $d['max_uses'] !== null && (int)$d['max_uses'] > 0 ? (int)$d['max_uses'] : null,
            'per_user_limit' => max(0, (int)$d['per_user_limit']), 'starts_at' => $start, 'expires_at' => $exp, 'active' => $this->req->bool('active') ? 1 : 0, 'note' => $d['note'] ?: null, 'updated_at' => now()];}

    public function store() {
        $row = $this->data();
        $row['created_by'] = Auth::id();
        $row['created_at'] = now();
        $id = DB::insert('coupons', $row);
        Audit::log('coupon.created', 'coupon', $id, ['code' => $row['code']]);
        $this->ok('Coupon ' . $row['code'] . ' created.', '/admin/coupons/' . $id . '/edit');}

    private function coupon(string $id): array {
        return DB::one('SELECT * FROM {coupons} WHERE id = ?', [(int)$id]) ?? abort(404);}

    public function edit(string $id) {
        $c = $this->coupon($id);
        $uses = DB::all('SELECT r.*, u.name, u.email, o.ref, co.title AS course_title FROM {coupon_redemptions} r JOIN {users} u ON u.id = r.user_id LEFT JOIN {orders} o ON o.id = r.order_id LEFT JOIN {courses} co ON co.id = r.course_id WHERE r.coupon_id = ? ORDER BY r.id DESC LIMIT 100', [$c['id']]);
        return view('coupons/form', ['c' => $c, 'courses' => $this->courses(), 'uses' => $uses]);}

    public function update(string $id) {
        $c = $this->coupon($id);
        $row = $this->data((int)$c['id']);
        DB::update('coupons', $row, 'id = ?', [$c['id']]);
        Audit::log('coupon.updated', 'coupon', (int)$c['id'], ['code' => $row['code']]);
        $this->ok('Coupon saved.');}

    public function toggle(string $id) {
        $c = $this->coupon($id);
        DB::update('coupons', ['active' => (int)$c['active'] ? 0 : 1, 'updated_at' => now()], 'id = ?', [$c['id']]);
        Audit::log('coupon.toggled', 'coupon', (int)$c['id'], ['active' => !(int)$c['active']]);
        $this->ok((int)$c['active'] ? 'Coupon paused.' : 'Coupon activated.');}

    public function destroy(string $id) {
        $c = $this->coupon($id);
        DB::delete('coupons', 'id = ?', [$c['id']]);
        Audit::log('coupon.deleted', 'coupon', (int)$c['id'], ['code' => $c['code']]);
        $this->ok('Coupon deleted.', '/admin/coupons');}}
