<?php
return ["CREATE TABLE IF NOT EXISTS {announcements} (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  body MEDIUMTEXT NULL,
  course_id INT UNSIGNED NULL,
  pinned TINYINT(1) NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  notified_at DATETIME NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  KEY ix_ann_pub (status, published_at),
  KEY ix_ann_course (course_id),
  CONSTRAINT fk_ann_course FOREIGN KEY (course_id) REFERENCES {courses}(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"];
