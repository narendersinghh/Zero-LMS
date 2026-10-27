<?php
$layout = 'admin';
$title = 'Settings';
include \App\Core\View::find('admin/settings/_tabs');
$cur = $providers[$provider];
$sp = $cur['smtp'] ?? null;
$hasApi = in_array('api', $cur['methods'], true);
$hasSmtp = in_array('smtp', $cur['methods'], true);
$m = $hasApi && ($method === 'api' || !$hasSmtp) ? 'api' : 'smtp';
$isReal = $hasApi || $hasSmtp;
$label = $driver === 'log' ? 'Log only' : ($driver === 'mail' ? 'PHP mail()' : $cur['name'] . ($driver === 'api' ? ' · API' : ' · SMTP'));
$hostVal = $s['smtp_host'] && $provider === \App\Core\MailProviders::current() ? $s['smtp_host'] : ($sp['host'] ?? '');
// Compact client-side description of every provider (labels, defaults) for the field toggles in mail.js.
$js = [];
foreach ($providers as $k => $p) {
    $js[$k] = ['m' => $p['methods'], 'h' => $p['smtp']['host'] ?? '', 'p' => $p['smtp']['port'] ?? 587, 's' => $p['smtp']['secure'] ?? 'tls', 'he' => !empty($p['smtp']['host_editable']), 'uf' => $p['smtp']['user_fixed'] ?? '',
        'ul' => $p['smtp']['user_label'] ?? '', 'pl' => $p['smtp']['pass_label'] ?? '', 'kl' => $p['api']['key_label'] ?? '', 'sl' => $p['api']['secret_label'] ?? '', 'd' => !empty($p['api']['domain']), 'n' => $p['name']];}
$hidden = fn(bool $show) => $show ? '' : ' hidden';
?>
<div class="settings-hero <?= $driver === 'log' ? 'tint-yellow' : 'tint-blue' ?>"><span class="sh-ico"><?= icon('mail') ?></span><div><h2>Email delivery</h2><p><?= $driver === 'log' ? 'Emails are being written to a log file — no emails are delivered to students yet. Pick a provider below to start sending.' : 'Connected via ' . e($label) . '. Account setup links, enrollment and lesson emails are delivered through it.' ?></p></div><span class="badge badge-<?= $driver === 'log' ? 'warning' : 'success' ?>"><?= e($label) ?></span></div>
<?php if ($lastError): ?><div class="alert alert-danger"><?= icon('alert') ?><span><b>Last delivery error</b> (<?= e(time_ago($lastError['created_at'])) ?>): <?= e(str_limit($lastError['last_error'], 220)) ?></span><a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/mail-log', ['status' => 'failed'])) ?>">Email log</a></div><?php endif; ?>

<form method="post" action="<?= e(url('/admin/settings/mail')) ?>" class="stack-lg mail-form" data-loading data-mail-form data-providers="<?= e(json_encode($js)) ?>" data-test-url="<?= e(url('/admin/settings/mail/test')) ?>">
<?= csrf_field() ?>
<section class="set-sec">
<div class="set-desc"><span class="step-pill">Step 1</span><h3>Choose your email provider</h3><p>On shared hosting, sending through a provider keeps your emails out of spam. <b>Brevo</b> or <b>Mailjet</b> are the easiest to start with — both have free plans and an API, so no SMTP ports are needed.</p></div>
<div class="card set-fields">
<div class="mp-grid" role="radiogroup" aria-label="Email provider">
<?php foreach ($providers as $k => $p): ?>
<label class="mp-card"><input type="radio" name="mail_provider" value="<?= e($k) ?>"<?= checked($k === $provider) ?>>
<span class="mp-logo" style="--c:<?= e($p['color']) ?>"><?= e(mb_strtoupper(mb_substr($p['name'], 0, 1))) ?></span>
<span class="mp-text"><b><?= e($p['name']) ?></b><?php if (!empty($p['aka'])): ?><small class="muted"><?= e($p['aka']) ?></small><?php endif; ?><small><?= e($p['tagline']) ?></small>
<span class="mp-chips"><?php if (in_array('api', $p['methods'], true)): ?><i>API</i><?php endif; ?><?php if (in_array('smtp', $p['methods'], true)): ?><i>SMTP</i><?php endif; ?><?php if ($k === 'log'): ?><i>Testing</i><?php endif; ?></span></span>
</label>
<?php endforeach; ?>
</div>
</div>
</section>

<section class="set-sec" data-connect<?= $hidden($isReal) ?>>
<div class="set-desc"><span class="step-pill">Step 2</span><h3>Connect <span data-pname><?= e($cur['name']) ?></span></h3><p>Paste the credentials from your provider. Keys and passwords are stored encrypted and never shown again — leave a field blank to keep the saved value.</p></div>
<div class="card set-fields">
<div class="mp-method" data-method-wrap<?= $hidden($hasApi && $hasSmtp) ?>>
<label><input type="radio" name="mail_method" value="api"<?= checked($m === 'api') ?>><span><b>API</b> <em class="pill pill-accent">Recommended</em><small>Uses HTTPS — works even when the host blocks mail ports.</small></span></label>
<label><input type="radio" name="mail_method" value="smtp"<?= checked($m === 'smtp') ?>><span><b>SMTP</b><small>Classic mail server login (ports 587 / 465).</small></span></label>
</div>
<div class="stack" data-api-fields<?= $hidden($hasApi && $m === 'api') ?>>
<div class="field"><label for="mail_api_key" data-key-label><?= e($cur['api']['key_label'] ?? 'API key') ?></label><input id="mail_api_key" type="password" name="mail_api_key" autocomplete="new-password" placeholder="<?= $hasKey ? '•••••••• saved — leave blank to keep' : 'Paste your key' ?>"></div>
<div class="field" data-secret-field<?= $hidden(!empty($cur['api']['secret_label'])) ?>><label for="mail_api_secret" data-secret-label><?= e($cur['api']['secret_label'] ?? 'Secret key') ?></label><input id="mail_api_secret" type="password" name="mail_api_secret" autocomplete="new-password" placeholder="<?= $hasSecret ? '•••••••• saved — leave blank to keep' : 'Paste your secret key' ?>"></div>
<div class="grid-2" data-domain-field<?= $hidden(!empty($cur['api']['domain'])) ?>>
<div class="field"><label for="mailgun_domain">Sending domain</label><input id="mailgun_domain" name="mailgun_domain" value="<?= e($s['mailgun_domain'] ?? '') ?>" placeholder="mg.yourdomain.com"></div>
<div class="field"><label for="mailgun_region">Region</label><select id="mailgun_region" name="mailgun_region"><option value="us"<?= selected('us', $s['mailgun_region'] ?? 'us') ?>>US (api.mailgun.net)</option><option value="eu"<?= selected('eu', $s['mailgun_region'] ?? 'us') ?>>EU (api.eu.mailgun.net)</option></select></div>
</div>
</div>
<div class="stack" data-smtp-fields<?= $hidden($hasSmtp && $m === 'smtp') ?>>
<div class="smtp-server" data-smtp-fixed<?= $hidden($hasSmtp && empty($sp['host_editable'])) ?>><?= icon('shield') ?><span>Server <b data-host-text><?= e($sp['host'] ?? '') ?></b> · port <b data-port-text><?= (int)($s['smtp_port'] ?: ($sp['port'] ?? 587)) ?></b> · <b data-secure-text><?= e(strtoupper($s['smtp_secure'] ?: ($sp['secure'] ?? 'tls'))) ?></b> — filled in for you.</span></div>
<div class="field" data-host-field<?= $hidden($hasSmtp && !empty($sp['host_editable'])) ?>><label for="smtp_host">SMTP server</label><input id="smtp_host" name="smtp_host" value="<?= e($hostVal) ?>" placeholder="smtp.example.com"></div>
<div class="field" data-user-field<?= $hidden(empty($sp['user_fixed'])) ?>><label for="smtp_user" data-user-label><?= e($sp['user_label'] ?? 'SMTP username') ?></label><input id="smtp_user" name="smtp_user" value="<?= e($provider === \App\Core\MailProviders::current() ? $s['smtp_user'] : '') ?>" autocomplete="off"></div>
<div class="field"><label for="smtp_pass" data-pass-label><?= e($sp['pass_label'] ?? 'SMTP password') ?></label><input id="smtp_pass" type="password" name="smtp_pass" autocomplete="new-password" placeholder="<?= $hasPass ? '•••••••• saved — leave blank to keep' : '' ?>"></div>
<details class="mp-adv"><summary>Port & encryption</summary>
<div class="grid-2 mt-3"><div class="field"><label for="smtp_port">Port</label><input id="smtp_port" type="number" name="smtp_port" value="<?= e($s['smtp_port'] ?: ($sp['port'] ?? 587)) ?>"><small class="hint">587 (TLS) or 465 (SSL). Blocked? Try 2525.</small></div>
<div class="field"><label for="smtp_secure">Encryption</label><select id="smtp_secure" name="smtp_secure"><option value="tls"<?= selected('tls', $s['smtp_secure'] ?: ($sp['secure'] ?? 'tls')) ?>>STARTTLS (587)</option><option value="ssl"<?= selected('ssl', $s['smtp_secure'] ?: ($sp['secure'] ?? 'tls')) ?>>SSL/TLS (465)</option><option value="none"<?= selected('none', $s['smtp_secure']) ?>>None</option></select></div></div>
</details>
</div>
<div class="mp-help">
<b><?= icon('info') ?> Where to find your credentials</b>
<?php foreach ($providers as $k => $p): ?><div data-steps-for="<?= e($k) ?>"<?= $hidden($k === $provider) ?>><ol><?php foreach ($p['steps'] as $st): ?><li><?= e($st) ?></li><?php endforeach; ?></ol><?php if ($p['docs']): ?><a href="<?= e($p['docs']) ?>" target="_blank" rel="noopener noreferrer"><?= icon('external') ?> <?= e($p['name']) ?> setup guide</a><?php endif; ?></div><?php endforeach; ?>
</div>
</div>
</section>
<div class="note small" data-nosetup<?= $hidden(!$isReal) ?>><?php foreach (['phpmail', 'log'] as $k): ?><span data-steps-for="<?= $k ?>"<?= $hidden($k === $provider) ?>><?= e($providers[$k]['steps'][0]) ?></span><?php endforeach; ?></div>

<section class="set-sec">
<div class="set-desc"><span class="step-pill">Step 3</span><h3>Sender</h3><p>Use an address on your own domain and verify it (or the whole domain) with your provider. Adding the SPF and DKIM DNS records the provider shows keeps emails out of spam.</p></div>
<div class="card set-fields">
<div class="grid-2">
<div class="field"><label for="mail_from_name">From name</label><input id="mail_from_name" name="mail_from_name" value="<?= e($s['mail_from_name']) ?>" placeholder="<?= e($s['site_name']) ?>" maxlength="100"></div>
<div class="field"><label for="mail_from_email">From email</label><input id="mail_from_email" type="email" name="mail_from_email" value="<?= e($s['mail_from_email']) ?>" placeholder="no-reply@yourdomain.com"><small class="hint">Must be a sender verified with your provider.</small></div>
</div>
<details class="mp-adv"><summary>Sending limits</summary>
<div class="field mt-3"><label for="mail_batch_size">Emails per batch</label><input id="mail_batch_size" type="number" min="1" max="500" name="mail_batch_size" value="<?= e($s['mail_batch_size']) ?>"><small class="hint">Queued emails are sent in batches every few minutes (cron). Keep this under your provider's or host's hourly limit.</small></div>
</details>
</div>
</section>

<section class="set-sec">
<div class="set-desc"><span class="step-pill">Step 4</span><h3>Send a test</h3><p>Uses the details in this form — nothing is saved until you click Save. Errors from your provider are shown exactly as received.</p></div>
<div class="card set-fields">
<div class="mp-test"><div class="field grow"><label for="test_to">Send test to</label><input id="test_to" type="email" name="test_to" value="<?= e($adminEmail) ?>"></div><button type="button" class="btn btn-dark" data-mail-test><?= icon('mail') ?>Send test email</button></div>
<div class="mp-result" data-mail-result hidden></div>
</div>
</section>

<section class="set-sec">
<div class="set-desc"><h3>Help</h3><p>Quick answers to the most common problems.</p></div>
<div class="card set-fields mp-faq small">
<details><summary>Why do I need a provider at all?</summary><p>Shared-hosting servers have poor sending reputation, so mail from them often lands in spam or is silently dropped. A provider (Brevo, Mailjet, SendGrid…) sends from trusted servers and tells you if something bounces.</p></details>
<details><summary>API or SMTP — which should I pick?</summary><p>API if your provider offers it: it uses normal HTTPS, so it works even when your host blocks mail ports 25/465/587, and it's faster. SMTP works with any mailbox (Gmail, Zoho, cPanel).</p></details>
<details><summary>“Authentication failed” / 535 / 401</summary><p>The login or key is wrong. Copy it again without spaces. Brevo SMTP needs the SMTP key (not your password); Gmail needs a 16-character App Password; SendGrid's SMTP username is literally <code>apikey</code>.</p></details>
<details><summary>“Connection timed out” / “Could not connect”</summary><p>Your host is blocking the port. Try port 2525 (Brevo, Mailjet, SendGrid, Mailgun support it), or switch to the API method.</p></details>
<details><summary>“Sender not verified” / emails go to spam</summary><p>Verify the From address or domain at your provider and add the SPF, DKIM (and ideally DMARC) DNS records it gives you in your domain's DNS zone.</p></details>
<details><summary>Test worked, but students don't get emails</summary><p>Emails are queued and sent by the scheduled task. Make sure the cron job from <a href="<?= e(url('/admin/system')) ?>">System & updates</a> is set up, or “web cron” is enabled in Learning settings. Check the <a href="<?= e(url('/admin/mail-log')) ?>">email log</a> for failures.</p></details>
</div>
</section>

<div class="set-actions"><span class="muted">Queued <?= (int)($stats['pending'] ?? 0) ?> · Sent <?= (int)($stats['sent'] ?? 0) ?> · Failed <?= (int)($stats['failed'] ?? 0) ?></span><a class="btn btn-ghost" href="<?= e(url('/admin/emails')) ?>">Email templates</a><button class="btn btn-primary"><?= icon('check') ?>Save email settings</button></div>
</form>
<script src="<?= e(asset('dist/mail.min.js')) ?>" defer></script>
<?php include \App\Core\View::find('admin/settings/_end'); ?>
