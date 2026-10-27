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
        if (($parent = $this->req->int('rendition_of')) > 0) {
            $label = $this->req->str('label');
            if (!isset(Media::HEIGHTS[$label]) || !DB::val("SELECT id FROM {videos} WHERE id = ? AND provider = 'local'", [$parent])) {
                @unlink($dest);
                json_out(['error' => 'Pick a quality for this version.'], 422);}
            $old = DB::one('SELECT id, source FROM {video_renditions} WHERE video_id = ? AND label = ?', [$parent, $label]);
            if ($old) {
                @unlink(Media::DIR . '/' . $old['source']);
                DB::delete('video_renditions', 'id = ?', [$old['id']]);}
            $rid = DB::insert('video_renditions', ['video_id' => $parent, 'label' => $label, 'height' => Media::HEIGHTS[$label], 'source' => $final, 'mime' => $mime === 'application/octet-stream' ? 'video/mp4' : $mime, 'size' => filesize($dest), 'created_at' => now()]);
            Audit::log('video.rendition', 'video', $parent, ['label' => $label]);
            json_out(['ok' => true, 'done' => true, 'rendition' => $rid]);}
        $title = trim($this->req->str('title')) ?: ucfirst(trim(preg_replace('/[_-]+/', ' ', pathinfo($name, PATHINFO_FILENAME))));
        $id = DB::insert('videos', ['title' => mb_substr($title, 0, 200), 'provider' => 'local', 'source' => $final, 'mime' => $mime === 'application/octet-stream' ? 'video/mp4' : $mime, 'size' => filesize($dest), 'duration' => max(0, $this->req->int('duration')), 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()]);
        Audit::log('video.uploaded', 'video', $id, ['title' => $title, 'size' => filesize($dest)]);
        json_out(['ok' => true, 'done' => true, 'video' => ['id' => $id, 'title' => $title, 'provider' => 'local', 'provider_h' => self::PROVIDERS['local']]]);}

    public function external() {
        $d = $this->validate(['title' => 'required|max:200', 'provider' => 'required|in:bunny,vimeo,youtube', 'source' => 'required|max:500', 'duration' => 'max:10']);
        $src = self::sourceId($d['provider'], $d['source']);
        $dur = self::seconds((string)$d['duration'], 60);
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

    /** Extract the provider's video ID from a pasted URL or ID. */
    private static function sourceId(string $provider, string $src): string {
        if ($provider === 'youtube') {
            return Media::youtubeId($src);}
        if ($provider === 'vimeo') {
            return Media::vimeoId($src);}
        return preg_match('~/([0-9a-f-]{36})~i', $src, $m) ? $m[1] : trim($src);}

    /** "h:mm:ss" or "mm:ss" to seconds; a bare number is multiplied by $unit. */
    private static function seconds(string $v, int $unit = 1): int {
        $v = trim($v);
        if (strpos($v, ':') === false) {
            return max(0, (int)$v * $unit);}
        $t = 0;
        foreach (explode(':', $v) as $part) {
            $t = $t * 60 + (int)$part;}
        return $t;}

    public function update(string $id) {
        $v = DB::one('SELECT * FROM {videos} WHERE id = ?', [(int)$id]) ?? abort(404);
        $d = $this->validate(['title' => 'required|max:200', 'duration' => 'max:10', 'provider' => 'in:bunny,vimeo,youtube', 'source' => 'max:500']);
        $row = ['title' => $d['title'], 'duration' => self::seconds((string)($d['duration'] ?? 0)), 'updated_at' => now()];
        if ($v['provider'] !== 'local' && trim((string)($d['source'] ?? '')) !== '') {
            $prov = ($d['provider'] ?? '') ?: $v['provider'];
            $src = self::sourceId($prov, (string)$d['source']);
            if ($src === '') {
                flash('error', 'That video link could not be read. Paste the full URL or the video ID.');
                back();}
            $row += ['provider' => $prov, 'source' => $src];}
        DB::update('videos', $row, 'id = ?', [$v['id']]);
        Audit::log('video.updated', 'video', (int)$v['id'], array_intersect_key($row, ['provider' => 1, 'source' => 1]));
        if ($this->req->isAjax()) {
            json_out(['ok' => true]);}
        $this->ok('Video updated.');}

    /** Quality versions of an uploaded video (each is a separate file the player can switch to). */
    public function qualities(string $id) {
        $v = DB::one("SELECT * FROM {videos} WHERE id = ? AND provider = 'local'", [(int)$id]) ?? abort(404);
        $rows = DB::all('SELECT * FROM {video_renditions} WHERE video_id = ? ORDER BY height DESC', [$v['id']]);
        return view('admin/videos/qualities', ['v' => $v, 'rows' => $rows, 'maxChunk' => min(8 * 1048576, (int)(Uploads::maxUploadBytes() * 0.9))]);}

    public function saveQualityLabel(string $id) {
        $v = DB::one("SELECT id FROM {videos} WHERE id = ? AND provider = 'local'", [(int)$id]) ?? abort(404);
        $label = $this->req->str('quality_label');
        DB::update('videos', ['quality_label' => isset(Media::HEIGHTS[$label]) ? $label : null, 'updated_at' => now()], 'id = ?', [$v['id']]);
        $this->ok('Saved.');}

    public function deleteRendition(string $id) {
        $r = DB::one('SELECT * FROM {video_renditions} WHERE id = ?', [(int)$id]) ?? abort(404);
        @unlink(Media::DIR . '/' . basename($r['source']));
        DB::delete('video_renditions', 'id = ?', [$r['id']]);
        $this->ok($r['label'] . ' version removed.');}

    public function stream(string $id) {
        $v = DB::one("SELECT * FROM {videos} WHERE id = ? AND provider = 'local'", [(int)$id]) ?? abort(404);
        $file = Media::path($v) ?? abort(404);
        \App\Core\Session::close();
        Media::sendFile($file, $v['mime'] ?: 'video/mp4');}

    public function destroy(string $id) {
        $v = DB::one('SELECT * FROM {videos} WHERE id = ?', [(int)$id]) ?? abort(404);
        if ($v['provider'] === 'local' && ($file = Media::path($v))) {
            @unlink($file);}
        foreach (DB::col('SELECT source FROM {video_renditions} WHERE video_id = ?', [$v['id']]) as $src) {
            @unlink(Media::DIR . '/' . basename($src));}
        DB::delete('video_renditions', 'video_id = ?', [$v['id']]);
        DB::delete('videos', 'id = ?', [$v['id']]);
        Audit::log('video.deleted', 'video', (int)$v['id'], ['title' => $v['title']]);
        $this->ok('Video deleted.');}}
