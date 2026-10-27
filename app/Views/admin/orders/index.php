<?php
use App\Services\Payments\Payments;
$layout = 'admin';
$title = 'Orders & payments';
$q = $_GET;
unset($q['page']);
$actions = '<a class="btn btn-ghost btn-sm" href="' . e(url('/admin/orders/export', $q)) . '">' . icon('download') . 'Export</a>' . (can('payments.configure') ? '<a class="btn btn-ghost btn-sm" href="' . e(url('/admin/settings/payments')) . '">' . icon('settings') . 'Payment settings</a>' : '');
$st = (string)($_GET['status'] ?? '');
$cls = ['paid' => 'success', 'submitted' => 'info', 'pending' => 'warning', 'rejected' => 'danger'];
?>
<div class="kpis">
<a class="kpi tint-blue" href="?status=submitted"><small>To verify</small><b><?= (int)($counts['submitted'] ?? 0) ?></b><span class="kpi-sub">UTR submitted by students</span></a>
<a class="kpi tint-yellow" href="?status=pending"><small>Awaiting payment</small><b><?= (int)($counts['pending'] ?? 0) ?></b><span class="kpi-sub">Checkout started</span></a>
<div class="kpi tint-green"><small>Revenue this month</small><b><?= e(money($revenue['month'])) ?></b><span class="kpi-sub"><?= (int)($counts['paid'] ?? 0) ?> paid orders</span></div>
<div class="kpi tint-purple"><small>Total revenue</small><b><?= e(money($revenue['total'])) ?></b><span class="kpi-sub">All time</span></div>
</div>
<form class="toolbar" method="get">
<div class="search grow"><?= icon('search') ?><input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search order, UTR, name or email…"></div>
<select name="status" data-autosubmit><option value="">All statuses</option><?php foreach (Payments::STATUSES as $k => $v): ?><option value="<?= $k ?>"<?= selected($k, $st) ?>><?= e($v) ?></option><?php endforeach; ?></select>
<select name="course" data-autosubmit><option value="">All courses</option><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"<?= selected($c['id'], $_GET['course'] ?? '') ?>><?= e($c['title']) ?></option><?php endforeach; ?></select>
</form>
<?php if (!$rows): ?><?php partial('empty', ['illus' => 'empty', 'heading' => 'No orders yet', 'text' => 'Share a course link (Courses → Edit → Pricing) — orders appear here as students pay.']); ?>
<?php else: ?>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>Order</th><th>Student</th><th>Course</th><th>Amount</th><th>Payment</th><th>Status</th><th class="right">Actions</th></tr></thead>
<tbody>
<?php foreach ($rows as $o): ?>
<tr>
<td><b><?= e($o['ref']) ?></b><small class="muted d-block"><?= e(dtime($o['created_at'])) ?></small></td>
<td><a href="<?= e(url('/admin/students/' . $o['user_id'])) ?>"><b><?= e($o['name']) ?></b><small class="muted d-block"><?= e($o['email']) ?><?= $o['phone'] ? ' · ' . e($o['phone']) : '' ?></small></a></td>
<td><?= e($o['course_title']) ?></td>
<td class="nowrap"><b><?= e(money($o['amount'], $o['currency'])) ?></b><?php if ((float)$o['discount'] > 0): ?><small class="muted d-block">−<?= e(money($o['discount'], $o['currency'])) ?><?= $o['coupon_code'] ? ' · ' . e($o['coupon_code']) : '' ?></small><?php endif; ?></td>
<td class="small"><?= e(strtoupper($o['gateway'])) ?><?php if ($o['utr']): ?><span class="d-block">UTR <code><?= e($o['utr']) ?></code></span><?php endif; ?><?php if ($o['proof']): ?><a class="d-block" href="<?= e(url('/admin/orders/' . $o['id'] . '/proof')) ?>" target="_blank" rel="noopener"><?= icon('file') ?> Screenshot</a><?php endif; ?><?php if ($o['payer_note']): ?><span class="d-block muted">“<?= e($o['payer_note']) ?>”</span><?php endif; ?></td>
<td><span class="badge badge-<?= $cls[$o['status']] ?? 'muted' ?>"><?= e(Payments::STATUSES[$o['status']] ?? $o['status']) ?></span><?php if ($o['admin_note']): ?><small class="muted d-block"><?= e(str_limit($o['admin_note'], 40)) ?></small><?php endif; ?></td>
<td class="right nowrap">
<?php if (in_array($o['status'], ['submitted', 'pending', 'rejected'], true)): ?>
<form method="post" action="<?= e(url('/admin/orders/' . $o['id'] . '/approve')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-primary btn-sm" data-confirm="Confirm you received <?= e(money($o['amount'], $o['currency'])) ?><?= $o['utr'] ? ' (UTR ' . e($o['utr']) . ')' : '' ?>? Access will be granted."><?= icon('check') ?>Approve</button></form>
<?php if ($o['status'] !== 'rejected'): ?><button type="button" class="btn btn-ghost btn-sm" data-modal-open="rej-<?= (int)$o['id'] ?>">Reject</button>
<dialog class="modal" id="rej-<?= (int)$o['id'] ?>"><form method="post" action="<?= e(url('/admin/orders/' . $o['id'] . '/reject')) ?>" class="stack"><?= csrf_field() ?><div class="modal-head"><h3>Reject <?= e($o['ref']) ?></h3><button type="button" class="btn-icon" data-modal-close aria-label="Close"><?= icon('x') ?></button></div><div class="field"><label>Reason (sent to the student)</label><input name="reason" value="We could not find a payment matching this UTR and amount." required></div><div class="modal-foot"><button class="btn btn-danger">Reject order</button></div></form></dialog><?php endif; ?>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?= $pg->links() ?>
<?php endif; ?>
