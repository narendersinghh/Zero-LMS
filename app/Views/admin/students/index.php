<?php
$layout = 'admin';
$title = 'Students';
$q = $_GET;
unset($q['page']);
$actions = '<a class="btn btn-ghost btn-sm" href="' . e(url('/admin/students/export', $q)) . '">' . icon('download') . 'Export</a><a class="btn btn-primary btn-sm" href="' . e(url('/admin/students/create')) . '">' . icon('plus') . 'Add student</a>';
$st = (string)($_GET['status'] ?? '');
$section = 'students';
include \App\Core\View::find('admin/students/_tabs');
?>
<form class="toolbar" method="get">
<div class="search grow"><?= icon('search') ?><input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search name, email or phone…"></div>
<select name="status" data-autosubmit><option value="">All statuses</option><?php foreach (['active' => 'Active', 'pending' => 'Awaiting setup', 'suspended' => 'Suspended'] as $k => $v): ?><option value="<?= $k ?>"<?= selected($k, $st) ?>><?= $v ?></option><?php endforeach; ?></select>
<select name="course" data-autosubmit><option value="">All courses</option><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"<?= selected($c['id'], $_GET['course'] ?? '') ?>><?= e($c['title']) ?></option><?php endforeach; ?></select>
<select name="sort" data-autosubmit><?php foreach (['new' => 'Newest', 'name' => 'Name', 'email' => 'Email', 'login' => 'Last login'] as $k => $v): ?><option value="<?= $k ?>"<?= selected($k, $_GET['sort'] ?? 'new') ?>><?= $v ?></option><?php endforeach; ?></select>
</form>
<?php if (!$rows): ?>
<?php partial('empty', ['icon' => 'users', 'heading' => 'No students found', 'text' => 'Add students one by one or import a CSV of email addresses.', 'action' => '<a class="btn btn-primary" href="' . e(url('/admin/import')) . '">' . icon('upload') . 'Import CSV</a>']); ?>
<?php else: ?>
<form method="post" action="<?= e(url('/admin/students/bulk')) ?>" data-bulk>
<?= csrf_field() ?>
<div class="bulkbar" data-bulkbar hidden>
<span><b data-bulk-count>0</b> selected</span>
<select name="action" data-bulk-action><option value="">Bulk action…</option><option value="enroll">Enroll in course</option><option value="invite">Resend setup email</option><option value="suspend">Suspend</option><option value="activate">Activate</option><option value="delete">Delete</option></select>
<select name="course_id" data-bulk-course hidden><option value="">Choose course…</option><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['title']) ?></option><?php endforeach; ?></select>
<label class="check" data-bulk-notify hidden><input type="checkbox" name="notify" value="1" checked><span>Email students</span></label>
<button class="btn btn-primary btn-sm" data-confirm="Apply this action to the selected students?">Apply</button>
</div>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th class="w-check"><input type="checkbox" data-check-all aria-label="Select all"></th><th>Student</th><th>Courses</th><th>Status</th><th>Last login</th><th>Joined</th></tr></thead>
<tbody>
<?php foreach ($rows as $u): ?>
<tr>
<td><input type="checkbox" name="ids[]" value="<?= (int)$u['id'] ?>" data-check aria-label="Select"></td>
<td><a class="user-cell" href="<?= e(url('/admin/students/' . $u['id'])) ?>"><?php partial('avatar', ['u' => $u]); ?><span><b><?= e($u['name']) ?></b><small class="muted"><?= e($u['email']) ?></small></span></a></td>
<td><?= (int)$u['courses'] ?></td>
<td><?= $u['status'] !== 'active' ? '<span class="badge badge-warning">Suspended</span>' : ($u['activated'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-muted">Awaiting setup</span>') ?></td>
<td class="muted small"><?= $u['last_login_at'] ? e(time_ago($u['last_login_at'])) : 'Never' ?></td>
<td class="muted small"><?= e(dt($u['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
</form>
<?= $pg->links() ?>
<?php endif; ?>
