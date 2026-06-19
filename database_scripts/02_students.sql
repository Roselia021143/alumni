CREATE TABLE IF NOT EXISTS `students` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_code` VARCHAR(50) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `nickname` VARCHAR(100) DEFAULT NULL,
  `generation` VARCHAR(50) DEFAULT NULL,
  `faculty` VARCHAR(150) DEFAULT NULL,
  `major` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `facebook` VARCHAR(150) DEFAULT NULL,
  `instagram` VARCHAR(150) DEFAULT NULL,
  `line_id_contact` VARCHAR(100) DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `parent_student_id` INT UNSIGNED DEFAULT NULL,
  `student_code_visible` TINYINT(1) NOT NULL DEFAULT 0,
  `generation_visible` TINYINT(1) NOT NULL DEFAULT 0,
  `phone_visible` TINYINT(1) NOT NULL DEFAULT 0,
  `email_visible` TINYINT(1) NOT NULL DEFAULT 0,
  `facebook_visible` TINYINT(1) NOT NULL DEFAULT 0,
  `instagram_visible` TINYINT(1) NOT NULL DEFAULT 0,
  `line_id_contact_visible` TINYINT(1) NOT NULL DEFAULT 0,
  `profile_image_visible` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_students_student_code` (`student_code`),
  KEY `idx_students_parent_student_id` (`parent_student_id`),
  CONSTRAINT `fk_students_parent`
    FOREIGN KEY (`parent_student_id`)
    REFERENCES `students` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

