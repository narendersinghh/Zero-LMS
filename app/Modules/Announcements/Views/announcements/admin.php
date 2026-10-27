<?php
$layout = 'admin';
$title = 'Announcements';
$actions = '<a class="btn btn-primary btn-sm" href="' . e(url('/admin/announcements/create')) . '">' . icon('plus') . 'New announcement</a>';
?>
<?php if (!$rows): ?>
<?php partial('empty', ['illus' => 'empty', 'icon' => 'bell', 'heading' => 'No announcements yet', 'text' => 'Share news with everyone or with one course. Students see it on their dashboard and get a notification.', 'action' => '<a class="btn btn-primary" href="' . e(url('/admin/announcements/create')) . '">' . icon('plus') . 'New announcement</a>']); ?>
<?php else: ?>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>Title</th><th>Audience</th><th>Status</th><th>Published</th><th class="right">Actions</th></tr></thead>
<tbody>
<?php foreach ($rows as $a): $sched = $a['status'] === 'published' && $a['published_at'] > now(); ?>
<tr>
<td><a href="<?= e(url('/admin/announcements/' . $a['id'] . '/edit')) ?>"><b><?= $a['pinned'] ? '📌 ' : '' ?><?= e($a['title']) ?></b></a><small class="muted d-block"><?= e(str_limit(strip_tags((string)$a['body']), 80)) ?></small></td>
<td><?= $a['course_title'] ? e($a['course_title']) : '<span class="badge badge-info">All students</span>' ?></td>
<td><?= $a['status'] === 'draft' ? '<span class="badge badge-muted">Draft</span>' : ($sched ? '<span class="badge badge-warning">Scheduled</span>' : '<span class="badge badge-success">Published</span>') ?><?= $a['notified_at'] ? ' <span class="badge badge-muted">' . icon('mail') . 'Emailed</span>' : '' ?></td>
<td class="small muted nowrap"><?= $a['published_at'] ? e(dtime($a['published_at'])) : '—' ?></td>
<td class="right nowrap"><a class="btn-icon" href="<?= e(url('/admin/announcements/' . $a['id'] . '/edit')) ?>" title="Edit"><?= icon('edit') ?></a>
<form method="post" action="<?= e(url('/admin/announcements/' . $a['id'] . '/delete')) ?>" class="inline"><?= csrf_field() ?><button class="btn-icon danger" title="Delete" data-confirm="Delete this announcement?"><?= icon('trash') ?></button></form></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?= $pg->links() ?>
<?php endif; ?>
