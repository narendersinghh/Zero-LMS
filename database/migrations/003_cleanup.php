<?php
/** 1.3 cleanup: delete the retired Example module and replace em dashes in stored templates, quotes and settings. */
use App\Core\DB;
use App\Core\Scheduler;
use App\Core\Settings;

return function () {
    foreach (['app/Modules/Example'] as $rel) {
        if (is_dir(BASE_PATH . '/' . $rel)) {
            Scheduler::rmdir(BASE_PATH . '/' . $rel);}}
    $mods = Settings::get('modules_enabled', []);
    if (is_array($mods) && in_array('Example', $mods, true)) {
        Settings::set(['modules_enabled' => array_values(array_diff($mods, ['Example']))]);}
    $fix = fn(string $s) => str_replace([' — ', '—'], [', ', '-'], $s);
    // Text columns that hold user-facing copy (sample content and templates).
    foreach (['email_templates' => ['name', 'subject', 'body'], 'courses' => ['title', 'subtitle', 'description', 'highlights'], 'modules' => ['title', 'description'], 'lessons' => ['title', 'content']] as $table => $cols) {
        $have = DB::col('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [DB::prefix() . $table]);
        $cols = array_values(array_intersect($cols, $have));
        if (!$cols) {
            continue;}
        $where = implode(' OR ', array_map(fn($c) => "`$c` LIKE ?", $cols));
        foreach (DB::all('SELECT id, `' . implode('`, `', $cols) . "` FROM {{$table}} WHERE $where", array_fill(0, count($cols), '%—%')) as $r) {
            $id = $r['id'];
            unset($r['id']);
            DB::update($table, array_map(fn($v) => $v === null ? null : $fix((string)$v), $r), 'id = ?', [$id]);}}
    // Quotes keep their "Text - Author" format (the parser accepts a plain dash).
    $q = Settings::get('quotes', []);
    if (is_array($q) && strpos(implode("\n", $q), '—') !== false) {
        Settings::set(['quotes' => array_map(fn($l) => str_replace('—', '-', (string)$l), $q)]);}
    foreach (DB::all("SELECT id, quotes FROM {courses} WHERE quotes LIKE ?", ['%—%']) as $c) {
        DB::update('courses', ['quotes' => str_replace('—', '-', $c['quotes'])], 'id = ?', [$c['id']]);}
    Settings::clearCache();};
