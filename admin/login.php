<?php
/**
 * PAGE DE CONNEXION - Back-Office Taxi Julien
 * Authentification sécurisée avec rate limiting et CSRF
 */
declare(strict_types=1);

require_once 'config.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$rateLimited = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le CSRF
    if (!verify_csrf()) {
        $error = 'Session expirée. Veuillez réessayer.';
    }
    // Vérifier le rate limit
    elseif (!check_rate_limit('login', 5, 15)) {
        $rateLimited = true;
        $error = 'Trop de tentatives. Veuillez attendre 15 minutes.';
    }
    else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username && $password) {
            // Vérifier dans Supabase
            $result = supabase()->select('admins', 'username=eq.' . urlencode($username));

            if ($result['success'] && !empty($result['data'])) {
                $admin = $result['data'][0];

                if (password_verify($password, $admin['password_hash'])) {
                    // Connexion réussie
                    reset_rate_limit('login');

                    // Régénérer l'ID de session après login
                    session_regenerate_id(true);

                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
                    $_SESSION['_created'] = time();

                    // Mettre à jour last_login
                    supabase()->update('admins', 'id=eq.' . $admin['id'], [
                        'last_login' => date('c')
                    ]);

                    // Logger l'activité
                    logActivity('login', 'user', $admin['id']);

                    header('Location: index.php');
                    exit;
                }
            }

            // Échec de connexion
            increment_rate_limit('login');
            $error = 'Identifiants incorrects';
        } else {
            $error = 'Veuillez remplir tous les champs';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Connexion - Taxi Julien Admin</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
    .login-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
        padding: 1rem;
    }

    .login-box {
        background: white;
        padding: 2.5rem;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        width: 100%;
        max-width: 400px;
    }

    .login-logo {
        text-align: center;
        margin-bottom: 2rem;
    }

    .login-logo-icon {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 2rem;
    }

    .login-logo h1 {
        font-size: 1.5rem;
        color: #1a365d;
        margin: 0;
    }

    .login-logo p {
        color: #718096;
        margin: 0.5rem 0 0;
        font-size: 0.9rem;
    }

    .login-form .form-group {
        margin-bottom: 1.25rem;
    }

    .login-form .form-label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #4a5568;
        font-size: 0.9rem;
    }

    .login-form .form-control {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.2s;
    }

    .login-form .form-control:focus {
        outline: none;
        border-color: #1a365d;
        box-shadow: 0 0 0 3px rgba(26, 54, 93, 0.1);
    }

    .login-btn {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .login-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(26, 54, 93, 0.3);
    }

    .login-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .alert {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .alert-danger {
        background: #fed7d7;
        color: #c53030;
        border: 1px solid #feb2b2;
    }

    .alert-warning {
        background: #fefcbf;
        color: #744210;
        border: 1px solid #f6e05e;
    }

    .login-footer {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e2e8f0;
        text-align: center;
    }

    .login-footer a {
        color: #1a365d;
        text-decoration: none;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .login-footer a:hover {
        text-decoration: underline;
    }

    .security-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
        font-size: 0.8rem;
        color: #a0aec0;
    }
    </style>
</head>
<body class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <div class="login-logo-icon">🚕</div>
            <h1>Taxi Julien</h1>
            <p>Back-Office Administration</p>
        </div>

        <?php if ($error): ?>
        <div class="alert <?= $rateLimited ? 'alert-warning' : 'alert-danger' ?>">
            <span><?= $rateLimited ? '⏱️' : '⚠️' ?></span>
            <span><?= e($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="username">Nom d'utilisateur</label>
                <input type="text"
                       id="username"
                       name="username"
                       class="form-control"
                       required
                       autofocus
                       autocomplete="username"
                       value="<?= e($_POST['username'] ?? '') ?>"
                       <?= $rateLimited ? 'disabled' : '' ?>>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Mot de passe</label>
                <input type="password"
                       id="password"
                       name="password"
                       class="form-control"
                       required
                       autocomplete="current-password"
                       <?= $rateLimited ? 'disabled' : '' ?>>
            </div>

            <button type="submit" class="login-btn" <?= $rateLimited ? 'disabled' : '' ?>>
                <span>🔐</span>
                <span>Se connecter</span>
            </button>
        </form>

        <div class="security-badge">
            <span>🔒</span>
            <span>Connexion sécurisée</span>
        </div>

        <div class="login-footer">
            <a href="../index.html">
                <span>←</span>
                <span>Retour au site</span>
            </a>
        </div>
    </div>
</body>
</html>
