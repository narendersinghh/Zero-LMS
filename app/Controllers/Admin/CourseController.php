<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Audit;
use App\Core\DB;
use App\Core\Html;
use App\Core\Paginator;
use App\Core\Uploads;
use App\Services\Courses;

class CourseController extends Controller {
    public function index() {
        $q = $this->req->str('q');
        $status = $this->req->str('status');
        $where = ['1=1'];
        $p = [];
        if ($q !== '') {
            $where[] = '(c.title LIKE ? OR c.slug LIKE ? OR c.category LIKE ?)';
            array_push($p, DB::like($q), DB::like($q), DB::like($q));}
        if ($status !== '' && isset(Courses::STATUSES[$status])) {
            $where[] = 'c.status = ?';
            $p[] = $status;}
        elseif ($status === '') {
            $where[] = "c.status <> 'archived'";}
        $w = implode(' AND ', $where);
        $pg = new Paginator((int)DB::val("SELECT COUNT(*) FROM {courses} c WHERE $w", $p), 24);
        $courses = DB::all("SELECT c.*, (SELECT COUNT(*) FROM {lessons} l WHERE l.course_id = c.id) AS lesson_count, (SELECT COUNT(*) FROM {enrollments} e WHERE e.course_id = c.id) AS enrolled, (SELECT COUNT(*) FROM {enrollments} e WHERE e.course_id = c.id AND e.completed_at IS NOT NULL) AS completed,
            (SELECT COALESCE(SUM(o.amount), 0) FROM {orders} o WHERE o.course_id = c.id AND o.status = 'paid') AS revenue, (SELECT COUNT(*) FROM {modules} m WHERE m.course_id = c.id) AS module_count FROM {courses} c WHERE $w ORDER BY c.sort_order, c.id DESC" . $pg->limitSql(), $p);
        $counts = DB::pairs('SELECT status, COUNT(*) FROM {courses} GROUP BY status');
        $videoCount = (int)DB::val('SELECT COUNT(*) FROM {videos}');
        return view('admin/courses/index', ['courses' => $courses, 'pg' => $pg, 'q' => $q, 'status' => $status, 'counts' => $counts, 'videoCount' => $videoCount]);}

    public function create() {
        return view('admin/courses/form', ['course' => ['id' => 0, 'title' => '', 'slug' => '', 'subtitle' => '', 'description' => '', 'cover_image' => '', 'category' => '', 'instructor_name' => '', 'status' => 'draft', 'start_date' => '', 'end_date' => '', 'access_days' => '', 'release_time' => '00:00:00', 'quotes' => '', 'sort_order' => 0]]);}

    private function data(int $id = 0): array {
        $d = $this->validate([
            'title' => 'required|max:200', 'slug' => 'max:140', 'subtitle' => 'max:255', 'category' => 'max:100', 'instructor_name' => 'max:150', 'status' => 'required|in:draft,published,archived',
            'start_date' => 'date', 'end_date' => 'date', 'access_days' => 'int', 'release_time' => 'max:8', 'sort_order' => 'int', 'price' => 'numeric', 'sale_price' => 'numeric', 'color' => 'max:20', 'illustration' => 'max:40',]);
        $slug = $d['slug'] !== '' ? slugify($d['slug']) : '';
        $slug = Courses::uniqueSlug($slug ?: $d['title'], $id);
        if ($d['start_date'] && $d['end_date'] && $d['end_date'] < $d['start_date']) {
            back(['end_date' => 'End date must be after the start date.']);}
        $rt = preg_match('/^\d{2}:\d{2}/', (string)$d['release_time']) ? substr($d['release_time'], 0, 5) . ':00' : '00:00:00';
        return ['title' => $d['title'], 'slug' => $slug, 'subtitle' => $d['subtitle'] ?: null, 'description' => Html::clean($this->req->input('description', '')), 'category' => $d['category'] ?: null,
            'instructor_name' => $d['instructor_name'] ?: null, 'status' => $d['status'], 'start_date' => $d['start_date'] ?: null, 'end_date' => $d['end_date'] ?: null,
            'access_days' => $d['access_days'] !== '' && $d['access_days'] !== null && (int)$d['access_days'] > 0 ? (int)$d['access_days'] : null, 'release_time' => $rt,
            'quotes' => trim((string)$this->req->input('quotes', '')) ?: null, 'price' => $d['price'] !== '' && $d['price'] !== null ? max(0, round((float)$d['price'], 2)) : null, 'sale_price' => $d['sale_price'] !== '' && $d['sale_price'] !== null ? max(0, round((float)$d['sale_price'], 2)) : null,
            'is_public' => $this->req->bool('is_public') ? 1 : 0, 'highlights' => trim((string)$this->req->input('highlights', '')) ?: null, 'color' => in_array($d['color'], COURSE_TINTS, true) ? $d['color'] : null, 'illustration' => in_array($d['illustration'], COURSE_ILLUS, true) ? $d['illustration'] : null, 'sort_order' => (int)$d['sort_order'], 'archived_at' => $d['status'] === 'archived' ? now() : null, 'updated_at' => now()];}

    private function cover(array &$row, ?array $existing = null): void {
        if ($this->req->str('remove_cover') === '1' && $existing) {
            Uploads::deletePublic($existing['cover_image']);
            $row['cover_image'] = null;}
        if ($f = $this->req->file('cover_image')) {
            try {
                $row['cover_image'] = Uploads::image($f, 'courses', 5120);
                // Make the card-size copies now so no page view has to resize a large photo.
                thumb_url($row['cover_image'], 640);
                thumb_url($row['cover_image'], 320);
                if ($existing) {
                    Uploads::deletePublic($existing['cover_image']);}}
            catch (\Throwable $e) {
                flash('error', 'Cover image: ' . $e->getMessage());}}}

    public function store() {
        $row = $this->data();
        $this->cover($row);
        $row['created_by'] = \App\Core\Auth::id();
        $row['created_at'] = now();
        $id = DB::insert('courses', $row);
        Audit::log('course.created', 'course', $id, ['title' => $row['title']]);
        $this->ok('Course created. Now add modules and lessons.', '/admin/courses/' . $id . '/curriculum');}

    public function edit(string $id) {
        $course = Courses::find((int)$id) ?? abort(404);
        $stats = DB::one("SELECT COUNT(*) AS enrolled, SUM(status = 'active') AS active FROM {enrollments} WHERE course_id = ?", [(int)$id]);
        return view('admin/courses/form', ['course' => $course, 'stats' => $stats]);}

    public function update(string $id) {
        $course = Courses::find((int)$id) ?? abort(404);
        $row = $this->data((int)$id);
        if ($course['status'] === 'archived' && $row['status'] === 'archived') {
            $row['archived_at'] = $course['archived_at'];}
        $this->cover($row, $course);
        DB::update('courses', $row, 'id = ?', [(int)$id]);
        Audit::log('course.updated', 'course', (int)$id, ['title' => $row['title']]);
        $this->ok('Course saved.');}

    public function duplicate(string $id) {
        $new = Courses::duplicate((int)$id);
        $this->ok('Course duplicated as a draft.', '/admin/courses/' . $new . '/edit');}

    public function status(string $id) {
        $course = Courses::find((int)$id) ?? abort(404);
        $st = $this->req->str('status');
        if (!isset(Courses::STATUSES[$st])) {
            abort(400);}
        DB::update('courses', ['status' => $st, 'archived_at' => $st === 'archived' ? now() : null, 'updated_at' => now()], 'id = ?', [$course['id']]);
        Audit::log('course.status', 'course', (int)$course['id'], ['from' => $course['status'], 'to' => $st]);
        $this->ok('Course ' . strtolower(Courses::STATUSES[$st]) . '.');}

    public function destroy(string $id) {
        $course = Courses::find((int)$id) ?? abort(404);
        if ($this->req->str('confirm') !== $course['slug']) {
            flash('error', 'Type the course slug to confirm deletion.');
            back();}
        DB::delete('courses', 'id = ?', [$course['id']]);
        Uploads::deletePublic($course['cover_image']);
        Audit::log('course.deleted', 'course', (int)$course['id'], ['title' => $course['title']]);
        $this->ok('Course deleted permanently.', '/admin/courses');}}
