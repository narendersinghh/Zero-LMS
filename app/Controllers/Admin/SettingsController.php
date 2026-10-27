<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Crypto;
use App\Core\Defaults;
use App\Core\Mailer;
use App\Core\Settings;
use App\Core\Uploads;

class SettingsController extends Controller {
    public const TABS = ['general' => ['General', 'settings'], 'branding' => ['Branding & theme', 'palette'], 'mail' => ['Email delivery', 'mail'], 'video' => ['Video security', 'shield'], 'learning' => ['Learning & notifications', 'sparkles'], 'payments' => ['Payments', 'key']];

    public function show(string $tab) {
        if (!isset(self::TABS[$tab])) {
            abort(404);}
        if (($tab === 'mail' && !Auth::can('mail.configure')) || ($tab === 'payments' && !Auth::can('payments.configure'))) {
            abort(403);}
        return view('admin/settings/' . $tab, ['tab' => $tab, 's' => Settings::all(), 'tabs' => self::TABS, 'timezones' => \DateTimeZone::listIdentifiers()]);}

    public function save(string $tab) {
        if (!isset(self::TABS[$tab])) {
            abort(404);}
        $in = fn($k, $d = '') => trim((string)$this->req->input($k, $d));
        $v = [];
        switch ($tab) {
            case 'general':
                $this->validate(['site_name' => 'required|max:80', 'site_tagline' => 'max:160', 'support_email' => 'email', 'timezone' => 'required', 'login_title' => 'max:80', 'login_subtitle' => 'max:200']);
                $tz = in_array($in('timezone'), \DateTimeZone::listIdentifiers(), true) ? $in('timezone') : 'UTC';
                $v = ['site_name' => $in('site_name'), 'site_tagline' => $in('site_tagline'), 'support_email' => $in('support_email'), 'timezone' => $tz,
                    'date_format' => in_array($in('date_format'), ['M j, Y', 'd M Y', 'd/m/Y', 'm/d/Y', 'Y-m-d', 'j F Y'], true) ? $in('date_format') : 'M j, Y',
                    'time_format' => in_array($in('time_format'), ['g:i A', 'H:i'], true) ? $in('time_format') : 'g:i A', 'login_title' => $in('login_title'), 'login_subtitle' => $in('login_subtitle')];
                break;
            case 'branding':
                if ($this->req->str('reset_palettes') === '1') {
                    Settings::set(['palette_dark' => Defaults::palettes()['dark'], 'palette_light' => Defaults::palettes()['light']]);
                    Audit::log('settings.palettes_reset');
                    $this->ok('Theme colors restored to defaults.');}
                $pal = [];
                foreach (['dark', 'light'] as $mode) {
                    $cur = (array)Settings::get('palette_' . $mode, []);
                    foreach (array_keys(Defaults::palettes()[$mode]) as $k) {
                        $c = strtolower($in("palette_{$mode}_$k"));
                        $pal[$mode][$k] = preg_match('/^#[0-9a-f]{6}$/', $c) ? $c : ($cur[$k] ?? Defaults::palettes()[$mode][$k]);}}
                $fonts = \App\Core\Theme::FONTS;
                $v = ['palette_dark' => $pal['dark'], 'palette_light' => $pal['light'],
                    'heading_font' => in_array($in('heading_font'), $fonts, true) ? $in('heading_font') : 'Inter Tight', 'body_font' => in_array($in('body_font'), $fonts, true) ? $in('body_font') : 'Inter',
                    'heading_weight' => in_array($in('heading_weight'), ['500', '600', '700', '800'], true) ? $in('heading_weight') : '700', 'base_font_size' => (string)max(13, min(18, (int)$in('base_font_size', '15'))),
                    'radius' => (string)max(0, min(28, (int)$in('radius', '14'))), 'button_radius' => (string)max(0, min(40, (int)$in('button_radius', '10'))),
                    'button_style' => $in('button_style') === 'outline' ? 'outline' : 'solid', 'shadow' => in_array($in('shadow'), ['none', 'soft', 'strong'], true) ? $in('shadow') : 'soft',
                    'default_theme' => $in('default_theme') === 'light' ? 'light' : 'dark', 'allow_theme_toggle' => $this->req->bool('allow_theme_toggle'), 'custom_css' => mb_substr((string)$this->req->input('custom_css', ''), 0, 20000)];
                foreach (['logo' => 'Logo (dark theme)', 'logo_light' => 'Logo (light theme)', 'favicon' => 'Favicon'] as $k => $label) {
                    if ($this->req->str('remove_' . $k) === '1') {
                        Uploads::deletePublic((string)Settings::get($k));
                        $v[$k] = '';}
                    if ($f = $this->req->file($k)) {
                        try {
                            $v[$k] = Uploads::image($f, 'brand', 2048, true);
                            Uploads::deletePublic((string)Settings::get($k));}
                        catch (\Throwable $e) {
                            flash('error', $label . ': ' . $e->getMessage());}}}
                break;
            case 'mail':
                if (!Auth::can('mail.configure')) {
                    abort(403);}
                $this->validate(['mail_driver' => 'required|in:smtp,mail,log', 'mail_from_email' => 'email', 'mail_from_name' => 'max:100', 'smtp_host' => 'max:190', 'smtp_port' => 'int', 'smtp_secure' => 'in:tls,ssl,none', 'mail_batch_size' => 'int']);
                $v = ['mail_driver' => $in('mail_driver'), 'mail_from_email' => $in('mail_from_email'), 'mail_from_name' => $in('mail_from_name'), 'smtp_host' => $in('smtp_host'), 'smtp_port' => $in('smtp_port', '587'),
                    'smtp_user' => $in('smtp_user'), 'smtp_secure' => $in('smtp_secure', 'tls'), 'mail_batch_size' => max(1, min(500, (int)$in('mail_batch_size', '40')))];
                if ($in('smtp_pass') !== '') {
                    $v['smtp_pass'] = Crypto::encrypt((string)$_POST['smtp_pass']);}
                break;
            case 'video':
                $v = ['video_token_ttl' => max(10, min(1440, (int)$in('video_token_ttl', '240'))), 'video_watermark' => $this->req->bool('video_watermark'), 'video_block_direct' => $this->req->bool('video_block_direct'),
                    'video_sendfile' => in_array($in('video_sendfile'), ['none', 'xsendfile', 'xaccel'], true) ? $in('video_sendfile') : 'none', 'video_complete_percent' => max(50, min(100, (int)$in('video_complete_percent', '90'))),
                    'bunny_library_id' => preg_replace('/[^0-9]/', '', $in('bunny_library_id')), 'bunny_cdn_host' => $in('bunny_cdn_host')];
                if ($in('bunny_token_key') !== '') {
                    $v['bunny_token_key'] = Crypto::encrypt($in('bunny_token_key'));}
                if ($this->req->str('clear_bunny_key') === '1') {
                    $v['bunny_token_key'] = '';}
                break;
            case 'payments':
                if (!Auth::can('payments.configure')) {
                    abort(403);}
                $vpa = $in('upi_vpa');
                if ($vpa !== '' && !preg_match('/^[A-Za-z0-9._-]{2,}@[A-Za-z0-9.-]{2,}$/', $vpa)) {
                    flash('error', 'Enter a valid UPI ID, e.g. name@bank.');
                    back();}
                $v = ['upi_enabled' => $this->req->bool('upi_enabled'), 'upi_vpa' => $vpa, 'upi_payee_name' => mb_substr($in('upi_payee_name'), 0, 60), 'upi_instructions' => mb_substr($in('upi_instructions'), 0, 1000),
                    'upi_provisional_access' => $this->req->bool('upi_provisional_access'), 'upi_require_proof' => $this->req->bool('upi_require_proof'), 'allow_registration' => $this->req->bool('allow_registration')];
                break;
            case 'learning':
                $quotes = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)$this->req->input('quotes', '')))));
                $v = ['show_quotes' => $this->req->bool('show_quotes'), 'quotes' => $quotes, 'notify_lesson_unlock' => $this->req->bool('notify_lesson_unlock'), 'email_lesson_unlock' => $this->req->bool('email_lesson_unlock'),
                    'expiry_reminder_days' => max(0, min(30, (int)$in('expiry_reminder_days', '3'))), 'web_cron' => $this->req->bool('web_cron')];
                break;}
        Settings::set($v);
        Audit::log('settings.updated', 'settings', null, ['tab' => $tab, 'keys' => array_keys($v)]);
        $this->ok('Settings saved.');}}
