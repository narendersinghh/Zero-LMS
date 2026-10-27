<?php
$layout = 'admin';
$title = 'CSV import & bulk enrollment';
$actions = '<a class="btn btn-ghost btn-sm" href="' . e(url('/admin/import/sample')) . '">' . icon('download') . 'Sample CSV</a>';
?>
<form method="post" action="<?= e(url('/admin/import')) ?>" enctype="multipart/form-data" class="form-layout" data-loading>
<?= csrf_field() ?>
<div class="stack-lg">
<div class="card stack">
<div class="card-head"><div><h3>1. Upload CSV</h3><small class="muted">Columns: <code>email</code> (required), <code>name</code> or <code>first_name</code>/<code>last_name</code>, <code>phone</code>. A plain list of emails without a header also works.</small></div></div>
<label class="dropzone"><input type="file" name="csv" accept=".csv,text/csv,text/plain" required data-file-label><?= icon('file') ?><b data-file-name>Choose a CSV file</b><small class="muted">Up to 10 MB · comma, semicolon or tab separated</small></label>
</div>
<div class="card stack">
<div class="card-head"><h3>2. Choose courses</h3></div>
<?php if (!$courses): ?><p class="muted small">No courses yet — users will be created without enrollments.</p><?php endif; ?>
<div class="check-list">
<?php foreach ($courses as $c): ?><label class="check"><input type="checkbox" name="courses[]" value="<?= (int)$c['id'] ?>"><span><?= e($c['title']) ?><small class="muted"><?= $c['access_days'] ? ' · ' . (int)$c['access_days'] . '-day access' : ' · no time limit' ?><?= $c['start_date'] ? ' · starts ' . e($c['start_date']) : '' ?></small></span></label><?php endforeach; ?>
</div>
<div class="grid-2"><div class="field"><label>Access starts</label><input type="datetime-local" name="starts_at"><small class="hint">Default: now (Day 1 of the drip schedule)</small></div><div class="field"><label>Access expires</label><input type="datetime-local" name="expires_at"><small class="hint">Default: course access duration / end date</small></div></div>
</div>
</div>
<aside class="stack-lg">
<div class="card stack">
<div class="card-head"><h3>3. Notifications</h3></div>
<label class="check"><input type="checkbox" name="email_new" value="1" checked><span>Email new users a secure password setup link & login instructions</span></label>
<label class="check"><input type="checkbox" name="email_existing" value="1" checked><span>Email existing users about their new course access</span></label>
<p class="small muted">Emails are queued and sent in batches of <?= (int)setting('mail_batch_size', 40) ?> to respect shared-hosting limits.</p>
<button class="btn btn-primary btn-block"><?= icon('upload') ?>Run import</button>
</div>
<div class="card">
<div class="card-head"><h3>Recent imports</h3></div>
<?php if (!$history): ?><p class="muted small">No imports yet.</p><?php endif; ?>
<?php foreach ($history as $h): ?><a class="list-row" href="<?= e(url('/admin/import/' . $h['id'])) ?>"><span class="grow"><b><?= e($h['filename']) ?></b><small class="muted"><?= (int)$h['created'] ?> new · <?= (int)$h['existing'] ?> existing · <?= (int)$h['skipped'] ?> skipped · <?= e(time_ago($h['created_at'])) ?></small></span><?= icon('chevron-right', 'muted') ?></a><?php endforeach; ?>
</div>
</aside>
</form>
