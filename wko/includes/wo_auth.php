<?php
/**
 * LSB Work Order System - Authentication Functions
 */

require_once __DIR__ . '/../config/wo_config.php';

/**
 * Process demo department switch - must execute before permission check
 * Switch to first user of specified department
 */
function wo_process_dept_switch() {
    if (isset($_GET['switch_dept'])) {
        if ($_GET['switch_dept'] === 'reset') {
            unset($_SESSION['demo_dept']);
            unset($_SESSION['demo_user']);
        } else {
            $deptCode = $_GET['switch_dept'];
            $pdo = wo_get_db();

            // Get first active user of this department
            $stmt = $pdo->prepare("
                SELECT u.id, u.email, u.name, u.title, u.dept_code, d.dept_name, d.can_approve, d.can_delete
                FROM lsb_wo_users u
                JOIN lsb_wo_dept_config d ON u.dept_code = d.dept_code
                WHERE u.dept_code = ? AND u.is_active = 1
                ORDER BY u.id
                LIMIT 1
            ");
            $stmt->execute([$deptCode]);
            $demoUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($demoUser) {
                $_SESSION['demo_dept'] = $deptCode;
                $_SESSION['demo_user'] = $demoUser;
            }
        }
        // Redirect back to current page (remove switch_dept parameter)
        $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
        if (!empty($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $params);
            unset($params['switch_dept']);
            if (!empty($params)) {
                $redirectUrl .= '?' . http_build_query($params);
            }
        }
        header('Location: ' . $redirectUrl);
        exit;
    }
}

// Process department switch immediately when file loads
wo_process_dept_switch();

/**
 * Check if user is logged in
 */
function wo_is_logged_in() {
    return !empty($_SESSION['wo_user']['id']);
}

/**
 * Get current logged in user
 * Supports demo mode department switch
 */
function wo_get_current_user() {
    $user = $_SESSION['wo_user'] ?? null;
    if (!$user) return null;

    // Check if there's a demo department switch
    $demoDept = $_SESSION['demo_dept'] ?? null;
    $demoUser = $_SESSION['demo_user'] ?? null;
    if ($demoDept && $demoUser) {
        // Override user info for demo
        $user['id'] = $demoUser['id'];
        $user['email'] = $demoUser['email'];
        $user['name'] = $demoUser['name'] . ' (' . $demoUser['dept_name'] . ')';
        $user['title'] = $demoUser['title'];
        $user['dept_code'] = $demoUser['dept_code'];
        $user['dept_name'] = $demoUser['dept_name'];
        $user['can_approve'] = $demoUser['can_approve'];
        $user['can_delete'] = $demoUser['can_delete'];
        $user['demo_dept'] = $demoDept;
    } else {
        // Get current user's department info
        $pdo = wo_get_db();
        $stmt = $pdo->prepare("
            SELECT u.dept_code, d.dept_name, d.can_approve, d.can_delete
            FROM lsb_wo_users u
            LEFT JOIN lsb_wo_dept_config d ON u.dept_code = d.dept_code
            WHERE u.id = ?
        ");
        $stmt->execute([$user['id']]);
        $deptInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($deptInfo) {
            $user['dept_code'] = $deptInfo['dept_code'];
            $user['dept_name'] = $deptInfo['dept_name'];
            $user['can_approve'] = $deptInfo['can_approve'];
            $user['can_delete'] = $deptInfo['can_delete'];
        }
    }

    return $user;
}

/**
 * Get current user ID
 */
function wo_get_user_id() {
    return $_SESSION['wo_user']['id'] ?? null;
}

/**
 * Require login
 */
function wo_require_login() {
    if (!wo_is_logged_in()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            wo_error_response('Please login first', 401);
        }
        header('Location: login.php');
        exit;
    }
}

/**
 * Require admin privileges
 */
function wo_require_admin() {
    wo_require_login();
    if (empty($_SESSION['wo_user']['is_admin'])) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            wo_error_response('Access denied', 403);
        }
        http_response_code(403);
        die('Access denied');
    }
}

/**
 * User login
 */
function wo_login($email, $password) {
    $pdo = wo_get_db();

    // Find user
    $stmt = $pdo->prepare("
        SELECT id, email, name, password_hash, role, department, is_admin, is_active,
               login_attempts, locked_until
        FROM lsb_wo_users
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    // Check if active
    if (!$user['is_active']) {
        return ['success' => false, 'message' => 'Account is disabled'];
    }

    // Check if locked
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
        return ['success' => false, 'message' => "Account is locked. Try again in {$remaining} minutes"];
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        // Increment failure count
        $attempts = $user['login_attempts'] + 1;
        $lockedUntil = null;

        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
            $attempts = 0;
        }

        $stmt = $pdo->prepare("
            UPDATE lsb_wo_users
            SET login_attempts = ?, locked_until = ?
            WHERE id = ?
        ");
        $stmt->execute([$attempts, $lockedUntil, $user['id']]);

        if ($lockedUntil) {
            return ['success' => false, 'message' => 'Too many failed attempts. Account locked for 30 minutes'];
        }

        $remaining = MAX_LOGIN_ATTEMPTS - $attempts;
        return ['success' => false, 'message' => "Invalid email or password. {$remaining} attempts remaining"];
    }

    // Login successful, reset failure count and update login time
    $stmt = $pdo->prepare("
        UPDATE lsb_wo_users
        SET login_attempts = 0, locked_until = NULL, last_login = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);

    // Set Session
    $_SESSION['wo_user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'role' => $user['role'],
        'department' => $user['department'],
        'is_admin' => (bool)$user['is_admin']
    ];

    return ['success' => true, 'message' => 'Login successful', 'user' => $_SESSION['wo_user']];
}

/**
 * User logout
 */
function wo_logout() {
    unset($_SESSION['wo_user']);
    session_regenerate_id(true);
}

/**
 * Change password
 */
function wo_change_password($userId, $currentPassword, $newPassword) {
    $pdo = wo_get_db();

    // Get current password
    $stmt = $pdo->prepare("SELECT password_hash FROM lsb_wo_users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }

    // Verify current password
    if (!password_verify($currentPassword, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }

    // Password strength check
    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters'];
    }

    // Update password
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE lsb_wo_users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$newHash, $userId]);

    return ['success' => true, 'message' => 'Password changed successfully'];
}

/**
 * Reset password (admin)
 */
function wo_reset_password($userId, $newPassword) {
    $pdo = wo_get_db();

    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters'];
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        UPDATE lsb_wo_users
        SET password_hash = ?, login_attempts = 0, locked_until = NULL
        WHERE id = ?
    ");
    $stmt->execute([$newHash, $userId]);

    return ['success' => true, 'message' => 'Password reset successfully'];
}

/**
 * Check if user can approve (department has approval permission)
 */
function wo_can_approve($user = null) {
    if (!$user) {
        $user = wo_get_current_user();
    }
    if (!$user) return false;

    return !empty($user['can_approve']);
}

/**
 * Check if user can delete WO (department has delete permission)
 */
function wo_can_delete($user = null) {
    if (!$user) {
        $user = wo_get_current_user();
    }
    if (!$user) return false;

    return !empty($user['can_delete']);
}

/**
 * Get user's department info
 */
function wo_get_user_dept($userId = null) {
    $pdo = wo_get_db();

    if (!$userId) {
        $user = wo_get_current_user();
        if (!$user) return null;
        $userId = $user['id'];
    }

    $stmt = $pdo->prepare("
        SELECT u.dept_code, d.dept_name, d.can_approve, d.can_delete
        FROM lsb_wo_users u
        LEFT JOIN lsb_wo_dept_config d ON u.dept_code = d.dept_code
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all departments with approval permission
 */
function wo_get_approval_depts() {
    $pdo = wo_get_db();
    $stmt = $pdo->query("
        SELECT dept_code, dept_name, sort_order
        FROM lsb_wo_dept_config
        WHERE can_approve = 1 AND is_active = 1
        ORDER BY sort_order
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
