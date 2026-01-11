-- LSB Work Order System - Department-Based Approval Migration
-- Version: 2.0
-- Date: 2026-01-02
-- Description: Migrate from role-based to department-based approval system

SET NAMES utf8mb4;

-- ============================================
-- 1. Create new department configuration table
-- ============================================
DROP TABLE IF EXISTS `lsb_wo_dept_config`;
CREATE TABLE `lsb_wo_dept_config` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dept_code` VARCHAR(30) NOT NULL COMMENT 'OPT, EXEC, ACC, ENG, ADM',
  `dept_name` VARCHAR(100) NOT NULL,
  `can_approve` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Can approve WO requests',
  `can_delete` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Can delete WO requests',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dept_code` (`dept_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Department configuration table';

-- Insert default departments
INSERT INTO `lsb_wo_dept_config` (`dept_code`, `dept_name`, `can_approve`, `can_delete`, `sort_order`) VALUES
('OPT', 'Operation', 0, 0, 1),
('EXEC', 'Executive', 1, 0, 2),
('ACC', 'Accounting', 1, 0, 3),
('ENG', 'Engineering', 1, 0, 4),
('ADM', 'Administrator', 0, 1, 5);

-- ============================================
-- 2. Modify users table: add title and dept_code
-- ============================================
ALTER TABLE `lsb_wo_users`
  ADD COLUMN `title` VARCHAR(100) NULL COMMENT 'Job title' AFTER `name`,
  ADD COLUMN `dept_code` VARCHAR(30) NULL COMMENT 'Department code' AFTER `title`,
  ADD INDEX `idx_dept_code` (`dept_code`);

-- ============================================
-- 3. Modify review table: use dept_code instead of reviewer_role
-- ============================================
-- First, backup old review table structure (data will need manual migration if exists)
-- The review table structure will be:
-- - reviewer_dept: department code
-- - reviewer_id, reviewer_name, reviewer_email: who actually reviewed (any member of dept)

ALTER TABLE `lsb_wo_review`
  ADD COLUMN `reviewer_dept` VARCHAR(30) NULL COMMENT 'Department code' AFTER `wo_id`,
  ADD COLUMN `reviewer_id` INT UNSIGNED NULL COMMENT 'Actual reviewer user ID' AFTER `reviewer_dept`,
  MODIFY COLUMN `reviewer_role` VARCHAR(30) NULL COMMENT 'Legacy role code',
  DROP INDEX `uk_wo_reviewer`,
  ADD UNIQUE KEY `uk_wo_dept` (`wo_id`, `reviewer_dept`);

-- ============================================
-- 4. Insert default users for each department (for testing)
-- ============================================
-- Password for all test users: password123
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO `lsb_wo_users` (`email`, `name`, `title`, `dept_code`, `password_hash`, `is_admin`) VALUES
('opt1@lsb.com', 'Operation User 1', 'Operations Coordinator', 'OPT', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
('opt2@lsb.com', 'Operation User 2', 'Operations Manager', 'OPT', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
('exec1@lsb.com', 'Executive User 1', 'Vice President', 'EXEC', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
('exec2@lsb.com', 'Executive User 2', 'General Manager', 'EXEC', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
('acc1@lsb.com', 'Accounting User 1', 'Controller', 'ACC', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
('acc2@lsb.com', 'Accounting User 2', 'CFO', 'ACC', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
('eng1@lsb.com', 'Engineering User 1', 'Project Engineer', 'ENG', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
('eng2@lsb.com', 'Engineering User 2', 'Technical Director', 'ENG', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
('adm1@lsb.com', 'Admin User 1', 'System Administrator', 'ADM', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1)
ON DUPLICATE KEY UPDATE dept_code = VALUES(dept_code), title = VALUES(title);

-- Update existing admin user
UPDATE `lsb_wo_users` SET dept_code = 'ADM', title = 'System Administrator' WHERE email = 'admin@libertysteelbuildings.com';
