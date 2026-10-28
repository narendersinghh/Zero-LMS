<?php
namespace App\Core;

/** Builds CSS custom properties + font links from branding settings. */
class Theme {
    public const FONTS = ['Inter', 'Inter Tight', 'Manrope', 'Plus Jakarta Sans', 'DM Sans', 'Poppins', 'Outfit', 'Sora', 'Space Grotesk', 'Urbanist', 'Lexend', 'Montserrat', 'Roboto', 'Open Sans', 'Lato', 'Nunito', 'Work Sans', 'Rubik', 'Figtree', 'Onest', 'Geist', 'Playfair Display', 'Merriweather', 'Lora', 'System UI'];

    public static function fontsHref(): string {
        // Inter and Inter Tight ship with the app (assets/fonts), so only other picks load from Google.
        $fams = array_unique(array_filter([Settings::get('heading_font'), Settings::get('body_font')], fn($f) => $f && !in_array($f, ['System UI', 'Inter', 'Inter Tight'], true)));
        if (!$fams) {
            return '';}
        $q = implode('&', array_map(fn($f) => 'family=' . str_replace(' ', '+', $f) . ':wght@400;500;600;700;800', $fams));
        return 'https://fonts.googleapis.com/css2?' . $q . '&display=swap';}

    /** Self-hosted font files worth preloading for the current branding (Latin subset). */
    public static function preloadFonts(): array {
        $map = ['Inter' => 'fonts/inter-latin.woff2', 'Inter Tight' => 'fonts/inter-tight-latin.woff2'];
        return array_values(array_unique(array_filter(array_map(fn($f) => $map[$f] ?? null, [Settings::get('heading_font'), Settings::get('body_font')]))));}

    private static function font(string $f): string {
        $stack = "system-ui,-apple-system,'Segoe UI',Roboto,sans-serif";
        return $f && $f !== 'System UI' ? "'" . str_replace("'", '', $f) . "'," . $stack : $stack;}

    private static function vars(array $p): string {
        $d = Defaults::palettes()['dark'];
        $o = '';
        foreach ($d as $k => $_) {
            $v = $p[$k] ?? $d[$k];
            if (!preg_match('/^#[0-9a-f]{3,8}$/i', (string)$v)) {
                $v = $d[$k];}
            $o .= '--' . str_replace('_', '-', $k) . ':' . $v . ';';}
        $o .= '--accent-soft:' . self::alpha($p['accent'] ?? $d['accent'], .14) . ';--accent-ring:' . self::alpha($p['accent'] ?? $d['accent'], .35) . ';';
        return $o;}

    public static function alpha(string $hex, float $a): string {
        $h = ltrim($hex, '#');
        if (strlen($h) === 3) {
            $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];}
        $h = substr(str_pad($h, 6, '0'), 0, 6);
        return 'rgba(' . hexdec(substr($h, 0, 2)) . ',' . hexdec(substr($h, 2, 2)) . ',' . hexdec(substr($h, 4, 2)) . ',' . $a . ')';}

    public static function css(): string {
        $dark = (array)Settings::get('palette_dark', []);
        $light = (array)Settings::get('palette_light', []);
        $shadow = ['none' => 'none', 'soft' => '0 1px 2px rgba(0,0,0,.06),0 8px 24px rgba(0,0,0,.08)', 'strong' => '0 2px 4px rgba(0,0,0,.12),0 16px 40px rgba(0,0,0,.18)'][Settings::get('shadow', 'soft')] ?? 'none';
        $common = '--font-heading:' . self::font((string)Settings::get('heading_font')) . ';--font-body:' . self::font((string)Settings::get('body_font')) . ';--heading-weight:' . (int)Settings::get('heading_weight', 700) . ';--fs:' . max(12, min(20, (int)Settings::get('base_font_size', 15))) . 'px;--radius:' . (int)Settings::get('radius', 14) . 'px;--btn-radius:' . (int)Settings::get('button_radius', 10) . 'px;--shadow:' . $shadow . ';';
        $css = ':root{' . $common . '}:root,[data-theme="dark"]{' . self::vars($dark) . 'color-scheme:dark}[data-theme="light"]{' . self::vars($light) . 'color-scheme:light}';
        if (Settings::get('button_style') === 'outline') {
            $css .= '.btn-primary{background:transparent;color:var(--accent-text);border-color:var(--accent)}.btn-primary:hover{background:var(--accent);color:var(--accent-contrast)}';}
        $custom = (string)Settings::get('custom_css', '');
        return $css . str_ireplace(['</style', '<script'], '', $custom);}

    /** Theme for the current visitor: cookie preference (if toggling allowed) else default. */
    public static function current(): string {
        $def = Settings::get('default_theme', 'dark') === 'light' ? 'light' : 'dark';
        if (!Settings::get('allow_theme_toggle', true)) {
            return $def;}
        $c = $_COOKIE['zx_theme'] ?? '';
        return in_array($c, ['light', 'dark'], true) ? $c : $def;}}
