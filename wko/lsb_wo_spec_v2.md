# LSB Work Order System V2 - 完善版设计方案

> 基于 lsb_wo_minimal_spec.md 的改进版本
> 更新日期: 2025-12-29

---

## 1. Excel模板分析结果

### 1.1 模板结构
模板包含 **6个Sheet**:
- **Contract** (主合同页) - 打印区域 A1:F162
- **CO #1 ~ CO #4** (变更单 Change Order)
- **CO Summary** (变更单汇总)

### 1.2 关键字段提取位置 (Contract Sheet)

| 字段名 | 中文说明 | 建议提取方式 |
|--------|----------|--------------|
| W.O. No. | 工作单号 | 固定单元格 |
| LSB Job No. | LSB项目编号 | 固定单元格 |
| PROJECT / Project Name | 项目名称 | 固定单元格 |
| Address | 项目地址 | 固定单元格 |
| ISSUED TO / COMPANY NAME | 供应商名称 | 固定单元格 |
| Contact | 联系人 | 固定单元格 |
| Phone | 电话 | 固定单元格 |
| Date Issued | 签发日期 | 固定单元格 |
| Cost Code No. | 成本代码 | 固定单元格 |
| ORIGINAL W.O. AMOUNT | 原始合同金额 | 固定单元格 |
| CONTRACT TO DATE | 合同至今总额 | 公式计算 |
| GST: Extra | GST税 | 标注说明 |
| Lien Holdback | 留置扣款 | 百分比 |
| SCOPE OF WORK SUMMARY | 工作范围 | 文本区域 |
| Owner Name | 业主名称 | 固定单元格 |

### 1.3 合同条款 (固定文本，无需提取)
- 付款条款 (90%进度款 + 10%尾款)
- WCB安全要求
- 变更单加价 10% markup
- 保修期2年
- 保险要求 $2M
- 现场清洁要求

---

## 2. 修订后的数据模型

### 2.1 主表: `lsb_wo_header`

```sql
CREATE TABLE lsb_wo_header (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

  -- 工作单信息
  wo_no VARCHAR(30) NOT NULL UNIQUE,              -- WO-2025-000123
  title VARCHAR(200) NULL,                        -- 标题/简述

  -- 项目信息
  lsb_job_no VARCHAR(50) NULL,                    -- LSB项目编号
  project_code VARCHAR(50) NOT NULL,              -- 项目代码
  project_name VARCHAR(200) NULL,                 -- 项目名称
  project_address VARCHAR(300) NULL,              -- 项目地址
  owner_name VARCHAR(200) NULL,                   -- 业主名称

  -- 供应商信息
  vendor_name VARCHAR(200) NULL,                  -- 供应商/分包商名称
  vendor_address VARCHAR(300) NULL,               -- 供应商地址
  vendor_contact VARCHAR(100) NULL,               -- 联系人
  vendor_phone VARCHAR(50) NULL,                  -- 电话
  vendor_email VARCHAR(190) NULL,                 -- 邮箱

  -- 金额信息
  original_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,  -- 原始合同金额
  change_order_amount DECIMAL(12,2) DEFAULT 0.00,       -- 变更单总额
  total_amount DECIMAL(12,2) GENERATED ALWAYS AS (original_amount + change_order_amount) STORED,
  currency CHAR(3) NOT NULL DEFAULT 'CAD',
  cost_code VARCHAR(50) NULL,                     -- 成本代码
  holdback_percent DECIMAL(5,2) DEFAULT 10.00,    -- 留置扣款百分比

  -- 范围描述
  scope_summary TEXT NULL,                        -- 工作范围摘要

  -- 申请人信息
  requester_name VARCHAR(100) NOT NULL,
  requester_email VARCHAR(190) NULL,
  requester_department VARCHAR(100) NULL,

  -- 状态和时间
  status VARCHAR(20) NOT NULL DEFAULT 'DRAFT',    -- DRAFT/SUBMITTED/DONE/REJECTED
  issued_date DATE NULL,                          -- 签发日期
  submitted_at DATETIME NULL,
  completed_at DATETIME NULL,

  -- 文件
  source_excel_path VARCHAR(500) NULL,            -- 上传的Excel文件路径
  memo TEXT NULL,                                 -- 备注

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_status (status),
  INDEX idx_project_code (project_code),
  INDEX idx_lsb_job_no (lsb_job_no),
  INDEX idx_requester_email (requester_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.2 审阅表: `lsb_wo_review`

```sql
CREATE TABLE lsb_wo_review (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  wo_id INT UNSIGNED NOT NULL,

  reviewer_role VARCHAR(30) NOT NULL,             -- PM, PM_MANAGER, GM, CFO, TECH
  reviewer_name VARCHAR(100) NOT NULL,
  reviewer_email VARCHAR(190) NOT NULL,

  -- 审阅决定: PENDING / ACK / ACK_WITH_CONDITION / REJECTED
  decision VARCHAR(30) NOT NULL DEFAULT 'PENDING',
  comment TEXT NULL,
  condition_note TEXT NULL,                       -- 条件说明 (ACK_WITH_CONDITION时使用)
  reviewed_at DATETIME NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uk_wo_reviewer (wo_id, reviewer_role),
  INDEX idx_wo_id (wo_id),
  INDEX idx_reviewer_email (reviewer_email),
  INDEX idx_decision (decision),

  CONSTRAINT fk_review_wo
    FOREIGN KEY (wo_id) REFERENCES lsb_wo_header(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.3 审阅人配置表: `lsb_wo_reviewer_config`

```sql
CREATE TABLE lsb_wo_reviewer_config (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

  role_code VARCHAR(30) NOT NULL UNIQUE,          -- PM, PM_MANAGER, GM, CFO, TECH
  role_name VARCHAR(100) NOT NULL,                -- 角色显示名称
  role_name_cn VARCHAR(100) NULL,                 -- 中文名称

  reviewer_name VARCHAR(100) NOT NULL,            -- 默认审阅人姓名
  reviewer_email VARCHAR(190) NOT NULL,           -- 默认审阅人邮箱

  is_required TINYINT(1) DEFAULT 1,               -- 是否必须审阅
  sort_order INT DEFAULT 0,                       -- 审阅顺序
  is_active TINYINT(1) DEFAULT 1,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 初始数据
INSERT INTO lsb_wo_reviewer_config (role_code, role_name, role_name_cn, reviewer_name, reviewer_email, sort_order) VALUES
('PM', 'Project Manager', '项目经理', 'PM Name', 'pm@libertysteelbuildings.com', 1),
('PM_MANAGER', 'PM Manager', '项目经理主管', 'PM Manager', 'pmm@libertysteelbuildings.com', 2),
('TECH', 'Technical Director', '技术总监', 'Tech Director', 'tech@libertysteelbuildings.com', 3),
('CFO', 'Finance Director', '财务总监', 'CFO Name', 'cfo@libertysteelbuildings.com', 4),
('GM', 'General Manager', '总经理', 'GM Name', 'gm@libertysteelbuildings.com', 5);
```

### 2.4 文件表: `lsb_wo_files`

```sql
CREATE TABLE lsb_wo_files (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  wo_id INT UNSIGNED NOT NULL,

  file_name VARCHAR(255) NOT NULL,                -- 原始文件名
  file_path VARCHAR(500) NULL,                    -- 存储路径
  file_size BIGINT UNSIGNED DEFAULT 0,
  file_type VARCHAR(100) NULL,                    -- MIME类型
  file_ext VARCHAR(20) NULL,
  file_category VARCHAR(50) DEFAULT 'attachment', -- contract/change_order/attachment

  uploaded_by VARCHAR(100) NULL,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  is_active TINYINT(1) DEFAULT 1,

  INDEX idx_wo_id (wo_id),
  INDEX idx_category (file_category),

  CONSTRAINT fk_file_wo
    FOREIGN KEY (wo_id) REFERENCES lsb_wo_header(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.5 变更单表: `lsb_wo_change_order` (可选，后期实现)

```sql
CREATE TABLE lsb_wo_change_order (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  wo_id INT UNSIGNED NOT NULL,

  co_no INT NOT NULL,                             -- 变更单序号 1,2,3,4...
  description TEXT NULL,
  amount DECIMAL(12,2) DEFAULT 0.00,

  status VARCHAR(20) DEFAULT 'DRAFT',
  issued_date DATE NULL,

  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uk_wo_co (wo_id, co_no),
  CONSTRAINT fk_co_wo
    FOREIGN KEY (wo_id) REFERENCES lsb_wo_header(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. 审阅决定类型

| 状态 | 英文 | 中文 | 说明 |
|------|------|------|------|
| PENDING | Pending | 待审阅 | 初始状态 |
| ACK | Acknowledged | 已确认 | 无条件确认 |
| ACK_WITH_CONDITION | Acknowledged with Condition | 有条件确认 | 确认但有附加条件 |
| REJECTED | Rejected | 拒绝 | 不同意，需修改 |

### 工作流规则
1. 所有审阅人都是 `ACK` 或 `ACK_WITH_CONDITION` → WO状态变为 `DONE`
2. 任一审阅人 `REJECTED` → WO状态变为 `REJECTED`
3. `REJECTED` 后，申请人可以修改后重新提交

---

## 4. 身份验证方案

### 4.1 采用方案: Email + Session 登录

使用简单的 Email + Password + Session 方式进行身份验证，简单可控，易于维护。

### 4.2 用户表: `lsb_wo_users`

```sql
CREATE TABLE lsb_wo_users (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,            -- password_hash()
  role VARCHAR(30) NULL,                          -- 关联reviewer_config的role_code
  department VARCHAR(100) NULL,                   -- 部门
  is_admin TINYINT(1) DEFAULT 0,                  -- 管理员标识
  is_active TINYINT(1) DEFAULT 1,
  last_login DATETIME NULL,
  login_attempts INT DEFAULT 0,                   -- 登录失败次数
  locked_until DATETIME NULL,                     -- 锁定截止时间
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_role (role),
  INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 初始管理员账户 (密码需要在部署时修改)
INSERT INTO lsb_wo_users (email, name, password_hash, role, is_admin) VALUES
('admin@libertysteelbuildings.com', 'System Admin', '', 'ADMIN', 1);
```

### 4.3 登录流程

```
1. 用户输入 Email + Password
2. 验证账户是否存在且激活
3. 检查是否被锁定 (5次失败锁定30分钟)
4. password_verify() 验证密码
5. 成功: 创建Session, 更新last_login
6. 失败: 增加login_attempts
```

### 4.4 Session管理

```php
// 登录成功后设置Session
$_SESSION['wo_user'] = [
    'id' => $user['id'],
    'email' => $user['email'],
    'name' => $user['name'],
    'role' => $user['role'],
    'is_admin' => $user['is_admin']
];

// 页面权限检查
function wo_require_login() {
    if (empty($_SESSION['wo_user'])) {
        header('Location: login.php');
        exit;
    }
}

function wo_require_admin() {
    wo_require_login();
    if (!$_SESSION['wo_user']['is_admin']) {
        http_response_code(403);
        die('Access denied');
    }
}
```

---

## 5. Excel文件处理方案

### 5.1 采用方案: ChatGPT-4 Mini API 解析

使用 OpenAI ChatGPT-4 Mini API 读取 Excel 文件内容，解析后返回 JSON 数据，用于页面展示和数据提取。

**优点:**
- 智能识别字段，无需硬编码单元格位置
- 支持多种Excel格式 (.xlsx, .xls, .xltx)
- 可处理复杂布局和合并单元格
- 成本低 (GPT-4 Mini 价格约 $0.15/1M tokens)

### 5.2 处理流程

```
1. 用户上传Excel文件 → 保存到服务器
2. 后端将Excel转为Base64或提取文本内容
3. 调用GPT-4 Mini API，提供解析提示词
4. API返回结构化JSON数据
5. 前端展示JSON数据 + 提供原文件下载
```

### 5.3 API调用示例

```php
/**
 * 使用GPT-4 Mini解析Excel文件
 */
function parseExcelWithGPT($filePath) {
    // 使用PhpSpreadsheet读取Excel为文本
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    // 提取前100行数据为文本
    $textContent = '';
    foreach ($sheet->getRowIterator(1, 100) as $row) {
        $rowData = [];
        foreach ($row->getCellIterator() as $cell) {
            $rowData[] = $cell->getValue();
        }
        $textContent .= implode(' | ', array_filter($rowData)) . "\n";
    }

    // 调用GPT-4 Mini API
    $response = callOpenAI([
        'model' => 'gpt-4o-mini',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are an expert at parsing Work Order documents. Extract key fields and return as JSON.'
            ],
            [
                'role' => 'user',
                'content' => "Parse this Work Order Excel content and extract fields:\n\n$textContent\n\nReturn JSON with fields: wo_no, lsb_job_no, project_name, project_address, owner_name, vendor_name, vendor_contact, vendor_phone, original_amount, scope_summary, issued_date"
            ]
        ],
        'response_format' => ['type' => 'json_object']
    ]);

    return json_decode($response['choices'][0]['message']['content'], true);
}
```

### 5.4 返回JSON格式

```json
{
  "wo_no": "WO-2025-000123",
  "lsb_job_no": "LSB-2025-456",
  "project_name": "ABC Industrial Building",
  "project_address": "123 Main Street, Edmonton, AB",
  "owner_name": "ABC Corporation",
  "vendor_name": "XYZ Contractors Ltd.",
  "vendor_contact": "John Smith",
  "vendor_phone": "780-123-4567",
  "original_amount": 125000.00,
  "scope_summary": "Supply and install structural steel framing...",
  "issued_date": "2025-01-15",
  "confidence": 0.95,
  "warnings": ["Some fields may need manual verification"]
}
```

### 5.5 前端展示

```html
<!-- Excel内容预览区 -->
<div class="card">
  <div class="card-header">
    <h5>Excel解析结果</h5>
    <a href="download.php?id=123" class="btn btn-sm btn-outline-primary">
      <i class="fas fa-download"></i> 下载原文件
    </a>
  </div>
  <div class="card-body">
    <table class="table table-bordered">
      <tr><th>W.O. No.</th><td id="wo_no"></td></tr>
      <tr><th>Project Name</th><td id="project_name"></td></tr>
      <tr><th>Vendor</th><td id="vendor_name"></td></tr>
      <tr><th>Amount</th><td id="original_amount"></td></tr>
      <!-- ... -->
    </table>
  </div>
</div>
```

### 5.6 PhpSpreadsheet 安装

```bash
# 使用Composer安装 (用于Excel文本提取)
cd c:\xampp\htdocs\aiforms
composer require phpoffice/phpspreadsheet
```

---

## 6. 文件结构

```
wko/
├── config/
│   └── wo_config.php             # WO系统配置
├── api/
│   ├── wo.php                    # WO API接口
│   ├── auth.php                  # 登录/登出API
│   ├── user.php                  # 用户管理API
│   └── excel_parse.php           # Excel解析API (GPT-4 Mini)
├── includes/
│   ├── wo_functions.php          # WO专用函数
│   ├── wo_auth.php               # 身份验证函数
│   └── wo_openai.php             # OpenAI API封装
├── sql/
│   └── lsb_wo_schema.sql         # 数据库脚本
├── uploads/                      # 上传文件目录
│   └── wo/
│       └── {wo_id}/
├── login.php                     # 登录页面
├── logout.php                    # 登出处理
├── dashboard.php                 # 仪表盘首页
├── wo_list.php                   # 我创建的WO列表
├── wo_inbox.php                  # 待我审阅
├── wo_create.php                 # 创建WO
├── wo_view.php                   # 查看详情+审阅
├── wo_edit.php                   # 编辑草稿
├── user_list.php                 # 用户管理 (管理员)
├── user_edit.php                 # 编辑用户 (管理员)
└── profile.php                   # 个人资料/修改密码
```

---

## 7. 开发阶段

### Phase 1: 基础功能 (MVP)
- [ ] 数据库表创建 (6张表)
- [ ] Email + Session 登录系统
- [ ] 用户管理 (管理员添加用户)
- [ ] 创建/编辑WO草稿
- [ ] 上传Excel文件
- [ ] GPT-4 Mini 解析Excel → JSON展示
- [ ] 提交WO (创建审阅记录)
- [ ] 审阅人查看/ACK/ACK_WITH_CONDITION/拒绝
- [ ] WO状态自动更新

### Phase 2: 增强功能
- [ ] 审阅历史记录
- [ ] 文件多版本管理
- [ ] WO搜索和筛选
- [ ] 批量操作
- [ ] 数据导出 (Excel/PDF)

### Phase 3: 高级功能
- [ ] 变更单管理 (Change Order)
- [ ] 邮件通知
- [ ] Microsoft SSO (可选)
- [ ] 报表和统计
- [ ] 审计日志

---

## 8. 与RFQ系统共享资源

```php
// 共享的文件
require_once dirname(__DIR__) . '/config/database.php';   // 数据库连接
require_once dirname(__DIR__) . '/includes/functions.php'; // 通用函数
require_once dirname(__DIR__) . '/includes/header.php';    // 页面头部
require_once dirname(__DIR__) . '/includes/footer.php';    // 页面底部

// 共享的CSS/JS
// assets/css/style.css
// assets/js/app.js
```

---

## 9. API设计

### 9.1 认证API (`api/auth.php`)

| 方法 | action | 说明 |
|------|--------|------|
| POST | login | 用户登录 |
| POST | logout | 用户登出 |
| GET  | check | 检查登录状态 |
| POST | change_password | 修改密码 |

### 9.2 用户管理API (`api/user.php`) - 管理员

| 方法 | action | 说明 |
|------|--------|------|
| GET  | list | 获取用户列表 |
| GET  | get | 获取用户详情 |
| POST | create | 创建用户 |
| POST | update | 更新用户 |
| POST | toggle_active | 启用/禁用用户 |
| POST | reset_password | 重置密码 |

### 9.3 WO API (`api/wo.php`)

| 方法 | action | 说明 |
|------|--------|------|
| POST | create | 创建WO草稿 |
| POST | update | 更新WO |
| POST | submit | 提交WO |
| GET  | get | 获取WO详情 |
| GET  | list | 获取WO列表 (我创建的) |
| GET  | inbox | 获取待审阅列表 |
| POST | review | 提交审阅决定 |
| POST | upload | 上传文件 |
| GET  | download | 下载文件 |
| POST | delete_file | 删除文件 |

### 9.4 Excel解析API (`api/excel_parse.php`)

| 方法 | action | 说明 |
|------|--------|------|
| POST | parse | 上传并解析Excel文件 |
| GET  | result | 获取解析结果 (JSON) |

### 9.5 响应格式

```json
{
  "success": true,
  "message": "Operation completed",
  "data": { ... }
}
```

---

## 10. 下一步行动

1. **确认方案** - 请确认以上设计是否符合需求
2. **创建数据库** - 执行SQL脚本创建6张表
3. **安装依赖** - `composer require phpoffice/phpspreadsheet`
4. **开发登录模块** - Email + Session 登录系统
5. **开发用户管理** - 管理员添加/编辑用户
6. **开发WO核心功能** - 创建、编辑、上传、提交
7. **集成GPT-4 Mini** - Excel解析功能
8. **开发审阅功能** - 查看、ACK、拒绝

---

## 11. 数据库表汇总

| 表名 | 说明 | Phase |
|------|------|-------|
| `lsb_wo_users` | 用户表 | 1 |
| `lsb_wo_header` | WO主表 | 1 |
| `lsb_wo_review` | 审阅记录表 | 1 |
| `lsb_wo_reviewer_config` | 审阅人配置表 | 1 |
| `lsb_wo_files` | 文件附件表 | 1 |
| `lsb_wo_change_order` | 变更单表 | 3 |

---

**End of Spec V2**
**更新日期: 2025-12-29**
