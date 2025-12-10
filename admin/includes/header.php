<?php
if (!defined('ADMIN_LOADED')) {
    die('Accès direct interdit');
}
requireLogin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - Back-Office Taxi Julien</title>
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <span class="logo-icon">🚖</span>
                    <span class="logo-text">Taxi Julien</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>

                <div class="nav-section">Pages & SEO</div>

                <a href="pages.php" class="nav-item <?= $activePage === 'pages' ? 'active' : '' ?>">
                    <span class="nav-icon">📄</span>
                    <span class="nav-text">Pages</span>
                </a>

                <a href="seo.php" class="nav-item <?= $activePage === 'seo' ? 'active' : '' ?>">
                    <span class="nav-icon">🔍</span>
                    <span class="nav-text">SEO</span>
                </a>

                <div class="nav-section">Blog</div>

                <a href="blog.php" class="nav-item <?= $activePage === 'blog' ? 'active' : '' ?>">
                    <span class="nav-icon">📝</span>
                    <span class="nav-text">Articles</span>
                </a>

                <a href="blog-new.php" class="nav-item">
                    <span class="nav-icon">➕</span>
                    <span class="nav-text">Nouvel article</span>
                </a>

                <div class="nav-section">Médias</div>

                <a href="media.php" class="nav-item <?= $activePage === 'media' ? 'active' : '' ?>">
                    <span class="nav-icon">🖼️</span>
                    <span class="nav-text">Bibliothèque</span>
                </a>

                <div class="nav-section">Paramètres</div>

                <a href="settings.php" class="nav-item <?= $activePage === 'settings' ? 'active' : '' ?>">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Paramètres</span>
                </a>

                <a href="tarifs.php" class="nav-item <?= $activePage === 'tarifs' ? 'active' : '' ?>">
                    <span class="nav-icon">💰</span>
                    <span class="nav-text">Tarifs</span>
                </a>

                <?php if (isAdmin()): ?>
                <a href="users.php" class="nav-item <?= $activePage === 'users' ? 'active' : '' ?>">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Utilisateurs</span>
                </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['admin_username'], 0, 1)) ?></div>
                    <div class="user-details">
                        <div class="user-name"><?= e($_SESSION['admin_username']) ?></div>
                        <div class="user-role"><?= e($_SESSION['admin_role']) ?></div>
                    </div>
                </div>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <h1 class="page-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
                <div class="topbar-actions">
                    <a href="../index.html" target="_blank" class="btn btn-secondary">
                        Voir le site
                    </a>
                </div>
            </div>

            <div class="content-wrapper">
                <?php
                $flash = getFlash();
                if ($flash):
                ?>
                    <div class="alert alert-<?= $flash['type'] ?>">
                        <?= e($flash['message']) ?>
                    </div>
                <?php endif; ?>
