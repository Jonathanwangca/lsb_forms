<?php
/**
 * LSB Work Order System - Excel Parse API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/wo_auth.php';
require_once __DIR__ . '/../includes/wo_openai.php';

wo_require_login();

$action = $_GET['action'] ?? $_POST['action'] ?? 'parse';

switch ($action) {
    case 'parse':
        handleParse();
        break;

    default:
        wo_error_response('Invalid action', 400);
}

function handleParse() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wo_error_response('Method not allowed', 405);
    }

    if (empty($_FILES['file'])) {
        wo_error_response('No file uploaded');
    }

    $file = $_FILES['file'];

    // 验证文件
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wo_error_response('File upload error');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'xls', 'xltx'])) {
        wo_error_response('Only Excel files are allowed (.xlsx, .xls, .xltx)');
    }

    // 保存临时文件
    $tempDir = sys_get_temp_dir();
    $tempFile = $tempDir . '/' . uniqid('wo_excel_') . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
        wo_error_response('Failed to save uploaded file');
    }

    try {
        // 提取Excel文本
        $extractResult = wo_extract_excel_text_simple($tempFile);

        if (!$extractResult['success']) {
            // 如果简化提取失败，尝试返回基本信息
            unlink($tempFile);
            wo_json_response(false, $extractResult['message']);
            return;
        }

        $textContent = $extractResult['data'];

        // 如果文本内容太短，可能提取失败
        if (strlen($textContent) < 50) {
            unlink($tempFile);
            wo_json_response(false, 'Could not extract enough content from Excel file');
            return;
        }

        // 调用GPT解析
        $parseResult = wo_parse_excel_with_gpt($textContent);

        // 清理临时文件
        unlink($tempFile);

        if ($parseResult['success']) {
            wo_json_response(true, 'Excel parsed successfully', $parseResult['data']);
        } else {
            wo_json_response(false, $parseResult['message']);
        }
    } catch (Exception $e) {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        error_log('Excel parse error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        wo_error_response('Error parsing file: ' . $e->getMessage());
    } catch (Error $e) {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        error_log('Excel parse fatal error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        wo_error_response('Error parsing file: ' . $e->getMessage());
    }
}
