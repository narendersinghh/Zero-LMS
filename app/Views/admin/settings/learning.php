<?php $layout = 'admin'; $title = 'Settings'; include \App\Core\View::find('admin/settings/_tabs'); ?>
<form method="post" action="<?= e(url('/admin/settings/learning')) ?>" class="card stack narrow" data-loading>
<?= csrf_field() ?>
<div class="card-head"><h3>Drip notifications</h3></div>
<label class="check"><input type="checkbox" name="notify_lesson_unlock" value="1"<?= checked($s['notify_lesson_unlock']) ?>><span>In-app notification when a scheduled lesson unlocks</span></label>
<label class="check"><input type="checkbox" name="email_lesson_unlock" value="1"<?= checked($s['email_lesson_unlock']) ?>><span>Email students when new lessons unlock (one email per student per run)</span></label>
<div class="field"><label>Expiry reminder (days before)</label><input type="number" min="0" max="30" name="expiry_reminder_days" value="<?= e($s['expiry_reminder_days']) ?>"><small class="hint">0 disables reminders.</small></div>
<label class="check"><input type="checkbox" name="web_cron" value="1"<?= checked($s['web_cron']) ?>><span>Run scheduled tasks automatically during page visits (if you can't set up a cron job)</span></label>
<hr>
<div class="card-head"><div><h3>Motivational quotes</h3><small class="muted">Shown on the dashboard, course and lesson pages. One per line; “Quote — Author”. Courses can override with their own.</small></div></div>
<label class="check"><input type="checkbox" name="show_quotes" value="1"<?= checked($s['show_quotes']) ?>><span>Show quotes</span></label>
<textarea name="quotes" rows="12"><?= e(implode("\n", (array)$s['quotes'])) ?></textarea>
<div><button class="btn btn-primary">Save</button></div>
</form>
