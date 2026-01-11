<?php
/**
 * LSB Work Order System - Preset Comments API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/wo_auth.php';

wo_require_login();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        handleList();
        break;

    case 'get':
        handleGet();
        break;

    case 'create':
        wo_require_admin();
        handleCreate();
        break;

    case 'update':
        wo_require_admin();
        handleUpdate();
        break;

    case 'delete':
        wo_require_admin();
        handleDelete();
        break;

    case 'reorder':
        wo_require_admin();
        handleReorder();
        break;

    default:
        wo_error_response('Invalid action', 400);
}

function handleList() {
    $pdo = wo_get_db();

    $type = $_GET['type'] ?? '';  // COMMENT or CONDITION_NOTE
    $category = $_GET['category'] ?? '';
    $activeOnly = !isset($_GET['all']); // By default only show active

    $where = [];
    $params = [];

    if ($type) {
        $where[] = "type = ?";
        $params[] = $type;
    }

    if ($category) {
        $where[] = "category = ?";
        $params[] = $category;
    }

    if ($activeOnly) {
        $where[] = "is_active = 1";
    }

    $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

    $stmt = $pdo->prepare("
        SELECT id, type, category, comment_text, sort_order, is_active, created_at
        FROM lsb_wo_preset_comments
        $whereClause
        ORDER BY type, category, sort_order, id
    ");
    $stmt->execute($params);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    wo_json_response(true, '', ['comments' => $comments]);
}

function handleGet() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        wo_error_response('Comment ID required');
    }

    $pdo = wo_get_db();
    $stmt = $pdo->prepare("SELECT * FROM lsb_wo_preset_comments WHERE id = ?");
    $stmt->execute([$id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$comment) {
        wo_error_response('Comment not found', 404);
    }

    wo_json_response(true, '', ['comment' => $comment]);
}

function handleCreate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $type = trim($_POST['type'] ?? 'COMMENT');
    $category = trim($_POST['category'] ?? 'GENERAL');
    $commentText = trim($_POST['comment_text'] ?? '');
    $sortOrder = intval($_POST['sort_order'] ?? 0);

    if (empty($commentText)) {
        wo_error_response('Comment text is required');
    }

    $validTypes = ['COMMENT', 'CONDITION_NOTE'];
    if (!in_array($type, $validTypes)) {
        wo_error_response('Invalid type');
    }

    $validCategories = ['ACK', 'REJECT', 'CONDITION', 'GENERAL'];
    if (!in_array($category, $validCategories)) {
        wo_error_response('Invalid category');
    }

    $pdo = wo_get_db();
    $currentUser = wo_get_current_user();

    $stmt = $pdo->prepare("
        INSERT INTO lsb_wo_preset_comments (type, category, comment_text, sort_order, created_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$type, $category, $commentText, $sortOrder, $currentUser['id']]);

    wo_json_response(true, 'Preset comment created successfully', ['id' => $pdo->lastInsertId()]);
}

function handleUpdate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        wo_error_response('Comment ID required');
    }

    $pdo = wo_get_db();

    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM lsb_wo_preset_comments WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        wo_error_response('Comment not found', 404);
    }

    $fields = [];
    $params = [];

    if (isset($_POST['type'])) {
        $validTypes = ['COMMENT', 'CONDITION_NOTE'];
        if (!in_array($_POST['type'], $validTypes)) {
            wo_error_response('Invalid type');
        }
        $fields[] = "type = ?";
        $params[] = $_POST['type'];
    }

    if (isset($_POST['category'])) {
        $validCategories = ['ACK', 'REJECT', 'CONDITION', 'GENERAL'];
        if (!in_array($_POST['category'], $validCategories)) {
            wo_error_response('Invalid category');
        }
        $fields[] = "category = ?";
        $params[] = $_POST['category'];
    }

    if (isset($_POST['comment_text'])) {
        $commentText = trim($_POST['comment_text']);
        if (empty($commentText)) {
            wo_error_response('Comment text is required');
        }
        $fields[] = "comment_text = ?";
        $params[] = $commentText;
    }

    if (isset($_POST['sort_order'])) {
        $fields[] = "sort_order = ?";
        $params[] = intval($_POST['sort_order']);
    }

    if (isset($_POST['is_active'])) {
        $fields[] = "is_active = ?";
        $params[] = intval($_POST['is_active']) ? 1 : 0;
    }

    if (empty($fields)) {
        wo_error_response('No fields to update');
    }

    $params[] = $id;
    $sql = "UPDATE lsb_wo_preset_comments SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    wo_json_response(true, 'Preset comment updated successfully');
}

function handleDelete() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        wo_error_response('Comment ID required');
    }

    $pdo = wo_get_db();
    $stmt = $pdo->prepare("DELETE FROM lsb_wo_preset_comments WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        wo_error_response('Comment not found', 404);
    }

    wo_json_response(true, 'Preset comment deleted successfully');
}

function handleReorder() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $orders = $_POST['orders'] ?? [];
    if (empty($orders) || !is_array($orders)) {
        wo_error_response('Orders array required');
    }

    $pdo = wo_get_db();
    $stmt = $pdo->prepare("UPDATE lsb_wo_preset_comments SET sort_order = ? WHERE id = ?");

    foreach ($orders as $order) {
        if (isset($order['id']) && isset($order['sort_order'])) {
            $stmt->execute([intval($order['sort_order']), intval($order['id'])]);
        }
    }

    wo_json_response(true, 'Order updated successfully');
}
