<?php
$pageTitle = 'Sitemap & Robots.txt';
require_once 'includes/header.php';

// Récupérer les pages et articles
$pagesResult = supabase()->select('pages', 'select=id,slug,title,updated_at&order=title.asc');
$pages = $pagesResult['success'] ? $pagesResult['data'] : [];

$postsResult = supabase()->select('blog_posts', 'select=id,slug,title,updated_at,is_published&is_published=eq.true&order=created_at.desc');
$posts = $postsResult['success'] ? $postsResult['data'] : [];

// Configuration
$settingsResult = supabase()->select('settings', 'key=in.(site_url,sitemap_frequency)');
$settings = [];
if ($settingsResult['success']) {
    foreach ($settingsResult['data'] as $s) {
        $settings[$s['key']] = $s['value'];
    }
}

$siteUrl = $settings['site_url'] ?? 'https://votresite.fr';
$frequency = $settings['sitemap_frequency'] ?? 'weekly';

// Générer le sitemap XML
$sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Pages
foreach ($pages as $page) {
    $lastmod = !empty($page['updated_at']) ? date('Y-m-d', strtotime($page['updated_at'])) : date('Y-m-d');
    $priority = $page['slug'] === 'index' ? '1.0' : '0.8';

    $sitemap .= "  <url>\n";
    $sitemap .= "    <loc>{$siteUrl}/{$page['slug']}.html</loc>\n";
    $sitemap .= "    <lastmod>{$lastmod}</lastmod>\n";
    $sitemap .= "    <changefreq>{$frequency}</changefreq>\n";
    $sitemap .= "    <priority>{$priority}</priority>\n";
    $sitemap .= "  </url>\n";
}

// Articles de blog
foreach ($posts as $post) {
    $lastmod = !empty($post['updated_at']) ? date('Y-m-d', strtotime($post['updated_at'])) : date('Y-m-d');

    $sitemap .= "  <url>\n";
    $sitemap .= "    <loc>{$siteUrl}/blog/{$post['slug']}.html</loc>\n";
    $sitemap .= "    <lastmod>{$lastmod}</lastmod>\n";
    $sitemap .= "    <changefreq>monthly</changefreq>\n";
    $sitemap .= "    <priority>0.6</priority>\n";
    $sitemap .= "  </url>\n";
}

$sitemap .= '</urlset>';

// Robots.txt par défaut
$robotsTxt = "User-agent: *\n";
$robotsTxt .= "Allow: /\n\n";
$robotsTxt .= "# Bloquer le back-office\n";
$robotsTxt .= "Disallow: /admin/\n\n";
$robotsTxt .= "# Sitemap\n";
$robotsTxt .= "Sitemap: {$siteUrl}/sitemap.xml\n";

// Sauvegarder si demandé
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'generate_sitemap') {
        $sitemapPath = dirname(__DIR__) . '/sitemap.xml';
        file_put_contents($sitemapPath, $sitemap);
        setFlash('success', 'Sitemap.xml généré avec succès !');
    }

    if ($action === 'generate_robots') {
        $robotsPath = dirname(__DIR__) . '/robots.txt';
        file_put_contents($robotsPath, $robotsTxt);
        setFlash('success', 'Robots.txt généré avec succès !');
    }

    header('Location: sitemap.php');
    exit;
}
?>

<style>
.file-preview {
    background: #1a1a2e;
    color: #e0e0e0;
    padding: 1.5rem;
    border-radius: 8px;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 0.85rem;
    line-height: 1.6;
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 400px;
    overflow-y: auto;
}
.file-preview .tag {
    color: #7dd3fc;
}
.file-preview .attr {
    color: #fbbf24;
}
.file-preview .value {
    color: #86efac;
}
.file-preview .comment {
    color: #6b7280;
}
.gen-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}
.url-list {
    max-height: 300px;
    overflow-y: auto;
}
.url-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--gray-100);
    font-size: 0.9rem;
}
.url-item:last-child {
    border-bottom: none;
}
.url-path {
    font-family: monospace;
    color: var(--primary);
}
.priority-badge {
    font-size: 0.75rem;
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    background: var(--gray-100);
    color: var(--gray-600);
}
</style>

<div class="page-header">
    <h2 class="page-title">Sitemap & Robots.txt</h2>
    <p class="page-subtitle">Configurez l'indexation de votre site par les moteurs de recherche</p>
</div>

<div class="gen-grid">
    <!-- Sitemap -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">🗺️ Sitemap.xml</h3>
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="generate_sitemap">
                <button type="submit" class="btn btn-primary btn-sm">Générer</button>
            </form>
        </div>
        <div class="card-body">
            <p style="color: var(--gray-600); margin-bottom: 1rem; font-size: 0.9rem;">
                Le sitemap aide Google à découvrir et indexer toutes vos pages.
            </p>

            <h4 style="font-size: 0.9rem; margin-bottom: 0.75rem;">URLs incluses (<?= count($pages) + count($posts) ?>)</h4>
            <div class="url-list">
                <?php foreach ($pages as $page): ?>
                <div class="url-item">
                    <span class="url-path">/<?= e($page['slug']) ?>.html</span>
                    <span class="priority-badge"><?= $page['slug'] === 'index' ? '1.0' : '0.8' ?></span>
                </div>
                <?php endforeach; ?>
                <?php foreach ($posts as $post): ?>
                <div class="url-item">
                    <span class="url-path">/blog/<?= e($post['slug']) ?>.html</span>
                    <span class="priority-badge">0.6</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Robots.txt -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">🤖 Robots.txt</h3>
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="generate_robots">
                <button type="submit" class="btn btn-primary btn-sm">Générer</button>
            </form>
        </div>
        <div class="card-body">
            <p style="color: var(--gray-600); margin-bottom: 1rem; font-size: 0.9rem;">
                Le fichier robots.txt indique aux robots ce qu'ils peuvent ou non explorer.
            </p>

            <div class="file-preview"><span class="comment"># Configuration robots.txt</span>

User-agent: *
Allow: /

<span class="comment"># Bloquer le back-office</span>
Disallow: /admin/

<span class="comment"># Sitemap</span>
Sitemap: <?= e($siteUrl) ?>/sitemap.xml</div>
        </div>
    </div>
</div>

<!-- Aperçu Sitemap -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📄 Aperçu du Sitemap.xml</h3>
    </div>
    <div class="card-body">
        <div class="file-preview"><?= htmlspecialchars($sitemap) ?></div>
    </div>
</div>

<!-- Conseils -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">💡 Conseils SEO technique</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
            <div>
                <h4 style="margin-bottom: 0.5rem;">📍 Soumettre à Google</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Soumettez votre sitemap dans <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a> pour accélérer l'indexation.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">🔄 Mise à jour</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Régénérez le sitemap après chaque modification importante de votre site.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">🚫 Exclusions</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Le back-office est automatiquement exclu. Ajoutez d'autres exclusions si nécessaire.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
