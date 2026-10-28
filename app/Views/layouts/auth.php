<?php
use App\Core\Theme;
use App\Services\Courses;
$q = Courses::quote();
?><!doctype html>
<html lang="en" data-theme="<?= e(Theme::current()) ?>">
<head><?php partial('head', ['title' => $title ?? 'Sign in']); ?></head>
<body class="auth-body">
<div class="auth">
<section class="auth-art" aria-hidden="true">
<div class="auth-art-in">
<?php partial('logo'); ?>
<div class="auth-hero">
<span class="eyebrow"><?= icon('sparkles') ?> <?= e(setting('site_tagline')) ?></span>
<?php if ($q): ?><blockquote><p>“<?= e($q['text']) ?>”</p><?php if ($q['author']): ?><cite><?= e($q['author']) ?></cite><?php endif; ?></blockquote><?php endif; ?>
</div>
<?= illus('auth', 'auth-illus') ?>
</div>
</section>
<section class="auth-form">
<div class="auth-top"><span class="show-sm"><?php partial('logo'); ?></span><?php partial('theme_toggle'); ?></div>
<div class="auth-card">
<?php partial('flash'); ?>
<?= $content ?>
</div>
<p class="auth-foot muted">© <?= gmdate('Y') ?> <?= e(setting('site_name')) ?><?php if (setting('support_email')): ?> · <a href="mailto:<?= e(setting('support_email')) ?>">Need help?</a><?php endif; ?></p>
</section>
</div>
</body>
</html>
