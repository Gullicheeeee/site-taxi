<?php
/**
 * RÉGÉNÉRATION DES PAGES - Back-Office Taxi Julien
 * Permet de régénérer tous les fichiers HTML statiques en une seule action
 */
declare(strict_types=1);

require_once 'config.php';
require_once 'includes/generator.php';
requireLogin();

// Vérifier le rôle admin
if (!hasRole('admin')) {
    setFlash('danger', 'Accès non autorisé');
    header('Location: index.php');
    exit;
}

$results = [];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $generator = pageGenerator();

    if ($action === 'regenerate_all') {
        // Régénérer toutes les pages
        $results['pages'] = $generator->regenerateAllPages();
        // Régénérer tous les articles de blog
        $results['blog'] = $generator->regenerateAllBlogPosts();

        $totalPages = count($results['pages']);
        $totalBlog = count($results['blog']) - 1; // -1 pour la liste du blog
        $successPages = count(array_filter($results['pages'], fn($r) => $r['success']));
        $successBlog = count(array_filter($results['blog'], fn($r) => $r['success']));

        setFlash('success', "Régénération terminée : {$successPages}/{$totalPages} pages et {$successBlog}/{$totalBlog} articles de blog.");
        logActivity('regenerate_all', 'site', null, [
            'pages' => $totalPages,
            'blog_posts' => $totalBlog
        ]);
    }

    if ($action === 'regenerate_pages') {
        $results['pages'] = $generator->regenerateAllPages();
        $total = count($results['pages']);
        $success = count(array_filter($results['pages'], fn($r) => $r['success']));
        setFlash('success', "Régénération terminée : {$success}/{$total} pages générées.");
        logActivity('regenerate', 'pages', null, ['count' => $total]);
    }

    if ($action === 'regenerate_blog') {
        $results['blog'] = $generator->regenerateAllBlogPosts();
        $total = count($results['blog']) - 1;
        $success = count(array_filter($results['blog'], fn($r) => $r['success'])) - 1;
        setFlash('success', "Régénération terminée : {$success}/{$total} articles générés + liste du blog.");
        logActivity('regenerate', 'blog', null, ['count' => $total]);
    }

    if ($action === 'update_config') {
        // Forcer la mise à jour du fichier de configuration depuis les settings
        $settingsResult = supabase()->select('settings');
        $settings = [];
        if ($settingsResult['success']) {
            foreach ($settingsResult['data'] as $s) {
                $settings[$s['key']] = $s['value'];
            }
        }

        $siteConfig = [
            'site_name' => $settings['site_name'] ?? 'Taxi Julien',
            'phone' => $settings['contact_phone'] ?? '',
            'phone_link' => '+33' . preg_replace('/\D/', '', $settings['contact_phone'] ?? ''),
            'email' => $settings['contact_email'] ?? '',
            'address' => $settings['contact_address'] ?? '',
            'hours' => '24h/24, 7j/7',
            'whatsapp' => preg_replace('/\D/', '', $settings['whatsapp'] ?? '') ?: ('33' . preg_replace('/\D/', '', $settings['contact_phone'] ?? '')),
            'social' => [
                'facebook' => $settings['facebook_url'] ?? '',
                'instagram' => $settings['instagram_url'] ?? ''
            ],
            'seo' => [
                'title_suffix' => ' | ' . ($settings['site_name'] ?? 'Taxi Julien'),
                'default_og_image' => '/images/og-image.jpg'
            ]
        ];

        if ($generator->saveSiteConfig($siteConfig)) {
            setFlash('success', 'Fichier de configuration mis à jour.');
            logActivity('update', 'site_config', null);
        } else {
            setFlash('danger', 'Erreur lors de la mise à jour de la configuration.');
        }
    }
}

$pageTitle = 'Régénération des pages';
require_once 'includes/header.php';
?>

<div class="page-header">
    <h2 class="page-title">Régénération des fichiers HTML</h2>
    <p class="page-subtitle">Régénérez les fichiers HTML statiques du site après modification des templates ou paramètres</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">

    <!-- Régénérer tout -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tout régénérer</h3>
        </div>
        <div class="card-body">
            <p style="margin-bottom: 1.5rem; color: var(--gray-600);">
                Régénère toutes les pages et tous les articles de blog publiés. Utile après une modification des templates.
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="regenerate_all">
                <button type="submit" class="btn btn-primary" style="width: 100%;" onclick="return confirm('Régénérer tous les fichiers HTML ? Cette opération peut prendre quelques secondes.')">
                    Régénérer tout le site
                </button>
            </form>
        </div>
    </div>

    <!-- Régénérer les pages -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pages uniquement</h3>
        </div>
        <div class="card-body">
            <p style="margin-bottom: 1.5rem; color: var(--gray-600);">
                Régénère uniquement les pages publiées (hors blog).
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="regenerate_pages">
                <button type="submit" class="btn btn-secondary" style="width: 100%;">
                    Régénérer les pages
                </button>
            </form>
        </div>
    </div>

    <!-- Régénérer le blog -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Blog uniquement</h3>
        </div>
        <div class="card-body">
            <p style="margin-bottom: 1.5rem; color: var(--gray-600);">
                Régénère tous les articles de blog publiés et la page de liste du blog.
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="regenerate_blog">
                <button type="submit" class="btn btn-secondary" style="width: 100%;">
                    Régénérer le blog
                </button>
            </form>
        </div>
    </div>

    <!-- Mettre à jour la config -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Configuration</h3>
        </div>
        <div class="card-body">
            <p style="margin-bottom: 1.5rem; color: var(--gray-600);">
                Met à jour le fichier site-config.json avec les paramètres actuels (téléphone, email, etc.).
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="update_config">
                <button type="submit" class="btn btn-secondary" style="width: 100%;">
                    Mettre à jour la config
                </button>
            </form>
        </div>
    </div>

</div>

<!-- Informations -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3 class="card-title">Comment fonctionne la génération ?</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
            <div>
                <h4 style="margin-bottom: 0.5rem;">1. Templates</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Les fichiers HTML sont générés à partir des templates situés dans <code>/admin/templates/</code>.
                    Modifiez ces fichiers pour changer le design global.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">2. Configuration</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Les informations de contact (téléphone, email...) sont lues depuis <code>/data/site-config.json</code>.
                    Ce fichier est mis à jour via les Réglages.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">3. Publication</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Quand vous cliquez sur "Publier" dans l'éditeur de page, le fichier HTML correspondant est créé ou mis à jour.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">4. Fichiers générés</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Les fichiers HTML sont créés à la racine du site. Les articles de blog vont dans <code>/blog/</code>.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Résultats détaillés si présents -->
<?php if (!empty($results)): ?>
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3 class="card-title">Résultats de la régénération</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($results['pages'])): ?>
        <h4 style="margin-bottom: 0.5rem;">Pages</h4>
        <table class="table" style="margin-bottom: 1.5rem;">
            <thead>
                <tr>
                    <th>Slug</th>
                    <th>Statut</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results['pages'] as $slug => $result): ?>
                <tr>
                    <td><code><?= e($slug) ?>.html</code></td>
                    <td>
                        <?php if ($result['success']): ?>
                        <span class="badge badge-success">OK</span>
                        <?php else: ?>
                        <span class="badge badge-danger">Erreur</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($result['message']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if (!empty($results['blog'])): ?>
        <h4 style="margin-bottom: 0.5rem;">Blog</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Fichier</th>
                    <th>Statut</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results['blog'] as $slug => $result): ?>
                <tr>
                    <td><code><?= e($result['file'] ?? $slug) ?></code></td>
                    <td>
                        <?php if ($result['success']): ?>
                        <span class="badge badge-success">OK</span>
                        <?php else: ?>
                        <span class="badge badge-danger">Erreur</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($result['message']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
