<?php
/**
 * RFQ Form Section: Building Structure
 * 建筑结构概述
 */
?>
<!-- ========== 建筑结构概述 ========== -->
<div class="form-section">
    <div class="form-section-header">
        <i class="bi bi-building"></i> <?php echo sectionTitle('结构概述', 'Building Structure'); ?>
    </div>
    <div class="form-section-body">
        <div class="switch-group">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[pre_eng_building]" value="1" id="sw_pre_eng"
                    <?php echo ($main['pre_eng_building'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_pre_eng"><?php echo switchLabel('预制工程建筑', 'Pre Eng Building'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[bridge_crane]" value="1" id="sw_crane"
                    <?php echo ($main['bridge_crane'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_crane"><?php echo switchLabel('天车', 'Bridge Crane'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[mezzanine_steels]" value="1" id="sw_mezzanine"
                    <?php echo ($main['mezzanine_steels'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_mezzanine"><?php echo switchLabel('夹层', 'Mezzanine Steels'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[factory_mutual]" value="1" id="sw_fm"
                    <?php echo ($main['factory_mutual'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_fm">Factory Mutual</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[loading_canopy]" value="1" id="sw_canopy"
                    <?php echo ($main['loading_canopy'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_canopy"><?php echo switchLabel('大雨蓬', 'Loading Canopy'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[future_expansion]" value="1" id="sw_expansion"
                    <?php echo ($main['future_expansion'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_expansion"><?php echo switchLabel('扩建', 'Future Expansion'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[parapet]" value="1" id="sw_parapet"
                    <?php echo ($main['parapet'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_parapet"><?php echo switchLabel('女儿墙', 'Parapet'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[concrete_wall_curb]" value="1" id="sw_curb"
                    <?php echo ($main['concrete_wall_curb'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_curb"><?php echo switchLabel('墙裙', 'Concrete Wall Curb'); ?></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="main[leed]" value="1" id="sw_leed"
                    <?php echo ($main['leed'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_leed">LEED</label>
            </div>
        </div>
    </div>
</div>
