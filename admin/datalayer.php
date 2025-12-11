<?php
$pageTitle = 'DataLayer & GTM';
require_once 'includes/header.php';

// Récupérer la configuration tracking
$trackingResult = supabase()->select('settings', 'key=like.datalayer_%');
$tracking = [];
if ($trackingResult['success']) {
    foreach ($trackingResult['data'] as $s) {
        $key = str_replace('datalayer_', '', $s['key']);
        $tracking[$key] = $s['value'];
    }
}

// Récupérer les événements personnalisés
$eventsResult = supabase()->select('settings', 'key=eq.datalayer_events');
$events = [];
if ($eventsResult['success'] && !empty($eventsResult['data'])) {
    $events = json_decode($eventsResult['data'][0]['value'], true) ?: [];
}

// Valeurs par défaut
$defaults = [
    'gtm_id' => '',
    'ga4_id' => '',
    'fb_pixel' => '',
    'conversion_phone' => 'phone_call',
    'conversion_form' => 'form_submit',
    'track_scroll' => '0',
    'track_clicks' => '0',
    'track_forms' => '1',
    'track_phone' => '1',
    'debug_mode' => '0'
];

foreach ($defaults as $key => $value) {
    if (!isset($tracking[$key])) {
        $tracking[$key] = $value;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_config';

    if ($action === 'save_config') {
        $fields = ['gtm_id', 'ga4_id', 'fb_pixel', 'conversion_phone', 'conversion_form',
                   'track_scroll', 'track_clicks', 'track_forms', 'track_phone', 'debug_mode'];

        foreach ($fields as $field) {
            $value = $_POST[$field] ?? '';
            if (in_array($field, ['track_scroll', 'track_clicks', 'track_forms', 'track_phone', 'debug_mode'])) {
                $value = isset($_POST[$field]) ? '1' : '0';
            }

            $dbKey = 'datalayer_' . $field;
            $existing = supabase()->select('settings', "key=eq.{$dbKey}");
            if ($existing['success'] && !empty($existing['data'])) {
                supabase()->update('settings', "key=eq.{$dbKey}", ['value' => $value]);
            } else {
                supabase()->insert('settings', ['key' => $dbKey, 'value' => $value]);
            }
        }
        setFlash('success', 'Configuration sauvegardée');
    }

    if ($action === 'add_event') {
        $eventName = trim($_POST['event_name'] ?? '');
        $eventCategory = trim($_POST['event_category'] ?? '');
        $eventTrigger = trim($_POST['event_trigger'] ?? '');
        $eventSelector = trim($_POST['event_selector'] ?? '');
        $eventParams = trim($_POST['event_params'] ?? '{}');

        if ($eventName) {
            $events[] = [
                'id' => uniqid('evt_'),
                'name' => $eventName,
                'category' => $eventCategory,
                'trigger' => $eventTrigger,
                'selector' => $eventSelector,
                'params' => $eventParams,
                'active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $existing = supabase()->select('settings', 'key=eq.datalayer_events');
            if ($existing['success'] && !empty($existing['data'])) {
                supabase()->update('settings', 'key=eq.datalayer_events', ['value' => json_encode($events, JSON_UNESCAPED_UNICODE)]);
            } else {
                supabase()->insert('settings', ['key' => 'datalayer_events', 'value' => json_encode($events, JSON_UNESCAPED_UNICODE)]);
            }
            setFlash('success', 'Événement ajouté');
        }
    }

    if ($action === 'toggle_event') {
        $id = $_POST['id'] ?? '';
        foreach ($events as &$evt) {
            if ($evt['id'] === $id) {
                $evt['active'] = !$evt['active'];
                break;
            }
        }
        supabase()->update('settings', 'key=eq.datalayer_events', ['value' => json_encode($events, JSON_UNESCAPED_UNICODE)]);
        setFlash('success', 'Événement mis à jour');
    }

    if ($action === 'delete_event') {
        $id = $_POST['id'] ?? '';
        $events = array_values(array_filter($events, fn($e) => $e['id'] !== $id));
        supabase()->update('settings', 'key=eq.datalayer_events', ['value' => json_encode($events, JSON_UNESCAPED_UNICODE)]);
        setFlash('success', 'Événement supprimé');
    }

    header('Location: datalayer.php');
    exit;
}

$triggers = [
    'click' => 'Clic sur élément',
    'submit' => 'Soumission de formulaire',
    'scroll' => 'Scroll (% de page)',
    'visibility' => 'Élément visible',
    'pageview' => 'Chargement de page',
    'timer' => 'Après X secondes'
];
?>

<style>
.tracking-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}
.tracking-section {
    background: white;
    border-radius: 12px;
    border: 1px solid var(--gray-200);
    overflow: hidden;
}
.tracking-section-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.tracking-section-body {
    padding: 1.5rem;
}
.id-input {
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 0.9rem;
}
.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--gray-100);
}
.toggle-row:last-child {
    border-bottom: none;
}
.toggle-label {
    display: flex;
    flex-direction: column;
}
.toggle-label span {
    font-weight: 500;
}
.toggle-label small {
    color: var(--gray-500);
    font-size: 0.8rem;
}
.toggle-switch {
    position: relative;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}
.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.toggle-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background-color: var(--gray-300);
    transition: .3s;
    border-radius: 24px;
}
.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}
input:checked + .toggle-slider {
    background-color: var(--primary);
}
input:checked + .toggle-slider:before {
    transform: translateX(20px);
}
.events-table {
    width: 100%;
    border-collapse: collapse;
}
.events-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    background: var(--gray-50);
    font-weight: 600;
    font-size: 0.8rem;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.events-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
    font-size: 0.9rem;
}
.events-table tr:hover {
    background: var(--gray-50);
}
.event-name {
    font-family: 'Monaco', 'Menlo', monospace;
    font-weight: 500;
    color: var(--primary);
}
.event-trigger {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.5rem;
    background: var(--gray-100);
    border-radius: 4px;
    font-size: 0.8rem;
}
.status-badge {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}
.status-badge.active {
    background: #dcfce7;
    color: #166534;
}
.status-badge.inactive {
    background: var(--gray-100);
    color: var(--gray-500);
}
.code-block {
    background: #1e1e2e;
    color: #cdd6f4;
    padding: 1rem;
    border-radius: 8px;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 0.8rem;
    overflow-x: auto;
    line-height: 1.6;
    white-space: pre;
}
.code-block .comment {
    color: #6c7086;
}
.code-block .string {
    color: #a6e3a1;
}
.code-block .key {
    color: #89b4fa;
}
.add-event-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.form-full {
    grid-column: span 2;
}
.copy-btn {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    padding: 0.35rem 0.75rem;
    background: rgba(255,255,255,0.1);
    border: none;
    border-radius: 4px;
    color: #cdd6f4;
    cursor: pointer;
    font-size: 0.75rem;
}
.copy-btn:hover {
    background: rgba(255,255,255,0.2);
}
.code-wrapper {
    position: relative;
}
</style>

<div class="page-header">
    <h2 class="page-title">DataLayer & GTM</h2>
    <p class="page-subtitle">Configurez le tracking via Google Tag Manager et le dataLayer</p>
</div>

<form method="POST">
<input type="hidden" name="action" value="save_config">

<div class="tracking-grid">
    <!-- IDs de tracking -->
    <div class="tracking-section">
        <div class="tracking-section-header">
            <svg class="icon-svg" viewBox="0 0 24 24"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
            Identifiants de tracking
        </div>
        <div class="tracking-section-body">
            <div class="form-group">
                <label class="form-label">Google Tag Manager ID</label>
                <input type="text" name="gtm_id" class="form-control id-input"
                       value="<?= e($tracking['gtm_id']) ?>" placeholder="GTM-XXXXXXX">
                <p style="font-size: 0.8rem; color: var(--gray-500); margin-top: 0.25rem;">
                    ID de votre conteneur GTM
                </p>
            </div>
            <div class="form-group">
                <label class="form-label">Google Analytics 4 ID</label>
                <input type="text" name="ga4_id" class="form-control id-input"
                       value="<?= e($tracking['ga4_id']) ?>" placeholder="G-XXXXXXXXXX">
                <p style="font-size: 0.8rem; color: var(--gray-500); margin-top: 0.25rem;">
                    Pour le tracking direct (optionnel si GTM configuré)
                </p>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Facebook Pixel ID</label>
                <input type="text" name="fb_pixel" class="form-control id-input"
                       value="<?= e($tracking['fb_pixel']) ?>" placeholder="123456789012345">
            </div>
        </div>
    </div>

    <!-- Options de tracking -->
    <div class="tracking-section">
        <div class="tracking-section-header">
            <svg class="icon-svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Tracking automatique
        </div>
        <div class="tracking-section-body">
            <div class="toggle-row">
                <div class="toggle-label">
                    <span>Clics téléphone</span>
                    <small>Événement lors d'un clic sur tel:</small>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="track_phone" value="1" <?= $tracking['track_phone'] == '1' ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="toggle-row">
                <div class="toggle-label">
                    <span>Formulaires</span>
                    <small>Événement lors d'une soumission</small>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="track_forms" value="1" <?= $tracking['track_forms'] == '1' ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="toggle-row">
                <div class="toggle-label">
                    <span>Scroll depth</span>
                    <small>25%, 50%, 75%, 100%</small>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="track_scroll" value="1" <?= $tracking['track_scroll'] == '1' ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="toggle-row">
                <div class="toggle-label">
                    <span>Clics sortants</span>
                    <small>Liens vers sites externes</small>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="track_clicks" value="1" <?= $tracking['track_clicks'] == '1' ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="toggle-row" style="border-top: 1px solid var(--gray-200); margin-top: 0.5rem; padding-top: 1rem;">
                <div class="toggle-label">
                    <span>Mode debug</span>
                    <small>Log dans la console</small>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="debug_mode" value="1" <?= $tracking['debug_mode'] == '1' ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Noms des événements -->
<div class="tracking-section" style="margin-top: 1.5rem;">
    <div class="tracking-section-header">
        <svg class="icon-svg" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Noms des événements de conversion
    </div>
    <div class="tracking-section-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Appel téléphonique</label>
                <input type="text" name="conversion_phone" class="form-control id-input"
                       value="<?= e($tracking['conversion_phone']) ?>" placeholder="phone_call">
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Soumission formulaire</label>
                <input type="text" name="conversion_form" class="form-control id-input"
                       value="<?= e($tracking['conversion_form']) ?>" placeholder="form_submit">
            </div>
        </div>
    </div>
</div>

<div style="margin-top: 1.5rem;">
    <button type="submit" class="btn btn-primary">Enregistrer la configuration</button>
</div>
</form>

<!-- Événements personnalisés -->
<div class="tracking-section" style="margin-top: 2rem;">
    <div class="tracking-section-header">
        <svg class="icon-svg" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        Événements personnalisés
        <span style="margin-left: auto; font-size: 0.85rem; font-weight: normal; color: var(--gray-500);">
            <?= count($events) ?> événement(s)
        </span>
    </div>
    <div class="tracking-section-body" style="padding: 0;">
        <?php if (empty($events)): ?>
        <div style="padding: 3rem; text-align: center; color: var(--gray-500);">
            <svg class="icon-svg" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.5;" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            <p>Aucun événement personnalisé</p>
            <p style="font-size: 0.85rem;">Créez des événements pour tracker des actions spécifiques</p>
        </div>
        <?php else: ?>
        <table class="events-table">
            <thead>
                <tr>
                    <th>Événement</th>
                    <th>Déclencheur</th>
                    <th>Sélecteur</th>
                    <th>Statut</th>
                    <th style="width: 100px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $evt): ?>
                <tr>
                    <td>
                        <span class="event-name"><?= e($evt['name']) ?></span>
                        <?php if (!empty($evt['category'])): ?>
                        <br><span style="font-size: 0.8rem; color: var(--gray-500);"><?= e($evt['category']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="event-trigger"><?= e($triggers[$evt['trigger']] ?? $evt['trigger']) ?></span>
                    </td>
                    <td style="font-family: monospace; font-size: 0.8rem; color: var(--gray-600);">
                        <?= e($evt['selector'] ?: '-') ?>
                    </td>
                    <td>
                        <span class="status-badge <?= $evt['active'] ? 'active' : 'inactive' ?>">
                            <?= $evt['active'] ? 'Actif' : 'Inactif' ?>
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_event">
                                <input type="hidden" name="id" value="<?= e($evt['id']) ?>">
                                <button type="submit" class="btn btn-sm btn-secondary">
                                    <?= $evt['active'] ? 'Off' : 'On' ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_event">
                                <input type="hidden" name="id" value="<?= e($evt['id']) ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ?')">
                                    <svg class="icon-svg" style="width: 14px; height: 14px;" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Ajouter un événement -->
<div class="tracking-section" style="margin-top: 1.5rem;">
    <div class="tracking-section-header">
        <svg class="icon-svg" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Ajouter un événement
    </div>
    <div class="tracking-section-body">
        <form method="POST" class="add-event-form">
            <input type="hidden" name="action" value="add_event">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Nom de l'événement *</label>
                <input type="text" name="event_name" class="form-control id-input" placeholder="cta_click" required>
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Catégorie</label>
                <input type="text" name="event_category" class="form-control" placeholder="engagement">
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Déclencheur *</label>
                <select name="event_trigger" class="form-control" required>
                    <?php foreach ($triggers as $key => $label): ?>
                    <option value="<?= $key ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Sélecteur CSS</label>
                <input type="text" name="event_selector" class="form-control id-input" placeholder=".btn-cta, #reservation">
            </div>
            <div class="form-group form-full" style="margin: 0;">
                <label class="form-label">Paramètres JSON (optionnel)</label>
                <input type="text" name="event_params" class="form-control id-input" placeholder='{"value": 1, "currency": "EUR"}'>
            </div>
            <div class="form-full">
                <button type="submit" class="btn btn-primary">Ajouter l'événement</button>
            </div>
        </form>
    </div>
</div>

<!-- Code à intégrer -->
<div class="tracking-section" style="margin-top: 1.5rem;">
    <div class="tracking-section-header">
        <svg class="icon-svg" viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        Code d'intégration
    </div>
    <div class="tracking-section-body">
        <p style="margin-bottom: 1rem; color: var(--gray-600);">
            Ajoutez ce code dans le <code>&lt;head&gt;</code> de votre site :
        </p>
        <div class="code-wrapper">
            <button class="copy-btn" onclick="copyCode('code-head')">Copier</button>
            <div class="code-block" id="code-head"><span class="comment">&lt;!-- DataLayer & GTM --&gt;</span>
&lt;script&gt;
window.dataLayer = window.dataLayer || [];
<?php if (!empty($tracking['gtm_id'])): ?>
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?= e($tracking['gtm_id']) ?>');
<?php endif; ?>
&lt;/script&gt;</div>
        </div>

        <?php if (!empty($tracking['gtm_id'])): ?>
        <p style="margin: 1.5rem 0 1rem; color: var(--gray-600);">
            Juste après <code>&lt;body&gt;</code> :
        </p>
        <div class="code-wrapper">
            <button class="copy-btn" onclick="copyCode('code-body')">Copier</button>
            <div class="code-block" id="code-body">&lt;noscript&gt;&lt;iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($tracking['gtm_id']) ?>"
height="0" width="0" style="display:none;visibility:hidden"&gt;&lt;/iframe&gt;&lt;/noscript&gt;</div>
        </div>
        <?php endif; ?>

        <p style="margin: 1.5rem 0 1rem; color: var(--gray-600);">
            Avant <code>&lt;/body&gt;</code> - Script de tracking automatique :
        </p>
        <div class="code-wrapper">
            <button class="copy-btn" onclick="copyCode('code-tracking')">Copier</button>
            <div class="code-block" id="code-tracking">&lt;script&gt;
(function() {
    var debug = <?= $tracking['debug_mode'] == '1' ? 'true' : 'false' ?>;
    function push(event, data) {
        data = data || {};
        data.event = event;
        window.dataLayer.push(data);
        if (debug) console.log('dataLayer:', event, data);
    }
<?php if ($tracking['track_phone'] == '1'): ?>

    // Track phone clicks
    document.querySelectorAll('a[href^="tel:"]').forEach(function(el) {
        el.addEventListener('click', function() {
            push('<?= e($tracking['conversion_phone']) ?>', {
                phone_number: this.href.replace('tel:', '')
            });
        });
    });
<?php endif; ?>
<?php if ($tracking['track_forms'] == '1'): ?>

    // Track form submissions
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            push('<?= e($tracking['conversion_form']) ?>', {
                form_id: this.id || 'unknown',
                form_name: this.getAttribute('name') || ''
            });
        });
    });
<?php endif; ?>
<?php if ($tracking['track_scroll'] == '1'): ?>

    // Track scroll depth
    var scrollMarks = {25: false, 50: false, 75: false, 100: false};
    window.addEventListener('scroll', function() {
        var percent = Math.round((window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100);
        [25, 50, 75, 100].forEach(function(mark) {
            if (percent >= mark && !scrollMarks[mark]) {
                scrollMarks[mark] = true;
                push('scroll_depth', {percent: mark});
            }
        });
    });
<?php endif; ?>
<?php if ($tracking['track_clicks'] == '1'): ?>

    // Track outbound clicks
    document.querySelectorAll('a[href^="http"]').forEach(function(el) {
        if (el.hostname !== window.location.hostname) {
            el.addEventListener('click', function() {
                push('outbound_click', {url: this.href});
            });
        }
    });
<?php endif; ?>
<?php foreach ($events as $evt): ?>
<?php if ($evt['active'] && !empty($evt['selector'])): ?>

    // <?= e($evt['name']) ?>
    document.querySelectorAll('<?= e($evt['selector']) ?>').forEach(function(el) {
        el.addEventListener('<?= $evt['trigger'] === 'submit' ? 'submit' : 'click' ?>', function() {
            push('<?= e($evt['name']) ?>'<?php if (!empty($evt['params']) && $evt['params'] !== '{}'): ?>, <?= $evt['params'] ?><?php endif; ?>);
        });
    });
<?php endif; ?>
<?php endforeach; ?>
})();
&lt;/script&gt;</div>
        </div>
    </div>
</div>

<script>
function copyCode(id) {
    var code = document.getElementById(id).innerText;
    navigator.clipboard.writeText(code).then(function() {
        alert('Code copié !');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
