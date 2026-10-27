<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Hooks;
use App\Core\Html;
use App\Core\Media;
use App\Services\Access;
use App\Services\Courses;
use App\Services\Learning;
use App\Services\Progress;

class LessonController extends Controller {
    public function show(string $slug, string $id) {
        $user = Auth::user();
        $course = Courses::bySlug($slug);
        if (!$course) {
            abort(404);}
        $curr = Courses::curriculum((int)$course['id'], !Auth::isStaff());
        $lesson = null;
        foreach ($curr['lessons'] as $l) {
            if ((int)$l['id'] === (int)$id) {
                $lesson = $l;}}
        if (!$lesson) {
            abort(404);}
        $check = Access::canViewLesson($user, $lesson, $course);
        if (!$check['ok']) {
            $msg = ['locked' => 'This lesson unlocks on ' . dtime($check['unlock_at']) . '.', 'expired' => 'Your access to this course has expired.', 'pending' => 'Your access to this course has not started yet.', 'suspended' => 'Your access to this course is paused. Please contact support.'][$check['reason']] ?? 'You do not have access to this lesson.';
            if (in_array($check['reason'], ['locked', 'expired', 'pending', 'suspended'], true)) {
                flash('info', $msg);
                redirect('/courses/' . $course['slug']);}
            abort(403, $msg);}
        $enr = $check['enrollment'];
        $progress = Progress::lessonMap((int)$user['id'], (int)$course['id']);
        $c = Learning::annotate($curr, $enr, $course, $progress, $check['preview']);
        $flat = $c['flat'];
        $idx = array_search((int)$lesson['id'], array_map(fn($x) => (int)$x['id'], $flat), true);
        if ($idx !== false) {
            $lesson = $flat[$idx];}
        $prev = $idx > 0 ? $flat[$idx - 1] : null;
        $next = $idx !== false && $idx < count($flat) - 1 ? $flat[$idx + 1] : null;
        if (!$check['preview'] || $enr) {
            Progress::touch((int)$user['id'], $lesson);}
        $video = $lesson['video_id'] ? DB::one('SELECT * FROM {videos} WHERE id = ?', [(int)$lesson['video_id']]) : null;
        $playback = $video ? Media::playbackUrl($video, (int)$lesson['id'], (int)$user['id']) : '';
        $settings = json_decode((string)$lesson['settings'], true) ?: [];
        $content = Hooks::filter('lesson.content', Html::clean($lesson['content']), $lesson, $user);
        return view('student/lesson', [
            'course' => $course, 'lesson' => $lesson, 'c' => $c, 'prev' => $prev, 'next' => $next, 'video' => $video, 'playback' => $playback,
            'embed' => $video && Media::isEmbed($video), 'content' => $content, 'settings' => $settings, 'lp' => $progress[$lesson['id']] ?? null,
            'preview' => $check['preview'], 'user' => $user, 'quote' => Courses::quote($course),]);}

    private function lessonFor(string $id): array {
        $lesson = DB::one('SELECT l.*, m.release_day AS module_release_day FROM {lessons} l LEFT JOIN {modules} m ON m.id = l.module_id WHERE l.id = ?', [(int)$id]);
        if (!$lesson) {
            json_out(['error' => 'Not found'], 404);}
        $chk = Access::canViewLesson(Auth::user(), $lesson);
        if (!$chk['ok']) {
            json_out(['error' => 'Forbidden'], 403);}
        return $lesson;}

    public function progress(string $id) {
        $lesson = $this->lessonFor($id);
        $done = Progress::savePosition(Auth::id(), $lesson, (int)$this->req->input('position', 0), (int)$this->req->input('duration', 0));
        if ((int)$lesson['duration'] === 0 && (int)$this->req->input('duration', 0) > 0) {
            DB::update('lessons', ['duration' => (int)$this->req->input('duration')], 'id = ?', [$lesson['id']]);}
        json_out(['ok' => true, 'completed' => $done]);}

    public function complete(string $id) {
        $lesson = $this->lessonFor($id);
        if ($this->req->str('undo') === '1') {
            Progress::uncomplete(Auth::id(), (int)$lesson['id']);
            $done = false;}
        else {
            Progress::complete(Auth::id(), $lesson);
            $done = true;}
        if ($this->req->isAjax()) {
            $p = Progress::forCourses(Auth::id(), [(int)$lesson['course_id']])[(int)$lesson['course_id']];
            json_out(['ok' => true, 'completed' => $done, 'course' => $p]);}
        back([], false);}}
