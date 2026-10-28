<?php
namespace App\Modules\Resources;

use App\Controllers\Controller as Base;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Session;
use App\Core\Uploads;
use App\Services\Access;

class Controller extends Base {
    public const DIR = STORAGE_PATH . '/private/resources';
    public const MAX = 52428800;
    /** extension => accepted MIME types (Office files are zip containers; finfo may report generic types). */
    private const TYPES = [
        'pdf' => ['application/pdf'], 'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
        'zip' => ['application/zip', 'application/x-zip-compressed'], 'png' => ['image/png'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'webp' => ['image/webp'], 'gif' => ['image/gif'],
        'mp3' => ['audio/mpeg', 'audio/mp3'], 'txt' => ['text/plain'], 'csv' => ['text/csv', 'text/plain', 'application/csv']];

    public static function icon(?string $mime, ?string $url): string {
        if ($url) {
            return 'external';}
        if (strpos((string)$mime, 'image/') === 0) {
            return 'eye';}
        if (strpos((string)$mime, 'audio/') === 0) {
            return 'volume';}
        return 'file';}

    private function lesson(string $id): array {
        return DB::one('SELECT l.*, c.title AS course_title, c.slug AS course_slug FROM {lessons} l JOIN {courses} c ON c.id = l.course_id WHERE l.id = ?', [(int)$id]) ?? abort(404);}

    public function index(string $id) {
        $lesson = $this->lesson($id);
        $rows = DB::all('SELECT * FROM {lesson_resources} WHERE lesson_id = ? ORDER BY sort, id', [$lesson['id']]);
        return view('resources/admin', ['lesson' => $lesson, 'rows' => $rows, 'maxUpload' => min(self::MAX, Uploads::maxUploadBytes()), 'exts' => array_keys(self::TYPES)]);}

    public function store(string $id) {
        $lesson = $this->lesson($id);
        $title = mb_substr($this->req->str('title'), 0, 200);
        $sort = (int)DB::val('SELECT COALESCE(MAX(sort), 0) + 1 FROM {lesson_resources} WHERE lesson_id = ?', [$lesson['id']]);
        $row = ['lesson_id' => $lesson['id'], 'sort' => $sort, 'created_at' => now()];
        if ($this->req->str('kind') === 'link') {
            $url = $this->req->str('url');
            if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
                flash('error', 'Enter a valid https:// link.');
                back();}
            $row += ['title' => $title ?: (parse_url($url, PHP_URL_HOST) ?: 'Link'), 'url' => mb_substr($url, 0, 500)];}
        else {
            $f = $this->req->file('file');
            if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
                flash('error', $f ? Uploads::errorMessage((int)$f['error']) : 'Choose a file to upload.');
                back();}
            $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
            $mime = Uploads::mime($f['tmp_name']);
            if (!isset(self::TYPES[$ext]) || !in_array($mime, self::TYPES[$ext], true)) {
                flash('error', 'Unsupported file type (' . e($ext ?: $mime) . '). Allowed: ' . implode(', ', array_keys(self::TYPES)) . '.');
                back();}
            if ($f['size'] > self::MAX) {
                flash('error', 'File is larger than 50 MB.');
                back();}
            if (!is_dir(self::DIR)) {
                mkdir(self::DIR, 0750, true);}
            $name = gmdate('Ym') . '-' . bin2hex(random_bytes(12)) . '.' . $ext;
            if (!move_uploaded_file($f['tmp_name'], self::DIR . '/' . $name)) {
                flash('error', 'Could not save the file. Check that storage/ is writable.');
                back();}
            $orig = preg_replace('/[^\pL\pN ._()-]+/u', '_', basename((string)$f['name']));
            $row += ['title' => $title ?: pathinfo($orig, PATHINFO_FILENAME), 'file' => $name, 'original_name' => mb_substr($orig, 0, 255), 'mime' => $mime, 'size' => (int)$f['size']];}
        $rid = DB::insert('lesson_resources', $row);
        Audit::log('resource.added', 'lesson', (int)$lesson['id'], ['resource_id' => $rid, 'title' => $row['title']]);
        $this->ok('Resource added.', '/admin/lessons/' . $lesson['id'] . '/resources');}

    private function resource(string $id): array {
        return DB::one('SELECT * FROM {lesson_resources} WHERE id = ?', [(int)$id]) ?? abort(404);}

    public function destroy(string $id) {
        $r = $this->resource($id);
        DB::delete('lesson_resources', 'id = ?', [$r['id']]);
        if ($r['file'] && is_file(self::DIR . '/' . basename($r['file']))) {
            @unlink(self::DIR . '/' . basename($r['file']));}
        Audit::log('resource.deleted', 'lesson', (int)$r['lesson_id'], ['title' => $r['title']]);
        $this->ok('Resource deleted.', '/admin/lessons/' . $r['lesson_id'] . '/resources');}

    public function move(string $id) {
        $r = $this->resource($id);
        $ids = array_map('intval', DB::col('SELECT id FROM {lesson_resources} WHERE lesson_id = ? ORDER BY sort, id', [$r['lesson_id']]));
        $i = array_search((int)$r['id'], $ids, true);
        $j = $this->req->str('dir') === 'up' ? $i - 1 : $i + 1;
        if ($i !== false && isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            foreach ($ids as $n => $rid) {
                DB::run('UPDATE {lesson_resources} SET sort = ? WHERE id = ?', [$n + 1, $rid]);}}
        redirect('/admin/lessons/' . $r['lesson_id'] . '/resources');}

    /** Protected download: only users who can view the lesson. */
    public function download(string $id) {
        $r = $this->resource($id);
        $lesson = DB::one('SELECT l.*, m.release_day AS module_release_day FROM {lessons} l LEFT JOIN {modules} m ON m.id = l.module_id WHERE l.id = ?', [$r['lesson_id']]) ?? abort(404);
        if (!Access::canViewLesson(Auth::user(), $lesson)['ok']) {
            abort(403, 'You do not have access to this resource.');}
        DB::run('UPDATE {lesson_resources} SET downloads = downloads + 1 WHERE id = ?', [$r['id']]);
        if ($r['url']) {
            redirect($r['url']);}
        $path = self::DIR . '/' . basename((string)$r['file']);
        if (!is_file($path)) {
            abort(404, 'File not found.');}
        Session::close();
        while (ob_get_level() > 0) {
            ob_end_clean();}
        $fn = $r['original_name'] ?: basename($path);
        header('Content-Type: ' . ($r['mime'] ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', preg_replace('/[^\x20-\x7e]/', '_', $fn)) . '"; filename*=UTF-8\'\'' . rawurlencode($fn));
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        $fp = fopen($path, 'rb');
        while (!feof($fp)) {
            echo fread($fp, 1048576);
            flush();}
        fclose($fp);
        exit;}}
