<?php
/**
 * LSB Work Order System - Review Inbox (Department-Based)
 */

$pageTitle = 'Inbox';
require_once __DIR__ . '/includes/wo_functions.php';
require_once __DIR__ . '/includes/wo_header.php';

$userDeptCode = $currentUser['dept_code'] ?? '';
$canApprove = !empty($currentUser['can_approve']);
$pdo = wo_get_db();

$pendingItems = [];
$reviewedItems = [];

if ($canApprove && $userDeptCode) {
    // Get pending review list - department based
    $stmt = $pdo->prepare("
        SELECT h.*, r.decision, r.reviewer_dept, d.dept_name
        FROM lsb_wo_header h
        JOIN lsb_wo_review r ON h.id = r.wo_id
        LEFT JOIN lsb_wo_dept_config d ON r.reviewer_dept = d.dept_code
        WHERE r.reviewer_dept = ?
          AND h.status = 'SUBMITTED'
          AND r.decision = 'PENDING'
        ORDER BY h.submitted_at DESC
    ");
    $stmt->execute([$userDeptCode]);
    $pendingItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get reviewed list - department has reviewed
    $stmt = $pdo->prepare("
        SELECT h.*, r.decision, r.reviewer_dept, r.reviewer_name, r.reviewed_at, d.dept_name
        FROM lsb_wo_header h
        JOIN lsb_wo_review r ON h.id = r.wo_id
        LEFT JOIN lsb_wo_dept_config d ON r.reviewer_dept = d.dept_code
        WHERE r.reviewer_dept = ?
          AND r.decision != 'PENDING'
        ORDER BY r.reviewed_at DESC
        LIMIT 20
    ");
    $stmt->execute([$userDeptCode]);
    $reviewedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-inbox me-2"></i>Review Inbox</h1>
    <?php if ($canApprove): ?>
    <span class="badge bg-info fs-6">
        <i class="fas fa-building me-1"></i><?= htmlspecialchars($currentUser['dept_name'] ?? $userDeptCode) ?> Department
    </span>
    <?php endif; ?>
</div>

<?php if (!$canApprove): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    Your department (<?= htmlspecialchars($currentUser['dept_name'] ?? $userDeptCode ?: 'Not assigned') ?>) does not have approval privileges.
    Only departments with approval rights can review work orders.
</div>
<?php endif; ?>

<!-- Pending Reviews -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <i class="fas fa-clock me-2"></i>Pending Reviews for <?= htmlspecialchars($currentUser['dept_name'] ?? $userDeptCode) ?>
        <?php if (count($pendingItems) > 0): ?>
        <span class="badge bg-dark"><?= count($pendingItems) ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (!$canApprove): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-ban fa-3x mb-3 opacity-50"></i>
            <p>Your department does not have approval privileges.</p>
        </div>
        <?php elseif (empty($pendingItems)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-check-circle fa-3x mb-3 opacity-50"></i>
            <p>No pending reviews. You're all caught up!</p>
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>WO No.</th>
                    <th>Title / Project</th>
                    <th>Vendor</th>
                    <th>Amount</th>
                    <th>Requester</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingItems as $item): ?>
                <tr>
                    <td>
                        <a href="wo_view.php?id=<?= $item['id'] ?>" class="fw-bold text-decoration-none">
                            <?= htmlspecialchars($item['wo_no']) ?>
                        </a>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($item['title'] ?: '-') ?></div>
                        <small class="text-muted"><?= htmlspecialchars($item['project_name'] ?: '') ?></small>
                    </td>
                    <td><?= htmlspecialchars($item['vendor_name'] ?: '-') ?></td>
                    <td><?= wo_format_amount($item['original_amount']) ?></td>
                    <td><?= htmlspecialchars($item['requester_name']) ?></td>
                    <td><?= date('M j, Y', strtotime($item['submitted_at'])) ?></td>
                    <td>
                        <a href="wo_view.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-warning">
                            <i class="fas fa-clipboard-check me-1"></i>Review
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php if ($canApprove): ?>
<!-- Recently Reviewed -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-history me-2"></i>Recently Reviewed
    </div>
    <div class="card-body p-0">
        <?php if (empty($reviewedItems)): ?>
        <div class="text-center py-5 text-muted">
            <p class="mb-0">No review history yet.</p>
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>WO No.</th>
                    <th>Title</th>
                    <th>Dept Decision</th>
                    <th>Reviewed By</th>
                    <th>WO Status</th>
                    <th>Reviewed At</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviewedItems as $item): ?>
                <tr>
                    <td>
                        <a href="wo_view.php?id=<?= $item['id'] ?>" class="text-decoration-none">
                            <?= htmlspecialchars($item['wo_no']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($item['title'] ?: $item['project_name'] ?: '-') ?></td>
                    <td><?= wo_decision_badge($item['decision']) ?></td>
                    <td><?= htmlspecialchars($item['reviewer_name'] ?: '-') ?></td>
                    <td><?= wo_status_badge($item['status']) ?></td>
                    <td><?= date('M j, Y H:i', strtotime($item['reviewed_at'])) ?></td>
                    <td>
                        <a href="wo_view.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/wo_footer.php'; ?>
