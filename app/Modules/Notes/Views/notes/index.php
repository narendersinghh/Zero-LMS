<?php $layout = 'app'; $title = 'My notes'; ?>
<div class="page-head"><div><h1>My notes</h1><p class="muted">Everything you've written while learning, grouped by course.</p></div>
<?php if ($groups || $q !== ''): ?><a class="btn btn-ghost" href="<?= e(url('/notes/export')) ?>"><?= icon('download') ?>Download .txt</a><?php endif; ?></div>
<?php if ($groups || $q !== ''): ?><form class="toolbar" method="get"><div class="search grow"><?= icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search your notes…" aria-label="Search notes"></div></form><?php endif; ?>
<?php if (!$groups): ?>
<?php partial('empty', ['icon' => 'edit', 'illus' => 'empty', 'heading' => $q !== '' ? 'No notes match your search' : 'No notes yet', 'text' => $q !== '' ? 'Try a different word.' : 'Open any lesson and use the notes box below the video — it saves as you type.']); ?>
<?php else: ?>
<div class="stack-lg">
<?php foreach ($groups as $courseTitle => $notes): ?>
<section class="card">
<div class="card-head"><h3><?= icon('book') ?> <?= e($courseTitle) ?></h3><span class="pill"><?= count($notes) ?></span></div>
<div class="stack">
<?php foreach ($notes as $n): ?>
<article class="note-item">
<div class="row between gap-2 wrap"><a href="<?= e(url('/courses/' . $n['course_slug'] . '/lessons/' . $n['lesson_id'] . '#notes')) ?>"><b><?= e($n['lesson_title']) ?></b></a><small class="muted"><?= e(time_ago($n['updated_at'])) ?></small></div>
<p class="note-body m-0"><?= nl2br(e(str_limit($n['body'], 600))) ?></p>
</article>
<?php endforeach; ?>
</div>
</section>
<?php endforeach; ?>
</div>
<?php endif; ?>
