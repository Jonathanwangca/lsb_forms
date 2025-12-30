# LSB RFQ 数据库设计方案 V3.0 (完整版)

## 版本说明
- V1.0: 22张表，完全范式化设计
- V2.0: 10张表，合理合并，便于开发维护
- **V3.0: 11张表，补充所有缺失内容**

---

## 更新内容 (V2.0 → V3.0)

| 序号 | 更新项 | 说明 |
|------|--------|------|
| 1 | 新增 `lsb_rfq_order_entry` 表 | 报价资料 Order Entry，用户勾选后需上传相应文件 |
| 2 | 主表新增 `erection` 字段 | 安装 Erection (N/A表示不适用) |
| 3 | 完善 Scope of Work 选项 | 8种报价范围组合选项 |
| 4 | 补充 Building Dimensions 缺失字段 | 夹层范围、女儿墙内衬板 |
| 5 | 补充主结构材料字段 | Note其他要求、中间漆+面漆、外露构件油漆、防火涂料 |
| 6 | 补充材质选项 | Q235B~Q460C完整系列 |
| 7 | 补充底漆选项 | 醇酸、环氧、环氧富锌、无机富锌、聚氨酯等 |
| 8 | 新增次结构原材料厂家 | secondary_manufacturer 字段 |
| 9 | 完善屋墙面做法说明表 | 增加 method_detail 详细说明字段 |
| 10 | 参数表新增40个类别 | 约200+选项 |

---

## 一、表结构总览

| 序号 | 表名 | 说明 | 关系 | 字段数 |
|------|------|------|------|--------|
| 1 | `lsb_rfq_reference` | 参数选项表 | - | 15 |
| 2 | `lsb_rfq_main` | 主表(基本信息+结构+范围+安装) | 主表 | ~40 |
| 3 | `lsb_rfq_order_entry` | 报价资料表 (新增) | 1:1 | 15 |
| 4 | `lsb_rfq_steel` | 钢结构表(尺寸+主结构+次结构) | 1:1 | ~45 |
| 5 | `lsb_rfq_envelope` | 围护系统表(屋面+墙面+雨蓬+采光) | 1:1 | ~50 |
| 6 | `lsb_rfq_method` | 做法表(屋面+墙面) | 1:N | 8 |
| 7 | `lsb_rfq_panel` | 板材表(屋面+墙面) | 1:N | 14 |
| 8 | `lsb_rfq_insulation` | 保温棉表(屋面+墙面) | 1:N | 11 |
| 9 | `lsb_rfq_drainage` | 排水系统表 | 1:N | 9 |
| 10 | `lsb_rfq_remarks` | 备注表(变更+说明) | 1:N | 8 |
| 11 | `lsb_rfq_files` | 文件上传表 | 1:N | 18 |

---

## 二、表关系图

```
lsb_rfq_reference (参数表 - 独立)

lsb_rfq_main (主表)
    │
    ├── 1:1 ── lsb_rfq_order_entry (报价资料) ★新增
    │              └── 关联文件上传
    │
    ├── 1:1 ── lsb_rfq_steel (钢结构)
    │              └── 主结构+次结构+建筑尺寸
    │
    ├── 1:1 ── lsb_rfq_envelope (围护系统)
    │              └── 屋面+墙面+雨蓬+采光
    │
    ├── 1:N ── lsb_rfq_method (做法说明) ★完善
    │              └── method_category: roof / wall
    │              └── method_no: 1, 2, 3
    │
    ├── 1:N ── lsb_rfq_panel (板材)
    │              └── panel_category: roof / wall
    │
    ├── 1:N ── lsb_rfq_insulation (保温棉)
    │              └── insulation_category: roof / wall
    │
    ├── 1:N ── lsb_rfq_drainage (排水)
    │              └── drainage_type: roof_1 / roof_2 / canopy
    │
    ├── 1:N ── lsb_rfq_remarks (备注)
    │              └── remark_type: note / change
    │
    └── 1:N ── lsb_rfq_files (文件)
```

---

## 三、新增/修改表详细说明

### 3.1 lsb_rfq_main (主表) - 修改

**新增字段：**
| 字段 | 类型 | 说明 |
|------|------|------|
| `erection` | TINYINT(1) | 安装 Erection (0=N/A, 1=需要) |
| `erection_remarks` | TEXT | 安装备注 |
| `scope_type` | VARCHAR(50) | 报价范围类型代码 |

---

### 3.2 lsb_rfq_order_entry (报价资料表) - 新增

**对应 Excel 位置：** Row 10-11 "报价资料 Order Entry"

| 字段 | 类型 | 说明 |
|------|------|------|
| `mbs_drawing` | TINYINT(1) | MBS结构图纸 |
| `mbs_drawing_file_id` | INT | 关联文件ID |
| `architect_drawing` | TINYINT(1) | 建筑蓝图 Architect Drawing |
| `architect_drawing_file_id` | INT | 关联文件ID |
| `foundation_design` | TINYINT(1) | 结构蓝图 Foundation Design |
| `foundation_design_file_id` | INT | 关联文件ID |
| `autocad_drawing` | TINYINT(1) | AutoCAD 建筑图纸 |
| `autocad_drawing_file_id` | INT | 关联文件ID |
| `fm_report` | TINYINT(1) | FM报告 |
| `fm_report_file_id` | INT | 关联文件ID |
| `other_docs` | TINYINT(1) | 其他文件 |
| `other_docs_file_id` | INT | 关联文件ID |
| `other_docs_desc` | VARCHAR(255) | 其他文件描述 |

---

### 3.3 lsb_rfq_steel (钢结构表) - 完善

**新增/完善字段：**

**建筑尺寸部分：**
| 字段 | 类型 | 说明 |
|------|------|------|
| `parapet_wall_liner` | VARCHAR(100) | 女儿墙内衬板 |
| `mezzanine_floor_area` | VARCHAR(255) | 夹层范围 Floor areas |

**主结构部分：**
| 字段 | 类型 | 说明 |
|------|------|------|
| `primary_steel_note` | TEXT | 主钢架其他要求 Note |
| `intermediate_coat` | VARCHAR(100) | 中间漆 |
| `intermediate_thickness` | INT | 中间漆厚度(μm) |
| `top_coat_paint` | VARCHAR(100) | 面漆 |
| `top_coat_thickness` | INT | 面漆厚度(μm) |
| `coating_scope` | TEXT | 涂刷范围 |
| `exposed_paint` | VARCHAR(100) | 外露构件油漆 |
| `exposed_paint_scope` | TEXT | 外露构件油漆范围 |
| `fire_coating` | VARCHAR(100) | 防火涂料类型 |
| `fire_coating_scope` | TEXT | 防火涂料范围 |
| `fire_coating_na` | TINYINT(1) | 防火涂料是否N/A |

**次结构部分：**
| 字段 | 类型 | 说明 |
|------|------|------|
| `secondary_manufacturer` | VARCHAR(100) | 次结构原材料厂家代码 |

---

### 3.4 lsb_rfq_method (做法说明表) - 完善

**对应 Excel 位置：** Row 33-38 "屋墙面做法说明"

| 字段 | 类型 | 说明 |
|------|------|------|
| `method_category` | ENUM | roof=屋面做法, wall=墙面做法 |
| `method_no` | TINYINT | 做法序号(1,2,3) |
| `method_desc` | VARCHAR(255) | 做法描述(如"屋面外板+保温棉+屋面内板") |
| `method_detail` | TEXT | 做法详细说明 |
| `scope` | TEXT | 适用范围 |

**屋面做法示例：**
- 屋面做法描述1：屋面外板+保温棉+屋面内板
- 屋面做法描述2：铝镁锰屋面
- 屋面做法描述3：柔性屋面

**墙面做法示例：**
- 墙面做法1：墙面外板+保温棉+墙面内板
- 墙面做法2：夹芯板墙面
- 墙面做法3：(自定义)

---

## 四、参数表选项 (完善版)

### 4.1 报价范围 scope_of_work

| 代码 | 中文 | 英文 |
|------|------|------|
| MAIN_MATERIAL | 主结构+次结构+围护材料 | Primary + Secondary + Cladding |
| MAIN_SECONDARY | 主结构+次结构 | Primary + Secondary Steel |
| MAIN_ONLY | 仅主结构 | Primary Steel Only |
| SECONDARY_ONLY | 仅次结构 | Secondary Steel Only |
| CLADDING_ONLY | 仅围护材料 | Cladding Only |
| MAIN_CLADDING | 主结构+围护材料 | Primary + Cladding |
| SECONDARY_CLADDING | 次结构+围护材料 | Secondary + Cladding |
| FULL_PACKAGE | 全包(含安装) | Full Package (incl. Erection) |

### 4.2 材质等级 steel_grade

| 代码 | 说明 |
|------|------|
| Q235B | 普通碳素结构钢 |
| Q345B | (旧标准，等同Q355B) |
| Q355B | 低合金高强度结构钢 (默认) |
| Q355C | 低合金高强度结构钢 (冲击韧性) |
| Q355D | 低合金高强度结构钢 (低温冲击) |
| Q390B | 高强度结构钢 |
| Q420B | 高强度结构钢 |
| Q420C | 高强度结构钢 (冲击韧性) |
| Q460B | 高强度结构钢 |
| Q460C | 高强度结构钢 (冲击韧性) |

### 4.3 底漆类型 primer_type

| 代码 | 中文 | 英文 |
|------|------|------|
| ALKYD_ACRYLIC | 醇酸和丙烯酸 | Alkyd & Acrylic |
| ALKYD | 醇酸底漆 | Alkyd Primer |
| EPOXY | 环氧底漆 | Epoxy Primer |
| EPOXY_ZINC | 环氧富锌底漆 | Epoxy Zinc-rich Primer |
| INORGANIC_ZINC | 无机富锌底漆 | Inorganic Zinc-rich Primer |
| POLYURETHANE | 聚氨酯底漆 | Polyurethane Primer |
| ACRYLIC | 丙烯酸底漆 | Acrylic Primer |
| SHOP_PRIMER | 车间底漆 | Shop Primer |

### 4.4 中间漆类型 intermediate_coat

| 代码 | 中文 | 英文 |
|------|------|------|
| NONE | 无 | None |
| EPOXY | 环氧中间漆 | Epoxy Intermediate |
| EPOXY_MICA | 环氧云铁中间漆 | Epoxy MIO Intermediate |
| POLYURETHANE | 聚氨酯中间漆 | Polyurethane Intermediate |
| ALKYD | 醇酸中间漆 | Alkyd Intermediate |

### 4.5 面漆类型 top_coat

| 代码 | 中文 | 英文 |
|------|------|------|
| NONE | 无 | None |
| ALKYD | 醇酸面漆 | Alkyd Topcoat |
| ACRYLIC | 丙烯酸面漆 | Acrylic Topcoat |
| POLYURETHANE | 聚氨酯面漆 | Polyurethane Topcoat |
| FLUOROCARBON | 氟碳面漆 | Fluorocarbon Topcoat |
| EPOXY | 环氧面漆 | Epoxy Topcoat |

### 4.6 防火涂料 fire_coating

| 代码 | 中文 | 英文 |
|------|------|------|
| NA | 不适用 N/A | N/A |
| THIN_INTUMESCENT | 薄型膨胀型 | Thin Intumescent |
| THICK_INTUMESCENT | 厚型膨胀型 | Thick Intumescent |
| ULTRA_THIN | 超薄型 | Ultra-thin |
| CEMENTITE | 水泥基 | Cementite |

### 4.7 次结构原材料厂家 secondary_manufacturer

| 代码 | 中文 | 英文 |
|------|------|------|
| COMPANY_STD | 公司标准 | Company Standard |
| BAOSTEEL | 宝钢 | BaoSteel |
| SHOUGANG | 首钢 | Shougang |
| MAGANG | 马钢 | Magang |
| DOMESTIC | 国内一线品牌 | Domestic First-tier |

---

## 五、文件清单

| 文件 | 说明 |
|------|------|
| `rfq_database_v3.sql` | 11张表的DDL语句 (完整版) |
| `rfq_reference_data_v3.sql` | 参数表完整数据 (40类别, 200+选项) |
| `rfq_database_design_v3.md` | 本设计文档 |

---

## 六、与 Excel 对应关系

| Excel 区域 | 数据库表 | 说明 |
|------------|----------|------|
| Row 5-8: 基本信息 | lsb_rfq_main | 基本字段 |
| Row 10-11: 报价资料 Order Entry | lsb_rfq_order_entry | ★新增 |
| Row 13-15: 结构概述 Building | lsb_rfq_main | 建筑结构特征 |
| Row 16: 安装 Erection | lsb_rfq_main.erection | ★新增 |
| Row 17-20: 报价范围 Scope of Work | lsb_rfq_main | 报价范围 |
| Row 21-23: 建筑描述 Building Dimensions | lsb_rfq_steel | 建筑尺寸 |
| Row 24-29: 主钢架 Primary Steels | lsb_rfq_steel | 主结构材料 |
| Row 30-32: 次结构 Secondary Steels | lsb_rfq_steel | 次结构材料 |
| Row 33-38: 屋墙面做法说明 | lsb_rfq_method | ★完善 |
| Row 39-49: 屋面系统配置 | lsb_rfq_envelope | 围护系统 |
| Row 50-75: 屋面系统材质要求 | lsb_rfq_panel + lsb_rfq_insulation | 板材+保温棉 |
| Row 76-92: 墙面系统材质要求 | lsb_rfq_panel + lsb_rfq_insulation | 板材+保温棉 |
| Row 44-46: 排水系统 | lsb_rfq_drainage | 排水 |
| Row 93-99: 其他补充/变更 | lsb_rfq_remarks | 备注 |

---

## 七、下一步

1. 执行 `rfq_database_v3.sql` 创建表结构
2. 执行 `rfq_reference_data_v3.sql` 初始化参数
3. 更新 PHP 表单和 API 以支持新字段
4. 测试完整流程
