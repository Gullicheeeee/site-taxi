<?php
/**
 * GESTION DES MÉDIAS - Back-Office Taxi Julien
 * Upload, compression, gestion du texte alternatif
 */
declare(strict_types=1);

$pageTitle = 'Gestion des Images';
require_once 'includes/header.php';

// Configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_OPTIMIZED_SIZE', 200 * 1024); // 200KB recommandé
define('QUALITY_COMPRESSION', 85); // Qualité JPEG
define('MAX_WIDTH', 1920); // Largeur max
define('MAX_HEIGHT', 1080); // Hauteur max

/**
 * Compresse et optimise une image
 */
function optimizeImage(string $sourcePath, string $mimeType, int $maxWidth = MAX_WIDTH, int $maxHeight = MAX_HEIGHT): ?string {
    // Vérifier GD
    if (!function_exists('imagecreatefromjpeg')) {
        return null;
    }

    // Charger l'image selon le type
    switch ($mimeType) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            return null;
    }

    if (!$source) return null;

    // Dimensions originales
    $width = imagesx($source);
    $height = imagesy($source);

    // Calcul du redimensionnement
    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);

    // Créer la nouvelle image
    $destination = imagecreatetruecolor($newWidth, $newHeight);

    // Préserver la transparence pour PNG et GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 0, 0, 0, 127);
        imagefill($destination, 0, 0, $transparent);
    }

    // Redimensionner
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Sauvegarder temporairement
    $tempFile = sys_get_temp_dir() . '/optimized_' . uniqid() . '.jpg';

    // Convertir en JPEG pour optimisation (sauf PNG avec transparence)
    if ($mimeType === 'image/png') {
        // Vérifier si l'image a de la transparence
        $hasTransparency = false;
        for ($x = 0; $x < $width && !$hasTransparency; $x++) {
            for ($y = 0; $y < $height && !$hasTransparency; $y++) {
                $rgba = imagecolorat($source, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                if ($alpha > 0) $hasTransparency = true;
            }
        }

        if ($hasTransparency) {
            $tempFile = sys_get_temp_dir() . '/optimized_' . uniqid() . '.png';
            imagepng($destination, $tempFile, 8);
        } else {
            imagejpeg($destination, $tempFile, QUALITY_COMPRESSION);
        }
    } else {
        imagejpeg($destination, $tempFile, QUALITY_COMPRESSION);
    }

    // Libérer la mémoire
    imagedestroy($source);
    imagedestroy($destination);

    return $tempFile;
}

// Upload d'image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    require_csrf();

    $file = $_FILES['image'];
    $altText = trim($_POST['alt_text'] ?? '');
    $autoCompress = isset($_POST['auto_compress']);

    // Vérifier le texte alternatif
    if (empty($altText)) {
        setFlash('danger', 'Le texte alternatif est obligatoire pour le SEO et l\'accessibilité');
        header('Location: media.php');
        exit;
    }

    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($mimeType, $allowedTypes)) {
            if ($file['size'] <= MAX_FILE_SIZE) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('img_') . '.' . strtolower($ext);
                $uploadPath = $file['tmp_name'];
                $finalSize = $file['size'];
                $wasCompressed = false;

                // Compression automatique si activée
                if ($autoCompress && $file['size'] > MAX_OPTIMIZED_SIZE) {
                    $optimizedPath = optimizeImage($file['tmp_name'], $mimeType);
                    if ($optimizedPath && file_exists($optimizedPath)) {
                        $newSize = filesize($optimizedPath);
                        // Utiliser l'image optimisée seulement si elle est plus petite
                        if ($newSize < $file['size']) {
                            $uploadPath = $optimizedPath;
                            $finalSize = $newSize;
                            $wasCompressed = true;
                            // Mettre à jour l'extension si convertie en JPEG
                            if (pathinfo($optimizedPath, PATHINFO_EXTENSION) === 'jpg') {
                                $filename = uniqid('img_') . '.jpg';
                                $mimeType = 'image/jpeg';
                            }
                        }
                    }
                }

                // Upload vers Supabase Storage
                $result = supabase()->uploadFile('images', $filename, $uploadPath, $mimeType);

                if ($result['success']) {
                    // Enregistrer dans la table media
                    supabase()->insert('media', [
                        'filename' => $filename,
                        'original_name' => $file['name'],
                        'file_path' => $filename,
                        'file_url' => $result['url'],
                        'file_type' => $mimeType,
                        'file_size' => $finalSize,
                        'alt_text' => $altText
                    ]);

                    // Logger l'activité
                    logActivity('upload', 'media', $filename);

                    // Message de succès
                    if ($wasCompressed) {
                        $savedKb = round(($file['size'] - $finalSize) / 1024);
                        $savedPercent = round((1 - $finalSize / $file['size']) * 100);
                        setFlash('success', "Image uploadée et optimisée ! ({$savedKb} KB économisés, -{$savedPercent}%)");
                    } else {
                        setFlash('success', 'Image uploadée avec succès !');
                    }
                } else {
                    setFlash('danger', 'Erreur upload Supabase: ' . ($result['error'] ?? 'Inconnu'));
                }

                // Nettoyer le fichier temporaire optimisé
                if ($wasCompressed && file_exists($uploadPath)) {
                    @unlink($uploadPath);
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

        // Logger l'activité
        logActivity('delete', 'media', $media['filename']);

        setFlash('success', 'Image supprimée');
    }
    header('Location: media.php');
    exit;
}

// Mise à jour alt text
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_alt'])) {
    require_csrf();

    $id = $_POST['media_id'] ?? '';
    $altText = trim($_POST['alt_text'] ?? '');

    if ($id && $altText) {
        supabase()->update('media', 'id=eq.' . urlencode($id), ['alt_text' => $altText]);
        setFlash('success', 'Texte alternatif mis à jour');
    } elseif ($id && !$altText) {
        setFlash('danger', 'Le texte alternatif ne peut pas être vide');
    }
    header('Location: media.php');
    exit;
}

// Récupérer les images
$result = supabase()->select('media', 'order=uploaded_at.desc');
$mediaList = $result['success'] ? $result['data'] : [];

// Statistiques
$totalSize = array_sum(array_column($mediaList, 'file_size'));
$imagesWithoutAlt = count(array_filter($mediaList, fn($m) => empty($m['alt_text'])));
$heavyImages = count(array_filter($mediaList, fn($m) => ($m['file_size'] ?? 0) > MAX_OPTIMIZED_SIZE));
?>

<style>
.media-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.media-stat {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    border: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.media-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.media-stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-900);
}

.media-stat-label {
    font-size: 0.85rem;
    color: var(--gray-500);
}

.upload-zone {
    border: 2px dashed var(--gray-300);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 180px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.upload-zone:hover,
.upload-zone.dragover {
    border-color: var(--primary);
    background: rgba(26, 54, 93, 0.05);
}

.upload-options {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--gray-200);
}

.upload-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: var(--gray-600);
}

.upload-checkbox input {
    width: 18px;
    height: 18px;
    accent-color: var(--primary);
}

.image-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem;
}

.image-card {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;
}

.image-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.image-card.warning {
    border-color: #f59e0b;
}

.image-card.error {
    border-color: #ef4444;
}

.image-preview {
    position: relative;
    height: 160px;
    overflow: hidden;
    cursor: pointer;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.2s;
}

.image-card:hover .image-preview img {
    transform: scale(1.05);
}

.image-badges {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.image-badge {
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
}

.image-badge.size-good {
    background: #dcfce7;
    color: #16a34a;
}

.image-badge.size-heavy {
    background: #fef3c7;
    color: #b45309;
}

.image-badge.no-alt {
    background: #fee2e2;
    color: #dc2626;
}

.image-info {
    padding: 0.75rem;
}

.image-name {
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--gray-700);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-bottom: 0.25rem;
}

.image-size {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.image-actions {
    display: flex;
    gap: 0.5rem;
    padding: 0 0.75rem;
}

.image-alt-form {
    padding: 0.75rem;
    padding-top: 0;
    border-top: 1px solid var(--gray-100);
}

.image-alt-input {
    width: 100%;
    padding: 0.4rem 0.6rem;
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    font-size: 0.75rem;
}

.image-alt-input:focus {
    outline: none;
    border-color: var(--primary);
}

.image-alt-input.error {
    border-color: #ef4444;
    background: #fef2f2;
}

@media (max-width: 768px) {
    .media-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <h2 class="page-title">Gestion des Images</h2>
        <p class="page-subtitle">Uploadez et gérez vos images optimisées pour le SEO</p>
    </div>
</div>

<!-- Statistiques -->
<div class="media-stats">
    <div class="media-stat">
        <div class="media-stat-icon" style="background: #e0f2fe;">🖼️</div>
        <div>
            <div class="media-stat-value"><?= count($mediaList) ?></div>
            <div class="media-stat-label">Images total</div>
        </div>
    </div>
    <div class="media-stat">
        <div class="media-stat-icon" style="background: #dcfce7;">💾</div>
        <div>
            <div class="media-stat-value"><?= round($totalSize / 1024 / 1024, 1) ?> MB</div>
            <div class="media-stat-label">Espace utilisé</div>
        </div>
    </div>
    <div class="media-stat">
        <div class="media-stat-icon" style="background: <?= $imagesWithoutAlt > 0 ? '#fee2e2' : '#dcfce7' ?>;">
            <?= $imagesWithoutAlt > 0 ? '⚠️' : '✅' ?>
        </div>
        <div>
            <div class="media-stat-value"><?= $imagesWithoutAlt ?></div>
            <div class="media-stat-label">Sans texte alt</div>
        </div>
    </div>
    <div class="media-stat">
        <div class="media-stat-icon" style="background: <?= $heavyImages > 0 ? '#fef3c7' : '#dcfce7' ?>;">
            <?= $heavyImages > 0 ? '⚡' : '✅' ?>
        </div>
        <div>
            <div class="media-stat-value"><?= $heavyImages ?></div>
            <div class="media-stat-label">> 200 KB</div>
        </div>
    </div>
</div>

<!-- Zone d'upload -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Uploader une image</h3>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="upload-form">
            <?= csrf_field() ?>
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
                    <label class="form-label">
                        Texte alternatif (alt) <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="alt_text" id="alt-input" class="form-control"
                           placeholder="Ex: Taxi devant la gare de Martigues" required>
                    <p class="form-help">
                        <strong>Obligatoire</strong> pour le SEO et l'accessibilité. Décrivez le contenu de l'image.
                    </p>

                    <div class="upload-options">
                        <label class="upload-checkbox">
                            <input type="checkbox" name="auto_compress" checked>
                            <span>Compression automatique (recommandé)</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3" style="width: 100%;" id="upload-btn" disabled>
                        ⬆️ Uploader l'image
                    </button>
                    <p id="upload-validation" style="font-size: 0.8rem; color: #ef4444; margin-top: 0.5rem; display: none;">
                        Veuillez sélectionner une image et remplir le texte alternatif
                    </p>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bibliothèque d'images -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Bibliothèque d'images (<?= count($mediaList) ?>)</h3>
        <?php if ($imagesWithoutAlt > 0): ?>
        <span class="badge badge-danger"><?= $imagesWithoutAlt ?> sans alt</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($mediaList)): ?>
        <div style="text-align: center; padding: 3rem;">
            <p style="font-size: 3rem; margin-bottom: 1rem;">🖼️</p>
            <p style="color: var(--gray-500);">Aucune image uploadée</p>
        </div>
        <?php else: ?>
        <div class="image-grid">
            <?php foreach ($mediaList as $media):
                $size = $media['file_size'] ?? 0;
                $sizeKb = round($size / 1024);
                $hasAlt = !empty($media['alt_text']);
                $isHeavy = $size > MAX_OPTIMIZED_SIZE;
                $cardClass = !$hasAlt ? 'error' : ($isHeavy ? 'warning' : '');
            ?>
            <div class="image-card <?= $cardClass ?>">
                <div class="image-preview" onclick="copyToClipboard('<?= e($media['file_url']) ?>')">
                    <img src="<?= e($media['file_url']) ?>" alt="<?= e($media['alt_text']) ?>" loading="lazy">
                    <div class="image-badges">
                        <?php if (!$hasAlt): ?>
                        <span class="image-badge no-alt">Sans alt</span>
                        <?php endif; ?>
                        <?php if ($isHeavy): ?>
                        <span class="image-badge size-heavy"><?= $sizeKb ?> KB</span>
                        <?php else: ?>
                        <span class="image-badge size-good"><?= $sizeKb ?> KB</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="image-info">
                    <div class="image-name" title="<?= e($media['original_name']) ?>">
                        <?= e($media['original_name']) ?>
                    </div>
                    <div class="image-size">
                        <?= strtoupper(pathinfo($media['filename'], PATHINFO_EXTENSION)) ?> • <?= $sizeKb ?> KB
                    </div>
                </div>

                <div class="image-actions">
                    <button type="button" class="btn btn-sm btn-secondary" style="flex: 1;"
                            onclick="copyToClipboard('<?= e($media['file_url']) ?>')">
                        📋 Copier
                    </button>
                    <a href="?delete=<?= e($media['id']) ?>" class="btn btn-sm btn-danger"
                       onclick="return confirm('Supprimer cette image ?')">🗑️</a>
                </div>

                <form method="POST" class="image-alt-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="update_alt" value="1">
                    <input type="hidden" name="media_id" value="<?= e($media['id']) ?>">
                    <div style="display: flex; gap: 0.25rem;">
                        <input type="text" name="alt_text" class="image-alt-input <?= !$hasAlt ? 'error' : '' ?>"
                               value="<?= e($media['alt_text']) ?>"
                               placeholder="<?= $hasAlt ? '' : 'Texte alt manquant !' ?>">
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
// Variables
const uploadZone = document.getElementById('upload-zone');
const imageInput = document.getElementById('image-input');
const altInput = document.getElementById('alt-input');
const uploadBtn = document.getElementById('upload-btn');
const preview = document.getElementById('upload-preview');
const validation = document.getElementById('upload-validation');

// Validation du formulaire
function validateForm() {
    const hasImage = imageInput.files.length > 0;
    const hasAlt = altInput.value.trim().length > 0;

    uploadBtn.disabled = !(hasImage && hasAlt);

    if (!hasImage || !hasAlt) {
        validation.style.display = 'block';
        if (!hasImage && !hasAlt) {
            validation.textContent = 'Veuillez sélectionner une image et remplir le texte alternatif';
        } else if (!hasImage) {
            validation.textContent = 'Veuillez sélectionner une image';
        } else {
            validation.textContent = 'Le texte alternatif est obligatoire';
        }
    } else {
        validation.style.display = 'none';
    }
}

// Upload zone events
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
        validateForm();
    }
});

imageInput.addEventListener('change', () => {
    if (imageInput.files.length) {
        showPreview(imageInput.files[0]);
        validateForm();
    }
});

altInput.addEventListener('input', validateForm);

function showPreview(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        const sizeKb = (file.size / 1024).toFixed(1);
        const isHeavy = file.size > <?= MAX_OPTIMIZED_SIZE ?>;

        preview.innerHTML = `
            <img src="${e.target.result}" style="max-height: 120px; border-radius: 8px;">
            <p style="margin-top: 0.5rem; font-weight: 600; font-size: 0.9rem;">${file.name}</p>
            <p style="font-size: 0.8rem; color: ${isHeavy ? '#f59e0b' : 'var(--gray-500)'};">
                ${sizeKb} KB ${isHeavy ? '(sera compressée)' : ''}
            </p>
        `;
    };
    reader.readAsDataURL(file);
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        const toast = document.createElement('div');
        toast.className = 'alert alert-success';
        toast.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999; animation: fadeIn 0.3s;';
        toast.innerHTML = '✅ URL copiée dans le presse-papiers';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    });
}

// Initial validation
validateForm();
</script>

<?php require_once 'includes/footer.php'; ?>
