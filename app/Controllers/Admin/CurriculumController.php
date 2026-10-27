<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Audit;
use App\Core\DB;
use App\Services\Courses;

class CurriculumController extends Controller {
    public function index(string $id) {
        $course = Courses::find((int)$id) ?? abort(404);
        $curr = Courses::curriculum((int)$id, false);
        return view('admin/courses/curriculum', ['course' => $course, 'modules' => $curr['modules'], 'lessonCount' => count($curr['lessons'])]);}

    public function storeModule(string $id) {
        $course = Courses::find((int)$id) ?? abort(404);
        $d = $this->validate(['title' => 'required|max:200', 'release_day' => 'int', 'description' => 'max:2000']);
        $sort = (int)DB::val('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {modules} WHERE course_id = ?', [$course['id']]);
        $mid = DB::insert('modules', ['course_id' => $course['id'], 'title' => $d['title'], 'description' => $d['description'] ?: null, 'release_day' => (int)$d['release_day'] > 0 ? (int)$d['release_day'] : null, 'sort_order' => $sort, 'created_at' => now(), 'updated_at' => now()]);
        Audit::log('module.created', 'module', $mid, ['course_id' => $course['id']]);
        $this->ok('Module added.');}

    public function updateModule(string $id) {
        $m = DB::one('SELECT * FROM {modules} WHERE id = ?', [(int)$id]) ?? abort(404);
        $d = $this->validate(['title' => 'required|max:200', 'release_day' => 'int', 'description' => 'max:2000']);
        DB::update('modules', ['title' => $d['title'], 'description' => $d['description'] ?: null, 'release_day' => (int)$d['release_day'] > 0 ? (int)$d['release_day'] : null, 'updated_at' => now()], 'id = ?', [$m['id']]);
        Audit::log('module.updated', 'module', (int)$m['id']);
        $this->ok('Module saved.');}

    public function deleteModule(string $id) {
        $m = DB::one('SELECT * FROM {modules} WHERE id = ?', [(int)$id]) ?? abort(404);
        $mode = $this->req->str('lessons', 'keep');
        DB::transaction(function () use ($m, $mode) {
            if ($mode === 'delete') {
                DB::delete('lessons', 'module_id = ?', [$m['id']]);}
            DB::delete('modules', 'id = ?', [$m['id']]);});
        Audit::log('module.deleted', 'module', (int)$m['id'], ['title' => $m['title'], 'lessons' => $mode]);
        $this->ok('Module deleted.');}

    /** JSON: {modules:[id,...], lessons:[{id, module_id}, ...]} in display order. */
    public function reorder(string $id) {
        $course = Courses::find((int)$id) ?? abort(404);
        $j = $this->req->json();
        $mods = array_map('intval', $j['modules'] ?? []);
        $valid = DB::col('SELECT id FROM {modules} WHERE course_id = ?', [$course['id']]);
        DB::transaction(function () use ($mods, $j, $valid, $course) {
            foreach ($mods as $i => $mid) {
                if (in_array($mid, array_map('intval', $valid), true)) {
                    DB::run('UPDATE {modules} SET sort_order = ? WHERE id = ?', [$i + 1, $mid]);}}
            foreach (($j['lessons'] ?? []) as $i => $l) {
                $mid = (int)($l['module_id'] ?? 0);
                $mid = in_array($mid, array_map('intval', $valid), true) ? $mid : null;
                DB::run('UPDATE {lessons} SET sort_order = ?, module_id = ? WHERE id = ? AND course_id = ?', [$i + 1, $mid, (int)$l['id'], $course['id']]);}});
        json_out(['ok' => true]);}}
