<?php
/**
 * RFQ Form Section: Building Dimensions & Steel
 * 建筑尺寸 & 钢结构
 */
?>
<!-- ========== 建筑尺寸 & 钢结构 ========== -->
<div class="form-section">
    <div class="form-section-header">
        <i class="bi bi-rulers"></i> <span class="section-number">F.</span> <?php echo sectionTitle('建筑描述 & 钢结构材料', 'Building Dimensions & Steel'); ?>
    </div>
    <div class="form-section-body">
        <!-- 建筑尺寸 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">F.1</span> <?php echo sectionTitle('建筑尺寸', 'Building Dimensions'); ?></div>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('length'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="steel[length]"
                           value="<?php echo h($steel['length'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('length_source'); ?></label>
                    <input type="text" class="form-control" name="steel[length_source]"
                           value="<?php echo h($steel['length_source'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('width'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="steel[width]"
                           value="<?php echo h($steel['width'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('width_source'); ?></label>
                    <input type="text" class="form-control" name="steel[width_source]"
                           value="<?php echo h($steel['width_source'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('eave_height'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="steel[eave_height]"
                           value="<?php echo h($steel['eave_height'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('eave_height_source'); ?></label>
                    <input type="text" class="form-control" name="steel[eave_height_source]"
                           value="<?php echo h($steel['eave_height_source'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('parapet_top_elevation'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="steel[parapet_top_elevation]"
                           value="<?php echo h($steel['parapet_top_elevation'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('parapet_wall_liner'); ?></label>
                    <input type="text" class="form-control" name="steel[parapet_wall_liner]"
                           value="<?php echo h($steel['parapet_wall_liner'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo FL('mezzanine_floor_area'); ?></label>
                    <input type="text" class="form-control" name="steel[mezzanine_floor_area]"
                           value="<?php echo h($steel['mezzanine_floor_area'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('floor_elevation'); ?></label>
                    <input type="number" step="0.01" class="form-control" name="steel[floor_elevation]"
                           value="<?php echo h($steel['floor_elevation'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('floor_type'); ?></label>
                    <input type="text" class="form-control" name="steel[floor_type]"
                           value="<?php echo h($steel['floor_type'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- 主结构材料 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">F.2</span> <?php echo sectionTitle('主结构材料', 'Primary Steel'); ?></div>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('steel_grade'); ?></label>
                    <select class="form-select" name="steel[steel_grade]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('steel_grade') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($steel['steel_grade'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo FL('primary_manufacturer'); ?></label>
                    <select class="form-select" name="steel[steel_manufacturer]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('steel_manufacturer') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($steel['steel_manufacturer'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('fabrication_plant'); ?></label>
                    <select class="form-select" name="steel[processing_plant]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('processing_plant') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($steel['processing_plant'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('primer_type'); ?></label>
                    <select class="form-select" name="steel[primer_type]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('primer_type') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($steel['primer_type'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('primer_thickness'); ?></label>
                    <select class="form-select" name="steel[primer_thickness]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('primer_thickness') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($steel['primer_thickness'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-12">
                    <label class="form-label"><?php echo FL('primary_steel_note'); ?></label>
                    <textarea class="form-control" name="steel[primary_steel_note]" rows="2"><?php echo h($steel['primary_steel_note'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- 中间漆+面漆 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">F.3</span> <?php echo sectionTitle('中间漆+面漆', 'Intermediate & Top Coat'); ?></div>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('intermediate_coat'); ?></label>
                    <select class="form-select" name="steel[intermediate_coat]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('intermediate_coat') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($steel['intermediate_coat'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('intermediate_thickness'); ?></label>
                    <input type="number" class="form-control" name="steel[intermediate_thickness]"
                           value="<?php echo h($steel['intermediate_thickness'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('top_coat_paint'); ?></label>
                    <select class="form-select" name="steel[top_coat_paint]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('top_coat_paint') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($steel['top_coat_paint'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('top_coat_thickness'); ?></label>
                    <input type="number" class="form-control" name="steel[top_coat_thickness]"
                           value="<?php echo h($steel['top_coat_thickness'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('painting_method'); ?></label>
                    <select class="form-select" name="steel[paint_method]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('painting_method') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($steel['paint_method'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-12">
                    <label class="form-label"><?php echo FL('coating_scope'); ?></label>
                    <textarea class="form-control" name="steel[coating_scope]" rows="2"><?php echo h($steel['coating_scope'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- 外露构件油漆 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">F.4</span> <?php echo sectionTitle('外露构件油漆', 'Exposed Paint'); ?></div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label"><?php echo FL('exposed_paint'); ?></label>
                    <select class="form-select" name="steel[exposed_paint]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('exposed_paint') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($steel['exposed_paint'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label"><?php echo FL('exposed_paint_scope'); ?></label>
                    <input type="text" class="form-control" name="steel[exposed_paint_scope]"
                           value="<?php echo h($steel['exposed_paint_scope'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- 防火涂料 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">F.5</span> <?php echo sectionTitle('普通钢结构防火涂料', 'Fire Coating'); ?></div>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('fire_coating_na'); ?></label>
                    <select class="form-select" name="steel[fire_coating_na]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <option value="1" <?php echo ($steel['fire_coating_na'] ?? '') == '1' ? 'selected' : ''; ?>>N/A <?php echo $lang === 'en' ? '' : '不适用'; ?></option>
                        <option value="0" <?php echo (isset($steel['fire_coating_na']) && $steel['fire_coating_na'] === '0') ? 'selected' : ''; ?>><?php echo $lang === 'en' ? 'Required' : '需要防火涂料'; ?></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo FL('fire_coating'); ?></label>
                    <select class="form-select" name="steel[fire_coating]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('fire_coating') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($steel['fire_coating'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label"><?php echo FL('fire_coating_scope'); ?></label>
                    <input type="text" class="form-control" name="steel[fire_coating_scope]"
                           value="<?php echo h($steel['fire_coating_scope'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- 次结构材料 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">F.6</span> <?php echo sectionTitle('次结构材料', 'Secondary Steel'); ?></div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label"><?php echo FL('secondary_manufacturer'); ?></label>
                    <select class="form-select" name="steel[secondary_manufacturer]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('secondary_manufacturer') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($steel['secondary_manufacturer'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('roof_purlin_galvanized'); ?></label>
                    <select class="form-select" name="steel[roof_purlin_galvanized]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <option value="0" <?php echo (isset($steel['roof_purlin_galvanized']) && $steel['roof_purlin_galvanized'] === '0') ? 'selected' : ''; ?>><?php echo $noLabel; ?></option>
                        <option value="1" <?php echo ($steel['roof_purlin_galvanized'] ?? '') == '1' ? 'selected' : ''; ?>><?php echo $yesLabel; ?></option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('roof_purlin_paint'); ?></label>
                    <select class="form-select" name="steel[roof_purlin_paint]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('purlin_paint') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($steel['roof_purlin_paint'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('wall_purlin_galvanized'); ?></label>
                    <select class="form-select" name="steel[wall_purlin_galvanized]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <option value="0" <?php echo (isset($steel['wall_purlin_galvanized']) && $steel['wall_purlin_galvanized'] === '0') ? 'selected' : ''; ?>><?php echo $noLabel; ?></option>
                        <option value="1" <?php echo ($steel['wall_purlin_galvanized'] ?? '') == '1' ? 'selected' : ''; ?>><?php echo $yesLabel; ?></option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo FL('wall_purlin_paint'); ?></label>
                    <select class="form-select" name="steel[wall_purlin_paint]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('purlin_paint') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($steel['wall_purlin_paint'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- 花纹钢板 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">F.7</span> <?php echo sectionTitle('花纹钢板', 'Checkered Plate'); ?></div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label"><?php echo FL('checkered_plate_paint'); ?></label>
                    <select class="form-select" name="steel[checkered_plate_paint]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('checkered_plate') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($steel['checkered_plate_paint'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label"><?php echo FL('checkered_plate_scope'); ?></label>
                    <input type="text" class="form-control" name="steel[checkered_plate_scope]"
                           value="<?php echo h($steel['checkered_plate_scope'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo FL('checkered_plate_remarks'); ?></label>
                    <input type="text" class="form-control" name="steel[checkered_plate_remarks]"
                           value="<?php echo h($steel['checkered_plate_remarks'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- 其他要求 -->
        <div class="row g-3 mt-2">
            <div class="col-md-12">
                <label class="form-label"><?php echo FL('other_requirements'); ?></label>
                <textarea class="form-control" name="steel[other_requirements]" rows="2"><?php echo h($steel['other_requirements'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>
</div>
