<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Paginator;

class NotificationController extends Controller {
    public function index() {
        $uid = Auth::id();
        $pg = new Paginator((int)DB::val('SELECT COUNT(*) FROM {notifications} WHERE user_id = ?', [$uid]), 20);
        $items = DB::all('SELECT * FROM {notifications} WHERE user_id = ? ORDER BY id DESC' . $pg->limitSql(), [$uid]);
        return view('student/notifications', ['items' => $items, 'pg' => $pg]);}

    public function readAll() {
        DB::run('UPDATE {notifications} SET read_at = ? WHERE user_id = ? AND read_at IS NULL', [now(), Auth::id()]);
        if ($this->req->isAjax()) {
            json_out(['ok' => true]);}
        back([], false);}

    public function open(string $id) {
        $n = DB::one('SELECT * FROM {notifications} WHERE id = ? AND user_id = ?', [(int)$id, Auth::id()]);
        if (!$n) {
            abort(404);}
        if (!$n['read_at']) {
            DB::update('notifications', ['read_at' => now()], 'id = ?', [$n['id']]);}
        $u = (string)$n['url'];
        redirect($u && $u[0] === '/' && strpos($u, '//') !== 0 ? $u : '/notifications');}}
