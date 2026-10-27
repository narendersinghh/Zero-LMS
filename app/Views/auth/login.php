<?php $layout = 'auth'; $title = 'Sign in'; ?>
<h1 class="auth-title"><?= e(setting('login_title', 'Welcome back')) ?></h1>
<p class="muted mb-4"><?= e(setting('login_subtitle')) ?></p>
<form method="post" action="<?= e(url('/login')) ?>" class="stack" data-loading>
<?= csrf_field() ?>
<div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required autofocus class="<?= has_error('email') ? 'is-invalid' : '' ?>"><?= field_error('email') ?></div>
<div class="field"><div class="label-row"><label for="password">Password</label><a href="<?= e(url('/password/forgot')) ?>" class="small">Forgot password?</a></div>
<div class="input-group"><input id="password" type="password" name="password" autocomplete="current-password" required><button type="button" class="input-btn" data-toggle-password aria-label="Show password"><?= icon('eye') ?></button></div></div>
<button type="submit" class="btn btn-primary btn-lg btn-block">Sign in <?= icon('arrow-right') ?></button>
</form>
<p class="muted small mt-4 center">New here? Use the setup link from your welcome email, or <a href="<?= e(url('/password/forgot')) ?>">request a new one</a>.</p>
