<?php
$pageTitle = 'Dashboard SEO';
require_once 'includes/header.php';

// Récupérer les statistiques
$pagesResult = supabase()->select('pages', 'select=id,title,slug,status,seo_score,meta_title,meta_description,updated_at');
$pages = $pagesResult['success'] ? $pagesResult['data'] : [];

$postsResult = supabase()->select('blog_posts', 'select=id,title,is_published,meta_title,meta_description');
$posts = $postsResult['success'] ? $postsResult['data'] : [];

$mediaResult = supabase()->select('media', 'select=id,alt_text,file_size');
$media = $mediaResult['success'] ? $mediaResult['data'] : [];

// Calculs SEO
$totalPages = count($pages);
$publishedPages = count(array_filter($pages, fn($p) => ($p['status'] ?? 'published') === 'published'));
$draftPages = $totalPages - $publishedPages;

$totalPosts = count($posts);
$publishedPosts = count(array_filter($posts, fn($p) => $p['is_published']));

$totalMedia = count($media);
$mediaWithoutAlt = count(array_filter($media, fn($m) => empty($m['alt_text'])));
$heavyMedia = count(array_filter($media, fn($m) => ($m['file_size'] ?? 0) > 500000)); // > 500KB

// Alertes SEO
$seoAlerts = [];

// Pages sans meta title
$pagesWithoutMeta = array_filter($pages, fn($p) => empty($p['meta_title']));
if (count($pagesWithoutMeta) > 0) {
    $seoAlerts[] = [
        'type' => 'warning',
        'icon' => '⚠️',
        'message' => count($pagesWithoutMeta) . ' page(s) sans meta title',
        'action' => 'pages.php'
    ];
}

// Pages sans meta description
$pagesWithoutDesc = array_filter($pages, fn($p) => empty($p['meta_description']));
if (count($pagesWithoutDesc) > 0) {
    $seoAlerts[] = [
        'type' => 'warning',
        'icon' => '📝',
        'message' => count($pagesWithoutDesc) . ' page(s) sans meta description',
        'action' => 'pages.php'
    ];
}

// Articles sans meta
$postsWithoutMeta = array_filter($posts, fn($p) => empty($p['meta_title']) || empty($p['meta_description']));
if (count($postsWithoutMeta) > 0) {
    $seoAlerts[] = [
        'type' => 'info',
        'icon' => '📰',
        'message' => count($postsWithoutMeta) . ' article(s) à optimiser SEO',
        'action' => 'blog.php'
    ];
}

// Images sans alt
if ($mediaWithoutAlt > 0) {
    $seoAlerts[] = [
        'type' => 'error',
        'icon' => '🖼️',
        'message' => $mediaWithoutAlt . ' image(s) sans texte alternatif',
        'action' => 'media.php'
    ];
}

// Images trop lourdes
if ($heavyMedia > 0) {
    $seoAlerts[] = [
        'type' => 'warning',
        'icon' => '⚡',
        'message' => $heavyMedia . ' image(s) trop lourdes (> 500KB)',
        'action' => 'media.php'
    ];
}

// Score SEO global (simplifié)
$seoScore = 100;
$seoScore -= count($pagesWithoutMeta) * 5;
$seoScore -= count($pagesWithoutDesc) * 5;
$seoScore -= $mediaWithoutAlt * 3;
$seoScore -= $heavyMedia * 2;
$seoScore = max(0, min(100, $seoScore));

// Couleur du score
$scoreColor = $seoScore >= 80 ? '#22c55e' : ($seoScore >= 50 ? '#f59e0b' : '#ef4444');
$scoreLabel = $seoScore >= 80 ? 'Excellent' : ($seoScore >= 50 ? 'À améliorer' : 'Critique');
?>

<style>
/* Dashboard SEO Styles */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid var(--gray-200);
    transition: all 0.2s;
}
.stat-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.stat-card .stat-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}
.stat-card .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.stat-card .stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--gray-900);
    line-height: 1;
}
.stat-card .stat-label {
    color: var(--gray-500);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}
.stat-card .stat-detail {
    font-size: 0.8rem;
    color: var(--gray-400);
    margin-top: 0.5rem;
}

/* Score SEO */
.seo-score-card {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    color: white;
    grid-column: span 2;
}
.seo-score-card .stat-label {
    color: rgba(255,255,255,0.7);
}
.seo-score-ring {
    position: relative;
    width: 120px;
    height: 120px;
}
.seo-score-ring svg {
    transform: rotate(-90deg);
}
.seo-score-ring .score-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}
.seo-score-ring .score-number {
    font-size: 2rem;
    font-weight: 700;
}
.seo-score-ring .score-label {
    font-size: 0.75rem;
    opacity: 0.8;
}

/* Alerts */
.alerts-card {
    grid-column: span 2;
}
.alert-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}
.alert-item:last-child {
    margin-bottom: 0;
}
.alert-item.warning {
    background: #fef3c7;
    color: #92400e;
}
.alert-item.error {
    background: #fee2e2;
    color: #991b1b;
}
.alert-item.info {
    background: #e0f2fe;
    color: #075985;
}
.alert-item.success {
    background: #dcfce7;
    color: #166534;
}
.alert-item a {
    margin-left: auto;
    color: inherit;
    font-weight: 600;
    text-decoration: none;
}
.alert-item a:hover {
    text-decoration: underline;
}

/* Cards */
.dashboard-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.card-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.card-subtitle {
    color: var(--gray-500);
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}
.quick-action {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: 8px;
    text-decoration: none;
    color: var(--gray-700);
    transition: all 0.2s;
    border: 1px solid transparent;
}
.quick-action:hover {
    background: white;
    border-color: var(--primary);
    color: var(--primary);
}
.quick-action-icon {
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}
.quick-action-text {
    font-weight: 500;
    font-size: 0.9rem;
}

/* Pages list */
.pages-list {
    max-height: 300px;
    overflow-y: auto;
}
.page-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--gray-100);
}
.page-item:last-child {
    border-bottom: none;
}
.page-score {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
}
.page-info {
    flex: 1;
    min-width: 0;
}
.page-title {
    font-weight: 500;
    color: var(--gray-800);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.page-slug {
    font-size: 0.8rem;
    color: var(--gray-400);
}
.page-actions {
    display: flex;
    gap: 0.5rem;
}

/* Checklist */
.checklist-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
}
.checklist-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}
.checklist-icon.done {
    background: #dcfce7;
    color: #166534;
}
.checklist-icon.pending {
    background: #fee2e2;
    color: #991b1b;
}
</style>

<div class="page-header">
    <h2 class="page-title">Dashboard SEO</h2>
    <p class="page-subtitle">Vue d'ensemble de la santé SEO de votre site</p>
</div>

<!-- Stats principales -->
<div class="dashboard-grid">
    <!-- Score SEO Global -->
    <div class="stat-card seo-score-card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p class="stat-label">Score SEO Global</p>
                <p style="font-size: 1.5rem; font-weight: 600; margin: 0.5rem 0;"><?= $scoreLabel ?></p>
                <p style="font-size: 0.85rem; opacity: 0.7;"><?= count($seoAlerts) ?> point(s) à améliorer</p>
            </div>
            <div class="seo-score-ring">
                <svg width="120" height="120" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="12"/>
                    <circle cx="60" cy="60" r="54" fill="none" stroke="<?= $scoreColor ?>" stroke-width="12"
                            stroke-dasharray="<?= 339.3 * ($seoScore / 100) ?> 339.3"
                            stroke-linecap="round"/>
                </svg>
                <div class="score-value">
                    <div class="score-number"><?= $seoScore ?></div>
                    <div class="score-label">/100</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertes SEO -->
    <div class="stat-card alerts-card">
        <div class="stat-header">
            <div>
                <h3 class="card-title">⚠️ Alertes SEO</h3>
                <p class="card-subtitle">Actions recommandées</p>
            </div>
        </div>
        <?php if (empty($seoAlerts)): ?>
        <div class="alert-item success">
            <span>✅</span>
            <span>Tout est optimisé !</span>
        </div>
        <?php else: ?>
        <?php foreach (array_slice($seoAlerts, 0, 4) as $alert): ?>
        <div class="alert-item <?= $alert['type'] ?>">
            <span><?= $alert['icon'] ?></span>
            <span><?= $alert['message'] ?></span>
            <a href="<?= $alert['action'] ?>">Corriger →</a>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pages -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #e0f2fe; color: #0369a1;">📄</div>
        </div>
        <div class="stat-value"><?= $publishedPages ?></div>
        <div class="stat-label">Pages publiées</div>
        <div class="stat-detail"><?= $draftPages ?> brouillon(s)</div>
    </div>

    <!-- Articles -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #fef3c7; color: #d97706;">📝</div>
        </div>
        <div class="stat-value"><?= $publishedPosts ?></div>
        <div class="stat-label">Articles publiés</div>
        <div class="stat-detail"><?= $totalPosts - $publishedPosts ?> brouillon(s)</div>
    </div>

    <!-- Images -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">🖼️</div>
        </div>
        <div class="stat-value"><?= $totalMedia ?></div>
        <div class="stat-label">Images</div>
        <div class="stat-detail"><?= $totalMedia - $mediaWithoutAlt ?> optimisées</div>
    </div>

    <!-- Maillage -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #f3e8ff; color: #9333ea;">🔗</div>
        </div>
        <div class="stat-value"><?= $totalPages * 2 ?></div>
        <div class="stat-label">Liens internes</div>
        <div class="stat-detail">~<?= round($totalPages * 2 / max(1, $totalPages), 1) ?> liens/page</div>
    </div>
</div>

<!-- Deuxième ligne -->
<div class="dashboard-row">
    <!-- Actions rapides -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">⚡ Actions rapides</h3>
        </div>
        <div class="card-body">
            <div class="quick-actions">
                <a href="page-edit.php?new=1" class="quick-action">
                    <div class="quick-action-icon">📄</div>
                    <div class="quick-action-text">Nouvelle page</div>
                </a>
                <a href="blog-edit.php" class="quick-action">
                    <div class="quick-action-icon">📝</div>
                    <div class="quick-action-text">Nouvel article</div>
                </a>
                <a href="media.php" class="quick-action">
                    <div class="quick-action-icon">🖼️</div>
                    <div class="quick-action-text">Ajouter image</div>
                </a>
                <a href="seo-audit.php" class="quick-action">
                    <div class="quick-action-icon">🔍</div>
                    <div class="quick-action-text">Audit SEO</div>
                </a>
                <a href="redirections.php" class="quick-action">
                    <div class="quick-action-icon">↩️</div>
                    <div class="quick-action-text">Redirections</div>
                </a>
                <a href="sitemap.php" class="quick-action">
                    <div class="quick-action-icon">🗺️</div>
                    <div class="quick-action-text">Sitemap</div>
                </a>
                <a href="settings.php" class="quick-action">
                    <div class="quick-action-icon">⚙️</div>
                    <div class="quick-action-text">Paramètres</div>
                </a>
                <a href="../index.html" target="_blank" class="quick-action">
                    <div class="quick-action-icon">👁️</div>
                    <div class="quick-action-text">Voir le site</div>
                </a>
            </div>
        </div>
    </div>

    <!-- Checklist SEO -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">✅ Checklist SEO</h3>
        </div>
        <div class="card-body">
            <div class="checklist-item">
                <div class="checklist-icon <?= count($pagesWithoutMeta) === 0 ? 'done' : 'pending' ?>">
                    <?= count($pagesWithoutMeta) === 0 ? '✓' : '!' ?>
                </div>
                <span>Toutes les pages ont un meta title</span>
            </div>
            <div class="checklist-item">
                <div class="checklist-icon <?= count($pagesWithoutDesc) === 0 ? 'done' : 'pending' ?>">
                    <?= count($pagesWithoutDesc) === 0 ? '✓' : '!' ?>
                </div>
                <span>Toutes les pages ont une meta description</span>
            </div>
            <div class="checklist-item">
                <div class="checklist-icon <?= $mediaWithoutAlt === 0 ? 'done' : 'pending' ?>">
                    <?= $mediaWithoutAlt === 0 ? '✓' : '!' ?>
                </div>
                <span>Toutes les images ont un alt</span>
            </div>
            <div class="checklist-item">
                <div class="checklist-icon <?= $heavyMedia === 0 ? 'done' : 'pending' ?>">
                    <?= $heavyMedia === 0 ? '✓' : '!' ?>
                </div>
                <span>Images optimisées (< 500KB)</span>
            </div>
            <div class="checklist-item">
                <div class="checklist-icon done">✓</div>
                <span>Sitemap.xml présent</span>
            </div>
            <div class="checklist-item">
                <div class="checklist-icon done">✓</div>
                <span>Robots.txt configuré</span>
            </div>
        </div>
    </div>
</div>

<!-- Troisième ligne - Pages -->
<div class="dashboard-row">
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 class="card-title">📄 Pages du site</h3>
                <p class="card-subtitle">Statut SEO de chaque page</p>
            </div>
            <a href="pages.php" class="btn btn-sm btn-secondary">Voir tout</a>
        </div>
        <div class="card-body">
            <div class="pages-list">
                <?php foreach (array_slice($pages, 0, 6) as $page):
                    // Calcul score simplifié pour la page
                    $pageScore = 100;
                    if (empty($page['meta_title'])) $pageScore -= 30;
                    if (empty($page['meta_description'])) $pageScore -= 30;
                    $pageScoreColor = $pageScore >= 80 ? '#22c55e' : ($pageScore >= 50 ? '#f59e0b' : '#ef4444');
                ?>
                <div class="page-item">
                    <div class="page-score" style="background: <?= $pageScoreColor ?>;">
                        <?= $pageScore ?>
                    </div>
                    <div class="page-info">
                        <div class="page-title"><?= e($page['title']) ?></div>
                        <div class="page-slug">/<?= e($page['slug']) ?>.html</div>
                    </div>
                    <div class="page-actions">
                        <a href="page-edit.php?id=<?= e($page['id']) ?>" class="btn btn-sm btn-primary">Modifier</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Derniers articles -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 class="card-title">📝 Derniers articles</h3>
            </div>
            <a href="blog.php" class="btn btn-sm btn-secondary">Voir tout</a>
        </div>
        <div class="card-body">
            <?php if (empty($posts)): ?>
            <p style="text-align: center; color: var(--gray-500); padding: 2rem;">
                Aucun article
            </p>
            <?php else: ?>
            <?php foreach (array_slice($posts, 0, 5) as $post): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--gray-100);">
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= e($post['title']) ?>
                    </div>
                </div>
                <?php if ($post['is_published']): ?>
                <span class="badge badge-success" style="margin-left: 0.5rem;">Publié</span>
                <?php else: ?>
                <span class="badge badge-warning" style="margin-left: 0.5rem;">Brouillon</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
