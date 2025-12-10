<?php
define('ADMIN_LOADED', true);
require_once 'config.php';

$activePage = 'settings';
$pageTitle = 'Paramètres du Site';

// Récupérer tous les paramètres
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM settings ORDER BY setting_key");
    $settingsData = $stmt->fetchAll();

    // Convertir en array associatif
    $settings = [];
    foreach ($settingsData as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (PDOException $e) {
    $settings = [];
    setFlash('error', 'Erreur lors du chargement des paramètres');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();

        foreach ($_POST as $key => $value) {
            if ($key !== 'submit') {
                $stmt = $db->prepare("
                    UPDATE settings SET setting_value = ? WHERE setting_key = ?
                ");
                $stmt->execute([trim($value), $key]);
            }
        }

        setFlash('success', 'Paramètres mis à jour avec succès');
        header('Location: settings.php');
        exit;
    } catch (PDOException $e) {
        $errors[] = 'Erreur lors de la mise à jour : ' . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Erreurs :</strong>
        <ul style="margin: 0.5rem 0 0 1.5rem;">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-3">
        <div class="card-header">
            <h2 class="card-title">Informations Générales</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="site_name" class="form-label">Nom du site</label>
                <input
                    type="text"
                    id="site_name"
                    name="site_name"
                    class="form-input"
                    value="<?= e($settings['site_name'] ?? 'Taxi Julien') ?>"
                >
            </div>

            <div class="form-group">
                <label for="phone_number" class="form-label">Numéro de téléphone</label>
                <input
                    type="text"
                    id="phone_number"
                    name="phone_number"
                    class="form-input"
                    value="<?= e($settings['phone_number'] ?? '') ?>"
                    placeholder="+33 1 23 45 67 89"
                >
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email de contact</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input"
                    value="<?= e($settings['email'] ?? '') ?>"
                    placeholder="contact@taxijulien.fr"
                >
            </div>

            <div class="form-group">
                <label for="address" class="form-label">Adresse</label>
                <input
                    type="text"
                    id="address"
                    name="address"
                    class="form-input"
                    value="<?= e($settings['address'] ?? '') ?>"
                    placeholder="Martigues, Bouches-du-Rhône (13)"
                >
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h2 class="card-title">Réseaux Sociaux</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="facebook_url" class="form-label">Page Facebook</label>
                <input
                    type="url"
                    id="facebook_url"
                    name="facebook_url"
                    class="form-input"
                    value="<?= e($settings['facebook_url'] ?? '') ?>"
                    placeholder="https://facebook.com/..."
                >
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h2 class="card-title">Outils & Analytics</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="google_analytics" class="form-label">Code Google Analytics</label>
                <input
                    type="text"
                    id="google_analytics"
                    name="google_analytics"
                    class="form-input"
                    value="<?= e($settings['google_analytics'] ?? '') ?>"
                    placeholder="G-XXXXXXXXXX"
                >
                <div class="form-help">Ex: G-XXXXXXXXXX ou UA-XXXXXXXXX-X</div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h2 class="card-title">Fonctionnalités</h2>
        </div>
        <div class="card-body">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input
                    type="checkbox"
                    name="enable_reservations"
                    value="1"
                    <?= ($settings['enable_reservations'] ?? '1') == '1' ? 'checked' : '' ?>
                >
                <span>Activer les réservations en ligne</span>
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-lg">
            💾 Enregistrer les paramètres
        </button>
    </div>
</form>

<div class="card mt-3" style="background: var(--gray-50);">
    <div class="card-body">
        <h3 style="margin-bottom: 1rem;">💡 Informations</h3>
        <p style="margin-bottom: 0.5rem;">
            Les paramètres modifiés ici seront utilisés dans tout le site.
        </p>
        <p style="margin-bottom: 0;">
            Pour ajouter de nouveaux paramètres, vous devez les insérer dans la table <code>settings</code> de la base de données.
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
