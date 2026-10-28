<?php $layout = 'auth'; $title = 'Sign in'; ?>
<h1 class="auth-title"><?= e(setting('login_title', 'Welcome back')) ?></h1>
<p class="muted mb-4"><?= e(setting('login_subtitle')) ?></p>
<form method="post" action="<?= e(url('/login')) ?>" class="stack" data-loading>
<?= csrf_field() ?>
<div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required autofocus class="<?= has_error('email') ? 'is-invalid' : '' ?>"><?= field_error('email') ?></div>
<div class="field"><label for="password">Password</label>
<div class="input-group"><input id="password" type="password" name="password" autocomplete="current-password" required><button type="button" class="input-btn" data-toggle-password aria-label="Show password"><?= icon('eye') ?></button></div>
<a href="<?= e(url('/password/forgot')) ?>" class="small forgot-link">Forgot password?</a></div>
<button type="submit" class="btn btn-primary btn-lg btn-block">Sign in <?= icon('arrow-right') ?></button>
</form>
<?php if (setting('allow_registration', true)): ?><p class="muted small mt-4 center">New here? <a href="<?= e(url('/register')) ?>">Create an account</a><?php if (setting('store_enabled')): ?> · <a href="<?= e(url('/store')) ?>">Browse courses</a><?php endif; ?></p><?php endif; ?>
<p class="muted small mt-2 center">Got a welcome email? Use its setup link, or <a href="<?= e(url('/password/forgot')) ?>">request a new one</a>.</p>
