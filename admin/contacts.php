<?php
/**
 * GESTION DES CONTACTS - Back-Office Taxi Julien
 * Affichage et gestion des messages reçus via le formulaire de contact
 */
declare(strict_types=1);

$pageTitle = 'Messages';
require_once 'includes/header.php';

// Marquer comme lu via AJAX ou GET
if (isset($_GET['read'])) {
    $id = $_GET['read'];
    supabase()->update('contacts', 'id=eq.' . urlencode($id), [
        'is_read' => true
    ]);
    header('Location: contacts.php');
    exit;
}

// Supprimer un message
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    supabase()->delete('contacts', 'id=eq.' . urlencode($id));
    setFlash('success', 'Message supprimé');
    header('Location: contacts.php');
    exit;
}

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $result = supabase()->select('contacts', 'order=created_at.desc');
    $contacts = $result['success'] ? $result['data'] : [];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="contacts_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    // BOM UTF-8 pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // En-têtes
    fputcsv($output, ['ID', 'Nom', 'Email', 'Téléphone', 'Message', 'Lu', 'Date'], ';');

    foreach ($contacts as $contact) {
        fputcsv($output, [
            $contact['id'],
            $contact['name'] ?? '',
            $contact['email'] ?? '',
            $contact['phone'] ?? '',
            $contact['message'] ?? '',
            ($contact['is_read'] ?? false) ? 'Oui' : 'Non',
            isset($contact['created_at']) ? date('d/m/Y H:i', strtotime($contact['created_at'])) : ''
        ], ';');
    }

    fclose($output);
    exit;
}

// Récupérer les messages depuis Supabase
$result = supabase()->select('contacts', 'order=created_at.desc');
$contacts = $result['success'] ? $result['data'] : [];

// Compter les non-lus
$unreadCount = count(array_filter($contacts, fn($c) => !($c['is_read'] ?? false)));
?>

<style>
.contacts-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.contact-stat {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    border: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.contact-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.contact-stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--gray-900);
}

.contact-stat-label {
    font-size: 0.85rem;
    color: var(--gray-500);
}

.message-row {
    transition: background 0.2s;
}

.message-row.unread {
    background: rgba(59, 130, 246, 0.05);
}

.message-row:hover {
    background: var(--gray-50);
}

.message-preview {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--gray-600);
    font-size: 0.9rem;
}

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 16px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--gray-500);
    line-height: 1;
}

.modal-close:hover {
    color: var(--gray-800);
}

.modal-body {
    padding: 1.5rem;
}

.contact-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.contact-info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.contact-info-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: var(--gray-500);
    font-weight: 600;
}

.contact-info-value {
    font-size: 0.95rem;
    color: var(--gray-800);
}

.contact-info-value a {
    color: var(--primary);
    text-decoration: none;
}

.contact-info-value a:hover {
    text-decoration: underline;
}

.message-content {
    background: var(--gray-100);
    padding: 1.25rem;
    border-radius: 12px;
    white-space: pre-wrap;
    line-height: 1.6;
    color: var(--gray-700);
}

.modal-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--gray-200);
}
</style>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <h2 class="page-title">Messages de contact</h2>
        <p class="page-subtitle">
            <?= $unreadCount ?> message<?= $unreadCount > 1 ? 's' : '' ?> non lu<?= $unreadCount > 1 ? 's' : '' ?>
        </p>
    </div>
    <a href="?export=csv" class="btn btn-secondary">
        📥 Exporter CSV
    </a>
</div>

<!-- Statistiques -->
<div class="contacts-stats">
    <div class="contact-stat">
        <div class="contact-stat-icon" style="background: #e0f2fe;">📨</div>
        <div>
            <div class="contact-stat-value"><?= count($contacts) ?></div>
            <div class="contact-stat-label">Total messages</div>
        </div>
    </div>
    <div class="contact-stat">
        <div class="contact-stat-icon" style="background: #fef3c7;">🔔</div>
        <div>
            <div class="contact-stat-value"><?= $unreadCount ?></div>
            <div class="contact-stat-label">Non lus</div>
        </div>
    </div>
    <div class="contact-stat">
        <div class="contact-stat-icon" style="background: #dcfce7;">✅</div>
        <div>
            <div class="contact-stat-value"><?= count($contacts) - $unreadCount ?></div>
            <div class="contact-stat-label">Traités</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">📋 Tous les messages</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($contacts)): ?>
        <div style="text-align: center; padding: 3rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
            <p style="color: var(--gray-500);">Aucun message pour le moment</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Statut</th>
                        <th>Nom</th>
                        <th>Contact</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact):
                        $isUnread = !($contact['is_read'] ?? false);
                    ?>
                    <tr class="message-row <?= $isUnread ? 'unread' : '' ?>">
                        <td>
                            <?php if ($isUnread): ?>
                            <span class="badge badge-info">Nouveau</span>
                            <?php else: ?>
                            <span class="badge badge-success">Lu</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= e($contact['name'] ?? 'Sans nom') ?></strong>
                        </td>
                        <td>
                            <?php if (!empty($contact['email'])): ?>
                            <a href="mailto:<?= e($contact['email']) ?>" style="color: var(--primary); text-decoration: none;">
                                <?= e($contact['email']) ?>
                            </a><br>
                            <?php endif; ?>
                            <?php if (!empty($contact['phone'])): ?>
                            <a href="tel:<?= e($contact['phone']) ?>" style="color: var(--gray-600); text-decoration: none;">
                                <?= e($contact['phone']) ?>
                            </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="message-preview">
                                <?= e(substr($contact['message'] ?? '', 0, 80)) ?><?= strlen($contact['message'] ?? '') > 80 ? '...' : '' ?>
                            </div>
                        </td>
                        <td>
                            <small style="color: var(--gray-500);">
                                <?= isset($contact['created_at']) ? date('d/m/Y H:i', strtotime($contact['created_at'])) : '-' ?>
                            </small>
                        </td>
                        <td>
                            <div class="table-actions">
                                <button type="button" class="btn btn-sm btn-primary view-message"
                                        data-id="<?= e($contact['id']) ?>"
                                        data-name="<?= e($contact['name'] ?? 'Sans nom') ?>"
                                        data-email="<?= e($contact['email'] ?? '') ?>"
                                        data-phone="<?= e($contact['phone'] ?? '') ?>"
                                        data-message="<?= e($contact['message'] ?? '') ?>"
                                        data-date="<?= isset($contact['created_at']) ? date('d/m/Y H:i', strtotime($contact['created_at'])) : '-' ?>">
                                    👁️
                                </button>
                                <a href="?delete=<?= urlencode($contact['id']) ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Supprimer ce message ?')">
                                    🗑️
                                </a>
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
<div class="modal-overlay" id="message-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Message de</h3>
            <button class="modal-close" id="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="contact-info">
                <div class="contact-info-item">
                    <span class="contact-info-label">Email</span>
                    <span class="contact-info-value"><a href="#" id="modal-email"></a></span>
                </div>
                <div class="contact-info-item">
                    <span class="contact-info-label">Téléphone</span>
                    <span class="contact-info-value"><a href="#" id="modal-phone"></a></span>
                </div>
                <div class="contact-info-item">
                    <span class="contact-info-label">Date</span>
                    <span class="contact-info-value" id="modal-date"></span>
                </div>
            </div>

            <div class="contact-info-label" style="margin-bottom: 0.5rem;">Message</div>
            <div class="message-content" id="modal-message"></div>

            <div class="modal-actions">
                <a href="#" id="modal-reply-email" class="btn btn-primary">
                    📧 Répondre par email
                </a>
                <a href="#" id="modal-reply-phone" class="btn btn-secondary">
                    📞 Appeler
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const modal = document.getElementById('message-modal');
const closeBtn = document.getElementById('close-modal');

// Ouvrir le modal
document.querySelectorAll('.view-message').forEach(btn => {
    btn.addEventListener('click', function() {
        const data = this.dataset;

        document.getElementById('modal-title').textContent = 'Message de ' + data.name;

        const emailEl = document.getElementById('modal-email');
        emailEl.textContent = data.email || 'Non renseigné';
        emailEl.href = data.email ? 'mailto:' + data.email : '#';

        const phoneEl = document.getElementById('modal-phone');
        phoneEl.textContent = data.phone || 'Non renseigné';
        phoneEl.href = data.phone ? 'tel:' + data.phone : '#';

        document.getElementById('modal-date').textContent = data.date;
        document.getElementById('modal-message').textContent = data.message;

        document.getElementById('modal-reply-email').href = data.email ? 'mailto:' + data.email : '#';
        document.getElementById('modal-reply-email').style.display = data.email ? 'inline-flex' : 'none';

        document.getElementById('modal-reply-phone').href = data.phone ? 'tel:' + data.phone : '#';
        document.getElementById('modal-reply-phone').style.display = data.phone ? 'inline-flex' : 'none';

        modal.classList.add('active');

        // Marquer comme lu
        if (data.id) {
            fetch('?read=' + encodeURIComponent(data.id));
            // Mettre à jour visuellement
            const row = this.closest('tr');
            if (row) {
                row.classList.remove('unread');
                const badge = row.querySelector('.badge');
                if (badge) {
                    badge.className = 'badge badge-success';
                    badge.textContent = 'Lu';
                }
            }
        }
    });
});

// Fermer le modal
closeBtn.addEventListener('click', () => {
    modal.classList.remove('active');
});

modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        modal.classList.remove('active');
    }
});

// Fermer avec Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('active')) {
        modal.classList.remove('active');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
