<?php $layout = 'app'; $title = 'Notifications'; ?>
<div class="page-head"><div><h1>Notifications</h1><p class="muted">Lesson unlocks, access updates and achievements.</p></div>
<?php if ($items): ?><form method="post" action="<?= e(url('/notifications/read-all')) ?>"><?= csrf_field() ?><button class="btn btn-ghost"><?= icon('check') ?>Mark all as read</button></form><?php endif; ?></div>
<?php if (!$items): ?><?php partial('empty', ['illus' => 'empty', 'heading' => 'No notifications yet', 'text' => 'We will let you know when new lessons unlock.']); ?>
<?php else: ?>
<div class="card list-card">
<?php foreach ($items as $n): ?>
<a class="list-row<?= $n['read_at'] ? '' : ' unread' ?>" href="<?= e(url('/notifications/' . $n['id'] . '/open')) ?>">
<span class="list-ico"><?= icon(['lesson' => 'play', 'enrollment' => 'book', 'access' => 'clock', 'achievement' => 'trophy'][$n['type']] ?? 'bell') ?></span>
<span class="grow"><b><?= e($n['title']) ?></b><small class="muted"><?= e($n['body']) ?></small></span>
<small class="muted nowrap"><?= e(time_ago($n['created_at'])) ?></small>
</a>
<?php endforeach; ?>
</div>
<?= $pg->links() ?>
<?php endif; ?>
