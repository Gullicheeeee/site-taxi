<?php
$pageTitle = 'Paramètres';
require_once 'includes/header.php';

$db = getDB();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => $_POST['site_name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'address' => $_POST['address'] ?? '',
        'google_analytics' => $_POST['google_analytics'] ?? '',
        'facebook_pixel' => $_POST['facebook_pixel'] ?? '',
        'whatsapp' => $_POST['whatsapp'] ?? '',
        'facebook_url' => $_POST['facebook_url'] ?? '',
        'instagram_url' => $_POST['instagram_url'] ?? '',
    ];

    foreach ($settings as $key => $value) {
        setSetting($key, trim($value));
    }

    setFlash('success', 'Paramètres enregistrés !');
    header('Location: settings.php');
    exit;
}

// Changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $user = $stmt->fetch();

    if (!password_verify($current, $user['password'])) {
        setFlash('danger', 'Mot de passe actuel incorrect');
    } elseif ($new !== $confirm) {
        setFlash('danger', 'Les mots de passe ne correspondent pas');
    } elseif (strlen($new) < 6) {
        setFlash('danger', 'Le mot de passe doit contenir au moins 6 caractères');
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $_SESSION['admin_id']]);
        setFlash('success', 'Mot de passe modifié !');
    }
    header('Location: settings.php');
    exit;
}
?>

<div class="page-header">
    <h2 class="page-title">Paramètres</h2>
    <p class="page-subtitle">Configuration générale du site</p>
</div>

<form method="POST">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <!-- Informations générales -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informations générales</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Nom du site</label>
                    <input type="text" name="site_name" class="form-control"
                           value="<?= e(getSetting('site_name', 'Taxi Julien')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= e(getSetting('phone')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= e(getSetting('email')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Adresse</label>
                    <input type="text" name="address" class="form-control"
                           value="<?= e(getSetting('address')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" name="whatsapp" class="form-control"
                           value="<?= e(getSetting('whatsapp')) ?>"
                           placeholder="33612345678 (sans le +)">
                </div>
            </div>
        </div>

        <!-- Réseaux sociaux & Tracking -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Réseaux sociaux & Tracking</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">URL Facebook</label>
                    <input type="url" name="facebook_url" class="form-control"
                           value="<?= e(getSetting('facebook_url')) ?>"
                           placeholder="https://facebook.com/votrepage">
                </div>

                <div class="form-group">
                    <label class="form-label">URL Instagram</label>
                    <input type="url" name="instagram_url" class="form-control"
                           value="<?= e(getSetting('instagram_url')) ?>"
                           placeholder="https://instagram.com/votrepage">
                </div>

                <div class="form-group">
                    <label class="form-label">Google Analytics ID</label>
                    <input type="text" name="google_analytics" class="form-control"
                           value="<?= e(getSetting('google_analytics')) ?>"
                           placeholder="G-XXXXXXXXXX ou UA-XXXXXXXX-X">
                    <p class="form-help">ID de mesure Google Analytics 4</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Facebook Pixel ID</label>
                    <input type="text" name="facebook_pixel" class="form-control"
                           value="<?= e(getSetting('facebook_pixel')) ?>"
                           placeholder="123456789012345">
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        💾 Enregistrer les paramètres
    </button>
</form>

<!-- Changer le mot de passe -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">Sécurité</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="change_password" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Mot de passe actuel</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
            </div>
            <button type="submit" class="btn btn-secondary">
                🔐 Changer le mot de passe
            </button>
        </form>
    </div>
</div>

<!-- Code de tracking à intégrer -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">Code de tracking à intégrer</h3>
    </div>
    <div class="card-body">
        <p style="margin-bottom: 1rem;">Ajoutez ce code dans le <code>&lt;head&gt;</code> de chaque page HTML :</p>

        <?php if (getSetting('google_analytics')): ?>
        <h4 style="margin-bottom: 0.5rem;">Google Analytics</h4>
        <pre style="background: var(--gray-800); color: #fff; padding: 1rem; border-radius: 8px; overflow-x: auto; margin-bottom: 1rem;"><code>&lt;!-- Google Analytics --&gt;
&lt;script async src="https://www.googletagmanager.com/gtag/js?id=<?= e(getSetting('google_analytics')) ?>"&gt;&lt;/script&gt;
&lt;script&gt;
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= e(getSetting('google_analytics')) ?>');
&lt;/script&gt;</code></pre>
        <?php endif; ?>

        <?php if (getSetting('facebook_pixel')): ?>
        <h4 style="margin-bottom: 0.5rem;">Facebook Pixel</h4>
        <pre style="background: var(--gray-800); color: #fff; padding: 1rem; border-radius: 8px; overflow-x: auto;"><code>&lt;!-- Facebook Pixel --&gt;
&lt;script&gt;
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '<?= e(getSetting('facebook_pixel')) ?>');
  fbq('track', 'PageView');
&lt;/script&gt;</code></pre>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
