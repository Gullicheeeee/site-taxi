<?php
$pageTitle = 'Catégories & Tags';
require_once 'includes/header.php';

// Récupérer les catégories
$catResult = supabase()->select('settings', 'key=eq.blog_categories');
$categories = [];
if ($catResult['success'] && !empty($catResult['data'])) {
    $categories = json_decode($catResult['data'][0]['value'], true) ?: [];
}

// Récupérer les tags
$tagResult = supabase()->select('settings', 'key=eq.blog_tags');
$tags = [];
if ($tagResult['success'] && !empty($tagResult['data'])) {
    $tags = json_decode($tagResult['data'][0]['value'], true) ?: [];
}

// Compter les articles par catégorie
$postsResult = supabase()->select('blog_posts', 'select=category');
$postsByCategory = [];
if ($postsResult['success']) {
    foreach ($postsResult['data'] as $post) {
        $cat = $post['category'] ?? 'non-classé';
        $postsByCategory[$cat] = ($postsByCategory[$cat] ?? 0) + 1;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? 'category';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: slugify($name);
        $description = trim($_POST['description'] ?? '');
        $color = $_POST['color'] ?? '#3b82f6';

        if ($name) {
            $item = [
                'id' => uniqid(),
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'color' => $color
            ];

            if ($type === 'category') {
                $categories[] = $item;
                $key = 'blog_categories';
                $data = $categories;
            } else {
                $tags[] = $item;
                $key = 'blog_tags';
                $data = $tags;
            }

            $existing = supabase()->select('settings', "key=eq.{$key}");
            if ($existing['success'] && !empty($existing['data'])) {
                supabase()->update('settings', "key=eq.{$key}", ['value' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
            } else {
                supabase()->insert('settings', ['key' => $key, 'value' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
            }

            setFlash('success', ($type === 'category' ? 'Catégorie' : 'Tag') . ' ajouté(e)');
        }
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if ($type === 'category') {
            $categories = array_values(array_filter($categories, fn($c) => $c['id'] !== $id));
            supabase()->update('settings', 'key=eq.blog_categories', ['value' => json_encode($categories, JSON_UNESCAPED_UNICODE)]);
        } else {
            $tags = array_values(array_filter($tags, fn($t) => $t['id'] !== $id));
            supabase()->update('settings', 'key=eq.blog_tags', ['value' => json_encode($tags, JSON_UNESCAPED_UNICODE)]);
        }
        setFlash('success', ($type === 'category' ? 'Catégorie' : 'Tag') . ' supprimé(e)');
    }

    header('Location: categories.php');
    exit;
}

// Fonction pour générer un slug
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'n-a';
}
?>

<style>
.cat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}
.cat-card {
    background: white;
    border-radius: 12px;
    border: 1px solid var(--gray-200);
    overflow: hidden;
}
.cat-card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.cat-list {
    max-height: 400px;
    overflow-y: auto;
}
.cat-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: background 0.2s;
}
.cat-item:hover {
    background: var(--gray-50);
}
.cat-item:last-child {
    border-bottom: none;
}
.cat-color {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}
.cat-info {
    flex: 1;
}
.cat-name {
    font-weight: 500;
}
.cat-slug {
    font-size: 0.8rem;
    color: var(--gray-500);
    font-family: monospace;
}
.cat-count {
    background: var(--gray-100);
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    color: var(--gray-600);
}
.cat-empty {
    padding: 3rem;
    text-align: center;
    color: var(--gray-500);
}
.add-form {
    padding: 1.5rem;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
}
.add-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 50px auto;
    gap: 1rem;
    align-items: end;
}
.color-picker {
    width: 40px;
    height: 38px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    padding: 0;
}
.tag-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    margin: 0.25rem;
    background: var(--gray-100);
}
.tags-cloud {
    padding: 1.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
</style>

<div class="page-header">
    <h2 class="page-title">Catégories & Tags</h2>
    <p class="page-subtitle">Organisez vos articles avec des catégories et des tags</p>
</div>

<div class="cat-grid">
    <!-- Catégories -->
    <div class="cat-card">
        <div class="cat-card-header">
            <h3 style="margin: 0;">🏷️ Catégories</h3>
            <span style="color: var(--gray-500); font-size: 0.9rem;"><?= count($categories) ?> catégories</span>
        </div>

        <?php if (empty($categories)): ?>
        <div class="cat-empty">
            <p style="font-size: 2rem; margin-bottom: 0.5rem;">🏷️</p>
            <p>Aucune catégorie</p>
        </div>
        <?php else: ?>
        <div class="cat-list">
            <?php foreach ($categories as $cat): ?>
            <div class="cat-item">
                <span class="cat-color" style="background: <?= e($cat['color']) ?>"></span>
                <div class="cat-info">
                    <div class="cat-name"><?= e($cat['name']) ?></div>
                    <div class="cat-slug">/blog/categorie/<?= e($cat['slug']) ?></div>
                </div>
                <span class="cat-count"><?= $postsByCategory[$cat['slug']] ?? 0 ?> articles</span>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="type" value="category">
                    <input type="hidden" name="id" value="<?= e($cat['id']) ?>">
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette catégorie ?')">🗑️</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="add-form">
            <h4 style="margin: 0 0 1rem 0;">➕ Nouvelle catégorie</h4>
            <form method="POST" class="add-form-grid">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="type" value="category">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Nom</label>
                    <input type="text" name="name" class="form-control" placeholder="Actualités" required>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Slug (optionnel)</label>
                    <input type="text" name="slug" class="form-control" placeholder="actualites">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Couleur</label>
                    <input type="color" name="color" class="color-picker" value="#3b82f6">
                </div>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>
        </div>
    </div>

    <!-- Tags -->
    <div class="cat-card">
        <div class="cat-card-header">
            <h3 style="margin: 0;">#️⃣ Tags</h3>
            <span style="color: var(--gray-500); font-size: 0.9rem;"><?= count($tags) ?> tags</span>
        </div>

        <?php if (empty($tags)): ?>
        <div class="cat-empty">
            <p style="font-size: 2rem; margin-bottom: 0.5rem;">#️⃣</p>
            <p>Aucun tag</p>
        </div>
        <?php else: ?>
        <div class="tags-cloud">
            <?php foreach ($tags as $tag): ?>
            <span class="tag-badge" style="background: <?= e($tag['color']) ?>20; color: <?= e($tag['color']) ?>;">
                #<?= e($tag['name']) ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="type" value="tag">
                    <input type="hidden" name="id" value="<?= e($tag['id']) ?>">
                    <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0; color: inherit; opacity: 0.6;" onclick="return confirm('Supprimer ce tag ?')">×</button>
                </form>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="add-form">
            <h4 style="margin: 0 0 1rem 0;">➕ Nouveau tag</h4>
            <form method="POST" class="add-form-grid">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="type" value="tag">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Nom</label>
                    <input type="text" name="name" class="form-control" placeholder="taxi" required>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Slug (optionnel)</label>
                    <input type="text" name="slug" class="form-control" placeholder="taxi">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Couleur</label>
                    <input type="color" name="color" class="color-picker" value="#22c55e">
                </div>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>
        </div>
    </div>
</div>

<!-- Aide -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">💡 Différence entre catégories et tags</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <h4 style="margin-bottom: 0.5rem; color: var(--primary);">🏷️ Catégories</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    <strong>Hiérarchiques et générales.</strong> Utilisez-les pour classer vos articles dans de grandes thématiques (ex: "Actualités", "Conseils", "Destinations").
                    Chaque article appartient généralement à une seule catégorie.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem; color: #22c55e;">#️⃣ Tags</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    <strong>Plats et spécifiques.</strong> Utilisez-les pour des mots-clés précis (ex: "aéroport", "tarifs", "réservation").
                    Un article peut avoir plusieurs tags pour améliorer la recherche.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
