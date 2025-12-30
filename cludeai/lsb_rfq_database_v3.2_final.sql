-- ============================================================
-- LSB RFQ System V3.2 - 最终版数据库脚本
-- Final Database Schema and Reference Data
--
-- 创建日期: 2025-12-29
-- 数据库: lsb_forms
-- 字符集: utf8mb4
--
-- 包含内容:
--   1. 15个数据表的完整结构定义
--   2. 全部参考数据 (lsb_rfq_reference)
--
-- 表清单:
--   - lsb_rfq_main          : RFQ主表 (基本信息+联系人+结构+范围+安装)
--   - lsb_rfq_steel         : 钢结构表 (尺寸+主结构+次结构)
--   - lsb_rfq_envelope      : 围护系统表 (屋面+墙面+雨篷+采光)
--   - lsb_rfq_order_entry   : 报价资料表 (Order Entry)
--   - lsb_rfq_panel         : 板材表 (屋面+墙面板材)
--   - lsb_rfq_insulation    : 保温棉表 (屋面+墙面保温棉)
--   - lsb_rfq_method        : 做法表 (屋面+墙面做法说明)
--   - lsb_rfq_drainage      : 排水系统表
--   - lsb_rfq_files         : 文件上传表
--   - lsb_rfq_remarks       : 备注表 (补充说明+变更记录)
--   - lsb_rfq_reference     : 参数选项表
--   - lsb_rfq_cladding_spec : 板材规格明细表 (V3.2新增)
--   - lsb_rfq_cladding_method: 构造做法表 (V3.2新增)
--   - lsb_rfq_supplements   : 补充说明表 (V3.2新增)
--   - lsb_rfq_change_log    : 变更记录表 (V3.2新增)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- PART 1: 表结构定义
-- ============================================================

-- ------------------------------------------------------------
-- 1. RFQ主表
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_main`;
CREATE TABLE `lsb_rfq_main` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_no` varchar(50) NOT NULL COMMENT '报价项目申请号 RFQ No.',
  `job_number` varchar(50) DEFAULT NULL COMMENT '项目编号 Job Number',
  `project_name` varchar(255) DEFAULT NULL COMMENT '项目名称 Project Name',
  `project_location` varchar(255) DEFAULT NULL COMMENT '项目所在位置 Project Location',
  `building_qty` int(11) DEFAULT 1 COMMENT '单体数量 Building Qty',
  `floor_area_1` varchar(50) DEFAULT NULL COMMENT '建筑屋面面积1',
  `floor_area_2` varchar(50) DEFAULT NULL COMMENT '建筑屋面面积2',
  `due_date` date DEFAULT NULL COMMENT '要求报价提交日期 Due by',
  `liberty_contact` varchar(100) DEFAULT NULL COMMENT 'Liberty联系电话',
  `status` enum('draft','submitted','quoted','approved','rejected') DEFAULT 'draft' COMMENT '状态',
  `contact_to` varchar(100) DEFAULT NULL COMMENT '发给 To (如: USAS Shanghai)',
  `contact_email` varchar(255) DEFAULT NULL COMMENT '电子邮箱地址',
  `attn` varchar(100) DEFAULT NULL COMMENT '收件人 Attn (如: Gordon Wang)',
  `account_manager` varchar(100) DEFAULT NULL COMMENT '客户经理 Account Manager',
  `account_manager_title` varchar(100) DEFAULT NULL COMMENT '客户经理职称',
  `project_type` varchar(100) DEFAULT NULL COMMENT '项目类型 (如: INAVA STEEL jobs)',
  `pre_eng_building` tinyint(1) DEFAULT NULL COMMENT '预制工程建筑 Pre Eng Building',
  `bridge_crane` tinyint(1) DEFAULT NULL COMMENT '天车 Bridge Crane',
  `mezzanine_steels` tinyint(1) DEFAULT NULL COMMENT '夹层 Mezzanine Steels',
  `factory_mutual` tinyint(1) DEFAULT NULL COMMENT 'Factory Mutual',
  `loading_canopy` tinyint(1) DEFAULT NULL COMMENT '大雨蓬 Loading Canopy',
  `future_expansion` tinyint(1) DEFAULT NULL COMMENT '扩建 Building Future Expansion',
  `parapet` tinyint(1) DEFAULT NULL COMMENT '女儿墙 Parapet',
  `concrete_wall_curb` tinyint(1) DEFAULT NULL COMMENT '墙裙 Concrete wall curb',
  `leed` tinyint(1) DEFAULT NULL COMMENT 'LEED认证',
  `erection` tinyint(1) DEFAULT NULL COMMENT '安装 Erection',
  `erection_remarks` text DEFAULT NULL COMMENT '安装备注',
  `scope_type` varchar(50) DEFAULT NULL COMMENT '主次围材料类型代码',
  `steel_deck` tinyint(1) DEFAULT NULL COMMENT '楼面板 Steel Deck',
  `partition_wall_frame` tinyint(1) DEFAULT NULL COMMENT '内隔墙 Partition Wall Frame',
  `door_window` tinyint(1) DEFAULT NULL COMMENT '门窗 Man Door & Window',
  `top_coat` tinyint(1) DEFAULT NULL COMMENT '面漆',
  `louver` tinyint(1) DEFAULT NULL COMMENT '百叶窗 Louver',
  `cable_tray_support` tinyint(1) DEFAULT NULL COMMENT '管线吊架 Cable Tray support',
  `railing` tinyint(1) DEFAULT NULL COMMENT '栏杆扶手 Railing',
  `glazing_curtain_wall` tinyint(1) DEFAULT NULL COMMENT '玻璃幕墙 Glazing Curtain Wall',
  `aluminum_cladding` tinyint(1) DEFAULT NULL COMMENT '铝板 Aluminum cladding',
  `laboratory_inspect` tinyint(1) DEFAULT NULL COMMENT '试验检测 Laboratory & Inspect',
  `laboratory_remarks` text DEFAULT NULL COMMENT '检测备注',
  `created_by` int(10) unsigned DEFAULT NULL COMMENT '创建者ID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `version` int(11) DEFAULT 1 COMMENT '版本号',
  `current_version_at` datetime DEFAULT NULL COMMENT '当前版本时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rfq_no` (`rfq_no`),
  KEY `idx_job_number` (`job_number`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='RFQ主表 - 基本信息+联系人+结构+范围+安装';

-- ------------------------------------------------------------
-- 2. 钢结构表
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_steel`;
CREATE TABLE `lsb_rfq_steel` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL COMMENT '关联RFQ ID',
  `length` decimal(10,2) DEFAULT NULL COMMENT '长度 Length(m)',
  `length_source` varchar(100) DEFAULT NULL COMMENT '长度数据来源',
  `width` decimal(10,2) DEFAULT NULL COMMENT '宽度 Width(m)',
  `width_source` varchar(100) DEFAULT NULL COMMENT '宽度数据来源',
  `eave_height` decimal(10,2) DEFAULT NULL COMMENT '檐口高度 Eave Height(m)',
  `eave_height_source` varchar(100) DEFAULT NULL COMMENT '檐口高度数据来源',
  `parapet_top_elevation` decimal(10,2) DEFAULT NULL COMMENT '女儿墙顶标高(m)',
  `parapet_wall_liner` varchar(100) DEFAULT NULL COMMENT '女儿墙内衬板',
  `mezzanine_floor_area` varchar(255) DEFAULT NULL COMMENT '夹层范围 Floor areas',
  `floor_type` varchar(100) DEFAULT NULL COMMENT '楼面形式 Floor type',
  `floor_elevation` decimal(10,2) DEFAULT NULL COMMENT '楼面标高 Floor Elevation(m)',
  `steel_grade` varchar(20) DEFAULT NULL COMMENT '材质等级 Steel Grade',
  `steel_manufacturer` varchar(100) DEFAULT NULL COMMENT '原材料厂家代码',
  `processing_plant` varchar(100) DEFAULT NULL COMMENT '加工厂代码',
  `primer_type` varchar(100) DEFAULT NULL COMMENT '底漆类型代码',
  `primer_thickness` int(11) DEFAULT NULL COMMENT '底漆厚度(μm)',
  `primary_steel_note` text DEFAULT NULL COMMENT '主钢架其他要求 Note',
  `intermediate_coat` varchar(100) DEFAULT NULL COMMENT '中间漆',
  `intermediate_thickness` int(11) DEFAULT NULL COMMENT '中间漆厚度(μm)',
  `top_coat_paint` varchar(100) DEFAULT NULL COMMENT '面漆',
  `top_coat_thickness` int(11) DEFAULT NULL COMMENT '面漆厚度(μm)',
  `coating_scope` text DEFAULT NULL COMMENT '涂刷范围',
  `paint_method` varchar(50) DEFAULT NULL COMMENT '面漆涂刷方式代码',
  `exposed_paint` varchar(100) DEFAULT NULL COMMENT '外露构件油漆',
  `exposed_paint_scope` text DEFAULT NULL COMMENT '外露构件油漆范围',
  `fire_coating` varchar(100) DEFAULT NULL COMMENT '防火涂料类型',
  `fire_coating_scope` text DEFAULT NULL COMMENT '防火涂料范围',
  `fire_coating_na` tinyint(1) DEFAULT NULL COMMENT '防火涂料是否N/A',
  `secondary_manufacturer` varchar(100) DEFAULT NULL COMMENT '次结构原材料厂家代码',
  `roof_purlin_galvanized` tinyint(1) DEFAULT NULL COMMENT '屋面檩条是否镀锌',
  `roof_purlin_paint` varchar(100) DEFAULT NULL COMMENT '屋面檩条油漆代码',
  `wall_purlin_galvanized` tinyint(1) DEFAULT NULL COMMENT '墙面檩条是否镀锌',
  `wall_purlin_paint` varchar(100) DEFAULT NULL COMMENT '墙面檩条油漆代码',
  `checkered_plate_paint` varchar(100) DEFAULT NULL COMMENT '花纹钢板处理代码',
  `checkered_plate_scope` text DEFAULT NULL COMMENT '花纹钢板范围',
  `checkered_plate_remarks` text DEFAULT NULL COMMENT '花纹钢板备注',
  `other_requirements` text DEFAULT NULL COMMENT '其他要求',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rfq_id` (`rfq_id`),
  CONSTRAINT `lsb_rfq_steel_ibfk_1` FOREIGN KEY (`rfq_id`) REFERENCES `lsb_rfq_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='RFQ钢结构表 - 尺寸+主结构+次结构';

-- ------------------------------------------------------------
-- 3. 围护系统表
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_envelope`;
CREATE TABLE `lsb_rfq_envelope` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL COMMENT '关联RFQ ID',
  `wall_material` varchar(100) DEFAULT NULL COMMENT '屋墙面材料代码',
  `material_remarks` text DEFAULT NULL COMMENT '材料备注',
  `is_renovation` tinyint(1) DEFAULT NULL COMMENT '是否改造项目',
  `structural_reinforcement` tinyint(1) DEFAULT NULL COMMENT '结构加固',
  `cladding_addition` tinyint(1) DEFAULT NULL COMMENT '围护板加建',
  `reuse` tinyint(1) DEFAULT NULL COMMENT '利旧',
  `mep_installation` tinyint(1) DEFAULT NULL COMMENT '机电安装',
  `renovation_other` tinyint(1) DEFAULT NULL COMMENT '其他改造',
  `renovation_remarks` text DEFAULT NULL COMMENT '改造备注&补充',
  `waterproof_standard` tinyint(1) DEFAULT NULL COMMENT '是否考虑GB55030-2022',
  `waterproof_remarks` text DEFAULT NULL COMMENT '防水规范备注',
  `aclok_roof` tinyint(1) DEFAULT NULL COMMENT 'Aclok铝镁锰屋面板',
  `aclok_remarks` text DEFAULT NULL COMMENT '铝镁锰备注',
  `sandwich_panel` tinyint(1) DEFAULT NULL COMMENT '夹芯板',
  `sandwich_remarks` text DEFAULT NULL COMMENT '夹芯板备注',
  `roof_ventilator` tinyint(1) DEFAULT NULL COMMENT '屋面通风器',
  `roof_opening` tinyint(1) DEFAULT NULL COMMENT '屋面开口',
  `ventilator_requirements` text DEFAULT NULL COMMENT '通风器/开口尺寸及要求',
  `roof_skylight` tinyint(1) DEFAULT NULL COMMENT '屋面气楼/天窗(条形)',
  `skylight_requirements` text DEFAULT NULL COMMENT '气楼/天窗尺寸及要求',
  `roof_ridge_lantern` tinyint(1) DEFAULT NULL COMMENT '屋脊气楼/天窗 Ridge Skylight',
  `roof_ridge_lantern_remarks` text DEFAULT NULL COMMENT '屋脊气楼备注及尺寸要求',
  `pv_system` tinyint(1) DEFAULT NULL COMMENT 'LS585屋面光伏系统',
  `pv_requirements` text DEFAULT NULL COMMENT '光伏系统要求',
  `roof_liner_layout` varchar(50) DEFAULT NULL COMMENT '屋面内衬板布置方式代码',
  `roof_liner_remarks` text DEFAULT NULL COMMENT '屋面内衬板备注',
  `roof_waterproof_membrane` tinyint(1) DEFAULT NULL COMMENT '屋面防水透气膜',
  `roof_waterproof_material` varchar(100) DEFAULT NULL COMMENT '屋面防水透气膜材料',
  `roof_vapor_barrier` tinyint(1) DEFAULT NULL COMMENT '屋面隔汽膜',
  `roof_vapor_material` varchar(100) DEFAULT NULL COMMENT '屋面隔汽膜材料',
  `roof_wire_mesh` varchar(50) DEFAULT NULL COMMENT '屋面钢丝网',
  `roof_wire_mesh_material` varchar(100) DEFAULT NULL COMMENT '屋面钢丝网材料',
  `canopy_has_insulation` tinyint(1) DEFAULT NULL COMMENT '大雨蓬是否有保温棉',
  `canopy_insulation_remarks` varchar(255) DEFAULT NULL COMMENT '大雨蓬保温棉备注',
  `wall_outer_curb_height` decimal(5,2) DEFAULT NULL COMMENT '墙面外板墙裙高度(m)',
  `wall_outer_layout` varchar(20) DEFAULT NULL COMMENT '墙面外板铺设方式代码',
  `wall_liner_curb_height` decimal(5,2) DEFAULT NULL COMMENT '墙面内板墙裙高度(m)',
  `wall_liner_layout` varchar(20) DEFAULT NULL COMMENT '墙面内板铺设方式代码',
  `wall_waterproof_membrane` varchar(50) DEFAULT NULL COMMENT '墙面防水透气膜',
  `wall_vapor_barrier` varchar(50) DEFAULT NULL COMMENT '墙面隔汽膜',
  `wall_wire_mesh` varchar(50) DEFAULT NULL COMMENT '墙面钢丝网',
  `small_canopy_width` int(11) DEFAULT NULL COMMENT '小雨蓬悬挑宽度(mm)',
  `small_canopy_method` varchar(100) DEFAULT NULL COMMENT '小雨蓬做法代码',
  `small_canopy_drainage` varchar(100) DEFAULT NULL COMMENT '小雨蓬排水做法代码',
  `small_canopy_remarks` text DEFAULT NULL COMMENT '小雨蓬备注',
  `skylight_layout` varchar(100) DEFAULT NULL COMMENT '采光板铺设方式代码',
  `skylight_material` varchar(255) DEFAULT NULL COMMENT '采光板材料代码',
  `skylight_calculation` varchar(100) DEFAULT NULL COMMENT '采光板统计原则代码',
  `skylight_fm_certified` tinyint(1) DEFAULT NULL COMMENT '采光板是否需要FM认证',
  `skylight_brand` varchar(100) DEFAULT NULL COMMENT '采光板品牌代码',
  `skylight_length` varchar(50) DEFAULT NULL COMMENT '采光板长度(m)',
  `skylight_other_requirements` text DEFAULT NULL COMMENT '采光板其他要求',
  `rock_wool_panel` text DEFAULT NULL COMMENT '岩棉板',
  `flexible_roof` text DEFAULT NULL COMMENT '柔性屋面',
  `envelope_other` text DEFAULT NULL COMMENT '其他',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rfq_id` (`rfq_id`),
  CONSTRAINT `lsb_rfq_envelope_ibfk_1` FOREIGN KEY (`rfq_id`) REFERENCES `lsb_rfq_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='RFQ围护系统表 - 屋面+墙面+雨篷+采光+屋脊气楼';

-- ------------------------------------------------------------
-- 4. 报价资料表 (Order Entry)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_order_entry`;
CREATE TABLE `lsb_rfq_order_entry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL COMMENT '关联RFQ ID',
  `mbs_drawing` tinyint(1) DEFAULT NULL COMMENT 'MBS结构图纸',
  `mbs_drawing_file_id` int(10) unsigned DEFAULT NULL COMMENT 'MBS结构图纸文件ID',
  `architect_drawing` tinyint(1) DEFAULT NULL COMMENT '建筑蓝图 Architect Drawing',
  `architect_drawing_file_id` int(10) unsigned DEFAULT NULL COMMENT '建筑蓝图文件ID',
  `foundation_design` tinyint(1) DEFAULT NULL COMMENT '结构蓝图 Foundation Design',
  `foundation_design_file_id` int(10) unsigned DEFAULT NULL COMMENT '结构蓝图文件ID',
  `autocad_drawing` tinyint(1) DEFAULT NULL COMMENT 'AutoCAD 建筑图纸',
  `autocad_drawing_file_id` int(10) unsigned DEFAULT NULL COMMENT 'AutoCAD图纸文件ID',
  `fm_report` tinyint(1) DEFAULT NULL COMMENT 'FM报告',
  `fm_report_file_id` int(10) unsigned DEFAULT NULL COMMENT 'FM报告文件ID',
  `other_docs` tinyint(1) DEFAULT NULL COMMENT '其他文件 other',
  `other_docs_file_id` int(10) unsigned DEFAULT NULL COMMENT '其他文件ID',
  `other_docs_desc` varchar(255) DEFAULT NULL COMMENT '其他文件描述',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rfq_id` (`rfq_id`),
  CONSTRAINT `lsb_rfq_order_entry_ibfk_1` FOREIGN KEY (`rfq_id`) REFERENCES `lsb_rfq_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='RFQ报价资料表 - Order Entry';

-- ------------------------------------------------------------
-- 5. 板材表
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_panel`;
CREATE TABLE `lsb_rfq_panel` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL COMMENT '关联RFQ ID',
  `panel_category` enum('roof','wall') NOT NULL COMMENT '板材分类: roof=屋面, wall=墙面',
  `panel_type` varchar(30) NOT NULL COMMENT '板材类型: outer/liner/canopy_upper/canopy_lower/parapet_liner/partition',
  `panel_no` tinyint(4) DEFAULT 1 COMMENT '序号(1,2,3)',
  `thickness` decimal(5,2) DEFAULT NULL COMMENT '总厚(mm)',
  `profile` varchar(50) DEFAULT NULL COMMENT '板型',
  `strength` varchar(50) DEFAULT NULL COMMENT '强度',
  `coating` varchar(50) DEFAULT NULL COMMENT '涂层',
  `galvanizing` varchar(50) DEFAULT NULL COMMENT '镀铝锌/镀铝锌镁',
  `color` varchar(50) DEFAULT NULL COMMENT '颜色',
  `origin` varchar(50) DEFAULT NULL COMMENT '产地',
  `remarks` text DEFAULT NULL COMMENT '备注',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rfq_id` (`rfq_id`),
  KEY `idx_category_type` (`panel_category`,`panel_type`),
  CONSTRAINT `lsb_rfq_panel_ibfk_1` FOREIGN KEY (`rfq_id`) REFERENCES `lsb_rfq_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='RFQ板材表 - 屋面+墙面板材';

-- ------------------------------------------------------------
-- 6. 保温棉表
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_insulation`;
CREATE TABLE `lsb_rfq_insulation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL COMMENT '关联RFQ ID',
  `insulation_category` enum('roof','wall') NOT NULL COMMENT '保温棉分类: roof=屋面, wall=墙面',
  `insulation_no` tinyint(4) DEFAULT 1 COMMENT '序号(1,2,3)',
  `thickness` int(11) DEFAULT NULL COMMENT '厚度(mm)',
  `density` int(11) DEFAULT NULL COMMENT '容重(kg/m³)',
  `facing` varchar(50) DEFAULT NULL COMMENT '贴面',
  `flame_retardant` varchar(50) DEFAULT NULL COMMENT '阻燃说明',
  `color` varchar(50) DEFAULT NULL COMMENT '颜色',
  `brand` varchar(100) DEFAULT NULL COMMENT '品牌',
  `other_requirements` text DEFAULT NULL COMMENT '其他要求',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rfq_id` (`rfq_id`),
  KEY `idx_category` (`insulation_category`),
  CONSTRAINT `lsb_rfq_insulation_ibfk_1` FOREIGN KEY (`rfq_id`) REFERENCES `lsb_rfq_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='RFQ保温棉表 - 屋面+墙面保温棉';

-- ------------------------------------------------------------
-- 7. 做法表
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_method`;
CREATE TABLE `lsb_rfq_method` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL COMMENT '关联RFQ ID',
  `method_category` enum('roof','wall') NOT NULL COMMENT '做法分类: roof=屋面, wall=墙面',
  `method_no` tinyint(4) DEFAULT 1 COMMENT '做法序号(1,2,3)',
  `method_desc` varchar(255) DEFAULT NULL COMMENT '做法描述',
  `method_detail` text DEFAULT NULL COMMENT '做法详细说明',
  `scope` text DEFAULT NULL COMMENT '适用范围',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rfq_id` (`rfq_id`),
  KEY `idx_category` (`method_category`),
  CONSTRAINT `lsb_rfq_method_ibfk_1` FOREIGN KEY (`rfq_id`) REFERENCES `lsb_rfq_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='RFQ做法表 - 屋面+墙面做法说明';

-- ------------------------------------------------------------
-- 8. 排水系统表
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_drainage`;
CREATE TABLE `lsb_rfq_drainage` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL COMMENT '关联RFQ ID',
  `drainage_type` enum('roof_1','roof_2','canopy') NOT NULL COMMENT '排水类型: roof_1=屋面1, roof_2=屋面2, canopy=雨蓬',
  `method` varchar(100) DEFAULT NULL COMMENT '排水方式',
  `scope` varchar(100) DEFAULT NULL COMMENT '适用范围',
  `gutter_material` varchar(100) DEFAULT NULL COMMENT '天沟材料',
  `gutter_spec` varchar(100) DEFAULT NULL COMMENT '天沟规格(厚度/展宽)',
  `downpipe_type` varchar(100) DEFAULT NULL COMMENT '落水管类型',
  `remarks` text DEFAULT NULL COMMENT '备注',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rfq_id` (`rfq_id`),
  KEY `idx_type` (`drainage_type`),
  CONSTRAINT `lsb_rfq_drainage_ibfk_1` FOREIGN KEY (`rfq_id`) REFERENCES `lsb_rfq_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='RFQ排水系统表';

-- ------------------------------------------------------------
-- 9. 文件上传表
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_files`;
CREATE TABLE `lsb_rfq_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '文件ID',
  `rfq_id` int(10) unsigned NOT NULL COMMENT '关联RFQ ID',
  `file_name` varchar(255) NOT NULL COMMENT '原始文件名',
  `file_path` varchar(500) DEFAULT NULL COMMENT '文件存储路径',
  `file_size` bigint(20) unsigned DEFAULT 0 COMMENT '文件大小(字节)',
  `file_type` varchar(100) DEFAULT NULL COMMENT 'MIME类型',
  `file_ext` varchar(20) DEFAULT NULL COMMENT '文件扩展名',
  `file_content` longblob DEFAULT NULL COMMENT '文件二进制内容',
  `file_category` varchar(50) DEFAULT NULL COMMENT '文件类别代码',
  `file_category_name` varchar(100) DEFAULT NULL COMMENT '文件类别名称',
  `description` varchar(500) DEFAULT NULL COMMENT '文件描述',
  `version` varchar(20) DEFAULT '1.0' COMMENT '文件版本',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否有效',
  `upload_date` date DEFAULT NULL COMMENT '上传日期',
  `upload_datetime` datetime DEFAULT NULL COMMENT '上传时间',
  `upload_ip` varchar(45) DEFAULT NULL COMMENT '上传者IP',
  `uploaded_by` int(10) unsigned DEFAULT NULL COMMENT '上传者用户ID',
  `uploaded_by_name` varchar(100) DEFAULT NULL COMMENT '上传者用户名',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_rfq_id` (`rfq_id`),
  KEY `idx_file_category` (`file_category`),
  KEY `idx_upload_date` (`upload_date`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `lsb_rfq_files_ibfk_1` FOREIGN KEY (`rfq_id`) REFERENCES `lsb_rfq_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='RFQ文件上传表';

-- ------------------------------------------------------------
-- 10. 备注表
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_remarks`;
CREATE TABLE `lsb_rfq_remarks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL COMMENT '关联RFQ ID',
  `remark_type` enum('note','change') NOT NULL COMMENT '备注类型: note=补充说明, change=变更记录',
  `remark_no` int(11) DEFAULT 1 COMMENT '序号',
  `remark_content` text NOT NULL COMMENT '内容',
  `remark_date` date DEFAULT NULL COMMENT '日期(变更记录用)',
  `created_by` int(10) unsigned DEFAULT NULL COMMENT '创建人ID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rfq_id` (`rfq_id`),
  KEY `idx_type` (`remark_type`),
  CONSTRAINT `lsb_rfq_remarks_ibfk_1` FOREIGN KEY (`rfq_id`) REFERENCES `lsb_rfq_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='RFQ备注表 - 补充说明+变更记录';

-- ------------------------------------------------------------
-- 11. 参数选项表
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_reference`;
CREATE TABLE `lsb_rfq_reference` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL COMMENT '参数类别代码',
  `category_name` varchar(100) NOT NULL COMMENT '参数类别名称(英文)',
  `category_name_cn` varchar(100) DEFAULT NULL COMMENT '参数类别名称(中文)',
  `code` varchar(50) NOT NULL COMMENT '参数值代码',
  `value_cn` varchar(255) NOT NULL COMMENT '参数值(中文)',
  `value_en` varchar(255) DEFAULT NULL COMMENT '参数值(英文)',
  `unit` varchar(20) DEFAULT NULL COMMENT '单位',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序顺序',
  `is_default` tinyint(1) DEFAULT 0 COMMENT '是否默认值',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用',
  `parent_id` int(10) unsigned DEFAULT NULL COMMENT '父级ID(用于级联选项)',
  `extra_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '扩展数据' CHECK (json_valid(`extra_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_category_code` (`category`,`code`),
  KEY `idx_category` (`category`),
  KEY `idx_code` (`code`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='RFQ参数选项表';

-- ------------------------------------------------------------
-- 12. 板材规格明细表 (V3.2新增)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_cladding_spec`;
CREATE TABLE `lsb_rfq_cladding_spec` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL COMMENT 'RFQ ID',
  `system_type` enum('roof','wall','canopy','parapet') NOT NULL COMMENT '系统类型：roof屋面/wall墙面/canopy雨篷/parapet女儿墙',
  `layer_position` enum('outer','liner','core') NOT NULL DEFAULT 'outer' COMMENT '层位置：outer外板/liner内衬板/core芯材',
  `zone_code` varchar(20) DEFAULT NULL COMMENT '区域代码 (如 R1, W1, W2)',
  `zone_name` varchar(100) DEFAULT NULL COMMENT '区域名称',
  `zone_area` decimal(12,2) DEFAULT NULL COMMENT '区域面积 (m²)',
  `panel_profile` varchar(50) DEFAULT NULL COMMENT '板型代码',
  `panel_profile_custom` varchar(100) DEFAULT NULL COMMENT '自定义板型说明',
  `base_material` varchar(50) DEFAULT NULL COMMENT '基材代码',
  `base_material_custom` varchar(100) DEFAULT NULL COMMENT '自定义基材说明',
  `thickness` decimal(5,2) DEFAULT NULL COMMENT '厚度 (mm)',
  `coating_type` varchar(50) DEFAULT NULL COMMENT '涂层类型代码',
  `coating_type_custom` varchar(100) DEFAULT NULL COMMENT '自定义涂层说明',
  `color_code` varchar(50) DEFAULT NULL COMMENT '颜色代码',
  `color_name` varchar(100) DEFAULT NULL COMMENT '颜色名称',
  `brand` varchar(100) DEFAULT NULL COMMENT '品牌',
  `model` varchar(100) DEFAULT NULL COMMENT '型号',
  `insulation_material` varchar(50) DEFAULT NULL COMMENT '保温材料代码',
  `insulation_material_custom` varchar(100) DEFAULT NULL COMMENT '自定义保温材料',
  `insulation_thickness` decimal(6,2) DEFAULT NULL COMMENT '保温厚度 (mm)',
  `insulation_density` decimal(6,2) DEFAULT NULL COMMENT '保温密度 (kg/m³)',
  `r_value` decimal(6,3) DEFAULT NULL COMMENT '热阻值 R-value',
  `fire_rating` varchar(50) DEFAULT NULL COMMENT '防火等级',
  `fm_approved` tinyint(1) DEFAULT NULL COMMENT 'FM认证',
  `remarks` text DEFAULT NULL COMMENT '备注说明',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序顺序',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rfq_id` (`rfq_id`),
  KEY `idx_system_type` (`system_type`),
  KEY `idx_zone_code` (`zone_code`),
  CONSTRAINT `lsb_rfq_cladding_spec_ibfk_1` FOREIGN KEY (`rfq_id`) REFERENCES `lsb_rfq_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='屋墙面板材规格明细表';

-- ------------------------------------------------------------
-- 13. 构造做法表 (V3.2新增)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_cladding_method`;
CREATE TABLE `lsb_rfq_cladding_method` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL COMMENT 'RFQ ID',
  `system_type` enum('roof','wall','canopy','parapet') NOT NULL COMMENT '系统类型',
  `zone_code` varchar(20) DEFAULT NULL COMMENT '区域代码',
  `zone_name` varchar(100) DEFAULT NULL COMMENT '区域名称',
  `method_code` varchar(50) DEFAULT NULL COMMENT '做法代码',
  `method_name` varchar(200) DEFAULT NULL COMMENT '做法名称',
  `method_desc` text DEFAULT NULL COMMENT '做法描述',
  `layer_composition` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '层次构成JSON' CHECK (json_valid(`layer_composition`)),
  `u_value` decimal(6,4) DEFAULT NULL COMMENT '传热系数 U-value (W/m²·K)',
  `acoustic_rating` varchar(50) DEFAULT NULL COMMENT '隔声等级',
  `fire_rating` varchar(50) DEFAULT NULL COMMENT '防火等级',
  `applicable_condition` text DEFAULT NULL COMMENT '适用条件说明',
  `detail_drawing_ref` varchar(200) DEFAULT NULL COMMENT '详图参考编号',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序顺序',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rfq_id` (`rfq_id`),
  KEY `idx_system_type` (`system_type`),
  KEY `idx_method_code` (`method_code`),
  CONSTRAINT `lsb_rfq_cladding_method_ibfk_1` FOREIGN KEY (`rfq_id`) REFERENCES `lsb_rfq_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='屋墙面构造做法表';

-- ------------------------------------------------------------
-- 14. 补充说明表 (V3.2新增)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_supplements`;
CREATE TABLE `lsb_rfq_supplements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL COMMENT 'RFQ ID',
  `category` enum('general','steel','envelope','load','site','schedule','commercial','other') NOT NULL DEFAULT 'general' COMMENT '分类类别',
  `title` varchar(200) DEFAULT NULL COMMENT '标题',
  `content` text NOT NULL COMMENT '说明内容',
  `importance` enum('normal','important','critical') DEFAULT 'normal' COMMENT '重要程度',
  `related_section` varchar(100) DEFAULT NULL COMMENT '关联表单区块',
  `related_field` varchar(100) DEFAULT NULL COMMENT '关联字段',
  `attachment_path` varchar(500) DEFAULT NULL COMMENT '附件路径',
  `attachment_name` varchar(200) DEFAULT NULL COMMENT '附件名称',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序顺序',
  `created_by` varchar(100) DEFAULT NULL COMMENT '创建人',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rfq_id` (`rfq_id`),
  KEY `idx_category` (`category`),
  KEY `idx_importance` (`importance`),
  CONSTRAINT `lsb_rfq_supplements_ibfk_1` FOREIGN KEY (`rfq_id`) REFERENCES `lsb_rfq_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='RFQ补充说明表';

-- ------------------------------------------------------------
-- 15. 变更记录表 (V3.2新增)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lsb_rfq_change_log`;
CREATE TABLE `lsb_rfq_change_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL COMMENT 'RFQ ID',
  `version` int(11) NOT NULL COMMENT '版本号',
  `previous_version` int(11) DEFAULT NULL COMMENT '前一版本号',
  `change_type` enum('create','update','submit','approve','reject','revise','cancel') NOT NULL COMMENT '变更类型',
  `change_summary` varchar(500) DEFAULT NULL COMMENT '变更摘要',
  `changed_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '变更字段详情JSON' CHECK (json_valid(`changed_fields`)),
  `change_reason` text DEFAULT NULL COMMENT '变更原因',
  `changed_by` varchar(100) DEFAULT NULL COMMENT '变更人',
  `changed_by_role` varchar(50) DEFAULT NULL COMMENT '变更人角色',
  `changed_at` datetime DEFAULT current_timestamp() COMMENT '变更时间',
  PRIMARY KEY (`id`),
  KEY `idx_rfq_id` (`rfq_id`),
  KEY `idx_version` (`version`),
  KEY `idx_change_type` (`change_type`),
  KEY `idx_changed_at` (`changed_at`),
  CONSTRAINT `lsb_rfq_change_log_ibfk_1` FOREIGN KEY (`rfq_id`) REFERENCES `lsb_rfq_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='RFQ变更记录表';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- PART 2: 参考数据
-- 参考数据请导入 lsb_rfq_reference_data_final.sql
-- ============================================================

SELECT 'LSB RFQ V3.2 Schema Created Successfully!' AS Message;
SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE 'lsb_rfq_%';
