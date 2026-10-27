<?php
$layout = 'admin';
$title = 'Email templates';
$actions = '<a class="btn btn-ghost btn-sm" href="' . e(url('/admin/mail-log')) . '">' . icon('list') . 'Email log</a>';
?>
<div class="kpis kpis-3">
<a class="kpi" href="<?= e(url('/admin/mail-log', ['status' => 'pending'])) ?>"><small>Queued</small><b><?= (int)($stats['pending'] ?? 0) ?></b></a>
<a class="kpi" href="<?= e(url('/admin/mail-log', ['status' => 'sent'])) ?>"><small>Sent</small><b class="success"><?= (int)($stats['sent'] ?? 0) ?></b></a>
<a class="kpi" href="<?= e(url('/admin/mail-log', ['status' => 'failed'])) ?>"><small>Failed</small><b class="<?= !empty($stats['failed']) ? 'danger' : '' ?>"><?= (int)($stats['failed'] ?? 0) ?></b></a>
</div>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>Template</th><th>Subject</th><th>Status</th><th>Updated</th><th></th></tr></thead>
<tbody>
<?php foreach ($templates as $t): ?>
<tr><td><b><?= e($t['name']) ?></b><small class="muted d-block"><code><?= e($t['key']) ?></code></small></td><td><?= e($t['subject']) ?></td><td><?= $t['enabled'] ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-muted">Disabled</span>' ?></td><td class="muted small"><?= e(time_ago($t['updated_at'])) ?></td>
<td class="right nowrap"><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/emails/' . $t['key'] . '/preview')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>Preview</a><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/emails/' . $t['key'] . '/edit')) ?>"><?= icon('edit') ?>Edit</a></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
<p class="small muted mt-3">Delivery method, sender and SMTP are configured in <a href="<?= e(url('/admin/settings/mail')) ?>">Settings → Email delivery</a>. Branding (logo, accent color) is applied automatically.</p>
