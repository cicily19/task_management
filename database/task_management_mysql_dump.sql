-- MySQL dump for Task Management (Laravel assignment)
-- Database: MySQL 8.x
-- Charset: utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `tasks`;

CREATE TABLE `tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `due_date` date NOT NULL,
  `priority` enum('low','medium','high') NOT NULL,
  `status` enum('pending','in_progress','done') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tasks_title_due_date_unique` (`title`,`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tasks` (`id`, `title`, `due_date`, `priority`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Draft API documentation', '2026-04-01', 'high', 'pending', NOW(), NOW()),
(2, 'Review pull requests', '2026-04-01', 'medium', 'in_progress', NOW(), NOW()),
(3, 'Update README deployment section', '2026-04-02', 'low', 'done', NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;
