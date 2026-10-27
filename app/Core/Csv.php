<?php
namespace App\Core;

class Csv {
    /** Parse a CSV file into rows keyed by normalized header names. Detects delimiter and BOM. */
    public static function read(string $path, int $max = 20000): array {
        $fh = fopen($path, 'r');
        if (!$fh) {
            return [];}
        $first = (string)fgets($fh);
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delim = substr_count($first, ';') > substr_count($first, ',') ? ';' : (substr_count($first, "\t") > substr_count($first, ',') ? "\t" : ',');
        $head = array_map(fn($h) => strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $h), '_')), str_getcsv(trim($first), $delim));
        $hasHeader = (bool)array_intersect($head, ['email', 'e_mail', 'email_address', 'name', 'first_name']);
        $rows = [];
        if (!$hasHeader) {
            $head = ['email', 'name', 'last_name'];
            rewind($fh);}
        while (($r = fgetcsv($fh, 0, $delim)) !== false && count($rows) < $max) {
            if ($r === [null] || !array_filter($r, fn($v) => trim((string)$v) !== '')) {
                continue;}
            $row = [];
            foreach ($head as $i => $h) {
                $row[$h] = trim((string)($r[$i] ?? ''));}
            if (!$hasHeader) {
                $row['email'] = preg_replace('/^\xEF\xBB\xBF/', '', $row['email']);}
            $rows[] = $row;}
        fclose($fh);
        return $rows;}

    /** Stream a CSV download. */
    public static function download(string $filename, array $header, iterable $rows): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) . '"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $header);
        foreach ($rows as $r) {
            // Prevent CSV formula injection in spreadsheet apps.
            fputcsv($out, array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, array_values($r)));}
        fclose($out);
        exit;}}
