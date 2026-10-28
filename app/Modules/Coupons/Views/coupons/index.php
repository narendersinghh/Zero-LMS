<?php
$layout = 'admin';
$title = 'Coupons';
$actions = '<a class="btn btn-primary btn-sm" href="' . e(url('/admin/coupons/create')) . '">' . icon('plus') . 'New coupon</a>';
?>
<form class="toolbar" method="get"><div class="search grow"><?= icon('search') ?><input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search coupon code…"></div></form>
<?php if (!$rows): ?><?php partial('empty', ['illus' => 'empty', 'icon' => 'sparkles', 'heading' => 'No coupons yet', 'text' => 'Create discount codes and share course links that apply them automatically.', 'action' => '<a class="btn btn-primary" href="' . e(url('/admin/coupons/create')) . '">' . icon('plus') . 'New coupon</a>']); ?>
<?php else: ?>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>Code</th><th>Discount</th><th>Course</th><th>Uses</th><th>Valid</th><th>Status</th><th class="right">Share</th></tr></thead>
<tbody>
<?php foreach ($rows as $c): $link = $c['course_slug'] ? url('/c/' . $c['course_slug'], ['coupon' => $c['code']], true) : ''; $expired = $c['expires_at'] && strtotime($c['expires_at'] . ' UTC') <= time(); ?>
<tr>
<td><a href="<?= e(url('/admin/coupons/' . $c['id'] . '/edit')) ?>"><code><?= e($c['code']) ?></code></a><?php if ($c['note']): ?><small class="muted d-block"><?= e(str_limit($c['note'], 50)) ?></small><?php endif; ?></td>
<td><b><?= $c['type'] === 'fixed' ? e(money($c['value'])) : e(rtrim(rtrim(number_format((float)$c['value'], 2), '0'), '.')) . '%' ?></b> off</td>
<td><?= $c['course_title'] ? e($c['course_title']) : '<span class="muted">All courses</span>' ?></td>
<td><?= (int)$c['used_count'] ?><?= $c['max_uses'] ? ' / ' . (int)$c['max_uses'] : '' ?><?php if ((float)$c['saved'] > 0): ?><small class="muted d-block"><?= e(money($c['saved'])) ?> saved</small><?php endif; ?></td>
<td class="small muted nowrap"><?= $c['starts_at'] ? e(dt($c['starts_at'])) : 'Now' ?> → <?= $c['expires_at'] ? e(dt($c['expires_at'])) : 'No expiry' ?></td>
<td><?php if (!(int)$c['active']): ?><span class="badge badge-muted">Paused</span><?php elseif ($expired): ?><span class="badge badge-warning">Expired</span><?php elseif ($c['max_uses'] && $c['used_count'] >= $c['max_uses']): ?><span class="badge badge-warning">Used up</span><?php else: ?><span class="badge badge-success">Active</span><?php endif; ?></td>
<td class="right nowrap"><?php if ($link): ?><button type="button" class="btn btn-ghost btn-sm" data-copy="<?= e($link) ?>"><?= icon('copy') ?>Copy link</button><?php else: ?><button type="button" class="btn btn-ghost btn-sm" data-copy="<?= e($c['code']) ?>"><?= icon('copy') ?>Copy code</button><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?= $pg->links() ?>
<?php endif; ?>
<p class="small muted mt-3">Tip: add <code>?coupon=CODE</code> to any course link (<code><?= e(url('/c/your-course', [], true)) ?></code>) and the code is applied automatically at checkout.</p>
