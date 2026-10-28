<?php
$layout = 'admin';
$title = 'Import results';
$crumbs = ['Students' => '/admin/students', 'Import CSV' => '/admin/import'];
?>
<div class="kpis">
<div class="kpi"><small>Rows in file</small><b><?= (int)$imp['total'] ?></b></div>
<div class="kpi"><small>New accounts</small><b class="success"><?= (int)$imp['created'] ?></b></div>
<div class="kpi"><small>Existing accounts</small><b><?= (int)$imp['existing'] ?></b></div>
<div class="kpi"><small>Skipped</small><b class="<?= $imp['skipped'] ? 'danger' : '' ?>"><?= (int)$imp['skipped'] ?></b></div>
</div>
<div class="card">
<div class="card-head"><h3><?= e($imp['filename']) ?></h3><span class="muted small"><?= e(dtime($imp['created_at'])) ?></span></div>
<?php if (!$errors): ?><p class="success"><?= icon('check-circle') ?> All rows imported successfully.</p>
<?php else: ?>
<div class="table-wrap"><table class="table table-sm"><thead><tr><th>Row</th><th>Value</th><th>Problem</th></tr></thead><tbody>
<?php foreach ($errors as $er): ?><tr><td><?= (int)$er['row'] ?></td><td><code><?= e($er['value']) ?: '(empty)' ?></code></td><td><?= e($er['error']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
<div class="row gap-2 mt-4"><a class="btn btn-primary" href="<?= e(url('/admin/students')) ?>">View students</a><a class="btn btn-ghost" href="<?= e(url('/admin/mail-log')) ?>">Email queue</a><a class="btn btn-ghost" href="<?= e(url('/admin/import')) ?>">New import</a></div>
</div>
