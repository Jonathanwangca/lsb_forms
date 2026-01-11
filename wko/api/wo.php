<?php
/**
 * LSB Work Order System - Work Order API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/wo_functions.php';
require_once __DIR__ . '/../includes/wo_auth.php';

wo_require_login();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        handleCreate();
        break;

    case 'update':
        handleUpdate();
        break;

    case 'get':
        handleGet();
        break;

    case 'list':
        handleList();
        break;

    case 'inbox':
        handleInbox();
        break;

    case 'submit':
        handleSubmit();
        break;

    case 'review':
        handleReview();
        break;

    case 'upload':
        handleUpload();
        break;

    case 'delete_file':
        handleDeleteFile();
        break;

    case 'download':
        handleDownload();
        break;

    case 'delete':
        handleDelete();
        break;

    case 'withdraw':
        handleWithdraw();
        break;

    case 'revert_to_draft':
        handleRevertToDraft();
        break;

    default:
        wo_error_response('Invalid action', 400);
}

function handleCreate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $currentUser = wo_get_current_user();

    $data = [
        'title' => $_POST['title'] ?? null,
        'lsb_job_no' => $_POST['lsb_job_no'] ?? null,
        'project_code' => $_POST['project_code'] ?? null,
        'project_name' => $_POST['project_name'] ?? null,
        'project_address' => $_POST['project_address'] ?? null,
        'owner_name' => $_POST['owner_name'] ?? null,
        'vendor_name' => $_POST['vendor_name'] ?? null,
        'vendor_address' => $_POST['vendor_address'] ?? null,
        'vendor_contact' => $_POST['vendor_contact'] ?? null,
        'vendor_phone' => $_POST['vendor_phone'] ?? null,
        'vendor_email' => $_POST['vendor_email'] ?? null,
        'original_amount' => floatval($_POST['original_amount'] ?? 0),
        'cost_code' => $_POST['cost_code'] ?? null,
        'holdback_percent' => floatval($_POST['holdback_percent'] ?? 10),
        'scope_summary' => $_POST['scope_summary'] ?? null,
        'memo' => $_POST['memo'] ?? null,
        'issued_date' => $_POST['issued_date'] ?? null,
        'requester_id' => $currentUser['id'],
        'requester_name' => $currentUser['name'],
        'requester_email' => $currentUser['email'],
        'requester_department' => $currentUser['department'] ?? null,
    ];

    $result = wo_create($data);
    if ($result['success']) {
        wo_json_response(true, $result['message'], $result['data']);
    } else {
        wo_error_response($result['message']);
    }
}

function handleUpdate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        wo_error_response('Work Order ID required');
    }

    // 检查权限
    $wo = wo_get($id);
    if (!$wo) {
        wo_error_response('Work Order not found', 404);
    }

    // 使用 wo_get_current_user() 支持演示部门切换
    $currentUser = wo_get_current_user();
    $pdo = wo_get_db();

    $isOwner = ($wo['requester_id'] == $currentUser['id']);
    $isAdmin = $currentUser['is_admin'];
    $userDeptCode = $currentUser['dept_code'] ?? '';

    // 检查是否是拒绝该项目的部门成员
    $isRejecterDept = false;
    if ($wo['status'] === 'REJECTED' && $userDeptCode) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lsb_wo_review WHERE wo_id = ? AND reviewer_dept = ? AND decision = 'REJECTED'");
        $stmt->execute([$id, $userDeptCode]);
        $isRejecterDept = ($stmt->fetchColumn() > 0);
    }

    // 权限判断：
    // - DRAFT状态：创建者或管理员可编辑
    // - REJECTED状态：创建者、拒绝部门成员、或管理员可编辑
    $canEditDraft = ($wo['status'] === 'DRAFT') && ($isOwner || $isAdmin);
    $canEditRejected = ($wo['status'] === 'REJECTED') && ($isOwner || $isRejecterDept || $isAdmin);

    if (!$canEditDraft && !$canEditRejected) {
        wo_error_response('Permission denied', 403);
    }

    $data = [];
    $fields = ['title', 'lsb_job_no', 'project_code', 'project_name', 'project_address', 'owner_name',
               'vendor_name', 'vendor_address', 'vendor_contact', 'vendor_phone', 'vendor_email',
               'original_amount', 'cost_code', 'holdback_percent', 'scope_summary', 'memo', 'issued_date'];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $data[$field] = $_POST[$field];
        }
    }

    $result = wo_update($id, $data);
    if ($result['success']) {
        wo_json_response(true, $result['message']);
    } else {
        wo_error_response($result['message']);
    }
}

function handleGet() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        wo_error_response('Work Order ID required');
    }

    $wo = wo_get($id);
    if (!$wo) {
        wo_error_response('Work Order not found', 404);
    }

    // 获取审阅记录
    $reviews = wo_get_reviews($id);

    // 获取文件列表
    $files = wo_get_files($id);

    wo_json_response(true, '', [
        'wo' => $wo,
        'reviews' => $reviews,
        'files' => $files
    ]);
}

function handleList() {
    // Use wo_get_current_user() to support demo department switch
    $currentUser = wo_get_current_user();
    $userId = $currentUser['id'];

    // Admin (is_admin or ADM department) can see all WOs
    $isAdmin = $currentUser['is_admin'] || ($currentUser['dept_code'] === 'ADM');

    $filters = [
        'status' => $_GET['status'] ?? null,
        'search' => $_GET['search'] ?? null,
        'page' => $_GET['page'] ?? 1,
        'limit' => $_GET['limit'] ?? 20
    ];

    $result = wo_list($userId, $filters, $isAdmin);

    // Add approval progress for SUBMITTED items
    $pdo = wo_get_db();
    foreach ($result['items'] as &$item) {
        if ($item['status'] === 'SUBMITTED') {
            $stmt = $pdo->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN decision IN ('ACK', 'ACK_WITH_CONDITION') THEN 1 ELSE 0 END) as approved
                FROM lsb_wo_review
                WHERE wo_id = ?
            ");
            $stmt->execute([$item['id']]);
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            $item['approval_total'] = intval($progress['total']);
            $item['approval_approved'] = intval($progress['approved']);
        }
    }
    unset($item);

    wo_json_response(true, '', $result);
}

function handleInbox() {
    $currentUser = wo_get_current_user();
    $deptCode = $currentUser['dept_code'] ?? '';

    if (!$deptCode || empty($currentUser['can_approve'])) {
        wo_json_response(true, '', ['items' => []]);
        return;
    }

    $items = wo_inbox($deptCode);
    wo_json_response(true, '', ['items' => $items]);
}

function handleSubmit() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        wo_error_response('Work Order ID required');
    }

    // 检查权限
    $wo = wo_get($id);
    if (!$wo) {
        wo_error_response('Work Order not found', 404);
    }

    // 使用 wo_get_current_user() 支持演示部门切换
    $currentUser = wo_get_current_user();
    $pdo = wo_get_db();

    $isOwner = ($wo['requester_id'] == $currentUser['id']);
    $isAdmin = $currentUser['is_admin'];
    $userDeptCode = $currentUser['dept_code'] ?? '';

    // 检查是否是拒绝该项目的部门成员
    $isRejecterDept = false;
    if ($wo['status'] === 'REJECTED' && $userDeptCode) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lsb_wo_review WHERE wo_id = ? AND reviewer_dept = ? AND decision = 'REJECTED'");
        $stmt->execute([$id, $userDeptCode]);
        $isRejecterDept = ($stmt->fetchColumn() > 0);
    }

    // 提交权限判断：
    // - DRAFT状态：创建者可提交
    // - REJECTED状态：创建者、拒绝部门成员、或管理员可重新提交
    $canSubmitDraft = ($wo['status'] === 'DRAFT') && $isOwner;
    $canSubmitRejected = ($wo['status'] === 'REJECTED') && ($isOwner || $isRejecterDept || $isAdmin);

    if (!$canSubmitDraft && !$canSubmitRejected) {
        wo_error_response('Permission denied', 403);
    }

    $result = wo_submit($id);
    if ($result['success']) {
        wo_json_response(true, $result['message']);
    } else {
        wo_error_response($result['message']);
    }
}

function handleReview() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $woId = intval($_POST['wo_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $comment = $_POST['comment'] ?? null;
    $conditionNote = $_POST['condition_note'] ?? null;

    if (!$woId || !$decision) {
        wo_error_response('Work Order ID and decision are required');
    }

    // 使用 wo_get_current_user() 以支持演示部门切换
    $currentUser = wo_get_current_user();

    // 检查用户部门是否有审批权限
    if (empty($currentUser['can_approve'])) {
        wo_error_response('Your department does not have approval privileges', 403);
    }

    $deptCode = $currentUser['dept_code'];
    if (!$deptCode) {
        wo_error_response('You must be assigned to a department to review', 403);
    }

    $result = wo_review(
        $woId,
        $deptCode,
        $currentUser['id'],
        $currentUser['name'],
        $currentUser['email'],
        $decision,
        $comment,
        $conditionNote
    );

    if ($result['success']) {
        wo_json_response(true, $result['message']);
    } else {
        wo_error_response($result['message']);
    }
}

function handleUpload() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $woId = intval($_POST['wo_id'] ?? 0);
    if (!$woId) {
        wo_error_response('Work Order ID required');
    }

    if (empty($_FILES['file'])) {
        wo_error_response('No file uploaded');
    }

    $category = $_POST['category'] ?? 'attachment';
    $uploadedBy = $_SESSION['wo_user']['name'];

    $result = wo_upload_file($woId, $_FILES['file'], $category, $uploadedBy);

    if ($result['success']) {
        wo_json_response(true, $result['message'], $result['data']);
    } else {
        wo_error_response($result['message']);
    }
}

function handleDeleteFile() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $fileId = intval($_POST['file_id'] ?? 0);
    if (!$fileId) {
        wo_error_response('File ID required');
    }

    $result = wo_delete_file($fileId);
    if ($result['success']) {
        wo_json_response(true, $result['message']);
    } else {
        wo_error_response($result['message']);
    }
}

function handleDownload() {
    $fileId = intval($_GET['file_id'] ?? 0);
    if (!$fileId) {
        wo_error_response('File ID required');
    }

    $pdo = wo_get_db();
    $stmt = $pdo->prepare("SELECT * FROM lsb_wo_files WHERE id = ? AND is_active = 1");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        wo_error_response('File not found', 404);
    }

    if (!file_exists($file['file_path'])) {
        wo_error_response('File not found on server', 404);
    }

    // 输出文件
    header('Content-Type: ' . ($file['file_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
    header('Content-Length: ' . $file['file_size']);
    readfile($file['file_path']);
    exit;
}

function handleDelete() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        wo_error_response('Work Order ID required');
    }

    // 管理员或有删除权限的部门成员可以删除
    $currentUser = wo_get_current_user();
    $canDelete = $currentUser['is_admin'] || !empty($currentUser['can_delete']);

    if (!$canDelete) {
        wo_error_response('Permission denied. Only administrators or Administrator department members can delete Work Orders.', 403);
    }

    $wo = wo_get($id);
    if (!$wo) {
        wo_error_response('Work Order not found', 404);
    }

    $pdo = wo_get_db();

    try {
        $pdo->beginTransaction();

        // 删除相关文件记录（文件会因外键级联删除，但物理文件需要手动删除）
        $stmt = $pdo->prepare("SELECT file_path FROM lsb_wo_files WHERE wo_id = ?");
        $stmt->execute([$id]);
        $files = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 删除 WO 主记录（相关的 reviews 和 files 会通过外键级联删除）
        $stmt = $pdo->prepare("DELETE FROM lsb_wo_header WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();

        // 删除物理文件
        foreach ($files as $filePath) {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        // 删除上传目录
        $uploadDir = WO_UPLOAD_PATH . '/' . $id;
        if (is_dir($uploadDir)) {
            @rmdir($uploadDir);
        }

        wo_json_response(true, 'Work Order deleted successfully');
    } catch (Exception $e) {
        $pdo->rollBack();
        wo_error_response('Failed to delete Work Order: ' . $e->getMessage());
    }
}

function handleWithdraw() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        wo_error_response('Work Order ID required');
    }

    $wo = wo_get($id);
    if (!$wo) {
        wo_error_response('Work Order not found', 404);
    }

    // 只有 SUBMITTED 状态可以撤回
    if ($wo['status'] !== 'SUBMITTED') {
        wo_error_response('Only submitted Work Orders can be withdrawn');
    }

    // 权限检查：仅创建者可撤回
    $currentUser = wo_get_current_user();
    $isOwner = ($wo['requester_id'] == $currentUser['id']);

    if (!$isOwner) {
        wo_error_response('Permission denied. Only the requester can withdraw this Work Order.', 403);
    }

    $pdo = wo_get_db();

    try {
        $pdo->beginTransaction();

        // 更新 WO 状态为 DRAFT
        $stmt = $pdo->prepare("UPDATE lsb_wo_header SET status = 'DRAFT', submitted_at = NULL, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        // 删除所有审阅记录
        $stmt = $pdo->prepare("DELETE FROM lsb_wo_review WHERE wo_id = ?");
        $stmt->execute([$id]);

        $pdo->commit();

        wo_json_response(true, 'Work Order withdrawn successfully');
    } catch (Exception $e) {
        $pdo->rollBack();
        wo_error_response('Failed to withdraw Work Order: ' . $e->getMessage());
    }
}

function handleRevertToDraft() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        wo_error_response('Work Order ID required');
    }

    $wo = wo_get($id);
    if (!$wo) {
        wo_error_response('Work Order not found', 404);
    }

    // 只有 REJECTED 状态可以恢复为草稿
    if ($wo['status'] !== 'REJECTED') {
        wo_error_response('Only rejected Work Orders can be reverted to draft');
    }

    // 权限检查：创建者、拒绝部门成员、或管理员可操作
    $currentUser = wo_get_current_user();
    $pdo = wo_get_db();

    $isOwner = ($wo['requester_id'] == $currentUser['id']);
    $isAdmin = $currentUser['is_admin'];
    $userDeptCode = $currentUser['dept_code'] ?? '';

    // 检查是否是拒绝该项目的部门成员
    $isRejecterDept = false;
    if ($userDeptCode) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lsb_wo_review WHERE wo_id = ? AND reviewer_dept = ? AND decision = 'REJECTED'");
        $stmt->execute([$id, $userDeptCode]);
        $isRejecterDept = ($stmt->fetchColumn() > 0);
    }

    if (!$isOwner && !$isRejecterDept && !$isAdmin) {
        wo_error_response('Permission denied. Only the requester, rejecting department members, or admin can revert this Work Order.', 403);
    }

    try {
        $pdo->beginTransaction();

        // 更新 WO 状态为 DRAFT
        $stmt = $pdo->prepare("UPDATE lsb_wo_header SET status = 'DRAFT', submitted_at = NULL, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        // 删除所有审阅记录
        $stmt = $pdo->prepare("DELETE FROM lsb_wo_review WHERE wo_id = ?");
        $stmt->execute([$id]);

        $pdo->commit();

        wo_json_response(true, 'Work Order reverted to draft successfully');
    } catch (Exception $e) {
        $pdo->rollBack();
        wo_error_response('Failed to revert Work Order: ' . $e->getMessage());
    }
}
