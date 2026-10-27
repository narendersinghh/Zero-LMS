<?php
$layout = 'admin';
$title = $course['title'];
$crumbs = ['Courses' => '/admin/courses'];
$actions = '<a class="btn btn-ghost btn-sm" href="' . e(url('/courses/' . $course['slug'])) . '" target="_blank" rel="noopener">' . icon('eye') . 'Preview</a><a class="btn btn-primary btn-sm" href="' . e(url('/admin/courses/' . $course['id'] . '/lessons/create')) . '">' . icon('plus') . 'Add lesson</a>';
$typeIcon = ['video' => 'video', 'text' => 'file', 'embed' => 'external'];
?>
<div class="subnav"><a href="<?= e(url('/admin/courses/' . $course['id'] . '/edit')) ?>">Details & schedule</a><a class="active" href="<?= e(url('/admin/courses/' . $course['id'] . '/curriculum')) ?>">Curriculum</a><a href="<?= e(url('/admin/enrollments', ['course' => $course['id']])) ?>">Enrollments</a><a href="<?= e(url('/admin/reports/courses/' . $course['id'])) ?>">Report</a><?php foreach (\App\Core\Hooks::filter('admin.course_tabs', [], $course) as $tl => $tu): ?><a href="<?= e(url($tu)) ?>"><?= e($tl) ?></a><?php endforeach; ?></div>
<div class="row between wrap gap-3 mb-4">
<p class="muted small m-0"><?= icon('info') ?> Drag lessons and modules to reorder. Lessons unlock on their <b>day</b> (counted from each student's start date), on a <b>fixed date</b>, or immediately. A module's day applies to lessons without their own.</p>
<span class="badge badge-<?= ['published' => 'success', 'draft' => 'muted', 'archived' => 'warning'][$course['status']] ?>"><?= e(ucfirst($course['status'])) ?> · <?= (int)$lessonCount ?> lessons</span>
</div>
<div class="curriculum" data-curriculum data-reorder="<?= e(url('/admin/courses/' . $course['id'] . '/reorder')) ?>">
<?php if (!$modules): ?><?php partial('empty', ['icon' => 'layers', 'heading' => 'No content yet', 'text' => 'Start with a module (e.g. "Week 1" or "Day 1"), then add lessons to it.']); ?><?php endif; ?>
<?php foreach ($modules as $m): ?>
<section class="cmod" data-module="<?= (int)$m['id'] ?>" <?= $m['id'] ? 'draggable="true"' : '' ?>>
<header class="cmod-head">
<?php if ($m['id']): ?><span class="drag" data-drag-module title="Drag to reorder"><?= icon('drag') ?></span><?php endif; ?>
<div class="grow"><h3><?= e($m['title']) ?></h3><small class="muted"><?= count($m['lessons']) ?> lessons<?= $m['release_day'] ? ' · unlocks Day ' . (int)$m['release_day'] : '' ?><?= !empty($m['description']) ? ' · ' . e(str_limit($m['description'], 80)) : '' ?></small></div>
<a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/courses/' . $course['id'] . '/lessons/create', ['module' => $m['id']])) ?>"><?= icon('plus') ?>Lesson</a>
<?php if ($m['id']): ?>
<button type="button" class="btn-icon" data-modal-open="mod-<?= (int)$m['id'] ?>" aria-label="Edit module"><?= icon('edit') ?></button>
<dialog class="modal" id="mod-<?= (int)$m['id'] ?>"><form method="post" action="<?= e(url('/admin/modules/' . $m['id'])) ?>" class="stack"><?= csrf_field() ?>
<div class="modal-head"><h3>Edit module</h3><button type="button" class="btn-icon" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
<div class="field"><label>Title</label><input name="title" value="<?= e($m['title']) ?>" required></div>
<div class="field"><label>Description</label><textarea name="description" rows="2"><?= e($m['description']) ?></textarea></div>
<div class="field"><label>Unlock day (optional)</label><input type="number" min="1" name="release_day" value="<?= e($m['release_day']) ?>" placeholder="Immediately"><small class="hint">Lessons in this module without their own schedule unlock on this day.</small></div>
<div class="modal-foot"><button class="btn btn-primary">Save module</button></div></form>
<form method="post" action="<?= e(url('/admin/modules/' . $m['id'] . '/delete')) ?>" class="modal-danger"><?= csrf_field() ?><select name="lessons"><option value="keep">Keep lessons (move to unassigned)</option><option value="delete">Delete lessons too</option></select><button class="btn btn-danger btn-sm" data-confirm="Delete this module?"><?= icon('trash') ?>Delete module</button></form></dialog>
<?php endif; ?>
</header>
<ol class="clessons" data-lessons>
<?php foreach ($m['lessons'] as $l): $day = $l['release_day'] ?: null; ?>
<li class="clesson" draggable="true" data-lesson="<?= (int)$l['id'] ?>">
<span class="drag" title="Drag to reorder"><?= icon('drag') ?></span>
<span class="ltype"><?= icon($typeIcon[$l['type']] ?? 'file') ?></span>
<a class="grow" href="<?= e(url('/admin/lessons/' . $l['id'] . '/edit')) ?>"><b><?= e($l['title']) ?></b>
<small class="muted"><?php if ($l['release_at']): ?><?= icon('calendar') ?> <?= e(dtime($l['release_at'])) ?><?php elseif ($day): ?>Day <?= (int)$day ?><?php elseif ($m['release_day']): ?>Day <?= (int)$m['release_day'] ?> (module)<?php else: ?>Immediately<?php endif; ?><?= ($l['duration'] ?: $l['video_duration']) ? ' · ' . e(human_duration($l['duration'] ?: $l['video_duration'])) : '' ?><?= $l['video_provider'] ? ' · ' . e($l['video_provider']) : '' ?></small></a>
<?php if ($l['status'] === 'draft'): ?><span class="badge badge-muted">Draft</span><?php endif; ?>
<div class="row gap-1">
<a class="btn-icon" href="<?= e(url('/courses/' . $course['slug'] . '/lessons/' . $l['id'])) ?>" target="_blank" rel="noopener" title="Preview"><?= icon('eye') ?></a>
<a class="btn-icon" href="<?= e(url('/admin/lessons/' . $l['id'] . '/edit')) ?>" title="Edit"><?= icon('edit') ?></a>
<form method="post" action="<?= e(url('/admin/lessons/' . $l['id'] . '/duplicate')) ?>"><?= csrf_field() ?><button class="btn-icon" title="Duplicate"><?= icon('copy') ?></button></form>
<form method="post" action="<?= e(url('/admin/lessons/' . $l['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="btn-icon danger" title="Delete" data-confirm="Delete this lesson and its student progress?"><?= icon('trash') ?></button></form>
</div>
</li>
<?php endforeach; ?>
</ol>
<?php if (!$m['lessons']): ?><p class="cempty muted small">Drop lessons here</p><?php endif; ?>
</section>
<?php endforeach; ?>
</div>
<div class="card mt-4">
<form method="post" action="<?= e(url('/admin/courses/' . $course['id'] . '/modules')) ?>" class="row gap-2 wrap end-items">
<?= csrf_field() ?>
<div class="field grow"><label>New module</label><input name="title" placeholder="e.g. Week 1: Foundations" required></div>
<div class="field w-160"><label>Unlock day</label><input type="number" min="1" name="release_day" placeholder="Optional"></div>
<button class="btn btn-primary"><?= icon('plus') ?>Add module</button>
</form>
</div>
<div class="save-indicator" data-save-indicator hidden><?= icon('check') ?> Order saved</div>
