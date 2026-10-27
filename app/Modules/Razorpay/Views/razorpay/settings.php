<?php
$layout = 'admin';
$title = 'Razorpay';
?>
<div class="form-layout">
<form method="post" action="<?= e(url('/admin/razorpay')) ?>" class="card stack" data-loading>
<?= csrf_field() ?>
<div class="card-head"><div><h3>Razorpay gateway</h3><small class="muted">Adds “Card, UPI, Netbanking & Wallets” at checkout. Payments are confirmed automatically.</small></div><span class="badge badge-<?= $enabled ? 'success' : 'muted' ?>"><?= $enabled ? 'Active' : 'Off' ?></span></div>
<label class="check"><input type="checkbox" name="razorpay_enabled" value="1"<?= checked($s['razorpay_enabled'] ?? false) ?>><span>Enable Razorpay at checkout</span></label>
<div class="field"><label for="kid">Key ID</label><input id="kid" name="razorpay_key_id" value="<?= e($s['razorpay_key_id'] ?? '') ?>" placeholder="rzp_live_XXXXXXXXXXXX" autocomplete="off"></div>
<div class="field"><label for="ksec">Key secret</label><input id="ksec" type="password" name="razorpay_key_secret" placeholder="<?= !empty($s['razorpay_key_secret']) ? '•••••••• (saved, encrypted)' : 'From Dashboard → Account & Settings → API keys' ?>" autocomplete="new-password"></div>
<div class="field"><label for="wsec">Webhook secret</label><input id="wsec" type="password" name="razorpay_webhook_secret" placeholder="<?= !empty($s['razorpay_webhook_secret']) ? '•••••••• (saved, encrypted)' : 'The secret you set when creating the webhook' ?>" autocomplete="new-password"></div>
<div class="row gap-2"><button class="btn btn-primary"><?= icon('check') ?>Save</button></div>
</form>
<aside class="stack-lg">
<div class="card stack">
<div class="card-head"><h3>Setup</h3></div>
<ol class="small muted m-0" style="padding-left:1.1rem">
<li>Create API keys in the Razorpay Dashboard and paste them here.</li>
<li>Add a webhook with event <code>payment_link.paid</code> pointing to:</li>
</ol>
<div class="copy-box"><code><?= e(url('/razorpay/webhook', [], true)) ?></code><button type="button" class="btn-icon" data-copy="<?= e(url('/razorpay/webhook', [], true)) ?>" title="Copy"><?= icon('copy') ?></button></div>
<p class="small muted m-0">The webhook confirms payments even if the student closes the browser before returning.</p>
<form method="post" action="<?= e(url('/admin/razorpay/test')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"<?= $enabled ? '' : ' disabled' ?>><?= icon('refresh') ?>Test connection</button></form>
</div>
</aside>
</div>
