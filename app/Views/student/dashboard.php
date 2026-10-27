<?php
$layout = 'app';
$title = 'Dashboard';
$now = new DateTime('now', site_tz());
$hour = (int)$now->format('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$first = explode(' ', trim($user['name']))[0];
$overallPct = $stats['total_lessons'] ? (int)floor($stats['completed_lessons'] / $stats['total_lessons'] * 100) : 0;
$cwUrl = fn($x) => url('/courses/' . $x['course']['slug'] . '/lessons/' . $x['lesson_id']);
?>
<div class="dash">
<div class="dash-main">
<section class="banner">
<div>
<span class="eyebrow"><?= icon('sparkles') ?> <?= e($now->format('l, F j')) ?></span>
<h1><?= e($greet) ?>, <?= e($first) ?> 👋</h1>
<p class="lead"><?= $stats['courses'] ? ($continue ? 'Pick up right where you left off. A few minutes today keeps your streak alive.' : 'You\'re all caught up. New lessons unlock on schedule.') : 'Your learning space is ready. Explore courses to get started.' ?></p>
<?php if ($continue): ?>
<a class="continue" href="<?= e(url('/courses/' . $continue['course']['slug'] . '/lessons/' . $continue['lesson_id'])) ?>">
<span class="continue-play"><?= icon('play') ?></span>
<span class="continue-text"><small>Continue · <?= e($continue['course']['title']) ?></small><b><?= e($continue['title']) ?></b><?php if ($continue['percent']): ?><span class="progress progress-thin"><i style="width:<?= (int)$continue['percent'] ?>%"></i></span><?php endif; ?></span>
<?= icon('arrow-right', 'continue-arrow') ?>
</a>
<?php elseif (!$stats['courses']): ?><a class="btn btn-primary btn-lg" href="<?= e(url(setting('store_enabled') ? '/store' : '/courses')) ?>"><?= icon('search') ?>Explore courses</a><?php endif; ?>
</div>
<div class="banner-art"><?= illus('hero-learner') ?></div>
</section>

<section class="tiles">
<div class="tile tint-yellow"><span class="tile-ico"><?= icon('book') ?></span><small>My courses</small><b><?= (int)$stats['courses'] ?><span> · <?= (int)$stats['finished'] ?> done</span></b></div>
<div class="tile tint-green"><span class="tile-ico"><?= icon('check-circle') ?></span><small>Lessons completed</small><b><?= (int)$stats['completed_lessons'] ?><span>/<?= (int)$stats['total_lessons'] ?></span></b></div>
<div class="tile tint-orange"><span class="tile-ico"><?= icon('flame') ?></span><small>Learning streak</small><b><?= (int)$stats['streak'] ?><span> day<?= $stats['streak'] === 1 ? '' : 's' ?></span></b></div>
<div class="tile tint-purple tile-ring"><?php partial('ring', ['pct' => $overallPct, 'size' => 64]); ?><div><small>Overall progress</small><b style="font-size:1rem;display:block"><?= $overallPct >= 100 ? 'All done!' : 'Keep going' ?></b></div></div>
</section>

<?php foreach ($expiring as $x): ?>
<div class="alert alert-warning m-0"><?= icon('clock') ?><span>Access to <b><?= e($x['title']) ?></b> ends <b><?= e(dt($x['expires_at'])) ?></b> (<?= e(time_until(strtotime($x['expires_at'] . ' UTC'))) ?> left).</span><a class="btn btn-sm btn-ghost" href="<?= e(url('/courses/' . $x['slug'])) ?>">Continue</a></div>
<?php endforeach; ?>

<?php if ($cw): ?>
<section>
<div class="section-head"><h2>Continue watching</h2><a href="<?= e(url('/courses')) ?>">View all</a></div>
<div class="cw-grid">
<?php foreach ($cw as $x): $cc = $x['course']; ?>
<a class="cw" href="<?= e($cwUrl($x)) ?>">
<div class="cv<?= $cc['cover_image'] ? '' : ' cv-illus tint-' . e(course_tint($cc)) ?>"<?= $cc['cover_image'] ? ' style="background-image:url(\'' . e(upload_url($cc['cover_image'])) . '\')"' : '' ?>><?php if (!$cc['cover_image']): ?><img src="<?= e(course_illus($cc)) ?>" alt="" loading="lazy"><?php endif; ?><span class="cw-play"><?= icon('play') ?></span><?php if ($x['percent']): ?><span class="cw-bar"><i style="width:<?= (int)$x['percent'] ?>%"></i></span><?php endif; ?></div>
<div class="cw-body"><small><?= e($cc['title']) ?></small><b><?= e($x['title']) ?></b></div>
</a>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>

<section>
<div class="section-head"><h2>My courses</h2><?php if (count($enrollments) > 4): ?><a href="<?= e(url('/courses')) ?>">View all</a><?php else: ?><a href="<?= e(url(setting('store_enabled') ? '/store' : '/courses')) ?>">Explore more</a><?php endif; ?></div>
<?php if (!$enrollments): ?>
<?php partial('empty', ['illus' => 'explore', 'heading' => 'No courses yet', 'text' => 'Browse the catalog or use the course link you received to enroll.', 'action' => '<a class="btn btn-primary" href="' . e(url(setting('store_enabled') ? '/store' : '/courses')) . '">' . icon('search') . 'Explore courses</a>']); ?>
<?php else: ?>
<div class="course-grid"><?php foreach (array_slice($enrollments, 0, 4) as $c): partial('course_card', ['c' => $c, 'p' => $overview[$c['id']] ?? []]); endforeach; ?></div>
<?php endif; ?>
</section>
<div class="grid-2 dash-pair">
<div class="card">
<div class="card-head"><h3>Achievements</h3><span class="muted small"><?= count(array_filter($achievements, fn($a) => $a[4] >= $a[5])) ?>/<?= count($achievements) ?> unlocked</span></div>
<?php foreach ($achievements as [$name, $desc, $ic, $tint, $have, $need]): $ok = $have >= $need; ?>
<div class="ach" title="<?= e($desc) ?>"><span class="ach-badge<?= $ok ? '' : ' locked' ?>" style="background:var(--p-<?= e($tint) ?>)"><?= icon($ic) ?></span><div class="grow"><b><?= e($name) ?><span><?= (int)$have ?>/<?= (int)$need ?></span></b><small class="muted"><?= e($desc) ?></small><div class="progress"><i style="width:<?= round($have / $need * 100) ?>%"></i></div></div></div>
<?php endforeach; ?>
</div>
<div class="card">
<div class="card-head"><h3>Learning statistics</h3></div>
<div class="stat-list">
<div class="stat-li"><?= icon('clock') ?>Hours learned<b><?= e((string)$stats['hours']) ?></b></div>
<div class="stat-li"><?= icon('calendar') ?>Active days<b><?= (int)$stats['active_days'] ?></b></div>
<div class="stat-li"><?= icon('check-circle') ?>Lessons completed<b><?= (int)$stats['completed_lessons'] ?></b></div>
<div class="stat-li"><?= icon('trophy') ?>Courses completed<b><?= (int)$stats['finished'] ?></b></div>
</div>
</div>
</div>
<?php foreach ($widgets as $w): ?><?= $w ?><?php endforeach; ?>
</div>

<aside class="dash-side">
<div class="card">
<div class="card-head"><h3>Upcoming</h3><span class="muted small"><?= e($now->format('F')) ?></span></div>
<div class="days"><?php foreach ($days as $d): ?><div class="day<?= $d['today'] ? ' today' : '' ?><?= $d['count'] ? ' has' : '' ?>"><span><?= e($d['dow']) ?></span><b><?= e($d['day']) ?></b></div><?php endforeach; ?></div>
<?php if (!$upcoming): ?><p class="muted small m-0">No new lessons scheduled. You're all caught up! 🎉</p><?php endif; ?>
<div class="up-list">
<?php foreach ($upcoming as $l): ?>
<a class="up" href="<?= e(url('/courses/' . $l['course']['slug'])) ?>"><span class="up-ico" style="background:var(--p-<?= e(course_tint($l['course'])) ?>)"><?= icon('lock') ?></span><span class="grow"><b><?= e($l['title']) ?></b><small><?= e(dtime($l['unlock_at'])) ?> · <span data-countdown="<?= (int)$l['unlock_at'] ?>"><?= e(time_until($l['unlock_at'])) ?></span></small></span></a>
<?php endforeach; ?>
</div>
</div>
<?php if ($quote): ?><figure class="quote-card"><?= icon('quote', 'quote-ico') ?><blockquote><?= e($quote['text']) ?></blockquote><?php if ($quote['author']): ?><figcaption><?= e($quote['author']) ?></figcaption><?php endif; ?></figure><?php endif; ?>
<?= hook_html('dashboard.side', $user) ?>
</aside>
</div>
