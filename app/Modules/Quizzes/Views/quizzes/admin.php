<?php
$layout = 'admin';
$title = 'Quiz · ' . $lesson['title'];
$crumbs = ['Courses' => '/admin/courses', $lesson['course_title'] => '/admin/courses/' . $lesson['course_id'] . '/curriculum', 'Lesson' => '/admin/lessons/' . $lesson['id'] . '/edit'];
$actions = '<a class="btn btn-ghost btn-sm" href="' . e(url('/courses/' . $lesson['course_slug'] . '/lessons/' . $lesson['id'] . '#quiz')) . '" target="_blank" rel="noopener">' . icon('eye') . 'Preview</a>';
$optRows = function (array $opts, int $correct, string $prefix) {
    $out = '';
    for ($i = 0; $i < 6; $i++) {
        $v = $opts[$i] ?? '';
        $out .= '<div class="quiz-edit-opt"><label class="quiz-correct" title="Correct answer"><input type="radio" name="correct" value="' . $i . '"' . ($i === $correct && $v !== '' ? ' checked' : '') . ' required><span class="sr">Correct</span></label><input name="options[]" value="' . e($v) . '" placeholder="Option ' . ($i + 1) . ($i < 2 ? '' : ' (optional)') . '"' . ($i < 2 ? ' required' : '') . ' maxlength="500" aria-label="Option ' . ($i + 1) . '"></div>';}
    return $out;};
?>
<div class="form-layout">
<div class="stack-lg">
<?php if (!$questions): ?><?php partial('empty', ['icon' => 'target', 'heading' => 'No questions yet', 'text' => 'Add your first multiple-choice question below. Mark the correct answer with the circle on its left.']); ?><?php endif; ?>
<?php foreach ($questions as $n => $q): ?>
<form method="post" action="<?= e(url('/admin/quiz-questions/' . $q['id'])) ?>" class="card stack" id="q-<?= (int)$q['id'] ?>">
<?= csrf_field() ?>
<div class="card-head"><h3>Question <?= $n + 1 ?></h3><div class="row gap-1">
<button class="btn-icon" formaction="<?= e(url('/admin/quiz-questions/' . $q['id'] . '/move')) ?>" name="dir" value="up" formnovalidate title="Move up"<?= $n === 0 ? ' disabled' : '' ?>><?= icon('chevron-up') ?></button>
<button class="btn-icon" formaction="<?= e(url('/admin/quiz-questions/' . $q['id'] . '/move')) ?>" name="dir" value="down" formnovalidate title="Move down"<?= $n === count($questions) - 1 ? ' disabled' : '' ?>><?= icon('chevron-down') ?></button>
<button class="btn-icon danger" formaction="<?= e(url('/admin/quiz-questions/' . $q['id'] . '/delete')) ?>" formnovalidate title="Delete" data-confirm="Delete this question?"><?= icon('trash') ?></button></div></div>
<div class="field"><label>Question</label><textarea name="question" rows="2" required maxlength="2000"><?= e($q['question']) ?></textarea></div>
<div class="field"><label>Answers <small class="muted">— select the correct one</small></label><?= $optRows($q['options'], (int)$q['correct_index'], 'q' . $q['id']) ?></div>
<div class="field"><label>Explanation <span class="muted">(shown after submitting)</span></label><textarea name="explanation" rows="2" maxlength="2000"><?= e($q['explanation']) ?></textarea></div>
<div><button class="btn btn-ghost btn-sm"><?= icon('check') ?>Save question</button></div>
</form>
<?php endforeach; ?>
<form method="post" action="<?= e(url('/admin/lessons/' . $lesson['id'] . '/quiz/questions')) ?>" class="card stack tint-yellow" id="new-question">
<?= csrf_field() ?>
<div class="card-head"><h3><?= icon('plus') ?> New question</h3></div>
<div class="field"><label>Question</label><textarea name="question" rows="2" required maxlength="2000" placeholder="e.g. What is the first step of the habit loop?"></textarea></div>
<div class="field"><label>Answers <small class="muted">— select the correct one</small></label><?= $optRows([], 0, 'new') ?></div>
<div class="field"><label>Explanation <span class="muted">(optional)</span></label><textarea name="explanation" rows="2" maxlength="2000"></textarea></div>
<div><button class="btn btn-primary"><?= icon('plus') ?>Add question</button></div>
</form>
</div>
<aside class="stack-lg">
<form method="post" action="<?= e(url('/admin/lessons/' . $lesson['id'] . '/quiz')) ?>" class="card stack">
<?= csrf_field() ?>
<div class="card-head"><h3>Quiz settings</h3></div>
<div class="field"><label>Title</label><input name="title" value="<?= e($quiz['title'] ?? 'Quick check') ?>" required maxlength="200"></div>
<div class="grid-2"><div class="field"><label>Pass mark (%)</label><input type="number" name="pass_percent" min="1" max="100" value="<?= (int)($quiz['pass_percent'] ?? 70) ?>" required></div>
<div class="field"><label>Max attempts</label><input type="number" name="max_attempts" min="0" value="<?= e($quiz['max_attempts'] ?? '') ?>" placeholder="Unlimited"></div></div>
<label class="check"><input type="checkbox" name="require_to_complete" value="1"<?= checked($quiz['require_to_complete'] ?? 0) ?>><span>Passing marks the lesson complete</span></label>
<label class="check"><input type="checkbox" name="shuffle" value="1"<?= checked($quiz['shuffle'] ?? 0) ?>><span>Shuffle question order</span></label>
<button class="btn btn-primary btn-block"><?= icon('check') ?>Save settings</button>
</form>
<?php if ($quiz): ?>
<div class="card small">
<div class="card-head"><h3>Results</h3></div>
<dl class="dl"><dt>Attempts</dt><dd><?= (int)$stats['attempts'] ?></dd><dt>Learners</dt><dd><?= (int)$stats['learners'] ?></dd><dt>Average score</dt><dd><?= (int)$stats['avg_pct'] ?>%</dd><dt>Passes</dt><dd><?= (int)$stats['passes'] ?></dd></dl>
</div>
<form method="post" action="<?= e(url('/admin/lessons/' . $lesson['id'] . '/quiz/delete')) ?>" class="card danger-zone"><?= csrf_field() ?><div><h3>Delete quiz</h3><p class="small muted">Removes all questions and attempts.</p></div><button class="btn btn-danger btn-sm" data-confirm="Delete this quiz and all attempts?"><?= icon('trash') ?>Delete</button></form>
<?php endif; ?>
</aside>
</div>
