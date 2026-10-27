<?php
$layout = 'admin';
$isNew = !$lesson['id'];
$title = $isNew ? 'New lesson' : 'Edit lesson';
$crumbs = ['Courses' => '/admin/courses', $course['title'] => '/admin/courses/' . $course['id'] . '/curriculum'];
$v = fn($k) => old($k, $lesson[$k] ?? '');
$mode = old('release_mode', $lesson['release_at'] ? 'date' : ($lesson['release_day'] ? 'day' : 'immediate'));
$type = $v('type') ?: 'video';
if (!$isNew) {
    $actions = '<a class="btn btn-ghost btn-sm" href="' . e(url('/courses/' . $course['slug'] . '/lessons/' . $lesson['id'])) . '" target="_blank" rel="noopener">' . icon('eye') . 'Preview</a>';}
$dur = (int)$v('duration');
?>
<form method="post" action="<?= e(url($isNew ? '/admin/courses/' . $course['id'] . '/lessons' : '/admin/lessons/' . $lesson['id'])) ?>" class="form-layout" data-loading>
<?= csrf_field() ?>
<div class="stack-lg">
<div class="card stack">
<div class="field"><label for="title">Lesson title</label><input id="title" name="title" value="<?= e($v('title')) ?>" required maxlength="200" placeholder="Day 1: Getting started"><?= field_error('title') ?></div>
<div class="field"><label>Lesson type</label><div class="seg" data-seg="type"><?php foreach ($types as $k => $label): ?><label><input type="radio" name="type" value="<?= e($k) ?>"<?= checked($type === $k) ?>><span><?= e($label) ?></span></label><?php endforeach; ?></div></div>
<div class="field" data-show-when="type=video">
<label>Video</label>
<input type="hidden" name="video_id" value="<?= e($v('video_id')) ?>" data-video-id>
<div class="video-pick" data-video-pick data-search="<?= e(url('/admin/videos/search')) ?>">
<div class="video-current" data-video-current><?php if ($video): ?><span class="ltype"><?= icon('video') ?></span><span class="grow"><b><?= e($video['title']) ?></b><small class="muted"><?= e(\App\Controllers\Admin\VideoController::PROVIDERS[$video['provider']] ?? $video['provider']) ?><?= $video['duration'] ? ' · ' . e(human_duration($video['duration'])) : '' ?></small></span><?php else: ?><span class="muted">No video selected</span><?php endif; ?></div>
<button type="button" class="btn btn-ghost btn-sm" data-modal-open="video-modal"><?= icon('video') ?>Choose / upload</button>
</div>
<?= field_error('video_id') ?>
</div>
<div class="field" data-show-when="type=embed"><label>Embed URL</label><input type="url" name="embed_url" value="<?= e(old('embed_url', $settings['embed_url'] ?? '')) ?>" placeholder="https://…"><small class="hint">Any embeddable https page (Loom, Google Slides, Typeform…).</small><?= field_error('embed_url') ?></div>
<div class="field"><label>Short summary</label><input name="summary" value="<?= e($v('summary')) ?>" maxlength="500" placeholder="Shown under the title and in lists"></div>
<div class="field"><label>Lesson content / notes</label><div class="editor" data-editor><textarea name="content" hidden><?= e($v('content')) ?></textarea></div></div>
</div>
</div>
<aside class="stack-lg">
<div class="card stack">
<div class="card-head"><h3>Publish</h3></div>
<div class="field"><label>Status</label><select name="status"><option value="published"<?= selected('published', $v('status')) ?>>Published</option><option value="draft"<?= selected('draft', $v('status')) ?>>Draft (hidden)</option></select></div>
<div class="field"><label>Module</label><select name="module_id"><option value="0">— No module —</option><?php foreach ($modules as $m): ?><option value="<?= (int)$m['id'] ?>"<?= selected($m['id'], $v('module_id')) ?>><?= e($m['title']) ?><?= $m['release_day'] ? ' (Day ' . (int)$m['release_day'] . ')' : '' ?></option><?php endforeach; ?></select></div>
<div class="row gap-2 wrap">
<button class="btn btn-primary grow" name="after" value="<?= $isNew ? 'list' : 'list' ?>"><?= icon('check') ?>Save</button>
<button class="btn btn-ghost" name="after" value="<?= $isNew ? 'new' : 'stay' ?>"><?= $isNew ? 'Save & add another' : 'Save & stay' ?></button>
</div>
</div>
<div class="card stack">
<div class="card-head"><h3>Release schedule</h3></div>
<div class="seg seg-v" data-seg="release_mode">
<label><input type="radio" name="release_mode" value="immediate"<?= checked($mode === 'immediate') ?>><span>Immediately<?= '' ?></span></label>
<label><input type="radio" name="release_mode" value="day"<?= checked($mode === 'day') ?>><span>On day N of the course</span></label>
<label><input type="radio" name="release_mode" value="date"<?= checked($mode === 'date') ?>><span>On a fixed date & time</span></label>
</div>
<div class="field" data-show-when="release_mode=day"><label>Day number</label><input type="number" min="1" name="release_day" value="<?= e($v('release_day')) ?>"><small class="hint">Day 1 = the student's start date. Unlocks at <?= e(substr((string)$course['release_time'], 0, 5)) ?> (<?= e(setting('timezone')) ?>).</small></div>
<div class="field" data-show-when="release_mode=date"><label>Release at</label><input type="datetime-local" name="release_at" value="<?= e(old('release_at', to_local($lesson['release_at']))) ?>"><small class="hint">Same moment for everyone (<?= e(setting('timezone')) ?>).</small></div>
</div>
<div class="card stack">
<div class="card-head"><h3>Options</h3></div>
<div class="field"><label>Duration (mm:ss)</label><input name="duration" value="<?= $dur ? e(human_duration($dur)) : '' ?>" placeholder="Auto from video"></div>
<label class="check"><input type="checkbox" name="manual_complete" value="1"<?= checked(!empty($settings['manual_complete']) || $isNew) ?>><span>Show “Mark as complete” button</span></label>
<label class="check"><input type="checkbox" name="hide_quote" value="1"<?= checked(!empty($settings['hide_quote'])) ?>><span>Hide motivational quote</span></label>
</div>
</aside>
</form>
<?php if ($lesson['id']): ?><div class="mt-4"><?= hook_html('admin.lesson_panels', $lesson, $course) ?></div><?php endif; ?>
<?php partial('video_modal'); ?>
