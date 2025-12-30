<?php
/**
 * RFQ Form Section: Order Entry
 * 报价资料
 */

// 文件类型定义
$fileCategories = [
    'mbs_drawing' => ['label_cn' => 'MBS结构图纸', 'label_en' => 'MBS Drawing'],
    'architect_drawing' => ['label_cn' => '建筑蓝图', 'label_en' => 'Architect Drawing'],
    'foundation_design' => ['label_cn' => '结构蓝图', 'label_en' => 'Foundation Design'],
    'autocad_drawing' => ['label_cn' => 'AutoCAD 建筑图纸', 'label_en' => 'AutoCAD Drawing'],
    'fm_report' => ['label_cn' => 'FM报告', 'label_en' => 'FM Report'],
    'other_docs' => ['label_cn' => '其他文件', 'label_en' => 'Other Documents']
];

// 文件大小限制 (20MB)
$maxFileSizeMB = 20;
$maxFileSize = $maxFileSizeMB * 1024 * 1024;

// 按分类整理已上传文件
$filesByCategory = [];
foreach ($files as $file) {
    $cat = $file['file_category'] ?? 'other_docs';
    if (!isset($filesByCategory[$cat])) {
        $filesByCategory[$cat] = [];
    }
    $filesByCategory[$cat][] = $file;
}
?>
<!-- ========== 报价资料 Order Entry ========== -->
<div class="form-section">
    <div class="form-section-header">
        <i class="bi bi-folder-check"></i> <?php echo sectionTitle('报价资料', 'Order Entry'); ?>
    </div>
    <div class="form-section-body">
        <div class="switch-group">
            <?php foreach ($fileCategories as $catKey => $catInfo): ?>
            <div class="form-check form-switch">
                <input class="form-check-input order-entry-switch" type="checkbox" role="switch"
                       name="order_entry[<?php echo $catKey; ?>]" value="1"
                       id="sw_<?php echo $catKey; ?>"
                       data-category="<?php echo $catKey; ?>"
                       <?php echo !empty($orderEntry[$catKey]) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="sw_<?php echo $catKey; ?>">
                    <?php echo switchLabel($catInfo['label_cn'], $catInfo['label_en']); ?>
                </label>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 其他文件描述 -->
        <div class="row g-2 mt-3" id="other_docs_desc_row" style="<?php echo !empty($orderEntry['other_docs']) ? '' : 'display:none;'; ?>">
            <div class="col-md-12">
                <label class="form-label"><?php echo fieldLabel('其他文件描述', 'Other Documents Description'); ?></label>
                <input type="text" class="form-control" name="order_entry[other_docs_desc]"
                       value="<?php echo h($orderEntry['other_docs_desc'] ?? ''); ?>">
            </div>
        </div>

        <!-- 文件上传区域 -->
        <div id="file-upload-container" class="mt-3">
            <?php foreach ($fileCategories as $catKey => $catInfo):
                $isChecked = !empty($orderEntry[$catKey]);
                $catFiles = $filesByCategory[$catKey] ?? [];
                // 如果有已上传的文件，也显示上传区域
                $shouldShow = $isChecked || !empty($catFiles);
            ?>
            <div class="file-upload-row" id="upload_row_<?php echo $catKey; ?>"
                 style="<?php echo $shouldShow ? '' : 'display:none;'; ?>"
                 data-has-files="<?php echo !empty($catFiles) ? '1' : '0'; ?>">
                <div class="card mb-2">
                    <div class="card-body py-2 px-3">
                        <div class="row align-items-center g-2">
                            <div class="col-md-3">
                                <span class="fw-medium text-primary">
                                    <i class="bi bi-file-earmark"></i>
                                    <?php echo $lang === 'en' ? $catInfo['label_en'] : $catInfo['label_cn']; ?>
                                </span>
                            </div>
                            <div class="col-md-5">
                                <input type="file" class="form-control form-control-sm file-input"
                                       name="files[<?php echo $catKey; ?>][]"
                                       id="file_<?php echo $catKey; ?>"
                                       data-category="<?php echo $catKey; ?>"
                                       data-max-size="<?php echo $maxFileSize; ?>"
                                       multiple
                                       accept=".pdf,.dwg,.dxf,.doc,.docx,.xls,.xlsx,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif">
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">
                                    <?php echo $lang === 'en'
                                        ? "Max {$maxFileSizeMB}MB/file. PDF, DWG, DOC, XLS, ZIP, Images"
                                        : "单文件最大{$maxFileSizeMB}MB，支持PDF,DWG,DOC,XLS,ZIP,图片"; ?>
                                </small>
                            </div>
                        </div>
                        <!-- 文件大小错误提示 -->
                        <div class="file-size-error text-danger small mt-1" id="error_<?php echo $catKey; ?>" style="display:none;">
                            <i class="bi bi-exclamation-circle"></i>
                            <span class="error-message"></span>
                        </div>

                        <!-- 已上传文件列表 -->
                        <?php if (!empty($catFiles)): ?>
                        <div class="uploaded-files mt-2">
                            <small class="text-muted d-block mb-1">
                                <?php echo $lang === 'en' ? 'Uploaded files:' : '已上传文件:'; ?>
                            </small>
                            <?php foreach ($catFiles as $file): ?>
                            <div class="uploaded-file-item d-flex align-items-center justify-content-between py-1 px-2 bg-light rounded mb-1">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-file-earmark-check text-success me-2"></i>
                                    <span class="small"><?php echo h($file['file_name']); ?></span>
                                    <span class="badge bg-secondary ms-2"><?php echo formatFileSize($file['file_size']); ?></span>
                                </div>
                                <div>
                                    <?php if (!empty($file['file_path'])): ?>
                                    <a href="<?php echo h($file['file_path']); ?>" class="btn btn-sm btn-outline-primary py-0 px-1"
                                       target="_blank" title="<?php echo $lang === 'en' ? 'Download' : '下载'; ?>">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 btn-delete-file"
                                            data-file-id="<?php echo $file['id']; ?>"
                                            title="<?php echo $lang === 'en' ? 'Delete' : '删除'; ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const MAX_FILE_SIZE = <?php echo $maxFileSize; ?>;
    const MAX_FILE_SIZE_MB = <?php echo $maxFileSizeMB; ?>;
    const LANG = '<?php echo $lang; ?>';

    // 格式化文件大小
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // 处理开关切换时显示/隐藏文件上传区域
    document.querySelectorAll('.order-entry-switch').forEach(function(sw) {
        sw.addEventListener('change', function() {
            const category = this.dataset.category;
            const uploadRow = document.getElementById('upload_row_' + category);

            if (uploadRow) {
                const hasFiles = uploadRow.dataset.hasFiles === '1' ||
                                 uploadRow.querySelectorAll('.uploaded-file-item').length > 0;
                if (this.checked) {
                    uploadRow.style.display = '';
                } else if (!hasFiles) {
                    uploadRow.style.display = 'none';
                }
            }

            if (category === 'other_docs') {
                const descRow = document.getElementById('other_docs_desc_row');
                if (descRow) {
                    descRow.style.display = this.checked ? '' : 'none';
                }
            }
        });
    });

    // 文件选择时验证大小
    document.querySelectorAll('.file-input').forEach(function(input) {
        input.addEventListener('change', function() {
            const category = this.dataset.category;
            const errorDiv = document.getElementById('error_' + category);
            const errorMsg = errorDiv.querySelector('.error-message');
            const files = this.files;
            let hasError = false;
            let errorFiles = [];

            for (let i = 0; i < files.length; i++) {
                if (files[i].size > MAX_FILE_SIZE) {
                    hasError = true;
                    errorFiles.push(files[i].name + ' (' + formatFileSize(files[i].size) + ')');
                }
            }

            if (hasError) {
                const msg = LANG === 'en'
                    ? 'File too large (max ' + MAX_FILE_SIZE_MB + 'MB): '
                    : '文件超出大小限制（最大' + MAX_FILE_SIZE_MB + 'MB）: ';
                errorMsg.textContent = msg + errorFiles.join(', ');
                errorDiv.style.display = 'block';
                this.classList.add('is-invalid');
            } else {
                errorDiv.style.display = 'none';
                this.classList.remove('is-invalid');
            }
        });
    });

    // 表单提交前验证
    const form = document.getElementById('rfq-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasError = false;
            let errorMessages = [];

            document.querySelectorAll('.file-input').forEach(function(input) {
                const files = input.files;
                for (let i = 0; i < files.length; i++) {
                    if (files[i].size > MAX_FILE_SIZE) {
                        hasError = true;
                        errorMessages.push(files[i].name);
                    }
                }
            });

            if (hasError) {
                e.preventDefault();
                const msg = LANG === 'en'
                    ? 'The following files exceed the size limit (' + MAX_FILE_SIZE_MB + 'MB):\n\n' + errorMessages.join('\n') + '\n\nPlease remove or compress these files before submitting.'
                    : '以下文件超出大小限制（' + MAX_FILE_SIZE_MB + 'MB）：\n\n' + errorMessages.join('\n') + '\n\n请移除或压缩这些文件后再提交。';
                alert(msg);
                return false;
            }
        });
    }

    // 文件删除处理
    document.querySelectorAll('.btn-delete-file').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const fileId = this.dataset.fileId;
            const fileItem = this.closest('.uploaded-file-item');
            const confirmMsg = LANG === 'en' ? 'Are you sure you want to delete this file?' : '确定要删除这个文件吗？';

            if (confirm(confirmMsg)) {
                fetch('/aiforms/api/file.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        file_id: fileId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fileItem.remove();
                    } else {
                        alert(data.message || (LANG === 'en' ? 'Delete failed' : '删除失败'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(LANG === 'en' ? 'Delete failed' : '删除失败');
                });
            }
        });
    });
});
</script>
