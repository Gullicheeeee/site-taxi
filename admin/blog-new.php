<?php
define('ADMIN_LOADED', true);
require_once 'config.php';

$activePage = 'blog';
$pageTitle = 'Nouvel Article';

$errors = [];
$formData = [
    'title' => '',
    'slug' => '',
    'category' => '',
    'excerpt' => '',
    'content' => '',
    'featured_image' => '',
    'meta_title' => '',
    'meta_description' => '',
    'is_published' => 0
];

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

    // Si pas d'erreurs, enregistrer
    if (empty($errors)) {
        try {
            $db = getDB();

            // Vérifier que le slug est unique
            $stmt = $db->prepare("SELECT COUNT(*) FROM blog_posts WHERE slug = ?");
            $stmt->execute([$formData['slug']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Ce slug existe déjà. Veuillez en choisir un autre.';
            } else {
                $stmt = $db->prepare("
                    INSERT INTO blog_posts (
                        title, slug, category, excerpt, content, featured_image,
                        meta_title, meta_description, author_id, is_published,
                        published_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $publishedDate = $formData['is_published'] ? date('Y-m-d H:i:s') : null;

                $stmt->execute([
                    $formData['title'],
                    $formData['slug'],
                    $formData['category'],
                    $formData['excerpt'],
                    $formData['content'],
                    $formData['featured_image'],
                    $formData['meta_title'],
                    $formData['meta_description'],
                    $_SESSION['admin_id'],
                    $formData['is_published'],
                    $publishedDate
                ]);

                setFlash('success', 'Article créé avec succès');
                header('Location: blog-edit.php?id=' . $db->lastInsertId());
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de la création de l\'article: ' . $e->getMessage();
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
        <div class="card-header">
            <h2 class="card-title">Informations Principales</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="title" class="form-label required">Titre de l'article</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    class="form-input"
                    value="<?= e($formData['title']) ?>"
                    required
                    placeholder="Ex: 10 Conseils pour Préparer votre Transfert Aéroport"
                >
            </div>

            <div class="form-group">
                <label for="slug" class="form-label required">Slug (URL)</label>
                <input
                    type="text"
                    id="slug"
                    name="slug"
                    class="form-input"
                    value="<?= e($formData['slug']) ?>"
                    required
                    placeholder="Ex: conseils-transfert-aeroport"
                >
                <div class="form-help">Le slug sera utilisé dans l'URL de l'article</div>
            </div>

            <div class="form-group">
                <label for="category" class="form-label">Catégorie</label>
                <input
                    type="text"
                    id="category"
                    name="category"
                    class="form-input"
                    value="<?= e($formData['category']) ?>"
                    placeholder="Ex: Voyages, Santé, Conseils..."
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
                    placeholder="Résumé court de l'article (affiché dans la liste des articles)"
                    data-maxlength="300"
                ><?= e($formData['excerpt']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="featured_image" class="form-label">Image à la une (emoji ou URL)</label>
                <input
                    type="text"
                    id="featured_image"
                    name="featured_image"
                    class="form-input"
                    value="<?= e($formData['featured_image']) ?>"
                    placeholder="Ex: ✈️ ou /uploads/image.jpg"
                >
                <div class="form-help">Entrez un emoji ou l'URL d'une image</div>
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
                    <button type="button" class="editor-btn" data-command="bold" title="Gras">
                        <strong>B</strong>
                    </button>
                    <button type="button" class="editor-btn" data-command="italic" title="Italique">
                        <em>I</em>
                    </button>
                    <button type="button" class="editor-btn" data-command="h2" title="Titre H2">
                        H2
                    </button>
                    <button type="button" class="editor-btn" data-command="h3" title="Titre H3">
                        H3
                    </button>
                    <button type="button" class="editor-btn" data-command="link" title="Lien">
                        🔗
                    </button>
                    <button type="button" class="editor-btn" data-command="list" title="Liste">
                        • Liste
                    </button>
                </div>
                <textarea
                    id="content"
                    name="content"
                    class="form-textarea editor-content"
                    rows="20"
                    required
                    placeholder="Contenu HTML de l'article..."
                ><?= e($formData['content']) ?></textarea>
                <div class="form-help">Utilisez du HTML pour formater votre contenu</div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h2 class="card-title">SEO</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="meta_title" class="form-label">Titre Meta (SEO)</label>
                <input
                    type="text"
                    id="meta_title"
                    name="meta_title"
                    class="form-input"
                    value="<?= e($formData['meta_title']) ?>"
                    placeholder="Laisser vide pour utiliser le titre de l'article"
                    data-maxlength="60"
                >
            </div>

            <div class="form-group">
                <label for="meta_description" class="form-label">Description Meta (SEO)</label>
                <textarea
                    id="meta_description"
                    name="meta_description"
                    class="form-textarea"
                    rows="3"
                    placeholder="Description pour les moteurs de recherche"
                    data-maxlength="160"
                ><?= e($formData['meta_description']) ?></textarea>
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
                    <?= $formData['is_published'] ? 'checked' : '' ?>
                >
                <span>Publier cet article immédiatement</span>
            </label>
            <div class="form-help mt-1">
                Si décoché, l'article sera enregistré en brouillon
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-lg">
            💾 Enregistrer l'article
        </button>
        <a href="blog.php" class="btn btn-secondary btn-lg">
            Annuler
        </a>
    </div>
</form>

<?php require_once 'includes/footer.php'; ?>
