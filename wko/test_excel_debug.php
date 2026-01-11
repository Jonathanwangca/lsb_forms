<?php
/**
 * 调试Excel文件结构
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['file'])) {
    $file = $_FILES['file'];

    echo "<h2>File Info:</h2>";
    echo "<pre>" . print_r($file, true) . "</pre>";

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext === 'xlsx' || $ext === 'xltx') {
            $zip = new ZipArchive();
            if ($zip->open($file['tmp_name']) === true) {
                echo "<h2>ZIP Archive Contents:</h2>";
                echo "<ul>";
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    echo "<li>" . htmlspecialchars($name) . "</li>";
                }
                echo "</ul>";

                // 检查sharedStrings
                echo "<h2>sharedStrings.xml:</h2>";
                $ss = $zip->getFromName('xl/sharedStrings.xml');
                if ($ss) {
                    echo "<p>Found! Length: " . strlen($ss) . " bytes</p>";
                    echo "<pre style='max-height:200px;overflow:auto;background:#f5f5f5;padding:10px;'>" . htmlspecialchars(substr($ss, 0, 2000)) . "</pre>";
                } else {
                    echo "<p style='color:orange'>Not found</p>";
                }

                // 检查workbook.xml来找到sheet名称
                echo "<h2>workbook.xml:</h2>";
                $wb = $zip->getFromName('xl/workbook.xml');
                if ($wb) {
                    echo "<pre style='max-height:200px;overflow:auto;background:#f5f5f5;padding:10px;'>" . htmlspecialchars($wb) . "</pre>";
                }

                // 检查sheet1.xml
                echo "<h2>Worksheets:</h2>";

                // 尝试不同的路径
                $sheetPaths = [
                    'xl/worksheets/sheet1.xml',
                    'xl/worksheets/Sheet1.xml',
                    'xl/worksheets/sheet.xml',
                ];

                // 也从workbook中解析sheet信息
                if ($wb) {
                    $wbXml = @simplexml_load_string($wb);
                    if ($wbXml) {
                        $wbXml->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                        $sheets = $wbXml->xpath('//main:sheet');
                        echo "<p>Sheets found in workbook: " . count($sheets) . "</p>";
                        foreach ($sheets as $sheet) {
                            echo "<p>Sheet: " . (string)$sheet['name'] . " (sheetId: " . (string)$sheet['sheetId'] . ")</p>";
                        }
                    }
                }

                // 列出xl/worksheets目录下的所有文件
                echo "<h3>Files in xl/worksheets/:</h3>";
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (strpos($name, 'xl/worksheets/') === 0) {
                        echo "<p>" . htmlspecialchars($name) . "</p>";
                        $sheetPaths[] = $name;
                    }
                }

                foreach (array_unique($sheetPaths) as $path) {
                    $sheet = $zip->getFromName($path);
                    if ($sheet) {
                        echo "<h3>$path:</h3>";
                        echo "<p>Found! Length: " . strlen($sheet) . " bytes</p>";
                        echo "<pre style='max-height:300px;overflow:auto;background:#e8f5e9;padding:10px;font-size:11px;'>" . htmlspecialchars(substr($sheet, 0, 5000)) . "</pre>";

                        // 尝试解析
                        $xml = @simplexml_load_string($sheet);
                        if ($xml) {
                            echo "<p style='color:green'>XML parsed successfully</p>";

                            // 获取命名空间
                            $namespaces = $xml->getNamespaces(true);
                            echo "<p>Namespaces: " . print_r($namespaces, true) . "</p>";

                            // 尝试获取行
                            $ns = $namespaces[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                            $xml->registerXPathNamespace('s', $ns);

                            $rows = $xml->xpath('//s:row');
                            echo "<p>Rows found with XPath: " . count($rows) . "</p>";

                            // 也尝试直接访问
                            if (isset($xml->sheetData->row)) {
                                echo "<p>Rows via direct access: " . count($xml->sheetData->row) . "</p>";

                                // 显示前几行
                                $count = 0;
                                foreach ($xml->sheetData->row as $row) {
                                    if ($count++ >= 5) break;
                                    echo "<p>Row " . (string)$row['r'] . ": ";
                                    foreach ($row->c as $cell) {
                                        $cellRef = (string)$cell['r'];
                                        $cellType = (string)$cell['t'];
                                        $cellValue = (string)$cell->v;
                                        echo "[$cellRef:t=$cellType,v=$cellValue] ";
                                    }
                                    echo "</p>";
                                }
                            }
                        } else {
                            echo "<p style='color:red'>Failed to parse XML</p>";
                        }
                    }
                }

                $zip->close();
            } else {
                echo "<p style='color:red'>Failed to open ZIP</p>";
            }
        } else {
            echo "<p>File extension: $ext (not xlsx/xltx)</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Excel Structure</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
    <h1>Debug Excel Structure</h1>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file" accept=".xlsx,.xls,.xltx">
        <button type="submit">Analyze</button>
    </form>
</body>
</html>
