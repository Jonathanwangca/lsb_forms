<?php
/**
 * Labels API
 * LSB RFQ System V3.1
 * CRUD operations for labels.json
 */

require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// 标签文件路径
$labelsFile = dirname(__DIR__) . '/assets/lang/labels.json';

// 获取操作类型
$action = $_REQUEST['action'] ?? '';

try {
    // 读取当前标签数据
    $labels = [];
    if (file_exists($labelsFile)) {
        $labels = json_decode(file_get_contents($labelsFile), true) ?: [];
    }

    switch ($action) {
        case 'get_all':
            // 获取所有标签
            jsonSuccess($labels);
            break;

        case 'get_category':
            // 获取某个分类的标签
            $category = $_GET['category'] ?? '';
            if (empty($category) || !isset($labels[$category])) {
                jsonError('Category not found');
            }
            jsonSuccess($labels[$category]);
            break;

        case 'save_label':
            // 保存标签（添加或更新）
            $category = trim($_POST['category'] ?? '');
            $key = trim($_POST['key'] ?? '');
            $originalKey = trim($_POST['original_key'] ?? '');
            $valueCn = trim($_POST['value_cn'] ?? '');
            $valueEn = trim($_POST['value_en'] ?? '');

            if (empty($category) || empty($key) || empty($valueCn) || empty($valueEn)) {
                jsonError('All fields are required');
            }

            // 验证键名格式
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
                jsonError('Key must start with lowercase letter and contain only lowercase letters, numbers and underscores');
            }

            // 确保分类存在
            if (!isset($labels[$category])) {
                $labels[$category] = [];
            }

            // 如果是编辑且键名改变，删除旧键
            if ($originalKey && $originalKey !== $key && isset($labels[$category][$originalKey])) {
                unset($labels[$category][$originalKey]);
            }

            // 检查键名是否重复（新增时）
            if (!$originalKey && isset($labels[$category][$key])) {
                jsonError('Key already exists in this category');
            }

            // 保存标签
            $labels[$category][$key] = [
                'cn' => $valueCn,
                'en' => $valueEn
            ];

            // 按键名排序
            ksort($labels[$category]);

            // 写入文件
            if (!saveLabelsFile($labelsFile, $labels)) {
                jsonError('Failed to save file');
            }

            jsonSuccess(['key' => $key], 'Saved successfully');
            break;

        case 'delete_label':
            // 删除标签
            $category = trim($_POST['category'] ?? '');
            $key = trim($_POST['key'] ?? '');

            if (empty($category) || empty($key)) {
                jsonError('Invalid parameters');
            }

            if (!isset($labels[$category][$key])) {
                jsonError('Label not found');
            }

            unset($labels[$category][$key]);

            // 写入文件
            if (!saveLabelsFile($labelsFile, $labels)) {
                jsonError('Failed to save file');
            }

            jsonSuccess(null, 'Deleted successfully');
            break;

        case 'add_category':
            // 添加分类
            $category = trim($_POST['category'] ?? '');

            if (empty($category)) {
                jsonError('Category name is required');
            }

            // 验证分类名格式
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $category)) {
                jsonError('Category must start with lowercase letter and contain only lowercase letters, numbers and underscores');
            }

            if (isset($labels[$category])) {
                jsonError('Category already exists');
            }

            $labels[$category] = [];

            // 按分类名排序
            ksort($labels);

            // 写入文件
            if (!saveLabelsFile($labelsFile, $labels)) {
                jsonError('Failed to save file');
            }

            jsonSuccess(['category' => $category], 'Category created successfully');
            break;

        case 'delete_category':
            // 删除分类
            $category = trim($_POST['category'] ?? '');

            if (empty($category)) {
                jsonError('Category name is required');
            }

            if (!isset($labels[$category])) {
                jsonError('Category not found');
            }

            // 检查分类是否为空
            if (!empty($labels[$category])) {
                jsonError('Cannot delete non-empty category. Please delete all labels first.');
            }

            unset($labels[$category]);

            // 写入文件
            if (!saveLabelsFile($labelsFile, $labels)) {
                jsonError('Failed to save file');
            }

            jsonSuccess(null, 'Category deleted successfully');
            break;

        default:
            jsonError('Invalid action');
    }
} catch (Exception $e) {
    jsonError('Error: ' . $e->getMessage());
}

/**
 * 保存标签文件
 */
function saveLabelsFile($filePath, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    return file_put_contents($filePath, $json) !== false;
}
