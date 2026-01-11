<?php
/**
 * Schema配置管理页面
 * 显示和管理rfq_schema.php中的字段定义
 * LSB RFQ System V3.2
 */

// 检查访问权限
if (!isset($_GET['config']) || $_GET['config'] !== 'true') {
    header('HTTP/1.0 403 Forbidden');
    echo '<h1>403 Forbidden</h1><p>Access denied. This page requires config=true parameter.</p>';
    exit;
}

$pageTitle = 'Schema Configuration - Inava Steel Customer Portal';
require_once dirname(__DIR__) . '/includes/rfq_header.php';
require_once __DIR__ . '/rfq_schema.php';

// 获取Schema数据
$schema = getRfqSchema();
$sections = $schema['sections'];
$fields = $schema['fields'];

// 当前选中的Section
$currentSection = isset($_GET['section']) ? $_GET['section'] : '';
$currentSubsection = isset($_GET['subsection']) ? $_GET['subsection'] : '';

// 获取当前Section的字段
$currentFields = [];
if ($currentSection && isset($fields[$currentSection])) {
    if ($currentSubsection && isset($fields[$currentSection][$currentSubsection])) {
        // 有Subsection的情况
        $currentFields = $fields[$currentSection][$currentSubsection];
    } elseif (!$currentSubsection) {
        // 检查是否有subsection结构
        $sectionFields = $fields[$currentSection];
        $hasSubsections = false;
        foreach ($sectionFields as $key => $value) {
            if (is_array($value) && isset($value['cn'])) {
                // 这是直接的字段
                $currentFields[$key] = $value;
            } else {
                // 这是subsection
                $hasSubsections = true;
            }
        }
        if ($hasSubsections && !$currentFields) {
            // 只有subsection，没有直接字段
            $currentFields = null;
        }
    }
}

// 动态表格字段定义
$dynamicFields = [
    'panels' => ['title_cn' => '板材字段', 'title_en' => 'Panel Fields', 'fields' => $schema['panel_fields']],
    'insulations' => ['title_cn' => '保温棉字段', 'title_en' => 'Insulation Fields', 'fields' => $schema['insulation_fields']],
    'drainages' => ['title_cn' => '排水字段', 'title_en' => 'Drainage Fields', 'fields' => $schema['drainage_fields']],
    'methods' => ['title_cn' => '做法字段', 'title_en' => 'Method Fields', 'fields' => $schema['method_fields']],
    'supplements' => ['title_cn' => '补充说明字段', 'title_en' => 'Supplement Fields', 'fields' => $schema['supplement_fields']],
];
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <span class="h5 mb-0"><i class="bi bi-diagram-3"></i> <?php echo $lang === 'en' ? 'Schema Configuration' : 'Schema字段配置'; ?></span>
            </div>
            <div>
                <a href="/aiforms/rfq/config.php?config=true<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>" class="btn btn-outline-primary">
                    <i class="bi bi-gear"></i> <?php echo $lang === 'en' ? 'Parameters' : '参数配置'; ?>
                </a>
                <a href="/aiforms/rfq/config_labels.php?config=true<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>" class="btn btn-outline-primary">
                    <i class="bi bi-translate"></i> <?php echo $lang === 'en' ? 'Labels' : '标签配置'; ?>
                </a>
            </div>
        </div>
        <p class="text-muted mb-4" style="font-size: 11px;">
            <?php echo $lang === 'en'
                ? 'View form field structure definitions. This page displays all sections, subsections, and field configurations defined in the schema (read-only).'
                : 'View form field structure definitions. 查看表单字段结构定义，此页面展示Schema中定义的所有Section、Subsection及字段配置（只读）。'; ?>
        </p>

        <div class="row">
            <!-- 左侧Section导航 -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-list-ul"></i> <?php echo $lang === 'en' ? 'Sections' : 'Section索引'; ?></span>
                        <span class="badge bg-secondary"><?php echo count($sections); ?></span>
                    </div>
                    <div id="section-nav" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($sections as $key => $section): ?>
                        <a href="?config=true&section=<?php echo $key; ?><?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>"
                           class="list-group-item list-group-item-action py-2 <?php echo ($currentSection === $key) ? 'active' : ''; ?>"
                           style="font-size: 0.85rem; border-left: 3px solid <?php echo $currentSection === $key ? 'var(--primary-color)' : 'transparent'; ?>;">
                            <i class="bi <?php echo $section['icon']; ?> me-1"></i>
                            <?php echo $key; ?>. <?php echo $lang === 'en' ? $section['en'] : $section['cn']; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 动态表格字段 -->
                <div class="card mt-3">
                    <div class="card-header py-2" style="font-size: 0.85rem;">
                        <i class="bi bi-table"></i> <?php echo $lang === 'en' ? 'Dynamic Fields' : '动态表格'; ?>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($dynamicFields as $key => $dynamic): ?>
                        <a href="?config=true&dynamic=<?php echo $key; ?><?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>"
                           class="list-group-item list-group-item-action py-2 <?php echo (isset($_GET['dynamic']) && $_GET['dynamic'] === $key) ? 'active' : ''; ?>"
                           style="font-size: 0.85rem;">
                            <?php echo $lang === 'en' ? $dynamic['title_en'] : $dynamic['title_cn']; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 右侧字段详情 -->
            <div class="col-md-9">
                <?php if (isset($_GET['dynamic']) && isset($dynamicFields[$_GET['dynamic']])): ?>
                <!-- 动态表格字段详情 -->
                <?php
                $dynamicKey = $_GET['dynamic'];
                $dynamicInfo = $dynamicFields[$dynamicKey];
                ?>
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-table"></i>
                        <strong><?php echo $lang === 'en' ? $dynamicInfo['title_en'] : $dynamicInfo['title_cn']; ?></strong>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="150"><?php echo $lang === 'en' ? 'Field Key' : '字段名'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'English' : '英文'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'Chinese' : '中文'; ?></th>
                                        <th width="100"><?php echo $lang === 'en' ? 'Type' : '类型'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'Options/Ref' : '选项/引用'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dynamicInfo['fields'] as $fieldKey => $field): ?>
                                    <tr>
                                        <td><code><?php echo h($fieldKey); ?></code></td>
                                        <td><?php echo h($field['en']); ?></td>
                                        <td><?php echo h($field['cn']); ?></td>
                                        <td><span class="badge bg-<?php echo getTypeBadgeColor($field['type']); ?>"><?php echo h($field['type']); ?></span></td>
                                        <td>
                                            <?php if (isset($field['ref_category'])): ?>
                                                <span class="badge bg-info">ref: <?php echo h(is_array($field['ref_category']) ? json_encode($field['ref_category']) : $field['ref_category']); ?></span>
                                            <?php elseif (isset($field['options'])): ?>
                                                <small class="text-muted">
                                                    <?php
                                                    $optLabels = array_map(function($opt) use ($lang) {
                                                        return $lang === 'en' ? $opt['en'] : $opt['cn'];
                                                    }, $field['options']);
                                                    echo implode(', ', array_slice($optLabels, 0, 3));
                                                    if (count($optLabels) > 3) echo '...';
                                                    ?>
                                                </small>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php elseif ($currentSection && isset($sections[$currentSection])): ?>
                <!-- Section字段详情 -->
                <?php $sectionInfo = $sections[$currentSection]; ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi <?php echo $sectionInfo['icon']; ?>"></i>
                            <strong><?php echo $currentSection; ?>. <?php echo $lang === 'en' ? $sectionInfo['en'] : $sectionInfo['cn']; ?></strong>
                            <?php if ($currentSubsection): ?>
                            <span class="text-muted">→</span>
                            <span class="text-primary"><?php echo $currentSubsection; ?> <?php
                                $sub = $sectionInfo['subsections'][$currentSubsection] ?? null;
                                echo $sub ? ($lang === 'en' ? $sub['en'] : $sub['cn']) : '';
                            ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="badge bg-secondary"><?php echo $sectionInfo['table']; ?></span>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($currentFields === null): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-info-circle fs-3"></i>
                            <p class="mt-2"><?php echo $lang === 'en' ? 'This section has subsections. Please select a subsection below.' : '此Section包含子分类，请从下方选择一个Subsection查看。'; ?></p>
                        </div>
                        <?php elseif (empty($currentFields)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3"></i>
                            <p class="mt-2"><?php echo $lang === 'en' ? 'No fields defined for this section.' : '此区域暂无字段定义。'; ?></p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="150"><?php echo $lang === 'en' ? 'Field Key' : '字段名'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'English' : '英文'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'Chinese' : '中文'; ?></th>
                                        <th width="100"><?php echo $lang === 'en' ? 'Type' : '类型'; ?></th>
                                        <th width="80"><?php echo $lang === 'en' ? 'Width' : '宽度'; ?></th>
                                        <th width="200"><?php echo $lang === 'en' ? 'Form Name' : '表单名'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'Options/Ref' : '选项/引用'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($currentFields as $fieldKey => $field): ?>
                                    <tr>
                                        <td><code><?php echo h($fieldKey); ?></code></td>
                                        <td><?php echo h($field['en']); ?></td>
                                        <td><?php echo h($field['cn']); ?></td>
                                        <td><span class="badge bg-<?php echo getTypeBadgeColor($field['type']); ?>"><?php echo h($field['type']); ?></span></td>
                                        <td><?php echo isset($field['width']) ? $field['width'] : '-'; ?></td>
                                        <td><code class="small"><?php echo h($field['name'] ?? '-'); ?></code></td>
                                        <td>
                                            <?php if (isset($field['ref_category'])): ?>
                                                <a href="/aiforms/rfq/config.php?config=true&category=<?php echo urlencode($field['ref_category']); ?><?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>" class="badge bg-info text-decoration-none">
                                                    ref: <?php echo h($field['ref_category']); ?>
                                                </a>
                                            <?php elseif (isset($field['options'])): ?>
                                                <small class="text-muted">
                                                    <?php
                                                    $optLabels = array_map(function($opt) use ($lang) {
                                                        return $lang === 'en' ? ($opt['en'] ?? $opt['value']) : ($opt['cn'] ?? $opt['value']);
                                                    }, $field['options']);
                                                    echo implode(', ', array_slice($optLabels, 0, 3));
                                                    if (count($optLabels) > 3) echo '...';
                                                    ?>
                                                </small>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 如果Section有subsection，显示subsection概览 -->
                <?php if (!$currentSubsection && !empty($sectionInfo['subsections'])): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <i class="bi bi-diagram-2"></i> <?php echo $lang === 'en' ? 'Subsections Overview' : 'Subsection概览'; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="100"><?php echo $lang === 'en' ? 'Number' : '编号'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'English' : '英文'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'Chinese' : '中文'; ?></th>
                                        <th width="100"><?php echo $lang === 'en' ? 'Fields' : '字段数'; ?></th>
                                        <th width="100"><?php echo $lang === 'en' ? 'Action' : '操作'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sectionInfo['subsections'] as $subKey => $sub): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?php echo $subKey; ?></span></td>
                                        <td><?php echo h($sub['en']); ?></td>
                                        <td><?php echo h($sub['cn']); ?></td>
                                        <td>
                                            <?php
                                            $subFields = $fields[$currentSection][$subKey] ?? [];
                                            echo count($subFields);
                                            ?>
                                        </td>
                                        <td>
                                            <a href="?config=true&section=<?php echo $currentSection; ?>&subsection=<?php echo urlencode($subKey); ?><?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <!-- 默认欢迎页面 -->
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-diagram-3" style="font-size: 4rem; color: #ddd;"></i>
                        <h5 class="mt-3 text-muted"><?php echo $lang === 'en' ? 'Schema Configuration' : 'Schema字段配置'; ?></h5>
                        <p class="text-muted mb-4">
                            <?php echo $lang === 'en'
                                ? 'View and manage field definitions from rfq_schema.php. Select a section from the left to view its fields.'
                                : '查看和管理rfq_schema.php中的字段定义。从左侧选择一个Section查看其字段。'; ?>
                        </p>

                        <!-- Schema统计 -->
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-4">
                                        <div class="card bg-light">
                                            <div class="card-body py-3">
                                                <h3 class="mb-0 text-primary"><?php echo count($sections); ?></h3>
                                                <small class="text-muted"><?php echo $lang === 'en' ? 'Sections' : 'Sections'; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="card bg-light">
                                            <div class="card-body py-3">
                                                <?php
                                                $totalSubsections = 0;
                                                foreach ($sections as $s) {
                                                    $totalSubsections += count($s['subsections'] ?? []);
                                                }
                                                ?>
                                                <h3 class="mb-0 text-success"><?php echo $totalSubsections; ?></h3>
                                                <small class="text-muted"><?php echo $lang === 'en' ? 'Subsections' : 'Subsections'; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="card bg-light">
                                            <div class="card-body py-3">
                                                <?php
                                                $totalFields = 0;
                                                foreach ($fields as $sectionFields) {
                                                    foreach ($sectionFields as $key => $value) {
                                                        if (is_array($value) && isset($value['cn'])) {
                                                            $totalFields++;
                                                        } elseif (is_array($value)) {
                                                            $totalFields += count($value);
                                                        }
                                                    }
                                                }
                                                ?>
                                                <h3 class="mb-0 text-info"><?php echo $totalFields; ?></h3>
                                                <small class="text-muted"><?php echo $lang === 'en' ? 'Fields' : '字段数'; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section概览表格 -->
                <div class="card mt-3">
                    <div class="card-header">
                        <i class="bi bi-list-columns"></i> <?php echo $lang === 'en' ? 'All Sections Overview' : '所有Section概览'; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60"><?php echo $lang === 'en' ? 'Key' : '编号'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'English' : '英文'; ?></th>
                                        <th><?php echo $lang === 'en' ? 'Chinese' : '中文'; ?></th>
                                        <th width="120"><?php echo $lang === 'en' ? 'DB Key' : '数据库键'; ?></th>
                                        <th width="150"><?php echo $lang === 'en' ? 'Table' : '数据表'; ?></th>
                                        <th width="100"><?php echo $lang === 'en' ? 'Subsections' : '子分类'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sections as $key => $section): ?>
                                    <tr style="cursor: pointer;" onclick="location.href='?config=true&section=<?php echo $key; ?><?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>'">
                                        <td><span class="badge bg-primary"><?php echo $key; ?></span></td>
                                        <td><i class="bi <?php echo $section['icon']; ?> me-2 text-muted"></i><?php echo h($section['en']); ?></td>
                                        <td><?php echo h($section['cn']); ?></td>
                                        <td><code><?php echo h($section['key']); ?></code></td>
                                        <td><code class="small"><?php echo h($section['table']); ?></code></td>
                                        <td>
                                            <?php if (!empty($section['subsections'])): ?>
                                            <span class="badge bg-secondary"><?php echo count($section['subsections']); ?></span>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * 获取类型对应的Badge颜色
 */
function getTypeBadgeColor($type) {
    $colors = [
        'text' => 'secondary',
        'number' => 'info',
        'email' => 'info',
        'date' => 'warning',
        'select' => 'primary',
        'yesno' => 'success',
        'switch' => 'success',
        'textarea' => 'dark',
        'hidden' => 'light',
    ];
    return $colors[$type] ?? 'secondary';
}
?>

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
h3 {
    font-size: 16px !important;
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
.section-nav-group .list-group-item {
    border-radius: 0;
}
.section-nav-group .list-group-item.active {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}
#section-nav .list-group-item {
    padding: 0.5rem 0.75rem;
    font-size: 13px;
}
#section-nav .list-group-item:hover:not(.active) {
    background-color: #f8f9fa;
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
.badge.bg-light {
    color: #666;
    border: 1px solid #ddd;
}
.card-header {
    padding: 0.5rem 0.75rem;
}
.card-body {
    padding: 0.5rem;
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
</style>

<?php require_once dirname(__DIR__) . '/includes/rfq_footer.php'; ?>
