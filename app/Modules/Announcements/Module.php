<?php
namespace App\Modules\Announcements;

use App\Core\App;
use App\Core\Hooks;
use App\Core\Module as BaseModule;
use App\Core\View;

/** Course & site-wide announcements for students. */
class Module extends BaseModule {
    public function register(App $app): void {
        $this->permissions(['super_admin' => ['announcements.manage'], 'admin' => ['announcements.manage']]);
        $this->adminNav('Announcements', '/admin/announcements', 'bell', 'announcements.manage', 41);
        $this->studentNav('Announcements', '/announcements', 'bell', 31);
        $c = 'App\\Modules\\Announcements\\Controller@';
        Hooks::on('app.routes', function ($r) use ($c) {
            $r->get('/announcements', $c . 'index', ['auth']);
            $r->group('/admin/announcements', ['auth', 'staff', 'can:announcements.manage'], function ($r) use ($c) {
                $r->get('', $c . 'adminIndex');
                $r->get('/create', $c . 'create');
                $r->post('', $c . 'store');
                $r->get('/{id}/edit', $c . 'edit');
                $r->post('/{id}', $c . 'update');
                $r->post('/{id}/delete', $c . 'destroy');});});
        // Scheduled announcements: in-app notifications once their publish time passes (deduplicated).
        Hooks::on('cron.run', function () {
            foreach (\App\Core\DB::all("SELECT * FROM {announcements} WHERE status = 'published' AND published_at <= ? AND published_at > ?", [now(), gmdate('Y-m-d H:i:s', time() - 86400)]) as $a) {
                Announcements::notify($a, false);}});
        Hooks::on('dashboard.side', function (array $items, array $user) {
            $list = Announcements::forUser((int)$user['id'], 3);
            if ($list) {
                $items[] = View::render('announcements/_card', ['list' => $list, 'heading' => 'Announcements']);}
            return $items;}, 5);
        Hooks::on('course.side', function (array $items, array $course, $enr, $user) {
            if ($user) {
                $list = Announcements::forUser((int)$user['id'], 3, (int)$course['id']);
                if ($list) {
                    $items[] = View::render('announcements/_card', ['list' => $list, 'heading' => 'Course updates']);}}
            return $items;});}}
