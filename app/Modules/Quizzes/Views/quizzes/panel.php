<?php
$best = $attempts ? max(array_map(fn($a) => (int)$a['percent'], $attempts)) : null;
$passedAny = (bool)array_filter($attempts, fn($a) => (int)$a['passed'] === 1);
$last = $attempts[0] ?? null;
$left = $quiz['max_attempts'] ? max(0, (int)$quiz['max_attempts'] - count($attempts)) : null;
$canTake = $left === null || $left > 0 || can('courses.preview');
$showForm = $canTake && (!$last || $retake);
$lastAnswers = $last ? (json_decode((string)$last['answers'], true) ?: []) : [];
$action = url('/quizzes/' . $quiz['id'] . '/submit');
$self = url('/courses/' . $course['slug'] . '/lessons/' . $lesson['id']);
?>
<section class="card quiz-card mt-4" id="quiz">
<div class="card-head">
<div><span class="eyebrow"><?= icon('target') ?> Quiz</span><h3 class="mt-1"><?= e($quiz['title']) ?></h3>
<small class="muted"><?= count($questions) ?> question<?= count($questions) == 1 ? '' : 's' ?> · pass at <?= (int)$quiz['pass_percent'] ?>%<?= $left !== null ? ' · ' . $left . ' attempt' . ($left == 1 ? '' : 's') . ' left' : '' ?><?= $quiz['require_to_complete'] ? ' · pass to complete this lesson' : '' ?></small></div>
<?php if ($best !== null): ?><span class="quiz-best<?= $passedAny ? ' ok' : '' ?>"><b><?= $best ?>%</b><small>best</small></span><?php endif; ?>
</div>

<?php if ($last && !$showForm): ?>
<div class="quiz-result <?= $last['passed'] ? 'pass' : 'fail' ?>">
<span class="quiz-result-ico"><?= icon($last['passed'] ? 'trophy' : 'refresh') ?></span>
<div class="grow"><b><?= $last['passed'] ? 'Passed!' : 'Not quite yet' ?></b><small class="d-block">Last attempt: <?= (int)$last['score'] ?>/<?= (int)$last['total'] ?> correct (<?= (int)$last['percent'] ?>%) · <?= e(time_ago($last['created_at'])) ?></small></div>
<?php if ($canTake): ?><a class="btn btn-sm <?= $last['passed'] ? 'btn-ghost' : 'btn-primary' ?>" href="<?= e($self . '?retake=1#quiz') ?>"><?= icon('refresh') ?>Retake</a><?php endif; ?>
</div>
<details class="quiz-review"<?= $review ? ' open' : '' ?>><summary>Review answers</summary>
<ol class="quiz-review-list">
<?php foreach ($questions as $q): $a = $lastAnswers[$q['id']] ?? null; $ok = $a !== null && (int)$a === (int)$q['correct_index']; ?>
<li class="<?= $ok ? 'ok' : 'bad' ?>"><b><?= e($q['question']) ?></b>
<?php foreach ($q['options'] as $i => $o): ?><div class="qr-opt<?= $i === (int)$q['correct_index'] ? ' correct' : '' ?><?= $a !== null && (int)$a === $i && !$ok ? ' wrong' : '' ?>"><?= icon($i === (int)$q['correct_index'] ? 'check' : ($a !== null && (int)$a === $i ? 'x' : 'chevron-right')) ?><span><?= e($o) ?></span></div><?php endforeach; ?>
<?php if ($q['explanation']): ?><p class="small muted m-0 mt-1"><?= e($q['explanation']) ?></p><?php endif; ?>
</li>
<?php endforeach; ?>
</ol></details>
<?php elseif ($showForm): ?>
<form method="post" action="<?= e($action) ?>" class="quiz-form" data-quiz>
<?= csrf_field() ?>
<div class="quiz-progress" hidden><span class="progress"><i data-quiz-bar></i></span><small class="muted" data-quiz-count></small></div>
<?php foreach ($questions as $n => $q): ?>
<fieldset class="quiz-q" data-quiz-step>
<legend><span class="quiz-n"><?= $n + 1 ?></span><?= e($q['question']) ?></legend>
<div class="quiz-opts">
<?php foreach ($q['options'] as $i => $o): ?><label class="quiz-opt"><input type="radio" name="answers[<?= (int)$q['id'] ?>]" value="<?= (int)$i ?>" required><span><?= e($o) ?></span></label><?php endforeach; ?>
</div>
</fieldset>
<?php endforeach; ?>
<div class="quiz-nav">
<button type="button" class="btn btn-ghost" data-quiz-prev hidden><?= icon('arrow-left') ?>Back</button>
<button type="button" class="btn btn-primary" data-quiz-next hidden>Next <?= icon('arrow-right') ?></button>
<button class="btn btn-primary" data-quiz-submit><?= icon('check-circle') ?>Submit answers</button>
</div>
</form>
<?php else: ?>
<p class="muted small m-0">You have used all attempts for this quiz.</p>
<?php endif; ?>
</section>
