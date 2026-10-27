<?php
namespace App\Core;

/**
 * Email service with a DB queue. Drivers: smtp, mail (PHP mail()), log (writes to storage/logs).
 * Templates live in the email_templates table and support {{placeholders}}.
 */
class Mailer {
    public static function config(): array {
        $driver = Settings::get('mail_driver') ?: Env::get('MAIL_DRIVER', 'log');
        return [
            'driver' => $driver,
            'from' => Settings::get('mail_from_email') ?: Env::get('MAIL_FROM', 'no-reply@' . (parse_url(base_url(), PHP_URL_HOST) ?: 'localhost')),
            'from_name' => Settings::get('mail_from_name') ?: Env::get('MAIL_FROM_NAME', Settings::get('site_name', 'Zeroes')),
            'host' => Settings::get('smtp_host') ?: Env::get('SMTP_HOST', ''),
            'port' => (int)(Settings::get('smtp_host') ? Settings::get('smtp_port', 587) : Env::get('SMTP_PORT', 587)),
            'user' => Settings::get('smtp_host') ? Settings::get('smtp_user', '') : Env::get('SMTP_USER', ''),
            'pass' => Settings::get('smtp_host') ? Crypto::decrypt(Settings::get('smtp_pass', '')) : Env::get('SMTP_PASS', ''),
            'secure' => Settings::get('smtp_host') ? Settings::get('smtp_secure', 'tls') : Env::get('SMTP_SECURE', 'tls'),];}

    /** Queue a templated email. */
    public static function queueTemplate(string $key, string $toEmail, string $toName, array $vars = [], int $delaySeconds = 0): bool {
        $tpl = self::template($key);
        if (!$tpl || !(int)$tpl['enabled']) {
            return false;}
        $vars = array_merge(self::globals(), ['name' => $toName, 'first_name' => explode(' ', trim($toName) ?: 'there')[0], 'email' => $toEmail], $vars);
        self::queue($toEmail, $toName, self::fill($tpl['subject'], $vars, false), self::fill($tpl['body'], $vars, true), $delaySeconds, $key);
        return true;}

    public static function queue(string $toEmail, string $toName, string $subject, string $html, int $delaySeconds = 0, string $template = ''): int {
        Scheduler::$mailQueued = true;
        return DB::insert('mail_queue', ['to_email' => $toEmail, 'to_name' => $toName, 'subject' => $subject, 'body' => $html, 'template' => $template ?: null,
            'status' => 'pending', 'attempts' => 0, 'send_after' => gmdate('Y-m-d H:i:s', time() + $delaySeconds), 'created_at' => now()]);}

    public static function template(string $key): ?array {
        static $cache = [];
        return $cache[$key] ??= DB::one('SELECT * FROM {email_templates} WHERE `key` = ?', [$key]);}

    public static function globals(): array {
        return ['site_name' => Settings::get('site_name', 'Zeroes'), 'site_url' => base_url(), 'login_url' => url('/login', [], true), 'support_email' => Settings::get('support_email', ''), 'year' => gmdate('Y')];}

    public static function fill(string $s, array $vars, bool $html): string {
        return preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function ($m) use ($vars, $html) {
            $v = (string)($vars[$m[1]] ?? '');
            // Raw HTML only for explicitly trusted *_line variables; URLs and text are escaped.
            return $html && substr($m[1], -5) !== '_line' ? e($v) : $v;}, $s);}

    /** Wrap HTML body in the branded email layout. */
    public static function layout(string $body, string $subject): string {
        return View::render('emails/layout', ['body' => $body, 'subject' => $subject]);}

    /** Process pending queue items. Returns number sent. */
    public static function processQueue(int $limit = 0): int {
        $limit = $limit ?: max(1, (int)Settings::get('mail_batch_size', 40));
        $lockFile = STORAGE_PATH . '/cache/mail.lock';
        $fh = @fopen($lockFile, 'c');
        if (!$fh || !flock($fh, LOCK_EX | LOCK_NB)) {
            return 0;}
        $sent = 0;
        try {
            $rows = DB::all("SELECT * FROM {mail_queue} WHERE status = 'pending' AND send_after <= ? ORDER BY id LIMIT " . (int)$limit, [now()]);
            foreach ($rows as $r) {
                try {
                    self::sendNow($r['to_email'], $r['to_name'], $r['subject'], self::layout($r['body'], $r['subject']));
                    DB::update('mail_queue', ['status' => 'sent', 'sent_at' => now(), 'attempts' => $r['attempts'] + 1, 'last_error' => null], 'id = ?', [$r['id']]);
                    $sent++;}
                catch (\Throwable $e) {
                    $a = (int)$r['attempts'] + 1;
                    DB::update('mail_queue', ['status' => $a >= 5 ? 'failed' : 'pending', 'attempts' => $a, 'last_error' => substr($e->getMessage(), 0, 500), 'send_after' => gmdate('Y-m-d H:i:s', time() + 300 * $a)], 'id = ?', [$r['id']]);
                    Logger::write('mail', 'error', $r['to_email'] . ': ' . $e->getMessage());}}}
        finally {
            flock($fh, LOCK_UN);
            fclose($fh);}
        return $sent;}

    public static function sendNow(string $to, string $toName, string $subject, string $html): void {
        $c = self::config();
        $text = trim(html_entity_decode(strip_tags(preg_replace(['/<br\s*\/?>/i', '/<\/(p|h\d|li|div|tr)>/i', '/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/i'], ["\n", "\n\n", '$2 ($1)'], $html)), ENT_QUOTES, 'UTF-8'));
        $text = preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+/', ' ', $text));
        $headers = ['List-Unsubscribe' => '<mailto:' . $c['from'] . '>'];
        switch ($c['driver']) {
            case 'smtp':
                (new Smtp($c))->send($c['from'], $c['from_name'], $to, $subject, $html, $text, $headers);
                break;
            case 'mail':
                $mime = self::buildMime($c['from'], $c['from_name'], $to, $subject, $html, $text, $headers, true);
                [$hdr, $body] = explode("\n\n", str_replace("\r\n", "\n", $mime), 2);
                $hdr = preg_replace('/^(To|Subject):.*\n/mi', '', $hdr . "\n");
                if (!@mail($to, self::encodeHeader($subject), str_replace("\n", "\r\n", $body), trim(str_replace("\n", "\r\n", $hdr)), '-f' . $c['from'])) {
                    throw new \RuntimeException('PHP mail() failed. Configure SMTP instead.');}
                break;
            default:
                Logger::write('mail', 'info', "To: $to | Subject: $subject\n$text\n" . str_repeat('-', 40));}}

    public static function encodeHeader(string $s): string {
        return preg_match('/[^\x20-\x7e]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;}

    public static function buildMime(string $from, string $fromName, string $to, string $subject, string $html, string $text, array $extra = [], bool $forMailFn = false): string {
        $b = 'zx' . bin2hex(random_bytes(12));
        $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
        $h = [
            'Date' => date('r'), 'From' => self::encodeHeader($fromName) . ' <' . $from . '>', 'To' => $to, 'Subject' => self::encodeHeader($subject),
            'Message-ID' => '<' . bin2hex(random_bytes(16)) . '@' . $domain . '>', 'MIME-Version' => '1.0', 'Content-Type' => 'multipart/alternative; boundary="' . $b . '"',];
        $h = array_merge($h, $extra);
        $out = '';
        foreach ($h as $k => $v) {
            $out .= $k . ': ' . str_replace(["\r", "\n"], '', $v) . "\n";}
        $out .= "\n--$b\nContent-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: base64\n\n" . chunk_split(base64_encode($text), 76, "\n");
        $out .= "--$b\nContent-Type: text/html; charset=UTF-8\nContent-Transfer-Encoding: base64\n\n" . chunk_split(base64_encode($html), 76, "\n") . "--$b--\n";
        return $out;}}
