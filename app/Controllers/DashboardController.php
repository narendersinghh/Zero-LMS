<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Hooks;
use App\Services\Courses;
use App\Services\Learning;
use App\Services\Notifier;
use App\Services\Progress;

class DashboardController extends Controller {
    public function index() {
        $user = Auth::user();
        $uid = (int)$user['id'];
        $enrollments = Learning::enrollments($uid);
        $overview = Learning::overview($uid, $enrollments);
        $recent = DB::all("SELECT p.lesson_id, p.percent, p.completed_at, p.last_viewed_at, l.title, l.course_id, c.slug, c.title AS course_title, c.cover_image FROM {lesson_progress} p JOIN {lessons} l ON l.id = p.lesson_id AND l.status = 'published' JOIN {courses} c ON c.id = l.course_id AND c.status = 'published' JOIN {enrollments} e ON e.course_id = c.id AND e.user_id = p.user_id WHERE p.user_id = ? ORDER BY p.last_viewed_at DESC LIMIT 6", [$uid]);
        // Continue learning: last touched incomplete lesson that is still accessible, else next lesson in the most recent active course.
        $continue = null;
        $byId = array_column($enrollments, null, 'id');
        foreach ($recent as $r) {
            $c = $byId[$r['course_id']] ?? null;
            if ($c && $c['state'] === 'active' && !$r['completed_at']) {
                $continue = ['lesson_id' => $r['lesson_id'], 'title' => $r['title'], 'course' => $c, 'percent' => (int)$r['percent']];
                break;}}
        if (!$continue) {
            foreach ($enrollments as $c) {
                $o = $overview[$c['id']] ?? null;
                if ($c['state'] === 'active' && $o && $o['next']) {
                    $continue = ['lesson_id' => $o['next']['id'], 'title' => $o['next']['title'], 'course' => $c, 'percent' => (int)($o['next']['progress']['percent'] ?? 0)];
                    break;}}}
        $upcoming = [];
        foreach ($enrollments as $c) {
            foreach (array_slice($overview[$c['id']]['upcoming'] ?? [], 0, 4) as $l) {
                $upcoming[] = $l + ['course' => $c];}}
        usort($upcoming, fn($a, $b) => $a['unlock_at'] <=> $b['unlock_at']);
        $stats = ['courses' => count($enrollments), 'completed_lessons' => array_sum(array_map(fn($o) => $o['completed'], $overview)), 'streak' => Progress::streak($uid),
            'finished' => count(array_filter($enrollments, fn($c) => !empty($c['completed_at'])))];
        // Continue watching: in-progress lessons first, then each active course's next lesson.
        $cw = [];
        foreach ($recent as $r) {
            $c = $byId[$r['course_id']] ?? null;
            if ($c && $c['state'] === 'active' && !$r['completed_at'] && count($cw) < 3) {
                $cw[$r['lesson_id']] = ['lesson_id' => $r['lesson_id'], 'title' => $r['title'], 'course' => $c, 'percent' => (int)$r['percent']];}}
        foreach ($enrollments as $c) {
            $n = $overview[$c['id']]['next'] ?? null;
            if ($c['state'] === 'active' && $n && !isset($cw[$n['id']]) && count($cw) < 3) {
                $cw[$n['id']] = ['lesson_id' => $n['id'], 'title' => $n['title'], 'course' => $c, 'percent' => (int)($n['progress']['percent'] ?? 0)];}}
        $stats['total_lessons'] = array_sum(array_map(fn($o) => $o['total'], $overview));
        $stats['hours'] = round((float)DB::val('SELECT COALESCE(SUM(CASE WHEN p.completed_at IS NOT NULL THEN GREATEST(p.duration, p.position, l.duration) ELSE p.position END), 0) FROM {lesson_progress} p JOIN {lessons} l ON l.id = p.lesson_id WHERE p.user_id = ?', [$uid]) / 3600, 1);
        $stats['active_days'] = (int)DB::val('SELECT COUNT(DISTINCT DATE(last_viewed_at)) FROM {lesson_progress} WHERE user_id = ?', [$uid]);
        $achievements = [
            ['First step', 'Complete your first lesson', 'check-circle', 'green', min($stats['completed_lessons'], 1), 1],
            ['Momentum', 'Complete 10 lessons', 'activity', 'blue', min($stats['completed_lessons'], 10), 10],
            ['On fire', 'Reach a 7-day streak', 'flame', 'orange', min($stats['streak'], 7), 7],
            ['Regular', 'Learn on 15 different days', 'calendar', 'purple', min($stats['active_days'], 15), 15],
            ['Finisher', 'Complete a whole course', 'trophy', 'yellow', min($stats['finished'], 1), 1],];
        $days = [];
        for ($i = 0; $i < 5; $i++) {
            $d = (new \DateTime('today', site_tz()))->modify("+$i day");
            $key = $d->format('Y-m-d');
            $days[$key] = ['dow' => $d->format('D'), 'day' => $d->format('j'), 'today' => $i === 0, 'count' => 0];}
        foreach ($upcoming as $l) {
            $k = (new \DateTime('@' . $l['unlock_at']))->setTimezone(site_tz())->format('Y-m-d');
            if (isset($days[$k])) {
                $days[$k]['count']++;}}
        $expiring = array_values(array_filter($enrollments, fn($c) => $c['state'] === 'active' && $c['expires_at'] && strtotime($c['expires_at'] . ' UTC') < time() + 14 * 86400));
        $focus = $continue['course'] ?? ($enrollments[0] ?? null);
        return view('student/dashboard', [
            'user' => $user, 'enrollments' => $enrollments, 'overview' => $overview, 'recent' => $recent, 'continue' => $continue,
            'upcoming' => array_slice($upcoming, 0, 4), 'cw' => array_values($cw), 'achievements' => $achievements, 'days' => $days, 'stats' => $stats, 'expiring' => $expiring, 'quote' => Courses::quote($focus),
            'notifications' => Notifier::recent($uid, 5), 'widgets' => Hooks::filter('dashboard.widgets', [], $user),]);}}
