<?php
/**
 * LSB Work Order System - Department Configuration
 */

$pageTitle = 'Department Configuration';
require_once __DIR__ . '/includes/wo_functions.php';
require_once __DIR__ . '/includes/wo_header.php';
wo_require_admin();

$pdo = wo_get_db();
$stmt = $pdo->query("SELECT * FROM lsb_wo_dept_config ORDER BY sort_order");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-building me-2"></i>Department Configuration</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#deptModal" onclick="openCreate()">
        <i class="fas fa-plus me-1"></i>Add Department
    </button>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-info-circle me-2"></i>Department Permissions
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <ul class="mb-0">
                    <li><strong>Can Approve:</strong> Department members can review and approve/reject WO requests</li>
                    <li><strong>Can Delete:</strong> Department members can delete WO requests</li>
                </ul>
            </div>
            <div class="col-md-6">
                <ul class="mb-0">
                    <li><strong>Operation (OPT):</strong> Creates WO requests, can resubmit after rejection</li>
                    <li><strong>Administrator (ADM):</strong> Can delete requests, system management</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Dept Code</th>
                    <th>Department Name</th>
                    <th>Can Approve</th>
                    <th>Can Delete</th>
                    <th>Status</th>
                    <th>Members</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $d):
                    // Count members
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lsb_wo_users WHERE dept_code = ? AND is_active = 1");
                    $stmt->execute([$d['dept_code']]);
                    $memberCount = $stmt->fetchColumn();
                ?>
                <tr>
                    <td><?= $d['sort_order'] ?></td>
                    <td><code><?= htmlspecialchars($d['dept_code']) ?></code></td>
                    <td><?= htmlspecialchars($d['dept_name']) ?></td>
                    <td>
                        <?= $d['can_approve'] ? '<span class="badge bg-success"><i class="fas fa-check"></i> Yes</span>' : '<span class="badge bg-secondary">No</span>' ?>
                    </td>
                    <td>
                        <?= $d['can_delete'] ? '<span class="badge bg-danger"><i class="fas fa-trash"></i> Yes</span>' : '<span class="badge bg-secondary">No</span>' ?>
                    </td>
                    <td>
                        <?= $d['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
                    </td>
                    <td>
                        <span class="badge bg-info"><?= $memberCount ?> members</span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editDept(<?= htmlspecialchars(json_encode($d)) ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteDept('<?= $d['dept_code'] ?>')" <?= $memberCount > 0 ? 'disabled title="Cannot delete: has members"' : '' ?>>
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Department Modal -->
<div class="modal fade" id="deptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="deptForm" onsubmit="saveDept(event)">
                <div class="modal-body">
                    <input type="hidden" id="deptId" name="id">

                    <div class="mb-3">
                        <label class="form-label">Department Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="deptCode" name="dept_code" required
                               placeholder="e.g., OPT, EXEC, ACC, ENG, ADM" style="text-transform: uppercase;">
                        <small class="text-muted">Short code for the department (max 30 chars)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Department Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="deptName" name="dept_name" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="sortOrder" name="sort_order" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="isActive" name="is_active" value="1" checked>
                                <label class="form-check-label" for="isActive">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light">
                        <div class="card-header">Permissions</div>
                        <div class="card-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="canApprove" name="can_approve" value="1">
                                <label class="form-check-label" for="canApprove">
                                    <strong>Can Approve</strong> - Members can review and approve/reject WO requests
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="canDelete" name="can_delete" value="1">
                                <label class="form-check-label" for="canDelete">
                                    <strong>Can Delete</strong> - Members can delete WO requests
                                </label>
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
let deptModal;
let isEdit = false;

document.addEventListener('DOMContentLoaded', function() {
    deptModal = new bootstrap.Modal(document.getElementById('deptModal'));
});

function openCreate() {
    isEdit = false;
    document.getElementById('modalTitle').textContent = 'Add Department';
    document.getElementById('deptForm').reset();
    document.getElementById('deptId').value = '';
    document.getElementById('deptCode').disabled = false;
    document.getElementById('isActive').checked = true;
    document.getElementById('canApprove').checked = false;
    document.getElementById('canDelete').checked = false;
}

function editDept(data) {
    isEdit = true;
    document.getElementById('modalTitle').textContent = 'Edit Department';
    document.getElementById('deptId').value = data.id;
    document.getElementById('deptCode').value = data.dept_code;
    document.getElementById('deptCode').disabled = true;
    document.getElementById('deptName').value = data.dept_name;
    document.getElementById('sortOrder').value = data.sort_order;
    document.getElementById('isActive').checked = data.is_active == 1;
    document.getElementById('canApprove').checked = data.can_approve == 1;
    document.getElementById('canDelete').checked = data.can_delete == 1;
    deptModal.show();
}

function saveDept(e) {
    e.preventDefault();
    const form = document.getElementById('deptForm');
    const formData = new FormData(form);

    // Handle checkboxes
    if (!document.getElementById('isActive').checked) {
        formData.set('is_active', '0');
    }
    if (!document.getElementById('canApprove').checked) {
        formData.set('can_approve', '0');
    }
    if (!document.getElementById('canDelete').checked) {
        formData.set('can_delete', '0');
    }

    // If editing, add dept_code manually (disabled inputs don't submit)
    if (isEdit) {
        formData.append('dept_code', document.getElementById('deptCode').value);
    }

    const btn = document.getElementById('saveBtn');
    showLoading(btn);

    fetch('api/dept_config.php?action=' + (isEdit ? 'update' : 'create'), {
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
            deptModal.hide();
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Operation failed', 'danger');
        }
    })
    .catch(err => {
        hideLoading(btn);
        console.error('Save department error:', err);
        showAlert('Error: ' + err.message, 'danger');
    });
}

function deleteDept(deptCode) {
    if (!confirm('Are you sure you want to delete this department?')) return;

    fetch('api/dept_config.php?action=delete&dept_code=' + encodeURIComponent(deptCode), {
        method: 'POST'
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
