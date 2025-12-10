<?php
$pageTitle = 'Messages';
require_once 'includes/header.php';

$db = getDB();

// Marquer comme lu
if (isset($_GET['read'])) {
    $id = (int)$_GET['read'];
    $stmt = $db->prepare("UPDATE contacts SET is_read = 1 WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: contacts.php');
    exit;
}

// Supprimer
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Message supprimé');
    header('Location: contacts.php');
    exit;
}

// Récupérer les messages
$contacts = $db->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();
$unreadCount = $db->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0")->fetchColumn();
?>

<div class="page-header">
    <h2 class="page-title">Messages de contact</h2>
    <p class="page-subtitle">
        <?= $unreadCount ?> message(s) non lu(s)
    </p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Tous les messages (<?= count($contacts) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($contacts)): ?>
        <div style="text-align: center; padding: 3rem;">
            <p style="font-size: 3rem; margin-bottom: 1rem;">📭</p>
            <p style="color: var(--gray-500);">Aucun message pour le moment</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Statut</th>
                        <th>Nom</th>
                        <th>Email / Téléphone</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                    <tr style="<?= !$contact['is_read'] ? 'background: rgba(66, 153, 225, 0.05);' : '' ?>">
                        <td>
                            <?php if (!$contact['is_read']): ?>
                            <span class="badge badge-info">Nouveau</span>
                            <?php else: ?>
                            <span class="badge badge-success">Lu</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= e($contact['name']) ?></strong>
                        </td>
                        <td>
                            <?php if ($contact['email']): ?>
                            <a href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a><br>
                            <?php endif; ?>
                            <?php if ($contact['phone']): ?>
                            <a href="tel:<?= e($contact['phone']) ?>"><?= e($contact['phone']) ?></a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= e(substr($contact['message'], 0, 100)) ?><?= strlen($contact['message']) > 100 ? '...' : '' ?></small>
                        </td>
                        <td>
                            <small><?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?></small>
                        </td>
                        <td>
                            <div class="table-actions">
                                <button type="button" class="btn btn-sm btn-primary view-message"
                                        data-name="<?= e($contact['name']) ?>"
                                        data-email="<?= e($contact['email']) ?>"
                                        data-phone="<?= e($contact['phone']) ?>"
                                        data-message="<?= e($contact['message']) ?>"
                                        data-date="<?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?>"
                                        data-id="<?= $contact['id'] ?>">
                                    Voir
                                </button>
                                <a href="?delete=<?= $contact['id'] ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Supprimer ce message ?')">🗑️</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal pour voir le message -->
<div id="message-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modal-name">Message de</h3>
            <button type="button" id="close-modal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div style="padding: 1.5rem;">
            <p style="margin-bottom: 1rem;">
                <strong>Email :</strong> <a href="#" id="modal-email"></a><br>
                <strong>Téléphone :</strong> <a href="#" id="modal-phone"></a><br>
                <strong>Date :</strong> <span id="modal-date"></span>
            </p>
            <div style="background: var(--gray-100); padding: 1rem; border-radius: 8px;">
                <p id="modal-message" style="white-space: pre-wrap;"></p>
            </div>
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                <a href="#" id="modal-reply-email" class="btn btn-primary">📧 Répondre par email</a>
                <a href="#" id="modal-reply-phone" class="btn btn-secondary">📞 Appeler</a>
            </div>
        </div>
    </div>
</div>

<script>
const modal = document.getElementById('message-modal');
const closeBtn = document.getElementById('close-modal');

document.querySelectorAll('.view-message').forEach(btn => {
    btn.addEventListener('click', () => {
        const data = btn.dataset;

        document.getElementById('modal-name').textContent = 'Message de ' + data.name;
        document.getElementById('modal-email').textContent = data.email;
        document.getElementById('modal-email').href = 'mailto:' + data.email;
        document.getElementById('modal-phone').textContent = data.phone;
        document.getElementById('modal-phone').href = 'tel:' + data.phone;
        document.getElementById('modal-date').textContent = data.date;
        document.getElementById('modal-message').textContent = data.message;
        document.getElementById('modal-reply-email').href = 'mailto:' + data.email;
        document.getElementById('modal-reply-phone').href = 'tel:' + data.phone;

        modal.style.display = 'flex';

        // Marquer comme lu
        fetch('?read=' + data.id);
    });
});

closeBtn.addEventListener('click', () => {
    modal.style.display = 'none';
    location.reload();
});

modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        modal.style.display = 'none';
        location.reload();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
