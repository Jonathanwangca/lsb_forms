<?php
/**
 * LSB Work Order System - Header Template
 */

require_once __DIR__ . '/wo_auth.php';
wo_require_login();

$currentUser = wo_get_current_user();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Department switch handling moved to wo_auth.php, executed before permission check

// Get all departments (for department switcher display)
$allDepts = [];
$pdo = wo_get_db();
$stmt = $pdo->query("SELECT dept_code, dept_name, can_approve, can_delete FROM lsb_wo_dept_config WHERE is_active = 1 ORDER BY sort_order");
$allDepts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Current demo department
$demoDept = $_SESSION['demo_dept'] ?? null;

// Get pending review count - department based
$inboxCount = 0;
if ($currentUser && !empty($currentUser['dept_code']) && !empty($currentUser['can_approve'])) {
    $pdo = wo_get_db();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM lsb_wo_review r
        JOIN lsb_wo_header h ON r.wo_id = h.id
        WHERE r.reviewer_dept = ?
          AND h.status = 'SUBMITTED'
          AND r.decision = 'PENDING'
    ");
    $stmt->execute([$currentUser['dept_code']]);
    $inboxCount = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Work Order System' ?> - LSB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a365d;
            --secondary-color: #2d5a87;
        }
        body {
            background-color: #f5f7fa;
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
        }
        .sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            width: 250px;
            height: calc(100vh - 56px);
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            padding: 20px 0;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            color: #333;
            padding: 12px 20px;
            border-radius: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar .nav-link:hover {
            background: #f0f4f8;
            color: var(--primary-color);
        }
        .sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }
        .sidebar-section {
            padding: 10px 20px;
            font-size: 12px;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px 30px;
            min-height: calc(100vh - 56px);
        }
        .page-header {
            margin-bottom: 25px;
        }
        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
            font-weight: 600;
        }
        .table th {
            font-weight: 600;
            color: #555;
            border-bottom-width: 1px;
        }
        .badge-count {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 10px;
        }
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
                z-index: 1000;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
    <?php if (isset($extraStyles)) echo $extraStyles; ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white d-lg-none me-2" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-file-contract me-2"></i>LSB Work Order
            </a>
            <div class="ms-auto d-flex align-items-center">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <?= htmlspecialchars($currentUser['name']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-section">Main Menu</div>
        <nav class="nav flex-column">
            <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link <?= $currentPage === 'wo_inbox' ? 'active' : '' ?>" href="wo_inbox.php">
                <i class="fas fa-inbox"></i> Inbox
                <?php if ($inboxCount > 0): ?>
                <span class="badge bg-danger badge-count ms-auto"><?= $inboxCount ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link <?= $currentPage === 'wo_list' ? 'active' : '' ?>" href="wo_list.php">
                <i class="fas fa-list"></i> My Work Orders
            </a>
            <a class="nav-link <?= $currentPage === 'wo_create' ? 'active' : '' ?>" href="wo_create.php">
                <i class="fas fa-plus-circle"></i> Create New WO
            </a>
        </nav>

        <?php if ($currentUser['is_admin']): ?>
        <div class="sidebar-section mt-4">Administration</div>
        <nav class="nav flex-column">
            <a class="nav-link <?= $currentPage === 'user_list' ? 'active' : '' ?>" href="user_list.php">
                <i class="fas fa-users"></i> User Management
            </a>
            <a class="nav-link <?= $currentPage === 'dept_config' ? 'active' : '' ?>" href="dept_config.php">
                <i class="fas fa-building"></i> Department Config
            </a>
            <a class="nav-link <?= $currentPage === 'preset_comments' ? 'active' : '' ?>" href="preset_comments.php">
                <i class="fas fa-comment-dots"></i> Preset Comments
            </a>
        </nav>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Demo Department Switcher -->
        <div class="demo-role-switcher mb-3 p-2 bg-warning bg-opacity-25 rounded d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center flex-wrap">
                <i class="fas fa-building me-2 text-warning"></i>
                <span class="me-2 fw-bold text-warning">Demo Mode:</span>
                <div class="btn-group btn-group-sm flex-wrap" role="group">
                    <a href="?switch_dept=reset" class="btn <?= !$demoDept ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['wo_user']['name'] ?? 'Original') ?>
                    </a>
                    <?php foreach ($allDepts as $dept): ?>
                    <a href="?switch_dept=<?= $dept['dept_code'] ?>"
                       class="btn <?= $demoDept === $dept['dept_code'] ? 'btn-primary' : 'btn-outline-secondary' ?>"
                       title="<?= htmlspecialchars($dept['dept_name']) ?><?php if ($dept['can_approve']): ?> - Can Approve<?php endif; ?><?php if ($dept['can_delete']): ?> - Can Delete<?php endif; ?>">
                        <?= htmlspecialchars($dept['dept_code']) ?>
                        <?php if ($dept['can_approve']): ?><i class="fas fa-check-circle text-success ms-1"></i><?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>Switch department (first user). <i class="fas fa-check-circle text-success"></i> = Can Approve
            </small>
        </div>
