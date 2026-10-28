<?php
/**
 * Per-enrollment progress counters so reports and lists read one enrollment row instead of aggregating
 * every lesson_progress row. Kept in sync by Progress::sync() and re-checked daily by the scheduler.
 */
use App\Core\DB;

return function () {
    $cols = DB::col('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [DB::prefix() . 'enrollments']);
    if (!in_array('lessons_done', $cols, true)) {
        DB::run('ALTER TABLE {enrollments} ADD COLUMN lessons_started INT UNSIGNED NOT NULL DEFAULT 0, ADD COLUMN lessons_done INT UNSIGNED NOT NULL DEFAULT 0, ADD INDEX ix_enroll_accessed (last_accessed_at)');}
    $have = DB::col('SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [DB::prefix() . 'lesson_progress']);
    if (!in_array('ix_progress_cover', $have, true)) {
        DB::run('ALTER TABLE {lesson_progress} ADD INDEX ix_progress_cover (course_id, user_id, completed_at, last_viewed_at, percent)');}
    if (!in_array('ix_progress_viewed_user', $have, true)) {
        DB::run('ALTER TABLE {lesson_progress} ADD INDEX ix_progress_viewed_user (last_viewed_at, user_id)');}
    \App\Services\Progress::resyncAll();};
