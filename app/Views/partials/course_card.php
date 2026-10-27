<?php
/** @var array $c course+enrollment row, @var array $p progress */
$pct = (int)($p['percent'] ?? 0);
$state = $c['state'] ?? 'active';
$labels = ['active' => '', 'pending' => 'Starts ' . dt(\App\Services\Access::effectiveStart($c['enrollment'], $c)), 'expired' => 'Access expired', 'suspended' => 'Paused', 'unavailable' => 'Unavailable'];
?>
<a class="course-card<?= $state !== 'active' ? ' is-' . e($state) : '' ?>" href="<?= e(url('/courses/' . $c['slug'])) ?>">
<div class="course-cover"<?= $c['cover_image'] ? ' style="background-image:url(\'' . e(upload_url($c['cover_image'])) . '\')"' : '' ?>>
<?php if (!$c['cover_image']): ?><span class="cover-fallback"><?= e(initials($c['title'])) ?></span><?php endif; ?>
<?php if (!empty($c['completed_at'])): ?><span class="badge badge-success cover-badge"><?= icon('trophy') ?>Completed</span><?php elseif ($labels[$state] ?? ''): ?><span class="badge cover-badge"><?= e($labels[$state]) ?></span><?php endif; ?>
</div>
<div class="course-body">
<?php if ($c['category']): ?><span class="eyebrow"><?= e($c['category']) ?></span><?php endif; ?>
<h3><?= e($c['title']) ?></h3>
<?php if ($c['subtitle']): ?><p class="muted clamp-2"><?= e($c['subtitle']) ?></p><?php endif; ?>
<div class="course-meta">
<div class="progress" aria-label="<?= $pct ?>% complete"><i style="width:<?= $pct ?>%"></i></div>
<div class="row between small"><span><?= (int)($p['completed'] ?? 0) ?>/<?= (int)($p['total'] ?? 0) ?> lessons</span><b><?= $pct ?>%</b></div>
<?php if ($c['expires_at'] && $state === 'active'): ?><div class="small muted mt-1"><?= icon('clock') ?> Access until <?= e(dt($c['expires_at'])) ?></div><?php endif; ?>
</div>
</div>
</a>
