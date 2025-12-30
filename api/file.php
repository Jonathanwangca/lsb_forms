<?php
/**
 * File API 接口
 * 处理RFQ文件上传和删除
 */
require_once dirname(__DIR__) . '/includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

// 处理JSON请求体
$input = [];
if ($method === 'POST' && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

$action = $input['action'] ?? $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'upload':
            handleUpload();
            break;

        case 'delete':
            handleDelete($input);
            break;

        case 'list':
            handleList();
            break;

        default:
            jsonError('Invalid action', 400);
    }
} catch (Exception $e) {
    jsonError($e->getMessage(), 500);
}

/**
 * 处理文件上传
 */
function handleUpload() {
    $rfqId = intval($_POST['rfq_id'] ?? 0);
    $category = $_POST['category'] ?? 'other_docs';
    $categoryName = $_POST['category_name'] ?? '';

    if (!$rfqId) {
        jsonError('RFQ ID is required', 400);
    }

    if (empty($_FILES['file'])) {
        jsonError('No file uploaded', 400);
    }

    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by extension'
        ];
        jsonError($errorMessages[$file['error']] ?? 'Upload error', 400);
    }

    // 验证文件类型
    $allowedExts = ['pdf', 'dwg', 'dxf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', '7z', 'jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        jsonError('File type not allowed: ' . $ext, 400);
    }

    // 验证文件大小 (最大 50MB)
    $maxSize = 50 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        jsonError('File too large. Maximum size is 50MB', 400);
    }

    // 创建上传目录
    $uploadDir = dirname(__DIR__) . '/uploads/rfq/' . $rfqId;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 生成唯一文件名
    $newFileName = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $filePath = $uploadDir . '/' . $newFileName;
    $relativePath = '/aiforms/uploads/rfq/' . $rfqId . '/' . $newFileName;

    // 移动文件
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        jsonError('Failed to save file', 500);
    }

    // 获取文件MIME类型
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    // 保存到数据库
    $fileId = dbInsert('lsb_rfq_files', [
        'rfq_id' => $rfqId,
        'file_name' => $file['name'],
        'file_path' => $relativePath,
        'file_size' => $file['size'],
        'file_type' => $mimeType,
        'file_ext' => $ext,
        'file_category' => $category,
        'file_category_name' => $categoryName,
        'upload_date' => date('Y-m-d'),
        'upload_datetime' => date('Y-m-d H:i:s'),
        'upload_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'is_active' => 1
    ]);

    jsonSuccess([
        'id' => $fileId,
        'file_name' => $file['name'],
        'file_path' => $relativePath,
        'file_size' => $file['size'],
        'file_size_formatted' => formatFileSize($file['size'])
    ], 'File uploaded successfully');
}

/**
 * 处理文件删除
 */
function handleDelete($input) {
    $fileId = intval($input['file_id'] ?? $_POST['file_id'] ?? 0);

    if (!$fileId) {
        jsonError('File ID is required', 400);
    }

    // 获取文件信息
    $file = dbQueryOne("SELECT * FROM lsb_rfq_files WHERE id = ?", [$fileId]);

    if (!$file) {
        jsonError('File not found', 404);
    }

    // 软删除（标记为不活跃）
    dbUpdate('lsb_rfq_files', [
        'is_active' => 0,
        'deleted_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$fileId]);

    // 可选：删除物理文件
    // $filePath = dirname(__DIR__) . $file['file_path'];
    // if (file_exists($filePath)) {
    //     unlink($filePath);
    // }

    jsonSuccess(null, 'File deleted successfully');
}

/**
 * 获取RFQ文件列表
 */
function handleList() {
    $rfqId = intval($_GET['rfq_id'] ?? 0);
    $category = $_GET['category'] ?? null;

    if (!$rfqId) {
        jsonError('RFQ ID is required', 400);
    }

    $sql = "SELECT * FROM lsb_rfq_files WHERE rfq_id = ? AND is_active = 1";
    $params = [$rfqId];

    if ($category) {
        $sql .= " AND file_category = ?";
        $params[] = $category;
    }

    $sql .= " ORDER BY file_category, upload_datetime DESC";

    $files = dbQuery($sql, $params);

    // 格式化文件大小
    foreach ($files as &$file) {
        $file['file_size_formatted'] = formatFileSize($file['file_size']);
    }

    jsonSuccess($files);
}
