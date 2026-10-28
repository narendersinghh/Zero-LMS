<?php
$layout = 'admin';
$title = 'Certificates';
$actions = '<button type="button" class="btn btn-primary btn-sm" data-modal-open="issue-modal">' . icon('plus') . 'Issue certificate</button><a class="btn btn-ghost btn-sm" href="' . e(url('/verify')) . '" target="_blank" rel="noopener">' . icon('shield') . 'Public verify page</a>';
?>
<p class="muted small"><?= icon('info') ?> Certificates are issued automatically when a student completes every lesson of a course. <?= (int)$total ?> active certificate(s).</p>
<form class="toolbar" method="get">
<div class="search grow"><?= icon('search') ?><input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search name, email, code or course…"></div>
<select name="course" data-autosubmit><option value="">All courses</option><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"<?= selected($c['id'], $_GET['course'] ?? '') ?>><?= e($c['title']) ?></option><?php endforeach; ?></select>
</form>
<?php if (!$rows): ?>
<?php partial('empty', ['illus' => 'empty', 'icon' => 'trophy', 'heading' => 'No certificates yet', 'text' => 'They appear here as students complete courses.']); ?>
<?php else: ?>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>Student</th><th>Course</th><th>Code</th><th>Issued</th><th>Status</th><th class="right">Actions</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
<td><a href="<?= e(url('/admin/students/' . $r['user_id'])) ?>"><b><?= e($r['name']) ?></b><small class="muted d-block"><?= e($r['email']) ?></small></a></td>
<td><?= e($r['course_title']) ?></td>
<td><code><?= e($r['code']) ?></code></td>
<td class="small muted nowrap"><?= e(dt($r['issued_at'])) ?></td>
<td><?= $r['revoked_at'] ? '<span class="badge badge-danger">Revoked</span>' : '<span class="badge badge-success">Valid</span>' ?></td>
<td class="right nowrap">
<a class="btn-icon" href="<?= e(url('/certificates/' . $r['code'])) ?>" target="_blank" rel="noopener" title="View"><?= icon('eye') ?></a>
<?php if ($r['revoked_at']): ?>
<form method="post" action="<?= e(url('/admin/certificates/' . $r['id'] . '/restore')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-ghost btn-sm">Restore</button></form>
<?php else: ?>
<form method="post" action="<?= e(url('/admin/certificates/' . $r['id'] . '/revoke')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" data-confirm="Revoke this certificate? Its verification link will show it as revoked.">Revoke</button></form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?= $pg->links() ?>
<?php endif; ?>
<dialog class="modal" id="issue-modal"><form method="post" action="<?= e(url('/admin/certificates')) ?>" class="stack"><?= csrf_field() ?>
<div class="modal-head"><h3>Issue a certificate</h3><button type="button" class="btn-icon" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
<div class="field"><label>Student email</label><input type="email" name="email" required></div>
<div class="field"><label>Course</label><select name="course_id" required><option value="">Choose…</option><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['title']) ?></option><?php endforeach; ?></select></div>
<label class="check"><input type="checkbox" name="notify" value="1" checked><span>Notify the student by email</span></label>
<div class="modal-foot"><button class="btn btn-primary">Issue</button></div></form></dialog>
