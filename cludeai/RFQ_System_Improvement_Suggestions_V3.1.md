# LSB RFQ System 改进建议书
## V3.1 版本评审与未来发展规划

**文档版本**: 1.0
**编制日期**: 2025-12-27
**适用系统**: LSB RFQ System V3.1
**文档用途**: 团队会议讨论材料

---

## 目录

1. [当前系统评估](#1-当前系统评估)
2. [功能性改进建议](#2-功能性改进建议)
3. [技术架构优化](#3-技术架构优化)
4. [用户体验提升](#4-用户体验提升)
5. [业务流程改进](#5-业务流程改进)
6. [未来发展路线图](#6-未来发展路线图)
7. [优先级与实施计划](#7-优先级与实施计划)

---

## 1. 当前系统评估

### 1.1 系统优势

| 方面 | 现状 | 评价 |
|------|------|------|
| **数据结构** | 采用1:1和1:N关系的规范化设计 | ✅ 优秀 |
| **参数管理** | 统一参考数据表管理下拉选项 | ✅ 优秀 |
| **双语支持** | 中英文标签完整覆盖 | ✅ 优秀 |
| **表单分组** | 按业务逻辑清晰分类 | ✅ 良好 |
| **状态流转** | 完整的RFQ生命周期管理 | ✅ 良好 |

### 1.2 覆盖范围统计

- **主表字段**: 30+ 个核心信息字段
- **子表数量**: 10个专业数据表
- **参数类别**: 47个下拉选项类别
- **参数选项**: 250+ 个可选值

---

## 2. 功能性改进建议

### 2.1 结构荷载信息 [高优先级]

**现状问题**: 当前系统缺少完整的荷载定义，这对报价准确性影响重大。

**建议新增表**: `lsb_rfq_loads`

```sql
CREATE TABLE lsb_rfq_loads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rfq_id INT NOT NULL,
    -- 恒荷载
    roof_dead_load DECIMAL(10,2) COMMENT '屋面恒荷载 kN/m²',
    mezzanine_dead_load DECIMAL(10,2) COMMENT '夹层恒荷载 kN/m²',
    -- 活荷载
    roof_live_load DECIMAL(10,2) COMMENT '屋面活荷载 kN/m²',
    mezzanine_live_load DECIMAL(10,2) COMMENT '夹层活荷载 kN/m²',
    -- 风荷载
    basic_wind_speed DECIMAL(10,2) COMMENT '基本风速 m/s',
    wind_pressure DECIMAL(10,2) COMMENT '风压 kN/m²',
    terrain_category VARCHAR(20) COMMENT '地面粗糙度类别',
    -- 雪荷载
    ground_snow_load DECIMAL(10,2) COMMENT '地面雪荷载 kN/m²',
    roof_snow_load DECIMAL(10,2) COMMENT '屋面雪荷载 kN/m²',
    -- 地震参数
    seismic_zone VARCHAR(20) COMMENT '抗震设防烈度',
    seismic_group VARCHAR(20) COMMENT '设计地震分组',
    site_category VARCHAR(20) COMMENT '场地类别',
    -- 特殊荷载
    collateral_load DECIMAL(10,2) COMMENT '附加恒荷载 kN/m²',
    collateral_description TEXT COMMENT '附加荷载说明',
    FOREIGN KEY (rfq_id) REFERENCES lsb_rfq_main(id)
);
```

**业务价值**:
- 准确计算钢结构用量
- 避免因荷载遗漏导致的报价偏差
- 符合国家规范要求

### 2.2 吊车详细信息 [高优先级]

**现状问题**: 主表仅有布尔字段标识是否有吊车，缺少详细参数。

**建议新增表**: `lsb_rfq_crane`

```sql
CREATE TABLE lsb_rfq_crane (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rfq_id INT NOT NULL,
    crane_no INT DEFAULT 1 COMMENT '吊车编号',
    -- 基本参数
    crane_type VARCHAR(50) COMMENT '吊车类型: 桥式/悬挂/单梁/双梁',
    capacity DECIMAL(10,2) COMMENT '起重量 t',
    span DECIMAL(10,2) COMMENT '跨度 m',
    lift_height DECIMAL(10,2) COMMENT '起升高度 m',
    -- 运行参数
    crane_class VARCHAR(20) COMMENT '工作级别: A1-A8',
    duty_cycle VARCHAR(50) COMMENT '工作制度',
    -- 轨道信息
    rail_type VARCHAR(50) COMMENT '轨道型号',
    rail_elevation DECIMAL(10,2) COMMENT '轨顶标高 m',
    -- 供应方式
    crane_supplier VARCHAR(20) COMMENT '吊车供应方: 业主/承包商',
    runway_beam_by VARCHAR(20) COMMENT '吊车梁供应方',
    FOREIGN KEY (rfq_id) REFERENCES lsb_rfq_main(id)
);
```

**业务价值**:
- 精确计算吊车梁和牛腿设计
- 明确供货范围界面
- 支持多吊车场景

### 2.3 门窗表详细信息 [中优先级]

**现状问题**: 门窗仅有布尔标识，无法获取具体规格和数量。

**建议新增表**: `lsb_rfq_opening`

```sql
CREATE TABLE lsb_rfq_opening (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rfq_id INT NOT NULL,
    building_no INT DEFAULT 1,
    opening_type VARCHAR(50) COMMENT '类型: 人门/工业门/窗户/卷帘门',
    width DECIMAL(10,2) COMMENT '宽度 mm',
    height DECIMAL(10,2) COMMENT '高度 mm',
    quantity INT COMMENT '数量',
    location VARCHAR(100) COMMENT '位置描述',
    material VARCHAR(50) COMMENT '材质',
    hardware VARCHAR(100) COMMENT '五金配件要求',
    fire_rating VARCHAR(20) COMMENT '防火等级',
    remarks TEXT COMMENT '备注',
    FOREIGN KEY (rfq_id) REFERENCES lsb_rfq_main(id)
);
```

### 2.4 附件与图纸管理 [中优先级]

**建议新增表**: `lsb_rfq_attachment`

```sql
CREATE TABLE lsb_rfq_attachment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rfq_id INT NOT NULL,
    file_name VARCHAR(255) COMMENT '文件名',
    file_path VARCHAR(500) COMMENT '存储路径',
    file_type VARCHAR(50) COMMENT '文件类型: 图纸/计算书/技术规格/其他',
    file_size INT COMMENT '文件大小 bytes',
    uploaded_by VARCHAR(100) COMMENT '上传人',
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    version VARCHAR(20) COMMENT '版本号',
    is_current TINYINT(1) DEFAULT 1 COMMENT '是否当前版本',
    remarks TEXT,
    FOREIGN KEY (rfq_id) REFERENCES lsb_rfq_main(id)
);
```

---

## 3. 技术架构优化

### 3.1 数据验证增强

**前端验证**:
```javascript
// 建议增加的验证规则
const validationRules = {
    // 尺寸逻辑验证
    dimensions: {
        validate: (data) => {
            if (data.width > data.length) {
                return '宽度不应大于长度，请确认是否正确';
            }
            if (data.eave_height < 6) {
                return '檐口高度小于6m，请确认是否为低层建筑';
            }
            return true;
        }
    },
    // 面积一致性验证
    area: {
        validate: (data) => {
            const calculated = data.length * data.width;
            const difference = Math.abs(calculated - data.floor_area_1);
            if (difference > calculated * 0.1) {
                return '计算面积与填写面积差异超过10%，请核实';
            }
            return true;
        }
    }
};
```

**后端验证**:
- 增加业务规则校验层
- 实现字段间依赖关系验证
- 添加数据完整性检查

### 3.2 审计追踪系统

**建议新增表**: `lsb_rfq_audit_log`

```sql
CREATE TABLE lsb_rfq_audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rfq_id INT NOT NULL,
    action VARCHAR(50) COMMENT '操作: create/update/status_change/export/print',
    field_name VARCHAR(100) COMMENT '修改字段',
    old_value TEXT COMMENT '原值',
    new_value TEXT COMMENT '新值',
    operator VARCHAR(100) COMMENT '操作人',
    operator_ip VARCHAR(50) COMMENT '操作IP',
    operated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    remarks TEXT,
    INDEX idx_rfq_id (rfq_id),
    INDEX idx_operated_at (operated_at)
);
```

### 3.3 版本控制机制

**建议方案**:
1. RFQ修订版本号: RFQ202512270001-R1, R2, R3...
2. 每次重大修改自动创建新版本
3. 保留历史版本可追溯查询
4. 版本对比功能

---

## 4. 用户体验提升

### 4.1 智能默认值

**按项目类型预填**:

| 项目类型 | 推荐默认值 |
|----------|------------|
| 仓库 | 檐高12m, 无吊车, 双层屋面 |
| 厂房 | 檐高10m, 可能有吊车, 通风器 |
| 冷库 | 夹芯板, 密封要求高 |
| 办公楼 | 夹层楼面, 窗户较多 |

### 4.2 条件显示逻辑

**建议实现**:
```javascript
// 字段显示逻辑
const conditionalDisplay = {
    // 有吊车时显示吊车详情
    'crane_section': () => form.bridge_crane === 1,
    // 有夹层时显示夹层参数
    'mezzanine_section': () => form.mezzanine_steels === 1,
    // 有采光板时显示采光板详情
    'skylight_section': () => form.roof_skylight === 1,
    // LEED认证时显示环保要求
    'leed_section': () => form.leed === 1
};
```

### 4.3 实时计算功能

**建议增加**:
- 建筑面积自动计算: 长 × 宽
- 墙面积估算: 周长 × 檐高
- 屋面面积估算: 考虑坡度
- 钢量预估: 基于经验公式

### 4.4 模板系统

**功能描述**:
1. 保存常用项目配置为模板
2. 新建RFQ时可选择模板快速填充
3. 模板分类管理（按客户/项目类型）

---

## 5. 业务流程改进

### 5.1 审批工作流

**建议流程**:

```
Draft → Submitted → Under Review → Quoted → Approved/Rejected
  │         │            │           │            │
  └─修改────┴─退回修改───┴─补充信息──┴─价格协商───┘
```

**功能需求**:
- 角色权限控制
- 审批意见记录
- 邮件/消息通知
- 超时提醒

### 5.2 报价集成

**建议功能**:
- 关联报价单表
- 多版本报价对比
- 报价有效期管理
- 中标状态跟踪

### 5.3 客户门户

**功能设想**:
- 客户自助填写RFQ
- 实时查看处理进度
- 在线沟通反馈
- 历史项目查询

---

## 6. 未来发展路线图

### Phase 1: 基础完善 (1-2个月)

| 任务 | 优先级 | 工作量 |
|------|--------|--------|
| 新增荷载信息表 | P0 | 中 |
| 新增吊车详情表 | P0 | 中 |
| 增强数据验证 | P1 | 小 |
| 修复已知问题 | P0 | 小 |

### Phase 2: 功能扩展 (2-3个月)

| 任务 | 优先级 | 工作量 |
|------|--------|--------|
| 门窗明细表 | P1 | 中 |
| 附件管理系统 | P1 | 中 |
| 审计日志 | P2 | 小 |
| 版本控制 | P2 | 中 |

### Phase 3: 体验优化 (2-3个月)

| 任务 | 优先级 | 工作量 |
|------|--------|--------|
| 条件显示逻辑 | P1 | 中 |
| 智能默认值 | P2 | 小 |
| 实时计算 | P2 | 中 |
| 项目模板 | P2 | 中 |

### Phase 4: 系统集成 (3-6个月)

| 任务 | 优先级 | 工作量 |
|------|--------|--------|
| 审批工作流 | P1 | 大 |
| 报价系统集成 | P1 | 大 |
| 用户权限管理 | P1 | 中 |
| 客户门户 | P3 | 大 |

---

## 7. 优先级与实施计划

### 7.1 优先级矩阵

```
                    高业务价值
                        │
         ┌──────────────┼──────────────┐
         │   [荷载信息] │ [审批工作流] │
         │   [吊车详情] │ [报价集成]   │
  低     │──────────────┼──────────────│     高
  实施   │   [智能默认] │ [门窗明细]   │   实施
  难度   │   [实时计算] │ [附件管理]   │   难度
         │──────────────┼──────────────│
         │   [条件显示] │ [客户门户]   │
         │   [模板系统] │ [版本控制]   │
         └──────────────┼──────────────┘
                        │
                    低业务价值
```

### 7.2 建议立即行动项

1. **荷载信息表** - 对报价准确性影响最大
2. **吊车详情表** - 吊车项目必需信息
3. **数据验证增强** - 减少人为错误
4. **审计日志** - 合规性要求

### 7.3 资源估算

| 阶段 | 开发工作量 | 测试工作量 | 建议人员 |
|------|-----------|-----------|----------|
| Phase 1 | 2人周 | 1人周 | 1后端+1前端 |
| Phase 2 | 3人周 | 1.5人周 | 1后端+1前端 |
| Phase 3 | 3人周 | 1.5人周 | 1全栈+UI设计 |
| Phase 4 | 6人周 | 3人周 | 2后端+1前端 |

---

## 附录

### A. 相关文件清单

| 文件 | 说明 |
|------|------|
| rfq_reference_data_v3.1.sql | 参考数据定义 |
| sample_data_v3.1.sql | 示例数据 |
| form.php | 表单页面 |
| api/rfq.php | API接口 |

### B. 参考规范

- GB 50009-2012 建筑结构荷载规范
- GB 50011-2010 建筑抗震设计规范
- CECS 102:2002 门式刚架轻型房屋钢结构技术规程

### C. 讨论议题

1. 各改进项的优先级排序是否合适？
2. Phase 1的实施时间表如何安排？
3. 是否需要增加其他业务功能？
4. 客户门户是否纳入近期规划？
5. 与现有ERP/CRM系统的集成需求？

---

**文档编制**: AI Assistant
**审核人**: ________________
**批准人**: ________________
**会议日期**: ________________

---

*本文档为讨论稿，最终方案以会议决议为准。*
