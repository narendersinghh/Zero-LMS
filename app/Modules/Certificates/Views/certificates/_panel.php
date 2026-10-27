<div class="card mt-4">
<div class="card-head"><h3><?= icon('trophy') ?> Certificates</h3><a class="small" href="<?= e(url('/certificates')) ?>">All</a></div>
<?php if (!$certs): ?><p class="muted small m-0">Complete a course to earn your first certificate.</p><?php endif; ?>
<?php foreach ($certs as $ct): ?>
<a class="list-row" href="<?= e(url('/certificates/' . $ct['code'])) ?>"><span class="list-ico done"><?= icon('trophy') ?></span><span class="grow"><b><?= e($ct['course_title']) ?></b><small class="muted"><?= e(dt($ct['issued_at'])) ?> · <?= e($ct['code']) ?></small></span><?= icon('chevron-right', 'muted') ?></a>
<?php endforeach; ?>
</div>
