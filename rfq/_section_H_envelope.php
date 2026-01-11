<?php
/**
 * RFQ Form Section: Envelope System Configuration
 * 围护系统配置（特殊配置、改造项目等）
 *
 * 注意：板材规格和做法已拆分到独立section
 * - 做法说明 → _section_G_method.php
 * - 屋面材质 → _section_I_roof_material.php
 * - 墙面材质 → _section_J_wall_material.php
 */
?>
<!-- ========== 围护系统配置 ========== -->
<div class="form-section">
    <div class="form-section-header">
        <i class="bi bi-house"></i> <span class="section-number">H.</span> <?php echo sectionTitle('围护系统配置', 'Envelope Configuration'); ?>
    </div>
    <div class="form-section-body">
        <!-- 屋墙面材料总体配置 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">H.1</span> <?php echo sectionTitle('屋墙面材料（特殊材料请备注）', 'Material Configuration'); ?></div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Material Type' : '材料类型'; ?></label>
                    <select class="form-select" name="envelope[wall_material]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <?php foreach (getRefOptions('wall_material') as $opt): ?>
                        <option value="<?php echo h($opt['value']); ?>"
                            <?php echo ($envelope['wall_material'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                            <?php echo h($opt['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Remarks' : '备注'; ?></label>
                    <input type="text" class="form-control" name="envelope[material_remarks]"
                           value="<?php echo h($envelope['material_remarks'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- 改造项目 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">H.2</span> <?php echo sectionTitle('改造项目', 'Renovation'); ?></div>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Is Renovation' : '改造项目'; ?></label>
                    <select class="form-select" name="envelope[is_renovation]">
                        <option value=""><?php echo $selectPlaceholder; ?></option>
                        <option value="0" <?php echo (isset($envelope['is_renovation']) && $envelope['is_renovation'] === '0') ? 'selected' : ''; ?>><?php echo $noLabel; ?></option>
                        <option value="1" <?php echo ($envelope['is_renovation'] ?? '') == '1' ? 'selected' : ''; ?>><?php echo $yesLabel; ?></option>
                    </select>
                </div>
            </div>
            <div class="switch-group mt-3" id="renovation-options">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="envelope[structural_reinforcement]" value="1" id="sw_reinforce"
                        <?php echo ($envelope['structural_reinforcement'] ?? 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="sw_reinforce"><?php echo $lang === 'en' ? 'Structural Reinforcement' : '结构加固'; ?></label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="envelope[cladding_addition]" value="1" id="sw_cladding"
                        <?php echo ($envelope['cladding_addition'] ?? 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="sw_cladding"><?php echo $lang === 'en' ? 'Cladding Addition' : '围护板加建'; ?></label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="envelope[reuse]" value="1" id="sw_reuse"
                        <?php echo ($envelope['reuse'] ?? 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="sw_reuse"><?php echo $lang === 'en' ? 'Reuse' : '利旧'; ?></label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="envelope[mep_installation]" value="1" id="sw_mep"
                        <?php echo ($envelope['mep_installation'] ?? 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="sw_mep"><?php echo $lang === 'en' ? 'MEP Installation' : '机电安装'; ?></label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="envelope[renovation_other]" value="1" id="sw_reno_other"
                        <?php echo ($envelope['renovation_other'] ?? 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="sw_reno_other"><?php echo $lang === 'en' ? 'Other' : '其他'; ?></label>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-12">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Renovation Remarks' : '备注&补充'; ?></label>
                    <textarea class="form-control" name="envelope[renovation_remarks]" rows="2"><?php echo h($envelope['renovation_remarks'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- 防水规范 (隐藏GB字段) -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">H.3</span> <?php echo sectionTitle('防水规范', 'Waterproof Standard'); ?></div>
            <div class="row g-3 align-items-center">
                <!-- GB字段已隐藏，保留数据字段以防止数据丢失 -->
                <input type="hidden" name="envelope[waterproof_standard]" value="<?php echo ($envelope['waterproof_standard'] ?? 0) ? '1' : '0'; ?>">
                <div class="col-md-12">
                    <label class="form-label"><?php echo $lang === 'en' ? 'Remarks' : '备注'; ?></label>
                    <input type="text" class="form-control" name="envelope[waterproof_remarks]"
                           value="<?php echo h($envelope['waterproof_remarks'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- 屋面特殊配置 -->
        <div class="form-subsection">
            <div class="form-subsection-title"><span class="subsection-number">H.4</span> <?php echo sectionTitle('屋面特殊配置', 'Roof Special Configuration'); ?></div>
            <!-- ACLOK铝镁锰屋面板 -->
            <div class="row g-3 align-items-start">
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="envelope[aclok_roof]" value="1" id="sw_aclok"
                            <?php echo ($envelope['aclok_roof'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sw_aclok"><?php echo $lang === 'en' ? 'ACLOK Aluminum Roof' : 'Aclok铝镁锰屋面板'; ?></label>
                    </div>
                </div>
                <div class="col-md-9">
                    <small class="text-muted">
                        <?php echo $lang === 'en'
                            ? '1. With wind clips (required); 2. Thickness >= 0.90mm'
                            : '1、带抗风夹（公司规定，必须考虑）；2、厚度必须>=0.90mm'; ?>
                    </small>
                </div>
            </div>
            <!-- 夹芯板 -->
            <div class="row g-3 align-items-center mt-2">
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="envelope[sandwich_panel]" value="1" id="sw_sandwich"
                            <?php echo ($envelope['sandwich_panel'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sw_sandwich"><?php echo $lang === 'en' ? 'Sandwich Panel' : '夹芯板'; ?></label>
                    </div>
                </div>
                <div class="col-md-9">
                    <input type="text" class="form-control form-control-sm" name="envelope[sandwich_remarks]"
                           placeholder="<?php echo $lang === 'en' ? 'Sandwich Panel Remarks' : '夹芯板备注'; ?>"
                           value="<?php echo h($envelope['sandwich_remarks'] ?? ''); ?>">
                </div>
            </div>
            <!-- 屋面通风器 & 屋面开口 -->
            <div class="row g-3 align-items-center mt-2">
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="envelope[roof_ventilator]" value="1" id="sw_ventilator"
                            <?php echo ($envelope['roof_ventilator'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sw_ventilator"><?php echo $lang === 'en' ? 'Roof Ventilator' : '屋面通风器'; ?></label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="envelope[roof_opening]" value="1" id="sw_opening"
                            <?php echo ($envelope['roof_opening'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sw_opening"><?php echo $lang === 'en' ? 'Roof Opening' : '屋面开口'; ?></label>
                    </div>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control form-control-sm" name="envelope[ventilator_requirements]"
                           placeholder="<?php echo $lang === 'en' ? 'Size & Requirements' : '尺寸及其他要求'; ?>"
                           value="<?php echo h($envelope['ventilator_requirements'] ?? ''); ?>">
                </div>
            </div>
            <!-- 屋面气楼&天窗 -->
            <div class="row g-3 align-items-center mt-2">
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="envelope[roof_skylight]" value="1" id="sw_skylight"
                            <?php echo ($envelope['roof_skylight'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sw_skylight"><?php echo $lang === 'en' ? 'Roof Skylight/Monitor' : '屋面气楼&天窗'; ?></label>
                    </div>
                </div>
                <div class="col-md-9">
                    <input type="text" class="form-control form-control-sm" name="envelope[skylight_requirements]"
                           placeholder="<?php echo $lang === 'en' ? 'Skylight Requirements' : '尺寸及其他要求'; ?>"
                           value="<?php echo h($envelope['skylight_requirements'] ?? ''); ?>">
                </div>
            </div>
            <!-- 屋脊气楼/天窗 -->
            <div class="row g-3 align-items-center mt-2">
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="envelope[roof_ridge_lantern]" value="1" id="sw_lantern"
                            <?php echo ($envelope['roof_ridge_lantern'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sw_lantern"><?php echo $lang === 'en' ? 'Ridge Skylight/Lantern' : '屋脊气楼/天窗'; ?></label>
                    </div>
                </div>
                <div class="col-md-9">
                    <input type="text" class="form-control form-control-sm" name="envelope[roof_ridge_lantern_remarks]"
                           placeholder="<?php echo $lang === 'en' ? 'Ridge Lantern Remarks' : '屋脊气楼备注及尺寸要求'; ?>"
                           value="<?php echo h($envelope['roof_ridge_lantern_remarks'] ?? ''); ?>">
                </div>
            </div>
            <!-- LS585屋面光伏系统 -->
            <div class="row g-3 align-items-center mt-2">
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="envelope[pv_system]" value="1" id="sw_pv"
                            <?php echo ($envelope['pv_system'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sw_pv"><?php echo $lang === 'en' ? 'LS585 PV System' : 'LS585屋面光伏系统'; ?></label>
                    </div>
                </div>
                <div class="col-md-9">
                    <input type="text" class="form-control form-control-sm" name="envelope[pv_requirements]"
                           placeholder="<?php echo $lang === 'en' ? 'Other Requirements' : '其他要求'; ?>"
                           value="<?php echo h($envelope['pv_requirements'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
</div>
