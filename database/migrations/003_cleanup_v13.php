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
    foreach (DB::all('SELECT id, subject, body FROM {email_templates}') as $t) {
        if (strpos($t['subject'] . $t['body'], '—') !== false) {
            DB::update('email_templates', ['subject' => $fix($t['subject']), 'body' => $fix($t['body'])], 'id = ?', [$t['id']]);}}
    // Quotes keep their "Text - Author" format (the parser accepts a plain dash).
    $q = Settings::get('quotes', []);
    if (is_array($q) && strpos(implode("\n", $q), '—') !== false) {
        Settings::set(['quotes' => array_map(fn($l) => str_replace('—', '-', (string)$l), $q)]);}
    foreach (DB::all("SELECT id, quotes FROM {courses} WHERE quotes LIKE ?", ['%—%']) as $c) {
        DB::update('courses', ['quotes' => str_replace('—', '-', $c['quotes'])], 'id = ?', [$c['id']]);}
    Settings::clearCache();};
