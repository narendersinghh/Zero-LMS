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
<div class="settings-hero <?= $driver === 'log' ? 'tint-yellow' : 'tint-blue' ?>"><span class="sh-ico"><?= icon('mail') ?></span><div><h2>Email delivery</h2><p><?= $driver === 'log' ? 'Emails are only written to a log file. Pick a provider to start delivering to students.' : 'Connected via ' . e($label) . '. Emails are sent automatically in the background.' ?></p></div><span class="badge badge-<?= $driver === 'log' ? 'warning' : 'success' ?>"><?= e($label) ?></span></div>
<?php if ($lastError): ?><div class="alert alert-danger"><?= icon('alert') ?><span><b>Last delivery error</b> (<?= e(time_ago($lastError['created_at'])) ?>): <?= e(str_limit($lastError['last_error'], 220)) ?></span><a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/mail-log', ['status' => 'failed'])) ?>">Email log</a></div><?php endif; ?>

<form method="post" action="<?= e(url('/admin/settings/mail')) ?>" class="mail-form" data-loading data-mail-form data-providers="<?= e(json_encode($js)) ?>" data-test-url="<?= e(url('/admin/settings/mail/test')) ?>">
<?= csrf_field() ?>
<div class="mail-main">
<div class="card mail-card">
<div class="mc-head"><span class="mc-n">1</span><div><h3>Provider</h3><small class="muted">Brevo or Mailjet are the easiest: free plans and an API, no mail ports needed.</small></div></div>
<?php $grp = ['Recommended: API and SMTP' => [], 'SMTP mailbox or server' => [], 'Other' => []];
foreach ($providers as $k => $p) {
    $grp[in_array('api', $p['methods'], true) ? 'Recommended: API and SMTP' : (in_array('smtp', $p['methods'], true) ? 'SMTP mailbox or server' : 'Other')][$k] = $p;} ?>
<div class="mp-pick"><span class="mp-logo" data-plogo style="--c:<?= e($cur['color']) ?>"><?= e(mb_strtoupper(mb_substr($cur['name'], 0, 1))) ?></span>
<div class="field grow"><label for="mail_provider" class="sr-only">Email provider</label><select id="mail_provider" name="mail_provider"><?php foreach ($grp as $g => $list): if (!$list) continue; ?><optgroup label="<?= e($g) ?>"><?php foreach ($list as $k => $p): ?><option value="<?= e($k) ?>" data-color="<?= e($p['color']) ?>" data-tag="<?= e($p['tagline']) ?>"<?= selected($k, $provider) ?>><?= e($p['name']) ?><?= !empty($p['aka']) ? ' (' . e($p['aka']) . ')' : '' ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select></div></div>
<p class="small muted m-0" data-ptag><?= e($cur['tagline']) ?></p>
<div class="note small m-0" data-nosetup<?= $hidden(!$isReal) ?>><?php foreach (['phpmail', 'log'] as $k): ?><span data-steps-for="<?= $k ?>"<?= $hidden($k === $provider) ?>><?= e($providers[$k]['steps'][0]) ?></span><?php endforeach; ?></div>
</div>

<div class="card mail-card" data-connect<?= $hidden($isReal) ?>>
<div class="mc-head"><span class="mc-n">2</span><div><h3>Connect <span data-pname><?= e($cur['name']) ?></span></h3><small class="muted">Stored encrypted. Leave a field blank to keep the saved value.</small></div></div>
<div class="mp-method" data-method-wrap<?= $hidden($hasApi && $hasSmtp) ?>>
<label><input type="radio" name="mail_method" value="api"<?= checked($m === 'api') ?>><span><b>API</b> <em class="pill pill-accent">Recommended</em><small>HTTPS, works when mail ports are blocked.</small></span></label>
<label><input type="radio" name="mail_method" value="smtp"<?= checked($m === 'smtp') ?>><span><b>SMTP</b><small>Mail server login (587 / 465).</small></span></label>
</div>
<div class="stack" data-api-fields<?= $hidden($hasApi && $m === 'api') ?>>
<div class="field"><label for="mail_api_key" data-key-label><?= e($cur['api']['key_label'] ?? 'API key') ?></label><input id="mail_api_key" type="password" name="mail_api_key" autocomplete="new-password" placeholder="<?= $hasKey ? '•••••••• saved, leave blank to keep' : 'Paste your key' ?>"></div>
<div class="field" data-secret-field<?= $hidden(!empty($cur['api']['secret_label'])) ?>><label for="mail_api_secret" data-secret-label><?= e($cur['api']['secret_label'] ?? 'Secret key') ?></label><input id="mail_api_secret" type="password" name="mail_api_secret" autocomplete="new-password" placeholder="<?= $hasSecret ? '•••••••• saved, leave blank to keep' : 'Paste your secret key' ?>"></div>
<div class="grid-2" data-domain-field<?= $hidden(!empty($cur['api']['domain'])) ?>>
<div class="field"><label for="mailgun_domain">Sending domain</label><input id="mailgun_domain" name="mailgun_domain" value="<?= e($s['mailgun_domain'] ?? '') ?>" placeholder="mg.yourdomain.com"></div>
<div class="field"><label for="mailgun_region">Region</label><select id="mailgun_region" name="mailgun_region"><option value="us"<?= selected('us', $s['mailgun_region'] ?? 'us') ?>>US (api.mailgun.net)</option><option value="eu"<?= selected('eu', $s['mailgun_region'] ?? 'us') ?>>EU (api.eu.mailgun.net)</option></select></div>
</div>
</div>
<div class="stack" data-smtp-fields<?= $hidden($hasSmtp && $m === 'smtp') ?>>
<div class="smtp-server" data-smtp-fixed<?= $hidden($hasSmtp && empty($sp['host_editable'])) ?>><?= icon('shield') ?><span>Server <b data-host-text><?= e($sp['host'] ?? '') ?></b> · port <b data-port-text><?= (int)($s['smtp_port'] ?: ($sp['port'] ?? 587)) ?></b> · <b data-secure-text><?= e(strtoupper($s['smtp_secure'] ?: ($sp['secure'] ?? 'tls'))) ?></b>, filled in for you.</span></div>
<div class="field" data-host-field<?= $hidden($hasSmtp && !empty($sp['host_editable'])) ?>><label for="smtp_host">SMTP server</label><input id="smtp_host" name="smtp_host" value="<?= e($hostVal) ?>" placeholder="smtp.example.com"></div>
<div class="grid-2">
<div class="field" data-user-field<?= $hidden(empty($sp['user_fixed'])) ?>><label for="smtp_user" data-user-label><?= e($sp['user_label'] ?? 'SMTP username') ?></label><input id="smtp_user" name="smtp_user" value="<?= e($provider === \App\Core\MailProviders::current() ? $s['smtp_user'] : '') ?>" autocomplete="off"></div>
<div class="field"><label for="smtp_pass" data-pass-label><?= e($sp['pass_label'] ?? 'SMTP password') ?></label><input id="smtp_pass" type="password" name="smtp_pass" autocomplete="new-password" placeholder="<?= $hasPass ? '•••••••• saved, leave blank to keep' : '' ?>"></div>
</div>
<details class="mp-adv"><summary>Port & encryption</summary>
<div class="grid-2 mt-3"><div class="field"><label for="smtp_port">Port</label><input id="smtp_port" type="number" name="smtp_port" value="<?= e($s['smtp_port'] ?: ($sp['port'] ?? 587)) ?>"><small class="hint">587 (TLS) or 465 (SSL). Blocked? Try 2525.</small></div>
<div class="field"><label for="smtp_secure">Encryption</label><select id="smtp_secure" name="smtp_secure"><option value="tls"<?= selected('tls', $s['smtp_secure'] ?: ($sp['secure'] ?? 'tls')) ?>>STARTTLS (587)</option><option value="ssl"<?= selected('ssl', $s['smtp_secure'] ?: ($sp['secure'] ?? 'tls')) ?>>SSL/TLS (465)</option><option value="none"<?= selected('none', $s['smtp_secure']) ?>>None</option></select></div></div>
</details>
</div>
</div>

<div class="card mail-card">
<div class="mc-head"><span class="mc-n">3</span><div><h3>Sender</h3><small class="muted">Use an address on your domain that is verified with your provider.</small></div></div>
<div class="grid-2">
<div class="field"><label for="mail_from_name">From name</label><input id="mail_from_name" name="mail_from_name" value="<?= e($s['mail_from_name']) ?>" placeholder="<?= e($s['site_name']) ?>" maxlength="100"></div>
<div class="field"><label for="mail_from_email">From email</label><input id="mail_from_email" type="email" name="mail_from_email" value="<?= e($s['mail_from_email']) ?>" placeholder="no-reply@yourdomain.com"></div>
</div>
<details class="mp-adv"><summary>Sending limits</summary>
<div class="field mt-3"><label for="mail_batch_size">Emails per batch</label><input id="mail_batch_size" type="number" min="1" max="500" name="mail_batch_size" value="<?= e($s['mail_batch_size']) ?>"><small class="hint">Keep this under your provider's or host's hourly limit.</small></div>
</details>
</div>
<div class="set-actions"><span class="muted">Queued <?= (int)($stats['pending'] ?? 0) ?> · Sent <?= (int)($stats['sent'] ?? 0) ?> · Failed <?= (int)($stats['failed'] ?? 0) ?></span><a class="btn btn-ghost" href="<?= e(url('/admin/emails')) ?>">Templates</a><button class="btn btn-primary"><?= icon('check') ?>Save</button></div>
</div>

<aside class="mail-side">
<div class="card mail-card">
<div class="mc-head"><span class="mc-n"><?= icon('mail') ?></span><div><h3>Send a test</h3><small class="muted">Uses this form, nothing is saved.</small></div></div>
<div class="field"><label for="test_to" class="sr-only">Send test to</label><input id="test_to" type="email" name="test_to" value="<?= e($adminEmail) ?>"></div>
<button type="button" class="btn btn-dark btn-block" data-mail-test><?= icon('mail') ?>Send test email</button>
<div class="mp-result" data-mail-result hidden></div>
</div>
<div class="card mail-card mp-help" data-connect<?= $hidden($isReal) ?>>
<b><?= icon('info') ?> Where to find your credentials</b>
<?php foreach ($providers as $k => $p): ?><div data-steps-for="<?= e($k) ?>"<?= $hidden($k === $provider) ?>><ol><?php foreach ($p['steps'] as $st): ?><li><?= e($st) ?></li><?php endforeach; ?></ol><?php if ($p['docs']): ?><a href="<?= e($p['docs']) ?>" target="_blank" rel="noopener noreferrer"><?= icon('external') ?> <?= e($p['name']) ?> guide</a><?php endif; ?></div><?php endforeach; ?>
</div>
<div class="card mail-card mp-faq small">
<b>Troubleshooting</b>
<details><summary>Authentication failed / 401</summary><p>The key or login is wrong. Copy it again without spaces. Brevo API keys start with <code>xkeysib-</code>; SMTP keys (<code>xsmtpsib-</code>) only work with SMTP. Gmail needs an App Password.</p></details>
<details><summary>Brevo: unrecognised IP address</summary><p>Brevo blocks API calls from new IPs. Open Brevo → Security → Authorised IPs and add your server IP, or turn the blocking off.</p></details>
<details><summary>Sender not valid / goes to spam</summary><p>Verify the From address or your domain at the provider and add the SPF and DKIM DNS records it shows.</p></details>
<details><summary>Connection timed out</summary><p>Your host blocks the port. Try 2525 or switch to the API method.</p></details>
<details><summary>Test works, students get nothing</summary><p>Emails are queued and sent automatically right after the page that created them. Check the <a href="<?= e(url('/admin/mail-log')) ?>">email log</a> for the exact provider error.</p></details>
</div>
</aside>
</form>
<script src="<?= e(asset('dist/mail.min.js')) ?>" defer></script>
<?php include \App\Core\View::find('admin/settings/_end'); ?>
