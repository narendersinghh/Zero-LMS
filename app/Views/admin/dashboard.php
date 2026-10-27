<?php
use App\Core\Charts;
$layout = 'admin';
$title = 'Dashboard';
$u = auth();
$tz = new DateTime('now', site_tz());
$hour = (int)$tz->format('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$summary = [];
if ((int)$s['orders_submitted']) {
    $summary[] = (int)$s['orders_submitted'] . ' payment' . ((int)$s['orders_submitted'] === 1 ? '' : 's') . ' to verify';}
$summary[] = (int)$s['new_students'] . ' student' . ((int)$s['new_students'] === 1 ? '' : 's') . ' joined this week';
$summary[] = number_format((int)$s['active_learners']) . ' active learner' . ((int)$s['active_learners'] === 1 ? '' : 's');
$done = count(array_filter($checklist, fn($c) => $c[1]));
$pct = (int)round($done / max(1, count($checklist)) * 100);
$statusCls = ['paid' => 'success', 'submitted' => 'info', 'pending' => 'warning', 'rejected' => 'danger'];
$statusTxt = ['paid' => 'Paid', 'submitted' => 'Verify', 'pending' => 'Awaiting', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'];
$verbs = ['auth.login' => 'signed in', 'auth.logout' => 'signed out', 'auth.failed' => 'failed sign-in attempt', 'order.created' => 'started a checkout', 'order.submitted' => 'submitted a UPI payment', 'order.paid' => 'approved a payment',
    'order.rejected' => 'rejected a payment', 'enrollment.created' => 'enrolled a student', 'enrollment.extended' => 'extended access', 'enrollment.updated' => 'updated an enrollment', 'user.created' => 'created an account', 'user.password_set' => 'set a password',
    'course.created' => 'created a course', 'course.updated' => 'updated a course', 'lesson.created' => 'added a lesson', 'lesson.updated' => 'edited a lesson', 'video.uploaded' => 'uploaded a video', 'import.completed' => 'imported students from CSV',
    'settings.updated' => 'changed settings', 'modules.updated' => 'changed modules', 'system.update' => 'installed an update', 'student.invite_sent' => 'resent a setup email'];
$actIco = ['auth' => 'logout', 'order' => 'key', 'enrollment' => 'layers', 'user' => 'user', 'course' => 'book', 'lesson' => 'file', 'video' => 'video', 'import' => 'upload', 'settings' => 'settings', 'system' => 'refresh', 'modules' => 'package', 'student' => 'users'];
$shareUrl = $share ? url('/c/' . $share['slug'], [], true) : '';
?>
<section class="ad-hero">
<div class="ad-hero-copy">
<span class="eyebrow"><?= icon('sparkles') ?> <?= e($tz->format('l, F j')) ?></span>
<h2><?= e($greet) ?>, <?= e(explode(' ', trim($u['name']))[0]) ?> 👋</h2>
<p class="muted"><?= e(implode(' · ', $summary)) ?></p>
<div class="ad-actions">
<?php if (can('courses.manage')): ?><a class="btn btn-primary" href="<?= e(url('/admin/courses/create')) ?>"><?= icon('plus') ?>New course</a><?php endif; ?>
<?php if (can('students.manage')): ?><a class="btn btn-ghost" href="<?= e(url('/admin/students/create')) ?>"><?= icon('user') ?>Add student</a><?php endif; ?>
<?php if (can('imports.run')): ?><a class="btn btn-ghost" href="<?= e(url('/admin/import')) ?>"><?= icon('upload') ?>Import CSV</a><?php endif; ?>
<?php if (can('videos.manage')): ?><a class="btn btn-ghost" href="<?= e(url('/admin/videos')) ?>"><?= icon('video') ?>Upload video</a><?php endif; ?>
<?php if ($shareUrl): ?><button type="button" class="btn btn-ghost" data-copy="<?= e($shareUrl) ?>" title="<?= e($share['title']) ?>"><?= icon('copy') ?>Copy course link</button><?php endif; ?>
</div>
</div>
<div class="ad-hero-art"><?= illus('hero-learner') ?></div>
</section>

<section class="ad-kpis">
<?php foreach ($kpis as [$label, $value, $sub, $chg, $series, $ic, $tint, $href]): ?>
<a class="ad-kpi" href="<?= e(url($href)) ?>">
<div class="ad-kpi-top"><span class="ad-kpi-ico" style="background:var(--p-<?= e($tint) ?>)"><?= icon($ic) ?></span><small><?= e($label) ?></small><?php if ($chg !== null): ?><span class="ad-chg <?= $chg >= 0 ? 'up' : 'down' ?>"><?= $chg >= 0 ? '▲' : '▼' ?> <?= abs($chg) ?>%</span><?php endif; ?></div>
<b><?= e($value) ?></b>
<?= Charts::spark(array_values($series)) ?>
<span class="kpi-sub"><?= e($sub) ?></span>
</a>
<?php endforeach; ?>
</section>

<div class="ad-grid">
<div class="ad-col">
<div class="card">
<div class="card-head"><div><h3>Learning activity</h3><small class="muted">Last 4 weeks</small></div><a href="<?= e(url('/admin/reports')) ?>">Reports <?= icon('arrow-right') ?></a></div>
<?= Charts::line([['name' => 'New enrollments', 'data' => $chart['enrolls'], 'color' => 'var(--accent)', 'area' => true], ['name' => 'Lessons completed', 'data' => $chart['completions'], 'color' => 'var(--success)']], $chart['labels'], ['legend' => true, 'label' => 'Enrollments and lesson completions, last 4 weeks']) ?>
</div>

<div class="card">
<div class="card-head"><h3>Top courses</h3><?php if (can('courses.manage')): ?><a href="<?= e(url('/admin/courses')) ?>">All courses <?= icon('arrow-right') ?></a><?php endif; ?></div>
<?php if (!$top): ?><?php partial('empty', ['illus' => 'empty', 'heading' => 'No courses yet', 'text' => 'Create your first course to see it here.', 'action' => '<a class="btn btn-primary" href="' . e(url('/admin/courses/create')) . '">' . icon('plus') . 'New course</a>']); ?><?php endif; ?>
<div class="ad-courses">
<?php foreach ($top as $c): $avg = $c['enrolled'] && $c['lesson_count'] ? min(100, (int)round($c['done_lessons'] / ($c['enrolled'] * $c['lesson_count']) * 100)) : 0; ?>
<a class="ad-course" href="<?= e(url(can('reports.view') ? '/admin/reports/courses/' . $c['id'] : '/admin/courses/' . $c['id'] . '/edit')) ?>">
<span class="ad-thumb"><?= course_visual($c) ?></span>
<span class="grow"><b><?= e($c['title']) ?></b><small class="muted"><?= (int)$c['enrolled'] ?> enrolled · <?= (int)$c['completed'] ?> finished<?= $c['status'] !== 'published' ? ' · ' . e($c['status']) : '' ?></small><span class="progress"><i style="width:<?= $avg ?>%"></i></span></span>
<span class="ad-pct"><?= $avg ?>%<small>avg progress</small></span>
</a>
<?php endforeach; ?>
</div>
</div>

<div class="grid-2 ad-pair">
<div class="card">
<div class="card-head"><h3>Recent orders</h3><?php if (can('orders.manage')): ?><a href="<?= e(url('/admin/orders')) ?>">All orders</a><?php endif; ?></div>
<?php if (!$orders): ?><p class="muted small m-0">No orders yet. Share a course link to start selling.</p><?php endif; ?>
<?php foreach ($orders as $o): ?>
<a class="ad-row" href="<?= e(url('/admin/orders', ['q' => $o['ref']])) ?>"><span class="grow"><b><?= e($o['name']) ?></b><small class="muted"><?= e(str_limit($o['title'], 34)) ?> · <?= e(time_ago($o['created_at'])) ?></small></span><span class="ad-amt"><?= e(money($o['amount'], $o['currency'])) ?><span class="badge badge-<?= $statusCls[$o['status']] ?? 'muted' ?>"><?= e($statusTxt[$o['status']] ?? $o['status']) ?></span></span></a>
<?php endforeach; ?>
</div>
<div class="card">
<div class="card-head"><h3>Newest students</h3><?php if (can('students.manage')): ?><a href="<?= e(url('/admin/students')) ?>">All students</a><?php endif; ?></div>
<?php if (!$recentStudents): ?><p class="muted small m-0">No students yet.</p><?php endif; ?>
<?php foreach ($recentStudents as $st): ?>
<a class="ad-row" href="<?= e(url('/admin/students/' . $st['id'])) ?>"><?php partial('avatar', ['u' => $st]); ?><span class="grow"><b><?= e($st['name']) ?></b><small class="muted"><?= e($st['email']) ?></small></span><?= $st['activated'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-muted">Invited</span>' ?></a>
<?php endforeach; ?>
</div>
</div>
<?= hook_html('admin.dashboard_widgets') ?>
</div>

<aside class="ad-col">
<div class="card ad-attn">
<div class="card-head"><h3>Needs attention</h3><?php if ($attention): ?><span class="badge badge-danger"><?= count($attention) ?></span><?php endif; ?></div>
<?php if (!$attention): ?>
<div class="ad-clear"><?= illus('success') ?><b>All clear!</b><small class="muted">Nothing needs your attention right now.</small></div>
<?php endif; ?>
<?php foreach ($attention as [$ic, $tint, $head, $text, $href, $cta]): ?>
<a class="ad-attn-row" href="<?= e(url($href)) ?>"><span class="ad-attn-ico" style="background:var(--p-<?= e($tint) ?>)"><?= icon($ic) ?></span><span class="grow"><b><?= e($head) ?></b><small class="muted"><?= e($text) ?></small></span><span class="ad-go"><?= e($cta) ?> <?= icon('arrow-right') ?></span></a>
<?php endforeach; ?>
</div>

<?php if ($pct < 100): ?>
<div class="card ad-setup">
<div class="ad-setup-head"><?= Charts::donut($pct, 'done', 'var(--accent)', 84) ?><div><h3>Finish setting up</h3><small class="muted"><?= $done ?> of <?= count($checklist) ?> steps complete</small></div></div>
<ul class="ad-check">
<?php foreach ($checklist as [$label, $ok, $href]): ?>
<li class="<?= $ok ? 'ok' : '' ?>"><a href="<?= e(url($href)) ?>"><span class="ad-tick"><?= $ok ? icon('check') : '' ?></span><span class="grow"><?= e($label) ?></span><?php if (!$ok): ?><?= icon('chevron-right', 'muted') ?><?php endif; ?></a></li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>

<div class="card">
<div class="card-head"><h3>Recent activity</h3><?php if (can('logs.view')): ?><a href="<?= e(url('/admin/logs')) ?>">Audit log</a><?php endif; ?></div>
<ul class="ad-feed">
<?php foreach ($activity as $a): $grp = explode('.', $a['action'])[0]; ?>
<li><span class="ad-feed-ico"><?= icon($actIco[$grp] ?? 'activity') ?></span><span class="grow"><b><?= e($a['name'] ?: 'System') ?></b> <?= e($verbs[$a['action']] ?? str_replace(['.', '_'], [' · ', ' '], $a['action'])) ?><small class="muted d-block"><?= e(time_ago($a['created_at'])) ?></small></span></li>
<?php endforeach; ?>
<?php if (!$activity): ?><li class="muted small">No activity yet.</li><?php endif; ?>
</ul>
<p class="small muted m-0 mt-3"><?= icon('clock') ?> Scheduled tasks: <?= $cronLast ? e(time_ago($cronLast)) : 'never run' ?> · Mail queue: <?= (int)$s['mail_pending'] ?> pending</p>
</div>
</aside>
</div>
