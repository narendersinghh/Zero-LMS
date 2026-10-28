<div class="card mt-4">
<div class="card-head"><h3><?= icon('target') ?> Quiz scores</h3></div>
<?php foreach ($rows as $r): ?>
<a class="list-row" href="<?= e(url('/courses/' . $r['slug'] . '/lessons/' . $r['lesson_id'] . '#quiz')) ?>"><span class="list-ico<?= $r['passed'] ? ' done' : '' ?>"><?= icon($r['passed'] ? 'check' : 'target') ?></span><span class="grow"><b><?= e($r['title']) ?></b><small class="muted">Best score <?= (int)$r['best'] ?>%</small></span></a>
<?php endforeach; ?>
</div>
