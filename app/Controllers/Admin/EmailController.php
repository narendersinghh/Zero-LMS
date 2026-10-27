<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Html;
use App\Core\Mailer;
use App\Core\Paginator;

class EmailController extends Controller {
    public const VARS = [
        'common' => ['site_name', 'site_url', 'login_url', 'support_email', 'name', 'first_name', 'email', 'year'],
        'welcome' => ['setup_link', 'course_line', 'link_hours'], 'enrolled' => ['course_title', 'course_url', 'access_line'], 'password_reset' => ['reset_link', 'link_hours'],
        'lesson_unlocked' => ['course_title', 'lesson_title', 'lesson_url', 'quote'], 'access_expiring' => ['course_title', 'course_url', 'expires_at', 'progress'], 'access_extended' => ['course_title', 'course_url', 'expires_at'],];

    public function index() {
        $stats = DB::pairs('SELECT status, COUNT(*) FROM {mail_queue} GROUP BY status');
        return view('admin/emails/index', ['templates' => DB::all('SELECT * FROM {email_templates} ORDER BY id'), 'stats' => $stats]);}

    private function tpl(string $key): array {
        return DB::one('SELECT * FROM {email_templates} WHERE `key` = ?', [$key]) ?? abort(404);}

    public function edit(string $key) {
        return view('admin/emails/edit', ['t' => $this->tpl($key), 'vars' => array_merge(self::VARS['common'], self::VARS[$key] ?? [])]);}

    public function update(string $key) {
        $t = $this->tpl($key);
        $d = $this->validate(['subject' => 'required|max:255', 'body' => 'required']);
        DB::update('email_templates', ['subject' => $d['subject'], 'body' => Html::clean($d['body']), 'enabled' => $this->req->bool('enabled') ? 1 : 0, 'updated_at' => now()], 'id = ?', [$t['id']]);
        Audit::log('email_template.updated', 'email_template', (int)$t['id'], ['key' => $key]);
        $this->ok('Template saved.');}

    private function sample(): array {
        $u = Auth::user();
        return array_merge(Mailer::globals(), ['name' => $u['name'], 'first_name' => explode(' ', $u['name'])[0], 'email' => $u['email'], 'setup_link' => url('/password/setup/sample-token', [], true),
            'reset_link' => url('/password/reset/sample-token', [], true), 'course_title' => 'Sample Course', 'course_url' => url('/courses/sample', [], true), 'lesson_title' => 'Day 3 · Building momentum',
            'lesson_url' => url('/courses/sample/lessons/1', [], true), 'expires_at' => dt(time() + 3 * 86400), 'progress' => 64, 'link_hours' => 168, 'quote' => 'Progress, not perfection.',
            'course_line' => ' with access to <strong>Sample Course</strong>', 'access_line' => 'Your access is valid until <strong>' . e(dt(time() + 30 * 86400)) . '</strong>.']);}

    public function preview(string $key) {
        $t = $this->tpl($key);
        $vars = $this->sample();
        echo Mailer::layout(Mailer::fill($t['body'], $vars, true), Mailer::fill($t['subject'], $vars, false));
        exit;}

    public function test(string $key) {
        $t = $this->tpl($key);
        $vars = $this->sample();
        $subj = '[Test] ' . Mailer::fill($t['subject'], $vars, false);
        try {
            Mailer::sendNow(Auth::user()['email'], Auth::user()['name'], $subj, Mailer::layout(Mailer::fill($t['body'], $vars, true), $subj));
            $this->ok('Test email sent to ' . Auth::user()['email'] . (Mailer::config()['driver'] === 'log' ? ' (mail driver is "log": check storage/logs)' : '') . '.');}
        catch (\Throwable $e) {
            flash('error', 'Sending failed: ' . $e->getMessage());
            back();}}

    public function log() {
        $w = ['1=1'];
        $p = [];
        if (in_array($st = $this->req->str('status'), ['pending', 'sent', 'failed'], true)) {
            $w[] = 'status = ?';
            $p[] = $st;}
        if (($q = $this->req->str('q')) !== '') {
            $w[] = '(to_email LIKE ? OR subject LIKE ?)';
            array_push($p, DB::like($q), DB::like($q));}
        $ws = implode(' AND ', $w);
        $pg = new Paginator((int)DB::val("SELECT COUNT(*) FROM {mail_queue} WHERE $ws", $p), 50);
        $rows = DB::all("SELECT id, to_email, to_name, subject, template, status, attempts, last_error, send_after, sent_at, created_at FROM {mail_queue} WHERE $ws ORDER BY id DESC" . $pg->limitSql(), $p);
        return view('admin/emails/log', ['rows' => $rows, 'pg' => $pg, 'driver' => Mailer::config()['driver']]);}

    public function process() {
        $n = Mailer::processQueue(100);
        $this->ok("$n email(s) sent.");}

    public function retry(string $id) {
        DB::update('mail_queue', ['status' => 'pending', 'attempts' => 0, 'send_after' => now()], 'id = ?', [(int)$id]);
        $this->ok('Email re-queued.');}}
