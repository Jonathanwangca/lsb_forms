<?php
/**
 * RFQ Form Section: Wall Material Requirements
 * 墙面系统材质要求
 * 对应 Excel Row 76-92
 * 支持动态增删记录
 */

// ==================== 数据准备 ====================

// 预设墙面板材数据
$wallPanels = array_values(array_filter($panels ?? [], fn($p) => ($p['panel_category'] ?? '') === 'wall'));

// 分类整理板材数据
$wallOuterPanels = array_values(array_filter($wallPanels, fn($p) => ($p['panel_type'] ?? '') === 'outer'));
$wallLinerPanels = array_values(array_filter($wallPanels, fn($p) => ($p['panel_type'] ?? '') === 'liner'));
$parapetPanels = array_values(array_filter($wallPanels, fn($p) => ($p['panel_type'] ?? '') === 'parapet_liner'));
$partitionPanels = array_values(array_filter($wallPanels, fn($p) => ($p['panel_type'] ?? '') === 'partition'));

// 如果没有数据，提供默认预设
if (empty($wallOuterPanels)) {
    $wallOuterPanels = [
        ['panel_type' => 'outer', 'panel_no' => 1],
        ['panel_type' => 'outer', 'panel_no' => 2],
        ['panel_type' => 'outer', 'panel_no' => 3],
    ];
}
if (empty($wallLinerPanels)) {
    $wallLinerPanels = [
        ['panel_type' => 'liner', 'panel_no' => 1],
        ['panel_type' => 'liner', 'panel_no' => 2],
        ['panel_type' => 'liner', 'panel_no' => 3],
    ];
}
if (empty($parapetPanels)) {
    $parapetPanels = [
        ['panel_type' => 'parapet_liner', 'panel_no' => 1],
    ];
}
if (empty($partitionPanels)) {
    $partitionPanels = [
        ['panel_type' => 'partition', 'panel_no' => 1],
    ];
}

// 预设墙面保温棉数据
$wallInsulations = array_values(array_filter($insulations ?? [], fn($i) => ($i['insulation_category'] ?? '') === 'wall'));
if (empty($wallInsulations)) {
    $wallInsulations = [
        ['insulation_no' => 1],
        ['insulation_no' => 2],
        ['insulation_no' => 3],
    ];
}

// ==================== 渲染函数 ====================

/**
 * 渲染墙面板材行
 */
function renderWallPanelRow($panel, $index, $panelType, $profileRef, $lang, $selectPlaceholder) {
    $prefix = "panels[wall_{$panelType}_{$index}]";
    $labelMap = [
        'outer' => $lang === 'en' ? 'Wall Outer ' : '墙面外板',
        'liner' => $lang === 'en' ? 'Wall Liner ' : '墙面内衬板',
        'parapet_liner' => $lang === 'en' ? 'Parapet Liner ' : '女儿墙内衬板',
        'partition' => $lang === 'en' ? 'Partition Panel ' : '内隔墙墙面板',
    ];
    $label = ($labelMap[$panelType] ?? $panelType) . $index;
    ob_start();
?>
<tr class="wall-panel-row" data-index="<?php echo $index; ?>" data-type="<?php echo $panelType; ?>">
    <td class="text-nowrap">
        <strong><span class="panel-label"><?php echo $label; ?></span>:</strong>
        <input type="hidden" name="<?php echo $prefix; ?>[panel_category]" value="wall">
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
            <?php if ($panelType === 'parapet_liner'): ?>
            <option value="same_as_wall" <?php echo ($panel['profile'] ?? '') == 'same_as_wall' ? 'selected' : ''; ?>>
                <?php echo $lang === 'en' ? 'Same as wall' : '同墙面外板'; ?>
            </option>
            <?php endif; ?>
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
            <?php foreach (getRefOptions('panel_color_ral') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($panel['color'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[origin]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('panel_brand') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($panel['origin'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td class="text-center" style="width:50px;">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="WallMaterial.removePanelRow(this, '<?php echo $panelType; ?>')">
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>
<?php
    return ob_get_clean();
}

/**
 * 渲染墙面保温棉行
 */
function renderWallInsulationRow($insul, $index, $lang, $selectPlaceholder) {
    $prefix = "insulations[wall_{$index}]";
    $label = ($lang === 'en' ? 'Wall Insulation ' : '墙面保温棉') . $index;
    ob_start();
?>
<tr class="wall-insulation-row" data-index="<?php echo $index; ?>">
    <td class="text-nowrap" style="width:140px;">
        <strong><span class="insul-label"><?php echo $label; ?></span>:</strong>
        <input type="hidden" name="<?php echo $prefix; ?>[insulation_category]" value="wall">
        <input type="hidden" name="<?php echo $prefix; ?>[insulation_no]" class="insul-no" value="<?php echo $index; ?>">
    </td>
    <td style="width:80px;">
        <input type="number" class="form-control form-control-sm" name="<?php echo $prefix; ?>[thickness]"
               value="<?php echo h($insul['thickness'] ?? ''); ?>" placeholder="mm">
    </td>
    <td style="width:90px;">
        <input type="number" class="form-control form-control-sm" name="<?php echo $prefix; ?>[density]"
               value="<?php echo h($insul['density'] ?? ''); ?>" placeholder="kg/m³">
    </td>
    <td style="width:130px;">
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[facing]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('insulation_facing') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($insul['facing'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td style="width:130px;">
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[flame_retardant]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('flame_retardant') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($insul['flame_retardant'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td style="width:110px;">
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[color]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('insulation_color') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($insul['color'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td style="width:130px;">
        <select class="form-select form-select-sm" name="<?php echo $prefix; ?>[brand]">
            <option value=""><?php echo $selectPlaceholder; ?></option>
            <?php foreach (getRefOptions('insulation_brand') as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>" <?php echo ($insul['brand'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                <?php echo h($opt['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td style="min-width:150px;">
        <input type="text" class="form-control form-control-sm" name="<?php echo $prefix; ?>[other_requirements]"
               value="<?php echo h($insul['other_requirements'] ?? ''); ?>">
    </td>
    <td class="text-center" style="width:50px;">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="WallMaterial.removeInsulationRow(this)">
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>
<?php
    return ob_get_clean();
}
?>
<!-- ========== 墙面系统材质要求 ========== -->
<div class="form-section">
    <div class="form-section-header">
        <i class="bi bi-building"></i> <?php echo sectionTitle('墙面系统材质要求', 'Wall Material Requirements'); ?>
    </div>
    <div class="form-section-body">
        <!-- 墙面配置 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><?php echo sectionTitle('墙面配置', 'Wall Configuration'); ?></div>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Outer Curb Height' : '墙面外板墙裙高度'; ?></label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.1" class="form-control"
                               name="envelope[wall_outer_curb_height]"
                               value="<?php echo h($envelope['wall_outer_curb_height'] ?? ''); ?>">
                        <span class="input-group-text">m</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Outer Layout' : '外板铺设方式'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[wall_outer_layout]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('wall_panel_layout') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>" <?php echo ($envelope['wall_outer_layout'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Liner Curb Height' : '墙面内板墙裙高度'; ?></label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.1" class="form-control"
                               name="envelope[wall_liner_curb_height]"
                               value="<?php echo h($envelope['wall_liner_curb_height'] ?? ''); ?>">
                        <span class="input-group-text">m</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Liner Layout' : '内板铺设方式'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[wall_liner_layout]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('wall_panel_layout') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>" <?php echo ($envelope['wall_liner_layout'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- 墙面外板 -->
        <div class="form-subsection">
            <div class="form-subsection-title d-flex justify-content-between align-items-center">
                <span><?php echo sectionTitle('墙面外板', 'Wall Outer Panel'); ?></span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="WallMaterial.addPanelRow('outer')">
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
                <tbody id="wall-outer-panel-container">
                    <?php foreach ($wallOuterPanels as $idx => $panel): ?>
                    <?php echo renderWallPanelRow($panel, $idx + 1, 'outer', 'panel_profile_wall', $lang, $selectPlaceholder); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- 墙面保温棉 -->
        <div class="form-subsection">
            <div class="form-subsection-title d-flex justify-content-between align-items-center">
                <span><?php echo sectionTitle('墙面保温棉', 'Wall Insulation'); ?></span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="WallMaterial.addInsulationRow()">
                    <i class="bi bi-plus"></i> <?php echo $lang === 'en' ? 'Add' : '添加'; ?>
                </button>
            </div>
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:140px;"><?php echo $lang === 'en' ? 'Item' : '项目'; ?></th>
                        <th style="width:80px;"><?php echo $lang === 'en' ? 'Thick.' : '厚度'; ?></th>
                        <th style="width:90px;"><?php echo $lang === 'en' ? 'Density' : '容重'; ?></th>
                        <th style="width:130px;"><?php echo $lang === 'en' ? 'Facing' : '贴面'; ?></th>
                        <th style="width:130px;"><?php echo $lang === 'en' ? 'Flame' : '阻燃'; ?></th>
                        <th style="width:110px;"><?php echo $lang === 'en' ? 'Color' : '颜色'; ?></th>
                        <th style="width:130px;"><?php echo $lang === 'en' ? 'Brand' : '品牌'; ?></th>
                        <th style="min-width:150px;"><?php echo $lang === 'en' ? 'Other' : '其他要求'; ?></th>
                        <th style="width:50px;"><?php echo $lang === 'en' ? 'Act.' : '操作'; ?></th>
                    </tr>
                </thead>
                <tbody id="wall-insulation-container">
                    <?php foreach ($wallInsulations as $idx => $insul): ?>
                    <?php echo renderWallInsulationRow($insul, $idx + 1, $lang, $selectPlaceholder); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- 墙面防水透气膜/隔汽膜/钢丝网 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><?php echo sectionTitle('墙面防水透气膜/隔汽膜/钢丝网', 'Wall Membrane & Wire Mesh'); ?></div>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Waterproof Membrane' : '防水透气膜'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[wall_waterproof_membrane]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <option value="none" <?php echo ($envelope['wall_waterproof_membrane'] ?? '') == 'none' ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'None' : '无'; ?></option>
                        <option value="yes" <?php echo ($envelope['wall_waterproof_membrane'] ?? '') == 'yes' ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'Yes' : '有'; ?></option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Vapor Barrier' : '隔汽膜'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[wall_vapor_barrier]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <option value="none" <?php echo ($envelope['wall_vapor_barrier'] ?? '') == 'none' ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'None' : '无'; ?></option>
                        <option value="yes" <?php echo ($envelope['wall_vapor_barrier'] ?? '') == 'yes' ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'Yes' : '有'; ?></option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Wire Mesh' : '钢丝网'; ?></label>
                    <select class="form-select form-select-sm" name="envelope[wall_wire_mesh]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <option value="none" <?php echo ($envelope['wall_wire_mesh'] ?? '') == 'none' ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'None' : '无'; ?></option>
                        <?php foreach (getRefOptions('wire_mesh') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>" <?php echo ($envelope['wall_wire_mesh'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- 墙面内衬板 -->
        <div class="form-subsection">
            <div class="form-subsection-title d-flex justify-content-between align-items-center">
                <span><?php echo sectionTitle('墙面内衬板', 'Wall Liner Panel'); ?></span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="WallMaterial.addPanelRow('liner')">
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
                <tbody id="wall-liner-panel-container">
                    <?php foreach ($wallLinerPanels as $idx => $panel): ?>
                    <?php echo renderWallPanelRow($panel, $idx + 1, 'liner', 'panel_profile_liner', $lang, $selectPlaceholder); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- 女儿墙内衬板 -->
        <div class="form-subsection">
            <div class="form-subsection-title d-flex justify-content-between align-items-center">
                <span><?php echo sectionTitle('女儿墙内衬板', 'Parapet Liner Panel'); ?></span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="WallMaterial.addPanelRow('parapet_liner')">
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
                <tbody id="wall-parapet_liner-panel-container">
                    <?php foreach ($parapetPanels as $idx => $panel): ?>
                    <?php echo renderWallPanelRow($panel, $idx + 1, 'parapet_liner', 'panel_profile_wall', $lang, $selectPlaceholder); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- 内隔墙墙面板 -->
        <div class="form-subsection">
            <div class="form-subsection-title d-flex justify-content-between align-items-center">
                <span><?php echo sectionTitle('内隔墙墙面板', 'Partition Wall Panel'); ?></span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="WallMaterial.addPanelRow('partition')">
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
                <tbody id="wall-partition-panel-container">
                    <?php foreach ($partitionPanels as $idx => $panel): ?>
                    <?php echo renderWallPanelRow($panel, $idx + 1, 'partition', 'panel_profile_liner', $lang, $selectPlaceholder); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 行模板 -->
<template id="wall-outer-panel-template">
    <?php echo renderWallPanelRow([], '__INDEX__', 'outer', 'panel_profile_wall', $lang, $selectPlaceholder); ?>
</template>
<template id="wall-liner-panel-template">
    <?php echo renderWallPanelRow([], '__INDEX__', 'liner', 'panel_profile_liner', $lang, $selectPlaceholder); ?>
</template>
<template id="wall-parapet_liner-panel-template">
    <?php echo renderWallPanelRow([], '__INDEX__', 'parapet_liner', 'panel_profile_wall', $lang, $selectPlaceholder); ?>
</template>
<template id="wall-partition-panel-template">
    <?php echo renderWallPanelRow([], '__INDEX__', 'partition', 'panel_profile_liner', $lang, $selectPlaceholder); ?>
</template>
<template id="wall-insulation-template">
    <?php echo renderWallInsulationRow([], '__INDEX__', $lang, $selectPlaceholder); ?>
</template>

<script>
// 墙面材质管理器
const WallMaterial = {
    outerPanelIndex: <?php echo count($wallOuterPanels); ?>,
    linerPanelIndex: <?php echo count($wallLinerPanels); ?>,
    parapetPanelIndex: <?php echo count($parapetPanels); ?>,
    partitionPanelIndex: <?php echo count($partitionPanels); ?>,
    insulationIndex: <?php echo count($wallInsulations); ?>,

    // 添加板材行
    addPanelRow: function(panelType) {
        const containerId = 'wall-' + panelType + '-panel-container';
        const templateId = 'wall-' + panelType + '-panel-template';
        const container = document.getElementById(containerId);
        const template = document.getElementById(templateId);

        let newIndex;
        switch(panelType) {
            case 'outer': newIndex = ++this.outerPanelIndex; break;
            case 'liner': newIndex = ++this.linerPanelIndex; break;
            case 'parapet_liner': newIndex = ++this.parapetPanelIndex; break;
            case 'partition': newIndex = ++this.partitionPanelIndex; break;
        }

        const html = template.innerHTML.replace(/__INDEX__/g, newIndex);
        container.insertAdjacentHTML('beforeend', html);
    },

    // 删除板材行
    removePanelRow: function(btn, panelType) {
        const containerId = 'wall-' + panelType + '-panel-container';
        const container = document.getElementById(containerId);
        if (container.querySelectorAll('.wall-panel-row').length <= 1) {
            alert('<?php echo $lang === "en" ? "At least one record is required." : "至少需要保留一条记录"; ?>');
            return;
        }
        btn.closest('.wall-panel-row').remove();
        this.renumberPanelRows(panelType);
    },

    // 添加保温棉行
    addInsulationRow: function() {
        const container = document.getElementById('wall-insulation-container');
        const template = document.getElementById('wall-insulation-template');
        const html = template.innerHTML.replace(/__INDEX__/g, ++this.insulationIndex);
        container.insertAdjacentHTML('beforeend', html);
    },

    // 删除保温棉行
    removeInsulationRow: function(btn) {
        const container = document.getElementById('wall-insulation-container');
        if (container.querySelectorAll('.wall-insulation-row').length <= 1) {
            alert('<?php echo $lang === "en" ? "At least one record is required." : "至少需要保留一条记录"; ?>');
            return;
        }
        btn.closest('.wall-insulation-row').remove();
        this.renumberInsulationRows();
    },

    // 重新编号板材行
    renumberPanelRows: function(panelType) {
        const containerId = 'wall-' + panelType + '-panel-container';
        const container = document.getElementById(containerId);
        const rows = container.querySelectorAll('.wall-panel-row');
        const labelMap = {
            'outer': '<?php echo $lang === "en" ? "Wall Outer " : "墙面外板"; ?>',
            'liner': '<?php echo $lang === "en" ? "Wall Liner " : "墙面内衬板"; ?>',
            'parapet_liner': '<?php echo $lang === "en" ? "Parapet Liner " : "女儿墙内衬板"; ?>',
            'partition': '<?php echo $lang === "en" ? "Partition Panel " : "内隔墙墙面板"; ?>'
        };

        rows.forEach((row, idx) => {
            const num = idx + 1;
            row.dataset.index = num;

            // 更新标签
            const label = row.querySelector('.panel-label');
            if (label) label.textContent = labelMap[panelType] + num;

            // 更新隐藏字段
            const noInput = row.querySelector('.panel-no');
            if (noInput) noInput.value = num;

            // 更新所有input/select的name属性
            const newPrefix = `panels[wall_${panelType}_${num}]`;
            row.querySelectorAll('input, select').forEach(input => {
                const name = input.name;
                if (name) {
                    const newName = name.replace(/panels\[wall_\w+_\d+\]/, newPrefix);
                    input.name = newName;
                }
            });
        });
    },

    // 重新编号保温棉行
    renumberInsulationRows: function() {
        const container = document.getElementById('wall-insulation-container');
        const rows = container.querySelectorAll('.wall-insulation-row');
        const labelPrefix = '<?php echo $lang === "en" ? "Wall Insulation " : "墙面保温棉"; ?>';

        rows.forEach((row, idx) => {
            const num = idx + 1;
            row.dataset.index = num;

            // 更新标签
            const label = row.querySelector('.insul-label');
            if (label) label.textContent = labelPrefix + num;

            // 更新隐藏字段
            const noInput = row.querySelector('.insul-no');
            if (noInput) noInput.value = num;

            // 更新所有input/select的name属性
            const newPrefix = `insulations[wall_${num}]`;
            row.querySelectorAll('input, select').forEach(input => {
                const name = input.name;
                if (name) {
                    const newName = name.replace(/insulations\[wall_\d+\]/, newPrefix);
                    input.name = newName;
                }
            });
        });
    }
};
</script>
