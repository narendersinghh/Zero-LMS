<?php
namespace App\Modules\Quizzes;

use App\Core\DB;

class Service {
    public static function quizFor(int $lessonId): ?array {
        return DB::one('SELECT * FROM {quizzes} WHERE lesson_id = ?', [$lessonId]);}

    public static function questions(int $quizId): array {
        $rows = DB::all('SELECT * FROM {quiz_questions} WHERE quiz_id = ? ORDER BY sort, id', [$quizId]);
        foreach ($rows as &$r) {
            $r['options'] = json_decode((string)$r['options'], true) ?: [];}
        return $rows;}

    /** Grade submitted answers ([question_id => option_index]). */
    public static function grade(array $questions, array $answers): array {
        $score = 0;
        $detail = [];
        foreach ($questions as $q) {
            $a = isset($answers[$q['id']]) && $answers[$q['id']] !== '' ? (int)$answers[$q['id']] : null;
            $ok = $a !== null && $a === (int)$q['correct_index'];
            if ($ok) {
                $score++;}
            $detail[(int)$q['id']] = $a;}
        $total = count($questions);
        return ['score' => $score, 'total' => $total, 'percent' => $total ? (int)round($score / $total * 100) : 0, 'answers' => $detail];}}
