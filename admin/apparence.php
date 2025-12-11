<?php
$pageTitle = 'Personnalisation';
require_once 'includes/header.php';

// Récupérer les paramètres d'apparence
$result = supabase()->select('settings', 'key=like.theme_%');
$theme = [];
if ($result['success']) {
    foreach ($result['data'] as $s) {
        $key = str_replace('theme_', '', $s['key']);
        $theme[$key] = $s['value'];
    }
}

// Valeurs par défaut
$defaults = [
    'primary_color' => '#3b82f6',
    'secondary_color' => '#1e40af',
    'accent_color' => '#f59e0b',
    'text_color' => '#1f2937',
    'bg_color' => '#ffffff',
    'font_family' => 'Inter',
    'font_size' => '16',
    'header_style' => 'transparent',
    'header_bg' => '#ffffff',
    'footer_bg' => '#1f2937',
    'footer_text' => '#ffffff',
    'logo_url' => '',
    'favicon_url' => '',
    'cta_text' => 'Réserver maintenant',
    'cta_phone' => '',
    'show_social_header' => '0',
    'show_social_footer' => '1'
];

foreach ($defaults as $key => $value) {
    if (!isset($theme[$key])) {
        $theme[$key] = $value;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($defaults as $key => $default) {
        $value = $_POST[$key] ?? $default;
        if ($key === 'show_social_header' || $key === 'show_social_footer') {
            $value = isset($_POST[$key]) ? '1' : '0';
        }

        $dbKey = 'theme_' . $key;
        $existing = supabase()->select('settings', "key=eq.{$dbKey}");
        if ($existing['success'] && !empty($existing['data'])) {
            supabase()->update('settings', "key=eq.{$dbKey}", ['value' => $value]);
        } else {
            supabase()->insert('settings', ['key' => $dbKey, 'value' => $value]);
        }
    }

    setFlash('success', 'Personnalisation enregistrée');
    header('Location: apparence.php');
    exit;
}

$fonts = [
    'Inter' => 'Inter (Moderne)',
    'Roboto' => 'Roboto (Classique)',
    'Open Sans' => 'Open Sans (Lisible)',
    'Lato' => 'Lato (Élégant)',
    'Montserrat' => 'Montserrat (Dynamique)',
    'Playfair Display' => 'Playfair Display (Luxe)',
    'Poppins' => 'Poppins (Arrondi)'
];
?>

<style>
.theme-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 1.5rem;
}
.theme-section {
    background: white;
    border-radius: 12px;
    border: 1px solid var(--gray-200);
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.theme-section-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.theme-section-body {
    padding: 1.5rem;
}
.color-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}
.color-picker-group {
    text-align: center;
}
.color-picker-group label {
    display: block;
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
}
.color-picker-wrap {
    position: relative;
    display: inline-block;
}
.color-picker {
    width: 60px;
    height: 60px;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    padding: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.color-value {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
    font-family: monospace;
}
.preview-frame {
    background: white;
    border-radius: 12px;
    border: 1px solid var(--gray-200);
    overflow: hidden;
    position: sticky;
    top: 1rem;
}
.preview-header {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-200);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.preview-content {
    height: 500px;
    overflow: hidden;
    position: relative;
}
.preview-site {
    transform: scale(0.5);
    transform-origin: top left;
    width: 200%;
    height: 200%;
    border: none;
    pointer-events: none;
}
.preview-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gray-100);
}
.preview-placeholder {
    text-align: center;
    color: var(--gray-500);
}
.upload-area {
    border: 2px dashed var(--gray-300);
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}
.upload-area:hover {
    border-color: var(--primary);
    background: var(--gray-50);
}
.upload-area input {
    display: none;
}
.current-logo {
    max-width: 200px;
    max-height: 80px;
    margin-bottom: 1rem;
}
.font-preview {
    padding: 1rem;
    background: var(--gray-50);
    border-radius: 8px;
    margin-top: 0.5rem;
}
.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: 8px;
    margin-bottom: 0.75rem;
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
    inset: 0;
    background-color: var(--gray-300);
    transition: .3s;
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
    transition: .3s;
    border-radius: 50%;
}
input:checked + .toggle-slider {
    background-color: var(--primary);
}
input:checked + .toggle-slider:before {
    transform: translateX(24px);
}
</style>

<div class="page-header">
    <h2 class="page-title">Personnalisation</h2>
    <p class="page-subtitle">Personnalisez l'apparence de votre site</p>
</div>

<form method="POST">
<div class="theme-layout">
    <div class="theme-sections">
        <!-- Couleurs -->
        <div class="theme-section">
            <div class="theme-section-header">🎨 Couleurs</div>
            <div class="theme-section-body">
                <div class="color-grid">
                    <div class="color-picker-group">
                        <label>Couleur principale</label>
                        <div class="color-picker-wrap">
                            <input type="color" name="primary_color" class="color-picker" value="<?= e($theme['primary_color']) ?>" onchange="updatePreview()">
                        </div>
                        <div class="color-value"><?= e($theme['primary_color']) ?></div>
                    </div>
                    <div class="color-picker-group">
                        <label>Couleur secondaire</label>
                        <div class="color-picker-wrap">
                            <input type="color" name="secondary_color" class="color-picker" value="<?= e($theme['secondary_color']) ?>" onchange="updatePreview()">
                        </div>
                        <div class="color-value"><?= e($theme['secondary_color']) ?></div>
                    </div>
                    <div class="color-picker-group">
                        <label>Couleur d'accent</label>
                        <div class="color-picker-wrap">
                            <input type="color" name="accent_color" class="color-picker" value="<?= e($theme['accent_color']) ?>" onchange="updatePreview()">
                        </div>
                        <div class="color-value"><?= e($theme['accent_color']) ?></div>
                    </div>
                    <div class="color-picker-group">
                        <label>Texte</label>
                        <div class="color-picker-wrap">
                            <input type="color" name="text_color" class="color-picker" value="<?= e($theme['text_color']) ?>" onchange="updatePreview()">
                        </div>
                        <div class="color-value"><?= e($theme['text_color']) ?></div>
                    </div>
                    <div class="color-picker-group">
                        <label>Footer (fond)</label>
                        <div class="color-picker-wrap">
                            <input type="color" name="footer_bg" class="color-picker" value="<?= e($theme['footer_bg']) ?>" onchange="updatePreview()">
                        </div>
                        <div class="color-value"><?= e($theme['footer_bg']) ?></div>
                    </div>
                    <div class="color-picker-group">
                        <label>Footer (texte)</label>
                        <div class="color-picker-wrap">
                            <input type="color" name="footer_text" class="color-picker" value="<?= e($theme['footer_text']) ?>" onchange="updatePreview()">
                        </div>
                        <div class="color-value"><?= e($theme['footer_text']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Typographie -->
        <div class="theme-section">
            <div class="theme-section-header">🔤 Typographie</div>
            <div class="theme-section-body">
                <div class="form-group">
                    <label class="form-label">Police de caractères</label>
                    <select name="font_family" class="form-control" id="font-select" onchange="updateFontPreview()">
                        <?php foreach ($fonts as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $theme['font_family'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="font-preview" id="font-preview" style="font-family: <?= e($theme['font_family']) ?>">
                        <p style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">Taxi Julien</p>
                        <p style="margin: 0;">Votre service de taxi professionnel pour tous vos déplacements.</p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Taille de base (px)</label>
                    <input type="range" name="font_size" min="14" max="20" value="<?= e($theme['font_size']) ?>" class="form-control" style="padding: 0;" oninput="document.getElementById('font-size-val').textContent = this.value + 'px'">
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--gray-500);">
                        <span>14px</span>
                        <span id="font-size-val"><?= e($theme['font_size']) ?>px</span>
                        <span>20px</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Header -->
        <div class="theme-section">
            <div class="theme-section-header">📍 En-tête</div>
            <div class="theme-section-body">
                <div class="form-group">
                    <label class="form-label">Style du header</label>
                    <select name="header_style" class="form-control">
                        <option value="transparent" <?= $theme['header_style'] === 'transparent' ? 'selected' : '' ?>>Transparent (sur hero)</option>
                        <option value="solid" <?= $theme['header_style'] === 'solid' ? 'selected' : '' ?>>Solide</option>
                        <option value="sticky" <?= $theme['header_style'] === 'sticky' ? 'selected' : '' ?>>Sticky (reste fixe)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Couleur de fond header</label>
                    <input type="color" name="header_bg" class="color-picker" value="<?= e($theme['header_bg']) ?>" style="width: 100%; height: 40px;">
                </div>
            </div>
        </div>

        <!-- Logo & Favicon -->
        <div class="theme-section">
            <div class="theme-section-header">🖼️ Logo & Favicon</div>
            <div class="theme-section-body">
                <div class="form-group">
                    <label class="form-label">URL du logo</label>
                    <?php if (!empty($theme['logo_url'])): ?>
                    <img src="<?= e($theme['logo_url']) ?>" class="current-logo" alt="Logo actuel">
                    <?php endif; ?>
                    <input type="text" name="logo_url" class="form-control" value="<?= e($theme['logo_url']) ?>" placeholder="https://... ou /images/logo.png">
                    <p style="font-size: 0.8rem; color: var(--gray-500); margin-top: 0.25rem;">
                        Uploadez votre logo dans la médiathèque et collez l'URL ici
                    </p>
                </div>
                <div class="form-group">
                    <label class="form-label">URL du favicon</label>
                    <input type="text" name="favicon_url" class="form-control" value="<?= e($theme['favicon_url']) ?>" placeholder="https://... ou /favicon.ico">
                    <p style="font-size: 0.8rem; color: var(--gray-500); margin-top: 0.25rem;">
                        Icône qui apparaît dans l'onglet du navigateur (32x32px recommandé)
                    </p>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="theme-section">
            <div class="theme-section-header">📞 Bouton d'action</div>
            <div class="theme-section-body">
                <div class="form-group">
                    <label class="form-label">Texte du bouton CTA</label>
                    <input type="text" name="cta_text" class="form-control" value="<?= e($theme['cta_text']) ?>" placeholder="Réserver maintenant">
                </div>
                <div class="form-group">
                    <label class="form-label">Numéro de téléphone CTA</label>
                    <input type="tel" name="cta_phone" class="form-control" value="<?= e($theme['cta_phone']) ?>" placeholder="06 XX XX XX XX">
                    <p style="font-size: 0.8rem; color: var(--gray-500); margin-top: 0.25rem;">
                        Numéro appelé quand on clique sur le bouton
                    </p>
                </div>
            </div>
        </div>

        <!-- Réseaux sociaux -->
        <div class="theme-section">
            <div class="theme-section-header">🌐 Affichage réseaux sociaux</div>
            <div class="theme-section-body">
                <div class="toggle-row">
                    <div>
                        <strong>Afficher dans le header</strong>
                        <p style="font-size: 0.85rem; color: var(--gray-500); margin: 0;">Icônes réseaux sociaux en haut de page</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="show_social_header" value="1" <?= $theme['show_social_header'] == '1' ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="toggle-row">
                    <div>
                        <strong>Afficher dans le footer</strong>
                        <p style="font-size: 0.85rem; color: var(--gray-500); margin: 0;">Icônes réseaux sociaux en bas de page</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="show_social_footer" value="1" <?= $theme['show_social_footer'] == '1' ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Enregistrer les modifications</button>
    </div>

    <!-- Aperçu -->
    <div class="preview-frame">
        <div class="preview-header">👁️ Aperçu en direct</div>
        <div class="preview-content">
            <div class="preview-overlay">
                <div class="preview-placeholder">
                    <p style="font-size: 2rem; margin-bottom: 0.5rem;">🖥️</p>
                    <p>L'aperçu sera disponible</p>
                    <p>après publication des modifications</p>
                    <a href="../index.html" target="_blank" class="btn btn-primary btn-sm" style="margin-top: 1rem;">
                        Voir le site →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</form>

<script>
// Charger les polices Google
const fonts = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Playfair Display', 'Poppins'];
const link = document.createElement('link');
link.href = 'https://fonts.googleapis.com/css2?family=' + fonts.map(f => f.replace(' ', '+')).join('&family=') + '&display=swap';
link.rel = 'stylesheet';
document.head.appendChild(link);

function updateFontPreview() {
    const font = document.getElementById('font-select').value;
    document.getElementById('font-preview').style.fontFamily = font;
}

// Mettre à jour les valeurs de couleur affichées
document.querySelectorAll('.color-picker').forEach(picker => {
    picker.addEventListener('input', function() {
        const valueEl = this.closest('.color-picker-group')?.querySelector('.color-value');
        if (valueEl) valueEl.textContent = this.value;
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
