<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'Gestion des Pages';

// Creer une nouvelle page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_page'])) {
    $title = trim($_POST['new_page_title'] ?? '');
    $slug = trim($_POST['new_page_slug'] ?? '');

    if (!empty($title)) {
        // Generer le slug si vide
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
            $slug = trim($slug, '-');
        }

        // Verifier que le slug n'existe pas deja
        $check = supabase()->select('pages', 'slug=eq.' . urlencode($slug));
        if ($check['success'] && !empty($check['data'])) {
            $slug = $slug . '-' . time();
        }

        $newPage = [
            'title' => $title,
            'slug' => $slug,
            'hero_title' => $title,
            'hero_subtitle' => '',
            'meta_title' => $title,
            'meta_description' => '',
            'status' => 'draft'
        ];

        $result = supabase()->insert('pages', $newPage);

        if ($result['success'] && !empty($result['data'])) {
            $newId = $result['data'][0]['id'];
            setFlash('success', 'Page creee ! Vous pouvez maintenant la modifier.');
            header('Location: page-edit.php?id=' . urlencode($newId));
            exit;
        } else {
            setFlash('danger', 'Erreur lors de la creation de la page');
        }
    } else {
        setFlash('danger', 'Le titre de la page est obligatoire');
    }

    header('Location: pages.php');
    exit;
}

// Supprimer une page
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Supprimer les sections associees
    supabase()->delete('page_sections', 'page_id=eq.' . urlencode($id));
    // Supprimer la page
    supabase()->delete('pages', 'id=eq.' . urlencode($id));
    setFlash('success', 'Page supprimee');
    header('Location: pages.php');
    exit;
}

// Toggle publication d'une page
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $result = supabase()->select('pages', 'id=eq.' . urlencode($id));
    if ($result['success'] && !empty($result['data'])) {
        $page = $result['data'][0];
        $newStatus = isset($page['status']) && $page['status'] === 'published' ? 'draft' : 'published';
        supabase()->update('pages', 'id=eq.' . urlencode($id), [
            'status' => $newStatus,
            'updated_at' => date('c')
        ]);
        setFlash('success', $newStatus === 'published' ? 'Page publiee' : 'Page depubliee');
    }
    header('Location: pages.php');
    exit;
}

// Recuperer toutes les pages
$result = supabase()->select('pages', 'order=title.asc');
$pages = $result['success'] ? $result['data'] : [];

require_once 'includes/header.php';
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 class="page-title">Gestion des Pages</h2>
        <p class="page-subtitle">Modifiez le contenu, le SEO et les images de chaque page</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('create-modal').classList.add('active')">
        + Nouvelle page
    </button>
</div>

<!-- Modal creation de page -->
<div class="modal" id="create-modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Creer une nouvelle page</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('create-modal').classList.remove('active')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="create_page" value="1">
                <div class="form-group">
                    <label class="form-label">Titre de la page *</label>
                    <input type="text" name="new_page_title" class="form-control" placeholder="Ex: Nos Tarifs" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Slug (URL)</label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="color: var(--gray-500);">votresite.fr/</span>
                        <input type="text" name="new_page_slug" class="form-control" style="flex: 1;" placeholder="nos-tarifs"
                               pattern="[a-z0-9-]+" oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '-')">
                        <span style="color: var(--gray-500);">.html</span>
                    </div>
                    <p class="form-help">Laissez vide pour generer automatiquement depuis le titre</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('create-modal').classList.remove('active')">Annuler</button>
                <button type="submit" class="btn btn-primary">Creer la page</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">Toutes les pages (<?= count($pages) ?>)</h3>
    </div>
    <div class="card-body">
        <style>
.page-status { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500; }
.page-status.published { background: #dcfce7; color: #166534; }
.page-status.draft { background: #fef3c7; color: #92400e; }
.page-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
</style>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Titre Hero</th>
                        <th>Statut</th>
                        <th>Meta Title</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $page):
                        $status = $page['status'] ?? 'published';
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($page['title']) ?></strong>
                            <br><small style="color: var(--gray-500);">slug: <?= e($page['slug']) ?></small>
                        </td>
                        <td>
                            <small><?= e(substr($page['hero_title'] ?? '', 0, 40)) ?><?= strlen($page['hero_title'] ?? '') > 40 ? '...' : '' ?></small>
                        </td>
                        <td>
                            <?php if ($status === 'published'): ?>
                            <span class="page-status published">Publiee</span>
                            <?php else: ?>
                            <span class="page-status draft">Brouillon</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= e(substr($page['meta_title'] ?? '', 0, 35)) ?><?= strlen($page['meta_title'] ?? '') > 35 ? '...' : '' ?></small>
                        </td>
                        <td>
                            <div class="page-actions">
                                <a href="page-edit.php?id=<?= e($page['id']) ?>" class="btn btn-primary btn-sm">
                                    Modifier
                                </a>
                                <a href="?toggle=<?= e($page['id']) ?>" class="btn btn-secondary btn-sm">
                                    <?= $status === 'published' ? 'Depublier' : 'Publier' ?>
                                </a>
                                <a href="?delete=<?= e($page['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cette page et toutes ses sections ?')">
                                    Suppr.
                                </a>
                            </div>
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
        <h3 class="card-title">Aide - Structure des pages</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <div>
                <h4 style="margin-bottom: 0.5rem;">🎯 Hero Section</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Titre principal, sous-titre et image de fond optionnelle en haut de chaque page.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">📝 SEO</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Meta title (50-60 car.), meta description (150-160 car.) et mots-clés.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">📦 Sections</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Blocs de contenu éditables : cartes, textes, images, etc.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
