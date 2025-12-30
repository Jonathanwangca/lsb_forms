<?php
/**
 * RFQ API 接口
 * LSB RFQ System V3.2
 */
require_once dirname(__DIR__) . '/includes/functions.php';

// 获取请求方法和action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'save':
        case 'save_draft':
            handleSave();
            break;

        case 'delete':
            handleDelete();
            break;

        case 'export':
            handleExport();
            break;

        case 'import':
            handleImport();
            break;

        case 'get':
            handleGet();
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
 * 保存RFQ
 */
function handleSave() {
    $rfqId = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // 构建数据结构
    $data = [];

    // 主表数据
    if (isset($_POST['main'])) {
        $main = $_POST['main'];
        // 处理复选框字段 (建筑结构特征 + 报价范围)
        $checkboxFields = [
            // 建筑结构特征
            'pre_eng_building', 'bridge_crane', 'mezzanine_steels', 'factory_mutual',
            'loading_canopy', 'future_expansion', 'parapet', 'concrete_wall_curb', 'leed',
            // 报价范围
            'steel_deck', 'partition_wall_frame', 'door_window', 'top_coat', 'louver',
            'cable_tray_support', 'railing', 'glazing_curtain_wall', 'aluminum_cladding',
            'laboratory_inspect'
        ];
        foreach ($checkboxFields as $field) {
            $main[$field] = isset($main[$field]) ? 1 : null;
        }
        // 处理可选的下拉字段 (空值转NULL)
        $optionalFields = ['erection', 'scope_type'];
        foreach ($optionalFields as $field) {
            if (isset($main[$field]) && $main[$field] === '') {
                $main[$field] = null;
            }
        }
        $data['main'] = $main;
    }

    // 报价资料 Order Entry 数据
    if (isset($_POST['order_entry'])) {
        $orderEntry = $_POST['order_entry'];
        $orderEntryCheckboxes = [
            'mbs_drawing', 'architect_drawing', 'foundation_design',
            'autocad_drawing', 'fm_report', 'other_docs'
        ];
        foreach ($orderEntryCheckboxes as $field) {
            $orderEntry[$field] = isset($orderEntry[$field]) ? 1 : null;
        }
        $data['order_entry'] = $orderEntry;
    }

    // 钢结构数据
    if (isset($_POST['steel'])) {
        $steel = $_POST['steel'];
        // 处理布尔字段
        $steelBoolFields = ['roof_purlin_galvanized', 'wall_purlin_galvanized', 'fire_coating_na'];
        foreach ($steelBoolFields as $field) {
            if (isset($steel[$field]) && $steel[$field] !== '') {
                $steel[$field] = intval($steel[$field]);
            } else {
                $steel[$field] = null;
            }
        }
        // 处理空字符串转NULL
        foreach ($steel as $key => $value) {
            if ($value === '') {
                $steel[$key] = null;
            }
        }
        $data['steel'] = $steel;
    }

    // 围护系统数据
    if (isset($_POST['envelope'])) {
        $envelope = $_POST['envelope'];
        $envelopeCheckboxes = [
            'aclok_roof', 'sandwich_panel', 'roof_ventilator', 'roof_opening',
            'roof_skylight', 'roof_ridge_lantern', 'pv_system',
            'waterproof_standard', 'roof_waterproof_membrane', 'roof_vapor_barrier',
            'canopy_has_insulation',
            'is_renovation', 'structural_reinforcement', 'cladding_addition',
            'reuse', 'mep_installation', 'renovation_other'
        ];
        foreach ($envelopeCheckboxes as $field) {
            $envelope[$field] = isset($envelope[$field]) ? 1 : null;
        }
        // 处理布尔下拉字段
        $envelopeBoolFields = ['skylight_fm_certified'];
        foreach ($envelopeBoolFields as $field) {
            if (isset($envelope[$field]) && $envelope[$field] !== '') {
                $envelope[$field] = intval($envelope[$field]);
            } else {
                $envelope[$field] = null;
            }
        }
        // 处理空字符串转NULL
        foreach ($envelope as $key => $value) {
            if ($value === '') {
                $envelope[$key] = null;
            }
        }
        $data['envelope'] = $envelope;
    }

    // 板材数据
    if (isset($_POST['panels'])) {
        $data['panels'] = array_filter($_POST['panels'], function($p) {
            return !empty($p['panel_type']);
        });
        // 移除UI辅助字段，不保存到数据库
        foreach ($data['panels'] as &$panel) {
            unset($panel['panel_type_select']);
        }
        unset($panel);
    }

    // 保温棉数据
    if (isset($_POST['insulations'])) {
        $data['insulations'] = array_filter($_POST['insulations'], function($i) {
            return !empty($i['thickness']);
        });
    }

    // 做法数据
    if (isset($_POST['methods'])) {
        $data['methods'] = array_filter($_POST['methods'], function($m) {
            return !empty($m['method_desc']);
        });
    }

    // 排水数据
    if (isset($_POST['drainages'])) {
        $data['drainages'] = array_filter($_POST['drainages'], function($d) {
            return !empty($d['method']);
        });
    }

    // 备注数据
    if (isset($_POST['remarks'])) {
        $data['remarks'] = array_filter($_POST['remarks'], function($r) {
            return !empty($r['remark_content']);
        });
    }

    // V3.2: 板材规格明细
    if (isset($_POST['cladding_specs'])) {
        $data['cladding_specs'] = array_filter($_POST['cladding_specs'], function($s) {
            return !empty($s['system_type']);
        });
        // 处理复选框字段
        foreach ($data['cladding_specs'] as &$spec) {
            $spec['fm_approved'] = isset($spec['fm_approved']) ? 1 : null;
            // 处理空字符串转NULL
            foreach ($spec as $key => $value) {
                if ($value === '') {
                    $spec[$key] = null;
                }
            }
        }
        unset($spec);
    }

    // V3.2: 构造做法
    if (isset($_POST['cladding_methods'])) {
        $data['cladding_methods'] = array_filter($_POST['cladding_methods'], function($m) {
            return !empty($m['system_type']) && !empty($m['method_name']);
        });
    }

    // V3.2: 补充说明
    if (isset($_POST['supplements'])) {
        $data['supplements'] = array_filter($_POST['supplements'], function($s) {
            return !empty($s['content']);
        });
        // 处理空字符串转NULL
        foreach ($data['supplements'] as &$supp) {
            foreach ($supp as $key => $value) {
                if ($value === '') {
                    $supp[$key] = null;
                }
            }
        }
        unset($supp);
    }

    // 保存
    $savedId = saveRfqData($data, $rfqId ?: null);

    // 处理文件上传
    if (!empty($_FILES['files'])) {
        handleFileUploads($savedId, $_FILES['files']);
    }

    // 获取保存后的完整数据
    $rfq = dbQueryOne("SELECT id, rfq_no FROM lsb_rfq_main WHERE id = ?", [$savedId]);

    // 如果是表单提交，重定向到编辑页面
    if (isset($_POST['action']) && $_POST['action'] === 'save' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Location: /aiforms/rfq/form_rfq.php?id=' . $savedId . '&saved=1');
        exit;
    }

    jsonSuccess([
        'id' => $savedId,
        'rfq_no' => $rfq['rfq_no']
    ], 'RFQ saved successfully');
}

/**
 * 处理多文件上传
 */
function handleFileUploads($rfqId, $filesArray) {
    $allowedExts = ['pdf', 'dwg', 'dxf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', '7z', 'jpg', 'jpeg', 'png', 'gif'];
    $maxSize = 50 * 1024 * 1024; // 50MB

    // 创建上传目录
    $uploadDir = dirname(__DIR__) . '/uploads/rfq/' . $rfqId;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 文件分类标签
    $categoryLabels = [
        'mbs_drawing' => 'MBS结构图纸',
        'architect_drawing' => '建筑蓝图',
        'foundation_design' => '结构蓝图',
        'autocad_drawing' => 'AutoCAD建筑图纸',
        'fm_report' => 'FM报告',
        'other_docs' => '其他文件'
    ];

    // 遍历每个分类
    foreach ($filesArray as $category => $categoryFiles) {
        // 处理多文件上传格式
        if (!isset($categoryFiles['name'])) continue;

        $fileCount = is_array($categoryFiles['name']) ? count($categoryFiles['name']) : 1;

        for ($i = 0; $i < $fileCount; $i++) {
            // 获取文件信息
            if (is_array($categoryFiles['name'])) {
                $fileName = $categoryFiles['name'][$i];
                $tmpName = $categoryFiles['tmp_name'][$i];
                $fileSize = $categoryFiles['size'][$i];
                $error = $categoryFiles['error'][$i];
            } else {
                $fileName = $categoryFiles['name'];
                $tmpName = $categoryFiles['tmp_name'];
                $fileSize = $categoryFiles['size'];
                $error = $categoryFiles['error'];
            }

            // 跳过空文件或错误
            if (empty($fileName) || $error !== UPLOAD_ERR_OK) {
                continue;
            }

            // 验证文件扩展名
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts)) {
                continue;
            }

            // 验证文件大小
            if ($fileSize > $maxSize) {
                continue;
            }

            // 生成唯一文件名
            $newFileName = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
            $filePath = $uploadDir . '/' . $newFileName;
            $relativePath = '/aiforms/uploads/rfq/' . $rfqId . '/' . $newFileName;

            // 移动文件
            if (!move_uploaded_file($tmpName, $filePath)) {
                continue;
            }

            // 获取MIME类型
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            // 保存到数据库
            dbInsert('lsb_rfq_files', [
                'rfq_id' => $rfqId,
                'file_name' => $fileName,
                'file_path' => $relativePath,
                'file_size' => $fileSize,
                'file_type' => $mimeType,
                'file_ext' => $ext,
                'file_category' => $category,
                'file_category_name' => $categoryLabels[$category] ?? $category,
                'upload_date' => date('Y-m-d'),
                'upload_datetime' => date('Y-m-d H:i:s'),
                'upload_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'is_active' => 1
            ]);
        }
    }
}

/**
 * 删除RFQ
 */
function handleDelete() {
    $input = json_decode(file_get_contents('php://input'), true);
    $rfqId = $input['id'] ?? intval($_POST['id'] ?? 0);

    if (!$rfqId) {
        jsonError('RFQ ID is required', 400);
    }

    $deleted = dbDelete('lsb_rfq_main', 'id = ?', [$rfqId]);

    if ($deleted) {
        jsonSuccess(null, 'RFQ deleted successfully');
    } else {
        jsonError('RFQ not found', 404);
    }
}

/**
 * 导出RFQ为JSON
 */
function handleExport() {
    $rfqId = intval($_GET['id'] ?? 0);

    if (!$rfqId) {
        jsonError('RFQ ID is required', 400);
    }

    $data = getRfqFullData($rfqId);

    if (!$data) {
        jsonError('RFQ not found', 404);
    }

    // 清理不需要导出的字段
    unset($data['main']['created_by']);

    // 添加导出信息
    $data['_export'] = [
        'exported_at' => date('Y-m-d H:i:s'),
        'version' => '3.2'
    ];

    // 设置下载头
    $filename = 'RFQ_' . $data['main']['rfq_no'] . '_' . date('Ymd') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 导入RFQ
 */
function handleImport() {
    $data = null;
    $mode = $_POST['mode'] ?? 'new';

    // 支持两种方式：文件上传或JSON字符串
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // 文件上传方式
        $jsonContent = file_get_contents($_FILES['file']['tmp_name']);
        $data = json_decode($jsonContent, true);
    } elseif (isset($_POST['data'])) {
        // JSON字符串方式
        $data = json_decode($_POST['data'], true);
    } else {
        jsonError('No data provided', 400);
    }

    if (!$data || !isset($data['main'])) {
        jsonError('Invalid JSON format', 400);
    }

    $rfqId = null;

    if ($mode === 'update' && !empty($data['main']['rfq_no'])) {
        // 更新模式：查找现有 RFQ
        $existing = dbQueryOne("SELECT id FROM lsb_rfq_main WHERE rfq_no = ?", [$data['main']['rfq_no']]);
        if ($existing) {
            $rfqId = $existing['id'];
        } else {
            jsonError('RFQ not found: ' . $data['main']['rfq_no'], 404);
        }
    } else {
        // 新建模式：生成新 RFQ 编号
        unset($data['main']['id']);
        $data['main']['rfq_no'] = generateRfqNo();
        $data['main']['status'] = 'draft';
    }

    // 清理导出信息
    unset($data['_export']);

    // 保存数据
    $savedId = saveRfqData($data, $rfqId);

    // 获取保存后的 RFQ 编号
    $rfq = dbQueryOne("SELECT rfq_no FROM lsb_rfq_main WHERE id = ?", [$savedId]);

    jsonSuccess([
        'id' => $savedId,
        'rfq_no' => $rfq['rfq_no'],
        'mode' => $mode
    ], $mode === 'update' ? 'RFQ updated successfully' : 'RFQ imported successfully');
}

/**
 * 获取单个RFQ
 */
function handleGet() {
    $rfqId = intval($_GET['id'] ?? 0);

    if (!$rfqId) {
        jsonError('RFQ ID is required', 400);
    }

    $data = getRfqFullData($rfqId);

    if (!$data) {
        jsonError('RFQ not found', 404);
    }

    jsonSuccess($data);
}

/**
 * 获取RFQ列表
 */
function handleList() {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = isset($_GET['pageSize']) ? min(100, max(1, intval($_GET['pageSize']))) : 20;

    $filters = [];
    if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
    if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];

    $result = getRfqList($page, $pageSize, $filters);

    jsonSuccess($result);
}
