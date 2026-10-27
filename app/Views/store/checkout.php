<?php
$layout = 'public';
$title = 'Checkout · ' . $course['title'];
$cur = $price['currency'];
?>
<div class="checkout">
<div>
<a class="back-link" href="<?= e(url('/c/' . $course['slug'])) ?>"><?= icon('arrow-left') ?>Back to course</a>
<h1 class="h2">Complete your enrollment</h1>
<form method="post" action="<?= e(url('/c/' . $course['slug'] . '/checkout')) ?>" class="stack-lg" data-loading data-checkout data-quote="<?= e(url('/c/' . $course['slug'] . '/quote')) ?>">
<?= csrf_field() ?>
<div class="card stack">
<div class="step-head"><span class="step-n">1</span><div><h3>Your account</h3><?php if (!$user): ?><small class="muted">Already registered? <a href="<?= e(url('/login', ['next' => '/c/' . $course['slug'] . '/checkout'])) ?>">Sign in</a> first.</small><?php endif; ?></div></div>
<?php if ($user): ?>
<div class="row gap-3"><?php partial('avatar', ['u' => $user]); ?><div><b><?= e($user['name']) ?></b><small class="muted d-block"><?= e($user['email']) ?></small></div></div>
<?php else: ?>
<div class="grid-2"><div class="field"><label for="name">Full name</label><input id="name" name="name" value="<?= e(old('name')) ?>" required autocomplete="name"></div>
<div class="field"><label for="phone">Mobile number</label><input id="phone" name="phone" type="tel" value="<?= e(old('phone')) ?>" autocomplete="tel" inputmode="tel"></div></div>
<div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email"><?= field_error('email') ?><small class="hint">Your login details and receipts are sent here.</small></div>
<div class="field"><label for="password">Create a password</label><div class="input-group"><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" data-strength><button type="button" class="input-btn" data-toggle-password aria-label="Show password"><?= icon('eye') ?></button></div><div class="strength"><i></i></div><small class="hint">At least 8 characters with a letter and a number.</small><?= field_error('password') ?></div>
<?php endif; ?>
</div>
<?php if (!$price['free'] || trim(hook_html('checkout.fields', $course, $price)) !== ''): ?>
<div class="card stack">
<div class="step-head"><span class="step-n">2</span><div><h3>Payment</h3><small class="muted">Choose how you'd like to pay.</small></div></div>
<?= hook_html('checkout.fields', $course, $price) ?>
<?php if (!$gateways && !$price['free']): ?><div class="alert alert-warning"><?= icon('alert') ?><span>Online payments are not set up yet. Please contact <?= e(setting('support_email') ?: 'support') ?>.</span></div><?php endif; ?>
<div class="pay-opts" data-pay-opts<?= $price['free'] ? ' hidden' : '' ?>>
<?php $first = true; foreach ($gateways as $k => $g): ?>
<label class="pay-opt"><input type="radio" name="gateway" value="<?= e($k) ?>"<?= $first ? ' checked' : '' ?>><span><b><?= e($g->label()) ?></b><small class="muted"><?= e($g->description()) ?></small></span><?= $k === 'upi' ? '<span class="upi-mark">UPI</span>' : '' ?></label>
<?php $first = false; endforeach; ?>
</div>
</div>
<?php endif; ?>
<button class="btn btn-primary btn-lg btn-block" <?= !$gateways && !$price['free'] ? 'disabled' : '' ?>><span data-cta><?= $price['free'] ? 'Get free access' : 'Continue to pay ' . e(money($price['final'], $cur)) ?></span> <?= icon('arrow-right') ?></button>
<p class="small muted center"><?= icon('shield') ?> By continuing you agree to the course terms. Access is personal and may not be shared.</p>
</form>
</div>
<aside>
<div class="card summary">
<?= course_visual($course, 'sum-cv') ?>
<h3><?= e($course['title']) ?></h3>
<?php if ($course['subtitle']): ?><p class="muted small"><?= e($course['subtitle']) ?></p><?php endif; ?>
<dl class="sum-lines">
<dt>Price</dt><dd><?= e(money($price['base'], $cur)) ?></dd>
<?php if ($price['base'] > $price['list']): ?><dt>Launch offer</dt><dd class="success">−<?= e(money($price['base'] - $price['list'], $cur)) ?></dd><?php endif; ?>
<dt data-disc-row<?= $price['discount'] > 0 ? '' : ' hidden' ?>>Coupon <span data-disc-label><?= e($price['label'] ?? '') ?></span></dt><dd class="success" data-disc-row<?= $price['discount'] > 0 ? '' : ' hidden' ?>>−<span data-disc><?= e(money($price['discount'], $cur)) ?></span></dd>
<dt class="total">Total</dt><dd class="total" data-total><?= $price['free'] ? 'Free' : e(money($price['final'], $cur)) ?></dd>
</dl>
</div>
</aside>
</div>
