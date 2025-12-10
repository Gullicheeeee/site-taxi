<?php
$pageTitle = 'Gestion des Images';
require_once 'includes/header.php';

$db = getDB();

// Upload d'image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($mimeType, $allowedTypes)) {
            if ($file['size'] <= MAX_UPLOAD_SIZE) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('img_') . '.' . strtolower($ext);
                $destination = UPLOAD_PATH . $filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $stmt = $db->prepare("INSERT INTO images (filename, original_name, alt_text, page_slug, section) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $filename,
                        $file['name'],
                        $_POST['alt_text'] ?? '',
                        $_POST['page_slug'] ?? '',
                        $_POST['section'] ?? ''
                    ]);
                    setFlash('success', 'Image uploadée avec succès !');
                } else {
                    setFlash('danger', 'Erreur lors de l\'upload');
                }
            } else {
                setFlash('danger', 'Image trop volumineuse (max 5MB)');
            }
        } else {
            setFlash('danger', 'Format non autorisé (JPG, PNG, GIF, WebP uniquement)');
        }
    }
    header('Location: images.php');
    exit;
}

// Suppression d'image
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT filename FROM images WHERE id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetch();

    if ($image) {
        $filepath = UPLOAD_PATH . $image['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $stmt = $db->prepare("DELETE FROM images WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Image supprimée');
    }
    header('Location: images.php');
    exit;
}

// Récupérer les images
$images = $db->query("SELECT * FROM images ORDER BY uploaded_at DESC")->fetchAll();
$pages = $db->query("SELECT slug, title FROM pages ORDER BY title")->fetchAll();
?>

<div class="page-header">
    <h2 class="page-title">Gestion des Images</h2>
    <p class="page-subtitle">Uploadez et gérez les images de votre site</p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Uploader une image</h3>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Image</label>
                    <div class="upload-zone" id="upload-zone">
                        <input type="file" name="image" id="image-input" accept="image/*" style="display: none;" required>
                        <div id="upload-preview">
                            <p style="font-size: 2rem; margin-bottom: 0.5rem;">📁</p>
                            <p>Cliquez ou glissez une image ici</p>
                            <p style="font-size: 0.875rem; color: var(--gray-500);">JPG, PNG, GIF, WebP - Max 5MB</p>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Texte alternatif (alt)</label>
                    <input type="text" name="alt_text" class="form-control" placeholder="Description de l'image">
                    <p class="form-help">Important pour le SEO et l'accessibilité</p>

                    <label class="form-label mt-2">Page associée</label>
                    <select name="page_slug" class="form-control">
                        <option value="">-- Aucune --</option>
                        <?php foreach ($pages as $p): ?>
                        <option value="<?= e($p['slug']) ?>"><?= e($p['title']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label mt-2">Section</label>
                    <input type="text" name="section" class="form-control" placeholder="Ex: hero, services, about">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                ⬆️ Uploader l'image
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Bibliothèque d'images (<?= count($images) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($images)): ?>
        <p style="text-align: center; color: var(--gray-500); padding: 2rem;">
            Aucune image uploadée pour le moment
        </p>
        <?php else: ?>
        <div class="image-grid">
            <?php foreach ($images as $img): ?>
            <div class="image-item" data-id="<?= $img['id'] ?>">
                <img src="uploads/<?= e($img['filename']) ?>" alt="<?= e($img['alt_text']) ?>">
                <div class="overlay">
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" class="btn btn-sm btn-primary copy-url" data-url="<?= UPLOAD_URL . e($img['filename']) ?>">
                            📋
                        </button>
                        <a href="?delete=<?= $img['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette image ?')">
                            🗑️
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Liste détaillée</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Aperçu</th>
                        <th>Nom</th>
                        <th>Alt</th>
                        <th>Page</th>
                        <th>URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($images as $img): ?>
                    <tr>
                        <td>
                            <img src="uploads/<?= e($img['filename']) ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                        </td>
                        <td><?= e($img['original_name']) ?></td>
                        <td><?= e($img['alt_text']) ?: '<em style="color: var(--gray-400);">Non défini</em>' ?></td>
                        <td><?= e($img['page_slug']) ?: '-' ?></td>
                        <td>
                            <code style="font-size: 0.75rem;"><?= UPLOAD_URL . e($img['filename']) ?></code>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-secondary copy-url" data-url="<?= UPLOAD_URL . e($img['filename']) ?>">
                                Copier URL
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Upload zone interactions
const uploadZone = document.getElementById('upload-zone');
const imageInput = document.getElementById('image-input');
const preview = document.getElementById('upload-preview');

uploadZone.addEventListener('click', () => imageInput.click());

uploadZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadZone.classList.add('dragover');
});

uploadZone.addEventListener('dragleave', () => {
    uploadZone.classList.remove('dragover');
});

uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        imageInput.files = e.dataTransfer.files;
        showPreview(e.dataTransfer.files[0]);
    }
});

imageInput.addEventListener('change', () => {
    if (imageInput.files.length) {
        showPreview(imageInput.files[0]);
    }
});

function showPreview(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        preview.innerHTML = `<img src="${e.target.result}" style="max-height: 150px; border-radius: 8px;">
            <p style="margin-top: 0.5rem;">${file.name}</p>`;
    };
    reader.readAsDataURL(file);
}

// Copy URL
document.querySelectorAll('.copy-url').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const url = window.location.origin + '/' + btn.dataset.url;
        navigator.clipboard.writeText(url).then(() => {
            const originalText = btn.innerHTML;
            btn.innerHTML = '✓';
            setTimeout(() => btn.innerHTML = originalText, 1500);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
