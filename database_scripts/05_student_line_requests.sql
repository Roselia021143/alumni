CREATE TABLE IF NOT EXISTS `student_line_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `requester_student_id` INT UNSIGNED NOT NULL,
  `target_student_code` VARCHAR(50) NOT NULL,
  `direction` ENUM('parent', 'child') NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_line_requests_requester` (`requester_student_id`),
  KEY `idx_line_requests_target_code` (`target_student_code`),
  CONSTRAINT `fk_line_requests_requester`
    FOREIGN KEY (`requester_student_id`)
    REFERENCES `students` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

