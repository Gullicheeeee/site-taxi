<?php
require_once 'config.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        // Vérifier dans Supabase
        $result = supabase()->select('admins', 'username=eq.' . urlencode($username));

        if ($result['success'] && !empty($result['data'])) {
            $admin = $result['data'][0];

            if (password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];

                // Mettre à jour last_login
                supabase()->update('admins', 'id=eq.' . $admin['id'], [
                    'last_login' => date('c')
                ]);

                header('Location: index.php');
                exit;
            }
        }
        $error = 'Identifiants incorrects';
    } else {
        $error = 'Veuillez remplir tous les champs';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Taxi Julien Admin</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <h1>Taxi Julien</h1>
            <p style="color: #718096; margin-top: 0.5rem;">Back-Office Administration</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Nom d'utilisateur</label>
                <input type="text" name="username" class="form-control" required autofocus
                       value="<?= e($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Se connecter
            </button>
        </form>

        <p style="text-align: center; margin-top: 1.5rem; color: #a0aec0; font-size: 0.875rem;">
            Identifiants par défaut : admin / admin123
        </p>
    </div>
</body>
</html>
