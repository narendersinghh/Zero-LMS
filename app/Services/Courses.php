<?php
namespace App\Services;

use App\Core\Audit;
use App\Core\DB;
use App\Core\Hooks;
use App\Core\Settings;

class Courses {
    public const STATUSES = ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'];

    public static function find(int $id): ?array {
        return DB::one('SELECT * FROM {courses} WHERE id = ?', [$id]);}

    public static function bySlug(string $slug): ?array {
        return DB::one('SELECT * FROM {courses} WHERE slug = ?', [$slug]);}

    public static function uniqueSlug(string $title, int $ignoreId = 0): string {
        $base = slugify($title);
        $slug = $base;
        $i = 2;
        while ((int)DB::val('SELECT COUNT(*) FROM {courses} WHERE slug = ? AND id <> ?', [$slug, $ignoreId])) {
            $slug = $base . '-' . $i++;}
        return $slug;}

    /** Lesson types; modules can register more via the "lesson.types" filter. */
    public static function lessonTypes(): array {
        return Hooks::filter('lesson.types', ['video' => 'Video lesson', 'text' => 'Article / text', 'embed' => 'Embedded content (URL)']);}

    /** Modules + lessons (ordered) for a course. Two queries. */
    public static function curriculum(int $courseId, bool $publishedOnly = true): array {
        $modules = DB::all('SELECT * FROM {modules} WHERE course_id = ? ORDER BY sort_order, id', [$courseId]);
        $lessons = DB::all('SELECT l.*, m.release_day AS module_release_day, v.duration AS video_duration, v.provider AS video_provider FROM {lessons} l LEFT JOIN {modules} m ON m.id = l.module_id LEFT JOIN {videos} v ON v.id = l.video_id WHERE l.course_id = ?' . ($publishedOnly ? " AND l.status = 'published'" : '') . ' ORDER BY COALESCE(m.sort_order, 999999), l.sort_order, l.id', [$courseId]);
        $byModule = [];
        foreach ($lessons as $l) {
            $byModule[(int)$l['module_id']][] = $l;}
        $out = [];
        foreach ($modules as $m) {
            $m['lessons'] = $byModule[(int)$m['id']] ?? [];
            $out[] = $m;}
        if (!empty($byModule[0])) {
            $out[] = ['id' => 0, 'title' => 'Lessons', 'description' => '', 'release_day' => null, 'lessons' => $byModule[0]];}
        return ['modules' => $out, 'lessons' => $lessons];}

    public static function duplicate(int $courseId): int {
        return DB::transaction(function () use ($courseId) {
            $c = self::find($courseId);
            if (!$c) {
                throw new \RuntimeException('Course not found');}
            $orig = $c['id'];
            unset($c['id']);
            $c['title'] .= ' (Copy)';
            $c['slug'] = self::uniqueSlug($c['title']);
            $c['status'] = 'draft';
            $c['archived_at'] = null;
            $c['created_at'] = $c['updated_at'] = now();
            $newId = DB::insert('courses', $c);
            $map = [];
            foreach (DB::all('SELECT * FROM {modules} WHERE course_id = ? ORDER BY sort_order, id', [$orig]) as $m) {
                $old = $m['id'];
                unset($m['id']);
                $m['course_id'] = $newId;
                $m['created_at'] = $m['updated_at'] = now();
                $map[$old] = DB::insert('modules', $m);}
            $rows = [];
            foreach (DB::all('SELECT * FROM {lessons} WHERE course_id = ? ORDER BY sort_order, id', [$orig]) as $l) {
                unset($l['id']);
                $l['course_id'] = $newId;
                $l['module_id'] = $l['module_id'] ? ($map[$l['module_id']] ?? null) : null;
                $l['created_at'] = $l['updated_at'] = now();
                $rows[] = $l;}
            DB::insertMany('lessons', $rows);
            Audit::log('course.duplicated', 'course', $newId, ['from' => $orig]);
            return $newId;});}

    /** Quote of the day for a course (falls back to global quotes). */
    public static function quote(?array $course = null): ?array {
        if (!Settings::get('show_quotes', true)) {
            return null;}
        $list = [];
        if ($course && trim((string)$course['quotes']) !== '') {
            $list = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)$course['quotes']))));}
        if (!$list) {
            $list = array_values(array_filter((array)Settings::get('quotes', [])));}
        if (!$list) {
            return null;}
        $i = ((int)gmdate('z') + (int)($course['id'] ?? 0)) % count($list);
        $parts = preg_split('/\s+[—–-]{1,2}\s+(?=[^—–-]+$)/u', $list[$i], 2);
        return ['text' => trim($parts[0], " \"“”"), 'author' => $parts[1] ?? ''];}}
