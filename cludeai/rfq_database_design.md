# LSB RFQ 数据库设计方案

## 一、Excel表格结构分析

### 表格概览
- 表名: RFQ (2025.dec)
- 用途: INAVA-USAS 报价申请表 (Request for Quotation)
- 行数: 100行
- 列数: 9列

### 表格内容区域划分

| 区域 | 行范围 | 内容描述 |
|------|--------|----------|
| 表头 | 0-4 | 表单标题、收件人信息 |
| 基本信息 | 5-9 | RFQ编号、项目信息、联系方式 |
| 报价资料 | 10-12 | 图纸和资料类型选择 |
| 结构概述 | 13-15 | 建筑结构形式选项 |
| 安装 | 16 | 安装信息(N/A) |
| 报价范围 | 17-20 | 工作范围选项 |
| 建筑描述 | 21-23 | 尺寸、标高信息 |
| 主次结构材料 | 24-32 | 钢材、油漆、次结构信息 |
| 屋墙面做法 | 33-49 | 屋面墙面施工方式、排水、通风等 |
| 屋面系统材质 | 50-75 | 屋面板材、保温棉、采光板等详细参数 |
| 墙面系统材质 | 76-92 | 墙面板材、保温棉等详细参数 |
| 其他 | 93-99 | 补充说明和变更记录 |

---

## 二、数据库表设计建议

根据表格结构，建议拆分为以下数据表：

### 1. 主表: `lsb_rfq_main` - RFQ主信息表
存储每个RFQ的基本信息

### 2. 子表: `lsb_rfq_documents` - 报价资料表
存储提交的图纸和资料信息

### 3. 子表: `lsb_rfq_building` - 建筑结构表
存储建筑结构特征选项

### 4. 子表: `lsb_rfq_scope` - 报价范围表
存储工作范围选项

### 5. 子表: `lsb_rfq_dimensions` - 建筑尺寸表
存储建筑尺寸信息

### 6. 子表: `lsb_rfq_primary_steel` - 主结构材料表
存储主钢架材料信息

### 7. 子表: `lsb_rfq_secondary_steel` - 次结构材料表
存储次结构材料信息

### 8. 子表: `lsb_rfq_roof_system` - 屋面系统表
存储屋面做法和材料

### 9. 子表: `lsb_rfq_wall_system` - 墙面系统表
存储墙面做法和材料

### 10. 子表: `lsb_rfq_drainage` - 排水系统表
存储排水做法

### 11. 子表: `lsb_rfq_changes` - 变更记录表
存储报价变更信息

### 12. 参数表: `lsb_rfq_reference` - 参数选项表
存储所有下拉选项和参考值

---

## 三、参数选项提取

### 3.1 材质等级 (steel_grade)
- Q355B

### 3.2 原材料厂家 (steel_manufacturer)
- 宝钢 BaoSteel or equivalent
- 公司标准

### 3.3 加工厂 (processing_plant)
- 美联Suzhou

### 3.4 底漆类型 (primer_type)
- 醇酸和丙烯酸

### 3.5 底漆厚度 (primer_thickness)
- 60 (μm)

### 3.6 面漆涂刷方式 (painting_method)
- 工厂涂刷

### 3.7 檩条镀锌选项 (purlin_galvanized)
- 是
- 否

### 3.8 檩条油漆 (purlin_paint)
- 同主构底漆

### 3.9 屋面做法 (roof_method)
- 屋面外板+保温棉+屋面内板
- 铝镁锰屋面
- 柔性屋面

### 3.10 墙面材料选择 (wall_material)
- 美联常规材料

### 3.11 改造项目选项 (renovation_type)
- 结构加固
- 围护板加建
- 利旧
- 机电安装
- 其他

### 3.12 防水规范 (waterproof_standard)
- 《建筑与市政工程防水通用规范》GB55030-2022
- 考虑/不考虑

### 3.13 屋面排水形式 (roof_drainage_type)
- 彩钢板天沟外排水
- 不锈钢天沟内排水

### 3.14 天沟材料 (gutter_material)
- 公司标准彩钢板天沟
- 1.2mm厚不锈钢展宽1219mm
- 1.0mm厚不锈钢展宽1000mm

### 3.15 落水管类型 (downpipe_type)
- 彩钢板落水管
- UPVC160

### 3.16 板材厚度 (panel_thickness)
- 0.43
- 0.53
- 0.6
- 75 (夹芯板)

### 3.17 板型 (panel_profile)
- LSIII
- PBR1026
- U900内板
- 夹芯板

### 3.18 强度等级 (panel_strength)
- 350强度

### 3.19 涂层类型 (coating_type)
- 基色
- HDP
- PE

### 3.20 镀层类型 (galvanizing_type)
- AM100(含配件)
- AM150(含配件)

### 3.21 板材颜色 (panel_color)
- 标准色
- 0 (无/基色)

### 3.22 板材产地 (panel_origin)
- 宝钢

### 3.23 保温棉厚度 (insulation_thickness)
- 75
- 100

### 3.24 保温棉容重 (insulation_density)
- 12
- 16

### 3.25 保温棉贴面 (insulation_facing)
- WMP-VR
- FSK

### 3.26 阻燃说明 (flame_retardant)
- 进口阻燃
- 国产阻燃

### 3.27 保温棉颜色 (insulation_color)
- 普通红棉
- 黄棉

### 3.28 保温棉品牌 (insulation_brand)
- 欧文斯科宁

### 3.29 内衬板布置方式 (liner_layout)
- 檩下布置

### 3.30 小雨蓬悬挑宽度 (canopy_width)
- 1000

### 3.31 小雨蓬做法 (canopy_method)
- 标准做法(双板同外墙)

### 3.32 小雨蓬排水做法 (canopy_drainage)
- 自由排水

### 3.33 采光板铺设方式 (skylight_layout)
- 按建筑图纸布置

### 3.34 采光板材料 (skylight_material)
- LSIII单层 2400gsm

### 3.35 采光板统计原则 (skylight_calculation)
- 按图统计

### 3.36 采光板FM认证 (skylight_fm)
- 是
- 否

### 3.37 采光板品牌 (skylight_brand)
- 多凯

### 3.38 采光板长度 (skylight_length)
- 4.5m

### 3.39 墙面板铺设方式 (wall_panel_layout)
- 竖铺

### 3.40 墙裙高度 (wall_curb_height)
- 1.2

### 3.41 钢丝网选项 (wire_mesh)
- 报价
- 现场编织
- 无

### 3.42 报价资料类型 (document_type)
- MBS结构图纸
- 建筑蓝图 Architect Drawing
- 结构蓝图 Foundation Design
- AutoCAD 建筑图纸
- FM报告
- other

### 3.43 建筑结构选项 (building_structure)
- 结构形式 Pre Eng Building
- 天车 Bridge Crane
- 夹层 Mezzanine Steels
- Factory Mutual
- 大雨蓬 Loading Canopy
- 扩建 Building Future Expansion
- 女儿墙 Parapet
- 墙裙 Concrete wall curb
- LEED

### 3.44 报价范围选项 (scope_options)
- 主结构+次结构+围护材料
- 楼面板 Steel Deck
- 内隔墙 Partition Wall Frame
- 门窗 Man Door & Window
- 面漆
- 百叶窗 Louver
- 管线吊架 Cable Tray support
- 栏杆扶手 Railing
- 玻璃幕墙 Glazing Curtain Wall
- 铝板 Aluminum cladding
- 试验 Laboratory & Inspect

---

## 四、详细表结构设计

详见 `rfq_database_tables.sql` 文件

