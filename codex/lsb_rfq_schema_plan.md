# LSB RFQ（RFQ (2025.dec)）表单解析与数据库设计建议

## 表单结构梳理（按版面分区）
- 基本信息：报价项目申请号、项目编号、Liberty 联系电话、项目名称、项目所在位置、单体数量、建筑屋面面积、要求报价提交日期。
- 报价资料（Order Entry）：MBS 结构图纸、建筑蓝图、结构蓝图、AutoCAD 建筑图纸、FM 报告、other（当前示例均为“-”占位）。
- 结构概述：结构形式、天车、夹层、Factory Mutual、大雨蓬、扩建、女儿墙、墙裙、LEED。
- 报价范围（Scope of Work）：主次围材料=“主结构+次结构+围护材料”，楼面板、内隔墙、门窗、面漆、百叶窗、管线吊架、栏杆扶手、玻璃幕墙、铝板、试验（备注：指定检测或实验室费用）。
- 建筑描述（Building Dimensions）：长度、宽度、檐口高度、女儿墙顶标高、女儿墙内衬板、夹层范围、楼面形式、楼面标高（多项标注“MBS Tekla模型”来源）。
- 主次结构材料：主钢架材质 Q355B，原材料厂家“宝钢 BaoSteel or equivalent”，加工厂“美联Suzhou”，底漆“醇酸和丙烯酸”厚度 60μm；中间漆/面漆参数；次结构“公司标准”“同主构底漆”；花纹钢板“刷漆(同主构)”等。
- 屋/墙面做法说明：屋面做法 1（屋面外板+保温棉+屋面内板）、2（铝镁锰屋面）、3（柔性屋面）；墙面做法 1~3；常规材料“美联常规材料”；改造项目（结构加固/围护板加建/利旧/机电安装/其他）；标准规范、备注。
- 排水/开口：屋面排水形式 1（彩钢板天沟外排水）、2（不锈钢天沟内排水）；雨蓬排水；屋面通风器/开口、气楼&天窗；LS585 屋面光伏系统。
- 屋面系统材质要求：屋面外板方案 (0.6mm LSIII 350强度 基色/HDP AM150 宝钢)、保温棉 (100mm 16kg WMP-VR 进口阻燃 普通红棉 欧文斯科宁)、防水透气膜/隔汽膜/钢丝网、屋面内衬板 (0.43 U900 PE AM100 标准色 宝钢)、大雨蓬板、采光 (LSIII 单层 2400gsm 多凯 是否 FM)。
- 墙面系统材质要求：外板/墙裙高度 1.2m 竖铺；外板方案1 (0.53 PBR1026 HDP AM150 标准色 宝钢)、外板2 (75mm 夹芯板)；保温棉1 (75mm 12kg FSK 国产阻燃 黄棉 欧文斯科宁)；防水透气膜/隔汽膜/钢丝网=无；内衬板 (0.43 U900 PE AM100 标准色 宝钢)；女儿墙内衬板；内隔墙板型参数。
- 其他：补充描述、变更记录（序号/变更内容/日期）。

## 数据库表设计（全部以 `lsb_rfq_` 前缀）
- `lbs_rfq`（主表）  
  `id` PK, `rfq_no`, `job_number`, `project_name`, `project_location`, `building_qty`, `floor_area`, `due_date`, `contact_to`, `contact_email`, `contact_person`, `account_manager`, `liberty_phone`, `notes`.
- `lsb_rfq_order_entry`（附件/资料）  
  `id` PK, `rfq_id` FK, `doc_type` (枚举：mbs_structural, architect_dwg, foundation_dwg, autocad_dwg, fm_report, other), `provided` (bool/enum Y/N/-), `file_ref`, `comment`.
- `lsb_rfq_building_feature`（结构特征/合规）  
  `id` PK, `rfq_id` FK, `pre_eng_building` Y/N, `bridge_crane` Y/N, `mezzanine` Y/N, `factory_mutual` Y/N, `loading_canopy` Y/N, `future_expansion` Y/N, `parapet` Y/N, `concrete_wall_curb` Y/N, `leed` Y/N, `erection_scope` text, `other_feature`.
- `lsb_rfq_scope`（报价范围与项目类型）  
  `id` PK, `rfq_id` FK, `main_secondary_cladding` text, `steel_deck` Y/N, `partition_wall` Y/N, `door_window` Y/N, `finish_paint` text, `louver` Y/N, `cable_tray_support` Y/N, `railing` Y/N, `glazing_wall` Y/N, `aluminum_cladding` Y/N, `lab_test` Y/N, `retrofit_strengthen` Y/N, `retrofit_cladding` Y/N, `retrofit_reuse` Y/N, `mep_install` Y/N, `other_scope`, `scope_note`.
- `lsb_rfq_dimensions`（尺寸/层信息）  
  `id` PK, `rfq_id` FK, `length`, `width`, `eave_height`, `parapet_top_elev`, `parapet_liner`, `floor_area`, `floor_type`, `floor_elevation`, `mezzanine_area`, `measurement_source` (e.g., tekla_model), `dimension_note`.
- `lsb_rfq_primary_steel`  
  `id` PK, `rfq_id` FK, `steel_grade`, `plate_supplier`, `fabrication_plant`, `primer`, `primer_thk`, `intermediate_coat`, `intermediate_thk`, `topcoat`, `topcoat_thk`, `paint_scope`, `exposed_paint`, `topcoat_method`, `note`.
- `lsb_rfq_secondary_steel`  
  `id` PK, `rfq_id` FK, `material_supplier`, `roof_purlin_galvanized` Y/N, `roof_purlin_paint`, `wall_purlin_galvanized` Y/N, `wall_purlin_paint`, `checker_plate_finish`, `checker_plate_scope`, `note`.
- `lsb_rfq_envelope_roof`（屋面系统主表）  
  `id` PK, `rfq_id` FK, `roof_desc1`, `roof_desc2`, `roof_desc3`, `roof_material_generic`, `membrane_spec`, `vapor_barrier_spec`, `wire_mesh_spec`, `ventilator_need` Y/N, `roof_opening_need` Y/N, `skylight_need` Y/N, `pv_need` Y/N, `note`.
- `lsb_rfq_roof_panel`（多方案子表，序号体现 1/2/3）  
  `id` PK, `rfq_id` FK, `seq_no`, `role` (outer, inner, canopy_top, canopy_bottom), `thickness`, `profile`, `strength`, `coating`, `plating`, `color`, `origin`, `scope`.
- `lsb_rfq_roof_insulation`  
  `id` PK, `rfq_id` FK, `seq_no`, `thickness`, `density`, `facing`, `flame_rating`, `color`, `brand`, `other_req`.
- `lsb_rfq_roof_drainage`  
  `id` PK, `rfq_id` FK, `drain_type` (e.g., 彩钢板天沟外排水/不锈钢天沟内排水/雨蓬排水), `area_scope`, `gutter_type`, `downspout`, `note`.
- `lsb_rfq_skylight`  
  `id` PK, `rfq_id` FK, `layout_rule`, `material_desc`, `statistics_rule`, `fm_required` Y/N, `brand`, `length`, `other_req`.
- `lsb_rfq_envelope_wall`（墙面系统主表）  
  `id` PK, `rfq_id` FK, `wall_desc1`, `wall_desc2`, `wall_desc3`, `skirt_height_outer`, `skirt_height_inner`, `panel_layout_outer`, `panel_layout_inner`, `parapet_liner`, `partition_panel`, `note`.
- `lsb_rfq_wall_panel`（子表，外板/内板/女儿墙/内隔墙）  
  `id` PK, `rfq_id` FK, `seq_no`, `role` (outer, inner, parapet, partition), `thickness`, `profile`, `strength`, `coating`, `plating`, `color`, `origin`, `scope`.
- `lsb_rfq_wall_insulation`  
  `id` PK, `rfq_id` FK, `seq_no`, `thickness`, `density`, `facing`, `flame_rating`, `color`, `brand`, `other_req`.
- `lsb_rfq_change_log`  
  `id` PK, `rfq_id` FK, `change_seq`, `change_desc`, `change_date`.

## 参数表 `lsb_rfq_reference`（保存选项/默认值）
- 结构：`id` PK, `category` (如 doc_type/feature/scope/material/profile/coating/drainage/brand/color/standard), `item_key`, `label_cn`, `label_en`, `value` (可存默认值或选项值), `unit`, `value_type` (enum: text/number/bool), `source` (sheet/用户), `sort_order`, `notes`.
- 用法示例（可作为初始参数集）：
  - `doc_type`: MBS结构图纸, 建筑蓝图, 结构蓝图, AutoCAD建筑图纸, FM报告, other。
  - `feature`: Pre Eng Building, Brdige Crane, Mezzanine Steels, Factory Mutual, Loading Canopy, Building Future Expansion, Parapet, Concrete wall curb, LEED。
  - `scope_item`: 主结构+次结构+围护材料, 楼面板, 内隔墙, 门窗, 面漆, 百叶窗, 管线吊架, 栏杆扶手, 玻璃幕墙, 铝板, 试验。
  - `steel_grade`: Q355B；`plate_supplier`: 宝钢 BaoSteel or equivalent；`fabrication_plant`: 美联Suzhou。
  - `coating`: 醇酸和丙烯酸, HDP, PE；`plating`: AM150(含配件), AM100(含配件)。
  - `panel_profile`: LSIII, U900内板, PBR1026, 夹芯板, 同屋面外板, 同墙面外板。
  - `color`: 基色, 标准色, 普通红棉/黄棉（保温棉色）；`brand`: 欧文斯科宁, 多凯, 宝钢。
  - `drainage_type`: 彩钢板天沟外排水, 不锈钢天沟内排水, 不锈钢天沟外排水, 雨蓬排水做法(不锈钢天沟外排水)。
  - `insulation`: WMP-VR (贴面), 进口阻燃/国产阻燃, 厚度 100/75 mm, 容重 16/12 kg/m³。
  - `standard`: 《建筑与市政工程防水通用规范》GB55030-2022, 公司标准彩钢板天沟, 统计原则=按图统计/按建筑图纸布置。

## 备注与后续
- 以上表拆分覆盖了表单中的层次（主表/资料/特征/范围/尺寸/主次材/屋墙系统/排水/变更），避免单表过宽且便于多方案（seq_no 子表）存储。
- 参数表可支撑下拉选项/默认值配置；实际导入时可将表单出现的文本先入 `lsb_rfq_reference`，再在业务表以外键或代码引用。
