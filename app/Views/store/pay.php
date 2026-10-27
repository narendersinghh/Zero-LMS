<?php
$layout = 'app';
$title = 'Pay for ' . $o['course_title'];
$amount = money($o['amount'], $o['currency']);
$vpa = (string)setting('upi_vpa');
?>
<div class="pay-page">
<a class="back-link" href="<?= e(url('/orders')) ?>"><?= icon('arrow-left') ?>My orders</a>
<?php if ($o['status'] === 'submitted'): ?>
<div class="card state-card tint-blue">
<?= illus('verify', 'state-illus') ?>
<div><span class="pill pill-dark">Order <?= e($o['ref']) ?></span><h1 class="h2">We're verifying your payment</h1>
<p>Thanks! We received UTR <b><?= e($o['utr']) ?></b> for <b><?= e($amount) ?></b>. You'll get an email and a notification as soon as access to <b><?= e($o['course_title']) ?></b> is active, usually within a few hours.</p>
<div class="row gap-2 wrap"><a class="btn btn-dark" href="<?= e(url('/dashboard')) ?>">Go to dashboard</a><a class="btn btn-ghost" href="<?= e(url('/explore')) ?>">Explore courses</a></div></div>
</div>
<?php else: ?>
<?php if ($o['status'] === 'rejected'): ?><div class="alert alert-danger"><?= icon('alert') ?><span><b>We couldn't verify your last payment.</b> <?= e($o['admin_note']) ?> Please check the details and submit again.</span></div><?php endif; ?>
<?php if ($o['status'] === 'cancelled'): ?><div class="alert alert-warning"><?= icon('alert') ?><span>This order was cancelled. <a href="<?= e(url('/c/' . $o['course_slug'])) ?>">Start a new order</a>.</span></div><?php else: ?>
<div class="pay-grid">
<div class="card pay-qr-card tint-yellow">
<span class="pill pill-dark">Step 1 · Pay <?= e($amount) ?></span>
<div class="qr-box"><?= $qr ?></div>
<a class="btn btn-dark btn-lg btn-block show-touch" href="<?= e($uri) ?>"><?= icon('external') ?>Pay with UPI app</a>
<p class="small center m-0">Scan with GPay, PhonePe, Paytm, BHIM or any UPI app<span class="show-touch-inline">, or tap the button above on this phone</span>.</p>
<div class="vpa"><span class="muted small">UPI ID</span><b><?= e($vpa) ?></b><button type="button" class="btn-icon" data-copy="<?= e($vpa) ?>" aria-label="Copy UPI ID"><?= icon('copy') ?></button></div>
<div class="vpa"><span class="muted small">Amount</span><b><?= e($amount) ?></b><button type="button" class="btn-icon" data-copy="<?= e(number_format((float)$o['amount'], 2, '.', '')) ?>" aria-label="Copy amount"><?= icon('copy') ?></button></div>
</div>
<div class="card stack">
<span class="pill">Step 2 · Confirm payment</span>
<h1 class="h3 m-0"><?= e($o['course_title']) ?></h1>
<p class="muted small m-0">Order <b><?= e($o['ref']) ?></b> · <?= e($amount) ?></p>
<?php if (setting('upi_instructions')): ?><div class="note small"><?= nl2br(e(setting('upi_instructions'))) ?></div><?php endif; ?>
<form method="post" action="<?= e(url('/orders/' . $o['ref'] . '/confirm')) ?>" enctype="multipart/form-data" class="stack" data-loading>
<?= csrf_field() ?>
<div class="field"><label for="utr">UPI transaction ID / UTR</label><input id="utr" name="utr" value="<?= e(old('utr', $o['status'] === 'rejected' ? '' : (string)$o['utr'])) ?>" required inputmode="numeric" autocomplete="off" placeholder="e.g. 412345678901" maxlength="30"><?= field_error('utr') ?><small class="hint">Find it in your UPI app under the payment's details (12 digits).</small></div>
<div class="field"><label for="proof">Payment screenshot <?= setting('upi_require_proof') ? '' : '<span class="muted">(optional)</span>' ?></label><input id="proof" type="file" name="proof" accept="image/png,image/jpeg,image/webp" <?= setting('upi_require_proof') ? 'required' : '' ?>></div>
<div class="field"><label for="note">Note <span class="muted">(optional)</span></label><input id="note" name="note" maxlength="255" placeholder="Paid from another number? Tell us here."></div>
<button class="btn btn-primary btn-lg btn-block"><?= icon('check-circle') ?>I've paid, submit</button>
</form>
<form method="post" action="<?= e(url('/orders/' . $o['ref'] . '/cancel')) ?>" class="center"><?= csrf_field() ?><button class="link-btn muted" data-confirm="Cancel this order?">Cancel order</button></form>
</div>
</div>
<?php endif; ?>
<?php endif; ?>
</div>
