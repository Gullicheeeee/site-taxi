<?php
require_once __DIR__ . '/../config.php';
requireLogin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Back-Office') ?> - Taxi Julien Admin</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <span>🚖</span>
                    <span>Taxi Julien</span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">Principal</div>
                <a href="index.php" class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">
                    <span class="icon">📊</span>
                    <span>Dashboard</span>
                </a>

                <div class="nav-section">Contenu</div>
                <a href="pages.php" class="nav-link <?= $currentPage === 'pages' ? 'active' : '' ?>">
                    <span class="icon">📄</span>
                    <span>Pages & SEO</span>
                </a>
                <a href="blog.php" class="nav-link <?= $currentPage === 'blog' ? 'active' : '' ?>">
                    <span class="icon">📝</span>
                    <span>Blog</span>
                </a>
                <a href="images.php" class="nav-link <?= $currentPage === 'images' ? 'active' : '' ?>">
                    <span class="icon">🖼️</span>
                    <span>Images</span>
                </a>

                <div class="nav-section">Analytics</div>
                <a href="tracking.php" class="nav-link <?= $currentPage === 'tracking' ? 'active' : '' ?>">
                    <span class="icon">📈</span>
                    <span>Statistiques</span>
                </a>
                <a href="contacts.php" class="nav-link <?= $currentPage === 'contacts' ? 'active' : '' ?>">
                    <span class="icon">📬</span>
                    <span>Messages</span>
                </a>

                <div class="nav-section">Configuration</div>
                <a href="settings.php" class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <span class="icon">⚙️</span>
                    <span>Paramètres</span>
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <header class="header">
                <h1 class="header-title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
                <div class="header-actions">
                    <a href="../index.html" target="_blank" class="btn btn-secondary btn-sm">
                        👁️ Voir le site
                    </a>
                    <div class="header-user">
                        <span>👤 <?= e($_SESSION['admin_username'] ?? 'Admin') ?></span>
                        <a href="logout.php" class="btn btn-sm btn-secondary">Déconnexion</a>
                    </div>
                </div>
            </header>

            <div class="content">
                <?php if ($flash = getFlash()): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?= e($flash['message']) ?>
                </div>
                <?php endif; ?>
