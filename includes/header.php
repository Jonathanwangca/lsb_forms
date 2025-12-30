<?php
/**
 * 页面头部模板
 * LSB RFQ System
 */
require_once dirname(__DIR__) . '/includes/functions.php';

// 语言设置
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['rfq_lang']) ? $_COOKIE['rfq_lang'] : 'both');
if (!in_array($lang, ['en', 'cn', 'both'])) {
    $lang = 'both';
}
// 设置cookie保持语言选择
if (isset($_GET['lang'])) {
    setcookie('rfq_lang', $lang, time() + 86400 * 30, '/');
}
$GLOBALS['current_lang'] = $lang;

// 加载语言字典
$langLabels = [];
$langFile = dirname(__DIR__) . '/assets/lang/labels.json';
if (file_exists($langFile)) {
    $langLabels = json_decode(file_get_contents($langFile), true) ?: [];
}
$GLOBALS['lang_labels'] = $langLabels;

$pageTitle = $pageTitle ?? 'LSB RFQ System';
$htmlLang = $lang === 'en' ? 'en' : 'zh-CN';
?>
<!DOCTYPE html>
<html lang="<?php echo $htmlLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/aiforms/assets/css/style.css" rel="stylesheet">
    <style>
        /* Red switch for English Only toggle */
        #langSwitch:checked {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
        }
        #langSwitch:focus {
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
    </style>
    <?php if ($lang === 'en'): ?>
    <style>
        /* English only mode - hide Chinese labels */
        .form-label-cn { display: none !important; }
        .form-label-en { display: block !important; font-size: 0.85em !important; color: #495057 !important; }
    </style>
    <?php elseif ($lang === 'cn'): ?>
    <style>
        /* Chinese only mode */
        .form-label-en { display: none !important; }
        .form-label-cn { display: block !important; }
    </style>
    <?php endif; ?>
    <script>
        // 当前语言设置
        window.currentLang = '<?php echo $lang; ?>';

        // 切换语言
        function toggleLanguage() {
            const currentUrl = new URL(window.location.href);
            const newLang = window.currentLang === 'en' ? 'both' : 'en';
            currentUrl.searchParams.set('lang', newLang);
            window.location.href = currentUrl.toString();
        }
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/aiforms/">
                <i class="bi bi-building"></i> LSB RFQ System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/aiforms/rfq/list.php<?php echo $lang !== 'both' ? '?lang='.$lang : ''; ?>">
                            <i class="bi bi-list-ul"></i> <?php echo $lang === 'en' ? 'RFQ List' : 'RFQ 列表'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/aiforms/rfq/form_rfq.php<?php echo $lang !== 'both' ? '?lang='.$lang : ''; ?>">
                            <i class="bi bi-plus-circle"></i> <?php echo $lang === 'en' ? 'New RFQ' : '新建 RFQ'; ?>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="langSwitch"
                                   <?php echo $lang === 'en' ? 'checked' : ''; ?>
                                   onchange="toggleLanguage()" style="cursor: pointer;">
                            <label class="form-check-label text-white" for="langSwitch" style="cursor: pointer; font-size: 0.9em;">
                                English Only
                            </label>
                        </div>
                    </li>
                    <li class="nav-item me-2">
                        <a class="nav-link" href="/aiforms/rfq/config.php?config=true<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>" title="<?php echo $lang === 'en' ? 'Parameter Config' : '参数配置'; ?>">
                            <i class="bi bi-gear"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/aiforms/rfq/import.php<?php echo $lang !== 'both' ? '?lang='.$lang : ''; ?>">
                            <i class="bi bi-upload"></i> <?php echo $lang === 'en' ? 'Import' : 'Import JSON'; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container-fluid py-4">
