<?php
/**
 * 测试Excel解析API - 调试版本
 */
error_reporting(E_ALL);
ini_set('display_errors', 0); // 禁止显示错误到输出

// 捕获所有错误
$errors = [];
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errors) {
    $errors[] = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    return true;
});

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../includes/wo_auth.php';
    require_once __DIR__ . '/../includes/wo_openai.php';

    // 检查登录
    if (!isset($_SESSION['wo_user'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in', 'errors' => $errors]);
        exit;
    }

    if (empty($_FILES['file'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded', 'errors' => $errors]);
        exit;
    }

    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Upload error: ' . $file['error'], 'errors' => $errors]);
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $tempDir = sys_get_temp_dir();
    $tempFile = $tempDir . '/' . uniqid('wo_excel_') . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
        echo json_encode(['success' => false, 'message' => 'Failed to move file', 'errors' => $errors]);
        exit;
    }

    // 提取Excel内容
    $extractResult = wo_extract_excel_text_simple($tempFile);

    if (!$extractResult['success']) {
        unlink($tempFile);
        echo json_encode(['success' => false, 'message' => 'Extract failed: ' . $extractResult['message'], 'errors' => $errors]);
        exit;
    }

    $textContent = $extractResult['data'];
    $contentLength = strlen($textContent);

    if ($contentLength < 50) {
        unlink($tempFile);
        echo json_encode(['success' => false, 'message' => 'Content too short: ' . $contentLength . ' chars', 'preview' => substr($textContent, 0, 200), 'errors' => $errors]);
        exit;
    }

    // 调用GPT
    $parseResult = wo_parse_excel_with_gpt($textContent);

    unlink($tempFile);

    if ($parseResult['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Parsed successfully',
            'data' => $parseResult['data'],
            'content_length' => $contentLength,
            'content_preview' => substr($textContent, 0, 500),
            'errors' => $errors
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'GPT parse failed: ' . $parseResult['message'],
            'content_length' => $contentLength,
            'content_preview' => substr($textContent, 0, 500),
            'errors' => $errors
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'errors' => $errors
    ]);
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'errors' => $errors
    ]);
}
