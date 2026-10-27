<?php $layout = auth() ? 'app' : 'public'; $title = 'Verify certificate'; $valid = $cert && !$cert['revoked_at']; ?>
<div style="max-width:620px;margin:0 auto" class="stack-lg">
<div class="center"><h1>Verify a certificate</h1><p class="muted">Enter the code printed on a <?= e(setting('site_name')) ?> certificate.</p></div>
<form method="get" action="<?= e(url('/verify')) ?>" class="row gap-2"><input name="code" value="<?= e($code) ?>" placeholder="e.g. 7KQ2M9XHPA" aria-label="Certificate code" style="text-transform:uppercase" required><button class="btn btn-primary">Verify</button></form>
<?php if ($searched): ?>
<?php if ($valid): ?>
<div class="card tint-green stack" style="gap:.5rem">
<span class="badge badge-success" style="width:fit-content"><?= icon('check-circle') ?>Valid certificate</span>
<h2 class="m-0"><?= e($cert['user_name']) ?></h2>
<p class="m-0">completed <b><?= e($cert['course_title']) ?></b><?= $cert['instructor_name'] ? ' by ' . e($cert['instructor_name']) : '' ?></p>
<p class="small muted m-0">Issued <?= e(dt($cert['issued_at'])) ?> · Code <code><?= e($cert['code']) ?></code></p>
</div>
<?php elseif ($cert): ?>
<div class="alert alert-danger"><?= icon('alert') ?><span>Certificate <b><?= e($cert['code']) ?></b> was revoked on <?= e(dt($cert['revoked_at'])) ?> and is no longer valid.</span></div>
<?php else: ?>
<div class="alert alert-warning"><?= icon('alert') ?><span>No certificate found with code <b><?= e($code) ?></b>. Check the code and try again.</span></div>
<?php endif; ?>
<?php endif; ?>
</div>
