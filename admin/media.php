<?php
$pageTitle = 'Gestion des Images';
require_once 'includes/header.php';

// Upload d'image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($mimeType, $allowedTypes)) {
            if ($file['size'] <= 5 * 1024 * 1024) { // 5MB max
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('img_') . '.' . strtolower($ext);

                // Upload vers Supabase Storage
                $result = supabase()->uploadFile('images', $filename, $file['tmp_name'], $mimeType);

                if ($result['success']) {
                    // Enregistrer dans la table media
                    supabase()->insert('media', [
                        'filename' => $filename,
                        'original_name' => $file['name'],
                        'file_path' => $filename,
                        'file_url' => $result['url'],
                        'file_type' => $mimeType,
                        'file_size' => $file['size'],
                        'alt_text' => $_POST['alt_text'] ?? ''
                    ]);
                    setFlash('success', 'Image uploadée avec succès !');
                } else {
                    setFlash('danger', 'Erreur upload Supabase: ' . ($result['error'] ?? 'Inconnu'));
                }
            } else {
                setFlash('danger', 'Image trop volumineuse (max 5MB)');
            }
        } else {
            setFlash('danger', 'Format non autorisé (JPG, PNG, GIF, WebP)');
        }
    } else {
        setFlash('danger', 'Erreur lors de l\'upload');
    }
    header('Location: media.php');
    exit;
}

// Suppression d'image
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Récupérer l'image
    $result = supabase()->select('media', 'id=eq.' . urlencode($id));
    if ($result['success'] && !empty($result['data'])) {
        $media = $result['data'][0];

        // Supprimer du Storage
        supabase()->deleteFile('images', $media['file_path']);

        // Supprimer de la DB
        supabase()->delete('media', 'id=eq.' . urlencode($id));

        setFlash('success', 'Image supprimée');
    }
    header('Location: media.php');
    exit;
}

// Mise à jour alt text
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_alt'])) {
    $id = $_POST['media_id'] ?? '';
    $altText = trim($_POST['alt_text'] ?? '');

    if ($id) {
        supabase()->update('media', 'id=eq.' . urlencode($id), ['alt_text' => $altText]);
        setFlash('success', 'Texte alternatif mis à jour');
    }
    header('Location: media.php');
    exit;
}

// Récupérer les images
$result = supabase()->select('media', 'order=uploaded_at.desc');
$mediaList = $result['success'] ? $result['data'] : [];
?>

<div class="page-header">
    <h2 class="page-title">Gestion des Images</h2>
    <p class="page-subtitle">Uploadez et gérez vos images sur Supabase Storage</p>
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
                            <p style="font-size: 2.5rem; margin-bottom: 0.5rem;">📁</p>
                            <p style="font-weight: 600;">Cliquez ou glissez une image ici</p>
                            <p style="font-size: 0.875rem; color: var(--gray-500);">JPG, PNG, GIF, WebP - Max 5MB</p>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Texte alternatif (alt)</label>
                    <input type="text" name="alt_text" class="form-control" placeholder="Description de l'image pour le SEO">
                    <p class="form-help">Important pour le référencement et l'accessibilité</p>

                    <button type="submit" class="btn btn-primary mt-3" style="width: 100%;">
                        ⬆️ Uploader l'image
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Bibliothèque d'images (<?= count($mediaList) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($mediaList)): ?>
        <div style="text-align: center; padding: 3rem;">
            <p style="font-size: 3rem; margin-bottom: 1rem;">🖼️</p>
            <p style="color: var(--gray-500);">Aucune image uploadée</p>
        </div>
        <?php else: ?>
        <div class="image-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
            <?php foreach ($mediaList as $media): ?>
            <div class="image-item" style="aspect-ratio: auto; padding: 0.5rem; border: 1px solid var(--gray-200); border-radius: 8px;">
                <img src="<?= e($media['file_url']) ?>" alt="<?= e($media['alt_text']) ?>"
                     style="width: 100%; height: 150px; object-fit: cover; border-radius: 6px; cursor: pointer;"
                     onclick="copyToClipboard('<?= e($media['file_url']) ?>')">

                <div style="padding: 0.5rem 0;">
                    <p style="font-size: 0.75rem; color: var(--gray-500); margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <?= e($media['original_name']) ?>
                    </p>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn btn-sm btn-secondary" style="flex: 1;"
                            onclick="copyToClipboard('<?= e($media['file_url']) ?>')">
                        📋 Copier URL
                    </button>
                    <a href="?delete=<?= e($media['id']) ?>" class="btn btn-sm btn-danger"
                       onclick="return confirm('Supprimer cette image ?')">🗑️</a>
                </div>

                <form method="POST" style="margin-top: 0.5rem;">
                    <input type="hidden" name="update_alt" value="1">
                    <input type="hidden" name="media_id" value="<?= e($media['id']) ?>">
                    <div style="display: flex; gap: 0.25rem;">
                        <input type="text" name="alt_text" class="form-control" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;"
                               value="<?= e($media['alt_text']) ?>" placeholder="Texte alt">
                        <button type="submit" class="btn btn-sm btn-primary" style="padding: 0.25rem 0.5rem;">✓</button>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Upload zone
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
        preview.innerHTML = `
            <img src="${e.target.result}" style="max-height: 150px; border-radius: 8px;">
            <p style="margin-top: 0.5rem; font-weight: 600;">${file.name}</p>
            <p style="font-size: 0.875rem; color: var(--gray-500);">${(file.size / 1024).toFixed(1)} KB</p>
        `;
    };
    reader.readAsDataURL(file);
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Toast notification
        const toast = document.createElement('div');
        toast.className = 'alert alert-success';
        toast.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999; animation: fadeIn 0.3s;';
        toast.textContent = 'URL copiée !';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
