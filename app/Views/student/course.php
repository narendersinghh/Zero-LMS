<?php
$layout = 'app';
$title = $course['title'];
$state = $c['state'];
$stateMsg = ['pending' => ['clock', 'Your access starts on ' . dtime($c['start']) . '. See you then!'], 'expired' => ['lock', 'Your access to this course ended on ' . dt($enr['expires_at'] ?? null) . '. Contact support if you need more time.'], 'suspended' => ['alert', 'Your access to this course is paused. Please contact support.']][$state] ?? null;
?>
<?php if ($preview): ?><div class="alert alert-info"><?= icon('eye') ?><span>Admin preview — all lessons are unlocked for you.<?= $course['status'] !== 'published' ? ' This course is <b>' . e($course['status']) . '</b> and hidden from students.' : '' ?></span><a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/courses/' . $course['id'] . '/curriculum')) ?>">Edit course</a></div><?php endif; ?>
<section class="course-hero"<?= $course['cover_image'] ? ' style="--cover:url(\'' . e(upload_url($course['cover_image'])) . '\')"' : '' ?>>
<div class="course-hero-in">
<a href="<?= e(url('/courses')) ?>" class="back-link"><?= icon('arrow-left') ?>My courses</a>
<?php if ($course['category']): ?><span class="eyebrow"><?= e($course['category']) ?></span><?php endif; ?>
<h1><?= e($course['title']) ?></h1>
<?php if ($course['subtitle']): ?><p class="lead muted"><?= e($course['subtitle']) ?></p><?php endif; ?>
<div class="chips">
<span class="chip"><?= icon('list') ?><?= (int)$c['total'] ?> lessons</span>
<?php if ($course['instructor_name']): ?><span class="chip"><?= icon('user') ?><?= e($course['instructor_name']) ?></span><?php endif; ?>
<?php if ($enr && $state === 'active'): ?><span class="chip"><?= icon('calendar') ?>Day <?= (int)$c['current_day'] ?></span><?php endif; ?>
<?php if ($enr): ?><span class="chip"><?= icon('clock') ?><?= $enr['expires_at'] ? 'Access until ' . e(dt($enr['expires_at'])) : 'Lifetime access' ?></span><?php endif; ?>
</div>
<?php if ($c['next'] && $state === 'active'): ?>
<a class="btn btn-primary btn-lg mt-4" href="<?= e(url('/courses/' . $course['slug'] . '/lessons/' . $c['next']['id'])) ?>"><?= icon('play') ?><?= $c['completed'] ? 'Continue: ' : 'Start: ' ?><?= e(str_limit($c['next']['title'], 40)) ?></a>
<?php endif; ?>
</div>
<div class="course-hero-side">
<?php partial('ring', ['pct' => $c['percent'], 'size' => 120]); ?>
<div class="center"><b><?= (int)$c['completed'] ?> of <?= (int)$c['total'] ?></b><small class="muted d-block">lessons completed</small></div>
</div>
</section>

<?php if ($stateMsg): ?><div class="alert alert-warning"><?= icon($stateMsg[0]) ?><span><?= e($stateMsg[1]) ?></span></div><?php endif; ?>

<div class="course-layout">
<div>
<?php if (trim(strip_tags((string)$course['description'])) !== ''): ?><div class="card prose mb-4"><?= \App\Core\Html::clean($course['description']) ?></div><?php endif; ?>
<?php if (!$c['modules']): ?><?php partial('empty', ['icon' => 'video', 'heading' => 'Lessons coming soon', 'text' => 'Content for this course is being prepared.']); ?><?php endif; ?>
<div class="modules">
<?php foreach ($c['modules'] as $i => $m): if (!$m['lessons']) continue; ?>
<details class="module" <?= $i < 3 || $m['done'] < $m['count'] ? 'open' : '' ?>>
<summary><div><span class="eyebrow">Module <?= $i + 1 ?><?= $m['release_day'] ? ' · Day ' . (int)$m['release_day'] : '' ?></span><h3><?= e($m['title']) ?></h3><?php if (!empty($m['description'])): ?><small class="muted"><?= e($m['description']) ?></small><?php endif; ?></div><span class="module-count"><?= (int)$m['done'] ?>/<?= (int)$m['count'] ?></span><?= icon('chevron-down', 'chev') ?></summary>
<ol class="lessons">
<?php foreach ($m['lessons'] as $l): $href = url('/courses/' . $course['slug'] . '/lessons/' . $l['id']); $dur = (int)($l['duration'] ?: ($l['video_duration'] ?? 0)); ?>
<li class="lesson<?= $l['completed'] ? ' is-done' : ($l['available'] ? '' : ' is-locked') ?><?= ($c['next']['id'] ?? 0) === $l['id'] ? ' is-next' : '' ?>">
<?php if ($l['available']): ?><a href="<?= e($href) ?>"><?php else: ?><div><?php endif; ?>
<span class="lesson-ico"><?= icon($l['completed'] ? 'check' : ($l['available'] ? ($l['type'] === 'video' ? 'play' : 'file') : 'lock')) ?></span>
<span class="grow"><b><?= e($l['title']) ?></b>
<small class="muted"><?php if ($l['day'] > 1 || !empty($l['release_at'])): ?>Day <?= (int)$l['day'] ?> · <?php endif; ?><?= $dur ? e(human_duration($dur)) : e(ucfirst($l['type'])) ?><?php if (!$l['available'] && $l['unlock_at'] < PHP_INT_MAX && $state === 'active'): ?> · Unlocks <?= e(dtime($l['unlock_at'])) ?><?php endif; ?></small>
<?php if (!$l['completed'] && !empty($l['progress']['percent'])): ?><span class="progress progress-thin"><i style="width:<?= (int)$l['progress']['percent'] ?>%"></i></span><?php endif; ?>
</span>
<?php if (!$l['available'] && $l['unlock_at'] < PHP_INT_MAX && $l['unlock_at'] > time() && $state === 'active'): ?><span class="pill pill-muted" data-countdown="<?= (int)$l['unlock_at'] ?>"><?= e(time_until($l['unlock_at'])) ?></span><?php elseif ($l['completed']): ?><span class="pill pill-success">Done</span><?php elseif (($c['next']['id'] ?? 0) === $l['id']): ?><span class="pill pill-accent">Up next</span><?php endif; ?>
<?php if ($l['available']): ?></a><?php else: ?></div><?php endif; ?>
</li>
<?php endforeach; ?>
</ol>
</details>
<?php endforeach; ?>
</div>
</div>
<aside class="course-side">
<?php if ($quote): ?><figure class="quote-card quote-sm"><?= icon('quote', 'quote-ico') ?><blockquote><?= e($quote['text']) ?></blockquote><?php if ($quote['author']): ?><figcaption>— <?= e($quote['author']) ?></figcaption><?php endif; ?></figure><?php endif; ?>
<?php if ($c['upcoming'] && $state !== 'expired'): ?>
<div class="card mt-4"><div class="card-head"><h3><?= icon('calendar') ?> Coming up</h3></div>
<ul class="timeline"><?php foreach (array_slice($c['upcoming'], 0, 5) as $l): ?><li><span class="tl-dot"></span><div><b><?= e($l['title']) ?></b><small class="unlock"><?= icon('lock') ?> <?= e(dtime($l['unlock_at'])) ?></small></div></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<?php if ($enr): ?>
<div class="card mt-4 small"><div class="card-head"><h3><?= icon('info') ?> Your access</h3></div>
<dl class="dl"><dt>Started</dt><dd><?= e(dt($c['start'])) ?></dd><dt>Expires</dt><dd><?= $enr['expires_at'] ? e(dt($enr['expires_at'])) : 'Never' ?></dd><?php if ($enr['completed_at']): ?><dt>Completed</dt><dd><?= e(dt($enr['completed_at'])) ?></dd><?php endif; ?></dl></div>
<?php endif; ?>
</aside>
</div>
