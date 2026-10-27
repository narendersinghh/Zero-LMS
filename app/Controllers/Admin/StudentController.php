<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csv;
use App\Core\DB;
use App\Core\Paginator;
use App\Services\Courses;
use App\Services\Enrollments;
use App\Services\Progress;
use App\Services\Users;

class StudentController extends Controller {
    private function filters(): array {
        $w = ["u.role = 'student'"];
        $p = [];
        $q = $this->req->str('q');
        if ($q !== '') {
            $w[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            array_push($p, DB::like($q), DB::like($q), DB::like($q));}
        switch ($this->req->str('status')) {
            case 'active':
                $w[] = "u.status = 'active' AND u.password_hash IS NOT NULL";
                break;
            case 'pending':
                $w[] = 'u.password_hash IS NULL';
                break;
            case 'suspended':
                $w[] = "u.status = 'suspended'";
                break;}
        if ($cid = $this->req->int('course')) {
            $w[] = 'EXISTS (SELECT 1 FROM {enrollments} e WHERE e.user_id = u.id AND e.course_id = ?)';
            $p[] = $cid;}
        return [implode(' AND ', $w), $p];}

    public function index() {
        [$w, $p] = $this->filters();
        $pg = new Paginator((int)DB::val("SELECT COUNT(*) FROM {users} u WHERE $w", $p), 30);
        $sort = ['name' => 'u.name', 'email' => 'u.email', 'login' => 'u.last_login_at DESC', 'new' => 'u.id DESC'][$this->req->str('sort', 'new')] ?? 'u.id DESC';
        $rows = DB::all("SELECT u.id, u.name, u.email, u.avatar, u.status, u.password_hash IS NOT NULL AS activated, u.last_login_at, u.created_at, (SELECT COUNT(*) FROM {enrollments} e WHERE e.user_id = u.id) AS courses FROM {users} u WHERE $w ORDER BY $sort" . $pg->limitSql(), $p);
        $courses = DB::all("SELECT id, title FROM {courses} WHERE status <> 'archived' ORDER BY title");
        return view('admin/students/index', ['rows' => $rows, 'pg' => $pg, 'courses' => $courses]);}

    public function export() {
        [$w, $p] = $this->filters();
        $rows = DB::all("SELECT u.id, u.name, u.email, u.phone, u.status, IF(u.password_hash IS NULL, 'pending', 'yes') AS activated, u.last_login_at, u.created_at, (SELECT GROUP_CONCAT(c.title SEPARATOR '; ') FROM {enrollments} e JOIN {courses} c ON c.id = e.course_id WHERE e.user_id = u.id) AS courses FROM {users} u WHERE $w ORDER BY u.id", $p);
        Audit::log('students.exported', null, null, ['count' => count($rows)]);
        Csv::download('students-' . gmdate('Y-m-d') . '.csv', ['ID', 'Name', 'Email', 'Phone', 'Status', 'Activated', 'Last login (UTC)', 'Created (UTC)', 'Courses'], $rows);}

    public function create() {
        return view('admin/students/create', ['courses' => DB::all("SELECT id, title, access_days, end_date FROM {courses} WHERE status <> 'archived' ORDER BY title")]);}

    public function store() {
        $d = $this->validate(['name' => 'max:150', 'email' => 'required|email|unique:users,email', 'phone' => 'max:40', 'password' => 'password', 'starts_at' => 'datetime', 'expires_at' => 'datetime']);
        $courseIds = array_map('intval', $this->req->arr('courses'));
        $id = Users::create(['name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'] ?: null, 'password' => $d['password'] ?: null]);
        $titles = $this->enrollMany($id, $courseIds, $d['starts_at'] ? to_utc($d['starts_at']) : null, $d['expires_at'] ? to_utc($d['expires_at']) : null, false);
        if ($this->req->bool('send_email')) {
            $u = Users::find($id);
            if (!$d['password']) {
                Users::sendWelcome($u, $titles);}
            else {
                foreach ($this->coursesByIds($courseIds) as $c) {
                    $e = \App\Services\Access::enrollment($id, (int)$c['id']);
                    Enrollments::sendEnrolledEmail($u, $c, $e['expires_at'] ?? null, $e['starts_at'] ?? null);}}}
        $this->ok('Student created' . ($titles ? ' and enrolled in ' . count($titles) . ' course(s)' : '') . '.', '/admin/students/' . $id);}

    private function coursesByIds(array $ids): array {
        $ids = array_values(array_filter(array_unique($ids)));
        return $ids ? DB::all('SELECT * FROM {courses} WHERE id IN (' . DB::in($ids) . ')', $ids) : [];}

    private function enrollMany(int $userId, array $courseIds, ?string $start, ?string $expires, bool $notify): array {
        $titles = [];
        foreach ($this->coursesByIds($courseIds) as $c) {
            Enrollments::enroll($userId, $c, ['starts_at' => $start ?? now(), 'expires_at' => $expires, 'source' => 'manual', 'notify' => $notify]);
            $titles[] = $c['title'];}
        return $titles;}

    public function show(string $id) {
        $u = DB::one("SELECT * FROM {users} WHERE id = ? AND role = 'student'", [(int)$id]) ?? abort(404);
        $enr = DB::all('SELECT e.*, c.title, c.slug, c.status AS course_status, c.start_date, c.end_date, c.access_days, c.release_time FROM {enrollments} e JOIN {courses} c ON c.id = e.course_id WHERE e.user_id = ? ORDER BY e.created_at DESC', [$u['id']]);
        $progress = Progress::forCourses((int)$u['id'], array_column($enr, 'course_id'));
        $recent = DB::all('SELECT p.*, l.title, c.title AS course_title FROM {lesson_progress} p JOIN {lessons} l ON l.id = p.lesson_id JOIN {courses} c ON c.id = p.course_id WHERE p.user_id = ? ORDER BY p.last_viewed_at DESC LIMIT 15', [$u['id']]);
        $logs = DB::all("SELECT * FROM {audit_logs} WHERE (entity_type = 'user' AND entity_id = ?) OR user_id = ? ORDER BY id DESC LIMIT 15", [$u['id'], $u['id']]);
        $courses = DB::all("SELECT id, title FROM {courses} WHERE status <> 'archived' AND id NOT IN (SELECT course_id FROM {enrollments} WHERE user_id = ?) ORDER BY title", [$u['id']]);
        $mails = DB::all('SELECT id, subject, status, created_at, sent_at, last_error FROM {mail_queue} WHERE to_email = ? ORDER BY id DESC LIMIT 10', [$u['email']]);
        return view('admin/students/show', ['u' => $u, 'enr' => $enr, 'progress' => $progress, 'recent' => $recent, 'logs' => $logs, 'courses' => $courses, 'mails' => $mails]);}

    public function update(string $id) {
        $u = DB::one("SELECT * FROM {users} WHERE id = ? AND role = 'student'", [(int)$id]) ?? abort(404);
        $d = $this->validate(['name' => 'required|max:150', 'email' => 'required|email|unique:users,email,' . $u['id'], 'phone' => 'max:40', 'password' => 'password']);
        $row = ['name' => $d['name'], 'email' => strtolower($d['email']), 'phone' => $d['phone'] ?: null, 'updated_at' => now()];
        DB::update('users', $row, 'id = ?', [$u['id']]);
        if ($d['password']) {
            Users::setPassword((int)$u['id'], $d['password']);}
        Audit::log('student.updated', 'user', (int)$u['id'], ['email_changed' => $u['email'] !== $row['email'], 'password_changed' => (bool)$d['password']]);
        $this->ok('Student saved.');}

    public function enroll(string $id) {
        $u = Users::find((int)$id) ?? abort(404);
        $d = $this->validate(['course_id' => 'required|int', 'starts_at' => 'datetime', 'expires_at' => 'datetime']);
        $c = Courses::find((int)$d['course_id']) ?? abort(404);
        Enrollments::enroll((int)$u['id'], $c, ['starts_at' => $d['starts_at'] ? to_utc($d['starts_at']) : now(), 'expires_at' => $d['expires_at'] ? to_utc($d['expires_at']) : null, 'notify' => $this->req->bool('notify') && $u['password_hash']]);
        if ($this->req->bool('notify') && !$u['password_hash']) {
            Users::sendWelcome($u, [$c['title']]);}
        $this->ok('Enrolled in ' . $c['title'] . '.');}

    public function invite(string $id) {
        $u = Users::find((int)$id) ?? abort(404);
        if ($u['password_hash']) {
            Users::sendReset($u);}
        else {
            $titles = DB::col('SELECT c.title FROM {enrollments} e JOIN {courses} c ON c.id = e.course_id WHERE e.user_id = ?', [$u['id']]);
            Users::sendWelcome($u, $titles);}
        Audit::log('student.invite_sent', 'user', (int)$u['id']);
        $this->ok($u['password_hash'] ? 'Password reset link sent.' : 'Account setup email sent.');}

    public function status(string $id) {
        $u = DB::one("SELECT * FROM {users} WHERE id = ? AND role = 'student'", [(int)$id]) ?? abort(404);
        $st = $u['status'] === 'active' ? 'suspended' : 'active';
        DB::update('users', ['status' => $st, 'updated_at' => now()], 'id = ?', [$u['id']]);
        if ($st === 'suspended') {
            Auth::bumpSessionVersion((int)$u['id']);}
        Audit::log('student.' . $st, 'user', (int)$u['id']);
        $this->ok($st === 'active' ? 'Student re-activated.' : 'Student suspended and signed out.');}

    public function destroy(string $id) {
        $u = DB::one("SELECT * FROM {users} WHERE id = ? AND role = 'student'", [(int)$id]) ?? abort(404);
        DB::delete('users', 'id = ?', [$u['id']]);
        \App\Core\Uploads::deletePublic($u['avatar']);
        Audit::log('student.deleted', 'user', (int)$u['id'], ['email' => $u['email']]);
        $this->ok('Student deleted.', '/admin/students');}

    public function bulk() {
        $ids = array_values(array_filter(array_map('intval', $this->req->arr('ids'))));
        $action = $this->req->str('action');
        if (!$ids) {
            flash('error', 'Select at least one student.');
            back();}
        $users = DB::all("SELECT * FROM {users} WHERE role = 'student' AND id IN (" . DB::in($ids) . ')', $ids);
        $n = 0;
        switch ($action) {
            case 'enroll':
                $c = Courses::find($this->req->int('course_id'));
                if (!$c) {
                    flash('error', 'Choose a course.');
                    back();}
                foreach ($users as $u) {
                    Enrollments::enroll((int)$u['id'], $c, ['source' => 'bulk', 'notify' => $this->req->bool('notify') && $u['password_hash']]);
                    $n++;}
                break;
            case 'suspend':
            case 'activate':
                $st = $action === 'suspend' ? 'suspended' : 'active';
                $n = DB::run("UPDATE {users} SET status = ? WHERE role = 'student' AND id IN (" . DB::in($ids) . ')', array_merge([$st], $ids))->rowCount();
                if ($st === 'suspended') {
                    DB::run('UPDATE {users} SET session_version = session_version + 1 WHERE id IN (' . DB::in($ids) . ')', $ids);}
                break;
            case 'invite':
                foreach ($users as $u) {
                    if (!$u['password_hash'] && $u['status'] === 'active') {
                        Users::sendWelcome($u);
                        $n++;}}
                break;
            case 'delete':
                $n = DB::run("DELETE FROM {users} WHERE role = 'student' AND id IN (" . DB::in($ids) . ')', $ids)->rowCount();
                break;
            default:
                abort(400);}
        Audit::log('students.bulk_' . $action, null, null, ['count' => $n, 'ids' => array_slice($ids, 0, 100)]);
        $this->ok("Bulk action applied to $n student(s).");}}
