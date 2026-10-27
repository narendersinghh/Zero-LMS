<?php
namespace App\Core;

/** Dependency-free SMTP client (SSL, STARTTLS, AUTH LOGIN/PLAIN). */
class Smtp {
    private $sock;
    private array $cfg;
    public string $log = '';

    public function __construct(array $cfg) {
        $this->cfg = array_merge(['host' => '', 'port' => 587, 'user' => '', 'pass' => '', 'secure' => 'tls', 'timeout' => 20], $cfg);}

    public function send(string $from, string $fromName, string $to, string $subject, string $html, string $text = '', array $headers = []): void {
        $c = $this->cfg;
        $host = ($c['secure'] === 'ssl' ? 'ssl://' : '') . $c['host'];
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
        $this->sock = @stream_socket_client($host . ':' . (int)$c['port'], $errno, $errstr, (int)$c['timeout'], STREAM_CLIENT_CONNECT, $ctx);
        if (!$this->sock) {
            throw new \RuntimeException("SMTP connect failed: $errstr ($errno)");}
        stream_set_timeout($this->sock, (int)$c['timeout']);
        try {
            $this->expect(220);
            $ehloHost = parse_url(base_url(), PHP_URL_HOST) ?: 'localhost';
            $this->cmd('EHLO ' . $ehloHost, 250);
            if ($c['secure'] === 'tls') {
                $this->cmd('STARTTLS', 220);
                if (!stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT | (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT : 0))) {
                    throw new \RuntimeException('STARTTLS negotiation failed');}
                $this->cmd('EHLO ' . $ehloHost, 250);}
            if ($c['user'] !== '') {
                $this->cmd('AUTH LOGIN', 334);
                $this->cmd(base64_encode($c['user']), 334);
                $this->cmd(base64_encode($c['pass']), 235);}
            $this->cmd('MAIL FROM:<' . $from . '>', 250);
            $this->cmd('RCPT TO:<' . $to . '>', [250, 251]);
            $this->cmd('DATA', 354);
            $msg = Mailer::buildMime($from, $fromName, $to, $subject, $html, $text, $headers);
            $msg = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $msg));
            fwrite($this->sock, str_replace("\n", "\r\n", $msg) . "\r\n.\r\n");
            $this->expect(250);
            $this->cmd('QUIT', 221);}
        finally {
            if (is_resource($this->sock)) {
                fclose($this->sock);}}}

    private function cmd(string $cmd, $expect): string {
        fwrite($this->sock, $cmd . "\r\n");
        return $this->expect($expect);}

    private function expect($codes): string {
        $codes = (array)$codes;
        $resp = '';
        while (($line = fgets($this->sock, 1024)) !== false) {
            $resp .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;}}
        $this->log .= $resp;
        $code = (int)substr($resp, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new \RuntimeException('SMTP error: ' . trim($resp ?: 'no response'));}
        return $resp;}}
