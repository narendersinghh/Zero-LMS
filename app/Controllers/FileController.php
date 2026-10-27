<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Uploads;

/**
 * Files attached inside lesson content. Images are public (uploads/content, random names) so they render anywhere;
 * documents (PDF, Office, ZIP...) live in storage/files and are only served to signed-in users.
 */
class FileController extends Controller {
    public const DOCS = ['pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'ppt' => 'application/vnd.ms-powerpoint', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'csv' => 'text/csv', 'txt' => 'text/plain', 'zip' => 'application/zip', 'mp3' => 'audio/mpeg'];
    private const DIR = STORAGE_PATH . '/files';
    private const MAX = 50 * 1048576;

    public function upload() {
        $f = $this->req->file('file');
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            json_out(['error' => Uploads::errorMessage((int)($f['error'] ?? UPLOAD_ERR_NO_FILE))], 422);}
        $name = mb_substr(trim(preg_replace('/[^\pL\pN ._()-]+/u', '_', basename((string)$f['name'])), '._ '), 0, 120) ?: 'file';
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
            try {
                $rel = Uploads::image($f, 'content', 8192);}
            catch (\RuntimeException $e) {
                json_out(['error' => $e->getMessage()], 422);}
            [$w, $h] = @getimagesize(UPLOAD_PATH . '/' . $rel) ?: [0, 0];
            json_out(['ok' => true, 'kind' => 'image', 'url' => upload_url($rel), 'name' => $name, 'width' => $w, 'height' => $h]);}
        if (!isset(self::DOCS[$ext])) {
            json_out(['error' => 'Allowed: images (JPG, PNG, WebP, GIF) and ' . strtoupper(implode(', ', array_keys(self::DOCS))) . '.'], 422);}
        if ($f['size'] > self::MAX) {
            json_out(['error' => 'File is larger than 50 MB.'], 422);}
        $mime = Uploads::mime($f['tmp_name']);
        if ($ext === 'pdf' && $mime !== 'application/pdf') {
            json_out(['error' => 'This file is not a valid PDF.'], 422);}
        if (!is_dir(self::DIR)) {
            mkdir(self::DIR, 0700, true);
            @file_put_contents(self::DIR . '/.htaccess', "Require all denied\n");}
        $stored = gmdate('Ym') . '-' . bin2hex(random_bytes(10)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], self::DIR . '/' . $stored)) {
            json_out(['error' => 'Could not save the file.'], 500);}
        // Keep the original name next to the file so downloads get a friendly filename.
        @file_put_contents(self::DIR . '/' . $stored . '.name', $name);
        Audit::log('file.uploaded', null, null, ['file' => $stored, 'name' => $name]);
        json_out(['ok' => true, 'kind' => 'file', 'url' => url('/files/' . $stored), 'name' => $name, 'ext' => $ext, 'size' => human_size((int)$f['size'])]);}

    public function show(string $name) {
        if (!preg_match('/^\d{6}-[a-f0-9]{20}\.([a-z0-9]{2,5})$/', $name, $m) || !isset(self::DOCS[$m[1]]) || !is_file(self::DIR . '/' . $name)) {
            abort(404);}
        \App\Core\Session::close();
        $file = self::DIR . '/' . $name;
        $label = is_file($file . '.name') ? (string)file_get_contents($file . '.name') : $name;
        $inline = in_array($m[1], ['pdf', 'txt', 'mp3'], true) && !$this->req->query('download');
        while (ob_get_level() > 0) {
            ob_end_clean();}
        header('Content-Type: ' . self::DOCS[$m[1]]);
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $label) . '"; filename*=UTF-8\'\'' . rawurlencode($label));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        if ($m[1] !== 'pdf') {
            // Chrome's PDF viewer refuses sandboxed documents, so only non-PDF files get the locked-down policy.
            header("Content-Security-Policy: default-src 'none'; sandbox");}
        readfile($file);
        exit;}}
