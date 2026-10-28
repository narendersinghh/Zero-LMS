<?php
$o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
return [
"CREATE TABLE IF NOT EXISTS {quizzes} (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lesson_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL DEFAULT 'Quick check',
  pass_percent TINYINT UNSIGNED NOT NULL DEFAULT 70,
  max_attempts INT UNSIGNED NULL,
  require_to_complete TINYINT(1) NOT NULL DEFAULT 0,
  shuffle TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uq_quiz_lesson (lesson_id),
  CONSTRAINT fk_quiz_lesson FOREIGN KEY (lesson_id) REFERENCES {lessons}(id) ON DELETE CASCADE) $o",
"CREATE TABLE IF NOT EXISTS {quiz_questions} (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT UNSIGNED NOT NULL,
  question TEXT NOT NULL,
  options TEXT NOT NULL,
  correct_index TINYINT UNSIGNED NOT NULL DEFAULT 0,
  explanation TEXT NULL,
  sort INT NOT NULL DEFAULT 0,
  KEY ix_qq_quiz (quiz_id, sort),
  CONSTRAINT fk_qq_quiz FOREIGN KEY (quiz_id) REFERENCES {quizzes}(id) ON DELETE CASCADE) $o",
"CREATE TABLE IF NOT EXISTS {quiz_attempts} (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  score INT UNSIGNED NOT NULL DEFAULT 0,
  total INT UNSIGNED NOT NULL DEFAULT 0,
  percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  passed TINYINT(1) NOT NULL DEFAULT 0,
  answers TEXT NULL,
  created_at DATETIME NOT NULL,
  KEY ix_qa_user (user_id, quiz_id),
  CONSTRAINT fk_qa_quiz FOREIGN KEY (quiz_id) REFERENCES {quizzes}(id) ON DELETE CASCADE,
  CONSTRAINT fk_qa_user FOREIGN KEY (user_id) REFERENCES {users}(id) ON DELETE CASCADE) $o",];
