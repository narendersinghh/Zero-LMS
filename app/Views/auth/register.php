<?php $layout = 'auth'; $title = 'Create account'; ?>
<h1 class="auth-title">Create your account</h1>
<p class="muted mb-4">Join <?= e(setting('site_name')) ?> and start learning today.</p>
<form method="post" action="<?= e(url('/register')) ?>" class="stack" data-loading>
<?= csrf_field() ?>
<div class="field"><label for="name">Full name</label><input id="name" name="name" value="<?= e(old('name')) ?>" required autocomplete="name"><?= field_error('name') ?></div>
<div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email"><?= field_error('email') ?></div>
<div class="field"><label for="phone">Mobile <span class="muted">(optional)</span></label><input id="phone" type="tel" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel"></div>
<div class="field"><label for="password">Password</label><div class="input-group"><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" data-strength><button type="button" class="input-btn" data-toggle-password aria-label="Show password"><?= icon('eye') ?></button></div><div class="strength"><i></i></div><small class="hint">At least 8 characters with a letter and a number.</small><?= field_error('password') ?></div>
<button type="submit" class="btn btn-primary btn-lg btn-block">Create account <?= icon('arrow-right') ?></button>
</form>
<p class="muted small mt-4 center">Already have an account? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
