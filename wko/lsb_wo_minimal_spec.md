# LSB Work Order / Purchase Order Review System (PHP + MySQL) — Minimal Design Spec

> Goal: Build a **very simple** internal system where a Work Order (also used as a Purchase Order) is submitted by PM, then **five fixed reviewers** (PM, PM_MANAGER, GM, CFO, TECH) **read and leave comments**.  
> No hard “approval” needed — only **ACK (Acknowledged / 已阅确认)**.  
> All reviewers can see each other’s comments on the same WO.

---

## 1. Scope (MVP)

### Must-have
- Create a WO draft with basic info + upload Excel file (template).
- Submit WO → system generates **5 review rows** from a config file.
- Reviewers open WO → read header + download Excel → write comment → click **ACK**.
- All comments are visible to everyone (same WO page).
- When all 5 reviewers ACK → WO status becomes **DONE**.

### Nice-to-have (optional later)
- Email notifications on submit / ACK completion.
- Excel auto-extract to fill WO header (deterministic parsing).
- Budget warning automation (CFO comment template), without changing DB structure.

---

## 2. Terminology

- **WO**: Work Order / Purchase Order.
- **Reviewer Roles** (fixed): `PM`, `PM_MANAGER`, `GM`, `CFO`, `TECH`
- **ACK**: *Acknowledged* (已知悉 / 已阅确认). Used instead of approve/reject.

---

## 3. Data Model (MySQL)

### Table naming convention
All tables use prefix: `lsb_wo_`

### 3.1 Main table: `lsb_wo_header`
Stores basic WO information.

```sql
CREATE TABLE lsb_wo_header (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

  wo_no VARCHAR(30) NOT NULL UNIQUE,              -- WO-2025-000123
  title VARCHAR(200) NULL,

  project_code VARCHAR(50) NOT NULL,
  project_name VARCHAR(200) NULL,

  requester_name VARCHAR(100) NOT NULL,           -- submitter (PM or PM_MANAGER)
  requester_email VARCHAR(190) NULL,

  vendor_name VARCHAR(200) NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'CAD',

  status VARCHAR(20) NOT NULL DEFAULT 'DRAFT',    -- DRAFT/SUBMITTED/DONE
  submitted_at DATETIME NULL,

  source_excel_path VARCHAR(500) NULL,            -- uploaded Excel path/URL
  memo TEXT NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Suggested indexes (optional)
```sql
CREATE INDEX idx_wo_status ON lsb_wo_header(status);
CREATE INDEX idx_wo_project_code ON lsb_wo_header(project_code);
CREATE INDEX idx_wo_requester_email ON lsb_wo_header(requester_email);
```

---

### 3.2 Child table: `lsb_wo_review`
Stores who reviewed + their ACK + their comment.

```sql
CREATE TABLE lsb_wo_review (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  wo_id BIGINT UNSIGNED NOT NULL,

  reviewer_role VARCHAR(30) NOT NULL,             -- PM, PM_MANAGER, GM, CFO, TECH
  reviewer_name VARCHAR(100) NOT NULL,
  reviewer_email VARCHAR(190) NOT NULL,

  decision VARCHAR(20) NOT NULL DEFAULT 'PENDING',-- PENDING/ACK
  comment TEXT NULL,
  reviewed_at DATETIME NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uk_wo_reviewer (wo_id, reviewer_role, reviewer_email),
  KEY idx_review_wo (wo_id),

  CONSTRAINT fk_review_wo
    FOREIGN KEY (wo_id) REFERENCES lsb_wo_header(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. Fixed Reviewer Config (hard-coded)

Create a config file: `config/wo_reviewers.php`

```php
<?php
return [
  ['role' => 'PM',         'name' => 'PM Name',          'email' => 'pm@company.com'],
  ['role' => 'PM_MANAGER', 'name' => 'PM Manager Name',  'email' => 'pmm@company.com'],
  ['role' => 'GM',         'name' => 'General Manager',  'email' => 'gm@company.com'],
  ['role' => 'CFO',        'name' => 'Finance Director', 'email' => 'cfo@company.com'],
  ['role' => 'TECH',       'name' => 'Tech Director',    'email' => 'tech@company.com'],
];
```

> In MVP, identity is matched by **email** (e.g., Microsoft login email).  
> Later you can replace config with a DB table if needed.

---

## 5. Workflow

### 5.1 Create Draft
- User inputs WO header fields.
- Upload Excel file → store file on server → save `source_excel_path`.
- Save to DB: `status='DRAFT'`.

### 5.2 Submit
When user clicks “Submit”:
1) Update header to `SUBMITTED` and set `submitted_at=NOW()`
2) Insert 5 reviewer rows into `lsb_wo_review` from config (decision=PENDING)
3) (Optional) notify by email

**Important**: Use a DB transaction.

### 5.3 ACK Review
When reviewer writes a comment and clicks ACK:
1) Update that reviewer’s row:
   - `decision='ACK'`
   - `comment=...`
   - `reviewed_at=NOW()`
2) Check whether all 5 rows are ACK
3) If yes → update header `status='DONE'`

---

## 6. Core SQL / Pseudocode (AI-friendly)

### 6.1 Submit WO (transaction)
```sql
START TRANSACTION;

UPDATE lsb_wo_header
SET status='SUBMITTED', submitted_at=NOW()
WHERE id=? AND status='DRAFT';

-- Insert 5 reviewers (execute once per reviewer)
INSERT IGNORE INTO lsb_wo_review
(wo_id, reviewer_role, reviewer_name, reviewer_email, decision)
VALUES (?, ?, ?, ?, 'PENDING');

COMMIT;
```

### 6.2 ACK (and auto-DONE)
```sql
-- 1) ACK my row
UPDATE lsb_wo_review
SET decision='ACK', comment=?, reviewed_at=NOW()
WHERE wo_id=? AND reviewer_email=?;

-- 2) check remaining pending
SELECT COUNT(*) AS pending_cnt
FROM lsb_wo_review
WHERE wo_id=? AND decision <> 'ACK';

-- 3) if pending_cnt = 0 -> mark DONE
UPDATE lsb_wo_header
SET status='DONE'
WHERE id=?;
```

---

## 7. Minimal Endpoints / Pages (recommended)

### Option A: Simple PHP pages (no REST required)
- `wo_list.php` — list WO I created (filter by requester_email)
- `wo_inbox.php` — WO waiting for my ACK (join header + review where reviewer_email = me and decision=PENDING)
- `wo_create.php` — create draft + upload excel
- `wo_view.php?id=...` — view WO header + excel link + all reviews/comments + ACK form for current user
- `wo_submit.php?id=...` — submit draft (POST only)

### Option B: REST API (if you want)
- `POST /api/wo/create`
- `POST /api/wo/{id}/submit`
- `GET  /api/wo/{id}`  (returns header + reviews)
- `POST /api/wo/{id}/ack` (body: comment)
- `GET  /api/wo/inbox?email=...`

**Standard response format**
```json
{ "status": "S|E", "message": "", "data": { } }
```

---

## 8. UI Requirements (MVP)

### WO Detail Page (`wo_view.php`)
Sections:
1) **Header**: project_code, vendor_name, total_amount, currency, requester, status
2) **Excel download**: link to `source_excel_path`
3) **Review stream** (always visible to everyone):
   - reviewer_role, reviewer_name, decision, reviewed_at, comment
4) **My ACK box** (only if the viewer matches a reviewer_email on this WO):
   - textarea comment
   - button “ACK (Acknowledged / 已阅确认)”

---

## 9. Security / Permission Rules (simple)

MVP rules (email-based):
- A user can ACK only their own review row: `WHERE reviewer_email = current_user_email`
- Any reviewer listed in `lsb_wo_review` can view the WO + all comments
- The requester can view the WO + all comments
- Admin (optional) can view everything

Implementation idea:
- `current_user_email` from session / SSO.
- On WO view, validate:
  - requester_email == current_user_email OR
  - exists review row with reviewer_email == current_user_email OR
  - user is admin

---

## 10. Excel Auto-Fill (Optional)

### 10.1 Deterministic extraction (recommended)
Use PhpSpreadsheet:
- Read fixed cells / named ranges from the uploaded Excel.
- Populate:
  - project_code, vendor_name, total_amount, memo, etc.
Pros: stable, auditable, no AI needed.

### 10.2 AI-assisted extraction (only if templates vary)
- Convert extracted text to AI prompt
- AI returns JSON with fields
- Server validates JSON and then updates header

> In MVP, **skip AI extraction** unless needed.

---

## 11. Acceptance Tests (MVP)

1) Create draft WO with Excel attached → status=DRAFT.
2) Submit → status=SUBMITTED; exactly 5 review rows created with decision=PENDING.
3) Any reviewer opens WO → sees header + Excel + all review rows.
4) Reviewer posts comment + ACK → their row becomes ACK.
5) After all 5 ACK → WO becomes DONE automatically.

---

## 12. Implementation Notes for AI Code Generation

When asking AI to code:
- Provide this spec + DB DDL.
- Require:
  - PDO with prepared statements
  - DB transactions for Submit
  - File upload handling (store path in DB)
  - Email-based session identity (`current_user_email`)
  - Simple HTML pages + minimal CSS (Bootstrap optional)

---

## 13. Appendix: Suggested constants

In PHP:
- Status: `DRAFT`, `SUBMITTED`, `DONE`
- Decision: `PENDING`, `ACK`
- Roles: `PM`, `PM_MANAGER`, `GM`, `CFO`, `TECH`

---

**End of spec**
