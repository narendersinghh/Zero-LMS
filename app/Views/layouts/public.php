<?php
use App\Core\Theme;
$u = auth();
?><!doctype html>
<html lang="en" data-theme="<?= e(Theme::current()) ?>">
<head><?php partial('head', ['title' => $title ?? '']); ?></head>
<body class="public-body">
<header class="pub-top">
<div class="pub-in">
<?php partial('logo', ['href' => $u ? '/dashboard' : '/explore']); ?>
<nav class="pub-nav"><a href="<?= e(url('/explore')) ?>" class="<?= is_path('/explore') ? 'active' : '' ?>">Explore courses</a></nav>
<div class="pub-actions">
<?php partial('theme_toggle'); ?>
<?php if ($u): ?><a class="btn btn-primary btn-sm" href="<?= e(url(can('admin.access') ? '/admin' : '/dashboard')) ?>"><?= icon('grid') ?>My dashboard</a>
<?php else: ?><a class="btn btn-ghost btn-sm" href="<?= e(url('/login')) ?>">Sign in</a><?php if (setting('allow_registration', true)): ?><a class="btn btn-dark btn-sm hide-xs" href="<?= e(url('/register')) ?>">Create account</a><?php endif; ?><?php endif; ?>
</div>
</div>
</header>
<main id="main" class="pub-main">
<?php partial('flash'); ?>
<?= $content ?>
</main>
<footer class="pub-foot"><span>© <?= gmdate('Y') ?> <?= e(setting('site_name')) ?></span><?php if (setting('support_email')): ?><a href="mailto:<?= e(setting('support_email')) ?>">Support</a><?php endif; ?></footer>
<?php if (!empty($scripts)): foreach ((array)$scripts as $s): ?><script src="<?= e(asset($s)) ?>" defer></script><?php endforeach; endif; ?>
</body>
</html>
