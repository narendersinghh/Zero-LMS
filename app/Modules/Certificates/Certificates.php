<?php
namespace App\Modules\Certificates;

use App\Core\Audit;
use App\Core\DB;
use App\Core\Mailer;
use App\Services\Notifier;

/** Certificate issuing & lookup. */
class Certificates {
    public static function code(): string {
        $c = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $r = '';
            for ($i = 0; $i < 10; $i++) {
                $r .= $c[random_int(0, strlen($c) - 1)];}}
        while (DB::val('SELECT id FROM {certificates} WHERE code = ?', [$r]));
        return $r;}

    public static function forUserCourse(int $userId, int $courseId): ?array {
        return DB::one('SELECT * FROM {certificates} WHERE user_id = ? AND course_id = ? AND revoked_at IS NULL', [$userId, $courseId]);}

    public static function byCode(string $code): ?array {
        return DB::one('SELECT ct.*, u.name AS user_name, c.title AS course_title, c.slug AS course_slug, c.instructor_name FROM {certificates} ct JOIN {users} u ON u.id = ct.user_id JOIN {courses} c ON c.id = ct.course_id WHERE ct.code = ?', [strtoupper(trim($code))]);}

    public static function forUser(int $userId): array {
        return DB::all('SELECT ct.*, c.title AS course_title, c.slug AS course_slug FROM {certificates} ct JOIN {courses} c ON c.id = ct.course_id WHERE ct.user_id = ? AND ct.revoked_at IS NULL ORDER BY ct.issued_at DESC', [$userId]);}

    /** Issue (or re-activate) a certificate. Returns the certificate row. */
    public static function issue(int $userId, int $courseId, bool $notify = true, ?int $by = null): ?array {
        $existing = DB::one('SELECT * FROM {certificates} WHERE user_id = ? AND course_id = ?', [$userId, $courseId]);
        if ($existing && !$existing['revoked_at']) {
            return $existing;}
        if ($existing) {
            DB::update('certificates', ['revoked_at' => null, 'issued_at' => now(), 'issued_by' => $by], 'id = ?', [$existing['id']]);
            $id = (int)$existing['id'];}
        else {
            $id = DB::insert('certificates', ['user_id' => $userId, 'course_id' => $courseId, 'code' => self::code(), 'issued_at' => now(), 'issued_by' => $by]);}
        $cert = DB::one('SELECT * FROM {certificates} WHERE id = ?', [$id]);
        Audit::log('certificate.issued', 'certificate', $id, ['user_id' => $userId, 'course_id' => $courseId], $by);
        if ($notify) {
            $u = DB::one('SELECT name, email FROM {users} WHERE id = ?', [$userId]);
            $c = DB::one('SELECT title FROM {courses} WHERE id = ?', [$courseId]);
            if ($u && $c) {
                $link = url('/certificates/' . $cert['code'], [], true);
                Notifier::notify($userId, 'Your certificate is ready 🎓', $c['title'], '/certificates/' . $cert['code'], 'achievement', 'cert-' . $id);
                Mailer::queue($u['email'], $u['name'], 'Your certificate for ' . $c['title'],
                    '<h1>Congratulations, ' . e(explode(' ', trim($u['name']))[0]) . '! 🎓</h1><p>You completed <strong>' . e($c['title']) . '</strong>. Your certificate of completion is ready.</p><p class="btn-wrap"><a class="btn" href="' . e($link) . '">View certificate</a></p><p>Anyone can verify it at <a href="' . e(url('/verify/' . $cert['code'], [], true)) . '">' . e(url('/verify/' . $cert['code'], [], true)) . '</a> (code <strong>' . e($cert['code']) . '</strong>).</p>', 0, 'certificate');}}
        return $cert;}}
