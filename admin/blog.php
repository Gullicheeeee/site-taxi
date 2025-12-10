<?php
$pageTitle = 'Gestion du Blog';
require_once 'includes/header.php';

$db = getDB();

// Supprimer un article
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Article supprimé');
    header('Location: blog.php');
    exit;
}

// Changer le statut
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE blog_posts SET is_published = NOT is_published, published_at = CASE WHEN is_published = 0 THEN datetime('now') ELSE published_at END WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: blog.php');
    exit;
}

// Récupérer les articles
$posts = $db->query("SELECT * FROM blog_posts ORDER BY created_at DESC")->fetchAll();
?>

<div class="page-header d-flex justify-between align-center">
    <div>
        <h2 class="page-title">Gestion du Blog</h2>
        <p class="page-subtitle">Créez et gérez vos articles de blog</p>
    </div>
    <a href="blog-edit.php" class="btn btn-primary">+ Nouvel article</a>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Tous les articles (<?= count($posts) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($posts)): ?>
        <div style="text-align: center; padding: 3rem;">
            <p style="font-size: 3rem; margin-bottom: 1rem;">📝</p>
            <p style="color: var(--gray-500); margin-bottom: 1rem;">Aucun article pour le moment</p>
            <a href="blog-edit.php" class="btn btn-primary">Créer votre premier article</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Meta Title</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <strong><?= e($post['title']) ?></strong>
                            <br><small style="color: var(--gray-500);">slug: <?= e($post['slug']) ?></small>
                        </td>
                        <td>
                            <small><?= e(substr($post['meta_title'] ?? '', 0, 40)) ?><?= strlen($post['meta_title'] ?? '') > 40 ? '...' : '' ?></small>
                        </td>
                        <td>
                            <?php if ($post['is_published']): ?>
                            <span class="badge badge-success">Publié</span>
                            <?php else: ?>
                            <span class="badge badge-warning">Brouillon</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= date('d/m/Y', strtotime($post['created_at'])) ?></small>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="blog-edit.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-primary">Modifier</a>
                                <a href="?toggle=<?= $post['id'] ?>" class="btn btn-sm btn-secondary">
                                    <?= $post['is_published'] ? 'Dépublier' : 'Publier' ?>
                                </a>
                                <a href="?delete=<?= $post['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cet article ?')">🗑️</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Conseils SEO pour le Blog</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <div>
                <h4 style="margin-bottom: 0.5rem;">📌 Titres accrocheurs</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Utilisez des chiffres, questions ou mots puissants. Ex: "5 conseils pour..."
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">🔗 URLs optimisées</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Gardez le slug court et descriptif avec vos mots-clés principaux.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">🖼️ Images optimisées</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Ajoutez une image à la une avec un texte alt descriptif.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">📝 Contenu de qualité</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Minimum 500 mots, structuré avec des sous-titres H2, H3.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
