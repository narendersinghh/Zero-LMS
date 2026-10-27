<?php
use App\Controllers\Admin\VideoController;
$layout = 'admin';
$title = 'Courses & videos';
$section = 'videos';
$actions = '<button type="button" class="btn btn-primary btn-sm" data-modal-open="upload-modal">' . icon('upload') . 'Upload videos</button>';
include \App\Core\View::find('admin/courses/_tabs');
?>
<div class="how">
<div class="how-step"><span class="lt-ico" style="background:var(--p-blue)"><?= icon('upload') ?></span><div><b>1. Add videos here</b><small>Upload MP4s (stored privately) or link Bunny, Vimeo or YouTube. Large files upload in chunks.</small></div><span class="how-n">1</span></div>
<div class="how-step"><span class="lt-ico" style="background:var(--p-yellow)"><?= icon('layers') ?></span><div><b>2. Attach to lessons</b><small>In Courses → Curriculum → lesson, pick a video. One video can be reused in several courses.</small></div><span class="how-n">2</span></div>
<div class="how-step"><span class="lt-ico" style="background:var(--p-green)"><?= icon('shield') ?></span><div><b>3. Students watch securely</b><small>Signed, expiring links, no download button and a moving watermark, only for enrolled students.</small></div><span class="how-n">3</span></div>
</div>
<div class="kpis kpis-3">
<div class="kpi"><span class="kpi-ico"><?= icon('video') ?></span><small>Videos</small><b><?= number_format($pg->total) ?></b></div>
<div class="kpi"><span class="kpi-ico"><?= icon('shield') ?></span><small>Protected storage used</small><b><?= e(human_size($disk)) ?></b><span class="kpi-sub">storage/videos (not web accessible)</span></div>
<div class="kpi"><span class="kpi-ico"><?= icon('upload') ?></span><small>Waiting in FTP folder</small><b><?= count($incoming) ?></b><span class="kpi-sub"><form method="post" action="<?= e(url('/admin/videos/scan')) ?>" class="inline"><?= csrf_field() ?><button class="link-btn">Import now</button></form></span></div>
</div>
<div class="toolbar">
<div class="tabs"><a class="tab<?= $provider === '' ? ' active' : '' ?>" href="?">All</a><?php foreach (VideoController::PROVIDERS as $k => $v): ?><a class="tab<?= $provider === $k ? ' active' : '' ?>" href="?provider=<?= e($k) ?>"><?= e($v) ?></a><?php endforeach; ?></div>
<form class="search" method="get"><?= icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search videos…"></form>
</div>
<?php if (!$videos): ?>
<?php partial('empty', ['icon' => 'video', 'heading' => 'No videos yet', 'text' => 'Upload MP4 files (stored privately and streamed with expiring tokens) or connect Bunny Stream / Vimeo / YouTube videos.', 'action' => '<button type="button" class="btn btn-primary" data-modal-open="upload-modal">' . icon('upload') . 'Upload videos</button>']); ?>
<?php else: ?>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>Video</th><th>Source</th><th>Duration</th><th>Size</th><th>Used in lessons</th><th>Added</th><th></th></tr></thead>
<tbody>
<?php foreach ($videos as $v): ?>
<tr>
<?php $ext = $v['provider'] !== 'local'; $link = $v['provider'] === 'youtube' ? 'https://youtu.be/' . $v['source'] : ($v['provider'] === 'vimeo' ? 'https://vimeo.com/' . $v['source'] : $v['source']); $dur = (int)$v['duration']; ?>
<td><div class="vid-title"><span class="vid-thumb"<?= $v['provider'] === 'youtube' ? ' style="background-image:url(\'https://i.ytimg.com/vi/' . e(\App\Core\Media::youtubeId($v['source'])) . '/mqdefault.jpg\')"' : '' ?>><?= icon('play') ?></span><span class="grow"><b><?= e($v['title']) ?></b><?php if ($ext): ?><small class="muted mono-wrap"><?= e(str_limit($link, 48)) ?></small><?php endif; ?></span></div></td>
<td><span class="badge badge-<?= $v['provider'] === 'local' ? 'accent' : 'muted' ?>"><?= e(VideoController::PROVIDERS[$v['provider']] ?? $v['provider']) ?></span></td>
<td><?= $v['duration'] ? e(human_duration($v['duration'])) : '<span class="muted">-</span>' ?></td>
<td class="nowrap"><?= $v['size'] ? e(human_size($v['size'])) : '<span class="muted">-</span>' ?></td>
<td><?php if (empty($usedIn[$v['id']])): ?><span class="badge badge-warning">Not used yet</span><?php else: ?><div class="used-in"><?php foreach (array_slice($usedIn[$v['id']], 0, 3) as $l): ?><a href="<?= e(url('/admin/lessons/' . $l['id'] . '/edit')) ?>"><?= e(str_limit($l['course_title'], 22)) ?> › <?= e(str_limit($l['title'], 28)) ?></a><?php endforeach; ?><?php if (count($usedIn[$v['id']]) > 3): ?><small class="muted">+<?= count($usedIn[$v['id']]) - 3 ?> more</small><?php endif; ?></div><?php endif; ?></td>
<td class="muted small nowrap"><?= e(dt($v['created_at'])) ?></td>
<td class="right nowrap">
<button type="button" class="btn-icon" title="Edit" data-modal-open="edit-modal" data-fill="<?= e(json_encode(['_action' => url('/admin/videos/' . $v['id']), 'title' => $v['title'], 'duration' => $dur ? ($dur >= 3600 ? gmdate('G:i:s', $dur) : gmdate('i:s', $dur)) : '', 'provider' => $ext ? $v['provider'] : '', 'source' => $ext ? $link : '', '_ext' => $ext])) ?>"><?= icon('edit') ?></button>
<?php if ($v['provider'] === 'local'): ?><button type="button" class="btn-icon" data-preview-video="<?= e(url('/admin/videos/' . $v['id'] . '/stream')) ?>" title="Preview"><?= icon('play') ?></button><?php endif; ?>
<form method="post" action="<?= e(url('/admin/videos/' . $v['id'] . '/delete')) ?>" class="inline"><?= csrf_field() ?><button class="btn-icon danger" title="Delete" data-confirm="<?= $v['used'] ? 'This video is used in ' . (int)$v['used'] . ' lesson(s). ' : '' ?>Delete this video<?= $v['provider'] === 'local' ? ' file' : '' ?> permanently?"><?= icon('trash') ?></button></form>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?= $pg->links() ?>
<?php endif; ?>
<div class="card mt-4 small muted"><b>Tip:</b> For very large files you can also upload via FTP/File Manager into <code>storage/videos/incoming/</code> and click “Import now”. For the strongest protection and adaptive streaming at scale, use Bunny Stream with token authentication (Settings → Video security). No method can fully prevent screen recording. The dynamic watermark discourages sharing.</div>
<dialog class="modal modal-lg" id="upload-modal">
<div class="modal-head"><h3>Upload videos</h3><button type="button" class="btn-icon" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
<?php partial('uploader', ['maxChunk' => $maxChunk]); ?>
<hr>
<h4>Or add an external video</h4>
<form method="post" action="<?= e(url('/admin/videos/external')) ?>" class="stack"><?= csrf_field() ?>
<div class="grid-3"><div class="field"><label>Provider</label><select name="provider"><option value="bunny">Bunny Stream</option><option value="vimeo">Vimeo</option><option value="youtube">YouTube</option></select></div><div class="field"><label>Title</label><input name="title" required></div><div class="field"><label>Duration (mm:ss)</label><input name="duration"></div></div>
<div class="field"><label>Video URL or ID</label><input name="source" required></div>
<div><button class="btn btn-primary">Add video</button></div>
</form>
</dialog>
<dialog class="modal" id="edit-modal">
<div class="modal-head"><h3>Edit video</h3><button type="button" class="btn-icon" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
<form method="post" action="" class="stack"><?= csrf_field() ?>
<div class="grid-2 ev-row"><div class="field"><label for="ev-title">Title</label><input id="ev-title" name="title" required maxlength="200"></div><div class="field"><label for="ev-dur">Duration (mm:ss)</label><input id="ev-dur" name="duration" placeholder="12:30"></div></div>
<div data-ext-only class="stack">
<div class="field"><label for="ev-prov">Provider</label><select id="ev-prov" name="provider"><option value="bunny">Bunny Stream</option><option value="vimeo">Vimeo</option><option value="youtube">YouTube</option></select></div>
<div class="field"><label for="ev-src">Video URL or ID</label><input id="ev-src" name="source" placeholder="https://youtu.be/..."><small class="hint">Lessons using this video switch to the new link right away.</small></div>
</div>
<p class="small muted m-0" data-local-only><?= icon('shield') ?> Uploaded files cannot be swapped. Upload the new file and pick it in the lesson instead.</p>
<div class="actions"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary"><?= icon('check') ?>Save changes</button></div>
</form>
</dialog>
<dialog class="modal modal-lg" id="preview-modal"><div class="modal-head"><h3>Preview</h3><button type="button" class="btn-icon" data-modal-close aria-label="Close"><?= icon('x') ?></button></div><video controls controlslist="nodownload" class="w-100" data-preview-player></video></dialog>
