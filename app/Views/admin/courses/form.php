<?php
use App\Services\Courses;
$layout = 'admin';
$isNew = !$course['id'];
$title = $isNew ? 'New course' : 'Edit course';
$crumbs = ['Courses' => '/admin/courses'];
$v = fn($k) => old($k, $course[$k] ?? '');
if (!$isNew) {
    $actions = '<a class="btn btn-ghost btn-sm" href="' . e(url('/courses/' . $course['slug'])) . '" target="_blank" rel="noopener">' . icon('eye') . 'Preview</a><a class="btn btn-ghost btn-sm" href="' . e(url('/admin/courses/' . $course['id'] . '/curriculum')) . '">' . icon('layers') . 'Curriculum</a>';}
?>
<?php if (!$isNew): ?><div class="subnav"><a class="active" href="<?= e(url('/admin/courses/' . $course['id'] . '/edit')) ?>">Details & schedule</a><a href="<?= e(url('/admin/courses/' . $course['id'] . '/curriculum')) ?>">Curriculum</a><a href="<?= e(url('/admin/enrollments', ['course' => $course['id']])) ?>">Enrollments</a><a href="<?= e(url('/admin/reports/courses/' . $course['id'])) ?>">Report</a><?php foreach (\App\Core\Hooks::filter('admin.course_tabs', [], $course) as $tl => $tu): ?><a href="<?= e(url($tu)) ?>"><?= e($tl) ?></a><?php endforeach; ?></div><?php endif; ?>
<form method="post" action="<?= e(url($isNew ? '/admin/courses' : '/admin/courses/' . $course['id'])) ?>" enctype="multipart/form-data" class="form-layout" data-loading>
<?= csrf_field() ?>
<div class="stack-lg">
<div class="card stack">
<div class="card-head"><h3>Course details</h3></div>
<div class="field"><label for="title">Title</label><input id="title" name="title" value="<?= e($v('title')) ?>" required maxlength="200" data-slug-source><?= field_error('title') ?></div>
<div class="field"><label for="subtitle">Subtitle</label><input id="subtitle" name="subtitle" value="<?= e($v('subtitle')) ?>" maxlength="255" placeholder="A one-line promise of what students will achieve"></div>
<div class="field"><label>Description</label><div class="editor" data-editor><textarea name="description" hidden><?= e($v('description')) ?></textarea></div></div>
<div class="grid-2">
<div class="field"><label for="category">Category</label><input id="category" name="category" value="<?= e($v('category')) ?>" maxlength="100" placeholder="e.g. Webinar bonus"></div>
<div class="field"><label for="instructor_name">Instructor</label><input id="instructor_name" name="instructor_name" value="<?= e($v('instructor_name')) ?>" maxlength="150"></div>
</div>
<div class="field"><label for="slug">URL slug</label><div class="input-prefix"><span><?= e(url('/courses/')) ?></span><input id="slug" name="slug" value="<?= e($v('slug')) ?>" maxlength="140" placeholder="auto-generated" data-slug-target></div></div>
</div>
<div class="card stack">
<div class="card-head"><div><h3>Schedule & access</h3><small class="muted">Lessons unlock by day relative to each student's start. All times use <?= e(setting('timezone')) ?>.</small></div></div>
<div class="grid-3">
<div class="field"><label for="start_date">Course start date</label><input id="start_date" type="date" name="start_date" value="<?= e($v('start_date')) ?>"><small class="hint">Optional. Day 1 never starts before this date (e.g. a live cohort).</small></div>
<div class="field"><label for="release_time">Daily unlock time</label><input id="release_time" type="time" name="release_time" value="<?= e(substr((string)$v('release_time'), 0, 5)) ?>"><small class="hint">When each new day's lessons unlock.</small></div>
<div class="field"><label for="access_days">Access duration (days)</label><input id="access_days" type="number" min="0" name="access_days" value="<?= e($v('access_days')) ?>" placeholder="Unlimited"><small class="hint">From the student's start date.</small></div>
</div>
<div class="field"><label for="end_date">Hard end date</label><input id="end_date" type="date" name="end_date" value="<?= e($v('end_date')) ?>"><small class="hint">Optional. Access ends for everyone on this date, whichever comes first. Applies to new enrollments; extend existing ones in Enrollments.</small><?= field_error('end_date') ?></div>
</div>
<div class="card stack">
<div class="card-head"><div><h3>Pricing & shareable link</h3><small class="muted">Anyone with the link can view the course, create an account and pay (UPI).</small></div><label class="switch" title="Public sales page"><input type="checkbox" name="is_public" value="1"<?= checked($v('is_public')) ?>><span></span></label></div>
<div class="grid-3">
<div class="field"><label for="price">Price (₹)</label><input id="price" type="number" min="0" step="1" name="price" value="<?= e($v('price') !== null && $v('price') !== '' ? (float)$v('price') : '') ?>" placeholder="0 = free"></div>
<div class="field"><label for="sale_price">Offer price (₹)</label><input id="sale_price" type="number" min="0" step="1" name="sale_price" value="<?= e($v('sale_price') !== null && $v('sale_price') !== '' ? (float)$v('sale_price') : '') ?>" placeholder="Optional"><small class="hint">Shown with the original price struck through.</small></div>
<div class="field"><label>Share link</label><?php if (!$isNew): ?><div class="copy-box"><code><?= e(url('/c/' . $course['slug'], [], true)) ?></code><button type="button" class="btn-icon" data-copy="<?= e(url('/c/' . $course['slug'], [], true)) ?>" title="Copy link"><?= icon('copy') ?></button></div><?php else: ?><small class="hint">Available after saving.</small><?php endif; ?></div>
</div>
<div class="field"><label for="highlights">Highlights (one per line)</label><textarea id="highlights" name="highlights" rows="4" placeholder="21 daily video lessons&#10;Watch on any device&#10;Certificate of completion"><?= e($v('highlights')) ?></textarea></div>
</div>
<div class="card stack">
<div class="card-head"><div><h3>Card style</h3><small class="muted">Used when there is no cover image.</small></div></div>
<div class="field"><label>Color</label><div class="swatches"><?php foreach (COURSE_TINTS as $t): ?><label class="swatch-opt tint-<?= $t ?>"><input type="radio" name="color" value="<?= $t ?>"<?= checked(course_tint($course + ['id' => 0]) === $t && ($v('color') === $t || (!$v('color') && $t === 'yellow'))) ?>><span><?= ucfirst($t) ?></span></label><?php endforeach; ?></div></div>
<div class="field"><label>Illustration</label><div class="illus-pick"><?php foreach (COURSE_ILLUS as $i): ?><label><input type="radio" name="illustration" value="<?= $i ?>"<?= checked($v('illustration') === $i) ?>><img src="<?= e(asset('img/illus/course-' . $i . '.svg')) ?>" alt="<?= e($i) ?>"></label><?php endforeach; ?></div></div>
</div>
<div class="card stack">
<div class="card-head"><div><h3>Motivational quotes</h3><small class="muted">One per line, optionally “Quote — Author”. A different one shows each day. Leave empty to use the global list.</small></div></div>
<textarea name="quotes" rows="5" placeholder="Small steps every day. — Anonymous"><?= e($v('quotes')) ?></textarea>
</div>
</div>
<aside class="stack-lg">
<div class="card stack">
<div class="card-head"><h3>Publish</h3></div>
<div class="field"><label>Status</label><select name="status"><?php foreach (Courses::STATUSES as $k => $lbl): ?><option value="<?= $k ?>"<?= selected($k, $v('status')) ?>><?= e($lbl) ?></option><?php endforeach; ?></select><small class="hint">Only published courses are visible to enrolled students.</small></div>
<div class="field"><label>Sort order</label><input type="number" name="sort_order" value="<?= e($v('sort_order')) ?>"></div>
<button class="btn btn-primary btn-block"><?= icon('check') ?><?= $isNew ? 'Create course' : 'Save changes' ?></button>
</div>
<div class="card stack">
<div class="card-head"><h3>Cover image</h3></div>
<div class="cover-preview" data-image-preview<?= $course['cover_image'] ? ' style="background-image:url(\'' . e(upload_url($course['cover_image'])) . '\')"' : '' ?>><?= $course['cover_image'] ? '' : icon('upload') ?></div>
<input type="file" name="cover_image" accept="image/png,image/jpeg,image/webp" data-preview-input>
<small class="hint">1600×900 recommended. Max 5 MB.</small>
<?php if ($course['cover_image']): ?><label class="check"><input type="checkbox" name="remove_cover" value="1"><span>Remove cover</span></label><?php endif; ?>
</div>
<?php if (!$isNew): ?>
<div class="card stack small">
<div class="card-head"><h3>At a glance</h3></div>
<dl class="dl"><dt>Enrollments</dt><dd><?= (int)($stats['enrolled'] ?? 0) ?> (<?= (int)($stats['active'] ?? 0) ?> active)</dd><dt>Created</dt><dd><?= e(dt($course['created_at'])) ?></dd><dt>Updated</dt><dd><?= e(time_ago($course['updated_at'])) ?></dd></dl>
</div>
<?php endif; ?>
</aside>
</form>
<?php if (!$isNew && can('courses.delete')): ?>
<div class="card danger-zone mt-5">
<div><h3>Delete course</h3><p class="muted small">Permanently deletes the course, its modules, lessons, enrollments and progress. Videos stay in the library. Type <code><?= e($course['slug']) ?></code> to confirm.</p></div>
<form method="post" action="<?= e(url('/admin/courses/' . $course['id'] . '/delete')) ?>" class="row gap-2 wrap"><?= csrf_field() ?><input name="confirm" placeholder="<?= e($course['slug']) ?>" required><button class="btn btn-danger" data-confirm="This cannot be undone. Delete permanently?"><?= icon('trash') ?>Delete</button></form>
</div>
<?php endif; ?>
