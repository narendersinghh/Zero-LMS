<?php
use App\Modules\Resources\Controller as R;
$layout = 'admin';
$title = 'Resources · ' . $lesson['title'];
$crumbs = ['Courses' => '/admin/courses', $lesson['course_title'] => '/admin/courses/' . $lesson['course_id'] . '/curriculum', 'Lesson' => '/admin/lessons/' . $lesson['id'] . '/edit'];
?>
<div class="form-layout">
<div class="stack-lg">
<?php if (!$rows): ?><?php partial('empty', ['icon' => 'download', 'heading' => 'No resources yet', 'text' => 'Upload worksheets, slides, PDFs or audio, or link to external material. Students see them below the lesson.']); ?>
<?php else: ?>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>Resource</th><th>Type</th><th>Downloads</th><th class="right">Actions</th></tr></thead>
<tbody>
<?php foreach ($rows as $n => $r): ?>
<tr><td><span class="row gap-2"><span class="ltype"><?= icon(R::icon($r['mime'], $r['url'])) ?></span><span><b><?= e($r['title']) ?></b><small class="muted d-block"><?= $r['url'] ? e(str_limit($r['url'], 60)) : e($r['original_name']) ?></small></span></span></td>
<td class="small"><?= $r['url'] ? 'Link' : e(strtoupper(pathinfo((string)$r['original_name'], PATHINFO_EXTENSION))) . ' · ' . e(human_size($r['size'])) ?></td>
<td><?= (int)$r['downloads'] ?></td>
<td class="right nowrap"><form method="post" class="inline" action="<?= e(url('/admin/resources/' . $r['id'] . '/move')) ?>"><?= csrf_field() ?><button class="btn-icon" name="dir" value="up" title="Move up"<?= $n === 0 ? ' disabled' : '' ?>><?= icon('chevron-up') ?></button><button class="btn-icon" name="dir" value="down" title="Move down"<?= $n === count($rows) - 1 ? ' disabled' : '' ?>><?= icon('chevron-down') ?></button></form>
<a class="btn-icon" href="<?= e(url('/resources/' . $r['id'] . '/download')) ?>" title="Download"><?= icon('download') ?></a>
<form method="post" class="inline" action="<?= e(url('/admin/resources/' . $r['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="btn-icon danger" title="Delete" data-confirm="Delete this resource?"><?= icon('trash') ?></button></form></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php endif; ?>
</div>
<aside class="stack-lg">
<form method="post" action="<?= e(url('/admin/lessons/' . $lesson['id'] . '/resources')) ?>" enctype="multipart/form-data" class="card stack" data-loading>
<?= csrf_field() ?><input type="hidden" name="kind" value="file">
<div class="card-head"><h3><?= icon('upload') ?> Upload a file</h3></div>
<div class="field"><label>File</label><input type="file" name="file" required accept=".<?= e(implode(',.', $exts)) ?>"><small class="hint"><?= e(strtoupper(implode(', ', $exts))) ?> · max <?= e(human_size($maxUpload)) ?></small></div>
<div class="field"><label>Title <span class="muted">(optional)</span></label><input name="title" maxlength="200" placeholder="Defaults to the file name"></div>
<button class="btn btn-primary btn-block"><?= icon('upload') ?>Upload</button>
</form>
<form method="post" action="<?= e(url('/admin/lessons/' . $lesson['id'] . '/resources')) ?>" class="card stack">
<?= csrf_field() ?><input type="hidden" name="kind" value="link">
<div class="card-head"><h3><?= icon('external') ?> Add a link</h3></div>
<div class="field"><label>URL</label><input type="url" name="url" required placeholder="https://"></div>
<div class="field"><label>Title</label><input name="title" maxlength="200"></div>
<button class="btn btn-ghost btn-block"><?= icon('plus') ?>Add link</button>
</form>
</aside>
</div>
