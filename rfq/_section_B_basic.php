<?php
/**
 * RFQ Form Section: Basic Information
 * 基本信息
 */
?>
<!-- ========== 基本信息 ========== -->
<div class="form-section">
    <div class="form-section-header">
        <i class="bi bi-info-circle"></i> <span class="section-number">B.</span> <?php echo sectionTitle('基本信息', 'Basic Information'); ?>
    </div>
    <div class="form-section-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">
                    <span class="form-label-cn">报价项目申请号</span>
                    <span class="form-label-en">RFQ No.</span>
                </label>
                <input type="text" class="form-control" name="main[rfq_no]"
                       value="<?php echo h($main['rfq_no'] ?? ''); ?>"
                       placeholder="Auto-generated" readonly>
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <span class="form-label-cn">项目编号</span>
                    <span class="form-label-en">Job Number</span>
                </label>
                <input type="text" class="form-control" name="main[job_number]"
                       value="<?php echo h($main['job_number'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <span class="form-label-cn">要求报价提交日期</span>
                    <span class="form-label-en">Due by</span>
                </label>
                <input type="date" class="form-control" name="main[due_date]"
                       value="<?php echo h($main['due_date'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <span class="form-label-cn">Liberty 联系电话</span>
                    <span class="form-label-en">Liberty Contact</span>
                </label>
                <input type="text" class="form-control" name="main[liberty_contact]"
                       value="<?php echo h($main['liberty_contact'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">
                    <span class="form-label-cn">项目名称</span>
                    <span class="form-label-en">Project Name</span>
                </label>
                <input type="text" class="form-control" name="main[project_name]"
                       value="<?php echo h($main['project_name'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">
                    <span class="form-label-cn">项目所在位置</span>
                    <span class="form-label-en">Project Location</span>
                </label>
                <input type="text" class="form-control" name="main[project_location]"
                       value="<?php echo h($main['project_location'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">
                    <span class="form-label-cn">单体数量</span>
                    <span class="form-label-en">Building Qty</span>
                </label>
                <input type="number" class="form-control" name="main[building_qty]"
                       value="<?php echo h($main['building_qty'] ?? 1); ?>" min="1">
            </div>
            <div class="col-md-2">
                <label class="form-label">
                    <span class="form-label-cn">建筑屋面面积 1</span>
                    <span class="form-label-en">Floor Area 1</span>
                </label>
                <input type="text" class="form-control" name="main[floor_area_1]"
                       value="<?php echo h($main['floor_area_1'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">
                    <span class="form-label-cn">建筑屋面面积 2</span>
                    <span class="form-label-en">Floor Area 2</span>
                </label>
                <input type="text" class="form-control" name="main[floor_area_2]"
                       value="<?php echo h($main['floor_area_2'] ?? ''); ?>">
            </div>
        </div>
    </div>
</div>
