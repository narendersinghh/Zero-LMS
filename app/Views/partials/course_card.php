<?php
/** Pastel course card. @var array $c course+enrollment row, @var array $p progress {total, completed, percent} */
$pct = (int)($p['percent'] ?? 0);
$state = $c['state'] ?? 'active';
$done = !empty($c['completed_at']) || ($pct >= 100 && ($p['total'] ?? 0) > 0);
$label = $done ? 'Completed' : ['active' => $pct > 0 ? 'In progress' : 'New', 'pending' => 'Starts ' . dt(\App\Services\Access::effectiveStart($c['enrollment'], $c)), 'expired' => 'Access ended', 'suspended' => 'Paused'][$state] ?? '';
$cta = $state !== 'active' ? 'View' : ($done ? 'Review' : ($pct > 0 ? 'Continue' : 'Start'));
?>
<a class="ccard tint-<?= e(course_tint($c)) ?><?= $state !== 'active' ? ' is-' . e($state) : '' ?>" href="<?= e(url('/courses/' . $c['slug'])) ?>">
<div class="ccard-top">
<div>
<span class="pill"><?= $done ? icon('trophy') : '' ?><?= e($label) ?></span>
<h3><?= e($c['title']) ?></h3>
<?php if ($c['subtitle']): ?><p class="clamp-2"><?= e($c['subtitle']) ?></p><?php endif; ?>
<div class="ccard-meta"><span><?= icon('list') ?><?= (int)($p['total'] ?? 0) ?> lessons</span><?php if ($c['expires_at'] && $state === 'active'): ?><span><?= icon('clock') ?>Until <?= e(dt($c['expires_at'], 'M j')) ?></span><?php endif; ?></div>
</div>
<?php if ($c['cover_image']): ?><div class="ccard-art photo" style="background-image:url('<?= e(upload_url($c['cover_image'])) ?>')"></div><?php else: ?><div class="ccard-art"><img src="<?= e(course_illus($c)) ?>" alt="" loading="lazy"></div><?php endif; ?>
<div class="ccard-prog"><div class="row between"><span>Progress</span><span><?= $pct ?>%</span></div><div class="progress"><i style="width:<?= $pct ?>%"></i></div></div>
</div>
<div class="ccard-foot"><small>Lessons: <b><?= (int)($p['completed'] ?? 0) ?>/<?= (int)($p['total'] ?? 0) ?></b></small><span class="btn btn-dark btn-sm"><?= e($cta) ?></span></div>
</a>
