<?php
$layout = auth() ? 'app' : 'public';
$title = 'Explore courses';
?>
<section class="explore-hero tint-purple">
<div><h1>Explore courses</h1><p class="lead">Practical lessons, one day at a time. Pick a course and start today.</p>
<form class="search search-lg" method="get"><?= icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search courses…" aria-label="Search courses"></form></div>
<?= illus('explore', 'explore-illus') ?>
</section>
<?php if (!$courses): ?><?php partial('empty', ['illus' => 'empty', 'heading' => $q ? 'No courses match your search' : 'New courses are coming soon', 'text' => 'Check back shortly.']); ?>
<?php else: ?>
<div class="shop-grid">
<?php foreach ($courses as $c): $p = \App\Services\Payments\Payments::price($c); $owned = isset($mine[$c['id']]); ?>
<a class="shop-card" href="<?= e(url($owned ? '/courses/' . $c['slug'] : '/c/' . $c['slug'])) ?>">
<?= course_visual($c, 'shop-cv') ?>
<div class="shop-body">
<div class="row between gap-2"><?php if ($c['category']): ?><span class="pill"><?= e($c['category']) ?></span><?php endif; ?><span class="small muted"><?= icon('list') ?> <?= (int)$c['lesson_count'] ?> lessons</span></div>
<h3><?= e($c['title']) ?></h3>
<?php if ($c['instructor_name']): ?><p class="small muted m-0">by <?= e($c['instructor_name']) ?></p><?php endif; ?>
<div class="shop-foot"><?php if ($owned): ?><span class="badge badge-success"><?= icon('check') ?>Enrolled</span><b>Open →</b><?php else: ?><span class="price-sm"><?php if ($p['base'] > $p['final']): ?><s><?= e(money($p['base'], $p['currency'])) ?></s><?php endif; ?></span><b class="price-tag"><?= $p['free'] ? 'Free' : e(money($p['final'], $p['currency'])) ?></b><?php endif; ?></div>
</div>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>
