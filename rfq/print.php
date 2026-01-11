<?php
/**
 * RFQ PDF 打印页面
 * LSB RFQ System V3.2
 *
 * Note: This file uses the unified schema (rfq_schema.php) for consistency
 * with the form UI. Any label or field changes should be made in the schema.
 */
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/rfq_render.php';

$rfqId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$size = isset($_GET['size']) ? $_GET['size'] : 'letter';

// 语言设置
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['rfq_lang']) ? $_COOKIE['rfq_lang'] : 'both');
if (!in_array($lang, ['en', 'cn', 'both'])) {
    $lang = 'both';
}
// 设置全局语言变量，供 getRefValue() 等函数使用
$GLOBALS['current_lang'] = $lang;

if (!$rfqId) {
    die('RFQ ID is required');
}

$rfqData = getRfqFullData($rfqId);
if (!$rfqData) {
    die('RFQ not found');
}

$main = $rfqData['main'];
$orderEntry = $rfqData['order_entry'] ?? [];
$steel = $rfqData['steel'] ?? [];
$envelope = $rfqData['envelope'] ?? [];
$panels = $rfqData['panels'] ?? [];
$insulations = $rfqData['insulations'] ?? [];
$methods = $rfqData['methods'] ?? [];
$drainages = $rfqData['drainages'] ?? [];
$remarks = $rfqData['remarks'] ?? [];
// V3.2 新增
$claddingSpecs = $rfqData['cladding_specs'] ?? [];
$claddingMethods = $rfqData['cladding_methods'] ?? [];
$supplements = $rfqData['supplements'] ?? [];

// 设置页面大小CSS类
$pageClass = $size === 'a4' ? 'page-a4' : 'page-letter';

/**
 * 打印标签辅助函数 - 使用统一的 rfqLabel()
 * 优先从 rfq_schema.php 查找，然后 labels.json，最后使用传入的默认值
 */
function printLabel($cn, $en, $key = null) {
    return rfqLabel($cn, $en, $key);
}

/**
 * 打印是/否
 */
function printYesNo($value) {
    global $lang;
    if ($value === null || $value === '') return 'N/A';
    if ($value) {
        return $lang === 'en' ? 'Yes' : ($lang === 'cn' ? '是' : '是 Yes');
    }
    return $lang === 'en' ? 'No' : ($lang === 'cn' ? '否' : '否 No');
}

/**
 * 打印值（空值返回N/A）
 */
function printValue($value, $suffix = '') {
    if ($value === null || $value === '') return 'N/A';
    return h($value) . ($suffix ? ' ' . $suffix : '');
}

/**
 * 打印引用值（空值返回N/A）
 */
function printRefValue($category, $value) {
    if ($value === null || $value === '') return 'N/A';
    $result = getRefValue($category, $value);
    return $result ?: h($value);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'en' ? 'en' : 'zh-CN'; ?>">
<head>
    <meta charset="UTF-8">
    <title>RFQ <?php echo h($main['rfq_no']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'SimSun', 'Microsoft YaHei', Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.25;
            color: #000;
        }

        @page {
            size: <?php echo $size === 'a4' ? 'A4' : 'letter'; ?>;
            margin: 8mm;
        }

        .print-container {
            max-width: <?php echo $size === 'a4' ? '210mm' : '8.5in'; ?>;
            margin: 0 auto;
            padding: 3mm;
        }

        /* 标题 */
        .print-header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }

        .print-header h1 {
            font-size: 14pt;
            margin-bottom: 3px;
        }

        .print-header .subtitle {
            font-size: 9pt;
            color: #666;
        }

        /* 区块 */
        .print-section {
            margin-bottom: 6px;
            page-break-inside: avoid;
        }

        .print-section-header {
            background: #f0f0f0;
            padding: 3px 8px;
            font-weight: bold;
            font-size: 10pt;
            border: 1px solid #000;
            border-bottom: none;
        }

        .section-number {
            font-weight: bold;
            margin-right: 4px;
        }

        .subsection-number {
            font-weight: bold;
            margin-right: 4px;
        }

        .print-section-body {
            border: 1px solid #000;
            padding: 6px;
        }

        /* 信息行 */
        .info-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 2px;
        }

        .info-item {
            width: 33.33%;
            padding: 2px 4px;
        }

        .info-item.half {
            width: 50%;
        }

        .info-item.full {
            width: 100%;
        }

        .info-item.quarter {
            width: 25%;
        }

        .info-label {
            font-weight: bold;
            font-size: 8pt;
            color: #333;
        }

        .info-label-en {
            font-size: 7pt;
            color: #666;
        }

        .info-value {
            font-size: 9pt;
        }

        /* 复选框列表 */
        .checkbox-list {
            display: flex;
            flex-wrap: wrap;
        }

        .checkbox-item {
            width: 25%;
            padding: 1px 4px;
            font-size: 8pt;
        }

        .checkbox-item.checked::before {
            content: '☑ ';
        }

        .checkbox-item.unchecked::before {
            content: '☐ ';
            color: #999;
        }

        .checkbox-item.unchecked {
            color: #999;
        }

        /* 表格 */
        .print-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        .print-table th,
        .print-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            text-align: left;
        }

        .print-table th {
            background: #f0f0f0;
            font-weight: bold;
        }

        /* 子标题 */
        .subsection-title {
            font-weight: bold;
            font-size: 8pt;
            margin: 4px 0 3px 0;
            color: #333;
            border-bottom: 1px solid #ccc;
            padding-bottom: 1px;
        }

        /* 页脚 */
        .print-footer {
            margin-top: 10px;
            padding-top: 5px;
            border-top: 1px solid #ccc;
            font-size: 7pt;
            color: #666;
            display: flex;
            justify-content: space-between;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }
        }

        /* 打印按钮 */
        .print-actions {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }

        .print-actions button {
            padding: 8px 16px;
            margin-left: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="print-actions no-print">
        <button onclick="window.print()">Print PDF</button>
        <button onclick="window.close()">Close</button>
    </div>

    <div class="print-container">
        <!-- 标题 -->
        <div class="print-header">
            <h1><?php echo printLabel('INAVA-USAS 报价申请', 'INAVA-USAS RFQ'); ?></h1>
            <div class="subtitle"><?php echo printLabel('报价申请表', 'Request for Quotation'); ?></div>
        </div>

        <!-- 联系人信息 -->
        <div class="print-section">
            <div class="print-section-header"><span class="section-number">A.</span> <?php echo printLabel('联系人信息', 'Contact Information'); ?></div>
            <div class="print-section-body">
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('发给', 'To'); ?></div>
                        <div class="info-value"><?php echo printValue($main['contact_to']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('电子邮箱', 'Email'); ?></div>
                        <div class="info-value"><?php echo printValue($main['contact_email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('收件人', 'Attn'); ?></div>
                        <div class="info-value"><?php echo printValue($main['attn']); ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('客户经理', 'Account Manager'); ?></div>
                        <div class="info-value"><?php echo printValue($main['account_manager']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('职称', 'Title'); ?></div>
                        <div class="info-value"><?php echo printValue($main['account_manager_title']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('项目类型', 'Project Type'); ?></div>
                        <div class="info-value"><?php echo printValue($main['project_type']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 基本信息 -->
        <div class="print-section">
            <div class="print-section-header"><span class="section-number">B.</span> <?php echo printLabel('基本信息', 'Basic Information'); ?></div>
            <div class="print-section-body">
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('报价项目申请号', 'RFQ No.'); ?></div>
                        <div class="info-value"><?php echo h($main['rfq_no']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('项目编号', 'Job Number'); ?></div>
                        <div class="info-value"><?php echo h($main['job_number']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('要求报价提交日期', 'Due by'); ?></div>
                        <div class="info-value"><?php echo $main['due_date'] ? date('Y-m-d', strtotime($main['due_date'])) : '-'; ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item half">
                        <div class="info-label"><?php echo printLabel('项目名称', 'Project Name'); ?></div>
                        <div class="info-value"><?php echo h($main['project_name']); ?></div>
                    </div>
                    <div class="info-item half">
                        <div class="info-label"><?php echo printLabel('项目所在位置', 'Project Location'); ?></div>
                        <div class="info-value"><?php echo h($main['project_location']); ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('单体数量', 'Building Qty'); ?></div>
                        <div class="info-value"><?php echo h($main['building_qty']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('建筑屋面面积', 'Floor Areas'); ?></div>
                        <div class="info-value"><?php echo h($main['floor_area_1']); ?> / <?php echo h($main['floor_area_2']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('联系电话', 'Contact'); ?></div>
                        <div class="info-value"><?php echo h($main['liberty_contact']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 报价资料 Order Entry -->
        <div class="print-section">
            <div class="print-section-header"><span class="section-number">C.</span> <?php echo printLabel('报价资料', 'Order Entry'); ?></div>
            <div class="print-section-body">
                <?php if (!empty($orderEntry)): ?>
                <div class="checkbox-list">
                    <div class="checkbox-item <?php echo ($orderEntry['mbs_drawing'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('MBS结构图纸', 'MBS Drawing'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($orderEntry['architect_drawing'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('建筑蓝图', 'Architect Drawing'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($orderEntry['foundation_design'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('结构蓝图', 'Foundation Design'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($orderEntry['autocad_drawing'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('AutoCAD 建筑图纸', 'AutoCAD Drawing'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($orderEntry['fm_report'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('FM报告', 'FM Report'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($orderEntry['other_docs'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('其他文件', 'Other'); ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item full">
                        <div class="info-label"><?php echo printLabel('其他文件描述', 'Other Documents Description'); ?></div>
                        <div class="info-value"><?php echo printValue($orderEntry['other_docs_desc'] ?? null); ?></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 结构概述 -->
        <div class="print-section">
            <div class="print-section-header"><span class="section-number">D.</span> <?php echo printLabel('结构概述', 'Building Structure'); ?></div>
            <div class="print-section-body">
                <div class="checkbox-list">
                    <div class="checkbox-item <?php echo $main['pre_eng_building'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('预制工程建筑', 'Pre Eng Building'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['bridge_crane'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('天车', 'Bridge Crane'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['mezzanine_steels'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('夹层', 'Mezzanine Steels'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['factory_mutual'] ? 'checked' : 'unchecked'; ?>">
                        Factory Mutual
                    </div>
                    <div class="checkbox-item <?php echo $main['loading_canopy'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('大雨蓬', 'Loading Canopy'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['future_expansion'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('扩建', 'Future Expansion'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['parapet'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('女儿墙', 'Parapet'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['concrete_wall_curb'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('墙裙', 'Concrete Wall Curb'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['leed'] ? 'checked' : 'unchecked'; ?>">
                        LEED
                    </div>
                </div>
            </div>
        </div>

        <!-- 报价范围 -->
        <div class="print-section">
            <div class="print-section-header"><span class="section-number">E.</span> <?php echo printLabel('报价范围', 'Scope of Work'); ?></div>
            <div class="print-section-body">
                <div class="info-row">
                    <div class="info-item half">
                        <div class="info-label"><?php echo printLabel('包含', 'Include'); ?></div>
                        <div class="info-value"><?php echo h(getRefValue('scope_of_work', $main['scope_type'])); ?></div>
                    </div>
                    <div class="info-item half">
                        <div class="info-label"><?php echo printLabel('安装', 'Erection'); ?></div>
                        <div class="info-value"><?php echo printYesNo($main['erection']); ?></div>
                    </div>
                </div>
                <div class="checkbox-list">
                    <div class="checkbox-item <?php echo $main['steel_deck'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('楼面板', 'Steel Deck'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['partition_wall_frame'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('内隔墙', 'Partition Wall'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['door_window'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('门窗', 'Door & Window'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['top_coat'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('面漆', 'Top Coat'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['louver'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('百叶窗', 'Louver'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['cable_tray_support'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('管线吊架', 'Cable Tray'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['railing'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('栏杆扶手', 'Railing'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['glazing_curtain_wall'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('玻璃幕墙', 'Curtain Wall'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['aluminum_cladding'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('铝板', 'Aluminum Cladding'); ?>
                    </div>
                    <div class="checkbox-item <?php echo $main['laboratory_inspect'] ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('试验', 'Laboratory'); ?>
                    </div>
                </div>
                <?php if (!empty($main['erection_remarks']) || !empty($main['laboratory_remarks'])): ?>
                <div class="info-row">
                    <?php if (!empty($main['erection_remarks'])): ?>
                    <div class="info-item half">
                        <div class="info-label"><?php echo printLabel('安装备注', 'Erection Remarks'); ?></div>
                        <div class="info-value"><?php echo h($main['erection_remarks']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($main['laboratory_remarks'])): ?>
                    <div class="info-item half">
                        <div class="info-label"><?php echo printLabel('检测备注', 'Laboratory Remarks'); ?></div>
                        <div class="info-value"><?php echo h($main['laboratory_remarks']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 建筑尺寸 & 钢结构 -->
        <div class="print-section">
            <div class="print-section-header"><span class="section-number">F.</span> <?php echo printLabel('建筑描述 & 钢结构材料', 'Building Description & Steel Materials'); ?></div>
            <div class="print-section-body">
                <!-- 建筑尺寸 -->
                <div class="subsection-title"><span class="subsection-number">F.1</span> <?php echo printLabel('建筑尺寸', 'Building Dimensions'); ?></div>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('长度', 'Length'); ?></th>
                        <td><?php echo printValue($steel['length'] ?? null, 'm'); ?> <?php echo !empty($steel['length_source']) ? '(' . h($steel['length_source']) . ')' : ''; ?></td>
                        <th><?php echo printLabel('宽度', 'Width'); ?></th>
                        <td><?php echo printValue($steel['width'] ?? null, 'm'); ?> <?php echo !empty($steel['width_source']) ? '(' . h($steel['width_source']) . ')' : ''; ?></td>
                        <th><?php echo printLabel('檐口高度', 'Eave Height'); ?></th>
                        <td><?php echo printValue($steel['eave_height'] ?? null, 'm'); ?> <?php echo !empty($steel['eave_height_source']) ? '(' . h($steel['eave_height_source']) . ')' : ''; ?></td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('女儿墙顶标高', 'Parapet Top Elevation'); ?></th>
                        <td><?php echo printValue($steel['parapet_top_elevation'] ?? null, 'm'); ?></td>
                        <th><?php echo printLabel('女儿墙内衬板', 'Parapet Wall Liner'); ?></th>
                        <td><?php echo printValue($steel['parapet_wall_liner'] ?? null); ?></td>
                        <th><?php echo printLabel('夹层范围', 'Mezzanine Area'); ?></th>
                        <td><?php echo printValue($steel['mezzanine_floor_area'] ?? null); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('地面标高', 'Floor Elevation'); ?></th>
                        <td><?php echo printValue($steel['floor_elevation'] ?? null, 'm'); ?></td>
                        <th><?php echo printLabel('地面类型', 'Floor Type'); ?></th>
                        <td colspan="3"><?php echo printValue($steel['floor_type'] ?? null); ?></td>
                    </tr>
                </table>

                <!-- 主结构材料 -->
                <div class="subsection-title"><span class="subsection-number">F.2</span> <?php echo printLabel('主结构材料', 'Primary Steel'); ?></div>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('材质', 'Steel Grade'); ?></th>
                        <td><?php echo printValue($steel['steel_grade'] ?? null); ?></td>
                        <th><?php echo printLabel('原材料厂家', 'Steel Manufacturer'); ?></th>
                        <td><?php echo printRefValue('steel_manufacturer', $steel['steel_manufacturer'] ?? null); ?></td>
                        <th><?php echo printLabel('加工厂', 'Plant'); ?></th>
                        <td><?php echo printRefValue('processing_plant', $steel['processing_plant'] ?? null); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('底漆', 'Primer'); ?></th>
                        <td><?php echo printRefValue('primer_type', $steel['primer_type'] ?? null); ?></td>
                        <th><?php echo printLabel('底漆厚度', 'Primer Thickness'); ?></th>
                        <td><?php echo printValue($steel['primer_thickness'] ?? null, 'μm'); ?></td>
                        <th colspan="2"></th>
                    </tr>
                    <?php if (!empty($steel['primary_steel_note'])): ?>
                    <tr>
                        <th><?php echo printLabel('备注', 'Note'); ?></th>
                        <td colspan="5"><?php echo h($steel['primary_steel_note']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>

                <!-- 中间漆+面漆 -->
                <?php if (!empty($steel['intermediate_coat']) || !empty($steel['top_coat_paint'])): ?>
                <div class="subsection-title"><span class="subsection-number">F.3</span> <?php echo printLabel('中间漆+面漆', 'Intermediate & Top Coat'); ?></div>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('中间漆', 'Intermediate Coat'); ?></th>
                        <td><?php echo h(getRefValue('intermediate_coat', $steel['intermediate_coat'])); ?></td>
                        <th><?php echo printLabel('中间漆厚度', 'Intermediate Thickness'); ?></th>
                        <td><?php echo h($steel['intermediate_thickness']); ?> μm</td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('面漆', 'Top Coat'); ?></th>
                        <td><?php echo h(getRefValue('top_coat_paint', $steel['top_coat_paint'])); ?></td>
                        <th><?php echo printLabel('面漆厚度', 'Top Coat Thickness'); ?></th>
                        <td><?php echo h($steel['top_coat_thickness']); ?> μm</td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('涂刷方式', 'Painting Method'); ?></th>
                        <td><?php echo h(getRefValue('painting_method', $steel['paint_method'])); ?></td>
                        <th><?php echo printLabel('涂刷范围', 'Coating Scope'); ?></th>
                        <td><?php echo h($steel['coating_scope']); ?></td>
                    </tr>
                </table>
                <?php endif; ?>

                <!-- 外露构件油漆 -->
                <?php if (!empty($steel['exposed_paint'])): ?>
                <div class="subsection-title"><span class="subsection-number">F.4</span> <?php echo printLabel('外露构件油漆', 'Exposed Paint'); ?></div>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('外露构件油漆', 'Exposed Paint'); ?></th>
                        <td><?php echo h(getRefValue('exposed_paint', $steel['exposed_paint'])); ?></td>
                        <th><?php echo printLabel('范围', 'Scope'); ?></th>
                        <td><?php echo h($steel['exposed_paint_scope']); ?></td>
                    </tr>
                </table>
                <?php endif; ?>

                <!-- 防火涂料 -->
                <?php if ($steel['fire_coating_na'] !== null || !empty($steel['fire_coating'])): ?>
                <div class="subsection-title"><span class="subsection-number">F.5</span> <?php echo printLabel('普通钢结构防火涂料', 'Fire Coating'); ?></div>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('状态', 'Status'); ?></th>
                        <td><?php echo $steel['fire_coating_na'] ? printLabel('N/A 不适用', 'N/A') : printLabel('需要防火涂料', 'Fire Coating Required'); ?></td>
                        <th><?php echo printLabel('防火涂料类型', 'Fire Coating Type'); ?></th>
                        <td><?php echo h(getRefValue('fire_coating', $steel['fire_coating'])); ?></td>
                    </tr>
                    <?php if (!empty($steel['fire_coating_scope'])): ?>
                    <tr>
                        <th><?php echo printLabel('范围', 'Scope'); ?></th>
                        <td colspan="3"><?php echo h($steel['fire_coating_scope']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php endif; ?>

                <!-- 次结构材料 -->
                <div class="subsection-title"><span class="subsection-number">F.6</span> <?php echo printLabel('次结构材料', 'Secondary Steel'); ?></div>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('原材料厂家', 'Manufacturer'); ?></th>
                        <td><?php echo h(getRefValue('secondary_manufacturer', $steel['secondary_manufacturer'])); ?></td>
                        <th colspan="2"></th>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('屋面檩条镀锌', 'Roof Purlin Galvanized'); ?></th>
                        <td><?php echo printYesNo($steel['roof_purlin_galvanized']); ?></td>
                        <th><?php echo printLabel('屋面檩条油漆', 'Roof Purlin Paint'); ?></th>
                        <td><?php echo h(getRefValue('purlin_paint', $steel['roof_purlin_paint'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('墙面檩条镀锌', 'Wall Purlin Galvanized'); ?></th>
                        <td><?php echo printYesNo($steel['wall_purlin_galvanized']); ?></td>
                        <th><?php echo printLabel('墙面檩条油漆', 'Wall Purlin Paint'); ?></th>
                        <td><?php echo h(getRefValue('purlin_paint', $steel['wall_purlin_paint'])); ?></td>
                    </tr>
                </table>

                <!-- 花纹钢板 -->
                <div class="subsection-title"><span class="subsection-number">F.7</span> <?php echo printLabel('花纹钢板', 'Checkered Plate'); ?></div>
                <?php if (!empty($steel['checkered_plate_paint']) || !empty($steel['checkered_plate_scope'])): ?>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('油漆类型', 'Paint Type'); ?></th>
                        <td><?php echo printRefValue('checkered_plate', $steel['checkered_plate_paint'] ?? null); ?></td>
                        <th><?php echo printLabel('范围', 'Scope'); ?></th>
                        <td><?php echo printValue($steel['checkered_plate_scope'] ?? null); ?></td>
                    </tr>
                    <?php if (!empty($steel['checkered_plate_remarks'])): ?>
                    <tr>
                        <th><?php echo printLabel('备注', 'Remarks'); ?></th>
                        <td colspan="3"><?php echo h($steel['checkered_plate_remarks']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>

                <!-- 其他要求 -->
                <?php if (!empty($steel['other_requirements'])): ?>
                <div class="subsection-title"><?php echo printLabel('其他要求', 'Other Requirements'); ?></div>
                <div class="info-value" style="padding: 5px 10px; background: #f9f9f9; border-radius: 4px;">
                    <?php echo nl2br(h($steel['other_requirements'])); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 做法说明 G -->
        <?php
        $roofMethods = array_values(array_filter($methods, fn($m) => ($m['method_category'] ?? '') === 'roof'));
        $wallMethods = array_values(array_filter($methods, fn($m) => ($m['method_category'] ?? '') === 'wall'));
        ?>
        <div class="print-section">
            <div class="print-section-header"><span class="section-number">G.</span> <?php echo printLabel('屋墙面做法说明', 'Construction Method'); ?></div>
            <div class="print-section-body">
                <!-- G.1 屋面做法 -->
                <div class="subsection-title"><span class="subsection-number">G.1</span> <?php echo printLabel('屋面做法', 'Roof Method'); ?></div>
                <?php if (!empty($roofMethods)): ?>
                <table class="print-table">
                    <tr>
                        <th style="width:15%;"><?php echo printLabel('项目', 'Item'); ?></th>
                        <th style="width:50%;"><?php echo printLabel('做法描述', 'Method Description'); ?></th>
                        <th style="width:35%;"><?php echo printLabel('范围', 'Scope'); ?></th>
                    </tr>
                    <?php foreach ($roofMethods as $idx => $m): ?>
                    <tr>
                        <td><?php echo printLabel('屋面做法', 'Roof Method'); ?> <?php echo ($idx + 1); ?></td>
                        <td><?php echo h($m['method_desc'] ?: 'N/A'); ?></td>
                        <td><?php echo printValue($m['scope'] ?? null); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>

                <!-- G.2 墙面做法 -->
                <div class="subsection-title"><span class="subsection-number">G.2</span> <?php echo printLabel('墙面做法', 'Wall Method'); ?></div>
                <?php if (!empty($wallMethods)): ?>
                <table class="print-table">
                    <tr>
                        <th style="width:15%;"><?php echo printLabel('项目', 'Item'); ?></th>
                        <th style="width:50%;"><?php echo printLabel('做法描述', 'Method Description'); ?></th>
                        <th style="width:35%;"><?php echo printLabel('范围', 'Scope'); ?></th>
                    </tr>
                    <?php foreach ($wallMethods as $idx => $m): ?>
                    <tr>
                        <td><?php echo printLabel('墙面做法', 'Wall Method'); ?> <?php echo ($idx + 1); ?></td>
                        <td><?php echo h($m['method_desc'] ?: 'N/A'); ?></td>
                        <td><?php echo printValue($m['scope'] ?? null); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 围护系统配置 H -->
        <div class="print-section">
            <div class="print-section-header"><span class="section-number">H.</span> <?php echo printLabel('围护系统配置', 'Envelope Configuration'); ?></div>
            <div class="print-section-body">
                <!-- H.1 屋墙面材料 -->
                <div class="subsection-title"><span class="subsection-number">H.1</span> <?php echo printLabel('屋墙面材料', 'Material Configuration'); ?></div>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('材料类型', 'Material Type'); ?></th>
                        <td><?php echo printRefValue('wall_material', $envelope['wall_material'] ?? null); ?></td>
                        <th><?php echo printLabel('备注', 'Remarks'); ?></th>
                        <td><?php echo printValue($envelope['material_remarks'] ?? null); ?></td>
                    </tr>
                </table>

                <!-- H.2 改造项目 -->
                <div class="subsection-title"><span class="subsection-number">H.2</span> <?php echo printLabel('改造项目', 'Renovation'); ?></div>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('改造项目', 'Is Renovation'); ?></th>
                        <td><?php echo printYesNo($envelope['is_renovation'] ?? null); ?></td>
                        <th colspan="2"></th>
                    </tr>
                </table>
                <?php if (!empty($envelope['is_renovation'])): ?>
                <div class="checkbox-list">
                    <div class="checkbox-item <?php echo ($envelope['structural_reinforcement'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('结构加固', 'Structural Reinforcement'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($envelope['cladding_addition'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('围护板加建', 'Cladding Addition'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($envelope['reuse'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('利旧', 'Reuse'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($envelope['mep_installation'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('机电安装', 'MEP Installation'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($envelope['renovation_other'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('其他', 'Other'); ?>
                    </div>
                </div>
                <?php if (!empty($envelope['renovation_remarks'])): ?>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('备注', 'Remarks'); ?></th>
                        <td colspan="3"><?php echo h($envelope['renovation_remarks']); ?></td>
                    </tr>
                </table>
                <?php endif; ?>
                <?php endif; ?>

                <!-- H.3 防水规范 (隐藏GB字段，只显示备注) -->
                <div class="subsection-title"><span class="subsection-number">H.3</span> <?php echo printLabel('防水规范', 'Waterproof Standard'); ?></div>
                <?php if (!empty($envelope['waterproof_remarks'])): ?>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('备注', 'Remarks'); ?></th>
                        <td colspan="3"><?php echo h($envelope['waterproof_remarks']); ?></td>
                    </tr>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>

                <!-- H.4 屋面特殊配置 -->
                <div class="subsection-title"><span class="subsection-number">H.4</span> <?php echo printLabel('屋面特殊配置', 'Roof Special Configuration'); ?></div>
                <div class="checkbox-list">
                    <div class="checkbox-item <?php echo ($envelope['aclok_roof'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('Aclok铝镁锰屋面板', 'Aclok Roof Panel'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($envelope['sandwich_panel'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('夹芯板', 'Sandwich Panel'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($envelope['roof_ventilator'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('屋面通风器', 'Roof Ventilator'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($envelope['roof_opening'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('屋面开口', 'Roof Opening'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($envelope['roof_skylight'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('屋面气楼/天窗(条形)', 'Roof Skylight (Strip)'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($envelope['roof_ridge_lantern'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('屋脊气楼/天窗', 'Ridge Skylight'); ?>
                    </div>
                    <div class="checkbox-item <?php echo ($envelope['pv_system'] ?? 0) ? 'checked' : 'unchecked'; ?>">
                        <?php echo printLabel('LS585光伏系统', 'LS585 PV System'); ?>
                    </div>
                </div>
                <?php
                // H.4 备注字段
                $h4HasRemarks = !empty($envelope['sandwich_remarks']) || !empty($envelope['ventilator_requirements'])
                    || !empty($envelope['skylight_requirements']) || !empty($envelope['roof_ridge_lantern_remarks'])
                    || !empty($envelope['pv_requirements']);
                if ($h4HasRemarks):
                ?>
                <table class="print-table" style="margin-top: 8px;">
                    <?php if (!empty($envelope['sandwich_remarks'])): ?>
                    <tr>
                        <th style="width:25%;"><?php echo printLabel('夹芯板备注', 'Sandwich Panel Remarks'); ?></th>
                        <td colspan="3"><?php echo h($envelope['sandwich_remarks']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($envelope['ventilator_requirements'])): ?>
                    <tr>
                        <th><?php echo printLabel('通风器/开口要求', 'Ventilator/Opening Requirements'); ?></th>
                        <td colspan="3"><?php echo h($envelope['ventilator_requirements']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($envelope['skylight_requirements'])): ?>
                    <tr>
                        <th><?php echo printLabel('气楼/天窗要求', 'Skylight/Monitor Requirements'); ?></th>
                        <td colspan="3"><?php echo h($envelope['skylight_requirements']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($envelope['roof_ridge_lantern_remarks'])): ?>
                    <tr>
                        <th><?php echo printLabel('屋脊气楼备注', 'Ridge Lantern Remarks'); ?></th>
                        <td colspan="3"><?php echo h($envelope['roof_ridge_lantern_remarks']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($envelope['pv_requirements'])): ?>
                    <tr>
                        <th><?php echo printLabel('其他要求', 'Other Requirements'); ?></th>
                        <td colspan="3"><?php echo h($envelope['pv_requirements']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- 屋面系统材质要求 -->
        <?php
        $roofPanels = array_values(array_filter($panels, fn($p) => ($p['panel_category'] ?? '') === 'roof'));
        $roofOuterPanels = array_values(array_filter($roofPanels, fn($p) => ($p['panel_type'] ?? '') === 'outer'));
        $roofLinerPanels = array_values(array_filter($roofPanels, fn($p) => ($p['panel_type'] ?? '') === 'liner'));
        $canopyPanels = array_values(array_filter($roofPanels, fn($p) => in_array($p['panel_type'] ?? '', ['canopy_upper', 'canopy_lower'])));
        $roofInsulations = array_values(array_filter($insulations, fn($i) => ($i['insulation_category'] ?? '') === 'roof'));
        $roofDrainages = array_values(array_filter($drainages, fn($d) => in_array($d['drainage_type'] ?? '', ['roof_1', 'roof_2', 'canopy'])));
        ?>
        <div class="print-section">
            <div class="print-section-header"><span class="section-number">I.</span> <?php echo printLabel('屋面系统材质要求', 'Roof Material Requirements'); ?></div>
            <div class="print-section-body">
                <!-- I.1 屋面排水系统 -->
                <div class="subsection-title"><span class="subsection-number">I.1</span> <?php echo printLabel('屋面排水系统', 'Roof Drainage System'); ?></div>
                <?php if (!empty($roofDrainages)): ?>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('项目', 'Item'); ?></th>
                        <th><?php echo printLabel('类型', 'Type'); ?></th>
                        <th><?php echo printLabel('排水方式', 'Method'); ?></th>
                        <th><?php echo printLabel('范围', 'Scope'); ?></th>
                        <th><?php echo printLabel('天沟规格', 'Gutter Spec'); ?></th>
                        <th><?php echo printLabel('落水管', 'Downspout'); ?></th>
                    </tr>
                    <?php foreach ($roofDrainages as $idx => $d): ?>
                    <tr>
                        <td><?php echo printLabel('排水', 'Drainage'); ?> <?php echo ($idx + 1); ?></td>
                        <td><?php
                            $dtype = $d['drainage_type'] ?? 'roof_1';
                            if ($dtype === 'canopy') {
                                echo printLabel('雨蓬', 'Canopy');
                            } elseif ($dtype === 'roof_1') {
                                echo printLabel('屋面1', 'Roof 1');
                            } elseif ($dtype === 'roof_2') {
                                echo printLabel('屋面2', 'Roof 2');
                            } else {
                                echo printLabel('屋面', 'Roof');
                            }
                        ?></td>
                        <td><?php echo h(getRefValue('drainage_method', $d['method']) ?: $d['method']); ?></td>
                        <td><?php echo h($d['scope']); ?></td>
                        <td><?php echo h($d['gutter_spec']); ?></td>
                        <td><?php echo h(getRefValue('downpipe_type', $d['downpipe_type']) ?: $d['downpipe_type']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>

                <!-- I.2 屋面外板 -->
                <div class="subsection-title"><span class="subsection-number">I.2</span> <?php echo printLabel('屋面外板', 'Roof Outer Panel'); ?></div>
                <?php if (!empty($roofOuterPanels)): ?>
                <table class="print-table" style="font-size: 9px;">
                    <tr>
                        <th><?php echo printLabel('项目', 'Item'); ?></th>
                        <th><?php echo printLabel('厚度', 'Thickness'); ?></th>
                        <th><?php echo printLabel('板型', 'Profile'); ?></th>
                        <th><?php echo printLabel('强度', 'Strength'); ?></th>
                        <th><?php echo printLabel('涂层', 'Coating'); ?></th>
                        <th><?php echo printLabel('镀层', 'Galvanizing'); ?></th>
                        <th><?php echo printLabel('颜色', 'Color'); ?></th>
                        <th><?php echo printLabel('产地', 'Origin'); ?></th>
                    </tr>
                    <?php foreach ($roofOuterPanels as $idx => $p): ?>
                    <tr>
                        <td><?php echo printLabel('外板', 'Outer'); ?> <?php echo ($idx + 1); ?></td>
                        <td><?php echo $p['thickness'] ? h($p['thickness']) . 'mm' : ''; ?></td>
                        <td><?php echo h(getRefValue('panel_profile_roof', $p['profile']) ?: $p['profile']); ?></td>
                        <td><?php echo h(getRefValue('panel_strength', $p['strength']) ?: $p['strength']); ?></td>
                        <td><?php echo h(getRefValue('panel_coating', $p['coating']) ?: $p['coating']); ?></td>
                        <td><?php echo h(getRefValue('panel_galvanizing', $p['galvanizing']) ?: $p['galvanizing']); ?></td>
                        <td><?php echo h(getRefValue('panel_color', $p['color']) ?: $p['color']); ?></td>
                        <td><?php echo h(getRefValue('panel_origin', $p['origin']) ?: $p['origin']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>

                <!-- I.3 屋面保温棉 -->
                <div class="subsection-title"><span class="subsection-number">I.3</span> <?php echo printLabel('屋面保温棉', 'Roof Insulation'); ?></div>
                <?php if (!empty($roofInsulations)): ?>
                <table class="print-table" style="font-size: 9px;">
                    <tr>
                        <th><?php echo printLabel('项目', 'Item'); ?></th>
                        <th><?php echo printLabel('厚度', 'Thickness'); ?></th>
                        <th><?php echo printLabel('容重', 'Density'); ?></th>
                        <th><?php echo printLabel('贴面', 'Facing'); ?></th>
                        <th><?php echo printLabel('阻燃', 'Flame'); ?></th>
                        <th><?php echo printLabel('颜色', 'Color'); ?></th>
                        <th><?php echo printLabel('品牌', 'Brand'); ?></th>
                        <th><?php echo printLabel('其他', 'Other'); ?></th>
                    </tr>
                    <?php foreach ($roofInsulations as $idx => $i): ?>
                    <tr>
                        <td><?php echo printLabel('屋面保温', 'Roof Insul.'); ?> <?php echo ($idx + 1); ?></td>
                        <td><?php echo $i['thickness'] ? h($i['thickness']) . 'mm' : ''; ?></td>
                        <td><?php echo $i['density'] ? h($i['density']) . 'kg/m³' : ''; ?></td>
                        <td><?php echo h(getRefValue('insulation_facing', $i['facing']) ?: $i['facing']); ?></td>
                        <td><?php echo h(getRefValue('flame_retardant', $i['flame_retardant']) ?: $i['flame_retardant']); ?></td>
                        <td><?php echo h(getRefValue('insulation_color', $i['color']) ?: $i['color']); ?></td>
                        <td><?php echo h(getRefValue('insulation_brand', $i['brand']) ?: $i['brand']); ?></td>
                        <td><?php echo h($i['other_requirements']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>

                <!-- I.4 防水透气膜/隔汽膜/钢丝网 -->
                <div class="subsection-title"><span class="subsection-number">I.4</span> <?php echo printLabel('防水透气膜/隔汽膜/钢丝网', 'Membrane & Wire Mesh'); ?></div>
                <?php if (!empty($envelope['roof_waterproof_membrane']) || !empty($envelope['roof_vapor_barrier']) || !empty($envelope['roof_wire_mesh'])): ?>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('防水透气膜', 'Waterproof Membrane'); ?></th>
                        <td><?php echo printYesNo($envelope['roof_waterproof_membrane'] ?? null); ?></td>
                        <th><?php echo printLabel('材料要求', 'Material Req.'); ?></th>
                        <td><?php echo h($envelope['roof_waterproof_material'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('隔汽膜', 'Vapor Barrier'); ?></th>
                        <td><?php echo printYesNo($envelope['roof_vapor_barrier'] ?? null); ?></td>
                        <th><?php echo printLabel('隔汽膜材料', 'Vapor Material'); ?></th>
                        <td><?php echo h($envelope['roof_vapor_material'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('钢丝网', 'Wire Mesh'); ?></th>
                        <td><?php echo h(getRefValue('wire_mesh', $envelope['roof_wire_mesh']) ?: $envelope['roof_wire_mesh']); ?></td>
                        <th><?php echo printLabel('钢丝网材料', 'Wire Mesh Material'); ?></th>
                        <td><?php echo h($envelope['roof_wire_mesh_material'] ?? ''); ?></td>
                    </tr>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>

                <!-- I.5 屋面内衬板 -->
                <div class="subsection-title"><span class="subsection-number">I.5</span> <?php echo printLabel('屋面内衬板', 'Roof Liner Panel'); ?></div>
                <?php if (!empty($roofLinerPanels)): ?>
                <table class="print-table" style="font-size: 9px;">
                    <tr>
                        <th><?php echo printLabel('项目', 'Item'); ?></th>
                        <th><?php echo printLabel('厚度', 'Thickness'); ?></th>
                        <th><?php echo printLabel('板型', 'Profile'); ?></th>
                        <th><?php echo printLabel('强度', 'Strength'); ?></th>
                        <th><?php echo printLabel('涂层', 'Coating'); ?></th>
                        <th><?php echo printLabel('镀层', 'Galvanizing'); ?></th>
                        <th><?php echo printLabel('颜色', 'Color'); ?></th>
                        <th><?php echo printLabel('产地', 'Origin'); ?></th>
                    </tr>
                    <?php foreach ($roofLinerPanels as $idx => $p): ?>
                    <tr>
                        <td><?php echo printLabel('内衬板', 'Liner'); ?> <?php echo ($idx + 1); ?></td>
                        <td><?php echo $p['thickness'] ? h($p['thickness']) . 'mm' : ''; ?></td>
                        <td><?php echo h(getRefValue('panel_profile_liner', $p['profile']) ?: $p['profile']); ?></td>
                        <td><?php echo h(getRefValue('panel_strength', $p['strength']) ?: $p['strength']); ?></td>
                        <td><?php echo h(getRefValue('panel_coating', $p['coating']) ?: $p['coating']); ?></td>
                        <td><?php echo h(getRefValue('panel_galvanizing', $p['galvanizing']) ?: $p['galvanizing']); ?></td>
                        <td><?php echo h(getRefValue('panel_color', $p['color']) ?: $p['color']); ?></td>
                        <td><?php echo h(getRefValue('panel_origin', $p['origin']) ?: $p['origin']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
                <?php if (!empty($envelope['roof_liner_layout']) || !empty($envelope['roof_liner_remarks'])): ?>
                <table class="print-table" style="margin-top: 4px;">
                    <tr>
                        <th style="width:25%;"><?php echo printLabel('内衬板铺设', 'Liner Layout'); ?></th>
                        <td><?php echo h(getRefValue('liner_layout', $envelope['roof_liner_layout']) ?: $envelope['roof_liner_layout']); ?></td>
                        <th style="width:15%;"><?php echo printLabel('备注', 'Remarks'); ?></th>
                        <td><?php echo h($envelope['roof_liner_remarks'] ?? ''); ?></td>
                    </tr>
                </table>
                <?php endif; ?>
                <?php if (empty($roofLinerPanels) && empty($envelope['roof_liner_layout']) && empty($envelope['roof_liner_remarks'])): ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>

                <!-- I.6 大雨蓬板 -->
                <div class="subsection-title"><span class="subsection-number">I.6</span> <?php echo printLabel('大雨蓬板', 'Loading Canopy Panel'); ?></div>
                <?php if (!empty($canopyPanels)): ?>
                <table class="print-table" style="font-size: 9px;">
                    <tr>
                        <th><?php echo printLabel('项目', 'Item'); ?></th>
                        <th><?php echo printLabel('厚度', 'Thickness'); ?></th>
                        <th><?php echo printLabel('板型', 'Profile'); ?></th>
                        <th><?php echo printLabel('强度', 'Strength'); ?></th>
                        <th><?php echo printLabel('涂层', 'Coating'); ?></th>
                        <th><?php echo printLabel('镀层', 'Galvanizing'); ?></th>
                        <th><?php echo printLabel('颜色', 'Color'); ?></th>
                        <th><?php echo printLabel('产地', 'Origin'); ?></th>
                    </tr>
                    <?php foreach ($canopyPanels as $p): ?>
                    <tr>
                        <td><?php echo $p['panel_type'] === 'canopy_upper' ? printLabel('雨蓬上板', 'Canopy Upper') : printLabel('雨蓬下板', 'Canopy Lower'); ?></td>
                        <td><?php echo $p['thickness'] ? h($p['thickness']) . 'mm' : ''; ?></td>
                        <td><?php echo h(getRefValue('panel_profile_roof', $p['profile']) ?: $p['profile']); ?></td>
                        <td><?php echo h(getRefValue('panel_strength', $p['strength']) ?: $p['strength']); ?></td>
                        <td><?php echo h(getRefValue('panel_coating', $p['coating']) ?: $p['coating']); ?></td>
                        <td><?php echo h(getRefValue('panel_galvanizing', $p['galvanizing']) ?: $p['galvanizing']); ?></td>
                        <td><?php echo h(getRefValue('panel_color', $p['color']) ?: $p['color']); ?></td>
                        <td><?php echo h(getRefValue('panel_origin', $p['origin']) ?: $p['origin']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
                <?php if (isset($envelope['canopy_has_insulation']) || !empty($envelope['canopy_insulation_remarks'])): ?>
                <table class="print-table" style="margin-top: 4px;">
                    <tr>
                        <th style="width:25%;"><?php echo printLabel('雨蓬保温', 'Canopy Insulation'); ?></th>
                        <td><?php echo printYesNo($envelope['canopy_has_insulation'] ?? null); ?></td>
                        <th style="width:15%;"><?php echo printLabel('备注', 'Remarks'); ?></th>
                        <td><?php echo h($envelope['canopy_insulation_remarks'] ?? ''); ?></td>
                    </tr>
                </table>
                <?php endif; ?>
                <?php if (empty($canopyPanels) && !isset($envelope['canopy_has_insulation']) && empty($envelope['canopy_insulation_remarks'])): ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>

                <!-- I.7 标准小雨蓬 -->
                <div class="subsection-title"><span class="subsection-number">I.7</span> <?php echo printLabel('标准小雨蓬', 'Small Canopy'); ?></div>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('悬挑宽度', 'Canopy Width'); ?></th>
                        <td><?php echo h($envelope['small_canopy_width']); ?> mm</td>
                        <th><?php echo printLabel('做法', 'Method'); ?></th>
                        <td><?php echo h(getRefValue('canopy_method', $envelope['small_canopy_method'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('排水做法', 'Drainage Method'); ?></th>
                        <td><?php echo h(getRefValue('canopy_drainage', $envelope['small_canopy_drainage'])); ?></td>
                        <th><?php echo printLabel('备注', 'Remarks'); ?></th>
                        <td><?php echo h($envelope['small_canopy_remarks'] ?? ''); ?></td>
                    </tr>
                </table>

                <!-- I.8 屋面采光 -->
                <div class="subsection-title"><span class="subsection-number">I.8</span> <?php echo printLabel('屋面采光', 'Roof Skylight'); ?></div>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('铺设方式', 'Layout'); ?></th>
                        <td><?php echo h(getRefValue('skylight_layout', $envelope['skylight_layout'])); ?></td>
                        <th><?php echo printLabel('材料', 'Material'); ?></th>
                        <td><?php echo h(getRefValue('skylight_material', $envelope['skylight_material'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('品牌', 'Brand'); ?></th>
                        <td><?php echo h(getRefValue('skylight_brand', $envelope['skylight_brand'])); ?></td>
                        <th><?php echo printLabel('长度', 'Length'); ?></th>
                        <td><?php echo h($envelope['skylight_length']); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('FM认证', 'FM Certified'); ?></th>
                        <td><?php echo printYesNo($envelope['skylight_fm_certified'] ?? null); ?></td>
                        <th colspan="2"></th>
                    </tr>
                    <?php if (!empty($envelope['skylight_other_requirements'])): ?>
                    <tr>
                        <th><?php echo printLabel('其他要求', 'Other Requirements'); ?></th>
                        <td colspan="3"><?php echo h($envelope['skylight_other_requirements']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>

                <!-- I.9 其他屋面材料 -->
                <div class="subsection-title"><span class="subsection-number">I.9</span> <?php echo printLabel('其他屋面材料', 'Other Roof Materials'); ?></div>
                <?php if (!empty($envelope['rock_wool_panel']) || !empty($envelope['flexible_roof']) || !empty($envelope['envelope_other'])): ?>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('岩棉板', 'Rockwool Panel'); ?></th>
                        <td><?php echo printValue($envelope['rock_wool_panel'] ?? null); ?></td>
                        <th><?php echo printLabel('柔性屋面', 'Flexible Roof'); ?></th>
                        <td><?php echo printValue($envelope['flexible_roof'] ?? null); ?></td>
                    </tr>
                    <?php if (!empty($envelope['envelope_other'])): ?>
                    <tr>
                        <th><?php echo printLabel('其他', 'Other'); ?></th>
                        <td colspan="3"><?php echo h($envelope['envelope_other']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 墙面系统材质要求 -->
        <?php
        $wallPanels = array_values(array_filter($panels, fn($p) => ($p['panel_category'] ?? '') === 'wall'));
        $wallOuterPanels = array_values(array_filter($wallPanels, fn($p) => ($p['panel_type'] ?? '') === 'outer'));
        $wallLinerPanels = array_values(array_filter($wallPanels, fn($p) => ($p['panel_type'] ?? '') === 'liner'));
        $parapetLinerPanels = array_values(array_filter($wallPanels, fn($p) => ($p['panel_type'] ?? '') === 'parapet_liner'));
        $partitionPanels = array_values(array_filter($wallPanels, fn($p) => ($p['panel_type'] ?? '') === 'partition'));
        $wallInsulations = array_values(array_filter($insulations, fn($i) => ($i['insulation_category'] ?? '') === 'wall'));
        ?>
        <div class="print-section">
            <div class="print-section-header"><span class="section-number">J.</span> <?php echo printLabel('墙面系统材质要求', 'Wall Material Requirements'); ?></div>
            <div class="print-section-body">
                <!-- J.1 墙面配置 -->
                <div class="subsection-title"><span class="subsection-number">J.1</span> <?php echo printLabel('墙面配置', 'Wall Configuration'); ?></div>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('墙面外板铺设', 'Outer Wall Layout'); ?></th>
                        <td><?php echo printRefValue('wall_panel_layout', $envelope['wall_outer_layout'] ?? null); ?></td>
                        <th><?php echo printLabel('外板墙裙高度', 'Outer Curb Height'); ?></th>
                        <td><?php echo printValue($envelope['wall_outer_curb_height'] ?? null, 'm'); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('墙面内板铺设', 'Liner Wall Layout'); ?></th>
                        <td><?php echo printRefValue('wall_panel_layout', $envelope['wall_liner_layout'] ?? null); ?></td>
                        <th><?php echo printLabel('内板墙裙高度', 'Liner Curb Height'); ?></th>
                        <td><?php echo printValue($envelope['wall_liner_curb_height'] ?? null, 'm'); ?></td>
                    </tr>
                </table>

                <!-- J.2 墙面外板 -->
                <div class="subsection-title"><span class="subsection-number">J.2</span> <?php echo printLabel('墙面外板', 'Wall Outer Panel'); ?></div>
                <?php if (!empty($wallOuterPanels)): ?>
                <table class="print-table" style="font-size: 9px;">
                    <tr>
                        <th><?php echo printLabel('项目', 'Item'); ?></th>
                        <th><?php echo printLabel('厚度', 'Thickness'); ?></th>
                        <th><?php echo printLabel('板型', 'Profile'); ?></th>
                        <th><?php echo printLabel('强度', 'Strength'); ?></th>
                        <th><?php echo printLabel('涂层', 'Coating'); ?></th>
                        <th><?php echo printLabel('镀层', 'Galvanizing'); ?></th>
                        <th><?php echo printLabel('颜色', 'Color'); ?></th>
                        <th><?php echo printLabel('产地', 'Origin'); ?></th>
                    </tr>
                    <?php foreach ($wallOuterPanels as $idx => $p): ?>
                    <tr>
                        <td><?php echo printLabel('外板', 'Outer'); ?> <?php echo ($idx + 1); ?></td>
                        <td><?php echo $p['thickness'] ? h($p['thickness']) . 'mm' : ''; ?></td>
                        <td><?php echo h(getRefValue('panel_profile_wall', $p['profile']) ?: $p['profile']); ?></td>
                        <td><?php echo h(getRefValue('panel_strength', $p['strength']) ?: $p['strength']); ?></td>
                        <td><?php echo h(getRefValue('panel_coating', $p['coating']) ?: $p['coating']); ?></td>
                        <td><?php echo h(getRefValue('panel_galvanizing', $p['galvanizing']) ?: $p['galvanizing']); ?></td>
                        <td><?php echo h(getRefValue('panel_color', $p['color']) ?: $p['color']); ?></td>
                        <td><?php echo h(getRefValue('panel_origin', $p['origin']) ?: $p['origin']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>

                <!-- J.3 墙面保温棉 -->
                <div class="subsection-title"><span class="subsection-number">J.3</span> <?php echo printLabel('墙面保温棉', 'Wall Insulation'); ?></div>
                <?php if (!empty($wallInsulations)): ?>
                <table class="print-table" style="font-size: 9px;">
                    <tr>
                        <th><?php echo printLabel('项目', 'Item'); ?></th>
                        <th><?php echo printLabel('厚度', 'Thickness'); ?></th>
                        <th><?php echo printLabel('容重', 'Density'); ?></th>
                        <th><?php echo printLabel('贴面', 'Facing'); ?></th>
                        <th><?php echo printLabel('阻燃', 'Flame'); ?></th>
                        <th><?php echo printLabel('颜色', 'Color'); ?></th>
                        <th><?php echo printLabel('品牌', 'Brand'); ?></th>
                        <th><?php echo printLabel('其他', 'Other'); ?></th>
                    </tr>
                    <?php foreach ($wallInsulations as $idx => $i): ?>
                    <tr>
                        <td><?php echo printLabel('墙面保温', 'Wall Insul.'); ?> <?php echo ($idx + 1); ?></td>
                        <td><?php echo $i['thickness'] ? h($i['thickness']) . 'mm' : ''; ?></td>
                        <td><?php echo $i['density'] ? h($i['density']) . 'kg/m³' : ''; ?></td>
                        <td><?php echo h(getRefValue('insulation_facing', $i['facing']) ?: $i['facing']); ?></td>
                        <td><?php echo h(getRefValue('flame_retardant', $i['flame_retardant']) ?: $i['flame_retardant']); ?></td>
                        <td><?php echo h(getRefValue('insulation_color', $i['color']) ?: $i['color']); ?></td>
                        <td><?php echo h(getRefValue('insulation_brand', $i['brand']) ?: $i['brand']); ?></td>
                        <td><?php echo h($i['other_requirements']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>

                <!-- J.4 墙面防水透气膜/隔汽膜/钢丝网 -->
                <div class="subsection-title"><span class="subsection-number">J.4</span> <?php echo printLabel('防水透气膜/隔汽膜/钢丝网', 'Wall Membrane & Wire Mesh'); ?></div>
                <?php if (!empty($envelope['wall_waterproof_membrane']) || !empty($envelope['wall_vapor_barrier']) || !empty($envelope['wall_wire_mesh'])): ?>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('防水透气膜', 'Waterproof Membrane'); ?></th>
                        <td><?php echo printYesNo($envelope['wall_waterproof_membrane'] ?? null); ?></td>
                        <th><?php echo printLabel('隔汽膜', 'Vapor Barrier'); ?></th>
                        <td><?php echo printYesNo($envelope['wall_vapor_barrier'] ?? null); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('钢丝网', 'Wire Mesh'); ?></th>
                        <td colspan="3"><?php echo h(getRefValue('wire_mesh', $envelope['wall_wire_mesh']) ?: $envelope['wall_wire_mesh']); ?></td>
                    </tr>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>

                <!-- J.5 墙面内衬板 -->
                <div class="subsection-title"><span class="subsection-number">J.5</span> <?php echo printLabel('墙面内衬板', 'Wall Liner Panel'); ?></div>
                <?php if (!empty($wallLinerPanels)): ?>
                <table class="print-table" style="font-size: 9px;">
                    <tr>
                        <th><?php echo printLabel('项目', 'Item'); ?></th>
                        <th><?php echo printLabel('厚度', 'Thickness'); ?></th>
                        <th><?php echo printLabel('板型', 'Profile'); ?></th>
                        <th><?php echo printLabel('强度', 'Strength'); ?></th>
                        <th><?php echo printLabel('涂层', 'Coating'); ?></th>
                        <th><?php echo printLabel('镀层', 'Galvanizing'); ?></th>
                        <th><?php echo printLabel('颜色', 'Color'); ?></th>
                        <th><?php echo printLabel('产地', 'Origin'); ?></th>
                    </tr>
                    <?php foreach ($wallLinerPanels as $idx => $p): ?>
                    <tr>
                        <td><?php echo printLabel('内衬板', 'Liner'); ?> <?php echo ($idx + 1); ?></td>
                        <td><?php echo $p['thickness'] ? h($p['thickness']) . 'mm' : ''; ?></td>
                        <td><?php echo h(getRefValue('panel_profile_liner', $p['profile']) ?: $p['profile']); ?></td>
                        <td><?php echo h(getRefValue('panel_strength', $p['strength']) ?: $p['strength']); ?></td>
                        <td><?php echo h(getRefValue('panel_coating', $p['coating']) ?: $p['coating']); ?></td>
                        <td><?php echo h(getRefValue('panel_galvanizing', $p['galvanizing']) ?: $p['galvanizing']); ?></td>
                        <td><?php echo h(getRefValue('panel_color', $p['color']) ?: $p['color']); ?></td>
                        <td><?php echo h(getRefValue('panel_origin', $p['origin']) ?: $p['origin']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>

                <!-- J.6 女儿墙内衬板 -->
                <div class="subsection-title"><span class="subsection-number">J.6</span> <?php echo printLabel('女儿墙内衬板', 'Parapet Liner Panel'); ?></div>
                <?php if (!empty($parapetLinerPanels)): ?>
                <table class="print-table" style="font-size: 9px;">
                    <tr>
                        <th><?php echo printLabel('项目', 'Item'); ?></th>
                        <th><?php echo printLabel('厚度', 'Thickness'); ?></th>
                        <th><?php echo printLabel('板型', 'Profile'); ?></th>
                        <th><?php echo printLabel('强度', 'Strength'); ?></th>
                        <th><?php echo printLabel('涂层', 'Coating'); ?></th>
                        <th><?php echo printLabel('镀层', 'Galvanizing'); ?></th>
                        <th><?php echo printLabel('颜色', 'Color'); ?></th>
                        <th><?php echo printLabel('产地', 'Origin'); ?></th>
                    </tr>
                    <?php foreach ($parapetLinerPanels as $idx => $p): ?>
                    <tr>
                        <td><?php echo printLabel('女儿墙内衬', 'Parapet'); ?> <?php echo ($idx + 1); ?></td>
                        <td><?php echo $p['thickness'] ? h($p['thickness']) . 'mm' : ''; ?></td>
                        <td><?php echo h(getRefValue('panel_profile_wall', $p['profile']) ?: $p['profile']); ?></td>
                        <td><?php echo h(getRefValue('panel_strength', $p['strength']) ?: $p['strength']); ?></td>
                        <td><?php echo h(getRefValue('panel_coating', $p['coating']) ?: $p['coating']); ?></td>
                        <td><?php echo h(getRefValue('panel_galvanizing', $p['galvanizing']) ?: $p['galvanizing']); ?></td>
                        <td><?php echo h(getRefValue('panel_color', $p['color']) ?: $p['color']); ?></td>
                        <td><?php echo h(getRefValue('panel_origin', $p['origin']) ?: $p['origin']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>

                <!-- J.7 内隔墙墙面板 -->
                <div class="subsection-title"><span class="subsection-number">J.7</span> <?php echo printLabel('内隔墙墙面板', 'Partition Wall Panel'); ?></div>
                <?php if (!empty($partitionPanels)): ?>
                <table class="print-table" style="font-size: 9px;">
                    <tr>
                        <th><?php echo printLabel('项目', 'Item'); ?></th>
                        <th><?php echo printLabel('厚度', 'Thickness'); ?></th>
                        <th><?php echo printLabel('板型', 'Profile'); ?></th>
                        <th><?php echo printLabel('强度', 'Strength'); ?></th>
                        <th><?php echo printLabel('涂层', 'Coating'); ?></th>
                        <th><?php echo printLabel('镀层', 'Galvanizing'); ?></th>
                        <th><?php echo printLabel('颜色', 'Color'); ?></th>
                        <th><?php echo printLabel('产地', 'Origin'); ?></th>
                    </tr>
                    <?php foreach ($partitionPanels as $idx => $p): ?>
                    <tr>
                        <td><?php echo printLabel('内隔墙', 'Partition'); ?> <?php echo ($idx + 1); ?></td>
                        <td><?php echo $p['thickness'] ? h($p['thickness']) . 'mm' : ''; ?></td>
                        <td><?php echo h(getRefValue('panel_profile_liner', $p['profile']) ?: $p['profile']); ?></td>
                        <td><?php echo h(getRefValue('panel_strength', $p['strength']) ?: $p['strength']); ?></td>
                        <td><?php echo h(getRefValue('panel_coating', $p['coating']) ?: $p['coating']); ?></td>
                        <td><?php echo h(getRefValue('panel_galvanizing', $p['galvanizing']) ?: $p['galvanizing']); ?></td>
                        <td><?php echo h(getRefValue('panel_color', $p['color']) ?: $p['color']); ?></td>
                        <td><?php echo h(getRefValue('panel_origin', $p['origin']) ?: $p['origin']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- V3.2: 板材规格明细 -->
        <?php if (!empty($claddingSpecs)): ?>
        <div class="print-section">
            <div class="print-section-header"><span class="section-number">*</span> <?php echo printLabel('板材规格明细', 'Cladding Specification'); ?></div>
            <div class="print-section-body">
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('系统', 'System'); ?></th>
                        <th><?php echo printLabel('层位', 'Layer'); ?></th>
                        <th><?php echo printLabel('区域', 'Zone'); ?></th>
                        <th><?php echo printLabel('板型', 'Profile'); ?></th>
                        <th><?php echo printLabel('基材', 'Material'); ?></th>
                        <th><?php echo printLabel('厚度', 'Thick'); ?></th>
                        <th><?php echo printLabel('涂层', 'Coating'); ?></th>
                        <th><?php echo printLabel('颜色', 'Color'); ?></th>
                        <th><?php echo printLabel('品牌', 'Brand'); ?></th>
                    </tr>
                    <?php
                    $systemLabels = $lang === 'en' ? [
                        'roof' => 'Roof', 'wall' => 'Wall', 'canopy' => 'Canopy', 'parapet' => 'Parapet'
                    ] : [
                        'roof' => '屋面', 'wall' => '墙面', 'canopy' => '雨篷', 'parapet' => '女儿墙'
                    ];
                    $layerLabels = $lang === 'en' ? [
                        'outer' => 'Outer', 'liner' => 'Liner', 'core' => 'Core'
                    ] : [
                        'outer' => '外板', 'liner' => '内衬', 'core' => '芯材'
                    ];
                    foreach ($claddingSpecs as $spec):
                    ?>
                    <tr>
                        <td><?php echo h($systemLabels[$spec['system_type']] ?? $spec['system_type']); ?></td>
                        <td><?php echo h($layerLabels[$spec['layer_position']] ?? $spec['layer_position']); ?></td>
                        <td><?php echo h($spec['zone_code']); ?></td>
                        <td><?php echo h(getRefValue('panel_profile_roof', $spec['panel_profile']) ?: $spec['panel_profile']); ?></td>
                        <td><?php echo h(getRefValue('base_material', $spec['base_material']) ?: $spec['base_material']); ?></td>
                        <td><?php echo $spec['thickness'] ? h($spec['thickness']) . 'mm' : ''; ?></td>
                        <td><?php echo h(getRefValue('coating_type', $spec['coating_type']) ?: $spec['coating_type']); ?></td>
                        <td><?php echo h(getRefValue('panel_color', $spec['color_code']) ?: $spec['color_code']); ?></td>
                        <td><?php echo h(getRefValue('panel_origin', $spec['brand']) ?: $spec['brand']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- V3.2: 备注 Notes -->
        <div class="print-section">
            <div class="print-section-header"><span class="section-number">K.</span> <?php echo printLabel('备注', 'Notes'); ?></div>
            <div class="print-section-body">
                <?php if (!empty($supplements)): ?>
                <?php
                $importanceLabels = [
                    'normal' => ['cn' => '普通', 'en' => 'Normal', 'icon' => ''],
                    'important' => ['cn' => '重要', 'en' => 'Important', 'icon' => '⚠'],
                    'critical' => ['cn' => '关键', 'en' => 'Critical', 'icon' => '‼']
                ];
                $categoryLabels = [
                    'general' => ['cn' => '通用', 'en' => 'General'],
                    'steel' => ['cn' => '钢结构', 'en' => 'Steel'],
                    'envelope' => ['cn' => '围护', 'en' => 'Envelope'],
                    'load' => ['cn' => '荷载', 'en' => 'Load'],
                    'site' => ['cn' => '现场', 'en' => 'Site'],
                    'schedule' => ['cn' => '进度', 'en' => 'Schedule'],
                    'commercial' => ['cn' => '商务', 'en' => 'Commercial'],
                    'other' => ['cn' => '其他', 'en' => 'Other']
                ];
                $relatedLabels = [
                    'basic' => ['cn' => '基本信息', 'en' => 'Basic Info'],
                    'steel' => ['cn' => '钢结构', 'en' => 'Steel'],
                    'envelope' => ['cn' => '围护系统', 'en' => 'Envelope'],
                    'cladding' => ['cn' => '板材规格', 'en' => 'Cladding']
                ];
                $noteIndex = 0;
                foreach ($supplements as $supp):
                    $noteIndex++;
                    $impKey = $supp['importance'] ?? 'normal';
                    $catKey = $supp['category'] ?? 'general';
                    $relKey = $supp['related_section'] ?? '';
                ?>
                <div style="border: 1px solid #ddd; margin-bottom: 6px; padding: 6px; background: #fafafa;">
                    <div style="display: flex; gap: 10px; font-size: 9px; margin-bottom: 4px;">
                        <span><strong>#<?php echo $noteIndex; ?></strong></span>
                        <span><?php echo printLabel('类别', 'Category'); ?>: <?php echo $lang === 'en' ? ($categoryLabels[$catKey]['en'] ?? $catKey) : ($categoryLabels[$catKey]['cn'] ?? $catKey); ?></span>
                        <span><?php echo printLabel('重要程度', 'Importance'); ?>: <?php echo ($importanceLabels[$impKey]['icon'] ?? '') . ' ' . ($lang === 'en' ? ($importanceLabels[$impKey]['en'] ?? $impKey) : ($importanceLabels[$impKey]['cn'] ?? $impKey)); ?></span>
                        <?php if (!empty($relKey)): ?>
                        <span><?php echo printLabel('关联区域', 'Related'); ?>: <?php echo $lang === 'en' ? ($relatedLabels[$relKey]['en'] ?? $relKey) : ($relatedLabels[$relKey]['cn'] ?? $relKey); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($supp['sort_order']) && $supp['sort_order'] != '0'): ?>
                        <span><?php echo printLabel('排序', 'Order'); ?>: <?php echo h($supp['sort_order']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($supp['title'])): ?>
                    <div style="font-weight: bold; margin-bottom: 2px;"><?php echo h($supp['title']); ?></div>
                    <?php endif; ?>
                    <div style="white-space: pre-wrap;"><?php echo h($supp['content']); ?></div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="info-value" style="color: #999;">N/A</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 状态 -->
        <div class="print-section">
            <div class="print-section-header"><span class="section-number">L.</span> <?php echo printLabel('状态', 'Status'); ?></div>
            <div class="print-section-body">
                <div class="info-row">
                    <div class="info-item">
                        <span class="info-label"><?php echo printLabel('报价状态', 'RFQ Status'); ?>:</span>
                        <span class="info-value"><?php echo h(getRefValue('rfq_status', $main['status'])); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 页脚 -->
        <div class="print-footer">
            <div><?php echo printLabel('打印时间', 'Printed'); ?>: <?php echo date('Y-m-d H:i:s'); ?></div>
            <div><?php echo printLabel('状态', 'Status'); ?>: <?php echo h(getRefValue('rfq_status', $main['status'])); ?></div>
            <div>LSB RFQ System V3.2</div>
        </div>
    </div>

    <script>
        // 自动打印
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
