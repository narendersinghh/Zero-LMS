<?php
namespace App\Core;

/**
 * Built-in email provider presets for the "Email delivery" settings page.
 * Each preset describes which methods it supports (api/smtp), SMTP defaults, credential labels and setup steps.
 */
class MailProviders {
    public static function all(): array {
        return [
            'brevo' => ['name' => 'Brevo', 'aka' => 'formerly Sendinblue', 'color' => '#0b996e', 'tagline' => 'Easy setup, generous free plan, great for India & EU.', 'methods' => ['api', 'smtp'],
                'smtp' => ['host' => 'smtp-relay.brevo.com', 'port' => 587, 'secure' => 'tls', 'user_label' => 'SMTP login (shown in Brevo, usually your account email)', 'pass_label' => 'SMTP key (not your Brevo password)'],
                'api' => ['key_label' => 'API key (v3)'],
                'steps' => ['Create a free account at brevo.com and verify your sender email or domain (Senders, Domains & Dedicated IPs).', 'For API: open Settings → SMTP & API → API keys → Generate a new API key, then paste it here.', 'For SMTP: open Settings → SMTP & API → SMTP tab, copy the SMTP login and generate an SMTP key.'],
                'docs' => 'https://help.brevo.com/hc/en-us/articles/209467485'],
            'mailjet' => ['name' => 'Mailjet', 'color' => '#9585f4', 'tagline' => 'Reliable sending with a free plan.', 'methods' => ['api', 'smtp'],
                'smtp' => ['host' => 'in-v3.mailjet.com', 'port' => 587, 'secure' => 'tls', 'user_label' => 'API key (used as SMTP username)', 'pass_label' => 'Secret key (used as SMTP password)'],
                'api' => ['key_label' => 'API key', 'secret_label' => 'Secret key'],
                'steps' => ['Sign up at mailjet.com and validate your sender address or domain.', 'Open Account settings → REST API → API key management (Primary and Sub-account).', 'Copy the API key and the Secret key. Both are needed for API and SMTP.'],
                'docs' => 'https://documentation.mailjet.com/hc/en-us/articles/360043229473'],
            'sendgrid' => ['name' => 'SendGrid', 'aka' => 'Twilio', 'color' => '#1a82e2', 'tagline' => 'Popular, high-volume transactional email.', 'methods' => ['api', 'smtp'],
                'smtp' => ['host' => 'smtp.sendgrid.net', 'port' => 587, 'secure' => 'tls', 'user_label' => 'Username: type the word apikey', 'pass_label' => 'API key', 'user_fixed' => 'apikey'],
                'api' => ['key_label' => 'API key (Mail Send permission)'],
                'steps' => ['Create a SendGrid account and complete Sender Authentication (single sender or domain).', 'Go to Settings → API Keys → Create API Key with “Mail Send” access.', 'Paste the key here. For SMTP the username is literally “apikey”.'],
                'docs' => 'https://docs.sendgrid.com/for-developers/sending-email/integrating-with-the-smtp-api'],
            'mailgun' => ['name' => 'Mailgun', 'color' => '#f06b66', 'tagline' => 'Developer-friendly, US and EU regions.', 'methods' => ['api', 'smtp'],
                'smtp' => ['host' => 'smtp.mailgun.org', 'port' => 587, 'secure' => 'tls', 'user_label' => 'SMTP username (e.g. postmaster@mg.yourdomain.com)', 'pass_label' => 'SMTP password'],
                'api' => ['key_label' => 'Private API key', 'domain' => true],
                'steps' => ['Add and verify your sending domain in Mailgun (Sending → Domains) by adding the DNS records it shows.', 'For API: copy your Private API key (API Security) and the domain name, and pick your region (US or EU).', 'For SMTP: open the domain → SMTP credentials and create a login. EU accounts use smtp.eu.mailgun.org.'],
                'docs' => 'https://documentation.mailgun.com/docs/mailgun/user-manual/get-started/'],
            'postmark' => ['name' => 'Postmark', 'color' => '#ffde00', 'tagline' => 'Excellent inbox delivery for transactional email.', 'methods' => ['api', 'smtp'],
                'smtp' => ['host' => 'smtp.postmarkapp.com', 'port' => 587, 'secure' => 'tls', 'user_label' => 'Server API token (used as username)', 'pass_label' => 'Server API token (same token again)'],
                'api' => ['key_label' => 'Server API token'],
                'steps' => ['Create a Postmark server and verify a sender signature or domain.', 'Open your server → API Tokens and copy the Server API token.', 'Paste it here (for SMTP use the token as both username and password).'],
                'docs' => 'https://postmarkapp.com/developer/user-guide/send-email-with-smtp'],
            'ses' => ['name' => 'Amazon SES', 'color' => '#ff9900', 'tagline' => 'Very low cost at scale (AWS account needed).', 'methods' => ['smtp'],
                'smtp' => ['host' => 'email-smtp.us-east-1.amazonaws.com', 'port' => 587, 'secure' => 'tls', 'user_label' => 'SMTP username (from SES SMTP settings)', 'pass_label' => 'SMTP password', 'host_editable' => true],
                'steps' => ['In the AWS console open SES and verify your domain or sender email. Request production access to leave the sandbox.', 'Open SMTP settings → Create SMTP credentials (these are not your AWS keys).', 'Use the SMTP endpoint of your region, e.g. email-smtp.ap-south-1.amazonaws.com for Mumbai.'],
                'docs' => 'https://docs.aws.amazon.com/ses/latest/dg/send-email-smtp.html'],
            'zoho' => ['name' => 'Zoho Mail', 'color' => '#e42527', 'tagline' => 'Use your Zoho business mailbox.', 'methods' => ['smtp'],
                'smtp' => ['host' => 'smtp.zoho.in', 'port' => 465, 'secure' => 'ssl', 'user_label' => 'Your Zoho email address', 'pass_label' => 'Password or app-specific password', 'host_editable' => true],
                'steps' => ['Use smtp.zoho.in for India accounts (smtp.zoho.com for global, smtp.zoho.eu for EU).', 'If two-factor authentication is on, create an app-specific password in Zoho Accounts → Security → App Passwords.', 'The “From email” must be your Zoho address (or an alias of it).'],
                'docs' => 'https://www.zoho.com/mail/help/zoho-smtp.html'],
            'gmail' => ['name' => 'Gmail / Google Workspace', 'color' => '#ea4335', 'tagline' => 'Fine for small volumes (Google daily limits apply).', 'methods' => ['smtp'],
                'smtp' => ['host' => 'smtp.gmail.com', 'port' => 587, 'secure' => 'tls', 'user_label' => 'Your Gmail / Workspace address', 'pass_label' => '16-character App Password'],
                'steps' => ['Turn on 2-Step Verification for the Google account.', 'Open myaccount.google.com/apppasswords and create an App Password (name it “Zeroes”).', 'Paste the 16-character App Password here. Your normal Gmail password will not work.'],
                'docs' => 'https://support.google.com/accounts/answer/185833'],
            'outlook' => ['name' => 'Outlook / Microsoft 365', 'color' => '#0078d4', 'tagline' => 'For Microsoft 365 business mailboxes.', 'methods' => ['smtp'],
                'smtp' => ['host' => 'smtp.office365.com', 'port' => 587, 'secure' => 'tls', 'user_label' => 'Your Microsoft 365 email address', 'pass_label' => 'Password or app password'],
                'steps' => ['Your Microsoft 365 admin must enable “Authenticated SMTP” for this mailbox (Microsoft 365 admin center → Users → Mail → Manage email apps).', 'If MFA is enabled, create an app password.', 'The “From email” must be this mailbox.'],
                'docs' => 'https://learn.microsoft.com/en-us/exchange/mail-flow-best-practices/how-to-set-up-a-multifunction-device-or-application-to-send-email-using-microsoft-365-or-office-365'],
            'hostinger' => ['name' => 'Hostinger email', 'color' => '#673de6', 'tagline' => 'Use a mailbox from your Hostinger plan.', 'methods' => ['smtp'],
                'smtp' => ['host' => 'smtp.hostinger.com', 'port' => 465, 'secure' => 'ssl', 'user_label' => 'Full email address', 'pass_label' => 'Mailbox password'],
                'steps' => ['Create a mailbox in hPanel → Emails (e.g. no-reply@yourdomain.com).', 'Use the full email address and its password here.', 'The “From email” should be the same mailbox.'],
                'docs' => 'https://support.hostinger.com/en/articles/1575756-how-to-get-email-account-configuration-details-for-hostinger-email'],
            'cpanel' => ['name' => 'cPanel / hosting email', 'color' => '#ff6c2c', 'tagline' => 'The mailbox that came with your web hosting.', 'methods' => ['smtp'],
                'smtp' => ['host' => 'mail.yourdomain.com', 'port' => 465, 'secure' => 'ssl', 'user_label' => 'Full email address', 'pass_label' => 'Mailbox password', 'host_editable' => true],
                'steps' => ['In cPanel open Email Accounts and create a mailbox (e.g. no-reply@yourdomain.com).', 'Click “Connect Devices” next to it to see the outgoing server, usually mail.yourdomain.com on port 465 (SSL).', 'Enter that server, the full email address and its password.'],
                'docs' => 'https://docs.cpanel.net/cpanel/email/email-accounts/'],
            'custom' => ['name' => 'Other SMTP server', 'color' => '#64748b', 'tagline' => 'Any provider that gives you SMTP details.', 'methods' => ['smtp'],
                'smtp' => ['host' => '', 'port' => 587, 'secure' => 'tls', 'user_label' => 'SMTP username', 'pass_label' => 'SMTP password', 'host_editable' => true],
                'steps' => ['Ask your provider for: SMTP host, port, encryption (TLS/SSL), username and password.', 'Port 587 normally uses STARTTLS, port 465 uses SSL. Some shared hosts block both. Try port 2525 or an API provider.'],
                'docs' => ''],
            'phpmail' => ['name' => 'PHP mail()', 'color' => '#4f5b93', 'tagline' => 'No setup, but emails often land in spam.', 'methods' => ['mail'], 'steps' => ['Uses your server\'s built-in mail function. Works on many shared hosts, but deliverability is poor and some hosts disable it. Use a provider above for real students.'], 'docs' => ''],
            'log' => ['name' => 'Log only (testing)', 'color' => '#a1a1aa', 'tagline' => 'Nothing is sent, emails are written to a log file.', 'methods' => ['log'], 'steps' => ['Emails are saved to storage/logs/mail-YYYY-MM.log so you can test without sending. Switch to a real provider before inviting students.'], 'docs' => ''],];}

    public static function get(string $key): ?array {
        return self::all()[$key] ?? null;}

    /** Best guess of the provider for installs configured before presets existed. */
    public static function current(): string {
        $p = (string)Settings::get('mail_provider', '');
        if ($p && self::get($p)) {
            return $p;}
        $d = Settings::get('mail_driver') ?: Env::get('MAIL_DRIVER', 'log');
        if ($d === 'mail') {
            return 'phpmail';}
        if ($d !== 'smtp') {
            return 'log';}
        $host = strtolower((string)(Settings::get('smtp_host') ?: Env::get('SMTP_HOST', '')));
        foreach (self::all() as $k => $pv) {
            if (!empty($pv['smtp']['host']) && $pv['smtp']['host'] === $host) {
                return $k;}}
        return 'custom';}}
