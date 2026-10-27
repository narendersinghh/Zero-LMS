<?php
namespace App\Modules\Quizzes;

use App\Core\App;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Hooks;
use App\Core\Module as BaseModule;
use App\Core\View;

/** Per-lesson multiple-choice quizzes. */
class Module extends BaseModule {
    public function register(App $app): void {
        $c = Controller::class;
        Hooks::on('app.routes', function ($r) use ($c) {
            $staff = ['auth', 'staff', 'can:courses.manage'];
            $r->get('/admin/lessons/{id}/quiz', $c . '@edit', $staff);
            $r->post('/admin/lessons/{id}/quiz', $c . '@save', $staff);
            $r->post('/admin/lessons/{id}/quiz/delete', $c . '@destroy', $staff);
            $r->post('/admin/lessons/{id}/quiz/questions', $c . '@addQuestion', $staff);
            $r->post('/admin/quiz-questions/{id}', $c . '@updateQuestion', $staff);
            $r->post('/admin/quiz-questions/{id}/delete', $c . '@deleteQuestion', $staff);
            $r->post('/admin/quiz-questions/{id}/move', $c . '@moveQuestion', $staff);
            $r->post('/quizzes/{id}/submit', $c . '@submit', ['auth']);});
        Hooks::on('head.assets', function (array $a) {
            $a[] = ['css' => module_asset('Quizzes', 'quiz.css')];
            $a[] = ['js' => module_asset('Quizzes', 'quiz.js')];
            return $a;});
        Hooks::on('admin.lesson_panels', function (array $html, array $lesson) {
            $q = Service::quizFor((int)$lesson['id']);
            $n = $q ? (int)DB::val('SELECT COUNT(*) FROM {quiz_questions} WHERE quiz_id = ?', [$q['id']]) : 0;
            $html[] = View::render('quizzes/admin_panel', ['lesson' => $lesson, 'quiz' => $q, 'count' => $n]);
            return $html;}, 20);
        Hooks::on('lesson.panels', function (array $html, array $lesson, array $course, array $user) {
            $q = Service::quizFor((int)$lesson['id']);
            if (!$q) {
                return $html;}
            $questions = Service::questions((int)$q['id']);
            if (!$questions) {
                return $html;}
            $attempts = DB::all('SELECT * FROM {quiz_attempts} WHERE quiz_id = ? AND user_id = ? ORDER BY id DESC', [$q['id'], $user['id']]);
            if ($q['shuffle']) {
                mt_srand(crc32($user['id'] . '-' . $q['id'] . '-' . count($attempts)));
                shuffle($questions);
                mt_srand();}
            $html[] = View::render('quizzes/panel', ['quiz' => $q, 'questions' => $questions, 'attempts' => $attempts, 'lesson' => $lesson, 'course' => $course,
                'retake' => isset($_GET['retake']), 'review' => isset($_GET['quiz_result'])]);
            return $html;}, 20);
        // A quiz marked "required to complete" replaces the manual "Mark as complete" button until it is passed.
        $gate = function (bool $allowed, array $lesson, array $user) {
            $q = Service::quizFor((int)$lesson['id']);
            if (!$allowed || !$q || !(int)$q['require_to_complete'] || in_array($user['role'], ['super_admin', 'admin'], true)) {
                return $allowed;}
            return (bool)DB::val('SELECT 1 FROM {quiz_attempts} WHERE quiz_id = ? AND user_id = ? AND passed = 1 LIMIT 1', [$q['id'], $user['id']]);};
        Hooks::on('lesson.manual_complete', $gate);
        Hooks::on('lesson.can_complete', $gate);
        Hooks::on('dashboard.side', function (array $html, array $user) {
            $rows = DB::all('SELECT q.id, l.id AS lesson_id, l.title, c.slug, MAX(a.percent) AS best, MAX(a.passed) AS passed FROM {quiz_attempts} a JOIN {quizzes} q ON q.id = a.quiz_id JOIN {lessons} l ON l.id = q.lesson_id JOIN {courses} c ON c.id = l.course_id WHERE a.user_id = ? GROUP BY q.id, l.id, l.title, c.slug ORDER BY MAX(a.id) DESC LIMIT 5', [$user['id']]);
            if ($rows) {
                $html[] = View::render('quizzes/widget', ['rows' => $rows]);}
            return $html;}, 30);}}
