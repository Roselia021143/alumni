CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- บัญชีเริ่มต้นของระบบ (คำสั่งนี้รันซ้ำได้)
INSERT INTO `admins` (`username`, `password`)
VALUES (
  'admin',
  '$2y$10$ek0juWEDkK.gK.r9rbGxR.XgaorrNLzm6MbhJ345ReJl.6df8ncda'
)
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

