<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Crypto;
use App\Core\DB;
use App\Core\MailProviders;
use App\Core\Mailer;
use App\Core\Settings;

/** Settings → Email delivery: provider presets, API/SMTP credentials, sender and test email. */
class MailSettingsController extends Controller {
    public function show() {
        $provider = MailProviders::current();
        $driver = Settings::get('mail_driver') ?: env('MAIL_DRIVER', 'log');
        $lastError = DB::one("SELECT last_error, created_at FROM {mail_queue} WHERE last_error IS NOT NULL AND last_error <> '' ORDER BY id DESC LIMIT 1");
        $stats = DB::pairs('SELECT status, COUNT(*) FROM {mail_queue} GROUP BY status');
        return view('admin/settings/mail', ['tab' => 'mail', 's' => Settings::all(), 'providers' => MailProviders::all(), 'provider' => $provider, 'method' => $driver === 'api' ? 'api' : 'smtp',
            'driver' => $driver, 'lastError' => $lastError, 'stats' => $stats, 'adminEmail' => Auth::user()['email'],
            'hasKey' => (string)Settings::get('mail_api_key', '') !== '', 'hasSecret' => (string)Settings::get('mail_api_secret', '') !== '', 'hasPass' => (string)Settings::get('smtp_pass', '') !== '']);}

    /**
     * Turn the posted form into [settings to store, runtime Mailer config]. Blank secrets keep stored values
     * unless the provider changed (then a new key is required).
     */
    private function build(): array {
        $in = fn($k, $d = '') => trim((string)$this->req->input($k, $d));
        $key = $in('mail_provider', 'log');
        $p = MailProviders::get($key);
        if (!$p) {
            throw new \InvalidArgumentException('Choose an email provider.');}
        $sameProvider = MailProviders::current() === $key;
        $method = in_array('api', $p['methods'], true) && $in('mail_method', 'api') === 'api' ? 'api' : (in_array('smtp', $p['methods'], true) ? 'smtp' : $p['methods'][0]);
        $driver = ['mail' => 'mail', 'log' => 'log', 'api' => 'api', 'smtp' => 'smtp'][$method];
        $fromEmail = $in('mail_from_email');
        if ($fromEmail !== '' && !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Enter a valid “From” email address.');}
        if (in_array($driver, ['api', 'smtp'], true) && $fromEmail === '') {
            throw new \InvalidArgumentException('Enter the “From” email address — it must be a sender you verified with ' . $p['name'] . '.');}
        $save = ['mail_provider' => $key, 'mail_driver' => $driver, 'mail_from_email' => $fromEmail, 'mail_from_name' => mb_substr($in('mail_from_name'), 0, 100), 'mail_batch_size' => max(1, min(500, (int)$in('mail_batch_size', '40')))];
        $secret = function (string $field, string $setting, string $label) use ($in, $sameProvider) {
            $v = (string)($_POST[$field] ?? '');
            if ($v !== '') {
                return trim($v);}
            $stored = Crypto::decrypt((string)Settings::get($setting, ''));
            if ($stored === '' || !$sameProvider) {
                throw new \InvalidArgumentException('Enter the ' . $label . '.');}
            return $stored;};
        $cfg = ['driver' => $driver, 'provider' => $key, 'from' => $fromEmail ?: 'no-reply@' . (parse_url(base_url(), PHP_URL_HOST) ?: 'localhost'), 'from_name' => $save['mail_from_name'] ?: (string)Settings::get('site_name', 'Zeroes')];
        if ($driver === 'api') {
            $cfg['api_key'] = $secret('mail_api_key', 'mail_api_key', $p['api']['key_label']);
            $save['mail_api_key'] = Crypto::encrypt($cfg['api_key']);
            if (!empty($p['api']['secret_label'])) {
                $cfg['api_secret'] = $secret('mail_api_secret', 'mail_api_secret', $p['api']['secret_label']);
                $save['mail_api_secret'] = Crypto::encrypt($cfg['api_secret']);}
            if (!empty($p['api']['domain'])) {
                $cfg['mailgun_domain'] = $save['mailgun_domain'] = strtolower(preg_replace('/[^a-z0-9.-]/i', '', $in('mailgun_domain')));
                $cfg['mailgun_region'] = $save['mailgun_region'] = $in('mailgun_region') === 'eu' ? 'eu' : 'us';
                if ($cfg['mailgun_domain'] === '') {
                    throw new \InvalidArgumentException('Enter your Mailgun sending domain.');}}}
        elseif ($driver === 'smtp') {
            $sp = $p['smtp'];
            $host = !empty($sp['host_editable']) && $in('smtp_host') !== '' ? $in('smtp_host') : $sp['host'];
            if ($key === 'mailgun' && $in('mailgun_region') === 'eu') {
                $host = 'smtp.eu.mailgun.org';}
            if ($host === '' || !preg_match('/^[a-z0-9.-]+$/i', $host)) {
                throw new \InvalidArgumentException('Enter a valid SMTP server (host name).');}
            $port = (int)$in('smtp_port', (string)$sp['port']) ?: (int)$sp['port'];
            $secure = in_array($in('smtp_secure'), ['tls', 'ssl', 'none'], true) ? $in('smtp_secure') : $sp['secure'];
            $user = !empty($sp['user_fixed']) ? $sp['user_fixed'] : $in('smtp_user');
            if ($user === '') {
                throw new \InvalidArgumentException('Enter the ' . strtolower(strtok($sp['user_label'], '(')) . '.');}
            $pass = $secret('smtp_pass', 'smtp_pass', strtolower(strtok($sp['pass_label'], '(')));
            $save += ['smtp_host' => $host, 'smtp_port' => (string)$port, 'smtp_secure' => $secure, 'smtp_user' => $user, 'smtp_pass' => Crypto::encrypt($pass)];
            $cfg += ['host' => $host, 'port' => $port, 'secure' => $secure, 'user' => $user, 'pass' => $pass];}
        return [$save, $cfg, $p];}

    public function save() {
        try {
            [$save, , $p] = $this->build();}
        catch (\InvalidArgumentException $e) {
            flash('error', $e->getMessage());
            back();}
        Settings::set($save);
        Audit::log('settings.mail', 'settings', null, ['provider' => $save['mail_provider'], 'driver' => $save['mail_driver']]);
        $this->ok($save['mail_driver'] === 'log' ? 'Saved. Emails will be written to the log only.' : 'Saved. Emails will be sent with ' . $p['name'] . ($save['mail_driver'] === 'api' ? ' (API)' : ($save['mail_driver'] === 'smtp' ? ' (SMTP)' : '')) . '. Send a test to confirm.');}

    /** Sends a test using the values currently in the form (nothing is saved). */
    public function test() {
        $to = trim((string)$this->req->input('test_to', ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            json_out(['ok' => false, 'message' => 'Enter a valid email address to send the test to.']);}
        try {
            [, $cfg, $p] = $this->build();}
        catch (\InvalidArgumentException $e) {
            json_out(['ok' => false, 'message' => $e->getMessage()]);}
        $site = (string)Settings::get('site_name', 'Zeroes');
        $subject = 'Test email from ' . $site;
        $body = '<h1>It works! 🎉</h1><p>This test was sent from <strong>' . e($site) . '</strong> using <strong>' . e($p['name']) . '</strong>' . ($cfg['driver'] === 'api' ? ' (API)' : ($cfg['driver'] === 'smtp' ? ' (SMTP)' : '')) . '.</p><p>Your students will receive account, enrollment and lesson emails like this one.</p>';
        try {
            Mailer::sendNow($to, '', $subject, Mailer::layout($body, $subject), $cfg);}
        catch (\Throwable $e) {
            Audit::log('settings.mail_test_failed', 'settings', null, ['provider' => $cfg['provider'], 'error' => substr($e->getMessage(), 0, 200)]);
            json_out(['ok' => false, 'message' => $e->getMessage(), 'tips' => $this->tips($e->getMessage(), $cfg)], 200);}
        Audit::log('settings.mail_test', 'settings', null, ['provider' => $cfg['provider'], 'to' => $to]);
        json_out(['ok' => true, 'message' => $cfg['driver'] === 'log' ? 'Test written to storage/logs/mail-' . gmdate('Y-m') . '.log (log-only mode — nothing was delivered).' : 'Test email sent to ' . $to . '. Check the inbox (and spam folder). If it looks good, click Save.']);}

    private function tips(string $err, array $cfg): array {
        $e = strtolower($err);
        $t = [];
        if (strpos($e, 'connect') !== false || strpos($e, 'timed out') !== false || strpos($e, 'network') !== false) {
            $t[] = 'Your host may block outgoing mail ports. Try port 2525 or 465, or switch to an API provider (Brevo, Mailjet, SendGrid, Mailgun, Postmark) which uses normal HTTPS.';}
        $auth = strpos($e, '535') !== false || strpos($e, 'auth') !== false || strpos($e, '401') !== false || strpos($e, 'key not found') !== false || strpos($e, 'unauthorized') !== false;
        if ($auth) {
            $t[] = 'The username/password or API key was rejected. Copy it again from the provider (no spaces). Gmail needs an App Password, not your normal password.';}
        if (!$t && (strpos($e, 'sender') !== false || strpos($e, 'not verified') !== false || strpos($e, '550') !== false || strpos($e, 'from address') !== false)) {
            $t[] = 'Verify the “From” email or your domain with the provider, and make sure SPF/DKIM DNS records are added.';}
        if (strpos($e, 'certificate') !== false || strpos($e, 'starttls') !== false || strpos($e, 'ssl') !== false) {
            $t[] = 'Encryption mismatch: use port 587 with STARTTLS or port 465 with SSL.';}
        return $t ?: ['Double-check every field, then try again. The exact error from the server is shown above.'];}}
