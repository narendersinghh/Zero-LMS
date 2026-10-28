<?php
use App\Core\Theme;
?><!doctype html>
<html lang="en" data-theme="<?= e(Theme::current()) ?>">
<head><?php partial('head', ['title' => $title]); ?></head>
<body class="auth-body">
<div class="error-page">
<?php partial('logo'); ?>
<?= illus((int)$code === 403 || (int)$code === 419 ? 'locked' : '404') ?>
<div class="error-code"><?= (int)$code ?></div>
<h1><?= e($title) ?></h1>
<p class="muted"><?= e($message ?: ($code === 404 ? 'The page you are looking for does not exist or has moved.' : 'Please try again.')) ?></p>
<div class="row gap-2 center-x mt-4"><a class="btn btn-primary" href="<?= e(url('/')) ?>"><?= icon('home') ?>Go home</a><?php if ($code === 419): ?><a class="btn btn-ghost" href="#" data-back>Go back</a><?php endif; ?></div>
</div>
</body>
</html>
