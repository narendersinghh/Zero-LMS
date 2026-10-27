<?php $layout = 'app'; $title = 'Profile'; ?>
<div class="page-head"><div><h1>Profile & settings</h1><p class="muted">Manage your personal details, password and preferences.</p></div></div>
<div class="grid-2">
<div class="stack-lg">
<div class="card">
<div class="card-head"><h3>Personal details</h3></div>
<div class="row gap-3 mb-4 wrap">
<?php partial('avatar', ['u' => $user, 'size' => 'xl']); ?>
<form method="post" action="<?= e(url('/profile/avatar')) ?>" enctype="multipart/form-data" class="row gap-2 wrap">
<?= csrf_field() ?>
<label class="btn btn-ghost btn-sm"><?= icon('upload') ?>Upload photo<input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" hidden data-autosubmit></label>
<?php if ($user['avatar']): ?><button class="btn btn-ghost btn-sm" name="remove" value="1">Remove</button><?php endif; ?>
</form>
</div>
<form method="post" action="<?= e(url('/profile')) ?>" class="stack">
<?= csrf_field() ?>
<div class="field"><label>Full name</label><input name="name" value="<?= e(old('name', $user['name'])) ?>" required maxlength="150"></div>
<div class="field"><label>Email</label><input value="<?= e($user['email']) ?>" disabled><small class="hint">Contact support to change your email address.</small></div>
<div class="field"><label>Phone</label><input name="phone" value="<?= e(old('phone', $user['phone'])) ?>" maxlength="40"></div>
<div class="field"><label>About you</label><textarea name="bio" rows="3" maxlength="1000"><?= e(old('bio', $user['bio'])) ?></textarea></div>
<div><button class="btn btn-primary">Save changes</button></div>
</form>
</div>
<div class="card">
<div class="card-head"><h3>Password</h3></div>
<form method="post" action="<?= e(url('/profile/password')) ?>" class="stack">
<?= csrf_field() ?>
<input type="hidden" name="email" value="<?= e($user['email']) ?>" autocomplete="username">
<?php if ($user['password_hash']): ?><div class="field"><label>Current password</label><input type="password" name="current_password" autocomplete="current-password" required><?= field_error('current_password') ?></div><?php endif; ?>
<div class="field"><label>New password</label><input type="password" name="password" autocomplete="new-password" required minlength="8" data-strength><div class="strength"><i></i></div><?= field_error('password') ?></div>
<div class="field"><label>Confirm new password</label><input type="password" name="password_confirmation" autocomplete="new-password" required></div>
<div><button class="btn btn-primary">Update password</button></div>
</form>
</div>
</div>
<div class="stack-lg">
<?php if (setting('allow_theme_toggle', true)): ?>
<div class="card">
<div class="card-head"><h3>Appearance</h3></div>
<div class="theme-pick">
<button type="button" class="theme-opt" data-set-theme="dark"><span class="swatch swatch-dark"></span>Dark</button>
<button type="button" class="theme-opt" data-set-theme="light"><span class="swatch swatch-light"></span>Light</button>
</div>
</div>
<?php endif; ?>
<div class="card">
<div class="card-head"><h3>My course access</h3></div>
<?php if (!$enrollments): ?><p class="muted small">No enrollments yet.</p><?php endif; ?>
<?php foreach ($enrollments as $c): ?>
<div class="access-row"><div class="grow"><b><?= e($c['title']) ?></b><small class="muted"><?= $c['expires_at'] ? 'Access until ' . e(dtime($c['expires_at'])) : 'Lifetime access' ?></small></div>
<span class="badge badge-<?= ['active' => 'success', 'pending' => 'info', 'expired' => 'danger', 'suspended' => 'warning'][$c['state']] ?? 'muted' ?>"><?= e(ucfirst($c['state'])) ?></span></div>
<?php endforeach; ?>
</div>
<div class="card small muted">
<div class="card-head"><h3>Account</h3></div>
<dl class="dl"><dt>Member since</dt><dd><?= e(dt($user['created_at'])) ?></dd><dt>Last sign in</dt><dd><?= e(dtime($user['last_login_at'])) ?></dd></dl>
</div>
</div>
</div>
