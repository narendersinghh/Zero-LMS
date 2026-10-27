<?php
namespace App\Modules\Announcements;

use App\Controllers\Controller as BaseController;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Html;
use App\Core\Paginator;

class Controller extends BaseController {
    public function index() {
        return view('announcements/index', ['list' => Announcements::forUser(Auth::id())]);}

    public function adminIndex() {
        $pg = new Paginator((int)DB::val('SELECT COUNT(*) FROM {announcements}'), 30);
        $rows = DB::all('SELECT a.*, c.title AS course_title, u.name AS author FROM {announcements} a LEFT JOIN {courses} c ON c.id = a.course_id LEFT JOIN {users} u ON u.id = a.created_by ORDER BY a.pinned DESC, COALESCE(a.published_at, a.created_at) DESC' . $pg->limitSql());
        return view('announcements/admin', ['rows' => $rows, 'pg' => $pg]);}

    private function courses(): array {
        return DB::all("SELECT id, title FROM {courses} WHERE status <> 'archived' ORDER BY title");}

    public function create() {
        return view('announcements/form', ['a' => ['id' => 0, 'title' => '', 'body' => '', 'course_id' => null, 'pinned' => 0, 'status' => 'published', 'published_at' => null, 'notified_at' => null], 'courses' => $this->courses()]);}

    public function edit(string $id) {
        $a = DB::one('SELECT * FROM {announcements} WHERE id = ?', [(int)$id]) ?? abort(404);
        return view('announcements/form', ['a' => $a, 'courses' => $this->courses()]);}

    private function data(?array $old = null): array {
        $d = $this->validate(['title' => 'required|max:200', 'course_id' => 'int', 'status' => 'required|in:draft,published', 'published_at' => 'datetime']);
        $cid = (int)$d['course_id'];
        if ($cid && !DB::val('SELECT id FROM {courses} WHERE id = ?', [$cid])) {
            $cid = 0;}
        $pub = $d['status'] === 'published' ? ($d['published_at'] ? to_utc($d['published_at']) : ($old['published_at'] ?? now())) : null;
        return ['title' => $d['title'], 'body' => Html::clean($this->req->input('body', '')), 'course_id' => $cid ?: null, 'pinned' => $this->req->bool('pinned') ? 1 : 0, 'status' => $d['status'], 'published_at' => $pub, 'updated_at' => now()];}

    private function afterSave(int $id): string {
        $a = DB::one('SELECT * FROM {announcements} WHERE id = ?', [$id]);
        if ($a['status'] === 'published' && $a['published_at'] <= now() && $this->req->bool('notify')) {
            $n = Announcements::notify($a, $this->req->bool('email'));
            return " Notified $n student(s).";}
        return '';}

    public function store() {
        $row = $this->data() + ['created_by' => Auth::id(), 'created_at' => now()];
        $id = DB::insert('announcements', $row);
        Audit::log('announcement.created', 'announcement', $id, ['title' => $row['title']]);
        $this->ok('Announcement saved.' . $this->afterSave($id), '/admin/announcements');}

    public function update(string $id) {
        $a = DB::one('SELECT * FROM {announcements} WHERE id = ?', [(int)$id]) ?? abort(404);
        DB::update('announcements', $this->data($a), 'id = ?', [$a['id']]);
        Audit::log('announcement.updated', 'announcement', (int)$a['id']);
        $this->ok('Announcement updated.' . $this->afterSave((int)$a['id']), '/admin/announcements');}

    public function destroy(string $id) {
        $a = DB::one('SELECT * FROM {announcements} WHERE id = ?', [(int)$id]) ?? abort(404);
        DB::delete('announcements', 'id = ?', [$a['id']]);
        Audit::log('announcement.deleted', 'announcement', (int)$a['id'], ['title' => $a['title']]);
        $this->ok('Announcement deleted.', '/admin/announcements');}}
