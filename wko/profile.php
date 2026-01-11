<?php
/**
 * LSB Work Order System - User Profile
 */

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/wo_header.php';

$user = wo_get_current_user();
?>

<div class="page-header">
    <h1><i class="fas fa-user me-2"></i>My Profile</h1>
</div>

<div class="row">
    <div class="col-lg-6">
        <!-- Profile Info -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-id-card me-2"></i>Profile Information
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Name</label>
                    <p class="fs-5 mb-0"><?= htmlspecialchars($user['name']) ?></p>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Email</label>
                    <p class="fs-5 mb-0"><?= htmlspecialchars($user['email']) ?></p>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Role</label>
                    <p class="mb-0">
                        <?php if ($user['role']): ?>
                        <span class="badge bg-info"><?= htmlspecialchars($user['role']) ?></span>
                        <?php else: ?>
                        <span class="text-muted">Not assigned</span>
                        <?php endif; ?>
                        <?php if ($user['is_admin']): ?>
                        <span class="badge bg-warning ms-1">Administrator</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="mb-0">
                    <label class="form-label text-muted">Department</label>
                    <p class="mb-0"><?= htmlspecialchars($user['department'] ?: 'Not specified') ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <!-- Change Password -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-key me-2"></i>Change Password
            </div>
            <div class="card-body">
                <form id="passwordForm" onsubmit="changePassword(event)">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required minlength="8">
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary" id="changeBtn">
                        <i class="fas fa-save me-1"></i>Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function changePassword(e) {
    e.preventDefault();
    const form = document.getElementById('passwordForm');
    const formData = new FormData(form);

    // Validate passwords match
    if (formData.get('new_password') !== formData.get('confirm_password')) {
        showAlert('New passwords do not match', 'danger');
        return;
    }

    formData.append('action', 'change_password');

    const btn = document.getElementById('changeBtn');
    showLoading(btn);

    fetch('api/auth.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        hideLoading(btn);
        if (data.success) {
            showAlert(data.message);
            form.reset();
        } else {
            showAlert(data.message, 'danger');
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/wo_footer.php'; ?>
