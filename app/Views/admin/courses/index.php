<?php
use App\Services\Courses;
$layout = 'admin';
$title = 'Courses';
$actions = '<a class="btn btn-primary btn-sm" href="' . e(url('/admin/courses/create')) . '">' . icon('plus') . 'New course</a>';
?>
<div class="toolbar">
<div class="tabs"><a class="tab<?= $status === '' ? ' active' : '' ?>" href="?">Current</a><?php foreach (Courses::STATUSES as $k => $v): ?><a class="tab<?= $status === $k ? ' active' : '' ?>" href="?status=<?= e($k) ?>"><?= e($v) ?> <span class="count"><?= (int)($counts[$k] ?? 0) ?></span></a><?php endforeach; ?></div>
<form class="search" method="get"><?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?><?= icon('search') ?><input name="q" value="<?= e($q) ?>" placeholder="Search courses…" type="search"></form>
</div>
<?php if (!$courses): ?>
<?php partial('empty', ['icon' => 'book', 'heading' => $q ? 'No courses match your search' : 'Create your first course', 'text' => 'Courses hold modules, daily lessons and videos.', 'action' => '<a class="btn btn-primary" href="' . e(url('/admin/courses/create')) . '">' . icon('plus') . 'New course</a>']); ?>
<?php else: ?>
<div class="admin-course-grid">
<?php foreach ($courses as $c): ?>
<div class="card admin-course">
<a class="course-cover sm" href="<?= e(url('/admin/courses/' . $c['id'] . '/curriculum')) ?>"<?= $c['cover_image'] ? ' style="background-image:url(\'' . e(upload_url($c['cover_image'])) . '\')"' : '' ?>><?php if (!$c['cover_image']): ?><span class="cover-fallback"><?= e(initials($c['title'])) ?></span><?php endif; ?><span class="badge cover-badge badge-<?= ['published' => 'success', 'draft' => 'muted', 'archived' => 'warning'][$c['status']] ?>"><?= e(Courses::STATUSES[$c['status']] ?? $c['status']) ?></span></a>
<div class="course-body">
<h3><a href="<?= e(url('/admin/courses/' . $c['id'] . '/curriculum')) ?>"><?= e($c['title']) ?></a></h3>
<div class="small muted row gap-3 wrap"><span><?= icon('list') ?> <?= (int)$c['lesson_count'] ?> lessons</span><span><?= icon('users') ?> <?= (int)$c['enrolled'] ?> enrolled</span><?php if ($c['access_days']): ?><span><?= icon('clock') ?> <?= (int)$c['access_days'] ?>-day access</span><?php endif; ?></div>
<div class="row gap-2 mt-3 wrap">
<a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/courses/' . $c['id'] . '/curriculum')) ?>"><?= icon('layers') ?>Curriculum</a>
<a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/courses/' . $c['id'] . '/edit')) ?>"><?= icon('edit') ?>Edit</a>
<div class="dropdown" data-dropdown><button type="button" class="btn btn-sm btn-ghost" data-dropdown-toggle aria-label="More"><?= icon('chevron-down') ?></button>
<div class="dropdown-menu">
<a href="<?= e(url('/courses/' . $c['slug'])) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>Preview as student</a>
<a href="<?= e(url('/admin/reports/courses/' . $c['id'])) ?>"><?= icon('chart') ?>Progress report</a>
<form method="post" action="<?= e(url('/admin/courses/' . $c['id'] . '/duplicate')) ?>"><?= csrf_field() ?><button><?= icon('copy') ?>Duplicate</button></form>
<?php foreach (['published' => ['Publish', 'check-circle'], 'draft' => ['Move to draft', 'file'], 'archived' => ['Archive', 'archive']] as $st => $lbl): if ($st === $c['status']) continue; ?>
<form method="post" action="<?= e(url('/admin/courses/' . $c['id'] . '/status')) ?>"><?= csrf_field() ?><input type="hidden" name="status" value="<?= $st ?>"><button <?= $st === 'archived' ? 'data-confirm="Archive this course? Students will lose access until it is published again."' : '' ?>><?= icon($lbl[1]) ?><?= $lbl[0] ?></button></form>
<?php endforeach; ?>
</div></div>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?= $pg->links() ?>
<?php endif; ?>
