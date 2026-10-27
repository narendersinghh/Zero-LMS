<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csv;
use App\Core\DB;
use App\Core\Paginator;
use App\Services\Courses;

class ReportController extends Controller {
    public function index() {
        $rows = DB::all("SELECT c.id, c.title, c.slug, c.status, c.cover_image,
            (SELECT COUNT(*) FROM {lessons} l WHERE l.course_id = c.id AND l.status = 'published') AS lessons,
            (SELECT COUNT(*) FROM {enrollments} e WHERE e.course_id = c.id) AS enrolled,
            (SELECT COUNT(*) FROM {enrollments} e WHERE e.course_id = c.id AND e.completed_at IS NOT NULL) AS completed,
            (SELECT COUNT(*) FROM {lesson_progress} p WHERE p.course_id = c.id AND p.completed_at IS NOT NULL) AS lesson_completions,
            (SELECT COUNT(DISTINCT p.user_id) FROM {lesson_progress} p WHERE p.course_id = c.id) AS started
            FROM {courses} c ORDER BY c.status = 'archived', c.id DESC");
        foreach ($rows as &$r) {
            $r['avg'] = $r['enrolled'] && $r['lessons'] ? (int)round($r['lesson_completions'] / ($r['enrolled'] * $r['lessons']) * 100) : 0;}
        return view('admin/reports/index', ['rows' => $rows]);}

    private function courseRows(int $courseId, string $limit = ''): array {
        return DB::all("SELECT u.id, u.name, u.email, e.starts_at, e.expires_at, e.status, e.completed_at, e.last_accessed_at,
            COUNT(p.completed_at) AS done, MAX(p.last_viewed_at) AS last_view, COALESCE(SUM(p.position), 0) AS watched
            FROM {enrollments} e JOIN {users} u ON u.id = e.user_id
            LEFT JOIN {lesson_progress} p ON p.user_id = e.user_id AND p.course_id = e.course_id
            WHERE e.course_id = ? GROUP BY e.id ORDER BY done DESC, u.name" . $limit, [$courseId]);}

    public function course(string $id) {
        $course = Courses::find((int)$id) ?? abort(404);
        $total = (int)DB::val("SELECT COUNT(*) FROM {lessons} WHERE course_id = ? AND status = 'published'", [$course['id']]);
        $pg = new Paginator((int)DB::val('SELECT COUNT(*) FROM {enrollments} WHERE course_id = ?', [$course['id']]), 50);
        $rows = $this->courseRows((int)$course['id'], $pg->limitSql());
        $lessonStats = DB::all("SELECT l.id, l.title, l.release_day, COUNT(p.id) AS viewers, COUNT(p.completed_at) AS completions, ROUND(AVG(p.percent)) AS avg_pct FROM {lessons} l LEFT JOIN {lesson_progress} p ON p.lesson_id = l.id WHERE l.course_id = ? AND l.status = 'published' GROUP BY l.id ORDER BY l.sort_order, l.id", [$course['id']]);
        $dist = DB::one("SELECT SUM(pct = 0) a, SUM(pct > 0 AND pct < 25) b, SUM(pct >= 25 AND pct < 50) c, SUM(pct >= 50 AND pct < 75) d, SUM(pct >= 75 AND pct < 100) e, SUM(pct >= 100) f FROM (SELECT e.id, IF(? = 0, 0, COUNT(p.completed_at) / ? * 100) pct FROM {enrollments} e LEFT JOIN {lesson_progress} p ON p.user_id = e.user_id AND p.course_id = e.course_id AND p.completed_at IS NOT NULL WHERE e.course_id = ? GROUP BY e.id) t", [$total, max(1, $total), $course['id']]);
        return view('admin/reports/course', ['course' => $course, 'rows' => $rows, 'pg' => $pg, 'total' => $total, 'lessonStats' => $lessonStats, 'dist' => $dist]);}

    public function export(string $id) {
        $course = Courses::find((int)$id) ?? abort(404);
        $total = (int)DB::val("SELECT COUNT(*) FROM {lessons} WHERE course_id = ? AND status = 'published'", [$course['id']]);
        $out = [];
        foreach ($this->courseRows((int)$course['id']) as $r) {
            $out[] = [$r['name'], $r['email'], $r['status'], $r['starts_at'], $r['expires_at'] ?: 'Lifetime', $r['done'] . '/' . $total, $total ? floor($r['done'] / $total * 100) . '%' : '0%', $r['completed_at'], $r['last_view']];}
        \App\Core\Audit::log('report.exported', 'course', (int)$course['id']);
        Csv::download(slugify($course['title']) . '-progress-' . gmdate('Y-m-d') . '.csv', ['Name', 'Email', 'Enrollment status', 'Starts (UTC)', 'Expires (UTC)', 'Lessons completed', 'Progress', 'Course completed (UTC)', 'Last activity (UTC)'], $out);}}
