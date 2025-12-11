<?php
$pageTitle = 'Éditeur d\'article';
require_once 'includes/header.php';

$id = $_GET['id'] ?? null;
$post = null;

// Récupérer l'article existant
if ($id) {
    $result = supabase()->select('blog_posts', 'id=eq.' . urlencode($id));
    if ($result['success'] && !empty($result['data'])) {
        $post = $result['data'][0];
    } else {
        setFlash('danger', 'Article non trouvé');
        header('Location: blog.php');
        exit;
    }
}

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

// Tags de l'article actuel
$postTags = [];
if ($post && !empty($post['tags'])) {
    $postTags = is_array($post['tags']) ? $post['tags'] : (json_decode($post['tags'], true) ?: []);
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $excerpt = trim($_POST['excerpt'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $selectedTags = $_POST['tags'] ?? [];
    $featuredImage = trim($_POST['featured_image'] ?? '');
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $metaKeywords = trim($_POST['meta_keywords'] ?? '');
    $isPublished = isset($_POST['is_published']);
    $scheduledAt = trim($_POST['scheduled_at'] ?? '');

    // Générer le slug si vide
    if (empty($slug) && !empty($title)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    }

    $data = [
        'title' => $title,
        'slug' => $slug,
        'content' => $content,
        'excerpt' => $excerpt,
        'category' => $category,
        'tags' => json_encode($selectedTags, JSON_UNESCAPED_UNICODE),
        'featured_image' => $featuredImage ?: null,
        'meta_title' => $metaTitle,
        'meta_description' => $metaDescription,
        'meta_keywords' => $metaKeywords,
        'is_published' => $isPublished,
        'scheduled_at' => $scheduledAt ?: null
    ];

    // Gérer la publication programmée
    if ($scheduledAt && !$isPublished) {
        $scheduledTime = strtotime($scheduledAt);
        if ($scheduledTime && $scheduledTime <= time()) {
            $data['is_published'] = true;
            $data['published_at'] = date('c', $scheduledTime);
        }
    } elseif ($isPublished && (!$post || !$post['is_published'])) {
        $data['published_at'] = date('c');
    }

    if ($id) {
        // Mise à jour
        $data['updated_at'] = date('c');
        $result = supabase()->update('blog_posts', 'id=eq.' . urlencode($id), $data);
        if ($result['success']) {
            setFlash('success', 'Article mis à jour');
        } else {
            setFlash('danger', 'Erreur: ' . ($result['error'] ?? 'Inconnu'));
        }
    } else {
        // Création
        $result = supabase()->insert('blog_posts', $data);
        if ($result['success']) {
            setFlash('success', 'Article créé');
            header('Location: blog.php');
            exit;
        } else {
            setFlash('danger', 'Erreur: ' . ($result['error'] ?? 'Inconnu'));
        }
    }

    // Recharger l'article
    if ($id) {
        $result = supabase()->select('blog_posts', 'id=eq.' . urlencode($id));
        if ($result['success'] && !empty($result['data'])) {
            $post = $result['data'][0];
        }
    }
}

// Récupérer les images pour le picker
$mediaResult = supabase()->select('media', 'order=uploaded_at.desc');
$mediaList = $mediaResult['success'] ? $mediaResult['data'] : [];
?>

<div class="page-header d-flex justify-between align-center">
    <div>
        <h2 class="page-title"><?= $id ? 'Modifier l\'article' : 'Nouvel article' ?></h2>
        <?php if ($post): ?>
        <p class="page-subtitle">
            <?php if ($post['is_published']): ?>
            <span class="badge badge-success">Publié</span>
            <?php else: ?>
            <span class="badge badge-warning">Brouillon</span>
            <?php endif; ?>
            &nbsp;·&nbsp; <?= e($post['category'] ?: 'Sans catégorie') ?>
        </p>
        <?php endif; ?>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button type="button" class="btn btn-secondary" onclick="openPreview()">👁️ Prévisualiser</button>
        <a href="blog.php" class="btn btn-secondary">← Retour</a>
    </div>
</div>

<form method="POST">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <!-- Colonne principale -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Contenu</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Titre de l'article *</label>
                        <input type="text" name="title" class="form-control" required
                               value="<?= e($post['title'] ?? '') ?>"
                               placeholder="Ex: Les avantages du taxi pour vos transferts aéroport">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Slug (URL)</label>
                        <input type="text" name="slug" class="form-control"
                               value="<?= e($post['slug'] ?? '') ?>"
                               placeholder="Ex: avantages-taxi-transferts-aeroport">
                        <p class="form-help">Laissez vide pour générer automatiquement depuis le titre</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Extrait (résumé)</label>
                        <textarea name="excerpt" class="form-control" rows="2"
                                  placeholder="Court résumé affiché dans la liste des articles"><?= e($post['excerpt'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contenu de l'article</label>

                        <!-- Toolbar d'ajout de blocs -->
                        <div class="block-toolbar">
                            <span style="font-size: 0.85rem; color: var(--gray-500); margin-right: 0.5rem;">Titres :</span>
                            <button type="button" class="block-btn" onclick="addBlock('h2')" title="Titre H2 (Section principale)">
                                <strong>H2</strong>
                            </button>
                            <button type="button" class="block-btn" onclick="addBlock('h3')" title="Titre H3 (Sous-section)">
                                <strong>H3</strong>
                            </button>
                            <button type="button" class="block-btn" onclick="addBlock('h4')" title="Titre H4 (Sous-sous-section)">
                                <strong>H4</strong>
                            </button>
                            <span style="font-size: 0.85rem; color: var(--gray-500); margin: 0 0.5rem;">|</span>
                            <button type="button" class="block-btn" onclick="addBlock('paragraph')" title="Paragraphe">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="15" y2="18"/></svg>
                                Texte
                            </button>
                            <button type="button" class="block-btn" onclick="addBlock('list')" title="Liste a puces">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="9" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="9" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg>
                                Liste
                            </button>
                            <button type="button" class="block-btn block-tip" onclick="addBlock('tip')" title="Astuce Pro">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                Astuce
                            </button>
                            <button type="button" class="block-btn block-warning" onclick="addBlock('warning')" title="Important">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                Important
                            </button>
                            <button type="button" class="block-btn" onclick="addBlock('quote')" title="Citation">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3z"/></svg>
                                Citation
                            </button>
                            <button type="button" class="block-btn" onclick="addBlock('image')" title="Image">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                Image
                            </button>
                        </div>

                        <!-- Conteneur des blocs -->
                        <div id="blocks-container" class="blocks-container">
                            <!-- Les blocs seront inseres ici -->
                        </div>

                        <input type="hidden" name="content" id="content-input">
                    </div>

                    <style>
                    .block-toolbar {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 0.5rem;
                        padding: 0.75rem;
                        background: var(--gray-50);
                        border: 1px solid var(--gray-200);
                        border-radius: 8px 8px 0 0;
                        align-items: center;
                    }
                    .block-btn {
                        display: inline-flex;
                        align-items: center;
                        gap: 0.35rem;
                        padding: 0.4rem 0.75rem;
                        background: white;
                        border: 1px solid var(--gray-200);
                        border-radius: 6px;
                        font-size: 0.8rem;
                        cursor: pointer;
                        transition: all 0.2s;
                    }
                    .block-btn:hover { background: var(--gray-100); border-color: var(--gray-300); }
                    .block-btn.block-tip { color: #0369a1; }
                    .block-btn.block-tip:hover { background: #e0f2fe; }
                    .block-btn.block-warning { color: #b45309; }
                    .block-btn.block-warning:hover { background: #fef3c7; }

                    .blocks-container {
                        border: 1px solid var(--gray-200);
                        border-top: none;
                        border-radius: 0 0 8px 8px;
                        min-height: 400px;
                        padding: 1rem;
                        background: white;
                    }
                    .block-item {
                        position: relative;
                        margin-bottom: 1rem;
                        border: 2px solid transparent;
                        border-radius: 8px;
                        transition: all 0.2s;
                    }
                    .block-item:hover { border-color: var(--gray-200); }
                    .block-item:focus-within { border-color: var(--primary); }

                    .block-controls {
                        position: absolute;
                        top: -12px;
                        right: 8px;
                        display: none;
                        gap: 0.25rem;
                        background: white;
                        padding: 0.25rem;
                        border-radius: 4px;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    }
                    .block-item:hover .block-controls,
                    .block-item:focus-within .block-controls { display: flex; }
                    .block-control-btn {
                        width: 24px;
                        height: 24px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border: none;
                        background: var(--gray-100);
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 0.75rem;
                    }
                    .block-control-btn:hover { background: var(--gray-200); }
                    .block-control-btn.delete:hover { background: #fee2e2; color: #dc2626; }

                    .block-heading {
                        padding: 0.75rem 1rem;
                    }
                    .block-heading-input {
                        width: 100%;
                        border: none;
                        outline: none;
                        background: transparent;
                        color: #1e293b;
                    }
                    .block-heading[data-level="h2"] .block-heading-input {
                        font-size: 1.5rem;
                        font-weight: 700;
                    }
                    .block-heading[data-level="h3"] .block-heading-input {
                        font-size: 1.25rem;
                        font-weight: 600;
                    }
                    .block-heading[data-level="h4"] .block-heading-input {
                        font-size: 1.1rem;
                        font-weight: 600;
                    }
                    .block-heading-tag {
                        display: inline-block;
                        font-size: 0.7rem;
                        font-weight: 600;
                        padding: 0.15rem 0.4rem;
                        border-radius: 4px;
                        margin-right: 0.5rem;
                        vertical-align: middle;
                    }
                    .block-heading[data-level="h2"] .block-heading-tag {
                        background: #fef3c7;
                        color: #b45309;
                    }
                    .block-heading[data-level="h3"] .block-heading-tag {
                        background: #dbeafe;
                        color: #1d4ed8;
                    }
                    .block-heading[data-level="h4"] .block-heading-tag {
                        background: #f3e8ff;
                        color: #7c3aed;
                    }
                    .block-heading-number {
                        display: inline-block;
                        color: #f59e0b;
                        margin-right: 0.5rem;
                        font-weight: 700;
                    }

                    .block-quote {
                        padding: 1rem 1.25rem;
                    }
                    .block-quote-box {
                        border-left: 4px solid #6366f1;
                        padding-left: 1rem;
                        background: #eef2ff;
                        padding: 1rem;
                        border-radius: 0 8px 8px 0;
                    }
                    .block-quote-input {
                        width: 100%;
                        border: none;
                        outline: none;
                        background: transparent;
                        font-size: 1rem;
                        font-style: italic;
                        line-height: 1.6;
                        resize: vertical;
                        min-height: 50px;
                    }

                    .block-paragraph {
                        padding: 0.5rem 1rem;
                    }
                    .block-paragraph-input {
                        width: 100%;
                        min-height: 80px;
                        border: none;
                        outline: none;
                        resize: vertical;
                        font-size: 1rem;
                        line-height: 1.7;
                        background: transparent;
                    }

                    .block-list {
                        padding: 0.75rem 1rem;
                    }
                    .block-list-items {
                        list-style: none;
                        padding: 0;
                        margin: 0;
                    }
                    .block-list-item {
                        display: flex;
                        align-items: flex-start;
                        gap: 0.5rem;
                        margin-bottom: 0.5rem;
                    }
                    .block-list-item::before {
                        content: "→";
                        color: #f59e0b;
                        font-weight: bold;
                        flex-shrink: 0;
                    }
                    .block-list-item input {
                        flex: 1;
                        border: none;
                        outline: none;
                        font-size: 0.95rem;
                        padding: 0.25rem 0;
                        background: transparent;
                    }
                    .add-list-item {
                        color: var(--primary);
                        cursor: pointer;
                        font-size: 0.85rem;
                        margin-top: 0.5rem;
                    }

                    .block-tip-box {
                        background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%);
                        border-left: 4px solid #0ea5e9;
                        border-radius: 0 8px 8px 0;
                        padding: 1rem 1.25rem;
                    }
                    .block-tip-label {
                        font-weight: 600;
                        color: #0369a1;
                        margin-bottom: 0.5rem;
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                    }
                    .block-tip-input {
                        width: 100%;
                        border: none;
                        outline: none;
                        background: transparent;
                        font-size: 0.95rem;
                        line-height: 1.6;
                        resize: vertical;
                        min-height: 50px;
                    }

                    .block-warning-box {
                        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                        border-left: 4px solid #f59e0b;
                        border-radius: 0 8px 8px 0;
                        padding: 1rem 1.25rem;
                    }
                    .block-warning-label {
                        font-weight: 600;
                        color: #b45309;
                        margin-bottom: 0.5rem;
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                    }
                    .block-warning-input {
                        width: 100%;
                        border: none;
                        outline: none;
                        background: transparent;
                        font-size: 0.95rem;
                        line-height: 1.6;
                        resize: vertical;
                        min-height: 50px;
                    }

                    .block-image {
                        padding: 1rem;
                        text-align: center;
                    }
                    .block-image img {
                        max-width: 100%;
                        border-radius: 8px;
                    }
                    .block-image-placeholder {
                        background: var(--gray-100);
                        border: 2px dashed var(--gray-300);
                        border-radius: 8px;
                        padding: 2rem;
                        cursor: pointer;
                    }
                    .block-image-placeholder:hover {
                        border-color: var(--primary);
                        background: var(--gray-50);
                    }

                    .blocks-empty {
                        text-align: center;
                        padding: 3rem;
                        color: var(--gray-400);
                    }
                    </style>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">SEO - Référencement</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control" maxlength="70"
                               value="<?= e($post['meta_title'] ?? '') ?>"
                               placeholder="Titre pour les moteurs de recherche (50-60 car.)">
                        <p class="form-help">
                            <span id="meta-title-count"><?= strlen($post['meta_title'] ?? '') ?></span>/60 caractères
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="2" maxlength="170"
                                  placeholder="Description pour les moteurs de recherche (150-160 car.)"><?= e($post['meta_description'] ?? '') ?></textarea>
                        <p class="form-help">
                            <span id="meta-desc-count"><?= strlen($post['meta_description'] ?? '') ?></span>/160 caractères
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mots-clés</label>
                        <input type="text" name="meta_keywords" class="form-control"
                               value="<?= e($post['meta_keywords'] ?? '') ?>"
                               placeholder="taxi, transfert aéroport, nice, cannes">
                        <p class="form-help">Séparés par des virgules</p>
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

        <!-- Colonne latérale -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Publication</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_published" value="1"
                                   <?= ($post['is_published'] ?? false) ? 'checked' : '' ?>>
                            <span>Publier l'article</span>
                        </label>
                    </div>

                    <?php if ($post): ?>
                    <div style="font-size: 0.875rem; color: var(--gray-500); margin-top: 1rem;">
                        <p>Créé le : <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></p>
                        <?php if (!empty($post['updated_at'])): ?>
                        <p>Modifié le : <?= date('d/m/Y H:i', strtotime($post['updated_at'])) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($post['published_at'])): ?>
                        <p>Publié le : <?= date('d/m/Y H:i', strtotime($post['published_at'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                        💾 <?= $id ? 'Enregistrer les modifications' : 'Créer l\'article' ?>
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Image à la une</h3>
                </div>
                <div class="card-body">
                    <input type="hidden" name="featured_image" id="featured-image-input"
                           value="<?= e($post['featured_image'] ?? '') ?>">

                    <div id="featured-image-preview" style="margin-bottom: 1rem;">
                        <?php if (!empty($post['featured_image'])): ?>
                        <img src="<?= e($post['featured_image']) ?>" alt=""
                             style="width: 100%; border-radius: 8px;">
                        <?php else: ?>
                        <div style="background: var(--gray-100); border-radius: 8px; padding: 2rem; text-align: center;">
                            <p style="color: var(--gray-500);">Aucune image</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="btn btn-secondary" style="width: 100%;" onclick="openMediaPicker()">
                        Choisir une image
                    </button>
                    <?php if (!empty($post['featured_image'])): ?>
                    <button type="button" class="btn btn-danger btn-sm" style="width: 100%; margin-top: 0.5rem;"
                            onclick="clearFeaturedImage()">
                        Supprimer l'image
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Catégorie</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($categories)): ?>
                    <select name="category" class="form-control">
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['slug']) ?>" <?= ($post['category'] ?? '') === $cat['slug'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <input type="text" name="category" class="form-control"
                           value="<?= e($post['category'] ?? '') ?>"
                           placeholder="Ex: Conseils, Actualités">
                    <p style="font-size: 0.8rem; color: var(--gray-500); margin-top: 0.5rem;">
                        <a href="categories.php">Créer des catégories</a> pour les sélectionner ici
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tags</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($tags)): ?>
                    <div class="tags-select">
                        <?php foreach ($tags as $tag): ?>
                        <label class="tag-checkbox" style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.75rem; margin: 0.2rem; background: <?= in_array($tag['slug'], $postTags) ? e($tag['color']).'20' : 'var(--gray-100)' ?>; border-radius: 20px; cursor: pointer; font-size: 0.85rem; border: 1px solid <?= in_array($tag['slug'], $postTags) ? e($tag['color']) : 'transparent' ?>;">
                            <input type="checkbox" name="tags[]" value="<?= e($tag['slug']) ?>"
                                   <?= in_array($tag['slug'], $postTags) ? 'checked' : '' ?>
                                   style="display: none;">
                            <span style="color: <?= e($tag['color']) ?>;">#<?= e($tag['name']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p style="font-size: 0.9rem; color: var(--gray-500);">
                        Aucun tag disponible. <a href="categories.php">Créer des tags</a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Programmation</h3>
                </div>
                <div class="card-body">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Publier le</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control"
                               value="<?= !empty($post['scheduled_at']) ? date('Y-m-d\TH:i', strtotime($post['scheduled_at'])) : '' ?>">
                        <p style="font-size: 0.8rem; color: var(--gray-500); margin-top: 0.5rem;">
                            Laissez vide pour publier immédiatement
                        </p>
                    </div>
                    <?php if (!empty($post['scheduled_at']) && !$post['is_published']): ?>
                    <div style="margin-top: 1rem; padding: 0.75rem; background: #fef3c7; border-radius: 8px; font-size: 0.85rem;">
                        <strong>Programmé</strong> pour le <?= date('d/m/Y à H:i', strtotime($post['scheduled_at'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal Media Picker -->
<div class="modal" id="media-picker-modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3>Choisir une image</h3>
            <button type="button" class="modal-close" onclick="closeMediaPicker()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="image-grid" style="grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.5rem;">
                <?php foreach ($mediaList as $media): ?>
                <div class="image-item" style="cursor: pointer; padding: 0.25rem; border: 2px solid transparent; border-radius: 8px;"
                     onclick="selectImage('<?= e($media['file_url']) ?>')"
                     onmouseover="this.style.borderColor='var(--primary)'"
                     onmouseout="this.style.borderColor='transparent'">
                    <img src="<?= e($media['file_url']) ?>" alt="<?= e($media['alt_text'] ?? '') ?>"
                         style="width: 100%; height: 80px; object-fit: cover; border-radius: 6px;">
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($mediaList)): ?>
            <div style="text-align: center; padding: 2rem;">
                <p style="color: var(--gray-500);">Aucune image disponible</p>
                <a href="media.php" class="btn btn-primary" style="margin-top: 1rem;">Uploader des images</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Prévisualisation Article -->
<div class="modal" id="preview-modal">
    <div class="modal-content" style="max-width: 900px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;">
        <div class="modal-header" style="border-bottom: 1px solid var(--gray-200);">
            <h3>Prévisualisation de l'article</h3>
            <button type="button" class="modal-close" onclick="closePreview()">&times;</button>
        </div>
        <div class="modal-body" style="overflow-y: auto; flex: 1;">
            <!-- Simulation du front-end -->
            <div id="preview-container" style="background: white; font-family: system-ui, -apple-system, sans-serif;">
                <!-- Hero -->
                <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; padding: 3rem 2rem; text-align: center; border-radius: 12px 12px 0 0;">
                    <div id="preview-category-badge" style="display: inline-block; background: rgba(255,255,255,0.2); padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.85rem; margin-bottom: 1rem;"></div>
                    <h1 id="preview-article-title" style="font-size: 2rem; margin-bottom: 0.5rem; color: white;"></h1>
                    <p id="preview-article-date" style="opacity: 0.8; font-size: 0.9rem;"></p>
                </div>

                <!-- Image à la une -->
                <div id="preview-featured-image-container" style="display: none;">
                    <img id="preview-featured-image" src="" alt="" style="width: 100%; max-height: 400px; object-fit: cover;">
                </div>

                <!-- Contenu -->
                <div style="padding: 2rem; line-height: 1.8;">
                    <div id="preview-excerpt" style="font-size: 1.1rem; color: #666; border-left: 4px solid #3b82f6; padding-left: 1rem; margin-bottom: 2rem; font-style: italic;"></div>
                    <div id="preview-content" style="color: #333;"></div>
                </div>

                <!-- Meta SEO -->
                <div style="background: #f8fafc; padding: 1.5rem 2rem; border-radius: 0 0 12px 12px; border-top: 1px solid #e2e8f0;">
                    <p style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.5rem;">Aperçu dans les résultats Google :</p>
                    <div style="background: white; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <p id="preview-seo-title" style="color: #1a0dab; font-size: 1.1rem; margin-bottom: 0.25rem;"></p>
                        <p id="preview-seo-url" style="color: #006621; font-size: 0.85rem; margin-bottom: 0.25rem;"></p>
                        <p id="preview-seo-desc" style="color: #545454; font-size: 0.85rem;"></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="padding: 1rem; border-top: 1px solid var(--gray-200); display: flex; justify-content: flex-end; gap: 0.5rem;">
            <button type="button" class="btn btn-secondary" onclick="closePreview()">Fermer</button>
        </div>
    </div>
</div>

<script>
// ========== EDITEUR DE BLOCS ==========
const blocksContainer = document.getElementById('blocks-container');
const contentInput = document.getElementById('content-input');
let headingCounter = 0;
let currentImageBlock = null;

// Contenu existant a parser
const existingContent = <?= json_encode($post['content'] ?? '') ?>;

// Initialiser avec le contenu existant ou un bloc vide
document.addEventListener('DOMContentLoaded', () => {
    if (existingContent && existingContent.trim()) {
        parseExistingContent(existingContent);
    } else {
        showEmptyState();
    }
    updateHeadingNumbers();
});

// Parser le contenu HTML existant en blocs
function parseExistingContent(html) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const elements = doc.body.children;

    if (elements.length === 0) {
        // Contenu texte simple
        addBlock('paragraph', html);
        return;
    }

    for (const el of elements) {
        const tag = el.tagName.toLowerCase();

        if (tag === 'h2') {
            addBlock('h2', el.textContent.replace(/^\d+\.\s*/, ''));
        } else if (tag === 'h3') {
            addBlock('h3', el.textContent);
        } else if (tag === 'h4') {
            addBlock('h4', el.textContent);
        } else if (tag === 'blockquote') {
            addBlock('quote', el.textContent);
        } else if (tag === 'p' && el.classList.contains('tip-box')) {
            addBlock('tip', el.textContent.replace(/^Astuce Pro\s*:\s*/i, ''));
        } else if (tag === 'div' && el.classList.contains('tip-box')) {
            addBlock('tip', el.textContent.replace(/^Astuce Pro\s*:\s*/i, ''));
        } else if (tag === 'p' && el.classList.contains('warning-box')) {
            addBlock('warning', el.textContent.replace(/^Important\s*:\s*/i, ''));
        } else if (tag === 'div' && el.classList.contains('warning-box')) {
            addBlock('warning', el.textContent.replace(/^Important\s*:\s*/i, ''));
        } else if (tag === 'ul' || tag === 'ol') {
            const items = Array.from(el.querySelectorAll('li')).map(li => li.textContent);
            addBlock('list', items);
        } else if (tag === 'img' || (tag === 'figure' && el.querySelector('img'))) {
            const img = tag === 'img' ? el : el.querySelector('img');
            addBlock('image', img.src);
        } else if (tag === 'p' || tag === 'div') {
            addBlock('paragraph', el.innerHTML);
        }
    }

    if (blocksContainer.children.length === 0) {
        addBlock('paragraph', html);
    }
}

function showEmptyState() {
    blocksContainer.innerHTML = `
        <div class="blocks-empty">
            <p style="font-size: 2rem; margin-bottom: 0.5rem;">+</p>
            <p>Cliquez sur un bouton ci-dessus pour ajouter du contenu</p>
        </div>
    `;
}

function removeEmptyState() {
    const emptyState = blocksContainer.querySelector('.blocks-empty');
    if (emptyState) emptyState.remove();
}

// Compteurs pour chaque niveau de titre
let h2Counter = 0;

// Ajouter un bloc
function addBlock(type, content = '') {
    removeEmptyState();
    const blockId = 'block-' + Date.now();

    let blockHTML = '';

    switch (type) {
        case 'h2':
            h2Counter++;
            blockHTML = `
                <div class="block-item block-heading" data-type="h2" data-level="h2" id="${blockId}">
                    <div class="block-controls">
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', -1)">↑</button>
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', 1)">↓</button>
                        <button type="button" class="block-control-btn delete" onclick="deleteBlock('${blockId}')">×</button>
                    </div>
                    <span class="block-heading-tag">H2</span>
                    <span class="block-heading-number">${h2Counter}.</span>
                    <input type="text" class="block-heading-input" placeholder="Titre principal de section" value="${escapeHtml(content)}">
                </div>
            `;
            break;

        case 'h3':
            blockHTML = `
                <div class="block-item block-heading" data-type="h3" data-level="h3" id="${blockId}">
                    <div class="block-controls">
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', -1)">↑</button>
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', 1)">↓</button>
                        <button type="button" class="block-control-btn delete" onclick="deleteBlock('${blockId}')">×</button>
                    </div>
                    <span class="block-heading-tag">H3</span>
                    <input type="text" class="block-heading-input" placeholder="Sous-titre de section" value="${escapeHtml(content)}">
                </div>
            `;
            break;

        case 'h4':
            blockHTML = `
                <div class="block-item block-heading" data-type="h4" data-level="h4" id="${blockId}">
                    <div class="block-controls">
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', -1)">↑</button>
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', 1)">↓</button>
                        <button type="button" class="block-control-btn delete" onclick="deleteBlock('${blockId}')">×</button>
                    </div>
                    <span class="block-heading-tag">H4</span>
                    <input type="text" class="block-heading-input" placeholder="Sous-sous-titre" value="${escapeHtml(content)}">
                </div>
            `;
            break;

        case 'heading':
            // Fallback pour ancien format - traiter comme H2
            return addBlock('h2', content);

        case 'paragraph':
            blockHTML = `
                <div class="block-item block-paragraph" data-type="paragraph" id="${blockId}">
                    <div class="block-controls">
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', -1)">↑</button>
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', 1)">↓</button>
                        <button type="button" class="block-control-btn delete" onclick="deleteBlock('${blockId}')">×</button>
                    </div>
                    <textarea class="block-paragraph-input" placeholder="Ecrivez votre texte ici...">${content}</textarea>
                </div>
            `;
            break;

        case 'list':
            const items = Array.isArray(content) ? content : ['', '', ''];
            let listItemsHTML = items.map((item, i) => `
                <div class="block-list-item">
                    <input type="text" placeholder="Element ${i + 1}" value="${escapeHtml(item)}">
                    <button type="button" style="border: none; background: none; color: var(--gray-400); cursor: pointer;" onclick="this.parentElement.remove()">×</button>
                </div>
            `).join('');

            blockHTML = `
                <div class="block-item block-list" data-type="list" id="${blockId}">
                    <div class="block-controls">
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', -1)">↑</button>
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', 1)">↓</button>
                        <button type="button" class="block-control-btn delete" onclick="deleteBlock('${blockId}')">×</button>
                    </div>
                    <div class="block-list-items">
                        ${listItemsHTML}
                    </div>
                    <div class="add-list-item" onclick="addListItem('${blockId}')">+ Ajouter un element</div>
                </div>
            `;
            break;

        case 'tip':
            blockHTML = `
                <div class="block-item" data-type="tip" id="${blockId}">
                    <div class="block-controls">
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', -1)">↑</button>
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', 1)">↓</button>
                        <button type="button" class="block-control-btn delete" onclick="deleteBlock('${blockId}')">×</button>
                    </div>
                    <div class="block-tip-box">
                        <div class="block-tip-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            Astuce Pro :
                        </div>
                        <textarea class="block-tip-input" placeholder="Votre conseil ou astuce...">${escapeHtml(content)}</textarea>
                    </div>
                </div>
            `;
            break;

        case 'warning':
            blockHTML = `
                <div class="block-item" data-type="warning" id="${blockId}">
                    <div class="block-controls">
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', -1)">↑</button>
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', 1)">↓</button>
                        <button type="button" class="block-control-btn delete" onclick="deleteBlock('${blockId}')">×</button>
                    </div>
                    <div class="block-warning-box">
                        <div class="block-warning-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            Important :
                        </div>
                        <textarea class="block-warning-input" placeholder="Information importante...">${escapeHtml(content)}</textarea>
                    </div>
                </div>
            `;
            break;

        case 'quote':
            blockHTML = `
                <div class="block-item block-quote" data-type="quote" id="${blockId}">
                    <div class="block-controls">
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', -1)">↑</button>
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', 1)">↓</button>
                        <button type="button" class="block-control-btn delete" onclick="deleteBlock('${blockId}')">×</button>
                    </div>
                    <div class="block-quote-box">
                        <textarea class="block-quote-input" placeholder="Votre citation...">${escapeHtml(content)}</textarea>
                    </div>
                </div>
            `;
            break;

        case 'image':
            const hasImage = content && content.trim();
            blockHTML = `
                <div class="block-item block-image" data-type="image" id="${blockId}">
                    <div class="block-controls">
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', -1)">↑</button>
                        <button type="button" class="block-control-btn" onclick="moveBlock('${blockId}', 1)">↓</button>
                        <button type="button" class="block-control-btn delete" onclick="deleteBlock('${blockId}')">×</button>
                    </div>
                    <input type="hidden" class="block-image-url" value="${escapeHtml(content)}">
                    ${hasImage
                        ? `<img src="${escapeHtml(content)}" alt=""><br><button type="button" class="btn btn-sm btn-secondary" style="margin-top: 0.5rem;" onclick="selectImageForBlock('${blockId}')">Changer l'image</button>`
                        : `<div class="block-image-placeholder" onclick="selectImageForBlock('${blockId}')">
                               <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                               <p style="margin-top: 0.5rem;">Cliquez pour choisir une image</p>
                           </div>`
                    }
                </div>
            `;
            break;
    }

    blocksContainer.insertAdjacentHTML('beforeend', blockHTML);
}

// Ajouter un element a une liste
function addListItem(blockId) {
    const block = document.getElementById(blockId);
    const listItems = block.querySelector('.block-list-items');
    const count = listItems.children.length + 1;

    listItems.insertAdjacentHTML('beforeend', `
        <div class="block-list-item">
            <input type="text" placeholder="Element ${count}">
            <button type="button" style="border: none; background: none; color: var(--gray-400); cursor: pointer;" onclick="this.parentElement.remove()">×</button>
        </div>
    `);
}

// Deplacer un bloc
function moveBlock(blockId, direction) {
    const block = document.getElementById(blockId);
    if (direction === -1 && block.previousElementSibling) {
        block.parentNode.insertBefore(block, block.previousElementSibling);
    } else if (direction === 1 && block.nextElementSibling) {
        block.parentNode.insertBefore(block.nextElementSibling, block);
    }
    updateHeadingNumbers();
}

// Supprimer un bloc
function deleteBlock(blockId) {
    const block = document.getElementById(blockId);
    if (confirm('Supprimer ce bloc ?')) {
        block.remove();
        updateHeadingNumbers();
        if (blocksContainer.children.length === 0) {
            showEmptyState();
        }
    }
}

// Mettre a jour les numeros des titres H2
function updateHeadingNumbers() {
    const h2Headings = blocksContainer.querySelectorAll('[data-type="h2"]');
    h2Headings.forEach((h, i) => {
        const numberSpan = h.querySelector('.block-heading-number');
        if (numberSpan) numberSpan.textContent = (i + 1) + '.';
    });
    h2Counter = h2Headings.length;
}

// Selection d'image pour un bloc
function selectImageForBlock(blockId) {
    currentImageBlock = blockId;
    openMediaPicker();
}

// Echapper le HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Generer le HTML final pour sauvegarde
function generateContent() {
    let html = '';
    const blocks = blocksContainer.querySelectorAll('.block-item');

    blocks.forEach(block => {
        const type = block.dataset.type;

        switch (type) {
            case 'h2':
                const h2Num = block.querySelector('.block-heading-number')?.textContent || '';
                const h2Text = block.querySelector('.block-heading-input').value;
                if (h2Text.trim()) {
                    html += `<h2>${h2Num} ${h2Text}</h2>\n`;
                }
                break;

            case 'h3':
                const h3Text = block.querySelector('.block-heading-input').value;
                if (h3Text.trim()) {
                    html += `<h3>${h3Text}</h3>\n`;
                }
                break;

            case 'h4':
                const h4Text = block.querySelector('.block-heading-input').value;
                if (h4Text.trim()) {
                    html += `<h4>${h4Text}</h4>\n`;
                }
                break;

            case 'heading':
                // Ancien format - traiter comme H2
                const headingNum = block.querySelector('.block-heading-number')?.textContent || '';
                const headingText = block.querySelector('.block-heading-input').value;
                if (headingText.trim()) {
                    html += `<h2>${headingNum} ${headingText}</h2>\n`;
                }
                break;

            case 'paragraph':
                const paragraphText = block.querySelector('.block-paragraph-input').value;
                if (paragraphText.trim()) {
                    // Convertir les sauts de ligne en paragraphes
                    const paragraphs = paragraphText.split('\n\n').filter(p => p.trim());
                    paragraphs.forEach(p => {
                        html += `<p>${p.replace(/\n/g, '<br>')}</p>\n`;
                    });
                }
                break;

            case 'list':
                const listItems = block.querySelectorAll('.block-list-item input');
                const items = Array.from(listItems).map(i => i.value).filter(v => v.trim());
                if (items.length) {
                    html += '<ul class="styled-list">\n';
                    items.forEach(item => {
                        html += `  <li>${item}</li>\n`;
                    });
                    html += '</ul>\n';
                }
                break;

            case 'tip':
                const tipText = block.querySelector('.block-tip-input').value;
                if (tipText.trim()) {
                    html += `<div class="tip-box"><strong>Astuce Pro :</strong> ${tipText}</div>\n`;
                }
                break;

            case 'warning':
                const warningText = block.querySelector('.block-warning-input').value;
                if (warningText.trim()) {
                    html += `<div class="warning-box"><strong>Important :</strong> ${warningText}</div>\n`;
                }
                break;

            case 'quote':
                const quoteText = block.querySelector('.block-quote-input').value;
                if (quoteText.trim()) {
                    html += `<blockquote>${quoteText}</blockquote>\n`;
                }
                break;

            case 'image':
                const imageUrl = block.querySelector('.block-image-url').value;
                if (imageUrl.trim()) {
                    html += `<figure class="article-image"><img src="${imageUrl}" alt=""></figure>\n`;
                }
                break;
        }
    });

    return html;
}

// Synchroniser le contenu avant soumission
document.querySelector('form').addEventListener('submit', () => {
    contentInput.value = generateContent();
});

// Media Picker
function openMediaPicker() {
    document.getElementById('media-picker-modal').classList.add('active');
}

function closeMediaPicker() {
    document.getElementById('media-picker-modal').classList.remove('active');
}

function selectImage(url) {
    // Si on selectionne pour un bloc image
    if (currentImageBlock) {
        const block = document.getElementById(currentImageBlock);
        if (block) {
            block.querySelector('.block-image-url').value = url;
            const placeholder = block.querySelector('.block-image-placeholder');
            if (placeholder) {
                placeholder.outerHTML = `<img src="${url}" alt=""><br><button type="button" class="btn btn-sm btn-secondary" style="margin-top: 0.5rem;" onclick="selectImageForBlock('${currentImageBlock}')">Changer l'image</button>`;
            } else {
                const img = block.querySelector('img');
                if (img) img.src = url;
            }
        }
        currentImageBlock = null;
    } else {
        // Image a la une
        document.getElementById('featured-image-input').value = url;
        document.getElementById('featured-image-preview').innerHTML = `
            <img src="${url}" alt="" style="width: 100%; border-radius: 8px;">
        `;
    }
    closeMediaPicker();
}

function clearFeaturedImage() {
    document.getElementById('featured-image-input').value = '';
    document.getElementById('featured-image-preview').innerHTML = `
        <div style="background: var(--gray-100); border-radius: 8px; padding: 2rem; text-align: center;">
            <p style="color: var(--gray-500);">Aucune image</p>
        </div>
    `;
}

// Compteurs de caractères
document.querySelector('input[name="meta_title"]')?.addEventListener('input', function() {
    document.getElementById('meta-title-count').textContent = this.value.length;
    document.getElementById('preview-title').textContent = this.value || 'Titre de l\'article';
});

document.querySelector('textarea[name="meta_description"]')?.addEventListener('input', function() {
    document.getElementById('meta-desc-count').textContent = this.value.length;
    document.getElementById('preview-desc').textContent = this.value || 'Description de l\'article...';
});

document.querySelector('input[name="slug"]')?.addEventListener('input', function() {
    document.getElementById('preview-slug').textContent = this.value || 'article-slug';
});

// Fermer modal avec Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeMediaPicker();
        closePreview();
    }
});

// Prévisualisation de l'article
function openPreview() {
    // Récupérer les valeurs actuelles du formulaire
    const title = document.querySelector('input[name="title"]').value || 'Titre de l\'article';
    const slug = document.querySelector('input[name="slug"]').value || 'article-slug';
    const excerpt = document.querySelector('textarea[name="excerpt"]').value || '';
    const content = document.getElementById('editor').innerHTML || '<p>Contenu de l\'article...</p>';
    const category = document.querySelector('input[name="category"]').value || 'Non classé';
    const featuredImage = document.getElementById('featured-image-input').value || '';
    const metaTitle = document.querySelector('input[name="meta_title"]').value || title;
    const metaDesc = document.querySelector('textarea[name="meta_description"]').value || excerpt;

    // Mettre à jour la prévisualisation
    document.getElementById('preview-category-badge').textContent = category;
    document.getElementById('preview-article-title').textContent = title;
    document.getElementById('preview-article-date').textContent = 'Publié le ' + new Date().toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });

    // Image à la une
    const imgContainer = document.getElementById('preview-featured-image-container');
    if (featuredImage) {
        document.getElementById('preview-featured-image').src = featuredImage;
        imgContainer.style.display = 'block';
    } else {
        imgContainer.style.display = 'none';
    }

    // Extrait et contenu
    document.getElementById('preview-excerpt').textContent = excerpt;
    document.getElementById('preview-excerpt').style.display = excerpt ? 'block' : 'none';
    document.getElementById('preview-content').innerHTML = content;

    // SEO
    document.getElementById('preview-seo-title').textContent = metaTitle;
    document.getElementById('preview-seo-url').textContent = 'taxijulien.fr/blog/' + slug;
    document.getElementById('preview-seo-desc').textContent = metaDesc || 'Description de l\'article...';

    // Ouvrir le modal
    document.getElementById('preview-modal').classList.add('active');
}

function closePreview() {
    document.getElementById('preview-modal').classList.remove('active');
}

// Fermer preview en cliquant en dehors
document.getElementById('preview-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closePreview();
});

// Toggle tag selection style
document.querySelectorAll('.tag-checkbox input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const label = this.closest('.tag-checkbox');
        const color = label.querySelector('span').style.color || '#22c55e';
        if (this.checked) {
            label.style.background = color + '20';
            label.style.borderColor = color;
        } else {
            label.style.background = 'var(--gray-100)';
            label.style.borderColor = 'transparent';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
