<div class="card zx-mod-card">
<div class="card-head"><div><h3><?= icon('target') ?> Quiz</h3><small class="muted"><?= $quiz ? (int)$count . ' question' . ($count == 1 ? '' : 's') . ' · pass at ' . (int)$quiz['pass_percent'] . '%' . ($quiz['require_to_complete'] ? ' · required to complete' : '') : 'Check understanding with a short multiple-choice quiz.' ?></small></div>
<a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/lessons/' . $lesson['id'] . '/quiz')) ?>"><?= icon($quiz ? 'edit' : 'plus') ?><?= $quiz ? 'Manage quiz' : 'Add quiz' ?></a></div>
</div>
