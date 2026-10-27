<?php
namespace App\Core;

/** Factory defaults for settings, theme palettes, email templates and quotes. */
class Defaults {
    public static function palettes(): array {
        return [
            'dark' => ['bg' => '#0a0a0b', 'surface' => '#131315', 'surface_2' => '#1b1b1f', 'border' => '#2a2a30', 'text' => '#f5f5f4', 'muted' => '#a1a1aa', 'accent' => '#f5d708', 'accent_contrast' => '#0a0a0b', 'accent_text' => '#f5d708', 'success' => '#22c55e', 'warning' => '#f59e0b', 'danger' => '#ef4444', 'input_bg' => '#0f0f11', 'sidebar_bg' => '#0e0e10', 'hero_from' => '#1c1a06', 'hero_to' => '#131315'],
            'light' => ['bg' => '#f6f6f3', 'surface' => '#ffffff', 'surface_2' => '#f0f0ec', 'border' => '#e3e3dd', 'text' => '#121212', 'muted' => '#5d5d66', 'accent' => '#f5d708', 'accent_contrast' => '#121212', 'accent_text' => '#7a6a00', 'success' => '#15803d', 'warning' => '#b45309', 'danger' => '#dc2626', 'input_bg' => '#ffffff', 'sidebar_bg' => '#ffffff', 'hero_from' => '#fff8c2', 'hero_to' => '#ffffff'],];}

    public static function settings(): array {
        return [
            'site_name' => 'Zeroes', 'site_tagline' => 'Learn something new, every day.', 'timezone' => 'UTC', 'date_format' => 'M j, Y', 'time_format' => 'g:i A',
            'support_email' => '', 'logo' => '', 'logo_light' => '', 'favicon' => '',
            'heading_font' => 'Inter Tight', 'body_font' => 'Inter', 'heading_weight' => '700', 'base_font_size' => '15',
            'default_theme' => 'dark', 'allow_theme_toggle' => true, 'radius' => '14', 'button_radius' => '10', 'button_style' => 'solid', 'shadow' => 'soft',
            'palette_dark' => self::palettes()['dark'], 'palette_light' => self::palettes()['light'], 'custom_css' => '',
            'login_title' => 'Welcome back', 'login_subtitle' => 'Sign in to continue your learning journey.',
            'mail_driver' => '', 'mail_from_name' => '', 'mail_from_email' => '', 'smtp_host' => '', 'smtp_port' => '587', 'smtp_user' => '', 'smtp_pass' => '', 'smtp_secure' => 'tls',
            'mail_batch_size' => 40, 'notify_lesson_unlock' => true, 'email_lesson_unlock' => true, 'expiry_reminder_days' => 3,
            'video_token_ttl' => 240, 'video_watermark' => true, 'video_sendfile' => 'none', 'video_complete_percent' => 90, 'video_block_direct' => true,
            'bunny_library_id' => '', 'bunny_token_key' => '', 'bunny_cdn_host' => '',
            'quotes' => self::quotes(), 'show_quotes' => true,
            'update_repo' => 'narendersinghh/Zero-LMS', 'update_channel' => 'release', 'update_branch' => 'main', 'update_token' => '', 'installed_commit' => '',
            'modules_enabled' => [], 'cron_last_run' => '', 'cron_secret' => '', 'web_cron' => true,];}

    public static function quotes(): array {
        return [
            'The expert in anything was once a beginner. — Helen Hayes',
            'Small daily improvements are the key to staggering long-term results.',
            'Learning never exhausts the mind. — Leonardo da Vinci',
            'Success is the sum of small efforts, repeated day in and day out. — Robert Collier',
            'The beautiful thing about learning is that nobody can take it away from you. — B.B. King',
            'Don\'t watch the clock; do what it does. Keep going. — Sam Levenson',
            'An investment in knowledge pays the best interest. — Benjamin Franklin',
            'You don\'t have to be great to start, but you have to start to be great. — Zig Ziglar',
            'Discipline is choosing between what you want now and what you want most.',
            'Every lesson completed is a step closer to the person you want to become.',
            'Consistency beats intensity. Show up today.',
            'The capacity to learn is a gift; the ability to learn is a skill; the willingness to learn is a choice. — Brian Herbert',
            'What we learn with pleasure we never forget. — Alfred Mercier',
            'Progress, not perfection.',
            'It always seems impossible until it\'s done. — Nelson Mandela',];}

    public static function emailTemplates(): array {
        return [
            'welcome' => ['name' => 'Welcome / account setup', 'subject' => 'Welcome to {{site_name}} — set up your account',
                'body' => "<h1>Welcome, {{first_name}}!</h1><p>An account has been created for you on <strong>{{site_name}}</strong>{{course_line}}.</p><p>Click the button below to choose your password and start learning. This link expires in {{link_hours}} hours.</p><p class=\"btn-wrap\"><a class=\"btn\" href=\"{{setup_link}}\">Set up my account</a></p><p><strong>How to log in later:</strong><br>Go to <a href=\"{{login_url}}\">{{login_url}}</a> and sign in with <strong>{{email}}</strong> and the password you choose.</p>"],
            'enrolled' => ['name' => 'Added to a course', 'subject' => 'You now have access to {{course_title}}',
                'body' => "<h1>You're in, {{first_name}}!</h1><p>You've been given access to <strong>{{course_title}}</strong>.</p><p>{{access_line}}</p><p class=\"btn-wrap\"><a class=\"btn\" href=\"{{course_url}}\">Start learning</a></p><p>Sign in at <a href=\"{{login_url}}\">{{login_url}}</a> with <strong>{{email}}</strong>.</p>"],
            'password_reset' => ['name' => 'Password reset', 'subject' => 'Reset your {{site_name}} password',
                'body' => "<h1>Reset your password</h1><p>Hi {{first_name}}, we received a request to reset your password.</p><p class=\"btn-wrap\"><a class=\"btn\" href=\"{{reset_link}}\">Choose a new password</a></p><p>This link expires in {{link_hours}} hour(s). If you didn't request this, you can safely ignore this email.</p>"],
            'lesson_unlocked' => ['name' => 'New lesson unlocked', 'subject' => 'New lesson available: {{lesson_title}}',
                'body' => "<h1>Today's lesson is ready</h1><p>Hi {{first_name}}, a new lesson just unlocked in <strong>{{course_title}}</strong>:</p><p style=\"font-size:18px\"><strong>{{lesson_title}}</strong></p><p class=\"btn-wrap\"><a class=\"btn\" href=\"{{lesson_url}}\">Watch now</a></p><p><em>{{quote}}</em></p>"],
            'access_expiring' => ['name' => 'Access expiring soon', 'subject' => 'Your access to {{course_title}} ends {{expires_at}}',
                'body' => "<h1>Heads up, {{first_name}}</h1><p>Your access to <strong>{{course_title}}</strong> ends on <strong>{{expires_at}}</strong>. You've completed {{progress}}% so far.</p><p class=\"btn-wrap\"><a class=\"btn\" href=\"{{course_url}}\">Continue learning</a></p>"],
            'access_extended' => ['name' => 'Access extended', 'subject' => 'Your access to {{course_title}} has been extended',
                'body' => "<h1>Good news, {{first_name}}!</h1><p>Your access to <strong>{{course_title}}</strong> has been extended until <strong>{{expires_at}}</strong>.</p><p class=\"btn-wrap\"><a class=\"btn\" href=\"{{course_url}}\">Continue learning</a></p>"],];}}
