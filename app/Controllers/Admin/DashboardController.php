<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\DB;
use App\Core\Settings;

class DashboardController extends Controller {
    public function index() {
        $now = now();
        $week = gmdate('Y-m-d H:i:s', time() - 7 * 86400);
        $s = DB::one("SELECT
            (SELECT COUNT(*) FROM {users} WHERE role = 'student') AS students,
            (SELECT COUNT(*) FROM {users} WHERE role = 'student' AND created_at > ?) AS new_students,
            (SELECT COUNT(*) FROM {enrollments} WHERE status = 'active' AND (expires_at IS NULL OR expires_at > ?)) AS active_enrollments,
            (SELECT COUNT(*) FROM {enrollments} WHERE status = 'active' AND expires_at > ? AND expires_at <= ?) AS expiring,
            (SELECT COUNT(*) FROM {courses} WHERE status = 'published') AS courses,
            (SELECT COUNT(*) FROM {lessons}) AS lessons,
            (SELECT COUNT(*) FROM {lesson_progress} WHERE completed_at > ?) AS completions_week,
            (SELECT COUNT(DISTINCT user_id) FROM {lesson_progress} WHERE last_viewed_at > ?) AS active_learners,
            (SELECT COUNT(*) FROM {mail_queue} WHERE status = 'pending') AS mail_pending,
            (SELECT COUNT(*) FROM {mail_queue} WHERE status = 'failed') AS mail_failed,
            (SELECT COUNT(*) FROM {users} WHERE role = 'student' AND password_hash IS NULL) AS pending_setup", [$week, $now, $now, gmdate('Y-m-d H:i:s', time() + 7 * 86400), $week, $week]);
        $since = gmdate('Y-m-d', time() - 13 * 86400);
        $daily = DB::pairs('SELECT DATE(completed_at) d, COUNT(*) FROM {lesson_progress} WHERE completed_at >= ? GROUP BY d', [$since]);
        $views = DB::pairs('SELECT DATE(last_viewed_at) d, COUNT(DISTINCT user_id) FROM {lesson_progress} WHERE last_viewed_at >= ? GROUP BY d', [$since]);
        $chart = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = gmdate('Y-m-d', time() - $i * 86400);
            $chart[] = ['d' => $d, 'c' => (int)($daily[$d] ?? 0), 'v' => (int)($views[$d] ?? 0)];}
        $top = DB::all("SELECT c.id, c.title, c.slug, c.cover_image, c.status, COUNT(e.id) AS enrolled, SUM(e.completed_at IS NOT NULL) AS completed FROM {courses} c LEFT JOIN {enrollments} e ON e.course_id = c.id WHERE c.status <> 'archived' GROUP BY c.id ORDER BY enrolled DESC LIMIT 5");
        $recentStudents = DB::all("SELECT id, name, email, avatar, created_at, password_hash IS NOT NULL AS activated, last_login_at FROM {users} WHERE role = 'student' ORDER BY id DESC LIMIT 6");
        $activity = DB::all('SELECT a.*, u.name FROM {audit_logs} a LEFT JOIN {users} u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 8');
        return view('admin/dashboard', ['s' => $s, 'chart' => $chart, 'top' => $top, 'recentStudents' => $recentStudents, 'activity' => $activity, 'cronLast' => Settings::get('cron_last_run')]);}}
