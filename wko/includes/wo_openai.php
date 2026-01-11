<?php
/**
 * LSB Work Order System - OpenAI API Functions
 */

require_once __DIR__ . '/../config/wo_config.php';

/**
 * 调用OpenAI API
 */
function wo_call_openai($messages, $model = null, $options = []) {
    $apiKey = OPENAI_API_KEY;
    if (empty($apiKey)) {
        return ['success' => false, 'message' => 'OpenAI API key not configured'];
    }

    $model = $model ?: OPENAI_MODEL;

    $data = array_merge([
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => 2000,
        'temperature' => 0.1
    ], $options);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'message' => 'API request failed: ' . $error];
    }

    $result = json_decode($response, true);

    if ($httpCode !== 200) {
        $errorMsg = $result['error']['message'] ?? 'Unknown error';
        return ['success' => false, 'message' => 'API error: ' . $errorMsg];
    }

    return [
        'success' => true,
        'data' => $result
    ];
}

/**
 * 使用GPT解析Excel内容
 */
function wo_parse_excel_with_gpt($textContent) {
    $systemPrompt = <<<EOT
You are an expert at parsing Work Order contract documents.
Extract key fields from the document and return as a JSON object.

The document typically has this structure:
- Header section with company info (LIBERTY STEEL BUILDINGS INC.)
- A "SUBCONTRACT" or "WORK ORDER" section with:
  - Labels on the left (like "Subcontractor:", "Address:", "Contact:", "Phone:")
  - Values on the right side of each label
- Project information section
- Contract/payment details with amounts

Required fields to extract:
- wo_no: Work Order number (format like WO-XXXX or similar)
- lsb_job_no: LSB Job number (look for "Job No." or "LSB Job")
- project_name: Name of the project
- project_address: Project site address (NOT the vendor's address)
- owner_name: Name of the owner/client
- vendor_name: Subcontractor/Vendor company name (after "Subcontractor:" label)
- vendor_address: Subcontractor's address - look for "Vendor Address" or "Address:" label in the Subcontractor section. Extract the full address value (typically starts with a street number like "10951...")
- vendor_contact: Contact person's name - look for the value AFTER "Contact:" or "Attn:" label. This is a person's first name (like "Jonathan", "John", etc.)
- vendor_phone: Phone number - look for value AFTER "Phone:" label
- vendor_email: Email address (if available)
- original_amount: Contract total amount (number only). Look for "Total", "Contract Amount", "Original Amount", "Grand Total", or large numbers marked "(numeric)"
- scope_summary: Brief description of work scope
- issued_date: Issue date in YYYY-MM-DD format
- cost_code: Cost code if mentioned

CRITICAL PARSING RULES:
1. Data is in "Label | Value" format separated by "|"
2. For vendor_address: Find the row with "Address" and extract the NEXT value (street number like "10951...")
3. For vendor_contact: Find the row with "Contact" or "Attn" and extract the person's NAME (like "Jonathan")
4. Do NOT confuse project address with vendor address - they are different
5. Numbers marked with "(numeric)" are likely amounts

If a field cannot be found, set it to null.
Return ONLY valid JSON, no additional text.
EOT;

    $userPrompt = "Parse this Work Order document and extract the fields:\n\n" . $textContent;

    $result = wo_call_openai([
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userPrompt]
    ], null, [
        'response_format' => ['type' => 'json_object']
    ]);

    if (!$result['success']) {
        return $result;
    }

    $content = $result['data']['choices'][0]['message']['content'] ?? '';

    try {
        $parsed = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Failed to parse GPT response as JSON'];
        }
        return ['success' => true, 'data' => $parsed];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error parsing response: ' . $e->getMessage()];
    }
}

/**
 * 从Excel文件提取文本内容 (简化版，不使用PhpSpreadsheet)
 * 改进版：同时提取字符串和数值数据
 */
function wo_extract_excel_text_simple($filePath) {
    // 检查文件是否存在
    if (!file_exists($filePath)) {
        return ['success' => false, 'message' => 'File not found: ' . $filePath];
    }

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    // 对于xlsx/xltx文件
    if (in_array($ext, ['xlsx', 'xltx'])) {
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'ZipArchive extension not available'];
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($filePath);

        if ($openResult !== true) {
            $errorMessages = [
                ZipArchive::ER_EXISTS => 'File already exists',
                ZipArchive::ER_INCONS => 'Zip archive inconsistent',
                ZipArchive::ER_INVAL => 'Invalid argument',
                ZipArchive::ER_MEMORY => 'Memory allocation failure',
                ZipArchive::ER_NOENT => 'No such file',
                ZipArchive::ER_NOZIP => 'Not a zip archive',
                ZipArchive::ER_OPEN => 'Cannot open file',
                ZipArchive::ER_READ => 'Read error',
                ZipArchive::ER_SEEK => 'Seek error',
            ];
            $errorMsg = $errorMessages[$openResult] ?? "Unknown error (code: $openResult)";
            return ['success' => false, 'message' => 'Cannot open Excel file: ' . $errorMsg];
        }

        // 1. 读取 sharedStrings.xml 构建字符串索引
        $sharedStrings = [];
        $ssContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssContent) {
            $ssXml = @simplexml_load_string($ssContent);
            if ($ssXml) {
                foreach ($ssXml->si as $si) {
                    $text = '';
                    if (isset($si->t)) {
                        $text = (string)$si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $r) {
                            $text .= (string)$r->t;
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        // 2. 读取所有工作表并按单元格顺序提取内容
        $allContent = [];

        // 查找所有工作表文件
        $sheetFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/^xl\/worksheets\/sheet\d*\.xml$/i', $name)) {
                $sheetFiles[] = $name;
            }
        }

        // 如果没找到，尝试常见路径
        if (empty($sheetFiles)) {
            $sheetFiles = ['xl/worksheets/sheet1.xml', 'xl/worksheets/Sheet1.xml'];
        }

        foreach ($sheetFiles as $sheetPath) {
            $sheetContent = $zip->getFromName($sheetPath);
            if (!$sheetContent) continue;

            $sheetXml = @simplexml_load_string($sheetContent);
            if (!$sheetXml) continue;

            // 直接访问sheetData->row，而不是用XPath（更可靠）
            if (!isset($sheetXml->sheetData->row)) {
                continue;
            }

            foreach ($sheetXml->sheetData->row as $row) {
                $rowData = [];

                // 直接遍历单元格
                foreach ($row->c as $cell) {
                    $cellValue = '';
                    $cellType = (string)$cell['t']; // 单元格类型
                    $value = isset($cell->v) ? (string)$cell->v : '';

                    if ($cellType === 's' && isset($sharedStrings[(int)$value])) {
                        // 字符串类型 - 从sharedStrings获取
                        $cellValue = $sharedStrings[(int)$value];
                    } elseif ($cellType === 'str') {
                        // 公式计算结果字符串 - 直接使用v值
                        $cellValue = $value;
                    } elseif ($cellType === 'inlineStr') {
                        // 内联字符串
                        if (isset($cell->is->t)) {
                            $cellValue = (string)$cell->is->t;
                        } elseif (isset($cell->is->r)) {
                            // 富文本内联字符串
                            foreach ($cell->is->r as $r) {
                                $cellValue .= (string)$r->t;
                            }
                        }
                    } elseif ($cellType === 'b') {
                        // 布尔类型
                        $cellValue = $value === '1' ? 'Yes' : 'No';
                    } elseif ($cellType === 'e') {
                        // 错误类型 - 跳过
                        continue;
                    } elseif ($value !== '') {
                        // 数值类型 - 直接使用值（这包括金额等数字）
                        $cellValue = $value;

                        // 尝试格式化大数字（可能是金额）
                        if (is_numeric($value) && strlen($value) > 3) {
                            $numValue = floatval($value);
                            // 如果是大于100的数字，可能是金额，添加标记
                            if ($numValue > 100 && $numValue < 100000000) {
                                $cellValue = $value . " (numeric)";
                            }
                        }
                    }

                    if (trim($cellValue) !== '') {
                        $rowData[] = trim($cellValue);
                    }
                }

                if (!empty($rowData)) {
                    $allContent[] = implode(" | ", $rowData);
                }
            }

            // 限制最多读取5个工作表
            if (count($sheetFiles) > 5) break;
        }

        $zip->close();

        if (empty($allContent)) {
            return ['success' => false, 'message' => 'No readable content found in Excel file'];
        }

        // 组合所有内容
        $finalContent = implode("\n", $allContent);

        return ['success' => true, 'data' => $finalContent];
    }

    // 对于xls文件（旧格式），暂不支持
    if ($ext === 'xls') {
        return ['success' => false, 'message' => 'Old Excel format (.xls) is not supported. Please save as .xlsx'];
    }

    return ['success' => false, 'message' => 'Unsupported file format: ' . $ext];
}
