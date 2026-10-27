<?php $maxChunk = min(8 * 1048576, (int)(\App\Core\Uploads::maxUploadBytes() * 0.9)); ?>
<dialog class="modal modal-lg" id="video-modal">
<div class="modal-head"><h3>Choose a video</h3><button type="button" class="btn-icon" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
<div class="tabs" data-tabs><button type="button" class="tab active" data-tab="vm-lib">Library</button><button type="button" class="tab" data-tab="vm-up">Upload</button><button type="button" class="tab" data-tab="vm-ext">Bunny / Vimeo / YouTube</button></div>
<div class="tab-panel" id="vm-lib">
<div class="search mb-3"><?= icon('search') ?><input type="search" placeholder="Search videos…" data-video-search></div>
<div class="video-list" data-video-list><p class="muted small">Loading…</p></div>
</div>
<div class="tab-panel" id="vm-up" hidden>
<?php partial('uploader', ['maxChunk' => $maxChunk, 'pick' => true]); ?>
</div>
<div class="tab-panel" id="vm-ext" hidden>
<form class="stack" data-external-form action="<?= e(url('/admin/videos/external')) ?>">
<div class="grid-2"><div class="field"><label>Provider</label><select name="provider"><option value="bunny">Bunny Stream (recommended, token-signed)</option><option value="vimeo">Vimeo (private / unlisted)</option><option value="youtube">YouTube (unlisted)</option></select></div><div class="field"><label>Title</label><input name="title" required></div></div>
<div class="field"><label>Video URL or ID</label><input name="source" required placeholder="Paste the video link or ID"></div>
<div class="field"><label>Duration (mm:ss)</label><input name="duration" placeholder="Optional"></div>
<div><button class="btn btn-primary">Add & select</button></div>
</form>
</div>
</dialog>
