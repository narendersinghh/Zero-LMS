<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Media;
use App\Core\Paginator;
use App\Core\Uploads;

/** Video library: chunked uploads (bypasses small upload limits), external providers, FTP folder scan. */
class VideoController extends Controller {
    public const PROVIDERS = ['local' => 'Protected upload', 'bunny' => 'Bunny Stream', 'vimeo' => 'Vimeo', 'youtube' => 'YouTube (unlisted)'];

    public function index() {
        $q = $this->req->str('q');
        $prov = $this->req->str('provider');
        $w = ['1=1'];
        $p = [];
        if ($q !== '') {
            $w[] = 'v.title LIKE ?';
            $p[] = DB::like($q);}
        if (isset(self::PROVIDERS[$prov])) {
            $w[] = 'v.provider = ?';
            $p[] = $prov;}
        $ws = implode(' AND ', $w);
        $pg = new Paginator((int)DB::val("SELECT COUNT(*) FROM {videos} v WHERE $ws", $p), 30);
        $videos = DB::all("SELECT v.*, (SELECT COUNT(*) FROM {lessons} l WHERE l.video_id = v.id) AS used FROM {videos} v WHERE $ws ORDER BY v.id DESC" . $pg->limitSql(), $p);
        $ids = array_column($videos, 'id');
        $usedIn = [];
        if ($ids) {
            foreach (DB::all('SELECT l.id, l.title, l.video_id, c.title AS course_title FROM {lessons} l JOIN {courses} c ON c.id = l.course_id WHERE l.video_id IN (' . DB::in($ids) . ') ORDER BY c.title, l.sort_order', $ids) as $l) {
                $usedIn[$l['video_id']][] = $l;}}
        $incoming = array_map('basename', array_filter(glob(Media::DIR . '/incoming/*') ?: [], 'is_file'));
        $disk = (int)DB::val("SELECT COALESCE(SUM(size), 0) FROM {videos} WHERE provider = 'local'");
        return view('admin/videos/index', ['usedIn' => $usedIn, 'videos' => $videos, 'pg' => $pg, 'q' => $q, 'provider' => $prov, 'incoming' => $incoming, 'disk' => $disk, 'maxChunk' => min(8 * 1048576, (int)(Uploads::maxUploadBytes() * 0.9))]);}

    public function search() {
        $q = $this->req->str('q');
        $rows = DB::all('SELECT id, title, provider, duration, size FROM {videos}' . ($q !== '' ? ' WHERE title LIKE ?' : '') . ' ORDER BY id DESC LIMIT 30', $q !== '' ? [DB::like($q)] : []);
        foreach ($rows as &$r) {
            $r['duration_h'] = human_duration($r['duration']);
            $r['provider_h'] = self::PROVIDERS[$r['provider']] ?? $r['provider'];}
        json_out(['items' => $rows]);}

    /**
     * Chunked upload. Fields: upload_id, index, total, name, chunk (file). Final chunk assembles the file.
     */
    public function uploadChunk() {
        @set_time_limit(0);
        $uid = preg_replace('/[^a-zA-Z0-9]/', '', $this->req->str('upload_id'));
        $index = $this->req->int('index');
        $total = $this->req->int('total');
        $name = $this->req->str('name');
        $f = $this->req->file('chunk');
        if (strlen($uid) < 16 || $total < 1 || $total > 20000 || $index < 0 || $index >= $total || !$f || $f['error'] !== UPLOAD_ERR_OK) {
            json_out(['error' => $f ? Uploads::errorMessage((int)$f['error']) : 'Invalid upload request.'], 422);}
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['mp4', 'webm', 'mov', 'm4v', 'mp3', 'm4a'], true)) {
            json_out(['error' => 'Only MP4, WebM, MOV, M4V, MP3 or M4A files are allowed.'], 422);}
        $dir = STORAGE_PATH . '/tmp/up-' . $uid;
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);}
        move_uploaded_file($f['tmp_name'], $dir . '/' . $index . '.part');
        $have = count(glob($dir . '/*.part') ?: []);
        if ($have < $total) {
            json_out(['ok' => true, 'received' => $have]);}
        $final = gmdate('Ym') . '-' . bin2hex(random_bytes(10)) . '.' . $ext;
        $dest = Media::DIR . '/' . $final;
        $out = fopen($dest, 'wb');
        for ($i = 0; $i < $total; $i++) {
            $part = $dir . '/' . $i . '.part';
            if (!is_file($part)) {
                fclose($out);
                @unlink($dest);
                json_out(['error' => 'Missing chunk ' . $i . '. Please retry the upload.'], 422);}
            $in = fopen($part, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
            unlink($part);}
        fclose($out);
        @rmdir($dir);
        $mime = Uploads::mime($dest);
        if (!isset(Uploads::VIDEO_TYPES[$mime]) && $mime !== 'application/octet-stream') {
            @unlink($dest);
            json_out(['error' => 'File content is not a supported video (' . $mime . ').'], 422);}
        $title = trim($this->req->str('title')) ?: ucfirst(trim(preg_replace('/[_-]+/', ' ', pathinfo($name, PATHINFO_FILENAME))));
        $id = DB::insert('videos', ['title' => mb_substr($title, 0, 200), 'provider' => 'local', 'source' => $final, 'mime' => $mime === 'application/octet-stream' ? 'video/mp4' : $mime, 'size' => filesize($dest), 'duration' => max(0, $this->req->int('duration')), 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()]);
        Audit::log('video.uploaded', 'video', $id, ['title' => $title, 'size' => filesize($dest)]);
        json_out(['ok' => true, 'done' => true, 'video' => ['id' => $id, 'title' => $title, 'provider' => 'local', 'provider_h' => self::PROVIDERS['local']]]);}

    public function external() {
        $d = $this->validate(['title' => 'required|max:200', 'provider' => 'required|in:bunny,vimeo,youtube', 'source' => 'required|max:500', 'duration' => 'max:10']);
        $src = $d['source'];
        if ($d['provider'] === 'youtube') {
            $src = Media::youtubeId($src);}
        elseif ($d['provider'] === 'vimeo') {
            $src = Media::vimeoId($src);}
        elseif (preg_match('~/([0-9a-f-]{36})~i', $src, $m)) {
            $src = $m[1];}
        $dur = preg_match('/^(\d+):(\d{2})$/', (string)$d['duration'], $m) ? (int)$m[1] * 60 + (int)$m[2] : (int)$d['duration'] * 60;
        $id = DB::insert('videos', ['title' => $d['title'], 'provider' => $d['provider'], 'source' => $src, 'duration' => $dur, 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()]);
        Audit::log('video.added', 'video', $id, ['provider' => $d['provider']]);
        if ($this->req->isAjax()) {
            json_out(['ok' => true, 'video' => ['id' => $id, 'title' => $d['title'], 'provider' => $d['provider'], 'provider_h' => self::PROVIDERS[$d['provider']]]]);}
        $this->ok('Video added.');}

    /** Import files uploaded via FTP into storage/videos/incoming. */
    public function scan() {
        $n = 0;
        foreach (glob(Media::DIR . '/incoming/*') ?: [] as $f) {
            if (!is_file($f)) {
                continue;}
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            $mime = Uploads::mime($f);
            if (!in_array($ext, ['mp4', 'webm', 'mov', 'm4v', 'mp3', 'm4a'], true)) {
                continue;}
            $final = gmdate('Ym') . '-' . bin2hex(random_bytes(10)) . '.' . $ext;
            if (!@rename($f, Media::DIR . '/' . $final)) {
                continue;}
            $title = ucfirst(trim(preg_replace('/[_-]+/', ' ', pathinfo($f, PATHINFO_FILENAME))));
            DB::insert('videos', ['title' => mb_substr($title, 0, 200), 'provider' => 'local', 'source' => $final, 'mime' => isset(Uploads::VIDEO_TYPES[$mime]) ? $mime : 'video/mp4', 'size' => filesize(Media::DIR . '/' . $final), 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()]);
            $n++;}
        Audit::log('video.scan', null, null, ['imported' => $n]);
        $this->ok($n ? "$n video(s) imported from the incoming folder." : 'No new video files found in storage/videos/incoming.');}

    public function update(string $id) {
        $v = DB::one('SELECT * FROM {videos} WHERE id = ?', [(int)$id]) ?? abort(404);
        $d = $this->validate(['title' => 'required|max:200', 'duration' => 'int']);
        DB::update('videos', ['title' => $d['title'], 'duration' => max(0, (int)$d['duration']), 'updated_at' => now()], 'id = ?', [$v['id']]);
        if ($this->req->isAjax()) {
            json_out(['ok' => true]);}
        $this->ok('Video updated.');}

    public function stream(string $id) {
        $v = DB::one("SELECT * FROM {videos} WHERE id = ? AND provider = 'local'", [(int)$id]) ?? abort(404);
        $file = Media::path($v) ?? abort(404);
        \App\Core\Session::close();
        Media::sendFile($file, $v['mime'] ?: 'video/mp4');}

    public function destroy(string $id) {
        $v = DB::one('SELECT * FROM {videos} WHERE id = ?', [(int)$id]) ?? abort(404);
        if ($v['provider'] === 'local' && ($file = Media::path($v))) {
            @unlink($file);}
        DB::delete('videos', 'id = ?', [$v['id']]);
        Audit::log('video.deleted', 'video', (int)$v['id'], ['title' => $v['title']]);
        $this->ok('Video deleted.');}}
