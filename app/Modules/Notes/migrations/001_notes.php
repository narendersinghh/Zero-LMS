<?php
$o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
return [
"CREATE TABLE IF NOT EXISTS {lesson_notes} (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  lesson_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_note (user_id, lesson_id),
  KEY ix_note_course (user_id, course_id),
  CONSTRAINT fk_note_user FOREIGN KEY (user_id) REFERENCES {users}(id) ON DELETE CASCADE,
  CONSTRAINT fk_note_lesson FOREIGN KEY (lesson_id) REFERENCES {lessons}(id) ON DELETE CASCADE) $o",];
