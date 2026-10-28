<?php
/** Indexes for the date-filtered counts on the admin dashboard and reports (they scanned whole tables before). */
use App\Core\DB;

return function () {
    $want = [
        'lesson_progress' => ['ix_progress_viewed' => 'last_viewed_at', 'ix_progress_done' => 'course_id, completed_at', 'ix_progress_completed' => 'completed_at'],
        'enrollments' => ['ix_enroll_created' => 'created_at'],
        'orders' => ['ix_order_paid' => 'status, paid_at'],];
    foreach ($want as $table => $idx) {
        $have = DB::col('SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [DB::prefix() . $table]);
        foreach ($idx as $name => $cols) {
            if (!in_array($name, $have, true)) {
                DB::run("ALTER TABLE {{$table}} ADD INDEX $name ($cols)");}}}};
