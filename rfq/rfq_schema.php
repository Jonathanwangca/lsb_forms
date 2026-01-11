<?php
/**
 * RFQ Schema - 统一字段配置
 *
 * 此文件定义了所有RFQ表单字段、Section结构、标签等
 * 表单和PDF共用此配置，确保一致性
 *
 * 结构说明:
 * - sections: 定义所有Section (A-L)
 * - fields: 定义所有字段（按Section和Subsection组织）
 * - panel_fields: 板材动态表格字段
 * - insulation_fields: 保温棉动态表格字段
 * - drainage_fields: 排水动态表格字段
 * - method_fields: 做法动态表格字段
 * - supplement_fields: 补充说明动态表格字段
 *
 * @version 3.3
 */

// 防止重复声明
if (function_exists('getRfqSchema')) {
    return;
}

/**
 * 获取完整Schema
 */
function getRfqSchema() {
    return [
        'sections' => getSections(),
        'fields' => getFields(),
        'panel_fields' => getPanelFields(),
        'insulation_fields' => getInsulationFields(),
        'drainage_fields' => getDrainageFields(),
        'method_fields' => getMethodFields(),
        'supplement_fields' => getSupplementFields(),
    ];
}

/**
 * Section定义 (A-L)
 */
function getSections() {
    return [
        'A' => [
            'key' => 'contact',
            'cn' => '联系人信息',
            'en' => 'Contact Information',
            'icon' => 'bi-person-lines-fill',
            'table' => 'rfq_contacts',
            'subsections' => []
        ],
        'B' => [
            'key' => 'basic',
            'cn' => '基本信息',
            'en' => 'Basic Information',
            'icon' => 'bi-info-circle',
            'table' => 'rfq_main',
            'subsections' => []
        ],
        'C' => [
            'key' => 'order_entry',
            'cn' => '报价资料',
            'en' => 'Order Entry',
            'icon' => 'bi-folder-check',
            'table' => 'rfq_order_entry',
            'subsections' => []
        ],
        'D' => [
            'key' => 'structure',
            'cn' => '结构概述',
            'en' => 'Building Structure',
            'icon' => 'bi-building',
            'table' => 'rfq_structure',
            'subsections' => []
        ],
        'E' => [
            'key' => 'scope',
            'cn' => '报价范围',
            'en' => 'Scope of Work',
            'icon' => 'bi-list-check',
            'table' => 'rfq_scope',
            'subsections' => []
        ],
        'F' => [
            'key' => 'steel',
            'cn' => '建筑描述 & 钢结构材料',
            'en' => 'Building Dimensions & Steel',
            'icon' => 'bi-rulers',
            'table' => 'rfq_steel',
            'subsections' => [
                'F.1' => ['cn' => '建筑尺寸', 'en' => 'Building Dimensions'],
                'F.2' => ['cn' => '主结构材料', 'en' => 'Primary Steel'],
                'F.3' => ['cn' => '中间漆+面漆', 'en' => 'Intermediate & Top Coat'],
                'F.4' => ['cn' => '外露构件油漆', 'en' => 'Exposed Paint'],
                'F.5' => ['cn' => '普通钢结构防火涂料', 'en' => 'Fire Coating'],
                'F.6' => ['cn' => '次结构材料', 'en' => 'Secondary Steel'],
                'F.7' => ['cn' => '花纹钢板', 'en' => 'Checkered Plate'],
            ]
        ],
        'G' => [
            'key' => 'method',
            'cn' => '屋墙面做法说明',
            'en' => 'Construction Method',
            'icon' => 'bi-layers-half',
            'table' => 'rfq_methods',
            'subsections' => [
                'G.1' => ['cn' => '屋面做法', 'en' => 'Roof Construction'],
                'G.2' => ['cn' => '墙面做法', 'en' => 'Wall Construction'],
            ]
        ],
        'H' => [
            'key' => 'envelope',
            'cn' => '围护系统配置',
            'en' => 'Envelope Configuration',
            'icon' => 'bi-house',
            'table' => 'rfq_envelope',
            'subsections' => [
                'H.1' => ['cn' => '屋墙面材料', 'en' => 'Material Configuration'],
                'H.2' => ['cn' => '改造项目', 'en' => 'Renovation'],
                'H.3' => ['cn' => '防水规范', 'en' => 'Waterproof Standard'],
                'H.4' => ['cn' => '屋面特殊配置', 'en' => 'Roof Special Configuration'],
            ]
        ],
        'I' => [
            'key' => 'roof_material',
            'cn' => '屋面系统材质要求',
            'en' => 'Roof Material Requirements',
            'icon' => 'bi-house-door',
            'table' => 'rfq_panels,rfq_insulations,rfq_drainages',
            'subsections' => [
                'I.1' => ['cn' => '屋面排水系统', 'en' => 'Roof Drainage System'],
                'I.2' => ['cn' => '屋面外板', 'en' => 'Roof Outer Panel'],
                'I.3' => ['cn' => '屋面保温棉', 'en' => 'Roof Insulation'],
                'I.4' => ['cn' => '防水透气膜/隔汽膜/钢丝网', 'en' => 'Membrane & Wire Mesh'],
                'I.5' => ['cn' => '屋面内衬板', 'en' => 'Roof Liner Panel'],
                'I.6' => ['cn' => '大雨蓬板', 'en' => 'Loading Canopy Panel'],
                'I.7' => ['cn' => '标准小雨蓬', 'en' => 'Small Canopy'],
                'I.8' => ['cn' => '屋面采光', 'en' => 'Roof Skylight'],
                'I.9' => ['cn' => '其他屋面材料', 'en' => 'Other Roof Materials'],
            ]
        ],
        'J' => [
            'key' => 'wall_material',
            'cn' => '墙面系统材质要求',
            'en' => 'Wall Material Requirements',
            'icon' => 'bi-bricks',
            'table' => 'rfq_panels,rfq_insulations',
            'subsections' => [
                'J.1' => ['cn' => '墙面配置', 'en' => 'Wall Configuration'],
                'J.2' => ['cn' => '墙面外板', 'en' => 'Wall Outer Panel'],
                'J.3' => ['cn' => '墙面保温棉', 'en' => 'Wall Insulation'],
                'J.4' => ['cn' => '墙面防水透气膜/隔汽膜/钢丝网', 'en' => 'Wall Membrane & Wire Mesh'],
                'J.5' => ['cn' => '墙面内衬板', 'en' => 'Wall Liner Panel'],
                'J.6' => ['cn' => '女儿墙内衬板', 'en' => 'Parapet Liner Panel'],
                'J.7' => ['cn' => '内隔墙墙面板', 'en' => 'Partition Wall Panel'],
            ]
        ],
        'K' => [
            'key' => 'supplements',
            'cn' => '备注',
            'en' => 'Notes',
            'icon' => 'bi-chat-left-text',
            'table' => 'rfq_supplements',
            'subsections' => []
        ],
        'L' => [
            'key' => 'status',
            'cn' => '状态',
            'en' => 'Status',
            'icon' => 'bi-flag',
            'table' => 'rfq_main',
            'subsections' => []
        ],
    ];
}

/**
 * 字段定义
 * 按Section和Subsection组织
 */
function getFields() {
    return [
        // ========== Section A: 联系人信息 ==========
        'A' => [
            'contact_to' => [
                'cn' => '发给',
                'en' => 'To',
                'type' => 'text',
                'name' => 'contact[contact_to]',
                'width' => 4,
            ],
            'attn' => [
                'cn' => '收件人',
                'en' => 'Attn',
                'type' => 'text',
                'name' => 'contact[attn]',
                'width' => 4,
            ],
            'contact_email' => [
                'cn' => '电子邮箱',
                'en' => 'Email',
                'type' => 'email',
                'name' => 'contact[contact_email]',
                'width' => 4,
            ],
            'account_manager' => [
                'cn' => '客户经理',
                'en' => 'Account Manager',
                'type' => 'text',
                'name' => 'contact[account_manager]',
                'width' => 4,
            ],
            'account_manager_title' => [
                'cn' => '职称',
                'en' => 'Title',
                'type' => 'text',
                'name' => 'contact[account_manager_title]',
                'width' => 4,
            ],
            'salesperson' => [
                'cn' => '销售人员',
                'en' => 'Salesperson',
                'type' => 'text',
                'name' => 'contact[salesperson]',
                'width' => 4,
            ],
            'design_manager' => [
                'cn' => '设计经理',
                'en' => 'Design Manager',
                'type' => 'text',
                'name' => 'contact[design_manager]',
                'width' => 4,
            ],
        ],

        // ========== Section B: 基本信息 ==========
        'B' => [
            'rfq_no' => [
                'cn' => '报价项目申请号',
                'en' => 'RFQ No.',
                'type' => 'text',
                'name' => 'main[rfq_no]',
                'width' => 3,
            ],
            'job_number' => [
                'cn' => '项目编号',
                'en' => 'Job Number',
                'type' => 'text',
                'name' => 'main[job_number]',
                'width' => 3,
            ],
            'project_name' => [
                'cn' => '项目名称',
                'en' => 'Project Name',
                'type' => 'text',
                'name' => 'main[project_name]',
                'width' => 6,
            ],
            'project_location' => [
                'cn' => '项目所在位置',
                'en' => 'Project Location',
                'type' => 'text',
                'name' => 'main[project_location]',
                'width' => 6,
            ],
            'building_qty' => [
                'cn' => '单体数量',
                'en' => 'Building Qty',
                'type' => 'number',
                'name' => 'main[building_qty]',
                'width' => 3,
            ],
            'floor_area_1' => [
                'cn' => '建筑屋面面积 1',
                'en' => 'Floor Area 1',
                'type' => 'text',
                'name' => 'main[floor_area_1]',
                'width' => 3,
            ],
            'floor_area_2' => [
                'cn' => '建筑屋面面积 2',
                'en' => 'Floor Area 2',
                'type' => 'text',
                'name' => 'main[floor_area_2]',
                'width' => 3,
            ],
            'due_date' => [
                'cn' => '要求报价提交日期',
                'en' => 'Due by',
                'type' => 'date',
                'name' => 'main[due_date]',
                'width' => 3,
            ],
            'liberty_contact' => [
                'cn' => 'Liberty 联系电话',
                'en' => 'Liberty Contact',
                'type' => 'text',
                'name' => 'main[liberty_contact]',
                'width' => 3,
            ],
        ],

        // ========== Section D: 结构概述 ==========
        'D' => [
            'roof_type' => [
                'cn' => '屋面形式',
                'en' => 'Roof Type',
                'type' => 'select',
                'name' => 'structure[roof_type]',
                'ref_category' => 'roof_type',
                'width' => 3,
            ],
            'roof_slope' => [
                'cn' => '屋面坡度',
                'en' => 'Roof Slope',
                'type' => 'select',
                'name' => 'structure[roof_slope]',
                'ref_category' => 'roof_slope',
                'width' => 3,
            ],
            'bay_spacing' => [
                'cn' => '开间',
                'en' => 'Bay Spacing',
                'type' => 'text',
                'name' => 'structure[bay_spacing]',
                'width' => 3,
            ],
            'design_code' => [
                'cn' => '结构设计规范',
                'en' => 'Design Code',
                'type' => 'select',
                'name' => 'structure[design_code]',
                'ref_category' => 'design_code',
                'width' => 3,
            ],
            'seismic_zone' => [
                'cn' => '抗震设防烈度',
                'en' => 'Seismic Zone',
                'type' => 'select',
                'name' => 'structure[seismic_zone]',
                'ref_category' => 'seismic_zone',
                'width' => 3,
            ],
            'wind_speed' => [
                'cn' => '基本风压',
                'en' => 'Wind Speed',
                'type' => 'select',
                'name' => 'structure[wind_speed]',
                'ref_category' => 'wind_speed',
                'width' => 3,
            ],
            'snow_load' => [
                'cn' => '基本雪压',
                'en' => 'Snow Load',
                'type' => 'select',
                'name' => 'structure[snow_load]',
                'ref_category' => 'snow_load',
                'width' => 3,
            ],
            'dead_load' => [
                'cn' => '恒荷载',
                'en' => 'Dead Load',
                'type' => 'text',
                'name' => 'structure[dead_load]',
                'width' => 3,
            ],
            'live_load' => [
                'cn' => '活荷载',
                'en' => 'Live Load',
                'type' => 'text',
                'name' => 'structure[live_load]',
                'width' => 3,
            ],
        ],

        // ========== Section E: 报价范围 ==========
        'E' => [
            'scope_type' => [
                'cn' => '主次围材料',
                'en' => 'Include',
                'type' => 'select',
                'name' => 'scope[scope_type]',
                'ref_category' => 'scope_type',
                'width' => 3,
            ],
            'erection' => [
                'cn' => '安装',
                'en' => 'Erection',
                'type' => 'select',
                'name' => 'scope[erection]',
                'ref_category' => 'erection_type',
                'width' => 3,
            ],
            'erection_remarks' => [
                'cn' => '安装备注',
                'en' => 'Erection Remarks',
                'type' => 'text',
                'name' => 'scope[erection_remarks]',
                'width' => 6,
            ],
            // 开关选项
            'bridge_crane' => ['cn' => '天车', 'en' => 'Bridge Crane', 'type' => 'switch', 'name' => 'scope[bridge_crane]'],
            'pre_eng_building' => ['cn' => '预制工程建筑', 'en' => 'Pre Eng Building', 'type' => 'switch', 'name' => 'scope[pre_eng_building]'],
            'future_expansion' => ['cn' => '扩建', 'en' => 'Future Expansion', 'type' => 'switch', 'name' => 'scope[future_expansion]'],
            'door_window' => ['cn' => '门窗', 'en' => 'Door & Window', 'type' => 'switch', 'name' => 'scope[door_window]'],
            'louver' => ['cn' => '百叶窗', 'en' => 'Louver', 'type' => 'switch', 'name' => 'scope[louver]'],
            'partition_wall_frame' => ['cn' => '内隔墙', 'en' => 'Partition Wall', 'type' => 'switch', 'name' => 'scope[partition_wall_frame]'],
            'mezzanine_steels' => ['cn' => '夹层', 'en' => 'Mezzanine Steels', 'type' => 'switch', 'name' => 'scope[mezzanine_steels]'],
            'loading_canopy' => ['cn' => '大雨蓬', 'en' => 'Loading Canopy', 'type' => 'switch', 'name' => 'scope[loading_canopy]'],
            'railing' => ['cn' => '栏杆扶手', 'en' => 'Railing', 'type' => 'switch', 'name' => 'scope[railing]'],
            'steel_deck' => ['cn' => '楼面板', 'en' => 'Steel Deck', 'type' => 'switch', 'name' => 'scope[steel_deck]'],
            'cable_tray_support' => ['cn' => '管线吊架', 'en' => 'Cable Tray', 'type' => 'switch', 'name' => 'scope[cable_tray_support]'],
            'auxiliary' => ['cn' => '辅材', 'en' => 'Auxiliary', 'type' => 'switch', 'name' => 'scope[auxiliary]'],
            'factory_mutual' => ['cn' => 'Factory Mutual', 'en' => 'Factory Mutual', 'type' => 'switch', 'name' => 'scope[factory_mutual]'],
            'leed' => ['cn' => 'LEED', 'en' => 'LEED', 'type' => 'switch', 'name' => 'scope[leed]'],
            'laboratory_inspect' => ['cn' => '试验', 'en' => 'Laboratory', 'type' => 'switch', 'name' => 'scope[laboratory_inspect]'],
            'laboratory_remarks' => ['cn' => '检测备注', 'en' => 'Laboratory Remarks', 'type' => 'text', 'name' => 'scope[laboratory_remarks]', 'width' => 12],
        ],

        // ========== Section F: 建筑描述 & 钢结构材料 ==========
        'F' => [
            // F.1 建筑尺寸
            'F.1' => [
                'length' => ['cn' => '长度 (m)', 'en' => 'Length (m)', 'type' => 'number', 'name' => 'steel[length]', 'width' => 2],
                'length_source' => ['cn' => '长度来源', 'en' => 'Source', 'type' => 'text', 'name' => 'steel[length_source]', 'width' => 2],
                'width' => ['cn' => '宽度 (m)', 'en' => 'Width (m)', 'type' => 'number', 'name' => 'steel[width]', 'width' => 2],
                'width_source' => ['cn' => '宽度来源', 'en' => 'Source', 'type' => 'text', 'name' => 'steel[width_source]', 'width' => 2],
                'eave_height' => ['cn' => '檐口高度 (m)', 'en' => 'Eave Height (m)', 'type' => 'number', 'name' => 'steel[eave_height]', 'width' => 2],
                'eave_height_source' => ['cn' => '檐口高度来源', 'en' => 'Source', 'type' => 'text', 'name' => 'steel[eave_height_source]', 'width' => 2],
                'parapet_top_elevation' => ['cn' => '女儿墙顶标高 (m)', 'en' => 'Parapet Top Elev. (m)', 'type' => 'number', 'name' => 'steel[parapet_top_elevation]', 'width' => 2],
                'parapet_wall_liner' => ['cn' => '女儿墙内衬板', 'en' => 'Parapet Wall Liner', 'type' => 'text', 'name' => 'steel[parapet_wall_liner]', 'width' => 2],
                'mezzanine_floor_area' => ['cn' => '夹层范围', 'en' => 'Mezzanine Floor Areas', 'type' => 'text', 'name' => 'steel[mezzanine_floor_area]', 'width' => 4],
                'floor_elevation' => ['cn' => '楼面标高 (m)', 'en' => 'Floor Elevation (m)', 'type' => 'number', 'name' => 'steel[floor_elevation]', 'width' => 2],
                'floor_type' => ['cn' => '楼面形式', 'en' => 'Floor Type', 'type' => 'text', 'name' => 'steel[floor_type]', 'width' => 2],
            ],
            // F.2 主结构材料
            'F.2' => [
                'steel_grade' => ['cn' => '材质', 'en' => 'Steel Grade', 'type' => 'select', 'name' => 'steel[steel_grade]', 'ref_category' => 'steel_grade', 'width' => 2],
                'steel_manufacturer' => ['cn' => '原材料厂家', 'en' => 'Manufacturer', 'type' => 'select', 'name' => 'steel[steel_manufacturer]', 'ref_category' => 'steel_manufacturer', 'width' => 3],
                'processing_plant' => ['cn' => '加工厂', 'en' => 'Plant', 'type' => 'select', 'name' => 'steel[processing_plant]', 'ref_category' => 'processing_plant', 'width' => 2],
                'primer_type' => ['cn' => '底漆', 'en' => 'Primer Type', 'type' => 'select', 'name' => 'steel[primer_type]', 'ref_category' => 'primer_type', 'width' => 2],
                'primer_thickness' => ['cn' => '底漆厚度', 'en' => 'Primer Thickness', 'type' => 'select', 'name' => 'steel[primer_thickness]', 'ref_category' => 'primer_thickness', 'width' => 2],
                'primary_steel_note' => ['cn' => '主钢架其他要求', 'en' => 'Primary Steel Note', 'type' => 'textarea', 'name' => 'steel[primary_steel_note]', 'width' => 12],
            ],
            // F.3 中间漆+面漆
            'F.3' => [
                'intermediate_coat' => ['cn' => '中间漆', 'en' => 'Intermediate Coat', 'type' => 'select', 'name' => 'steel[intermediate_coat]', 'ref_category' => 'intermediate_coat', 'width' => 2],
                'intermediate_thickness' => ['cn' => '中间漆厚度', 'en' => 'Intermediate Thickness', 'type' => 'number', 'name' => 'steel[intermediate_thickness]', 'width' => 2],
                'top_coat_paint' => ['cn' => '面漆', 'en' => 'Top Coat Paint', 'type' => 'select', 'name' => 'steel[top_coat_paint]', 'ref_category' => 'top_coat_paint', 'width' => 2],
                'top_coat_thickness' => ['cn' => '面漆厚度', 'en' => 'Top Coat Thickness', 'type' => 'number', 'name' => 'steel[top_coat_thickness]', 'width' => 2],
                'paint_method' => ['cn' => '面漆涂刷方式', 'en' => 'Painting Method', 'type' => 'select', 'name' => 'steel[paint_method]', 'ref_category' => 'painting_method', 'width' => 2],
                'coating_scope' => ['cn' => '涂刷范围', 'en' => 'Coating Scope', 'type' => 'textarea', 'name' => 'steel[coating_scope]', 'width' => 12],
            ],
            // F.4 外露构件油漆
            'F.4' => [
                'exposed_paint' => ['cn' => '外露构件油漆', 'en' => 'Exposed Paint', 'type' => 'select', 'name' => 'steel[exposed_paint]', 'ref_category' => 'exposed_paint', 'width' => 3],
                'exposed_paint_scope' => ['cn' => '外露构件油漆范围', 'en' => 'Exposed Paint Scope', 'type' => 'text', 'name' => 'steel[exposed_paint_scope]', 'width' => 9],
            ],
            // F.5 防火涂料
            'F.5' => [
                'fire_coating_na' => ['cn' => '是否N/A', 'en' => 'N/A Option', 'type' => 'select', 'name' => 'steel[fire_coating_na]', 'options' => [['value' => '1', 'cn' => 'N/A 不适用', 'en' => 'N/A'], ['value' => '0', 'cn' => '需要防火涂料', 'en' => 'Required']], 'width' => 2],
                'fire_coating' => ['cn' => '防火涂料类型', 'en' => 'Fire Coating Type', 'type' => 'select', 'name' => 'steel[fire_coating]', 'ref_category' => 'fire_coating', 'width' => 3],
                'fire_coating_scope' => ['cn' => '防火涂料范围', 'en' => 'Fire Coating Scope', 'type' => 'text', 'name' => 'steel[fire_coating_scope]', 'width' => 7],
            ],
            // F.6 次结构材料
            'F.6' => [
                'secondary_manufacturer' => ['cn' => '原材料厂家', 'en' => 'Manufacturer', 'type' => 'select', 'name' => 'steel[secondary_manufacturer]', 'ref_category' => 'secondary_manufacturer', 'width' => 3],
                'roof_purlin_galvanized' => ['cn' => '屋面檩条镀锌', 'en' => 'Roof Purlin Galv.', 'type' => 'yesno', 'name' => 'steel[roof_purlin_galvanized]', 'width' => 2],
                'roof_purlin_paint' => ['cn' => '屋面檩条油漆', 'en' => 'Roof Purlin Paint', 'type' => 'select', 'name' => 'steel[roof_purlin_paint]', 'ref_category' => 'purlin_paint', 'width' => 2],
                'wall_purlin_galvanized' => ['cn' => '墙面檩条镀锌', 'en' => 'Wall Purlin Galv.', 'type' => 'yesno', 'name' => 'steel[wall_purlin_galvanized]', 'width' => 2],
                'wall_purlin_paint' => ['cn' => '墙面檩条油漆', 'en' => 'Wall Purlin Paint', 'type' => 'select', 'name' => 'steel[wall_purlin_paint]', 'ref_category' => 'purlin_paint', 'width' => 2],
            ],
            // F.7 花纹钢板
            'F.7' => [
                'checkered_plate_paint' => ['cn' => '处理方式', 'en' => 'Treatment', 'type' => 'select', 'name' => 'steel[checkered_plate_paint]', 'ref_category' => 'checkered_plate', 'width' => 3],
                'checkered_plate_scope' => ['cn' => '范围', 'en' => 'Scope', 'type' => 'text', 'name' => 'steel[checkered_plate_scope]', 'width' => 5],
                'checkered_plate_remarks' => ['cn' => '备注', 'en' => 'Remarks', 'type' => 'text', 'name' => 'steel[checkered_plate_remarks]', 'width' => 4],
            ],
            // 其他要求
            'other_requirements' => ['cn' => '其他要求', 'en' => 'Other Requirements', 'type' => 'textarea', 'name' => 'steel[other_requirements]', 'width' => 12],
        ],

        // ========== Section H: 围护系统配置 ==========
        'H' => [
            // H.1 屋墙面材料
            'H.1' => [
                'wall_material' => ['cn' => '材料类型', 'en' => 'Material Type', 'type' => 'select', 'name' => 'envelope[wall_material]', 'ref_category' => 'wall_material', 'width' => 3],
                'material_remarks' => ['cn' => '备注', 'en' => 'Remarks', 'type' => 'text', 'name' => 'envelope[material_remarks]', 'width' => 9],
            ],
            // H.2 改造项目
            'H.2' => [
                'is_renovation' => ['cn' => '改造项目', 'en' => 'Is Renovation', 'type' => 'yesno', 'name' => 'envelope[is_renovation]', 'width' => 2],
                'structural_reinforcement' => ['cn' => '结构加固', 'en' => 'Structural Reinforcement', 'type' => 'switch', 'name' => 'envelope[structural_reinforcement]'],
                'cladding_addition' => ['cn' => '围护板加建', 'en' => 'Cladding Addition', 'type' => 'switch', 'name' => 'envelope[cladding_addition]'],
                'reuse' => ['cn' => '利旧', 'en' => 'Reuse', 'type' => 'switch', 'name' => 'envelope[reuse]'],
                'mep_installation' => ['cn' => '机电安装', 'en' => 'MEP Installation', 'type' => 'switch', 'name' => 'envelope[mep_installation]'],
                'renovation_other' => ['cn' => '其他', 'en' => 'Other', 'type' => 'switch', 'name' => 'envelope[renovation_other]'],
                'renovation_remarks' => ['cn' => '备注&补充', 'en' => 'Renovation Remarks', 'type' => 'textarea', 'name' => 'envelope[renovation_remarks]', 'width' => 12],
            ],
            // H.3 防水规范
            'H.3' => [
                'waterproof_standard' => ['cn' => '考虑GB55030-2022', 'en' => 'GB55030-2022', 'type' => 'hidden', 'name' => 'envelope[waterproof_standard]'],
                'waterproof_remarks' => ['cn' => '备注', 'en' => 'Remarks', 'type' => 'text', 'name' => 'envelope[waterproof_remarks]', 'width' => 12],
            ],
            // H.4 屋面特殊配置
            'H.4' => [
                'aclok_roof' => ['cn' => 'Aclok铝镁锰屋面板', 'en' => 'ACLOK Aluminum Roof', 'type' => 'switch', 'name' => 'envelope[aclok_roof]', 'hint_cn' => '1、带抗风夹（公司规定，必须考虑）；2、厚度必须>=0.90mm', 'hint_en' => '1. With wind clips (required); 2. Thickness >= 0.90mm'],
                'sandwich_panel' => ['cn' => '夹芯板', 'en' => 'Sandwich Panel', 'type' => 'switch', 'name' => 'envelope[sandwich_panel]'],
                'sandwich_remarks' => ['cn' => '夹芯板备注', 'en' => 'Sandwich Panel Remarks', 'type' => 'text', 'name' => 'envelope[sandwich_remarks]', 'width' => 9],
                'roof_ventilator' => ['cn' => '屋面通风器', 'en' => 'Roof Ventilator', 'type' => 'switch', 'name' => 'envelope[roof_ventilator]'],
                'roof_opening' => ['cn' => '屋面开口', 'en' => 'Roof Opening', 'type' => 'switch', 'name' => 'envelope[roof_opening]'],
                'ventilator_requirements' => ['cn' => '尺寸及其他要求', 'en' => 'Size & Requirements', 'type' => 'text', 'name' => 'envelope[ventilator_requirements]', 'width' => 6],
                'roof_skylight' => ['cn' => '屋面气楼&天窗', 'en' => 'Roof Skylight/Monitor', 'type' => 'switch', 'name' => 'envelope[roof_skylight]'],
                'skylight_requirements' => ['cn' => '尺寸及其他要求', 'en' => 'Skylight Requirements', 'type' => 'text', 'name' => 'envelope[skylight_requirements]', 'width' => 9],
                'roof_ridge_lantern' => ['cn' => '屋脊气楼/天窗', 'en' => 'Ridge Skylight/Lantern', 'type' => 'switch', 'name' => 'envelope[roof_ridge_lantern]'],
                'roof_ridge_lantern_remarks' => ['cn' => '屋脊气楼备注及尺寸要求', 'en' => 'Ridge Lantern Remarks', 'type' => 'text', 'name' => 'envelope[roof_ridge_lantern_remarks]', 'width' => 9],
                'pv_system' => ['cn' => 'LS585屋面光伏系统', 'en' => 'LS585 PV System', 'type' => 'switch', 'name' => 'envelope[pv_system]'],
                'pv_requirements' => ['cn' => '其他要求', 'en' => 'Other Requirements', 'type' => 'text', 'name' => 'envelope[pv_requirements]', 'width' => 9],
            ],
        ],

        // ========== Section I: 屋面系统材质要求 ==========
        'I' => [
            // I.4 防水透气膜/隔汽膜/钢丝网
            'I.4' => [
                'roof_waterproof_membrane' => ['cn' => '屋面防水透气膜', 'en' => 'Waterproof Membrane', 'type' => 'select', 'name' => 'envelope[roof_waterproof_membrane]', 'ref_category' => 'waterproof_membrane', 'width' => 3],
                'roof_vapor_barrier' => ['cn' => '屋面隔汽膜', 'en' => 'Vapor Barrier', 'type' => 'select', 'name' => 'envelope[roof_vapor_barrier]', 'ref_category' => 'vapor_barrier', 'width' => 3],
                'roof_wire_mesh' => ['cn' => '屋面钢丝网', 'en' => 'Roof Wire Mesh', 'type' => 'select', 'name' => 'envelope[roof_wire_mesh]', 'ref_category' => 'wire_mesh', 'width' => 3],
            ],
            // I.5 屋面内衬板备注
            'I.5' => [
                'roof_liner_layout' => ['cn' => '内衬板布置', 'en' => 'Liner Layout', 'type' => 'text', 'name' => 'envelope[roof_liner_layout]', 'width' => 6],
                'roof_liner_remarks' => ['cn' => '备注', 'en' => 'Remarks', 'type' => 'text', 'name' => 'envelope[roof_liner_remarks]', 'width' => 6],
            ],
            // I.6 大雨蓬板备注
            'I.6' => [
                'canopy_has_insulation' => ['cn' => '大雨蓬有保温棉', 'en' => 'Canopy Insulation', 'type' => 'yesno', 'name' => 'envelope[canopy_has_insulation]', 'width' => 3],
                'canopy_insulation_remarks' => ['cn' => '保温棉备注', 'en' => 'Insulation Remarks', 'type' => 'text', 'name' => 'envelope[canopy_insulation_remarks]', 'width' => 9],
            ],
            // I.7 标准小雨蓬
            'I.7' => [
                'small_canopy_width' => ['cn' => '悬挑宽度 (mm)', 'en' => 'Canopy Width (mm)', 'type' => 'number', 'name' => 'envelope[small_canopy_width]', 'width' => 2],
                'small_canopy_method' => ['cn' => '做法', 'en' => 'Method', 'type' => 'select', 'name' => 'envelope[small_canopy_method]', 'ref_category' => 'small_canopy_method', 'width' => 3],
                'small_canopy_drainage' => ['cn' => '排水做法', 'en' => 'Drainage', 'type' => 'select', 'name' => 'envelope[small_canopy_drainage]', 'ref_category' => 'small_canopy_drainage', 'width' => 3],
                'small_canopy_remarks' => ['cn' => '小雨蓬备注', 'en' => 'Small Canopy Remarks', 'type' => 'text', 'name' => 'envelope[small_canopy_remarks]', 'width' => 4],
            ],
            // I.8 屋面采光
            'I.8' => [
                'skylight_material' => ['cn' => '材料', 'en' => 'Material', 'type' => 'select', 'name' => 'envelope[skylight_material]', 'ref_category' => 'skylight_material', 'width' => 2],
                'skylight_layout' => ['cn' => '铺设方式', 'en' => 'Layout', 'type' => 'select', 'name' => 'envelope[skylight_layout]', 'ref_category' => 'skylight_layout', 'width' => 2],
                'skylight_length' => ['cn' => '长度', 'en' => 'Length', 'type' => 'text', 'name' => 'envelope[skylight_length]', 'width' => 2],
                'skylight_brand' => ['cn' => '品牌', 'en' => 'Brand', 'type' => 'select', 'name' => 'envelope[skylight_brand]', 'ref_category' => 'skylight_brand', 'width' => 2],
                'skylight_fm_certified' => ['cn' => 'FM认证', 'en' => 'FM Certified', 'type' => 'yesno', 'name' => 'envelope[skylight_fm_certified]', 'width' => 2],
                'skylight_panel_remarks' => ['cn' => '采光板备注', 'en' => 'Skylight Remarks', 'type' => 'text', 'name' => 'envelope[skylight_panel_remarks]', 'width' => 6],
                'skylight_other_requirements' => ['cn' => '采光板其他要求', 'en' => 'Skylight Other Requirements', 'type' => 'textarea', 'name' => 'envelope[skylight_other_requirements]', 'width' => 12],
            ],
        ],

        // ========== Section J: 墙面系统材质要求 ==========
        'J' => [
            // J.1 墙面配置
            'J.1' => [
                'wall_outer_layout' => ['cn' => '外板铺设方式', 'en' => 'Outer Panel Layout', 'type' => 'select', 'name' => 'envelope[wall_outer_layout]', 'ref_category' => 'panel_layout', 'width' => 3],
                'wall_outer_curb_height' => ['cn' => '外板墙裙高度 (m)', 'en' => 'Outer Curb Height (m)', 'type' => 'number', 'name' => 'envelope[wall_outer_curb_height]', 'width' => 2],
                'wall_liner_layout' => ['cn' => '内板铺设方式', 'en' => 'Liner Panel Layout', 'type' => 'select', 'name' => 'envelope[wall_liner_layout]', 'ref_category' => 'panel_layout', 'width' => 3],
                'wall_liner_curb_height' => ['cn' => '内板墙裙高度 (m)', 'en' => 'Liner Curb Height (m)', 'type' => 'number', 'name' => 'envelope[wall_liner_curb_height]', 'width' => 2],
            ],
            // J.4 墙面防水透气膜/隔汽膜/钢丝网
            'J.4' => [
                'wall_waterproof_membrane' => ['cn' => '墙面防水透气膜', 'en' => 'Wall Waterproof Membrane', 'type' => 'select', 'name' => 'envelope[wall_waterproof_membrane]', 'ref_category' => 'waterproof_membrane', 'width' => 3],
                'wall_vapor_barrier' => ['cn' => '墙面隔汽膜', 'en' => 'Wall Vapor Barrier', 'type' => 'select', 'name' => 'envelope[wall_vapor_barrier]', 'ref_category' => 'vapor_barrier', 'width' => 3],
                'wall_wire_mesh' => ['cn' => '墙面钢丝网', 'en' => 'Wall Wire Mesh', 'type' => 'select', 'name' => 'envelope[wall_wire_mesh]', 'ref_category' => 'wire_mesh', 'width' => 3],
            ],
        ],

        // ========== Section L: 状态 ==========
        'L' => [
            'status' => ['cn' => 'RFQ 状态', 'en' => 'RFQ Status', 'type' => 'select', 'name' => 'main[status]', 'ref_category' => 'rfq_status', 'width' => 3],
        ],
    ];
}

/**
 * 板材动态表格字段
 */
function getPanelFields() {
    return [
        'thickness' => ['cn' => '厚度', 'en' => 'Thickness', 'type' => 'number', 'placeholder' => 'mm'],
        'profile' => ['cn' => '板型', 'en' => 'Profile', 'type' => 'select', 'ref_category' => ['roof_outer' => 'roof_outer_profile', 'roof_liner' => 'roof_liner_profile', 'wall_outer' => 'wall_outer_profile', 'wall_liner' => 'wall_liner_profile']],
        'strength' => ['cn' => '强度', 'en' => 'Strength', 'type' => 'select', 'ref_category' => 'panel_strength'],
        'coating' => ['cn' => '涂层', 'en' => 'Coating', 'type' => 'select', 'ref_category' => 'panel_coating'],
        'galvanizing' => ['cn' => '镀锌量', 'en' => 'Galvanizing', 'type' => 'select', 'ref_category' => 'panel_galvanizing'],
        'color' => ['cn' => '颜色', 'en' => 'Color', 'type' => 'select', 'ref_category' => 'panel_color'],
        'origin' => ['cn' => '原产地', 'en' => 'Origin', 'type' => 'select', 'ref_category' => 'panel_origin'],
        'scope' => ['cn' => '范围', 'en' => 'Scope', 'type' => 'text'],
    ];
}

/**
 * 保温棉动态表格字段
 */
function getInsulationFields() {
    return [
        'thickness' => ['cn' => '厚度', 'en' => 'Thickness', 'type' => 'number', 'placeholder' => 'mm'],
        'density' => ['cn' => '容重', 'en' => 'Density', 'type' => 'select', 'ref_category' => 'insulation_density'],
        'facing' => ['cn' => '贴面', 'en' => 'Facing', 'type' => 'select', 'ref_category' => 'insulation_facing'],
        'flame_retardant' => ['cn' => '阻燃等级', 'en' => 'Flame Retardant', 'type' => 'select', 'ref_category' => 'flame_retardant'],
        'color' => ['cn' => '颜色', 'en' => 'Color', 'type' => 'select', 'ref_category' => 'insulation_color'],
        'brand' => ['cn' => '品牌', 'en' => 'Brand', 'type' => 'select', 'ref_category' => 'insulation_brand'],
        'scope' => ['cn' => '范围', 'en' => 'Scope', 'type' => 'text'],
        'other_requirements' => ['cn' => '其他要求', 'en' => 'Other Requirements', 'type' => 'text'],
    ];
}

/**
 * 排水动态表格字段
 */
function getDrainageFields() {
    return [
        'drainage_type' => ['cn' => '类型', 'en' => 'Type', 'type' => 'select', 'options' => [['value' => 'roof_1', 'cn' => '屋面1', 'en' => 'Roof 1'], ['value' => 'roof_2', 'cn' => '屋面2', 'en' => 'Roof 2'], ['value' => 'canopy', 'cn' => '雨蓬', 'en' => 'Canopy']]],
        'method' => ['cn' => '排水方式', 'en' => 'Drainage Method', 'type' => 'select', 'ref_category' => 'drainage_method'],
        'scope' => ['cn' => '范围', 'en' => 'Scope', 'type' => 'text'],
        'gutter_spec' => ['cn' => '天沟规格', 'en' => 'Gutter Spec', 'type' => 'text', 'placeholder_cn' => '如：1.2mm不锈钢', 'placeholder_en' => 'e.g., 1.2mm SS'],
        'downpipe_type' => ['cn' => '落水管类型', 'en' => 'Downspout Type', 'type' => 'select', 'ref_category' => 'downpipe_type'],
    ];
}

/**
 * 做法动态表格字段
 */
function getMethodFields() {
    return [
        'method_category' => ['cn' => '类别', 'en' => 'Category', 'type' => 'hidden'],
        'method_no' => ['cn' => '序号', 'en' => 'No.', 'type' => 'hidden'],
        'method_code' => ['cn' => '做法代码', 'en' => 'Method Code', 'type' => 'select'],
        'method_desc' => ['cn' => '做法描述', 'en' => 'Method Description', 'type' => 'text'],
        'scope' => ['cn' => '适用范围', 'en' => 'Scope/Range', 'type' => 'text'],
    ];
}

/**
 * 补充说明动态表格字段
 */
function getSupplementFields() {
    return [
        'category' => [
            'cn' => '类别',
            'en' => 'Category',
            'type' => 'select',
            'options' => [
                ['value' => 'general', 'cn' => '通用', 'en' => 'General'],
                ['value' => 'steel', 'cn' => '钢结构', 'en' => 'Steel'],
                ['value' => 'envelope', 'cn' => '围护', 'en' => 'Envelope'],
                ['value' => 'load', 'cn' => '荷载', 'en' => 'Load'],
                ['value' => 'site', 'cn' => '现场', 'en' => 'Site'],
                ['value' => 'schedule', 'cn' => '进度', 'en' => 'Schedule'],
                ['value' => 'commercial', 'cn' => '商务', 'en' => 'Commercial'],
                ['value' => 'other', 'cn' => '其他', 'en' => 'Other'],
            ]
        ],
        'importance' => [
            'cn' => '重要程度',
            'en' => 'Importance',
            'type' => 'select',
            'options' => [
                ['value' => 'normal', 'cn' => '普通', 'en' => 'Normal', 'class' => 'secondary'],
                ['value' => 'important', 'cn' => '重要', 'en' => 'Important', 'class' => 'warning'],
                ['value' => 'critical', 'cn' => '关键', 'en' => 'Critical', 'class' => 'danger'],
            ]
        ],
        'title' => ['cn' => '标题', 'en' => 'Title', 'type' => 'text', 'placeholder_cn' => '标题（可选）', 'placeholder_en' => 'Title (optional)'],
        'related_section' => [
            'cn' => '关联区域',
            'en' => 'Related',
            'type' => 'select',
            'options' => [
                ['value' => '', 'cn' => '-- 关联区域 --', 'en' => '-- Related --'],
                ['value' => 'basic', 'cn' => '基本信息', 'en' => 'Basic Info'],
                ['value' => 'steel', 'cn' => '钢结构', 'en' => 'Steel'],
                ['value' => 'envelope', 'cn' => '围护系统', 'en' => 'Envelope'],
                ['value' => 'cladding', 'cn' => '板材规格', 'en' => 'Cladding'],
            ]
        ],
        'sort_order' => ['cn' => '排序', 'en' => 'Order', 'type' => 'number', 'min' => 0, 'placeholder' => '#'],
        'content' => ['cn' => '内容', 'en' => 'Content', 'type' => 'textarea', 'placeholder_cn' => '输入补充说明内容...', 'placeholder_en' => 'Enter supplement content...'],
    ];
}

/**
 * 获取Section信息
 */
function getSection($sectionKey) {
    $sections = getSections();
    return $sections[$sectionKey] ?? null;
}

/**
 * 获取字段信息
 */
function getField($sectionKey, $fieldKey, $subsectionKey = null) {
    $fields = getFields();
    if (!isset($fields[$sectionKey])) return null;

    if ($subsectionKey && isset($fields[$sectionKey][$subsectionKey][$fieldKey])) {
        return $fields[$sectionKey][$subsectionKey][$fieldKey];
    }

    if (isset($fields[$sectionKey][$fieldKey])) {
        return $fields[$sectionKey][$fieldKey];
    }

    return null;
}

/**
 * 获取字段标签
 */
function getFieldLabel($sectionKey, $fieldKey, $lang = 'both', $subsectionKey = null) {
    $field = getField($sectionKey, $fieldKey, $subsectionKey);
    if (!$field) return $fieldKey;

    switch ($lang) {
        case 'en':
            return $field['en'];
        case 'cn':
            return $field['cn'];
        default:
            return $field['cn'] . ' ' . $field['en'];
    }
}

/**
 * 获取Section标题
 */
function getSectionTitle($sectionKey, $lang = 'both') {
    $section = getSection($sectionKey);
    if (!$section) return $sectionKey;

    switch ($lang) {
        case 'en':
            return $section['en'];
        case 'cn':
            return $section['cn'];
        default:
            return $section['cn'] . ' ' . $section['en'];
    }
}

/**
 * 获取Subsection标题
 */
function getSubsectionTitle($sectionKey, $subsectionKey, $lang = 'both') {
    $section = getSection($sectionKey);
    if (!$section || !isset($section['subsections'][$subsectionKey])) return $subsectionKey;

    $sub = $section['subsections'][$subsectionKey];
    switch ($lang) {
        case 'en':
            return $sub['en'];
        case 'cn':
            return $sub['cn'];
        default:
            return $sub['cn'] . ' ' . $sub['en'];
    }
}
