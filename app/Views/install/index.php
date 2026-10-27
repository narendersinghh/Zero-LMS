<?php $layout = 'install'; ?>
<ol class="steps"><?php foreach (['Requirements', 'Database', 'Site & admin'] as $i => $s): ?><li class="<?= $step === $i + 1 ? 'active' : ($step > $i + 1 ? 'done' : '') ?>"><span><?= $step > $i + 1 ? icon('check') : $i + 1 ?></span><?= e($s) ?></li><?php endforeach; ?></ol>
<div class="card install-card">
<?php if ($step === 1): ?>
<h2>Server check</h2>
<p class="muted mb-4">Zeroes runs on standard shared hosting. Required items must pass; optional items enable updates and image processing.</p>
<table class="table"><tbody>
<?php foreach ($reqs as $r): ?><tr><td><?= e($r[0]) ?><?php if (!$r[3]): ?> <span class="badge badge-muted">optional</span><?php endif; ?></td><td class="muted small"><?= e($r[2]) ?></td><td class="right"><?= $r[1] ? '<span class="badge badge-success">' . icon('check') . 'OK</span>' : '<span class="badge badge-' . ($r[3] ? 'danger' : 'warning') . '">' . icon('x') . 'Missing</span>' ?></td></tr><?php endforeach; ?>
</tbody></table>
<div class="row end mt-4"><?php if ($ok): ?><a class="btn btn-primary" href="<?= e(url('/install', ['step' => 2])) ?>">Continue <?= icon('arrow-right') ?></a><?php else: ?><span class="muted">Fix the required items and reload this page.</span><?php endif; ?></div>
<?php elseif ($step === 2): ?>
<h2>Database connection</h2>
<p class="muted mb-4">Create a MySQL/MariaDB database and user in your hosting panel (cPanel → MySQL® Databases), then enter the details.</p>
<form method="post" action="<?= e(url('/install/database')) ?>" class="stack" data-loading>
<?= csrf_field() ?>
<div class="grid-2"><div class="field"><label>Database host</label><input name="db_host" value="<?= e($db['host'] ?? 'localhost') ?>" required></div><div class="field"><label>Port</label><input name="db_port" type="number" value="<?= e($db['port'] ?? 3306) ?>" required></div></div>
<div class="field"><label>Database name</label><input name="db_name" value="<?= e($db['name'] ?? '') ?>" required></div>
<div class="grid-2"><div class="field"><label>Username</label><input name="db_user" value="<?= e($db['user'] ?? '') ?>" required autocomplete="off"></div><div class="field"><label>Password</label><input name="db_pass" type="password" autocomplete="new-password"></div></div>
<div class="field"><label>Table prefix</label><input name="db_prefix" value="<?= e($db['prefix'] ?? 'zx_') ?>" pattern="[A-Za-z0-9_]*"><small class="hint">Lets Zeroes share a database with other apps.</small></div>
<div class="row between mt-2"><a class="btn btn-ghost" href="<?= e(url('/install')) ?>"><?= icon('arrow-left') ?>Back</a><button class="btn btn-primary">Test & continue <?= icon('arrow-right') ?></button></div>
</form>
<?php else: ?>
<h2>Site & administrator</h2>
<p class="muted mb-4">Almost done. These can all be changed later in Settings.</p>
<form method="post" action="<?= e(url('/install/finish')) ?>" class="stack" data-loading>
<?= csrf_field() ?>
<div class="grid-2"><div class="field"><label>Site name</label><input name="site_name" value="<?= e(old('site_name', 'Zeroes')) ?>" required></div>
<div class="field"><label>Site URL</label><input name="site_url" type="url" value="<?= e(old('site_url', $guessUrl)) ?>" required><small class="hint">Use https:// if you have SSL.</small></div></div>
<div class="grid-2"><div class="field"><label>Timezone</label><select name="timezone"><?php foreach (DateTimeZone::listIdentifiers() as $tz): ?><option<?= selected($tz, old('timezone', date_default_timezone_get() === 'UTC' ? 'Asia/Kolkata' : 'UTC')) ?>><?= e($tz) ?></option><?php endforeach; ?></select><small class="hint">Lesson unlock times use this timezone.</small></div>
<div class="field"><label>Sender email (optional)</label><input name="mail_from" type="email" value="<?= e(old('mail_from')) ?>" placeholder="no-reply@yourdomain.com"></div></div>
<hr>
<div class="grid-2"><div class="field"><label>Your name</label><input name="admin_name" value="<?= e(old('admin_name')) ?>" required></div><div class="field"><label>Admin email</label><input name="admin_email" type="email" value="<?= e(old('admin_email')) ?>" required autocomplete="username"></div></div>
<div class="grid-2"><div class="field"><label>Password</label><input name="password" type="password" required minlength="8" autocomplete="new-password" data-strength><div class="strength"><i></i></div></div><div class="field"><label>Confirm password</label><input name="password_confirmation" type="password" required autocomplete="new-password"></div></div>
<label class="check"><input type="checkbox" name="demo" value="1" checked><span>Create a sample 21-day course to explore the student experience</span></label>
<div class="row between mt-2"><a class="btn btn-ghost" href="<?= e(url('/install', ['step' => 2])) ?>"><?= icon('arrow-left') ?>Back</a><button class="btn btn-primary btn-lg">Install Zeroes <?= icon('sparkles') ?></button></div>
</form>
<?php endif; ?>
</div>
