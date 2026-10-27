<?php
use App\Services\Payments\Payments;
$layout = 'admin';
$title = 'Settings';
include \App\Core\View::find('admin/settings/_tabs');
$all = Payments::gateways(false);
$upiReady = $s['upi_enabled'] && trim((string)$s['upi_vpa']) !== '';
?>
<div class="settings-hero tint-orange"><span class="sh-ico"><?= icon('key') ?></span><div><h2>Payments</h2><p>Sell courses with a shareable link. Students pay by UPI (QR or tap-to-pay), then you verify the transaction in Orders.</p></div><span class="badge badge-<?= $upiReady ? 'success' : 'warning' ?>"><?= $upiReady ? 'UPI active' : 'UPI not set up' ?></span></div>
<form method="post" action="<?= e(url('/admin/settings/payments')) ?>" class="stack-lg" data-loading>
<?= csrf_field() ?>
<section class="set-sec">
<div class="set-desc"><h3>UPI</h3><p>Use a business/merchant UPI ID for higher daily limits. The payee name appears in the student's UPI app.</p><ol class="small muted" style="padding-left:1.1rem;margin:.8rem 0 0"><li>Student scans the QR or taps “Pay with UPI app”</li><li>They enter the UTR from their app</li><li>You approve it in Orders → access is granted</li></ol></div>
<div class="card set-fields">
<div class="set-row"><div><b>Accept UPI payments</b><small>Shown as a payment option at checkout.</small></div><label class="switch"><input type="checkbox" name="upi_enabled" value="1"<?= checked($s['upi_enabled']) ?>><span></span></label></div>
<div class="grid-2">
<div class="field"><label>UPI ID (VPA)</label><input name="upi_vpa" value="<?= e($s['upi_vpa']) ?>" placeholder="business@okhdfcbank" pattern="[A-Za-z0-9._-]{2,}@[A-Za-z0-9.-]{2,}"></div>
<div class="field"><label>Payee name</label><input name="upi_payee_name" value="<?= e($s['upi_payee_name']) ?>" placeholder="<?= e($s['site_name']) ?>" maxlength="60"></div>
</div>
<div class="field"><label>Instructions on the payment page</label><textarea name="upi_instructions" rows="3" placeholder="e.g. Payments are verified within 2 hours between 9am–9pm."><?= e($s['upi_instructions']) ?></textarea></div>
</div>
</section>
<section class="set-sec">
<div class="set-desc"><h3>Verification</h3><p>UPI has no automatic confirmation without a gateway, so a team member approves each payment.</p></div>
<div class="card set-fields">
<div class="set-row"><div><b>Instant access on UTR submission</b><small>Students start learning right away; access is suspended automatically if you reject the payment.</small></div><label class="switch"><input type="checkbox" name="upi_provisional_access" value="1"<?= checked($s['upi_provisional_access']) ?>><span></span></label></div>
<div class="set-row"><div><b>Require payment screenshot</b><small>Makes verification easier when UTRs are mistyped.</small></div><label class="switch"><input type="checkbox" name="upi_require_proof" value="1"<?= checked($s['upi_require_proof']) ?>><span></span></label></div>
</div>
</section>
<section class="set-sec">
<div class="set-desc"><h3>Accounts</h3><p>Course links always allow sign-up during checkout.</p></div>
<div class="card set-fields">
<div class="set-row"><div><b>Open registration</b><small>Anyone can create a free student account at /register.</small></div><label class="switch"><input type="checkbox" name="allow_registration" value="1"<?= checked($s['allow_registration']) ?>><span></span></label></div>
</div>
</section>
<section class="set-sec">
<div class="set-desc"><h3>Gateways</h3><p>Cards, netbanking and wallets via a payment gateway module.</p></div>
<div class="card set-fields">
<?php foreach ($all as $k => $g): ?><div class="set-row"><div><b><?= e($g->label()) ?></b><small><?= e($g->description()) ?></small></div><span class="badge badge-<?= $g->enabled() ? 'success' : 'muted' ?>"><?= $g->enabled() ? 'Active' : 'Off' ?></span></div><?php endforeach; ?>
<?php if (!isset($all['razorpay'])): ?><div class="set-row"><div><b>Razorpay</b><small>Enable the Razorpay module to accept cards & netbanking.</small></div><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/modules')) ?>">Modules</a></div><?php elseif (can('payments.configure')): ?><div class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/razorpay')) ?>"><?= icon('settings') ?>Razorpay settings</a></div><?php endif; ?>
</div>
</section>
<div class="set-actions"><a class="btn btn-ghost" href="<?= e(url('/admin/orders')) ?>">View orders</a><button class="btn btn-primary"><?= icon('check') ?>Save payment settings</button></div>
</form>
<?php include \App\Core\View::find('admin/settings/_end'); ?>
