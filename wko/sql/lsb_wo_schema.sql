-- LSB Work Order System Database Schema
-- Version: 1.0
-- Date: 2025-12-29
-- Database: lsb_forms

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. 用户表: lsb_wo_users
-- ============================================
DROP TABLE IF EXISTS `lsb_wo_users`;
CREATE TABLE `lsb_wo_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(190) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(30) NULL COMMENT '关联reviewer_config的role_code',
  `department` VARCHAR(100) NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` DATETIME NULL,
  `login_attempts` INT NOT NULL DEFAULT 0,
  `locked_until` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  INDEX `idx_role` (`role`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='WO系统用户表';

-- ============================================
-- 2. 审阅人配置表: lsb_wo_reviewer_config
-- ============================================
DROP TABLE IF EXISTS `lsb_wo_reviewer_config`;
CREATE TABLE `lsb_wo_reviewer_config` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_code` VARCHAR(30) NOT NULL,
  `role_name` VARCHAR(100) NOT NULL,
  `role_name_cn` VARCHAR(100) NULL,
  `reviewer_name` VARCHAR(100) NOT NULL,
  `reviewer_email` VARCHAR(190) NOT NULL,
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_code` (`role_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审阅人配置表';

-- ============================================
-- 3. WO主表: lsb_wo_header
-- ============================================
DROP TABLE IF EXISTS `lsb_wo_header`;
CREATE TABLE `lsb_wo_header` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,

  -- 工作单信息
  `wo_no` VARCHAR(30) NOT NULL COMMENT 'WO编号 WO-2025-000001',
  `title` VARCHAR(200) NULL COMMENT '标题/简述',

  -- 项目信息
  `lsb_job_no` VARCHAR(50) NULL COMMENT 'LSB项目编号',
  `project_code` VARCHAR(50) NULL COMMENT '项目代码',
  `project_name` VARCHAR(200) NULL COMMENT '项目名称',
  `project_address` VARCHAR(300) NULL COMMENT '项目地址',
  `owner_name` VARCHAR(200) NULL COMMENT '业主名称',

  -- 供应商信息
  `vendor_name` VARCHAR(200) NULL COMMENT '供应商/分包商名称',
  `vendor_address` VARCHAR(300) NULL COMMENT '供应商地址',
  `vendor_contact` VARCHAR(100) NULL COMMENT '联系人',
  `vendor_phone` VARCHAR(50) NULL COMMENT '电话',
  `vendor_email` VARCHAR(190) NULL COMMENT '邮箱',

  -- 金额信息
  `original_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '原始合同金额',
  `change_order_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '变更单总额',
  `total_amount` DECIMAL(12,2) GENERATED ALWAYS AS (`original_amount` + `change_order_amount`) STORED COMMENT '合同至今总额',
  `currency` CHAR(3) NOT NULL DEFAULT 'CAD',
  `cost_code` VARCHAR(50) NULL COMMENT '成本代码',
  `holdback_percent` DECIMAL(5,2) NOT NULL DEFAULT 10.00 COMMENT '留置扣款百分比',

  -- 范围描述
  `scope_summary` TEXT NULL COMMENT '工作范围摘要',

  -- 申请人信息
  `requester_id` INT UNSIGNED NULL COMMENT '申请人用户ID',
  `requester_name` VARCHAR(100) NOT NULL,
  `requester_email` VARCHAR(190) NULL,
  `requester_department` VARCHAR(100) NULL,

  -- 状态和时间
  `status` VARCHAR(20) NOT NULL DEFAULT 'DRAFT' COMMENT 'DRAFT/SUBMITTED/DONE/REJECTED',
  `issued_date` DATE NULL COMMENT '签发日期',
  `submitted_at` DATETIME NULL,
  `completed_at` DATETIME NULL,

  -- Excel解析数据
  `excel_parsed_data` JSON NULL COMMENT 'GPT解析的Excel JSON数据',

  -- 备注
  `memo` TEXT NULL,

  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wo_no` (`wo_no`),
  INDEX `idx_status` (`status`),
  INDEX `idx_project_code` (`project_code`),
  INDEX `idx_lsb_job_no` (`lsb_job_no`),
  INDEX `idx_requester_id` (`requester_id`),
  INDEX `idx_requester_email` (`requester_email`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='WO主表';

-- ============================================
-- 4. 审阅记录表: lsb_wo_review
-- ============================================
DROP TABLE IF EXISTS `lsb_wo_review`;
CREATE TABLE `lsb_wo_review` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wo_id` INT UNSIGNED NOT NULL,

  `reviewer_role` VARCHAR(30) NOT NULL COMMENT 'PM, PM_MANAGER, GM, CFO, TECH',
  `reviewer_name` VARCHAR(100) NOT NULL,
  `reviewer_email` VARCHAR(190) NOT NULL,

  -- 审阅决定
  `decision` VARCHAR(30) NOT NULL DEFAULT 'PENDING' COMMENT 'PENDING/ACK/ACK_WITH_CONDITION/REJECTED',
  `comment` TEXT NULL,
  `condition_note` TEXT NULL COMMENT '条件说明 (ACK_WITH_CONDITION时使用)',
  `reviewed_at` DATETIME NULL,

  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wo_reviewer` (`wo_id`, `reviewer_role`),
  INDEX `idx_wo_id` (`wo_id`),
  INDEX `idx_reviewer_email` (`reviewer_email`),
  INDEX `idx_decision` (`decision`),

  CONSTRAINT `fk_review_wo` FOREIGN KEY (`wo_id`)
    REFERENCES `lsb_wo_header` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='WO审阅记录表';

-- ============================================
-- 5. 文件附件表: lsb_wo_files
-- ============================================
DROP TABLE IF EXISTS `lsb_wo_files`;
CREATE TABLE `lsb_wo_files` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wo_id` INT UNSIGNED NOT NULL,

  `file_name` VARCHAR(255) NOT NULL COMMENT '原始文件名',
  `file_path` VARCHAR(500) NULL COMMENT '存储路径',
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `file_type` VARCHAR(100) NULL COMMENT 'MIME类型',
  `file_ext` VARCHAR(20) NULL,
  `file_category` VARCHAR(50) NOT NULL DEFAULT 'attachment' COMMENT 'contract/change_order/attachment',

  `uploaded_by` VARCHAR(100) NULL,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,

  PRIMARY KEY (`id`),
  INDEX `idx_wo_id` (`wo_id`),
  INDEX `idx_category` (`file_category`),
  INDEX `idx_is_active` (`is_active`),

  CONSTRAINT `fk_file_wo` FOREIGN KEY (`wo_id`)
    REFERENCES `lsb_wo_header` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='WO文件附件表';

-- ============================================
-- 6. 变更单表: lsb_wo_change_order (Phase 3)
-- ============================================
DROP TABLE IF EXISTS `lsb_wo_change_order`;
CREATE TABLE `lsb_wo_change_order` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wo_id` INT UNSIGNED NOT NULL,

  `co_no` INT NOT NULL COMMENT '变更单序号 1,2,3,4...',
  `description` TEXT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,

  `status` VARCHAR(20) NOT NULL DEFAULT 'DRAFT',
  `issued_date` DATE NULL,

  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wo_co` (`wo_id`, `co_no`),
  INDEX `idx_wo_id` (`wo_id`),

  CONSTRAINT `fk_co_wo` FOREIGN KEY (`wo_id`)
    REFERENCES `lsb_wo_header` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='WO变更单表';

-- ============================================
-- 7. WO编号序列表
-- ============================================
DROP TABLE IF EXISTS `lsb_wo_sequence`;
CREATE TABLE `lsb_wo_sequence` (
  `year` INT NOT NULL,
  `last_number` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='WO编号序列';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- 初始数据
-- ============================================

-- 插入默认管理员 (密码: admin123, 部署后请修改)
INSERT INTO `lsb_wo_users` (`email`, `name`, `password_hash`, `role`, `is_admin`) VALUES
('admin@libertysteelbuildings.com', 'System Admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN', 1);

-- 插入审阅人配置
INSERT INTO `lsb_wo_reviewer_config` (`role_code`, `role_name`, `role_name_cn`, `reviewer_name`, `reviewer_email`, `sort_order`, `is_required`) VALUES
('PM', 'Project Manager', '项目经理', 'PM Name', 'pm@libertysteelbuildings.com', 1, 1),
('PM_MANAGER', 'PM Manager', '项目经理主管', 'PM Manager', 'pmm@libertysteelbuildings.com', 2, 1),
('TECH', 'Technical Director', '技术总监', 'Tech Director', 'tech@libertysteelbuildings.com', 3, 0),
('CFO', 'Finance Director', '财务总监', 'CFO Name', 'cfo@libertysteelbuildings.com', 4, 1),
('GM', 'General Manager', '总经理', 'GM Name', 'gm@libertysteelbuildings.com', 5, 1);

-- 初始化当年序列
INSERT INTO `lsb_wo_sequence` (`year`, `last_number`) VALUES (YEAR(CURDATE()), 0);
