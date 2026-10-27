<?php
/**
 * Minify assets/src/*.css|js into assets/dist/*.min.*  (no Node.js required)
 * Usage: php tools/build.php
 */
if (PHP_SAPI !== 'cli') {
    exit;}
$src = dirname(__DIR__) . '/assets/src';
$dist = dirname(__DIR__) . '/assets/dist';
@mkdir($dist, 0755, true);

function minify_css(string $c): string {
    $c = preg_replace('#/\*.*?\*/#s', '', $c);
    $c = preg_replace('/\s+/', ' ', $c);
    $c = preg_replace('/\s*([{};,>])\s*/', '$1', $c);
    $c = preg_replace('/:\s+/', ':', $c);
    $c = str_replace(';}', '}', $c);
    return trim($c);}

function minify_js(string $c): string {
    $out = [];
    $inBlock = false;
    foreach (preg_split('/\r?\n/', $c) as $line) {
        $t = trim($line);
        if ($inBlock) {
            if (strpos($t, '*/') !== false) {
                $inBlock = false;
                $t = trim(substr($t, strpos($t, '*/') + 2));}
            else {
                continue;}}
        if (strpos($t, '/*') === 0) {
            if (strpos($t, '*/') === false) {
                $inBlock = true;
                continue;}
            $t = trim(substr($t, strpos($t, '*/') + 2));}
        if ($t === '' || strpos($t, '//') === 0) {
            continue;}
        $out[] = $t;}
    return implode("\n", $out);}

foreach (glob($src . '/*.{css,js}', GLOB_BRACE) as $f) {
    $ext = pathinfo($f, PATHINFO_EXTENSION);
    $code = file_get_contents($f);
    $min = $ext === 'css' ? minify_css($code) : minify_js($code);
    $target = $dist . '/' . pathinfo($f, PATHINFO_FILENAME) . '.min.' . $ext;
    file_put_contents($target, $min . "\n");
    printf("%-22s %7s -> %7s bytes\n", basename($target), number_format(strlen($code)), number_format(strlen($min)));}
