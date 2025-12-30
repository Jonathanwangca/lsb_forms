<?php
/**
 * RFQ Form Section: Cladding Specification
 * 板材规格明细 (V3.2)
 */

// 按系统类型分组
$specsBySystem = [];
foreach ($claddingSpecs as $spec) {
    $type = $spec['system_type'] ?? 'roof';
    if (!isset($specsBySystem[$type])) {
        $specsBySystem[$type] = [];
    }
    $specsBySystem[$type][] = $spec;
}

// 系统类型选项
$systemTypes = [
    'roof' => ['cn' => '屋面', 'en' => 'Roof'],
    'wall' => ['cn' => '墙面', 'en' => 'Wall'],
    'canopy' => ['cn' => '雨篷', 'en' => 'Canopy'],
    'parapet' => ['cn' => '女儿墙', 'en' => 'Parapet']
];

// 层位置选项
$layerPositions = [
    'outer' => ['cn' => '外板', 'en' => 'Outer'],
    'liner' => ['cn' => '内衬板', 'en' => 'Liner'],
    'core' => ['cn' => '芯材', 'en' => 'Core']
];
?>
<!-- ========== 板材规格明细 (V3.2) ========== -->
<div class="form-section">
    <div class="form-section-header">
        <i class="bi bi-layers"></i> <?php echo sectionTitle('板材规格明细', 'Cladding Specification'); ?>
        <button type="button" class="btn btn-sm btn-outline-primary float-end" onclick="CladdingSpec.addRow()">
            <i class="bi bi-plus"></i> <?php echo $lang === 'en' ? 'Add Spec' : '添加规格'; ?>
        </button>
    </div>
    <div class="form-section-body">
        <!-- 规格列表 -->
        <div id="cladding-spec-container">
            <?php if (empty($claddingSpecs)): ?>
            <div class="text-muted text-center py-3" id="cladding-spec-empty">
                <?php echo $lang === 'en' ? 'No cladding specifications added yet. Click "Add Spec" to add.' : '暂无板材规格，点击"添加规格"添加'; ?>
            </div>
            <?php else: ?>
                <?php foreach ($claddingSpecs as $index => $spec): ?>
                <?php echo renderCladdingSpecRow($spec, $index, $systemTypes, $layerPositions, $selectPlaceholder, $lang); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 模板 (用于JS添加新行) -->
        <template id="cladding-spec-template">
            <?php echo renderCladdingSpecRow([], '__INDEX__', $systemTypes, $layerPositions, $selectPlaceholder, $lang); ?>
        </template>
    </div>
</div>

<?php
/**
 * 渲染单行板材规格
 */
function renderCladdingSpecRow($spec, $index, $systemTypes, $layerPositions, $selectPlaceholder, $lang) {
    $prefix = "cladding_specs[{$index}]";
    ob_start();
?>
<div class="cladding-spec-row card mb-2" data-index="<?php echo $index; ?>">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <!-- 系统类型 -->
            <div class="col-md-1">
                <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[system_type]" required>
                    <?php foreach ($systemTypes as $val => $labels): ?>
                    <option value="<?php echo $val; ?>" <?php echo ($spec['system_type'] ?? '') == $val ? 'selected' : ''; ?>>
                        <?php echo $lang === 'en' ? $labels['en'] : ($lang === 'cn' ? $labels['cn'] : $labels['cn'] . ' ' . $labels['en']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- 层位置 -->
            <div class="col-md-1">
                <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[layer_position]">
                    <?php foreach ($layerPositions as $val => $labels): ?>
                    <option value="<?php echo $val; ?>" <?php echo ($spec['layer_position'] ?? 'outer') == $val ? 'selected' : ''; ?>>
                        <?php echo $lang === 'en' ? $labels['en'] : ($lang === 'cn' ? $labels['cn'] : $labels['cn'] . ' ' . $labels['en']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- 区域代码 -->
            <div class="col-md-1">
                <input type="text" class="form-control form-control-sm" name="<?php echo $prefix; ?>[zone_code]"
                       value="<?php echo h($spec['zone_code'] ?? ''); ?>" placeholder="<?php echo $lang === 'en' ? 'Zone' : '区域'; ?>">
            </div>
            <!-- 板型 -->
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[panel_profile]">
                    <option value=""><?php echo $selectPlaceholder; ?></option>
                    <?php foreach (getRefOptions('panel_profile_roof') as $opt): ?>
                    <option value="<?php echo h($opt['value']); ?>" <?php echo ($spec['panel_profile'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                        <?php echo h($opt['label']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- 基材 -->
            <div class="col-md-1">
                <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[base_material]">
                    <option value=""><?php echo $selectPlaceholder; ?></option>
                    <?php foreach (getRefOptions('base_material') as $opt): ?>
                    <option value="<?php echo h($opt['value']); ?>" <?php echo ($spec['base_material'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                        <?php echo h($opt['label']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- 厚度 -->
            <div class="col-md-1">
                <input type="number" class="form-control form-control-sm" name="<?php echo $prefix; ?>[thickness]"
                       value="<?php echo h($spec['thickness'] ?? ''); ?>" step="0.01" placeholder="<?php echo $lang === 'en' ? 'mm' : '厚度mm'; ?>">
            </div>
            <!-- 涂层 -->
            <div class="col-md-1">
                <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[coating_type]">
                    <option value=""><?php echo $selectPlaceholder; ?></option>
                    <?php foreach (getRefOptions('coating_type') as $opt): ?>
                    <option value="<?php echo h($opt['value']); ?>" <?php echo ($spec['coating_type'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                        <?php echo h($opt['label']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- 颜色 -->
            <div class="col-md-1">
                <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[color_code]">
                    <option value=""><?php echo $selectPlaceholder; ?></option>
                    <?php foreach (getRefOptions('panel_color_ral') as $opt): ?>
                    <option value="<?php echo h($opt['value']); ?>" <?php echo ($spec['color_code'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                        <?php echo h($opt['label']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- 品牌 -->
            <div class="col-md-1">
                <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[brand]">
                    <option value=""><?php echo $selectPlaceholder; ?></option>
                    <?php foreach (getRefOptions('panel_brand') as $opt): ?>
                    <option value="<?php echo h($opt['value']); ?>" <?php echo ($spec['brand'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                        <?php echo h($opt['label']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- 删除按钮 -->
            <div class="col-auto">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="CladdingSpec.removeRow(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <!-- 第二行：保温/防火信息 -->
        <div class="row g-2 mt-1">
            <!-- 保温材料 -->
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[insulation_material]">
                    <option value=""><?php echo $lang === 'en' ? '-- Insulation --' : '-- 保温材料 --'; ?></option>
                    <?php foreach (getRefOptions('insulation_material') as $opt): ?>
                    <option value="<?php echo h($opt['value']); ?>" <?php echo ($spec['insulation_material'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                        <?php echo h($opt['label']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- 保温厚度 -->
            <div class="col-md-1">
                <input type="number" class="form-control form-control-sm" name="<?php echo $prefix; ?>[insulation_thickness]"
                       value="<?php echo h($spec['insulation_thickness'] ?? ''); ?>" step="1" placeholder="<?php echo $lang === 'en' ? 'Thick' : '保温厚'; ?>">
            </div>
            <!-- 保温密度 -->
            <div class="col-md-1">
                <input type="number" class="form-control form-control-sm" name="<?php echo $prefix; ?>[insulation_density]"
                       value="<?php echo h($spec['insulation_density'] ?? ''); ?>" step="1" placeholder="<?php echo $lang === 'en' ? 'kg/m³' : '密度'; ?>">
            </div>
            <!-- 防火等级 -->
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[fire_rating]">
                    <option value=""><?php echo $lang === 'en' ? '-- Fire Rating --' : '-- 防火等级 --'; ?></option>
                    <?php foreach (getRefOptions('fire_rating') as $opt): ?>
                    <option value="<?php echo h($opt['value']); ?>" <?php echo ($spec['fire_rating'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                        <?php echo h($opt['label']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- FM认证 -->
            <div class="col-md-1">
                <div class="form-check form-switch mt-1">
                    <input class="form-check-input" type="checkbox" name="<?php echo $prefix; ?>[fm_approved]" value="1"
                        <?php echo ($spec['fm_approved'] ?? 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label">FM</label>
                </div>
            </div>
            <!-- 区域面积 -->
            <div class="col-md-1">
                <input type="number" class="form-control form-control-sm" name="<?php echo $prefix; ?>[zone_area]"
                       value="<?php echo h($spec['zone_area'] ?? ''); ?>" step="0.01" placeholder="<?php echo $lang === 'en' ? 'm²' : '面积m²'; ?>">
            </div>
            <!-- 备注 -->
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" name="<?php echo $prefix; ?>[remarks]"
                       value="<?php echo h($spec['remarks'] ?? ''); ?>" placeholder="<?php echo $lang === 'en' ? 'Remarks' : '备注'; ?>">
            </div>
        </div>
    </div>
</div>
<?php
    return ob_get_clean();
}
?>

<script>
// 板材规格管理
const CladdingSpec = {
    index: <?php echo count($claddingSpecs); ?>,

    addRow: function() {
        const container = document.getElementById('cladding-spec-container');
        const template = document.getElementById('cladding-spec-template');
        const emptyMsg = document.getElementById('cladding-spec-empty');

        if (emptyMsg) emptyMsg.remove();

        const html = template.innerHTML.replace(/__INDEX__/g, this.index++);
        container.insertAdjacentHTML('beforeend', html);
    },

    removeRow: function(btn) {
        const row = btn.closest('.cladding-spec-row');
        if (row) {
            row.remove();
            // 如果没有行了，显示空消息
            const container = document.getElementById('cladding-spec-container');
            if (container.querySelectorAll('.cladding-spec-row').length === 0) {
                container.innerHTML = '<div class="text-muted text-center py-3" id="cladding-spec-empty">' +
                    '<?php echo $lang === "en" ? "No cladding specifications added yet." : "暂无板材规格"; ?></div>';
            }
        }
    }
};
</script>
