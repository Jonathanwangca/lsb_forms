<?php
/**
 * LSB Work Order System - Edit Work Order
 */

require_once __DIR__ . '/includes/wo_functions.php';
require_once __DIR__ . '/includes/wo_auth.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: wo_list.php');
    exit;
}

$wo = wo_get($id);
if (!$wo) {
    header('Location: wo_list.php');
    exit;
}

// 检查权限
$currentUser = wo_get_current_user();
$isOwner = ($wo['requester_id'] == $currentUser['id']);
$pdo = wo_get_db();

// 检查是否是GM角色
$stmt = $pdo->prepare("SELECT role_code FROM lsb_wo_reviewer_config WHERE reviewer_email = ? AND is_active = 1");
$stmt->execute([$currentUser['email']]);
$userRole = $stmt->fetchColumn();
$isGM = ($userRole === 'GM');

// 检查是否是拒绝该项目的reviewer
$isRejecter = false;
if ($wo['status'] === 'REJECTED') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lsb_wo_review WHERE wo_id = ? AND reviewer_email = ? AND decision = 'REJECTED'");
    $stmt->execute([$id, $currentUser['email']]);
    $isRejecter = ($stmt->fetchColumn() > 0);
}

// 编辑权限判断：
// - DRAFT状态：创建者或管理员可编辑
// - REJECTED状态：GM、拒绝者、或管理员可编辑
$canEditDraft = ($wo['status'] === 'DRAFT') && ($isOwner || $currentUser['is_admin']);
$canEditRejected = ($wo['status'] === 'REJECTED') && ($isGM || $isRejecter || $currentUser['is_admin']);
$canEdit = $canEditDraft || $canEditRejected;

// 提交权限判断：
// - DRAFT状态：仅创建者可提交
// - REJECTED状态：GM、拒绝者、或管理员可重新提交
$canSubmitDraft = ($wo['status'] === 'DRAFT') && $isOwner;
$canSubmitRejected = ($wo['status'] === 'REJECTED') && ($isGM || $isRejecter || $currentUser['is_admin']);
$canSubmit = $canSubmitDraft || $canSubmitRejected;

if (!$canEdit) {
    header('Location: wo_view.php?id=' . $id);
    exit;
}

$pageTitle = 'Edit: ' . $wo['wo_no'];
require_once __DIR__ . '/includes/wo_header.php';
?>

<style>
/* 紧凑表单样式 */
.compact-form .form-label {
    font-size: 0.75rem;
    margin-bottom: 0.15rem;
    color: #666;
}
.compact-form .form-control, .compact-form .form-select {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
.compact-form .input-group-text {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
.compact-form .row.g-2 > [class*="col-"] {
    margin-bottom: 0.35rem;
}
.compact-form .card-body {
    padding: 0.75rem;
}
.compact-form textarea.form-control {
    padding: 0.375rem 0.5rem;
}
/* 可折叠卡片头部样式 */
.collapsible-header {
    cursor: pointer;
    user-select: none;
    padding: 0.5rem 0.75rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.collapsible-header:hover {
    background-color: #f8f9fa;
}
.collapsible-header .toggle-icon {
    transition: transform 0.2s ease;
}
.collapsible-header.collapsed .toggle-icon {
    transform: rotate(-90deg);
}
.compact-form .card {
    margin-bottom: 0.5rem;
}
</style>

<div class="page-header d-flex justify-content-between align-items-center mb-2">
    <div>
        <h1 class="h4 mb-0"><i class="fas fa-edit me-2"></i>Edit Work Order</h1>
        <small class="text-muted"><?= htmlspecialchars($wo['wo_no']) ?> <?= wo_status_badge($wo['status']) ?></small>
    </div>
    <div>
        <?php if ($canEdit): ?>
        <button type="submit" form="woForm" class="btn btn-primary btn-sm" id="saveBtn">
            <i class="fas fa-save me-1"></i>Save Draft
        </button>
        <?php else: ?>
        <button type="button" class="btn btn-primary btn-sm" disabled title="You don't have permission to edit">
            <i class="fas fa-save me-1"></i>Save Draft
        </button>
        <?php endif; ?>

        <?php if ($canSubmit): ?>
        <button type="button" class="btn btn-success btn-sm" id="submitBtn" onclick="saveAndSubmit()">
            <i class="fas fa-paper-plane me-1"></i>Save & Submit
        </button>
        <?php else: ?>
        <button type="button" class="btn btn-success btn-sm" disabled title="You don't have permission to submit">
            <i class="fas fa-paper-plane me-1"></i>Save & Submit
        </button>
        <?php endif; ?>

        <a href="wo_view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<?php if ($wo['status'] === 'REJECTED'): ?>
<div class="alert alert-warning py-2 mb-2">
    <i class="fas fa-exclamation-triangle me-2"></i>
    This Work Order was rejected. Please make necessary changes and resubmit.
</div>
<?php endif; ?>

<form id="woForm" onsubmit="saveWO(event)" class="compact-form">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Basic Info - Collapsible -->
            <div class="card">
                <div class="card-header collapsible-header" data-bs-toggle="collapse" data-bs-target="#basicInfoCollapse">
                    <span><i class="fas fa-info-circle me-2"></i>Basic Information</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                <div class="collapse show" id="basicInfoCollapse">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Title / Description</label>
                                <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($wo['title'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">LSB Job No.</label>
                                <input type="text" class="form-control" name="lsb_job_no" value="<?= htmlspecialchars($wo['lsb_job_no'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Project Code</label>
                                <input type="text" class="form-control" name="project_code" value="<?= htmlspecialchars($wo['project_code'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Issued Date</label>
                                <input type="date" class="form-control" name="issued_date" value="<?= htmlspecialchars($wo['issued_date'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Info - Collapsible -->
            <div class="card">
                <div class="card-header collapsible-header" data-bs-toggle="collapse" data-bs-target="#projectInfoCollapse">
                    <span><i class="fas fa-building me-2"></i>Project Information</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                <div class="collapse show" id="projectInfoCollapse">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Project Name</label>
                                <input type="text" class="form-control" name="project_name" value="<?= htmlspecialchars($wo['project_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Owner Name</label>
                                <input type="text" class="form-control" name="owner_name" value="<?= htmlspecialchars($wo['owner_name'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Project Address</label>
                                <input type="text" class="form-control" name="project_address" value="<?= htmlspecialchars($wo['project_address'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vendor Info - Collapsible -->
            <div class="card">
                <div class="card-header collapsible-header" data-bs-toggle="collapse" data-bs-target="#vendorInfoCollapse">
                    <span><i class="fas fa-truck me-2"></i>Vendor / Subcontractor</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                <div class="collapse show" id="vendorInfoCollapse">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Vendor Name</label>
                                <input type="text" class="form-control" name="vendor_name" value="<?= htmlspecialchars($wo['vendor_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" name="vendor_contact" value="<?= htmlspecialchars($wo['vendor_contact'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Vendor Address</label>
                                <input type="text" class="form-control" name="vendor_address" value="<?= htmlspecialchars($wo['vendor_address'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="vendor_phone" value="<?= htmlspecialchars($wo['vendor_phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="vendor_email" value="<?= htmlspecialchars($wo['vendor_email'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scope of Work - Collapsible -->
            <div class="card">
                <div class="card-header collapsible-header" data-bs-toggle="collapse" data-bs-target="#scopeCollapse">
                    <span><i class="fas fa-tasks me-2"></i>Scope of Work</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                <div class="collapse show" id="scopeCollapse">
                    <div class="card-body">
                        <textarea class="form-control" name="scope_summary" rows="4"><?= htmlspecialchars($wo['scope_summary'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Memo - Collapsible (collapsed by default) -->
            <div class="card">
                <div class="card-header collapsible-header collapsed" data-bs-toggle="collapse" data-bs-target="#memoCollapse">
                    <span><i class="fas fa-sticky-note me-2"></i>Internal Memo</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                <div class="collapse" id="memoCollapse">
                    <div class="card-body">
                        <textarea class="form-control" name="memo" rows="2"><?= htmlspecialchars($wo['memo'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Amount Info - Collapsible -->
            <div class="card">
                <div class="card-header collapsible-header" data-bs-toggle="collapse" data-bs-target="#financeCollapse">
                    <span><i class="fas fa-dollar-sign me-2"></i>Financial Info</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                <div class="collapse show" id="financeCollapse">
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="form-label">Original Amount (CAD)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="original_amount" step="0.01" min="0" value="<?= $wo['original_amount'] ?>">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Cost Code</label>
                            <input type="text" class="form-control" name="cost_code" value="<?= htmlspecialchars($wo['cost_code'] ?? '') ?>">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Holdback %</label>
                            <input type="number" class="form-control" name="holdback_percent" step="0.01" min="0" max="100" value="<?= $wo['holdback_percent'] ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
const woId = <?= $id ?>;
let shouldSubmit = false;

// 折叠/展开时切换箭头状态
document.querySelectorAll('.collapsible-header').forEach(header => {
    const targetId = header.getAttribute('data-bs-target');
    const target = document.querySelector(targetId);
    if (target) {
        target.addEventListener('show.bs.collapse', () => header.classList.remove('collapsed'));
        target.addEventListener('hide.bs.collapse', () => header.classList.add('collapsed'));
    }
});

function saveWO(e) {
    e.preventDefault();
    const form = document.getElementById('woForm');
    const formData = new FormData(form);
    formData.append('action', 'update');

    const btn = document.getElementById('saveBtn');
    const willSubmit = shouldSubmit; // 保存当前状态
    shouldSubmit = false; // 立即重置，防止重复提交

    showLoading(btn);

    fetch('api/wo.php', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        if (!r.ok) {
            return r.text().then(text => {
                throw new Error('Server error (' + r.status + '): ' + text.substring(0, 200));
            });
        }
        return r.json();
    })
    .then(data => {
        hideLoading(btn);
        if (data.success) {
            showAlert(data.message, 'success');
            // 使用保存的状态判断是否需要提交
            if (willSubmit) {
                submitWO();
            }
        } else {
            showAlert(data.message || 'Save failed', 'danger');
        }
    })
    .catch(err => {
        hideLoading(btn);
        console.error('Save error:', err);
        showAlert('Error: ' + err.message, 'danger');
    });
}

function saveAndSubmit() {
    shouldSubmit = true;
    // 直接调用saveWO而不是通过dispatchEvent
    saveWO(new Event('submit'));
}

function submitWO() {
    const btn = document.getElementById('submitBtn');
    showLoading(btn);

    const formData = new FormData();
    formData.append('action', 'submit');
    formData.append('id', woId);

    fetch('api/wo.php', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        if (!r.ok) {
            throw new Error('Server error: ' + r.status);
        }
        return r.json();
    })
    .then(data => {
        hideLoading(btn);
        if (data.success) {
            showAlert('Work Order submitted successfully!', 'success');
            setTimeout(() => window.location.href = 'wo_list.php', 1500);
        } else {
            showAlert(data.message || 'Submit failed', 'danger');
        }
    })
    .catch(err => {
        hideLoading(btn);
        console.error('Submit error:', err);
        showAlert('Error submitting: ' + err.message, 'danger');
    });
}
</script>

<?php require_once __DIR__ . '/includes/wo_footer.php'; ?>
