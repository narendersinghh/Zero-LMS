<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Audit;
use App\Core\DB;
use App\Core\Paginator;
use App\Services\Enrollments;

class EnrollmentController extends Controller {
    public function index() {
        $w = ['1=1'];
        $p = [];
        $now = now();
        if ($cid = $this->req->int('course')) {
            $w[] = 'e.course_id = ?';
            $p[] = $cid;}
        $q = $this->req->str('q');
        if ($q !== '') {
            $w[] = '(u.name LIKE ? OR u.email LIKE ?)';
            array_push($p, DB::like($q), DB::like($q));}
        switch ($this->req->str('state')) {
            case 'active':
                $w[] = "e.status = 'active' AND (e.expires_at IS NULL OR e.expires_at > ?) AND e.starts_at <= ?";
                array_push($p, $now, $now);
                break;
            case 'expiring':
                $w[] = "e.status = 'active' AND e.expires_at > ? AND e.expires_at <= ?";
                array_push($p, $now, gmdate('Y-m-d H:i:s', time() + 7 * 86400));
                break;
            case 'expired':
                $w[] = 'e.expires_at IS NOT NULL AND e.expires_at <= ?';
                $p[] = $now;
                break;
            case 'pending':
                $w[] = 'e.starts_at > ?';
                $p[] = $now;
                break;
            case 'suspended':
                $w[] = "e.status = 'suspended'";
                break;
            case 'completed':
                $w[] = 'e.completed_at IS NOT NULL';
                break;}
        $ws = implode(' AND ', $w);
        $pg = new Paginator((int)DB::val("SELECT COUNT(*) FROM {enrollments} e JOIN {users} u ON u.id = e.user_id WHERE $ws", $p), 40);
        $rows = DB::all("SELECT e.*, u.name, u.email, c.title AS course_title, c.status AS course_status,
            (SELECT COUNT(*) FROM {lesson_progress} lp WHERE lp.user_id = e.user_id AND lp.course_id = e.course_id AND lp.completed_at IS NOT NULL) AS done,
            (SELECT COUNT(*) FROM {lessons} l WHERE l.course_id = e.course_id AND l.status = 'published') AS total
            FROM {enrollments} e JOIN {users} u ON u.id = e.user_id JOIN {courses} c ON c.id = e.course_id WHERE $ws ORDER BY e.id DESC" . $pg->limitSql(), $p);
        $courses = DB::all('SELECT id, title FROM {courses} ORDER BY title');
        return view('admin/enrollments/index', ['rows' => $rows, 'pg' => $pg, 'courses' => $courses]);}

    public function update(string $id) {
        $e = DB::one('SELECT * FROM {enrollments} WHERE id = ?', [(int)$id]) ?? abort(404);
        $d = $this->validate(['starts_at' => 'required|datetime', 'expires_at' => 'datetime', 'status' => 'required|in:active,suspended']);
        $exp = $this->req->bool('lifetime') ? null : ($d['expires_at'] ? to_utc($d['expires_at']) : null);
        $start = to_utc($d['starts_at']);
        if ($exp && $exp <= $start) {
            flash('error', 'Expiry must be after the start date.');
            back();}
        $extended = $exp === null ? $e['expires_at'] !== null : ($e['expires_at'] && $exp > $e['expires_at']);
        DB::update('enrollments', ['starts_at' => $start, 'status' => $d['status'], 'updated_at' => now()], 'id = ?', [$e['id']]);
        if ($exp !== $e['expires_at']) {
            Enrollments::extend((int)$e['id'], $exp, $extended && $this->req->bool('notify'));}
        Audit::log('enrollment.updated', 'enrollment', (int)$e['id'], ['status' => $d['status'], 'starts_at' => $start, 'expires_at' => $exp]);
        $this->ok('Enrollment updated.');}

    public function destroy(string $id) {
        $e = DB::one('SELECT * FROM {enrollments} WHERE id = ?', [(int)$id]) ?? abort(404);
        DB::delete('enrollments', 'id = ?', [$e['id']]);
        if ($this->req->bool('reset_progress')) {
            DB::delete('lesson_progress', 'user_id = ? AND course_id = ?', [$e['user_id'], $e['course_id']]);}
        Audit::log('enrollment.deleted', 'enrollment', (int)$e['id'], ['user_id' => $e['user_id'], 'course_id' => $e['course_id']]);
        $this->ok('Enrollment removed.');}

    public function bulk() {
        $ids = array_values(array_filter(array_map('intval', $this->req->arr('ids'))));
        if (!$ids) {
            flash('error', 'Select at least one enrollment.');
            back();}
        $action = $this->req->str('action');
        $in = DB::in($ids);
        $n = 0;
        switch ($action) {
            case 'extend':
                $days = max(1, min(3650, $this->req->int('days', 7)));
                foreach (DB::all("SELECT id, expires_at FROM {enrollments} WHERE id IN ($in)", $ids) as $e) {
                    if ($e['expires_at'] === null) {
                        continue;}
                    $base = max(time(), strtotime($e['expires_at'] . ' UTC'));
                    Enrollments::extend((int)$e['id'], gmdate('Y-m-d H:i:s', $base + $days * 86400), $this->req->bool('notify'));
                    $n++;}
                break;
            case 'set_expiry':
                $exp = to_utc($this->req->str('date'));
                if (!$exp) {
                    flash('error', 'Choose a date.');
                    back();}
                foreach ($ids as $id) {
                    Enrollments::extend($id, $exp, $this->req->bool('notify'));
                    $n++;}
                break;
            case 'lifetime':
                foreach ($ids as $id) {
                    Enrollments::extend($id, null, $this->req->bool('notify'));
                    $n++;}
                break;
            case 'suspend':
            case 'activate':
                $n = DB::run("UPDATE {enrollments} SET status = ?, updated_at = ? WHERE id IN ($in)", array_merge([$action === 'suspend' ? 'suspended' : 'active', now()], $ids))->rowCount();
                break;
            case 'delete':
                $n = DB::run("DELETE FROM {enrollments} WHERE id IN ($in)", $ids)->rowCount();
                break;
            default:
                abort(400);}
        Audit::log('enrollments.bulk_' . $action, null, null, ['count' => $n]);
        $this->ok("Updated $n enrollment(s).");}}
