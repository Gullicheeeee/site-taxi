<?php
define('ADMIN_LOADED', true);
require_once 'config.php';

$activePage = 'pages';
$pageTitle = 'Gestion des Pages';

// Récupérer toutes les pages
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT p.*, u.username
        FROM pages p
        LEFT JOIN users u ON p.updated_by = u.id
        ORDER BY p.slug
    ");
    $pages = $stmt->fetchAll();
} catch (PDOException $e) {
    $pages = [];
    setFlash('error', 'Erreur lors du chargement des pages');
}

require_once 'includes/header.php';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2 class="card-title">Pages du Site</h2>
        <span class="badge badge-info"><?= count($pages) ?> pages</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (count($pages) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Titre</th>
                            <th>Meta Description</th>
                            <th>Statut</th>
                            <th>Modifié le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                            <tr>
                                <td>
                                    <strong><?= e($page['slug']) ?>.html</strong>
                                </td>
                                <td><?= e($page['title']) ?></td>
                                <td>
                                    <?php if ($page['meta_description']): ?>
                                        <?= e(substr($page['meta_description'], 0, 60)) ?>...
                                    <?php else: ?>
                                        <span class="text-gray">Aucune</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($page['is_published']): ?>
                                        <span class="badge badge-success">✓ Publié</span>
                                    <?php else: ?>
                                        <span class="badge badge-gray">Brouillon</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('d/m/Y H:i', strtotime($page['updated_at'])) ?>
                                    <?php if ($page['username']): ?>
                                        <br><small class="text-gray">par <?= e($page['username']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="page-edit.php?id=<?= $page['id'] ?>" class="btn btn-sm btn-secondary">
                                            ✏️ Éditer
                                        </a>
                                        <a href="../<?= e($page['slug']) ?>.html" target="_blank" class="btn btn-sm btn-secondary">
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
                <div class="empty-icon">📄</div>
                <h3 class="empty-title">Aucune page</h3>
                <p class="empty-text">Commencez par initialiser vos pages</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-3" style="background: var(--gray-50);">
    <div class="card-body">
        <h3 style="margin-bottom: 1rem;">💡 Informations</h3>
        <p style="margin-bottom: 0.5rem;">
            Cette section vous permet de gérer les <strong>métadonnées SEO</strong> et les <strong>textes modifiables</strong> de chaque page du site.
        </p>
        <p style="margin-bottom: 0;">
            Pour modifier le contenu d'une page, cliquez sur "Éditer" pour accéder aux sections éditables.
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
