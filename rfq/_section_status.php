<?php
/**
 * RFQ Form Section: Status
 * 状态
 */
?>
<!-- ========== 状态 ========== -->
<div class="form-section">
    <div class="form-section-header">
        <i class="bi bi-flag"></i> <?php echo sectionTitle('状态', 'Status'); ?>
    </div>
    <div class="form-section-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label"><?php echo FL('rfq_status'); ?></label>
                <select class="form-select" name="main[status]">
                    <?php foreach (getRefOptions('rfq_status') as $opt): ?>
                    <option value="<?php echo h($opt['value']); ?>"
                        <?php echo ($main['status'] ?? 'draft') == $opt['value'] ? 'selected' : ''; ?>>
                        <?php echo h($opt['label']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>
