<?php
/**
 * LSB Work Order System - View Work Order
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

$reviews = wo_get_reviews($id);
$files = wo_get_files($id);

$currentUser = wo_get_current_user();
$isOwner = ($wo['requester_id'] == $currentUser['id']);
$pdo = wo_get_db();

// 部门权限
$userDeptCode = $currentUser['dept_code'] ?? '';
$canApprove = !empty($currentUser['can_approve']);
$canDeletePerm = !empty($currentUser['can_delete']);

// 检查是否是拒绝该项目的部门成员
$isRejecterDept = false;
if ($wo['status'] === 'REJECTED' && $userDeptCode) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lsb_wo_review WHERE wo_id = ? AND reviewer_dept = ? AND decision = 'REJECTED'");
    $stmt->execute([$id, $userDeptCode]);
    $isRejecterDept = ($stmt->fetchColumn() > 0);
}

// 编辑权限：
// - DRAFT状态：创建者或管理员可编辑
// - REJECTED状态：OPT部门（创建者部门）、拒绝部门成员、或管理员可编辑
$canEditDraft = ($wo['status'] === 'DRAFT') && ($isOwner || $currentUser['is_admin']);
$canEditRejected = ($wo['status'] === 'REJECTED') && ($isOwner || $isRejecterDept || $currentUser['is_admin']);
$canEdit = $canEditDraft || $canEditRejected;

// 提交权限判断：
// - DRAFT状态：仅创建者可提交
// - REJECTED状态：OPT创建者、拒绝部门成员、或管理员可重新提交
$canSubmitDraft = ($wo['status'] === 'DRAFT') && $isOwner;
$canSubmitRejected = ($wo['status'] === 'REJECTED') && ($isOwner || $isRejecterDept || $currentUser['is_admin']);
$canSubmit = $canSubmitDraft || $canSubmitRejected;

// 撤回权限判断：仅创建者可撤回 SUBMITTED 状态的 WO
$canWithdraw = ($wo['status'] === 'SUBMITTED') && $isOwner;

// 检查当前用户部门是否需要审阅
$myReview = null;
if ($canApprove && $userDeptCode) {
    foreach ($reviews as $review) {
        if ($review['reviewer_dept'] === $userDeptCode) {
            $myReview = $review;
            break;
        }
    }
}
$canReview = $myReview && $myReview['decision'] === 'PENDING' && $wo['status'] === 'SUBMITTED';

$pageTitle = 'WO: ' . $wo['wo_no'];
require_once __DIR__ . '/includes/wo_header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <p class="text-muted mb-1"><?= htmlspecialchars($wo['wo_no']) ?></p>
        <h1 class="mb-0">
            <i class="fas fa-file-contract me-2"></i><?= htmlspecialchars($wo['title'] ?: 'No title') ?>
            <?= wo_status_badge($wo['status']) ?>
        </h1>
    </div>
    <div>
        <?php if ($canEdit): ?>
        <a href="wo_edit.php?id=<?= $id ?>" class="btn btn-outline-primary">
            <i class="fas fa-edit me-1"></i>Edit
        </a>
        <?php endif; ?>
        <?php if ($wo['status'] === 'DRAFT' && $canSubmit): ?>
        <button class="btn btn-success" onclick="submitWO()">
            <i class="fas fa-paper-plane me-1"></i>Submit
        </button>
        <?php endif; ?>
        <?php if ($wo['status'] === 'REJECTED' && $canSubmit): ?>
        <button class="btn btn-warning" onclick="revertToDraft()">
            <i class="fas fa-undo me-1"></i>Revert to Draft
        </button>
        <?php endif; ?>
        <?php if ($canWithdraw): ?>
        <button class="btn btn-warning" onclick="withdrawWO()">
            <i class="fas fa-undo me-1"></i>Withdraw
        </button>
        <?php endif; ?>
        <a href="wo_list.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="row">
    <!-- Main Content -->
    <div class="col-lg-8">
        <!-- Basic Info -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-info-circle me-2"></i>Basic Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>WO Number:</strong> <?= htmlspecialchars($wo['wo_no']) ?></p>
                        <p><strong>Title:</strong> <?= htmlspecialchars($wo['title'] ?: '-') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Issued Date:</strong> <?= $wo['issued_date'] ? date('M j, Y', strtotime($wo['issued_date'])) : '-' ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Info -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-building me-2"></i>Project Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>LSB Job No.:</strong> <?= htmlspecialchars($wo['lsb_job_no'] ?: '-') ?></p>
                        <p><strong>Project Code:</strong> <?= htmlspecialchars($wo['project_code'] ?: '-') ?></p>
                        <p><strong>Project Name:</strong> <?= htmlspecialchars($wo['project_name'] ?: '-') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Owner:</strong> <?= htmlspecialchars($wo['owner_name'] ?: '-') ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($wo['project_address'] ?: '-') ?></p>
                        <p><strong>Issued Date:</strong> <?= $wo['issued_date'] ? date('M j, Y', strtotime($wo['issued_date'])) : '-' ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendor Info -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-truck me-2"></i>Vendor / Subcontractor
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Company:</strong> <?= htmlspecialchars($wo['vendor_name'] ?: '-') ?></p>
                        <p><strong>Contact:</strong> <?= htmlspecialchars($wo['vendor_contact'] ?: '-') ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($wo['vendor_address'] ?: '-') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Phone:</strong> <?= htmlspecialchars($wo['vendor_phone'] ?: '-') ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($wo['vendor_email'] ?: '-') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scope of Work -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-tasks me-2"></i>Scope of Work
            </div>
            <div class="card-body">
                <?php if ($wo['scope_summary']): ?>
                <p style="white-space: pre-wrap;"><?= htmlspecialchars($wo['scope_summary']) ?></p>
                <?php else: ?>
                <p class="text-muted">No scope description provided.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Files -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-paperclip me-2"></i>Attachments</span>
                <?php if ($canEdit): ?>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-upload me-1"></i>Upload
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($files)): ?>
                <p class="text-muted mb-0">No files attached.</p>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($files as $file): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-file-<?= $file['file_ext'] === 'pdf' ? 'pdf' : 'excel' ?> me-2 text-<?= $file['file_ext'] === 'pdf' ? 'danger' : 'success' ?>"></i>
                            <?= htmlspecialchars($file['file_name']) ?>
                            <small class="text-muted">(<?= number_format($file['file_size'] / 1024, 1) ?> KB)</small>
                        </div>
                        <a href="api/wo.php?action=download&file_id=<?= $file['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Review Form (if user can review) -->
        <?php if ($canReview): ?>
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark">
                <i class="fas fa-clipboard-check me-2"></i>Your Review Required
            </div>
            <div class="card-body">
                <form id="reviewForm" onsubmit="submitReview(event)">
                    <div class="mb-3">
                        <label class="form-label">Decision <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="decision" id="ack" value="ACK" required>
                                <label class="form-check-label" for="ack">
                                    <span class="badge bg-success">Acknowledge</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="decision" id="ackCond" value="ACK_WITH_CONDITION">
                                <label class="form-check-label" for="ackCond">
                                    <span class="badge bg-info">Acknowledge with Condition</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="decision" id="reject" value="REJECTED">
                                <label class="form-check-label" for="reject">
                                    <span class="badge bg-danger">Reject</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="conditionGroup" style="display: none;">
                        <label class="form-label">Condition Note</label>
                        <div class="input-group mb-1">
                            <select class="form-select form-select-sm" id="presetConditionNote" style="max-width: 200px;" onchange="applyPreset(this, 'condition_note')">
                                <option value="">-- Select Preset --</option>
                            </select>
                        </div>
                        <textarea class="form-control" name="condition_note" id="condition_note" rows="2" placeholder="Describe the conditions..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comments</label>
                        <div class="input-group mb-1">
                            <select class="form-select form-select-sm" id="presetComment" style="max-width: 200px;" onchange="applyPreset(this, 'comment')">
                                <option value="">-- Select Preset --</option>
                            </select>
                        </div>
                        <textarea class="form-control" name="comment" id="comment" rows="3" placeholder="Optional comments..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" id="reviewBtn">
                        <i class="fas fa-check me-1"></i>Submit Review
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Financial Info -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-dollar-sign me-2"></i>Financial Summary
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Original Amount:</span>
                    <strong><?= wo_format_amount($wo['original_amount']) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Change Orders:</span>
                    <strong><?= wo_format_amount($wo['change_order_amount']) ?></strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span><strong>Total:</strong></span>
                    <strong class="text-primary fs-5"><?= wo_format_amount($wo['total_amount']) ?></strong>
                </div>
                <div class="text-muted mt-2">
                    <small>Holdback: <?= $wo['holdback_percent'] ?>%</small><br>
                    <small>Cost Code: <?= htmlspecialchars($wo['cost_code'] ?: '-') ?></small>
                </div>
            </div>
        </div>

        <!-- Review Status -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-building me-2"></i>Department Approvals
            </div>
            <div class="card-body p-0">
                <?php if (empty($reviews)): ?>
                <div class="p-3 text-muted">No reviews required yet.</div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($reviews as $review): ?>
                    <div class="list-group-item py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($review['dept_name'] ?? $review['reviewer_dept']) ?></strong>
                                <?php if ($review['decision'] !== 'PENDING' && $review['reviewer_name']): ?>
                                <br><small class="text-muted">by <?= htmlspecialchars($review['reviewer_name']) ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <?php if ($review['decision'] !== 'PENDING' && $review['reviewed_at']): ?>
                                <small class="text-muted me-2"><?= date('M j H:i', strtotime($review['reviewed_at'])) ?></small>
                                <?php endif; ?>
                                <?= wo_decision_badge($review['decision']) ?>
                            </div>
                        </div>
                        <?php if ($review['comment']): ?>
                        <p class="mb-0 mt-1 small"><i class="fas fa-comment me-1"></i><?= htmlspecialchars($review['comment']) ?></p>
                        <?php endif; ?>
                        <?php if ($review['condition_note']): ?>
                        <p class="mb-0 mt-1 small text-info"><i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($review['condition_note']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Requester Info -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user me-2"></i>Requester
            </div>
            <div class="card-body">
                <p class="mb-1"><strong><?= htmlspecialchars($wo['requester_name']) ?></strong></p>
                <p class="mb-1 text-muted"><?= htmlspecialchars($wo['requester_email'] ?: '-') ?></p>
                <p class="mb-0 text-muted"><?= htmlspecialchars($wo['requester_department'] ?: '-') ?></p>
            </div>
        </div>

        <!-- Timeline -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-clock me-2"></i>Timeline
            </div>
            <div class="card-body">
                <p class="mb-2"><small class="text-muted">Created:</small><br><?= date('M j, Y H:i', strtotime($wo['created_at'])) ?></p>
                <?php if ($wo['submitted_at']): ?>
                <p class="mb-2"><small class="text-muted">Submitted:</small><br><?= date('M j, Y H:i', strtotime($wo['submitted_at'])) ?></p>
                <?php endif; ?>
                <?php if ($wo['completed_at']): ?>
                <p class="mb-0"><small class="text-muted">Completed:</small><br><?= date('M j, Y H:i', strtotime($wo['completed_at'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Admin Delete (Admin or ADM department with can_delete permission) -->
        <?php if ($currentUser['is_admin'] || $canDeletePerm): ?>
        <div class="card mt-4 border-danger">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-shield-alt me-2"></i><?= $currentUser['is_admin'] ? 'Admin Actions' : 'Administrator Actions' ?>
            </div>
            <div class="card-body">
                <button class="btn btn-outline-danger w-100" onclick="deleteWO()">
                    <i class="fas fa-trash-alt me-1"></i>Delete Work Order
                </button>
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    This action cannot be undone.
                </small>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadForm" onsubmit="uploadFile(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">File</label>
                        <input type="file" class="form-control" name="file" required
                               accept=".xlsx,.xls,.xltx,.pdf,.doc,.docx,.jpg,.jpeg,.png">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="contract">Contract</option>
                            <option value="attachment" selected>Attachment</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const woId = <?= $id ?>;
let presetComments = [];
let presetConditionNotes = [];

// Load preset comments on page load
document.addEventListener('DOMContentLoaded', function() {
    loadPresetComments();
});

function loadPresetComments() {
    // Load comments
    fetch('api/preset_comment.php?action=list&type=COMMENT')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                presetComments = data.data.comments;
                updateCommentPresets();
            }
        });

    // Load condition notes
    fetch('api/preset_comment.php?action=list&type=CONDITION_NOTE')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                presetConditionNotes = data.data.comments;
                updateConditionNotePresets();
            }
        });
}

function updateCommentPresets() {
    const select = document.getElementById('presetComment');
    if (!select) return;

    const decision = document.querySelector('input[name="decision"]:checked')?.value || '';
    let filteredComments = presetComments;

    // Filter by decision category
    if (decision === 'ACK') {
        filteredComments = presetComments.filter(c => c.category === 'ACK' || c.category === 'GENERAL');
    } else if (decision === 'ACK_WITH_CONDITION') {
        filteredComments = presetComments.filter(c => c.category === 'CONDITION' || c.category === 'GENERAL');
    } else if (decision === 'REJECTED') {
        filteredComments = presetComments.filter(c => c.category === 'REJECT' || c.category === 'GENERAL');
    }

    select.innerHTML = '<option value="">-- Select Preset --</option>' +
        filteredComments.map(c => `<option value="${escapeAttr(c.comment_text)}">${escapeHtml(c.comment_text.substring(0, 50))}${c.comment_text.length > 50 ? '...' : ''}</option>`).join('');
}

function updateConditionNotePresets() {
    const select = document.getElementById('presetConditionNote');
    if (!select) return;

    select.innerHTML = '<option value="">-- Select Preset --</option>' +
        presetConditionNotes.map(c => `<option value="${escapeAttr(c.comment_text)}">${escapeHtml(c.comment_text.substring(0, 50))}${c.comment_text.length > 50 ? '...' : ''}</option>`).join('');
}

function applyPreset(selectEl, targetId) {
    const textarea = document.getElementById(targetId);
    if (textarea && selectEl.value) {
        textarea.value = selectEl.value;
    }
    selectEl.value = ''; // Reset select
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function escapeAttr(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// Show/hide condition note field and update presets based on decision
document.querySelectorAll('input[name="decision"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('conditionGroup').style.display =
            this.value === 'ACK_WITH_CONDITION' ? 'block' : 'none';
        updateCommentPresets(); // Update presets based on selected decision
    });
});

function submitWO() {
    if (!confirm('Submit this Work Order for review?')) return;

    const formData = new FormData();
    formData.append('action', 'submit');
    formData.append('id', woId);

    fetch('api/wo.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    });
}

function submitReview(e) {
    e.preventDefault();
    const form = document.getElementById('reviewForm');
    const formData = new FormData(form);
    formData.append('action', 'review');
    formData.append('wo_id', woId);

    const btn = document.getElementById('reviewBtn');
    showLoading(btn);

    fetch('api/wo.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        hideLoading(btn);
        if (data.success) {
            showAlert(data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    });
}

function uploadFile(e) {
    e.preventDefault();
    const form = document.getElementById('uploadForm');
    const formData = new FormData(form);
    formData.append('action', 'upload');
    formData.append('wo_id', woId);

    fetch('api/wo.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
            showAlert(data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    });
}

function deleteWO() {
    if (!confirm('Are you sure you want to delete this Work Order?\n\nThis action cannot be undone!')) return;
    if (!confirm('Please confirm again: Delete Work Order <?= $wo['wo_no'] ?>?')) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', woId);

    fetch('api/wo.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.href = 'wo_list.php', 1500);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(err => {
        showAlert('Error: ' + err.message, 'danger');
    });
}

function withdrawWO() {
    if (!confirm('Withdraw this Work Order back to Draft status?\n\nAll review records will be deleted.')) return;

    const formData = new FormData();
    formData.append('action', 'withdraw');
    formData.append('id', woId);

    fetch('api/wo.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(err => {
        showAlert('Error: ' + err.message, 'danger');
    });
}

function revertToDraft() {
    if (!confirm('Revert this Work Order back to Draft status?\n\nAll review records will be deleted.')) return;

    const formData = new FormData();
    formData.append('action', 'revert_to_draft');
    formData.append('id', woId);

    fetch('api/wo.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(err => {
        showAlert('Error: ' + err.message, 'danger');
    });
}
</script>

<?php require_once __DIR__ . '/includes/wo_footer.php'; ?>
