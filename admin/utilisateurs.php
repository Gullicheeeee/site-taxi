<?php
$pageTitle = 'Utilisateurs';
require_once 'includes/header.php';

// Récupérer les utilisateurs
$usersResult = supabase()->select('settings', 'key=eq.admin_users');
$users = [];
if ($usersResult['success'] && !empty($usersResult['data'])) {
    $users = json_decode($usersResult['data'][0]['value'], true) ?: [];
}

// S'assurer que l'admin actuel existe
if (empty($users)) {
    $users = [
        [
            'id' => 'admin-1',
            'username' => $_SESSION['admin_username'] ?? 'admin',
            'email' => 'admin@taxijulien.fr',
            'role' => 'admin',
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => date('Y-m-d H:i:s')
        ]
    ];
    supabase()->insert('settings', ['key' => 'admin_users', 'value' => json_encode($users, JSON_UNESCAPED_UNICODE)]);
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'editor';
        $password = $_POST['password'] ?? '';

        if ($username && $email && $password) {
            // Vérifier si l'utilisateur existe déjà
            $exists = false;
            foreach ($users as $u) {
                if ($u['username'] === $username || $u['email'] === $email) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $users[] = [
                    'id' => uniqid('user-'),
                    'username' => $username,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_login' => null
                ];

                supabase()->update('settings', 'key=eq.admin_users', ['value' => json_encode($users, JSON_UNESCAPED_UNICODE)]);
                setFlash('success', 'Utilisateur créé avec succès');
            } else {
                setFlash('error', 'Cet utilisateur existe déjà');
            }
        }
    }

    if ($action === 'update_role') {
        $id = $_POST['id'] ?? '';
        $role = $_POST['role'] ?? 'editor';

        foreach ($users as &$u) {
            if ($u['id'] === $id) {
                $u['role'] = $role;
                break;
            }
        }

        supabase()->update('settings', 'key=eq.admin_users', ['value' => json_encode($users, JSON_UNESCAPED_UNICODE)]);
        setFlash('success', 'Rôle mis à jour');
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';

        // Ne pas supprimer le dernier admin
        $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
        $userToDelete = null;
        foreach ($users as $u) {
            if ($u['id'] === $id) {
                $userToDelete = $u;
                break;
            }
        }

        if ($userToDelete && ($userToDelete['role'] !== 'admin' || $adminCount > 1)) {
            $users = array_values(array_filter($users, fn($u) => $u['id'] !== $id));
            supabase()->update('settings', 'key=eq.admin_users', ['value' => json_encode($users, JSON_UNESCAPED_UNICODE)]);
            setFlash('success', 'Utilisateur supprimé');
        } else {
            setFlash('error', 'Impossible de supprimer le dernier administrateur');
        }
    }

    header('Location: utilisateurs.php');
    exit;
}

$roles = [
    'admin' => ['label' => 'Administrateur', 'color' => '#ef4444', 'desc' => 'Accès complet à toutes les fonctionnalités'],
    'editor' => ['label' => 'Éditeur', 'color' => '#3b82f6', 'desc' => 'Peut créer et modifier le contenu'],
    'author' => ['label' => 'Auteur', 'color' => '#22c55e', 'desc' => 'Peut créer et publier ses propres articles'],
    'contributor' => ['label' => 'Contributeur', 'color' => '#f59e0b', 'desc' => 'Peut créer des brouillons']
];
?>

<style>
.users-table {
    width: 100%;
    border-collapse: collapse;
}
.users-table th {
    text-align: left;
    padding: 1rem;
    background: var(--gray-50);
    border-bottom: 2px solid var(--gray-200);
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--gray-600);
}
.users-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}
.users-table tr:hover {
    background: var(--gray-50);
}
.user-cell {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1rem;
}
.user-info {
    display: flex;
    flex-direction: column;
}
.user-name {
    font-weight: 500;
}
.user-email {
    font-size: 0.85rem;
    color: var(--gray-500);
}
.role-badge {
    display: inline-block;
    padding: 0.3rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}
.role-select {
    padding: 0.4rem 0.75rem;
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    font-size: 0.85rem;
    background: white;
    cursor: pointer;
}
.add-user-form {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 150px auto;
    gap: 1rem;
    align-items: end;
}
.roles-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}
.role-card {
    padding: 1rem;
    border-radius: 8px;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
}
.role-card-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.role-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}
.role-card-desc {
    font-size: 0.85rem;
    color: var(--gray-600);
}
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.stat-card {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
}
.stat-label {
    font-size: 0.85rem;
    color: var(--gray-500);
}
</style>

<div class="page-header">
    <h2 class="page-title">Utilisateurs</h2>
    <p class="page-subtitle">Gérez les utilisateurs et leurs permissions</p>
</div>

<!-- Statistiques -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon" style="background: #eff6ff;">👥</div>
        <div>
            <div class="stat-value"><?= count($users) ?></div>
            <div class="stat-label">Utilisateurs</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #fef2f2;">👑</div>
        <div>
            <div class="stat-value"><?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></div>
            <div class="stat-label">Administrateurs</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #f0fdf4;">✏️</div>
        <div>
            <div class="stat-value"><?= count(array_filter($users, fn($u) => $u['role'] === 'editor')) ?></div>
            <div class="stat-label">Éditeurs</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #fffbeb;">📝</div>
        <div>
            <div class="stat-value"><?= count(array_filter($users, fn($u) => in_array($u['role'], ['author', 'contributor']))) ?></div>
            <div class="stat-label">Auteurs</div>
        </div>
    </div>
</div>

<!-- Ajouter un utilisateur -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">➕ Ajouter un utilisateur</h3>
    </div>
    <div class="card-body">
        <form method="POST" class="add-user-form">
            <input type="hidden" name="action" value="add">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Nom d'utilisateur</label>
                <input type="text" name="username" class="form-control" placeholder="julien" required>
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="julien@taxijulien.fr" required>
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required minlength="6">
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Rôle</label>
                <select name="role" class="form-control">
                    <?php foreach ($roles as $key => $role): ?>
                    <option value="<?= $key ?>"><?= $role['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Ajouter</button>
        </form>
    </div>
</div>

<!-- Liste des utilisateurs -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📋 Liste des utilisateurs</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>Inscrit le</th>
                    <th>Dernière connexion</th>
                    <th style="width: 100px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar" style="background: <?= $roles[$user['role']]['color'] ?>">
                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                            </div>
                            <div class="user-info">
                                <span class="user-name"><?= e($user['username']) ?></span>
                                <span class="user-email"><?= e($user['email']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_role">
                            <input type="hidden" name="id" value="<?= e($user['id']) ?>">
                            <select name="role" class="role-select" onchange="this.form.submit()">
                                <?php foreach ($roles as $key => $role): ?>
                                <option value="<?= $key ?>" <?= $user['role'] === $key ? 'selected' : '' ?>>
                                    <?= $role['label'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td style="color: var(--gray-500); font-size: 0.9rem;">
                        <?= !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '-' ?>
                    </td>
                    <td style="color: var(--gray-500); font-size: 0.9rem;">
                        <?= !empty($user['last_login']) ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais' ?>
                    </td>
                    <td>
                        <?php
                        $isCurrentUser = ($user['username'] === ($_SESSION['admin_username'] ?? ''));
                        $isLastAdmin = ($user['role'] === 'admin' && count(array_filter($users, fn($u) => $u['role'] === 'admin')) === 1);
                        ?>
                        <?php if (!$isCurrentUser && !$isLastAdmin): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= e($user['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cet utilisateur ?')">🗑️</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Rôles -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">🔐 Permissions par rôle</h3>
    </div>
    <div class="card-body">
        <div class="roles-grid">
            <?php foreach ($roles as $key => $role): ?>
            <div class="role-card">
                <div class="role-card-title">
                    <span class="role-dot" style="background: <?= $role['color'] ?>"></span>
                    <?= $role['label'] ?>
                </div>
                <div class="role-card-desc"><?= $role['desc'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
