<?php
namespace App\Core;

/**
 * Minimal QR Code generator (byte mode, error correction M, versions 1–15) rendering to SVG.
 * Dependency-free; enough capacity (up to 412 bytes) for UPI payment URIs.
 */
class QrCode {
    // [ecc codewords per block, [[blocks, data codewords], ...]] for level M
    private const BLOCKS = [1 => [10, [[1, 16]]], 2 => [16, [[1, 28]]], 3 => [26, [[1, 44]]], 4 => [18, [[2, 32]]], 5 => [24, [[2, 43]]], 6 => [16, [[4, 27]]], 7 => [18, [[4, 31]]], 8 => [22, [[2, 38], [2, 39]]],
        9 => [22, [[3, 36], [2, 37]]], 10 => [26, [[4, 43], [1, 44]]], 11 => [30, [[1, 50], [4, 51]]], 12 => [22, [[6, 36], [2, 37]]], 13 => [22, [[8, 37], [1, 38]]], 14 => [24, [[4, 40], [5, 41]]], 15 => [24, [[5, 41], [5, 42]]]];
    private const ALIGN = [1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30], 6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50], 11 => [6, 30, 54], 12 => [6, 32, 58], 13 => [6, 34, 62], 14 => [6, 26, 46, 66], 15 => [6, 26, 48, 70]];
    private array $m = [];
    private array $fn = [];
    private int $size = 0;
    private int $ver = 1;
    private static array $exp = [];
    private static array $log = [];

    public static function svg(string $text, int $px = 240, string $dark = '#000', string $light = '#fff'): string {
        $q = new self();
        $q->encode($text);
        $n = $q->size + 8;
        $d = '';
        for ($y = 0; $y < $q->size; $y++) {
            for ($x = 0; $x < $q->size; $x++) {
                if ($q->m[$y][$x]) {
                    $d .= 'M' . ($x + 4) . ' ' . ($y + 4) . 'h1v1h-1z';}}}
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $n . ' ' . $n . '" width="' . $px . '" height="' . $px . '" shape-rendering="crispEdges" role="img" aria-label="QR code"><rect width="100%" height="100%" fill="' . $light . '"/><path d="' . $d . '" fill="' . $dark . '"/></svg>';}

    /** Matrix of booleans (true = dark). Exposed for tests. */
    public static function matrix(string $text): array {
        $q = new self();
        $q->encode($text);
        return $q->m;}

    private static function gf(): void {
        if (self::$exp) {
            return;}
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $x;
            self::$log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;}}
        for ($i = 255; $i < 512; $i++) {
            self::$exp[$i] = self::$exp[$i - 255];}}

    private static function mul(int $a, int $b): int {
        return ($a && $b) ? self::$exp[self::$log[$a] + self::$log[$b]] : 0;}

    private static function rs(array $data, int $n): array {
        $gen = [1];
        for ($i = 0; $i < $n; $i++) {
            $next = array_fill(0, count($gen) + 1, 0);
            foreach ($gen as $j => $c) {
                $next[$j] ^= $c;
                $next[$j + 1] ^= self::mul($c, self::$exp[$i]);}
            $gen = $next;}
        $msg = array_merge($data, array_fill(0, $n, 0));
        $len = count($data);
        for ($i = 0; $i < $len; $i++) {
            $coef = $msg[$i];
            if ($coef) {
                for ($j = 1; $j <= $n; $j++) {
                    $msg[$i + $j] ^= self::mul($gen[$j], $coef);}}}
        return array_slice($msg, $len);}

    private function encode(string $text): void {
        self::gf();
        $bytes = array_values(unpack('C*', $text) ?: []);
        $len = count($bytes);
        $ver = 0;
        foreach (self::BLOCKS as $v => $b) {
            $cap = 0;
            foreach ($b[1] as $g) {
                $cap += $g[0] * $g[1];}
            if ((4 + ($v < 10 ? 8 : 16) + 8 * $len + 7) >> 3 <= $cap) {
                $ver = $v;
                break;}}
        if (!$ver) {
            throw new \InvalidArgumentException('Text too long for QR code.');}
        $this->ver = $ver;
        [$eccLen, $groups] = self::BLOCKS[$ver];
        $cap = 0;
        foreach ($groups as $g) {
            $cap += $g[0] * $g[1];}
        // Bit stream
        $bits = [];
        $put = function (int $val, int $n) use (&$bits) {
            for ($i = $n - 1; $i >= 0; $i--) {
                $bits[] = ($val >> $i) & 1;}};
        $put(4, 4);
        $put($len, $ver < 10 ? 8 : 16);
        foreach ($bytes as $b) {
            $put($b, 8);}
        $put(0, min(4, $cap * 8 - count($bits)));
        while (count($bits) % 8) {
            $bits[] = 0;}
        $data = [];
        foreach (array_chunk($bits, 8) as $c) {
            $data[] = bindec(implode('', $c));}
        for ($p = 0; count($data) < $cap; $p++) {
            $data[] = $p % 2 ? 0x11 : 0xEC;}
        // Blocks + ECC + interleave
        $blocks = [];
        $off = 0;
        foreach ($groups as $g) {
            for ($i = 0; $i < $g[0]; $i++) {
                $blk = array_slice($data, $off, $g[1]);
                $off += $g[1];
                $blocks[] = [$blk, self::rs($blk, $eccLen)];}}
        $final = [];
        $maxData = max(array_map(fn($b) => count($b[0]), $blocks));
        for ($i = 0; $i < $maxData; $i++) {
            foreach ($blocks as $b) {
                if (isset($b[0][$i])) {
                    $final[] = $b[0][$i];}}}
        for ($i = 0; $i < $eccLen; $i++) {
            foreach ($blocks as $b) {
                $final[] = $b[1][$i];}}
        // Matrix
        $this->size = $ver * 4 + 17;
        $s = $this->size;
        $this->m = array_fill(0, $s, array_fill(0, $s, false));
        $this->fn = array_fill(0, $s, array_fill(0, $s, false));
        $this->functionPatterns();
        $this->place($final);
        $best = 0;
        $bestScore = PHP_INT_MAX;
        $base = $this->m;
        for ($mask = 0; $mask < 8; $mask++) {
            $this->m = $base;
            $this->applyMask($mask);
            $this->formatBits($mask);
            $sc = $this->penalty();
            if ($sc < $bestScore) {
                $bestScore = $sc;
                $best = $mask;}}
        $this->m = $base;
        $this->applyMask($best);
        $this->formatBits($best);}

    private function set(int $x, int $y, bool $dark): void {
        $this->m[$y][$x] = $dark;
        $this->fn[$y][$x] = true;}

    private function functionPatterns(): void {
        $s = $this->size;
        for ($i = 0; $i < $s; $i++) {
            $this->set(6, $i, $i % 2 === 0);
            $this->set($i, 6, $i % 2 === 0);}
        foreach ([[3, 3], [$s - 4, 3], [3, $s - 4]] as [$cx, $cy]) {
            for ($dy = -4; $dy <= 4; $dy++) {
                for ($dx = -4; $dx <= 4; $dx++) {
                    $x = $cx + $dx;
                    $y = $cy + $dy;
                    if ($x < 0 || $y < 0 || $x >= $s || $y >= $s) {
                        continue;}
                    $d = max(abs($dx), abs($dy));
                    $this->set($x, $y, $d !== 2 && $d !== 4);}}}
        $al = self::ALIGN[$this->ver];
        $n = count($al);
        foreach ($al as $i => $ax) {
            foreach ($al as $j => $ay) {
                if (($i === 0 && $j === 0) || ($i === 0 && $j === $n - 1) || ($i === $n - 1 && $j === 0)) {
                    continue;}
                for ($dy = -2; $dy <= 2; $dy++) {
                    for ($dx = -2; $dx <= 2; $dx++) {
                        $this->set($ax + $dx, $ay + $dy, max(abs($dx), abs($dy)) !== 1);}}}}
        $this->formatBits(0);
        if ($this->ver >= 7) {
            $rem = $this->ver;
            for ($i = 0; $i < 12; $i++) {
                $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);}
            $bits = ($this->ver << 12) | $rem;
            for ($i = 0; $i < 18; $i++) {
                $bit = (($bits >> $i) & 1) === 1;
                $a = $s - 11 + $i % 3;
                $b = intdiv($i, 3);
                $this->set($a, $b, $bit);
                $this->set($b, $a, $bit);}}}

    private function formatBits(int $mask): void {
        $s = $this->size;
        $data = (0 << 3) | $mask; // level M = 0b00
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);}
        $bits = (($data << 10) | $rem) ^ 0x5412;
        $g = fn($i) => (($bits >> $i) & 1) === 1;
        for ($i = 0; $i <= 5; $i++) {
            $this->set(8, $i, $g($i));}
        $this->set(8, 7, $g(6));
        $this->set(8, 8, $g(7));
        $this->set(7, 8, $g(8));
        for ($i = 9; $i < 15; $i++) {
            $this->set(14 - $i, 8, $g($i));}
        for ($i = 0; $i < 8; $i++) {
            $this->set($s - 1 - $i, 8, $g($i));}
        for ($i = 8; $i < 15; $i++) {
            $this->set(8, $s - 15 + $i, $g($i));}
        $this->set(8, $s - 8, true);}

    private function place(array $data): void {
        $s = $this->size;
        $i = 0;
        $total = count($data) * 8;
        for ($right = $s - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;}
            for ($vert = 0; $vert < $s; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    $up = (($right + 1) & 2) === 0;
                    $y = $up ? $s - 1 - $vert : $vert;
                    if (!$this->fn[$y][$x] && $i < $total) {
                        $this->m[$y][$x] = (($data[$i >> 3] >> (7 - ($i & 7))) & 1) === 1;
                        $i++;}}}}}

    private function applyMask(int $mask): void {
        $s = $this->size;
        for ($y = 0; $y < $s; $y++) {
            for ($x = 0; $x < $s; $x++) {
                if ($this->fn[$y][$x]) {
                    continue;}
                switch ($mask) {
                    case 0: $inv = ($x + $y) % 2 === 0; break;
                    case 1: $inv = $y % 2 === 0; break;
                    case 2: $inv = $x % 3 === 0; break;
                    case 3: $inv = ($x + $y) % 3 === 0; break;
                    case 4: $inv = (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0; break;
                    case 5: $inv = ($x * $y) % 2 + ($x * $y) % 3 === 0; break;
                    case 6: $inv = (($x * $y) % 2 + ($x * $y) % 3) % 2 === 0; break;
                    default: $inv = (($x + $y) % 2 + ($x * $y) % 3) % 2 === 0;}
                if ($inv) {
                    $this->m[$y][$x] = !$this->m[$y][$x];}}}}

    private function penalty(): int {
        $s = $this->size;
        $p = 0;
        $dark = 0;
        for ($pass = 0; $pass < 2; $pass++) {
            for ($a = 0; $a < $s; $a++) {
                $run = 1;
                $line = '';
                for ($b = 0; $b < $s; $b++) {
                    $v = $pass ? $this->m[$b][$a] : $this->m[$a][$b];
                    $line .= $v ? '1' : '0';
                    if ($b > 0) {
                        $prev = $pass ? $this->m[$b - 1][$a] : $this->m[$a][$b - 1];
                        if ($v === $prev) {
                            $run++;
                            if ($run === 5) {
                                $p += 3;}
                            elseif ($run > 5) {
                                $p++;}}
                        else {
                            $run = 1;}}}
                $p += 40 * (substr_count($line, '10111010000') + substr_count($line, '00001011101'));}}
        for ($y = 0; $y < $s; $y++) {
            for ($x = 0; $x < $s; $x++) {
                if ($this->m[$y][$x]) {
                    $dark++;}
                if ($x < $s - 1 && $y < $s - 1) {
                    $c = $this->m[$y][$x];
                    if ($c === $this->m[$y][$x + 1] && $c === $this->m[$y + 1][$x] && $c === $this->m[$y + 1][$x + 1]) {
                        $p += 3;}}}}
        $p += 10 * (int)(abs($dark * 100 / ($s * $s) - 50) / 5);
        return (int)$p;}}
