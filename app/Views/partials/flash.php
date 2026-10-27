<?php $fl = \App\Core\Session::flashes(); if ($fl): ?>
<div class="toasts" role="status" aria-live="polite">
<?php foreach ($fl as $f): $t = in_array($f['type'], ['success', 'error', 'info', 'warning'], true) ? $f['type'] : 'info'; ?>
<div class="toast toast-<?= $t ?>"><?= icon(['success' => 'check-circle', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'][$t]) ?><span><?= e($f['msg']) ?></span><button type="button" class="toast-x" data-dismiss aria-label="Dismiss"><?= icon('x') ?></button></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
