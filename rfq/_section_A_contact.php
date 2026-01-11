<?php
/**
 * RFQ Form Section: Contact Information
 * 联系人信息
 */
?>
<!-- ========== 联系人信息 ========== -->
<div class="form-section">
    <div class="form-section-header">
        <i class="bi bi-person-lines-fill"></i> <span class="section-number">A.</span> <?php echo sectionTitle('联系人信息', 'Contact Information'); ?>
    </div>
    <div class="form-section-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">
                    <span class="form-label-cn">发给</span>
                    <span class="form-label-en">To</span>
                </label>
                <input type="text" class="form-control" name="main[contact_to]"
                       value="<?php echo h($main['contact_to'] ?? ''); ?>"
                       placeholder="e.g. USAS Shanghai">
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <span class="form-label-cn">电子邮箱</span>
                    <span class="form-label-en">Email</span>
                </label>
                <input type="email" class="form-control" name="main[contact_email]"
                       value="<?php echo h($main['contact_email'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <span class="form-label-cn">收件人</span>
                    <span class="form-label-en">Attn</span>
                </label>
                <input type="text" class="form-control" name="main[attn]"
                       value="<?php echo h($main['attn'] ?? ''); ?>"
                       placeholder="e.g. Gordon Wang">
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <span class="form-label-cn">客户经理</span>
                    <span class="form-label-en">Account Manager</span>
                </label>
                <input type="text" class="form-control" name="main[account_manager]"
                       value="<?php echo h($main['account_manager'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <span class="form-label-cn">客户经理职称</span>
                    <span class="form-label-en">Title</span>
                </label>
                <input type="text" class="form-control" name="main[account_manager_title]"
                       value="<?php echo h($main['account_manager_title'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <span class="form-label-cn">项目类型</span>
                    <span class="form-label-en">Project Type</span>
                </label>
                <input type="text" class="form-control" name="main[project_type]"
                       value="<?php echo h($main['project_type'] ?? ''); ?>"
                       placeholder="e.g. INAVA STEEL jobs">
            </div>
        </div>
    </div>
</div>
