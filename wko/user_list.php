<?php
/**
 * LSB Work Order System - User Management
 */

$pageTitle = 'User Management';
require_once __DIR__ . '/includes/wo_header.php';
wo_require_admin();

// Get department list
$pdo = wo_get_db();
$stmt = $pdo->query("SELECT dept_code, dept_name FROM lsb_wo_dept_config WHERE is_active = 1 ORDER BY sort_order");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-users me-2"></i>User Management</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openCreateModal()">
        <i class="fas fa-plus me-1"></i>Add User
    </button>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All Users</span>
        <input type="text" class="form-control form-control-sm" style="width: 250px;"
               placeholder="Search users..." id="searchInput" onkeyup="loadUsers()">
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Title</th>
                    <th>Department</th>
                    <th>Admin</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="userTable">
                <tr><td colspan="8" class="text-center py-4">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm" onsubmit="saveUser(event)">
                <div class="modal-body">
                    <input type="hidden" id="userId" name="id">

                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="userEmail" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="userName" name="name" required>
                    </div>

                    <div class="mb-3" id="passwordGroup">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="userPassword" name="password" minlength="8">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('userPassword', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" id="userTitle" name="title" placeholder="e.g., Project Manager, Controller">
                        <small class="text-muted">Job title (optional)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Department <span class="text-danger">*</span></label>
                        <select class="form-select" id="userDeptCode" name="dept_code" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept['dept_code']) ?>">
                                <?= htmlspecialchars($dept['dept_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="userIsAdmin" name="is_admin" value="1">
                        <label class="form-check-label" for="userIsAdmin">
                            Administrator
                        </label>
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

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="resetPasswordForm" onsubmit="resetPassword(event)">
                <div class="modal-body">
                    <input type="hidden" id="resetUserId">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="newPassword" required minlength="8">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('newPassword', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let userModal, resetModal;
let isEdit = false;

document.addEventListener('DOMContentLoaded', function() {
    userModal = new bootstrap.Modal(document.getElementById('userModal'));
    resetModal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    loadUsers();
});

function loadUsers() {
    const search = document.getElementById('searchInput').value;
    fetch(`api/user.php?action=list&search=${encodeURIComponent(search)}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderUsers(data.data.users);
            }
        });
}

function renderUsers(users) {
    const tbody = document.getElementById('userTable');
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No users found</td></tr>';
        return;
    }

    tbody.innerHTML = users.map(user => `
        <tr>
            <td><strong>${escapeHtml(user.name)}</strong></td>
            <td>${escapeHtml(user.email)}</td>
            <td>${escapeHtml(user.title || '-')}</td>
            <td>${user.dept_code ? `<span class="badge bg-info">${escapeHtml(user.dept_code)}</span> ${escapeHtml(user.dept_name || '')}` : '-'}</td>
            <td>${user.is_admin == 1 ? '<span class="badge bg-warning">Yes</span>' : '-'}</td>
            <td>${user.is_active == 1
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Disabled</span>'}</td>
            <td>${user.last_login ? formatDate(user.last_login) : 'Never'}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editUser(${user.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-warning" onclick="openResetPassword(${user.id})" title="Reset Password">
                    <i class="fas fa-key"></i>
                </button>
                <button class="btn btn-sm btn-outline-${user.is_active == 1 ? 'danger' : 'success'}"
                        onclick="toggleActive(${user.id})" title="${user.is_active == 1 ? 'Disable' : 'Enable'}">
                    <i class="fas fa-${user.is_active == 1 ? 'ban' : 'check'}"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function openCreateModal() {
    isEdit = false;
    document.getElementById('modalTitle').textContent = 'Add User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userEmail').disabled = false;
    document.getElementById('passwordGroup').style.display = 'block';
    document.getElementById('userPassword').required = true;
    document.getElementById('userDeptCode').required = true;
}

function editUser(id) {
    isEdit = true;
    fetch(`api/user.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const user = data.data.user;
                document.getElementById('modalTitle').textContent = 'Edit User';
                document.getElementById('userId').value = user.id;
                document.getElementById('userEmail').value = user.email;
                document.getElementById('userEmail').disabled = true;
                document.getElementById('userName').value = user.name;
                document.getElementById('userTitle').value = user.title || '';
                document.getElementById('userDeptCode').value = user.dept_code || '';
                document.getElementById('userIsAdmin').checked = user.is_admin == 1;
                document.getElementById('passwordGroup').style.display = 'none';
                document.getElementById('userPassword').required = false;
                userModal.show();
            }
        });
}

function saveUser(e) {
    e.preventDefault();

    const form = document.getElementById('userForm');
    const formData = new FormData(form);
    formData.append('action', isEdit ? 'update' : 'create');

    // If editing, email field is disabled and won't submit, need to add manually
    if (isEdit) {
        formData.append('email', document.getElementById('userEmail').value);
    }

    // Debug: log form data
    console.log('Submitting user form:', isEdit ? 'update' : 'create');
    for (let [key, value] of formData.entries()) {
        console.log('  ' + key + ':', value);
    }

    const btn = document.getElementById('saveBtn');
    showLoading(btn);

    fetch('api/user.php', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        console.log('Response status:', r.status);
        if (!r.ok) {
            return r.text().then(text => {
                console.error('Error response:', text);
                throw new Error('Server error (' + r.status + '): ' + text.substring(0, 200));
            });
        }
        return r.json();
    })
    .then(data => {
        console.log('Response data:', data);
        hideLoading(btn);
        if (data.success) {
            userModal.hide();
            loadUsers();
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message || 'Operation failed', 'danger');
        }
    })
    .catch(err => {
        hideLoading(btn);
        console.error('Save user error:', err);
        showAlert('Error: ' + err.message, 'danger');
    });
}

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

function toggleActive(id) {
    if (!confirm('Are you sure you want to change this user\'s status?')) return;

    const formData = new FormData();
    formData.append('action', 'toggle_active');
    formData.append('id', id);

    fetch('api/user.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            loadUsers();
            showAlert(data.message);
        } else {
            showAlert(data.message, 'danger');
        }
    });
}

function openResetPassword(id) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('newPassword').value = '';
    resetModal.show();
}

function resetPassword(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('action', 'reset_password');
    formData.append('id', document.getElementById('resetUserId').value);
    formData.append('new_password', document.getElementById('newPassword').value);

    fetch('api/user.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            resetModal.hide();
            showAlert(data.message);
        } else {
            showAlert(data.message, 'danger');
        }
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-CA') + ' ' + d.toLocaleTimeString('en-CA', {hour: '2-digit', minute: '2-digit'});
}
</script>

<?php require_once __DIR__ . '/includes/wo_footer.php'; ?>
