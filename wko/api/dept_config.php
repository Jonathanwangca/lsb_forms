<?php
/**
 * LSB Work Order System - Department Configuration API
 */

require_once dirname(__DIR__) . '/includes/wo_auth.php';
wo_require_admin();

$pdo = wo_get_db();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            handleList();
            break;
        case 'get':
            handleGet();
            break;
        case 'create':
            handleCreate();
            break;
        case 'update':
            handleUpdate();
            break;
        case 'delete':
            handleDelete();
            break;
        default:
            wo_error_response('Invalid action', 400);
    }
} catch (Exception $e) {
    wo_error_response('Error: ' . $e->getMessage(), 500);
}

function handleList() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM lsb_wo_dept_config ORDER BY sort_order");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    wo_json_response(true, '', ['departments' => $departments]);
}

function handleGet() {
    global $pdo;
    $deptCode = $_GET['dept_code'] ?? '';
    if (!$deptCode) {
        wo_error_response('Department code required');
    }

    $stmt = $pdo->prepare("SELECT * FROM lsb_wo_dept_config WHERE dept_code = ?");
    $stmt->execute([$deptCode]);
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dept) {
        wo_error_response('Department not found', 404);
    }

    wo_json_response(true, '', ['department' => $dept]);
}

function handleCreate() {
    global $pdo;

    $deptCode = strtoupper(trim($_POST['dept_code'] ?? ''));
    $deptName = trim($_POST['dept_name'] ?? '');

    if (!$deptCode || !$deptName) {
        wo_error_response('Department code and name are required');
    }

    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM lsb_wo_dept_config WHERE dept_code = ?");
    $stmt->execute([$deptCode]);
    if ($stmt->fetch()) {
        wo_error_response('Department code already exists');
    }

    $stmt = $pdo->prepare("
        INSERT INTO lsb_wo_dept_config (dept_code, dept_name, can_approve, can_delete, sort_order, is_active)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $deptCode,
        $deptName,
        isset($_POST['can_approve']) ? intval($_POST['can_approve']) : 0,
        isset($_POST['can_delete']) ? intval($_POST['can_delete']) : 0,
        intval($_POST['sort_order'] ?? 0),
        isset($_POST['is_active']) ? intval($_POST['is_active']) : 1
    ]);

    wo_json_response(true, 'Department created successfully');
}

function handleUpdate() {
    global $pdo;

    $deptCode = strtoupper(trim($_POST['dept_code'] ?? ''));
    $deptName = trim($_POST['dept_name'] ?? '');

    if (!$deptCode || !$deptName) {
        wo_error_response('Department code and name are required');
    }

    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM lsb_wo_dept_config WHERE dept_code = ?");
    $stmt->execute([$deptCode]);
    if (!$stmt->fetch()) {
        wo_error_response('Department not found', 404);
    }

    $stmt = $pdo->prepare("
        UPDATE lsb_wo_dept_config
        SET dept_name = ?, can_approve = ?, can_delete = ?, sort_order = ?, is_active = ?
        WHERE dept_code = ?
    ");
    $stmt->execute([
        $deptName,
        isset($_POST['can_approve']) ? intval($_POST['can_approve']) : 0,
        isset($_POST['can_delete']) ? intval($_POST['can_delete']) : 0,
        intval($_POST['sort_order'] ?? 0),
        isset($_POST['is_active']) ? intval($_POST['is_active']) : 1,
        $deptCode
    ]);

    wo_json_response(true, 'Department updated successfully');
}

function handleDelete() {
    global $pdo;

    $deptCode = $_GET['dept_code'] ?? $_POST['dept_code'] ?? '';
    if (!$deptCode) {
        wo_error_response('Department code required');
    }

    // Check if has members
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lsb_wo_users WHERE dept_code = ?");
    $stmt->execute([$deptCode]);
    if ($stmt->fetchColumn() > 0) {
        wo_error_response('Cannot delete department with active members. Please reassign users first.');
    }

    $stmt = $pdo->prepare("DELETE FROM lsb_wo_dept_config WHERE dept_code = ?");
    $stmt->execute([$deptCode]);

    if ($stmt->rowCount() === 0) {
        wo_error_response('Department not found', 404);
    }

    wo_json_response(true, 'Department deleted successfully');
}
