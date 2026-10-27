<?php
namespace App\Core;

/**
 * One-click updates from a GitHub repository (latest release or a branch).
 * Flow: check -> download zip -> validate -> backup current code -> extract (keeping .env, storage/, uploads/) -> migrate.
 */
class Updater {
    private const PROTECTED = ['.env', 'storage/', 'uploads/', '.git/', '.user.ini', 'php.ini'];
    private const CODE_PATHS = ['app', 'assets', 'database', 'tools', 'index.php', 'cron.php', '.htaccess', 'version.json', 'README.md'];

    private static function repo(): string {
        $r = trim((string)Settings::get('update_repo', ''), " /");
        if (!preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $r)) {
            throw new \RuntimeException('Update repository must be in the form owner/repo.');}
        return $r;}

    private static function http(string $url, ?string $saveTo = null, string $accept = 'application/vnd.github+json') {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('The PHP cURL extension is required for updates.');}
        $h = ['User-Agent: Zeroes-LMS-Updater', 'Accept: ' . $accept, 'X-GitHub-Api-Version: 2022-11-28'];
        $tok = Crypto::decrypt((string)Settings::get('update_token', ''));
        if ($tok) {
            $h[] = 'Authorization: Bearer ' . $tok;}
        $ch = curl_init($url);
        $fp = null;
        curl_setopt_array($ch, [CURLOPT_HTTPHEADER => $h, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 300, CURLOPT_SSL_VERIFYPEER => true]);
        if ($saveTo) {
            $fp = fopen($saveTo, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);}
        else {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);}
        $res = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($fp) {
            fclose($fp);}
        if ($res === false || $code >= 400) {
            if ($saveTo) {
                @unlink($saveTo);}
            $msg = $code === 404 ? 'Repository or release not found (private repos need an access token).' : ($code === 403 ? 'GitHub rate limit or permission denied. Add an access token.' : ($err ?: 'HTTP ' . $code));
            throw new \RuntimeException('GitHub request failed: ' . $msg);}
        return $saveTo ? true : json_decode((string)$res, true);}

    public static function check(): array {
        $repo = self::repo();
        $current = App::version();
        if (Settings::get('update_channel', 'release') === 'branch') {
            $branch = (string)Settings::get('update_branch', 'main');
            $c = self::http("https://api.github.com/repos/$repo/commits/" . rawurlencode($branch));
            $sha = (string)($c['sha'] ?? '');
            $installed = (string)Settings::get('installed_commit', '');
            $info = ['channel' => 'branch', 'current' => $current . ($installed ? ' (' . substr($installed, 0, 7) . ')' : ''), 'latest' => $branch . ' @ ' . substr($sha, 0, 7),
                'available' => $sha !== '' && $sha !== $installed, 'notes' => (string)($c['commit']['message'] ?? ''), 'published_at' => (string)($c['commit']['committer']['date'] ?? ''),
                'zip' => "https://api.github.com/repos/$repo/zipball/$sha", 'ref' => $sha, 'accept' => 'application/vnd.github+json'];}
        else {
            $r = self::http("https://api.github.com/repos/$repo/releases/latest");
            $latest = ltrim((string)($r['tag_name'] ?? ''), 'vV');
            $zip = (string)($r['zipball_url'] ?? '');
            $accept = 'application/vnd.github+json';
            foreach ($r['assets'] ?? [] as $a) {
                if (substr((string)$a['name'], -4) === '.zip') {
                    $zip = (string)$a['url'];
                    $accept = 'application/octet-stream';
                    break;}}
            $info = ['channel' => 'release', 'current' => $current, 'latest' => $latest, 'available' => $latest !== '' && version_compare($latest, $current, '>'),
                'notes' => (string)($r['body'] ?? ''), 'published_at' => (string)($r['published_at'] ?? ''), 'zip' => $zip, 'ref' => (string)($r['tag_name'] ?? ''), 'accept' => $accept];}
        Settings::set(['update_last_check' => now(), 'update_last_result' => array_diff_key($info, ['zip' => 1, 'accept' => 1])]);
        return $info;}

    public static function apply(array $info): array {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('The PHP Zip extension is required for updates.');}
        @set_time_limit(0);
        @ini_set('memory_limit', '256M');
        $log = [];
        $tmp = STORAGE_PATH . '/tmp/update-' . gmdate('YmdHis') . '.zip';
        self::maintenance(true);
        try {
            self::http($info['zip'], $tmp, $info['accept'] ?? 'application/vnd.github+json');
            $log[] = 'Downloaded package (' . human_size(filesize($tmp)) . ').';
            $zip = new \ZipArchive();
            if ($zip->open($tmp) !== true) {
                throw new \RuntimeException('Downloaded file is not a valid zip archive.');}
            $prefix = self::detectPrefix($zip);
            if ($zip->locateName($prefix . 'version.json') === false || $zip->locateName($prefix . 'app/bootstrap.php') === false) {
                throw new \RuntimeException('Package does not look like a Zeroes release.');}
            $backup = self::backup();
            $log[] = 'Backup created: ' . basename($backup);
            $count = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string)$zip->getNameIndex($i);
                $rel = substr($name, strlen($prefix));
                if ($rel === '' || substr($rel, -1) === '/' || strpos($rel, '..') !== false || self::isProtected($rel)) {
                    continue;}
                $dest = BASE_PATH . '/' . $rel;
                if (!is_dir(dirname($dest)) && !@mkdir(dirname($dest), 0755, true)) {
                    throw new \RuntimeException('Cannot create directory for ' . $rel);}
                $data = $zip->getFromIndex($i);
                $part = $dest . '.zxnew';
                if ($data === false || file_put_contents($part, $data) === false || !@rename($part, $dest)) {
                    @unlink($part);
                    throw new \RuntimeException('Cannot write ' . $rel . ' (check file permissions).');}
                $count++;}
            // Files deleted from the codebase are listed in the release's version.json ("removed") and cleaned up here.
            $gone = 0;
            foreach ((array)(json_decode((string)$zip->getFromName($prefix . 'version.json'), true)['removed'] ?? []) as $rel) {
                $rel = trim((string)$rel, '/');
                if ($rel === '' || strpos($rel, '..') !== false || self::isProtected($rel) || !preg_match('#^(app|assets|database|tools)/#', $rel) || !file_exists(BASE_PATH . '/' . $rel)) {
                    continue;}
                is_dir(BASE_PATH . '/' . $rel) ? Scheduler::rmdir(BASE_PATH . '/' . $rel) : @unlink(BASE_PATH . '/' . $rel);
                $gone++;}
            $zip->close();
            $log[] = "Updated $count files" . ($gone ? ", removed $gone obsolete." : '.');
            if (function_exists('opcache_reset')) {
                @opcache_reset();}
            $ran = Migrator::run();
            $log[] = $ran ? 'Migrations applied: ' . implode(', ', $ran) : 'No database migrations needed.';
            Settings::clearCache();
            Settings::set(['installed_commit' => $info['channel'] === 'branch' ? $info['ref'] : (string)Settings::get('installed_commit', ''), 'update_last_applied' => now()]);
            Audit::log('system.update', null, null, ['to' => $info['latest'], 'files' => $count]);}
        finally {
            @unlink($tmp);
            self::maintenance(false);}
        return $log;}

    private static function detectPrefix(\ZipArchive $zip): string {
        if ($zip->locateName('version.json') !== false) {
            return '';}
        $first = (string)$zip->getNameIndex(0);
        $p = strpos($first, '/') !== false ? substr($first, 0, strpos($first, '/') + 1) : '';
        return $p;}

    private static function isProtected(string $rel): bool {
        foreach (self::PROTECTED as $p) {
            if ($rel === rtrim($p, '/') || strpos($rel, $p) === 0) {
                return true;}}
        return false;}

    /** Zip the current code (not storage/uploads/.env) so an update can be rolled back. */
    public static function backup(): string {
        $file = STORAGE_PATH . '/backups/code-' . App::version() . '-' . gmdate('Ymd-His') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create backup in storage/backups.');}
        foreach (self::CODE_PATHS as $p) {
            $full = BASE_PATH . '/' . $p;
            if (is_file($full)) {
                $zip->addFile($full, $p);}
            elseif (is_dir($full)) {
                $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($full, \FilesystemIterator::SKIP_DOTS));
                foreach ($it as $f) {
                    if ($f->isFile()) {
                        $zip->addFile($f->getPathname(), substr(str_replace('\\', '/', $f->getPathname()), strlen(BASE_PATH) + 1));}}}}
        $zip->close();
        // Keep the 5 most recent backups.
        $all = glob(STORAGE_PATH . '/backups/code-*.zip') ?: [];
        usort($all, fn($a, $b) => filemtime($b) <=> filemtime($a));
        foreach (array_slice($all, 5) as $old) {
            @unlink($old);}
        return $file;}

    public static function backups(): array {
        $all = glob(STORAGE_PATH . '/backups/code-*.zip') ?: [];
        usort($all, fn($a, $b) => filemtime($b) <=> filemtime($a));
        return array_map(fn($f) => ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)], $all);}

    public static function rollback(string $name): void {
        $file = STORAGE_PATH . '/backups/' . basename($name);
        if (!preg_match('/^code-.*\.zip$/', basename($name)) || !is_file($file)) {
            throw new \RuntimeException('Backup not found.');}
        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) {
            throw new \RuntimeException('Backup archive is corrupt.');}
        self::maintenance(true);
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $rel = (string)$zip->getNameIndex($i);
                if (strpos($rel, '..') !== false || self::isProtected($rel) || substr($rel, -1) === '/') {
                    continue;}
                $dest = BASE_PATH . '/' . $rel;
                @mkdir(dirname($dest), 0755, true);
                file_put_contents($dest, $zip->getFromIndex($i));}
            $zip->close();
            if (function_exists('opcache_reset')) {
                @opcache_reset();}
            Settings::clearCache();
            Audit::log('system.rollback', null, null, ['backup' => basename($name)]);}
        finally {
            self::maintenance(false);}}

    public static function maintenance(bool $on): void {
        $f = STORAGE_PATH . '/maintenance';
        $on ? @file_put_contents($f, now()) : @unlink($f);}}
