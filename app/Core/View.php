<?php
namespace App\Core;

/**
 * PHP template renderer. A template may set $layout (e.g. 'app', 'admin', 'auth') and $title.
 * Modules can override any view by placing a file at app/Modules/<Name>/Views/<same path>.php.
 */
class View {
    private static array $shared = [];
    private static array $paths = [];

    public static function share(string $k, $v): void {
        self::$shared[$k] = $v;}

    public static function addPath(string $dir): void {
        array_unshift(self::$paths, rtrim($dir, '/'));}

    public static function find(string $tpl): string {
        foreach (self::$paths as $p) {
            if (is_file($f = $p . '/' . $tpl . '.php')) {
                return $f;}}
        $f = APP_PATH . '/Views/' . $tpl . '.php';
        if (!is_file($f)) {
            throw new \RuntimeException('View not found: ' . $tpl);}
        return $f;}

    public static function render(string $__tpl, array $__data = []): string {
        $__data = array_merge(self::$shared, $__data);
        $layout = null;
        $title = $__data['title'] ?? '';
        extract($__data, EXTR_SKIP);
        ob_start();
        include self::find($__tpl);
        $content = ob_get_clean();
        if ($layout) {
            ob_start();
            include self::find('layouts/' . $layout);
            return ob_get_clean();}
        return $content;}

    public static function partial(string $__tpl, array $__data = []): string {
        extract(array_merge(self::$shared, $__data), EXTR_SKIP);
        ob_start();
        include self::find('partials/' . $__tpl);
        return ob_get_clean();}}
