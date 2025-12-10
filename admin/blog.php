<?php
define('ADMIN_LOADED', true);
require_once 'config.php';

$activePage = 'blog';
$pageTitle = 'Gestion des Articles';

// Gestion des actions (suppression, publication)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $postId = $_POST['post_id'] ?? 0;

    try {
        $db = getDB();

        if ($action === 'delete' && $postId) {
            $stmt = $db->prepare("DELETE FROM blog_posts WHERE id = ?");
            $stmt->execute([$postId]);
            setFlash('success', 'Article supprimé avec succès');
        } elseif ($action === 'toggle_publish' && $postId) {
            $stmt = $db->prepare("UPDATE blog_posts SET is_published = NOT is_published WHERE id = ?");
            $stmt->execute([$postId]);
            setFlash('success', 'Statut de l\'article modifié');
        }

        header('Location: blog.php');
        exit;
    } catch (PDOException $e) {
        setFlash('error', 'Erreur lors de l\'opération');
    }
}

// Récupérer tous les articles
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

try {
    $db = getDB();
    $query = "SELECT id, title, slug, category, excerpt, is_published, created_at, updated_at FROM blog_posts WHERE 1=1";
    $params = [];

    if ($filter === 'published') {
        $query .= " AND is_published = 1";
    } elseif ($filter === 'draft') {
        $query .= " AND is_published = 0";
    }

    if ($search) {
        $query .= " AND (title LIKE ? OR excerpt LIKE ? OR category LIKE ?)";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
    }

    $query .= " ORDER BY updated_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $posts = [];
    setFlash('error', 'Erreur lors du chargement des articles');
}

require_once 'includes/header.php';
?>

<!-- Filters and Search -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="flex-between" style="gap: 1rem; flex-wrap: wrap;">
            <div class="flex gap-1">
                <a href="blog.php?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">
                    Tous (<?= count($posts) ?>)
                </a>
                <a href="blog.php?filter=published" class="btn btn-sm <?= $filter === 'published' ? 'btn-primary' : 'btn-secondary' ?>">
                    Publiés
                </a>
                <a href="blog.php?filter=draft" class="btn btn-sm <?= $filter === 'draft' ? 'btn-primary' : 'btn-secondary' ?>">
                    Brouillons
                </a>
            </div>
            <div class="flex gap-1" style="flex: 1; max-width: 400px;">
                <input
                    type="text"
                    name="search"
                    class="form-input"
                    placeholder="Rechercher un article..."
                    value="<?= e($search) ?>"
                >
                <button type="submit" class="btn btn-primary">Rechercher</button>
            </div>
        </form>
    </div>
</div>

<!-- Posts List -->
<div class="card">
    <div class="card-header flex-between">
        <h2 class="card-title">Articles du Blog</h2>
        <a href="blog-new.php" class="btn btn-primary">➕ Nouvel Article</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (count($posts) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Catégorie</th>
                            <th>Statut</th>
                            <th>Créé le</th>
                            <th>Modifié le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr data-edit-url="blog-edit.php?id=<?= $post['id'] ?>">
                                <td>
                                    <strong><?= e($post['title']) ?></strong>
                                    <?php if ($post['excerpt']): ?>
                                        <br><small class="text-gray"><?= e(substr($post['excerpt'], 0, 80)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($post['category']) ?></td>
                                <td>
                                    <?php if ($post['is_published']): ?>
                                        <span class="badge badge-success">✓ Publié</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">📋 Brouillon</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($post['created_at'])) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($post['updated_at'])) ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="blog-edit.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-secondary">
                                            ✏️ Éditer
                                        </a>

                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_publish">
                                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-secondary">
                                                <?= $post['is_published'] ? '👁️‍🗨️ Dépublier' : '✓ Publier' ?>
                                            </button>
                                        </form>

                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                                data-confirm="Êtes-vous sûr de vouloir supprimer cet article ?"
                                            >
                                                🗑️ Supprimer
                                            </button>
                                        </form>
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
                <h3 class="empty-title">Aucun article trouvé</h3>
                <p class="empty-text">
                    <?= $search ? 'Aucun résultat pour votre recherche' : 'Commencez par créer votre premier article' ?>
                </p>
                <a href="blog-new.php" class="btn btn-primary">Créer un Article</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
