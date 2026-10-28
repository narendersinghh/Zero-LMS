<?php
$layout = 'admin';
$isNew = !$c['id'];
$title = $isNew ? 'New coupon' : 'Coupon ' . $c['code'];
$crumbs = ['Coupons' => '/admin/coupons'];
$v = fn($k) => old($k, $c[$k] ?? '');
$slug = '';
foreach ($courses as $co) {
    if ((int)$co['id'] === (int)$c['course_id']) {
        $slug = $co['slug'];}}
?>
<div class="form-layout">
<form method="post" action="<?= e(url($isNew ? '/admin/coupons' : '/admin/coupons/' . $c['id'])) ?>" class="card stack" data-loading>
<?= csrf_field() ?>
<div class="grid-2">
<div class="field"><label for="code">Code</label><input id="code" name="code" value="<?= e($v('code')) ?>" required maxlength="40" style="text-transform:uppercase"><?= field_error('code') ?></div>
<div class="field"><label for="course_id">Applies to</label><select id="course_id" name="course_id"><option value="0">All courses</option><?php foreach ($courses as $co): ?><option value="<?= (int)$co['id'] ?>"<?= selected($co['id'], $v('course_id')) ?>><?= e($co['title']) ?></option><?php endforeach; ?></select></div>
</div>
<div class="grid-2">
<div class="field"><label for="type">Discount type</label><select id="type" name="type"><option value="percent"<?= selected('percent', $v('type')) ?>>Percentage (%)</option><option value="fixed"<?= selected('fixed', $v('type')) ?>>Fixed amount (₹)</option></select></div>
<div class="field"><label for="value">Value</label><input id="value" type="number" step="0.01" min="0" name="value" value="<?= e($v('value')) ?>" required><?= field_error('value') ?><small class="hint">100% makes the course free with this code.</small></div>
</div>
<div class="grid-2">
<div class="field"><label for="max_uses">Total uses</label><input id="max_uses" type="number" min="0" name="max_uses" value="<?= e($v('max_uses')) ?>" placeholder="Unlimited"></div>
<div class="field"><label for="per_user_limit">Uses per student</label><input id="per_user_limit" type="number" min="0" name="per_user_limit" value="<?= e($v('per_user_limit')) ?>"><small class="hint">0 = unlimited</small></div>
</div>
<div class="grid-2">
<div class="field"><label for="starts_at">Starts</label><input id="starts_at" type="datetime-local" name="starts_at" value="<?= e(old('starts_at', to_local($c['starts_at']))) ?>"></div>
<div class="field"><label for="expires_at">Expires</label><input id="expires_at" type="datetime-local" name="expires_at" value="<?= e(old('expires_at', to_local($c['expires_at']))) ?>"><?= field_error('expires_at') ?></div>
</div>
<div class="field"><label for="note">Internal note</label><input id="note" name="note" value="<?= e($v('note')) ?>" maxlength="255" placeholder="e.g. Webinar 12 Nov attendees"></div>
<label class="check"><input type="checkbox" name="active" value="1"<?= checked($v('active')) ?>><span>Active</span></label>
<div><button class="btn btn-primary"><?= icon('check') ?><?= $isNew ? 'Create coupon' : 'Save coupon' ?></button></div>
</form>
<aside class="stack-lg">
<?php if (!$isNew): ?>
<div class="card stack">
<div class="card-head"><h3>Share</h3></div>
<?php if ($slug): ?><div class="copy-box"><code><?= e(url('/c/' . $slug, ['coupon' => $c['code']], true)) ?></code><button type="button" class="btn-icon" data-copy="<?= e(url('/c/' . $slug, ['coupon' => $c['code']], true)) ?>" title="Copy link"><?= icon('copy') ?></button></div><small class="hint">Opening this link applies the code automatically at checkout.</small>
<?php else: ?><p class="small muted m-0">Works on every course. Add <code>?coupon=<?= e($c['code']) ?></code> to any course link.</p><?php endif; ?>
<div class="row gap-2 wrap">
<form method="post" action="<?= e(url('/admin/coupons/' . $c['id'] . '/toggle')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"><?= (int)$c['active'] ? 'Pause' : 'Activate' ?></button></form>
<form method="post" action="<?= e(url('/admin/coupons/' . $c['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="btn btn-danger btn-sm" data-confirm="Delete this coupon? Past orders keep their discount."><?= icon('trash') ?>Delete</button></form>
</div>
</div>
<div class="card">
<div class="card-head"><h3>Uses (<?= (int)$c['used_count'] ?>)</h3></div>
<?php if (!$uses): ?><p class="small muted m-0">Not used yet.</p><?php endif; ?>
<?php foreach ($uses as $u): ?><div class="list-row"><span class="grow"><b><?= e($u['name']) ?></b><small class="muted"><?= e($u['course_title'] ?: '') ?><?= $u['ref'] ? ' · ' . e($u['ref']) : ' · free enrollment' ?> · <?= e(time_ago($u['created_at'])) ?></small></span><span class="small success">−<?= e(money($u['discount'])) ?></span></div><?php endforeach; ?>
</div>
<?php else: ?>
<div class="card small muted">Codes are case-insensitive. A redemption is counted when an order is paid (or a 100%-off code enrolls a student).</div>
<?php endif; ?>
</aside>
</div>
