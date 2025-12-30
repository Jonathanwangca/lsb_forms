<?php
/**
 * Reference Data API
 * LSB RFQ System V3.1
 * CRUD operations for lsb_rfq_reference table
 */

require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// 获取操作类型
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            // 获取单个参数
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                jsonError('Invalid ID');
            }
            $item = dbQueryOne("SELECT * FROM lsb_rfq_reference WHERE id = ?", [$id]);
            if (!$item) {
                jsonError('Item not found');
            }
            jsonSuccess($item);
            break;

        case 'list':
            // 获取某个分类的所有参数
            $category = $_GET['category'] ?? '';
            if (empty($category)) {
                jsonError('Category is required');
            }
            $items = dbQuery("SELECT * FROM lsb_rfq_reference WHERE category = ? ORDER BY sort_order, id", [$category]);
            jsonSuccess($items);
            break;

        case 'categories':
            // 获取所有分类
            $categories = dbQuery("
                SELECT DISTINCT category, category_name, category_name_cn
                FROM lsb_rfq_reference
                ORDER BY category
            ");
            jsonSuccess($categories);
            break;

        case 'create':
            // 创建新参数
            $category = trim($_POST['category'] ?? '');
            $code = trim($_POST['code'] ?? '');
            $valueCn = trim($_POST['value_cn'] ?? '');
            $valueEn = trim($_POST['value_en'] ?? '');
            $sortOrder = intval($_POST['sort_order'] ?? 10);
            $isDefault = intval($_POST['is_default'] ?? 0);

            if (empty($category) || empty($code) || empty($valueCn) || empty($valueEn)) {
                jsonError('All fields are required');
            }

            // 检查代码是否重复
            $existing = dbQueryOne("SELECT id FROM lsb_rfq_reference WHERE category = ? AND code = ?", [$category, $code]);
            if ($existing) {
                jsonError('Code already exists in this category');
            }

            // 获取分类信息
            $catInfo = dbQueryOne("SELECT category_name, category_name_cn FROM lsb_rfq_reference WHERE category = ? LIMIT 1", [$category]);
            $categoryName = $catInfo['category_name'] ?? $category;
            $categoryNameCn = $catInfo['category_name_cn'] ?? $category;

            // 如果设为默认，先清除其他默认值
            if ($isDefault) {
                dbExecute("UPDATE lsb_rfq_reference SET is_default = 0 WHERE category = ?", [$category]);
            }

            // 插入新记录
            $sql = "INSERT INTO lsb_rfq_reference (category, category_name, category_name_cn, code, value_cn, value_en, sort_order, is_default)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            dbExecute($sql, [$category, $categoryName, $categoryNameCn, $code, $valueCn, $valueEn, $sortOrder, $isDefault]);

            $newId = dbLastInsertId();
            jsonSuccess(['id' => $newId], 'Created successfully');
            break;

        case 'update':
            // 更新参数
            $id = intval($_POST['id'] ?? 0);
            $code = trim($_POST['code'] ?? '');
            $valueCn = trim($_POST['value_cn'] ?? '');
            $valueEn = trim($_POST['value_en'] ?? '');
            $sortOrder = intval($_POST['sort_order'] ?? 10);
            $isDefault = intval($_POST['is_default'] ?? 0);

            if ($id <= 0) {
                jsonError('Invalid ID');
            }

            if (empty($code) || empty($valueCn) || empty($valueEn)) {
                jsonError('All fields are required');
            }

            // 获取当前记录
            $current = dbQueryOne("SELECT * FROM lsb_rfq_reference WHERE id = ?", [$id]);
            if (!$current) {
                jsonError('Item not found');
            }

            // 检查代码是否重复（排除自身）
            $existing = dbQueryOne("SELECT id FROM lsb_rfq_reference WHERE category = ? AND code = ? AND id != ?",
                [$current['category'], $code, $id]);
            if ($existing) {
                jsonError('Code already exists in this category');
            }

            // 如果设为默认，先清除其他默认值
            if ($isDefault) {
                dbExecute("UPDATE lsb_rfq_reference SET is_default = 0 WHERE category = ?", [$current['category']]);
            }

            // 更新记录
            $sql = "UPDATE lsb_rfq_reference SET code = ?, value_cn = ?, value_en = ?, sort_order = ?, is_default = ? WHERE id = ?";
            dbExecute($sql, [$code, $valueCn, $valueEn, $sortOrder, $isDefault, $id]);

            jsonSuccess(['id' => $id], 'Updated successfully');
            break;

        case 'update_sort':
            // 只更新排序
            $id = intval($_POST['id'] ?? 0);
            $sortOrder = intval($_POST['sort_order'] ?? 0);

            if ($id <= 0) {
                jsonError('Invalid ID');
            }

            dbExecute("UPDATE lsb_rfq_reference SET sort_order = ? WHERE id = ?", [$sortOrder, $id]);
            jsonSuccess(['id' => $id], 'Sort order updated');
            break;

        case 'set_default':
            // 设置默认值
            $id = intval($_POST['id'] ?? 0);
            $category = trim($_POST['category'] ?? '');

            if ($id <= 0 || empty($category)) {
                jsonError('Invalid parameters');
            }

            // 先清除该分类的所有默认值
            dbExecute("UPDATE lsb_rfq_reference SET is_default = 0 WHERE category = ?", [$category]);

            // 设置新的默认值
            dbExecute("UPDATE lsb_rfq_reference SET is_default = 1 WHERE id = ?", [$id]);

            jsonSuccess(['id' => $id], 'Default set successfully');
            break;

        case 'delete':
            // 删除参数
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                jsonError('Invalid ID');
            }

            dbExecute("DELETE FROM lsb_rfq_reference WHERE id = ?", [$id]);
            jsonSuccess(['id' => $id], 'Deleted successfully');
            break;

        default:
            jsonError('Invalid action');
    }
} catch (Exception $e) {
    jsonError('Error: ' . $e->getMessage());
}
