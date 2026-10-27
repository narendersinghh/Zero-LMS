<?php $layout = 'admin'; $title = 'Audit log'; ?>
<form class="toolbar" method="get">
<div class="search grow"><?= icon('search') ?><input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search action, details, email or IP…"></div>
<select name="action" data-autosubmit><option value="">All areas</option><?php foreach ($groups as $g): ?><option value="<?= e($g) ?>"<?= selected($g, $_GET['action'] ?? '') ?>><?= e(ucfirst(str_replace('_', ' ', $g))) ?></option><?php endforeach; ?></select>
<input type="date" name="from" value="<?= e($_GET['from'] ?? '') ?>" aria-label="From">
<input type="date" name="to" value="<?= e($_GET['to'] ?? '') ?>" aria-label="To">
<button class="btn btn-ghost">Filter</button>
</form>
<div class="card p-0"><div class="table-wrap"><table class="table table-sm">
<thead><tr><th>When</th><th>User</th><th>Action</th><th>Target</th><th>Details</th><th>IP</th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="6" class="center muted">No entries.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?>
<tr><td class="nowrap small"><?= e(dtime($r['created_at'])) ?></td><td><?= $r['user_id'] ? '<a href="' . e(url('/admin/logs', ['user' => $r['user_id']])) . '">' . e($r['name'] ?: '#' . $r['user_id']) . '</a>' : '<span class="muted">System</span>' ?></td>
<td><code><?= e($r['action']) ?></code></td><td class="small"><?= $r['entity_type'] ? e($r['entity_type']) . ' #' . (int)$r['entity_id'] : '' ?></td>
<td class="small muted mono-wrap"><?= e(str_limit((string)$r['details'], 140)) ?></td><td class="small muted nowrap"><?= e($r['ip']) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?= $pg->links() ?>
