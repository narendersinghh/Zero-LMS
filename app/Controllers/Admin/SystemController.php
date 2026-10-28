<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Audit;
use App\Core\Crypto;
use App\Core\DB;
use App\Core\Migrator;
use App\Core\ModuleManager;
use App\Core\Scheduler;
use App\Core\Settings;
use App\Core\Updater;

class SystemController extends Controller {
    public function index() {
        Scheduler::secret();
        $s = Settings::all();
        $cronLast = (string)($s['cron_last_run'] ?? '');
        $cronAge = $cronLast ? time() - strtotime($cronLast . ' UTC') : null;
        try {
            $driver = (string)(\App\Core\Mailer::config()['driver'] ?? 'log');}
        catch (\Throwable $e) {
            $driver = 'log';}
        $notWritable = [];
        foreach (['storage', 'storage/videos', 'storage/cache', 'storage/logs', 'storage/backups', 'storage/sessions', 'uploads'] as $p) {
            if (!is_writable(BASE_PATH . '/' . $p)) {
                $notWritable[] = $p;}}
        $codeWritable = is_writable(BASE_PATH . '/app') && is_writable(BASE_PATH . '/assets');
        $pending = Migrator::pending();
        $videoBytes = (int)DB::val("SELECT COALESCE(SUM(size),0) FROM {videos} WHERE provider = 'local'");
        $free = @disk_free_space(STORAGE_PATH);
        $opcache = function_exists('opcache_get_status') && @opcache_get_status(false);
        $maintenance = is_file(STORAGE_PATH . '/maintenance');
        // [state ok|warn|bad, title, detail]
        $health = [
            [PHP_VERSION_ID >= 80100 ? 'ok' : 'warn', 'PHP ' . PHP_VERSION, PHP_VERSION_ID >= 80100 ? 'Supported version' : 'Works, but PHP 8.1+ is recommended'],
            ['ok', 'Database', (string)DB::val('SELECT VERSION()')],
            [$notWritable ? 'bad' : 'ok', 'Storage folders', $notWritable ? 'Not writable: ' . implode(', ', $notWritable) : 'storage/ and uploads/ are writable'],
            [$codeWritable ? 'ok' : 'warn', 'Code folders', $codeWritable ? 'Writable, one-click updates can replace files' : 'app/ or assets/ not writable, updates need write access'],
            [class_exists('ZipArchive') ? 'ok' : 'bad', 'Zip extension', class_exists('ZipArchive') ? 'Available for updates & backups' : 'Missing, updates and backups need it'],
            [function_exists('curl_init') ? 'ok' : 'bad', 'cURL extension', function_exists('curl_init') ? 'Available for GitHub & email APIs' : 'Missing, needed for updates'],
            [$opcache ? 'ok' : 'warn', 'OPcache', $opcache ? 'Enabled, faster page loads' : 'Off. Ask your host to enable it for speed'],
            [$cronAge !== null && $cronAge > 86400 ? 'warn' : 'ok', 'Scheduled tasks', $cronAge === null ? 'Automatic, starts with the next visit' : 'Automatic, last run ' . time_ago($cronLast)],
            [$driver === 'log' ? 'warn' : 'ok', 'Email delivery', $driver === 'log' ? 'Log only, emails are not sent' : 'Sending via ' . strtoupper($driver)],
            [$maintenance ? 'warn' : 'ok', 'Maintenance mode', $maintenance ? 'ON, students cannot access the site' : 'Off, site is live'],
            [$free !== false && $free < 2 * 1073741824 ? 'warn' : 'ok', 'Video storage', human_size($videoBytes) . ' used' . ($free !== false ? ' · ' . human_size($free) . ' free' : '')],
            [$pending ? 'warn' : 'ok', 'Database migrations', $pending ? count($pending) . ' pending, run them below' : 'Up to date'],
            ['ok', 'Upload limit', human_size(\App\Core\Uploads::maxUploadBytes()) . ' per request (videos upload in chunks)'],
            [ini_get('memory_limit') === '-1' || (int)ini_get('memory_limit') >= 128 ? 'ok' : 'warn', 'Memory limit', ini_get('memory_limit') === '-1' ? 'Unlimited' : (string)ini_get('memory_limit')],];
        $score = ['ok' => 0, 'warn' => 0, 'bad' => 0];
        foreach ($health as $h) {
            $score[$h[0]]++;}
        return view('admin/system/index', ['health' => $health, 'score' => $score, 'backups' => Updater::backups(), 'last' => Settings::get('update_last_result'), 'lastCheck' => Settings::get('update_last_check'),
            'pending' => $pending, 'maintenance' => $maintenance, 's' => $s, 'version' => App::version(), 'cronAge' => $cronAge, 'cronLast' => $cronLast,
            'mailPending' => (int)DB::val("SELECT COUNT(*) FROM {mail_queue} WHERE status = 'pending'"), 'moduleCount' => count(ModuleManager::enabled()) . '/' . count(ModuleManager::discover())]);}

    public function check() {
        try {
            $i = Updater::check();
            flash($i['available'] ? 'success' : 'info', $i['available'] ? 'Update available: ' . $i['latest'] : 'You are running the latest version.');}
        catch (\Throwable $e) {
            flash('error', $e->getMessage());}
        redirect('/admin/system');}

    public function update() {
        try {
            $i = Updater::check();
            if (!$i['available'] && !$this->req->bool('force')) {
                $this->ok('Already up to date.', '/admin/system');}
            $log = Updater::apply($i);
            flash('success', 'Update complete. ' . implode(' ', $log));}
        catch (\Throwable $e) {
            \App\Core\Logger::error('Update failed: ' . $e->getMessage());
            flash('error', 'Update failed: ' . $e->getMessage() . ' Your previous files are untouched or can be restored from a backup below.');}
        redirect('/admin/system');}

    public function rollback() {
        try {
            Updater::rollback($this->req->str('backup'));
            flash('success', 'Code restored from backup.');}
        catch (\Throwable $e) {
            flash('error', $e->getMessage());}
        redirect('/admin/system');}

    public function saveUpdater() {
        $repo = trim($this->req->str('update_repo'), ' /');
        $repo = preg_replace('#^https?://github\.com/#i', '', $repo);
        if (!preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repo)) {
            flash('error', 'Repository must look like owner/repo.');
            back();}
        $v = ['update_repo' => $repo, 'update_channel' => $this->req->str('update_channel') === 'branch' ? 'branch' : 'release', 'update_branch' => preg_replace('/[^A-Za-z0-9._\/-]/', '', $this->req->str('update_branch', 'main')) ?: 'main'];
        if ($this->req->str('update_token') !== '') {
            $v['update_token'] = Crypto::encrypt($this->req->str('update_token'));}
        if ($this->req->bool('clear_token')) {
            $v['update_token'] = '';}
        Settings::set($v);
        Audit::log('settings.updater', null, null, ['repo' => $repo, 'channel' => $v['update_channel']]);
        $this->ok('Update source saved.');}

    /** Legacy endpoint: modules are managed on /admin/modules now. */
    public function modules() {
        return (new ModulesController())->save();}

    public function maintenance() {
        $on = !is_file(STORAGE_PATH . '/maintenance');
        Updater::maintenance($on);
        Audit::log('system.maintenance', null, null, ['on' => $on]);
        $this->ok($on ? 'Maintenance mode enabled. Only staff can access the site.' : 'Maintenance mode disabled.');}

    public function cron() {
        $r = Scheduler::run();
        $this->ok('Scheduled tasks ran: ' . json_encode($r));}

    public function cache() {
        Settings::clearCache();
        foreach (glob(STORAGE_PATH . '/cache/*.php') ?: [] as $f) {
            @unlink($f);}
        if (function_exists('opcache_reset')) {
            @opcache_reset();}
        $this->ok('Caches cleared.');}

    public function migrate() {
        $ran = Migrator::run();
        Audit::log('system.migrate', null, null, ['ran' => $ran]);
        $this->ok($ran ? 'Migrations applied: ' . implode(', ', $ran) : 'Database is up to date.');}}
