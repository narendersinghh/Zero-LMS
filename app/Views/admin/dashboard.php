<?php
$layout = 'admin';
$title = 'Dashboard';
$actions = '<a class="btn btn-ghost btn-sm" href="' . e(url('/admin/import')) . '">' . icon('upload') . 'Import CSV</a><a class="btn btn-primary btn-sm" href="' . e(url('/admin/courses/create')) . '">' . icon('plus') . 'New course</a>';
$max = max(1, max(array_column($chart, 'v') ?: [1]), max(array_column($chart, 'c') ?: [1]));
?>
<?php
$pendingOrders = can('orders.manage') ? (int)\App\Core\DB::val("SELECT COUNT(*) FROM {orders} WHERE status = 'submitted'") : 0;
$qa = [['New course', '/admin/courses/create', 'plus', 'yellow', 'courses.manage', 0], ['Upload video', '/admin/videos', 'video', 'blue', 'videos.manage', 0], ['Add student', '/admin/students/create', 'user', 'green', 'students.manage', 0],
    ['Import CSV', '/admin/import', 'upload', 'purple', 'imports.run', 0], ['Verify payments', '/admin/orders?status=submitted', 'key', 'orange', 'orders.manage', $pendingOrders], ['Branding', '/admin/settings/branding', 'palette', 'pink', 'branding.manage', 0]];
?>
<div class="kpis">
<a class="kpi" href="<?= e(url('/admin/students')) ?>"><span class="kpi-ico" style="background:var(--p-yellow)"><?= icon('users') ?></span><small>Students</small><b><?= number_format((int)$s['students']) ?></b><span class="kpi-sub success">+<?= (int)$s['new_students'] ?> this week</span></a>
<a class="kpi" href="<?= e(url('/admin/enrollments', ['state' => 'active'])) ?>"><span class="kpi-ico" style="background:var(--p-blue)"><?= icon('layers') ?></span><small>Active enrollments</small><b><?= number_format((int)$s['active_enrollments']) ?></b><span class="kpi-sub"><?= (int)$s['expiring'] ?> expiring in 7 days</span></a>
<a class="kpi" href="<?= e(url('/admin/reports')) ?>"><span class="kpi-ico" style="background:var(--p-green)"><?= icon('check-circle') ?></span><small>Lessons completed (7d)</small><b><?= number_format((int)$s['completions_week']) ?></b><span class="kpi-sub"><?= (int)$s['active_learners'] ?> active learners</span></a>
<a class="kpi" href="<?= e(url('/admin/courses')) ?>"><span class="kpi-ico" style="background:var(--p-purple)"><?= icon('book') ?></span><small>Published courses</small><b><?= (int)$s['courses'] ?></b><span class="kpi-sub"><?= (int)$s['lessons'] ?> lessons total</span></a>
</div>

<div class="quick"><?php foreach ($qa as [$l, $h, $i, $t, $perm, $n]): if (!can($perm)) continue; ?><a class="qa" href="<?= e(url($h)) ?>"><span class="qa-ico" style="background:var(--p-<?= $t ?>)"><?= icon($i) ?></span><?= e($l) ?><?php if ($n): ?><span class="count"><?= $n ?></span><?php endif; ?></a><?php endforeach; ?></div>
<?php if ((int)$s['mail_failed'] || (int)$s['pending_setup']): ?>
<div class="alerts-row">
<?php if ((int)$s['mail_failed']): ?><div class="alert alert-danger"><?= icon('mail') ?><span><b><?= (int)$s['mail_failed'] ?></b> email(s) failed to send. Check your mail settings.</span><a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/mail-log', ['status' => 'failed'])) ?>">Review</a></div><?php endif; ?>
<?php if ((int)$s['pending_setup']): ?><div class="alert alert-info"><?= icon('user') ?><span><b><?= (int)$s['pending_setup'] ?></b> student(s) haven't set up their account yet.</span><a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/students', ['status' => 'pending'])) ?>">View</a></div><?php endif; ?>
</div>
<?php endif; ?>

<div class="grid-main-side">
<div class="card">
<div class="card-head"><h3>Learning activity · last 14 days</h3><div class="legend"><span><i class="lg-a"></i>Active learners</span><span><i class="lg-b"></i>Lessons completed</span></div></div>
<div class="bars" role="img" aria-label="Activity chart">
<?php foreach ($chart as $d): ?><div class="bar-col" title="<?= e(dt($d['d'] . ' 12:00:00', 'M j')) ?>: <?= $d['v'] ?> learners, <?= $d['c'] ?> completions"><div class="bar-pair"><i class="bar-a" style="height:<?= round($d['v'] / $max * 100) ?>%"></i><i class="bar-b" style="height:<?= round($d['c'] / $max * 100) ?>%"></i></div><small><?= e(dt($d['d'] . ' 12:00:00', 'j')) ?></small></div><?php endforeach; ?>
</div>
</div>
<div class="card">
<div class="card-head"><h3>Top courses</h3><a class="small" href="<?= e(url('/admin/reports')) ?>">Reports</a></div>
<?php if (!$top): ?><p class="muted small">No courses yet.</p><?php endif; ?>
<?php foreach ($top as $c): $rate = $c['enrolled'] ? round($c['completed'] / $c['enrolled'] * 100) : 0; ?>
<a class="list-row" href="<?= e(url('/admin/reports/courses/' . $c['id'])) ?>"><span class="thumb"<?= $c['cover_image'] ? ' style="background-image:url(\'' . e(upload_url($c['cover_image'])) . '\')"' : '' ?>><?= $c['cover_image'] ? '' : e(initials($c['title'])) ?></span><span class="grow"><b><?= e($c['title']) ?></b><small class="muted"><?= (int)$c['enrolled'] ?> enrolled · <?= $rate ?>% completed</small></span></a>
<?php endforeach; ?>
</div>
</div>

<?= hook_html('admin.dashboard_widgets') ?>
<div class="grid-2 mt-4">
<div class="card">
<div class="card-head"><h3>Newest students</h3><a class="small" href="<?= e(url('/admin/students')) ?>">All students</a></div>
<?php if (!$recentStudents): ?><?php partial('empty', ['icon' => 'users', 'heading' => 'No students yet', 'action' => '<a class="btn btn-primary btn-sm" href="' . e(url('/admin/students/create')) . '">Add a student</a>']); ?><?php endif; ?>
<?php foreach ($recentStudents as $u): ?>
<a class="list-row" href="<?= e(url('/admin/students/' . $u['id'])) ?>"><?php partial('avatar', ['u' => $u]); ?><span class="grow"><b><?= e($u['name']) ?></b><small class="muted"><?= e($u['email']) ?></small></span><?= $u['activated'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-muted">Invited</span>' ?></a>
<?php endforeach; ?>
</div>
<div class="card">
<div class="card-head"><h3>Recent activity</h3><?php if (can('logs.view')): ?><a class="small" href="<?= e(url('/admin/logs')) ?>">Audit log</a><?php endif; ?></div>
<ul class="activity">
<?php foreach ($activity as $a): ?><li><span class="act-dot"></span><span class="grow"><b><?= e($a['name'] ?: 'System') ?></b> <span class="muted"><?= e(str_replace(['.', '_'], [' · ', ' '], $a['action'])) ?></span></span><small class="muted nowrap"><?= e(time_ago($a['created_at'])) ?></small></li><?php endforeach; ?>
</ul>
<p class="small muted mt-3"><?= icon('clock') ?> Scheduled tasks last ran: <?= $cronLast ? e(time_ago($cronLast)) : 'never' ?> · Mail queue: <?= (int)$s['mail_pending'] ?> pending</p>
</div>
</div>
