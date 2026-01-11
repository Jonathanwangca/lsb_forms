<?php
/**
 * LSB Work Order System - Reviewer Configuration
 */

$pageTitle = 'Reviewer Configuration';
require_once __DIR__ . '/includes/wo_functions.php';
require_once __DIR__ . '/includes/wo_header.php';
wo_require_admin();

$pdo = wo_get_db();
$stmt = $pdo->query("SELECT * FROM lsb_wo_reviewer_config ORDER BY sort_order");
$reviewers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-user-check me-2"></i>Reviewer Configuration</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reviewerModal" onclick="openCreate()">
        <i class="fas fa-plus me-1"></i>Add Reviewer
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Role Code</th>
                    <th>Role Name</th>
                    <th>Reviewer</th>
                    <th>Email</th>
                    <th>Required</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviewers as $r): ?>
                <tr>
                    <td><?= $r['sort_order'] ?></td>
                    <td><code><?= htmlspecialchars($r['role_code']) ?></code></td>
                    <td>
                        <?= htmlspecialchars($r['role_name']) ?>
                        <?php if ($r['role_name_cn']): ?>
                        <br><small class="text-muted"><?= htmlspecialchars($r['role_name_cn']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($r['reviewer_name']) ?></td>
                    <td><?= htmlspecialchars($r['reviewer_email']) ?></td>
                    <td>
                        <?= $r['is_required'] ? '<span class="badge bg-primary">Required</span>' : '<span class="badge bg-secondary">Optional</span>' ?>
                    </td>
                    <td>
                        <?= $r['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editReviewer(<?= htmlspecialchars(json_encode($r)) ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reviewer Modal -->
<div class="modal fade" id="reviewerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Reviewer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reviewerForm" onsubmit="saveReviewer(event)">
                <div class="modal-body">
                    <input type="hidden" id="reviewerId" name="id">

                    <div class="mb-3">
                        <label class="form-label">Role Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="roleCode" name="role_code" required
                               placeholder="e.g., PM, CFO, GM">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role Name (EN) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="roleName" name="role_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role Name (CN)</label>
                            <input type="text" class="form-control" id="roleNameCn" name="role_name_cn">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reviewer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="reviewerName" name="reviewer_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reviewer Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="reviewerEmail" name="reviewer_email" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="sortOrder" name="sort_order" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="isRequired" name="is_required" value="1" checked>
                                <label class="form-check-label" for="isRequired">Required Reviewer</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="isActive" name="is_active" value="1" checked>
                                <label class="form-check-label" for="isActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let reviewerModal;
let isEdit = false;

document.addEventListener('DOMContentLoaded', function() {
    reviewerModal = new bootstrap.Modal(document.getElementById('reviewerModal'));
});

function openCreate() {
    isEdit = false;
    document.getElementById('modalTitle').textContent = 'Add Reviewer';
    document.getElementById('reviewerForm').reset();
    document.getElementById('reviewerId').value = '';
    document.getElementById('roleCode').disabled = false;
    document.getElementById('isRequired').checked = true;
    document.getElementById('isActive').checked = true;
}

function editReviewer(data) {
    isEdit = true;
    document.getElementById('modalTitle').textContent = 'Edit Reviewer';
    document.getElementById('reviewerId').value = data.id;
    document.getElementById('roleCode').value = data.role_code;
    document.getElementById('roleCode').disabled = true;
    document.getElementById('roleName').value = data.role_name;
    document.getElementById('roleNameCn').value = data.role_name_cn || '';
    document.getElementById('reviewerName').value = data.reviewer_name;
    document.getElementById('reviewerEmail').value = data.reviewer_email;
    document.getElementById('sortOrder').value = data.sort_order;
    document.getElementById('isRequired').checked = data.is_required == 1;
    document.getElementById('isActive').checked = data.is_active == 1;
    reviewerModal.show();
}

function saveReviewer(e) {
    e.preventDefault();
    const form = document.getElementById('reviewerForm');
    const formData = new FormData(form);

    // 手动添加checkbox值
    if (!document.getElementById('isRequired').checked) {
        formData.set('is_required', '0');
    }
    if (!document.getElementById('isActive').checked) {
        formData.set('is_active', '0');
    }

    // 如果是编辑模式，role_code被禁用不会提交，需要手动添加
    if (isEdit) {
        formData.append('role_code', document.getElementById('roleCode').value);
    }

    const btn = document.getElementById('saveBtn');
    showLoading(btn);

    fetch('api/reviewer_config.php?action=' + (isEdit ? 'update' : 'create'), {
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
            reviewerModal.hide();
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Operation failed', 'danger');
        }
    })
    .catch(err => {
        hideLoading(btn);
        console.error('Save reviewer error:', err);
        showAlert('Error: ' + err.message, 'danger');
    });
}
</script>

<?php require_once __DIR__ . '/includes/wo_footer.php'; ?>
