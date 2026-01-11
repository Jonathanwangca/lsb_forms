<?php
/**
 * LSB Work Order System - Authentication API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/wo_auth.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;

    case 'logout':
        handleLogout();
        break;

    case 'check':
        handleCheck();
        break;

    case 'change_password':
        handleChangePassword();
        break;

    default:
        wo_error_response('Invalid action', 400);
}

function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        wo_error_response('Email and password are required');
    }

    $result = wo_login($email, $password);
    if ($result['success']) {
        wo_json_response(true, $result['message'], ['user' => $result['user']]);
    } else {
        wo_error_response($result['message'], 401);
    }
}

function handleLogout() {
    wo_logout();
    wo_json_response(true, 'Logged out successfully');
}

function handleCheck() {
    if (wo_is_logged_in()) {
        wo_json_response(true, 'Logged in', ['user' => wo_get_current_user()]);
    } else {
        wo_error_response('Not logged in', 401);
    }
}

function handleChangePassword() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    wo_require_login();

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword)) {
        wo_error_response('All password fields are required');
    }

    if ($newPassword !== $confirmPassword) {
        wo_error_response('New passwords do not match');
    }

    $result = wo_change_password(wo_get_user_id(), $currentPassword, $newPassword);
    if ($result['success']) {
        wo_json_response(true, $result['message']);
    } else {
        wo_error_response($result['message']);
    }
}
