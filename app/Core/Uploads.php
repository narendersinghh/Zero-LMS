<?php
namespace App\Core;

/** Safe upload handling for public images and private video files. */
class Uploads {
    public const IMAGE_TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/svg+xml' => 'svg', 'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico'];
    public const VIDEO_TYPES = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov', 'video/x-m4v' => 'm4v', 'audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a'];

    public static function mime(string $path): string {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        $m = (string)finfo_file($f, $path);
        finfo_close($f);
        return $m;}

    /** Store an uploaded image under uploads/<folder>/ and return the relative path. */
    public static function image(array $file, string $folder = 'brand', int $maxKb = 4096, bool $allowSvg = false): string {
        if (($file['error'] ?? 1) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException(self::errorMessage((int)($file['error'] ?? 1)));}
        if ($file['size'] > $maxKb * 1024) {
            throw new \RuntimeException('Image is too large (max ' . round($maxKb / 1024, 1) . ' MB).');}
        $mime = self::mime($file['tmp_name']);
        if ($mime === 'text/xml' || $mime === 'text/plain') {
            $mime = str_ends_with(strtolower($file['name']), '.svg') ? 'image/svg+xml' : $mime;}
        $ext = self::IMAGE_TYPES[$mime] ?? null;
        if (!$ext || ($ext === 'svg' && !$allowSvg)) {
            throw new \RuntimeException('Unsupported image type.');}
        if ($ext === 'svg') {
            $svg = (string)file_get_contents($file['tmp_name']);
            if (preg_match('/<script|on\w+\s*=|javascript:|<foreignObject|<iframe|xlink:href\s*=\s*["\'](?!#|data:image)/i', $svg)) {
                throw new \RuntimeException('SVG contains disallowed content.');}}
        elseif ($ext !== 'ico' && !@getimagesize($file['tmp_name'])) {
            throw new \RuntimeException('File is not a valid image.');}
        $dir = UPLOAD_PATH . '/' . $folder;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);}
        $name = gmdate('Ym') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            throw new \RuntimeException('Could not save the uploaded file.');}
        @chmod($dir . '/' . $name, 0644);
        return $folder . '/' . $name;}

    public static function deletePublic(?string $rel): void {
        if ($rel && strpos($rel, '..') === false && is_file(UPLOAD_PATH . '/' . $rel)) {
            @unlink(UPLOAD_PATH . '/' . $rel);}}

    public static function errorMessage(int $code): string {
        return [UPLOAD_ERR_INI_SIZE => 'File exceeds the server upload limit (upload_max_filesize).', UPLOAD_ERR_FORM_SIZE => 'File is too large.', UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.', UPLOAD_ERR_NO_FILE => 'No file was uploaded.', UPLOAD_ERR_NO_TMP_DIR => 'Server temp folder missing.', UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.'][$code] ?? 'Upload failed.';}

    public static function maxUploadBytes(): int {
        $f = fn($v) => (int)$v * ([ 'k' => 1024, 'm' => 1048576, 'g' => 1073741824][strtolower(substr(trim((string)$v), -1))] ?? 1);
        return min($f(ini_get('upload_max_filesize')), $f(ini_get('post_max_size')));}}
