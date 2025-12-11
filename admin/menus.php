<?php
$pageTitle = 'Gestion des menus';
require_once 'includes/header.php';

// Récupérer les pages pour le sélecteur
$pagesResult = supabase()->select('pages', 'select=id,slug,title&order=title.asc');
$pages = $pagesResult['success'] ? $pagesResult['data'] : [];

// Récupérer les menus existants
$menusResult = supabase()->select('settings', 'key=in.(menu_header,menu_footer)');
$menus = ['header' => [], 'footer' => []];
if ($menusResult['success']) {
    foreach ($menusResult['data'] as $m) {
        if ($m['key'] === 'menu_header') {
            $menus['header'] = json_decode($m['value'], true) ?: [];
        }
        if ($m['key'] === 'menu_footer') {
            $menus['footer'] = json_decode($m['value'], true) ?: [];
        }
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $menuType = $_POST['menu_type'] ?? 'header';
    $menuKey = 'menu_' . $menuType;

    if ($action === 'add_item') {
        $label = trim($_POST['label'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $target = $_POST['target'] ?? '_self';

        if ($label && $url) {
            $menus[$menuType][] = [
                'id' => uniqid(),
                'label' => $label,
                'url' => $url,
                'target' => $target
            ];

            // Sauvegarder
            $existing = supabase()->select('settings', "key=eq.{$menuKey}");
            if ($existing['success'] && !empty($existing['data'])) {
                supabase()->update('settings', "key=eq.{$menuKey}", ['value' => json_encode($menus[$menuType], JSON_UNESCAPED_UNICODE)]);
            } else {
                supabase()->insert('settings', ['key' => $menuKey, 'value' => json_encode($menus[$menuType], JSON_UNESCAPED_UNICODE)]);
            }

            setFlash('success', 'Élément ajouté au menu');
        }
    }

    if ($action === 'delete_item') {
        $id = $_POST['id'] ?? '';
        $menus[$menuType] = array_values(array_filter($menus[$menuType], fn($item) => $item['id'] !== $id));
        supabase()->update('settings', "key=eq.{$menuKey}", ['value' => json_encode($menus[$menuType], JSON_UNESCAPED_UNICODE)]);
        setFlash('success', 'Élément supprimé');
    }

    if ($action === 'reorder') {
        $order = json_decode($_POST['order'] ?? '[]', true);
        if (!empty($order)) {
            $newMenu = [];
            foreach ($order as $id) {
                foreach ($menus[$menuType] as $item) {
                    if ($item['id'] === $id) {
                        $newMenu[] = $item;
                        break;
                    }
                }
            }
            $menus[$menuType] = $newMenu;
            supabase()->update('settings', "key=eq.{$menuKey}", ['value' => json_encode($menus[$menuType], JSON_UNESCAPED_UNICODE)]);
            echo json_encode(['success' => true]);
            exit;
        }
    }

    if ($action !== 'reorder') {
        header('Location: menus.php');
        exit;
    }
}
?>

<style>
.menus-grid {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 1.5rem;
}
.menu-selector {
    background: white;
    border-radius: 12px;
    border: 1px solid var(--gray-200);
    overflow: hidden;
}
.menu-selector-header {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-200);
    font-weight: 600;
}
.menu-selector-item {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: background 0.2s;
}
.menu-selector-item:hover {
    background: var(--gray-50);
}
.menu-selector-item.active {
    background: var(--primary);
    color: white;
}
.menu-selector-item:last-child {
    border-bottom: none;
}
.menu-editor {
    background: white;
    border-radius: 12px;
    border: 1px solid var(--gray-200);
}
.menu-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.menu-items {
    min-height: 200px;
    padding: 1rem;
}
.menu-item {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    cursor: grab;
    transition: all 0.2s;
}
.menu-item:hover {
    border-color: var(--primary);
    background: white;
}
.menu-item.dragging {
    opacity: 0.5;
    transform: scale(1.02);
}
.menu-item-handle {
    color: var(--gray-400);
    cursor: grab;
}
.menu-item-content {
    flex: 1;
}
.menu-item-label {
    font-weight: 500;
}
.menu-item-url {
    font-size: 0.8rem;
    color: var(--gray-500);
    font-family: monospace;
}
.menu-item-actions {
    display: flex;
    gap: 0.5rem;
}
.menu-empty {
    text-align: center;
    padding: 3rem;
    color: var(--gray-500);
}
.add-item-form {
    padding: 1.5rem;
    border-top: 1px solid var(--gray-200);
    background: var(--gray-50);
}
.add-item-grid {
    display: grid;
    grid-template-columns: 1fr 1fr auto auto;
    gap: 1rem;
    align-items: end;
}
.page-selector {
    margin-top: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid var(--gray-200);
}
.page-list {
    max-height: 200px;
    overflow-y: auto;
    margin-top: 0.5rem;
}
.page-option {
    padding: 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.page-option:hover {
    background: var(--gray-100);
}
.target-badge {
    font-size: 0.7rem;
    padding: 0.15rem 0.4rem;
    border-radius: 3px;
    background: var(--gray-200);
    color: var(--gray-600);
}
</style>

<div class="page-header">
    <h2 class="page-title">Gestion des menus</h2>
    <p class="page-subtitle">Configurez les menus de navigation de votre site</p>
</div>

<div class="menus-grid">
    <!-- Sélecteur de menu -->
    <div>
        <div class="menu-selector">
            <div class="menu-selector-header">Emplacements de menu</div>
            <div class="menu-selector-item active" data-menu="header" onclick="selectMenu('header')">
                <span style="font-size: 1.2rem;">📍</span>
                <div>
                    <div style="font-weight: 500;">Menu principal</div>
                    <div style="font-size: 0.8rem; opacity: 0.8;">Navigation header</div>
                </div>
            </div>
            <div class="menu-selector-item" data-menu="footer" onclick="selectMenu('footer')">
                <span style="font-size: 1.2rem;">📍</span>
                <div>
                    <div style="font-weight: 500;">Menu pied de page</div>
                    <div style="font-size: 0.8rem; opacity: 0.8;">Navigation footer</div>
                </div>
            </div>
        </div>

        <!-- Ajouter depuis les pages -->
        <div class="page-selector">
            <div style="font-weight: 600; margin-bottom: 0.5rem;">📄 Ajouter une page</div>
            <p style="font-size: 0.85rem; color: var(--gray-500); margin-bottom: 0.75rem;">
                Cliquez sur une page pour l'ajouter au menu sélectionné
            </p>
            <div class="page-list">
                <?php foreach ($pages as $page): ?>
                <div class="page-option" onclick="addPageToMenu('<?= e($page['title']) ?>', '/<?= e($page['slug']) ?>.html')">
                    <span>📄</span>
                    <span><?= e($page['title']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Éditeur de menu -->
    <div class="menu-editor">
        <div class="menu-header">
            <h3 style="margin: 0;" id="menu-title">Menu principal</h3>
            <span style="color: var(--gray-500); font-size: 0.9rem;" id="menu-count">
                <?= count($menus['header']) ?> éléments
            </span>
        </div>

        <!-- Menu Header -->
        <div id="menu-header-items" class="menu-items">
            <?php if (empty($menus['header'])): ?>
            <div class="menu-empty" id="empty-header">
                <p style="font-size: 2rem; margin-bottom: 0.5rem;">☰</p>
                <p>Aucun élément dans ce menu</p>
                <p style="font-size: 0.85rem;">Ajoutez des pages ou des liens personnalisés</p>
            </div>
            <?php else: ?>
            <?php foreach ($menus['header'] as $item): ?>
            <div class="menu-item" draggable="true" data-id="<?= e($item['id']) ?>">
                <span class="menu-item-handle">⋮⋮</span>
                <div class="menu-item-content">
                    <div class="menu-item-label"><?= e($item['label']) ?></div>
                    <div class="menu-item-url"><?= e($item['url']) ?></div>
                </div>
                <?php if (($item['target'] ?? '_self') === '_blank'): ?>
                <span class="target-badge">Nouvel onglet</span>
                <?php endif; ?>
                <div class="menu-item-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_item">
                        <input type="hidden" name="menu_type" value="header">
                        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cet élément ?')">🗑️</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Menu Footer (caché par défaut) -->
        <div id="menu-footer-items" class="menu-items" style="display: none;">
            <?php if (empty($menus['footer'])): ?>
            <div class="menu-empty" id="empty-footer">
                <p style="font-size: 2rem; margin-bottom: 0.5rem;">☰</p>
                <p>Aucun élément dans ce menu</p>
                <p style="font-size: 0.85rem;">Ajoutez des pages ou des liens personnalisés</p>
            </div>
            <?php else: ?>
            <?php foreach ($menus['footer'] as $item): ?>
            <div class="menu-item" draggable="true" data-id="<?= e($item['id']) ?>">
                <span class="menu-item-handle">⋮⋮</span>
                <div class="menu-item-content">
                    <div class="menu-item-label"><?= e($item['label']) ?></div>
                    <div class="menu-item-url"><?= e($item['url']) ?></div>
                </div>
                <?php if (($item['target'] ?? '_self') === '_blank'): ?>
                <span class="target-badge">Nouvel onglet</span>
                <?php endif; ?>
                <div class="menu-item-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_item">
                        <input type="hidden" name="menu_type" value="footer">
                        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cet élément ?')">🗑️</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Formulaire d'ajout -->
        <div class="add-item-form">
            <h4 style="margin: 0 0 1rem 0;">➕ Ajouter un lien personnalisé</h4>
            <form method="POST" class="add-item-grid" id="add-form">
                <input type="hidden" name="action" value="add_item">
                <input type="hidden" name="menu_type" value="header" id="menu-type-input">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Libellé</label>
                    <input type="text" name="label" id="add-label" class="form-control" placeholder="Accueil" required>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">URL</label>
                    <input type="text" name="url" id="add-url" class="form-control" placeholder="/index.html" required>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Cible</label>
                    <select name="target" class="form-control">
                        <option value="_self">Même fenêtre</option>
                        <option value="_blank">Nouvel onglet</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>
        </div>
    </div>
</div>

<!-- Aide -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">💡 Conseils</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
            <div>
                <h4 style="margin-bottom: 0.5rem;">📱 Responsive</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Limitez le menu principal à 5-7 éléments pour une meilleure lisibilité sur mobile.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">🔗 Liens externes</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Pour les liens externes (réseaux sociaux, etc.), utilisez "Nouvel onglet".
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">↕️ Réorganiser</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Glissez-déposez les éléments pour réorganiser l'ordre du menu.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
let currentMenu = 'header';

function selectMenu(type) {
    currentMenu = type;

    // Mettre à jour la sélection visuelle
    document.querySelectorAll('.menu-selector-item').forEach(item => {
        item.classList.toggle('active', item.dataset.menu === type);
    });

    // Afficher le bon menu
    document.getElementById('menu-header-items').style.display = type === 'header' ? 'block' : 'none';
    document.getElementById('menu-footer-items').style.display = type === 'footer' ? 'block' : 'none';

    // Mettre à jour le formulaire
    document.getElementById('menu-type-input').value = type;

    // Mettre à jour le titre
    document.getElementById('menu-title').textContent = type === 'header' ? 'Menu principal' : 'Menu pied de page';

    // Mettre à jour le compteur
    const items = document.querySelectorAll(`#menu-${type}-items .menu-item`);
    document.getElementById('menu-count').textContent = items.length + ' éléments';
}

function addPageToMenu(label, url) {
    document.getElementById('add-label').value = label;
    document.getElementById('add-url').value = url;
    document.getElementById('add-form').scrollIntoView({ behavior: 'smooth' });
}

// Drag and drop
let draggedItem = null;

document.querySelectorAll('.menu-item').forEach(item => {
    item.addEventListener('dragstart', function(e) {
        draggedItem = this;
        this.classList.add('dragging');
    });

    item.addEventListener('dragend', function() {
        this.classList.remove('dragging');
        saveOrder();
    });

    item.addEventListener('dragover', function(e) {
        e.preventDefault();
        const container = this.parentElement;
        const afterElement = getDragAfterElement(container, e.clientY);
        if (afterElement == null) {
            container.appendChild(draggedItem);
        } else {
            container.insertBefore(draggedItem, afterElement);
        }
    });
});

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.menu-item:not(.dragging)')];

    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function saveOrder() {
    const container = document.getElementById(`menu-${currentMenu}-items`);
    const items = container.querySelectorAll('.menu-item');
    const order = Array.from(items).map(item => item.dataset.id);

    fetch('menus.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=reorder&menu_type=${currentMenu}&order=${JSON.stringify(order)}`
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
