<?php
/**
 * RFQ PDF 打印页面
 * LSB RFQ System V3.2
 */
require_once dirname(__DIR__) . '/includes/functions.php';

$rfqId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$size = isset($_GET['size']) ? $_GET['size'] : 'letter';

// 语言设置
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['rfq_lang']) ? $_COOKIE['rfq_lang'] : 'both');
if (!in_array($lang, ['en', 'cn', 'both'])) {
    $lang = 'both';
}

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
// V3.2 新增
$claddingSpecs = $rfqData['cladding_specs'] ?? [];
$supplements = $rfqData['supplements'] ?? [];

// 设置页面大小CSS类
$pageClass = $size === 'a4' ? 'page-a4' : 'page-letter';

/**
 * 打印标签辅助函数
 */
function printLabel($cn, $en) {
    global $lang;
    if ($lang === 'en') {
        return $en;
    } elseif ($lang === 'cn') {
        return $cn;
    }
    return $cn . ' ' . $en;
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
        <?php if (!empty($main['contact_to']) || !empty($main['attn']) || !empty($main['account_manager'])): ?>
        <div class="print-section">
            <div class="print-section-header"><?php echo printLabel('联系人信息', 'Contact Information'); ?></div>
            <div class="print-section-body">
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('发给', 'To'); ?></div>
                        <div class="info-value"><?php echo h($main['contact_to']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('电子邮箱', 'Email'); ?></div>
                        <div class="info-value"><?php echo h($main['contact_email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('收件人', 'Attn'); ?></div>
                        <div class="info-value"><?php echo h($main['attn']); ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('客户经理', 'Account Manager'); ?></div>
                        <div class="info-value"><?php echo h($main['account_manager']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('职称', 'Title'); ?></div>
                        <div class="info-value"><?php echo h($main['account_manager_title']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><?php echo printLabel('项目类型', 'Project Type'); ?></div>
                        <div class="info-value"><?php echo h($main['project_type']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 基本信息 -->
        <div class="print-section">
            <div class="print-section-header"><?php echo printLabel('基本信息', 'Basic Information'); ?></div>
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
        <?php if (!empty($orderEntry)): ?>
        <div class="print-section">
            <div class="print-section-header"><?php echo printLabel('报价资料', 'Order Entry'); ?></div>
            <div class="print-section-body">
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
                <?php if (!empty($orderEntry['other_docs_desc'])): ?>
                <div class="info-row">
                    <div class="info-item full">
                        <div class="info-label"><?php echo printLabel('其他文件描述', 'Other Documents Description'); ?></div>
                        <div class="info-value"><?php echo h($orderEntry['other_docs_desc']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 结构概述 -->
        <div class="print-section">
            <div class="print-section-header"><?php echo printLabel('结构概述', 'Building Structure'); ?></div>
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
            <div class="print-section-header"><?php echo printLabel('报价范围', 'Scope of Work'); ?></div>
            <div class="print-section-body">
                <div class="info-row">
                    <div class="info-item half">
                        <div class="info-label"><?php echo printLabel('主次围材料', 'Scope Type'); ?></div>
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
            <div class="print-section-header"><?php echo printLabel('建筑描述 & 钢结构材料', 'Building Description & Steel Materials'); ?></div>
            <div class="print-section-body">
                <!-- 建筑尺寸 -->
                <div class="subsection-title"><?php echo printLabel('建筑尺寸', 'Building Dimensions'); ?></div>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('长度', 'Length'); ?></th>
                        <td><?php echo h($steel['length']); ?> m <?php echo !empty($steel['length_source']) ? '(' . h($steel['length_source']) . ')' : ''; ?></td>
                        <th><?php echo printLabel('宽度', 'Width'); ?></th>
                        <td><?php echo h($steel['width']); ?> m <?php echo !empty($steel['width_source']) ? '(' . h($steel['width_source']) . ')' : ''; ?></td>
                        <th><?php echo printLabel('檐口高度', 'Eave Height'); ?></th>
                        <td><?php echo h($steel['eave_height']); ?> m</td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('女儿墙顶标高', 'Parapet Top Elevation'); ?></th>
                        <td><?php echo h($steel['parapet_top_elevation']); ?> m</td>
                        <th><?php echo printLabel('女儿墙内衬板', 'Parapet Wall Liner'); ?></th>
                        <td><?php echo h($steel['parapet_wall_liner']); ?></td>
                        <th><?php echo printLabel('夹层范围', 'Mezzanine Area'); ?></th>
                        <td><?php echo h($steel['mezzanine_floor_area']); ?></td>
                    </tr>
                </table>

                <!-- 主结构材料 -->
                <div class="subsection-title"><?php echo printLabel('主结构材料', 'Primary Steel'); ?></div>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('材质', 'Steel Grade'); ?></th>
                        <td><?php echo h($steel['steel_grade']); ?></td>
                        <th><?php echo printLabel('原材料厂家', 'Steel Manufacturer'); ?></th>
                        <td><?php echo h(getRefValue('steel_manufacturer', $steel['steel_manufacturer'])); ?></td>
                        <th><?php echo printLabel('加工厂', 'Plant'); ?></th>
                        <td><?php echo h(getRefValue('processing_plant', $steel['processing_plant'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('底漆', 'Primer'); ?></th>
                        <td><?php echo h(getRefValue('primer_type', $steel['primer_type'])); ?></td>
                        <th><?php echo printLabel('底漆厚度', 'Primer Thickness'); ?></th>
                        <td><?php echo h($steel['primer_thickness']); ?> μm</td>
                        <th colspan="2"></th>
                    </tr>
                </table>

                <!-- 中间漆+面漆 -->
                <?php if (!empty($steel['intermediate_coat']) || !empty($steel['top_coat_paint'])): ?>
                <div class="subsection-title"><?php echo printLabel('中间漆+面漆', 'Intermediate & Top Coat'); ?></div>
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
                <div class="subsection-title"><?php echo printLabel('外露构件油漆', 'Exposed Paint'); ?></div>
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
                <div class="subsection-title"><?php echo printLabel('普通钢结构防火涂料', 'Fire Coating'); ?></div>
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
                <div class="subsection-title"><?php echo printLabel('次结构材料', 'Secondary Steel'); ?></div>
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
            </div>
        </div>

        <!-- 围护系统 -->
        <div class="print-section">
            <div class="print-section-header"><?php echo printLabel('围护系统', 'Envelope System'); ?></div>
            <div class="print-section-body">
                <!-- 材料配置 -->
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('屋墙面材料', 'Wall Material'); ?></th>
                        <td><?php echo h(getRefValue('wall_material', $envelope['wall_material'])); ?></td>
                        <th><?php echo printLabel('内衬板布置', 'Liner Layout'); ?></th>
                        <td><?php echo h(getRefValue('liner_layout', $envelope['roof_liner_layout'])); ?></td>
                    </tr>
                </table>

                <!-- 屋面配置 -->
                <div class="subsection-title"><?php echo printLabel('屋面配置', 'Roof Configuration'); ?></div>
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

                <!-- 墙面配置 -->
                <div class="subsection-title"><?php echo printLabel('墙面配置', 'Wall Configuration'); ?></div>
                <table class="print-table">
                    <tr>
                        <th><?php echo printLabel('墙面外板铺设', 'Outer Wall Layout'); ?></th>
                        <td><?php echo h(getRefValue('wall_panel_layout', $envelope['wall_outer_layout'])); ?></td>
                        <th><?php echo printLabel('外板墙裙高度', 'Outer Curb Height'); ?></th>
                        <td><?php echo h($envelope['wall_outer_curb_height']); ?> m</td>
                    </tr>
                    <tr>
                        <th><?php echo printLabel('墙面内板铺设', 'Liner Wall Layout'); ?></th>
                        <td><?php echo h(getRefValue('wall_panel_layout', $envelope['wall_liner_layout'])); ?></td>
                        <th><?php echo printLabel('内板墙裙高度', 'Liner Curb Height'); ?></th>
                        <td><?php echo h($envelope['wall_liner_curb_height']); ?> m</td>
                    </tr>
                </table>

                <!-- 小雨蓬配置 -->
                <div class="subsection-title"><?php echo printLabel('小雨蓬', 'Small Canopy'); ?></div>
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
                        <th colspan="2"></th>
                    </tr>
                </table>

                <!-- 采光板配置 -->
                <div class="subsection-title"><?php echo printLabel('采光板', 'Skylight Panel'); ?></div>
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
                </table>
            </div>
        </div>

        <!-- V3.2: 板材规格明细 -->
        <?php if (!empty($claddingSpecs)): ?>
        <div class="print-section">
            <div class="print-section-header"><?php echo printLabel('板材规格明细', 'Cladding Specification'); ?></div>
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
                    $systemLabels = ['roof' => '屋面', 'wall' => '墙面', 'canopy' => '雨篷', 'parapet' => '女儿墙'];
                    $layerLabels = ['outer' => '外板', 'liner' => '内衬', 'core' => '芯材'];
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
                        <td><?php echo h(getRefValue('panel_color_ral', $spec['color_code']) ?: $spec['color_code']); ?></td>
                        <td><?php echo h(getRefValue('panel_brand', $spec['brand']) ?: $spec['brand']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- V3.2: 补充说明 -->
        <?php if (!empty($supplements)): ?>
        <div class="print-section">
            <div class="print-section-header"><?php echo printLabel('补充说明', 'Supplements'); ?></div>
            <div class="print-section-body">
                <?php
                $categoryLabels = [
                    'general' => '通用', 'steel' => '钢结构', 'envelope' => '围护',
                    'load' => '荷载', 'site' => '现场', 'schedule' => '进度',
                    'commercial' => '商务', 'other' => '其他'
                ];
                $importanceLabels = ['normal' => '', 'important' => '⚠ ', 'critical' => '‼ '];
                foreach ($supplements as $supp):
                ?>
                <div class="info-row" style="border-bottom: 1px dotted #ccc; padding: 2px 0;">
                    <div class="info-item" style="width: 15%;">
                        <span class="info-label">[<?php echo h($categoryLabels[$supp['category']] ?? $supp['category']); ?>]</span>
                    </div>
                    <div class="info-item" style="width: 85%;">
                        <span class="info-value">
                            <?php echo h($importanceLabels[$supp['importance']] ?? ''); ?>
                            <?php if ($supp['title']): ?><strong><?php echo h($supp['title']); ?>:</strong> <?php endif; ?>
                            <?php echo h($supp['content']); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

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
