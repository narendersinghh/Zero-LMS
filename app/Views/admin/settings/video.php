<?php $layout = 'admin'; $title = 'Settings'; include \App\Core\View::find('admin/settings/_tabs'); ?>
<div class="settings-hero tint-green"><span class="sh-ico"><?= icon('shield') ?></span><div><h2>Video security</h2><p>Uploads are stored outside the public folder and streamed only to enrolled students through short-lived links tied to their session. No website can fully prevent screen recording. The moving watermark identifies whoever shares a recording.</p></div></div>
<form method="post" action="<?= e(url('/admin/settings/video')) ?>" class="stack-lg" data-loading>
<?= csrf_field() ?>
<section class="set-sec">
<div class="set-desc"><h3>Protection</h3><p>Applies to uploaded videos and, where possible, to embedded players.</p></div>
<div class="card set-fields">
<div class="set-row"><div><b>Moving watermark</b><small>Shows the student's email over the video.</small></div><label class="switch"><input type="checkbox" name="video_watermark" value="1"<?= checked($s['video_watermark']) ?>><span></span></label></div>
<div class="set-row"><div><b>Block opening the video URL in a tab</b><small>Stops "open video in new tab" and direct downloads.</small></div><label class="switch"><input type="checkbox" name="video_block_direct" value="1"<?= checked($s['video_block_direct']) ?>><span></span></label></div>
<div class="grid-2">
<div class="field"><label>Playback link lifetime (minutes)</label><input type="number" min="10" max="1440" name="video_token_ttl" value="<?= e($s['video_token_ttl']) ?>"><small class="hint">Links also stop working when the student signs out.</small></div>
<div class="field"><label>Mark lesson complete at (% watched)</label><input type="number" min="50" max="100" name="video_complete_percent" value="<?= e($s['video_complete_percent']) ?>"></div>
</div>
</div>
</section>
<section class="set-sec">
<div class="set-desc"><h3>Server delivery</h3><p>Leave on "PHP streaming" unless your host supports X-Sendfile. It reduces server load with many viewers.</p></div>
<div class="card set-fields">
<div class="field"><label>Delivery method</label><select name="video_sendfile"><option value="none"<?= selected('none', $s['video_sendfile']) ?>>PHP streaming (works on all hosting)</option><option value="xsendfile"<?= selected('xsendfile', $s['video_sendfile']) ?>>Apache mod_xsendfile</option><option value="xaccel"<?= selected('xaccel', $s['video_sendfile']) ?>>Nginx X-Accel-Redirect (/protected-videos/)</option></select></div>
</div>
</section>
<section class="set-sec">
<div class="set-desc"><h3>Bunny Stream <span class="badge badge-muted">optional</span></h3><p>Adaptive HD streaming on a global CDN with signed, expiring embed links. The best choice for large audiences. In Bunny: enable <b>Embed view token authentication</b> and add your domain under <b>Allowed referrers</b>.</p></div>
<div class="card set-fields">
<div class="grid-2"><div class="field"><label>Video library ID</label><input name="bunny_library_id" value="<?= e($s['bunny_library_id']) ?>" inputmode="numeric"></div>
<div class="field"><label>Token authentication key</label><input type="password" name="bunny_token_key" placeholder="<?= $s['bunny_token_key'] ? '•••••••• saved' : 'Library → Security' ?>" autocomplete="new-password"><?php if ($s['bunny_token_key']): ?><label class="check mt-1"><input type="checkbox" name="clear_bunny_key" value="1"><span>Remove saved key</span></label><?php endif; ?></div></div>
<p class="small muted m-0"><?= icon('info') ?> Vimeo: set privacy to “Hide from Vimeo” and restrict embeds to your domain. YouTube lessons play in Zeroes' own player without YouTube's title, logo or links.</p>
</div>
</section>
<div class="set-actions"><button class="btn btn-primary"><?= icon('check') ?>Save video settings</button></div>
</form>
<?php include \App\Core\View::find('admin/settings/_end'); ?>
