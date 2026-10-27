<?php
namespace App\Services;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Hooks;
use App\Core\Mailer;

class Enrollments {
    /**
     * Create or re-activate an enrollment.
     * $opts: starts_at (UTC), expires_at (UTC, explicit override), source, notify (bool), notes
     * @return array{id:int, created:bool}
     */
    public static function enroll(int $userId, array $course, array $opts = []): array {
        $start = $opts['starts_at'] ?? now();
        $startTs = (int)strtotime($start . ' UTC');
        $expires = array_key_exists('expires_at', $opts) && $opts['expires_at'] !== null ? $opts['expires_at'] : Access::computeExpiry($course, $startTs);
        $existing = Access::enrollment($userId, (int)$course['id']);
        if ($existing) {
            DB::update('enrollments', ['status' => 'active', 'starts_at' => !empty($opts['keep_start']) ? $existing['starts_at'] : $start, 'expires_at' => $expires, 'updated_at' => now(), 'reminded_at' => null], 'id = ?', [$existing['id']]);
            $id = (int)$existing['id'];
            $created = false;
            Hooks::fire('enrollment.updated', $id);}
        else {
            $id = DB::insert('enrollments', ['user_id' => $userId, 'course_id' => (int)$course['id'], 'status' => 'active', 'starts_at' => $start, 'expires_at' => $expires,
                'source' => $opts['source'] ?? 'manual', 'notes' => $opts['notes'] ?? null, 'created_by' => Auth::id() ?: null, 'created_at' => now(), 'updated_at' => now()]);
            $created = true;
            Progress::sync($userId, (int)$course['id']);
            Hooks::fire('enrollment.created', $id, $userId, $course);}
        Audit::log($created ? 'enrollment.created' : 'enrollment.reactivated', 'enrollment', $id, ['user_id' => $userId, 'course_id' => (int)$course['id'], 'expires_at' => $expires]);
        Notifier::notify($userId, 'You have access to ' . $course['title'], 'Your course is ready. Jump in and start learning.', '/courses/' . $course['slug'], 'enrollment', 'enroll-' . $course['id'] . '-' . $id . '-' . substr(md5($start), 0, 6));
        if (!empty($opts['notify'])) {
            $u = Users::find($userId);
            if ($u) {
                self::sendEnrolledEmail($u, $course, $expires, $start);}}
        return ['id' => $id, 'created' => $created];}

    public static function sendEnrolledEmail(array $user, array $course, ?string $expires, ?string $start = null): void {
        $parts = [];
        if ($start && strtotime($start . ' UTC') > time() + 60) {
            $parts[] = 'Your access starts on <strong>' . e(dt($start)) . '</strong>.';}
        $parts[] = $expires ? 'Your access is valid until <strong>' . e(dt($expires)) . '</strong>.' : 'You have lifetime access to this course.';
        Mailer::queueTemplate('enrolled', $user['email'], $user['name'], ['course_title' => $course['title'], 'course_url' => url('/courses/' . $course['slug'], [], true), 'access_line' => implode(' ', $parts)]);}

    public static function extend(int $enrollmentId, ?string $newExpiry, bool $notify = false): void {
        $e = DB::one('SELECT e.*, c.title, c.slug, u.email, u.name FROM {enrollments} e JOIN {courses} c ON c.id = e.course_id JOIN {users} u ON u.id = e.user_id WHERE e.id = ?', [$enrollmentId]);
        if (!$e) {
            return;}
        DB::update('enrollments', ['expires_at' => $newExpiry, 'status' => 'active', 'reminded_at' => null, 'updated_at' => now()], 'id = ?', [$enrollmentId]);
        Audit::log('enrollment.extended', 'enrollment', $enrollmentId, ['from' => $e['expires_at'], 'to' => $newExpiry]);
        Notifier::notify((int)$e['user_id'], 'Access extended: ' . $e['title'], $newExpiry ? 'Your access now runs until ' . dt($newExpiry) . '.' : 'You now have lifetime access.', '/courses/' . $e['slug'], 'access');
        if ($notify) {
            Mailer::queueTemplate('access_extended', $e['email'], $e['name'], ['course_title' => $e['title'], 'course_url' => url('/courses/' . $e['slug'], [], true), 'expires_at' => $newExpiry ? dt($newExpiry) : 'forever (lifetime access)']);}}}
