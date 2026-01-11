<?php
/**
 * LSB Work Order System Configuration
 * Version: 2.1
 */

// 防止直接访问
if (!defined('WO_SYSTEM')) {
    define('WO_SYSTEM', true);
}

// 系统路径
define('WO_ROOT', dirname(__DIR__));
define('WO_UPLOAD_PATH', WO_ROOT . '/uploads/wo');
define('WO_UPLOAD_URL', '/aiforms/wko/uploads/wo');

// 引入共享的数据库配置
require_once dirname(WO_ROOT) . '/config/database.php';

// Session配置
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// WO状态
define('WO_STATUS_DRAFT', 'DRAFT');
define('WO_STATUS_SUBMITTED', 'SUBMITTED');
define('WO_STATUS_DONE', 'DONE');
define('WO_STATUS_REJECTED', 'REJECTED');

// 审阅决定
define('REVIEW_PENDING', 'PENDING');
define('REVIEW_ACK', 'ACK');
define('REVIEW_ACK_WITH_CONDITION', 'ACK_WITH_CONDITION');
define('REVIEW_REJECTED', 'REJECTED');

// 状态显示名称
$WO_STATUS_LABELS = [
    'DRAFT' => ['en' => 'Draft', 'cn' => '草稿', 'class' => 'secondary'],
    'SUBMITTED' => ['en' => 'Submitted', 'cn' => '已提交', 'class' => 'primary'],
    'DONE' => ['en' => 'Completed', 'cn' => '已完成', 'class' => 'success'],
    'REJECTED' => ['en' => 'Rejected', 'cn' => '已拒绝', 'class' => 'danger'],
];

// 审阅决定显示名称
$REVIEW_DECISION_LABELS = [
    'PENDING' => ['en' => 'Pending', 'cn' => '待审阅', 'class' => 'warning'],
    'ACK' => ['en' => 'Acknowledged', 'cn' => '已确认', 'class' => 'success'],
    'ACK_WITH_CONDITION' => ['en' => 'Acknowledged with Condition', 'cn' => '有条件确认', 'class' => 'info'],
    'REJECTED' => ['en' => 'Rejected', 'cn' => '已拒绝', 'class' => 'danger'],
];

// 文件类别
$FILE_CATEGORIES = [
    'contract' => ['en' => 'Contract', 'cn' => '合同文件'],
    'change_order' => ['en' => 'Change Order', 'cn' => '变更单'],
    'attachment' => ['en' => 'Attachment', 'cn' => '附件'],
];

// 允许上传的文件类型
$ALLOWED_FILE_TYPES = [
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'xls' => 'application/vnd.ms-excel',
    'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];

// 最大文件大小 (10MB)
define('MAX_FILE_SIZE', 10 * 1024 * 1024);

// 登录安全设置
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 30 * 60); // 30分钟

// OpenAI API 配置 (从.env读取，database.php已加载$_ENV)
define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY'] ?? '');
define('OPENAI_MODEL', $_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini');

/**
 * 获取数据库连接
 */
function wo_get_db() {
    return getDB();
}

/**
 * 生成WO编号
 */
function wo_generate_number() {
    $pdo = wo_get_db();
    $year = date('Y');

    // 锁定并更新序列
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT last_number FROM lsb_wo_sequence WHERE year = ? FOR UPDATE");
        $stmt->execute([$year]);
        $row = $stmt->fetch();

        if ($row) {
            $newNumber = $row['last_number'] + 1;
            $stmt = $pdo->prepare("UPDATE lsb_wo_sequence SET last_number = ? WHERE year = ?");
            $stmt->execute([$newNumber, $year]);
        } else {
            $newNumber = 1;
            $stmt = $pdo->prepare("INSERT INTO lsb_wo_sequence (year, last_number) VALUES (?, ?)");
            $stmt->execute([$year, $newNumber]);
        }

        $pdo->commit();
        return sprintf("WO-%d-%06d", $year, $newNumber);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * JSON响应
 */
function wo_json_response($success, $message = '', $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 错误响应
 */
function wo_error_response($message, $code = 400) {
    http_response_code($code);
    wo_json_response(false, $message);
}

/**
 * 清理输入
 */
function wo_clean_input($input) {
    if (is_array($input)) {
        return array_map('wo_clean_input', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * 获取状态标签HTML
 */
function wo_status_badge($status, $lang = 'en') {
    global $WO_STATUS_LABELS;
    $label = $WO_STATUS_LABELS[$status] ?? ['en' => $status, 'cn' => $status, 'class' => 'secondary'];
    $text = $label[$lang] ?? $label['en'];
    return sprintf('<span class="badge bg-%s">%s</span>', $label['class'], htmlspecialchars($text));
}

/**
 * 获取审阅决定标签HTML
 */
function wo_decision_badge($decision, $lang = 'en') {
    global $REVIEW_DECISION_LABELS;
    $label = $REVIEW_DECISION_LABELS[$decision] ?? ['en' => $decision, 'cn' => $decision, 'class' => 'secondary'];
    $text = $label[$lang] ?? $label['en'];
    return sprintf('<span class="badge bg-%s">%s</span>', $label['class'], htmlspecialchars($text));
}
