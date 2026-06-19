CREATE TABLE IF NOT EXISTS `student_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_users_student_id` (`student_id`),
  UNIQUE KEY `uq_student_users_username` (`username`),
  UNIQUE KEY `uq_student_users_email` (`email`),
  CONSTRAINT `fk_student_users_student`
    FOREIGN KEY (`student_id`)
    REFERENCES `students` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

