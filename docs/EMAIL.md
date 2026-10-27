# Email delivery in Zeroes

Zeroes sends account setup links, password resets, enrollment confirmations, lesson-unlock reminders, payment receipts and announcements by email. This guide explains how to connect a mail provider in **Admin → Settings → Email delivery**.

## Why you need a provider

Shared-hosting servers (cPanel, Hostinger, DirectAdmin…) share IP addresses with thousands of other sites, so mail sent straight from them is often marked as spam or silently dropped. Many hosts also block the SMTP ports (25, 465, 587). A dedicated email provider sends from trusted servers, signs your messages, and reports bounces.

Until you choose a provider, Zeroes runs in **Log only** mode: emails are written to `storage/logs/mail-YYYY-MM.log` and **nothing is delivered**.

## Which one should I pick?

| Situation | Recommended |
|---|---|
| Just starting, want it to work in 5 minutes | **Brevo** (API) or **Mailjet** (API) — free plans, simple setup |
| High volume / developer tools | **SendGrid**, **Mailgun**, **Postmark**, **Amazon SES** |
| You already have business email | **Zoho Mail**, **Google Workspace / Gmail**, **Microsoft 365**, **Hostinger** or **cPanel** mailbox (SMTP) |
| Testing only | **Log only** |

### API or SMTP?

- **API** (Brevo, Mailjet, SendGrid, Mailgun, Postmark) uses normal HTTPS, so it works even when your host blocks mail ports. It is faster and returns clear error messages. **Use it when available.**
- **SMTP** is a classic mail-server login. It works with any mailbox (Gmail, Zoho, cPanel), but some hosts block the ports.

## Setup in 4 steps (inside Zeroes)

1. **Choose your provider** — click its card.
2. **Connect** — paste the API key (or SMTP username/password). Server, port and encryption are filled in for you.
3. **Sender** — enter a From name and a From email that you have verified with the provider.
4. **Send a test** — uses the values in the form without saving. When it arrives, click **Save**.

Keys and passwords are stored encrypted. To keep a saved key, leave the field blank.

## Provider guides

### Brevo (formerly Sendinblue)
1. Sign up at brevo.com.
2. **Senders, Domains & Dedicated IPs** → add and verify your sender email (or authenticate your domain — recommended).
3. API: **Settings → SMTP & API → API keys → Generate a new API key**. Choose *Brevo* → *API* in Zeroes and paste the key.
4. SMTP alternative: **SMTP & API → SMTP** tab. Login = the SMTP login shown there, password = an **SMTP key** (not your account password). Server `smtp-relay.brevo.com`, port 587.

### Mailjet
1. Sign up at mailjet.com and validate your sender address or domain.
2. **Account settings → REST API → API key management**.
3. Copy the **API key** and **Secret key** — both are needed (for SMTP they are the username and password; server `in-v3.mailjet.com`, port 587).

### Gmail / Google Workspace
1. Turn on **2-Step Verification** for the account.
2. Open **myaccount.google.com/apppasswords**, create an app password called "Zeroes".
3. In Zeroes choose *Gmail*, username = your full address, password = the 16-character app password. Your normal password will not work.
4. Google limits daily sending (roughly 500/day for Gmail, more for Workspace) — use a provider for large cohorts.

### Zoho Mail
- Server: `smtp.zoho.in` (India), `smtp.zoho.com` (global) or `smtp.zoho.eu` (EU), port 465 SSL.
- Username = your Zoho address. With two-factor on, create an **app-specific password** (Zoho Accounts → Security → App Passwords).
- The From email must be that mailbox or one of its aliases.

### cPanel / hosting email
1. cPanel → **Email Accounts** → create e.g. `no-reply@yourdomain.com`.
2. Click **Connect Devices** to see the outgoing server (usually `mail.yourdomain.com`, port 465 SSL).
3. Use the full address and mailbox password.
4. If the test times out, your host blocks outgoing SMTP — switch to an API provider.

### SendGrid
1. Complete **Sender Authentication** (single sender or domain).
2. **Settings → API Keys → Create API Key** with *Mail Send* access.
3. API: paste the key. SMTP: username is the literal word `apikey`, password = the key.

### Mailgun
1. **Sending → Domains**: add a sending domain (e.g. `mg.yourdomain.com`) and add the DNS records shown.
2. API: copy the **Private API key**, enter the domain and region (US or EU).
3. SMTP: domain → **SMTP credentials**. EU accounts use `smtp.eu.mailgun.org`.

### Amazon SES
1. In SES, verify your domain (or address) and request **production access** to leave the sandbox.
2. **SMTP settings → Create SMTP credentials** (these are not your AWS access keys).
3. Use your region's endpoint, e.g. `email-smtp.ap-south-1.amazonaws.com` (Mumbai), port 587.

### Postmark
1. Create a server and verify a sender signature or domain.
2. Copy the **Server API token** and paste it (API), or use it as both SMTP username and password.

## DNS basics: SPF, DKIM, DMARC

Your provider shows the exact records to add in your domain's DNS (cPanel → Zone Editor, Hostinger → DNS Zone, Cloudflare → DNS):

- **SPF** (TXT): lists who may send for your domain. Only one SPF record per domain — merge includes, e.g. `v=spf1 include:spf.brevo.com include:_spf.google.com ~all`.
- **DKIM** (TXT or CNAME): a signature key proving the email wasn't altered.
- **DMARC** (TXT at `_dmarc`): tells inboxes what to do with failures. Start with `v=DMARC1; p=none; rua=mailto:you@yourdomain.com`.

DNS changes can take up to a few hours. Most providers have a "verify" button.

## Troubleshooting

| Error | What it means | Fix |
|---|---|---|
| `535 Authentication failed`, HTTP 401, "Key not found" | Wrong username/password/key | Copy the credential again (no spaces). Brevo SMTP needs the SMTP key; Gmail needs an App Password; SendGrid SMTP user is `apikey`. |
| `Connection timed out`, `Could not connect` | Host blocks the port | Try port 2525 (Brevo, Mailjet, SendGrid, Mailgun) or use the API method. |
| "Sender not verified", 550 rejected | From address/domain not verified | Verify the sender or authenticate the domain with your provider. |
| `STARTTLS` / certificate errors | Encryption doesn't match the port | 587 → STARTTLS, 465 → SSL. |
| Test works but students get nothing | Queue isn't being processed | Set up the cron job shown in **System & updates** or enable web cron in **Settings → Learning**. Check **Email templates → Email log**. |
| Emails land in spam | Missing SPF/DKIM or sending from a free address | Use an address on your own domain and add SPF/DKIM/DMARC. |

## Where things are

- Settings: **Admin → Settings → Email delivery**
- Template wording: **Admin → Email templates**
- Delivery history and failures: **Admin → Email templates → Email log**
- Developer note: drivers live in `app/Core/Mailer.php` (`smtp`, `api`, `mail`, `log`); provider presets in `app/Core/MailProviders.php`.
