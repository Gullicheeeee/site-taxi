<?php
/**
 * INITIALISATION DE LA BASE DE DONNÉES
 * Ce script crée les pages et sections dans Supabase à partir du contenu actuel du site
 */
declare(strict_types=1);

require_once 'config.php';
requireLogin();

// Vérifier le rôle admin
if (!hasRole('admin')) {
    setFlash('danger', 'Accès non autorisé');
    header('Location: index.php');
    exit;
}

$results = [];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'init_pages') {

    // Liste des pages à créer avec leurs données
    $pages = [
        [
            'slug' => 'index',
            'title' => 'Accueil',
            'meta_title' => 'Taxi Julien - Taxi Conventionné Martigues | Réservation 24/7',
            'meta_description' => 'Taxi Julien - Service de taxi conventionné CPAM à Martigues. Réservation en ligne, transport médicalisé, trajets aéroports et gares. Disponible 24/7.',
            'meta_keywords' => 'taxi Martigues, taxi conventionné, CPAM, transport médical, réservation taxi, aéroport Marseille, gare TGV Aix, taxi 13500',
            'hero_title' => 'Taxi Conventionné à Martigues',
            'hero_subtitle' => 'Votre transport médical et vos trajets quotidiens en toute sérénité',
            'status' => 'published'
        ],
        [
            'slug' => 'services',
            'title' => 'Nos Services',
            'meta_title' => 'Services Taxi Martigues | Conventionné, Aéroport, Longue Distance',
            'meta_description' => 'Tous les services de Taxi Julien à Martigues : transport conventionné CPAM, transferts aéroports et gares, longues distances, courses classiques. Disponible 24/7.',
            'meta_keywords' => 'taxi Martigues services, taxi conventionné, transfert aéroport, taxi longue distance, mise à disposition, taxi 24h',
            'hero_title' => 'Nos Services de Taxi',
            'hero_subtitle' => 'Un service adapté à tous vos besoins de transport',
            'status' => 'published'
        ],
        [
            'slug' => 'conventionne',
            'title' => 'Transport Conventionné',
            'meta_title' => 'Taxi Conventionné CPAM Martigues | Transport Médical Remboursé',
            'meta_description' => 'Transport conventionné CPAM à Martigues. Vos trajets médicaux pris en charge par la Sécurité Sociale. Formalités simplifiées, service agréé.',
            'meta_keywords' => 'taxi conventionné, CPAM, transport médical, Sécurité Sociale, remboursement taxi, Martigues',
            'hero_title' => 'Transport Conventionné CPAM',
            'hero_subtitle' => 'Vos trajets médicaux pris en charge par la Sécurité Sociale',
            'status' => 'published'
        ],
        [
            'slug' => 'aeroports-gares',
            'title' => 'Aéroports & Gares',
            'meta_title' => 'Transfert Aéroport Marseille & Gare TGV | Taxi Julien Martigues',
            'meta_description' => 'Transferts taxi vers Aéroport Marseille Provence, Gare TGV Aix-en-Provence depuis Martigues. Tarifs fixes, réservation en ligne.',
            'meta_keywords' => 'taxi aéroport Marseille, transfert gare TGV, navette aéroport, taxi Martigues aéroport',
            'hero_title' => 'Transferts Aéroports & Gares',
            'hero_subtitle' => 'Navettes vers Marseille Provence et Gare TGV Aix-en-Provence',
            'status' => 'published'
        ],
        [
            'slug' => 'longues-distances',
            'title' => 'Longues Distances',
            'meta_title' => 'Taxi Longue Distance Martigues | Trajets France Entière',
            'meta_description' => 'Service de taxi longue distance depuis Martigues. Déplacements interrégionaux, voyages d\'affaires partout en France.',
            'meta_keywords' => 'taxi longue distance, trajet France, taxi interrégional, Martigues',
            'hero_title' => 'Trajets Longues Distances',
            'hero_subtitle' => 'Déplacements interrégionaux partout en France',
            'status' => 'published'
        ],
        [
            'slug' => 'courses-classiques',
            'title' => 'Courses Classiques',
            'meta_title' => 'Taxi Martigues | Courses et Déplacements Quotidiens',
            'meta_description' => 'Service de taxi pour vos déplacements quotidiens à Martigues et environs. Courses, rendez-vous, sorties.',
            'meta_keywords' => 'taxi Martigues, course taxi, déplacement quotidien',
            'hero_title' => 'Courses Classiques',
            'hero_subtitle' => 'Vos déplacements quotidiens à Martigues et environs',
            'status' => 'published'
        ],
        [
            'slug' => 'mise-a-disposition',
            'title' => 'Mise à Disposition',
            'meta_title' => 'Taxi à Disposition Martigues | Location avec Chauffeur',
            'meta_description' => 'Location de taxi avec chauffeur à Martigues. Mise à disposition à l\'heure ou à la journée pour événements, tourisme, affaires.',
            'meta_keywords' => 'taxi disposition, location chauffeur, taxi événement, Martigues',
            'hero_title' => 'Mise à Disposition',
            'hero_subtitle' => 'Location de taxi avec chauffeur à l\'heure ou à la journée',
            'status' => 'published'
        ],
        [
            'slug' => 'simulateur',
            'title' => 'Simulateur de Prix',
            'meta_title' => 'Simulateur Prix Taxi Martigues | Estimation Gratuite',
            'meta_description' => 'Calculez instantanément le prix de votre course taxi à Martigues. Simulateur en ligne gratuit, estimation précise.',
            'meta_keywords' => 'prix taxi, simulateur taxi, estimation course, tarif taxi Martigues',
            'hero_title' => 'Simulateur de Prix',
            'hero_subtitle' => 'Calculez instantanément le tarif de votre trajet',
            'status' => 'published'
        ],
        [
            'slug' => 'blog',
            'title' => 'Blog',
            'meta_title' => 'Blog Taxi Julien | Actualités et Conseils Transport',
            'meta_description' => 'Retrouvez nos conseils et actualités sur le transport en taxi à Martigues : transport médical, aéroports, astuces voyage.',
            'meta_keywords' => 'blog taxi, actualités transport, conseils taxi',
            'hero_title' => 'Blog - Actualités Taxi',
            'hero_subtitle' => 'Conseils, astuces et actualités sur le transport en taxi',
            'status' => 'published'
        ],
        [
            'slug' => 'a-propos',
            'title' => 'À Propos',
            'meta_title' => 'À Propos de Taxi Julien | Votre Taxi de Confiance à Martigues',
            'meta_description' => 'Découvrez Taxi Julien, votre service de taxi conventionné de confiance à Martigues depuis plus de 10 ans.',
            'meta_keywords' => 'taxi Julien, à propos, taxi Martigues, chauffeur',
            'hero_title' => 'À Propos de Taxi Julien',
            'hero_subtitle' => 'Votre taxi de confiance à Martigues depuis plus de 10 ans',
            'status' => 'published'
        ],
        [
            'slug' => 'contact',
            'title' => 'Contact',
            'meta_title' => 'Contact Taxi Julien Martigues | Réservation et Informations',
            'meta_description' => 'Contactez Taxi Julien à Martigues. Réservation par téléphone, WhatsApp ou formulaire en ligne. Disponible 24h/24.',
            'meta_keywords' => 'contact taxi, réservation taxi, téléphone taxi Martigues',
            'hero_title' => 'Contactez-nous',
            'hero_subtitle' => 'Réservation et informations - Disponible 24h/24',
            'status' => 'published'
        ],
        [
            'slug' => 'reservation',
            'title' => 'Réservation',
            'meta_title' => 'Réserver un Taxi Martigues | Taxi Julien - Réservation en Ligne',
            'meta_description' => 'Réservez votre taxi en ligne à Martigues. Formulaire simple et rapide. Confirmation immédiate.',
            'meta_keywords' => 'réserver taxi, réservation en ligne, taxi Martigues',
            'hero_title' => 'Réserver un Taxi',
            'hero_subtitle' => 'Réservation simple et rapide - Confirmation immédiate',
            'status' => 'published'
        ],
        [
            'slug' => 'mentions-legales',
            'title' => 'Mentions Légales',
            'meta_title' => 'Mentions Légales | Taxi Julien Martigues',
            'meta_description' => 'Mentions légales du site Taxi Julien. Informations juridiques, politique de confidentialité, RGPD.',
            'meta_keywords' => 'mentions légales, RGPD, politique confidentialité',
            'hero_title' => 'Mentions Légales',
            'hero_subtitle' => 'Informations juridiques et politique de confidentialité',
            'status' => 'published'
        ]
    ];

    $successCount = 0;
    $errorCount = 0;

    foreach ($pages as $page) {
        // Vérifier si la page existe déjà
        $existing = supabase()->select('pages', 'slug=eq.' . urlencode($page['slug']));

        if ($existing['success'] && !empty($existing['data'])) {
            // Mettre à jour
            $result = supabase()->update('pages', 'slug=eq.' . urlencode($page['slug']), $page);
        } else {
            // Créer
            $page['created_at'] = date('c');
            $page['published_at'] = date('c');
            $result = supabase()->insert('pages', $page);
        }

        if ($result['success']) {
            $successCount++;
            $results[] = ['page' => $page['slug'], 'status' => 'success'];
        } else {
            $errorCount++;
            $results[] = ['page' => $page['slug'], 'status' => 'error', 'message' => $result['error'] ?? 'Unknown error'];
        }
    }

    setFlash('success', "Initialisation terminée : {$successCount} pages créées/mises à jour, {$errorCount} erreurs.");
}

// Initialiser les paramètres du site
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'init_settings') {
    $settings = [
        'site_name' => 'Taxi Julien',
        'site_tagline' => 'Votre taxi de confiance à Martigues',
        'site_url' => 'https://www.taxijulien.fr',
        'contact_phone' => '01 23 45 67 89',
        'contact_email' => 'contact@taxijulien.fr',
        'contact_address' => 'Martigues, Bouches-du-Rhône (13)',
        'whatsapp' => '33123456789',
        'facebook_url' => 'https://facebook.com/taxijulien',
        'instagram_url' => '',
        'posts_per_page' => '10'
    ];

    $successCount = 0;
    foreach ($settings as $key => $value) {
        $result = setSetting($key, $value);
        if ($result['success']) {
            $successCount++;
        }
    }

    // Mettre à jour aussi le fichier site-config.json
    $configFile = __DIR__ . '/../data/site-config.json';
    $config = [
        'site_name' => 'Taxi Julien',
        'phone' => '01 23 45 67 89',
        'phone_link' => '+33123456789',
        'email' => 'contact@taxijulien.fr',
        'address' => 'Martigues, Bouches-du-Rhône (13)',
        'hours' => '24h/24, 7j/7',
        'whatsapp' => '33123456789',
        'social' => [
            'facebook' => 'https://facebook.com/taxijulien',
            'instagram' => ''
        ],
        'seo' => [
            'title_suffix' => ' | Taxi Julien Martigues',
            'default_og_image' => '/images/og-image.jpg'
        ]
    ];
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    setFlash('success', "{$successCount} paramètres initialisés.");
}

// Créer un admin par défaut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'init_admin') {
    $username = trim($_POST['admin_username'] ?? 'admin');
    $password = trim($_POST['admin_password'] ?? '');
    $email = trim($_POST['admin_email'] ?? 'admin@taxijulien.fr');

    if (strlen($password) < 6) {
        setFlash('danger', 'Le mot de passe doit contenir au moins 6 caractères.');
    } else {
        // Vérifier si l'admin existe
        $existing = supabase()->select('admins', 'username=eq.' . urlencode($username));

        $adminData = [
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'admin'
        ];

        if ($existing['success'] && !empty($existing['data'])) {
            $result = supabase()->update('admins', 'username=eq.' . urlencode($username), $adminData);
        } else {
            $adminData['created_at'] = date('c');
            $result = supabase()->insert('admins', $adminData);
        }

        if ($result['success']) {
            setFlash('success', "Administrateur '{$username}' créé/mis à jour avec succès.");
        } else {
            setFlash('danger', 'Erreur lors de la création de l\'administrateur: ' . ($result['error'] ?? 'Unknown'));
        }
    }
}

$pageTitle = 'Initialisation de la base de données';
require_once 'includes/header.php';
?>

<div class="page-header">
    <h2 class="page-title">Initialisation de la Base de Données</h2>
    <p class="page-subtitle">Créer les données initiales dans Supabase pour le back-office</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem;">

    <!-- Initialiser les pages -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">1. Initialiser les Pages</h3>
        </div>
        <div class="card-body">
            <p style="margin-bottom: 1rem; color: var(--gray-600);">
                Crée toutes les pages du site dans Supabase avec leurs métadonnées SEO.
                Les pages existantes seront mises à jour.
            </p>
            <ul style="font-size: 0.9rem; color: var(--gray-500); margin-bottom: 1.5rem;">
                <li>Accueil, Services, Transport Conventionné</li>
                <li>Aéroports & Gares, Longues Distances</li>
                <li>Simulateur, Blog, Contact, Réservation...</li>
            </ul>
            <form method="POST">
                <input type="hidden" name="action" value="init_pages">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Créer/Mettre à jour les pages
                </button>
            </form>
        </div>
    </div>

    <!-- Initialiser les paramètres -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">2. Initialiser les Paramètres</h3>
        </div>
        <div class="card-body">
            <p style="margin-bottom: 1rem; color: var(--gray-600);">
                Configure les paramètres globaux du site (nom, contact, réseaux sociaux).
            </p>
            <ul style="font-size: 0.9rem; color: var(--gray-500); margin-bottom: 1.5rem;">
                <li>Nom du site : Taxi Julien</li>
                <li>Téléphone : 01 23 45 67 89</li>
                <li>Email : contact@taxijulien.fr</li>
                <li>+ Réseaux sociaux</li>
            </ul>
            <form method="POST">
                <input type="hidden" name="action" value="init_settings">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Initialiser les paramètres
                </button>
            </form>
        </div>
    </div>

    <!-- Créer un admin -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">3. Créer un Administrateur</h3>
        </div>
        <div class="card-body">
            <p style="margin-bottom: 1rem; color: var(--gray-600);">
                Créez un compte administrateur pour accéder au back-office.
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="init_admin">
                <div class="form-group">
                    <label class="form-label">Nom d'utilisateur</label>
                    <input type="text" name="admin_username" class="form-control" value="admin" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="admin_email" class="form-control" value="admin@taxijulien.fr" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mot de passe (min. 6 caractères)</label>
                    <input type="password" name="admin_password" class="form-control" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Créer l'administrateur
                </button>
            </form>
        </div>
    </div>

</div>

<!-- Résultats -->
<?php if (!empty($results)): ?>
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3 class="card-title">Résultats de l'initialisation</h3>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Statut</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $r): ?>
                <tr>
                    <td><code><?= e($r['page']) ?></code></td>
                    <td>
                        <?php if ($r['status'] === 'success'): ?>
                        <span class="badge badge-success">OK</span>
                        <?php else: ?>
                        <span class="badge badge-danger">Erreur</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($r['message'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Instructions -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3 class="card-title">Instructions d'utilisation</h3>
    </div>
    <div class="card-body">
        <ol style="line-height: 2;">
            <li><strong>Étape 1 :</strong> Cliquez sur "Créer/Mettre à jour les pages" pour créer toutes les pages dans Supabase.</li>
            <li><strong>Étape 2 :</strong> Cliquez sur "Initialiser les paramètres" pour configurer les informations du site.</li>
            <li><strong>Étape 3 :</strong> Si nécessaire, créez un nouvel administrateur avec un mot de passe sécurisé.</li>
            <li><strong>Étape 4 :</strong> Allez dans <a href="pages.php">Pages</a> pour voir et modifier les pages.</li>
            <li><strong>Étape 5 :</strong> Éditez une page et cliquez sur <strong>"Publier"</strong> pour générer le fichier HTML.</li>
        </ol>

        <div style="background: var(--warning-light, #fef3c7); padding: 1rem; border-radius: 8px; margin-top: 1rem;">
            <strong>Note importante :</strong> Les pages créées ici ne remplaceront pas automatiquement les fichiers HTML existants.
            Pour mettre à jour un fichier HTML, vous devez modifier la page dans l'éditeur puis cliquer sur "Publier".
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
