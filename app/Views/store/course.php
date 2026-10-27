<?php
$layout = 'public';
$title = $course['title'];
$highlights = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)$course['highlights']))));
$cur = $price['currency'];
?>
<section class="sales-hero tint-<?= e(course_tint($course)) ?>">
<div class="sales-copy">
<?php if (setting('store_enabled')): ?><a class="back-link" href="<?= e(url('/store')) ?>"><?= icon('arrow-left') ?>All courses</a><?php endif; ?>
<?php if ($course['category']): ?><span class="pill pill-dark"><?= e($course['category']) ?></span><?php endif; ?>
<h1><?= e($course['title']) ?></h1>
<?php if ($course['subtitle']): ?><p class="lead"><?= e($course['subtitle']) ?></p><?php endif; ?>
<div class="meta-row">
<span><?= icon('list') ?><?= (int)$lessonCount ?> lessons</span>
<?php if ($totalSecs): ?><span><?= icon('clock') ?><?= e(human_duration($totalSecs)) ?> total</span><?php endif; ?>
<?php if ($course['access_days']): ?><span><?= icon('calendar') ?><?= (int)$course['access_days'] ?>-day access</span><?php else: ?><span><?= icon('calendar') ?>Lifetime access</span><?php endif; ?>
<?php if ($students > 5): ?><span><?= icon('users') ?><?= number_format($students) ?> learners</span><?php endif; ?>
</div>
<?php if ($course['instructor_name']): ?><p class="small">By <b><?= e($course['instructor_name']) ?></b></p><?php endif; ?>
</div>
<div class="sales-art"><?php if ($course['cover_image']): ?><img class="sales-cover" src="<?= e(upload_url($course['cover_image'])) ?>" alt=""><?php else: ?><img src="<?= e(course_illus($course)) ?>" alt=""><?php endif; ?></div>
</section>

<div class="sales-layout">
<div class="stack-lg">
<?php if ($highlights): ?>
<div class="card"><h2 class="h3">What you'll get</h2><ul class="ticks"><?php foreach ($highlights as $h): ?><li><?= icon('check') ?><span><?= e($h) ?></span></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<?php if (trim(strip_tags((string)$course['description'])) !== ''): ?><div class="card prose"><h2 class="h3">About this course</h2><?= \App\Core\Html::clean($course['description']) ?></div><?php endif; ?>
<div class="card">
<h2 class="h3">Course content</h2>
<p class="muted small"><?= count(array_filter($modules, fn($m) => $m['lessons'])) ?> modules · <?= (int)$lessonCount ?> lessons · a new lesson unlocks on its scheduled day</p>
<div class="outline">
<?php foreach ($modules as $i => $m): if (!$m['lessons']) continue; ?>
<details <?= $i === 0 ? 'open' : '' ?>><summary><b><?= e($m['title']) ?></b><span class="muted small"><?= count($m['lessons']) ?> lessons</span><?= icon('chevron-down', 'chev') ?></summary>
<ul><?php foreach ($m['lessons'] as $l): $day = \App\Services\Access::releaseDay($l); ?><li><?= icon($l['type'] === 'video' ? 'play' : 'file') ?><span class="grow"><?= e($l['title']) ?></span><?php if ($day > 1): ?><span class="pill">Day <?= $day ?></span><?php endif; ?></li><?php endforeach; ?></ul></details>
<?php endforeach; ?>
</div>
</div>
</div>
<aside class="buy-box">
<div class="card buy-card">
<?= course_visual($course, 'buy-cv') ?>
<div class="buy-body">
<?php if ($price['free']): ?><div class="price"><b>Free</b></div>
<?php else: ?><div class="price"><b><?= e(money($price['final'], $cur)) ?></b><?php if ($price['base'] > $price['final']): ?><s><?= e(money($price['base'], $cur)) ?></s><span class="badge badge-success"><?= (int)round(($price['base'] - $price['final']) / $price['base'] * 100) ?>% off</span><?php endif; ?></div><?php endif; ?>
<?php if ($active): ?><a class="btn btn-primary btn-lg btn-block" href="<?= e(url('/courses/' . $course['slug'])) ?>"><?= icon('play') ?>Go to course</a>
<?php else: ?><a class="btn btn-primary btn-lg btn-block" href="<?= e(url('/c/' . $course['slug'] . '/checkout')) ?>"><?= $price['free'] ? 'Get free access' : 'Enroll now' ?> <?= icon('arrow-right') ?></a><?php endif; ?>
<ul class="buy-points small">
<li><?= icon('shield') ?>Secure UPI payment</li>
<li><?= icon('video') ?>Watch on phone, tablet or laptop</li>
<li><?= icon('mail') ?>Instant account & login by email</li>
</ul>
<button type="button" class="btn btn-ghost btn-sm btn-block" data-copy="<?= e(url('/c/' . $course['slug'], [], true)) ?>"><?= icon('copy') ?>Copy course link</button>
</div>
</div>
</aside>
</div>
