<?php
$pageTitle = 'Gestion du Blog';
require_once 'includes/header.php';

// Suppression d'article
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    supabase()->delete('blog_posts', 'id=eq.' . urlencode($id));
    setFlash('success', 'Article supprimé');
    header('Location: blog.php');
    exit;
}

// Toggle publication
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $result = supabase()->select('blog_posts', 'id=eq.' . urlencode($id));
    if ($result['success'] && !empty($result['data'])) {
        $post = $result['data'][0];
        $newStatus = !$post['is_published'];
        supabase()->update('blog_posts', 'id=eq.' . urlencode($id), [
            'is_published' => $newStatus,
            'published_at' => $newStatus ? date('c') : null
        ]);
        setFlash('success', $newStatus ? 'Article publié' : 'Article dépublié');
    }
    header('Location: blog.php');
    exit;
}

// Filtres
$filter = $_GET['filter'] ?? 'all';
$filterQuery = 'order=created_at.desc';
if ($filter === 'published') {
    $filterQuery = 'is_published=eq.true&order=created_at.desc';
} elseif ($filter === 'draft') {
    $filterQuery = 'is_published=eq.false&order=created_at.desc';
}

// Récupérer les articles
$result = supabase()->select('blog_posts', $filterQuery);
$posts = $result['success'] ? $result['data'] : [];

// Compter pour les filtres
$allResult = supabase()->select('blog_posts', 'select=id,is_published');
$allPosts = $allResult['success'] ? $allResult['data'] : [];
$totalAll = count($allPosts);
$totalPublished = count(array_filter($allPosts, fn($p) => $p['is_published']));
$totalDraft = $totalAll - $totalPublished;
?>

<style>
/* Style WordPress-like */
.wp-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}
.wp-title {
    font-size: 1.75rem;
    font-weight: 400;
    margin: 0;
}
.wp-filters {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid var(--gray-200);
    padding-bottom: 0.5rem;
}
.wp-filter {
    padding: 0.5rem 1rem;
    color: var(--gray-600);
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.9rem;
}
.wp-filter:hover {
    color: var(--primary);
}
.wp-filter.active {
    color: var(--primary);
    font-weight: 600;
}
.wp-filter .count {
    color: var(--gray-400);
    font-weight: 400;
}
.wp-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    overflow: hidden;
}
.wp-table th {
    background: var(--gray-50);
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--gray-700);
    border-bottom: 1px solid var(--gray-200);
}
.wp-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: top;
}
.wp-table tr:hover {
    background: var(--gray-50);
}
.wp-table tr:last-child td {
    border-bottom: none;
}
.post-title {
    font-weight: 600;
    color: var(--primary);
    text-decoration: none;
    font-size: 1rem;
}
.post-title:hover {
    text-decoration: underline;
}
.post-actions {
    display: none;
    margin-top: 0.5rem;
    font-size: 0.8rem;
}
.wp-table tr:hover .post-actions {
    display: block;
}
.post-actions a {
    color: var(--primary);
    text-decoration: none;
    margin-right: 0.75rem;
}
.post-actions a:hover {
    text-decoration: underline;
}
.post-actions a.delete {
    color: var(--danger);
}
.post-status {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}
.post-status.published {
    background: #dcfce7;
    color: #166534;
}
.post-status.draft {
    background: #fef3c7;
    color: #92400e;
}
.post-thumb {
    width: 60px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    background: var(--gray-100);
}
.post-thumb-placeholder {
    width: 60px;
    height: 40px;
    background: var(--gray-100);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-400);
    font-size: 1.2rem;
}
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--gray-500);
}
.empty-state h3 {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
    color: var(--gray-700);
}
.col-title { width: 40%; }
.col-category { width: 15%; }
.col-status { width: 10%; }
.col-date { width: 15%; }
.col-thumb { width: 80px; }
</style>

<div class="wp-header">
    <h1 class="wp-title">Articles</h1>
    <a href="blog-edit.php" class="btn btn-primary">+ Nouvel article</a>
</div>

<!-- Filtres -->
<div class="wp-filters">
    <a href="blog.php" class="wp-filter <?= $filter === 'all' ? 'active' : '' ?>">
        Tous <span class="count">(<?= $totalAll ?>)</span>
    </a>
    <a href="blog.php?filter=published" class="wp-filter <?= $filter === 'published' ? 'active' : '' ?>">
        Publiés <span class="count">(<?= $totalPublished ?>)</span>
    </a>
    <a href="blog.php?filter=draft" class="wp-filter <?= $filter === 'draft' ? 'active' : '' ?>">
        Brouillons <span class="count">(<?= $totalDraft ?>)</span>
    </a>
</div>

<?php if (empty($posts)): ?>
<div class="empty-state">
    <p style="font-size: 3rem; margin-bottom: 1rem;">📝</p>
    <h3>Aucun article <?= $filter !== 'all' ? 'dans cette catégorie' : '' ?></h3>
    <p>Commencez par créer votre premier article de blog.</p>
    <a href="blog-edit.php" class="btn btn-primary" style="margin-top: 1rem;">Créer un article</a>
</div>
<?php else: ?>

<table class="wp-table">
    <thead>
        <tr>
            <th class="col-thumb"></th>
            <th class="col-title">Titre</th>
            <th class="col-category">Catégorie</th>
            <th class="col-status">Statut</th>
            <th class="col-date">Date</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($posts as $post): ?>
        <tr>
            <td>
                <?php if (!empty($post['featured_image'])): ?>
                <img src="<?= e($post['featured_image']) ?>" alt="" class="post-thumb">
                <?php else: ?>
                <div class="post-thumb-placeholder">📄</div>
                <?php endif; ?>
            </td>
            <td>
                <a href="blog-edit.php?id=<?= e($post['id']) ?>" class="post-title">
                    <?= e($post['title']) ?>
                </a>
                <?php if (!empty($post['excerpt'])): ?>
                <p style="margin: 0.25rem 0 0; font-size: 0.85rem; color: var(--gray-500); line-height: 1.4;">
                    <?= e(mb_substr($post['excerpt'], 0, 80)) ?><?= mb_strlen($post['excerpt']) > 80 ? '...' : '' ?>
                </p>
                <?php endif; ?>
                <div class="post-actions">
                    <a href="blog-edit.php?id=<?= e($post['id']) ?>">Modifier</a>
                    <a href="?toggle=<?= e($post['id']) ?>">
                        <?= $post['is_published'] ? 'Dépublier' : 'Publier' ?>
                    </a>
                    <a href="#" onclick="previewPost('<?= e($post['id']) ?>'); return false;">Aperçu</a>
                    <a href="?delete=<?= e($post['id']) ?>" class="delete" onclick="return confirm('Supprimer cet article ?')">Supprimer</a>
                </div>
            </td>
            <td>
                <?php if (!empty($post['category'])): ?>
                <span style="color: var(--gray-600);"><?= e($post['category']) ?></span>
                <?php else: ?>
                <span style="color: var(--gray-400);">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($post['is_published']): ?>
                <span class="post-status published">Publié</span>
                <?php else: ?>
                <span class="post-status draft">Brouillon</span>
                <?php endif; ?>
            </td>
            <td>
                <span style="color: var(--gray-600); font-size: 0.9rem;">
                    <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                </span>
                <br>
                <span style="color: var(--gray-400); font-size: 0.8rem;">
                    <?= date('H:i', strtotime($post['created_at'])) ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php endif; ?>

<!-- Modal Aperçu -->
<div class="modal" id="preview-modal">
    <div class="modal-content" style="max-width: 700px; max-height: 85vh; overflow: hidden; display: flex; flex-direction: column;">
        <div class="modal-header">
            <h3>Aperçu de l'article</h3>
            <button type="button" class="modal-close" onclick="closePreview()">&times;</button>
        </div>
        <div class="modal-body" style="overflow-y: auto; flex: 1; padding: 0;">
            <div id="preview-content" style="padding: 1.5rem;">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
        <div class="modal-footer" style="padding: 1rem; border-top: 1px solid var(--gray-200); display: flex; justify-content: space-between;">
            <a id="preview-edit-link" href="#" class="btn btn-primary">Modifier</a>
            <button type="button" class="btn btn-secondary" onclick="closePreview()">Fermer</button>
        </div>
    </div>
</div>

<script>
// Données des articles
const postsData = {
<?php foreach ($posts as $p): ?>
    '<?= e($p['id']) ?>': {
        id: <?= json_encode($p['id']) ?>,
        title: <?= json_encode($p['title'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        slug: <?= json_encode($p['slug'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        excerpt: <?= json_encode($p['excerpt'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        content: <?= json_encode($p['content'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        category: <?= json_encode($p['category'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        featured_image: <?= json_encode($p['featured_image'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        meta_title: <?= json_encode($p['meta_title'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        meta_description: <?= json_encode($p['meta_description'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        published_at: <?= json_encode($p['published_at'] ?? null) ?>,
        is_published: <?= $p['is_published'] ? 'true' : 'false' ?>
    },
<?php endforeach; ?>
};

function previewPost(id) {
    const post = postsData[id];
    if (!post) return;

    const date = post.published_at
        ? new Date(post.published_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
        : 'Non publié';

    let html = '';

    // Image
    if (post.featured_image) {
        html += `<img src="${post.featured_image}" alt="" style="width: 100%; max-height: 250px; object-fit: cover; border-radius: 8px; margin-bottom: 1rem;">`;
    }

    // Titre et meta
    html += `
        <div style="margin-bottom: 1rem;">
            ${post.category ? `<span style="background: var(--primary); color: white; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem;">${post.category}</span>` : ''}
            <h2 style="margin: 0.5rem 0; font-size: 1.5rem;">${post.title}</h2>
            <p style="color: var(--gray-500); font-size: 0.85rem; margin: 0;">${date} · slug: ${post.slug}</p>
        </div>
    `;

    // Extrait
    if (post.excerpt) {
        html += `<p style="color: var(--gray-600); font-style: italic; border-left: 3px solid var(--primary); padding-left: 1rem; margin: 1rem 0;">${post.excerpt}</p>`;
    }

    // Contenu
    if (post.content) {
        html += `<div style="line-height: 1.7; color: var(--gray-700);">${post.content}</div>`;
    } else {
        html += `<p style="color: var(--gray-400); text-align: center; padding: 2rem;">Aucun contenu</p>`;
    }

    // SEO
    html += `
        <div style="background: var(--gray-50); padding: 1rem; border-radius: 8px; margin-top: 1.5rem;">
            <p style="font-size: 0.75rem; color: var(--gray-500); margin-bottom: 0.5rem;">Aperçu Google :</p>
            <p style="color: #1a0dab; font-size: 1rem; margin: 0;">${post.meta_title || post.title}</p>
            <p style="color: #006621; font-size: 0.85rem; margin: 0.25rem 0;">votresite.fr/blog/${post.slug}</p>
            <p style="color: #545454; font-size: 0.85rem; margin: 0;">${post.meta_description || post.excerpt || 'Pas de description'}</p>
        </div>
    `;

    document.getElementById('preview-content').innerHTML = html;
    document.getElementById('preview-edit-link').href = 'blog-edit.php?id=' + post.id;
    document.getElementById('preview-modal').classList.add('active');
}

function closePreview() {
    document.getElementById('preview-modal').classList.remove('active');
}

// Fermer avec clic extérieur ou Escape
document.getElementById('preview-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closePreview();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePreview();
});
</script>

<?php require_once 'includes/footer.php'; ?>
