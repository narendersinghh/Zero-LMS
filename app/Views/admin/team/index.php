<?php
use App\Core\Auth;
$layout = 'admin';
$title = 'Team';
$actions = '<button type="button" class="btn btn-primary btn-sm" data-modal-open="team-modal">' . icon('plus') . 'Invite team member</button>';
?>
<?php $tab = 'team'; include \App\Core\View::find('admin/settings/_tabs'); ?>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>Member</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $u): $me = (int)$u['id'] === Auth::id(); ?>
<tr><td><span class="user-cell"><?php partial('avatar', ['u' => $u]); ?><span><b><?= e($u['name']) ?><?= $me ? ' (you)' : '' ?></b><small class="muted"><?= e($u['email']) ?></small></span></span></td>
<td><span class="badge badge-<?= $u['role'] === 'super_admin' ? 'accent' : 'muted' ?>"><?= e(Auth::ROLES[$u['role']]) ?></span></td>
<td><?= $u['status'] === 'active' ? ($u['password_hash'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-muted">Invited</span>') : '<span class="badge badge-warning">Suspended</span>' ?></td>
<td class="small muted"><?= $u['last_login_at'] ? e(time_ago($u['last_login_at'])) : 'Never' ?></td>
<td class="right"><?php if (!$me): ?><form method="post" action="<?= e(url('/admin/team/' . $u['id'])) ?>" class="row gap-2 end"><?= csrf_field() ?>
<select name="role"><?php foreach (Auth::ROLES as $k => $v): ?><option value="<?= $k ?>"<?= selected($k, $u['role']) ?>><?= $v ?></option><?php endforeach; ?></select>
<select name="status"><option value="active"<?= selected('active', $u['status']) ?>>Active</option><option value="suspended"<?= selected('suspended', $u['status']) ?>>Suspended</option></select>
<button class="btn btn-ghost btn-sm">Update</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
<div class="card mt-4 small"><b>Roles.</b> <span class="muted"><b>Super Admin</b>: everything, including updates, modules, team, mail server and deleting courses. <b>Admin</b>: manage courses, videos, students, enrollments, imports, reports, email templates and branding. <b>Student</b>: learns in enrolled courses. Setting a member's role to Student removes admin access.</span></div>
<dialog class="modal" id="team-modal"><form method="post" action="<?= e(url('/admin/team')) ?>" class="stack"><?= csrf_field() ?>
<div class="modal-head"><h3>Invite team member</h3><button type="button" class="btn-icon" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
<div class="field"><label>Name</label><input name="name" required></div>
<div class="field"><label>Email</label><input type="email" name="email" required><small class="hint">Existing users with this email are promoted.</small></div>
<div class="field"><label>Role</label><select name="role"><option value="admin">Admin</option><option value="super_admin">Super Admin</option></select></div>
<div class="modal-foot"><button class="btn btn-primary">Send invite</button></div></form></dialog>
<?php include \App\Core\View::find('admin/settings/_end'); ?>
