<?php $layout = 'admin'; $title = 'Settings'; include \App\Core\View::find('admin/settings/_tabs'); ?>
<div class="settings-hero tint-purple"><span class="sh-ico"><?= icon('sparkles') ?></span><div><h2>Learning experience</h2><p>Keep students coming back: unlock reminders, expiry nudges and a daily motivational quote.</p></div></div>
<form method="post" action="<?= e(url('/admin/settings/learning')) ?>" class="stack-lg" data-loading>
<?= csrf_field() ?>
<section class="set-sec">
<div class="set-desc"><h3>Drip reminders</h3><p>Sent when a scheduled lesson unlocks (checked every ~5 minutes by the scheduler).</p></div>
<div class="card set-fields">
<div class="set-row"><div><b>In-app notification</b><small>Bell notification when a lesson unlocks.</small></div><label class="switch"><input type="checkbox" name="notify_lesson_unlock" value="1"<?= checked($s['notify_lesson_unlock']) ?>><span></span></label></div>
<div class="set-row"><div><b>Email notification</b><small>One email per student per unlock, using the “New lesson unlocked” template.</small></div><label class="switch"><input type="checkbox" name="email_lesson_unlock" value="1"<?= checked($s['email_lesson_unlock']) ?>><span></span></label></div>
<div class="set-row"><div><b>Expiry reminder</b><small>Days before access ends. 0 turns it off.</small></div><input type="number" min="0" max="30" name="expiry_reminder_days" value="<?= e($s['expiry_reminder_days']) ?>" style="width:90px"></div>
</div>
</section>
<section class="set-sec">
<div class="set-desc"><h3>Motivational quotes</h3><p>A different quote each day on the dashboard, course and lesson pages. One per line, “Quote - Author”. Courses can have their own list.</p></div>
<div class="card set-fields">
<div class="set-row"><div><b>Show quotes</b></div><label class="switch"><input type="checkbox" name="show_quotes" value="1"<?= checked($s['show_quotes']) ?>><span></span></label></div>
<textarea name="quotes" rows="10"><?= e(implode("\n", (array)$s['quotes'])) ?></textarea>
</div>
</section>
<div class="set-actions"><button class="btn btn-primary"><?= icon('check') ?>Save</button></div>
</form>
<?php include \App\Core\View::find('admin/settings/_end'); ?>
