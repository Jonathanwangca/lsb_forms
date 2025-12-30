<?php
/**
 * RFQ JSON 导入页面
 * LSB RFQ System V3.1
 */
$pageTitle = 'Import RFQ - LSB RFQ System';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-upload"></i> <?php echo $lang === 'en' ? 'Import RFQ from JSON' : '导入 RFQ JSON 数据'; ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <?php echo $lang === 'en'
                        ? 'Upload a JSON file exported from the RFQ system to import data.'
                        : '上传从 RFQ 系统导出的 JSON 文件以导入数据。'; ?>
                </div>

                <form id="importForm" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label"><?php echo $lang === 'en' ? 'Select JSON File' : '选择 JSON 文件'; ?></label>
                        <input type="file" class="form-control" name="json_file" id="json_file" accept=".json" required>
                        <small class="text-muted"><?php echo $lang === 'en' ? 'Only .json files are accepted' : '仅支持 .json 文件'; ?></small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><?php echo $lang === 'en' ? 'Import Options' : '导入选项'; ?></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="import_mode" id="mode_new" value="new" checked>
                            <label class="form-check-label" for="mode_new">
                                <?php echo $lang === 'en' ? 'Create as new RFQ (generate new RFQ No.)' : '创建为新 RFQ（生成新的 RFQ 编号）'; ?>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="import_mode" id="mode_update" value="update">
                            <label class="form-check-label" for="mode_update">
                                <?php echo $lang === 'en' ? 'Update existing RFQ (match by RFQ No.)' : '更新现有 RFQ（按 RFQ 编号匹配）'; ?>
                            </label>
                        </div>
                    </div>

                    <!-- JSON 预览区域 -->
                    <div class="mb-4" id="previewSection" style="display: none;">
                        <label class="form-label"><?php echo $lang === 'en' ? 'File Preview' : '文件预览'; ?></label>
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>RFQ No.:</strong> <span id="preview_rfq_no">-</span></p>
                                        <p class="mb-1"><strong><?php echo $lang === 'en' ? 'Job Number' : '项目编号'; ?>:</strong> <span id="preview_job_number">-</span></p>
                                        <p class="mb-1"><strong><?php echo $lang === 'en' ? 'Project Name' : '项目名称'; ?>:</strong> <span id="preview_project_name">-</span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong><?php echo $lang === 'en' ? 'Location' : '项目位置'; ?>:</strong> <span id="preview_location">-</span></p>
                                        <p class="mb-1"><strong><?php echo $lang === 'en' ? 'Buildings' : '建筑数量'; ?>:</strong> <span id="preview_buildings">-</span></p>
                                        <p class="mb-1"><strong><?php echo $lang === 'en' ? 'Status' : '状态'; ?>:</strong> <span id="preview_status">-</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/aiforms/rfq/list.php<?php echo $lang !== 'both' ? '?lang='.$lang : ''; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> <?php echo $lang === 'en' ? 'Back to List' : '返回列表'; ?>
                        </a>
                        <button type="submit" class="btn btn-primary" id="importBtn" disabled>
                            <i class="bi bi-upload"></i> <?php echo $lang === 'en' ? 'Import' : '导入'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 导入结果 -->
        <div class="card mt-4" id="resultSection" style="display: none;">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-check-circle"></i> <?php echo $lang === 'en' ? 'Import Result' : '导入结果'; ?>
                </h5>
            </div>
            <div class="card-body" id="resultContent">
            </div>
        </div>
    </div>
</div>

<script>
let jsonData = null;

document.getElementById('json_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) {
        document.getElementById('previewSection').style.display = 'none';
        document.getElementById('importBtn').disabled = true;
        jsonData = null;
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            jsonData = JSON.parse(e.target.result);

            // 显示预览
            const main = jsonData.main || {};
            document.getElementById('preview_rfq_no').textContent = main.rfq_no || '-';
            document.getElementById('preview_job_number').textContent = main.job_number || '-';
            document.getElementById('preview_project_name').textContent = main.project_name || '-';
            document.getElementById('preview_location').textContent = main.project_location || '-';
            document.getElementById('preview_buildings').textContent = main.building_qty || '-';
            document.getElementById('preview_status').textContent = main.status || '-';

            document.getElementById('previewSection').style.display = 'block';
            document.getElementById('importBtn').disabled = false;
        } catch (error) {
            alert('<?php echo $lang === "en" ? "Invalid JSON file" : "无效的 JSON 文件"; ?>');
            document.getElementById('previewSection').style.display = 'none';
            document.getElementById('importBtn').disabled = true;
            jsonData = null;
        }
    };
    reader.readAsText(file);
});

document.getElementById('importForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!jsonData) {
        alert('<?php echo $lang === "en" ? "Please select a valid JSON file" : "请选择有效的 JSON 文件"; ?>');
        return;
    }

    const importMode = document.querySelector('input[name="import_mode"]:checked').value;
    const importBtn = document.getElementById('importBtn');

    importBtn.disabled = true;
    importBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> <?php echo $lang === "en" ? "Importing..." : "导入中..."; ?>';

    try {
        const formData = new FormData();
        formData.append('action', 'import');
        formData.append('mode', importMode);
        formData.append('data', JSON.stringify(jsonData));

        const response = await fetch('/aiforms/api/rfq.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        const resultSection = document.getElementById('resultSection');
        const resultContent = document.getElementById('resultContent');

        if (result.success) {
            resultContent.innerHTML = `
                <div class="alert alert-success mb-3">
                    <i class="bi bi-check-circle"></i> ${result.message || '<?php echo $lang === "en" ? "Import successful" : "导入成功"; ?>'}
                </div>
                <p><strong>RFQ No.:</strong> ${result.data.rfq_no}</p>
                <p><strong>ID:</strong> ${result.data.id}</p>
                <div class="mt-3">
                    <a href="/aiforms/rfq/form_rfq.php?id=${result.data.id}<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> <?php echo $lang === 'en' ? 'Edit RFQ' : '编辑 RFQ'; ?>
                    </a>
                    <a href="/aiforms/rfq/list.php<?php echo $lang !== 'both' ? '?lang='.$lang : ''; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-list"></i> <?php echo $lang === 'en' ? 'View List' : '查看列表'; ?>
                    </a>
                </div>
            `;
        } else {
            resultContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle"></i> ${result.message || '<?php echo $lang === "en" ? "Import failed" : "导入失败"; ?>'}
                </div>
            `;
        }

        resultSection.style.display = 'block';
    } catch (error) {
        console.error('Error:', error);
        alert('<?php echo $lang === "en" ? "Import failed" : "导入失败"; ?>');
    } finally {
        importBtn.disabled = false;
        importBtn.innerHTML = '<i class="bi bi-upload"></i> <?php echo $lang === "en" ? "Import" : "导入"; ?>';
    }
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
