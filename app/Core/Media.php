<?php
namespace App\Core;

use App\Services\Access;

/**
 * Protected video delivery.
 * Local files live in storage/videos (not web accessible) and are streamed through PHP using short-lived,
 * HMAC-signed tokens bound to the user, lesson and session. Every token is checked server side against
 * the enrollment before bytes are sent. External providers use their own signed/privacy embeds.
 */
class Media {
    public const DIR = STORAGE_PATH . '/videos';

    public const HEIGHTS = ['2160p' => 2160, '1440p' => 1440, '1080p' => 1080, '720p' => 720, '480p' => 480, '360p' => 360, '240p' => 240];

    public static function token(int $videoId, int $lessonId, int $userId, int $rendition = 0): string {
        $ttl = max(10, (int)Settings::get('video_token_ttl', 240)) * 60;
        $p = Crypto::b64u(json_encode(['v' => $videoId, 'r' => $rendition, 'l' => $lessonId, 'u' => $userId, 'e' => time() + $ttl, 's' => substr(hash('sha256', Session::id()), 0, 20)]));
        return $p . '.' . Crypto::sign($p, 'media');}

    public static function verify(string $token): ?array {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2 || !hash_equals(Crypto::sign($parts[0], 'media'), $parts[1])) {
            return null;}
        $d = json_decode((string)Crypto::b64uDecode($parts[0]), true);
        if (!is_array($d) || ($d['e'] ?? 0) < time()) {
            return null;}
        if (!hash_equals((string)($d['s'] ?? ''), substr(hash('sha256', Session::id()), 0, 20))) {
            return null;}
        return $d;}

    public static function path(array $video): ?string {
        $file = realpath(self::DIR . '/' . $video['source']);
        $base = realpath(self::DIR);
        return ($file && $base && strpos($file, $base . DIRECTORY_SEPARATOR) === 0 && is_file($file)) ? $file : null;}

    /** URL the player should load for a lesson's video. */
    public static function playbackUrl(array $video, int $lessonId, int $userId): string {
        switch ($video['provider']) {
            case 'local':
                return url('/media/stream/' . self::token((int)$video['id'], $lessonId, $userId));
            case 'bunny':
                $lib = Settings::get('bunny_library_id');
                $key = Settings::get('bunny_token_key');
                $vid = rawurlencode($video['source']);
                $u = 'https://iframe.mediadelivery.net/embed/' . rawurlencode((string)$lib) . '/' . $vid . '?autoplay=false&preload=true&responsive=true';
                if ($key) {
                    $exp = time() + max(10, (int)Settings::get('video_token_ttl', 240)) * 60;
                    $u .= '&token=' . hash('sha256', Crypto::decrypt((string)$key) . $video['source'] . $exp) . '&expires=' . $exp;}
                return $u;
            case 'youtube':
                return 'https://www.youtube-nocookie.com/embed/' . rawurlencode(self::youtubeId($video['source'])) . '?modestbranding=1&rel=0&iv_load_policy=3&playsinline=1&enablejsapi=0';
            case 'vimeo':
                [$id, $h] = array_pad(explode('/', self::vimeoId($video['source']), 2), 2, '');
                return 'https://player.vimeo.com/video/' . rawurlencode($id) . '?title=0&byline=0&portrait=0&dnt=1' . ($h ? '&h=' . rawurlencode($h) : '');}
        return '';}

    /** Player quality list for an uploaded video: the original plus any extra versions, highest first. */
    public static function qualities(array $video, int $lessonId, int $userId): array {
        if ($video['provider'] !== 'local') {
            return [];}
        $rows = DB::all('SELECT id, label, height FROM {video_renditions} WHERE video_id = ? ORDER BY height DESC', [(int)$video['id']]);
        if (!$rows) {
            return [];}
        $label = (string)($video['quality_label'] ?: 'Original');
        $list = [['l' => $label, 'h' => self::HEIGHTS[$label] ?? 9999, 'u' => self::playbackUrl($video, $lessonId, $userId)]];
        foreach ($rows as $r) {
            $list[] = ['l' => $r['label'], 'h' => (int)$r['height'], 'u' => url('/media/stream/' . self::token((int)$video['id'], $lessonId, $userId, (int)$r['id']))];}
        usort($list, fn($a, $b) => $b['h'] <=> $a['h']);
        return $list;}

    public static function isEmbed(array $video): bool {
        return in_array($video['provider'], ['youtube', 'vimeo', 'bunny'], true);}

    public static function youtubeId(string $s): string {
        if (preg_match('~(?:youtu\.be/|v=|embed/|shorts/|live/)([A-Za-z0-9_-]{6,})~', $s, $m)) {
            return $m[1];}
        return preg_replace('/[^A-Za-z0-9_-]/', '', $s);}

    public static function vimeoId(string $s): string {
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)(?:/([a-z0-9]+))?~i', $s, $m)) {
            return $m[1] . (isset($m[2]) ? '/' . $m[2] : '');}
        if (preg_match('~[?&]h=([a-z0-9]+)~i', $s, $h) && preg_match('~(\d{5,})~', $s, $m)) {
            return $m[1] . '/' . $h[1];}
        return preg_replace('/[^0-9a-z\/]/i', '', $s);}

    /** Validate token + access and stream the file with HTTP Range support. */
    public static function stream(string $token): void {
        $req = App::instance()->request();
        $d = self::verify($token);
        $user = Auth::user();
        if (!$d || !$user || (int)$d['u'] !== (int)$user['id']) {
            throw new HttpException(403, 'This video link has expired. Please reload the lesson.');}
        if (Settings::get('video_block_direct', true)) {
            $dest = $req->header('Sec-Fetch-Dest');
            if ($dest !== null && !in_array($dest, ['video', 'audio', 'empty'], true)) {
                throw new HttpException(403, 'Direct video access is not allowed.');}}
        // Re-validate enrollment at most every 2 minutes per lesson (range requests are frequent).
        $okKey = '_media_ok';
        $ok = $_SESSION[$okKey][$d['l']] ?? 0;
        if (time() - $ok > 120) {
            $lesson = DB::one('SELECT * FROM {lessons} WHERE id = ?', [(int)$d['l']]);
            if (!$lesson || (int)$lesson['video_id'] !== (int)$d['v'] || !Access::canViewLesson($user, $lesson)['ok']) {
                throw new HttpException(403);}
            $_SESSION[$okKey][$d['l']] = time();}
        $video = DB::one('SELECT * FROM {videos} WHERE id = ? AND provider = ?', [(int)$d['v'], 'local']);
        if ($video && !empty($d['r'])) {
            $r = DB::one('SELECT source, mime FROM {video_renditions} WHERE id = ? AND video_id = ?', [(int)$d['r'], (int)$video['id']]);
            $video = $r ? array_merge($video, $r) : null;}
        $file = $video ? self::path($video) : null;
        if (!$file) {
            throw new HttpException(404, 'Video file not found.');}
        Session::close();
        self::sendFile($file, $video['mime'] ?: 'video/mp4');}

    public static function sendFile(string $file, string $mime): void {
        $size = filesize($file);
        $start = 0;
        $end = $size - 1;
        while (ob_get_level() > 0) {
            ob_end_clean();}
        header('Content-Type: ' . $mime);
        header('Accept-Ranges: bytes');
        header('Content-Disposition: inline; filename="video"');
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Robots-Tag: noindex, nofollow');
        header('Cross-Origin-Resource-Policy: same-origin');
        $mode = Settings::get('video_sendfile', 'none');
        if ($mode === 'xsendfile') {
            header('X-Sendfile: ' . $file);
            exit;}
        if ($mode === 'xaccel') {
            header('X-Accel-Redirect: /protected-videos/' . rawurlencode(basename($file)));
            exit;}
        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
            if ($m[1] === '' && $m[2] !== '') {
                $start = max(0, $size - (int)$m[2]);}
            else {
                $start = (int)$m[1];
                if ($m[2] !== '') {
                    $end = min((int)$m[2], $size - 1);}}
            if ($start > $end || $start >= $size) {
                http_response_code(416);
                header("Content-Range: bytes */$size");
                exit;}
            http_response_code(206);
            header("Content-Range: bytes $start-$end/$size");}
        $len = $end - $start + 1;
        header('Content-Length: ' . $len);
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
            exit;}
        @set_time_limit(0);
        $fp = fopen($file, 'rb');
        fseek($fp, $start);
        $chunk = 1048576;
        while ($len > 0 && !feof($fp) && !connection_aborted()) {
            $read = (int)min($chunk, $len);
            echo fread($fp, $read);
            flush();
            $len -= $read;}
        fclose($fp);
        exit;}}
