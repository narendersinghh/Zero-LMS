<?php
$dark = setting('logo');
$light = setting('logo_light') ?: $dark;
$name = (string)setting('site_name', 'Zeroes');
?>
<a class="brand" href="<?= e(url($href ?? '/')) ?>" aria-label="<?= e($name) ?> home">
<?php if ($dark): ?>
<img class="logo logo-dark" src="<?= e(upload_url($dark)) ?>" alt="<?= e($name) ?>">
<img class="logo logo-light" src="<?= e(upload_url($light)) ?>" alt="<?= e($name) ?>">
<?php else: ?>
<span class="brand-mark"><svg viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="9" fill="var(--accent)"/><path d="M10 10h12l-12 12h12" fill="none" stroke="var(--accent-contrast)" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
<span class="brand-name"><?= e($name) ?></span>
<?php endif; ?>
</a>
