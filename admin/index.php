<?php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

$db = getDB();

// Statistiques
$totalPages = $db->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$totalPosts = $db->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
$publishedPosts = $db->query("SELECT COUNT(*) FROM blog_posts WHERE is_published = 1")->fetchColumn();
$totalImages = $db->query("SELECT COUNT(*) FROM images")->fetchColumn();
$todayVisits = $db->query("SELECT COUNT(*) FROM visits WHERE DATE(visited_at) = DATE('now')")->fetchColumn();
$weekVisits = $db->query("SELECT COUNT(*) FROM visits WHERE visited_at >= datetime('now', '-7 days')")->fetchColumn();
$unreadContacts = $db->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0")->fetchColumn();

// Derniers articles
$recentPosts = $db->query("SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Dernières visites
$recentVisits = $db->query("SELECT page, COUNT(*) as count FROM visits WHERE visited_at >= datetime('now', '-24 hours') GROUP BY page ORDER BY count DESC LIMIT 5")->fetchAll();

// Derniers messages
$recentContacts = $db->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<div class="page-header">
    <h2 class="page-title">Bienvenue sur votre Back-Office</h2>
    <p class="page-subtitle">Gérez facilement le contenu de votre site Taxi Julien</p>
</div>

<!-- Stats rapides -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">📄</div>
        <div class="stat-value"><?= $totalPages ?></div>
        <div class="stat-label">Pages</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📝</div>
        <div class="stat-value"><?= $publishedPosts ?>/<?= $totalPosts ?></div>
        <div class="stat-label">Articles publiés</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🖼️</div>
        <div class="stat-value"><?= $totalImages ?></div>
        <div class="stat-label">Images</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">👁️</div>
        <div class="stat-value"><?= $todayVisits ?></div>
        <div class="stat-label">Visites aujourd'hui</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📈</div>
        <div class="stat-value"><?= $weekVisits ?></div>
        <div class="stat-label">Visites (7 jours)</div>
    </div>
    <div class="stat-card" style="<?= $unreadContacts > 0 ? 'background: rgba(66, 153, 225, 0.1);' : '' ?>">
        <div class="stat-icon">📬</div>
        <div class="stat-value"><?= $unreadContacts ?></div>
        <div class="stat-label">Messages non lus</div>
    </div>
</div>

<!-- Actions rapides -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Actions rapides</h3>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="pages.php" class="btn btn-primary">📄 Gérer le SEO</a>
            <a href="blog-edit.php" class="btn btn-primary">📝 Nouvel article</a>
            <a href="images.php" class="btn btn-secondary">🖼️ Uploader une image</a>
            <a href="tracking.php" class="btn btn-secondary">📊 Voir les stats</a>
            <a href="../index.html" target="_blank" class="btn btn-secondary">👁️ Voir le site</a>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Derniers articles -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Derniers articles</h3>
            <a href="blog.php" class="btn btn-sm btn-secondary">Voir tout</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentPosts)): ?>
            <p style="text-align: center; color: var(--gray-500); padding: 1rem;">
                Aucun article
            </p>
            <?php else: ?>
            <?php foreach ($recentPosts as $post): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--gray-200);">
                <div>
                    <strong><?= e($post['title']) ?></strong>
                    <br>
                    <small style="color: var(--gray-500);"><?= date('d/m/Y', strtotime($post['created_at'])) ?></small>
                </div>
                <?php if ($post['is_published']): ?>
                <span class="badge badge-success">Publié</span>
                <?php else: ?>
                <span class="badge badge-warning">Brouillon</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pages populaires (24h) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pages populaires (24h)</h3>
            <a href="tracking.php" class="btn btn-sm btn-secondary">Statistiques</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentVisits)): ?>
            <p style="text-align: center; color: var(--gray-500); padding: 1rem;">
                Aucune visite enregistrée
            </p>
            <?php else: ?>
            <?php foreach ($recentVisits as $visit): ?>
            <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--gray-200);">
                <span><?= e($visit['page'] ?: 'Accueil') ?></span>
                <span style="color: var(--gray-500);"><?= $visit['count'] ?> visites</span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Messages récents -->
<?php if (!empty($recentContacts)): ?>
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">Messages récents</h3>
        <a href="contacts.php" class="btn btn-sm btn-secondary">Voir tout</a>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentContacts as $contact): ?>
                <tr>
                    <td><strong><?= e($contact['name']) ?></strong></td>
                    <td><small><?= e(substr($contact['message'], 0, 50)) ?>...</small></td>
                    <td><small><?= date('d/m H:i', strtotime($contact['created_at'])) ?></small></td>
                    <td>
                        <?php if (!$contact['is_read']): ?>
                        <span class="badge badge-info">Nouveau</span>
                        <?php else: ?>
                        <span class="badge badge-success">Lu</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Guide de démarrage -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">Guide de démarrage rapide</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <div>
                <h4 style="margin-bottom: 0.5rem;">1. 📄 Optimisez le SEO</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Rendez-vous dans "Pages & SEO" pour modifier les meta-titres et descriptions de chaque page.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">2. 🖼️ Ajoutez des images</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Uploadez vos photos dans la bibliothèque d'images pour les utiliser dans vos articles.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">3. 📝 Créez du contenu</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Rédigez des articles de blog pour améliorer votre référencement et attirer plus de clients.
                </p>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">4. 📊 Suivez les stats</h4>
                <p style="color: var(--gray-600); font-size: 0.9rem;">
                    Consultez les statistiques pour voir quelles pages sont les plus visitées.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
