-- Repair for databases where students.id is a signed INT.
-- Use this file only when 07_student_profiles.sql already added the students
-- profile columns but failed with errno 150 while creating student_skills.

CREATE TABLE IF NOT EXISTS `student_skills` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `skill_name` VARCHAR(100) NOT NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_skills_student_name` (`student_id`, `skill_name`),
  KEY `idx_student_skills_student_sort` (`student_id`, `sort_order`),
  CONSTRAINT `fk_student_skills_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `title` VARCHAR(180) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `technologies` VARCHAR(500) DEFAULT NULL,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `project_url` VARCHAR(500) DEFAULT NULL,
  `repository_url` VARCHAR(500) DEFAULT NULL,
  `started_at` DATE DEFAULT NULL,
  `completed_at` DATE DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_projects_student_sort` (`student_id`, `sort_order`),
  KEY `idx_student_projects_featured` (`student_id`, `is_featured`, `is_visible`),
  CONSTRAINT `fk_student_projects_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_experiences` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `position` VARCHAR(150) NOT NULL,
  `organization` VARCHAR(180) NOT NULL,
  `employment_type` VARCHAR(50) DEFAULT NULL,
  `location` VARCHAR(180) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `started_at` DATE DEFAULT NULL,
  `ended_at` DATE DEFAULT NULL,
  `is_current` TINYINT(1) NOT NULL DEFAULT 0,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_experiences_student_sort` (`student_id`, `sort_order`),
  KEY `idx_student_experiences_current` (`student_id`, `is_current`, `is_visible`),
  CONSTRAINT `fk_student_experiences_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_activities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `activity_type` VARCHAR(50) NOT NULL DEFAULT 'activity',
  `title` VARCHAR(180) NOT NULL,
  `organization` VARCHAR(180) DEFAULT NULL,
  `role_name` VARCHAR(150) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `activity_date` DATE DEFAULT NULL,
  `reference_url` VARCHAR(500) DEFAULT NULL,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_activities_student_sort` (`student_id`, `sort_order`),
  KEY `idx_student_activities_type` (`student_id`, `activity_type`, `is_visible`),
  CONSTRAINT `fk_student_activities_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
