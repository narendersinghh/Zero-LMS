<?php $layout = 'admin'; $title = 'Settings'; include \App\Core\View::find('admin/settings/_tabs'); ?>
<div class="alert alert-info narrow"><?= icon('shield') ?><span>Uploaded videos are stored outside the public web folder and streamed only to enrolled students through short-lived, signed URLs bound to their session. Download controls are hidden and casual saving is blocked. <b>No web technology can fully prevent screen recording</b> — the moving watermark identifies the viewer to deter sharing.</span></div>
<form method="post" action="<?= e(url('/admin/settings/video')) ?>" class="card stack narrow" data-loading>
<?= csrf_field() ?>
<div class="grid-2">
<div class="field"><label>Playback link lifetime (minutes)</label><input type="number" min="10" max="1440" name="video_token_ttl" value="<?= e($s['video_token_ttl']) ?>"><small class="hint">Links stop working after this and when the user signs out.</small></div>
<div class="field"><label>Mark complete at (% watched)</label><input type="number" min="50" max="100" name="video_complete_percent" value="<?= e($s['video_complete_percent']) ?>"></div>
</div>
<label class="check"><input type="checkbox" name="video_watermark" value="1"<?= checked($s['video_watermark']) ?>><span>Show moving watermark with the viewer's email</span></label>
<label class="check"><input type="checkbox" name="video_block_direct" value="1"<?= checked($s['video_block_direct']) ?>><span>Block opening the video URL directly in a browser tab</span></label>
<div class="field"><label>Server file delivery</label><select name="video_sendfile"><option value="none"<?= selected('none', $s['video_sendfile']) ?>>PHP streaming (works everywhere)</option><option value="xsendfile"<?= selected('xsendfile', $s['video_sendfile']) ?>>Apache mod_xsendfile</option><option value="xaccel"<?= selected('xaccel', $s['video_sendfile']) ?>>Nginx X-Accel-Redirect (/protected-videos/)</option></select><small class="hint">Only change if your server supports it; it reduces PHP load for many viewers.</small></div>
<hr>
<div class="card-head"><div><h3>Bunny Stream (optional)</h3><small class="muted">Adaptive HLS streaming on a CDN with token-authenticated embeds — best for large audiences.</small></div></div>
<div class="grid-2"><div class="field"><label>Video library ID</label><input name="bunny_library_id" value="<?= e($s['bunny_library_id']) ?>"></div><div class="field"><label>Embed token authentication key</label><input type="password" name="bunny_token_key" placeholder="<?= $s['bunny_token_key'] ? '•••••••• (saved, encrypted)' : 'From Library → Security' ?>" autocomplete="new-password"><?php if ($s['bunny_token_key']): ?><label class="check mt-1"><input type="checkbox" name="clear_bunny_key" value="1"><span>Remove key</span></label><?php endif; ?></div></div>
<p class="small muted">In Bunny, enable <b>Embed view token authentication</b> and add your domain to <b>Allowed referrers</b>. For Vimeo, set the video privacy to “Hide from Vimeo” and restrict embedding to your domain.</p>
<div><button class="btn btn-primary">Save</button></div>
</form>
