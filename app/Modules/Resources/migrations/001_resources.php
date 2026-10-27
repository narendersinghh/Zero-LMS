<?php
$o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
return [
"CREATE TABLE IF NOT EXISTS {lesson_resources} (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lesson_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  file VARCHAR(120) NULL,
  original_name VARCHAR(255) NULL,
  mime VARCHAR(120) NULL,
  size BIGINT UNSIGNED NOT NULL DEFAULT 0,
  url VARCHAR(500) NULL,
  sort INT NOT NULL DEFAULT 0,
  downloads INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  KEY ix_res_lesson (lesson_id, sort),
  CONSTRAINT fk_res_lesson FOREIGN KEY (lesson_id) REFERENCES {lessons}(id) ON DELETE CASCADE) $o",];
