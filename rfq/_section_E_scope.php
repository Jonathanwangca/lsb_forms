<?php
/**
 * RFQ Form Section: Scope of Work
 * 报价范围
 */
?>
<!-- ========== 报价范围 ========== -->
<div class="form-section">
    <div class="form-section-header">
        <i class="bi bi-list-check"></i> <span class="section-number">E.</span> <?php echo sectionTitle('报价范围', 'Scope of Work'); ?>
    </div>
    <div class="form-section-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label"><?php echo fieldLabel('包含', 'Include'); ?></label>
                <select class="form-select" name="main[scope_type]">
                    <option value=""><?php echo $selectPlaceholder; ?></option>
                    <?php foreach (getRefOptions('scope_of_work') as $opt): ?>
                    <option value="<?php echo h($opt['value']); ?>"
                        <?php echo ($main['scope_type'] ?? '') == $opt['value'] ? 'selected' : ''; ?>>
                        <?php echo h($opt['label']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label"><?php echo fieldLabel('安装', 'Erection'); ?></label>
                <select class="form-select" name="main[erection]">
                    <option value="">-- N/A --</option>
                    <option value="0" <?php echo (isset($main['erection']) && $main['erection'] === '0') ? 'selected' : ''; ?>><?php echo $noLabel; ?></option>
                    <option value="1" <?php echo ($main['erection'] ?? '') == '1' ? 'selected' : ''; ?>><?php echo $yesLabel; ?></option>
                </select>
            </div>
        </div>
        <div class="row g-3 mb-2">
            <div class="col-12">
                <label class="form-label fw-bold"><?php echo fieldLabel('辅材', 'Auxiliary'); ?></label>
            </div>
        </div>
        <div class="switch-group">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[steel_deck]" value="1" id="sw_deck"
                    <?php echo ($main['steel_deck'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_deck"><?php echo switchLabel('楼面板', 'Steel Deck'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[partition_wall_frame]" value="1" id="sw_partition"
                    <?php echo ($main['partition_wall_frame'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_partition"><?php echo switchLabel('内隔墙', 'Partition Wall'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[door_window]" value="1" id="sw_door"
                    <?php echo ($main['door_window'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_door"><?php echo switchLabel('门窗', 'Door & Window'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[top_coat]" value="1" id="sw_topcoat"
                    <?php echo ($main['top_coat'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_topcoat"><?php echo switchLabel('面漆', 'Top Coat'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[louver]" value="1" id="sw_louver"
                    <?php echo ($main['louver'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_louver"><?php echo switchLabel('百叶窗', 'Louver'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[cable_tray_support]" value="1" id="sw_cable"
                    <?php echo ($main['cable_tray_support'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_cable"><?php echo switchLabel('管线吊架', 'Cable Tray'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[railing]" value="1" id="sw_railing"
                    <?php echo ($main['railing'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_railing"><?php echo switchLabel('栏杆扶手', 'Railing'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[glazing_curtain_wall]" value="1" id="sw_curtain"
                    <?php echo ($main['glazing_curtain_wall'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_curtain"><?php echo switchLabel('玻璃幕墙', 'Curtain Wall'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[aluminum_cladding]" value="1" id="sw_aluminum"
                    <?php echo ($main['aluminum_cladding'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_aluminum"><?php echo switchLabel('铝板', 'Aluminum Cladding'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[laboratory_inspect]" value="1" id="sw_lab"
                    <?php echo ($main['laboratory_inspect'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_lab"><?php echo switchLabel('试验', 'Laboratory'); ?></label>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-12">
                <label class="form-label"><?php echo fieldLabel('安装备注', 'Erection Remarks'); ?></label>
                <textarea class="form-control" name="main[erection_remarks]" rows="2"><?php echo h($main['erection_remarks'] ?? ''); ?></textarea>
            </div>
            <div class="col-md-12">
                <label class="form-label"><?php echo fieldLabel('检测备注', 'Laboratory Remarks'); ?></label>
                <textarea class="form-control" name="main[laboratory_remarks]" rows="2"><?php echo h($main['laboratory_remarks'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>
</div>
