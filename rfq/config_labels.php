<?php
/**
 * 标签配置管理页面
 * LSB RFQ System V3.1
 * 管理 labels.json 语言字典
 * 需要 URL 参数 config=true 才能访问
 */

// 检查访问权限
if (!isset($_GET['config']) || $_GET['config'] !== 'true') {
    header('HTTP/1.0 403 Forbidden');
    echo '<h1>403 Forbidden</h1><p>Access denied. This page requires config=true parameter.</p>';
    exit;
}

$pageTitle = 'Labels Configuration - LSB RFQ System';
require_once dirname(__DIR__) . '/includes/header.php';

// 标签文件路径
$labelsFile = dirname(__DIR__) . '/assets/lang/labels.json';
$labels = [];
if (file_exists($labelsFile)) {
    $labels = json_decode(file_get_contents($labelsFile), true) ?: [];
}

// 当前选中的分类
$currentCategory = isset($_GET['category']) ? $_GET['category'] : '';
$categoryItems = [];
if ($currentCategory && isset($labels[$currentCategory])) {
    $categoryItems = $labels[$currentCategory];
}

// 获取所有分类
$categories = array_keys($labels);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="bi bi-translate"></i> <?php echo $lang === 'en' ? 'Labels Configuration' : '标签配置管理'; ?></h4>
            <div>
                <a href="/aiforms/rfq/config.php?config=true<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>" class="btn btn-outline-primary me-2">
                    <i class="bi bi-gear"></i> <?php echo $lang === 'en' ? 'Parameters' : '参数配置'; ?>
                </a>
                <a href="/aiforms/rfq/list.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> <?php echo $lang === 'en' ? 'Back to List' : '返回列表'; ?>
                </a>
            </div>
        </div>

        <div class="row">
            <!-- 左侧分类列表 -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-folder"></i> <?php echo $lang === 'en' ? 'Categories' : '标签分类'; ?></span>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="LabelConfig.showAddCategoryModal()">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    <div class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($categories as $cat): ?>
                        <a href="?config=true&category=<?php echo urlencode($cat); ?><?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $currentCategory === $cat ? 'active' : ''; ?>">
                            <span><?php echo h($cat); ?></span>
                            <span class="badge <?php echo $currentCategory === $cat ? 'bg-light text-primary' : 'bg-primary'; ?>">
                                <?php echo count($labels[$cat]); ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 右侧标签列表 -->
            <div class="col-md-9">
                <?php if ($currentCategory): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-list-ul"></i>
                            <strong><?php echo h($currentCategory); ?></strong>
                            <span class="text-muted">(<?php echo count($categoryItems); ?> <?php echo $lang === 'en' ? 'items' : '项'; ?>)</span>
                        </span>
                        <button type="button" class="btn btn-sm btn-primary" onclick="LabelConfig.showAddModal()">
                            <i class="bi bi-plus"></i> <?php echo $lang === 'en' ? 'Add Label' : '添加标签'; ?>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="200"><?php echo $lang === 'en' ? 'Key' : '键名'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'Chinese' : '中文'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'English' : '英文'; ?></th>
                                        <th width="100"><?php echo $lang === 'en' ? 'Actions' : '操作'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categoryItems)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <?php echo $lang === 'en' ? 'No labels found' : '暂无标签'; ?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($categoryItems as $key => $item): ?>
                                    <tr data-key="<?php echo h($key); ?>">
                                        <td><code><?php echo h($key); ?></code></td>
                                        <td><?php echo h($item['cn'] ?? ''); ?></td>
                                        <td><?php echo h($item['en'] ?? ''); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary"
                                                        onclick="LabelConfig.showEditModal('<?php echo addslashes($key); ?>')"
                                                        title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger"
                                                        onclick="LabelConfig.deleteLabel('<?php echo addslashes($key); ?>')"
                                                        title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-arrow-left-circle" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="mt-3 text-muted">
                            <?php echo $lang === 'en' ? 'Please select a category from the left' : '请从左侧选择一个标签分类'; ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 添加/编辑标签模态框 -->
<div class="modal fade" id="labelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="labelModalTitle">
                    <i class="bi bi-plus-circle"></i> <?php echo $lang === 'en' ? 'Add Label' : '添加标签'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="labelForm">
                    <input type="hidden" name="original_key" id="label_original_key">
                    <input type="hidden" name="category" id="label_category" value="<?php echo h($currentCategory); ?>">

                    <div class="mb-3">
                        <label class="form-label"><?php echo $lang === 'en' ? 'Key' : '键名'; ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="key" id="label_key" required
                               placeholder="e.g. field_name">
                        <small class="text-muted"><?php echo $lang === 'en' ? 'Unique identifier, lowercase with underscores' : '唯一标识符，建议使用小写字母和下划线'; ?></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo $lang === 'en' ? 'Chinese Value' : '中文值'; ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="value_cn" id="label_value_cn" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo $lang === 'en' ? 'English Value' : '英文值'; ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="value_en" id="label_value_en" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php echo $lang === 'en' ? 'Cancel' : '取消'; ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="LabelConfig.saveLabel()">
                    <i class="bi bi-check"></i> <?php echo $lang === 'en' ? 'Save' : '保存'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 添加分类模态框 -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-folder-plus"></i> <?php echo $lang === 'en' ? 'Add Category' : '添加分类'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <div class="mb-3">
                        <label class="form-label"><?php echo $lang === 'en' ? 'Category Name' : '分类名称'; ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="category_name" id="new_category_name" required
                               placeholder="e.g. messages">
                        <small class="text-muted"><?php echo $lang === 'en' ? 'Lowercase, no spaces' : '小写字母，无空格'; ?></small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php echo $lang === 'en' ? 'Cancel' : '取消'; ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="LabelConfig.addCategory()">
                    <i class="bi bi-check"></i> <?php echo $lang === 'en' ? 'Create' : '创建'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// 当前标签数据
const labelsData = <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE); ?>;

const LabelConfig = {
    labelModal: null,
    categoryModal: null,
    currentCategory: '<?php echo addslashes($currentCategory); ?>',

    init() {
        this.labelModal = new bootstrap.Modal(document.getElementById('labelModal'));
        this.categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    },

    showAddModal() {
        document.getElementById('labelModalTitle').innerHTML = '<i class="bi bi-plus-circle"></i> <?php echo $lang === "en" ? "Add Label" : "添加标签"; ?>';
        document.getElementById('labelForm').reset();
        document.getElementById('label_original_key').value = '';
        document.getElementById('label_category').value = this.currentCategory;
        document.getElementById('label_key').readOnly = false;
        this.labelModal.show();
    },

    showEditModal(key) {
        const item = labelsData[this.currentCategory][key];
        if (!item) {
            alert('Label not found');
            return;
        }

        document.getElementById('labelModalTitle').innerHTML = '<i class="bi bi-pencil"></i> <?php echo $lang === "en" ? "Edit Label" : "编辑标签"; ?>';
        document.getElementById('label_original_key').value = key;
        document.getElementById('label_category').value = this.currentCategory;
        document.getElementById('label_key').value = key;
        document.getElementById('label_key').readOnly = true;
        document.getElementById('label_value_cn').value = item.cn || '';
        document.getElementById('label_value_en').value = item.en || '';
        this.labelModal.show();
    },

    showAddCategoryModal() {
        document.getElementById('categoryForm').reset();
        this.categoryModal.show();
    },

    async saveLabel() {
        const form = document.getElementById('labelForm');
        const formData = new FormData(form);
        formData.append('action', 'save_label');

        try {
            const response = await fetch('/aiforms/api/labels.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                this.labelModal.hide();
                location.reload();
            } else {
                alert(result.message || 'Failed to save');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to save');
        }
    },

    async deleteLabel(key) {
        if (!confirm('<?php echo $lang === "en" ? "Are you sure you want to delete this label?" : "确定删除此标签？"; ?>')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'delete_label');
            formData.append('category', this.currentCategory);
            formData.append('key', key);

            const response = await fetch('/aiforms/api/labels.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                location.reload();
            } else {
                alert(result.message || 'Failed to delete');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to delete');
        }
    },

    async addCategory() {
        const categoryName = document.getElementById('new_category_name').value.trim();
        if (!categoryName) {
            alert('<?php echo $lang === "en" ? "Please enter category name" : "请输入分类名称"; ?>');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'add_category');
            formData.append('category', categoryName);

            const response = await fetch('/aiforms/api/labels.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                this.categoryModal.hide();
                window.location.href = '?config=true&category=' + encodeURIComponent(categoryName) + '<?php echo $lang !== "both" ? "&lang=".$lang : ""; ?>';
            } else {
                alert(result.message || 'Failed to create category');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to create category');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => LabelConfig.init());
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
