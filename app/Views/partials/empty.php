<div class="empty">
<div class="empty-ico"><?= icon($icon ?? 'sparkles') ?></div>
<h3><?= e($heading ?? 'Nothing here yet') ?></h3>
<?php if (!empty($text)): ?><p class="muted"><?= e($text) ?></p><?php endif; ?>
<?php if (!empty($action)): ?><div class="mt-3"><?= $action ?></div><?php endif; ?>
</div>
