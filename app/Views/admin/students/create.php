<?php
$layout = 'admin';
$title = 'Add student';
$crumbs = ['Students' => '/admin/students'];
?>
<form method="post" action="<?= e(url('/admin/students')) ?>" class="form-layout" data-loading>
<?= csrf_field() ?>
<div class="card stack">
<div class="card-head"><h3>Student details</h3></div>
<div class="grid-2"><div class="field"><label>Email</label><input type="email" name="email" value="<?= e(old('email')) ?>" required autofocus><?= field_error('email') ?></div><div class="field"><label>Full name</label><input name="name" value="<?= e(old('name')) ?>" placeholder="Optional — derived from email"></div></div>
<div class="field"><label>Phone</label><input name="phone" value="<?= e(old('phone')) ?>"></div>
<div class="field"><label>Password</label><input type="password" name="password" autocomplete="new-password" placeholder="Leave empty to let the student choose (recommended)"><?= field_error('password') ?></div>
<hr>
<div class="card-head"><h3>Course access</h3></div>
<div class="check-list">
<?php if (!$courses): ?><p class="muted small">No courses yet.</p><?php endif; ?>
<?php foreach ($courses as $c): ?><label class="check"><input type="checkbox" name="courses[]" value="<?= (int)$c['id'] ?>"<?= checked(in_array((string)$c['id'], (array)old('courses', []), true)) ?>><span><?= e($c['title']) ?><small class="muted"><?= $c['access_days'] ? ' · ' . (int)$c['access_days'] . '-day access' : '' ?><?= $c['end_date'] ? ' · ends ' . e($c['end_date']) : '' ?></small></span></label><?php endforeach; ?>
</div>
<div class="grid-2"><div class="field"><label>Access starts</label><input type="datetime-local" name="starts_at" value="<?= e(old('starts_at')) ?>"><small class="hint">Default: now. Day 1 of drip lessons.</small></div><div class="field"><label>Access expires</label><input type="datetime-local" name="expires_at" value="<?= e(old('expires_at')) ?>"><small class="hint">Default: from the course's access duration / end date.</small></div></div>
</div>
<aside class="card stack">
<div class="card-head"><h3>Notify</h3></div>
<label class="check"><input type="checkbox" name="send_email" value="1" checked><span>Email login instructions<small class="muted d-block">Sends a secure link to set a password (valid 7 days).</small></span></label>
<button class="btn btn-primary btn-block"><?= icon('plus') ?>Create student</button>
</aside>
</form>
