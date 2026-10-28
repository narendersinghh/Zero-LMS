<?php
return ["CREATE TABLE IF NOT EXISTS {certificates} (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  code VARCHAR(16) NOT NULL,
  issued_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  issued_by INT UNSIGNED NULL,
  UNIQUE KEY uq_cert_code (code),
  UNIQUE KEY uq_cert_user_course (user_id, course_id),
  KEY ix_cert_course (course_id),
  CONSTRAINT fk_cert_user FOREIGN KEY (user_id) REFERENCES {users}(id) ON DELETE CASCADE,
  CONSTRAINT fk_cert_course FOREIGN KEY (course_id) REFERENCES {courses}(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"];
