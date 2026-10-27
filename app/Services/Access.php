<?php
namespace App\Services;

use App\Core\DB;

/**
 * Access rules: enrollment validity, course start dates, expiry and drip (day based / dated) lesson release.
 * Lesson release precedence: lesson.release_at (fixed date) > lesson.release_day > module.release_day > immediately.
 * "Day 1" is the effective start: max(enrollment start, course start date).
 */
class Access {
    public static function enrollment(int $userId, int $courseId): ?array {
        return DB::one('SELECT * FROM {enrollments} WHERE user_id = ? AND course_id = ? LIMIT 1', [$userId, $courseId]);}

    public static function ts(?string $utc): ?int {
        return $utc ? strtotime($utc . ' UTC') : null;}

    /** Returns: active | pending | expired | suspended | unavailable */
    public static function enrollmentState(?array $enr, array $course, ?int $now = null): string {
        $now = $now ?? time();
        if (!$enr) {
            return 'unavailable';}
        if ($course['status'] !== 'published') {
            return 'unavailable';}
        if ($enr['status'] !== 'active') {
            return 'suspended';}
        if ($enr['expires_at'] && self::ts($enr['expires_at']) <= $now) {
            return 'expired';}
        if (self::effectiveStart($enr, $course) > $now) {
            return 'pending';}
        return 'active';}

    public static function effectiveStart(array $enr, array $course): int {
        $s = (int)self::ts($enr['starts_at']);
        if (!empty($course['start_date'])) {
            $cs = (new \DateTime($course['start_date'] . ' 00:00:00', site_tz()))->getTimestamp();
            $s = max($s, $cs);}
        return $s;}

    public static function releaseDay(array $lesson): int {
        $d = $lesson['release_day'] ?? null;
        if ($d === null || $d === '') {
            $d = $lesson['module_release_day'] ?? null;}
        return max(1, (int)$d);}

    /** Unix timestamp at which a lesson unlocks for the given enrollment. */
    public static function unlockAt(array $lesson, ?array $enr, array $course): int {
        if (!empty($lesson['release_at'])) {
            return (int)self::ts($lesson['release_at']);}
        if (!$enr) {
            return PHP_INT_MAX;}
        $start = self::effectiveStart($enr, $course);
        $day = self::releaseDay($lesson);
        if ($day <= 1) {
            return $start;}
        $d = (new \DateTime('@' . $start))->setTimezone(site_tz());
        [$h, $m] = array_map('intval', explode(':', (string)($course['release_time'] ?? '00:00:00')) + [0, 0]);
        $d->setTime($h, $m)->modify('+' . ($day - 1) . ' days');
        return $d->getTimestamp();}

    /** Day number (1-based) the student is currently on. */
    public static function currentDay(array $enr, array $course): int {
        $start = (new \DateTime('@' . self::effectiveStart($enr, $course)))->setTimezone(site_tz())->setTime(0, 0);
        $today = (new \DateTime('now', site_tz()))->setTime(0, 0);
        return max(1, (int)$start->diff($today)->format('%r%a') + 1);}

    /**
     * Full check for viewing a lesson.
     * @return array{ok:bool, reason:string, unlock_at:int, enrollment:?array, course:?array, preview:bool}
     */
    public static function canViewLesson(array $user, array $lesson, ?array $course = null, $enr = false): array {
        $course = $course ?? DB::one('SELECT * FROM {courses} WHERE id = ?', [(int)$lesson['course_id']]);
        $res = ['ok' => false, 'reason' => 'unavailable', 'unlock_at' => 0, 'enrollment' => null, 'course' => $course, 'preview' => false];
        if (!$course) {
            return $res;}
        if (in_array($user['role'], ['super_admin', 'admin'], true)) {
            $res['ok'] = true;
            $res['reason'] = 'preview';
            $res['preview'] = true;
            $res['enrollment'] = $enr === false ? self::enrollment((int)$user['id'], (int)$course['id']) : $enr;
            return $res;}
        if ($enr === false) {
            $enr = self::enrollment((int)$user['id'], (int)$course['id']);}
        $res['enrollment'] = $enr;
        $state = self::enrollmentState($enr, $course);
        if ($state !== 'active') {
            $res['reason'] = $state;
            return $res;}
        if (($lesson['status'] ?? 'published') !== 'published') {
            return $res;}
        if (!array_key_exists('module_release_day', $lesson) && !empty($lesson['module_id'])) {
            $lesson['module_release_day'] = DB::val('SELECT release_day FROM {modules} WHERE id = ?', [(int)$lesson['module_id']]);}
        $unlock = self::unlockAt($lesson, $enr, $course);
        $res['unlock_at'] = $unlock;
        if ($unlock > time()) {
            $res['reason'] = 'locked';
            return $res;}
        $res['ok'] = true;
        $res['reason'] = 'ok';
        return $res;}

    /** Compute expiry for a new enrollment: earliest of explicit date, access_days and course end_date. */
    public static function computeExpiry(array $course, int $startTs, ?string $explicitUtc = null): ?string {
        $c = [];
        if ($explicitUtc) {
            $c[] = (int)self::ts($explicitUtc);}
        if (!empty($course['access_days'])) {
            $base = max($startTs, !empty($course['start_date']) ? (new \DateTime($course['start_date'], site_tz()))->getTimestamp() : 0);
            $c[] = $base + (int)$course['access_days'] * 86400;}
        if (!empty($course['end_date'])) {
            $c[] = (new \DateTime($course['end_date'] . ' 23:59:59', site_tz()))->getTimestamp();}
        return $c ? gmdate('Y-m-d H:i:s', min($c)) : null;}}
