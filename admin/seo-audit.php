<?php
$pageTitle = 'Audit SEO';
require_once 'includes/header.php';

// Récupérer toutes les données
$pagesResult = supabase()->select('pages', 'select=*');
$pages = $pagesResult['success'] ? $pagesResult['data'] : [];

$postsResult = supabase()->select('blog_posts', 'select=*');
$posts = $postsResult['success'] ? $postsResult['data'] : [];

$mediaResult = supabase()->select('media', 'select=*');
$media = $mediaResult['success'] ? $mediaResult['data'] : [];

// Analyse SEO complète
$issues = [];
$warnings = [];
$passed = [];

// =====================================================
// ANALYSE DES PAGES
// =====================================================
foreach ($pages as $page) {
    $pageUrl = '/' . $page['slug'] . '.html';

    // Meta Title
    if (empty($page['meta_title'])) {
        $issues[] = [
            'type' => 'error',
            'category' => 'Meta Title',
            'page' => $page['title'],
            'url' => $pageUrl,
            'message' => 'Meta title manquant',
            'recommendation' => 'Ajoutez un meta title de 50-60 caractères',
            'edit_url' => 'page-edit.php?id=' . $page['id'] . '#seo'
        ];
    } else {
        $titleLen = strlen($page['meta_title']);
        if ($titleLen < 30) {
            $warnings[] = [
                'type' => 'warning',
                'category' => 'Meta Title',
                'page' => $page['title'],
                'url' => $pageUrl,
                'message' => 'Meta title trop court (' . $titleLen . ' car.)',
                'recommendation' => 'Allongez le titre à 50-60 caractères',
                'edit_url' => 'page-edit.php?id=' . $page['id'] . '#seo'
            ];
        } elseif ($titleLen > 60) {
            $warnings[] = [
                'type' => 'warning',
                'category' => 'Meta Title',
                'page' => $page['title'],
                'url' => $pageUrl,
                'message' => 'Meta title trop long (' . $titleLen . ' car.)',
                'recommendation' => 'Réduisez à 60 caractères maximum',
                'edit_url' => 'page-edit.php?id=' . $page['id'] . '#seo'
            ];
        } else {
            $passed[] = ['category' => 'Meta Title', 'page' => $page['title'], 'message' => 'Longueur optimale'];
        }
    }

    // Meta Description
    if (empty($page['meta_description'])) {
        $issues[] = [
            'type' => 'error',
            'category' => 'Meta Description',
            'page' => $page['title'],
            'url' => $pageUrl,
            'message' => 'Meta description manquante',
            'recommendation' => 'Ajoutez une description de 150-160 caractères',
            'edit_url' => 'page-edit.php?id=' . $page['id'] . '#seo'
        ];
    } else {
        $descLen = strlen($page['meta_description']);
        if ($descLen < 100) {
            $warnings[] = [
                'type' => 'warning',
                'category' => 'Meta Description',
                'page' => $page['title'],
                'url' => $pageUrl,
                'message' => 'Meta description trop courte (' . $descLen . ' car.)',
                'recommendation' => 'Allongez à 150-160 caractères',
                'edit_url' => 'page-edit.php?id=' . $page['id'] . '#seo'
            ];
        } elseif ($descLen > 160) {
            $warnings[] = [
                'type' => 'warning',
                'category' => 'Meta Description',
                'page' => $page['title'],
                'url' => $pageUrl,
                'message' => 'Meta description trop longue (' . $descLen . ' car.)',
                'recommendation' => 'Réduisez à 160 caractères maximum',
                'edit_url' => 'page-edit.php?id=' . $page['id'] . '#seo'
            ];
        } else {
            $passed[] = ['category' => 'Meta Description', 'page' => $page['title'], 'message' => 'Longueur optimale'];
        }
    }

    // H1 (hero_title)
    if (empty($page['hero_title'])) {
        $warnings[] = [
            'type' => 'warning',
            'category' => 'Balise H1',
            'page' => $page['title'],
            'url' => $pageUrl,
            'message' => 'H1 manquant ou vide',
            'recommendation' => 'Ajoutez un titre H1 unique et descriptif',
            'edit_url' => 'page-edit.php?id=' . $page['id']
        ];
    } else {
        $passed[] = ['category' => 'Balise H1', 'page' => $page['title'], 'message' => 'H1 présent'];
    }
}

// =====================================================
// ANALYSE DES ARTICLES
// =====================================================
foreach ($posts as $post) {
    $postUrl = '/blog/' . $post['slug'];

    if (empty($post['meta_title'])) {
        $issues[] = [
            'type' => 'error',
            'category' => 'Meta Title',
            'page' => $post['title'] . ' (Article)',
            'url' => $postUrl,
            'message' => 'Meta title manquant',
            'recommendation' => 'Ajoutez un meta title optimisé',
            'edit_url' => 'blog-edit.php?id=' . $post['id']
        ];
    }

    if (empty($post['meta_description'])) {
        $issues[] = [
            'type' => 'error',
            'category' => 'Meta Description',
            'page' => $post['title'] . ' (Article)',
            'url' => $postUrl,
            'message' => 'Meta description manquante',
            'recommendation' => 'Ajoutez une meta description',
            'edit_url' => 'blog-edit.php?id=' . $post['id']
        ];
    }

    // Contenu trop court
    $contentLength = strlen(strip_tags($post['content'] ?? ''));
    if ($contentLength < 300) {
        $warnings[] = [
            'type' => 'warning',
            'category' => 'Contenu',
            'page' => $post['title'] . ' (Article)',
            'url' => $postUrl,
            'message' => 'Contenu trop court (' . $contentLength . ' car.)',
            'recommendation' => 'Minimum 300 mots recommandé pour le SEO',
            'edit_url' => 'blog-edit.php?id=' . $post['id']
        ];
    }
}

// =====================================================
// ANALYSE DES IMAGES
// =====================================================
foreach ($media as $img) {
    if (empty($img['alt_text'])) {
        $issues[] = [
            'type' => 'error',
            'category' => 'Images',
            'page' => $img['original_name'] ?? $img['filename'],
            'url' => '',
            'message' => 'Texte alternatif (alt) manquant',
            'recommendation' => 'Ajoutez une description de l\'image',
            'edit_url' => 'media.php'
        ];
    }

    $fileSize = $img['file_size'] ?? 0;
    if ($fileSize > 500000) {
        $warnings[] = [
            'type' => 'warning',
            'category' => 'Performance',
            'page' => $img['original_name'] ?? $img['filename'],
            'url' => '',
            'message' => 'Image trop lourde (' . round($fileSize / 1024) . ' KB)',
            'recommendation' => 'Compressez l\'image (< 500 KB)',
            'edit_url' => 'media.php'
        ];
    }
}

// Calculs finaux
$totalChecks = count($issues) + count($warnings) + count($passed);
$score = $totalChecks > 0 ? round((count($passed) / $totalChecks) * 100) : 100;
$scoreColor = $score >= 80 ? '#22c55e' : ($score >= 50 ? '#f59e0b' : '#ef4444');
$scoreLabel = $score >= 80 ? 'Bon' : ($score >= 50 ? 'À améliorer' : 'Critique');
?>

<style>
.audit-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
}
.audit-score {
    display: flex;
    align-items: center;
    gap: 2rem;
    background: white;
    padding: 1.5rem 2rem;
    border-radius: 12px;
    border: 1px solid var(--gray-200);
}
.score-circle {
    position: relative;
    width: 100px;
    height: 100px;
}
.score-circle svg {
    transform: rotate(-90deg);
}
.score-circle .value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}
.score-circle .number {
    font-size: 1.75rem;
    font-weight: 700;
}
.score-circle .label {
    font-size: 0.75rem;
    color: var(--gray-500);
}
.audit-stats {
    display: flex;
    gap: 2rem;
}
.audit-stat {
    text-align: center;
}
.audit-stat .count {
    font-size: 1.5rem;
    font-weight: 700;
}
.audit-stat .label {
    font-size: 0.85rem;
    color: var(--gray-500);
}
.audit-stat.errors .count { color: #ef4444; }
.audit-stat.warnings .count { color: #f59e0b; }
.audit-stat.passed .count { color: #22c55e; }

.issues-section {
    margin-bottom: 2rem;
}
.issues-section h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}
.issue-card {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    padding: 1rem 1.25rem;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}
.issue-card.error {
    border-left: 4px solid #ef4444;
}
.issue-card.warning {
    border-left: 4px solid #f59e0b;
}
.issue-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.issue-card.error .issue-icon {
    background: #fee2e2;
    color: #ef4444;
}
.issue-card.warning .issue-icon {
    background: #fef3c7;
    color: #f59e0b;
}
.issue-content {
    flex: 1;
    min-width: 0;
}
.issue-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}
.issue-meta {
    font-size: 0.85rem;
    color: var(--gray-500);
    margin-bottom: 0.5rem;
}
.issue-recommendation {
    font-size: 0.9rem;
    color: var(--gray-600);
    background: var(--gray-50);
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
}
.issue-action {
    flex-shrink: 0;
}

.category-badge {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
    background: var(--gray-100);
    color: var(--gray-600);
    margin-right: 0.5rem;
}

.filters {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}
.filter-btn {
    padding: 0.5rem 1rem;
    border: 1px solid var(--gray-200);
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.filter-btn:hover {
    border-color: var(--primary);
}
.filter-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}
</style>

<div class="page-header">
    <h2 class="page-title">Audit SEO</h2>
    <p class="page-subtitle">Analyse complète de l'optimisation de votre site</p>
</div>

<!-- Score global -->
<div class="audit-score">
    <div class="score-circle">
        <svg width="100" height="100" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="45" fill="none" stroke="#e5e7eb" stroke-width="10"/>
            <circle cx="50" cy="50" r="45" fill="none" stroke="<?= $scoreColor ?>" stroke-width="10"
                    stroke-dasharray="<?= 283 * ($score / 100) ?> 283"
                    stroke-linecap="round"/>
        </svg>
        <div class="value">
            <div class="number"><?= $score ?></div>
            <div class="label"><?= $scoreLabel ?></div>
        </div>
    </div>
    <div class="audit-stats">
        <div class="audit-stat errors">
            <div class="count"><?= count($issues) ?></div>
            <div class="label">Erreurs</div>
        </div>
        <div class="audit-stat warnings">
            <div class="count"><?= count($warnings) ?></div>
            <div class="label">Avertissements</div>
        </div>
        <div class="audit-stat passed">
            <div class="count"><?= count($passed) ?></div>
            <div class="label">Validés</div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="filters">
    <button class="filter-btn active" onclick="filterIssues('all')">Tout (<?= count($issues) + count($warnings) ?>)</button>
    <button class="filter-btn" onclick="filterIssues('error')">Erreurs (<?= count($issues) ?>)</button>
    <button class="filter-btn" onclick="filterIssues('warning')">Avertissements (<?= count($warnings) ?>)</button>
</div>

<!-- Erreurs critiques -->
<?php if (!empty($issues)): ?>
<div class="issues-section" data-type="error">
    <h3><span style="color: #ef4444;">●</span> Erreurs à corriger (<?= count($issues) ?>)</h3>
    <?php foreach ($issues as $issue): ?>
    <div class="issue-card error">
        <div class="issue-icon">!</div>
        <div class="issue-content">
            <div class="issue-title">
                <span class="category-badge"><?= e($issue['category']) ?></span>
                <?= e($issue['message']) ?>
            </div>
            <div class="issue-meta">
                <?= e($issue['page']) ?>
                <?php if (!empty($issue['url'])): ?>
                <span style="color: var(--gray-400);">·</span> <?= e($issue['url']) ?>
                <?php endif; ?>
            </div>
            <div class="issue-recommendation">
                💡 <?= e($issue['recommendation']) ?>
            </div>
        </div>
        <div class="issue-action">
            <a href="<?= e($issue['edit_url']) ?>" class="btn btn-sm btn-primary">Corriger</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Avertissements -->
<?php if (!empty($warnings)): ?>
<div class="issues-section" data-type="warning">
    <h3><span style="color: #f59e0b;">●</span> Avertissements (<?= count($warnings) ?>)</h3>
    <?php foreach ($warnings as $warning): ?>
    <div class="issue-card warning">
        <div class="issue-icon">⚠</div>
        <div class="issue-content">
            <div class="issue-title">
                <span class="category-badge"><?= e($warning['category']) ?></span>
                <?= e($warning['message']) ?>
            </div>
            <div class="issue-meta">
                <?= e($warning['page']) ?>
                <?php if (!empty($warning['url'])): ?>
                <span style="color: var(--gray-400);">·</span> <?= e($warning['url']) ?>
                <?php endif; ?>
            </div>
            <div class="issue-recommendation">
                💡 <?= e($warning['recommendation']) ?>
            </div>
        </div>
        <div class="issue-action">
            <a href="<?= e($warning['edit_url']) ?>" class="btn btn-sm btn-secondary">Modifier</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($issues) && empty($warnings)): ?>
<div class="card">
    <div class="card-body" style="text-align: center; padding: 4rem;">
        <p style="font-size: 4rem; margin-bottom: 1rem;">🎉</p>
        <h3>Excellent travail !</h3>
        <p style="color: var(--gray-500);">Aucun problème SEO détecté. Votre site est bien optimisé.</p>
    </div>
</div>
<?php endif; ?>

<script>
function filterIssues(type) {
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    document.querySelectorAll('.issues-section').forEach(section => {
        if (type === 'all') {
            section.style.display = 'block';
        } else {
            section.style.display = section.dataset.type === type ? 'block' : 'none';
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
