<?php
/**
 * 公共函数库
 * LSB RFQ System V3.1
 */

require_once dirname(__DIR__) . '/config/database.php';

/**
 * 安全输出HTML
 */
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * 格式化文件大小
 */
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

/**
 * 获取当前语言设置
 */
function getLang() {
    return $GLOBALS['current_lang'] ?? 'both';
}

/**
 * 获取标签文本 (根据语言设置)
 * @param string $category 分类 (如 'fields', 'sections', 'buttons')
 * @param string $key 字段键名
 * @return string 标签文本
 */
function L($category, $key) {
    $lang = getLang();
    $labels = $GLOBALS['lang_labels'] ?? [];

    if (!isset($labels[$category][$key])) {
        return $key; // 如果未找到，返回键名
    }

    $item = $labels[$category][$key];

    if ($lang === 'en') {
        return $item['en'] ?? $item['cn'] ?? $key;
    } elseif ($lang === 'cn') {
        return $item['cn'] ?? $key;
    } else {
        // both: 显示中英文
        $cn = $item['cn'] ?? '';
        $en = $item['en'] ?? '';
        if ($cn && $en && $cn !== $en) {
            return $cn . ' ' . $en;
        }
        return $cn ?: $en ?: $key;
    }
}

/**
 * 获取字段标签
 */
function FL($key) {
    return L('fields', $key);
}

/**
 * 获取区块标签
 */
function SL($key) {
    return L('sections', $key);
}

/**
 * 获取按钮标签
 */
function BL($key) {
    return L('buttons', $key);
}

/**
 * 获取通用标签
 */
function CL($key) {
    return L('common', $key);
}

/**
 * 获取区块标题 (根据语言设置)
 * 优先从 labels.json 的 sections 分类中查找，找不到时使用传入的默认值
 * @param string $cn 中文标题 (默认值)
 * @param string $en 英文标题 (默认值)
 * @param string $key 可选的标签键名，用于从labels.json查找
 */
function sectionTitle($cn, $en, $key = null) {
    $lang = getLang();
    $labels = $GLOBALS['lang_labels'] ?? [];

    // 尝试从 labels.json 中查找
    $lookupKey = $key;
    if (!$lookupKey) {
        // 将英文标签转换为 snake_case 作为查找键
        $lookupKey = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($en)));
        $lookupKey = trim($lookupKey, '_');
    }

    // 在 sections 分类中查找
    if (isset($labels['sections'][$lookupKey])) {
        $item = $labels['sections'][$lookupKey];
        $cn = $item['cn'] ?? $cn;
        $en = $item['en'] ?? $en;
    }

    if ($lang === 'en') {
        return $en;
    } elseif ($lang === 'cn') {
        return $cn;
    }
    return $cn . ' ' . $en;
}

/**
 * 获取字段标签HTML (支持中英文切换)
 * 使用统一的 rfqLabelHtml() 函数，优先从 rfq_schema.php 查找，然后 labels.json，最后使用传入的默认值
 * @param string $cn 中文标签 (默认值)
 * @param string $en 英文标签 (默认值)
 * @param string $key 可选的标签键名，用于从schema/labels查找
 */
function fieldLabel($cn, $en, $key = null) {
    // 如果 rfqLabelHtml 函数可用，使用它
    if (function_exists('rfqLabelHtml')) {
        return rfqLabelHtml($cn, $en, $key);
    }

    // 回退到原始实现（当 rfq_render.php 未加载时）
    $lang = getLang();
    $labels = $GLOBALS['lang_labels'] ?? [];

    $lookupKey = $key;
    if (!$lookupKey) {
        $lookupKey = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($en)));
        $lookupKey = trim($lookupKey, '_');
    }

    if (isset($labels['fields'][$lookupKey])) {
        $item = $labels['fields'][$lookupKey];
        $cn = $item['cn'] ?? $cn;
        $en = $item['en'] ?? $en;
    }

    if ($lang === 'en') {
        return $en;
    } elseif ($lang === 'cn') {
        return $cn;
    }
    return '<span class="form-label-cn">' . $cn . '</span><span class="form-label-en">' . $en . '</span>';
}

/**
 * 获取开关/复选框标签 (根据语言设置)
 * 优先从 labels.json 的 fields 分类中查找，找不到时使用传入的默认值
 * @param string $cn 中文标签 (默认值)
 * @param string $en 英文标签 (默认值)
 * @param string $key 可选的标签键名，用于从labels.json查找
 */
function switchLabel($cn, $en, $key = null) {
    $lang = getLang();
    $labels = $GLOBALS['lang_labels'] ?? [];

    // 尝试从 labels.json 中查找
    $lookupKey = $key;
    if (!$lookupKey) {
        // 将英文标签转换为 snake_case 作为查找键
        $lookupKey = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($en)));
        $lookupKey = trim($lookupKey, '_');
    }

    // 在 fields 分类中查找
    if (isset($labels['fields'][$lookupKey])) {
        $item = $labels['fields'][$lookupKey];
        $cn = $item['cn'] ?? $cn;
        $en = $item['en'] ?? $en;
    }

    if ($lang === 'en') {
        return $en;
    } elseif ($lang === 'cn') {
        return $cn;
    }
    return $cn . ' ' . $en;
}

/**
 * 生成RFQ编号
 */
function generateRfqNo() {
    $prefix = 'RFQ';
    $year = date('Y');
    $month = date('m');

    // 获取当月最大序号
    $sql = "SELECT MAX(CAST(SUBSTRING(rfq_no, 12) AS UNSIGNED)) as max_seq
            FROM lsb_rfq_main
            WHERE rfq_no LIKE ?";
    $result = dbQueryOne($sql, [$prefix . $year . $month . '%']);

    $seq = ($result['max_seq'] ?? 0) + 1;
    return sprintf('%s%s%s%04d', $prefix, $year, $month, $seq);
}

/**
 * 获取参数选项列表
 * @param string $category 分类名
 * @param string|null $lang 语言设置，null则使用当前语言
 */
function getRefOptions($category, $lang = null) {
    if ($lang === null) {
        $lang = getLang();
    }

    $sql = "SELECT code, value_cn, value_en, is_default
            FROM lsb_rfq_reference
            WHERE category = ? AND is_active = 1
            ORDER BY sort_order";
    $rows = dbQuery($sql, [$category]);

    $options = [];
    foreach ($rows as $row) {
        if ($lang === 'both') {
            // 中英文都显示，格式: "中文 English"
            $label = $row['value_cn'];
            if (!empty($row['value_en']) && $row['value_en'] !== $row['value_cn']) {
                $label .= ' ' . $row['value_en'];
            }
        } elseif ($lang === 'en') {
            $label = $row['value_en'] ?? $row['value_cn'];
        } else {
            $label = $row['value_cn'];
        }
        $options[] = [
            'value' => $row['code'],
            'label' => $label,
            'default' => $row['is_default']
        ];
    }
    return $options;
}

/**
 * 获取参数值显示文本
 * @param string $category 分类名
 * @param string $code 代码值
 * @param string|null $lang 语言设置，null则使用当前语言
 */
function getRefValue($category, $code, $lang = null) {
    if (empty($code)) return '';

    if ($lang === null) {
        $lang = getLang();
    }

    $sql = "SELECT value_cn, value_en FROM lsb_rfq_reference
            WHERE category = ? AND code = ?";
    $row = dbQueryOne($sql, [$category, $code]);

    if (!$row) return $code;

    if ($lang === 'both') {
        $label = $row['value_cn'];
        if (!empty($row['value_en']) && $row['value_en'] !== $row['value_cn']) {
            $label .= ' ' . $row['value_en'];
        }
        return $label;
    } elseif ($lang === 'en') {
        return $row['value_en'] ?? $row['value_cn'];
    }
    return $row['value_cn'];
}

/**
 * 获取RFQ完整数据
 */
function getRfqFullData($rfqId) {
    // 主表
    $main = dbQueryOne("SELECT * FROM lsb_rfq_main WHERE id = ?", [$rfqId]);
    if (!$main) return null;

    // 报价资料 Order Entry
    $orderEntry = dbQueryOne("SELECT * FROM lsb_rfq_order_entry WHERE rfq_id = ?", [$rfqId]);

    // 钢结构
    $steel = dbQueryOne("SELECT * FROM lsb_rfq_steel WHERE rfq_id = ?", [$rfqId]);

    // 围护系统
    $envelope = dbQueryOne("SELECT * FROM lsb_rfq_envelope WHERE rfq_id = ?", [$rfqId]);

    // 板材
    $panels = dbQuery("SELECT * FROM lsb_rfq_panel WHERE rfq_id = ? ORDER BY panel_category, panel_type, panel_no", [$rfqId]);

    // 保温棉
    $insulations = dbQuery("SELECT * FROM lsb_rfq_insulation WHERE rfq_id = ? ORDER BY insulation_category, insulation_no", [$rfqId]);

    // 做法
    $methods = dbQuery("SELECT * FROM lsb_rfq_method WHERE rfq_id = ? ORDER BY method_category, method_no", [$rfqId]);

    // 排水
    $drainages = dbQuery("SELECT * FROM lsb_rfq_drainage WHERE rfq_id = ?", [$rfqId]);

    // 备注
    $remarks = dbQuery("SELECT * FROM lsb_rfq_remarks WHERE rfq_id = ? ORDER BY remark_type, remark_no", [$rfqId]);

    // 文件
    $files = dbQuery("SELECT * FROM lsb_rfq_files WHERE rfq_id = ? AND is_active = 1", [$rfqId]);

    // V3.2: 板材规格明细
    $claddingSpecs = dbQuery("SELECT * FROM lsb_rfq_cladding_spec WHERE rfq_id = ? ORDER BY system_type, layer_position, sort_order", [$rfqId]);

    // V3.2: 构造做法
    $claddingMethods = dbQuery("SELECT * FROM lsb_rfq_cladding_method WHERE rfq_id = ? ORDER BY system_type, sort_order", [$rfqId]);

    // V3.2: 补充说明
    $supplements = dbQuery("SELECT * FROM lsb_rfq_supplements WHERE rfq_id = ? ORDER BY category, importance DESC, sort_order", [$rfqId]);

    // V3.2: 变更记录
    $changeLogs = dbQuery("SELECT * FROM lsb_rfq_change_log WHERE rfq_id = ? ORDER BY version DESC, changed_at DESC", [$rfqId]);

    return [
        'main' => $main,
        'order_entry' => $orderEntry,
        'steel' => $steel,
        'envelope' => $envelope,
        'panels' => $panels,
        'insulations' => $insulations,
        'methods' => $methods,
        'drainages' => $drainages,
        'remarks' => $remarks,
        'files' => $files,
        // V3.2 新增
        'cladding_specs' => $claddingSpecs,
        'cladding_methods' => $claddingMethods,
        'supplements' => $supplements,
        'change_logs' => $changeLogs
    ];
}

/**
 * 保存RFQ完整数据
 */
function saveRfqData($data, $rfqId = null) {
    $pdo = getDB();
    $pdo->beginTransaction();

    try {
        // 保存主表
        $mainData = $data['main'] ?? [];
        if ($rfqId) {
            // 编辑模式
            unset($mainData['id']);

            // 检查数据库中的 rfq_no 是否为空，如果为空则生成新的
            $existing = dbQueryOne("SELECT rfq_no FROM lsb_rfq_main WHERE id = ?", [$rfqId]);
            if ($existing && empty($existing['rfq_no']) && empty($mainData['rfq_no'])) {
                $mainData['rfq_no'] = generateRfqNo();
            }

            dbUpdate('lsb_rfq_main', $mainData, 'id = ?', [$rfqId]);
        } else {
            // 新建模式
            if (empty($mainData['rfq_no'])) {
                $mainData['rfq_no'] = generateRfqNo();
            }
            $rfqId = dbInsert('lsb_rfq_main', $mainData);
        }

        // 保存报价资料 Order Entry
        if (isset($data['order_entry'])) {
            $orderEntryData = $data['order_entry'];
            $orderEntryData['rfq_id'] = $rfqId;
            $existing = dbQueryOne("SELECT id FROM lsb_rfq_order_entry WHERE rfq_id = ?", [$rfqId]);
            if ($existing) {
                unset($orderEntryData['id']);
                dbUpdate('lsb_rfq_order_entry', $orderEntryData, 'rfq_id = ?', [$rfqId]);
            } else {
                dbInsert('lsb_rfq_order_entry', $orderEntryData);
            }
        }

        // 保存钢结构
        if (isset($data['steel'])) {
            $steelData = $data['steel'];
            $steelData['rfq_id'] = $rfqId;
            $existing = dbQueryOne("SELECT id FROM lsb_rfq_steel WHERE rfq_id = ?", [$rfqId]);
            if ($existing) {
                unset($steelData['id']);
                dbUpdate('lsb_rfq_steel', $steelData, 'rfq_id = ?', [$rfqId]);
            } else {
                dbInsert('lsb_rfq_steel', $steelData);
            }
        }

        // 保存围护系统
        if (isset($data['envelope'])) {
            $envelopeData = $data['envelope'];
            $envelopeData['rfq_id'] = $rfqId;
            $existing = dbQueryOne("SELECT id FROM lsb_rfq_envelope WHERE rfq_id = ?", [$rfqId]);
            if ($existing) {
                unset($envelopeData['id']);
                dbUpdate('lsb_rfq_envelope', $envelopeData, 'rfq_id = ?', [$rfqId]);
            } else {
                dbInsert('lsb_rfq_envelope', $envelopeData);
            }
        }

        // 保存板材 (先删除再插入)
        if (isset($data['panels'])) {
            dbDelete('lsb_rfq_panel', 'rfq_id = ?', [$rfqId]);
            foreach ($data['panels'] as $panel) {
                $panel['rfq_id'] = $rfqId;
                unset($panel['id']);
                dbInsert('lsb_rfq_panel', $panel);
            }
        }

        // 保存保温棉
        if (isset($data['insulations'])) {
            dbDelete('lsb_rfq_insulation', 'rfq_id = ?', [$rfqId]);
            foreach ($data['insulations'] as $insulation) {
                $insulation['rfq_id'] = $rfqId;
                unset($insulation['id']);
                dbInsert('lsb_rfq_insulation', $insulation);
            }
        }

        // 保存做法
        if (isset($data['methods'])) {
            dbDelete('lsb_rfq_method', 'rfq_id = ?', [$rfqId]);
            foreach ($data['methods'] as $method) {
                $method['rfq_id'] = $rfqId;
                unset($method['id']);
                dbInsert('lsb_rfq_method', $method);
            }
        }

        // 保存排水
        if (isset($data['drainages'])) {
            dbDelete('lsb_rfq_drainage', 'rfq_id = ?', [$rfqId]);
            foreach ($data['drainages'] as $drainage) {
                $drainage['rfq_id'] = $rfqId;
                unset($drainage['id']);
                dbInsert('lsb_rfq_drainage', $drainage);
            }
        }

        // 保存备注
        if (isset($data['remarks'])) {
            dbDelete('lsb_rfq_remarks', 'rfq_id = ?', [$rfqId]);
            foreach ($data['remarks'] as $remark) {
                $remark['rfq_id'] = $rfqId;
                unset($remark['id']);
                dbInsert('lsb_rfq_remarks', $remark);
            }
        }

        // V3.2: 保存板材规格明细
        if (isset($data['cladding_specs'])) {
            dbDelete('lsb_rfq_cladding_spec', 'rfq_id = ?', [$rfqId]);
            foreach ($data['cladding_specs'] as $spec) {
                $spec['rfq_id'] = $rfqId;
                unset($spec['id']);
                // 处理 layer_composition JSON
                if (isset($spec['layer_composition']) && is_array($spec['layer_composition'])) {
                    $spec['layer_composition'] = json_encode($spec['layer_composition'], JSON_UNESCAPED_UNICODE);
                }
                dbInsert('lsb_rfq_cladding_spec', $spec);
            }
        }

        // V3.2: 保存构造做法
        if (isset($data['cladding_methods'])) {
            dbDelete('lsb_rfq_cladding_method', 'rfq_id = ?', [$rfqId]);
            foreach ($data['cladding_methods'] as $method) {
                $method['rfq_id'] = $rfqId;
                unset($method['id']);
                // 处理 layer_composition JSON
                if (isset($method['layer_composition']) && is_array($method['layer_composition'])) {
                    $method['layer_composition'] = json_encode($method['layer_composition'], JSON_UNESCAPED_UNICODE);
                }
                dbInsert('lsb_rfq_cladding_method', $method);
            }
        }

        // V3.2: 保存补充说明
        if (isset($data['supplements'])) {
            dbDelete('lsb_rfq_supplements', 'rfq_id = ?', [$rfqId]);
            foreach ($data['supplements'] as $supplement) {
                $supplement['rfq_id'] = $rfqId;
                unset($supplement['id']);
                dbInsert('lsb_rfq_supplements', $supplement);
            }
        }

        $pdo->commit();
        return $rfqId;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * 导出RFQ为JSON
 */
function exportRfqJson($rfqId) {
    $data = getRfqFullData($rfqId);
    if (!$data) return null;

    // 移除敏感字段
    unset($data['main']['created_by']);

    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * 从JSON导入RFQ
 */
function importRfqJson($jsonString, $asNew = true) {
    $data = json_decode($jsonString, true);
    if (!$data || !isset($data['main'])) {
        throw new Exception('Invalid JSON format');
    }

    if ($asNew) {
        // 作为新记录导入
        unset($data['main']['id']);
        $data['main']['rfq_no'] = generateRfqNo();
        $data['main']['status'] = 'draft';
        return saveRfqData($data);
    } else {
        // 更新现有记录
        $rfqId = $data['main']['id'] ?? null;
        if (!$rfqId) {
            throw new Exception('RFQ ID is required for update');
        }
        return saveRfqData($data, $rfqId);
    }
}

/**
 * 获取RFQ列表
 */
function getRfqList($page = 1, $pageSize = 20, $filters = []) {
    $where = ['1=1'];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = 'm.status = ?';
        $params[] = $filters['status'];
    }

    if (!empty($filters['search'])) {
        $where[] = '(m.rfq_no LIKE ? OR m.project_name LIKE ? OR m.job_number LIKE ? OR m.contact_to LIKE ?)';
        $search = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    $whereClause = implode(' AND ', $where);

    // 总数
    $countSql = "SELECT COUNT(*) as total FROM lsb_rfq_main m WHERE $whereClause";
    $total = dbQueryOne($countSql, $params)['total'];

    // 分页数据
    $offset = ($page - 1) * $pageSize;
    $sql = "SELECT m.id, m.rfq_no, m.job_number, m.project_name, m.project_location,
                   m.building_qty, m.due_date, m.status, m.created_at,
                   m.contact_to, m.attn, m.account_manager,
                   s.length, s.width, s.eave_height
            FROM lsb_rfq_main m
            LEFT JOIN lsb_rfq_steel s ON m.id = s.rfq_id
            WHERE $whereClause
            ORDER BY m.created_at DESC
            LIMIT $pageSize OFFSET $offset";
    $list = dbQuery($sql, $params);

    return [
        'list' => $list,
        'total' => $total,
        'page' => $page,
        'pageSize' => $pageSize,
        'totalPages' => ceil($total / $pageSize)
    ];
}

/**
 * 获取板材数据（按类型分组）
 */
function getPanelsByType($panels) {
    $grouped = [
        'roof' => ['outer' => [], 'liner' => []],
        'wall' => ['outer' => [], 'liner' => [], 'parapet_liner' => [], 'partition' => []],
        'canopy' => ['upper' => [], 'lower' => []]
    ];

    foreach ($panels as $panel) {
        $cat = $panel['panel_category'];
        $type = $panel['panel_type'];
        if (isset($grouped[$cat][$type])) {
            $grouped[$cat][$type][] = $panel;
        }
    }

    return $grouped;
}

/**
 * 获取保温棉数据（按类型分组）
 */
function getInsulationsByType($insulations) {
    $grouped = ['roof' => [], 'wall' => []];

    foreach ($insulations as $ins) {
        $cat = $ins['insulation_category'];
        if (isset($grouped[$cat])) {
            $grouped[$cat][] = $ins;
        }
    }

    return $grouped;
}

/**
 * 获取做法数据（按类型分组）
 */
function getMethodsByType($methods) {
    $grouped = ['roof' => [], 'wall' => []];

    foreach ($methods as $method) {
        $cat = $method['method_category'];
        if (isset($grouped[$cat])) {
            $grouped[$cat][] = $method;
        }
    }

    return $grouped;
}

/**
 * JSON响应
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 成功响应
 */
function jsonSuccess($data = null, $message = 'Success') {
    jsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
}

/**
 * 错误响应
 */
function jsonError($message, $code = 400) {
    jsonResponse(['success' => false, 'message' => $message], $code);
}
