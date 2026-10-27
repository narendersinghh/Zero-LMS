<?php
namespace App\Modules\Example;

use App\Core\App;
use App\Core\Hooks;
use App\Core\Module as BaseModule;

/**
 * Reference module. Enable it in Admin → System & updates → Modules.
 * Modules can: register routes, add nav items, grant permissions, listen to events
 * (enrollment.created, lesson.completed, course.completed, cron.run …), filter values
 * (lesson.types, lesson.content, dashboard.widgets …), ship views and DB migrations (./migrations).
 */
class Module extends BaseModule {
    public function register(App $app): void {
        $this->permissions(['super_admin' => ['example.view'], 'admin' => ['example.view']]);
        $this->adminNav('Example', '/admin/example', 'sparkles', 'example.view');
        Hooks::on('app.routes', function ($r) {
            $r->get('/admin/example', fn() => view('example/index'), ['auth', 'staff', 'can:example.view']);});
        Hooks::on('dashboard.widgets', function (array $w, array $user) {
            $w[] = '<div class="card"><b>Example widget</b><p class="muted small m-0">Hello ' . e($user['name']) . ' — this card comes from app/Modules/Example.</p></div>';
            return $w;});
        Hooks::on('course.completed', function (int $userId, int $courseId) {
            // e.g. issue a certificate, send a webhook, award a badge…
        });}}
