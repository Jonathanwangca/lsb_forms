<?php
/**
 * RFQ Form Section: Roof Material Requirements
 * 屋面系统材质要求
 * 对应 Excel Row 50-75
 * 支持动态增删记录
 */

// ==================== 数据准备 ====================

// 预设屋面板材数据
$roofPanels = array_values(array_filter($panels ?? [], fn($p) => ($p['panel_category'] ?? '') === 'roof'));

// 分类整理板材数据
$outerPanels = array_values(array_filter($roofPanels, fn($p) => ($p['panel_type'] ?? '') === 'outer'));
$linerPanels = array_values(array_filter($roofPanels, fn($p) => ($p['panel_type'] ?? '') === 'liner'));
$canopyPanels = array_values(array_filter($roofPanels, fn($p) => in_array($p['panel_type'] ?? '', ['canopy_upper', 'canopy_lower'])));

// 如果没有数据，提供默认预设
if (empty($outerPanels)) {
    $outerPanels = [
        ['panel_type' => 'outer', 'panel_no' => 1],
        ['panel_type' => 'outer', 'panel_no' => 2],
        ['panel_type' => 'outer', 'panel_no' => 3],
    ];
}
if (empty($linerPanels)) {
    $linerPanels = [
        ['panel_type' => 'liner', 'panel_no' => 1],
        ['panel_type' => 'liner', 'panel_no' => 2],
        ['panel_type' => 'liner', 'panel_no' => 3],
    ];
}
if (empty($canopyPanels)) {
    $canopyPanels = [
        ['panel_type' => 'canopy_upper', 'panel_no' => 1],
        ['panel_type' => 'canopy_lower', 'panel_no' => 1],
    ];
}

// 预设保温棉数据
$roofInsulations = array_values(array_filter($insulations ?? [], fn($i) => ($i['insulation_category'] ?? '') === 'roof'));
if (empty($roofInsulations)) {
    $roofInsulations = [
        ['insulation_no' => 1],
        ['insulation_no' => 2],
        ['insulation_no' => 3],
    ];
}

// 预设排水数据 - 数据库使用 roof_1, roof_2, canopy 作为类型值
$roofDrainages = array_values(array_filter($drainages ?? [], fn($d) => in_array($d['drainage_type'] ?? '', ['roof_1', 'roof_2', 'canopy'])));
if (empty($roofDrainages)) {
    $roofDrainages = [
        ['drainage_type' => 'roof_1', 'drainage_no' => 1],
        ['drainage_type' => 'roof_2', 'drainage_no' => 2],
        ['drainage_type' => 'canopy', 'drainage_no' => 1],
    ];
}

// ==================== 渲染函数 ====================

/**
 * 渲染排水行
 */
function renderRoofDrainageRow($drain, $index, $lang, $selectPlaceholder) {
    $prefix = "drainages[roof_{$index}]";
    $drainType = $drain['drainage_type'] ?? 'roof_1';
    $isCanopy = $drainType === 'canopy';
    $label = $isCanopy
        ? ($lang === 'en' ? 'Canopy Drainage' : '雨蓬排水')
        : ($lang === 'en' ? 'Roof Drainage ' : '屋面排水') . $index;
    ob_start();
?>
<tr class="drainage-row" data-index="<?php echo $index; ?>">
    <td class="text-nowrap">
        <strong><span class="drain-label"><?php echo $label; ?></span>:</strong>
        <input type="hidden" name="<?php echo $prefix; ?>[drainage_type]" class="drain-type" value="<?php echo h($drainType); ?>">
        <input type="hidden" name="<?php echo $prefix; ?>[drainage_no]" class="drain-no" value="<?php echo $index; ?>">
    </td>
    <td>
        <select class="form-select form-select-sm drain-type-select" onchange="RoofMaterial.updateDrainType(this, <?php echo $index; ?>)">
            <option value="roof_1" <?php echo $drainType === 'roof_1' ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'Roof 1' : '屋面1'; ?></option>
            <option value="roof_2" <?php echo $drainType === 'roof_2' ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'Roof 2' : '屋面2'; ?></option>
            <option value="canopy" <?php echo $drainType === 'canopy' ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'Canopy' : '雨蓬'; ?></option>
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[method]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('drainage_method') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($drain['method'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <input type="text" class="form-control form-control-sm" name="<?php echo $prefix; ?>[scope]"
               value="<?php echo h($drain['scope'] ?? ''); ?>" placeholder="<?php echo $lang === 'en' ? 'Scope' : '范围'; ?>">
    </td>
    <td>
        <input type="text" class="form-control form-control-sm" name="<?php echo $prefix; ?>[gutter_spec]"
               value="<?php echo h($drain['gutter_spec'] ?? ''); ?>" placeholder="<?php echo $lang === 'en' ? 'e.g., 1.2mm SS' : '如：1.2mm不锈钢'; ?>">
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[downpipe_type]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('downpipe_type') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($drain['downpipe_type'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td class="text-center" style="width:50px;">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="RoofMaterial.removeDrainageRow(this)">
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>
<?php
    return ob_get_clean();
}

/**
 * 渲染外板/内衬板行
 */
function renderRoofPanelRow($panel, $index, $panelType, $profileRef, $lang, $selectPlaceholder) {
    $prefix = "panels[roof_{$panelType}_{$index}]";
    $labelMap = [
        'outer' => $lang === 'en' ? 'Outer Panel ' : '屋面外板',
        'liner' => $lang === 'en' ? 'Liner Panel ' : '屋面内衬板',
    ];
    $label = ($labelMap[$panelType] ?? $panelType) . $index;
    ob_start();
?>
<tr class="panel-row" data-index="<?php echo $index; ?>" data-type="<?php echo $panelType; ?>">
    <td class="text-nowrap">
        <strong><span class="panel-label"><?php echo $label; ?></span>:</strong>
        <input type="hidden" name="<?php echo $prefix; ?>[panel_category]" value="roof">
        <input type="hidden" name="<?php echo $prefix; ?>[panel_type]" value="<?php echo $panelType; ?>">
        <input type="hidden" name="<?php echo $prefix; ?>[panel_no]" class="panel-no" value="<?php echo $index; ?>">
    </td>
    <td>
        <input type="number" step="0.01" class="form-control form-control-sm" name="<?php echo $prefix; ?>[thickness]"
               value="<?php echo h($panel['thickness'] ?? ''); ?>" placeholder="mm">
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[profile]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions($profileRef) as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($panel['profile'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[strength]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('panel_strength') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($panel['strength'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[coating]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('panel_coating') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($panel['coating'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[galvanizing]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('panel_galvanizing') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($panel['galvanizing'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[color]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('panel_color') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($panel['color'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[origin]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('panel_origin') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($panel['origin'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td class="text-center" style="width:50px;">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="RoofMaterial.removePanelRow(this, '<?php echo $panelType; ?>')">
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>
<?php
    return ob_get_clean();
}

/**
 * 渲染保温棉行
 */
function renderRoofInsulationRow($insul, $index, $lang, $selectPlaceholder) {
    $prefix = "insulations[roof_{$index}]";
    $label = ($lang === 'en' ? 'Roof Insulation ' : '屋面保温棉') . $index;
    ob_start();
?>
<tr class="insulation-row" data-index="<?php echo $index; ?>">
    <td class="text-nowrap">
        <strong><span class="insul-label"><?php echo $label; ?></span>:</strong>
        <input type="hidden" name="<?php echo $prefix; ?>[insulation_category]" value="roof">
        <input type="hidden" name="<?php echo $prefix; ?>[insulation_no]" class="insul-no" value="<?php echo $index; ?>">
    </td>
    <td>
        <input type="number" class="form-control form-control-sm" name="<?php echo $prefix; ?>[thickness]"
               value="<?php echo h($insul['thickness'] ?? ''); ?>" placeholder="mm">
    </td>
    <td>
        <input type="number" class="form-control form-control-sm" name="<?php echo $prefix; ?>[density]"
               value="<?php echo h($insul['density'] ?? ''); ?>" placeholder="kg/m³">
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[facing]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('insulation_facing') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($insul['facing'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[flame_retardant]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('flame_retardant') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($insul['flame_retardant'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[color]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('insulation_color') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($insul['color'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[brand]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('insulation_brand') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($insul['brand'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <input type="text" class="form-control form-control-sm" name="<?php echo $prefix; ?>[other_requirements]"
               value="<?php echo h($insul['other_requirements'] ?? ''); ?>">
    </td>
    <td class="text-center" style="width:50px;">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="RoofMaterial.removeInsulationRow(this)">
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>
<?php
    return ob_get_clean();
}

/**
 * 渲染雨蓬板行
 */
function renderCanopyPanelRow($panel, $index, $panelType, $lang, $selectPlaceholder) {
    $prefix = "panels[roof_canopy_{$index}]";
    $labelMap = [
        'canopy_upper' => $lang === 'en' ? 'Canopy Upper Panel' : '大雨蓬上板',
        'canopy_lower' => $lang === 'en' ? 'Canopy Lower Panel' : '大雨蓬下板',
    ];
    $label = $labelMap[$panelType] ?? $panelType;
    ob_start();
?>
<tr class="canopy-row" data-index="<?php echo $index; ?>" data-type="<?php echo $panelType; ?>">
    <td class="text-nowrap">
        <strong><?php echo $label; ?>:</strong>
        <input type="hidden" name="<?php echo $prefix; ?>[panel_category]" value="roof">
        <input type="hidden" name="<?php echo $prefix; ?>[panel_type]" value="<?php echo $panelType; ?>">
        <input type="hidden" name="<?php echo $prefix; ?>[panel_no]" value="1">
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[panel_type_select]" onchange="RoofMaterial.updateCanopyType(this, <?php echo $index; ?>)">
            <option value="canopy_upper" <?php echo $panelType === 'canopy_upper' ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'Upper' : '上板'; ?></option>
            <option value="canopy_lower" <?php echo $panelType === 'canopy_lower' ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'Lower' : '下板'; ?></option>
        </select>
    </td>
    <td>
        <input type="number" step="0.01" class="form-control form-control-sm" name="<?php echo $prefix; ?>[thickness]"
               value="<?php echo h($panel['thickness'] ?? ''); ?>" placeholder="mm">
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[profile]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <option value="same_as_roof" <?php echo ($panel['profile'] ?? '') == 'same_as_roof' ? 'selected' : ''; ?>>
                <?php echo $lang === 'en' ? 'Same as roof' : '同屋面外板'; ?>
            </option>
            <?php foreach (getRefOptions('panel_profile_roof') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($panel['profile'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[coating]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('panel_coating') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($panel['coating'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[color]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('panel_color') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($panel['color'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[origin]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('panel_origin') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($panel['origin'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td class="text-center" style="width:50px;">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="RoofMaterial.removeCanopyRow(this)">
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>
<?php
    return ob_get_clean();
}
?>
<!-- ========== 屋面系统材质要求 ========== -->
<div class="form-section">
    <div class="form-section-header">
        <i class="bi bi-house-door"></i> <span class="section-number">I.</span> <?php echo sectionTitle('屋面系统材质要求', 'Roof Material Requirements'); ?>
    </div>
    <div class="form-section-body">
        <!-- 屋面排水系统 -->
        <div class="form-subsection">
            <div class="form-subsection-title d-flex justify-content-between align-items-center">
                <span><span class="subsection-number">I.1</span> <?php echo sectionTitle('屋面排水系统', 'Roof Drainage System'); ?></span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="RoofMaterial.addDrainageRow()">
                    <i class="bi bi-plus"></i> <?php echo $lang === 'en' ? 'Add' : '添加'; ?>
                </button>
            </div>
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:120px;"><?php echo $lang === 'en' ? 'Item' : '项目'; ?></th>
                        <th style="width:100px;"><?php echo $lang === 'en' ? 'Type' : '类型'; ?></th>
                        <th style="width:140px;"><?php echo $lang === 'en' ? 'Method' : '排水方式'; ?></th>
                        <th style="width:100px;"><?php echo $lang === 'en' ? 'Scope' : '范围'; ?></th>
                        <th style="width:160px;"><?php echo $lang === 'en' ? 'Gutter Spec' : '天沟规格'; ?></th>
                        <th style="width:140px;"><?php echo $lang === 'en' ? 'Downspout' : '落水管'; ?></th>
                        <th style="width:50px;"><?php echo $lang === 'en' ? 'Act.' : '操作'; ?></th>
                    </tr>
                </thead>
                <tbody id="roof-drainage-container">
                    <?php foreach ($roofDrainages as $idx => $drain): ?>
                    <?php echo renderRoofDrainageRow($drain, $idx + 1, $lang, $selectPlaceholder); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- 屋面外板 -->
        <div class="form-subsection">
            <div class="form-subsection-title d-flex justify-content-between align-items-center">
                <span><span class="subsection-number">I.2</span> <?php echo sectionTitle('屋面外板', 'Roof Outer Panel'); ?></span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="RoofMaterial.addPanelRow('outer')">
                    <i class="bi bi-plus"></i> <?php echo $lang === 'en' ? 'Add' : '添加'; ?>
                </button>
            </div>
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:120px;"><?php echo $lang === 'en' ? 'Item' : '项目'; ?></th>
                        <th style="width:65px;"><?php echo $lang === 'en' ? 'Thick.' : '总厚'; ?></th>
                        <th style="width:110px;"><?php echo $lang === 'en' ? 'Profile' : '板型'; ?></th>
                        <th style="width:90px;"><?php echo $lang === 'en' ? 'Strength' : '强度'; ?></th>
                        <th style="width:90px;"><?php echo $lang === 'en' ? 'Coating' : '涂层'; ?></th>
                        <th style="width:110px;"><?php echo $lang === 'en' ? 'Galv.' : '镀锌'; ?></th>
                        <th style="width:90px;"><?php echo $lang === 'en' ? 'Color' : '颜色'; ?></th>
                        <th style="width:90px;"><?php echo $lang === 'en' ? 'Origin' : '产地'; ?></th>
                        <th style="width:50px;"><?php echo $lang === 'en' ? 'Act.' : '操作'; ?></th>
                    </tr>
                </thead>
                <tbody id="roof-outer-panel-container">
                    <?php foreach ($outerPanels as $idx => $panel): ?>
                    <?php echo renderRoofPanelRow($panel, $idx + 1, 'outer', 'panel_profile_roof', $lang, $selectPlaceholder); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- 屋面保温棉 -->
        <div class="form-subsection">
            <div class="form-subsection-title d-flex justify-content-between align-items-center">
                <span><span class="subsection-number">I.3</span> <?php echo sectionTitle('屋面保温棉', 'Roof Insulation'); ?></span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="RoofMaterial.addInsulationRow()">
                    <i class="bi bi-plus"></i> <?php echo $lang === 'en' ? 'Add' : '添加'; ?>
                </button>
            </div>
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:130px;"><?php echo $lang === 'en' ? 'Item' : '项目'; ?></th>
                        <th style="width:75px;"><?php echo $lang === 'en' ? 'Thick.' : '厚度'; ?></th>
                        <th style="width:75px;"><?php echo $lang === 'en' ? 'Density' : '容重'; ?></th>
                        <th style="width:120px;"><?php echo $lang === 'en' ? 'Facing' : '贴面'; ?></th>
                        <th style="width:120px;"><?php echo $lang === 'en' ? 'Flame' : '阻燃'; ?></th>
                        <th style="width:100px;"><?php echo $lang === 'en' ? 'Color' : '颜色'; ?></th>
                        <th style="width:120px;"><?php echo $lang === 'en' ? 'Brand' : '品牌'; ?></th>
                        <th style="width:100px;"><?php echo $lang === 'en' ? 'Other' : '其他'; ?></th>
                        <th style="width:50px;"><?php echo $lang === 'en' ? 'Act.' : '操作'; ?></th>
                    </tr>
                </thead>
                <tbody id="roof-insulation-container">
                    <?php foreach ($roofInsulations as $idx => $insul): ?>
                    <?php echo renderRoofInsulationRow($insul, $idx + 1, $lang, $selectPlaceholder); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- 防水透气膜/隔汽膜/钢丝网 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">I.4</span> <?php echo sectionTitle('防水透气膜/隔汽膜/钢丝网', 'Membrane & Wire Mesh'); ?></div>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Waterproof Membrane' : '防水透气膜'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[roof_waterproof_membrane]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <option value="0" <?php echo (isset($envelope['roof_waterproof_membrane']) && $envelope['roof_waterproof_membrane'] == '0') ? 'selected' : ''; ?>><?php echo $noLabel; ?></option>
                        <option value="1" <?php echo ($envelope['roof_waterproof_membrane'] ?? '') == '1' ? 'selected' : ''; ?>><?php echo $yesLabel; ?></option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Material Req.' : '材料要求'; ?></label>
                    <input type="text" class="form-control form-control-sm" name="envelope[roof_waterproof_material]"
                           value="<?php echo h($envelope['roof_waterproof_material'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Wire Mesh' : '钢丝网'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[roof_wire_mesh]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('wire_mesh') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>" <?php echo ($envelope['roof_wire_mesh'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Wire Mesh Material' : '钢丝网材料'; ?></label>
                    <input type="text" class="form-control form-control-sm" name="envelope[roof_wire_mesh_material]"
                           value="<?php echo h($envelope['roof_wire_mesh_material'] ?? ''); ?>">
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Vapor Barrier' : '隔汽膜'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[roof_vapor_barrier]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <option value="0" <?php echo (isset($envelope['roof_vapor_barrier']) && $envelope['roof_vapor_barrier'] == '0') ? 'selected' : ''; ?>><?php echo $noLabel; ?></option>
                        <option value="1" <?php echo ($envelope['roof_vapor_barrier'] ?? '') == '1' ? 'selected' : ''; ?>><?php echo $yesLabel; ?></option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Vapor Material' : '隔汽膜材料'; ?></label>
                    <input type="text" class="form-control form-control-sm" name="envelope[roof_vapor_material]"
                           value="<?php echo h($envelope['roof_vapor_material'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- 屋面内衬板 -->
        <div class="form-subsection">
            <div class="form-subsection-title d-flex justify-content-between align-items-center">
                <span><span class="subsection-number">I.5</span> <?php echo sectionTitle('屋面内衬板', 'Roof Liner Panel'); ?></span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="RoofMaterial.addPanelRow('liner')">
                    <i class="bi bi-plus"></i> <?php echo $lang === 'en' ? 'Add' : '添加'; ?>
                </button>
            </div>
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:120px;"><?php echo $lang === 'en' ? 'Item' : '项目'; ?></th>
                        <th style="width:65px;"><?php echo $lang === 'en' ? 'Thick.' : '总厚'; ?></th>
                        <th style="width:110px;"><?php echo $lang === 'en' ? 'Profile' : '板型'; ?></th>
                        <th style="width:90px;"><?php echo $lang === 'en' ? 'Strength' : '强度'; ?></th>
                        <th style="width:90px;"><?php echo $lang === 'en' ? 'Coating' : '涂层'; ?></th>
                        <th style="width:110px;"><?php echo $lang === 'en' ? 'Galv.' : '镀锌'; ?></th>
                        <th style="width:90px;"><?php echo $lang === 'en' ? 'Color' : '颜色'; ?></th>
                        <th style="width:90px;"><?php echo $lang === 'en' ? 'Origin' : '产地'; ?></th>
                        <th style="width:50px;"><?php echo $lang === 'en' ? 'Act.' : '操作'; ?></th>
                    </tr>
                </thead>
                <tbody id="roof-liner-panel-container">
                    <?php foreach ($linerPanels as $idx => $panel): ?>
                    <?php echo renderRoofPanelRow($panel, $idx + 1, 'liner', 'panel_profile_liner', $lang, $selectPlaceholder); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Liner Layout' : '屋面内衬板布置方式'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[roof_liner_layout]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('liner_layout') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>" <?php echo ($envelope['roof_liner_layout'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Liner Remarks' : '其他补充说明'; ?></label>
                    <input type="text" class="form-control form-control-sm" name="envelope[roof_liner_remarks]"
                           value="<?php echo h($envelope['roof_liner_remarks'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- 大雨蓬板 -->
        <div class="form-subsection">
            <div class="form-subsection-title d-flex justify-content-between align-items-center">
                <span><span class="subsection-number">I.6</span> <?php echo sectionTitle('大雨蓬板', 'Loading Canopy Panel'); ?></span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="RoofMaterial.addCanopyRow()">
                    <i class="bi bi-plus"></i> <?php echo $lang === 'en' ? 'Add' : '添加'; ?>
                </button>
            </div>
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:110px;"><?php echo $lang === 'en' ? 'Item' : '项目'; ?></th>
                        <th style="width:90px;"><?php echo $lang === 'en' ? 'Position' : '位置'; ?></th>
                        <th style="width:65px;"><?php echo $lang === 'en' ? 'Thick.' : '总厚'; ?></th>
                        <th style="width:110px;"><?php echo $lang === 'en' ? 'Profile' : '板型'; ?></th>
                        <th style="width:90px;"><?php echo $lang === 'en' ? 'Coating' : '涂层'; ?></th>
                        <th style="width:90px;"><?php echo $lang === 'en' ? 'Color' : '颜色'; ?></th>
                        <th style="width:90px;"><?php echo $lang === 'en' ? 'Origin' : '产地'; ?></th>
                        <th style="width:50px;"><?php echo $lang === 'en' ? 'Act.' : '操作'; ?></th>
                    </tr>
                </thead>
                <tbody id="roof-canopy-panel-container">
                    <?php foreach ($canopyPanels as $idx => $panel): ?>
                    <?php echo renderCanopyPanelRow($panel, $idx + 1, $panel['panel_type'] ?? 'canopy_upper', $lang, $selectPlaceholder); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="envelope[canopy_has_insulation]" value="1" id="sw_canopy_insul_roof"
                            <?php echo ($envelope['canopy_has_insulation'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sw_canopy_insul_roof"><?php echo $lang === 'en' ? 'Has Insulation' : '大雨蓬有保温棉'; ?></label>
                    </div>
                </div>
                <div class="col-md-9">
                    <input type="text" class="form-control form-control-sm" name="envelope[canopy_insulation_remarks]"
                           value="<?php echo h($envelope['canopy_insulation_remarks'] ?? ''); ?>"
                           placeholder="<?php echo $lang === 'en' ? 'Other remarks' : '其他说明'; ?>">
                </div>
            </div>
        </div>

        <!-- 小雨蓬 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">I.7</span> <?php echo sectionTitle('标准小雨蓬', 'Small Canopy'); ?></div>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Width (mm)' : '悬挑宽度'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[small_canopy_width]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('canopy_width') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>" <?php echo ($envelope['small_canopy_width'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Method' : '做法'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[small_canopy_method]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('canopy_method') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>" <?php echo ($envelope['small_canopy_method'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Drainage' : '排水做法'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[small_canopy_drainage]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('canopy_drainage') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>" <?php echo ($envelope['small_canopy_drainage'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Remarks' : '备注'; ?></label>
                    <input type="text" class="form-control form-control-sm" name="envelope[small_canopy_remarks]"
                           value="<?php echo h($envelope['small_canopy_remarks'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- 屋面采光 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">I.8</span> <?php echo sectionTitle('屋面采光', 'Roof Skylight'); ?></div>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Layout' : '铺设方式'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[skylight_layout]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('skylight_layout') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>" <?php echo ($envelope['skylight_layout'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Material' : '材料描述'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[skylight_material]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('skylight_material') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>" <?php echo ($envelope['skylight_material'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'FM Certified' : 'FM认证'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[skylight_fm_certified]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <option value="0" <?php echo (isset($envelope['skylight_fm_certified']) && $envelope['skylight_fm_certified'] === '0') ? 'selected' : ''; ?>><?php echo $noLabel; ?></option>
                        <option value="1" <?php echo ($envelope['skylight_fm_certified'] ?? '') == '1' ? 'selected' : ''; ?>><?php echo $yesLabel; ?></option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Brand' : '品牌'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[skylight_brand]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('skylight_brand') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>" <?php echo ($envelope['skylight_brand'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Length' : '长度'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[skylight_length]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('skylight_length') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>" <?php echo ($envelope['skylight_length'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-12">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Other Requirements' : '其他要求'; ?></label>
                    <textarea class="form-control form-control-sm" name="envelope[skylight_other_requirements]" rows="2"><?php echo h($envelope['skylight_other_requirements'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- 其他屋面材料 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">I.9</span> <?php echo sectionTitle('其他屋面材料', 'Other Roof Materials'); ?></div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Rockwool Panel' : '岩棉板'; ?></label>
                    <input type="text" class="form-control form-control-sm" name="envelope[rock_wool_panel]"
                           value="<?php echo h($envelope['rock_wool_panel'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Flexible Roof' : '柔性屋面'; ?></label>
                    <input type="text" class="form-control form-control-sm" name="envelope[flexible_roof]"
                           value="<?php echo h($envelope['flexible_roof'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Other' : '其他'; ?></label>
                    <input type="text" class="form-control form-control-sm" name="envelope[envelope_other]"
                           value="<?php echo h($envelope['envelope_other'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 行模板 -->
<template id="roof-drainage-template">
    <?php echo renderRoofDrainageRow([], '__INDEX__', $lang, $selectPlaceholder); ?>
</template>
<template id="roof-outer-panel-template">
    <?php echo renderRoofPanelRow([], '__INDEX__', 'outer', 'panel_profile_roof', $lang, $selectPlaceholder); ?>
</template>
<template id="roof-liner-panel-template">
    <?php echo renderRoofPanelRow([], '__INDEX__', 'liner', 'panel_profile_liner', $lang, $selectPlaceholder); ?>
</template>
<template id="roof-insulation-template">
    <?php echo renderRoofInsulationRow([], '__INDEX__', $lang, $selectPlaceholder); ?>
</template>
<template id="roof-canopy-template">
    <?php echo renderCanopyPanelRow([], '__INDEX__', 'canopy_upper', $lang, $selectPlaceholder); ?>
</template>

<script>
// 屋面材质管理器
const RoofMaterial = {
    drainageIndex: <?php echo count($roofDrainages); ?>,
    outerPanelIndex: <?php echo count($outerPanels); ?>,
    linerPanelIndex: <?php echo count($linerPanels); ?>,
    insulationIndex: <?php echo count($roofInsulations); ?>,
    canopyIndex: <?php echo count($canopyPanels); ?>,

    // 添加排水行
    addDrainageRow: function() {
        const container = document.getElementById('roof-drainage-container');
        const template = document.getElementById('roof-drainage-template');
        const html = template.innerHTML.replace(/__INDEX__/g, ++this.drainageIndex);
        container.insertAdjacentHTML('beforeend', html);
    },

    // 删除排水行
    removeDrainageRow: function(btn) {
        const container = document.getElementById('roof-drainage-container');
        if (container.querySelectorAll('.drainage-row').length <= 1) {
            alert('<?php echo $lang === "en" ? "At least one record is required." : "至少需要保留一条记录"; ?>');
            return;
        }
        btn.closest('.drainage-row').remove();
        this.renumberRows('drainage', 'roof-drainage-container', 'drain');
    },

    // 更新排水类型标签
    updateDrainType: function(select, index) {
        const row = select.closest('.drainage-row');
        const typeInput = row.querySelector('.drain-type');
        const labelSpan = row.querySelector('.drain-label');
        const type = select.value;
        typeInput.value = type;
        const idx = row.dataset.index;
        if (type === 'canopy') {
            labelSpan.textContent = '<?php echo $lang === "en" ? "Canopy Drainage" : "雨蓬排水"; ?>';
        } else if (type === 'roof_1') {
            labelSpan.textContent = '<?php echo $lang === "en" ? "Roof Drainage 1" : "屋面排水1"; ?>';
        } else if (type === 'roof_2') {
            labelSpan.textContent = '<?php echo $lang === "en" ? "Roof Drainage 2" : "屋面排水2"; ?>';
        } else {
            labelSpan.textContent = '<?php echo $lang === "en" ? "Roof Drainage " : "屋面排水"; ?>' + idx;
        }
    },

    // 添加板材行
    addPanelRow: function(panelType) {
        const containerId = 'roof-' + panelType + '-panel-container';
        const templateId = 'roof-' + panelType + '-panel-template';
        const container = document.getElementById(containerId);
        const template = document.getElementById(templateId);

        let newIndex;
        if (panelType === 'outer') {
            newIndex = ++this.outerPanelIndex;
        } else {
            newIndex = ++this.linerPanelIndex;
        }

        const html = template.innerHTML.replace(/__INDEX__/g, newIndex);
        container.insertAdjacentHTML('beforeend', html);
    },

    // 删除板材行
    removePanelRow: function(btn, panelType) {
        const containerId = 'roof-' + panelType + '-panel-container';
        const container = document.getElementById(containerId);
        if (container.querySelectorAll('.panel-row').length <= 1) {
            alert('<?php echo $lang === "en" ? "At least one record is required." : "至少需要保留一条记录"; ?>');
            return;
        }
        btn.closest('.panel-row').remove();
        this.renumberPanelRows(panelType);
    },

    // 添加保温棉行
    addInsulationRow: function() {
        const container = document.getElementById('roof-insulation-container');
        const template = document.getElementById('roof-insulation-template');
        const html = template.innerHTML.replace(/__INDEX__/g, ++this.insulationIndex);
        container.insertAdjacentHTML('beforeend', html);
    },

    // 删除保温棉行
    removeInsulationRow: function(btn) {
        const container = document.getElementById('roof-insulation-container');
        if (container.querySelectorAll('.insulation-row').length <= 1) {
            alert('<?php echo $lang === "en" ? "At least one record is required." : "至少需要保留一条记录"; ?>');
            return;
        }
        btn.closest('.insulation-row').remove();
        this.renumberRows('insulation', 'roof-insulation-container', 'insul');
    },

    // 添加雨蓬板行
    addCanopyRow: function() {
        const container = document.getElementById('roof-canopy-panel-container');
        const template = document.getElementById('roof-canopy-template');
        const html = template.innerHTML.replace(/__INDEX__/g, ++this.canopyIndex);
        container.insertAdjacentHTML('beforeend', html);
    },

    // 删除雨蓬板行
    removeCanopyRow: function(btn) {
        const container = document.getElementById('roof-canopy-panel-container');
        if (container.querySelectorAll('.canopy-row').length <= 1) {
            alert('<?php echo $lang === "en" ? "At least one record is required." : "至少需要保留一条记录"; ?>');
            return;
        }
        btn.closest('.canopy-row').remove();
    },

    // 更新雨蓬板类型
    updateCanopyType: function(select, index) {
        const row = select.closest('.canopy-row');
        const typeInput = row.querySelector('input[name*="panel_type"]');
        if (typeInput) {
            typeInput.value = select.value;
        }
    },

    // 重新编号行
    renumberRows: function(rowType, containerId, prefix) {
        const container = document.getElementById(containerId);
        const rows = container.querySelectorAll('.' + rowType + '-row');
        const labelPrefix = {
            'drainage': '<?php echo $lang === "en" ? "Roof Drainage " : "屋面排水"; ?>',
            'insulation': '<?php echo $lang === "en" ? "Roof Insulation " : "屋面保温棉"; ?>'
        };

        rows.forEach((row, idx) => {
            const num = idx + 1;
            row.dataset.index = num;

            // 更新标签
            const label = row.querySelector('.' + prefix + '-label');
            if (label) {
                if (rowType === 'drainage') {
                    const typeSelect = row.querySelector('.drain-type-select');
                    const type = typeSelect ? typeSelect.value : 'roof_1';
                    if (type === 'canopy') {
                        label.textContent = '<?php echo $lang === "en" ? "Canopy Drainage" : "雨蓬排水"; ?>';
                    } else if (type === 'roof_1') {
                        label.textContent = '<?php echo $lang === "en" ? "Roof Drainage 1" : "屋面排水1"; ?>';
                    } else if (type === 'roof_2') {
                        label.textContent = '<?php echo $lang === "en" ? "Roof Drainage 2" : "屋面排水2"; ?>';
                    } else {
                        label.textContent = labelPrefix[rowType] + num;
                    }
                } else {
                    label.textContent = labelPrefix[rowType] + num;
                }
            }

            // 更新隐藏字段
            const noInput = row.querySelector('.' + prefix + '-no');
            if (noInput) noInput.value = num;

            // 更新所有input/select的name属性
            const newPrefix = rowType === 'drainage' ? `drainages[roof_${num}]` : `insulations[roof_${num}]`;
            row.querySelectorAll('input, select').forEach(input => {
                const name = input.name;
                if (name) {
                    const newName = name.replace(/\[roof_\d+\]/, `[roof_${num}]`);
                    input.name = newName;
                }
            });
        });
    },

    // 重新编号板材行
    renumberPanelRows: function(panelType) {
        const containerId = 'roof-' + panelType + '-panel-container';
        const container = document.getElementById(containerId);
        const rows = container.querySelectorAll('.panel-row');
        const labelPrefix = panelType === 'outer'
            ? '<?php echo $lang === "en" ? "Outer Panel " : "屋面外板"; ?>'
            : '<?php echo $lang === "en" ? "Liner Panel " : "屋面内衬板"; ?>';

        rows.forEach((row, idx) => {
            const num = idx + 1;
            row.dataset.index = num;

            // 更新标签
            const label = row.querySelector('.panel-label');
            if (label) label.textContent = labelPrefix + num;

            // 更新隐藏字段
            const noInput = row.querySelector('.panel-no');
            if (noInput) noInput.value = num;

            // 更新所有input/select的name属性
            const newPrefix = `panels[roof_${panelType}_${num}]`;
            row.querySelectorAll('input, select').forEach(input => {
                const name = input.name;
                if (name) {
                    const newName = name.replace(/panels\[roof_\w+_\d+\]/, newPrefix);
                    input.name = newName;
                }
            });
        });
    }
};
</script>
