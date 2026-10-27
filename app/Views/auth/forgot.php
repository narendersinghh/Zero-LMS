<?php $layout = 'auth'; $title = 'Reset password'; ?>
<a href="<?= e(url('/login')) ?>" class="back-link"><?= icon('arrow-left') ?>Back to sign in</a>
<h1 class="auth-title">Forgot your password?</h1>
<p class="muted mb-4">Enter your email and we'll send you a secure link to set a new password. First time here? This also resends your account setup link.</p>
<form method="post" action="<?= e(url('/password/forgot')) ?>" class="stack" data-loading>
<?= csrf_field() ?>
<div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required autofocus></div>
<button type="submit" class="btn btn-primary btn-lg btn-block">Send link <?= icon('mail') ?></button>
</form>
