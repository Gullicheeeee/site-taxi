<?php
$pageTitle = 'Redirections';
require_once 'includes/header.php';

// Pour cette démo, on stocke les redirections dans settings
// En production, utiliser la table redirections

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $source = trim($_POST['source'] ?? '');
        $target = trim($_POST['target'] ?? '');
        $type = (int)($_POST['type'] ?? 301);

        if ($source && $target) {
            // Récupérer les redirections existantes
            $result = supabase()->select('settings', 'key=eq.redirections');
            $redirections = [];
            if ($result['success'] && !empty($result['data'])) {
                $redirections = json_decode($result['data'][0]['value'], true) ?: [];
            }

            // Ajouter la nouvelle
            $redirections[] = [
                'id' => uniqid(),
                'source' => $source,
                'target' => $target,
                'type' => $type,
                'created_at' => date('Y-m-d H:i:s')
            ];

            // Sauvegarder
            if (empty($result['data'])) {
                supabase()->insert('settings', ['key' => 'redirections', 'value' => json_encode($redirections)]);
            } else {
                supabase()->update('settings', 'key=eq.redirections', ['value' => json_encode($redirections)]);
            }

            setFlash('success', 'Redirection ajoutée');
        }
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        $result = supabase()->select('settings', 'key=eq.redirections');
        if ($result['success'] && !empty($result['data'])) {
            $redirections = json_decode($result['data'][0]['value'], true) ?: [];
            $redirections = array_filter($redirections, fn($r) => $r['id'] !== $id);
            supabase()->update('settings', 'key=eq.redirections', ['value' => json_encode(array_values($redirections))]);
            setFlash('success', 'Redirection supprimée');
        }
    }

    header('Location: redirections.php');
    exit;
}

// Récupérer les redirections
$result = supabase()->select('settings', 'key=eq.redirections');
$redirections = [];
if ($result['success'] && !empty($result['data'])) {
    $redirections = json_decode($result['data'][0]['value'], true) ?: [];
}
?>

<style>
.redir-form {
    display: grid;
    grid-template-columns: 1fr 1fr 120px 120px;
    gap: 1rem;
    align-items: end;
}
.redir-table {
    width: 100%;
    border-collapse: collapse;
}
.redir-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    background: var(--gray-50);
    border-bottom: 2px solid var(--gray-200);
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--gray-600);
}
.redir-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
}
.redir-table tr:hover {
    background: var(--gray-50);
}
.url-cell {
    font-family: monospace;
    font-size: 0.9rem;
}
.type-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}
.type-301 {
    background: #dcfce7;
    color: #166534;
}
.type-302 {
    background: #e0f2fe;
    color: #075985;
}
.arrow {
    color: var(--gray-400);
    font-size: 1.2rem;
}
</style>

<div class="page-header">
    <h2 class="page-title">Redirections</h2>
    <p class="page-subtitle">Gérez les redirections 301/302 de votre site</p>
</div>

<!-- Formulaire d'ajout -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">➕ Ajouter une redirection</h3>
    </div>
    <div class="card-body">
        <form method="POST" class="redir-form">
            <input type="hidden" name="action" value="add">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">URL source</label>
                <input type="text" name="source" class="form-control" placeholder="/ancienne-page.html" required>
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label">URL de destination</label>
                <input type="text" name="target" class="form-control" placeholder="/nouvelle-page.html" required>
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Type</label>
                <select name="type" class="form-control">
                    <option value="301">301 (Permanent)</option>
                    <option value="302">302 (Temporaire)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Ajouter</button>
        </form>
    </div>
</div>

<!-- Liste des redirections -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📋 Redirections actives (<?= count($redirections) ?>)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($redirections)): ?>
        <div style="text-align: center; padding: 3rem;">
            <p style="font-size: 2rem; margin-bottom: 0.5rem;">↩️</p>
            <p style="color: var(--gray-500);">Aucune redirection configurée</p>
        </div>
        <?php else: ?>
        <table class="redir-table">
            <thead>
                <tr>
                    <th>Source</th>
                    <th></th>
                    <th>Destination</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th style="width: 80px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($redirections as $redir): ?>
                <tr>
                    <td class="url-cell"><?= e($redir['source']) ?></td>
                    <td class="arrow">→</td>
                    <td class="url-cell"><?= e($redir['target']) ?></td>
                    <td>
                        <span class="type-badge type-<?= $redir['type'] ?>"><?= $redir['type'] ?></span>
                    </td>
                    <td style="color: var(--gray-500); font-size: 0.85rem;">
                        <?= date('d/m/Y', strtotime($redir['created_at'])) ?>
                    </td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= e($redir['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette redirection ?')">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Aide -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">💡 Guide des redirections</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <h4 style="margin-bottom: 0.5rem; color: #166534;">301 - Redirection permanente</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem; margin: 0;">
                    Utilisez ce type quand une page a définitivement changé d'URL. Le "jus SEO" est transféré à la nouvelle URL.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem; color: #075985;">302 - Redirection temporaire</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem; margin: 0;">
                    Pour une redirection provisoire (maintenance, test A/B). Le référencement reste sur l'URL d'origine.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
