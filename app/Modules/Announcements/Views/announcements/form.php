<?php
$layout = 'admin';
$isNew = !$a['id'];
$title = $isNew ? 'New announcement' : 'Edit announcement';
$crumbs = ['Announcements' => '/admin/announcements'];
?>
<form method="post" action="<?= e(url($isNew ? '/admin/announcements' : '/admin/announcements/' . $a['id'])) ?>" class="form-layout" data-loading>
<?= csrf_field() ?>
<div class="card stack">
<div class="field"><label for="title">Title</label><input id="title" name="title" value="<?= e(old('title', $a['title'])) ?>" required maxlength="200" placeholder="e.g. Live Q&A this Saturday at 7 PM"><?= field_error('title') ?></div>
<div class="field"><label>Message</label><div class="editor" data-editor><textarea name="body" hidden><?= e(old('body', $a['body'])) ?></textarea></div></div>
</div>
<aside class="stack-lg">
<div class="card stack">
<div class="card-head"><h3>Publish</h3></div>
<div class="field"><label>Audience</label><select name="course_id"><option value="0">All students</option><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"<?= selected($c['id'], old('course_id', $a['course_id'])) ?>><?= e($c['title']) ?> students</option><?php endforeach; ?></select></div>
<div class="field"><label>Status</label><select name="status"><option value="published"<?= selected('published', old('status', $a['status'])) ?>>Published</option><option value="draft"<?= selected('draft', old('status', $a['status'])) ?>>Draft</option></select></div>
<div class="field"><label>Publish at <span class="muted">(optional)</span></label><input type="datetime-local" name="published_at" value="<?= e(old('published_at', to_local($a['published_at']))) ?>"><small class="hint">Leave empty to publish now. Future dates are scheduled (<?= e(setting('timezone')) ?>).</small></div>
<label class="check"><input type="checkbox" name="pinned" value="1"<?= checked(old('pinned', $a['pinned'])) ?>><span>Pin to top</span></label>
<label class="check"><input type="checkbox" name="notify" value="1" checked><span>Send in-app notification</span></label>
<label class="check"><input type="checkbox" name="email" value="1"<?= $a['notified_at'] ? ' disabled' : '' ?>><span>Also email students<?= $a['notified_at'] ? ' <small class="muted d-block">Already emailed ' . e(time_ago($a['notified_at'])) . '</small>' : '' ?></span></label>
<button class="btn btn-primary btn-block"><?= icon('check') ?><?= $isNew ? 'Save announcement' : 'Save changes' ?></button>
</div>
</aside>
</form>
