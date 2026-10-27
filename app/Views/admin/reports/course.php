<?php
$layout = 'admin';
$title = $course['title'];
$crumbs = ['Reports' => '/admin/reports'];
$actions = '<a class="btn btn-ghost btn-sm" href="' . e(url('/admin/reports/courses/' . $course['id'] . '/export')) . '">' . icon('download') . 'Export CSV</a>';
$labels = ['a' => 'Not started', 'b' => '1–24%', 'c' => '25–49%', 'd' => '50–74%', 'e' => '75–99%', 'f' => 'Completed'];
$dmax = max(1, max(array_map('intval', array_values($dist ?: ['a' => 0]))));
?>
<div class="grid-2">
<div class="card">
<div class="card-head"><h3>Progress distribution</h3><span class="muted small"><?= (int)$pg->total ?> students · <?= (int)$total ?> lessons</span></div>
<div class="hbars"><?php foreach ($labels as $k => $lbl): $n = (int)($dist[$k] ?? 0); ?><div class="hbar"><span><?= e($lbl) ?></span><div><i style="width:<?= round($n / $dmax * 100) ?>%"></i></div><b><?= $n ?></b></div><?php endforeach; ?></div>
</div>
<div class="card">
<div class="card-head"><h3>Lesson engagement</h3></div>
<div class="table-wrap scroll-y"><table class="table table-sm"><thead><tr><th>Lesson</th><th>Viewers</th><th>Completed</th><th>Avg watched</th></tr></thead><tbody>
<?php foreach ($lessonStats as $l): ?><tr><td><?= $l['release_day'] ? '<span class="muted">D' . (int)$l['release_day'] . '</span> ' : '' ?><?= e(str_limit($l['title'], 48)) ?></td><td><?= (int)$l['viewers'] ?></td><td><?= (int)$l['completions'] ?></td><td><?= (int)$l['avg_pct'] ?>%</td></tr><?php endforeach; ?>
</tbody></table></div>
</div>
</div>
<div class="card p-0 mt-4"><div class="table-wrap"><table class="table">
<thead><tr><th>Student</th><th>Progress</th><th>Access</th><th>Last activity</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): $pct = $total ? floor($r['done'] / $total * 100) : 0; ?>
<tr>
<td><a href="<?= e(url('/admin/students/' . $r['id'])) ?>"><b><?= e($r['name']) ?></b><small class="muted d-block"><?= e($r['email']) ?></small></a></td>
<td class="w-200"><div class="progress"><i style="width:<?= (int)$pct ?>%"></i></div><small class="muted"><?= (int)$r['done'] ?>/<?= (int)$total ?> · <?= (int)$pct ?>%</small></td>
<td class="small"><?= e(dt($r['starts_at'])) ?> → <?= $r['expires_at'] ? e(dt($r['expires_at'])) : 'Lifetime' ?></td>
<td class="small muted"><?= $r['last_view'] ? e(time_ago($r['last_view'])) : 'Not started' ?></td>
<td><?= $r['completed_at'] ? '<span class="badge badge-success">Completed</span>' : ($r['status'] === 'active' ? '<span class="badge badge-muted">In progress</span>' : '<span class="badge badge-warning">' . e($r['status']) . '</span>') ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?= $pg->links() ?>
