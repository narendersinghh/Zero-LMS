<?php $layout = 'admin'; $title = 'Settings'; include \App\Core\View::find('admin/settings/_tabs'); ?>
<form method="post" action="<?= e(url('/admin/settings/mail')) ?>" class="card stack narrow" data-loading>
<?= csrf_field() ?>
<div class="field"><label>Delivery method</label>
<div class="seg seg-v" data-seg="mail_driver">
<label><input type="radio" name="mail_driver" value="smtp"<?= checked(($s['mail_driver'] ?: env('MAIL_DRIVER', 'log')) === 'smtp') ?>><span><b>SMTP</b> — recommended (your host's mail server, Brevo, Mailgun, SES, Gmail/Workspace…)</span></label>
<label><input type="radio" name="mail_driver" value="mail"<?= checked(($s['mail_driver'] ?: env('MAIL_DRIVER', 'log')) === 'mail') ?>><span><b>PHP mail()</b> — simplest on shared hosting, lower deliverability</span></label>
<label><input type="radio" name="mail_driver" value="log"<?= checked(($s['mail_driver'] ?: env('MAIL_DRIVER', 'log')) === 'log') ?>><span><b>Log only</b> — for testing; writes emails to storage/logs</span></label>
</div></div>
<div class="grid-2"><div class="field"><label>From name</label><input name="mail_from_name" value="<?= e($s['mail_from_name']) ?>" placeholder="<?= e($s['site_name']) ?>"></div><div class="field"><label>From email</label><input type="email" name="mail_from_email" value="<?= e($s['mail_from_email']) ?>" placeholder="no-reply@yourdomain.com"><small class="hint">Use an address on your own domain (SPF/DKIM).</small></div></div>
<div data-show-when="mail_driver=smtp" class="stack">
<div class="grid-3"><div class="field"><label>SMTP host</label><input name="smtp_host" value="<?= e($s['smtp_host']) ?>" placeholder="mail.yourdomain.com"></div><div class="field"><label>Port</label><input type="number" name="smtp_port" value="<?= e($s['smtp_port']) ?>"></div><div class="field"><label>Encryption</label><select name="smtp_secure"><option value="tls"<?= selected('tls', $s['smtp_secure']) ?>>STARTTLS (587)</option><option value="ssl"<?= selected('ssl', $s['smtp_secure']) ?>>SSL/TLS (465)</option><option value="none"<?= selected('none', $s['smtp_secure']) ?>>None</option></select></div></div>
<div class="grid-2"><div class="field"><label>Username</label><input name="smtp_user" value="<?= e($s['smtp_user']) ?>" autocomplete="off"></div><div class="field"><label>Password</label><input type="password" name="smtp_pass" placeholder="<?= $s['smtp_pass'] ? '•••••••• (saved, encrypted)' : '' ?>" autocomplete="new-password"></div></div>
</div>
<div class="field"><label>Emails per batch</label><input type="number" min="1" max="500" name="mail_batch_size" value="<?= e($s['mail_batch_size']) ?>"><small class="hint">Sent every cron run (~5 min). Keep under your host's hourly limit.</small></div>
<div class="row gap-2"><button class="btn btn-primary">Save</button><a class="btn btn-ghost" href="<?= e(url('/admin/emails/welcome/edit')) ?>"><?= icon('mail') ?>Send a test from a template</a></div>
</form>
