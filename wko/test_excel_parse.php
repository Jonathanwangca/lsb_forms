<?php
/**
 * 测试Excel解析
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/wo_openai.php';

// 检查是否有上传的文件
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['file'])) {
    $file = $_FILES['file'];

    echo "<h3>File Info:</h3>";
    echo "<pre>";
    print_r($file);
    echo "</pre>";

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $tempFile = sys_get_temp_dir() . '/' . uniqid('test_') . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], $tempFile)) {
            echo "<h3>Extracting content...</h3>";

            $result = wo_extract_excel_text_simple($tempFile);

            echo "<h4>Extraction Result:</h4>";
            echo "<pre>";
            print_r($result);
            echo "</pre>";

            if ($result['success']) {
                echo "<h4>Extracted Content (first 5000 chars):</h4>";
                echo "<pre style='background:#f5f5f5; padding:10px; max-height:400px; overflow:auto;'>";
                echo htmlspecialchars(substr($result['data'], 0, 5000));
                echo "</pre>";

                echo "<h3>Calling GPT to parse...</h3>";
                $parseResult = wo_parse_excel_with_gpt($result['data']);

                echo "<h4>GPT Parse Result:</h4>";
                echo "<pre style='background:#e8f5e9; padding:10px;'>";
                print_r($parseResult);
                echo "</pre>";
            }

            unlink($tempFile);
        } else {
            echo "<p style='color:red'>Failed to move uploaded file</p>";
        }
    } else {
        echo "<p style='color:red'>Upload error: " . $file['error'] . "</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Excel Parse</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h2>Test Excel Parse</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file" accept=".xlsx,.xls,.xltx">
        <button type="submit">Test Parse</button>
    </form>
</body>
</html>
