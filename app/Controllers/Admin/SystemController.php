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
        if (!Settings::get('cron_secret')) {
            Settings::set(['cron_secret' => bin2hex(random_bytes(16))]);}
        $info = ['PHP version' => PHP_VERSION, 'Database' => DB::val('SELECT VERSION()'), 'Server' => $_SERVER['SERVER_SOFTWARE'] ?? 'n/a', 'Max upload' => human_size(\App\Core\Uploads::maxUploadBytes()),
            'Memory limit' => ini_get('memory_limit'), 'Max execution time' => ini_get('max_execution_time') . 's', 'Video storage used' => human_size((int)DB::val("SELECT COALESCE(SUM(size),0) FROM {videos} WHERE provider = 'local'")),
            'Zip extension' => class_exists('ZipArchive') ? 'Yes' : 'Missing', 'cURL extension' => function_exists('curl_init') ? 'Yes' : 'Missing', 'OPcache' => function_exists('opcache_get_status') && @opcache_get_status(false) ? 'Enabled' : 'Off',
            'fastcgi_finish_request' => function_exists('fastcgi_finish_request') ? 'Available (web cron runs after response)' : 'Unavailable (use real cron)'];
        $writable = [];
        foreach (['storage', 'storage/videos', 'storage/cache', 'storage/logs', 'storage/backups', 'uploads', '.env', 'app', 'assets'] as $p) {
            $writable[$p] = is_writable(BASE_PATH . '/' . $p);}
        return view('admin/system/index', ['info' => $info, 'writable' => $writable, 'modules' => ModuleManager::discover(), 'enabled' => ModuleManager::enabled(),
            'backups' => Updater::backups(), 'last' => Settings::get('update_last_result'), 'lastCheck' => Settings::get('update_last_check'), 'pending' => Migrator::pending(),
            'maintenance' => is_file(STORAGE_PATH . '/maintenance'), 's' => Settings::all(), 'version' => App::version()]);}

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

    public function modules() {
        $all = ModuleManager::discover();
        $on = array_values(array_intersect(array_map('strval', $this->req->arr('modules')), array_keys($all)));
        $ran = [];
        foreach (array_diff($on, ModuleManager::enabled()) as $key) {
            $ran = array_merge($ran, ModuleManager::migrate($key));}
        Settings::set(['modules_enabled' => $on]);
        Audit::log('modules.updated', null, null, ['enabled' => $on]);
        $this->ok('Modules updated.' . ($ran ? ' Migrations: ' . implode(', ', $ran) : ''));}

    public function maintenance() {
        $on = !is_file(STORAGE_PATH . '/maintenance');
        Updater::maintenance($on);
        Audit::log('system.maintenance', null, null, ['on' => $on]);
        $this->ok($on ? 'Maintenance mode enabled — only staff can access the site.' : 'Maintenance mode disabled.');}

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
