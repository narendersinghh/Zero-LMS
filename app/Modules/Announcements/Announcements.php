<?php
namespace App\Modules\Announcements;

use App\Core\DB;
use App\Core\Scheduler;
use App\Services\Notifier;

class Announcements {
    /** Published announcements visible to a user (global + their enrolled courses). */
    public static function forUser(int $userId, int $limit = 0, ?int $courseId = null): array {
        $sql = "SELECT a.*, c.title AS course_title, c.slug AS course_slug FROM {announcements} a LEFT JOIN {courses} c ON c.id = a.course_id WHERE a.status = 'published' AND a.published_at <= ? AND ";
        $p = [now()];
        if ($courseId) {
            $sql .= 'a.course_id = ?';
            $p[] = $courseId;}
        else {
            $sql .= '(a.course_id IS NULL OR a.course_id IN (SELECT course_id FROM {enrollments} WHERE user_id = ?))';
            $p[] = $userId;}
        $sql .= ' ORDER BY a.pinned DESC, a.published_at DESC' . ($limit ? ' LIMIT ' . (int)$limit : '');
        return DB::all($sql, $p);}

    /** Student ids targeted by an announcement. */
    public static function audience(array $a): array {
        if ($a['course_id']) {
            return DB::all("SELECT u.id, u.name, u.email FROM {enrollments} e JOIN {users} u ON u.id = e.user_id WHERE e.course_id = ? AND e.status = 'active' AND (e.expires_at IS NULL OR e.expires_at > ?) AND u.status = 'active'", [(int)$a['course_id'], now()]);}
        return DB::all("SELECT id, name, email FROM {users} WHERE role = 'student' AND status = 'active'");}

    /** In-app notification (deduplicated per announcement) + optional email. Returns recipients count. */
    public static function notify(array $a, bool $email): int {
        $users = self::audience($a);
        if (!$users) {
            return 0;}
        $body = str_limit(strip_tags((string)$a['body']), 140);
        Notifier::many(array_map(fn($u) => ['user_id' => (int)$u['id'], 'type' => 'info', 'title' => '📣 ' . mb_substr($a['title'], 0, 190), 'body' => $body, 'url' => '/announcements#a' . $a['id'], 'dedupe_key' => 'ann-' . $a['id']], $users));
        if ($email && !$a['notified_at']) {
            $html = '<h1>' . e($a['title']) . '</h1>' . \App\Core\Html::clean($a['body']) . '<p class="btn-wrap"><a class="btn" href="' . e(url('/announcements', [], true)) . '">Open ' . e(setting('site_name')) . '</a></p>';
            $rows = array_map(fn($u) => ['to_email' => $u['email'], 'to_name' => $u['name'], 'subject' => $a['title'], 'body' => $html, 'template' => 'announcement', 'status' => 'pending', 'attempts' => 0, 'send_after' => now(), 'created_at' => now()], $users);
            DB::insertMany('mail_queue', $rows);
            Scheduler::$mailQueued = true;
            DB::update('announcements', ['notified_at' => now()], 'id = ?', [$a['id']]);}
        return count($users);}}
