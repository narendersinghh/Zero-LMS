<?php
/** Settings hub shell: opens the two-column layout (closed by _end.php). Settings views, email templates, team and audit log include both. */
$hub = [
    'Workspace' => [['general', 'General', 'settings', '/admin/settings/general', 'branding.manage'], ['branding', 'Branding & theme', 'palette', '/admin/settings/branding', 'branding.manage'], ['learning', 'Learning', 'sparkles', '/admin/settings/learning', 'branding.manage'], ['store', 'Store', 'grid', '/admin/settings/store', 'branding.manage']],
    'Delivery' => [['mail', 'Email delivery', 'mail', '/admin/settings/mail', 'mail.configure'], ['emails', 'Email templates', 'file', '/admin/emails', 'emails.manage'], ['payments', 'Payments', 'key', '/admin/settings/payments', 'payments.configure'], ['video', 'Video security', 'shield', '/admin/settings/video', 'branding.manage']],
    'Admin' => [['team', 'Team & roles', 'users', '/admin/team', 'admins.manage'], ['logs', 'Audit log', 'activity', '/admin/logs', 'logs.view']],];
?>
<div class="settings-shell">
<nav class="settings-nav" aria-label="Settings sections">
<?php foreach ($hub as $g => $items): $items = array_filter($items, fn($i) => can($i[4])); if (!$items) continue; ?>
<span class="settings-label"><?= e($g) ?></span>
<?php foreach ($items as [$k, $label, $ic, $href]): ?><a class="<?= ($tab ?? '') === $k ? 'active' : '' ?>" href="<?= e(url($href)) ?>"><span class="sn-ico"><?= icon($ic) ?></span><b><?= e($label) ?></b></a><?php endforeach; ?>
<?php endforeach; ?>
</nav>
<div class="settings-body">
