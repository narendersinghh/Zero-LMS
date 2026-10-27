<div class="card mt-4">
<div class="card-head"><h3><?= icon('bell') ?> <?= e($heading) ?></h3><a class="small" href="<?= e(url('/announcements')) ?>">All</a></div>
<?php foreach ($list as $a): ?>
<a class="mini-notif" href="<?= e(url('/announcements')) ?>#a<?= (int)$a['id'] ?>"><b><?= $a['pinned'] ? icon('target') . ' ' : '' ?><?= e($a['title']) ?></b><small class="muted"><?= e(str_limit(strip_tags((string)$a['body']), 90)) ?> · <?= e(time_ago($a['published_at'])) ?></small></a>
<?php endforeach; ?>
</div>
