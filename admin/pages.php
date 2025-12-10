<?php
$pageTitle = 'Pages & SEO';
require_once 'includes/header.php';

$db = getDB();

// Récupérer toutes les pages
$pages = $db->query("SELECT * FROM pages ORDER BY title")->fetchAll();
?>

<div class="page-header">
    <h2 class="page-title">Gestion des Pages & SEO</h2>
    <p class="page-subtitle">Modifiez les meta-titres, descriptions et mots-clés de chaque page</p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Toutes les pages</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Meta Title</th>
                        <th>Meta Description</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $page): ?>
                    <tr>
                        <td>
                            <strong><?= e($page['title']) ?></strong>
                            <br><small style="color: var(--gray-500);"><?= e($page['filename']) ?></small>
                        </td>
                        <td>
                            <small><?= e(substr($page['meta_title'] ?? '', 0, 50)) ?><?= strlen($page['meta_title'] ?? '') > 50 ? '...' : '' ?></small>
                        </td>
                        <td>
                            <small><?= e(substr($page['meta_description'] ?? '', 0, 80)) ?><?= strlen($page['meta_description'] ?? '') > 80 ? '...' : '' ?></small>
                        </td>
                        <td>
                            <a href="page-edit.php?id=<?= $page['id'] ?>" class="btn btn-primary btn-sm">
                                Modifier
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Guide SEO</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <div>
                <h4 style="margin-bottom: 0.5rem;">📝 Meta Title</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Entre 50 et 60 caractères. Inclure le mot-clé principal au début.
                    Format recommandé : "Mot-clé - Nom du site"
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">📄 Meta Description</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Entre 150 et 160 caractères. Description attractive avec appel à l'action.
                    Inclure les mots-clés naturellement.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">🔑 Mots-clés</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    5-10 mots-clés pertinents séparés par des virgules.
                    Exemple : taxi martigues, taxi conventionné, cpam
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
