<?php
use App\Core\Media;
$layout = 'admin';
$title = $v['title'];
$crumbs = ['Courses & videos' => '/admin/courses', 'Video library' => '/admin/videos'];
$labels = array_keys(Media::HEIGHTS);
$have = array_column($rows, 'label');
$opts = '';
foreach ($labels as $l) {
    $opts .= '<option value="' . $l . '">' . $l . (in_array($l, $have, true) ? ' (replace)' : '') . '</option>';}
?>
<div class="form-layout">
<div class="stack-lg">
<div class="card stack">
<div class="card-head"><div><h3><?= icon('layers') ?> Quality versions</h3><small class="muted">Students pick between these in the player's gear menu, or leave it on Auto to match their screen. Export each version from your editor (for example HandBrake) at the same length.</small></div></div>
<div class="table-wrap"><table class="table table-sm"><thead><tr><th>Quality</th><th>File</th><th>Size</th><th></th></tr></thead><tbody>
<tr><td><b><?= e($v['quality_label'] ?: 'Original') ?></b> <span class="badge badge-muted">Original upload</span></td><td class="small muted mono-wrap"><?= e($v['source']) ?></td><td class="nowrap"><?= e(human_size((int)$v['size'])) ?></td><td></td></tr>
<?php foreach ($rows as $r): ?><tr><td><b><?= e($r['label']) ?></b></td><td class="small muted mono-wrap"><?= e($r['source']) ?></td><td class="nowrap"><?= e(human_size((int)$r['size'])) ?></td><td class="right"><form method="post" action="<?= e(url('/admin/videos/renditions/' . $r['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="btn-icon danger" title="Remove" data-confirm="Remove the <?= e($r['label']) ?> version?"><?= icon('trash') ?></button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
</div>
<div class="card stack">
<div class="card-head"><div><h3><?= icon('upload') ?> Add a version</h3><small class="muted">Choose the quality, then drop the file. Uploading the same quality again replaces it.</small></div></div>
<?php partial('uploader', ['maxChunk' => $maxChunk, 'single' => true, 'extra' => '<input type="hidden" name="rendition_of" value="' . (int)$v['id'] . '" data-up-field><div class="field"><label for="q-label">Quality of this file</label><select id="q-label" name="label" data-up-field required><option value="">Choose…</option>' . $opts . '</select></div>']); ?>
</div>
</div>
<aside class="stack-lg">
<div class="card stack">
<h3 class="m-0">Original file</h3>
<form method="post" action="<?= e(url('/admin/videos/' . $v['id'] . '/qualities')) ?>" class="stack"><?= csrf_field() ?>
<div class="field"><label for="ql">Its quality</label><select id="ql" name="quality_label"><option value="">Original</option><?php foreach ($labels as $l): ?><option value="<?= $l ?>"<?= selected($l, $v['quality_label']) ?>><?= $l ?></option><?php endforeach; ?></select><small class="hint">Shown in the player menu. Set it so the list sorts correctly.</small></div>
<button class="btn btn-ghost"><?= icon('check') ?>Save</button>
</form>
</div>
<div class="card small muted"><b>Tip:</b> 1080p for big screens, 720p as a safe default and 480p for slow mobile data covers most students. Each version is streamed with the same signed, expiring links as the original.</div>
</aside>
</div>
