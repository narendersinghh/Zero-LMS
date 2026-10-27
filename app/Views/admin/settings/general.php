<?php $layout = 'admin'; $title = 'Settings'; include \App\Core\View::find('admin/settings/_tabs'); ?>
<form method="post" action="<?= e(url('/admin/settings/general')) ?>" class="card stack narrow" data-loading>
<?= csrf_field() ?>
<div class="grid-2"><div class="field"><label>Site name</label><input name="site_name" value="<?= e($s['site_name']) ?>" required maxlength="80"></div><div class="field"><label>Tagline</label><input name="site_tagline" value="<?= e($s['site_tagline']) ?>" maxlength="160"></div></div>
<div class="field"><label>Support email</label><input type="email" name="support_email" value="<?= e($s['support_email']) ?>" placeholder="help@yourdomain.com"><small class="hint">Shown to students in the footer and emails.</small></div>
<div class="grid-3">
<div class="field"><label>Timezone</label><select name="timezone"><?php foreach ($timezones as $tz): ?><option<?= selected($tz, $s['timezone']) ?>><?= e($tz) ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Date format</label><select name="date_format"><?php foreach (['M j, Y', 'd M Y', 'j F Y', 'd/m/Y', 'm/d/Y', 'Y-m-d'] as $f): ?><option value="<?= e($f) ?>"<?= selected($f, $s['date_format']) ?>><?= e(date($f)) ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Time format</label><select name="time_format"><option value="g:i A"<?= selected('g:i A', $s['time_format']) ?>>3:30 PM</option><option value="H:i"<?= selected('H:i', $s['time_format']) ?>>15:30</option></select></div>
</div>
<hr>
<div class="grid-2"><div class="field"><label>Login heading</label><input name="login_title" value="<?= e($s['login_title']) ?>" maxlength="80"></div><div class="field"><label>Login subheading</label><input name="login_subtitle" value="<?= e($s['login_subtitle']) ?>" maxlength="200"></div></div>
<div><button class="btn btn-primary">Save settings</button></div>
</form>
