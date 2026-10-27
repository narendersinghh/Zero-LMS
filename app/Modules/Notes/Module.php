<?php
namespace App\Modules\Notes;

use App\Core\App;
use App\Core\DB;
use App\Core\Hooks;
use App\Core\Module as BaseModule;
use App\Core\View;

/** Private auto-saving notes per lesson. */
class Module extends BaseModule {
    public function register(App $app): void {
        $c = Controller::class;
        $this->studentNav('My notes', '/notes', 'edit', 40);
        Hooks::on('app.routes', function ($r) use ($c) {
            $r->get('/notes', $c . '@index', ['auth']);
            $r->get('/notes/export', $c . '@export', ['auth']);
            $r->post('/notes/{lessonId}', $c . '@save', ['auth']);});
        Hooks::on('head.assets', function (array $a) {
            if (is_path('/admin')) {
                return $a;}
            $a[] = ['css' => module_asset('Notes', 'notes.css')];
            $a[] = ['js' => module_asset('Notes', 'notes.js')];
            return $a;});
        Hooks::on('lesson.panels', function (array $html, array $lesson, array $course, array $user) {
            $note = DB::one('SELECT body, updated_at FROM {lesson_notes} WHERE user_id = ? AND lesson_id = ?', [$user['id'], $lesson['id']]);
            $html[] = View::render('notes/panel', ['lesson' => $lesson, 'note' => $note]);
            return $html;}, 30);}}
