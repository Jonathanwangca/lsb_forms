<?php
/**
 * RFQ 输入表单
 * LSB RFQ System V3.2
 *
 * 此文件为主表单入口，各部分内容拆分到 _section_*.php 文件中便于维护
 */
require_once dirname(__DIR__) . '/includes/rfq_header.php';

// 获取当前语言
$lang = getLang();

// 获取RFQ数据（编辑模式）
$rfqId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$rfqData = null;
$isEdit = false;

if ($rfqId > 0) {
    $rfqData = getRfqFullData($rfqId);
    if ($rfqData) {
        $isEdit = true;
    }
}

// 默认值
$main = $rfqData['main'] ?? ['status' => 'draft'];
$orderEntry = $rfqData['order_entry'] ?? [];
$steel = $rfqData['steel'] ?? [];
$envelope = $rfqData['envelope'] ?? [];
$panels = $rfqData['panels'] ?? [];
$insulations = $rfqData['insulations'] ?? [];
$methods = $rfqData['methods'] ?? [];
$drainages = $rfqData['drainages'] ?? [];
// 文件上传
$files = $rfqData['files'] ?? [];
// V3.2 新增
$claddingSpecs = $rfqData['cladding_specs'] ?? [];
$claddingMethods = $rfqData['cladding_methods'] ?? [];
$supplements = $rfqData['supplements'] ?? [];
$changeLogs = $rfqData['change_logs'] ?? [];

// 语言相关标签
$selectPlaceholder = $lang === 'en' ? '-- Select --' : '-- 请选择 --';
$naOption = 'N/A';
$yesLabel = $lang === 'en' ? 'Yes' : ($lang === 'cn' ? '是' : '是 Yes');
$noLabel = $lang === 'en' ? 'No' : ($lang === 'cn' ? '否' : '否 No');
?>

<div class="row">
    <div class="col-12">
        <!-- 工具栏 -->
        <div class="toolbar">
            <div>
                <h4 class="mb-0">
                    <?php if ($isEdit): ?>
                        <i class="bi bi-pencil-square"></i> <?php echo $lang === 'en' ? 'Edit RFQ' : ($lang === 'cn' ? '编辑 RFQ' : '编辑 Edit RFQ'); ?>: <?php echo h($main['rfq_no']); ?>
                        <span class="badge badge-<?php echo h($main['status'] ?? 'draft'); ?> ms-2" style="font-size: 0.6em; vertical-align: middle;">
                            <?php echo h(getRefValue('rfq_status', $main['status'] ?? 'draft')); ?>
                        </span>
                    <?php else: ?>
                        <i class="bi bi-plus-circle"></i> <?php echo $lang === 'en' ? 'New RFQ' : ($lang === 'cn' ? '新建 RFQ' : '新建 New RFQ'); ?>
                    <?php endif; ?>
                </h4>
            </div>
            <div class="btn-group-actions">
                <div class="btn-group me-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="SectionCollapse.expandAll()" title="<?php echo $lang === 'en' ? 'Expand All' : '展开全部'; ?>">
                        <i class="bi bi-arrows-expand"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="SectionCollapse.collapseAll()" title="<?php echo $lang === 'en' ? 'Collapse All' : '折叠全部'; ?>">
                        <i class="bi bi-arrows-collapse"></i>
                    </button>
                </div>
                <button type="button" class="btn btn-outline-secondary" onclick="if(typeof RFQ!=='undefined')RFQ.saveToLocal()">
                    <i class="bi bi-hdd"></i> <?php echo $lang === 'en' ? 'Save Local' : '本地保存'; ?>
                </button>
                <button type="button" class="btn btn-outline-primary" onclick="if(typeof RFQ!=='undefined')RFQ.saveDraft()">
                    <i class="bi bi-save"></i> <?php echo BL('save_draft'); ?>
                </button>
                <?php if ($isEdit): ?>
                <button type="button" class="btn btn-outline-info" onclick="if(typeof RFQ!=='undefined')RFQ.saveJsonFile(<?php echo $rfqId; ?>)">
                    <i class="bi bi-download"></i> <?php echo BL('export_json'); ?>
                </button>
                <a href="/aiforms/rfq/print.php?id=<?php echo $rfqId; ?>&size=letter" target="_blank" class="btn btn-outline-success">
                    <i class="bi bi-printer"></i> <?php echo $lang === 'en' ? 'Print PDF' : '打印 PDF'; ?>
                </a>
                <?php endif; ?>
                <button type="submit" form="rfq-form" class="btn btn-primary" onclick="if(typeof RFQ!=='undefined')RFQ.setStatusSubmitted()">
                    <i class="bi bi-check-lg"></i> <?php echo BL('submit'); ?>
                </button>
            </div>
        </div>

        <form id="rfq-form" class="rfq-form" method="post" action="/aiforms/api/rfq.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?php echo $rfqId; ?>">

            <?php
            // A. 联系人信息
            include __DIR__ . '/_section_A_contact.php';

            // B. 基本信息
            include __DIR__ . '/_section_B_basic.php';

            // C. 报价资料
            include __DIR__ . '/_section_C_order_entry.php';

            // D. 结构概述
            include __DIR__ . '/_section_D_structure.php';

            // E. 报价范围
            include __DIR__ . '/_section_E_scope.php';

            // F. 建筑尺寸 & 钢结构
            include __DIR__ . '/_section_F_steel.php';

            // G. 屋墙面做法说明
            include __DIR__ . '/_section_G_method.php';

            // H. 围护系统配置（特殊配置、改造项目等）
            include __DIR__ . '/_section_H_envelope.php';

            // I. 屋面系统材质要求
            include __DIR__ . '/_section_I_roof_material.php';

            // J. 墙面系统材质要求
            include __DIR__ . '/_section_J_wall_material.php';

            // K. 补充说明
            include __DIR__ . '/_section_K_supplements.php';

            // L. 状态
            include __DIR__ . '/_section_L_status.php';
            ?>

        </form>
    </div>
</div>

<!-- 浮动保存按钮 -->
<button type="button" class="floating-save-btn" id="floatingSaveBtn" onclick="if(typeof RFQ!=='undefined')RFQ.floatingSave()" title="<?php echo $lang === 'en' ? 'Save Draft' : '保存草稿'; ?>">
    <i class="bi bi-save"></i>
</button>

<div class="autosave-indicator">
    <i class="bi bi-check-circle"></i> Saved
</div>

<?php require_once dirname(__DIR__) . '/includes/rfq_footer.php'; ?>
