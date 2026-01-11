<?php
/**
 * LSB Work Order System - Reviewer Config API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/wo_auth.php';

wo_require_admin();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        handleList();
        break;

    case 'create':
        handleCreate();
        break;

    case 'update':
        handleUpdate();
        break;

    default:
        wo_error_response('Invalid action', 400);
}

function handleList() {
    $pdo = wo_get_db();
    $stmt = $pdo->query("SELECT * FROM lsb_wo_reviewer_config ORDER BY sort_order");
    $reviewers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    wo_json_response(true, '', ['reviewers' => $reviewers]);
}

function handleCreate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $roleCode = trim($_POST['role_code'] ?? '');
    $roleName = trim($_POST['role_name'] ?? '');
    $roleNameCn = $_POST['role_name_cn'] ?? null;
    $reviewerName = trim($_POST['reviewer_name'] ?? '');
    $reviewerEmail = trim($_POST['reviewer_email'] ?? '');
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $isRequired = isset($_POST['is_required']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (empty($roleCode) || empty($roleName) || empty($reviewerName) || empty($reviewerEmail)) {
        wo_error_response('Role code, role name, reviewer name and email are required');
    }

    $pdo = wo_get_db();

    // 检查role_code是否已存在
    $stmt = $pdo->prepare("SELECT id FROM lsb_wo_reviewer_config WHERE role_code = ?");
    $stmt->execute([$roleCode]);
    if ($stmt->fetch()) {
        wo_error_response('Role code already exists');
    }

    $stmt = $pdo->prepare("
        INSERT INTO lsb_wo_reviewer_config
        (role_code, role_name, role_name_cn, reviewer_name, reviewer_email, sort_order, is_required, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$roleCode, $roleName, $roleNameCn, $reviewerName, $reviewerEmail, $sortOrder, $isRequired, $isActive]);

    wo_json_response(true, 'Reviewer added successfully', ['id' => $pdo->lastInsertId()]);
}

function handleUpdate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        wo_error_response('ID required');
    }

    $pdo = wo_get_db();

    $stmt = $pdo->prepare("
        UPDATE lsb_wo_reviewer_config SET
            role_name = ?,
            role_name_cn = ?,
            reviewer_name = ?,
            reviewer_email = ?,
            sort_order = ?,
            is_required = ?,
            is_active = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $_POST['role_name'] ?? '',
        $_POST['role_name_cn'] ?? null,
        $_POST['reviewer_name'] ?? '',
        $_POST['reviewer_email'] ?? '',
        intval($_POST['sort_order'] ?? 0),
        isset($_POST['is_required']) && $_POST['is_required'] != '0' ? 1 : 0,
        isset($_POST['is_active']) && $_POST['is_active'] != '0' ? 1 : 0,
        $id
    ]);

    wo_json_response(true, 'Reviewer updated successfully');
}
