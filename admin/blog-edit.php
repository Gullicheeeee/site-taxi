<?php
$pageTitle = 'Éditeur d\'article';
require_once 'config.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$post = null;

// Récupérer l'article existant
if ($id) {
    $stmt = $db->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $featured_image = trim($_POST['featured_image'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $is_published = isset($_POST['is_published']) ? 1 : 0;

    // Générer le slug si vide
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    }

    if ($id) {
        // Mise à jour
        $stmt = $db->prepare("UPDATE blog_posts SET
            title = ?, slug = ?, excerpt = ?, content = ?,
            featured_image = ?, meta_title = ?, meta_description = ?,
            is_published = ?, updated_at = datetime('now'),
            published_at = CASE WHEN ? = 1 AND published_at IS NULL THEN datetime('now') ELSE published_at END
            WHERE id = ?");
        $stmt->execute([$title, $slug, $excerpt, $content, $featured_image, $meta_title, $meta_description, $is_published, $is_published, $id]);
        setFlash('success', 'Article mis à jour !');
    } else {
        // Création
        $stmt = $db->prepare("INSERT INTO blog_posts
            (title, slug, excerpt, content, featured_image, meta_title, meta_description, is_published, published_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $title, $slug, $excerpt, $content, $featured_image, $meta_title, $meta_description,
            $is_published, $is_published ? date('Y-m-d H:i:s') : null
        ]);
        $id = $db->lastInsertId();
        setFlash('success', 'Article créé !');
    }

    header('Location: blog.php');
    exit;
}

require_once 'includes/header.php';
?>

<div class="page-header d-flex justify-between align-center">
    <div>
        <h2 class="page-title"><?= $id ? 'Modifier l\'article' : 'Nouvel article' ?></h2>
    </div>
    <a href="blog.php" class="btn btn-secondary">← Retour</a>
</div>

<form method="POST">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <div>
            <!-- Contenu principal -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Contenu</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Titre de l'article *</label>
                        <input type="text" name="title" class="form-control" required
                               value="<?= e($post['title'] ?? '') ?>"
                               placeholder="Ex: Comment bien choisir son taxi conventionné ?">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Slug (URL)</label>
                        <input type="text" name="slug" class="form-control"
                               value="<?= e($post['slug'] ?? '') ?>"
                               placeholder="comment-choisir-taxi-conventionne">
                        <p class="form-help">Laissez vide pour générer automatiquement</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Extrait</label>
                        <textarea name="excerpt" class="form-control" rows="3"
                                  placeholder="Résumé de l'article (affiché dans la liste des articles)"><?= e($post['excerpt'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contenu de l'article</label>
                        <div class="editor-toolbar">
                            <button type="button" class="editor-btn" data-cmd="bold" title="Gras"><b>B</b></button>
                            <button type="button" class="editor-btn" data-cmd="italic" title="Italique"><i>I</i></button>
                            <button type="button" class="editor-btn" data-cmd="underline" title="Souligné"><u>U</u></button>
                            <button type="button" class="editor-btn" data-cmd="formatBlock" data-value="h2" title="Titre H2">H2</button>
                            <button type="button" class="editor-btn" data-cmd="formatBlock" data-value="h3" title="Titre H3">H3</button>
                            <button type="button" class="editor-btn" data-cmd="insertUnorderedList" title="Liste">•</button>
                            <button type="button" class="editor-btn" data-cmd="createLink" title="Lien">🔗</button>
                        </div>
                        <div id="editor" class="editor-content" contenteditable="true"><?= $post['content'] ?? '' ?></div>
                        <input type="hidden" name="content" id="content-input">
                    </div>
                </div>
            </div>
        </div>

        <div>
            <!-- Options -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Publication</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_published" value="1"
                                   <?= ($post['is_published'] ?? 0) ? 'checked' : '' ?>>
                            <span>Publier l'article</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        💾 <?= $id ? 'Mettre à jour' : 'Créer l\'article' ?>
                    </button>
                </div>
            </div>

            <!-- Image à la une -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Image à la une</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <input type="text" name="featured_image" id="featured-image" class="form-control"
                               value="<?= e($post['featured_image'] ?? '') ?>"
                               placeholder="URL de l'image">
                    </div>
                    <div id="image-preview" style="margin-top: 1rem;">
                        <?php if (!empty($post['featured_image'])): ?>
                        <img src="<?= e($post['featured_image']) ?>" style="max-width: 100%; border-radius: 8px;">
                        <?php endif; ?>
                    </div>
                    <p class="form-help mt-2">
                        <a href="images.php" target="_blank">Ouvrir la bibliothèque d'images</a>
                    </p>
                </div>
            </div>

            <!-- SEO -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">SEO</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control"
                               value="<?= e($post['meta_title'] ?? '') ?>"
                               maxlength="70">
                        <p class="form-help"><span id="meta-title-count"><?= strlen($post['meta_title'] ?? '') ?></span>/60</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="3"
                                  maxlength="200"><?= e($post['meta_description'] ?? '') ?></textarea>
                        <p class="form-help"><span id="meta-desc-count"><?= strlen($post['meta_description'] ?? '') ?></span>/160</p>
                    </div>

                    <!-- Aperçu Google -->
                    <div style="background: var(--gray-100); padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                        <p style="font-size: 0.75rem; color: var(--gray-500); margin-bottom: 0.5rem;">Aperçu Google</p>
                        <p style="color: #1a0dab; font-size: 1.1rem; margin-bottom: 0.25rem;" id="preview-title">
                            <?= e($post['meta_title'] ?? 'Titre de l\'article') ?>
                        </p>
                        <p style="color: #006621; font-size: 0.85rem; margin-bottom: 0.25rem;">
                            taxijulien.fr/blog/<span id="preview-slug"><?= e($post['slug'] ?? 'article-slug') ?></span>
                        </p>
                        <p style="color: #545454; font-size: 0.85rem;" id="preview-desc">
                            <?= e($post['meta_description'] ?? 'Description de l\'article...') ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Éditeur WYSIWYG simple
const editor = document.getElementById('editor');
const contentInput = document.getElementById('content-input');

document.querySelectorAll('.editor-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const cmd = btn.dataset.cmd;
        const value = btn.dataset.value || null;

        if (cmd === 'createLink') {
            const url = prompt('URL du lien:');
            if (url) document.execCommand(cmd, false, url);
        } else if (cmd === 'formatBlock') {
            document.execCommand(cmd, false, '<' + value + '>');
        } else {
            document.execCommand(cmd, false, value);
        }
        editor.focus();
    });
});

// Synchroniser le contenu avant soumission
document.querySelector('form').addEventListener('submit', () => {
    contentInput.value = editor.innerHTML;
});

// Compteurs de caractères
document.querySelector('input[name="meta_title"]').addEventListener('input', function() {
    document.getElementById('meta-title-count').textContent = this.value.length;
    document.getElementById('preview-title').textContent = this.value || 'Titre de l\'article';
});

document.querySelector('textarea[name="meta_description"]').addEventListener('input', function() {
    document.getElementById('meta-desc-count').textContent = this.value.length;
    document.getElementById('preview-desc').textContent = this.value || 'Description de l\'article...';
});

document.querySelector('input[name="slug"]').addEventListener('input', function() {
    document.getElementById('preview-slug').textContent = this.value || 'article-slug';
});

// Preview image
document.getElementById('featured-image').addEventListener('input', function() {
    const preview = document.getElementById('image-preview');
    if (this.value) {
        preview.innerHTML = `<img src="${this.value}" style="max-width: 100%; border-radius: 8px;" onerror="this.style.display='none'">`;
    } else {
        preview.innerHTML = '';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
