<?php $layout = 'app'; $title = 'Announcements'; ?>
<div class="page-head"><div><h1>Announcements</h1><p class="muted">Updates from your instructors.</p></div></div>
<?php if (!$list): ?>
<?php partial('empty', ['illus' => 'empty', 'icon' => 'bell', 'heading' => 'No announcements yet', 'text' => 'News and updates for your courses will show up here.']); ?>
<?php else: ?>
<div class="stack-lg" style="max-width:820px">
<?php foreach ($list as $a): ?>
<article class="card<?= $a['pinned'] ? ' tint-yellow' : '' ?>" id="a<?= (int)$a['id'] ?>">
<div class="row gap-2 wrap mb-3"><?php if ($a['pinned']): ?><span class="pill"><?= icon('target') ?> Pinned</span><?php endif; ?><span class="pill"><?= $a['course_title'] ? e($a['course_title']) : 'Everyone' ?></span><small class="muted"><?= e(dtime($a['published_at'])) ?></small></div>
<h2 class="m-0" style="font-size:1.2rem"><?= e($a['title']) ?></h2>
<?php if (trim(strip_tags((string)$a['body'], '<img>')) !== ''): ?><div class="prose mt-3"><?= \App\Core\Html::clean($a['body']) ?></div><?php endif; ?>
<?php if ($a['course_slug']): ?><a class="btn btn-ghost btn-sm mt-3" href="<?= e(url('/courses/' . $a['course_slug'])) ?>">Open course <?= icon('arrow-right') ?></a><?php endif; ?>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
