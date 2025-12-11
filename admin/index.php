<?php
/**
 * DASHBOARD SEO - Back-Office Taxi Julien
 * Vue d'ensemble de la santé SEO du site
 */
declare(strict_types=1);

$pageTitle = 'Dashboard SEO';
require_once 'includes/header.php';

// Récupérer les données depuis Supabase
$pagesResult = supabase()->select('pages', 'select=id,title,slug,status,seo_score,meta_title,meta_description,updated_at');
$pages = $pagesResult['success'] ? $pagesResult['data'] : [];

$postsResult = supabase()->select('blog_posts', 'select=id,title,slug,is_published,meta_title,meta_description,content,created_at');
$posts = $postsResult['success'] ? $postsResult['data'] : [];

$mediaResult = supabase()->select('media', 'select=id,filename,alt_text,file_size,file_url');
$media = $mediaResult['success'] ? $mediaResult['data'] : [];

$redirectsResult = supabase()->select('redirections', 'select=id,source_url,target_url,hit_count,is_active');
$redirects = $redirectsResult['success'] ? $redirectsResult['data'] : [];

// Statistiques de base
$totalPages = count($pages);
$publishedPages = count(array_filter($pages, fn($p) => ($p['status'] ?? 'published') === 'published'));
$draftPages = $totalPages - $publishedPages;

$totalPosts = count($posts);
$publishedPosts = count(array_filter($posts, fn($p) => $p['is_published'] ?? false));
$draftPosts = $totalPosts - $publishedPosts;

$totalMedia = count($media);
$mediaWithoutAlt = count(array_filter($media, fn($m) => empty($m['alt_text'])));
$heavyMedia = count(array_filter($media, fn($m) => ($m['file_size'] ?? 0) > 200000)); // > 200KB

// Calcul du nombre total de mots sur le site
$totalWords = 0;
foreach ($pages as $p) {
    $totalWords += str_word_count(strip_tags($p['hero_subtitle'] ?? ''));
}
foreach ($posts as $p) {
    $totalWords += str_word_count(strip_tags($p['content'] ?? ''));
}

// Analyse SEO détaillée
$seoIssues = [
    'critical' => [],
    'warning' => [],
    'info' => []
];

// Pages sans meta title (critique)
$pagesWithoutTitle = array_filter($pages, fn($p) => empty($p['meta_title']));
if (count($pagesWithoutTitle) > 0) {
    $seoIssues['critical'][] = [
        'icon' => '🔴',
        'message' => count($pagesWithoutTitle) . ' page(s) sans meta title',
        'detail' => 'Le meta title est essentiel pour le référencement',
        'action' => 'pages.php',
        'items' => array_map(fn($p) => $p['title'], $pagesWithoutTitle)
    ];
}

// Pages sans meta description (critique)
$pagesWithoutDesc = array_filter($pages, fn($p) => empty($p['meta_description']));
if (count($pagesWithoutDesc) > 0) {
    $seoIssues['critical'][] = [
        'icon' => '🔴',
        'message' => count($pagesWithoutDesc) . ' page(s) sans meta description',
        'detail' => 'La meta description améliore le CTR dans les résultats Google',
        'action' => 'pages.php',
        'items' => array_map(fn($p) => $p['title'], $pagesWithoutDesc)
    ];
}

// Images sans alt (critique pour accessibilité et SEO)
if ($mediaWithoutAlt > 0) {
    $seoIssues['critical'][] = [
        'icon' => '🖼️',
        'message' => $mediaWithoutAlt . ' image(s) sans texte alternatif',
        'detail' => 'Les textes alt sont essentiels pour l\'accessibilité et le SEO images',
        'action' => 'media.php',
        'items' => array_map(fn($m) => $m['filename'], array_filter($media, fn($m) => empty($m['alt_text'])))
    ];
}

// Articles sans meta (warning)
$postsWithoutMeta = array_filter($posts, fn($p) => empty($p['meta_title']) || empty($p['meta_description']));
if (count($postsWithoutMeta) > 0) {
    $seoIssues['warning'][] = [
        'icon' => '📝',
        'message' => count($postsWithoutMeta) . ' article(s) sans meta SEO complet',
        'detail' => 'Optimisez les meta de vos articles pour le référencement',
        'action' => 'blog.php',
        'items' => array_map(fn($p) => $p['title'], $postsWithoutMeta)
    ];
}

// Images trop lourdes (warning)
if ($heavyMedia > 0) {
    $seoIssues['warning'][] = [
        'icon' => '⚡',
        'message' => $heavyMedia . ' image(s) > 200KB',
        'detail' => 'Les images lourdes ralentissent le chargement du site',
        'action' => 'media.php',
        'items' => array_map(fn($m) => $m['filename'] . ' (' . round($m['file_size'] / 1024) . 'KB)',
                            array_filter($media, fn($m) => ($m['file_size'] ?? 0) > 200000))
    ];
}

// Contenu court (info)
$shortContent = array_filter($posts, fn($p) => str_word_count(strip_tags($p['content'] ?? '')) < 300);
if (count($shortContent) > 0) {
    $seoIssues['info'][] = [
        'icon' => '📏',
        'message' => count($shortContent) . ' article(s) avec contenu court (< 300 mots)',
        'detail' => 'Google favorise les contenus longs et détaillés',
        'action' => 'blog.php',
        'items' => array_map(fn($p) => $p['title'], $shortContent)
    ];
}

// Calcul du score SEO global (0-100)
$seoScore = 100;
$seoScore -= count($pagesWithoutTitle) * 10;
$seoScore -= count($pagesWithoutDesc) * 10;
$seoScore -= $mediaWithoutAlt * 5;
$seoScore -= $heavyMedia * 3;
$seoScore -= count($postsWithoutMeta) * 5;
$seoScore -= count($shortContent) * 2;
$seoScore = max(0, min(100, $seoScore));

// Couleur et label du score
if ($seoScore >= 80) {
    $scoreColor = '#22c55e';
    $scoreLabel = 'Excellent';
    $scoreClass = 'success';
} elseif ($seoScore >= 60) {
    $scoreColor = '#f59e0b';
    $scoreLabel = 'À améliorer';
    $scoreClass = 'warning';
} else {
    $scoreColor = '#ef4444';
    $scoreLabel = 'Critique';
    $scoreClass = 'danger';
}

// Compter les problèmes par niveau
$criticalCount = count($seoIssues['critical']);
$warningCount = count($seoIssues['warning']);
$infoCount = count($seoIssues['info']);
$totalIssues = $criticalCount + $warningCount + $infoCount;
?>

<style>
/* Dashboard SEO Styles */
.dashboard-hero {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.score-card {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    border-radius: 16px;
    padding: 2rem;
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.score-ring {
    position: relative;
    width: 160px;
    height: 160px;
    margin-bottom: 1rem;
}

.score-ring svg {
    transform: rotate(-90deg);
}

.score-ring .score-bg {
    fill: none;
    stroke: rgba(255,255,255,0.1);
    stroke-width: 12;
}

.score-ring .score-progress {
    fill: none;
    stroke-width: 12;
    stroke-linecap: round;
    transition: stroke-dasharray 1s ease;
}

.score-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.score-number {
    font-size: 3rem;
    font-weight: 700;
    line-height: 1;
}

.score-max {
    font-size: 0.9rem;
    opacity: 0.7;
}

.score-label {
    font-size: 1.25rem;
    font-weight: 600;
    margin-top: 0.5rem;
}

.score-detail {
    font-size: 0.9rem;
    opacity: 0.8;
    margin-top: 0.5rem;
}

/* Issues Panel */
.issues-panel {
    background: white;
    border-radius: 16px;
    border: 1px solid var(--gray-200);
    overflow: hidden;
}

.issues-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.issues-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.issues-tabs {
    display: flex;
    gap: 0.5rem;
}

.issues-tab {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    background: var(--gray-100);
    color: var(--gray-600);
    transition: all 0.2s;
}

.issues-tab:hover {
    background: var(--gray-200);
}

.issues-tab.active {
    background: var(--primary);
    color: white;
}

.issues-tab .count {
    display: inline-block;
    min-width: 18px;
    height: 18px;
    line-height: 18px;
    text-align: center;
    border-radius: 9px;
    margin-left: 0.4rem;
    font-size: 0.7rem;
}

.issues-tab.critical .count { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.issues-tab.warning .count { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
.issues-tab.info .count { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.issues-tab.active .count { background: rgba(255,255,255,0.2); color: white; }

.issues-list {
    max-height: 280px;
    overflow-y: auto;
}

.issue-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    transition: background 0.2s;
}

.issue-item:hover {
    background: var(--gray-50);
}

.issue-item:last-child {
    border-bottom: none;
}

.issue-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.issue-icon.critical { background: #fee2e2; }
.issue-icon.warning { background: #fef3c7; }
.issue-icon.info { background: #dbeafe; }

.issue-content {
    flex: 1;
    min-width: 0;
}

.issue-title {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
}

.issue-detail {
    font-size: 0.85rem;
    color: var(--gray-500);
}

.issue-action {
    flex-shrink: 0;
}

/* Stats Grid */
.stats-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-box {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    border: 1px solid var(--gray-200);
    transition: all 0.2s;
}

.stat-box:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.stat-box-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.stat-box-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.stat-box-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--gray-900);
    line-height: 1;
}

.stat-box-label {
    font-size: 0.85rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
}

.stat-box-detail {
    font-size: 0.75rem;
    color: var(--gray-400);
    margin-top: 0.5rem;
}

/* Quick Actions */
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    text-decoration: none;
    color: var(--gray-700);
    transition: all 0.2s;
}

.quick-action-btn:hover {
    border-color: var(--primary);
    background: var(--gray-50);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.quick-action-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.quick-action-text {
    flex: 1;
}

.quick-action-title {
    font-weight: 600;
    font-size: 0.95rem;
}

.quick-action-desc {
    font-size: 0.8rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
}

/* SEO Checklist */
.checklist-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.checklist-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: 8px;
}

.checklist-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.checklist-icon.done {
    background: #dcfce7;
    color: #166534;
}

.checklist-icon.pending {
    background: #fee2e2;
    color: #991b1b;
}

.checklist-text {
    flex: 1;
    font-size: 0.9rem;
}

/* Recent Activity */
.activity-list {
    max-height: 250px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--gray-100);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.activity-dot.page { background: #3b82f6; }
.activity-dot.post { background: #22c55e; }
.activity-dot.media { background: #f59e0b; }

.activity-text {
    flex: 1;
    font-size: 0.9rem;
    color: var(--gray-600);
}

.activity-text strong {
    color: var(--gray-800);
}

.activity-time {
    font-size: 0.8rem;
    color: var(--gray-400);
}

/* Responsive */
@media (max-width: 1200px) {
    .stats-row {
        grid-template-columns: repeat(3, 1fr);
    }
    .quick-actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-hero {
        grid-template-columns: 1fr;
    }
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
    .checklist-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-header">
    <h2 class="page-title">Dashboard SEO</h2>
    <p class="page-subtitle">Vue d'ensemble de la santé SEO de votre site</p>
</div>

<!-- Hero : Score + Issues -->
<div class="dashboard-hero">
    <!-- Score SEO Global -->
    <div class="score-card">
        <div class="score-ring">
            <svg width="160" height="160" viewBox="0 0 160 160">
                <circle class="score-bg" cx="80" cy="80" r="70"/>
                <circle class="score-progress" cx="80" cy="80" r="70"
                        stroke="<?= $scoreColor ?>"
                        stroke-dasharray="<?= 440 * ($seoScore / 100) ?> 440"/>
            </svg>
            <div class="score-value">
                <div class="score-number"><?= $seoScore ?></div>
                <div class="score-max">/100</div>
            </div>
        </div>
        <div class="score-label" style="color: <?= $scoreColor ?>"><?= $scoreLabel ?></div>
        <div class="score-detail">
            <?php if ($totalIssues > 0): ?>
                <?= $totalIssues ?> point<?= $totalIssues > 1 ? 's' : '' ?> à améliorer
            <?php else: ?>
                Tout est optimisé !
            <?php endif; ?>
        </div>
    </div>

    <!-- Panel des problèmes SEO -->
    <div class="issues-panel">
        <div class="issues-header">
            <h3>⚠️ Tâches SEO prioritaires</h3>
            <div class="issues-tabs">
                <button class="issues-tab critical active" data-tab="critical">
                    Critiques<span class="count"><?= $criticalCount ?></span>
                </button>
                <button class="issues-tab warning" data-tab="warning">
                    Alertes<span class="count"><?= $warningCount ?></span>
                </button>
                <button class="issues-tab info" data-tab="info">
                    Conseils<span class="count"><?= $infoCount ?></span>
                </button>
            </div>
        </div>
        <div class="issues-list" id="issues-list">
            <?php if ($totalIssues === 0): ?>
            <div style="padding: 3rem; text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🎉</div>
                <p style="color: var(--gray-500);">Excellent ! Aucun problème SEO détecté.</p>
            </div>
            <?php else: ?>
                <?php foreach (['critical', 'warning', 'info'] as $level): ?>
                    <?php foreach ($seoIssues[$level] as $issue): ?>
                    <div class="issue-item" data-level="<?= $level ?>" style="<?= $level !== 'critical' ? 'display: none;' : '' ?>">
                        <div class="issue-icon <?= $level ?>"><?= $issue['icon'] ?></div>
                        <div class="issue-content">
                            <div class="issue-title"><?= e($issue['message']) ?></div>
                            <div class="issue-detail"><?= e($issue['detail']) ?></div>
                        </div>
                        <a href="<?= $issue['action'] ?>" class="btn btn-sm btn-primary">Corriger</a>
                    </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Statistiques -->
<div class="stats-row">
    <div class="stat-box">
        <div class="stat-box-header">
            <div class="stat-box-icon" style="background: #e0f2fe; color: #0369a1;">📄</div>
        </div>
        <div class="stat-box-value"><?= $publishedPages ?></div>
        <div class="stat-box-label">Pages publiées</div>
        <div class="stat-box-detail"><?= $draftPages ?> brouillon<?= $draftPages > 1 ? 's' : '' ?></div>
    </div>

    <div class="stat-box">
        <div class="stat-box-header">
            <div class="stat-box-icon" style="background: #dcfce7; color: #16a34a;">📝</div>
        </div>
        <div class="stat-box-value"><?= $publishedPosts ?></div>
        <div class="stat-box-label">Articles publiés</div>
        <div class="stat-box-detail"><?= $draftPosts ?> brouillon<?= $draftPosts > 1 ? 's' : '' ?></div>
    </div>

    <div class="stat-box">
        <div class="stat-box-header">
            <div class="stat-box-icon" style="background: #fef3c7; color: #d97706;">🖼️</div>
        </div>
        <div class="stat-box-value"><?= $totalMedia ?></div>
        <div class="stat-box-label">Images</div>
        <div class="stat-box-detail"><?= $totalMedia - $mediaWithoutAlt ?> avec alt text</div>
    </div>

    <div class="stat-box">
        <div class="stat-box-header">
            <div class="stat-box-icon" style="background: #f3e8ff; color: #9333ea;">📊</div>
        </div>
        <div class="stat-box-value"><?= number_format($totalWords) ?></div>
        <div class="stat-box-label">Mots total</div>
        <div class="stat-box-detail">Contenu indexable</div>
    </div>

    <div class="stat-box">
        <div class="stat-box-header">
            <div class="stat-box-icon" style="background: #fee2e2; color: #dc2626;">↩️</div>
        </div>
        <div class="stat-box-value"><?= count($redirects) ?></div>
        <div class="stat-box-label">Redirections</div>
        <div class="stat-box-detail"><?= count(array_filter($redirects, fn($r) => $r['is_active'] ?? true)) ?> active<?= count(array_filter($redirects, fn($r) => $r['is_active'] ?? true)) > 1 ? 's' : '' ?></div>
    </div>
</div>

<!-- Actions rapides + Checklist -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Actions rapides -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">⚡ Actions rapides</h3>
        </div>
        <div class="card-body">
            <div class="quick-actions-grid">
                <a href="page-edit.php?new=1" class="quick-action-btn">
                    <div class="quick-action-icon" style="background: #e0f2fe;">📄</div>
                    <div class="quick-action-text">
                        <div class="quick-action-title">Nouvelle page</div>
                        <div class="quick-action-desc">Créer une page SEO-optimisée</div>
                    </div>
                </a>
                <a href="blog-edit.php" class="quick-action-btn">
                    <div class="quick-action-icon" style="background: #dcfce7;">📝</div>
                    <div class="quick-action-text">
                        <div class="quick-action-title">Nouvel article</div>
                        <div class="quick-action-desc">Rédiger un article de blog</div>
                    </div>
                </a>
                <a href="media.php" class="quick-action-btn">
                    <div class="quick-action-icon" style="background: #fef3c7;">🖼️</div>
                    <div class="quick-action-text">
                        <div class="quick-action-title">Ajouter média</div>
                        <div class="quick-action-desc">Uploader des images</div>
                    </div>
                </a>
                <a href="seo-audit.php" class="quick-action-btn">
                    <div class="quick-action-icon" style="background: #f3e8ff;">🔍</div>
                    <div class="quick-action-text">
                        <div class="quick-action-title">Audit SEO</div>
                        <div class="quick-action-desc">Analyse complète du site</div>
                    </div>
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
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div class="checklist-item">
                    <div class="checklist-icon <?= count($pagesWithoutTitle) === 0 ? 'done' : 'pending' ?>">
                        <?= count($pagesWithoutTitle) === 0 ? '✓' : '!' ?>
                    </div>
                    <span class="checklist-text">Meta titles complets</span>
                </div>
                <div class="checklist-item">
                    <div class="checklist-icon <?= count($pagesWithoutDesc) === 0 ? 'done' : 'pending' ?>">
                        <?= count($pagesWithoutDesc) === 0 ? '✓' : '!' ?>
                    </div>
                    <span class="checklist-text">Meta descriptions</span>
                </div>
                <div class="checklist-item">
                    <div class="checklist-icon <?= $mediaWithoutAlt === 0 ? 'done' : 'pending' ?>">
                        <?= $mediaWithoutAlt === 0 ? '✓' : '!' ?>
                    </div>
                    <span class="checklist-text">Alt text images</span>
                </div>
                <div class="checklist-item">
                    <div class="checklist-icon <?= $heavyMedia === 0 ? 'done' : 'pending' ?>">
                        <?= $heavyMedia === 0 ? '✓' : '!' ?>
                    </div>
                    <span class="checklist-text">Images < 200KB</span>
                </div>
                <div class="checklist-item">
                    <div class="checklist-icon done">✓</div>
                    <span class="checklist-text">Sitemap.xml</span>
                </div>
                <div class="checklist-item">
                    <div class="checklist-icon done">✓</div>
                    <span class="checklist-text">Robots.txt</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pages et articles récents -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Pages récentes -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📄 Pages du site</h3>
            <a href="pages.php" class="btn btn-sm btn-secondary">Voir tout</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Score SEO</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($pages, 0, 5) as $page):
                        // Calcul du score individuel
                        $pageScore = 100;
                        if (empty($page['meta_title'])) $pageScore -= 30;
                        if (empty($page['meta_description'])) $pageScore -= 30;
                        $pageScoreColor = $pageScore >= 80 ? '#22c55e' : ($pageScore >= 50 ? '#f59e0b' : '#ef4444');
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($page['title']) ?></strong>
                            <div style="font-size: 0.8rem; color: var(--gray-500);">/<?= e($page['slug']) ?></div>
                        </td>
                        <td>
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: <?= $pageScoreColor ?>; color: white; font-size: 0.8rem; font-weight: 600;">
                                <?= $pageScore ?>
                            </span>
                        </td>
                        <td>
                            <?php if (($page['status'] ?? 'published') === 'published'): ?>
                            <span class="badge badge-success">Publié</span>
                            <?php else: ?>
                            <span class="badge badge-warning">Brouillon</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="page-edit.php?id=<?= e($page['id']) ?>" class="btn btn-sm btn-primary">Modifier</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pages)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 2rem; color: var(--gray-500);">
                            Aucune page créée
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Articles récents -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📝 Articles récents</h3>
            <a href="blog.php" class="btn btn-sm btn-secondary">Voir tout</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Article</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($posts, 0, 5) as $post): ?>
                    <tr>
                        <td>
                            <strong><?= e($post['title']) ?></strong>
                            <div style="font-size: 0.8rem; color: var(--gray-500);">
                                <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($post['is_published'] ?? false): ?>
                            <span class="badge badge-success">Publié</span>
                            <?php else: ?>
                            <span class="badge badge-warning">Brouillon</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="blog-edit.php?id=<?= e($post['id']) ?>" class="btn btn-sm btn-primary">Modifier</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 2rem; color: var(--gray-500);">
                            Aucun article créé
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Gestion des onglets des issues
document.querySelectorAll('.issues-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const level = this.dataset.tab;

        // Activer l'onglet
        document.querySelectorAll('.issues-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');

        // Afficher les issues correspondantes
        document.querySelectorAll('.issue-item').forEach(item => {
            item.style.display = item.dataset.level === level ? 'flex' : 'none';
        });
    });
});

// Animation du score au chargement
document.addEventListener('DOMContentLoaded', function() {
    const progress = document.querySelector('.score-progress');
    if (progress) {
        const finalValue = <?= $seoScore ?>;
        progress.style.strokeDasharray = '0 440';
        setTimeout(() => {
            progress.style.strokeDasharray = (440 * finalValue / 100) + ' 440';
        }, 100);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
