<?php
define('ADMIN_LOADED', true);
require_once 'config.php';

$activePage = 'blog';
$postId = $_GET['id'] ?? 0;

if (!$postId) {
    header('Location: blog.php');
    exit;
}

$errors = [];

// Récupérer l'article
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if (!$post) {
        setFlash('error', 'Article introuvable');
        header('Location: blog.php');
        exit;
    }
} catch (PDOException $e) {
    setFlash('error', 'Erreur lors du chargement de l\'article');
    header('Location: blog.php');
    exit;
}

$pageTitle = 'Éditer : ' . $post['title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $formData = [
        'title' => trim($_POST['title'] ?? ''),
        'slug' => trim($_POST['slug'] ?? ''),
        'category' => trim($_POST['category'] ?? ''),
        'excerpt' => trim($_POST['excerpt'] ?? ''),
        'content' => trim($_POST['content'] ?? ''),
        'featured_image' => trim($_POST['featured_image'] ?? ''),
        'meta_title' => trim($_POST['meta_title'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'is_published' => isset($_POST['is_published']) ? 1 : 0
    ];

    // Validation
    if (empty($formData['title'])) {
        $errors[] = 'Le titre est obligatoire';
    }

    if (empty($formData['slug'])) {
        $errors[] = 'Le slug est obligatoire';
    }

    if (empty($formData['content'])) {
        $errors[] = 'Le contenu est obligatoire';
    }

    // Si pas d'erreurs, mettre à jour
    if (empty($errors)) {
        try {
            // Vérifier que le slug est unique (sauf pour cet article)
            $stmt = $db->prepare("SELECT COUNT(*) FROM blog_posts WHERE slug = ? AND id != ?");
            $stmt->execute([$formData['slug'], $postId]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Ce slug existe déjà. Veuillez en choisir un autre.';
            } else {
                $stmt = $db->prepare("
                    UPDATE blog_posts SET
                        title = ?,
                        slug = ?,
                        category = ?,
                        excerpt = ?,
                        content = ?,
                        featured_image = ?,
                        meta_title = ?,
                        meta_description = ?,
                        is_published = ?,
                        published_date = CASE
                            WHEN ? = 1 AND is_published = 0 THEN NOW()
                            WHEN ? = 0 THEN NULL
                            ELSE published_date
                        END
                    WHERE id = ?
                ");

                $stmt->execute([
                    $formData['title'],
                    $formData['slug'],
                    $formData['category'],
                    $formData['excerpt'],
                    $formData['content'],
                    $formData['featured_image'],
                    $formData['meta_title'],
                    $formData['meta_description'],
                    $formData['is_published'],
                    $formData['is_published'],
                    $formData['is_published'],
                    $postId
                ]);

                // Recharger l'article
                $stmt = $db->prepare("SELECT * FROM blog_posts WHERE id = ?");
                $stmt->execute([$postId]);
                $post = $stmt->fetch();

                setFlash('success', 'Article mis à jour avec succès');
            }
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de la mise à jour de l\'article: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Erreurs :</strong>
        <ul style="margin: 0.5rem 0 0 1.5rem;">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-3">
        <div class="card-header flex-between">
            <h2 class="card-title">Informations Principales</h2>
            <div class="flex gap-1">
                <?php if ($post['is_published']): ?>
                    <span class="badge badge-success">✓ Publié</span>
                <?php else: ?>
                    <span class="badge badge-warning">📋 Brouillon</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="title" class="form-label required">Titre de l'article</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    class="form-input"
                    value="<?= e($post['title']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="slug" class="form-label required">Slug (URL)</label>
                <input
                    type="text"
                    id="slug"
                    name="slug"
                    class="form-input"
                    value="<?= e($post['slug']) ?>"
                    required
                >
                <div class="form-help">
                    URL de l'article : <code>article-<?= e($post['slug']) ?>.html</code>
                </div>
            </div>

            <div class="form-group">
                <label for="category" class="form-label">Catégorie</label>
                <input
                    type="text"
                    id="category"
                    name="category"
                    class="form-input"
                    value="<?= e($post['category']) ?>"
                    list="categories"
                >
                <datalist id="categories">
                    <option value="Voyages">
                    <option value="Santé">
                    <option value="Conseils">
                    <option value="Découverte">
                    <option value="Écologie">
                    <option value="Événements">
                </datalist>
            </div>

            <div class="form-group">
                <label for="excerpt" class="form-label">Extrait</label>
                <textarea
                    id="excerpt"
                    name="excerpt"
                    class="form-textarea"
                    rows="3"
                    data-maxlength="300"
                ><?= e($post['excerpt']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="featured_image" class="form-label">Image à la une</label>
                <input
                    type="text"
                    id="featured_image"
                    name="featured_image"
                    class="form-input"
                    value="<?= e($post['featured_image']) ?>"
                >
                <div class="form-help">Emoji ou URL d'une image</div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h2 class="card-title">Contenu de l'article</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="content" class="form-label required">Contenu (HTML)</label>
                <div class="editor-toolbar">
                    <button type="button" class="editor-btn" data-command="bold"><strong>B</strong></button>
                    <button type="button" class="editor-btn" data-command="italic"><em>I</em></button>
                    <button type="button" class="editor-btn" data-command="h2">H2</button>
                    <button type="button" class="editor-btn" data-command="h3">H3</button>
                    <button type="button" class="editor-btn" data-command="link">🔗</button>
                    <button type="button" class="editor-btn" data-command="list">• Liste</button>
                </div>
                <textarea
                    id="content"
                    name="content"
                    class="form-textarea editor-content"
                    rows="20"
                    required
                ><?= e($post['content']) ?></textarea>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h2 class="card-title">SEO</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="meta_title" class="form-label">Titre Meta</label>
                <input
                    type="text"
                    id="meta_title"
                    name="meta_title"
                    class="form-input"
                    value="<?= e($post['meta_title']) ?>"
                    data-maxlength="60"
                >
            </div>

            <div class="form-group">
                <label for="meta_description" class="form-label">Description Meta</label>
                <textarea
                    id="meta_description"
                    name="meta_description"
                    class="form-textarea"
                    rows="3"
                    data-maxlength="160"
                ><?= e($post['meta_description']) ?></textarea>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h2 class="card-title">Publication</h2>
        </div>
        <div class="card-body">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input
                    type="checkbox"
                    name="is_published"
                    value="1"
                    <?= $post['is_published'] ? 'checked' : '' ?>
                >
                <span>Article publié</span>
            </label>
            <div class="form-help mt-1">
                Date de création : <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?><br>
                Dernière modification : <?= date('d/m/Y H:i', strtotime($post['updated_at'])) ?>
                <?php if ($post['published_date']): ?>
                    <br>Date de publication : <?= date('d/m/Y H:i', strtotime($post['published_date'])) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-lg">
            💾 Enregistrer les modifications
        </button>
        <a href="blog.php" class="btn btn-secondary btn-lg">
            Retour à la liste
        </a>
        <a href="../article-<?= e($post['slug']) ?>.html" target="_blank" class="btn btn-secondary btn-lg">
            👁️ Voir l'article
        </a>
    </div>
</form>

<?php require_once 'includes/footer.php'; ?>
