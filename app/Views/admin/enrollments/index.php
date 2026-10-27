<?php
use App\Services\Access;
$layout = 'admin';
$title = 'Enrollments';
$state = (string)($_GET['state'] ?? '');
?>
<form class="toolbar" method="get">
<div class="search grow"><?= icon('search') ?><input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search student…"></div>
<select name="course" data-autosubmit><option value="">All courses</option><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"<?= selected($c['id'], $_GET['course'] ?? '') ?>><?= e($c['title']) ?></option><?php endforeach; ?></select>
<select name="state" data-autosubmit><option value="">Any state</option><?php foreach (['active' => 'Active', 'expiring' => 'Expiring in 7 days', 'expired' => 'Expired', 'pending' => 'Not started', 'suspended' => 'Suspended', 'completed' => 'Completed'] as $k => $v): ?><option value="<?= $k ?>"<?= selected($k, $state) ?>><?= $v ?></option><?php endforeach; ?></select>
</form>
<?php if (!$rows): ?>
<?php partial('empty', ['icon' => 'layers', 'heading' => 'No enrollments found', 'text' => 'Enroll students from their profile, in bulk from the students list, or with a CSV import.']); ?>
<?php else: ?>
<form method="post" action="<?= e(url('/admin/enrollments/bulk')) ?>" data-bulk>
<?= csrf_field() ?>
<div class="bulkbar" data-bulkbar hidden>
<span><b data-bulk-count>0</b> selected</span>
<select name="action" data-bulk-action><option value="">Bulk action…</option><option value="extend">Extend by days</option><option value="set_expiry">Set expiry date</option><option value="lifetime">Give lifetime access</option><option value="suspend">Suspend</option><option value="activate">Activate</option><option value="delete">Remove</option></select>
<input type="number" name="days" value="7" min="1" class="w-90" data-bulk-days hidden aria-label="Days">
<input type="datetime-local" name="date" data-bulk-date hidden aria-label="Date">
<label class="check" data-bulk-notify hidden><input type="checkbox" name="notify" value="1" checked><span>Email students</span></label>
<button class="btn btn-primary btn-sm" data-confirm="Apply to selected enrollments?">Apply</button>
</div>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th class="w-check"><input type="checkbox" data-check-all aria-label="Select all"></th><th>Student</th><th>Course</th><th>Progress</th><th>Starts</th><th>Expires</th><th>State</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $e): $st = Access::enrollmentState($e, ['status' => $e['course_status'], 'start_date' => null]); $pct = $e['total'] ? floor($e['done'] / $e['total'] * 100) : 0; ?>
<tr>
<td><input type="checkbox" name="ids[]" value="<?= (int)$e['id'] ?>" data-check aria-label="Select"></td>
<td><a href="<?= e(url('/admin/students/' . $e['user_id'])) ?>"><b><?= e($e['name']) ?></b><small class="muted d-block"><?= e($e['email']) ?></small></a></td>
<td><?= e($e['course_title']) ?></td>
<td class="w-160"><div class="progress"><i style="width:<?= (int)$pct ?>%"></i></div><small class="muted"><?= (int)$e['done'] ?>/<?= (int)$e['total'] ?></small></td>
<td class="small nowrap"><?= e(dt($e['starts_at'])) ?></td>
<td class="small nowrap"><?= $e['expires_at'] ? e(dt($e['expires_at'])) : '<span class="muted">Lifetime</span>' ?></td>
<td><?php if ($e['completed_at']): ?><span class="badge badge-success">Completed</span><?php else: ?><span class="badge badge-<?= ['active' => 'success', 'pending' => 'info', 'expired' => 'danger', 'suspended' => 'warning'][$st] ?? 'muted' ?>"><?= e(ucfirst($st)) ?></span><?php endif; ?></td>
<td class="right"><a class="btn-icon" href="<?= e(url('/admin/students/' . $e['user_id'])) ?>" title="Manage"><?= icon('edit') ?></a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
</form>
<?= $pg->links() ?>
<?php endif; ?>
