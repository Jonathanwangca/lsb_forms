<?php
/**
 * RFQ System Header Template - WO Style
 * LSB RFQ System with sidebar navigation
 *
 * Note: This file includes the unified schema (rfq_schema.php) for consistency
 * between form UI and PDF print output.
 */
require_once dirname(__DIR__) . '/includes/functions.php';

// Load unified schema and render functions (if not already loaded)
$schemaFile = dirname(__DIR__) . '/rfq/rfq_render.php';
if (file_exists($schemaFile)) {
    require_once $schemaFile;
}

// Language settings
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['rfq_lang']) ? $_COOKIE['rfq_lang'] : 'both');
if (!in_array($lang, ['en', 'cn', 'both'])) {
    $lang = 'both';
}
if (isset($_GET['lang'])) {
    setcookie('rfq_lang', $lang, time() + 86400 * 30, '/');
}
$GLOBALS['current_lang'] = $lang;

// Load language labels
$langLabels = [];
$langFile = dirname(__DIR__) . '/assets/lang/labels.json';
if (file_exists($langFile)) {
    $langLabels = json_decode(file_get_contents($langFile), true) ?: [];
}
$GLOBALS['lang_labels'] = $langLabels;

$pageTitle = $pageTitle ?? 'Inava Steel Customer Portal';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$htmlLang = $lang === 'en' ? 'en' : 'zh-CN';
?>
<!DOCTYPE html>
<html lang="<?php echo $htmlLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/aiforms/assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a365d;
            --secondary-color: #2d5a87;
            --accent-color: #0d6efd;
        }
        body {
            background-color: #f5f7fa;
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
        }
        .sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            width: 250px;
            height: calc(100vh - 56px);
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            padding: 20px 0;
            overflow-y: auto;
            z-index: 100;
            transition: transform 0.3s ease, width 0.3s ease;
        }
        .sidebar.collapsed {
            transform: translateX(-250px);
        }
        .sidebar-toggle {
            position: fixed;
            top: 50%;
            left: 250px;
            transform: translateY(-50%);
            width: 20px;
            height: 60px;
            background: var(--primary-color);
            border: none;
            border-radius: 0 6px 6px 0;
            color: white;
            cursor: pointer;
            z-index: 101;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: left 0.3s ease, background 0.2s;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar-toggle:hover {
            background: var(--secondary-color);
        }
        .sidebar-toggle i {
            font-size: 12px;
            transition: transform 0.3s;
        }
        .sidebar.collapsed + .sidebar-toggle {
            left: 0;
        }
        .sidebar.collapsed + .sidebar-toggle i {
            transform: rotate(180deg);
        }
        .main-content {
            margin-left: 250px;
            padding: 20px 30px;
            min-height: calc(100vh - 56px);
            transition: margin-left 0.3s ease;
        }
        .main-content.expanded {
            margin-left: 0;
        }
        footer {
            transition: margin-left 0.3s ease;
        }
        .main-content.expanded ~ footer,
        footer.expanded {
            margin-left: 0 !important;
        }
        .sidebar .nav-link {
            color: #333;
            padding: 12px 20px;
            border-radius: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
        }
        .sidebar .nav-link:hover {
            background: #f0f4f8;
            color: var(--primary-color);
        }
        .sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }
        .sidebar-section {
            padding: 10px 20px;
            font-size: 12px;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .page-header {
            margin-bottom: 25px;
        }
        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
            font-weight: 600;
        }
        .table th {
            font-weight: 600;
            color: #555;
            border-bottom-width: 1px;
        }
        .badge-count {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 10px;
        }
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        /* Language switch styling */
        .lang-switch {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .lang-switch .form-check-input:checked {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        /* Form section overrides for WO style */
        .form-section {
            background: #fff;
            border: none;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .form-section-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 10px 10px 0 0;
            padding: 12px 20px;
        }
        .form-section-body {
            padding: 20px;
        }

        /* Toolbar styling */
        .toolbar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
    <?php if ($lang === 'en'): ?>
    <style>
        .form-label-cn { display: none !important; }
        .form-label-en { display: block !important; font-size: 0.85em !important; color: #495057 !important; }
    </style>
    <?php elseif ($lang === 'cn'): ?>
    <style>
        .form-label-en { display: none !important; }
        .form-label-cn { display: block !important; }
    </style>
    <?php endif; ?>
    <script>
        window.currentLang = '<?php echo $lang; ?>';
        function toggleLanguage() {
            const currentUrl = new URL(window.location.href);
            const newLang = window.currentLang === 'en' ? 'both' : 'en';
            currentUrl.searchParams.set('lang', newLang);
            window.location.href = currentUrl.toString();
        }
    </script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white d-lg-none me-2" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand" href="/aiforms/rfq/list.php<?php echo $lang !== 'both' ? '?lang='.$lang : ''; ?>">
                <i class="fas fa-building me-2"></i>Inava Steel Customer Portal
            </a>
            <div class="ms-auto d-flex align-items-center">
                <a href="/aiforms/rfq/list.php<?php echo $lang !== 'both' ? '?lang='.$lang : ''; ?>" class="btn btn-outline-light btn-sm me-3">
                    <i class="fas fa-list me-1"></i><?php echo $lang === 'en' ? 'RFQ List' : 'RFQ列表'; ?>
                </a>
                <div class="lang-switch me-3">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="langSwitch"
                               <?php echo $lang === 'en' ? 'checked' : ''; ?>
                               onchange="toggleLanguage()" style="cursor: pointer;">
                        <label class="form-check-label text-white" for="langSwitch" style="cursor: pointer; font-size: 0.9em;">
                            EN Only
                        </label>
                    </div>
                </div>
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-cog me-1"></i>Settings
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/aiforms/rfq/config.php?config=true<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>">
                            <i class="fas fa-sliders-h me-2"></i><?php echo $lang === 'en' ? 'Parameters' : '参数配置'; ?>
                        </a></li>
                        <li><a class="dropdown-item" href="/aiforms/rfq/config_labels.php?config=true<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>">
                            <i class="fas fa-language me-2"></i><?php echo $lang === 'en' ? 'Labels' : '标签配置'; ?>
                        </a></li>
                        <li><a class="dropdown-item" href="/aiforms/rfq/config_schema.php?config=true<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>">
                            <i class="fas fa-project-diagram me-2"></i><?php echo $lang === 'en' ? 'Schema' : 'Schema配置'; ?>
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar collapsed" id="sidebar">
        <div class="sidebar-section"><?php echo $lang === 'en' ? 'Main Menu' : '主菜单'; ?></div>
        <nav class="nav flex-column">
            <a class="nav-link <?php echo $currentPage === 'list' ? 'active' : ''; ?>"
               href="/aiforms/rfq/list.php<?php echo $lang !== 'both' ? '?lang='.$lang : ''; ?>">
                <i class="fas fa-list"></i> <?php echo $lang === 'en' ? 'RFQ List' : 'RFQ 列表'; ?>
            </a>
            <a class="nav-link <?php echo $currentPage === 'form_rfq' && !isset($_GET['id']) ? 'active' : ''; ?>"
               href="/aiforms/rfq/form_rfq.php<?php echo $lang !== 'both' ? '?lang='.$lang : ''; ?>">
                <i class="fas fa-plus-circle"></i> <?php echo $lang === 'en' ? 'New RFQ' : '新建 RFQ'; ?>
            </a>
            <a class="nav-link <?php echo $currentPage === 'import' ? 'active' : ''; ?>"
               href="/aiforms/rfq/import.php<?php echo $lang !== 'both' ? '?lang='.$lang : ''; ?>">
                <i class="fas fa-upload"></i> <?php echo $lang === 'en' ? 'Import JSON' : '导入 JSON'; ?>
            </a>
        </nav>

        <div class="sidebar-section mt-4"><?php echo $lang === 'en' ? 'Configuration' : '配置管理'; ?></div>
        <nav class="nav flex-column">
            <a class="nav-link <?php echo $currentPage === 'config' ? 'active' : ''; ?>"
               href="/aiforms/rfq/config.php?config=true<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>">
                <i class="fas fa-sliders-h"></i> <?php echo $lang === 'en' ? 'Parameters' : '参数配置'; ?>
            </a>
            <a class="nav-link <?php echo $currentPage === 'config_labels' ? 'active' : ''; ?>"
               href="/aiforms/rfq/config_labels.php?config=true<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>">
                <i class="fas fa-language"></i> <?php echo $lang === 'en' ? 'Labels' : '标签配置'; ?>
            </a>
            <a class="nav-link <?php echo $currentPage === 'config_schema' ? 'active' : ''; ?>"
               href="/aiforms/rfq/config_schema.php?config=true<?php echo $lang !== 'both' ? '&lang='.$lang : ''; ?>">
                <i class="fas fa-project-diagram"></i> <?php echo $lang === 'en' ? 'Schema' : 'Schema配置'; ?>
            </a>
        </nav>

        <div class="sidebar-section mt-4"><?php echo $lang === 'en' ? 'Other Systems' : '其他系统'; ?></div>
        <nav class="nav flex-column">
            <a class="nav-link" href="/aiforms/wko/dashboard.php">
                <i class="fas fa-file-contract"></i> <?php echo $lang === 'en' ? 'Work Order' : '工作订单'; ?>
            </a>
        </nav>
    </div>

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" id="sidebarCollapseBtn" title="<?php echo $lang === 'en' ? 'Toggle Sidebar' : '切换侧边栏'; ?>">
        <i class="fas fa-chevron-left"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content expanded" id="mainContent">
