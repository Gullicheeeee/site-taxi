<?php
define('ADMIN_LOADED', true);
require_once 'config.php';

$activePage = 'dashboard';
$pageTitle = 'Dashboard';

// Récupérer les statistiques
try {
    $db = getDB();

    // Nombre d'articles publiés
    $stmt = $db->query("SELECT COUNT(*) as count FROM blog_posts WHERE is_published = 1");
    $publishedPosts = $stmt->fetch()['count'];

    // Nombre d'articles en brouillon
    $stmt = $db->query("SELECT COUNT(*) as count FROM blog_posts WHERE is_published = 0");
    $draftPosts = $stmt->fetch()['count'];

    // Nombre total de pages
    $stmt = $db->query("SELECT COUNT(*) as count FROM pages");
    $totalPages = $stmt->fetch()['count'];

    // Nombre d'images
    $stmt = $db->query("SELECT COUNT(*) as count FROM media");
    $totalMedia = $stmt->fetch()['count'];

    // Derniers articles
    $stmt = $db->query("
        SELECT id, title, slug, category, is_published, created_at, updated_at
        FROM blog_posts
        ORDER BY updated_at DESC
        LIMIT 5
    ");
    $recentPosts = $stmt->fetchAll();

} catch (PDOException $e) {
    $publishedPosts = 0;
    $draftPosts = 0;
    $totalPages = 0;
    $totalMedia = 0;
    $recentPosts = [];
}

require_once 'includes/header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon success">
            📝
        </div>
        <div class="stat-content">
            <h3>Articles Publiés</h3>
            <div class="stat-value"><?= $publishedPosts ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon warning">
            📋
        </div>
        <div class="stat-content">
            <h3>Brouillons</h3>
            <div class="stat-value"><?= $draftPosts ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon primary">
            📄
        </div>
        <div class="stat-content">
            <h3>Pages</h3>
            <div class="stat-value"><?= $totalPages ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon info">
            🖼️
        </div>
        <div class="stat-content">
            <h3>Médias</h3>
            <div class="stat-value"><?= $totalMedia ?></div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-3">
    <div class="card-header">
        <h2 class="card-title">Actions Rapides</h2>
    </div>
    <div class="card-body">
        <div class="flex gap-2" style="flex-wrap: wrap;">
            <a href="blog-new.php" class="btn btn-primary">
                ➕ Nouvel Article
            </a>
            <a href="blog.php" class="btn btn-secondary">
                📝 Gérer les Articles
            </a>
            <a href="media.php" class="btn btn-secondary">
                🖼️ Bibliothèque Médias
            </a>
            <a href="pages.php" class="btn btn-secondary">
                📄 Gérer les Pages
            </a>
        </div>
    </div>
</div>

<!-- Recent Posts -->
<div class="card">
    <div class="card-header flex-between">
        <h2 class="card-title">Articles Récents</h2>
        <a href="blog.php" class="btn btn-sm btn-secondary">Voir tout</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (count($recentPosts) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Catégorie</th>
                            <th>Statut</th>
                            <th>Modifié le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPosts as $post): ?>
                            <tr>
                                <td>
                                    <strong><?= e($post['title']) ?></strong>
                                </td>
                                <td><?= e($post['category']) ?></td>
                                <td>
                                    <?php if ($post['is_published']): ?>
                                        <span class="badge badge-success">Publié</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Brouillon</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($post['updated_at'])) ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="blog-edit.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-secondary">
                                            ✏️ Éditer
                                        </a>
                                        <a href="../article-<?= e($post['slug']) ?>.html" target="_blank" class="btn btn-sm btn-secondary">
                                            👁️ Voir
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📝</div>
                <h3 class="empty-title">Aucun article</h3>
                <p class="empty-text">Commencez par créer votre premier article de blog</p>
                <a href="blog-new.php" class="btn btn-primary">Créer un Article</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Welcome Message -->
<?php if (!isset($_SESSION['dashboard_visited'])): ?>
    <div class="card mt-3" style="background: linear-gradient(135deg, #1a2f4f 0%, #2d4a73 100%); color: white;">
        <div class="card-body">
            <h2 style="color: white; margin-bottom: 1rem;">👋 Bienvenue dans votre Back-Office !</h2>
            <p style="margin-bottom: 1rem; opacity: 0.9;">
                Vous pouvez maintenant gérer facilement le contenu de votre site : articles de blog, pages, images et paramètres.
            </p>
            <div class="flex gap-2" style="flex-wrap: wrap;">
                <a href="blog-new.php" class="btn btn-primary">Créer votre premier article</a>
                <a href="settings.php" class="btn btn-secondary">Configurer le site</a>
            </div>
        </div>
    </div>
    <?php $_SESSION['dashboard_visited'] = true; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
