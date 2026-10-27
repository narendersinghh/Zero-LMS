<?php $layout = 'auth'; $title = $setup ? 'Set up your account' : 'Choose a new password'; ?>
<?php if (!$valid): ?>
<div class="empty"><div class="empty-ico"><?= icon('clock') ?></div><h3>This link has expired</h3><p class="muted">Links are valid for a limited time and can only be used once.</p><a class="btn btn-primary mt-3" href="<?= e(url('/password/forgot')) ?>">Request a new link</a></div>
<?php else: ?>
<h1 class="auth-title"><?= $setup ? 'Welcome, ' . e(explode(' ', $name)[0]) . '!' : 'Choose a new password' ?></h1>
<p class="muted mb-4"><?= $setup ? 'Create a password to activate your account. You\'ll use it with <b>' . e($email) . '</b> to sign in.' : 'Choose a strong password for <b>' . e($email) . '</b>.' ?></p>
<form method="post" action="<?= e(url(($setup ? '/password/setup/' : '/password/reset/') . $token)) ?>" class="stack" data-loading>
<?= csrf_field() ?>
<input type="hidden" name="email" value="<?= e($email) ?>" autocomplete="username">
<div class="field"><label for="password">New password</label><div class="input-group"><input id="password" type="password" name="password" autocomplete="new-password" required minlength="8" data-strength><button type="button" class="input-btn" data-toggle-password aria-label="Show password"><?= icon('eye') ?></button></div><div class="strength" aria-hidden="true"><i></i></div><small class="hint">At least 8 characters with a letter and a number.</small><?= field_error('password') ?></div>
<div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></div>
<button type="submit" class="btn btn-primary btn-lg btn-block"><?= $setup ? 'Activate account' : 'Update password' ?> <?= icon('arrow-right') ?></button>
</form>
<?php endif; ?>
