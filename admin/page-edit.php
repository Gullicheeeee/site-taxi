<?php
require_once 'config.php';
requireLogin();

$id = $_GET['id'] ?? '';

if (!$id) {
    header('Location: pages.php');
    exit;
}

// Récupérer la page
$result = supabase()->select('pages', 'id=eq.' . urlencode($id));
if (!$result['success'] || empty($result['data'])) {
    setFlash('danger', 'Page non trouvée');
    header('Location: pages.php');
    exit;
}
$page = $result['data'][0];

// Récupérer les sections de la page
$sectionsResult = supabase()->select('page_sections', 'page_id=eq.' . urlencode($id) . '&order=display_order.asc');
$sections = $sectionsResult['success'] ? $sectionsResult['data'] : [];

// Récupérer les images disponibles
$mediaResult = supabase()->select('media', 'order=uploaded_at.desc&limit=50');
$mediaList = $mediaResult['success'] ? $mediaResult['data'] : [];

// Types de sections disponibles
$sectionTypes = [
    'hero' => ['name' => 'Hero Section', 'icon' => '🎯', 'fields' => ['title', 'subtitle', 'image', 'badges', 'cta_primary', 'cta_secondary']],
    'cards' => ['name' => 'Grille de Cartes', 'icon' => '🃏', 'fields' => ['title', 'subtitle', 'items']],
    'features' => ['name' => 'Points Forts', 'icon' => '⭐', 'fields' => ['title', 'items']],
    'text' => ['name' => 'Texte Simple', 'icon' => '📝', 'fields' => ['title', 'content']],
    'cta' => ['name' => 'Call-to-Action', 'icon' => '📢', 'fields' => ['title', 'subtitle', 'cta_primary', 'cta_secondary', 'background']],
    'list' => ['name' => 'Liste', 'icon' => '📋', 'fields' => ['title', 'subtitle', 'items']],
    'image_text' => ['name' => 'Image + Texte', 'icon' => '🖼️', 'fields' => ['title', 'content', 'image', 'image_position']],
    'contact_info' => ['name' => 'Infos Contact', 'icon' => '📞', 'fields' => ['phone', 'email', 'address', 'hours']],
];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_page';

    if ($action === 'save_page') {
        // Générer le slug si modifié
        $newSlug = trim($_POST['slug'] ?? $page['slug']);
        $newSlug = strtolower(preg_replace('/[^a-z0-9-]+/i', '-', $newSlug));
        $newSlug = trim($newSlug, '-');

        $data = [
            'title' => trim($_POST['title'] ?? $page['title']),
            'slug' => $newSlug,
            'hero_title' => trim($_POST['hero_title'] ?? ''),
            'hero_subtitle' => trim($_POST['hero_subtitle'] ?? ''),
            'hero_image' => trim($_POST['hero_image'] ?? '') ?: null,
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'updated_at' => date('c')
        ];

        $result = supabase()->update('pages', 'id=eq.' . urlencode($id), $data);

        if ($result['success']) {
            setFlash('success', 'Page mise à jour !');
        } else {
            setFlash('danger', 'Erreur lors de la sauvegarde');
        }
    }

    if ($action === 'save_section') {
        $sectionId = $_POST['section_id'] ?? '';
        $sectionType = $_POST['section_type'] ?? 'text';

        // Construire les données selon le type
        $content = [];

        // Champs communs
        $content['title'] = trim($_POST['section_title'] ?? '');
        $content['subtitle'] = trim($_POST['section_subtitle'] ?? '');

        // Champs spécifiques selon le type
        if ($sectionType === 'hero') {
            $content['badges'] = array_filter(array_map('trim', explode("\n", $_POST['section_badges'] ?? '')));
            $content['cta_primary_text'] = trim($_POST['cta_primary_text'] ?? '');
            $content['cta_primary_url'] = trim($_POST['cta_primary_url'] ?? '');
            $content['cta_secondary_text'] = trim($_POST['cta_secondary_text'] ?? '');
            $content['cta_secondary_url'] = trim($_POST['cta_secondary_url'] ?? '');
        }

        if (in_array($sectionType, ['cards', 'features', 'list'])) {
            $items = [];
            $itemCount = (int)($_POST['item_count'] ?? 0);
            for ($i = 0; $i < $itemCount; $i++) {
                if (!empty($_POST['item_title_' . $i]) || !empty($_POST['item_text_' . $i])) {
                    $items[] = [
                        'icon' => trim($_POST['item_icon_' . $i] ?? ''),
                        'title' => trim($_POST['item_title_' . $i] ?? ''),
                        'text' => trim($_POST['item_text_' . $i] ?? ''),
                        'link_url' => trim($_POST['item_link_url_' . $i] ?? ''),
                        'link_text' => trim($_POST['item_link_text_' . $i] ?? ''),
                    ];
                }
            }
            $content['items'] = $items;
        }

        if ($sectionType === 'text' || $sectionType === 'image_text') {
            $content['text'] = $_POST['section_text'] ?? '';
        }

        if ($sectionType === 'cta') {
            $content['background'] = trim($_POST['section_background'] ?? 'primary');
            $content['cta_primary_text'] = trim($_POST['cta_primary_text'] ?? '');
            $content['cta_primary_url'] = trim($_POST['cta_primary_url'] ?? '');
            $content['cta_secondary_text'] = trim($_POST['cta_secondary_text'] ?? '');
            $content['cta_secondary_url'] = trim($_POST['cta_secondary_url'] ?? '');
        }

        if ($sectionType === 'image_text') {
            $content['image_position'] = $_POST['image_position'] ?? 'left';
        }

        if ($sectionType === 'contact_info') {
            $content['phone'] = trim($_POST['contact_phone'] ?? '');
            $content['email'] = trim($_POST['contact_email'] ?? '');
            $content['address'] = trim($_POST['contact_address'] ?? '');
            $content['hours'] = trim($_POST['contact_hours'] ?? '');
        }

        $sectionData = [
            'title' => $content['title'],
            'content' => json_encode($content),
            'image' => trim($_POST['section_image'] ?? '') ?: null,
            'section_type' => $sectionType,
            'is_visible' => isset($_POST['section_visible']) ? true : false,
            'updated_at' => date('c')
        ];

        if ($sectionId) {
            supabase()->update('page_sections', 'id=eq.' . urlencode($sectionId), $sectionData);
            setFlash('success', 'Section mise à jour !');
        }
    }

    if ($action === 'add_section') {
        $sectionType = $_POST['new_section_type'] ?? 'text';
        $newSection = [
            'page_id' => $id,
            'section_key' => 'section_' . time(),
            'section_type' => $sectionType,
            'title' => trim($_POST['new_section_title'] ?? 'Nouvelle section'),
            'content' => json_encode(['title' => $_POST['new_section_title'] ?? 'Nouvelle section']),
            'display_order' => count($sections) + 1,
            'is_visible' => true
        ];

        supabase()->insert('page_sections', $newSection);
        setFlash('success', 'Section ajoutée !');
    }

    if ($action === 'delete_section') {
        $sectionId = $_POST['section_id'] ?? '';
        if ($sectionId) {
            supabase()->delete('page_sections', 'id=eq.' . urlencode($sectionId));
            setFlash('success', 'Section supprimée');
        }
    }

    if ($action === 'reorder_sections') {
        $order = json_decode($_POST['sections_order'] ?? '[]', true);
        if ($order) {
            foreach ($order as $index => $sectionId) {
                supabase()->update('page_sections', 'id=eq.' . urlencode($sectionId), [
                    'display_order' => $index + 1
                ]);
            }
            setFlash('success', 'Ordre mis à jour');
        }
    }

    header('Location: page-edit.php?id=' . urlencode($id) . '#sections');
    exit;
}

$pageTitle = 'Modifier : ' . $page['title'];
require_once 'includes/header.php';

// Fonction pour décoder le contenu JSON
function getContent($section) {
    if (is_string($section['content'])) {
        $decoded = json_decode($section['content'], true);
        return is_array($decoded) ? $decoded : ['text' => $section['content']];
    }
    return $section['content'] ?: [];
}
?>

<style>
.section-editor { margin-bottom: 1.5rem; border: 2px solid var(--gray-200); border-radius: 12px; overflow: hidden; }
.section-editor.collapsed .section-body { display: none; }
.section-header { background: var(--gray-100); padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
.section-header:hover { background: var(--gray-200); }
.section-title-row { display: flex; align-items: center; gap: 1rem; }
.section-type-badge { background: var(--primary); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; }
.section-body { padding: 1.5rem; background: white; }
.items-container { border: 1px solid var(--gray-200); border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
.item-card { background: var(--gray-50); border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
.item-card:last-child { margin-bottom: 0; }
.item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.form-row-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.form-row-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
.drag-handle { cursor: move; color: var(--gray-400); font-size: 1.2rem; }
.visibility-toggle { display: flex; align-items: center; gap: 0.5rem; }
.visibility-toggle.hidden { opacity: 0.5; }

/* Block Editor Styles */
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
.block-tip-btn { color: #0369a1; }
.block-tip-btn:hover { background: #e0f2fe; }
.block-warning-btn { color: #b45309; }
.block-warning-btn:hover { background: #fef3c7; }

.blocks-container {
    border: 1px solid var(--gray-200);
    border-top: none;
    border-radius: 0 0 8px 8px;
    min-height: 200px;
    padding: 1rem;
    background: white;
}
.page-block-item {
    position: relative;
    margin-bottom: 1rem;
    border: 2px solid transparent;
    border-radius: 8px;
    transition: all 0.2s;
}
.page-block-item:hover { border-color: var(--gray-200); }
.page-block-item:focus-within { border-color: var(--primary); }

.page-block-controls {
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
.page-block-item:hover .page-block-controls,
.page-block-item:focus-within .page-block-controls { display: flex; }
.page-block-control-btn {
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
.page-block-control-btn:hover { background: var(--gray-200); }
.page-block-control-btn.delete:hover { background: #fee2e2; color: #dc2626; }

.page-block-heading { padding: 0.75rem 1rem; }
.page-block-heading-input {
    width: 100%;
    border: none;
    outline: none;
    background: transparent;
    color: #1e293b;
}
.page-block-heading[data-level="h2"] .page-block-heading-input { font-size: 1.5rem; font-weight: 700; }
.page-block-heading[data-level="h3"] .page-block-heading-input { font-size: 1.25rem; font-weight: 600; }
.page-block-heading[data-level="h4"] .page-block-heading-input { font-size: 1.1rem; font-weight: 600; }
.page-block-heading-tag {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    margin-right: 0.5rem;
    vertical-align: middle;
}
.page-block-heading[data-level="h2"] .page-block-heading-tag { background: #fef3c7; color: #b45309; }
.page-block-heading[data-level="h3"] .page-block-heading-tag { background: #dbeafe; color: #1d4ed8; }
.page-block-heading[data-level="h4"] .page-block-heading-tag { background: #f3e8ff; color: #7c3aed; }

.page-block-paragraph { padding: 0.5rem 1rem; }
.page-block-paragraph-input {
    width: 100%;
    min-height: 80px;
    border: none;
    outline: none;
    resize: vertical;
    font-size: 1rem;
    line-height: 1.7;
    background: transparent;
}

.page-block-list { padding: 0.75rem 1rem; }
.page-block-list-items { list-style: none; padding: 0; margin: 0; }
.page-block-list-item {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}
.page-block-list-item::before {
    content: ">";
    color: #f59e0b;
    font-weight: bold;
    flex-shrink: 0;
}
.page-block-list-item input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 0.95rem;
    padding: 0.25rem 0;
    background: transparent;
}
.page-add-list-item {
    color: var(--primary);
    cursor: pointer;
    font-size: 0.85rem;
    margin-top: 0.5rem;
}

.page-block-tip-box {
    background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%);
    border-left: 4px solid #0ea5e9;
    border-radius: 0 8px 8px 0;
    padding: 1rem 1.25rem;
}
.page-block-tip-label {
    font-weight: 600;
    color: #0369a1;
    margin-bottom: 0.5rem;
}
.page-block-tip-input {
    width: 100%;
    border: none;
    outline: none;
    background: transparent;
    font-size: 0.95rem;
    line-height: 1.6;
    resize: vertical;
    min-height: 50px;
}

.page-block-warning-box {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-left: 4px solid #f59e0b;
    border-radius: 0 8px 8px 0;
    padding: 1rem 1.25rem;
}
.page-block-warning-label {
    font-weight: 600;
    color: #b45309;
    margin-bottom: 0.5rem;
}
.page-block-warning-input {
    width: 100%;
    border: none;
    outline: none;
    background: transparent;
    font-size: 0.95rem;
    line-height: 1.6;
    resize: vertical;
    min-height: 50px;
}

.page-block-quote-box {
    border-left: 4px solid #6366f1;
    padding-left: 1rem;
    background: #eef2ff;
    padding: 1rem;
    border-radius: 0 8px 8px 0;
}
.page-block-quote-input {
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

.blocks-empty {
    text-align: center;
    padding: 2rem;
    color: var(--gray-400);
}
</style>

<div class="page-header d-flex justify-between align-center">
    <div>
        <h2 class="page-title"><?= e($page['title']) ?></h2>
        <p class="page-subtitle">slug: <?= e($page['slug']) ?></p>
    </div>
    <div style="display: flex; gap: 1rem;">
        <a href="../<?= e($page['slug']) ?>.html" target="_blank" class="btn btn-secondary">👁️ Voir la page</a>
        <a href="pages.php" class="btn btn-secondary">← Retour</a>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <button class="tab active" data-tab="content">Contenu & Blocs</button>
    <button class="tab" data-tab="seo">SEO</button>
</div>

<!-- Tab Contenu -->
<div class="tab-content active" id="tab-content">

    <!-- Hero Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🎯 Section Hero (En-tête de page)</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="save_page">
                <input type="hidden" name="meta_title" value="<?= e($page['meta_title']) ?>">
                <input type="hidden" name="meta_description" value="<?= e($page['meta_description']) ?>">
                <input type="hidden" name="meta_keywords" value="<?= e($page['meta_keywords']) ?>">

                <!-- Titre et Slug de la page -->
                <div class="form-row" style="margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--gray-200);">
                    <div class="form-group">
                        <label class="form-label">Titre de la page</label>
                        <input type="text" name="title" class="form-control"
                               value="<?= e($page['title']) ?>"
                               placeholder="Ex: Accueil">
                        <p class="form-help">Nom affiché dans le menu et le back-office</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Slug (URL)</label>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="color: var(--gray-500);">votresite.fr/</span>
                            <input type="text" name="slug" class="form-control" style="flex: 1;"
                                   value="<?= e($page['slug']) ?>"
                                   placeholder="ma-page"
                                   pattern="[a-z0-9-]+"
                                   oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/--+/g, '-')">
                            <span style="color: var(--gray-500);">.html</span>
                        </div>
                        <p class="form-help">URL de la page (lettres minuscules, chiffres et tirets uniquement)</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Titre Principal (H1)</label>
                        <input type="text" name="hero_title" class="form-control"
                               value="<?= e($page['hero_title']) ?>"
                               placeholder="Ex: Taxi Conventionné à Martigues">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sous-titre</label>
                        <input type="text" name="hero_subtitle" class="form-control"
                               value="<?= e($page['hero_subtitle']) ?>"
                               placeholder="Ex: Votre transport en toute sérénité">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Image Hero (optionnelle)</label>
                    <div style="display: flex; gap: 1rem; align-items: start;">
                        <input type="text" name="hero_image" id="hero_image" class="form-control"
                               value="<?= e($page['hero_image']) ?>"
                               placeholder="URL de l'image de fond ou laissez vide">
                        <button type="button" class="btn btn-secondary" onclick="openMediaPicker('hero_image')">
                            📁 Choisir
                        </button>
                    </div>
                    <?php if (!empty($page['hero_image'])): ?>
                    <div style="margin-top: 1rem;">
                        <img src="<?= e($page['hero_image']) ?>" alt="Preview" style="max-width: 300px; border-radius: 8px;">
                        <button type="button" class="btn btn-sm btn-danger" style="margin-left: 1rem;" onclick="document.getElementById('hero_image').value=''; this.parentElement.remove();">Supprimer</button>
                    </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">💾 Enregistrer le Hero</button>
            </form>
        </div>
    </div>

    <!-- Sections/Blocs -->
    <div id="sections">
        <div style="display: flex; justify-content: space-between; align-items: center; margin: 2rem 0 1rem;">
            <h3 style="margin: 0;">📦 Blocs de contenu (<?= count($sections) ?>)</h3>
        </div>

        <!-- Ajouter une section -->
        <div class="card" style="background: var(--gray-50); border: 2px dashed var(--gray-300);">
            <div class="card-body">
                <form method="POST" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                    <input type="hidden" name="action" value="add_section">
                    <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                        <label class="form-label">Titre du bloc</label>
                        <input type="text" name="new_section_title" class="form-control" value="Nouveau bloc" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                        <label class="form-label">Type de bloc</label>
                        <select name="new_section_type" class="form-control">
                            <?php foreach ($sectionTypes as $key => $type): ?>
                            <option value="<?= $key ?>"><?= $type['icon'] ?> <?= $type['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">+ Ajouter un bloc</button>
                </form>
            </div>
        </div>

        <!-- Liste des sections -->
        <?php if (empty($sections)): ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 3rem;">
                <p style="font-size: 3rem; margin-bottom: 1rem;">📦</p>
                <p style="color: var(--gray-500);">Aucun bloc de contenu. Ajoutez votre premier bloc ci-dessus.</p>
            </div>
        </div>
        <?php else: ?>

        <?php foreach ($sections as $index => $section):
            $content = getContent($section);
            $type = $section['section_type'] ?? 'text';
            $typeInfo = $sectionTypes[$type] ?? $sectionTypes['text'];
        ?>
        <div class="section-editor" id="section-<?= e($section['id']) ?>">
            <div class="section-header" onclick="toggleSection('<?= e($section['id']) ?>')">
                <div class="section-title-row">
                    <span class="drag-handle">⋮⋮</span>
                    <span class="section-type-badge"><?= $typeInfo['icon'] ?> <?= $typeInfo['name'] ?></span>
                    <strong><?= e($section['title'] ?: 'Sans titre') ?></strong>
                    <?php if (!$section['is_visible']): ?>
                    <span class="badge badge-warning">Masqué</span>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <span style="color: var(--gray-400);">▼</span>
                    <form method="POST" style="display: inline;" onclick="event.stopPropagation();">
                        <input type="hidden" name="action" value="delete_section">
                        <input type="hidden" name="section_id" value="<?= e($section['id']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ce bloc ?')">🗑️</button>
                    </form>
                </div>
            </div>

            <div class="section-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_section">
                    <input type="hidden" name="section_id" value="<?= e($section['id']) ?>">
                    <input type="hidden" name="section_type" value="<?= e($type) ?>">

                    <!-- Champs communs -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Titre du bloc</label>
                            <input type="text" name="section_title" class="form-control" value="<?= e($content['title'] ?? $section['title']) ?>">
                        </div>
                        <?php if (in_array($type, ['cards', 'features', 'list', 'cta', 'hero'])): ?>
                        <div class="form-group">
                            <label class="form-label">Sous-titre</label>
                            <input type="text" name="section_subtitle" class="form-control" value="<?= e($content['subtitle'] ?? '') ?>">
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($type === 'hero'): ?>
                    <!-- Champs Hero -->
                    <div class="form-group">
                        <label class="form-label">Badges (un par ligne)</label>
                        <textarea name="section_badges" class="form-control" rows="3" placeholder="✓ Agréé CPAM&#10;✓ Disponible 24/7"><?= e(implode("\n", $content['badges'] ?? [])) ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Bouton Principal - Texte</label>
                            <input type="text" name="cta_primary_text" class="form-control" value="<?= e($content['cta_primary_text'] ?? '') ?>" placeholder="Réserver un Taxi">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Bouton Principal - URL</label>
                            <input type="text" name="cta_primary_url" class="form-control" value="<?= e($content['cta_primary_url'] ?? '') ?>" placeholder="reservation.html">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Bouton Secondaire - Texte</label>
                            <input type="text" name="cta_secondary_text" class="form-control" value="<?= e($content['cta_secondary_text'] ?? '') ?>" placeholder="Estimer un Trajet">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Bouton Secondaire - URL</label>
                            <input type="text" name="cta_secondary_url" class="form-control" value="<?= e($content['cta_secondary_url'] ?? '') ?>" placeholder="simulateur.html">
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($type === 'text'): ?>
                    <!-- Éditeur de blocs pour le texte -->
                    <div class="form-group">
                        <label class="form-label">Contenu</label>

                        <!-- Toolbar d'ajout de blocs -->
                        <div class="block-toolbar">
                            <span style="font-size: 0.85rem; color: var(--gray-500); margin-right: 0.5rem;">Titres :</span>
                            <button type="button" class="block-btn" onclick="addPageBlock('<?= e($section['id']) ?>', 'h2')" title="Titre H2">
                                <strong>H2</strong>
                            </button>
                            <button type="button" class="block-btn" onclick="addPageBlock('<?= e($section['id']) ?>', 'h3')" title="Titre H3">
                                <strong>H3</strong>
                            </button>
                            <button type="button" class="block-btn" onclick="addPageBlock('<?= e($section['id']) ?>', 'h4')" title="Titre H4">
                                <strong>H4</strong>
                            </button>
                            <span style="font-size: 0.85rem; color: var(--gray-500); margin: 0 0.5rem;">|</span>
                            <button type="button" class="block-btn" onclick="addPageBlock('<?= e($section['id']) ?>', 'paragraph')" title="Paragraphe">
                                Texte
                            </button>
                            <button type="button" class="block-btn" onclick="addPageBlock('<?= e($section['id']) ?>', 'list')" title="Liste">
                                Liste
                            </button>
                            <button type="button" class="block-btn block-tip-btn" onclick="addPageBlock('<?= e($section['id']) ?>', 'tip')" title="Astuce">
                                Astuce
                            </button>
                            <button type="button" class="block-btn block-warning-btn" onclick="addPageBlock('<?= e($section['id']) ?>', 'warning')" title="Important">
                                Important
                            </button>
                            <button type="button" class="block-btn" onclick="addPageBlock('<?= e($section['id']) ?>', 'quote')" title="Citation">
                                Citation
                            </button>
                        </div>

                        <!-- Conteneur des blocs -->
                        <div id="blocks-<?= e($section['id']) ?>" class="blocks-container" data-section-id="<?= e($section['id']) ?>">
                            <!-- Les blocs seront chargés ici -->
                        </div>

                        <input type="hidden" name="section_text" id="content-<?= e($section['id']) ?>" value="<?= e($content['text'] ?? '') ?>">
                    </div>
                    <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        initPageBlocks('<?= e($section['id']) ?>', <?= json_encode($content['text'] ?? '') ?>);
                    });
                    </script>
                    <?php endif; ?>

                    <?php if ($type === 'image_text'): ?>
                    <!-- Champs Image + Texte -->
                    <div class="form-group">
                        <label class="form-label">Image</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" name="section_image" id="img_<?= e($section['id']) ?>" class="form-control" value="<?= e($section['image'] ?? '') ?>">
                            <button type="button" class="btn btn-secondary" onclick="openMediaPicker('img_<?= e($section['id']) ?>')">📁</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position de l'image</label>
                        <select name="image_position" class="form-control" style="max-width: 200px;">
                            <option value="left" <?= ($content['image_position'] ?? '') === 'left' ? 'selected' : '' ?>>Gauche</option>
                            <option value="right" <?= ($content['image_position'] ?? '') === 'right' ? 'selected' : '' ?>>Droite</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Texte</label>
                        <textarea name="section_text" class="form-control" rows="6"><?= e($content['text'] ?? '') ?></textarea>
                    </div>
                    <?php endif; ?>

                    <?php if ($type === 'cta'): ?>
                    <!-- Champs CTA -->
                    <div class="form-group">
                        <label class="form-label">Couleur de fond</label>
                        <select name="section_background" class="form-control" style="max-width: 200px;">
                            <option value="primary" <?= ($content['background'] ?? '') === 'primary' ? 'selected' : '' ?>>Primaire (bleu/noir)</option>
                            <option value="secondary" <?= ($content['background'] ?? '') === 'secondary' ? 'selected' : '' ?>>Secondaire</option>
                            <option value="light" <?= ($content['background'] ?? '') === 'light' ? 'selected' : '' ?>>Clair</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Bouton Principal - Texte</label>
                            <input type="text" name="cta_primary_text" class="form-control" value="<?= e($content['cta_primary_text'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Bouton Principal - URL</label>
                            <input type="text" name="cta_primary_url" class="form-control" value="<?= e($content['cta_primary_url'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Bouton Secondaire - Texte</label>
                            <input type="text" name="cta_secondary_text" class="form-control" value="<?= e($content['cta_secondary_text'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Bouton Secondaire - URL</label>
                            <input type="text" name="cta_secondary_url" class="form-control" value="<?= e($content['cta_secondary_url'] ?? '') ?>">
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($type === 'contact_info'): ?>
                    <!-- Champs Contact -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="contact_phone" class="form-control" value="<?= e($content['phone'] ?? '') ?>" placeholder="01 23 45 67 89">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="contact_email" class="form-control" value="<?= e($content['email'] ?? '') ?>" placeholder="contact@taxijulien.fr">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Adresse</label>
                            <input type="text" name="contact_address" class="form-control" value="<?= e($content['address'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Horaires</label>
                            <input type="text" name="contact_hours" class="form-control" value="<?= e($content['hours'] ?? '') ?>" placeholder="Disponible 24h/24, 7j/7">
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (in_array($type, ['cards', 'features', 'list'])): ?>
                    <!-- Éditeur d'items -->
                    <div class="items-container">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <label class="form-label" style="margin: 0;">
                                <?= $type === 'cards' ? 'Cartes' : ($type === 'features' ? 'Points forts' : 'Éléments de liste') ?>
                            </label>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="addItem(this, '<?= e($section['id']) ?>')">+ Ajouter</button>
                        </div>

                        <div class="items-list" id="items-<?= e($section['id']) ?>">
                            <?php
                            $items = $content['items'] ?? [];
                            foreach ($items as $i => $item):
                            ?>
                            <div class="item-card">
                                <div class="item-header">
                                    <strong><?= $type === 'list' ? 'Élément' : 'Carte' ?> <?= $i + 1 ?></strong>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.item-card').remove(); updateItemCount(this);">×</button>
                                </div>
                                <?php if ($type !== 'list'): ?>
                                <div class="form-row-3">
                                    <div class="form-group">
                                        <label class="form-label">Icône</label>
                                        <input type="text" name="item_icon_<?= $i ?>" class="form-control" value="<?= e($item['icon'] ?? '') ?>" placeholder="🏥">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Titre</label>
                                        <input type="text" name="item_title_<?= $i ?>" class="form-control" value="<?= e($item['title'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Texte</label>
                                        <input type="text" name="item_text_<?= $i ?>" class="form-control" value="<?= e($item['text'] ?? '') ?>">
                                    </div>
                                </div>
                                <?php if ($type === 'cards'): ?>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Texte du lien</label>
                                        <input type="text" name="item_link_text_<?= $i ?>" class="form-control" value="<?= e($item['link_text'] ?? '') ?>" placeholder="En savoir plus →">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">URL du lien</label>
                                        <input type="text" name="item_link_url_<?= $i ?>" class="form-control" value="<?= e($item['link_url'] ?? '') ?>" placeholder="page.html">
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php else: ?>
                                <div class="form-group">
                                    <input type="text" name="item_title_<?= $i ?>" class="form-control" value="<?= e($item['title'] ?? $item['text'] ?? '') ?>" placeholder="Élément de la liste">
                                    <input type="hidden" name="item_text_<?= $i ?>" value="">
                                    <input type="hidden" name="item_icon_<?= $i ?>" value="✓">
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="item_count" id="item_count_<?= e($section['id']) ?>" value="<?= count($items) ?>">
                    </div>
                    <?php endif; ?>

                    <!-- Visibilité et sauvegarde -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-200);">
                        <label class="visibility-toggle <?= !$section['is_visible'] ? 'hidden' : '' ?>">
                            <input type="checkbox" name="section_visible" <?= $section['is_visible'] ? 'checked' : '' ?>>
                            <span>Visible sur le site</span>
                        </label>
                        <button type="submit" class="btn btn-primary">💾 Enregistrer ce bloc</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <?php endif; ?>
    </div>
</div>

<!-- Tab SEO -->
<div class="tab-content" id="tab-seo" style="display: none;">
    <form method="POST">
        <input type="hidden" name="action" value="save_page">
        <input type="hidden" name="title" value="<?= e($page['title']) ?>">
        <input type="hidden" name="slug" value="<?= e($page['slug']) ?>">
        <input type="hidden" name="hero_title" value="<?= e($page['hero_title']) ?>">
        <input type="hidden" name="hero_subtitle" value="<?= e($page['hero_subtitle']) ?>">
        <input type="hidden" name="hero_image" value="<?= e($page['hero_image']) ?>">

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🔍 Balises Meta SEO</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Meta Title</label>
                    <input type="text" name="meta_title" class="form-control"
                           value="<?= e($page['meta_title']) ?>" maxlength="70">
                    <p class="form-help">
                        <span id="meta-title-count"><?= strlen($page['meta_title'] ?? '') ?></span>/60 caractères recommandés
                    </p>
                </div>

                <div class="form-group">
                    <label class="form-label">Meta Description</label>
                    <textarea name="meta_description" class="form-control" rows="3" maxlength="200"><?= e($page['meta_description']) ?></textarea>
                    <p class="form-help">
                        <span id="meta-desc-count"><?= strlen($page['meta_description'] ?? '') ?></span>/160 caractères recommandés
                    </p>
                </div>

                <div class="form-group">
                    <label class="form-label">Meta Keywords</label>
                    <input type="text" name="meta_keywords" class="form-control"
                           value="<?= e($page['meta_keywords']) ?>">
                    <p class="form-help">Mots-clés séparés par des virgules</p>
                </div>

                <!-- Aperçu Google -->
                <div style="background: var(--gray-100); padding: 1.5rem; border-radius: 8px; margin-top: 1.5rem;">
                    <p style="font-size: 0.75rem; color: var(--gray-500); margin-bottom: 0.75rem;">Aperçu Google</p>
                    <p style="color: #1a0dab; font-size: 1.2rem; margin-bottom: 0.25rem;">
                        <?= e($page['meta_title'] ?: $page['title']) ?>
                    </p>
                    <p style="color: #006621; font-size: 0.9rem; margin-bottom: 0.25rem;">
                        votresite.fr/<?= e($page['slug']) ?>
                    </p>
                    <p style="color: #545454; font-size: 0.9rem;">
                        <?= e($page['meta_description'] ?: 'Description de la page...') ?>
                    </p>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">💾 Enregistrer le SEO</button>
    </form>
</div>

<!-- Modal Media Picker -->
<div id="media-picker-modal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3>Choisir une image</h3>
            <button type="button" class="modal-close" onclick="closeMediaPicker()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="image-grid" style="grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.5rem;">
                <?php foreach ($mediaList as $media): ?>
                <div class="image-item" style="cursor: pointer; padding: 0.25rem; border: 2px solid transparent; border-radius: 8px;"
                     onclick="selectMedia('<?= e($media['file_url']) ?>')"
                     onmouseover="this.style.borderColor='var(--primary)'"
                     onmouseout="this.style.borderColor='transparent'">
                    <img src="<?= e($media['file_url']) ?>" alt="<?= e($media['alt_text'] ?? '') ?>"
                         style="width: 100%; height: 80px; object-fit: cover; border-radius: 6px;">
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($mediaList)): ?>
            <p style="text-align: center; color: var(--gray-500); padding: 2rem;">
                Aucune image. <a href="media.php">Uploadez des images</a>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Tabs
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
        tab.classList.add('active');
        document.getElementById('tab-' + tab.dataset.tab).style.display = 'block';
    });
});

// Toggle section collapse
function toggleSection(id) {
    const section = document.getElementById('section-' + id);
    section.classList.toggle('collapsed');
}

// Compteurs SEO
document.querySelector('input[name="meta_title"]')?.addEventListener('input', function() {
    document.getElementById('meta-title-count').textContent = this.value.length;
});
document.querySelector('textarea[name="meta_description"]')?.addEventListener('input', function() {
    document.getElementById('meta-desc-count').textContent = this.value.length;
});

// Media Picker
let currentMediaTarget = null;

function openMediaPicker(targetId) {
    currentMediaTarget = targetId;
    document.getElementById('media-picker-modal').classList.add('active');
}

function closeMediaPicker() {
    document.getElementById('media-picker-modal').classList.remove('active');
    currentMediaTarget = null;
}

function selectMedia(url) {
    if (currentMediaTarget) {
        document.getElementById(currentMediaTarget).value = url;
    }
    closeMediaPicker();
}

document.getElementById('media-picker-modal').addEventListener('click', function(e) {
    if (e.target === this) closeMediaPicker();
});

// Add item to list
function addItem(button, sectionId) {
    const container = document.getElementById('items-' + sectionId);
    const countInput = document.getElementById('item_count_' + sectionId);
    const index = parseInt(countInput.value);

    const form = button.closest('form');
    const sectionType = form.querySelector('input[name="section_type"]').value;

    let html = '';
    if (sectionType === 'list') {
        html = `
            <div class="item-card">
                <div class="item-header">
                    <strong>Élément ${index + 1}</strong>
                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.item-card').remove(); updateItemCount(this);">×</button>
                </div>
                <div class="form-group">
                    <input type="text" name="item_title_${index}" class="form-control" placeholder="Élément de la liste">
                    <input type="hidden" name="item_text_${index}" value="">
                    <input type="hidden" name="item_icon_${index}" value="✓">
                </div>
            </div>
        `;
    } else {
        html = `
            <div class="item-card">
                <div class="item-header">
                    <strong>Carte ${index + 1}</strong>
                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.item-card').remove(); updateItemCount(this);">×</button>
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <label class="form-label">Icône</label>
                        <input type="text" name="item_icon_${index}" class="form-control" placeholder="🏥">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Titre</label>
                        <input type="text" name="item_title_${index}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Texte</label>
                        <input type="text" name="item_text_${index}" class="form-control">
                    </div>
                </div>
                ${sectionType === 'cards' ? `
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Texte du lien</label>
                        <input type="text" name="item_link_text_${index}" class="form-control" placeholder="En savoir plus →">
                    </div>
                    <div class="form-group">
                        <label class="form-label">URL du lien</label>
                        <input type="text" name="item_link_url_${index}" class="form-control" placeholder="page.html">
                    </div>
                </div>
                ` : ''}
            </div>
        `;
    }

    container.insertAdjacentHTML('beforeend', html);
    countInput.value = index + 1;
}

function updateItemCount(element) {
    const container = element.closest('.items-container');
    const countInput = container.querySelector('input[name^="item_count"]');
    const items = container.querySelectorAll('.item-card');
    countInput.value = items.length;

    // Renumber items
    items.forEach((item, i) => {
        const inputs = item.querySelectorAll('input[name^="item_"]');
        inputs.forEach(input => {
            const name = input.name.replace(/_\d+$/, '_' + i);
            input.name = name;
        });
    });
}

// Escape to close modal
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMediaPicker();
});

// ========== PAGE BLOCK EDITOR ==========

// Initialiser les blocs pour une section
function initPageBlocks(sectionId, existingContent) {
    const container = document.getElementById('blocks-' + sectionId);
    if (!container) return;

    if (existingContent && existingContent.trim()) {
        parsePageContent(sectionId, existingContent);
    } else {
        showPageEmptyState(sectionId);
    }
}

// Parser le contenu HTML existant
function parsePageContent(sectionId, html) {
    const container = document.getElementById('blocks-' + sectionId);
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const elements = doc.body.children;

    if (elements.length === 0) {
        // Contenu texte simple
        addPageBlock(sectionId, 'paragraph', html);
        return;
    }

    for (const el of elements) {
        const tag = el.tagName.toLowerCase();

        if (tag === 'h2') {
            addPageBlock(sectionId, 'h2', el.textContent);
        } else if (tag === 'h3') {
            addPageBlock(sectionId, 'h3', el.textContent);
        } else if (tag === 'h4') {
            addPageBlock(sectionId, 'h4', el.textContent);
        } else if (tag === 'blockquote') {
            addPageBlock(sectionId, 'quote', el.textContent);
        } else if (tag === 'div' && el.classList.contains('tip-box')) {
            addPageBlock(sectionId, 'tip', el.textContent.replace(/^Astuce Pro\s*:\s*/i, ''));
        } else if (tag === 'div' && el.classList.contains('warning-box')) {
            addPageBlock(sectionId, 'warning', el.textContent.replace(/^Important\s*:\s*/i, ''));
        } else if (tag === 'ul' || tag === 'ol') {
            const items = Array.from(el.querySelectorAll('li')).map(li => li.textContent);
            addPageBlock(sectionId, 'list', items);
        } else if (tag === 'p' || tag === 'div') {
            addPageBlock(sectionId, 'paragraph', el.innerHTML);
        }
    }

    if (container.children.length === 0) {
        addPageBlock(sectionId, 'paragraph', html);
    }
}

function showPageEmptyState(sectionId) {
    const container = document.getElementById('blocks-' + sectionId);
    container.innerHTML = `
        <div class="blocks-empty">
            <p style="font-size: 1.5rem; margin-bottom: 0.5rem;">+</p>
            <p>Cliquez sur un bouton ci-dessus pour ajouter du contenu</p>
        </div>
    `;
}

function removePageEmptyState(sectionId) {
    const container = document.getElementById('blocks-' + sectionId);
    const emptyState = container.querySelector('.blocks-empty');
    if (emptyState) emptyState.remove();
}

// Ajouter un bloc a une section de page
function addPageBlock(sectionId, type, content = '') {
    removePageEmptyState(sectionId);
    const container = document.getElementById('blocks-' + sectionId);
    const blockId = 'pb-' + Date.now() + '-' + Math.random().toString(36).substr(2, 5);

    let blockHTML = '';

    switch (type) {
        case 'h2':
        case 'h3':
        case 'h4':
            blockHTML = `
                <div class="page-block-item page-block-heading" data-type="${type}" data-level="${type}" id="${blockId}">
                    <div class="page-block-controls">
                        <button type="button" class="page-block-control-btn" onclick="movePageBlock('${sectionId}', '${blockId}', -1)">&#8593;</button>
                        <button type="button" class="page-block-control-btn" onclick="movePageBlock('${sectionId}', '${blockId}', 1)">&#8595;</button>
                        <button type="button" class="page-block-control-btn delete" onclick="deletePageBlock('${sectionId}', '${blockId}')">x</button>
                    </div>
                    <span class="page-block-heading-tag">${type.toUpperCase()}</span>
                    <input type="text" class="page-block-heading-input" placeholder="Titre ${type.toUpperCase()}" value="${escapePageHtml(content)}" oninput="syncPageContent('${sectionId}')">
                </div>
            `;
            break;

        case 'paragraph':
            blockHTML = `
                <div class="page-block-item page-block-paragraph" data-type="paragraph" id="${blockId}">
                    <div class="page-block-controls">
                        <button type="button" class="page-block-control-btn" onclick="movePageBlock('${sectionId}', '${blockId}', -1)">&#8593;</button>
                        <button type="button" class="page-block-control-btn" onclick="movePageBlock('${sectionId}', '${blockId}', 1)">&#8595;</button>
                        <button type="button" class="page-block-control-btn delete" onclick="deletePageBlock('${sectionId}', '${blockId}')">x</button>
                    </div>
                    <textarea class="page-block-paragraph-input" placeholder="Ecrivez votre texte ici..." oninput="syncPageContent('${sectionId}')">${content}</textarea>
                </div>
            `;
            break;

        case 'list':
            const items = Array.isArray(content) ? content : ['', '', ''];
            let listItemsHTML = items.map((item, i) => `
                <div class="page-block-list-item">
                    <input type="text" placeholder="Element ${i + 1}" value="${escapePageHtml(item)}" oninput="syncPageContent('${sectionId}')">
                    <button type="button" style="border: none; background: none; color: var(--gray-400); cursor: pointer;" onclick="this.parentElement.remove(); syncPageContent('${sectionId}')">x</button>
                </div>
            `).join('');

            blockHTML = `
                <div class="page-block-item page-block-list" data-type="list" id="${blockId}">
                    <div class="page-block-controls">
                        <button type="button" class="page-block-control-btn" onclick="movePageBlock('${sectionId}', '${blockId}', -1)">&#8593;</button>
                        <button type="button" class="page-block-control-btn" onclick="movePageBlock('${sectionId}', '${blockId}', 1)">&#8595;</button>
                        <button type="button" class="page-block-control-btn delete" onclick="deletePageBlock('${sectionId}', '${blockId}')">x</button>
                    </div>
                    <div class="page-block-list-items">
                        ${listItemsHTML}
                    </div>
                    <div class="page-add-list-item" onclick="addPageListItem('${sectionId}', '${blockId}')">+ Ajouter un element</div>
                </div>
            `;
            break;

        case 'tip':
            blockHTML = `
                <div class="page-block-item" data-type="tip" id="${blockId}">
                    <div class="page-block-controls">
                        <button type="button" class="page-block-control-btn" onclick="movePageBlock('${sectionId}', '${blockId}', -1)">&#8593;</button>
                        <button type="button" class="page-block-control-btn" onclick="movePageBlock('${sectionId}', '${blockId}', 1)">&#8595;</button>
                        <button type="button" class="page-block-control-btn delete" onclick="deletePageBlock('${sectionId}', '${blockId}')">x</button>
                    </div>
                    <div class="page-block-tip-box">
                        <div class="page-block-tip-label">Astuce Pro :</div>
                        <textarea class="page-block-tip-input" placeholder="Votre conseil ou astuce..." oninput="syncPageContent('${sectionId}')">${escapePageHtml(content)}</textarea>
                    </div>
                </div>
            `;
            break;

        case 'warning':
            blockHTML = `
                <div class="page-block-item" data-type="warning" id="${blockId}">
                    <div class="page-block-controls">
                        <button type="button" class="page-block-control-btn" onclick="movePageBlock('${sectionId}', '${blockId}', -1)">&#8593;</button>
                        <button type="button" class="page-block-control-btn" onclick="movePageBlock('${sectionId}', '${blockId}', 1)">&#8595;</button>
                        <button type="button" class="page-block-control-btn delete" onclick="deletePageBlock('${sectionId}', '${blockId}')">x</button>
                    </div>
                    <div class="page-block-warning-box">
                        <div class="page-block-warning-label">Important :</div>
                        <textarea class="page-block-warning-input" placeholder="Information importante..." oninput="syncPageContent('${sectionId}')">${escapePageHtml(content)}</textarea>
                    </div>
                </div>
            `;
            break;

        case 'quote':
            blockHTML = `
                <div class="page-block-item page-block-quote" data-type="quote" id="${blockId}">
                    <div class="page-block-controls">
                        <button type="button" class="page-block-control-btn" onclick="movePageBlock('${sectionId}', '${blockId}', -1)">&#8593;</button>
                        <button type="button" class="page-block-control-btn" onclick="movePageBlock('${sectionId}', '${blockId}', 1)">&#8595;</button>
                        <button type="button" class="page-block-control-btn delete" onclick="deletePageBlock('${sectionId}', '${blockId}')">x</button>
                    </div>
                    <div class="page-block-quote-box">
                        <textarea class="page-block-quote-input" placeholder="Votre citation..." oninput="syncPageContent('${sectionId}')">${escapePageHtml(content)}</textarea>
                    </div>
                </div>
            `;
            break;
    }

    container.insertAdjacentHTML('beforeend', blockHTML);
    syncPageContent(sectionId);
}

// Ajouter un element a une liste
function addPageListItem(sectionId, blockId) {
    const block = document.getElementById(blockId);
    const listItems = block.querySelector('.page-block-list-items');
    const count = listItems.children.length + 1;

    listItems.insertAdjacentHTML('beforeend', `
        <div class="page-block-list-item">
            <input type="text" placeholder="Element ${count}" oninput="syncPageContent('${sectionId}')">
            <button type="button" style="border: none; background: none; color: var(--gray-400); cursor: pointer;" onclick="this.parentElement.remove(); syncPageContent('${sectionId}')">x</button>
        </div>
    `);
    syncPageContent(sectionId);
}

// Deplacer un bloc
function movePageBlock(sectionId, blockId, direction) {
    const block = document.getElementById(blockId);
    if (direction === -1 && block.previousElementSibling) {
        block.parentNode.insertBefore(block, block.previousElementSibling);
    } else if (direction === 1 && block.nextElementSibling) {
        block.parentNode.insertBefore(block.nextElementSibling, block);
    }
    syncPageContent(sectionId);
}

// Supprimer un bloc
function deletePageBlock(sectionId, blockId) {
    const block = document.getElementById(blockId);
    const container = document.getElementById('blocks-' + sectionId);
    if (confirm('Supprimer ce bloc ?')) {
        block.remove();
        if (container.children.length === 0) {
            showPageEmptyState(sectionId);
        }
        syncPageContent(sectionId);
    }
}

// Echapper le HTML
function escapePageHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Generer le HTML et synchroniser l'input hidden
function syncPageContent(sectionId) {
    const container = document.getElementById('blocks-' + sectionId);
    const input = document.getElementById('content-' + sectionId);
    if (!container || !input) return;

    let html = '';
    const blocks = container.querySelectorAll('.page-block-item');

    blocks.forEach(block => {
        const type = block.dataset.type;

        switch (type) {
            case 'h2':
            case 'h3':
            case 'h4':
                const headingText = block.querySelector('.page-block-heading-input').value;
                if (headingText.trim()) {
                    html += `<${type}>${headingText}</${type}>\n`;
                }
                break;

            case 'paragraph':
                const paragraphText = block.querySelector('.page-block-paragraph-input').value;
                if (paragraphText.trim()) {
                    const paragraphs = paragraphText.split('\n\n').filter(p => p.trim());
                    paragraphs.forEach(p => {
                        html += `<p>${p.replace(/\n/g, '<br>')}</p>\n`;
                    });
                }
                break;

            case 'list':
                const listItems = block.querySelectorAll('.page-block-list-item input');
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
                const tipText = block.querySelector('.page-block-tip-input').value;
                if (tipText.trim()) {
                    html += `<div class="tip-box"><strong>Astuce Pro :</strong> ${tipText}</div>\n`;
                }
                break;

            case 'warning':
                const warningText = block.querySelector('.page-block-warning-input').value;
                if (warningText.trim()) {
                    html += `<div class="warning-box"><strong>Important :</strong> ${warningText}</div>\n`;
                }
                break;

            case 'quote':
                const quoteText = block.querySelector('.page-block-quote-input').value;
                if (quoteText.trim()) {
                    html += `<blockquote>${quoteText}</blockquote>\n`;
                }
                break;
        }
    });

    input.value = html;
}

// Synchroniser avant soumission de tous les formulaires
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', () => {
        document.querySelectorAll('.blocks-container').forEach(container => {
            const sectionId = container.dataset.sectionId;
            if (sectionId) syncPageContent(sectionId);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
