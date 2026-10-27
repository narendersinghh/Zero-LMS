<?php
use App\Services\Courses;
use App\Services\Payments\Payments;
$layout = 'admin';
$title = 'Courses & videos';
$section = 'courses';
$actions = '<a class="btn btn-primary btn-sm" href="' . e(url('/admin/courses/create')) . '">' . icon('plus') . 'New course</a>';
$statusCls = ['published' => 'success', 'draft' => 'muted', 'archived' => 'warning'];
include \App\Core\View::find('admin/courses/_tabs');
?>
<div class="toolbar">
<div class="tabs tabs-pill"><a class="tab<?= $status === '' ? ' active' : '' ?>" href="?">Current</a><?php foreach (Courses::STATUSES as $k => $v): ?><a class="tab<?= $status === $k ? ' active' : '' ?>" href="?status=<?= e($k) ?>"><?= e($v) ?> <span class="count"><?= (int)($counts[$k] ?? 0) ?></span></a><?php endforeach; ?></div>
<form class="search grow" method="get"><?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?><?= icon('search') ?><input name="q" value="<?= e($q) ?>" placeholder="Search courses…" type="search"></form>
</div>
<?php if (!$courses): ?>
<?php partial('empty', ['illus' => 'empty', 'heading' => $q ? 'No courses match your search' : 'Create your first course', 'text' => 'A course holds modules (weeks or days), lessons and their videos. Students see lessons unlock on schedule.', 'action' => '<a class="btn btn-primary" href="' . e(url('/admin/courses/create')) . '">' . icon('plus') . 'New course</a>']); ?>
<?php else: ?>
<div class="acard-grid">
<?php foreach ($courses as $c):
    $p = Payments::price($c);
    $rate = $c['enrolled'] ? (int)round($c['completed'] / $c['enrolled'] * 100) : 0;
    $share = url('/c/' . $c['slug'], [], true); ?>
<article class="acard">
<a class="acard-media" href="<?= e(url('/admin/courses/' . $c['id'] . '/curriculum')) ?>">
<?= course_visual($c, 'acard-cv') ?>
<span class="badge badge-<?= $statusCls[$c['status']] ?? 'muted' ?> acard-status"><?= e(Courses::STATUSES[$c['status']] ?? $c['status']) ?></span>
<span class="acard-price"><?= $c['price'] === null || (float)$c['price'] <= 0 ? 'Free' : e(money($p['final'], $p['currency'])) ?><?php if ($p['base'] > $p['final']): ?><s><?= e(money($p['base'], $p['currency'])) ?></s><?php endif; ?></span>
</a>
<div class="acard-body">
<?php if ($c['category']): ?><span class="eyebrow"><?= e($c['category']) ?></span><?php endif; ?>
<h3><a href="<?= e(url('/admin/courses/' . $c['id'] . '/curriculum')) ?>"><?= e($c['title']) ?></a></h3>
<div class="acard-meta"><span><?= icon('layers') ?><?= (int)$c['module_count'] ?> modules</span><span><?= icon('list') ?><?= (int)$c['lesson_count'] ?> lessons</span><span><?= icon('clock') ?><?= $c['access_days'] ? (int)$c['access_days'] . '-day access' : 'Lifetime' ?></span></div>
<div class="acard-stats">
<div><small>Students</small><b><?= number_format((int)$c['enrolled']) ?></b></div>
<div><small>Completion</small><b><?= $rate ?>%</b></div>
<div><small>Revenue</small><b><?= e(money($c['revenue'])) ?></b></div>
</div>
<div class="progress" title="<?= $rate ?>% of students completed"><i style="width:<?= $rate ?>%"></i></div>
</div>
<div class="acard-foot">
<a class="btn btn-dark btn-sm" href="<?= e(url('/admin/courses/' . $c['id'] . '/curriculum')) ?>"><?= icon('layers') ?>Curriculum</a>
<a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/courses/' . $c['id'] . '/edit')) ?>"><?= icon('edit') ?>Edit</a>
<span class="grow"></span>
<?php if ($c['is_public'] && $c['status'] === 'published'): ?><button type="button" class="btn-icon" data-copy="<?= e($share) ?>" title="Copy sales link"><?= icon('copy') ?></button><?php endif; ?>
<a class="btn-icon" href="<?= e(url('/courses/' . $c['slug'])) ?>" target="_blank" rel="noopener" title="Preview as student"><?= icon('eye') ?></a>
<div class="dropdown" data-dropdown><button type="button" class="btn-icon" data-dropdown-toggle aria-label="More actions"><?= icon('menu') ?></button>
<div class="dropdown-menu">
<a href="<?= e(url('/admin/reports/courses/' . $c['id'])) ?>"><?= icon('chart') ?>Progress report</a>
<a href="<?= e(url('/admin/enrollments', ['course' => $c['id']])) ?>"><?= icon('users') ?>Enrollments</a>
<?php if ($c['is_public']): ?><a href="<?= e($share) ?>" target="_blank" rel="noopener"><?= icon('external') ?>Open sales page</a><?php endif; ?>
<form method="post" action="<?= e(url('/admin/courses/' . $c['id'] . '/duplicate')) ?>"><?= csrf_field() ?><button><?= icon('copy') ?>Duplicate</button></form>
<?php foreach (['published' => ['Publish', 'check-circle'], 'draft' => ['Move to draft', 'file'], 'archived' => ['Archive', 'archive']] as $st => $lbl): if ($st === $c['status']) continue; ?>
<form method="post" action="<?= e(url('/admin/courses/' . $c['id'] . '/status')) ?>"><?= csrf_field() ?><input type="hidden" name="status" value="<?= $st ?>"><button <?= $st === 'archived' ? 'data-confirm="Archive this course? Students will lose access until it is published again."' : '' ?>><?= icon($lbl[1]) ?><?= $lbl[0] ?></button></form>
<?php endforeach; ?>
</div></div>
</div>
</article>
<?php endforeach; ?>
<a class="acard acard-new" href="<?= e(url('/admin/courses/create')) ?>"><?= illus('explore') ?><b>Create a new course</b><small class="muted">Daily drip, cohort or self-paced</small></a>
</div>
<?= $pg->links() ?>
<?php endif; ?>
