<?php
/** Settings hub shell: opens the two-column layout (closed by _end.php). Each settings view includes both. */
$hub = [
    'Workspace' => [['general', 'General', 'settings', 'Name, timezone, sign-in text'], ['branding', 'Branding & theme', 'palette', 'Logo, colors, fonts'], ['learning', 'Learning', 'sparkles', 'Notifications, quotes']],
    'Delivery' => [['mail', 'Email delivery', 'mail', 'SMTP or API provider'], ['payments', 'Payments', 'key', 'UPI, gateways, sign-up'], ['video', 'Video security', 'shield', 'Playback & streaming']],];
$more = [['/admin/emails', 'Email templates', 'file', 'emails.manage'], ['/admin/team', 'Team & roles', 'users', 'admins.manage'], ['/admin/modules', 'Modules', 'package', 'modules.manage'], ['/admin/system', 'System & updates', 'refresh', 'settings.system']];
?>
<div class="settings-shell">
<nav class="settings-nav" aria-label="Settings sections">
<?php foreach ($hub as $g => $items): ?>
<span class="settings-label"><?= e($g) ?></span>
<?php foreach ($items as [$k, $label, $ic, $desc]): if (($k === 'mail' && !can('mail.configure')) || ($k === 'payments' && !can('payments.configure'))) continue; ?>
<a class="<?= $tab === $k ? 'active' : '' ?>" href="<?= e(url('/admin/settings/' . $k)) ?>"><span class="sn-ico"><?= icon($ic) ?></span><span><b><?= e($label) ?></b><small><?= e($desc) ?></small></span></a>
<?php endforeach; ?>
<?php endforeach; ?>
<span class="settings-label">More</span>
<?php foreach ($more as [$h, $label, $ic, $perm]): if (!can($perm)) continue; ?><a href="<?= e(url($h)) ?>"><span class="sn-ico"><?= icon($ic) ?></span><span><b><?= e($label) ?></b></span><?= icon('chevron-right', 'sn-go') ?></a><?php endforeach; ?>
</nav>
<div class="settings-body">
