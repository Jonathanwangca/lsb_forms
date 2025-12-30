<?php
/**
 * RFQ 数据导入帮助函数
 * 用于规范化 Excel 占位符 ("-", "0", "无" 等) 到数据库值
 *
 * Excel 占位符说明:
 * - "-" : 通常表示未选择/未设置
 * - "0" : 通常表示"否"或"不适用"
 * - "1" : 通常表示"是"或"已选择"
 * - "无" : 表示没有此项
 * - 空白 : 未填写
 */

/**
 * 规范化布尔值
 * Excel中的"-"/"0"/"无"/空白 → NULL 或 false
 * Excel中的"1"/"是"/"有" → true
 *
 * @param mixed $value 原始值
 * @param bool $treatDashAsNull 是否将"-"视为NULL (默认true)
 * @return bool|null
 */
function normalizeBoolean($value, $treatDashAsNull = true) {
    if ($value === null || $value === '') {
        return null;
    }

    $value = trim((string)$value);

    // 明确的"是"值
    if (in_array($value, ['1', '是', 'yes', 'true', 'Y', 'y', '有'], true)) {
        return true;
    }

    // 明确的"否"值
    if (in_array($value, ['0', '否', 'no', 'false', 'N', 'n', '无'], true)) {
        return false;
    }

    // "-" 占位符处理
    if ($value === '-') {
        return $treatDashAsNull ? null : false;
    }

    // 其他情况返回NULL
    return null;
}

/**
 * 规范化数值
 * Excel中的"-"/空白 → NULL
 *
 * @param mixed $value 原始值
 * @return float|int|null
 */
function normalizeNumeric($value) {
    if ($value === null || $value === '' || $value === '-') {
        return null;
    }

    $value = trim((string)$value);

    // 移除单位和空格
    $value = preg_replace('/[^\d.-]/', '', $value);

    if ($value === '' || $value === '-') {
        return null;
    }

    // 判断是整数还是浮点数
    if (strpos($value, '.') !== false) {
        return floatval($value);
    }
    return intval($value);
}

/**
 * 规范化字符串
 * Excel中的"-"/空白 → NULL
 * 其他值保留原样
 *
 * @param mixed $value 原始值
 * @param bool $treatDashAsNull 是否将"-"视为NULL
 * @return string|null
 */
function normalizeString($value, $treatDashAsNull = true) {
    if ($value === null || $value === '') {
        return null;
    }

    $value = trim((string)$value);

    if ($value === '' || ($treatDashAsNull && $value === '-')) {
        return null;
    }

    return $value;
}

/**
 * 规范化勾选状态
 * 用于 "报价资料 Order Entry" 和 "建筑结构" 等区域
 * Excel中勾选用"-"表示未选，其他值表示已选
 *
 * @param mixed $value 原始值
 * @return bool|null
 */
function normalizeCheckbox($value) {
    if ($value === null || $value === '') {
        return null;
    }

    $value = trim((string)$value);

    // "-" 表示未勾选
    if ($value === '-') {
        return false;
    }

    // "0" 表示未勾选
    if ($value === '0') {
        return false;
    }

    // 任何其他非空值表示已勾选
    return true;
}

/**
 * 规范化N/A状态
 * 用于 "安装 Erection" 和 "防火涂料" 等可能标记N/A的字段
 *
 * @param mixed $value 原始值
 * @param string $naPattern N/A的匹配模式
 * @return array ['is_na' => bool, 'value' => mixed]
 */
function normalizeNaValue($value, $naPattern = '/^(n\/a|na|不适用|无)$/i') {
    if ($value === null || $value === '') {
        return ['is_na' => null, 'value' => null];
    }

    $value = trim((string)$value);

    if ($value === '-') {
        return ['is_na' => null, 'value' => null];
    }

    if (preg_match($naPattern, $value)) {
        return ['is_na' => true, 'value' => null];
    }

    return ['is_na' => false, 'value' => $value];
}

/**
 * 规范化改造项目字段
 * Excel中用"0"表示否
 *
 * @param mixed $value
 * @return bool|null
 */
function normalizeRenovation($value) {
    if ($value === null || $value === '') {
        return null;
    }

    $value = trim((string)$value);

    if ($value === '0') {
        return false;
    }

    if ($value === '1' || $value === '-') {
        // "-" 在改造项目中可能表示需要勾选
        return $value === '1';
    }

    return null;
}

/**
 * 批量规范化主表布尔字段
 *
 * @param array $row Excel行数据
 * @param array $fieldMap 字段映射 ['db_field' => 'excel_col_index']
 * @return array 规范化后的字段值
 */
function normalizeMainBooleanFields($row, $fieldMap) {
    $result = [];
    foreach ($fieldMap as $dbField => $colIndex) {
        $value = isset($row[$colIndex]) ? $row[$colIndex] : null;
        $result[$dbField] = normalizeCheckbox($value);
    }
    return $result;
}

/**
 * 解析Excel颜色值
 * "0" → 基色
 * "标准色" → 标准色
 * 其他 → 自定义颜色
 *
 * @param mixed $value
 * @return string|null
 */
function normalizeColor($value) {
    if ($value === null || $value === '') {
        return null;
    }

    $value = trim((string)$value);

    if ($value === '0') {
        return '基色';  // 或返回 'BASE' 代码
    }

    if ($value === '-') {
        return null;
    }

    return $value;
}

/**
 * 示例: 导入一行RFQ数据
 */
function importRfqRow($row) {
    // 基本信息
    $main = [
        'rfq_no' => normalizeString($row['rfq_no']),
        'job_number' => normalizeString($row['job_number']),
        'project_name' => normalizeString($row['project_name']),

        // 联系人信息
        'contact_to' => normalizeString($row['contact_to']),
        'contact_email' => normalizeString($row['contact_email']),
        'attn' => normalizeString($row['attn']),
        'account_manager' => normalizeString($row['account_manager']),

        // 建筑结构特征 - 使用 normalizeCheckbox
        'pre_eng_building' => normalizeCheckbox($row['pre_eng_building']),
        'bridge_crane' => normalizeCheckbox($row['bridge_crane']),
        'mezzanine_steels' => normalizeCheckbox($row['mezzanine_steels']),
        'factory_mutual' => normalizeCheckbox($row['factory_mutual']),
        'loading_canopy' => normalizeCheckbox($row['loading_canopy']),
        'future_expansion' => normalizeCheckbox($row['future_expansion']),
        'parapet' => normalizeCheckbox($row['parapet']),
        'concrete_wall_curb' => normalizeCheckbox($row['concrete_wall_curb']),
        'leed' => normalizeCheckbox($row['leed']),

        // 安装
        'erection' => normalizeNaValue($row['erection'])['is_na'] === true ? false : normalizeCheckbox($row['erection']),
    ];

    // 钢结构
    $steel = [
        'length' => normalizeNumeric($row['length']),
        'length_source' => normalizeString($row['length_source']),
        'width' => normalizeNumeric($row['width']),
        'width_source' => normalizeString($row['width_source']),
        'eave_height' => normalizeNumeric($row['eave_height']),
        'eave_height_source' => normalizeString($row['eave_height_source']),

        // 主结构
        'steel_grade' => normalizeString($row['steel_grade']),
        'steel_manufacturer' => normalizeString($row['steel_manufacturer']),
        'primer_type' => normalizeString($row['primer_type']),
        'primer_thickness' => normalizeNumeric($row['primer_thickness']),

        // 次结构
        'secondary_manufacturer' => normalizeString($row['secondary_manufacturer']),
        'roof_purlin_galvanized' => normalizeBoolean($row['roof_purlin_galvanized']),
        'wall_purlin_galvanized' => normalizeBoolean($row['wall_purlin_galvanized']),
    ];

    return [
        'main' => $main,
        'steel' => $steel,
    ];
}

/**
 * 验证导入数据
 */
function validateImportData($data) {
    $errors = [];

    // 必填字段检查
    if (empty($data['main']['rfq_no'])) {
        $errors[] = 'RFQ编号不能为空';
    }

    if (empty($data['main']['project_name'])) {
        $errors[] = '项目名称不能为空';
    }

    return $errors;
}
