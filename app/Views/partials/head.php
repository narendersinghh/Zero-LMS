<?php
use App\Core\Theme;
$siteName = (string)setting('site_name', 'Zeroes');
$fav = setting('favicon');
$fonts = Theme::fontsHref();
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= e(($title ?? '') ? $title . ' · ' . $siteName : $siteName) ?></title>
<meta name="robots" content="noindex,nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="base-url" content="<?= e(url('/')) ?>">
<meta name="theme-color" content="<?= e(((array)setting('palette_' . Theme::current()))['bg'] ?? '#0a0a0b') ?>">
<link rel="icon" href="<?= $fav ? e(upload_url($fav)) : e(asset('img/favicon.svg')) ?>">
<?php if ($fonts): ?><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="<?= e($fonts) ?>"><?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('dist/app.min.css')) ?>">
<style><?= Theme::css() ?></style>
<script src="<?= e(asset('dist/app.min.js')) ?>" defer></script>
