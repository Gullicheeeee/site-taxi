<?php
define('ADMIN_LOADED', true);
require_once 'config.php';

$activePage = 'pages';
$pageId = $_GET['id'] ?? 0;

if (!$pageId) {
    header('Location: pages.php');
    exit;
}

// Récupérer la page
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    $page = $stmt->fetch();

    if (!$page) {
        setFlash('error', 'Page introuvable');
        header('Location: pages.php');
        exit;
    }

    // Récupérer les sections de la page
    $stmt = $db->prepare("SELECT * FROM page_sections WHERE page_id = ? ORDER BY display_order");
    $stmt->execute([$pageId]);
    $sections = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlash('error', 'Erreur lors du chargement de la page');
    header('Location: pages.php');
    exit;
}

$pageTitle = 'Éditer : ' . $page['title'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'title' => trim($_POST['title'] ?? ''),
        'meta_title' => trim($_POST['meta_title'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
        'is_published' => isset($_POST['is_published']) ? 1 : 0
    ];

    // Validation
    if (empty($formData['title'])) {
        $errors[] = 'Le titre est obligatoire';
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE pages SET
                    title = ?,
                    meta_title = ?,
                    meta_description = ?,
                    meta_keywords = ?,
                    is_published = ?,
                    updated_by = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $formData['title'],
                $formData['meta_title'],
                $formData['meta_description'],
                $formData['meta_keywords'],
                $formData['is_published'],
                $_SESSION['admin_id'],
                $pageId
            ]);

            // Recharger la page
            $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
            $stmt->execute([$pageId]);
            $page = $stmt->fetch();

            setFlash('success', 'Page mise à jour avec succès');
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de la mise à jour : ' . $e->getMessage();
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
            <h2 class="card-title">Informations Générales</h2>
            <code><?= e($page['slug']) ?>.html</code>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="title" class="form-label required">Titre de la page</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    class="form-input"
                    value="<?= e($page['title']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input
                        type="checkbox"
                        name="is_published"
                        value="1"
                        <?= $page['is_published'] ? 'checked' : '' ?>
                    >
                    <span>Page publiée</span>
                </label>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h2 class="card-title">SEO & Métadonnées</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="meta_title" class="form-label">Titre Meta (SEO)</label>
                <input
                    type="text"
                    id="meta_title"
                    name="meta_title"
                    class="form-input"
                    value="<?= e($page['meta_title']) ?>"
                    placeholder="Titre optimisé pour les moteurs de recherche"
                    data-maxlength="60"
                >
                <div class="form-help">Recommandé : 50-60 caractères</div>
            </div>

            <div class="form-group">
                <label for="meta_description" class="form-label">Description Meta (SEO)</label>
                <textarea
                    id="meta_description"
                    name="meta_description"
                    class="form-textarea"
                    rows="3"
                    placeholder="Description de la page pour les résultats de recherche"
                    data-maxlength="160"
                ><?= e($page['meta_description']) ?></textarea>
                <div class="form-help">Recommandé : 150-160 caractères</div>
            </div>

            <div class="form-group">
                <label for="meta_keywords" class="form-label">Mots-clés (optionnel)</label>
                <input
                    type="text"
                    id="meta_keywords"
                    name="meta_keywords"
                    class="form-input"
                    value="<?= e($page['meta_keywords']) ?>"
                    placeholder="taxi, martigues, transport, conventionné..."
                >
                <div class="form-help">Séparez les mots-clés par des virgules</div>
            </div>
        </div>
    </div>

    <?php if (count($sections) > 0): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h2 class="card-title">Sections de Contenu</h2>
            </div>
            <div class="card-body">
                <p style="margin-bottom: 1rem; color: var(--gray-600);">
                    Les sections ci-dessous sont gérées via la table <code>page_sections</code>.
                    Vous pouvez les éditer via une interface dédiée ou directement en base de données.
                </p>
                <div style="display: grid; gap: 1rem;">
                    <?php foreach ($sections as $section): ?>
                        <div style="border: 1px solid var(--gray-200); border-radius: var(--radius); padding: 1rem; background: var(--gray-50);">
                            <strong><?= e($section['section_key']) ?></strong>
                            <?php if ($section['section_title']): ?>
                                <br><small><?= e($section['section_title']) ?></small>
                            <?php endif; ?>
                            <br><span class="badge badge-info mt-1"><?= e($section['section_type']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-lg">
            💾 Enregistrer les modifications
        </button>
        <a href="pages.php" class="btn btn-secondary btn-lg">
            Retour à la liste
        </a>
        <a href="../<?= e($page['slug']) ?>.html" target="_blank" class="btn btn-secondary btn-lg">
            👁️ Voir la page
        </a>
    </div>
</form>

<?php require_once 'includes/footer.php'; ?>
