<?php
$layout = 'app';
$title = $lesson['title'];
$bodyClass = 'is-lesson';
$scripts = ['dist/player.min.js'];
$done = !empty($lp['completed_at']);
$wm = setting('video_watermark', true) ? $user['email'] . ' · #' . $user['id'] : '';
$lessonUrl = fn($l) => url('/courses/' . $course['slug'] . '/lessons/' . $l['id']);
$embedUrl = $lesson['type'] === 'embed' ? (string)($settings['embed_url'] ?? '') : '';
?>
<?php if ($preview): ?><div class="alert alert-info"><?= icon('eye') ?><span>Admin preview<?= $lesson['status'] !== 'published' ? ' · this lesson is a <b>draft</b>' : '' ?>.</span><a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/lessons/' . $lesson['id'] . '/edit')) ?>">Edit lesson</a></div><?php endif; ?>
<div class="lesson-layout">
<div class="lesson-main">
<nav class="crumbs mb-3"><a href="<?= e(url('/courses/' . $course['slug'])) ?>"><?= icon('arrow-left') ?><?= e($course['title']) ?></a></nav>
<?php if ($video && !$embed && $playback): ?>
<div class="player" data-player data-src="<?= e($playback) ?>" data-progress="<?= e(url('/lessons/' . $lesson['id'] . '/progress')) ?>" data-resume="<?= (int)($lp['position'] ?? 0) ?>" data-wm="<?= e($wm) ?>" data-done="<?= $done ? 1 : 0 ?>" data-mime="<?= e($video['mime'] ?: 'video/mp4') ?>">
<video playsinline preload="metadata" controlslist="nodownload noremoteplayback noplaybackrate" disablepictureinpicture disableremoteplayback></video>
<div class="player-shield"></div>
<?php if ($wm): ?><div class="watermark" aria-hidden="true"><?= e($wm) ?></div><?php endif; ?>
<button type="button" class="player-big" data-p="play" aria-label="Play"><?= icon('play') ?></button>
<div class="player-bar">
<button type="button" class="pbtn" data-p="toggle" aria-label="Play/Pause"><?= icon('play', 'i-play') ?><?= icon('pause', 'i-pause') ?></button>
<button type="button" class="pbtn hide-xs" data-p="back" aria-label="Back 10 seconds"><?= icon('rewind') ?></button>
<button type="button" class="pbtn hide-xs" data-p="fwd" aria-label="Forward 10 seconds"><?= icon('forward') ?></button>
<span class="ptime" data-p="time">0:00 / 0:00</span>
<div class="pseek" data-p="seek" role="slider" aria-label="Seek" tabindex="0"><i class="pbuf"></i><i class="pfill"></i><i class="pknob"></i></div>
<button type="button" class="pbtn pspeed" data-p="speed" aria-label="Playback speed">1×</button>
<button type="button" class="pbtn" data-p="mute" aria-label="Mute"><?= icon('volume', 'i-vol') ?><?= icon('mute', 'i-mute') ?></button>
<button type="button" class="pbtn" data-p="fs" aria-label="Fullscreen"><?= icon('maximize') ?></button>
</div>
</div>
<?php elseif (($video && $embed && $playback) || $embedUrl): ?>
<div class="player player-embed" data-embed data-progress="<?= e(url('/lessons/' . $lesson['id'] . '/progress')) ?>">
<iframe src="<?= e($embedUrl ?: $playback) ?>" title="<?= e($lesson['title']) ?>" loading="lazy" allow="autoplay; fullscreen; encrypted-media; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" sandbox="allow-scripts allow-same-origin allow-presentation allow-forms"></iframe>
<?php if ($wm): ?><div class="watermark" aria-hidden="true"><?= e($wm) ?></div><?php endif; ?>
</div>
<?php endif; ?>

<div class="lesson-head">
<div>
<span class="eyebrow"><?= e($lesson['module_title'] ?? '') ?><?= \App\Services\Access::releaseDay($lesson) > 1 ? ' · Day ' . \App\Services\Access::releaseDay($lesson) : '' ?></span>
<h1><?= e($lesson['title']) ?></h1>
<?php if ($lesson['summary']): ?><p class="muted"><?= e($lesson['summary']) ?></p><?php endif; ?>
</div>
<?php if ($done || !empty($settings['manual_complete']) || $lesson['type'] !== 'video' || !$settings): ?><button type="button" class="btn <?= $done ? 'btn-success' : 'btn-primary' ?>" data-complete="<?= e(url('/lessons/' . $lesson['id'] . '/complete')) ?>" data-done="<?= $done ? 1 : 0 ?>"><?= icon('check-circle') ?><span><?= $done ? 'Completed' : 'Mark as complete' ?></span></button><?php endif; ?>
</div>

<?php if (trim(strip_tags($content, '<img>')) !== ''): ?><article class="card prose"><?= $content ?></article><?php endif; ?>

<?php if ($quote && empty($settings['hide_quote'])): ?><figure class="quote-card quote-sm mt-4"><?= icon('quote', 'quote-ico') ?><blockquote><?= e($quote['text']) ?></blockquote><?php if ($quote['author']): ?><figcaption>— <?= e($quote['author']) ?></figcaption><?php endif; ?></figure><?php endif; ?>

<div class="lesson-nav">
<?php if ($prev): ?><a class="nav-card" href="<?= e($lessonUrl($prev)) ?>"><?= icon('arrow-left') ?><span><small class="muted">Previous</small><b><?= e(str_limit($prev['title'], 50)) ?></b></span></a><?php else: ?><span></span><?php endif; ?>
<?php if ($next): ?>
<?php if ($next['available']): ?><a class="nav-card nav-next" href="<?= e($lessonUrl($next)) ?>"><span><small class="muted">Next lesson</small><b><?= e(str_limit($next['title'], 50)) ?></b></span><?= icon('arrow-right') ?></a>
<?php else: ?><div class="nav-card nav-next is-locked"><span><small class="muted">Next lesson unlocks <?= e(dtime($next['unlock_at'])) ?></small><b><?= e(str_limit($next['title'], 50)) ?></b></span><?= icon('lock') ?></div><?php endif; ?>
<?php endif; ?>
</div>
</div>

<aside class="lesson-side">
<div class="card side-curriculum">
<div class="card-head"><div><h3><?= e($course['title']) ?></h3><small class="muted" data-course-progress><?= (int)$c['completed'] ?>/<?= (int)$c['total'] ?> complete</small></div><?php partial('ring', ['pct' => $c['percent'], 'size' => 44]); ?></div>
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
</aside>
</div>
