<?php
$pageTitle = 'Configuration Simulateur';
require_once 'includes/header.php';

// Recuperer les parametres actuels
$result = supabase()->select('settings');
$settingsData = [];
if ($result['success']) {
    foreach ($result['data'] as $setting) {
        $settingsData[$setting['key']] = $setting['value'];
    }
}

// Valeurs par defaut simulateur
$defaults = [
    'google_maps_api_key' => '',
    'sim_base_price' => '3.50',
    'sim_price_per_km' => '1.20',
    'sim_price_per_min' => '0.35',
    'sim_min_price' => '8.00',
    'sim_night_surcharge' => '1.5',
    'sim_sunday_surcharge' => '1.2',
    'sim_luggage_price' => '2.00',
    'sim_animal_price' => '3.00',
    'sim_booking_fee' => '4.00',
    'sim_destinations' => '[]'
];

foreach ($defaults as $key => $value) {
    if (!isset($settingsData[$key])) {
        $settingsData[$key] = $value;
    }
}

// Decoder les destinations
$destinations = json_decode($settingsData['sim_destinations'], true) ?: [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'save_api') {
        setSetting('google_maps_api_key', trim($_POST['google_maps_api_key'] ?? ''));
        setFlash('success', 'Cle API Google Maps enregistree');
    }

    if ($action === 'save_tarifs') {
        $tarifs = [
            'sim_base_price' => trim($_POST['sim_base_price'] ?? $defaults['sim_base_price']),
            'sim_price_per_km' => trim($_POST['sim_price_per_km'] ?? $defaults['sim_price_per_km']),
            'sim_price_per_min' => trim($_POST['sim_price_per_min'] ?? $defaults['sim_price_per_min']),
            'sim_min_price' => trim($_POST['sim_min_price'] ?? $defaults['sim_min_price']),
            'sim_night_surcharge' => trim($_POST['sim_night_surcharge'] ?? $defaults['sim_night_surcharge']),
            'sim_sunday_surcharge' => trim($_POST['sim_sunday_surcharge'] ?? $defaults['sim_sunday_surcharge']),
            'sim_luggage_price' => trim($_POST['sim_luggage_price'] ?? $defaults['sim_luggage_price']),
            'sim_animal_price' => trim($_POST['sim_animal_price'] ?? $defaults['sim_animal_price']),
            'sim_booking_fee' => trim($_POST['sim_booking_fee'] ?? $defaults['sim_booking_fee'])
        ];

        foreach ($tarifs as $key => $value) {
            setSetting($key, $value);
        }
        setFlash('success', 'Tarifs enregistres');
    }

    if ($action === 'save_destinations') {
        $newDestinations = [];
        $count = (int)($_POST['dest_count'] ?? 0);

        for ($i = 0; $i < $count; $i++) {
            $name = trim($_POST['dest_name_' . $i] ?? '');
            $price = trim($_POST['dest_price_' . $i] ?? '');

            if (!empty($name) && !empty($price)) {
                $newDestinations[] = [
                    'name' => $name,
                    'price' => (float)$price,
                    'address' => trim($_POST['dest_address_' . $i] ?? '')
                ];
            }
        }

        setSetting('sim_destinations', json_encode($newDestinations));
        setFlash('success', 'Destinations enregistrees');
    }

    if ($action === 'add_destination') {
        $destinations[] = [
            'name' => trim($_POST['new_dest_name'] ?? 'Nouvelle destination'),
            'price' => (float)($_POST['new_dest_price'] ?? 0),
            'address' => trim($_POST['new_dest_address'] ?? '')
        ];
        setSetting('sim_destinations', json_encode($destinations));
        setFlash('success', 'Destination ajoutee');
    }

    header('Location: simulateur-config.php');
    exit;
}
?>

<style>
.config-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 1.5rem;
}
.config-nav {
    background: white;
    border-radius: 12px;
    border: 1px solid var(--gray-200);
    overflow: hidden;
    position: sticky;
    top: 1rem;
    height: fit-content;
}
.config-nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    color: inherit;
}
.config-nav-item:hover { background: var(--gray-50); }
.config-nav-item.active { background: var(--primary); color: white; }
.config-nav-item:last-child { border-bottom: none; }
.config-section {
    background: white;
    border-radius: 12px;
    border: 1px solid var(--gray-200);
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.config-section-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
}
.config-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.config-section-body { padding: 1.5rem; }
.tarif-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}
.tarif-card {
    background: var(--gray-50);
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
}
.tarif-card label {
    display: block;
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
}
.tarif-input {
    width: 100%;
    padding: 0.75rem;
    font-size: 1.25rem;
    font-weight: 600;
    text-align: center;
    border: 2px solid var(--gray-200);
    border-radius: 8px;
}
.tarif-input:focus {
    border-color: var(--primary);
    outline: none;
}
.tarif-unit {
    font-size: 0.8rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
}
.destination-card {
    background: var(--gray-50);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    display: grid;
    grid-template-columns: 1fr 1fr 120px 50px;
    gap: 1rem;
    align-items: end;
}
.api-key-input {
    font-family: monospace;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
}
.api-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.9rem;
    margin-top: 1rem;
}
.api-status.configured { background: #dcfce7; color: #166534; }
.api-status.missing { background: #fef3c7; color: #92400e; }
.help-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}
.help-box h4 {
    color: #1e40af;
    margin: 0 0 0.5rem;
    font-size: 0.9rem;
}
.help-box p, .help-box ol {
    color: #1e40af;
    font-size: 0.85rem;
    margin: 0;
}
.help-box ol { padding-left: 1.25rem; }
@media (max-width: 900px) {
    .config-layout { grid-template-columns: 1fr; }
    .config-nav { display: flex; overflow-x: auto; position: static; }
    .config-nav-item { flex-shrink: 0; border-bottom: none; border-right: 1px solid var(--gray-100); }
    .tarif-grid { grid-template-columns: repeat(2, 1fr); }
    .destination-card { grid-template-columns: 1fr; }
}
</style>

<div class="page-header">
    <h2 class="page-title">Configuration du Simulateur</h2>
    <p class="page-subtitle">Gerez les tarifs et l'API Google Maps pour le calcul des trajets</p>
</div>

<div class="config-layout">
    <!-- Navigation -->
    <nav class="config-nav">
        <a href="#api" class="config-nav-item active" onclick="showSection('api', this)">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
            <span>API Google Maps</span>
        </a>
        <a href="#tarifs" class="config-nav-item" onclick="showSection('tarifs', this)">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            <span>Tarifs de base</span>
        </a>
        <a href="#supplements" class="config-nav-item" onclick="showSection('supplements', this)">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            <span>Supplements</span>
        </a>
        <a href="#destinations" class="config-nav-item" onclick="showSection('destinations', this)">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span>Prix fixes</span>
        </a>
    </nav>

    <!-- Sections -->
    <div class="config-sections">

        <!-- API Google Maps -->
        <div id="section-api" class="config-section">
            <div class="config-section-header">
                <h3 class="config-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    API Google Maps
                </h3>
            </div>
            <div class="config-section-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_api">

                    <div class="form-group">
                        <label class="form-label">Cle API Google Maps</label>
                        <input type="text" name="google_maps_api_key" class="form-control api-key-input"
                               value="<?= e($settingsData['google_maps_api_key']) ?>"
                               placeholder="AIzaSy...">
                        <p class="form-help">Necessaire pour le calcul automatique des distances et l'autocompletion des adresses</p>
                    </div>

                    <?php if (!empty($settingsData['google_maps_api_key'])): ?>
                    <div class="api-status configured">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        API configuree
                    </div>
                    <?php else: ?>
                    <div class="api-status missing">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        API non configuree - Le simulateur utilisera des estimations
                    </div>
                    <?php endif; ?>

                    <div class="help-box">
                        <h4>Comment obtenir une cle API Google Maps ?</h4>
                        <ol>
                            <li>Connectez-vous a <a href="https://console.cloud.google.com" target="_blank">Google Cloud Console</a></li>
                            <li>Creez un nouveau projet ou selectionnez un projet existant</li>
                            <li>Activez les APIs : <strong>Maps JavaScript API</strong>, <strong>Places API</strong>, <strong>Directions API</strong></li>
                            <li>Creez une cle API dans "Identifiants"</li>
                            <li>Restreignez la cle a votre domaine pour la securite</li>
                        </ol>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem;">
                        Enregistrer la cle API
                    </button>
                </form>
            </div>
        </div>

        <!-- Tarifs de base -->
        <div id="section-tarifs" class="config-section" style="display: none;">
            <div class="config-section-header">
                <h3 class="config-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    Tarifs de base
                </h3>
            </div>
            <div class="config-section-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_tarifs">

                    <div class="tarif-grid">
                        <div class="tarif-card">
                            <label>Prise en charge</label>
                            <input type="number" step="0.01" name="sim_base_price" class="tarif-input"
                                   value="<?= e($settingsData['sim_base_price']) ?>">
                            <div class="tarif-unit">EUR</div>
                        </div>
                        <div class="tarif-card">
                            <label>Prix au kilometre</label>
                            <input type="number" step="0.01" name="sim_price_per_km" class="tarif-input"
                                   value="<?= e($settingsData['sim_price_per_km']) ?>">
                            <div class="tarif-unit">EUR / km</div>
                        </div>
                        <div class="tarif-card">
                            <label>Prix a la minute</label>
                            <input type="number" step="0.01" name="sim_price_per_min" class="tarif-input"
                                   value="<?= e($settingsData['sim_price_per_min']) ?>">
                            <div class="tarif-unit">EUR / min</div>
                        </div>
                        <div class="tarif-card">
                            <label>Course minimum</label>
                            <input type="number" step="0.01" name="sim_min_price" class="tarif-input"
                                   value="<?= e($settingsData['sim_min_price']) ?>">
                            <div class="tarif-unit">EUR</div>
                        </div>
                        <div class="tarif-card">
                            <label>Frais de reservation</label>
                            <input type="number" step="0.01" name="sim_booking_fee" class="tarif-input"
                                   value="<?= e($settingsData['sim_booking_fee']) ?>">
                            <div class="tarif-unit">EUR</div>
                        </div>
                    </div>

                    <div class="help-box" style="margin-top: 1.5rem;">
                        <h4>Formule de calcul</h4>
                        <p>Prix = Prise en charge + (Distance x Prix/km) + (Duree x Prix/min) + Supplements</p>
                        <p style="margin-top: 0.5rem;">Le prix minimum sera applique si le calcul est inferieur.</p>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem;">
                        Enregistrer les tarifs
                    </button>
                </form>
            </div>
        </div>

        <!-- Supplements -->
        <div id="section-supplements" class="config-section" style="display: none;">
            <div class="config-section-header">
                <h3 class="config-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    Supplements
                </h3>
            </div>
            <div class="config-section-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_tarifs">

                    <div class="tarif-grid">
                        <div class="tarif-card">
                            <label>Majoration nuit (19h-7h)</label>
                            <input type="number" step="0.1" name="sim_night_surcharge" class="tarif-input"
                                   value="<?= e($settingsData['sim_night_surcharge']) ?>">
                            <div class="tarif-unit">Multiplicateur (ex: 1.5 = +50%)</div>
                        </div>
                        <div class="tarif-card">
                            <label>Majoration dimanche/ferie</label>
                            <input type="number" step="0.1" name="sim_sunday_surcharge" class="tarif-input"
                                   value="<?= e($settingsData['sim_sunday_surcharge']) ?>">
                            <div class="tarif-unit">Multiplicateur</div>
                        </div>
                        <div class="tarif-card">
                            <label>Supplement bagage</label>
                            <input type="number" step="0.5" name="sim_luggage_price" class="tarif-input"
                                   value="<?= e($settingsData['sim_luggage_price']) ?>">
                            <div class="tarif-unit">EUR / bagage volumineux</div>
                        </div>
                        <div class="tarif-card">
                            <label>Supplement animal</label>
                            <input type="number" step="0.5" name="sim_animal_price" class="tarif-input"
                                   value="<?= e($settingsData['sim_animal_price']) ?>">
                            <div class="tarif-unit">EUR</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem;">
                        Enregistrer les supplements
                    </button>
                </form>
            </div>
        </div>

        <!-- Destinations a prix fixe -->
        <div id="section-destinations" class="config-section" style="display: none;">
            <div class="config-section-header">
                <h3 class="config-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Destinations a prix fixe
                </h3>
            </div>
            <div class="config-section-body">
                <p style="color: var(--gray-600); margin-bottom: 1.5rem;">
                    Definissez des prix forfaitaires pour les destinations frequentes (aeroports, gares, etc.)
                </p>

                <form method="POST" id="destinations-form">
                    <input type="hidden" name="action" value="save_destinations">
                    <input type="hidden" name="dest_count" id="dest_count" value="<?= count($destinations) ?>">

                    <div id="destinations-list">
                        <?php foreach ($destinations as $i => $dest): ?>
                        <div class="destination-card">
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Nom</label>
                                <input type="text" name="dest_name_<?= $i ?>" class="form-control"
                                       value="<?= e($dest['name']) ?>" placeholder="Ex: Aeroport Marseille">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Adresse (optionnel)</label>
                                <input type="text" name="dest_address_<?= $i ?>" class="form-control"
                                       value="<?= e($dest['address'] ?? '') ?>" placeholder="Adresse complete">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Prix (EUR)</label>
                                <input type="number" step="1" name="dest_price_<?= $i ?>" class="form-control"
                                       value="<?= e($dest['price']) ?>" style="font-weight: 600;">
                            </div>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeDestination(this)" style="margin-bottom: 0.5rem;">
                                X
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($destinations)): ?>
                    <div class="alert alert-info" id="no-dest-msg">
                        Aucune destination configuree. Ajoutez vos premieres destinations ci-dessous.
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                        Enregistrer les destinations
                    </button>
                </form>

                <!-- Ajouter une destination -->
                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-200);">
                    <h4 style="margin-bottom: 1rem;">Ajouter une destination</h4>
                    <form method="POST" class="destination-card" style="margin: 0; background: #eff6ff; border: 2px dashed var(--primary);">
                        <input type="hidden" name="action" value="add_destination">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">Nom</label>
                            <input type="text" name="new_dest_name" class="form-control" placeholder="Ex: Gare TGV Aix" required>
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">Adresse (optionnel)</label>
                            <input type="text" name="new_dest_address" class="form-control" placeholder="Adresse complete">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">Prix (EUR)</label>
                            <input type="number" step="1" name="new_dest_price" class="form-control" placeholder="80" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm" style="margin-bottom: 0.5rem;">
                            +
                        </button>
                    </form>
                </div>

                <div class="help-box" style="margin-top: 1.5rem;">
                    <h4>Destinations suggerees</h4>
                    <p>Aeroport Marseille Provence, Gare TGV Aix-en-Provence, Gare Saint-Charles Marseille, Aeroport Nice Cote d'Azur, Aeroport Montpellier</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function showSection(section, el) {
    document.querySelectorAll('.config-section').forEach(s => s.style.display = 'none');
    document.getElementById('section-' + section).style.display = 'block';
    document.querySelectorAll('.config-nav-item').forEach(item => item.classList.remove('active'));
    if (el) el.classList.add('active');
}

function removeDestination(btn) {
    btn.closest('.destination-card').remove();
    updateDestCount();
    document.getElementById('no-dest-msg')?.remove();
}

function updateDestCount() {
    const cards = document.querySelectorAll('#destinations-list .destination-card');
    document.getElementById('dest_count').value = cards.length;

    // Renumber inputs
    cards.forEach((card, i) => {
        card.querySelectorAll('input[name^="dest_"]').forEach(input => {
            const baseName = input.name.replace(/_\d+$/, '');
            input.name = baseName + '_' + i;
        });
    });
}

// Handle hash navigation
if (window.location.hash) {
    const section = window.location.hash.substring(1);
    const navItem = document.querySelector(`a[href="#${section}"]`);
    if (navItem) showSection(section, navItem);
}
</script>

<?php require_once 'includes/footer.php'; ?>
