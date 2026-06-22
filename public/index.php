<?php

require_once __DIR__ . '/../bootstrap.php';

$lang = $_GET['lang'] ?? 'fa';
$lang = in_array($lang, ['fa', 'en']) ? $lang : 'fa';

$i18n = require __DIR__ . "/i18n/$lang.php";

function setActive($path) {
    return $_SERVER['REQUEST_URI'] === $path ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="<?= $lang === 'fa' ? 'fa' : 'en' ?>" dir="<?= $lang === 'fa' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $i18n['app_title'] ?> - Backend System</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        window.I18N = <?= json_encode($i18n, JSON_UNESCAPED_UNICODE) ?>;
        window.LANG = '<?= $lang ?>';
    </script>
</head>
<body>
    <div id="app">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <span class="logo-icon">⚡</span>
                    <span class="logo-text">Backend System</span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">✕</button>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section-label" data-i18n="nav_main">Main</div>
                <a href="#/" class="nav-item" data-route="">
                    <span class="nav-icon">📊</span>
                    <span data-i18n="nav_dashboard">Dashboard</span>
                </a>
                <div class="nav-section-label" data-i18n="nav_management">Management</div>
                <a href="#/users" class="nav-item" data-route="users">
                    <span class="nav-icon">👥</span>
                    <span data-i18n="nav_users">Users</span>
                </a>
                <a href="#/products" class="nav-item" data-route="products">
                    <span class="nav-icon">📦</span>
                    <span data-i18n="nav_products">Products</span>
                </a>
                <a href="#/articles" class="nav-item" data-route="articles">
                    <span class="nav-icon">📝</span>
                    <span data-i18n="nav_articles">Articles</span>
                </a>
                <div class="nav-section-label" data-i18n="nav_system">System</div>
                <a href="#/system" class="nav-item" data-route="system">
                    <span class="nav-icon">⚙️</span>
                    <span data-i18n="nav_system">System</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="lang-switch">
                    <a href="?lang=fa" class="lang-btn <?= $lang === 'fa' ? 'active' : '' ?>">🇮🇷 فارسی</a>
                    <a href="?lang=en" class="lang-btn <?= $lang === 'en' ? 'active' : '' ?>">🇬🇧 English</a>
                </div>
            </div>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <button class="menu-btn" id="menuBtn">☰</button>
                <div class="topbar-title">
                    <h1 id="pageTitle" data-i18n="page_dashboard">Dashboard</h1>
                </div>
                <div class="topbar-actions">
                    <div class="connection-status" id="connStatus">
                        <span class="status-dot"></span>
                        <span data-i18n="checking">Checking...</span>
                    </div>
                </div>
            </header>
            <div class="content" id="mainContent">
                <div class="loading-screen" id="loadingScreen">
                    <div class="spinner"></div>
                    <p data-i18n="loading">Loading...</p>
                </div>
            </div>
        </main>
    </div>
    <div id="toastContainer" class="toast-container"></div>
    <div id="modalOverlay" class="modal-overlay" style="display:none">
        <div class="modal" id="modalContent"></div>
    </div>
    <script src="assets/js/helpers.js?v=1.0"></script>
    <script src="assets/js/i18n.js?v=1.0"></script>
    <script src="assets/js/api.js?v=1.0"></script>
    <script src="assets/js/router.js?v=1.0"></script>
    <script src="assets/js/pages.js?v=1.0"></script>
    <script src="assets/js/app.js?v=1.0"></script>
</body>
</html>
