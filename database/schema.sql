-- database/schema.sql
-- Task Manager Pro - Schema de base de données complet

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Structure de la base de données
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `task_manager_pro` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `task_manager_pro`;

-- --------------------------------------------------------
-- Table des utilisateurs
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(50) DEFAULT NULL,
  `last_name` VARCHAR(50) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('user', 'admin', 'manager') DEFAULT 'user',
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `theme` ENUM('light', 'dark', 'auto') DEFAULT 'light',
  `language` VARCHAR(5) DEFAULT 'fr',
  `timezone` VARCHAR(50) DEFAULT 'Europe/Paris',
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table des projets
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `color` VARCHAR(7) DEFAULT '#4361ee',
  `icon` VARCHAR(50) DEFAULT 'folder',
  `owner_id` INT(11) UNSIGNED NOT NULL,
  `status` ENUM('active', 'archived', 'completed') DEFAULT 'active',
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_owner` (`owner_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_project_owner` FOREIGN KEY (`owner_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table des membres de projet
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `project_members` (
  `project_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `role` ENUM('viewer', 'member', 'admin') DEFAULT 'member',
  `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`, `user_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_pm_project` FOREIGN KEY (`project_id`) 
    REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pm_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table des tâches
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `project_id` INT(11) UNSIGNED DEFAULT NULL,
  `creator_id` INT(11) UNSIGNED NOT NULL,
  `assignee_id` INT(11) UNSIGNED DEFAULT NULL,
  `parent_task_id` INT(11) UNSIGNED DEFAULT NULL,
  `status` ENUM('pending', 'in_progress', 'completed', 'archived', 'cancelled') DEFAULT 'pending',
  `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
  `due_date` DATETIME DEFAULT NULL,
  `start_date` DATETIME DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `estimated_hours` DECIMAL(5,2) DEFAULT NULL,
  `actual_hours` DECIMAL(5,2) DEFAULT 0,
  `progress` INT(3) DEFAULT 0,
  `position` INT(11) DEFAULT 0,
  `is_recurring` BOOLEAN DEFAULT FALSE,
  `recurrence_pattern` JSON DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_creator` (`creator_id`),
  KEY `idx_assignee` (`assignee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_parent` (`parent_task_id`),
  FULLTEXT KEY `ft_search` (`title`, `description`),
  CONSTRAINT `fk_task_project` FOREIGN KEY (`project_id`) 
    REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_task_creator` FOREIGN KEY (`creator_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_task_assignee` FOREIGN KEY (`assignee_id`) 
    REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_task_parent` FOREIGN KEY (`parent_task_id`) 
    REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table des tags
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tags` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `color` VARCHAR(7) DEFAULT '#cccccc',
  `icon` VARCHAR(50) DEFAULT NULL,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `project_id` INT(11) UNSIGNED DEFAULT NULL,
  `is_global` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tag_per_scope` (`name`, `user_id`, `project_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_project` (`project_id`),
  CONSTRAINT `fk_tag_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tag_project` FOREIGN KEY (`project_id`) 
    REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table de relation tâches-tags
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `task_tags` (
  `task_id` INT(11) UNSIGNED NOT NULL,
  `tag_id` INT(11) UNSIGNED NOT NULL,
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`task_id`, `tag_id`),
  KEY `idx_tag` (`tag_id`),
  CONSTRAINT `fk_tt_task` FOREIGN KEY (`task_id`) 
    REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_tag` FOREIGN KEY (`tag_id`) 
    REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table des entrées de temps
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `time_entries` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `description` TEXT,
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME DEFAULT NULL,
  `duration` INT(11) DEFAULT NULL COMMENT 'Durée en secondes',
  `is_billable` BOOLEAN DEFAULT TRUE,
  `hourly_rate` DECIMAL(10,2) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_task` (`task_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_start` (`start_time`),
  KEY `idx_billable` (`is_billable`),
  CONSTRAINT `fk_te_task` FOREIGN KEY (`task_id`) 
    REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_te_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table des commentaires
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `parent_id` INT(11) UNSIGNED DEFAULT NULL,
  `content` TEXT NOT NULL,
  `is_edited` BOOLEAN DEFAULT FALSE,
  `edited_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_task` (`task_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_parent` (`parent_id`),
  CONSTRAINT `fk_comment_task` FOREIGN KEY (`task_id`) 
    REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_parent` FOREIGN KEY (`parent_id`) 
    REFERENCES `comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table des pièces jointes
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `attachments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` INT(11) UNSIGNED DEFAULT NULL,
  `comment_id` INT(11) UNSIGNED DEFAULT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `size` INT(11) NOT NULL COMMENT 'Taille en octets',
  `path` VARCHAR(500) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_task` (`task_id`),
  KEY `idx_comment` (`comment_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_attach_task` FOREIGN KEY (`task_id`) 
    REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attach_comment` FOREIGN KEY (`comment_id`) 
    REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attach_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table des notifications
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT,
  `data` JSON DEFAULT NULL,
  `is_read` BOOLEAN DEFAULT FALSE,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_type` (`type`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table de l'historique des activités
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT(11) UNSIGNED NOT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table des sessions
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(128) NOT NULL,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT,
  `payload` TEXT NOT NULL,
  `last_activity` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_last_activity` (`last_activity`),
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table des préférences utilisateur
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_preferences` (
  `user_id` INT(11) UNSIGNED NOT NULL,
  `key` VARCHAR(50) NOT NULL,
  `value` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `key`),
  CONSTRAINT `fk_pref_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- --------------------------------------------------------
-- Index supplémentaires pour les performances
-- --------------------------------------------------------

CREATE INDEX idx_tasks_search ON tasks(status, priority, due_date);
CREATE INDEX idx_time_entries_report ON time_entries(user_id, task_id, start_time);
CREATE INDEX idx_notifications_unread ON notifications(user_id, is_read, created_at);

-- --------------------------------------------------------
-- Triggers
-- --------------------------------------------------------

DELIMITER $$

-- Trigger pour mettre à jour les heures réelles d'une tâche
CREATE TRIGGER update_task_actual_hours
AFTER INSERT ON time_entries
FOR EACH ROW
BEGIN
  UPDATE tasks 
  SET actual_hours = (
    SELECT COALESCE(SUM(duration), 0) / 3600
    FROM time_entries 
    WHERE task_id = NEW.task_id
  )
  WHERE id = NEW.task_id;
END$$

-- Trigger pour créer une notification lors de l'assignation d'une tâche
CREATE TRIGGER notify_task_assignment
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
  IF NEW.assignee_id IS NOT NULL AND (OLD.assignee_id IS NULL OR OLD.assignee_id != NEW.assignee_id) THEN
    INSERT INTO notifications (user_id, type, title, message, data)
    VALUES (
      NEW.assignee_id,
      'task_assigned',
      'Nouvelle tâche assignée',
      CONCAT('La tâche "', NEW.title, '" vous a été assignée'),
      JSON_OBJECT('task_id', NEW.id, 'assigner_id', NEW.creator_id)
    );
  END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------
-- Données initiales
-- --------------------------------------------------------

-- Tags par défaut
INSERT INTO `tags` (`name`, `color`, `icon`, `is_global`) VALUES
('Bug', '#e74c3c', 'bug', TRUE),
('Feature', '#3498db', 'sparkles', TRUE),
('Documentation', '#f39c12', 'book', TRUE),
('Urgent', '#c0392b', 'alert-triangle', TRUE),
('Amélioration', '#27ae60', 'trending-up', TRUE),
('Test', '#9b59b6', 'check-circle', TRUE),
('Design', '#e91e63', 'palette', TRUE),
('Backend', '#34495e', 'server', TRUE),
('Frontend', '#16a085', 'layout', TRUE),
('DevOps', '#7f8c8d', 'settings', TRUE);

-- Utilisateur admin par défaut (mot de passe: Admin123!)
INSERT INTO `users` (`username`, `email`, `password`, `first_name`, `last_name`, `role`) VALUES
('admin', 'admin@taskmanager.local', '$2y$10$YKqPVPaLKqO3F7wZrKHPeOQxZmr3PM5CmAkxR9OqG2hmc.kfmJH3y', 'Admin', 'System', 'admin');
