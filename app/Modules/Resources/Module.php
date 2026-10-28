<?php
namespace App\Modules\Resources;

use App\Core\App;
use App\Core\DB;
use App\Core\Hooks;
use App\Core\Module as BaseModule;
use App\Core\View;

/** Downloadable lesson attachments and links, served only to students with access. */
class Module extends BaseModule {
    public function register(App $app): void {
        $c = Controller::class;
        Hooks::on('app.routes', function ($r) use ($c) {
            $staff = ['auth', 'staff', 'can:courses.manage'];
            $r->get('/admin/lessons/{id}/resources', $c . '@index', $staff);
            $r->post('/admin/lessons/{id}/resources', $c . '@store', $staff);
            $r->post('/admin/resources/{id}/delete', $c . '@destroy', $staff);
            $r->post('/admin/resources/{id}/move', $c . '@move', $staff);
            $r->get('/resources/{id}/download', $c . '@download', ['auth']);});
        Hooks::on('admin.lesson_panels', function (array $html, array $lesson) {
            $n = (int)DB::val('SELECT COUNT(*) FROM {lesson_resources} WHERE lesson_id = ?', [$lesson['id']]);
            $html[] = '<div class="card mt-4"><div class="card-head"><div><h3>' . icon('download') . ' Resources</h3><small class="muted">' . ($n ? $n . ' file' . ($n === 1 ? '' : 's') . '/link' . ($n === 1 ? '' : 's') . ' attached' : 'Attach worksheets, slides, PDFs or links.') . '</small></div><a class="btn btn-ghost btn-sm" href="' . e(url('/admin/lessons/' . $lesson['id'] . '/resources')) . '">' . icon($n ? 'edit' : 'plus') . ($n ? 'Manage' : 'Add resources') . '</a></div></div>';
            return $html;}, 10);
        Hooks::on('lesson.panels', function (array $html, array $lesson) {
            $rows = DB::all('SELECT * FROM {lesson_resources} WHERE lesson_id = ? ORDER BY sort, id', [$lesson['id']]);
            if ($rows) {
                $html[] = View::render('resources/panel', ['rows' => $rows]);}
            return $html;}, 10);}}
