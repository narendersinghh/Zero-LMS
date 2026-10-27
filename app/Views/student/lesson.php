<?php
$layout = 'app';
$title = $lesson['title'];
$bodyClass = 'is-lesson';
$scripts = ['dist/player.min.js'];
$done = !empty($lp['completed_at']);
$wm = setting('video_watermark', true) ? $user['email'] . ' · #' . $user['id'] : '';
$lessonUrl = fn($l) => url('/courses/' . $course['slug'] . '/lessons/' . $l['id']);
$embedUrl = $lesson['type'] === 'embed' ? (string)($settings['embed_url'] ?? '') : '';
$canManual = \App\Core\Hooks::filter('lesson.manual_complete', $done || !empty($settings['manual_complete']) || $lesson['type'] !== 'video' || !$settings, $lesson, $user);
$day = \App\Services\Access::releaseDay($lesson);
?>
<?php if ($preview): ?><div class="alert alert-info"><?= icon('eye') ?><span>Admin preview<?= $lesson['status'] !== 'published' ? ' · this lesson is a <b>draft</b>' : '' ?>.</span><a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/lessons/' . $lesson['id'] . '/edit')) ?>">Edit lesson</a></div><?php endif; ?>
<div class="lesson-layout">
<div class="lesson-main">
<?php if ($video && !$embed && ($yt || $playback)): ?>
<?php partial('player', ['lesson' => $lesson, 'video' => $video, 'playback' => $playback, 'yt' => $yt, 'user' => $user, 'lp' => $lp, 'done' => $done]); ?>
<?php elseif (($video && $embed && $playback) || $embedUrl): ?>
<div class="player player-embed" data-embed>
<iframe src="<?= e($embedUrl ?: $playback) ?>" title="<?= e($lesson['title']) ?>" loading="lazy" allow="autoplay; fullscreen; encrypted-media; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" sandbox="allow-scripts allow-same-origin allow-presentation allow-forms"></iframe>
<?php if ($wm): ?><div class="watermark" aria-hidden="true"><?= e($wm) ?></div><?php endif; ?>
</div>
<?php endif; ?>
<nav class="crumbs"><a href="<?= e(url('/courses/' . $course['slug'])) ?>"><?= icon('arrow-left') ?><?= e($course['title']) ?></a></nav>
<div class="lesson-head">
<div>
<span class="eyebrow"><?= e($lesson['module_title'] ?? '') ?><?= $day > 1 ? ' · Day ' . $day : '' ?></span>
<h1><?= e($lesson['title']) ?></h1>
<?php if ($lesson['summary']): ?><p class="muted"><?= e($lesson['summary']) ?></p><?php endif; ?>
</div>
<?php if ($canManual): ?><button type="button" class="btn <?= $done ? 'btn-success' : 'btn-primary' ?>" data-complete="<?= e(url('/lessons/' . $lesson['id'] . '/complete')) ?>" data-done="<?= $done ? 1 : 0 ?>"><?= icon('check-circle') ?><span><?= $done ? 'Completed' : 'Mark as complete' ?></span></button><?php endif; ?>
</div>
<?php if (trim(strip_tags($content, '<img>')) !== ''): ?><article class="card prose"><?= $content ?></article><?php endif; ?>
<?= hook_html('lesson.panels', $lesson, $course, $user, $c['state'] ?? null) ?>
<?php if ($quote && empty($settings['hide_quote'])): ?><figure class="quote-card quote-sm"><?= icon('quote', 'quote-ico') ?><blockquote><?= e($quote['text']) ?></blockquote><?php if ($quote['author']): ?><figcaption>— <?= e($quote['author']) ?></figcaption><?php endif; ?></figure><?php endif; ?>
<div class="lesson-nav">
<?php if ($prev): ?><a class="nav-card" href="<?= e($lessonUrl($prev)) ?>"><?= icon('arrow-left') ?><span><small class="muted">Previous</small><b><?= e(str_limit($prev['title'], 50)) ?></b></span></a><?php else: ?><span></span><?php endif; ?>
<?php if ($next): ?>
<?php if ($next['available']): ?><a class="nav-card nav-next" href="<?= e($lessonUrl($next)) ?>"><span><small class="muted">Next lesson</small><b><?= e(str_limit($next['title'], 50)) ?></b></span><?= icon('arrow-right') ?></a>
<?php else: ?><div class="nav-card nav-next is-locked"><span><small class="muted">Unlocks <?= e(dtime($next['unlock_at'])) ?></small><b><?= e(str_limit($next['title'], 50)) ?></b></span><?= icon('lock') ?></div><?php endif; ?>
<?php endif; ?>
</div>
</div>
<aside class="lesson-side">
<div class="card side-curriculum">
<div class="card-head"><div><h3><?= e(str_limit($course['title'], 40)) ?></h3><small class="muted" data-course-progress><?= (int)$c['completed'] ?>/<?= (int)$c['total'] ?> complete</small></div><?php partial('ring', ['pct' => $c['percent'], 'size' => 46]); ?></div>
<div class="side-scroll">
<?php foreach ($c['modules'] as $m): if (!$m['lessons']) continue; ?>
<div class="side-module"><span class="side-mtitle"><?= e($m['title']) ?></span>
<?php foreach ($m['lessons'] as $l): $cur = (int)$l['id'] === (int)$lesson['id']; ?>
<?php if ($l['available']): ?><a class="side-lesson<?= $cur ? ' current' : '' ?><?= $l['completed'] ? ' done' : '' ?>" href="<?= e($lessonUrl($l)) ?>" <?= $cur ? 'aria-current="page"' : '' ?>><?php else: ?><span class="side-lesson locked"><?php endif; ?>
<span class="side-ico"><?= icon($l['completed'] ? 'check' : ($l['available'] ? ($cur ? 'play' : 'chevron-right') : 'lock')) ?></span><span class="grow"><?= e($l['title']) ?><?php if (!$l['available'] && $l['unlock_at'] < PHP_INT_MAX): ?><small class="muted"><?= e(dt($l['unlock_at'])) ?></small><?php endif; ?></span>
<?php if ($l['available']): ?></a><?php else: ?></span><?php endif; ?>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
</div>
</div>
<?= hook_html('lesson.side', $lesson, $course, $user) ?>
</aside>
</div>
