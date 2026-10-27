<?php
namespace App\Core;

/**
 * Discovers modules in app/Modules/<Name>/ (module.json + Module.php) and boots enabled ones.
 * A module class extends App\Core\Module and can register routes, hooks, nav items, permissions, views and migrations.
 */
class ModuleManager {
    private static array $booted = [];

    public static function discover(): array {
        $mods = [];
        foreach (glob(APP_PATH . '/Modules/*/module.json') ?: [] as $f) {
            $meta = json_decode((string)file_get_contents($f), true) ?: [];
            $key = basename(dirname($f));
            $mods[$key] = array_merge(['name' => $key, 'description' => '', 'version' => '1.0.0', 'author' => ''], $meta, ['key' => $key, 'path' => dirname($f)]);}
        ksort($mods);
        return $mods;}

    public static function enabled(): array {
        $e = Settings::get('modules_enabled', []);
        return is_array($e) ? $e : [];}

    public static function boot(App $app): void {
        foreach (self::enabled() as $key) {
            $dir = APP_PATH . '/Modules/' . basename($key);
            $class = 'App\\Modules\\' . basename($key) . '\\Module';
            if (!is_file($dir . '/Module.php')) {
                continue;}
            try {
                $m = new $class($dir);
                if ($m instanceof Module) {
                    if (is_dir($dir . '/Views')) {
                        View::addPath($dir . '/Views');}
                    $m->register($app);
                    self::$booted[$key] = $m;}}
            catch (\Throwable $e) {
                Logger::error("Module $key failed to boot: " . $e->getMessage());}}}

    /** Run module migrations (database/migrations inside the module folder). */
    public static function migrate(string $key): array {
        $dir = APP_PATH . '/Modules/' . basename($key) . '/migrations';
        return is_dir($dir) ? Migrator::run($dir, 'module:' . $key) : [];}}
