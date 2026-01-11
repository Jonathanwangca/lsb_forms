-- LSB Work Order System - Preset Comments Migration
-- Version: 2.1
-- Date: 2026-01-02
-- Description: Add preset comments for review workflow

SET NAMES utf8mb4;

-- ============================================
-- 1. Create preset comments table
-- ============================================
DROP TABLE IF EXISTS `lsb_wo_preset_comments`;
CREATE TABLE `lsb_wo_preset_comments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(20) NOT NULL DEFAULT 'COMMENT' COMMENT 'COMMENT or CONDITION_NOTE',
  `category` VARCHAR(30) NOT NULL DEFAULT 'GENERAL' COMMENT 'ACK, REJECT, CONDITION, GENERAL',
  `comment_text` VARCHAR(500) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_type_category` (`type`, `category`),
  INDEX `idx_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Preset comments for WO review';

-- ============================================
-- 2. Insert default preset comments (for Comment field)
-- ============================================
INSERT INTO `lsb_wo_preset_comments` (`type`, `category`, `comment_text`, `sort_order`) VALUES
-- Approval comments
('COMMENT', 'ACK', 'Approved as submitted.', 1),
('COMMENT', 'ACK', 'Reviewed and approved. Please proceed.', 2),
('COMMENT', 'ACK', 'All requirements met. Approved.', 3),
('COMMENT', 'ACK', 'Budget verified and approved.', 4),
('COMMENT', 'ACK', 'Scope of work is acceptable. Approved.', 5),

-- Conditional approval comments
('COMMENT', 'CONDITION', 'Approved with conditions. See condition notes.', 10),
('COMMENT', 'CONDITION', 'Conditionally approved pending compliance.', 11),

-- Rejection comments
('COMMENT', 'REJECT', 'Incomplete documentation. Please provide missing information.', 20),
('COMMENT', 'REJECT', 'Budget exceeds approved limits. Please revise.', 21),
('COMMENT', 'REJECT', 'Vendor not on approved vendor list.', 22),
('COMMENT', 'REJECT', 'Scope of work needs clarification.', 23),
('COMMENT', 'REJECT', 'Missing required attachments.', 24),
('COMMENT', 'REJECT', 'Cost breakdown required before approval.', 25),

-- General comments
('COMMENT', 'GENERAL', 'Please contact the requester for additional details.', 30),
('COMMENT', 'GENERAL', 'Forwarded to management for final approval.', 31),
('COMMENT', 'GENERAL', 'Reviewed by department.', 32);

-- ============================================
-- 3. Insert default condition notes (for Condition Note field)
-- ============================================
INSERT INTO `lsb_wo_preset_comments` (`type`, `category`, `comment_text`, `sort_order`) VALUES
('CONDITION_NOTE', 'CONDITION', 'Obtain updated insurance certificate before work begins.', 1),
('CONDITION_NOTE', 'CONDITION', 'Submit revised cost breakdown for approval.', 2),
('CONDITION_NOTE', 'CONDITION', 'Ensure all safety requirements are met on site.', 3),
('CONDITION_NOTE', 'CONDITION', 'Obtain final management sign-off before proceeding.', 4),
('CONDITION_NOTE', 'CONDITION', 'Complete permit application before starting work.', 5),
('CONDITION_NOTE', 'CONDITION', 'Coordinate schedule with project manager.', 6),
('CONDITION_NOTE', 'CONDITION', 'Provide material specifications for review.', 7),
('CONDITION_NOTE', 'CONDITION', 'Confirm payment terms with accounting department.', 8);
