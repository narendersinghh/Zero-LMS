<?php
$layout = 'app';
$title = 'My courses';
$tabs = ['all' => 'All', 'progress' => 'In progress', 'completed' => 'Completed', 'expired' => 'Expired'];
$list = array_filter($enrollments, function ($c) use ($filter, $progress) {
    $p = $progress[$c['id']]['percent'] ?? 0;
    switch ($filter) {
        case 'progress': return $c['state'] === 'active' && empty($c['completed_at']);
        case 'completed': return !empty($c['completed_at']) || $p >= 100;
        case 'expired': return $c['state'] === 'expired';}
    return true;});
?>
<div class="page-head"><div><h1>My courses</h1><p class="muted">Everything you have access to, in one place.</p></div><a class="btn btn-ghost" href="<?= e(url('/explore')) ?>"><?= icon('search') ?>Explore more</a></div>
<div class="tabs tabs-pill mb-4"><?php foreach ($tabs as $k => $v): ?><a href="?filter=<?= e($k) ?>" class="tab<?= $filter === $k ? ' active' : '' ?>"><?= e($v) ?></a><?php endforeach; ?></div>
<?php if (!$list): ?>
<?php partial('empty', ['illus' => 'empty', 'heading' => 'No courses here', 'text' => $filter === 'all' ? 'You are not enrolled in any courses yet.' : 'Nothing matches this filter.']); ?>
<?php else: ?>
<div class="course-grid"><?php foreach ($list as $c): partial('course_card', ['c' => $c, 'p' => $progress[$c['id']] ?? []]); endforeach; ?></div>
<?php endif; ?>
