<?php
/**
 * RFQ Unified Rendering Functions
 * LSB RFQ System V3.2
 *
 * This file provides unified rendering functions that both form UI
 * and PDF print can use to ensure consistency.
 */

require_once __DIR__ . '/rfq_schema.php';

/**
 * Load the schema
 */
function rfqGetSchema() {
    static $schema = null;
    if ($schema === null) {
        $schema = getRfqSchema();
    }
    return $schema;
}

/**
 * Get field label based on language setting
 *
 * @param string $fieldKey Field key from schema
 * @param string|null $lang Language ('en', 'cn', 'both'), null = use global
 * @return string The label text
 */
function rfqFieldLabel($fieldKey, $lang = null) {
    $schema = rfqGetSchema();
    if ($lang === null) {
        $lang = getLang();
    }

    $field = $schema['fields'][$fieldKey] ?? null;
    if (!$field) {
        return $fieldKey;
    }

    $cn = $field['cn'] ?? $fieldKey;
    $en = $field['en'] ?? $fieldKey;

    if ($lang === 'en') {
        return $en;
    } elseif ($lang === 'cn') {
        return $cn;
    }
    return $cn . ' ' . $en;
}

/**
 * Get field label HTML for form display
 *
 * @param string $fieldKey Field key from schema
 * @param string|null $lang Language
 * @return string HTML for label
 */
function rfqFieldLabelHtml($fieldKey, $lang = null) {
    $schema = rfqGetSchema();
    if ($lang === null) {
        $lang = getLang();
    }

    $field = $schema['fields'][$fieldKey] ?? null;
    if (!$field) {
        return htmlspecialchars($fieldKey);
    }

    $cn = htmlspecialchars($field['cn'] ?? $fieldKey);
    $en = htmlspecialchars($field['en'] ?? $fieldKey);

    if ($lang === 'en') {
        return $en;
    } elseif ($lang === 'cn') {
        return $cn;
    }
    // both mode: use span for styling
    return '<span class="form-label-cn">' . $cn . '</span><span class="form-label-en">' . $en . '</span>';
}

/**
 * Get section title based on language setting
 *
 * @param string $sectionKey Section letter (A, B, C, etc.)
 * @param string|null $lang Language
 * @return string The section title
 */
function rfqSectionTitle($sectionKey, $lang = null) {
    $schema = rfqGetSchema();
    if ($lang === null) {
        $lang = getLang();
    }

    $section = $schema['sections'][$sectionKey] ?? null;
    if (!$section) {
        return $sectionKey;
    }

    $cn = $section['cn'] ?? $sectionKey;
    $en = $section['en'] ?? $sectionKey;

    if ($lang === 'en') {
        return $en;
    } elseif ($lang === 'cn') {
        return $cn;
    }
    return $cn . ' ' . $en;
}

/**
 * Get subsection title based on language setting
 *
 * @param string $subsectionKey Subsection number (e.g., 'F.1', 'H.2')
 * @param string|null $lang Language
 * @return string The subsection title
 */
function rfqSubsectionTitle($subsectionKey, $lang = null) {
    $schema = rfqGetSchema();
    if ($lang === null) {
        $lang = getLang();
    }

    // Extract section letter from subsection key (e.g., 'F' from 'F.1')
    $sectionLetter = substr($subsectionKey, 0, 1);
    $section = $schema['sections'][$sectionLetter] ?? null;

    if (!$section || !isset($section['subsections'][$subsectionKey])) {
        return $subsectionKey;
    }

    $subsection = $section['subsections'][$subsectionKey];
    $cn = $subsection['cn'] ?? $subsectionKey;
    $en = $subsection['en'] ?? $subsectionKey;

    if ($lang === 'en') {
        return $en;
    } elseif ($lang === 'cn') {
        return $cn;
    }
    return $cn . ' ' . $en;
}

/**
 * Get category label for supplements/remarks
 *
 * @param string $category Category key
 * @param string|null $lang Language
 * @return string The category label
 */
function rfqCategoryLabel($category, $lang = null) {
    $schema = rfqGetSchema();
    if ($lang === null) {
        $lang = getLang();
    }

    $cat = $schema['supplement_categories'][$category] ?? null;
    if (!$cat) {
        return $category;
    }

    if ($lang === 'en') {
        return $cat['en'];
    } elseif ($lang === 'cn') {
        return $cat['cn'];
    }
    return $cat['cn'] . ' ' . $cat['en'];
}

/**
 * Get cladding system label
 *
 * @param string $system System key ('roof', 'wall')
 * @param string|null $lang Language
 * @return string The system label
 */
function rfqCladdingSystemLabel($system, $lang = null) {
    $schema = rfqGetSchema();
    if ($lang === null) {
        $lang = getLang();
    }

    $sys = $schema['cladding_systems'][$system] ?? null;
    if (!$sys) {
        return $system;
    }

    if ($lang === 'en') {
        return $sys['en'];
    } elseif ($lang === 'cn') {
        return $sys['cn'];
    }
    return $sys['cn'] . ' ' . $sys['en'];
}

/**
 * Get cladding layer label
 *
 * @param string $layer Layer key ('outer', 'liner', 'insulation')
 * @param string|null $lang Language
 * @return string The layer label
 */
function rfqCladdingLayerLabel($layer, $lang = null) {
    $schema = rfqGetSchema();
    if ($lang === null) {
        $lang = getLang();
    }

    $lyr = $schema['cladding_layers'][$layer] ?? null;
    if (!$lyr) {
        return $layer;
    }

    if ($lang === 'en') {
        return $lyr['en'];
    } elseif ($lang === 'cn') {
        return $lyr['cn'];
    }
    return $lyr['cn'] . ' ' . $lyr['en'];
}

/**
 * Format a field value for display
 *
 * @param string $fieldKey Field key from schema
 * @param mixed $value The raw value
 * @param string|null $lang Language
 * @return string Formatted value for display
 */
function rfqFormatValue($fieldKey, $value, $lang = null) {
    if ($value === null || $value === '') {
        return '';
    }

    $schema = rfqGetSchema();
    $field = $schema['fields'][$fieldKey] ?? null;

    if (!$field) {
        return htmlspecialchars($value);
    }

    // Handle different field types
    switch ($field['type'] ?? 'text') {
        case 'checkbox':
            return rfqFormatYesNo($value, $lang);

        case 'select':
            if (isset($field['ref_category'])) {
                return getRefValue($field['ref_category'], $value, $lang);
            } elseif (isset($field['options'])) {
                return $field['options'][$value] ?? $value;
            }
            return htmlspecialchars($value);

        case 'date':
            return rfqFormatDate($value, $lang);

        default:
            // Add unit if specified
            $formatted = htmlspecialchars($value);
            if (isset($field['unit']) && $value !== '') {
                $formatted .= ' ' . $field['unit'];
            }
            return $formatted;
    }
}

/**
 * Format Yes/No value
 *
 * @param mixed $value The value (truthy/falsy)
 * @param string|null $lang Language
 * @return string Formatted Yes/No text
 */
function rfqFormatYesNo($value, $lang = null) {
    if ($lang === null) {
        $lang = getLang();
    }

    if ($value === null || $value === '') {
        return 'N/A';
    }

    if ($value) {
        return $lang === 'en' ? 'Yes' : ($lang === 'cn' ? '是' : '是 Yes');
    }
    return $lang === 'en' ? 'No' : ($lang === 'cn' ? '否' : '否 No');
}

/**
 * Format date value
 *
 * @param string $value Date string
 * @param string|null $lang Language
 * @return string Formatted date
 */
function rfqFormatDate($value, $lang = null) {
    if (empty($value)) {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return htmlspecialchars($value);
    }

    if ($lang === null) {
        $lang = getLang();
    }

    // Use ISO format for simplicity
    return date('Y-m-d', $timestamp);
}

/**
 * Get field info from schema
 *
 * @param string $fieldKey Field key
 * @return array|null Field info array or null if not found
 */
function rfqGetField($fieldKey) {
    $schema = rfqGetSchema();
    return $schema['fields'][$fieldKey] ?? null;
}

/**
 * Get section info from schema
 *
 * @param string $sectionKey Section letter
 * @return array|null Section info array or null if not found
 */
function rfqGetSection($sectionKey) {
    $schema = rfqGetSchema();
    return $schema['sections'][$sectionKey] ?? null;
}

/**
 * Get all sections
 *
 * @return array All sections from schema
 */
function rfqGetAllSections() {
    $schema = rfqGetSchema();
    return $schema['sections'];
}

/**
 * Get fields for a specific section
 *
 * @param string $sectionKey Section letter
 * @param string|null $subsectionKey Optional subsection key
 * @return array Array of field definitions
 */
function rfqGetFieldsForSection($sectionKey, $subsectionKey = null) {
    $schema = rfqGetSchema();
    $fields = [];

    foreach ($schema['fields'] as $key => $field) {
        if ($field['section'] === $sectionKey) {
            if ($subsectionKey === null || ($field['subsection'] ?? null) === $subsectionKey) {
                $fields[$key] = $field;
            }
        }
    }

    return $fields;
}

/**
 * Get value from data array using field definition
 *
 * @param array $data Data arrays (main, steel, envelope, etc.)
 * @param string $fieldKey Field key
 * @return mixed The field value
 */
function rfqGetValue($data, $fieldKey) {
    $field = rfqGetField($fieldKey);
    if (!$field) {
        return null;
    }

    $table = $field['table'] ?? 'main';

    // Map table names to data keys
    $tableMap = [
        'main' => 'main',
        'order_entry' => 'order_entry',
        'steel' => 'steel',
        'envelope' => 'envelope',
    ];

    $dataKey = $tableMap[$table] ?? $table;

    if (!isset($data[$dataKey])) {
        return null;
    }

    return $data[$dataKey][$fieldKey] ?? null;
}

/**
 * Render a form field input (for form UI)
 *
 * @param string $fieldKey Field key
 * @param mixed $value Current value
 * @param string $namePrefix Name attribute prefix (e.g., 'main', 'steel')
 * @param string|null $lang Language
 * @return string HTML for the input
 */
function rfqRenderFormField($fieldKey, $value, $namePrefix, $lang = null) {
    $field = rfqGetField($fieldKey);
    if (!$field) {
        return '';
    }

    $type = $field['type'] ?? 'text';
    $name = $namePrefix . '[' . $fieldKey . ']';
    $id = str_replace(['[', ']'], '_', $name);
    $value = htmlspecialchars($value ?? $field['default'] ?? '');
    $readonly = !empty($field['readonly']) ? 'readonly' : '';
    $class = 'form-control';

    switch ($type) {
        case 'select':
            $html = '<select class="form-select" name="' . $name . '" id="' . $id . '">';
            $html .= '<option value="">' . ($lang === 'en' ? '-- Select --' : '-- 请选择 --') . '</option>';

            if (isset($field['ref_category'])) {
                foreach (getRefOptions($field['ref_category'], $lang) as $opt) {
                    $selected = ($value == $opt['value']) ? 'selected' : '';
                    $html .= '<option value="' . htmlspecialchars($opt['value']) . '" ' . $selected . '>';
                    $html .= htmlspecialchars($opt['label']);
                    $html .= '</option>';
                }
            } elseif (isset($field['options'])) {
                foreach ($field['options'] as $optVal => $optLabel) {
                    $selected = ($value == $optVal) ? 'selected' : '';
                    $html .= '<option value="' . htmlspecialchars($optVal) . '" ' . $selected . '>';
                    $html .= htmlspecialchars($optLabel);
                    $html .= '</option>';
                }
            }

            $html .= '</select>';
            return $html;

        case 'checkbox':
            $checked = $value ? 'checked' : '';
            return '<div class="form-check form-switch">'
                . '<input class="form-check-input" type="checkbox" role="switch" '
                . 'name="' . $name . '" id="' . $id . '" value="1" ' . $checked . '>'
                . '<label class="form-check-label" for="' . $id . '">'
                . rfqFieldLabel($fieldKey, $lang)
                . '</label></div>';

        case 'textarea':
            return '<textarea class="' . $class . '" name="' . $name . '" id="' . $id . '" '
                . 'rows="2" ' . $readonly . '>' . $value . '</textarea>';

        case 'date':
            return '<input type="date" class="' . $class . '" name="' . $name . '" '
                . 'id="' . $id . '" value="' . $value . '" ' . $readonly . '>';

        case 'number':
            $min = isset($field['min']) ? 'min="' . $field['min'] . '"' : '';
            return '<input type="number" class="' . $class . '" name="' . $name . '" '
                . 'id="' . $id . '" value="' . $value . '" ' . $min . ' ' . $readonly . '>';

        case 'email':
            return '<input type="email" class="' . $class . '" name="' . $name . '" '
                . 'id="' . $id . '" value="' . $value . '" ' . $readonly . '>';

        default: // text
            return '<input type="text" class="' . $class . '" name="' . $name . '" '
                . 'id="' . $id . '" value="' . $value . '" ' . $readonly . '>';
    }
}

/**
 * Render a print field value (for PDF)
 *
 * @param string $fieldKey Field key
 * @param mixed $value Raw value
 * @param string|null $lang Language
 * @return string Formatted value for print
 */
function rfqRenderPrintValue($fieldKey, $value, $lang = null) {
    return rfqFormatValue($fieldKey, $value, $lang);
}

/**
 * Check if a field should be displayed based on conditions
 *
 * @param string $fieldKey Field key
 * @param array $data All data arrays
 * @return bool True if field should be shown
 */
function rfqShouldShowField($fieldKey, $data) {
    $field = rfqGetField($fieldKey);
    if (!$field) {
        return false;
    }

    // Check show_if condition
    if (isset($field['show_if'])) {
        $condition = $field['show_if'];
        $conditionField = $condition['field'] ?? null;
        $conditionValue = $condition['value'] ?? null;

        if ($conditionField) {
            $actualValue = rfqGetValue($data, $conditionField);
            if ($actualValue != $conditionValue) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Check if a section has any data to display
 *
 * @param string $sectionKey Section letter
 * @param array $data All data arrays
 * @return bool True if section has data
 */
function rfqSectionHasData($sectionKey, $data) {
    $fields = rfqGetFieldsForSection($sectionKey);

    foreach ($fields as $key => $field) {
        $value = rfqGetValue($data, $key);
        if ($value !== null && $value !== '') {
            return true;
        }
    }

    return false;
}

/**
 * Validate that form data matches expected schema
 *
 * @param array $data Form data
 * @return array Validation errors (empty if valid)
 */
function rfqValidateData($data) {
    $schema = rfqGetSchema();
    $errors = [];

    foreach ($schema['fields'] as $key => $field) {
        $value = rfqGetValue($data, $key);

        // Check required fields
        if (!empty($field['required']) && ($value === null || $value === '')) {
            $errors[$key] = 'Field ' . $key . ' is required';
        }

        // Check field type validation
        if ($value !== null && $value !== '') {
            switch ($field['type'] ?? 'text') {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$key] = 'Invalid email format';
                    }
                    break;
                case 'number':
                    if (!is_numeric($value)) {
                        $errors[$key] = 'Value must be a number';
                    }
                    break;
                case 'date':
                    if (strtotime($value) === false) {
                        $errors[$key] = 'Invalid date format';
                    }
                    break;
            }
        }
    }

    return $errors;
}

/**
 * Universal label function with fallback support
 *
 * This function provides a unified way to get labels with multiple fallback sources:
 * 1. Check rfq_schema.php fields
 * 2. Check rfq_schema.php sections/subsections
 * 3. Check labels.json
 * 4. Use provided default values
 *
 * @param string $cn Chinese label (fallback)
 * @param string $en English label (fallback)
 * @param string|null $key Optional explicit key to look up in schema/labels
 * @param string|null $lang Language ('en', 'cn', 'both'), null = use global
 * @return string The label text
 */
function rfqLabel($cn, $en, $key = null, $lang = null) {
    static $labels = null;

    if ($lang === null) {
        $lang = getLang();
    }

    $schema = rfqGetSchema();

    // Generate key from English if not provided
    $lookupKey = $key;
    if (!$lookupKey) {
        $lookupKey = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($en)));
        $lookupKey = trim($lookupKey, '_');
    }

    // 1. Check schema fields
    if (isset($schema['fields'][$lookupKey])) {
        $field = $schema['fields'][$lookupKey];
        $cn = $field['cn'] ?? $cn;
        $en = $field['en'] ?? $en;
    }
    // 2. Check schema sections
    elseif (isset($schema['sections'][$lookupKey])) {
        $section = $schema['sections'][$lookupKey];
        $cn = $section['cn'] ?? $cn;
        $en = $section['en'] ?? $en;
    }
    // 3. Check subsections (e.g., 'F.1', 'H.2')
    elseif (strpos($lookupKey, '.') !== false) {
        $sectionLetter = substr($lookupKey, 0, 1);
        if (isset($schema['sections'][$sectionLetter]['subsections'][$lookupKey])) {
            $subsection = $schema['sections'][$sectionLetter]['subsections'][$lookupKey];
            $cn = $subsection['cn'] ?? $cn;
            $en = $subsection['en'] ?? $en;
        }
    }
    // 4. Check supplement categories
    elseif (isset($schema['supplement_categories'][$lookupKey])) {
        $cat = $schema['supplement_categories'][$lookupKey];
        $cn = $cat['cn'] ?? $cn;
        $en = $cat['en'] ?? $en;
    }
    else {
        // 5. Load and check labels.json as last resort
        if ($labels === null) {
            $labelsFile = dirname(__DIR__) . '/assets/lang/labels.json';
            if (file_exists($labelsFile)) {
                $labels = json_decode(file_get_contents($labelsFile), true) ?: [];
            } else {
                $labels = [];
            }
        }

        if (isset($labels['fields'][$lookupKey])) {
            $item = $labels['fields'][$lookupKey];
            $cn = $item['cn'] ?? $cn;
            $en = $item['en'] ?? $en;
        } elseif (isset($labels['sections'][$lookupKey])) {
            $item = $labels['sections'][$lookupKey];
            $cn = $item['cn'] ?? $cn;
            $en = $item['en'] ?? $en;
        }
    }

    // Return based on language
    if ($lang === 'en') {
        return $en;
    } elseif ($lang === 'cn') {
        return $cn;
    }
    return $cn . ' ' . $en;
}

/**
 * Universal label function for HTML output (with span tags for styling)
 *
 * Similar to rfqLabel() but returns HTML with span tags for CSS styling in 'both' mode.
 * Used for form labels where CSS can control display of cn/en labels.
 *
 * @param string $cn Chinese label (fallback)
 * @param string $en English label (fallback)
 * @param string|null $key Optional explicit key to look up in schema/labels
 * @param string|null $lang Language ('en', 'cn', 'both'), null = use global
 * @return string HTML label text
 */
function rfqLabelHtml($cn, $en, $key = null, $lang = null) {
    static $labels = null;

    if ($lang === null) {
        $lang = getLang();
    }

    $schema = rfqGetSchema();

    // Generate key from English if not provided
    $lookupKey = $key;
    if (!$lookupKey) {
        $lookupKey = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($en)));
        $lookupKey = trim($lookupKey, '_');
    }

    // 1. Check schema fields
    if (isset($schema['fields'][$lookupKey])) {
        $field = $schema['fields'][$lookupKey];
        $cn = $field['cn'] ?? $cn;
        $en = $field['en'] ?? $en;
    }
    // 2. Check schema sections
    elseif (isset($schema['sections'][$lookupKey])) {
        $section = $schema['sections'][$lookupKey];
        $cn = $section['cn'] ?? $cn;
        $en = $section['en'] ?? $en;
    }
    // 3. Check subsections (e.g., 'F.1', 'H.2')
    elseif (strpos($lookupKey, '.') !== false) {
        $sectionLetter = substr($lookupKey, 0, 1);
        if (isset($schema['sections'][$sectionLetter]['subsections'][$lookupKey])) {
            $subsection = $schema['sections'][$sectionLetter]['subsections'][$lookupKey];
            $cn = $subsection['cn'] ?? $cn;
            $en = $subsection['en'] ?? $en;
        }
    }
    // 4. Check supplement categories
    elseif (isset($schema['supplement_categories'][$lookupKey])) {
        $cat = $schema['supplement_categories'][$lookupKey];
        $cn = $cat['cn'] ?? $cn;
        $en = $cat['en'] ?? $en;
    }
    else {
        // 5. Load and check labels.json as last resort
        if ($labels === null) {
            $labelsFile = dirname(__DIR__) . '/assets/lang/labels.json';
            if (file_exists($labelsFile)) {
                $labels = json_decode(file_get_contents($labelsFile), true) ?: [];
            } else {
                $labels = [];
            }
        }

        if (isset($labels['fields'][$lookupKey])) {
            $item = $labels['fields'][$lookupKey];
            $cn = $item['cn'] ?? $cn;
            $en = $item['en'] ?? $en;
        } elseif (isset($labels['sections'][$lookupKey])) {
            $item = $labels['sections'][$lookupKey];
            $cn = $item['cn'] ?? $cn;
            $en = $item['en'] ?? $en;
        }
    }

    // Escape HTML
    $cn = htmlspecialchars($cn);
    $en = htmlspecialchars($en);

    // Return based on language
    if ($lang === 'en') {
        return $en;
    } elseif ($lang === 'cn') {
        return $cn;
    }
    // both mode: use span for styling
    return '<span class="form-label-cn">' . $cn . '</span><span class="form-label-en">' . $en . '</span>';
}
