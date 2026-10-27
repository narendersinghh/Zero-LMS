<?php
use App\Services\Access;
$layout = 'admin';
$title = $u['name'];
$crumbs = ['Students' => '/admin/students'];
?>
<div class="profile-head card">
<?php partial('avatar', ['u' => $u, 'size' => 'xl']); ?>
<div class="grow"><h2><?= e($u['name']) ?></h2><p class="muted m-0"><?= e($u['email']) ?><?= $u['phone'] ? ' · ' . e($u['phone']) : '' ?></p>
<div class="row gap-2 mt-2 wrap"><?= $u['status'] !== 'active' ? '<span class="badge badge-warning">Suspended</span>' : ($u['password_hash'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-muted">Awaiting account setup</span>') ?><span class="badge badge-muted">Joined <?= e(dt($u['created_at'])) ?></span><span class="badge badge-muted">Last login: <?= $u['last_login_at'] ? e(time_ago($u['last_login_at'])) : 'never' ?></span></div></div>
<div class="row gap-2 wrap">
<form method="post" action="<?= e(url('/admin/students/' . $u['id'] . '/invite')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"><?= icon('mail') ?><?= $u['password_hash'] ? 'Send password reset' : 'Resend setup email' ?></button></form>
<form method="post" action="<?= e(url('/admin/students/' . $u['id'] . '/status')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" data-confirm="<?= $u['status'] === 'active' ? 'Suspend this student? They will be signed out immediately.' : 'Re-activate this student?' ?>"><?= icon($u['status'] === 'active' ? 'lock' : 'check') ?><?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?></button></form>
</div>
</div>

<div class="grid-main-side mt-4">
<div class="stack-lg">
<div class="card">
<div class="card-head"><h3>Enrollments</h3><button type="button" class="btn btn-primary btn-sm" data-modal-open="enroll-modal"><?= icon('plus') ?>Enroll in course</button></div>
<?php if (!$enr): ?><p class="muted small">Not enrolled in any courses.</p><?php endif; ?>
<?php foreach ($enr as $e): $p = $progress[$e['course_id']] ?? ['percent' => 0, 'completed' => 0, 'total' => 0]; $state = Access::enrollmentState($e, ['status' => $e['course_status'], 'start_date' => $e['start_date']]); ?>
<div class="enr-row">
<div class="grow"><b><?= e($e['title']) ?></b>
<small class="muted d-block">Start <?= e(dtime($e['starts_at'])) ?> · <?= $e['expires_at'] ? 'Expires ' . e(dtime($e['expires_at'])) : 'Lifetime' ?> · via <?= e($e['source']) ?></small>
<div class="progress mt-1"><i style="width:<?= (int)$p['percent'] ?>%"></i></div><small class="muted"><?= (int)$p['completed'] ?>/<?= (int)$p['total'] ?> lessons · <?= (int)$p['percent'] ?>%</small></div>
<span class="badge badge-<?= ['active' => 'success', 'pending' => 'info', 'expired' => 'danger', 'suspended' => 'warning'][$state] ?? 'muted' ?>"><?= e(ucfirst($state)) ?></span>
<button type="button" class="btn-icon" data-modal-open="enr-<?= (int)$e['id'] ?>" title="Edit access"><?= icon('edit') ?></button>
</div>
<dialog class="modal" id="enr-<?= (int)$e['id'] ?>"><form method="post" action="<?= e(url('/admin/enrollments/' . $e['id'])) ?>" class="stack"><?= csrf_field() ?>
<div class="modal-head"><h3><?= e($e['title']) ?></h3><button type="button" class="btn-icon" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
<div class="field"><label>Access starts</label><input type="datetime-local" name="starts_at" value="<?= e(to_local($e['starts_at'])) ?>" required></div>
<div class="field"><label>Access expires</label><input type="datetime-local" name="expires_at" value="<?= e(to_local($e['expires_at'])) ?>"><div class="row gap-2 mt-1 wrap" data-quick-extend><?php foreach ([7, 30, 90, 365] as $d): ?><button type="button" class="chip-btn" data-add-days="<?= $d ?>">+<?= $d ?>d</button><?php endforeach; ?></div></div>
<label class="check"><input type="checkbox" name="lifetime" value="1"<?= checked(!$e['expires_at']) ?>><span>Lifetime access (no expiry)</span></label>
<div class="field"><label>Status</label><select name="status"><option value="active"<?= selected('active', $e['status']) ?>>Active</option><option value="suspended"<?= selected('suspended', $e['status']) ?>>Suspended</option></select></div>
<label class="check"><input type="checkbox" name="notify" value="1" checked><span>Email the student if access is extended</span></label>
<div class="modal-foot"><button class="btn btn-primary">Save access</button></div></form>
<form method="post" action="<?= e(url('/admin/enrollments/' . $e['id'] . '/delete')) ?>" class="modal-danger"><?= csrf_field() ?><label class="check"><input type="checkbox" name="reset_progress" value="1"><span>Also delete progress</span></label><button class="btn btn-danger btn-sm" data-confirm="Remove this enrollment?"><?= icon('trash') ?>Remove</button></form></dialog>
<?php endforeach; ?>
</div>
<div class="card">
<div class="card-head"><h3>Recent learning activity</h3></div>
<?php if (!$recent): ?><p class="muted small">No lessons viewed yet.</p><?php endif; ?>
<div class="table-wrap"><table class="table table-sm"><tbody>
<?php foreach ($recent as $r): ?><tr><td><b><?= e($r['title']) ?></b><small class="muted d-block"><?= e($r['course_title']) ?></small></td><td><?= $r['completed_at'] ? '<span class="badge badge-success">Completed</span>' : '<span class="badge badge-muted">' . (int)$r['percent'] . '%</span>' ?></td><td class="muted small nowrap"><?= e(time_ago($r['last_viewed_at'])) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
</div>
</div>
<div class="stack-lg">
<div class="card">
<div class="card-head"><h3>Edit details</h3></div>
<form method="post" action="<?= e(url('/admin/students/' . $u['id'])) ?>" class="stack"><?= csrf_field() ?>
<div class="field"><label>Name</label><input name="name" value="<?= e($u['name']) ?>" required></div>
<div class="field"><label>Email</label><input type="email" name="email" value="<?= e($u['email']) ?>" required></div>
<div class="field"><label>Phone</label><input name="phone" value="<?= e($u['phone']) ?>"></div>
<div class="field"><label>Set new password</label><input type="password" name="password" autocomplete="new-password" placeholder="Leave empty to keep"></div>
<button class="btn btn-primary">Save</button>
</form>
</div>
<div class="card">
<div class="card-head"><h3>Emails</h3></div>
<?php if (!$mails): ?><p class="muted small">No emails sent.</p><?php endif; ?>
<?php foreach ($mails as $m): ?><div class="mini-notif"><b><?= e($m['subject']) ?></b><small class="muted"><?= e(ucfirst($m['status'])) ?> · <?= e(time_ago($m['sent_at'] ?: $m['created_at'])) ?><?= $m['last_error'] ? ' · ' . e(str_limit($m['last_error'], 60)) : '' ?></small></div><?php endforeach; ?>
</div>
<div class="card">
<div class="card-head"><h3>Audit trail</h3></div>
<ul class="activity"><?php foreach ($logs as $a): ?><li><span class="act-dot"></span><span class="grow small"><?= e(str_replace(['.', '_'], [' · ', ' '], $a['action'])) ?><small class="muted d-block"><?= e($a['ip']) ?></small></span><small class="muted nowrap"><?= e(time_ago($a['created_at'])) ?></small></li><?php endforeach; ?></ul>
</div>
<div class="card danger-zone"><div><h3>Delete student</h3><p class="muted small">Removes the account, enrollments and progress permanently.</p></div><form method="post" action="<?= e(url('/admin/students/' . $u['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="btn btn-danger btn-sm" data-confirm="Delete <?= e($u['email']) ?> permanently?"><?= icon('trash') ?>Delete</button></form></div>
</div>
</div>
<dialog class="modal" id="enroll-modal"><form method="post" action="<?= e(url('/admin/students/' . $u['id'] . '/enroll')) ?>" class="stack"><?= csrf_field() ?>
<div class="modal-head"><h3>Enroll in a course</h3><button type="button" class="btn-icon" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
<div class="field"><label>Course</label><select name="course_id" required><option value="">Choose…</option><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['title']) ?></option><?php endforeach; ?></select></div>
<div class="grid-2"><div class="field"><label>Starts</label><input type="datetime-local" name="starts_at"></div><div class="field"><label>Expires</label><input type="datetime-local" name="expires_at"><small class="hint">Empty = course default</small></div></div>
<label class="check"><input type="checkbox" name="notify" value="1" checked><span>Email the student</span></label>
<div class="modal-foot"><button class="btn btn-primary">Enroll</button></div></form></dialog>
