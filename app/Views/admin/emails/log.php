<?php
$layout = 'admin';
$title = 'Email log';
$crumbs = ['Email templates' => '/admin/emails'];
$actions = '<form method="post" action="' . e(url('/admin/mail-log/process')) . '" class="inline">' . csrf_field() . '<button class="btn btn-primary btn-sm">' . icon('refresh') . 'Send queued now</button></form>';
?>
<?php if ($driver === 'log'): ?><div class="alert alert-warning"><?= icon('alert') ?><span>Mail driver is <b>log</b> — emails are written to <code>storage/logs/mail-*.log</code> instead of being delivered. <?php if (can('mail.configure')): ?><a href="<?= e(url('/admin/settings/mail')) ?>">Configure SMTP</a><?php endif; ?></span></div><?php endif; ?>
<form class="toolbar" method="get">
<div class="search grow"><?= icon('search') ?><input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search recipient or subject…"></div>
<select name="status" data-autosubmit><option value="">All</option><?php foreach (['pending' => 'Queued', 'sent' => 'Sent', 'failed' => 'Failed'] as $k => $v): ?><option value="<?= $k ?>"<?= selected($k, $_GET['status'] ?? '') ?>><?= $v ?></option><?php endforeach; ?></select>
</form>
<div class="card p-0"><div class="table-wrap"><table class="table table-sm">
<thead><tr><th>Recipient</th><th>Subject</th><th>Status</th><th>Queued</th><th>Sent</th><th></th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="6" class="muted center">No emails.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?>
<tr><td><b><?= e($r['to_name']) ?></b><small class="muted d-block"><?= e($r['to_email']) ?></small></td><td><?= e($r['subject']) ?><?php if ($r['last_error']): ?><small class="danger d-block"><?= e(str_limit($r['last_error'], 120)) ?></small><?php endif; ?></td>
<td><span class="badge badge-<?= ['sent' => 'success', 'pending' => 'info', 'failed' => 'danger'][$r['status']] ?? 'muted' ?>"><?= e($r['status']) ?></span><?= $r['attempts'] > 1 ? '<small class="muted"> ×' . (int)$r['attempts'] . '</small>' : '' ?></td>
<td class="small muted nowrap"><?= e(dtime($r['created_at'])) ?></td><td class="small muted nowrap"><?= e(dtime($r['sent_at'])) ?></td>
<td class="right"><?php if ($r['status'] === 'failed'): ?><form method="post" action="<?= e(url('/admin/mail-log/' . $r['id'] . '/retry')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm">Retry</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?= $pg->links() ?>
