<div class="empty">
<?php if (!empty($illus)): ?><?= illus($illus, 'empty-art') ?><?php else: ?><div class="empty-ico"><?= icon($icon ?? 'sparkles') ?></div><?php endif; ?>
<h3><?= e($heading ?? 'Nothing here yet') ?></h3>
<?php if (!empty($text)): ?><p class="muted"><?= e($text) ?></p><?php endif; ?>
<?php if (!empty($action)): ?><div class="mt-4"><?= $action ?></div><?php endif; ?>
</div>
