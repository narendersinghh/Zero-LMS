# Zeroes LMS

A modern, premium, multi-purpose Learning Management System built for **standard shared hosting** (cPanel / DirectAdmin / Plesk). Started as a daily-video course platform for webinar buyers, designed to grow into any kind of online course or training program.

- **Zero dependencies** — plain PHP 8.0+ and MySQL 5.7+ / MariaDB 10.2+. No Composer, Node.js, Redis or Docker needed in production.
- **Web installer** and **one-click updates from GitHub** (with automatic code backups and rollback).
- Dark (default) and light themes, fully configurable branding with no code edits.

## Why a custom micro-framework?

We evaluated Laravel, CodeIgniter 4 and Slim. For this product a small purpose-built core wins on shared hosting:

| | Zeroes core | Laravel / CI4 / Slim |
|---|---|---|
| Upload & run on any host | ✅ copy files, open `/install` | needs Composer / vendor folder |
| Safe in-app updates | ✅ replace files from a GitHub zip | vendor drift, larger packages |
| Request overhead | ~6 small files, 3–8 queries per page | heavier bootstrap |

The core still follows framework conventions: front controller, router + middleware, controllers/services/views, migrations, CSRF, validation, event hooks and a module system.

## Features

**Courses** — create, edit, duplicate, archive, delete; modules & lessons with drag-and-drop ordering; lesson types (video, article, embed — extendable); drip release by *day N* from each student's start, by *fixed date*, or immediately; course start date, daily unlock time, access duration, hard end date; per-course motivational quotes; admin preview of any course/lesson.

**Students & access** — create users individually or via **CSV import** (batched queries, duplicates/invalid rows reported); automatic accounts with a secure **password setup link** and login instructions; multiple courses per user; per-enrollment start/expiry, manual extensions (+7/+30/+90 days, set date, lifetime), suspend/reactivate, bulk actions; CSV exports.

**Student experience** — greeting hero with *Continue learning*, stats (courses, lessons completed, learning streak, finished), progress cards, upcoming unlocks with live countdowns, recently viewed, notifications (in-app + email), expiry warnings, course page with locked/completed indicators, lesson page with custom player, resume position, auto-complete at a configurable %, previous/next navigation, profile/avatar/password/theme. Fully responsive with a mobile tab bar.

**Video protection** (as far as the web allows) —
- Uploaded videos live in `storage/videos/` (not web-accessible) and are **streamed through PHP** with HTTP Range support.
- Each playback URL is an **HMAC-signed, expiring token bound to the user, lesson and session**; access to the enrollment is re-validated server-side.
- Opening the stream URL directly in a browser tab is blocked; download/PiP/cast UI disabled; right-click/drag blocked; source URL not present in the page HTML.
- A **moving watermark** with the viewer's email deters screen-recording and sharing.
- Optional **Bunny Stream** (token-authenticated embeds, HLS CDN), Vimeo (domain-restricted) or unlisted YouTube.
- ⚠️ No technology can fully prevent screen recording or determined copying; these measures make casual downloading and sharing difficult.

Large uploads use **chunked uploading** (bypasses `upload_max_filesize`), or upload via FTP into `storage/videos/incoming/` and click *Import now*.

**Admin panel** — dashboard KPIs & 14-day activity chart, courses, curriculum builder, video library, students, enrollments, CSV import history, reports (per-course progress distribution, lesson engagement, CSV export), email templates (WYSIWYG + variables + preview + test send), email queue log, settings, audit log with filters, team management, system page (updates, backups, cron, modules, maintenance mode, migrations, server health).

**Branding** — logo (separate light/dark), favicon, heading/body fonts (Google Fonts list), heading weight, base size, radii, button style, shadows, complete **dark and light palettes** (16 tokens each) with live preview, default theme & user toggle, custom CSS. Default: accent `#f5d708`, *Inter Tight* headings, *Inter* body.

**Security** — roles (Super Admin / Admin / Student) with a permission map; bcrypt passwords with rehash; hardened sessions (strict mode, HttpOnly, SameSite, regeneration, idle timeout, UA-bound fingerprint, global logout on password change/suspension); CSRF on every POST; input validation; HTML sanitizer for rich text; login & reset throttling; no user enumeration; security headers + CSP; encrypted secrets (SMTP password, tokens) with AES-256-GCM; CSV-injection-safe exports; audit log of all admin actions; uploads folder can't execute scripts.

## New in 1.1

**Redesigned UI** — sidebar layout on desktop, app-style top bar + bottom tab bar on phones, pastel course cards, hand-drawn line-art illustrations (`assets/img/illus/`), a "Continue watching" row, achievements, learning statistics and an upcoming-lessons calendar. Admin content is centred with quick-action tiles.

**Sell courses with shareable links** — set a price/offer price and enable *Pricing & shareable link* on a course. `https://your-site/c/<course-slug>` is a public sales page; anyone can create an account during checkout and pay. Free courses (or 100%-off coupons) enroll instantly. Students can also sign up at `/register` and browse `/explore`.

**UPI payments (QR + link)** — Settings → Payments: enter your UPI ID. Checkout shows a QR code (generated on the server, no external service) and a "Pay with UPI app" deep link for phones. The student submits the UTR (and optionally a screenshot); you approve it under **Admin → Orders**, which grants access and emails the student. Optional: give access immediately on UTR submission (revoked automatically if rejected). Other gateways plug in through the `payment.gateways` hook — a **Razorpay** module (payment links + signed callback/webhook) is included.

**YouTube without YouTube** — YouTube lessons play through the IFrame API with YouTube's own controls hidden, the iframe cropped (no title bar / logo) and covered by a click shield, so viewers cannot click through to youtube.com. Zeroes' own controls: play/pause, ±10 s, volume, seek, captions (CC), speed, fullscreen; progress and resume work like uploaded videos. The video ID is fetched from an authenticated endpoint rather than printed in the page. ⚠️ A determined user can still find the ID in browser developer tools — for real protection use protected uploads or Bunny Stream.

**Modules** (System → Modules, all enabled by default on this install): Certificates (auto-issued, printable, public verification at `/verify`), Announcements (per course or everyone, notifications + email), Coupons (percent/fixed, limits, expiry, share links `/c/slug?coupon=CODE`), Razorpay, Quizzes (per-lesson MCQ, pass mark, can be required to complete a lesson), Lesson resources (protected downloads/links), Notes (private per-lesson notes with autosave and export).

## Requirements

PHP 8.0+ with `pdo_mysql`, `openssl`, `mbstring`, `fileinfo`, `dom` (plus `curl` + `zip` for updates, `gd` recommended) · MySQL 5.7+/MariaDB 10.2+ · Apache with `mod_rewrite` (or Nginx, see below).

## Installation (shared hosting)

1. Upload the files (or the release zip) to your domain folder, e.g. `public_html/` or a subfolder like `public_html/learn/`.
2. Create a MySQL database + user in cPanel.
3. Visit `https://yourdomain.com/install` and follow the 3 steps (server check → database → site & admin).
4. Add the cron job shown in **Admin → System & updates** (every 5 minutes):
   `*/5 * * * * php /home/USER/public_html/cron.php >/dev/null 2>&1`
   No cron? Built-in "web cron" runs tasks after page views, or ping the secret URL shown on the System page.
5. Configure **Settings → Email delivery**: pick a provider (Brevo, Mailjet, SendGrid, Mailgun, Postmark, SES, Zoho, Gmail, Microsoft 365, Hostinger, cPanel…), paste the API key or SMTP login, and send a test. See [docs/EMAIL.md](docs/EMAIL.md).

CLI alternative: `php tools/install.php --url=https://learn.example.com --db-name=zeroes --db-user=u --db-pass=p --admin-email=you@example.com --admin-pass=Secret123 --demo`

### Nginx

```nginx
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ ^/(app|database|storage|tools)/ { deny all; }
location ~ /\.env { deny all; }
# optional (Settings → Video security → X-Accel-Redirect)
location /protected-videos/ { internal; alias /path/to/zeroes/storage/videos/; }
```

If URL rewriting is unavailable set `APP_PRETTY_URLS="false"` in `.env` (URLs become `/index.php/...`).

## Email

Until a provider is chosen, Zeroes runs in **Log only** mode (emails are written to `storage/logs/mail-*.log`, nothing is delivered). **Admin → Settings → Email delivery** walks you through it in four steps: choose a provider from built-in presets, paste the credentials (API key — recommended, works even when the host blocks mail ports — or SMTP login; server, port and encryption are pre-filled), set the sender, and send a test before saving. Built-in API drivers: Brevo, Mailjet, SendGrid, Mailgun (US/EU) and Postmark. Full guide with per-provider steps, SPF/DKIM/DMARC and troubleshooting: [docs/EMAIL.md](docs/EMAIL.md).

## Updates via GitHub

**Admin → System & updates**: set the repository (default `narendersinghh/Zero-LMS`), choose channel *latest release* or *branch head*, and add a read-only token for private repos. *Install update* will:

1. download the release zip (asset `zeroes-*.zip`, or the source zipball),
2. validate it and put the site in maintenance mode,
3. back up the current code to `storage/backups/` (last 5 kept),
4. extract files — never touching `.env`, `storage/` or `uploads/`,
5. run new database migrations and clear caches.

Any backup can be restored from the same page (code only; database migrations are not reverted).

**Publishing a release:** bump nothing manually — push a tag, e.g. `git tag v1.1.0 && git push origin v1.1.0`. The included GitHub Action lints, builds minified assets, stamps `version.json` and attaches `zeroes-v1.1.0.zip` to the release.

## Development

```bash
php -S 127.0.0.1:8000 tools/dev-server.php   # local server with .htaccess-like routing
php tools/build.php                          # minify assets/src → assets/dist
php cron.php                                 # run scheduled tasks
```

Set `APP_DEBUG="true"` in `.env` to see error details. Logs are in `storage/logs/`.

### Project structure

```
index.php            front controller        cron.php      scheduled tasks
app/Core/            framework (router, DB, auth, sessions, mailer, media, updater, migrator, hooks…)
app/Services/        domain logic (access rules, enrollments, progress, learning, users, notifier)
app/Controllers/     student + Admin/ controllers
app/Views/           PHP templates (layouts, partials, student, admin, emails)
app/Modules/         optional feature modules (see Example)
database/migrations/ versioned schema migrations
assets/src → dist/   CSS/JS sources and minified builds
storage/             private: videos, logs, cache, sessions, backups (web access denied)
uploads/             public images (logos, covers, avatars)
```

## Extending Zeroes

Create `app/Modules/<Name>/module.json` + `Module.php` (extends `App\Core\Module`) and enable it under *System → Modules*. Modules can add routes, admin/student navigation, permissions, views (`Views/`), migrations (`migrations/`) and react to events:

`order.created`, `order.submitted`, `order.paid`, `order.rejected`, `coupon.used`, `user.created`, `enrollment.created`, `enrollment.updated`, `lesson.viewed`, `lesson.completed`, `course.completed`, `auth.login`, `cron.run`, filters `checkout.price`, `payment.gateways`, `lesson.can_complete`, `lesson.manual_complete`, `lesson.types`, `lesson.content`, `dashboard.widgets`, HTML slots `lesson.panels`, `lesson.side`, `course.side`, `course.header_actions`, `dashboard.side`, `profile.panels`, `checkout.fields`, `admin.lesson_panels`, `admin.course_tabs`, `admin.dashboard_widgets`, `head.assets`, `admin.nav`, `student.nav`, `auth.permissions`.

The schema and services were designed so quizzes, assignments, certificates, discussions, downloadable resources, drip emails, payments, coupons, subscriptions, multiple instructors and new content types plug in as modules (e.g. a payments module calls `Enrollments::enroll()` after checkout; certificates listen to `course.completed`). See `app/Modules/Example`.

## License

Proprietary — © Zeroes. All rights reserved.
