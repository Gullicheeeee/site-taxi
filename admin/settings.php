<?php
$pageTitle = 'Réglages';
require_once 'includes/header.php';

// Récupérer tous les paramètres
$result = supabase()->select('settings');
$settingsData = [];
if ($result['success']) {
    foreach ($result['data'] as $setting) {
        $settingsData[$setting['key']] = $setting['value'];
    }
}

// Valeurs par défaut
$defaults = [
    'site_name' => 'Taxi Julien',
    'site_tagline' => 'Votre taxi de confiance',
    'site_url' => 'https://votresite.fr',
    'contact_email' => '',
    'contact_phone' => '',
    'contact_address' => '',
    'whatsapp' => '',
    'facebook_url' => '',
    'instagram_url' => '',
    'twitter_url' => '',
    'linkedin_url' => '',
    'google_analytics_id' => '',
    'google_tag_manager_id' => '',
    'facebook_pixel_id' => '',
    'header_scripts' => '',
    'footer_scripts' => '',
    'posts_per_page' => '10',
    'date_format' => 'd/m/Y',
    'maintenance_mode' => '0'
];

foreach ($defaults as $key => $value) {
    if (!isset($settingsData[$key])) {
        $settingsData[$key] = $value;
    }
}

// Traitement du formulaire principal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password'])) {
    $section = $_POST['section'] ?? 'all';

    $settings = [
        'site_name' => trim($_POST['site_name'] ?? $settingsData['site_name']),
        'site_tagline' => trim($_POST['site_tagline'] ?? $settingsData['site_tagline']),
        'site_url' => trim($_POST['site_url'] ?? $settingsData['site_url']),
        'contact_phone' => trim($_POST['contact_phone'] ?? $settingsData['contact_phone']),
        'contact_email' => trim($_POST['contact_email'] ?? $settingsData['contact_email']),
        'contact_address' => trim($_POST['contact_address'] ?? $settingsData['contact_address']),
        'whatsapp' => trim($_POST['whatsapp'] ?? $settingsData['whatsapp']),
        'facebook_url' => trim($_POST['facebook_url'] ?? $settingsData['facebook_url']),
        'instagram_url' => trim($_POST['instagram_url'] ?? $settingsData['instagram_url']),
        'twitter_url' => trim($_POST['twitter_url'] ?? $settingsData['twitter_url']),
        'linkedin_url' => trim($_POST['linkedin_url'] ?? $settingsData['linkedin_url']),
        'google_analytics_id' => trim($_POST['google_analytics_id'] ?? $settingsData['google_analytics_id']),
        'google_tag_manager_id' => trim($_POST['google_tag_manager_id'] ?? $settingsData['google_tag_manager_id']),
        'facebook_pixel_id' => trim($_POST['facebook_pixel_id'] ?? $settingsData['facebook_pixel_id']),
        'header_scripts' => $_POST['header_scripts'] ?? $settingsData['header_scripts'],
        'footer_scripts' => $_POST['footer_scripts'] ?? $settingsData['footer_scripts'],
        'posts_per_page' => $_POST['posts_per_page'] ?? $settingsData['posts_per_page'],
        'date_format' => $_POST['date_format'] ?? $settingsData['date_format'],
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0'
    ];

    $success = true;
    foreach ($settings as $key => $value) {
        $result = setSetting($key, $value);
        if (!$result['success']) {
            $success = false;
        }
    }

    if ($success) {
        setFlash('success', 'Paramètres enregistrés avec succès');
    } else {
        setFlash('danger', 'Erreur lors de l\'enregistrement');
    }

    header('Location: settings.php');
    exit;
}

// Changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $result = supabase()->select('admins', 'id=eq.' . urlencode($_SESSION['admin_id']));

    if ($result['success'] && !empty($result['data'])) {
        $admin = $result['data'][0];

        if (!password_verify($current, $admin['password_hash'])) {
            setFlash('danger', 'Mot de passe actuel incorrect');
        } elseif ($new !== $confirm) {
            setFlash('danger', 'Les mots de passe ne correspondent pas');
        } elseif (strlen($new) < 6) {
            setFlash('danger', 'Le mot de passe doit contenir au moins 6 caractères');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            supabase()->update('admins', 'id=eq.' . urlencode($_SESSION['admin_id']), [
                'password_hash' => $hash
            ]);
            setFlash('success', 'Mot de passe modifié !');
        }
    } else {
        setFlash('danger', 'Erreur lors de la récupération de l\'utilisateur');
    }

    header('Location: settings.php#security');
    exit;
}
?>

<style>
.settings-layout {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 1.5rem;
}
.settings-nav {
    background: white;
    border-radius: 12px;
    border: 1px solid var(--gray-200);
    overflow: hidden;
    position: sticky;
    top: 1rem;
    height: fit-content;
}
.settings-nav-item {
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
.settings-nav-item:hover {
    background: var(--gray-50);
}
.settings-nav-item.active {
    background: var(--primary);
    color: white;
}
.settings-nav-item:last-child {
    border-bottom: none;
}
.settings-icon {
    font-size: 1.2rem;
}
.settings-section {
    background: white;
    border-radius: 12px;
    border: 1px solid var(--gray-200);
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.settings-section-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
}
.settings-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.settings-section-body {
    padding: 1.5rem;
}
.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}
.settings-full {
    grid-column: span 2;
}
.form-help {
    font-size: 0.8rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
}
.maintenance-toggle {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: 8px;
    border: 1px solid var(--gray-200);
}
.toggle-switch {
    position: relative;
    width: 50px;
    height: 26px;
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
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--gray-300);
    transition: .4s;
    border-radius: 26px;
}
.toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}
input:checked + .toggle-slider {
    background-color: var(--danger);
}
input:checked + .toggle-slider:before {
    transform: translateX(24px);
}
.code-preview {
    background: #1a1a2e;
    color: #e0e0e0;
    padding: 1rem;
    border-radius: 8px;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 0.8rem;
    overflow-x: auto;
    margin-top: 0.5rem;
}
.social-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
@media (max-width: 900px) {
    .settings-layout {
        grid-template-columns: 1fr;
    }
    .settings-nav {
        display: flex;
        overflow-x: auto;
        position: static;
    }
    .settings-nav-item {
        flex-shrink: 0;
        border-bottom: none;
        border-right: 1px solid var(--gray-100);
    }
    .settings-grid {
        grid-template-columns: 1fr;
    }
    .settings-full {
        grid-column: span 1;
    }
}
</style>

<div class="page-header">
    <h2 class="page-title">Réglages</h2>
    <p class="page-subtitle">Configurez les paramètres de votre site</p>
</div>

<form method="POST">
<div class="settings-layout">
    <!-- Navigation -->
    <nav class="settings-nav">
        <a href="#general" class="settings-nav-item active" onclick="showSection('general', this)">
            <span class="settings-icon">⚙️</span>
            <span>Général</span>
        </a>
        <a href="#contact" class="settings-nav-item" onclick="showSection('contact', this)">
            <span class="settings-icon">📞</span>
            <span>Contact</span>
        </a>
        <a href="#social" class="settings-nav-item" onclick="showSection('social', this)">
            <span class="settings-icon">🌐</span>
            <span>Réseaux sociaux</span>
        </a>
        <a href="#tracking" class="settings-nav-item" onclick="showSection('tracking', this)">
            <span class="settings-icon">📊</span>
            <span>Analytics</span>
        </a>
        <a href="#scripts" class="settings-nav-item" onclick="showSection('scripts', this)">
            <span class="settings-icon">💻</span>
            <span>Scripts</span>
        </a>
        <a href="#security" class="settings-nav-item" onclick="showSection('security', this)">
            <span class="settings-icon">🔐</span>
            <span>Sécurité</span>
        </a>
    </nav>

    <!-- Sections -->
    <div class="settings-sections">
        <!-- Général -->
        <div id="section-general" class="settings-section">
            <div class="settings-section-header">
                <h3 class="settings-section-title">⚙️ Réglages généraux</h3>
            </div>
            <div class="settings-section-body">
                <div class="settings-grid">
                    <div class="form-group">
                        <label class="form-label">Nom du site</label>
                        <input type="text" name="site_name" class="form-control" value="<?= e($settingsData['site_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Slogan</label>
                        <input type="text" name="site_tagline" class="form-control" value="<?= e($settingsData['site_tagline']) ?>">
                    </div>
                    <div class="form-group settings-full">
                        <label class="form-label">URL du site</label>
                        <input type="url" name="site_url" class="form-control" value="<?= e($settingsData['site_url']) ?>" placeholder="https://votresite.fr">
                        <p class="form-help">URL complète sans slash final. Utilisée pour le sitemap et les liens absolus.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Articles par page</label>
                        <select name="posts_per_page" class="form-control">
                            <?php foreach ([5, 10, 15, 20, 25] as $n): ?>
                            <option value="<?= $n ?>" <?= $settingsData['posts_per_page'] == $n ? 'selected' : '' ?>><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Format de date</label>
                        <select name="date_format" class="form-control">
                            <option value="d/m/Y" <?= $settingsData['date_format'] === 'd/m/Y' ? 'selected' : '' ?>>31/12/2024</option>
                            <option value="d M Y" <?= $settingsData['date_format'] === 'd M Y' ? 'selected' : '' ?>>31 Dec 2024</option>
                            <option value="Y-m-d" <?= $settingsData['date_format'] === 'Y-m-d' ? 'selected' : '' ?>>2024-12-31</option>
                        </select>
                    </div>
                    <div class="form-group settings-full">
                        <div class="maintenance-toggle">
                            <label class="toggle-switch">
                                <input type="checkbox" name="maintenance_mode" value="1" <?= $settingsData['maintenance_mode'] == '1' ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <div>
                                <strong>Mode maintenance</strong>
                                <p class="form-help" style="margin: 0;">Active une page de maintenance pour les visiteurs. Vous serez toujours connecté.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact -->
        <div id="section-contact" class="settings-section" style="display: none;">
            <div class="settings-section-header">
                <h3 class="settings-section-title">📞 Informations de contact</h3>
            </div>
            <div class="settings-section-body">
                <div class="settings-grid">
                    <div class="form-group">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="contact_phone" class="form-control" value="<?= e($settingsData['contact_phone']) ?>" placeholder="06 XX XX XX XX">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="contact_email" class="form-control" value="<?= e($settingsData['contact_email']) ?>" placeholder="contact@taxijulien.fr">
                    </div>
                    <div class="form-group">
                        <label class="form-label">WhatsApp</label>
                        <input type="text" name="whatsapp" class="form-control" value="<?= e($settingsData['whatsapp']) ?>" placeholder="33612345678 (sans le +)">
                        <p class="form-help">Numéro au format international sans le +</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Adresse</label>
                        <textarea name="contact_address" class="form-control" rows="2" placeholder="123 Rue Exemple, 06000 Nice"><?= e($settingsData['contact_address']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Réseaux sociaux -->
        <div id="section-social" class="settings-section" style="display: none;">
            <div class="settings-section-header">
                <h3 class="settings-section-title">🌐 Réseaux sociaux</h3>
            </div>
            <div class="settings-section-body">
                <div class="social-grid">
                    <div class="form-group">
                        <label class="form-label">📘 Facebook</label>
                        <input type="url" name="facebook_url" class="form-control" value="<?= e($settingsData['facebook_url']) ?>" placeholder="https://facebook.com/votrepage">
                    </div>
                    <div class="form-group">
                        <label class="form-label">📸 Instagram</label>
                        <input type="url" name="instagram_url" class="form-control" value="<?= e($settingsData['instagram_url']) ?>" placeholder="https://instagram.com/votrecompte">
                    </div>
                    <div class="form-group">
                        <label class="form-label">🐦 Twitter / X</label>
                        <input type="url" name="twitter_url" class="form-control" value="<?= e($settingsData['twitter_url']) ?>" placeholder="https://twitter.com/votrecompte">
                    </div>
                    <div class="form-group">
                        <label class="form-label">💼 LinkedIn</label>
                        <input type="url" name="linkedin_url" class="form-control" value="<?= e($settingsData['linkedin_url']) ?>" placeholder="https://linkedin.com/company/votreentreprise">
                    </div>
                </div>
            </div>
        </div>

        <!-- Tracking -->
        <div id="section-tracking" class="settings-section" style="display: none;">
            <div class="settings-section-header">
                <h3 class="settings-section-title">📊 Analytics & Tracking</h3>
            </div>
            <div class="settings-section-body">
                <div class="form-group">
                    <label class="form-label">Google Analytics ID</label>
                    <input type="text" name="google_analytics_id" class="form-control" value="<?= e($settingsData['google_analytics_id']) ?>" placeholder="G-XXXXXXXXXX">
                    <p class="form-help">ID de mesure Google Analytics 4 (format G-XXXXXXX)</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Google Tag Manager ID</label>
                    <input type="text" name="google_tag_manager_id" class="form-control" value="<?= e($settingsData['google_tag_manager_id']) ?>" placeholder="GTM-XXXXXXX">
                    <p class="form-help">Optionnel - Si vous utilisez GTM pour gérer vos tags</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Facebook Pixel ID</label>
                    <input type="text" name="facebook_pixel_id" class="form-control" value="<?= e($settingsData['facebook_pixel_id']) ?>" placeholder="123456789012345">
                    <p class="form-help">Pour le suivi des conversions Facebook Ads</p>
                </div>

                <?php if (!empty($settingsData['google_analytics_id'])): ?>
                <div style="margin-top: 1.5rem; padding: 1rem; background: var(--gray-50); border-radius: 8px;">
                    <h4 style="margin-bottom: 0.5rem;">Code Google Analytics</h4>
                    <p class="form-help">Ajoutez ce code dans le &lt;head&gt; de chaque page HTML :</p>
                    <div class="code-preview">&lt;script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($settingsData['google_analytics_id']) ?>"&gt;&lt;/script&gt;
&lt;script&gt;
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= e($settingsData['google_analytics_id']) ?>');
&lt;/script&gt;</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Scripts -->
        <div id="section-scripts" class="settings-section" style="display: none;">
            <div class="settings-section-header">
                <h3 class="settings-section-title">💻 Scripts personnalisés</h3>
            </div>
            <div class="settings-section-body">
                <div class="alert alert-warning" style="margin-bottom: 1.5rem;">
                    <strong>Attention :</strong> Les scripts personnalisés peuvent affecter le fonctionnement de votre site. Testez après chaque modification.
                </div>
                <div class="form-group">
                    <label class="form-label">Scripts Header (avant &lt;/head&gt;)</label>
                    <textarea name="header_scripts" class="form-control" rows="6" placeholder="<!-- CSS, polices, meta tags... -->"><?= e($settingsData['header_scripts']) ?></textarea>
                    <p class="form-help">Scripts CSS, polices, meta tags supplémentaires...</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Scripts Footer (avant &lt;/body&gt;)</label>
                    <textarea name="footer_scripts" class="form-control" rows="6" placeholder="<!-- Chat widgets, scripts de conversion... -->"><?= e($settingsData['footer_scripts']) ?></textarea>
                    <p class="form-help">Chat widgets, scripts de conversion, etc.</p>
                </div>
            </div>
        </div>

        <!-- Sécurité -->
        <div id="section-security" class="settings-section" style="display: none;">
            <div class="settings-section-header">
                <h3 class="settings-section-title">🔐 Sécurité</h3>
            </div>
            <div class="settings-section-body">
                <h4 style="margin-bottom: 1rem;">Changer le mot de passe</h4>
                </form><!-- Fermer le formulaire principal -->
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    <div class="settings-grid">
                        <div class="form-group">
                            <label class="form-label">Mot de passe actuel</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="form-group settings-full">
                            <label class="form-label">Confirmer le mot de passe</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-secondary">Changer le mot de passe</button>
                </form>
                <form method="POST"><!-- Rouvrir le formulaire principal pour le JS -->
            </div>
        </div>

        <!-- Bouton de sauvegarde -->
        <div id="save-btn-container" style="display: block;">
            <button type="submit" class="btn btn-primary btn-lg">
                Enregistrer les paramètres
            </button>
        </div>
    </div>
</div>
</form>

<script>
function showSection(section, el) {
    // Cacher toutes les sections
    document.querySelectorAll('.settings-section').forEach(s => s.style.display = 'none');
    // Afficher la section demandée
    document.getElementById('section-' + section).style.display = 'block';

    // Mettre à jour la navigation
    document.querySelectorAll('.settings-nav-item').forEach(item => {
        item.classList.remove('active');
    });
    if (el) el.classList.add('active');

    // Masquer le bouton de sauvegarde pour la section sécurité
    document.getElementById('save-btn-container').style.display = section === 'security' ? 'none' : 'block';
}

// Gérer le hash dans l'URL
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash) {
        const section = window.location.hash.substring(1);
        const navItem = document.querySelector(`a[href="#${section}"]`);
        if (navItem) showSection(section, navItem);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
