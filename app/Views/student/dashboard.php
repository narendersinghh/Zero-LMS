<?php
$layout = 'app';
$title = 'Dashboard';
$hour = (int)(new DateTime('now', site_tz()))->format('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$first = explode(' ', trim($user['name']))[0];
?>
<section class="hero">
<div class="hero-main">
<span class="eyebrow"><?= icon('sparkles') ?> <?= e((new DateTime('now', site_tz()))->format('l, F j')) ?></span>
<h1><?= e($greet) ?>, <?= e($first) ?>.</h1>
<p class="muted lead"><?= $stats['courses'] ? 'Keep the momentum going — every lesson moves you forward.' : 'Your learning space is ready. Courses you are given access to will appear here.' ?></p>
<?php if ($continue): ?>
<a class="continue" href="<?= e(url('/courses/' . $continue['course']['slug'] . '/lessons/' . $continue['lesson_id'])) ?>">
<span class="continue-play"><?= icon('play') ?></span>
<span class="continue-text"><small>Continue learning · <?= e($continue['course']['title']) ?></small><b><?= e($continue['title']) ?></b><?php if ($continue['percent']): ?><span class="progress progress-thin"><i style="width:<?= (int)$continue['percent'] ?>%"></i></span><?php endif; ?></span>
<?= icon('arrow-right', 'continue-arrow') ?>
</a>
<?php endif; ?>
</div>
<?php if ($quote): ?>
<figure class="quote-card">
<?= icon('quote', 'quote-ico') ?>
<blockquote><?= e($quote['text']) ?></blockquote>
<?php if ($quote['author']): ?><figcaption>— <?= e($quote['author']) ?></figcaption><?php endif; ?>
</figure>
<?php endif; ?>
</section>

<section class="stats">
<div class="stat"><span class="stat-ico"><?= icon('book') ?></span><div><b><?= (int)$stats['courses'] ?></b><small>Enrolled courses</small></div></div>
<div class="stat"><span class="stat-ico"><?= icon('check-circle') ?></span><div><b><?= (int)$stats['completed_lessons'] ?></b><small>Lessons completed</small></div></div>
<div class="stat"><span class="stat-ico stat-flame"><?= icon('flame') ?></span><div><b><?= (int)$stats['streak'] ?> <?= $stats['streak'] === 1 ? 'day' : 'days' ?></b><small>Learning streak</small></div></div>
<div class="stat"><span class="stat-ico"><?= icon('trophy') ?></span><div><b><?= (int)$stats['finished'] ?></b><small>Courses finished</small></div></div>
</section>

<?php foreach ($expiring as $x): ?>
<div class="alert alert-warning"><?= icon('clock') ?><span>Your access to <b><?= e($x['title']) ?></b> ends on <b><?= e(dt($x['expires_at'])) ?></b> (<?= e(time_until(strtotime($x['expires_at'] . ' UTC'))) ?> left).</span><a class="btn btn-sm btn-ghost" href="<?= e(url('/courses/' . $x['slug'])) ?>">Continue</a></div>
<?php endforeach; ?>

<div class="dash-grid">
<div class="dash-main">
<div class="section-head"><h2>My courses</h2><?php if (count($enrollments) > 3): ?><a href="<?= e(url('/courses')) ?>" class="small">View all <?= icon('arrow-right') ?></a><?php endif; ?></div>
<?php if (!$enrollments): ?>
<?php partial('empty', ['icon' => 'book', 'heading' => 'No courses yet', 'text' => 'When you are enrolled in a course it will show up here. Check your email for access details.']); ?>
<?php else: ?>
<div class="course-grid">
<?php foreach (array_slice($enrollments, 0, 6) as $c): $o = $overview[$c['id']] ?? ['percent' => 0, 'completed' => 0, 'total' => 0]; partial('course_card', ['c' => $c, 'p' => $o]); endforeach; ?>
</div>
<?php endif; ?>

<?php if ($recent): ?>
<div class="section-head mt-5"><h2>Recently viewed</h2></div>
<div class="card list-card">
<?php foreach ($recent as $r): ?>
<a class="list-row" href="<?= e(url('/courses/' . $r['slug'] . '/lessons/' . $r['lesson_id'])) ?>">
<span class="list-ico<?= $r['completed_at'] ? ' done' : '' ?>"><?= icon($r['completed_at'] ? 'check' : 'play') ?></span>
<span class="grow"><b><?= e($r['title']) ?></b><small class="muted"><?= e($r['course_title']) ?> · <?= e(time_ago($r['last_viewed_at'])) ?></small></span>
<?php if (!$r['completed_at'] && $r['percent']): ?><span class="pill"><?= (int)$r['percent'] ?>%</span><?php endif; ?>
<?= icon('chevron-right', 'muted') ?>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php foreach ($widgets as $w): ?><div class="mt-5"><?= $w ?></div><?php endforeach; ?>
</div>

<aside class="dash-side">
<div class="card">
<div class="card-head"><h3><?= icon('calendar') ?> Upcoming lessons</h3></div>
<?php if (!$upcoming): ?><p class="muted small">No scheduled lessons right now. You're all caught up!</p><?php endif; ?>
<ul class="timeline">
<?php foreach ($upcoming as $l): ?>
<li><span class="tl-dot"></span><div><b><?= e($l['title']) ?></b><small class="muted"><?= e($l['course']['title']) ?></small><small class="unlock"><?= icon('lock') ?> <?= e(dtime($l['unlock_at'])) ?> · <span data-countdown="<?= (int)$l['unlock_at'] ?>"><?= e(time_until($l['unlock_at'])) ?></span></small></div></li>
<?php endforeach; ?>
</ul>
</div>
<div class="card mt-4">
<div class="card-head"><h3><?= icon('bell') ?> Notifications</h3><a href="<?= e(url('/notifications')) ?>" class="small">All</a></div>
<?php if (!$notifications): ?><p class="muted small">Nothing new.</p><?php endif; ?>
<?php foreach ($notifications as $n): ?>
<a class="mini-notif<?= $n['read_at'] ? '' : ' unread' ?>" href="<?= e(url('/notifications/' . $n['id'] . '/open')) ?>"><b><?= e($n['title']) ?></b><small class="muted"><?= e($n['body']) ?> · <?= e(time_ago($n['created_at'])) ?></small></a>
<?php endforeach; ?>
</div>
</aside>
</div>
