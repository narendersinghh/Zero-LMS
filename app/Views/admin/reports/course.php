<?php
use App\Core\Charts;
use App\Services\Courses;
$layout = 'admin';
$title = $course['title'];
$crumbs = ['Reports' => '/admin/reports'];
$actions = '<a class="btn btn-ghost btn-sm" href="' . e(url('/admin/reports/courses/' . $course['id'] . '/export')) . '">' . icon('download') . 'Export CSV</a><a class="btn btn-ghost btn-sm" href="' . e(url('/admin/courses/' . $course['id'] . '/edit')) . '">' . icon('edit') . 'Edit course</a>';
$enrolled = (int)($f['enrolled'] ?? 0);
$rate = $enrolled ? round(($f['completed'] ?? 0) / $enrolled * 100) : 0;
$share = url('/c/' . $course['slug'], [], true);
$labels = array_values($keys);
$distLabels = ['a' => 'Not started', 'b' => '1–24%', 'c' => '25–49%', 'd' => '50–74%', 'e' => '75–99%', 'f' => 'Completed'];
$distMax = max(1, max(array_map('intval', array_values($dist ?: ['a' => 0]))));
$qs = fn(array $over) => '?' . http_build_query(array_filter(array_merge(['q' => $q, 'filter' => $filter, 'sort' => $sort], $over), fn($v) => $v !== '' && $v !== null));
$statusCls = ['published' => 'success', 'draft' => 'muted', 'archived' => 'warning'];
?>
<div class="rp-page">
<section class="card rp-hero">
<?= course_visual($course, 'rp-hero-cv') ?>
<div class="rp-hero-body">
<div class="row gap-2 wrap"><span class="badge badge-<?= $statusCls[$course['status']] ?? 'muted' ?>"><?= e(Courses::STATUSES[$course['status']] ?? $course['status']) ?></span><?php if ($course['category']): ?><span class="pill"><?= e($course['category']) ?></span><?php endif; ?><span class="pill"><?= (int)$total ?> lessons</span></div>
<h2><?= e($course['title']) ?></h2>
<div class="rp-hero-meta">
<span><small class="muted">Price</small><b><?= $price['free'] ? 'Free' : e(money($price['final'], $price['currency'])) ?></b><?php if (!$price['free'] && $price['base'] > $price['final']): ?> <s class="muted small"><?= e(money($price['base'], $price['currency'])) ?></s><?php endif; ?></span>
<span><small class="muted">Access</small><b><?= $course['access_days'] ? (int)$course['access_days'] . ' days' : 'Lifetime' ?></b></span>
<span><small class="muted">Created</small><b><?= e(dt($course['created_at'])) ?></b></span>
</div>
<?php if ((int)$course['is_public']): ?><div class="copy-box"><code><?= e($share) ?></code><button type="button" class="btn-icon" data-copy="<?= e($share) ?>" title="Copy share link"><?= icon('copy') ?></button></div><?php endif; ?>
</div>
</section>

<div class="rp-kpis">
<div class="rp-kpi"><span class="rp-kpi-ico" style="background:var(--p-yellow)"><?= icon('users') ?></span><small>Enrolled</small><b><?= number_format($enrolled) ?></b><span class="rp-kpi-note"><?= Charts::spark($series['enrolled']) ?: 'All time' ?></span></div>
<div class="rp-kpi"><span class="rp-kpi-ico" style="background:var(--p-blue)"><?= icon('activity') ?></span><small>Active last 7 days</small><b><?= number_format($active7) ?></b><span class="rp-kpi-note"><?= $enrolled ? round($active7 / $enrolled * 100) : 0 ?>% of students</span></div>
<div class="rp-kpi"><span class="rp-kpi-ico" style="background:var(--p-purple)"><?= icon('target') ?></span><small>Avg. progress</small><b><?= round($f['avg_progress'] ?? 0) ?>%</b><div class="progress progress-thin"><i style="width:<?= round($f['avg_progress'] ?? 0) ?>%"></i></div></div>
<div class="rp-kpi"><span class="rp-kpi-ico" style="background:var(--p-green)"><?= icon('trophy') ?></span><small>Completion rate</small><b><?= $rate ?>%</b><span class="rp-kpi-note"><?= (int)($f['completed'] ?? 0) ?> finished</span></div>
<div class="rp-kpi"><span class="rp-kpi-ico" style="background:var(--p-orange)"><?= icon('key') ?></span><small>Revenue</small><b><?= e(money($rev['revenue'] ?? 0)) ?></b><span class="rp-kpi-note"><?= (int)($rev['orders'] ?? 0) ?> paid orders</span></div>
</div>

<?php if (!$enrolled): ?>
<?php partial('empty', ['illus' => 'empty', 'heading' => 'No students in this course yet', 'text' => 'Share the course link or enroll students to see progress, engagement and drop-off here.', 'action' => (int)$course['is_public'] ? '<button type="button" class="btn btn-primary" data-copy="' . e($share) . '">' . icon('copy') . 'Copy course link</button>' : '<a class="btn btn-primary" href="' . e(url('/admin/students')) . '">' . icon('users') . 'Enroll students</a>']); ?>
<?php else: ?>

<div class="rp-grid-1-1">
<div class="card">
<div class="card-head"><div><h3>Completion funnel</h3><small class="muted">How far students get</small></div></div>
<?= Charts::funnel([['label' => 'Enrolled', 'value' => $enrolled], ['label' => 'Started', 'value' => $f['started'] ?? 0], ['label' => 'Halfway', 'value' => $f['halfway'] ?? 0], ['label' => 'Completed', 'value' => $f['completed'] ?? 0]]) ?>
</div>
<div class="card">
<div class="card-head"><div><h3>Progress distribution</h3><small class="muted">Students by share of lessons completed</small></div></div>
<div class="hbars rp-dist"><?php foreach ($distLabels as $k => $lbl): $n = (int)($dist[$k] ?? 0); ?><div class="hbar"><span><?= e($lbl) ?></span><div><i style="width:<?= round($n / $distMax * 100) ?>%"></i></div><b><?= $n ?></b></div><?php endforeach; ?></div>
</div>
</div>

<div class="card">
<div class="card-head"><div><h3>Daily activity</h3><small class="muted">Last 30 days</small></div></div>
<?= Charts::line([['name' => 'Active learners', 'data' => $series['active'], 'color' => 'var(--accent)', 'area' => true], ['name' => 'Lessons completed', 'data' => $series['completed'], 'color' => '#60a5fa'], ['name' => 'New enrollments', 'data' => $series['enrolled'], 'color' => 'var(--success)', 'dashed' => true]], $labels, ['label' => 'Daily activity']) ?>
</div>

<div class="card">
<div class="card-head"><div><h3>Lesson engagement</h3><small class="muted">% of enrolled students who opened and completed each lesson, in curriculum order — a falling bar shows where students drop off</small></div><div class="legend"><span><i class="rp-lg-v"></i>Viewed</span><span><i class="rp-lg-c"></i>Completed</span></div></div>
<?php if (!$lessonStats): ?><p class="muted small m-0">This course has no published lessons.</p><?php endif; ?>
<div class="rp-lessons">
<?php foreach ($lessonStats as $i => $l): $v = round($l['viewers'] / $enrolled * 100); $c = round($l['completions'] / $enrolled * 100); ?>
<div class="rp-lesson">
<span class="rp-n"><?= $i + 1 ?></span>
<div class="rp-lesson-t"><b title="<?= e($l['title']) ?>"><?= e($l['title']) ?></b><small class="muted"><?= e($l['module_title'] ?: 'No module') ?><?= $l['release_day'] ? ' · Day ' . (int)$l['release_day'] : '' ?> · avg <?= (int)round($l['avg_pct']) ?>% watched</small></div>
<div class="rp-track" title="Viewed <?= $v ?>% · Completed <?= $c ?>%"><i class="v" style="width:<?= min(100, $v) ?>%"></i><i class="c" style="width:<?= min(100, $c) ?>%"></i></div>
<span class="rp-pcts"><b><?= $v ?>%</b><small><?= $c ?>%</small></span>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

<div class="card p-0">
<div class="card-head rp-pad"><div><h3>Students</h3><small class="muted"><?= number_format($pg->total) ?> <?= $filter === 'all' && $q === '' ? 'enrolled' : 'matching' ?></small></div></div>
<div class="rp-tools rp-pad">
<form method="get" class="search grow"><?= icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name or email…"><input type="hidden" name="filter" value="<?= e($filter) ?>"><input type="hidden" name="sort" value="<?= e($sort) ?>"></form>
<nav class="tabs tabs-pill"><?php foreach ($filters as $k => $lbl): ?><a class="tab<?= $filter === $k ? ' active' : '' ?>" href="<?= e($qs(['filter' => $k, 'page' => null])) ?>"><?= e($lbl) ?></a><?php endforeach; ?></nav>
<form method="get"><input type="hidden" name="q" value="<?= e($q) ?>"><input type="hidden" name="filter" value="<?= e($filter) ?>"><select name="sort" data-autosubmit aria-label="Sort"><?php foreach (['progress' => 'Most progress', 'activity' => 'Recent activity', 'name' => 'Name', 'newest' => 'Newest'] as $k => $lbl): ?><option value="<?= $k ?>"<?= selected($k, $sort) ?>><?= e($lbl) ?></option><?php endforeach; ?></select></form>
</div>
<?php if (!$rows): ?><div class="rp-pad"><p class="muted center">No students match these filters.</p></div>
<?php else: ?>
<div class="table-wrap"><table class="table rp-table">
<thead><tr><th>Student</th><th>Progress</th><th>Last activity</th><th>Access</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($rows as $r):
    $pct = $total ? min(100, (int)floor($r['done'] / $total * 100)) : 0;
    $isDone = $r['completed_at'] || ($total && $r['done'] >= $total);
    $inactive = !$r['last_view'] || $r['last_view'] < $week;
    $expired = $r['expires_at'] && strtotime($r['expires_at'] . ' UTC') < time(); ?>
<tr>
<td><a class="user-cell" href="<?= e(url('/admin/students/' . $r['id'])) ?>"><?php partial('avatar', ['u' => $r]); ?><span><b><?= e($r['name']) ?></b><small class="muted"><?= e($r['email']) ?></small></span></a></td>
<td class="rp-w"><div class="rp-bar"><div class="progress"><i style="width:<?= $pct ?>%"></i></div><span><?= $pct ?>%</span></div><small class="muted"><?= (int)$r['done'] ?>/<?= (int)$total ?> lessons</small></td>
<td class="small"><?= $r['last_view'] ? e(time_ago($r['last_view'])) : '<span class="muted">Not started</span>' ?></td>
<td class="small nowrap"><?= e(dt($r['starts_at'])) ?> → <?= $r['expires_at'] ? e(dt($r['expires_at'])) : 'Lifetime' ?></td>
<td><?php if ($isDone): ?><span class="badge badge-success"><?= icon('trophy') ?>Completed</span><?php elseif ($r['status'] !== 'active'): ?><span class="badge badge-warning"><?= e(ucfirst($r['status'])) ?></span><?php elseif ($expired): ?><span class="badge badge-danger">Expired</span><?php elseif (!$r['viewed']): ?><span class="badge badge-muted">Not started</span><?php elseif ($inactive): ?><span class="badge badge-warning">Inactive</span><?php else: ?><span class="badge badge-info">In progress</span><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div>
<?= $pg->links() ?>
</div>
