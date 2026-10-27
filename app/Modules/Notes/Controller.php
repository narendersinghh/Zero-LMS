<?php
namespace App\Modules\Notes;

use App\Controllers\Controller as Base;
use App\Core\Auth;
use App\Core\DB;
use App\Services\Access;

class Controller extends Base {
    public function save(string $lessonId) {
        $lesson = DB::one('SELECT l.*, m.release_day AS module_release_day FROM {lessons} l LEFT JOIN {modules} m ON m.id = l.module_id WHERE l.id = ?', [(int)$lessonId]);
        if (!$lesson) {
            json_out(['error' => 'Not found'], 404);}
        $user = Auth::user();
        if (!Access::canViewLesson($user, $lesson)['ok']) {
            json_out(['error' => 'Forbidden'], 403);}
        $body = rtrim(str_replace("\r\n", "\n", (string)($_POST['body'] ?? '')));
        if (mb_strlen($body) > 20000) {
            json_out(['error' => 'Notes are limited to 20,000 characters.'], 422);}
        if (trim($body) === '') {
            DB::delete('lesson_notes', 'user_id = ? AND lesson_id = ?', [$user['id'], $lesson['id']]);
            json_out(['ok' => true, 'saved_at' => null]);}
        DB::run('INSERT INTO {lesson_notes} (user_id, lesson_id, course_id, body, created_at, updated_at) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE body = VALUES(body), updated_at = VALUES(updated_at)', [$user['id'], $lesson['id'], $lesson['course_id'], $body, now(), now()]);
        json_out(['ok' => true, 'saved_at' => dtime(now())]);}

    private function rows(string $q = ''): array {
        $p = [Auth::id()];
        $w = '';
        if ($q !== '') {
            $w = ' AND (n.body LIKE ? OR l.title LIKE ? OR c.title LIKE ?)';
            array_push($p, DB::like($q), DB::like($q), DB::like($q));}
        return DB::all('SELECT n.*, l.title AS lesson_title, c.title AS course_title, c.slug AS course_slug FROM {lesson_notes} n JOIN {lessons} l ON l.id = n.lesson_id JOIN {courses} c ON c.id = n.course_id WHERE n.user_id = ?' . $w . ' ORDER BY c.title, l.sort_order, l.id', $p);}

    public function index() {
        $q = $this->req->str('q');
        $groups = [];
        foreach ($this->rows($q) as $r) {
            $groups[$r['course_title']][] = $r;}
        return view('notes/index', ['groups' => $groups, 'q' => $q]);}

    public function export() {
        $out = 'My notes — ' . setting('site_name') . "\nExported " . dtime(now()) . "\n";
        $course = null;
        foreach ($this->rows() as $r) {
            if ($course !== $r['course_title']) {
                $course = $r['course_title'];
                $out .= "\n\n=== " . $course . " ===\n";}
            $out .= "\n## " . $r['lesson_title'] . "\n" . $r['body'] . "\n";}
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="my-notes-' . gmdate('Y-m-d') . '.txt"');
        header('Cache-Control: private, no-store');
        echo "\xEF\xBB\xBF" . $out;
        exit;}}
