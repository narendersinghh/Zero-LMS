<?php
namespace App\Services;

use App\Core\DB;
use App\Core\Hooks;
use App\Core\Settings;

class Progress {
    /** Record a lesson view (upsert). */
    public static function touch(int $userId, array $lesson): void {
        DB::run('INSERT INTO {lesson_progress} (user_id, lesson_id, course_id, views, last_viewed_at, created_at) VALUES (?,?,?,1,?,?) ON DUPLICATE KEY UPDATE views = views + 1, last_viewed_at = VALUES(last_viewed_at)', [$userId, (int)$lesson['id'], (int)$lesson['course_id'], now(), now()]);
        DB::run('UPDATE {enrollments} SET last_accessed_at = ? WHERE user_id = ? AND course_id = ?', [now(), $userId, (int)$lesson['course_id']]);
        Hooks::fire('lesson.viewed', $userId, $lesson);}

    /** Save playback position; auto-completes past the configured threshold. Returns true if newly completed. */
    public static function savePosition(int $userId, array $lesson, int $position, int $duration): bool {
        $duration = max(0, $duration);
        $position = max(0, min($position, $duration ?: $position));
        $pct = $duration > 0 ? (int)min(100, round($position / $duration * 100)) : 0;
        DB::run('INSERT INTO {lesson_progress} (user_id, lesson_id, course_id, position, duration, percent, last_viewed_at, created_at) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE position = VALUES(position), duration = VALUES(duration), percent = GREATEST(percent, VALUES(percent)), last_viewed_at = VALUES(last_viewed_at)', [$userId, (int)$lesson['id'], (int)$lesson['course_id'], $position, $duration, $pct, now(), now()]);
        if ($pct >= (int)Settings::get('video_complete_percent', 90) && Hooks::filter('lesson.can_complete', true, $lesson, \App\Core\Auth::user() ?? ['role' => 'student', 'id' => $userId])) {
            return self::complete($userId, $lesson);}
        return false;}

    public static function complete(int $userId, array $lesson): bool {
        $n = DB::run('UPDATE {lesson_progress} SET completed_at = ?, percent = 100 WHERE user_id = ? AND lesson_id = ? AND completed_at IS NULL', [now(), $userId, (int)$lesson['id']])->rowCount();
        if (!$n) {
            $exists = DB::val('SELECT id FROM {lesson_progress} WHERE user_id = ? AND lesson_id = ?', [$userId, (int)$lesson['id']]);
            if ($exists) {
                return false;}
            DB::insert('lesson_progress', ['user_id' => $userId, 'lesson_id' => (int)$lesson['id'], 'course_id' => (int)$lesson['course_id'], 'percent' => 100, 'completed_at' => now(), 'last_viewed_at' => now(), 'created_at' => now(), 'views' => 1]);}
        Hooks::fire('lesson.completed', $userId, $lesson);
        self::checkCourseComplete($userId, (int)$lesson['course_id']);
        return true;}

    public static function uncomplete(int $userId, int $lessonId): void {
        DB::run('UPDATE {lesson_progress} SET completed_at = NULL, percent = LEAST(percent, 99) WHERE user_id = ? AND lesson_id = ?', [$userId, $lessonId]);}

    private static function checkCourseComplete(int $userId, int $courseId): void {
        $p = self::forCourses($userId, [$courseId])[$courseId] ?? null;
        if ($p && $p['total'] > 0 && $p['completed'] >= $p['total']) {
            $n = DB::run('UPDATE {enrollments} SET completed_at = ? WHERE user_id = ? AND course_id = ? AND completed_at IS NULL', [now(), $userId, $courseId])->rowCount();
            if ($n) {
                $c = DB::one('SELECT title, slug FROM {courses} WHERE id = ?', [$courseId]);
                Notifier::notify($userId, 'Course completed: ' . ($c['title'] ?? ''), 'Amazing work — you finished every lesson!', '/courses/' . ($c['slug'] ?? ''), 'achievement');
                Hooks::fire('course.completed', $userId, $courseId);}}}

    /** @return array<int, array{total:int, completed:int, percent:int}> */
    public static function forCourses(int $userId, array $courseIds): array {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));
        if (!$courseIds) {
            return [];}
        $totals = DB::pairs("SELECT course_id, COUNT(*) FROM {lessons} WHERE status = 'published' AND course_id IN (" . DB::in($courseIds) . ') GROUP BY course_id', $courseIds);
        $done = DB::pairs("SELECT p.course_id, COUNT(*) FROM {lesson_progress} p JOIN {lessons} l ON l.id = p.lesson_id AND l.status = 'published' WHERE p.user_id = ? AND p.completed_at IS NOT NULL AND p.course_id IN (" . DB::in($courseIds) . ') GROUP BY p.course_id', array_merge([$userId], $courseIds));
        $out = [];
        foreach ($courseIds as $id) {
            $t = (int)($totals[$id] ?? 0);
            $c = (int)($done[$id] ?? 0);
            $out[$id] = ['total' => $t, 'completed' => $c, 'percent' => $t ? (int)floor($c / $t * 100) : 0];}
        return $out;}

    /** Map lesson_id => progress row for a user within a course. */
    public static function lessonMap(int $userId, int $courseId): array {
        $rows = DB::all('SELECT lesson_id, position, duration, percent, completed_at, last_viewed_at FROM {lesson_progress} WHERE user_id = ? AND course_id = ?', [$userId, $courseId]);
        return array_column($rows, null, 'lesson_id');}

    /** Consecutive days (ending today or yesterday) with learning activity. */
    public static function streak(int $userId): int {
        $days = DB::col('SELECT DISTINCT DATE(last_viewed_at) d FROM {lesson_progress} WHERE user_id = ? AND last_viewed_at > ? ORDER BY d DESC', [$userId, gmdate('Y-m-d', time() - 90 * 86400)]);
        $streak = 0;
        $cursor = new \DateTime('today', new \DateTimeZone('UTC'));
        if ($days && $days[0] !== $cursor->format('Y-m-d')) {
            $cursor->modify('-1 day');}
        foreach ($days as $d) {
            if ($d === $cursor->format('Y-m-d')) {
                $streak++;
                $cursor->modify('-1 day');}
            else {
                break;}}
        return $streak;}}
