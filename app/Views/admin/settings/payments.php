<?php
use App\Services\Payments\Payments;
$layout = 'admin';
$title = 'Settings';
include \App\Core\View::find('admin/settings/_tabs');
$all = Payments::gateways(false);
?>
<div class="form-layout">
<form method="post" action="<?= e(url('/admin/settings/payments')) ?>" class="stack-lg" data-loading>
<?= csrf_field() ?>
<div class="card stack">
<div class="card-head"><div><h3>UPI payments</h3><small class="muted">Students scan a QR or tap a UPI link, then submit the transaction reference (UTR). You approve it in Orders.</small></div><label class="switch"><input type="checkbox" name="upi_enabled" value="1"<?= checked($s['upi_enabled']) ?>><span></span></label></div>
<div class="grid-2">
<div class="field"><label>UPI ID (VPA)</label><input name="upi_vpa" value="<?= e($s['upi_vpa']) ?>" placeholder="business@okhdfcbank" pattern="[A-Za-z0-9._-]{2,}@[A-Za-z0-9.-]{2,}"><small class="hint">Use a business/merchant UPI ID for higher limits.</small></div>
<div class="field"><label>Payee name</label><input name="upi_payee_name" value="<?= e($s['upi_payee_name']) ?>" placeholder="<?= e($s['site_name']) ?>" maxlength="60"><small class="hint">Shown in the payer's UPI app.</small></div>
</div>
<div class="field"><label>Payment instructions</label><textarea name="upi_instructions" rows="3" placeholder="e.g. Payments are verified within 2 hours between 9am–9pm."><?= e($s['upi_instructions']) ?></textarea></div>
<label class="check"><input type="checkbox" name="upi_provisional_access" value="1"<?= checked($s['upi_provisional_access']) ?>><span>Give access immediately after the student submits a UTR <small class="muted d-block">Access is suspended automatically if you reject the payment.</small></span></label>
<label class="check"><input type="checkbox" name="upi_require_proof" value="1"<?= checked($s['upi_require_proof']) ?>><span>Require a payment screenshot</span></label>
</div>
<div class="card stack">
<div class="card-head"><h3>Accounts</h3></div>
<label class="check"><input type="checkbox" name="allow_registration" value="1"<?= checked($s['allow_registration']) ?>><span>Allow anyone to create a student account (/register) <small class="muted d-block">Course links always allow sign-up during checkout.</small></span></label>
</div>
<div class="sticky-actions"><button class="btn btn-primary"><?= icon('check') ?>Save payment settings</button></div>
</form>
<aside class="stack-lg">
<div class="card stack">
<div class="card-head"><h3>Gateways</h3></div>
<?php foreach ($all as $k => $g): ?><div class="list-row"><span class="grow"><b><?= e($g->label()) ?></b><small class="muted"><?= e($k) ?></small></span><span class="badge badge-<?= $g->enabled() ? 'success' : 'muted' ?>"><?= $g->enabled() ? 'Active' : 'Off' ?></span></div><?php endforeach; ?>
<p class="small muted m-0">Add card/netbanking gateways (e.g. Razorpay) by enabling a payment module under <a href="<?= e(url('/admin/system')) ?>">System → Modules</a>. Developers can register new gateways with the <code>payment.gateways</code> hook.</p>
</div>
</aside>
</div>
