<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Audit;
use App\Core\ModuleManager;
use App\Core\Settings;

/** Optional feature modules: list, enable/disable (runs migrations on enable). */
class ModulesController extends Controller {
    public function index() {
        $mods = ModuleManager::discover();
        $enabled = ModuleManager::enabled();
        $cats = array_values(array_unique(array_map(fn($m) => $m['category'] ?? 'Other', $mods)));
        sort($cats);
        return view('admin/modules/index', ['modules' => $mods, 'enabled' => $enabled, 'cats' => $cats, 'cat' => $this->req->str('cat')]);}

    public function save() {
        $all = ModuleManager::discover();
        $on = array_values(array_intersect(array_map('strval', $this->req->arr('modules')), array_keys($all)));
        // Single-module toggle from a card: keep the others as they are.
        if (($one = $this->req->str('module')) !== '' && isset($all[$one])) {
            $on = ModuleManager::enabled();
            $on = $this->req->bool('enable') ? array_values(array_unique(array_merge($on, [$one]))) : array_values(array_diff($on, [$one]));
            $on = array_values(array_intersect($on, array_keys($all)));}
        $before = ModuleManager::enabled();
        $ran = [];
        foreach (array_diff($on, $before) as $key) {
            $ran = array_merge($ran, ModuleManager::migrate($key));}
        Settings::set(['modules_enabled' => $on]);
        Audit::log('modules.updated', null, null, ['enabled' => $on, 'added' => array_values(array_diff($on, $before)), 'removed' => array_values(array_diff($before, $on))]);
        $added = array_values(array_diff($on, $before));
        $removed = array_values(array_diff($before, $on));
        $name = fn($k) => $all[$k]['name'] ?? $k;
        $msg = $added ? implode(', ', array_map($name, $added)) . ' enabled.' : ($removed ? implode(', ', array_map($name, $removed)) . ' disabled. Its data is kept.' : 'No changes.');
        $this->ok($msg . ($ran ? ' Database updated.' : ''), '/admin/modules');}}
