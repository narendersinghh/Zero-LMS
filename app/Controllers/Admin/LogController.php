<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\DB;
use App\Core\Paginator;

class LogController extends Controller {
    public function index() {
        $w = ['1=1'];
        $p = [];
        if (($q = $this->req->str('q')) !== '') {
            $w[] = '(a.action LIKE ? OR a.details LIKE ? OR u.email LIKE ? OR a.ip LIKE ?)';
            array_push($p, DB::like($q), DB::like($q), DB::like($q), DB::like($q));}
        if (($act = $this->req->str('action')) !== '') {
            $w[] = 'a.action LIKE ?';
            $p[] = addcslashes($act, '%_') . '%';}
        if (($from = $this->req->str('from')) !== '') {
            $w[] = 'a.created_at >= ?';
            $p[] = to_utc($from . ' 00:00:00');}
        if (($to = $this->req->str('to')) !== '') {
            $w[] = 'a.created_at <= ?';
            $p[] = to_utc($to . ' 23:59:59');}
        if ($uid = $this->req->int('user')) {
            $w[] = 'a.user_id = ?';
            $p[] = $uid;}
        $ws = implode(' AND ', $w);
        $pg = new Paginator((int)DB::val("SELECT COUNT(*) FROM {audit_logs} a LEFT JOIN {users} u ON u.id = a.user_id WHERE $ws", $p), 50);
        $rows = DB::all("SELECT a.*, u.name, u.email FROM {audit_logs} a LEFT JOIN {users} u ON u.id = a.user_id WHERE $ws ORDER BY a.id DESC" . $pg->limitSql(), $p);
        $groups = DB::col("SELECT DISTINCT SUBSTRING_INDEX(action, '.', 1) FROM {audit_logs} ORDER BY 1");
        return view('admin/logs/index', ['rows' => $rows, 'pg' => $pg, 'groups' => $groups]);}}
