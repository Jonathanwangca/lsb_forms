<?php
/**
 * 参数配置管理页面
 * LSB RFQ System V3.1
 * 需要 URL 参数 config=true 才能访问
 */

// 检查访问权限
if (!isset($_GET['config']) || $_GET['config'] !== 'true') {
    header('HTTP/1.0 403 Forbidden');
    echo '<h1>403 Forbidden</h1><p>Access denied. This page requires config=true parameter.</p>';
    exit;
}

$pageTitle = 'Parameter Configuration - LSB RFQ System';
require_once dirname(__DIR__) . '/includes/header.php';

// 获取所有分类
$categories = dbQuery("
    SELECT DISTINCT category, category_name, category_name_cn
    FROM lsb_rfq_reference
    ORDER BY category
");

// 当前选中的分类
$currentCategory = isset($_GET['category']) ? $_GET['category'] : '';
$categoryItems = [];

if ($currentCategory) {
    $categoryItems = dbQuery("
        SELECT * FROM lsb_rfq_reference
        WHERE category = ?
        ORDER BY sort_order, id
    ", [$currentCategory]);
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="bi bi-gear"></i> <?php echo $lang === 'en' ? 'Parameter Configuration' : '参数配置管理'; ?></h4>
            <div>
                <a href="/aiforms/rfq/config_labels.php?config=true<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>" class="btn btn-outline-primary me-2">
                    <i class="bi bi-translate"></i> <?php echo $lang === 'en' ? 'Labels' : '标签配置'; ?>
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
                    <div class="card-header">
                        <i class="bi bi-folder"></i> <?php echo $lang === 'en' ? 'Categories' : '参数分类'; ?>
                    </div>
                    <div class="list-group list-group-flush" id="category-list" style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($categories as $cat): ?>
                        <a href="?config=true&category=<?php echo urlencode($cat['category']); ?><?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>"
                           id="cat-<?php echo h($cat['category']); ?>"
                           class="list-group-item list-group-item-action <?php echo $currentCategory === $cat['category'] ? 'active' : ''; ?>"
                           onclick="RefConfig.saveScrollPosition()">
                            <div class="fw-bold"><?php echo h($cat['category']); ?></div>
                            <small class="<?php echo $currentCategory === $cat['category'] ? 'text-white-50' : 'text-muted'; ?>">
                                <?php echo h($cat['category_name_cn']); ?>
                            </small>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 右侧参数列表 -->
            <div class="col-md-9">
                <?php if ($currentCategory): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-list-ul"></i>
                            <strong><?php echo h($currentCategory); ?></strong>
                            <?php
                            $catInfo = array_filter($categories, fn($c) => $c['category'] === $currentCategory);
                            $catInfo = reset($catInfo);
                            if ($catInfo): ?>
                            - <?php echo h($catInfo['category_name_cn']); ?> / <?php echo h($catInfo['category_name']); ?>
                            <?php endif; ?>
                        </span>
                        <button type="button" class="btn btn-sm btn-primary" onclick="RefConfig.showAddModal()">
                            <i class="bi bi-plus"></i> <?php echo $lang === 'en' ? 'Add' : '添加'; ?>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60"><?php echo $lang === 'en' ? 'Order' : '排序'; ?></th>
                                        <th width="120"><?php echo $lang === 'en' ? 'Code' : '代码'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'Chinese' : '中文'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'English' : '英文'; ?></th>
                                        <th width="80"><?php echo $lang === 'en' ? 'Default' : '默认'; ?></th>
                                        <th width="120"><?php echo $lang === 'en' ? 'Actions' : '操作'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categoryItems)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <?php echo $lang === 'en' ? 'No items found' : '暂无数据'; ?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($categoryItems as $item): ?>
                                    <tr data-id="<?php echo $item['id']; ?>">
                                        <td>
                                            <input type="number" class="form-control form-control-sm"
                                                   value="<?php echo h($item['sort_order']); ?>"
                                                   style="width: 60px;"
                                                   onchange="RefConfig.updateSort(<?php echo $item['id']; ?>, this.value)">
                                        </td>
                                        <td><code><?php echo h($item['code']); ?></code></td>
                                        <td><?php echo h($item['value_cn']); ?></td>
                                        <td><?php echo h($item['value_en']); ?></td>
                                        <td class="text-center">
                                            <?php if ($item['is_default']): ?>
                                            <span class="badge bg-success"><i class="bi bi-check"></i></span>
                                            <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    onclick="RefConfig.setDefault(<?php echo $item['id']; ?>)"
                                                    title="Set as default">
                                                <i class="bi bi-circle"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary"
                                                        onclick="RefConfig.showEditModal(<?php echo $item['id']; ?>)"
                                                        title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger"
                                                        onclick="RefConfig.deleteItem(<?php echo $item['id']; ?>)"
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
                            <?php echo $lang === 'en' ? 'Please select a category from the left' : '请从左侧选择一个参数分类'; ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 添加/编辑模态框 -->
<div class="modal fade" id="refModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="refModalTitle">
                    <i class="bi bi-plus-circle"></i> <?php echo $lang === 'en' ? 'Add Parameter' : '添加参数'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="refForm">
                    <input type="hidden" name="id" id="ref_id">
                    <input type="hidden" name="category" id="ref_category" value="<?php echo h($currentCategory); ?>">

                    <!-- AI智能填充提示 -->
                    <div class="alert alert-info py-2 mb-3">
                        <i class="bi bi-lightbulb"></i>
                        <small><?php echo $lang === 'en'
                            ? 'Enter Chinese or English value, then click AI button to auto-generate other fields'
                            : '输入中文或英文值后，点击 AI 按钮自动生成其他字段'; ?></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo $lang === 'en' ? 'Chinese Value' : '中文值'; ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="value_cn" id="ref_value_cn" required
                                   placeholder="<?php echo $lang === 'en' ? 'e.g. 钢结构' : '例如：钢结构'; ?>">
                            <button type="button" class="btn btn-outline-primary" onclick="RefConfig.aiGenerate('cn')" title="AI生成代码和英文">
                                <i class="bi bi-magic"></i> AI
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo $lang === 'en' ? 'English Value' : '英文值'; ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="value_en" id="ref_value_en" required
                                   placeholder="<?php echo $lang === 'en' ? 'e.g. Steel Structure' : '例如：Steel Structure'; ?>">
                            <button type="button" class="btn btn-outline-primary" onclick="RefConfig.aiGenerate('en')" title="AI生成代码和中文">
                                <i class="bi bi-magic"></i> AI
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo $lang === 'en' ? 'Code' : '代码'; ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="code" id="ref_code" required
                               placeholder="<?php echo $lang === 'en' ? 'Auto-generated or manual input' : '自动生成或手动输入'; ?>">
                        <small class="text-muted"><?php echo $lang === 'en' ? 'Unique identifier, uppercase recommended' : '唯一标识符，建议使用大写字母'; ?></small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><?php echo $lang === 'en' ? 'Sort Order' : '排序'; ?></label>
                                <input type="number" class="form-control" name="sort_order" id="ref_sort_order" value="10">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><?php echo $lang === 'en' ? 'Default' : '默认值'; ?></label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_default" id="ref_is_default" value="1">
                                    <label class="form-check-label" for="ref_is_default">
                                        <?php echo $lang === 'en' ? 'Set as default' : '设为默认'; ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php echo $lang === 'en' ? 'Cancel' : '取消'; ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="RefConfig.saveItem()">
                    <i class="bi bi-check"></i> <?php echo $lang === 'en' ? 'Save' : '保存'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const RefConfig = {
    modal: null,
    currentCategory: '<?php echo addslashes($currentCategory); ?>',

    init() {
        this.modal = new bootstrap.Modal(document.getElementById('refModal'));
        this.restoreScrollPosition();
    },

    // 保存左侧分类列表的滚动位置
    saveScrollPosition() {
        const categoryList = document.getElementById('category-list');
        if (categoryList) {
            sessionStorage.setItem('configCategoryScroll', categoryList.scrollTop);
        }
    },

    // 恢复滚动位置并确保当前选中项可见
    restoreScrollPosition() {
        const categoryList = document.getElementById('category-list');
        if (!categoryList) return;

        // 尝试恢复之前保存的滚动位置
        const savedScroll = sessionStorage.getItem('configCategoryScroll');
        if (savedScroll) {
            categoryList.scrollTop = parseInt(savedScroll);
            sessionStorage.removeItem('configCategoryScroll');
        } else if (this.currentCategory) {
            // 如果没有保存的位置，滚动到当前选中的分类
            const activeItem = categoryList.querySelector('.list-group-item.active');
            if (activeItem) {
                activeItem.scrollIntoView({ block: 'center', behavior: 'instant' });
            }
        }
    },

    showAddModal() {
        document.getElementById('refModalTitle').innerHTML = '<i class="bi bi-plus-circle"></i> <?php echo $lang === "en" ? "Add Parameter" : "添加参数"; ?>';
        document.getElementById('refForm').reset();
        document.getElementById('ref_id').value = '';
        document.getElementById('ref_category').value = this.currentCategory;
        this.modal.show();
    },

    // AI智能生成其他字段
    async aiGenerate(sourceType) {
        const valueCn = document.getElementById('ref_value_cn').value.trim();
        const valueEn = document.getElementById('ref_value_en').value.trim();
        const codeInput = document.getElementById('ref_code');
        const valueCnInput = document.getElementById('ref_value_cn');
        const valueEnInput = document.getElementById('ref_value_en');

        // 确定输入值
        const inputValue = sourceType === 'cn' ? valueCn : valueEn;
        if (!inputValue) {
            alert(sourceType === 'cn'
                ? '<?php echo $lang === "en" ? "Please enter Chinese value first" : "请先输入中文值"; ?>'
                : '<?php echo $lang === "en" ? "Please enter English value first" : "请先输入英文值"; ?>');
            return;
        }

        // 显示加载状态
        const buttons = document.querySelectorAll('#refForm .btn-outline-primary');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        });

        try {
            const response = await fetch('/aiforms/api/ai_translate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    source_type: sourceType,
                    value: inputValue,
                    category: this.currentCategory
                })
            });

            const result = await response.json();

            if (result.success) {
                // 填充生成的值
                if (result.data.code && !codeInput.value) {
                    codeInput.value = result.data.code;
                }
                if (sourceType === 'cn' && result.data.value_en) {
                    valueEnInput.value = result.data.value_en;
                }
                if (sourceType === 'en' && result.data.value_cn) {
                    valueCnInput.value = result.data.value_cn;
                }
            } else {
                alert(result.message || '<?php echo $lang === "en" ? "AI generation failed" : "AI生成失败"; ?>');
            }
        } catch (error) {
            console.error('AI Error:', error);
            alert('<?php echo $lang === "en" ? "AI generation failed" : "AI生成失败"; ?>');
        } finally {
            // 恢复按钮状态
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-magic"></i> AI';
            });
        }
    },

    async showEditModal(id) {
        try {
            const response = await fetch(`/aiforms/api/reference.php?action=get&id=${id}`);
            const result = await response.json();

            if (result.success) {
                const data = result.data;
                document.getElementById('refModalTitle').innerHTML = '<i class="bi bi-pencil"></i> <?php echo $lang === "en" ? "Edit Parameter" : "编辑参数"; ?>';
                document.getElementById('ref_id').value = data.id;
                document.getElementById('ref_category').value = data.category;
                document.getElementById('ref_code').value = data.code;
                document.getElementById('ref_value_cn').value = data.value_cn;
                document.getElementById('ref_value_en').value = data.value_en;
                document.getElementById('ref_sort_order').value = data.sort_order;
                document.getElementById('ref_is_default').checked = data.is_default == 1;
                this.modal.show();
            } else {
                alert(result.message || 'Failed to load data');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to load data');
        }
    },

    async saveItem() {
        const form = document.getElementById('refForm');
        const formData = new FormData(form);
        formData.append('action', formData.get('id') ? 'update' : 'create');

        // 处理 checkbox
        if (!document.getElementById('ref_is_default').checked) {
            formData.set('is_default', '0');
        }

        try {
            const response = await fetch('/aiforms/api/reference.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                this.modal.hide();
                location.reload();
            } else {
                alert(result.message || 'Failed to save');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to save');
        }
    },

    async updateSort(id, sortOrder) {
        try {
            const formData = new FormData();
            formData.append('action', 'update_sort');
            formData.append('id', id);
            formData.append('sort_order', sortOrder);

            const response = await fetch('/aiforms/api/reference.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (!result.success) {
                alert(result.message || 'Failed to update');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    },

    async setDefault(id) {
        if (!confirm('<?php echo $lang === "en" ? "Set this item as default?" : "确定设为默认值？"; ?>')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'set_default');
            formData.append('id', id);
            formData.append('category', this.currentCategory);

            const response = await fetch('/aiforms/api/reference.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                location.reload();
            } else {
                alert(result.message || 'Failed to update');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to update');
        }
    },

    async deleteItem(id) {
        if (!confirm('<?php echo $lang === "en" ? "Are you sure you want to delete this item?" : "确定删除此参数？"; ?>')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            const response = await fetch('/aiforms/api/reference.php', {
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
    }
};

document.addEventListener('DOMContentLoaded', () => RefConfig.init());
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
