<?php
namespace App\Services;

use App\Core\DB;

/** Builds the student's view of a course: per-lesson state, next lesson, upcoming unlocks. */
class Learning {
    public static function annotate(array $curriculum, ?array $enr, array $course, array $progress, bool $preview = false): array {
        $state = $preview ? 'active' : Access::enrollmentState($enr, $course);
        $now = time();
        $flat = [];
        $next = null;
        $upcoming = [];
        $completed = 0;
        foreach ($curriculum['modules'] as &$m) {
            $mDone = 0;
            foreach ($m['lessons'] as &$l) {
                $unlock = $enr || !empty($l['release_at']) ? Access::unlockAt($l, $enr, $course) : $now;
                $p = $progress[$l['id']] ?? null;
                $l['progress'] = $p;
                $l['completed'] = !empty($p['completed_at']);
                $l['unlock_at'] = $unlock;
                $l['day'] = Access::releaseDay($l);
                $l['available'] = $preview || ($state === 'active' && $unlock <= $now);
                $l['module_title'] = $m['title'];
                if ($l['completed']) {
                    $completed++;
                    $mDone++;}
                if ($l['available'] && !$l['completed'] && !$next) {
                    $next = $l;}
                if (!$l['available'] && $state !== 'expired' && $state !== 'suspended' && $unlock < PHP_INT_MAX) {
                    $upcoming[] = $l;}
                $flat[] = $l;}
            unset($l);
            $m['done'] = $mDone;
            $m['count'] = count($m['lessons']);}
        unset($m);
        usort($upcoming, fn($a, $b) => $a['unlock_at'] <=> $b['unlock_at']);
        $total = count($flat);
        return ['modules' => $curriculum['modules'], 'flat' => $flat, 'next' => $next, 'upcoming' => $upcoming, 'total' => $total, 'completed' => $completed,
            'percent' => $total ? (int)floor($completed / $total * 100) : 0, 'state' => $state,
            'current_day' => $enr ? Access::currentDay($enr, $course) : 1, 'start' => $enr ? Access::effectiveStart($enr, $course) : null];}

    /** Enrollments of a user joined with course data. */
    public static function enrollments(int $userId): array {
        $rows = DB::all("SELECT e.id AS enrollment_id, e.user_id, e.status AS enrollment_status, e.starts_at, e.expires_at, e.completed_at, e.last_accessed_at, e.created_at AS enrolled_at, c.* FROM {enrollments} e JOIN {courses} c ON c.id = e.course_id WHERE e.user_id = ? AND c.status = 'published' ORDER BY (e.last_accessed_at IS NULL), e.last_accessed_at DESC, e.created_at DESC", [$userId]);
        foreach ($rows as &$r) {
            $r['enrollment'] = ['id' => $r['enrollment_id'], 'user_id' => $r['user_id'], 'status' => $r['enrollment_status'], 'starts_at' => $r['starts_at'], 'expires_at' => $r['expires_at'], 'completed_at' => $r['completed_at']];
            $r['state'] = Access::enrollmentState($r['enrollment'], $r);}
        return $rows;}

    /** Dashboard overview across all courses using 3 queries. */
    public static function overview(int $userId, array $enrollments): array {
        $ids = array_map(fn($r) => (int)$r['id'], $enrollments);
        if (!$ids) {
            return [];}
        $modules = DB::all('SELECT * FROM {modules} WHERE course_id IN (' . DB::in($ids) . ') ORDER BY sort_order, id', $ids);
        $lessons = DB::all("SELECT l.id, l.course_id, l.module_id, l.title, l.type, l.release_day, l.release_at, l.duration, l.status, l.sort_order, m.release_day AS module_release_day, m.sort_order AS msort, v.duration AS video_duration FROM {lessons} l LEFT JOIN {modules} m ON m.id = l.module_id LEFT JOIN {videos} v ON v.id = l.video_id WHERE l.status = 'published' AND l.course_id IN (" . DB::in($ids) . ') ORDER BY COALESCE(m.sort_order, 999999), l.sort_order, l.id', $ids);
        $prog = DB::all('SELECT lesson_id, course_id, position, duration, percent, completed_at, last_viewed_at FROM {lesson_progress} WHERE user_id = ? AND course_id IN (' . DB::in($ids) . ')', array_merge([$userId], $ids));
        $pByCourse = [];
        foreach ($prog as $p) {
            $pByCourse[$p['course_id']][$p['lesson_id']] = $p;}
        $mByCourse = [];
        foreach ($modules as $m) {
            $m['lessons'] = [];
            $mByCourse[$m['course_id']][$m['id']] = $m;}
        foreach ($lessons as $l) {
            $mid = (int)$l['module_id'];
            if ($mid && isset($mByCourse[$l['course_id']][$mid])) {
                $mByCourse[$l['course_id']][$mid]['lessons'][] = $l;}
            else {
                $mByCourse[$l['course_id']][0] ??= ['id' => 0, 'title' => 'Lessons', 'release_day' => null, 'lessons' => []];
                $mByCourse[$l['course_id']][0]['lessons'][] = $l;}}
        $out = [];
        foreach ($enrollments as $c) {
            $mods = array_values($mByCourse[$c['id']] ?? []);
            usort($mods, fn($a, $b) => ($a['id'] === 0) <=> ($b['id'] === 0));
            $out[$c['id']] = self::annotate(['modules' => $mods], $c['enrollment'], $c, $pByCourse[$c['id']] ?? []);}
        return $out;}}
