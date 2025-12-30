# LSB RFQ 数据库设计方案 V2.0 (精简版)

## 版本说明
- V1.0: 22张表，完全范式化设计
- **V2.0: 10张表，合理合并，便于开发维护**

---

## 一、表结构总览

| 序号 | 表名 | 说明 | 关系 | 字段数 |
|------|------|------|------|--------|
| 1 | `lsb_rfq_reference` | 参数选项表 | - | 14 |
| 2 | `lsb_rfq_main` | 主表(基本信息+结构+范围) | 主表 | ~35 |
| 3 | `lsb_rfq_steel` | 钢结构表(尺寸+主结构+次结构) | 1:1 | ~35 |
| 4 | `lsb_rfq_envelope` | 围护系统表(屋面+墙面+雨蓬+采光) | 1:1 | ~45 |
| 5 | `lsb_rfq_panel` | 板材表(屋面+墙面) | 1:N | 13 |
| 6 | `lsb_rfq_insulation` | 保温棉表(屋面+墙面) | 1:N | 11 |
| 7 | `lsb_rfq_method` | 做法表(屋面+墙面) | 1:N | 7 |
| 8 | `lsb_rfq_drainage` | 排水系统表 | 1:N | 8 |
| 9 | `lsb_rfq_remarks` | 备注表(变更+说明) | 1:N | 8 |
| 10 | `lsb_rfq_files` | 文件上传表 | 1:N | 18 |

---

## 二、表关系图

```
lsb_rfq_reference (参数表 - 独立)

lsb_rfq_main (主表)
    │
    ├── 1:1 ── lsb_rfq_steel (钢结构)
    │
    ├── 1:1 ── lsb_rfq_envelope (围护系统)
    │
    ├── 1:N ── lsb_rfq_panel (板材)
    │              └── panel_category: roof / wall
    │
    ├── 1:N ── lsb_rfq_insulation (保温棉)
    │              └── insulation_category: roof / wall
    │
    ├── 1:N ── lsb_rfq_method (做法)
    │              └── method_category: roof / wall
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

## 三、各表详细说明

### 3.1 lsb_rfq_main (主表)

**合并内容：**
- 原 `lsb_rfq_main` 基本信息
- 原 `lsb_rfq_building` 建筑结构特征
- 原 `lsb_rfq_scope` 报价范围

**主要字段分组：**
| 分组 | 字段示例 |
|------|----------|
| 基本信息 | rfq_no, job_number, project_name, due_date |
| 建筑结构 | pre_eng_building, bridge_crane, mezzanine_steels |
| 报价范围 | steel_deck, door_window, glazing_curtain_wall |

---

### 3.2 lsb_rfq_steel (钢结构表)

**合并内容：**
- 原 `lsb_rfq_dimensions` 建筑尺寸
- 原 `lsb_rfq_primary_steel` 主结构材料
- 原 `lsb_rfq_secondary_steel` 次结构材料

**主要字段分组：**
| 分组 | 字段示例 |
|------|----------|
| 建筑尺寸 | length, width, eave_height, floor_elevation |
| 主结构 | steel_grade, steel_manufacturer, primer_type |
| 次结构 | roof_purlin_galvanized, wall_purlin_paint |

---

### 3.3 lsb_rfq_envelope (围护系统表)

**合并内容：**
- 原 `lsb_rfq_roof_config` 屋面配置
- 原 `lsb_rfq_wall_config` 墙面配置
- 原 `lsb_rfq_small_canopy` 小雨蓬配置
- 原 `lsb_rfq_skylight` 采光板配置

**主要字段分组：**
| 分组 | 字段示例 |
|------|----------|
| 总体配置 | wall_material, is_renovation |
| 屋面特殊 | aclok_roof, roof_ventilator, pv_system |
| 墙面配置 | wall_outer_layout, wall_liner_curb_height |
| 小雨蓬 | small_canopy_width, small_canopy_method |
| 采光板 | skylight_layout, skylight_brand |

---

### 3.4 lsb_rfq_panel (板材表)

**合并内容：**
- 原 `lsb_rfq_roof_panel` 屋面板材
- 原 `lsb_rfq_wall_panel` 墙面板材

**区分字段：**
- `panel_category`: `roof` / `wall`
- `panel_type`: `outer` / `liner` / `canopy_upper` / `parapet_liner` 等

---

### 3.5 lsb_rfq_insulation (保温棉表)

**合并内容：**
- 原 `lsb_rfq_roof_insulation`
- 原 `lsb_rfq_wall_insulation`

**区分字段：**
- `insulation_category`: `roof` / `wall`

---

### 3.6 lsb_rfq_method (做法表)

**合并内容：**
- 原 `lsb_rfq_roof_method`
- 原 `lsb_rfq_wall_method`

**区分字段：**
- `method_category`: `roof` / `wall`

---

### 3.7 lsb_rfq_remarks (备注表)

**合并内容：**
- 原 `lsb_rfq_notes` 补充说明
- 原 `lsb_rfq_changes` 变更记录

**区分字段：**
- `remark_type`: `note` / `change`

---

## 四、与 V1.0 对比

| 对比项 | V1.0 | V2.0 |
|--------|------|------|
| 表数量 | 22张 | 10张 |
| 1:1关系表 | 9张独立表 | 合并为3张 |
| 相似结构表 | 分开存储 | 用type字段区分 |
| 开发复杂度 | 较高 | 较低 |
| 查询性能 | 需要多表JOIN | JOIN次数减少 |
| 范式程度 | 完全范式化 | 适度反范式 |

---

## 五、ScriptCase 开发建议

### 表单开发顺序：

1. **Grid + Form: lsb_rfq_main**
   - 主列表页 + 主表单
   - 包含基本信息、结构特征、报价范围

2. **Master-Detail: main → steel**
   - 嵌入式表单或Tab页
   - 钢结构信息

3. **Master-Detail: main → envelope**
   - 嵌入式表单或Tab页
   - 围护系统配置

4. **Multi-Record: panel / insulation / method**
   - 动态添加多条记录
   - 使用category字段过滤

5. **Multi-Upload: files**
   - 文件上传功能
   - Document (File Name) 模式

---

## 六、文件清单

| 文件 | 说明 |
|------|------|
| `rfq_database_v2.sql` | 10张表的DDL语句 |
| `rfq_reference_data.sql` | 参数表初始化数据(通用) |
| `rfq_database_tables.sql` | V1.0原版22张表(保留) |

---

## 七、下一步

1. 执行 `rfq_database_v2.sql` 创建表结构
2. 执行 `rfq_reference_data.sql` 初始化参数
3. 在 ScriptCase 中创建应用
