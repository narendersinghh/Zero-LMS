<?php
namespace App\Core;

/** Small server-rendered SVG charts (no JS libraries). Colors use CSS variables so both themes work. */
class Charts {
    private static int $n = 0;

    private static function niceMax(float $v): float {
        if ($v <= 0) {
            return 4;}
        $p = 10 ** floor(log10($v));
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            if ($v <= $m * $p) {
                return $m * $p;}}
        return 10 * $p;}

    /**
     * Line / area chart.
     * @param array $series [['name' => 'Enrollments', 'data' => [1,2,...], 'color' => 'var(--accent)', 'area' => true], ...]
     * @param array $labels x labels (same length as data)
     */
    public static function line(array $series, array $labels, array $opt = []): string {
        $id = 'ch' . (++self::$n);
        $w = 640;
        $h = (int)($opt['height'] ?? 220);
        $pl = 34;
        $pr = 10;
        $pt = 12;
        $pb = 26;
        $n = max(1, count($labels));
        $max = 0;
        foreach ($series as $s) {
            $max = max($max, ...array_map('floatval', $s['data'] ?: [0]));}
        $max = self::niceMax($max);
        $fmt = $opt['format'] ?? fn($v) => $v >= 1000 ? round($v / 1000, 1) . 'k' : (string)round($v);
        $x = fn($i) => $pl + ($n === 1 ? ($w - $pl - $pr) / 2 : $i * ($w - $pl - $pr) / ($n - 1));
        $y = fn($v) => $pt + ($h - $pt - $pb) * (1 - $v / $max);
        $svg = '<svg class="chart" viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none" role="img" aria-label="' . e($opt['label'] ?? 'Chart') . '"><defs>';
        foreach ($series as $k => $s) {
            $c = $s['color'] ?? 'var(--accent)';
            $svg .= '<linearGradient id="' . $id . 'g' . $k . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="' . $c . '" stop-opacity=".32"/><stop offset="1" stop-color="' . $c . '" stop-opacity="0"/></linearGradient>';}
        $svg .= '</defs>';
        for ($g = 0; $g <= 4; $g++) {
            $gy = $pt + ($h - $pt - $pb) * $g / 4;
            $svg .= '<line class="chart-grid" x1="' . $pl . '" x2="' . ($w - $pr) . '" y1="' . round($gy, 1) . '" y2="' . round($gy, 1) . '"/><text class="chart-y" x="' . ($pl - 6) . '" y="' . round($gy + 3, 1) . '">' . e($fmt($max * (4 - $g) / 4)) . '</text>';}
        $step = max(1, (int)ceil($n / 8));
        foreach ($labels as $i => $l) {
            if ($i % $step === 0 || $i === $n - 1) {
                $svg .= '<text class="chart-x" x="' . round($x($i), 1) . '" y="' . ($h - 8) . '">' . e($l) . '</text>';}}
        foreach ($series as $k => $s) {
            $c = $s['color'] ?? 'var(--accent)';
            $pts = [];
            foreach (array_values($s['data']) as $i => $v) {
                $pts[] = round($x($i), 1) . ',' . round($y((float)$v), 1);}
            if (!$pts) {
                continue;}
            if (!empty($s['area'])) {
                $svg .= '<path d="M' . $pl . ',' . ($h - $pb) . ' L' . implode(' L', $pts) . ' L' . round($x(count($pts) - 1), 1) . ',' . ($h - $pb) . ' Z" fill="url(#' . $id . 'g' . $k . ')"/>';}
            $svg .= '<polyline points="' . implode(' ', $pts) . '" fill="none" stroke="' . $c . '" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"' . (!empty($s['dashed']) ? ' stroke-dasharray="5 5"' : '') . '/>';}
        // Hover targets with native tooltips.
        foreach ($labels as $i => $l) {
            $tip = $l;
            foreach ($series as $s) {
                $tip .= ' · ' . ($s['name'] ?? '') . ': ' . ($opt['tip'] ?? fn($v) => $v)($s['data'][$i] ?? 0);}
            $bw = ($w - $pl - $pr) / max(1, $n - 1);
            $svg .= '<rect class="chart-hit" x="' . round($x($i) - $bw / 2, 1) . '" y="' . $pt . '" width="' . round($bw, 1) . '" height="' . ($h - $pt - $pb) . '"><title>' . e($tip) . '</title></rect>';}
        $legend = '';
        if (count($series) > 1 || !empty($opt['legend'])) {
            $legend = '<div class="legend">' . implode('', array_map(fn($s) => '<span><i style="background:' . ($s['color'] ?? 'var(--accent)') . '"></i>' . e($s['name'] ?? '') . '</span>', $series)) . '</div>';}
        return '<div class="chart-wrap">' . $svg . '</svg>' . $legend . '</div>';}

    /** Tiny sparkline for KPI cards. */
    public static function spark(array $data, string $color = 'var(--accent)'): string {
        $data = array_values(array_map('floatval', $data));
        $n = count($data);
        if ($n < 2) {
            return '';}
        $max = max(1, max($data));
        $pts = [];
        foreach ($data as $i => $v) {
            $pts[] = round($i * 100 / ($n - 1), 1) . ',' . round(28 - $v / $max * 26, 1);}
        return '<svg class="spark" viewBox="0 0 100 30" preserveAspectRatio="none" aria-hidden="true"><polyline points="' . implode(' ', $pts) . '" fill="none" stroke="' . $color . '" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round"/></svg>';}

    /** Donut/progress ring with center label. */
    public static function donut(float $pct, string $label = '', string $color = 'var(--accent)', int $size = 120): string {
        $pct = max(0, min(100, $pct));
        $r = 15.915;
        return '<div class="donut" style="width:' . $size . 'px;height:' . $size . 'px"><svg viewBox="0 0 36 36"><circle cx="18" cy="18" r="' . $r . '" fill="none" stroke="var(--surface-2)" stroke-width="3.6"/><circle cx="18" cy="18" r="' . $r . '" fill="none" stroke="' . $color . '" stroke-width="3.6" stroke-linecap="round" stroke-dasharray="' . round($pct, 1) . ' 100" transform="rotate(-90 18 18)"/></svg><div><b>' . round($pct) . '%</b>' . ($label !== '' ? '<small>' . e($label) . '</small>' : '') . '</div></div>';}

    /** Funnel rows: [['label' => 'Enrolled', 'value' => 120], ...] (first row = 100%). */
    public static function funnel(array $rows, array $colors = ['var(--p-yellow)', 'var(--p-blue)', 'var(--p-green)', 'var(--p-purple)', 'var(--p-pink)']): string {
        $top = max(1, (float)($rows[0]['value'] ?? 1));
        $o = '<div class="funnel">';
        foreach ($rows as $i => $r) {
            $pct = round((float)$r['value'] / $top * 100);
            $o .= '<div class="funnel-row"><span class="funnel-l">' . e($r['label']) . '</span><div class="funnel-bar"><i style="width:' . max(2, $pct) . '%;background:' . $colors[$i % count($colors)] . '"></i></div><b>' . number_format((float)$r['value']) . '</b><small>' . $pct . '%</small></div>';}
        return $o . '</div>';}}
