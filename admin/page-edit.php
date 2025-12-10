<?php
$pageTitle = 'Modifier la Page';
require_once 'config.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

// Récupérer la page
$stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->execute([$id]);
$page = $stmt->fetch();

if (!$page) {
    setFlash('danger', 'Page non trouvée');
    header('Location: pages.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $meta_keywords = trim($_POST['meta_keywords'] ?? '');
    $og_title = trim($_POST['og_title'] ?? '');
    $og_description = trim($_POST['og_description'] ?? '');

    $stmt = $db->prepare("UPDATE pages SET
        meta_title = ?,
        meta_description = ?,
        meta_keywords = ?,
        og_title = ?,
        og_description = ?,
        updated_at = datetime('now')
        WHERE id = ?");

    $stmt->execute([
        $meta_title,
        $meta_description,
        $meta_keywords,
        $og_title,
        $og_description,
        $id
    ]);

    // Mettre à jour le fichier HTML
    updateHtmlFile($page['filename'], [
        'meta_title' => $meta_title,
        'meta_description' => $meta_description,
        'meta_keywords' => $meta_keywords,
        'og_title' => $og_title ?: $meta_title,
        'og_description' => $og_description ?: $meta_description,
    ]);

    setFlash('success', 'Page mise à jour avec succès !');
    header('Location: pages.php');
    exit;
}

// Fonction pour mettre à jour le fichier HTML
function updateHtmlFile($filename, $data) {
    $filepath = __DIR__ . '/../' . $filename;

    if (!file_exists($filepath)) {
        return false;
    }

    $html = file_get_contents($filepath);

    // Mettre à jour le title
    if (!empty($data['meta_title'])) {
        $html = preg_replace(
            '/<title>.*?<\/title>/s',
            '<title>' . htmlspecialchars($data['meta_title']) . '</title>',
            $html
        );
    }

    // Mettre à jour meta description
    if (!empty($data['meta_description'])) {
        $html = preg_replace(
            '/<meta\s+name="description"\s+content="[^"]*"[^>]*>/i',
            '<meta name="description" content="' . htmlspecialchars($data['meta_description']) . '">',
            $html
        );
    }

    // Mettre à jour meta keywords
    if (!empty($data['meta_keywords'])) {
        if (preg_match('/<meta\s+name="keywords"/i', $html)) {
            $html = preg_replace(
                '/<meta\s+name="keywords"\s+content="[^"]*"[^>]*>/i',
                '<meta name="keywords" content="' . htmlspecialchars($data['meta_keywords']) . '">',
                $html
            );
        } else {
            // Ajouter si n'existe pas
            $html = preg_replace(
                '/(<meta\s+name="description"[^>]*>)/i',
                '$1' . "\n" . '    <meta name="keywords" content="' . htmlspecialchars($data['meta_keywords']) . '">',
                $html
            );
        }
    }

    // Mettre à jour OG title
    if (!empty($data['og_title'])) {
        $html = preg_replace(
            '/<meta\s+property="og:title"\s+content="[^"]*"[^>]*>/i',
            '<meta property="og:title" content="' . htmlspecialchars($data['og_title']) . '">',
            $html
        );
    }

    // Mettre à jour OG description
    if (!empty($data['og_description'])) {
        $html = preg_replace(
            '/<meta\s+property="og:description"\s+content="[^"]*"[^>]*>/i',
            '<meta property="og:description" content="' . htmlspecialchars($data['og_description']) . '">',
            $html
        );
    }

    file_put_contents($filepath, $html);
    return true;
}

require_once 'includes/header.php';
?>

<div class="page-header d-flex justify-between align-center">
    <div>
        <h2 class="page-title">Modifier : <?= e($page['title']) ?></h2>
        <p class="page-subtitle">Fichier : <?= e($page['filename']) ?></p>
    </div>
    <a href="pages.php" class="btn btn-secondary">← Retour</a>
</div>

<form method="POST">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Balises Meta (SEO)</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Meta Title</label>
                <input type="text" name="meta_title" class="form-control"
                       value="<?= e($page['meta_title']) ?>"
                       maxlength="70">
                <p class="form-help">
                    <span id="title-count"><?= strlen($page['meta_title'] ?? '') ?></span>/60 caractères (optimal: 50-60)
                </p>
            </div>

            <div class="form-group">
                <label class="form-label">Meta Description</label>
                <textarea name="meta_description" class="form-control" rows="3"
                          maxlength="200"><?= e($page['meta_description']) ?></textarea>
                <p class="form-help">
                    <span id="desc-count"><?= strlen($page['meta_description'] ?? '') ?></span>/160 caractères (optimal: 150-160)
                </p>
            </div>

            <div class="form-group">
                <label class="form-label">Meta Keywords</label>
                <input type="text" name="meta_keywords" class="form-control"
                       value="<?= e($page['meta_keywords']) ?>">
                <p class="form-help">Mots-clés séparés par des virgules</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Open Graph (Réseaux sociaux)</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">OG Title</label>
                <input type="text" name="og_title" class="form-control"
                       value="<?= e($page['og_title']) ?>">
                <p class="form-help">Titre affiché sur Facebook/LinkedIn (laissez vide pour utiliser le meta title)</p>
            </div>

            <div class="form-group">
                <label class="form-label">OG Description</label>
                <textarea name="og_description" class="form-control" rows="2"><?= e($page['og_description']) ?></textarea>
                <p class="form-help">Description affichée sur les réseaux sociaux</p>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            💾 Enregistrer les modifications
        </button>
        <a href="../<?= e($page['filename']) ?>" target="_blank" class="btn btn-secondary">
            👁️ Voir la page
        </a>
    </div>
</form>

<script>
// Compteur de caractères
document.querySelector('input[name="meta_title"]').addEventListener('input', function() {
    document.getElementById('title-count').textContent = this.value.length;
});
document.querySelector('textarea[name="meta_description"]').addEventListener('input', function() {
    document.getElementById('desc-count').textContent = this.value.length;
});
</script>

<?php require_once 'includes/footer.php'; ?>
