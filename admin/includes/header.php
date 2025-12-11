<?php
require_once __DIR__ . '/../config.php';
requireLogin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Récupérer les notifications (alertes SEO non résolues)
$notifCount = 0;
$notifications = [];

// Compter les problèmes SEO
$pagesResult = supabase()->select('pages', 'select=id,title,meta_title,meta_description');
if ($pagesResult['success']) {
    foreach ($pagesResult['data'] as $p) {
        if (empty($p['meta_title'])) {
            $notifications[] = ['type' => 'warning', 'message' => "Page \"{$p['title']}\" sans meta title", 'link' => "page-edit.php?id={$p['id']}"];
        }
        if (empty($p['meta_description'])) {
            $notifications[] = ['type' => 'warning', 'message' => "Page \"{$p['title']}\" sans meta description", 'link' => "page-edit.php?id={$p['id']}"];
        }
    }
}
$notifCount = count($notifications);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Back-Office') ?> - Taxi Julien Admin</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
    /* Modern Icons */
    .icon-svg {
        width: 20px;
        height: 20px;
        stroke: currentColor;
        stroke-width: 1.5;
        fill: none;
        flex-shrink: 0;
    }
    .sidebar-logo .icon-svg {
        width: 24px;
        height: 24px;
    }

    /* Header Search & Notifications */
    .header-search {
        position: relative;
        flex: 1;
        max-width: 400px;
        margin: 0 2rem;
    }
    .header-search input {
        width: 100%;
        padding: 0.5rem 1rem 0.5rem 2.5rem;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        font-size: 0.9rem;
        background: var(--gray-50);
        transition: all 0.2s;
    }
    .header-search input:focus {
        outline: none;
        border-color: var(--primary);
        background: white;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .header-search .search-icon {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-400);
        display: flex;
    }
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        margin-top: 0.5rem;
        max-height: 400px;
        overflow-y: auto;
        display: none;
        z-index: 1000;
    }
    .search-results.active {
        display: block;
    }
    .search-result-item {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--gray-100);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        color: inherit;
    }
    .search-result-item:hover {
        background: var(--gray-50);
    }
    .search-result-item:last-child {
        border-bottom: none;
    }
    .search-result-icon {
        width: 32px;
        height: 32px;
        background: var(--gray-100);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gray-500);
    }
    .search-result-title {
        font-weight: 500;
    }
    .search-result-type {
        font-size: 0.75rem;
        color: var(--gray-500);
    }

    /* Notifications */
    .notif-btn {
        position: relative;
        background: none;
        border: none;
        padding: 0.5rem;
        cursor: pointer;
        color: var(--gray-600);
        border-radius: 8px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .notif-btn:hover {
        background: var(--gray-100);
    }
    .notif-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        background: var(--danger);
        color: white;
        font-size: 0.6rem;
        font-weight: 600;
        min-width: 16px;
        height: 16px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
    }
    .notif-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        width: 320px;
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        margin-top: 0.5rem;
        display: none;
        z-index: 1000;
    }
    .notif-dropdown.active {
        display: block;
    }
    .notif-header {
        padding: 1rem;
        border-bottom: 1px solid var(--gray-200);
        font-weight: 600;
    }
    .notif-list {
        max-height: 300px;
        overflow-y: auto;
    }
    .notif-item {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--gray-100);
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        text-decoration: none;
        color: inherit;
        transition: background 0.2s;
    }
    .notif-item:hover {
        background: var(--gray-50);
    }
    .notif-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .notif-icon.warning {
        background: #fef3c7;
        color: #d97706;
    }
    .notif-icon.error {
        background: #fee2e2;
        color: #dc2626;
    }
    .notif-content {
        flex: 1;
        font-size: 0.85rem;
        line-height: 1.4;
    }
    .notif-footer {
        padding: 0.75rem 1rem;
        border-top: 1px solid var(--gray-200);
        text-align: center;
    }
    .notif-footer a {
        font-size: 0.85rem;
        color: var(--primary);
        text-decoration: none;
    }

    /* Mobile menu toggle */
    .mobile-toggle {
        display: none;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.5rem;
        color: var(--gray-600);
    }
    @media (max-width: 1024px) {
        .mobile-toggle {
            display: flex;
        }
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s;
        }
        .sidebar.open {
            transform: translateX(0);
        }
        .header-search {
            display: none;
        }
    }

    /* User avatar */
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.85rem;
    }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <svg class="icon-svg" viewBox="0 0 24 24"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18 10l-1.8-3.6c-.3-.6-.9-1-1.5-1H9.3c-.6 0-1.2.4-1.5 1L6 10l-2.5 1.1C2.7 11.3 2 12.1 2 13v3c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                    <span>Taxi Julien</span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">Principal</div>
                <a href="index.php" class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    <span>Dashboard</span>
                </a>

                <div class="nav-section">Contenu</div>
                <a href="pages.php" class="nav-link <?= in_array($currentPage, ['pages', 'page-edit']) ? 'active' : '' ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <span>Pages</span>
                </a>
                <a href="blog.php" class="nav-link <?= in_array($currentPage, ['blog', 'blog-edit']) ? 'active' : '' ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>
                    <span>Articles</span>
                </a>
                <a href="categories.php" class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    <span>Catégories</span>
                </a>
                <a href="media.php" class="nav-link <?= $currentPage === 'media' ? 'active' : '' ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>Médiathèque</span>
                </a>

                <div class="nav-section">SEO</div>
                <a href="seo-audit.php" class="nav-link <?= $currentPage === 'seo-audit' ? 'active' : '' ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <span>Audit SEO</span>
                </a>
                <a href="redirections.php" class="nav-link <?= $currentPage === 'redirections' ? 'active' : '' ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24"><polyline points="15 14 20 9 15 4"/><path d="M4 20v-7a4 4 0 0 1 4-4h12"/></svg>
                    <span>Redirections</span>
                </a>
                <a href="sitemap.php" class="nav-link <?= $currentPage === 'sitemap' ? 'active' : '' ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                    <span>Sitemap</span>
                </a>
                <a href="datalayer.php" class="nav-link <?= $currentPage === 'datalayer' ? 'active' : '' ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                    <span>DataLayer</span>
                </a>

                <div class="nav-section">Outils</div>
                <a href="simulateur-config.php" class="nav-link <?= $currentPage === 'simulateur-config' ? 'active' : '' ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    <span>Simulateur & Tarifs</span>
                </a>

                <div class="nav-section">Apparence</div>
                <a href="menus.php" class="nav-link <?= $currentPage === 'menus' ? 'active' : '' ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    <span>Menus</span>
                </a>
                <a href="apparence.php" class="nav-link <?= $currentPage === 'apparence' ? 'active' : '' ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="19" cy="17" r="2"/><circle cx="6" cy="12" r="3"/><path d="M12 6.5c0 3.5-6 10-6 10"/><path d="M16 17c-2 0-4.5-5-4.5-10.5"/><path d="M19 17c-3 0-5-6-5-10.5"/></svg>
                    <span>Personnalisation</span>
                </a>

                <div class="nav-section">Administration</div>
                <a href="utilisateurs.php" class="nav-link <?= $currentPage === 'utilisateurs' ? 'active' : '' ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>Utilisateurs</span>
                </a>
                <a href="settings.php" class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Réglages</span>
                </a>
            </nav>

            <div style="padding: 1rem; margin-top: auto; border-top: 1px solid var(--gray-200);">
                <a href="../index.html" target="_blank" style="display: flex; align-items: center; gap: 0.5rem; color: var(--gray-600); text-decoration: none; font-size: 0.85rem;">
                    <svg class="icon-svg" style="width: 18px; height: 18px;" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    <span>Voir le site</span>
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <header class="header">
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <svg class="icon-svg" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <h1 class="header-title"><?= e($pageTitle ?? 'Dashboard') ?></h1>

                <!-- Recherche globale -->
                <div class="header-search">
                    <span class="search-icon">
                        <svg class="icon-svg" style="width: 18px; height: 18px;" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </span>
                    <input type="text" id="global-search" placeholder="Rechercher pages, articles, médias..." autocomplete="off">
                    <div class="search-results" id="search-results"></div>
                </div>

                <div class="header-actions">
                    <!-- Notifications -->
                    <div style="position: relative;">
                        <button class="notif-btn" onclick="toggleNotifications()">
                            <svg class="icon-svg" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            <?php if ($notifCount > 0): ?>
                            <span class="notif-badge"><?= min($notifCount, 9) ?><?= $notifCount > 9 ? '+' : '' ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="notif-dropdown" id="notif-dropdown">
                            <div class="notif-header">
                                Notifications (<?= $notifCount ?>)
                            </div>
                            <div class="notif-list">
                                <?php if (empty($notifications)): ?>
                                <div style="padding: 2rem; text-align: center; color: var(--gray-500);">
                                    Aucune notification
                                </div>
                                <?php else: ?>
                                <?php foreach (array_slice($notifications, 0, 5) as $notif): ?>
                                <a href="<?= e($notif['link']) ?>" class="notif-item">
                                    <div class="notif-icon <?= $notif['type'] ?>">
                                        <svg class="icon-svg" style="width: 16px; height: 16px;" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                    </div>
                                    <div class="notif-content"><?= e($notif['message']) ?></div>
                                </a>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php if ($notifCount > 5): ?>
                            <div class="notif-footer">
                                <a href="seo-audit.php">Voir tout</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="header-user">
                        <div class="user-avatar"><?= strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)) ?></div>
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

<script>
// Données pour la recherche
const searchData = {
    pages: <?= json_encode(array_map(fn($p) => ['id' => $p['id'], 'title' => $p['title'], 'slug' => $p['slug']], $pagesResult['data'] ?? []), JSON_UNESCAPED_UNICODE) ?>,
    posts: []
};

// Recherche globale
const searchInput = document.getElementById('global-search');
const searchResults = document.getElementById('search-results');

searchInput?.addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    if (query.length < 2) {
        searchResults.classList.remove('active');
        return;
    }

    let results = [];

    // Rechercher dans les pages
    searchData.pages.forEach(page => {
        if (page.title.toLowerCase().includes(query) || page.slug.toLowerCase().includes(query)) {
            results.push({
                type: 'Page',
                title: page.title,
                url: 'page-edit.php?id=' + page.id
            });
        }
    });

    if (results.length === 0) {
        searchResults.innerHTML = '<div style="padding: 1rem; text-align: center; color: var(--gray-500);">Aucun résultat</div>';
    } else {
        searchResults.innerHTML = results.slice(0, 8).map(r => `
            <a href="${r.url}" class="search-result-item">
                <div class="search-result-icon">
                    <svg class="icon-svg" style="width: 16px; height: 16px;" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div>
                    <div class="search-result-title">${r.title}</div>
                    <div class="search-result-type">${r.type}</div>
                </div>
            </a>
        `).join('');
    }
    searchResults.classList.add('active');
});

// Fermer recherche au clic extérieur
document.addEventListener('click', function(e) {
    if (!e.target.closest('.header-search')) {
        searchResults?.classList.remove('active');
    }
    if (!e.target.closest('.notif-btn') && !e.target.closest('.notif-dropdown')) {
        document.getElementById('notif-dropdown')?.classList.remove('active');
    }
});

// Notifications
function toggleNotifications() {
    document.getElementById('notif-dropdown')?.classList.toggle('active');
}

// Mobile sidebar
function toggleSidebar() {
    document.getElementById('sidebar')?.classList.toggle('open');
}

// Scroll to active menu item on page load
document.addEventListener('DOMContentLoaded', function() {
    const activeLink = document.querySelector('.nav-link.active');
    if (activeLink) {
        activeLink.scrollIntoView({ block: 'center', behavior: 'instant' });
    }
});
</script>
