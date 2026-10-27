<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Audit;
use App\Core\DB;
use App\Core\Html;
use App\Services\Courses;

class LessonController extends Controller {
    private function formData(array $course, array $lesson) {
        $modules = DB::all('SELECT id, title, release_day FROM {modules} WHERE course_id = ? ORDER BY sort_order, id', [$course['id']]);
        $video = $lesson['video_id'] ? DB::one('SELECT * FROM {videos} WHERE id = ?', [(int)$lesson['video_id']]) : null;
        return view('admin/lessons/form', ['course' => $course, 'lesson' => $lesson, 'modules' => $modules, 'video' => $video, 'types' => Courses::lessonTypes(),
            'settings' => json_decode((string)($lesson['settings'] ?? ''), true) ?: []]);}

    public function create(string $courseId) {
        $course = Courses::find((int)$courseId) ?? abort(404);
        $mid = $this->req->int('module');
        $nextDay = (int)DB::val('SELECT COALESCE(MAX(release_day), 0) + 1 FROM {lessons} WHERE course_id = ?', [$course['id']]);
        return $this->formData($course, ['id' => 0, 'course_id' => $course['id'], 'module_id' => $mid ?: null, 'title' => '', 'type' => 'video', 'summary' => '', 'content' => '', 'video_id' => null,
            'release_day' => $nextDay > 1 ? $nextDay : null, 'release_at' => null, 'duration' => 0, 'status' => 'published', 'settings' => null]);}

    private function data(array $course): array {
        $d = $this->validate(['title' => 'required|max:200', 'type' => 'required|max:30', 'summary' => 'max:500', 'module_id' => 'int', 'video_id' => 'int', 'release_mode' => 'required|in:immediate,day,date',
            'release_day' => 'int', 'release_at' => 'datetime', 'status' => 'required|in:published,draft', 'duration' => 'max:10', 'embed_url' => 'url']);
        if (!isset(Courses::lessonTypes()[$d['type']])) {
            back(['type' => 'Unknown lesson type.']);}
        $mid = (int)$d['module_id'];
        if ($mid && !DB::val('SELECT id FROM {modules} WHERE id = ? AND course_id = ?', [$mid, $course['id']])) {
            $mid = 0;}
        $vid = (int)$d['video_id'];
        if ($vid && !DB::val('SELECT id FROM {videos} WHERE id = ?', [$vid])) {
            $vid = 0;}
        if ($d['type'] === 'video' && !$vid) {
            back(['video_id' => 'Choose or upload a video for a video lesson.']);}
        if ($d['type'] === 'embed' && !$d['embed_url']) {
            back(['embed_url' => 'An embed URL is required.']);}
        $dur = 0;
        if (preg_match('/^(?:(\d+):)?(\d{1,2}):(\d{2})$/', (string)$d['duration'], $m)) {
            $dur = (int)$m[1] * 3600 + (int)$m[2] * 60 + (int)$m[3];}
        elseif (is_numeric($d['duration'])) {
            $dur = (int)round((float)$d['duration'] * 60);}
        $mode = $d['release_mode'];
        $settings = ['manual_complete' => $this->req->bool('manual_complete'), 'embed_url' => $d['embed_url'] ?: null, 'hide_quote' => $this->req->bool('hide_quote')];
        return ['module_id' => $mid ?: null, 'title' => $d['title'], 'type' => $d['type'], 'summary' => $d['summary'] ?: null, 'content' => Html::clean($this->req->input('content', '')),
            'video_id' => $vid ?: null, 'release_day' => $mode === 'day' && (int)$d['release_day'] > 0 ? (int)$d['release_day'] : null,
            'release_at' => $mode === 'date' ? to_utc($d['release_at']) : null, 'duration' => $dur, 'status' => $d['status'],
            'settings' => json_encode($settings), 'updated_at' => now()];}

    public function store(string $courseId) {
        $course = Courses::find((int)$courseId) ?? abort(404);
        $row = $this->data($course);
        $row['course_id'] = $course['id'];
        $row['sort_order'] = (int)DB::val('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {lessons} WHERE course_id = ?', [$course['id']]);
        $row['created_at'] = now();
        if (!$row['duration'] && $row['video_id']) {
            $row['duration'] = (int)DB::val('SELECT duration FROM {videos} WHERE id = ?', [$row['video_id']]);}
        $id = DB::insert('lessons', $row);
        Audit::log('lesson.created', 'lesson', $id, ['course_id' => $course['id'], 'title' => $row['title']]);
        $this->ok('Lesson created.', $this->req->str('after') === 'new' ? '/admin/courses/' . $course['id'] . '/lessons/create?module=' . (int)$row['module_id'] : '/admin/courses/' . $course['id'] . '/curriculum');}

    public function edit(string $id) {
        $lesson = DB::one('SELECT * FROM {lessons} WHERE id = ?', [(int)$id]) ?? abort(404);
        return $this->formData(Courses::find((int)$lesson['course_id']), $lesson);}

    public function update(string $id) {
        $lesson = DB::one('SELECT * FROM {lessons} WHERE id = ?', [(int)$id]) ?? abort(404);
        $course = Courses::find((int)$lesson['course_id']);
        $row = $this->data($course);
        DB::update('lessons', $row, 'id = ?', [$lesson['id']]);
        Audit::log('lesson.updated', 'lesson', (int)$lesson['id'], ['title' => $row['title']]);
        $this->ok('Lesson saved.', $this->req->str('after') === 'stay' ? '/admin/lessons/' . $lesson['id'] . '/edit' : '/admin/courses/' . $course['id'] . '/curriculum');}

    public function duplicate(string $id) {
        $l = DB::one('SELECT * FROM {lessons} WHERE id = ?', [(int)$id]) ?? abort(404);
        unset($l['id']);
        $l['title'] .= ' (Copy)';
        $l['status'] = 'draft';
        $l['sort_order'] = (int)$l['sort_order'] + 1;
        $l['created_at'] = $l['updated_at'] = now();
        $new = DB::insert('lessons', $l);
        Audit::log('lesson.duplicated', 'lesson', $new, ['from' => (int)$id]);
        $this->ok('Lesson duplicated as draft.', '/admin/lessons/' . $new . '/edit');}

    public function destroy(string $id) {
        $l = DB::one('SELECT * FROM {lessons} WHERE id = ?', [(int)$id]) ?? abort(404);
        DB::delete('lessons', 'id = ?', [$l['id']]);
        Audit::log('lesson.deleted', 'lesson', (int)$l['id'], ['title' => $l['title'], 'course_id' => $l['course_id']]);
        $this->ok('Lesson deleted.', '/admin/courses/' . $l['course_id'] . '/curriculum');}}
