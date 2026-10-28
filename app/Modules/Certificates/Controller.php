<?php
namespace App\Modules\Certificates;

use App\Controllers\Controller as BaseController;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Paginator;

class Controller extends BaseController {
    public function mine() {
        return view('certificates/mine', ['certs' => Certificates::forUser(Auth::id())]);}

    public function show(string $code) {
        $cert = Certificates::byCode($code);
        if (!$cert || (!Auth::isStaff() && (int)$cert['user_id'] !== Auth::id()) || ($cert['revoked_at'] && !Auth::isStaff())) {
            abort(404);}
        return view('certificates/certificate', ['cert' => $cert]);}

    public function verifyForm() {
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($this->req->str('code')));
        if ($code !== '') {
            redirect('/verify/' . $code);}
        return view('certificates/verify', ['cert' => null, 'code' => '', 'searched' => false]);}

    public function verify(string $code) {
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($code));
        return view('certificates/verify', ['cert' => $code !== '' ? Certificates::byCode($code) : null, 'code' => $code, 'searched' => true]);}

    public function adminIndex() {
        $w = ['1=1'];
        $p = [];
        if (($q = $this->req->str('q')) !== '') {
            $w[] = '(u.name LIKE ? OR u.email LIKE ? OR ct.code LIKE ? OR c.title LIKE ?)';
            array_push($p, DB::like($q), DB::like($q), DB::like($q), DB::like($q));}
        if ($cid = $this->req->int('course')) {
            $w[] = 'ct.course_id = ?';
            $p[] = $cid;}
        $ws = implode(' AND ', $w);
        $base = "FROM {certificates} ct JOIN {users} u ON u.id = ct.user_id JOIN {courses} c ON c.id = ct.course_id WHERE $ws";
        $pg = new Paginator((int)DB::val("SELECT COUNT(*) $base", $p), 30);
        $rows = DB::all("SELECT ct.*, u.name, u.email, c.title AS course_title $base ORDER BY ct.issued_at DESC" . $pg->limitSql(), $p);
        return view('certificates/admin', ['rows' => $rows, 'pg' => $pg, 'courses' => DB::all('SELECT id, title FROM {courses} ORDER BY title'), 'total' => (int)DB::val('SELECT COUNT(*) FROM {certificates} WHERE revoked_at IS NULL')]);}

    public function adminIssue() {
        $d = $this->validate(['email' => 'required|email', 'course_id' => 'required|int']);
        $u = DB::one('SELECT id FROM {users} WHERE email = ?', [strtolower($d['email'])]);
        if (!$u) {
            flash('error', 'No user with that email.');
            back();}
        if (!DB::val('SELECT id FROM {courses} WHERE id = ?', [(int)$d['course_id']])) {
            abort(404);}
        $cert = Certificates::issue((int)$u['id'], (int)$d['course_id'], $this->req->bool('notify'), Auth::id());
        $this->ok('Certificate ' . $cert['code'] . ' issued.');}

    public function adminRevoke(string $id) {
        $c = DB::one('SELECT * FROM {certificates} WHERE id = ?', [(int)$id]) ?? abort(404);
        DB::update('certificates', ['revoked_at' => now()], 'id = ?', [$c['id']]);
        Audit::log('certificate.revoked', 'certificate', (int)$c['id'], ['code' => $c['code']]);
        $this->ok('Certificate revoked. Verification now shows it as revoked.');}

    public function adminRestore(string $id) {
        $c = DB::one('SELECT * FROM {certificates} WHERE id = ?', [(int)$id]) ?? abort(404);
        DB::update('certificates', ['revoked_at' => null], 'id = ?', [$c['id']]);
        Audit::log('certificate.restored', 'certificate', (int)$c['id'], ['code' => $c['code']]);
        $this->ok('Certificate restored.');}}
