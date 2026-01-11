<?php
/**
 * LSB Work Order System - User Management API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/wo_auth.php';

// All user management operations require admin privileges
wo_require_admin();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

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

    case 'toggle_active':
        handleToggleActive();
        break;

    case 'reset_password':
        handleResetPassword();
        break;

    default:
        wo_error_response('Invalid action', 400);
}

function handleList() {
    $pdo = wo_get_db();

    $search = $_GET['search'] ?? '';
    $where = "1=1";
    $params = [];

    if ($search) {
        $where .= " AND (u.email LIKE ? OR u.name LIKE ? OR u.title LIKE ? OR u.dept_code LIKE ?)";
        $search = "%$search%";
        $params = [$search, $search, $search, $search];
    }

    $stmt = $pdo->prepare("
        SELECT u.id, u.email, u.name, u.title, u.dept_code, u.role, u.department, u.is_admin, u.is_active, u.last_login, u.created_at,
               d.dept_name
        FROM lsb_wo_users u
        LEFT JOIN lsb_wo_dept_config d ON u.dept_code = d.dept_code
        WHERE $where
        ORDER BY u.created_at DESC
    ");
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    wo_json_response(true, '', ['users' => $users]);
}

function handleGet() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        wo_error_response('User ID required');
    }

    $pdo = wo_get_db();
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, u.name, u.title, u.dept_code, u.role, u.department, u.is_admin, u.is_active, u.last_login, u.created_at,
               d.dept_name
        FROM lsb_wo_users u
        LEFT JOIN lsb_wo_dept_config d ON u.dept_code = d.dept_code
        WHERE u.id = ?
    ");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        wo_error_response('User not found', 404);
    }

    wo_json_response(true, '', ['user' => $user]);
}

function handleCreate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $deptCode = $_POST['dept_code'] ?? null;
    $isAdmin = !empty($_POST['is_admin']) ? 1 : 0;

    // Validate
    if (empty($email) || empty($name) || empty($password)) {
        wo_error_response('Email, name and password are required');
    }

    if (empty($deptCode)) {
        wo_error_response('Department is required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        wo_error_response('Invalid email format');
    }

    if (strlen($password) < 8) {
        wo_error_response('Password must be at least 8 characters');
    }

    $pdo = wo_get_db();

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM lsb_wo_users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        wo_error_response('Email already exists');
    }

    // 验证部门存在
    $stmt = $pdo->prepare("SELECT dept_code FROM lsb_wo_dept_config WHERE dept_code = ?");
    $stmt->execute([$deptCode]);
    if (!$stmt->fetch()) {
        wo_error_response('Invalid department');
    }

    // Create user
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO lsb_wo_users (email, name, title, dept_code, password_hash, is_admin)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$email, $name, $title ?: null, $deptCode, $passwordHash, $isAdmin]);

    wo_json_response(true, 'User created successfully', ['id' => $pdo->lastInsertId()]);
}

function handleUpdate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        wo_error_response('User ID required');
    }

    $pdo = wo_get_db();

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM lsb_wo_users WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        wo_error_response('User not found', 404);
    }

    $fields = [];
    $params = [];

    // Updatable fields
    if (isset($_POST['name'])) {
        $fields[] = "name = ?";
        $params[] = trim($_POST['name']);
    }
    if (isset($_POST['title'])) {
        $fields[] = "title = ?";
        $params[] = trim($_POST['title']) ?: null;
    }
    if (isset($_POST['dept_code'])) {
        // Verify department exists
        if ($_POST['dept_code']) {
            $stmt = $pdo->prepare("SELECT dept_code FROM lsb_wo_dept_config WHERE dept_code = ?");
            $stmt->execute([$_POST['dept_code']]);
            if (!$stmt->fetch()) {
                wo_error_response('Invalid department');
            }
        }
        $fields[] = "dept_code = ?";
        $params[] = $_POST['dept_code'] ?: null;
    }
    if (isset($_POST['is_admin'])) {
        $fields[] = "is_admin = ?";
        $params[] = !empty($_POST['is_admin']) ? 1 : 0;
    }

    if (empty($fields)) {
        wo_error_response('No fields to update');
    }

    $params[] = $id;
    $sql = "UPDATE lsb_wo_users SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    wo_json_response(true, 'User updated successfully');
}

function handleToggleActive() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        wo_error_response('User ID required');
    }

    // Cannot disable yourself
    if ($id === wo_get_user_id()) {
        wo_error_response('Cannot disable your own account');
    }

    $pdo = wo_get_db();
    $stmt = $pdo->prepare("
        UPDATE lsb_wo_users
        SET is_active = NOT is_active
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    wo_json_response(true, 'User status updated');
}

function handleResetPassword() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $id = intval($_POST['id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';

    if (!$id) {
        wo_error_response('User ID required');
    }

    $result = wo_reset_password($id, $newPassword);
    if ($result['success']) {
        wo_json_response(true, $result['message']);
    } else {
        wo_error_response($result['message']);
    }
}
