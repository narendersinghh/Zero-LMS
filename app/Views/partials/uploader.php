<div class="uploader" data-uploader data-url="<?= e(url('/admin/videos/upload-chunk')) ?>" data-chunk="<?= (int)$maxChunk ?>"<?= !empty($pick) ? ' data-pick' : '' ?>>
<?= $extra ?? '' ?>
<label class="dropzone" data-dropzone>
<input type="file" accept="video/mp4,video/webm,video/quicktime,video/x-m4v,audio/mpeg,audio/mp4" <?= empty($single) ? ' multiple' : '' ?> hidden data-file>
<?= icon('upload') ?>
<b>Drop video files here or click to browse</b>
<small class="muted">MP4 (H.264/AAC) recommended · Large files are uploaded in <?= e(human_size($maxChunk)) ?> chunks, so server upload limits don't apply.</small>
</label>
<div class="upload-queue" data-queue></div>
</div>
