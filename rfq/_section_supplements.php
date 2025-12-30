<?php
/**
 * RFQ Form Section: Supplements
 * 补充说明 (V3.2)
 */

// 补充类别选项
$supplementCategories = [
    'general' => ['cn' => '通用', 'en' => 'General'],
    'steel' => ['cn' => '钢结构', 'en' => 'Steel'],
    'envelope' => ['cn' => '围护', 'en' => 'Envelope'],
    'load' => ['cn' => '荷载', 'en' => 'Load'],
    'site' => ['cn' => '现场', 'en' => 'Site'],
    'schedule' => ['cn' => '进度', 'en' => 'Schedule'],
    'commercial' => ['cn' => '商务', 'en' => 'Commercial'],
    'other' => ['cn' => '其他', 'en' => 'Other']
];

// 重要程度选项
$importanceLevels = [
    'normal' => ['cn' => '普通', 'en' => 'Normal', 'class' => 'secondary'],
    'important' => ['cn' => '重要', 'en' => 'Important', 'class' => 'warning'],
    'critical' => ['cn' => '关键', 'en' => 'Critical', 'class' => 'danger']
];
?>
<!-- ========== 补充说明 (V3.2) ========== -->
<div class="form-section">
    <div class="form-section-header">
        <i class="bi bi-chat-left-text"></i> <?php echo sectionTitle('补充说明', 'Supplements'); ?>
        <button type="button" class="btn btn-sm btn-outline-light float-end" onclick="Supplements.addRow()">
            <i class="bi bi-plus"></i> <?php echo $lang === 'en' ? 'Add Note' : '添加说明'; ?>
        </button>
    </div>
    <div class="form-section-body">
        <!-- 补充说明列表 -->
        <div id="supplements-container">
            <?php if (empty($supplements)): ?>
            <div class="text-muted text-center py-3" id="supplements-empty">
                <?php echo $lang === 'en' ? 'No supplements added yet. Click "Add Note" to add.' : '暂无补充说明，点击"添加说明"添加'; ?>
            </div>
            <?php else: ?>
                <?php foreach ($supplements as $index => $supp): ?>
                <?php echo renderSupplementRow($supp, $index, $supplementCategories, $importanceLevels, $lang); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 模板 -->
        <template id="supplement-template">
            <?php echo renderSupplementRow([], '__INDEX__', $supplementCategories, $importanceLevels, $lang); ?>
        </template>
    </div>
</div>

<?php
/**
 * 渲染单行补充说明
 */
function renderSupplementRow($supp, $index, $categories, $levels, $lang) {
    $prefix = "supplements[{$index}]";
    ob_start();
?>
<div class="supplement-row card mb-2" data-index="<?php echo $index; ?>">
    <div class="card-body py-2">
        <div class="row g-2 align-items-start">
            <!-- 类别 -->
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[category]">
                    <?php foreach ($categories as $val => $labels): ?>
                    <option value="<?php echo $val; ?>" <?php echo ($supp['category'] ?? 'general') == $val ? 'selected' : ''; ?>>
                        <?php echo $lang === 'en' ? $labels['en'] : ($lang === 'cn' ? $labels['cn'] : $labels['cn'] . ' ' . $labels['en']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- 重要程度 -->
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[importance]">
                    <?php foreach ($levels as $val => $labels): ?>
                    <option value="<?php echo $val; ?>" <?php echo ($supp['importance'] ?? 'normal') == $val ? 'selected' : ''; ?>>
                        <?php echo $lang === 'en' ? $labels['en'] : ($lang === 'cn' ? $labels['cn'] : $labels['cn'] . ' ' . $labels['en']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- 标题 -->
            <div class="col-md-3">
                <input type="text" class="form-control form-control-sm" name="<?php echo $prefix; ?>[title]"
                       value="<?php echo h($supp['title'] ?? ''); ?>" placeholder="<?php echo $lang === 'en' ? 'Title (optional)' : '标题（可选）'; ?>">
            </div>
            <!-- 关联区域 -->
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[related_section]">
                    <option value=""><?php echo $lang === 'en' ? '-- Related --' : '-- 关联区域 --'; ?></option>
                    <option value="basic" <?php echo ($supp['related_section'] ?? '') == 'basic' ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'Basic Info' : '基本信息'; ?></option>
                    <option value="steel" <?php echo ($supp['related_section'] ?? '') == 'steel' ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'Steel' : '钢结构'; ?></option>
                    <option value="envelope" <?php echo ($supp['related_section'] ?? '') == 'envelope' ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'Envelope' : '围护系统'; ?></option>
                    <option value="cladding" <?php echo ($supp['related_section'] ?? '') == 'cladding' ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'Cladding' : '板材规格'; ?></option>
                </select>
            </div>
            <!-- 排序 -->
            <div class="col-md-1">
                <input type="number" class="form-control form-control-sm" name="<?php echo $prefix; ?>[sort_order]"
                       value="<?php echo h($supp['sort_order'] ?? '0'); ?>" min="0" placeholder="#">
            </div>
            <!-- 删除按钮 -->
            <div class="col-auto">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="Supplements.removeRow(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <!-- 内容 -->
        <div class="row g-2 mt-1">
            <div class="col-12">
                <textarea class="form-control form-control-sm" name="<?php echo $prefix; ?>[content]" rows="2"
                          placeholder="<?php echo $lang === 'en' ? 'Enter supplement content...' : '输入补充说明内容...'; ?>"><?php echo h($supp['content'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>
</div>
<?php
    return ob_get_clean();
}
?>

<script>
// 补充说明管理
const Supplements = {
    index: <?php echo count($supplements); ?>,

    addRow: function() {
        const container = document.getElementById('supplements-container');
        const template = document.getElementById('supplement-template');
        const emptyMsg = document.getElementById('supplements-empty');

        if (emptyMsg) emptyMsg.remove();

        const html = template.innerHTML.replace(/__INDEX__/g, this.index++);
        container.insertAdjacentHTML('beforeend', html);
    },

    removeRow: function(btn) {
        const row = btn.closest('.supplement-row');
        if (row) {
            row.remove();
            const container = document.getElementById('supplements-container');
            if (container.querySelectorAll('.supplement-row').length === 0) {
                container.innerHTML = '<div class="text-muted text-center py-3" id="supplements-empty">' +
                    '<?php echo $lang === "en" ? "No supplements added yet." : "暂无补充说明"; ?></div>';
            }
        }
    }
};
</script>
