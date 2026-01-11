<?php
/**
 * LSB Work Order System - Dashboard
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/wo_functions.php';
require_once __DIR__ . '/includes/wo_header.php';

// Use wo_get_current_user() to support demo department switch
$userId = $currentUser['id'];
$userDeptCode = $currentUser['dept_code'] ?? '';
$canApprove = !empty($currentUser['can_approve']);
$isAdmin = $currentUser['is_admin'] || ($userDeptCode === 'ADM');
$pdo = wo_get_db();

// Stats
$stats = [];

// WO stats - Admin sees all, others see only their own
if ($isAdmin) {
    // Admin sees all WOs
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as cnt
        FROM lsb_wo_header
        GROUP BY status
    ");
} else {
    // Regular users see only their own WOs
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as cnt
        FROM lsb_wo_header
        WHERE requester_id = ?
        GROUP BY status
    ");
    $stmt->execute([$userId]);
}
$myStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stats['my_draft'] = $myStats['DRAFT'] ?? 0;
$stats['my_submitted'] = $myStats['SUBMITTED'] ?? 0;
$stats['my_done'] = $myStats['DONE'] ?? 0;
$stats['my_rejected'] = $myStats['REJECTED'] ?? 0;
$stats['my_total'] = array_sum($myStats);

// 待我部门审阅
$stats['pending_review'] = 0;
if ($canApprove && $userDeptCode) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM lsb_wo_review r
        JOIN lsb_wo_header h ON r.wo_id = h.id
        WHERE r.reviewer_dept = ?
          AND h.status = 'SUBMITTED'
          AND r.decision = 'PENDING'
    ");
    $stmt->execute([$userDeptCode]);
    $stats['pending_review'] = $stmt->fetchColumn();
}

// Recent WOs - Admin sees all, others see only their own
if ($isAdmin) {
    $stmt = $pdo->query("
        SELECT * FROM lsb_wo_header
        ORDER BY updated_at DESC
        LIMIT 5
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM lsb_wo_header
        WHERE requester_id = ?
        ORDER BY updated_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
}
$recentWOs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pending reviews for my department
$pendingReviews = [];
if ($canApprove && $userDeptCode) {
    $stmt = $pdo->prepare("
        SELECT h.*, r.reviewer_dept, r.decision
        FROM lsb_wo_header h
        JOIN lsb_wo_review r ON h.id = r.wo_id
        WHERE r.reviewer_dept = ?
          AND h.status = 'SUBMITTED'
          AND r.decision = 'PENDING'
        ORDER BY h.submitted_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userDeptCode]);
    $pendingReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="page-header">
    <h1><i class="fas fa-home me-2"></i>Dashboard</h1>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-warning bg-opacity-10 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Pending Review</h6>
                        <h2 class="mb-0 text-warning"><?= $stats['pending_review'] ?></h2>
                    </div>
                    <div class="fs-1 text-warning opacity-50">
                        <i class="fas fa-inbox"></i>
                    </div>
                </div>
            </div>
            <a href="wo_inbox.php" class="card-footer bg-transparent text-warning text-decoration-none">
                View Inbox <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-secondary bg-opacity-10 border-secondary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">My Drafts</h6>
                        <h2 class="mb-0 text-secondary"><?= $stats['my_draft'] ?></h2>
                    </div>
                    <div class="fs-1 text-secondary opacity-50">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>
            <a href="wo_list.php?status=DRAFT" class="card-footer bg-transparent text-secondary text-decoration-none">
                View Drafts <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-primary bg-opacity-10 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Submitted</h6>
                        <h2 class="mb-0 text-primary"><?= $stats['my_submitted'] ?></h2>
                    </div>
                    <div class="fs-1 text-primary opacity-50">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                </div>
            </div>
            <a href="wo_list.php?status=SUBMITTED" class="card-footer bg-transparent text-primary text-decoration-none">
                View Submitted <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-success bg-opacity-10 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Completed</h6>
                        <h2 class="mb-0 text-success"><?= $stats['my_done'] ?></h2>
                    </div>
                    <div class="fs-1 text-success opacity-50">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <a href="wo_list.php?status=DONE" class="card-footer bg-transparent text-success text-decoration-none">
                View Completed <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Pending Reviews -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-inbox me-2 text-warning"></i>Pending Reviews</span>
                <a href="wo_inbox.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pendingReviews)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                    <p>No pending reviews</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($pendingReviews as $wo): ?>
                    <a href="wo_view.php?id=<?= $wo['id'] ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?= htmlspecialchars($wo['wo_no']) ?></strong>
                                <p class="mb-0 text-muted small">
                                    <?= htmlspecialchars($wo['title'] ?: $wo['project_name'] ?: 'No title') ?>
                                </p>
                            </div>
                            <small class="text-muted"><?= date('M j', strtotime($wo['submitted_at'])) ?></small>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Work Orders -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-clock me-2 text-primary"></i>Recent Work Orders</span>
                <a href="wo_list.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentWOs)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-file-alt fa-3x mb-3 opacity-50"></i>
                    <p>No work orders yet</p>
                    <a href="wo_create.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Create First WO
                    </a>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentWOs as $wo): ?>
                    <a href="wo_view.php?id=<?= $wo['id'] ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?= htmlspecialchars($wo['wo_no']) ?></strong>
                                <?= wo_status_badge($wo['status']) ?>
                                <p class="mb-0 text-muted small">
                                    <?= htmlspecialchars($wo['title'] ?: $wo['project_name'] ?: 'No title') ?>
                                </p>
                            </div>
                            <small class="text-muted"><?= date('M j', strtotime($wo['updated_at'])) ?></small>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mt-4">
    <div class="card-header">
        <i class="fas fa-bolt me-2"></i>Quick Actions
    </div>
    <div class="card-body">
        <a href="wo_create.php" class="btn btn-sm btn-primary me-2">
            <i class="fas fa-plus me-1"></i>Create New
        </a>
        <a href="wo_list.php" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-list me-1"></i>View All
        </a>
        <?php if ($stats['pending_review'] > 0): ?>
        <a href="wo_inbox.php" class="btn btn-sm btn-warning">
            <i class="fas fa-inbox me-1"></i>Review (<?= $stats['pending_review'] ?>)
        </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/wo_footer.php'; ?>
