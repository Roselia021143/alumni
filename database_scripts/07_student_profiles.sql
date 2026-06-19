-- Student profile and portfolio schema.
-- Select the target database in phpMyAdmin before importing this file.
-- Import once, after 06_update_multimedia_major_name.sql.

ALTER TABLE `students`
  ADD COLUMN `education_status` ENUM('unspecified', 'studying', 'graduated', 'on_leave') NOT NULL DEFAULT 'unspecified' AFTER `major`,
  ADD COLUMN `current_study_year` TINYINT UNSIGNED DEFAULT NULL AFTER `education_status`,
  ADD COLUMN `expected_graduation_year` SMALLINT UNSIGNED DEFAULT NULL AFTER `current_study_year`,
  ADD COLUMN `graduation_year` SMALLINT UNSIGNED DEFAULT NULL AFTER `expected_graduation_year`,
  ADD COLUMN `headline` VARCHAR(180) DEFAULT NULL AFTER `graduation_year`,
  ADD COLUMN `bio` TEXT DEFAULT NULL AFTER `headline`,
  ADD COLUMN `employment_status` ENUM('unspecified', 'looking_for_internship', 'looking_for_work', 'employed', 'freelance', 'business_owner', 'not_available') NOT NULL DEFAULT 'unspecified' AFTER `bio`,
  ADD COLUMN `current_position` VARCHAR(150) DEFAULT NULL AFTER `employment_status`,
  ADD COLUMN `current_company` VARCHAR(180) DEFAULT NULL AFTER `current_position`,
  ADD COLUMN `work_location` VARCHAR(180) DEFAULT NULL AFTER `current_company`,
  ADD COLUMN `website_url` VARCHAR(500) DEFAULT NULL AFTER `work_location`,
  ADD COLUMN `github_url` VARCHAR(500) DEFAULT NULL AFTER `website_url`,
  ADD COLUMN `linkedin_url` VARCHAR(500) DEFAULT NULL AFTER `github_url`,
  ADD COLUMN `profile_slug` VARCHAR(120) DEFAULT NULL AFTER `linkedin_url`,
  ADD COLUMN `profile_visibility` ENUM('private', 'members', 'public') NOT NULL DEFAULT 'members' AFTER `profile_slug`,
  ADD COLUMN `about_visible` TINYINT(1) NOT NULL DEFAULT 1 AFTER `profile_visibility`,
  ADD COLUMN `education_visible` TINYINT(1) NOT NULL DEFAULT 1 AFTER `about_visible`,
  ADD COLUMN `employment_visible` TINYINT(1) NOT NULL DEFAULT 1 AFTER `education_visible`,
  ADD COLUMN `skills_visible` TINYINT(1) NOT NULL DEFAULT 1 AFTER `employment_visible`,
  ADD COLUMN `projects_visible` TINYINT(1) NOT NULL DEFAULT 1 AFTER `skills_visible`,
  ADD COLUMN `experiences_visible` TINYINT(1) NOT NULL DEFAULT 1 AFTER `projects_visible`,
  ADD COLUMN `activities_visible` TINYINT(1) NOT NULL DEFAULT 1 AFTER `experiences_visible`,
  ADD UNIQUE KEY `uq_students_profile_slug` (`profile_slug`),
  ADD KEY `idx_students_education_status` (`education_status`),
  ADD KEY `idx_students_employment_status` (`employment_status`),
  ADD KEY `idx_students_profile_visibility` (`profile_visibility`);

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
