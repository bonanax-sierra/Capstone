-- Table creation script for Adolescent Risk Analytics System
-- Version: 3.0 (Unified users table)
-- Purpose: Creates all necessary tables with their constraints and indexes

USE `capstone`;

SET foreign_key_checks = 0;

-- --------------------------------------
-- 1. Table Creation
-- --------------------------------------

-- 1.1 Create users table (for both admins and staffs)
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `username` VARCHAR(255) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','staff','coordinator','manager') DEFAULT 'staff',
  `user_token` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `login_attempts` INT(11) DEFAULT 0,
  `locked_until` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.2 Create school table
CREATE TABLE IF NOT EXISTS `school` (
  `school_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`school_id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 1.3 Create activity_info table (rewritten with Municipality)
CREATE TABLE IF NOT EXISTS `activity_info` (
  `activity_id` INT(11) NOT NULL AUTO_INCREMENT,
  `municipality` VARCHAR(255) DEFAULT NULL,
  `barangay` VARCHAR(255) DEFAULT NULL,
  `activity_title` VARCHAR(255) NOT NULL,
  `activity_type` VARCHAR(255) DEFAULT NULL,
  `date_of_activity` DATE DEFAULT NULL,
  `status` ENUM('active','completed','cancelled') DEFAULT 'active',
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`activity_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date_of_activity` (`date_of_activity`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_activity_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 1.4 Updated assessment table
CREATE TABLE IF NOT EXISTS `assessment` (
  `assessment_id` INT(11) NOT NULL AUTO_INCREMENT,

  -- Personal info
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) DEFAULT NULL,
  `extension_name` VARCHAR(50) DEFAULT NULL,
  `sex` ENUM('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `civil_status` ENUM('Single','Married','Other') DEFAULT NULL,
  `school_status` ENUM('In School Youth','Out of School Youth') DEFAULT NULL,
  `employment_status` ENUM('Employed','Unemployed','Underemployed','Other') DEFAULT NULL,
  `highest_educational_attainment` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,

  -- Assessment info
  `grade_level` VARCHAR(50) DEFAULT NULL,
  `current_problems` JSON DEFAULT NULL,
  `desired_service` VARCHAR(255) DEFAULT NULL,
  `pregnant` ENUM('Yes','No','N/A') DEFAULT 'N/A',
  `pregnant_age` VARCHAR(20) DEFAULT NULL,
  `family_planning_method` VARCHAR(255) DEFAULT NULL,
  `impregnated_someone` ENUM('Yes','No','N/A') DEFAULT 'N/A',

  -- Contact info
  `address` TEXT DEFAULT NULL,
  `mobile_accessibility` ENUM('Yes','No') DEFAULT 'No',
  `mobile_number` VARCHAR(20) DEFAULT NULL,
  `mobile_phone_type` ENUM('Smartphone','Basic Phone','Unknown') DEFAULT 'Unknown',
  `mobile_reason` VARCHAR(255) DEFAULT NULL,

  -- ML Risk categories
  `risk_category` VARCHAR(50) DEFAULT NULL,
  `risk_category_random_forest` VARCHAR(50) DEFAULT NULL,
  `risk_category_ann` VARCHAR(50) DEFAULT NULL,
  `risk_category_xgboost` VARCHAR(50) DEFAULT NULL,

  -- Foreign key references
  `school_id` INT(11) DEFAULT NULL,
  `activity_id` INT(11) DEFAULT NULL,

  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

  PRIMARY KEY (`assessment_id`),
  KEY `idx_risk_category` (`risk_category`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_activity_id` (`activity_id`),

  CONSTRAINT `fk_assessment_school` FOREIGN KEY (`school_id`) 
    REFERENCES `school` (`school_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_assessment_activity` FOREIGN KEY (`activity_id`) 
    REFERENCES `activity_info` (`activity_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- 1.5 Create user_logs table
CREATE TABLE IF NOT EXISTS `user_logs` (
  `log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`log_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_user_logs` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.6 Create system_settings table
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `setting_type` ENUM('string','integer','boolean','json') DEFAULT 'string',
  `description` TEXT DEFAULT NULL,
  `updated_by` INT(11) DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`setting_id`),
  KEY `idx_setting_key` (`setting_key`),
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.7 Create backup_history table
CREATE TABLE IF NOT EXISTS `backup_history` (
  `backup_id` INT(11) NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL,
  `file_size` BIGINT(20) DEFAULT NULL,
  `backup_type` ENUM('manual','automatic') DEFAULT 'manual',
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `status` ENUM('success','failed','in_progress') DEFAULT 'success',
  `error_message` TEXT DEFAULT NULL,
  PRIMARY KEY (`backup_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_backup_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.8 Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info','warning','error','success') DEFAULT 'info',
  `target_user_id` INT(11) DEFAULT NULL,
  `is_read` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `idx_target_user` (`target_user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.9 Create assessment_history table
CREATE TABLE IF NOT EXISTS `assessment_history` (
  `history_id` INT(11) NOT NULL AUTO_INCREMENT,
  `assessment_id` INT(11) NOT NULL,
  `field_name` VARCHAR(100) NOT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `changed_by` INT(11) DEFAULT NULL,
  `changed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`history_id`),
  KEY `idx_assessment_id` (`assessment_id`),
  KEY `idx_changed_at` (`changed_at`),
  CONSTRAINT `fk_assessment_history` FOREIGN KEY (`assessment_id`) REFERENCES `assessment` (`assessment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
