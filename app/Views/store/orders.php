<?php
use App\Services\Payments\Payments;
$layout = 'app';
$title = 'Orders & payments';
$cls = ['paid' => 'success', 'submitted' => 'info', 'pending' => 'warning', 'rejected' => 'danger'];
?>
<div class="page-head"><div><h1>Orders & payments</h1><p class="muted">Your purchases and their status.</p></div><a class="btn btn-ghost" href="<?= e(url('/explore')) ?>"><?= icon('search') ?>Explore courses</a></div>
<?php if (!$rows): ?><?php partial('empty', ['illus' => 'empty', 'heading' => 'No orders yet', 'text' => 'Courses you buy will appear here with their receipts.', 'action' => '<a class="btn btn-primary" href="' . e(url('/explore')) . '">Browse courses</a>']); ?>
<?php else: ?>
<div class="order-list">
<?php foreach ($rows as $r): ?>
<div class="card order-row">
<div class="grow"><b><?= e($r['course_title']) ?></b><small class="muted d-block"><?= e($r['ref']) ?> · <?= e(dtime($r['created_at'])) ?><?= $r['utr'] ? ' · UTR ' . e($r['utr']) : '' ?></small></div>
<b class="nowrap"><?= e(money($r['amount'], $r['currency'])) ?></b>
<span class="badge badge-<?= $cls[$r['status']] ?? 'muted' ?>"><?= e(Payments::STATUSES[$r['status']] ?? $r['status']) ?></span>
<?php if (in_array($r['status'], ['pending', 'rejected'], true)): ?><a class="btn btn-primary btn-sm" href="<?= e(url('/orders/' . $r['ref'] . '/pay')) ?>">Pay now</a>
<?php elseif ($r['status'] === 'paid'): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('/courses/' . $r['course_slug'])) ?>">Open course</a><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
