<?php
/**
 * RFQ Form/Print Consistency Check Tool
 * LSB RFQ System V3.2
 *
 * This tool validates that form sections and print.php are using
 * consistent field definitions from the unified schema.
 *
 * Run this script to identify any inconsistencies between
 * form UI and PDF print output.
 *
 * Usage: php check_consistency.php
 *        Or access via browser: check_consistency.php?config=true
 */

// Check access permission
if (php_sapi_name() !== 'cli' && (!isset($_GET['config']) || $_GET['config'] !== 'true')) {
    header('HTTP/1.0 403 Forbidden');
    echo '<h1>403 Forbidden</h1><p>Access denied. Add ?config=true to access.</p>';
    exit;
}

// Load dependencies
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/rfq_schema.php';

$schema = getRfqSchema();

// Output mode
$isHtml = php_sapi_name() !== 'cli';

function output($message, $type = 'info') {
    global $isHtml;
    if ($isHtml) {
        $colors = [
            'info' => '#333',
            'success' => '#28a745',
            'warning' => '#ffc107',
            'error' => '#dc3545',
        ];
        $color = $colors[$type] ?? '#333';
        echo "<div style=\"color: {$color}; margin: 5px 0;\">{$message}</div>";
    } else {
        $prefixes = [
            'info' => '',
            'success' => '[OK] ',
            'warning' => '[WARN] ',
            'error' => '[ERROR] ',
        ];
        echo ($prefixes[$type] ?? '') . $message . "\n";
    }
}

function outputSection($title) {
    global $isHtml;
    if ($isHtml) {
        echo "<h3 style=\"margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 5px;\">{$title}</h3>";
    } else {
        echo "\n=== {$title} ===\n";
    }
}

// Start output
if ($isHtml) {
    echo '<!DOCTYPE html><html><head><title>RFQ Consistency Check</title>';
    echo '<style>body{font-family:monospace;padding:20px;max-width:1200px;margin:0 auto;}</style>';
    echo '</head><body>';
    echo '<h1>RFQ Form/Print Consistency Check</h1>';
    echo '<p>This tool checks for inconsistencies between form sections and print.php</p>';
}

$errors = 0;
$warnings = 0;

// ============================================================
// 1. Check that all schema fields are used in form sections
// ============================================================
outputSection('1. Schema Fields Usage in Form Sections');

$formFiles = glob(__DIR__ . '/_section_*.php');
$formContent = '';
foreach ($formFiles as $file) {
    $formContent .= file_get_contents($file);
}

foreach ($schema['fields'] as $fieldKey => $fieldDef) {
    // Check if field is referenced in form files
    $found = false;
    $patterns = [
        "name=\"{$fieldDef['table']}[{$fieldKey}]\"",
        "name=\"main[{$fieldKey}]\"",
        "name=\"steel[{$fieldKey}]\"",
        "name=\"envelope[{$fieldKey}]\"",
        "name=\"order_entry[{$fieldKey}]\"",
        "\['{$fieldKey}'\]",
        "[\"{$fieldKey}\"]",
    ];

    foreach ($patterns as $pattern) {
        if (stripos($formContent, $pattern) !== false) {
            $found = true;
            break;
        }
    }

    if ($found) {
        output("Field '{$fieldKey}' found in form sections", 'success');
    } else {
        output("Field '{$fieldKey}' NOT found in form sections (table: {$fieldDef['table']})", 'warning');
        $warnings++;
    }
}

// ============================================================
// 2. Check that all schema fields are used in print.php
// ============================================================
outputSection('2. Schema Fields Usage in print.php');

$printContent = file_get_contents(__DIR__ . '/print.php');

foreach ($schema['fields'] as $fieldKey => $fieldDef) {
    // Check if field is referenced in print.php
    $found = false;
    $patterns = [
        "\$main['{$fieldKey}']",
        "\$steel['{$fieldKey}']",
        "\$envelope['{$fieldKey}']",
        "\$orderEntry['{$fieldKey}']",
        "[\"{$fieldKey}\"]",
        "['{$fieldKey}']",
    ];

    foreach ($patterns as $pattern) {
        if (stripos($printContent, $pattern) !== false) {
            $found = true;
            break;
        }
    }

    if ($found) {
        output("Field '{$fieldKey}' found in print.php", 'success');
    } else {
        output("Field '{$fieldKey}' NOT found in print.php (table: {$fieldDef['table']})", 'warning');
        $warnings++;
    }
}

// ============================================================
// 3. Check section numbering consistency
// ============================================================
outputSection('3. Section Numbering Consistency');

// Extract section numbers from form files
preg_match_all('/section-number["\']>\s*([A-Z])\.\s*</', $formContent, $formSections);
$formSectionLetters = array_unique($formSections[1] ?? []);
sort($formSectionLetters);

// Extract section numbers from print.php
preg_match_all('/section-number["\']>\s*([A-Z])\.\s*</', $printContent, $printSections);
$printSectionLetters = array_unique($printSections[1] ?? []);
sort($printSectionLetters);

// Schema sections
$schemaSections = array_keys($schema['sections']);
sort($schemaSections);

output("Schema sections: " . implode(', ', $schemaSections), 'info');
output("Form sections: " . implode(', ', $formSectionLetters), 'info');
output("Print sections: " . implode(', ', $printSectionLetters), 'info');

// Check for missing sections in form
$missingInForm = array_diff($schemaSections, $formSectionLetters);
if (!empty($missingInForm)) {
    output("Sections missing in form: " . implode(', ', $missingInForm), 'warning');
    $warnings++;
} else {
    output("All schema sections present in form", 'success');
}

// Check for missing sections in print
$missingInPrint = array_diff($schemaSections, $printSectionLetters);
if (!empty($missingInPrint)) {
    output("Sections missing in print: " . implode(', ', $missingInPrint), 'warning');
    $warnings++;
} else {
    output("All schema sections present in print", 'success');
}

// ============================================================
// 4. Check subsection numbering
// ============================================================
outputSection('4. Subsection Numbering Consistency');

foreach ($schema['sections'] as $letter => $section) {
    if (empty($section['subsections'])) continue;

    foreach ($section['subsections'] as $subKey => $subDef) {
        // Check in form
        $formHasSub = preg_match('/subsection-number["\']>\s*' . preg_quote($subKey) . '\s*</', $formContent);
        // Check in print
        $printHasSub = preg_match('/subsection-number["\']>\s*' . preg_quote($subKey) . '\s*</', $printContent);

        if ($formHasSub && $printHasSub) {
            output("Subsection '{$subKey}' found in both form and print", 'success');
        } elseif (!$formHasSub && !$printHasSub) {
            output("Subsection '{$subKey}' NOT found in either form or print", 'warning');
            $warnings++;
        } else {
            $missing = !$formHasSub ? 'form' : 'print';
            output("Subsection '{$subKey}' missing in {$missing}", 'error');
            $errors++;
        }
    }
}

// ============================================================
// 5. Check label consistency (comparing Chinese/English labels)
// ============================================================
outputSection('5. Label Function Usage');

// Check if printLabel is used in print.php
$printLabelCount = substr_count($printContent, 'printLabel(');
output("printLabel() calls in print.php: {$printLabelCount}", 'info');

// Check if fieldLabel/sectionTitle is used in form
$fieldLabelCount = substr_count($formContent, 'fieldLabel(');
$sectionTitleCount = substr_count($formContent, 'sectionTitle(');
output("fieldLabel() calls in form: {$fieldLabelCount}", 'info');
output("sectionTitle() calls in form: {$sectionTitleCount}", 'info');

// Check for schema-based functions
$rfqFieldLabelCount = substr_count($formContent, 'rfqFieldLabel(');
$rfqSectionTitleCount = substr_count($formContent, 'rfqSectionTitle(');
output("rfqFieldLabel() calls in form: {$rfqFieldLabelCount}", 'info');
output("rfqSectionTitle() calls in form: {$rfqSectionTitleCount}", 'info');

if ($rfqFieldLabelCount == 0 && $rfqSectionTitleCount == 0) {
    output("Form sections not yet using unified schema functions (rfqFieldLabel, rfqSectionTitle)", 'warning');
    $warnings++;
}

// ============================================================
// 6. Check reference category consistency
// ============================================================
outputSection('6. Reference Category Consistency');

$refCategories = [];
foreach ($schema['fields'] as $fieldKey => $fieldDef) {
    if (!empty($fieldDef['ref_category'])) {
        $refCategories[$fieldDef['ref_category']] = $fieldKey;
    }
}

// Get all categories from database
$dbCategories = dbQuery("SELECT DISTINCT category FROM lsb_rfq_reference");
$dbCategoryList = array_column($dbCategories, 'category');

foreach ($refCategories as $category => $fieldKey) {
    if (in_array($category, $dbCategoryList)) {
        output("Reference category '{$category}' exists in database (used by {$fieldKey})", 'success');
    } else {
        output("Reference category '{$category}' NOT found in database (used by {$fieldKey})", 'error');
        $errors++;
    }
}

// ============================================================
// 7. Check supplement category labels
// ============================================================
outputSection('7. Supplement Category Labels');

// Check if print.php uses hardcoded category labels
$hardcodedCategories = [
    "'general' => '通用'",
    "'steel' => '钢结构'",
    "'envelope' => '围护'",
];

$foundHardcoded = false;
foreach ($hardcodedCategories as $hc) {
    if (strpos($printContent, $hc) !== false) {
        $foundHardcoded = true;
        output("Found hardcoded category label in print.php: {$hc}", 'warning');
        $warnings++;
    }
}

if (!$foundHardcoded) {
    output("No hardcoded category labels found in print.php", 'success');
}

// Check if rfqCategoryLabel is used
if (strpos($printContent, 'rfqCategoryLabel(') !== false) {
    output("print.php uses rfqCategoryLabel() function", 'success');
} else {
    output("print.php should use rfqCategoryLabel() for category labels", 'info');
}

// ============================================================
// Summary
// ============================================================
outputSection('Summary');

output("Total errors: {$errors}", $errors > 0 ? 'error' : 'success');
output("Total warnings: {$warnings}", $warnings > 0 ? 'warning' : 'success');

if ($errors == 0 && $warnings == 0) {
    output("All consistency checks passed!", 'success');
} else {
    output("Please review the above issues to ensure form/print consistency.", 'info');
}

// Recommendations
outputSection('Recommendations');

output("1. Migrate form sections to use rfqFieldLabel() and rfqSectionTitle() functions", 'info');
output("2. Migrate print.php to use rfqRenderPrintValue() for formatted field output", 'info');
output("3. Update print.php to use rfqCategoryLabel() for supplement categories", 'info');
output("4. Run this check after any schema or template changes", 'info');

if ($isHtml) {
    echo '<p style="margin-top: 30px; color: #666;">Check completed at ' . date('Y-m-d H:i:s') . '</p>';
    echo '</body></html>';
}
