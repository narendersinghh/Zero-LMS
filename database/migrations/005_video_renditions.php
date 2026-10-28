<?php
/** Extra quality versions (e.g. 720p, 480p) for uploaded videos, switchable in the player. */
use App\Core\DB;

return function () {
    DB::run("CREATE TABLE IF NOT EXISTS {video_renditions} (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      video_id INT UNSIGNED NOT NULL,
      label VARCHAR(20) NOT NULL,
      height SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      source VARCHAR(255) NOT NULL,
      mime VARCHAR(60) NOT NULL DEFAULT 'video/mp4',
      size BIGINT UNSIGNED NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL,
      KEY ix_rend_video (video_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $cols = DB::col('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [DB::prefix() . 'videos']);
    if (!in_array('quality_label', $cols, true)) {
        DB::run("ALTER TABLE {videos} ADD COLUMN quality_label VARCHAR(20) NULL");}};
