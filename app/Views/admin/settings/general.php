<?php $layout = 'admin'; $title = 'Settings'; include \App\Core\View::find('admin/settings/_tabs'); ?>
<div class="settings-hero tint-yellow"><span class="sh-ico"><?= icon('settings') ?></span><div><h2>General</h2><p>Your academy's name, region and what students see when they sign in.</p></div></div>
<form method="post" action="<?= e(url('/admin/settings/general')) ?>" class="stack-lg" data-loading>
<?= csrf_field() ?>
<section class="set-sec">
<div class="set-desc"><h3>Identity</h3><p>Shown in the browser tab, emails, certificates and the header when no logo is uploaded.</p></div>
<div class="card set-fields">
<div class="grid-2"><div class="field"><label for="site_name">Academy name</label><input id="site_name" name="site_name" value="<?= e($s['site_name']) ?>" required maxlength="80"></div><div class="field"><label for="site_tagline">Tagline</label><input id="site_tagline" name="site_tagline" value="<?= e($s['site_tagline']) ?>" maxlength="160"></div></div>
<div class="field"><label for="support_email">Support email</label><input id="support_email" type="email" name="support_email" value="<?= e($s['support_email']) ?>" placeholder="help@yourdomain.com"><small class="hint">Shown to students in the footer and in every email.</small></div>
</div>
</section>
<section class="set-sec">
<div class="set-desc"><h3>Region & format</h3><p>Daily lesson unlock times and all dates use this timezone.</p></div>
<div class="card set-fields">
<div class="field"><label for="timezone">Timezone</label><select id="timezone" name="timezone"><?php foreach ($timezones as $tz): ?><option<?= selected($tz, $s['timezone']) ?>><?= e($tz) ?></option><?php endforeach; ?></select></div>
<div class="grid-2">
<div class="field"><label for="date_format">Date format</label><select id="date_format" name="date_format"><?php foreach (['M j, Y', 'd M Y', 'j F Y', 'd/m/Y', 'm/d/Y', 'Y-m-d'] as $f): ?><option value="<?= e($f) ?>"<?= selected($f, $s['date_format']) ?>><?= e(date($f)) ?></option><?php endforeach; ?></select></div>
<div class="field"><label for="time_format">Time format</label><select id="time_format" name="time_format"><option value="g:i A"<?= selected('g:i A', $s['time_format']) ?>>12-hour (3:30 PM)</option><option value="H:i"<?= selected('H:i', $s['time_format']) ?>>24-hour (15:30)</option></select></div>
</div>
</div>
</section>
<section class="set-sec">
<div class="set-desc"><h3>Sign-in page</h3><p>The welcome text on the login screen.</p></div>
<div class="card set-fields">
<div class="field"><label for="login_title">Heading</label><input id="login_title" name="login_title" value="<?= e($s['login_title']) ?>" maxlength="80"></div>
<div class="field"><label for="login_subtitle">Subheading</label><input id="login_subtitle" name="login_subtitle" value="<?= e($s['login_subtitle']) ?>" maxlength="200"></div>
</div>
</section>
<div class="set-actions"><span class="muted">Changes apply immediately.</span><button class="btn btn-primary"><?= icon('check') ?>Save changes</button></div>
</form>
<?php include \App\Core\View::find('admin/settings/_end'); ?>
