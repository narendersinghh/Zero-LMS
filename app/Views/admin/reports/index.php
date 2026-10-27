<?php
$layout = 'admin';
$title = 'Reports';
?>
<?php if (!$rows): ?><?php partial('empty', ['icon' => 'chart', 'heading' => 'No data yet', 'text' => 'Reports appear once you have courses and students.']); ?>
<?php else: ?>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>Course</th><th>Lessons</th><th>Enrolled</th><th>Started</th><th>Avg. progress</th><th>Completed</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
<td><a href="<?= e(url('/admin/reports/courses/' . $r['id'])) ?>"><b><?= e($r['title']) ?></b></a><?php if ($r['status'] !== 'published'): ?> <span class="badge badge-muted"><?= e($r['status']) ?></span><?php endif; ?></td>
<td><?= (int)$r['lessons'] ?></td>
<td><?= (int)$r['enrolled'] ?></td>
<td><?= (int)$r['started'] ?><small class="muted"> (<?= $r['enrolled'] ? round($r['started'] / $r['enrolled'] * 100) : 0 ?>%)</small></td>
<td class="w-160"><div class="progress"><i style="width:<?= (int)$r['avg'] ?>%"></i></div><small class="muted"><?= (int)$r['avg'] ?>%</small></td>
<td><?= (int)$r['completed'] ?></td>
<td class="right nowrap"><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/reports/courses/' . $r['id'])) ?>">Details</a><a class="btn-icon" href="<?= e(url('/admin/reports/courses/' . $r['id'] . '/export')) ?>" title="Export CSV"><?= icon('download') ?></a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php endif; ?>
