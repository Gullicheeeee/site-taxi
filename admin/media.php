<?php
define('ADMIN_LOADED', true);
require_once 'config.php';

$activePage = 'media';
$pageTitle = 'Bibliothèque Médias';

// Gestion de l'upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $errors = [];

    if ($file['error'] === UPLOAD_ERR_OK) {
        // Vérifier le type de fichier
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = 'Type de fichier non autorisé. Formats acceptés : JPG, PNG, GIF, WebP';
        }

        // Vérifier la taille
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            $errors[] = 'Fichier trop volumineux. Taille maximale : 5 MB';
        }

        if (empty($errors)) {
            try {
                // Créer un nom de fichier unique
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . time() . '.' . $extension;
                $uploadPath = UPLOAD_PATH . $filename;

                // Déplacer le fichier
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Enregistrer dans la base de données
                    $db = getDB();
                    $stmt = $db->prepare("
                        INSERT INTO media (filename, original_name, file_path, file_type, file_size, uploaded_by)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $filename,
                        $file['name'],
                        UPLOAD_URL . $filename,
                        $file['type'],
                        $file['size'],
                        $_SESSION['admin_id']
                    ]);

                    setFlash('success', 'Fichier uploadé avec succès');
                } else {
                    $errors[] = 'Erreur lors de l\'upload du fichier';
                }
            } catch (PDOException $e) {
                $errors[] = 'Erreur lors de l\'enregistrement : ' . $e->getMessage();
            }
        }
    } else {
        $errors[] = 'Erreur d\'upload : ' . $file['error'];
    }

    if (!empty($errors)) {
        setFlash('error', implode('<br>', $errors));
    }

    header('Location: media.php');
    exit;
}

// Gestion de la suppression
if (isset($_GET['delete'])) {
    $mediaId = $_GET['delete'];

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT filename FROM media WHERE id = ?");
        $stmt->execute([$mediaId]);
        $media = $stmt->fetch();

        if ($media) {
            // Supprimer le fichier physique
            $filePath = UPLOAD_PATH . $media['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Supprimer de la base de données
            $stmt = $db->prepare("DELETE FROM media WHERE id = ?");
            $stmt->execute([$mediaId]);

            setFlash('success', 'Média supprimé avec succès');
        }
    } catch (PDOException $e) {
        setFlash('error', 'Erreur lors de la suppression');
    }

    header('Location: media.php');
    exit;
}

// Récupérer tous les médias
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT m.*, u.username
        FROM media m
        LEFT JOIN users u ON m.uploaded_by = u.id
        ORDER BY m.uploaded_at DESC
    ");
    $medias = $stmt->fetchAll();
} catch (PDOException $e) {
    $medias = [];
    setFlash('error', 'Erreur lors du chargement des médias');
}

require_once 'includes/header.php';
?>

<div class="card mb-3">
    <div class="card-header">
        <h2 class="card-title">Uploader un fichier</h2>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 300px; margin-bottom: 0;">
                <label for="file" class="form-label">Sélectionner une image</label>
                <input
                    type="file"
                    id="file"
                    name="file"
                    class="form-input"
                    accept="image/*"
                    required
                >
                <div class="form-help">Formats acceptés : JPG, PNG, GIF, WebP - Taille max : 5 MB</div>
            </div>
            <button type="submit" class="btn btn-primary">
                ⬆️ Uploader
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Bibliothèque Médias (<?= count($medias) ?>)</h2>
    </div>
    <div class="card-body">
        <?php if (count($medias) > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem;">
                <?php foreach ($medias as $media): ?>
                    <div class="media-item" style="border: 1px solid var(--gray-200); border-radius: var(--radius-lg); overflow: hidden; background: white;">
                        <div style="aspect-ratio: 1; background: var(--gray-100); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <img
                                src="<?= e($media['file_path']) ?>"
                                alt="<?= e($media['alt_text'] ?? $media['original_name']) ?>"
                                style="width: 100%; height: 100%; object-fit: cover;"
                                loading="lazy"
                            >
                        </div>
                        <div style="padding: 1rem;">
                            <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($media['original_name']) ?>">
                                <?= e($media['original_name']) ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--gray-500); margin-bottom: 0.75rem;">
                                <?= round($media['file_size'] / 1024, 1) ?> KB<br>
                                <?= date('d/m/Y', strtotime($media['uploaded_at'])) ?>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button
                                    class="btn btn-sm btn-secondary"
                                    onclick="copyToClipboard('<?= e($media['file_path']) ?>')"
                                    style="flex: 1;"
                                >
                                    📋 URL
                                </button>
                                <a
                                    href="?delete=<?= $media['id'] ?>"
                                    class="btn btn-sm btn-danger"
                                    data-confirm="Supprimer ce média ?"
                                >
                                    🗑️
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🖼️</div>
                <h3 class="empty-title">Aucun média</h3>
                <p class="empty-text">Uploadez votre première image</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.media-item {
    transition: transform 0.2s, box-shadow 0.2s;
}

.media-item:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}
</style>

<?php require_once 'includes/footer.php'; ?>
