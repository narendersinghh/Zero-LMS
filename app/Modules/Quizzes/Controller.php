<?php
namespace App\Modules\Quizzes;

use App\Controllers\Controller as Base;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Hooks;
use App\Services\Access;
use App\Services\Progress;

class Controller extends Base {
    private function lesson(string $id): array {
        return DB::one('SELECT l.*, c.title AS course_title, c.slug AS course_slug FROM {lessons} l JOIN {courses} c ON c.id = l.course_id WHERE l.id = ?', [(int)$id]) ?? abort(404);}

    public function edit(string $id) {
        $lesson = $this->lesson($id);
        $quiz = Service::quizFor((int)$lesson['id']);
        $stats = $quiz ? DB::one('SELECT COUNT(*) attempts, COUNT(DISTINCT user_id) learners, COALESCE(ROUND(AVG(percent)),0) avg_pct, COALESCE(SUM(passed),0) passes FROM {quiz_attempts} WHERE quiz_id = ?', [$quiz['id']]) : null;
        return view('quizzes/admin', ['lesson' => $lesson, 'quiz' => $quiz, 'questions' => $quiz ? Service::questions((int)$quiz['id']) : [], 'stats' => $stats]);}

    public function save(string $id) {
        $lesson = $this->lesson($id);
        $d = $this->validate(['title' => 'required|max:200', 'pass_percent' => 'required|int', 'max_attempts' => 'int']);
        $row = ['title' => $d['title'], 'pass_percent' => max(1, min(100, (int)$d['pass_percent'])), 'max_attempts' => (int)$d['max_attempts'] > 0 ? (int)$d['max_attempts'] : null,
            'require_to_complete' => $this->req->bool('require_to_complete') ? 1 : 0, 'shuffle' => $this->req->bool('shuffle') ? 1 : 0, 'updated_at' => now()];
        $quiz = Service::quizFor((int)$lesson['id']);
        if ($quiz) {
            DB::update('quizzes', $row, 'id = ?', [$quiz['id']]);
            $qid = (int)$quiz['id'];}
        else {
            $qid = DB::insert('quizzes', $row + ['lesson_id' => $lesson['id'], 'created_at' => now()]);}
        Audit::log($quiz ? 'quiz.updated' : 'quiz.created', 'quiz', $qid, ['lesson_id' => $lesson['id']]);
        $this->ok('Quiz settings saved.', '/admin/lessons/' . $lesson['id'] . '/quiz');}

    public function destroy(string $id) {
        $lesson = $this->lesson($id);
        $quiz = Service::quizFor((int)$lesson['id']) ?? abort(404);
        DB::delete('quizzes', 'id = ?', [$quiz['id']]);
        Audit::log('quiz.deleted', 'quiz', (int)$quiz['id'], ['lesson_id' => $lesson['id']]);
        $this->ok('Quiz deleted.', '/admin/lessons/' . $lesson['id'] . '/edit');}

    /** Validate question fields from the request. */
    private function questionData(): array {
        $d = $this->validate(['question' => 'required|max:2000', 'explanation' => 'max:2000']);
        $opts = array_values(array_filter(array_map(fn($o) => trim((string)$o), $this->req->arr('options')), fn($o) => $o !== ''));
        $opts = array_map(fn($o) => mb_substr($o, 0, 500), $opts);
        if (count($opts) < 2 || count($opts) > 6) {
            flash('error', 'Each question needs 2 to 6 answer options.');
            back();}
        // "correct" refers to the position among the submitted option inputs; remap after removing blanks.
        $raw = $this->req->arr('options');
        $ci = $this->req->int('correct', -1);
        if (!isset($raw[$ci]) || trim((string)$raw[$ci]) === '') {
            flash('error', 'Mark which answer is correct.');
            back();}
        $correct = array_search(mb_substr(trim((string)$raw[$ci]), 0, 500), $opts, true);
        return ['question' => $d['question'], 'options' => json_encode($opts, JSON_UNESCAPED_UNICODE), 'correct_index' => (int)$correct, 'explanation' => $d['explanation'] ?: null];}

    public function addQuestion(string $id) {
        $lesson = $this->lesson($id);
        $quiz = Service::quizFor((int)$lesson['id']);
        if (!$quiz) {
            $qid = DB::insert('quizzes', ['lesson_id' => $lesson['id'], 'title' => 'Quick check', 'created_at' => now(), 'updated_at' => now()]);
            $quiz = ['id' => $qid];}
        $row = $this->questionData();
        $row['quiz_id'] = $quiz['id'];
        $row['sort'] = (int)DB::val('SELECT COALESCE(MAX(sort), 0) + 1 FROM {quiz_questions} WHERE quiz_id = ?', [$quiz['id']]);
        $qq = DB::insert('quiz_questions', $row);
        Audit::log('quiz.question_added', 'quiz', (int)$quiz['id'], ['question_id' => $qq]);
        $this->ok('Question added.', '/admin/lessons/' . $lesson['id'] . '/quiz#q-' . $qq);}

    private function question(string $id): array {
        return DB::one('SELECT qq.*, q.lesson_id FROM {quiz_questions} qq JOIN {quizzes} q ON q.id = qq.quiz_id WHERE qq.id = ?', [(int)$id]) ?? abort(404);}

    public function updateQuestion(string $id) {
        $q = $this->question($id);
        DB::update('quiz_questions', $this->questionData(), 'id = ?', [$q['id']]);
        Audit::log('quiz.question_updated', 'quiz', (int)$q['quiz_id'], ['question_id' => $q['id']]);
        $this->ok('Question saved.', '/admin/lessons/' . $q['lesson_id'] . '/quiz#q-' . $q['id']);}

    public function deleteQuestion(string $id) {
        $q = $this->question($id);
        DB::delete('quiz_questions', 'id = ?', [$q['id']]);
        Audit::log('quiz.question_deleted', 'quiz', (int)$q['quiz_id'], ['question_id' => $q['id']]);
        $this->ok('Question deleted.', '/admin/lessons/' . $q['lesson_id'] . '/quiz');}

    public function moveQuestion(string $id) {
        $q = $this->question($id);
        $ids = DB::col('SELECT id FROM {quiz_questions} WHERE quiz_id = ? ORDER BY sort, id', [$q['quiz_id']]);
        $i = array_search((int)$q['id'], array_map('intval', $ids), true);
        $j = $this->req->str('dir') === 'up' ? $i - 1 : $i + 1;
        if ($i !== false && isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            foreach ($ids as $n => $qid) {
                DB::run('UPDATE {quiz_questions} SET sort = ? WHERE id = ?', [$n + 1, $qid]);}}
        redirect('/admin/lessons/' . $q['lesson_id'] . '/quiz#q-' . $q['id']);}

    /** Student submission: graded on the server. */
    public function submit(string $id) {
        $user = Auth::user();
        $quiz = DB::one('SELECT * FROM {quizzes} WHERE id = ?', [(int)$id]) ?? abort(404);
        $lesson = DB::one('SELECT l.*, m.release_day AS module_release_day FROM {lessons} l LEFT JOIN {modules} m ON m.id = l.module_id WHERE l.id = ?', [$quiz['lesson_id']]) ?? abort(404);
        $chk = Access::canViewLesson($user, $lesson);
        if (!$chk['ok']) {
            abort(403);}
        $back = '/courses/' . $chk['course']['slug'] . '/lessons/' . $lesson['id'];
        $used = (int)DB::val('SELECT COUNT(*) FROM {quiz_attempts} WHERE quiz_id = ? AND user_id = ?', [$quiz['id'], $user['id']]);
        if ($quiz['max_attempts'] && $used >= (int)$quiz['max_attempts'] && !Auth::isStaff()) {
            flash('error', 'You have used all attempts for this quiz.');
            redirect($back . '#quiz');}
        $questions = Service::questions((int)$quiz['id']);
        if (!$questions) {
            redirect($back);}
        $g = Service::grade($questions, $this->req->arr('answers'));
        $passed = $g['percent'] >= (int)$quiz['pass_percent'];
        DB::insert('quiz_attempts', ['quiz_id' => $quiz['id'], 'user_id' => $user['id'], 'score' => $g['score'], 'total' => $g['total'], 'percent' => $g['percent'], 'passed' => $passed ? 1 : 0, 'answers' => json_encode($g['answers']), 'created_at' => now()]);
        if ($passed) {
            if ($quiz['require_to_complete']) {
                Progress::complete((int)$user['id'], $lesson);}
            Hooks::fire('quiz.passed', (int)$user['id'], $quiz, $g);}
        flash($passed ? 'success' : 'warning', $passed ? 'Quiz passed with ' . $g['percent'] . '%, great job!' : 'You scored ' . $g['percent'] . '%. You need ' . (int)$quiz['pass_percent'] . '% to pass.');
        redirect($back . '?quiz_result=1#quiz');}}
