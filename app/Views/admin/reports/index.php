<?php
use App\Controllers\Admin\ReportController;
use App\Core\Charts;
$layout = 'admin';
$title = 'Reports';
$labels = array_values($keys);
$hasData = array_sum(array_column($courses, 'students')) > 0;
$money = fn($v) => money($v);
$deltaBadge = function ($d, bool $pp = false) {
    if ($d === null) {
        return '<span class="rp-delta flat">-</span>';}
    $cls = $d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat');
    return '<span class="rp-delta ' . $cls . '">' . ($d > 0 ? '▲ ' : ($d < 0 ? '▼ ' : '')) . e(abs($d)) . ($pp ? ' pts' : '%') . '</span>';};
$fz = $funnel['enrolled'] ?? 0;
$completionRate = $fz ? round(($funnel['completed'] ?? 0) / $fz * 100) : 0;
?>
<div class="rp-page">
<div class="rp-head">
<div><p class="muted m-0">Learning, sales and engagement across all courses · daily figures in <?= e($tzName) ?></p></div>
<nav class="tabs tabs-pill" aria-label="Period"><?php foreach (ReportController::PERIODS as $d => $l): ?><a class="tab<?= $days === $d ? ' active' : '' ?>" href="?days=<?= $d ?>">Last <?= e($l) ?></a><?php endforeach; ?></nav>
</div>

<div class="rp-kpis">
<?php foreach ($kpis as $k): [$label, $value, $delta, $spark, $ic, $tint] = $k; ?>
<div class="rp-kpi">
<div class="row between gap-2"><span class="rp-kpi-ico" style="background:var(--p-<?= e($tint) ?>)"><?= icon($ic) ?></span><?= $deltaBadge($delta, !empty($k[7])) ?></div>
<small><?= e($label) ?></small>
<b><?= e($value) ?></b>
<?php if ($spark && array_sum($spark) > 0): ?><?= Charts::spark($spark, 'var(--accent)') ?><?php else: ?><span class="rp-kpi-note"><?= e($k[6] ?? 'vs previous ' . $days . ' days') ?></span><?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<?php if (!$hasData): ?>
<?php partial('empty', ['illus' => 'empty', 'heading' => 'No learning data yet', 'text' => 'Reports fill up as students enroll, watch lessons and pay for courses.', 'action' => '<a class="btn btn-primary" href="' . e(url('/admin/students/create')) . '">' . icon('plus') . 'Add a student</a>']); ?>
<?php else: ?>

<div class="rp-grid-2-1">
<div class="card">
<div class="card-head"><div><h3>Enrollments & revenue</h3><small class="muted">Per day, last <?= (int)$days ?> days</small></div>
<div class="tabs tabs-pill rp-mini-tabs" data-tabs><button type="button" class="tab active" data-tab="rp-enr">Enrollments</button><button type="button" class="tab" data-tab="rp-rev">Revenue</button></div></div>
<div id="rp-enr"><?= Charts::line([['name' => 'Enrollments', 'data' => $enrSeries, 'color' => 'var(--accent)', 'area' => true]], $labels, ['label' => 'Enrollments per day']) ?></div>
<div id="rp-rev" hidden><?= Charts::line([['name' => 'Revenue', 'data' => $revSeries, 'color' => 'var(--success)', 'area' => true]], $labels, ['label' => 'Revenue per day', 'format' => fn($v) => '₹' . ($v >= 1000 ? round($v / 1000, 1) . 'k' : round($v)), 'tip' => $money]) ?></div>
</div>
<div class="card">
<div class="card-head"><div><h3>Completion funnel</h3><small class="muted">All enrollments, lifetime</small></div></div>
<div class="rp-funnel-top"><?= Charts::donut($completionRate, 'completed') ?><p class="muted small m-0"><b class="rp-big"><?= number_format($fz) ?></b> enrollments · average progress <b><?= round($funnel['avg_progress'] ?? 0) ?>%</b></p></div>
<?= Charts::funnel([['label' => 'Enrolled', 'value' => $fz], ['label' => 'Started', 'value' => $funnel['started'] ?? 0], ['label' => 'Halfway', 'value' => $funnel['halfway'] ?? 0], ['label' => 'Completed', 'value' => $funnel['completed'] ?? 0]]) ?>
</div>
</div>

<div class="card">
<div class="card-head"><div><h3>Learning activity</h3><small class="muted">Students who watched a lesson and lessons completed per day</small></div></div>
<?= Charts::line([['name' => 'Active learners', 'data' => $actSeries, 'color' => 'var(--accent)', 'area' => true], ['name' => 'Lessons completed', 'data' => $cmpSeries, 'color' => '#60a5fa']], $labels, ['label' => 'Learning activity']) ?>
</div>

<div class="card p-0">
<div class="card-head rp-pad"><div><h3>Courses</h3><small class="muted">Lifetime performance per course</small></div></div>
<div class="table-wrap"><table class="table rp-table">
<thead><tr><th>Course</th><th>Students</th><th>Avg. progress</th><th>Completion</th><th>Revenue</th><th></th></tr></thead>
<tbody>
<?php foreach ($courses as $c): $rate = $c['students'] ? round($c['completed'] / $c['students'] * 100) : 0; ?>
<tr>
<td><a class="rp-course" href="<?= e(url('/admin/reports/courses/' . $c['id'])) ?>"><?= course_visual($c, 'rp-thumb') ?><span><b><?= e($c['title']) ?></b><small class="muted"><?= (int)$c['lessons'] ?> lessons<?= $c['status'] !== 'published' ? ' · <span class="badge badge-muted">' . e($c['status']) . '</span>' : '' ?></small></span></a></td>
<td><b><?= number_format((int)$c['students']) ?></b></td>
<td class="rp-w"><div class="rp-bar"><div class="progress"><i style="width:<?= (int)round($c['avg_progress']) ?>%"></i></div><span><?= (int)round($c['avg_progress']) ?>%</span></div></td>
<td><?= $rate ?>% <small class="muted">(<?= (int)$c['completed'] ?>)</small></td>
<td class="nowrap"><b><?= e(money($c['revenue'])) ?></b><small class="muted d-block"><?= (int)$c['orders'] ?> orders</small></td>
<td class="right"><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/reports/courses/' . $c['id'])) ?>">Details <?= icon('arrow-right') ?></a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
</div>

<div class="grid-2">
<div class="card">
<div class="card-head"><div><h3><?= icon('play') ?> Most watched lessons</h3><small class="muted">By number of students who opened them</small></div></div>
<?php if (!$watched): ?><p class="muted small m-0">No lesson views yet.</p><?php endif; ?>
<ol class="rp-rank">
<?php $top = max(1, (int)($watched[0]['viewers'] ?? 1)); foreach ($watched as $i => $l): ?>
<li><span class="rp-n"><?= $i + 1 ?></span><div class="grow"><b><?= e($l['title']) ?></b><small class="muted"><?= e($l['course_title']) ?> · <?= (int)$l['completions'] ?> completed · avg <?= (int)round($l['avg_pct']) ?>% watched</small><div class="progress progress-thin"><i style="width:<?= round($l['viewers'] / $top * 100) ?>%"></i></div></div><b class="rp-num"><?= (int)$l['viewers'] ?></b></li>
<?php endforeach; ?>
</ol>
</div>
<div class="card">
<div class="card-head"><div><h3><?= icon('alert') ?> Where students drop off</h3><small class="muted">Biggest fall in viewers compared with the previous lesson</small></div></div>
<?php if (!$drops): ?><div class="rp-ok"><?= icon('check-circle') ?><span>No drop-off detected. Students are keeping pace from lesson to lesson.</span></div><?php endif; ?>
<ol class="rp-rank">
<?php foreach ($drops as $l): ?>
<li><span class="rp-n rp-n-warn">−<?= (int)$l['drop_pct'] ?>%</span><div class="grow"><b><?= e($l['title']) ?></b><small class="muted"><?= e($l['course_title']) ?> · after “<?= e(str_limit($l['from'], 40)) ?>”</small></div><span class="rp-num nowrap"><?= (int)$l['prev_viewers'] ?> → <?= (int)$l['viewers'] ?></span></li>
<?php endforeach; ?>
</ol>
</div>
</div>
<?php endif; ?>
</div>
