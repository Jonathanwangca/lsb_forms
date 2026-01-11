<?php
/**
 * 标签配置管理页面
 * Inava Steel Customer Portal
 * 管理 labels.json 语言字典
 * 需要 URL 参数 config=true 才能访问
 */

// 检查访问权限
if (!isset($_GET['config']) || $_GET['config'] !== 'true') {
    header('HTTP/1.0 403 Forbidden');
    echo '<h1>403 Forbidden</h1><p>Access denied. This page requires config=true parameter.</p>';
    exit;
}

$pageTitle = 'Labels Configuration - Inava Steel Customer Portal';
require_once dirname(__DIR__) . '/includes/rfq_header.php';

// 标签文件路径
$labelsFile = dirname(__DIR__) . '/assets/lang/labels.json';
$labels = [];
if (file_exists($labelsFile)) {
    $labels = json_decode(file_get_contents($labelsFile), true) ?: [];
}

// 当前选中的分类
$currentCategory = isset($_GET['category']) ? $_GET['category'] : '';
$currentSection = isset($_GET['section']) ? $_GET['section'] : '';
$categoryItems = [];
if ($currentCategory && isset($labels[$currentCategory])) {
    $categoryItems = $labels[$currentCategory];
}

// 获取所有分类
$categories = array_keys($labels);

// Fields 字段分组定义
$fieldSections = [
    'contact' => [
        'en' => 'Contact Info',
        'cn' => '联系人信息',
        'icon' => 'bi-person-lines-fill',
        'keywords' => ['contact', 'attn', 'email', 'account_manager', 'salesperson', 'design_manager']
    ],
    'basic' => [
        'en' => 'Basic Info',
        'cn' => '基本信息',
        'icon' => 'bi-info-circle',
        'keywords' => ['rfq_no', 'job_number', 'project_name', 'project_location', 'building_qty', 'floor_area', 'due_date', 'liberty_contact', 'status', 'created_at', 'importance']
    ],
    'order_entry' => [
        'en' => 'Order Entry',
        'cn' => '报价资料',
        'icon' => 'bi-folder-check',
        'keywords' => ['drawing', 'autocad', 'mbs', 'architect', 'foundation', 'other_docs', 'fm_report', 'leed', 'factory_mutual', 'fm_approved']
    ],
    'structure' => [
        'en' => 'Structure',
        'cn' => '结构概述',
        'icon' => 'bi-building',
        'keywords' => ['roof_type', 'roof_slope', 'frame', 'length', 'width', 'eave_height', 'ridge_height', 'parapet_top', 'bay_spacing', 'seismic', 'wind_speed', 'snow_load', 'dead_load', 'live_load', 'design_code', 'fabrication', 'floor_elevation', 'floor_type', 'mezzanine']
    ],
    'scope' => [
        'en' => 'Scope',
        'cn' => '报价范围',
        'icon' => 'bi-list-check',
        'keywords' => ['scope', 'erection', 'bridge_crane', 'pre_eng', 'future_expansion', 'door_window', 'louver', 'railing', 'steel_deck', 'cable_tray', 'checkered', 'glazing', 'concrete_wall']
    ],
    'steel' => [
        'en' => 'Steel Materials',
        'cn' => '钢结构材料',
        'icon' => 'bi-box',
        'keywords' => ['steel_grade', 'primary_steel', 'primary_manufacturer', 'secondary_manufacturer', 'purlin', 'girt', 'flange_brace', 'partition_wall_frame']
    ],
    'painting' => [
        'en' => 'Painting',
        'cn' => '油漆涂装',
        'icon' => 'bi-palette',
        'keywords' => ['primer', 'intermediate', 'top_coat', 'exposed_paint', 'fire_coating', 'fire_rating', 'painting', 'coat']
    ],
    'envelope' => [
        'en' => 'Envelope',
        'cn' => '围护系统',
        'icon' => 'bi-house',
        'keywords' => ['wall_material', 'material_remarks', 'renovation', 'is_renovation', 'cladding', 'structural_reinforcement', 'mep_installation', 'reuse', 'waterproof']
    ],
    'roof' => [
        'en' => 'Roof System',
        'cn' => '屋面系统',
        'icon' => 'bi-house-door',
        'keywords' => ['roof_panel', 'roof_purlin', 'roof_liner', 'roof_opening', 'roof_ventilator', 'roof_skylight', 'roof_ridge', 'roof_vapor', 'roof_wire', 'roof_waterproof', 'aclok_roof', 'pv_system', 'pv_requirements', 'loading_canopy', 'canopy', 'small_canopy', 'skylight', 'ventilator']
    ],
    'wall' => [
        'en' => 'Wall System',
        'cn' => '墙面系统',
        'icon' => 'bi-bricks',
        'keywords' => ['wall_panel', 'wall_purlin', 'wall_liner', 'wall_outer', 'parapet']
    ],
    'panel' => [
        'en' => 'Panel Specs',
        'cn' => '板材规格',
        'icon' => 'bi-layers',
        'keywords' => ['panel_profile', 'panel_thickness', 'panel_color', 'thickness', 'coating', 'galvanizing', 'base_material', 'color_code', 'brand', 'model', 'strength', 'sandwich', 'aluminum', 'insulation', 'r_value', 'density', 'layer_position', 'zone', 'system_type', 'cladding_spec']
    ],
    'supplements' => [
        'en' => 'Notes',
        'cn' => '备注',
        'icon' => 'bi-chat-left-text',
        'keywords' => ['supplement', 'related_section', 'content', 'title', 'laboratory', 'other_requirements', 'notes', 'remarks']
    ],
    'other' => [
        'en' => 'Other',
        'cn' => '其他',
        'icon' => 'bi-three-dots',
        'keywords' => []
    ],
];

// 根据key确定所属section
function getFieldSection($key, $fieldSections) {
    $key = strtolower($key);
    foreach ($fieldSections as $sectionKey => $section) {
        if ($sectionKey === 'other') continue;
        foreach ($section['keywords'] as $keyword) {
            if (strpos($key, $keyword) === 0 || $key === $keyword) {
                return $sectionKey;
            }
        }
    }
    // 二次匹配：包含关键词
    foreach ($fieldSections as $sectionKey => $section) {
        if ($sectionKey === 'other') continue;
        foreach ($section['keywords'] as $keyword) {
            if (strpos($key, $keyword) !== false) {
                return $sectionKey;
            }
        }
    }
    return 'other';
}

// 如果是 fields 分类，按section分组
$groupedFields = [];
if ($currentCategory === 'fields' && !empty($categoryItems)) {
    foreach ($categoryItems as $key => $item) {
        $section = getFieldSection($key, $fieldSections);
        if (!isset($groupedFields[$section])) {
            $groupedFields[$section] = [];
        }
        $groupedFields[$section][$key] = $item;
    }
}

// 如果选择了section，只显示该section的字段
$displayItems = $categoryItems;
if ($currentCategory === 'fields' && $currentSection && isset($groupedFields[$currentSection])) {
    $displayItems = $groupedFields[$currentSection];
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <span class="h5 mb-0"><i class="bi bi-translate"></i> <?php echo $lang === 'en' ? 'Labels Configuration' : '标签配置管理'; ?></span>
            </div>
            <div>
                <a href="/aiforms/rfq/config.php?config=true<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>" class="btn btn-outline-primary">
                    <i class="bi bi-gear"></i> <?php echo $lang === 'en' ? 'Parameters' : '参数配置'; ?>
                </a>
                <a href="/aiforms/rfq/config_schema.php?config=true<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>" class="btn btn-outline-primary">
                    <i class="bi bi-diagram-3"></i> <?php echo $lang === 'en' ? 'Schema' : 'Schema配置'; ?>
                </a>
            </div>
        </div>
        <p class="text-muted mb-4" style="font-size: 11px;">
            <?php echo $lang === 'en'
                ? 'Manage bilingual labels for UI elements. Edit Chinese and English translations for field names, section titles, and other text displayed in forms and reports.'
                : 'Manage bilingual labels for UI elements. 管理界面元素的中英文标签，编辑表单和报表中显示的字段名称、Section标题及其他文本的翻译。'; ?>
        </p>

        <div class="row">
            <!-- 左侧分类列表 -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;" onclick="LabelConfig.toggleCategoryList()">
                        <span><i class="bi bi-list-ul"></i> <?php echo $lang === 'en' ? 'Index' : '标签索引'; ?> <i class="bi bi-chevron-up ms-1" id="categoryToggleIcon"></i></span>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); LabelConfig.showAddCategoryModal()">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    <!-- 搜索框 -->
                    <div class="p-2 border-bottom category-collapsible">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="categorySearch"
                                   placeholder="<?php echo $lang === 'en' ? 'Search...' : '搜索...'; ?>"
                                   oninput="LabelConfig.filterCategories(this.value)">
                            <button class="btn btn-outline-secondary" type="button" onclick="LabelConfig.clearSearch()" title="Clear">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                    <div class="list-group list-group-flush category-collapsible" id="categoryList" style="max-height: 550px; overflow-y: auto;">
                        <?php foreach ($categories as $cat): ?>
                        <?php if ($cat === 'fields'): ?>
                        <!-- Fields 分类特殊处理：显示子分类 -->
                        <div class="category-group" data-category="fields">
                            <a href="?config=true&category=fields<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>"
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center category-main <?php echo $currentCategory === 'fields' && !$currentSection ? 'active' : ($currentCategory === 'fields' ? 'bg-light' : ''); ?>"
                               data-name="fields">
                                <span><i class="bi bi-collection me-1"></i> fields</span>
                                <span class="badge <?php echo $currentCategory === 'fields' ? 'bg-primary' : 'bg-secondary'; ?>">
                                    <?php echo count($labels['fields']); ?>
                                </span>
                            </a>
                            <?php if ($currentCategory === 'fields'): ?>
                            <div class="field-sections ps-3">
                                <?php foreach ($fieldSections as $secKey => $secInfo): ?>
                                <?php if (isset($groupedFields[$secKey]) && !empty($groupedFields[$secKey])): ?>
                                <a href="?config=true&category=fields&section=<?php echo $secKey; ?><?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>"
                                   class="list-group-item list-group-item-action py-1 d-flex justify-content-between align-items-center field-section-item <?php echo $currentSection === $secKey ? 'active' : ''; ?>"
                                   data-section="<?php echo $secKey; ?>"
                                   data-name="<?php echo $secInfo['en']; ?> <?php echo $secInfo['cn']; ?>">
                                    <small><i class="bi <?php echo $secInfo['icon']; ?> me-1"></i><?php echo $lang === 'en' ? $secInfo['en'] : $secInfo['cn']; ?></small>
                                    <span class="badge bg-secondary" style="font-size: 0.7rem;"><?php echo count($groupedFields[$secKey]); ?></span>
                                </a>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <a href="?config=true&category=<?php echo urlencode($cat); ?><?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center category-item <?php echo $currentCategory === $cat ? 'active' : ''; ?>"
                           data-category="<?php echo h($cat); ?>"
                           data-name="<?php echo h($cat); ?>">
                            <span><?php echo h($cat); ?></span>
                            <span class="badge <?php echo $currentCategory === $cat ? 'bg-light text-primary' : 'bg-primary'; ?>">
                                <?php echo count($labels[$cat]); ?>
                            </span>
                        </a>
                        <?php endif; ?>
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
                            <?php if ($currentSection && isset($fieldSections[$currentSection])): ?>
                            <i class="bi bi-chevron-right mx-1"></i>
                            <span class="text-primary"><?php echo $lang === 'en' ? $fieldSections[$currentSection]['en'] : $fieldSections[$currentSection]['cn']; ?></span>
                            <?php endif; ?>
                            <span class="text-muted ms-2">(<?php echo count($displayItems); ?> <?php echo $lang === 'en' ? 'items' : '项'; ?>)</span>
                        </span>
                        <div>
                            <!-- 表格搜索 -->
                            <div class="input-group input-group-sm d-inline-flex me-2" style="width: 200px;">
                                <input type="text" class="form-control" id="tableSearch"
                                       placeholder="<?php echo $lang === 'en' ? 'Filter...' : '筛选...'; ?>"
                                       oninput="LabelConfig.filterTable(this.value)">
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="LabelConfig.showAddModal()">
                                <i class="bi bi-plus"></i> <?php echo $lang === 'en' ? 'Add Label' : '添加标签'; ?>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-hover table-striped mb-0" id="labelsTable">
                                <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th width="200"><?php echo $lang === 'en' ? 'Key' : '键名'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'English' : '英文'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'Chinese' : '中文'; ?></th>
                                        <th width="100"><?php echo $lang === 'en' ? 'Actions' : '操作'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($displayItems)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <?php echo $lang === 'en' ? 'No labels found' : '暂无标签'; ?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($displayItems as $key => $item): ?>
                                    <tr data-key="<?php echo h($key); ?>" class="label-row">
                                        <td><code><?php echo h($key); ?></code></td>
                                        <td class="label-en"><?php echo h($item['en'] ?? ''); ?></td>
                                        <td class="label-cn"><?php echo h($item['cn'] ?? ''); ?></td>
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

<style>
    /* 全局字体大小 */
    .row {
        font-size: 13px;
    }
    .card-header, .card-body, .table, .list-group-item {
        font-size: 13px;
    }
    h4 {
        font-size: 16px !important;
    }
    h5 {
        font-size: 14px !important;
    }
    .btn {
        font-size: 13px;
        padding: 0.35rem 0.6rem;
    }
    .btn-sm {
        font-size: 12px;
        padding: 0.25rem 0.5rem;
    }
    .badge {
        font-size: 11px;
    }
    .table th, .table td {
        padding: 0.4rem 0.6rem;
        font-size: 13px;
    }
    .list-group-item {
        padding: 0.5rem 0.75rem;
    }
    code {
        background: #f1f3f4;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 12px;
    }
    .card-header {
        padding: 0.5rem 0.75rem;
    }
    .text-muted, small {
        font-size: 12px;
    }
    .form-control, .input-group-text {
        font-size: 13px;
    }
    .modal-title {
        font-size: 15px;
    }
    .form-label {
        font-size: 13px;
    }
    .field-sections {
        border-left: 2px solid #dee2e6;
        margin-left: 10px;
    }
    .field-section-item {
        border-radius: 0 !important;
        font-size: 12px;
    }
    .field-section-item:hover {
        background-color: #f0f4f8 !important;
    }
    .category-main.bg-light {
        font-weight: 600;
    }
    .label-row.highlight {
        background-color: #fff3cd !important;
    }
    #tableSearch:focus, #categorySearch:focus {
        box-shadow: none;
        border-color: var(--primary-color);
    }
</style>

<script>
// 当前标签数据
const labelsData = <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE); ?>;

const LabelConfig = {
    labelModal: null,
    categoryModal: null,
    currentCategory: '<?php echo addslashes($currentCategory); ?>',
    currentSection: '<?php echo addslashes($currentSection); ?>',

    init() {
        this.labelModal = new bootstrap.Modal(document.getElementById('labelModal'));
        this.categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    },

    // 切换分类列表折叠状态
    toggleCategoryList() {
        const collapsibles = document.querySelectorAll('.category-collapsible');
        const icon = document.getElementById('categoryToggleIcon');
        const isHidden = collapsibles[0].style.display === 'none';

        collapsibles.forEach(el => {
            el.style.display = isHidden ? '' : 'none';
        });

        if (icon) {
            icon.className = isHidden ? 'bi bi-chevron-up ms-1' : 'bi bi-chevron-down ms-1';
        }
    },

    // 左侧分类搜索过滤
    filterCategories(query) {
        query = query.toLowerCase().trim();
        const items = document.querySelectorAll('#categoryList .category-item, #categoryList .category-main');
        const fieldSections = document.querySelectorAll('#categoryList .field-section-item');
        const groups = document.querySelectorAll('#categoryList .category-group');

        if (!query) {
            // 清空时显示全部
            items.forEach(item => item.style.display = '');
            fieldSections.forEach(item => item.style.display = '');
            groups.forEach(g => g.style.display = '');
        } else {
            // 过滤分类
            items.forEach(item => {
                const name = (item.dataset.name || item.dataset.category || '').toLowerCase();
                item.style.display = name.includes(query) ? '' : 'none';
            });
            // 过滤 fields 子分类
            fieldSections.forEach(item => {
                const name = (item.dataset.name || item.dataset.section || '').toLowerCase();
                item.style.display = name.includes(query) ? '' : 'none';
            });
            // 如果 fields 主分类匹配，显示所有子分类
            const fieldsMain = document.querySelector('.category-main[data-name="fields"]');
            if (fieldsMain && fieldsMain.style.display !== 'none') {
                fieldSections.forEach(item => item.style.display = '');
            }
            // 如果有子分类匹配，显示 fields 主分类
            const hasVisibleFieldSection = Array.from(fieldSections).some(s => s.style.display !== 'none');
            if (hasVisibleFieldSection && fieldsMain) {
                fieldsMain.style.display = '';
            }
        }
    },

    clearSearch() {
        document.getElementById('categorySearch').value = '';
        this.filterCategories('');
    },

    // 表格筛选
    filterTable(query) {
        query = query.toLowerCase().trim();
        const rows = document.querySelectorAll('#labelsTable tbody .label-row');

        rows.forEach(row => {
            row.classList.remove('highlight');
            if (!query) {
                row.style.display = '';
            } else {
                const key = row.dataset.key.toLowerCase();
                const cn = (row.querySelector('.label-cn')?.textContent || '').toLowerCase();
                const en = (row.querySelector('.label-en')?.textContent || '').toLowerCase();

                if (key.includes(query) || cn.includes(query) || en.includes(query)) {
                    row.style.display = '';
                    row.classList.add('highlight');
                } else {
                    row.style.display = 'none';
                }
            }
        });
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

<?php require_once dirname(__DIR__) . '/includes/rfq_footer.php'; ?>
