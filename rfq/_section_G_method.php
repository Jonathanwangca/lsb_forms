<?php
/**
 * RFQ Form Section: Construction Method
 * 屋墙面做法说明
 * 对应 Excel Row 33-38
 * 支持动态增删记录
 */

// 合并现有数据与预设
$roofMethods = array_values(array_filter($methods ?? [], fn($m) => ($m['method_category'] ?? '') === 'roof'));
$wallMethods = array_values(array_filter($methods ?? [], fn($m) => ($m['method_category'] ?? '') === 'wall'));

// 如果没有数据，提供默认预设
if (empty($roofMethods)) {
    $roofMethods = [
        ['method_category' => 'roof', 'method_no' => 1, 'method_desc' => '', 'scope' => ''],
        ['method_category' => 'roof', 'method_no' => 2, 'method_desc' => '', 'scope' => ''],
        ['method_category' => 'roof', 'method_no' => 3, 'method_desc' => '', 'scope' => ''],
    ];
}
if (empty($wallMethods)) {
    $wallMethods = [
        ['method_category' => 'wall', 'method_no' => 1, 'method_desc' => '', 'scope' => ''],
        ['method_category' => 'wall', 'method_no' => 2, 'method_desc' => '', 'scope' => ''],
        ['method_category' => 'wall', 'method_no' => 3, 'method_desc' => '', 'scope' => ''],
    ];
}

// 常用做法选项
$roofMethodOptions = [
    '' => $selectPlaceholder,
    'outer_insul_liner' => $lang === 'en' ? 'Outer Panel + Insulation + Liner' : '屋面外板+保温棉+屋面内板',
    'aclok' => $lang === 'en' ? 'Aluminum-Magnesium-Manganese Roof' : '铝镁锰屋面',
    'flexible' => $lang === 'en' ? 'Flexible Roofing' : '柔性屋面',
    'sandwich' => $lang === 'en' ? 'Sandwich Panel' : '夹芯板屋面',
    'single_layer' => $lang === 'en' ? 'Single Layer Panel' : '单层压型板',
    'other' => $lang === 'en' ? 'Other (Specify)' : '其他（请说明）',
];

$wallMethodOptions = [
    '' => $selectPlaceholder,
    'outer_insul_liner' => $lang === 'en' ? 'Outer Panel + Insulation + Liner' : '墙面外板+保温棉+墙面内板',
    'sandwich' => $lang === 'en' ? 'Sandwich Panel Wall' : '夹芯板墙面',
    'single_layer' => $lang === 'en' ? 'Single Layer Panel' : '单层压型板',
    'curtain_wall' => $lang === 'en' ? 'Curtain Wall' : '幕墙系统',
    'concrete_curb' => $lang === 'en' ? 'Concrete Curb + Panel' : '混凝土墙裙+压型板',
    'other' => $lang === 'en' ? 'Other (Specify)' : '其他（请说明）',
];

/**
 * 渲染做法行
 */
function renderMethodRow($method, $index, $category, $options, $lang, $selectPlaceholder) {
    $prefix = "methods[{$category}_{$index}]";
    $labelPrefix = $category === 'roof' ? ($lang === 'en' ? 'Roof Method ' : '屋面做法') : ($lang === 'en' ? 'Wall Method ' : '墙面做法');
    ob_start();
?>
<tr class="method-row" data-index="<?php echo $index; ?>" data-category="<?php echo $category; ?>">
    <td class="text-nowrap">
        <strong><?php echo $labelPrefix; ?><span class="method-num"><?php echo $index; ?></span>:</strong>
        <input type="hidden" name="<?php echo $prefix; ?>[method_category]" value="<?php echo $category; ?>">
        <input type="hidden" name="<?php echo $prefix; ?>[method_no]" class="method-no-input" value="<?php echo $index; ?>">
    </td>
    <td>
        <select class="form-select form-select-sm method-select"
                name="<?php echo $prefix; ?>[method_code]"
                data-target="<?php echo $prefix; ?>[method_desc]">
            <?php foreach ($options as $val => $label): ?>
            <option value="<?php echo $val; ?>" <?php echo ($method['method_code'] ?? '') == $val ? 'selected' : ''; ?>>
                <?php echo $label; ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="text" class="form-control form-control-sm mt-1"
               name="<?php echo $prefix; ?>[method_desc]"
               value="<?php echo h($method['method_desc'] ?? ''); ?>"
               placeholder="<?php echo $lang === 'en' ? 'Custom description...' : '自定义描述...'; ?>">
    </td>
    <td>
        <input type="text" class="form-control form-control-sm"
               name="<?php echo $prefix; ?>[scope]"
               value="<?php echo h($method['scope'] ?? ''); ?>"
               placeholder="<?php echo $lang === 'en' ? 'e.g., Main building, Canopy' : '如：主厂房、雨棚等'; ?>">
    </td>
    <td class="text-center" style="width:50px;">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="MethodManager.removeRow(this, '<?php echo $category; ?>')">
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>
<?php
    return ob_get_clean();
}
?>
<!-- ========== 屋墙面做法说明 ========== -->
<div class="form-section">
    <div class="form-section-header">
        <i class="bi bi-layers-half"></i> <span class="section-number">G.</span> <?php echo sectionTitle('屋墙面做法说明', 'Construction Method'); ?>
    </div>
    <div class="form-section-body">
        <!-- 屋面做法 -->
        <div class="form-subsection">
            <div class="form-subsection-title d-flex justify-content-between align-items-center">
                <span><span class="subsection-number">G.1</span> <?php echo sectionTitle('屋面做法', 'Roof Construction'); ?></span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="MethodManager.addRow('roof')">
                    <i class="bi bi-plus"></i> <?php echo $lang === 'en' ? 'Add' : '添加'; ?>
                </button>
            </div>
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:120px;"><?php echo $lang === 'en' ? 'Item' : '项目'; ?></th>
                        <th style="width:280px;"><?php echo $lang === 'en' ? 'Method Description' : '做法描述'; ?></th>
                        <th><?php echo $lang === 'en' ? 'Scope/Range' : '适用范围'; ?></th>
                        <th style="width:50px;"><?php echo $lang === 'en' ? 'Action' : '操作'; ?></th>
                    </tr>
                </thead>
                <tbody id="roof-methods-container">
                    <?php foreach ($roofMethods as $idx => $method): ?>
                    <?php echo renderMethodRow($method, $idx + 1, 'roof', $roofMethodOptions, $lang, $selectPlaceholder); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- 墙面做法 -->
        <div class="form-subsection">
            <div class="form-subsection-title d-flex justify-content-between align-items-center">
                <span><span class="subsection-number">G.2</span> <?php echo sectionTitle('墙面做法', 'Wall Construction'); ?></span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="MethodManager.addRow('wall')">
                    <i class="bi bi-plus"></i> <?php echo $lang === 'en' ? 'Add' : '添加'; ?>
                </button>
            </div>
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:120px;"><?php echo $lang === 'en' ? 'Item' : '项目'; ?></th>
                        <th style="width:280px;"><?php echo $lang === 'en' ? 'Method Description' : '做法描述'; ?></th>
                        <th><?php echo $lang === 'en' ? 'Scope/Range' : '适用范围'; ?></th>
                        <th style="width:50px;"><?php echo $lang === 'en' ? 'Action' : '操作'; ?></th>
                    </tr>
                </thead>
                <tbody id="wall-methods-container">
                    <?php foreach ($wallMethods as $idx => $method): ?>
                    <?php echo renderMethodRow($method, $idx + 1, 'wall', $wallMethodOptions, $lang, $selectPlaceholder); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 行模板 -->
<template id="roof-method-template">
    <?php echo renderMethodRow([], '__INDEX__', 'roof', $roofMethodOptions, $lang, $selectPlaceholder); ?>
</template>
<template id="wall-method-template">
    <?php echo renderMethodRow([], '__INDEX__', 'wall', $wallMethodOptions, $lang, $selectPlaceholder); ?>
</template>

<script>
// 做法管理器
const MethodManager = {
    roofIndex: <?php echo count($roofMethods); ?>,
    wallIndex: <?php echo count($wallMethods); ?>,

    addRow: function(category) {
        const container = document.getElementById(category + '-methods-container');
        const template = document.getElementById(category + '-method-template');

        let newIndex;
        if (category === 'roof') {
            newIndex = ++this.roofIndex;
        } else {
            newIndex = ++this.wallIndex;
        }

        const html = template.innerHTML.replace(/__INDEX__/g, newIndex);
        container.insertAdjacentHTML('beforeend', html);

        // 重新绑定事件
        this.bindSelectEvents();
    },

    removeRow: function(btn, category) {
        const row = btn.closest('.method-row');
        const container = document.getElementById(category + '-methods-container');

        // 至少保留1条记录
        if (container.querySelectorAll('.method-row').length <= 1) {
            alert('<?php echo $lang === "en" ? "At least one record is required." : "至少需要保留一条记录"; ?>');
            return;
        }

        if (row) {
            row.remove();
            this.renumberRows(category);
        }
    },

    renumberRows: function(category) {
        const container = document.getElementById(category + '-methods-container');
        const rows = container.querySelectorAll('.method-row');

        rows.forEach((row, idx) => {
            const num = idx + 1;
            // 更新显示的序号
            row.querySelector('.method-num').textContent = num;
            // 更新隐藏字段的值
            row.querySelector('.method-no-input').value = num;
            // 更新data-index
            row.dataset.index = num;

            // 更新所有input/select的name属性
            const prefix = `methods[${category}_${num}]`;
            row.querySelectorAll('input, select').forEach(input => {
                const name = input.name;
                if (name) {
                    // 替换 methods[category_X] 为新的序号
                    const newName = name.replace(/methods\[\w+_\d+\]/, prefix);
                    input.name = newName;
                }
            });

            // 更新select的data-target属性
            const select = row.querySelector('.method-select');
            if (select) {
                select.dataset.target = `${prefix}[method_desc]`;
            }
        });
    },

    bindSelectEvents: function() {
        document.querySelectorAll('.method-select').forEach(select => {
            // 移除旧的事件监听器（通过克隆替换）
            const newSelect = select.cloneNode(true);
            select.parentNode.replaceChild(newSelect, select);

            newSelect.addEventListener('change', function() {
                const targetName = this.dataset.target;
                const targetInput = document.querySelector(`input[name="${targetName}"]`);
                if (targetInput && this.value && this.value !== 'other') {
                    const selectedText = this.options[this.selectedIndex].text;
                    if (selectedText !== '<?php echo $selectPlaceholder; ?>') {
                        targetInput.value = selectedText;
                    }
                }
            });
        });
    }
};

// 初始化事件绑定
document.addEventListener('DOMContentLoaded', function() {
    MethodManager.bindSelectEvents();
});
</script>
