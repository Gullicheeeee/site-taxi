<?php
/**
 * CONFIGURATION BACK-OFFICE - TAXI JULIEN
 * Base de données SQLite (pas de configuration MySQL requise)
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration
define('SITE_NAME', 'Taxi Julien');
define('ADMIN_VERSION', '1.0.0');
define('DATA_PATH', __DIR__ . '/../data/');
define('DB_FILE', DATA_PATH . 'database.sqlite');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'admin/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Créer les dossiers nécessaires
if (!file_exists(DATA_PATH)) mkdir(DATA_PATH, 0755, true);
if (!file_exists(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);

// Connexion SQLite
function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new PDO('sqlite:' . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        initDatabase($db);
    }
    return $db;
}

// Initialiser la base de données
function initDatabase($db) {
    // Table utilisateurs
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME
    )");

    // Table pages (SEO)
    $db->exec("CREATE TABLE IF NOT EXISTS pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT UNIQUE NOT NULL,
        filename TEXT NOT NULL,
        title TEXT NOT NULL,
        meta_title TEXT,
        meta_description TEXT,
        meta_keywords TEXT,
        og_title TEXT,
        og_description TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Table blog
    $db->exec("CREATE TABLE IF NOT EXISTS blog_posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT UNIQUE NOT NULL,
        title TEXT NOT NULL,
        excerpt TEXT,
        content TEXT,
        featured_image TEXT,
        meta_title TEXT,
        meta_description TEXT,
        is_published INTEGER DEFAULT 0,
        published_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Table images
    $db->exec("CREATE TABLE IF NOT EXISTS images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        filename TEXT NOT NULL,
        original_name TEXT NOT NULL,
        alt_text TEXT,
        page_slug TEXT,
        section TEXT,
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Table settings
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Table tracking/analytics
    $db->exec("CREATE TABLE IF NOT EXISTS visits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        page TEXT,
        ip TEXT,
        user_agent TEXT,
        referer TEXT,
        visited_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Table contacts (formulaires)
    $db->exec("CREATE TABLE IF NOT EXISTS contacts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        email TEXT,
        phone TEXT,
        message TEXT,
        page_source TEXT,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Créer admin par défaut si pas d'utilisateurs
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO users (username, password, email) VALUES ('admin', '$hash', 'admin@taxijulien.fr')");
    }

    // Insérer les pages par défaut si vide
    $stmt = $db->query("SELECT COUNT(*) FROM pages");
    if ($stmt->fetchColumn() == 0) {
        $pages = [
            ['index', 'index.html', 'Accueil', 'Taxi Julien - Taxi Conventionné Martigues | Réservation 24/7', 'Taxi conventionné CPAM à Martigues. Service 24/7, aéroports, gares, longues distances.'],
            ['services', 'services.html', 'Nos Services', 'Tous nos Services - Taxi Julien Martigues', 'Découvrez tous nos services de taxi à Martigues.'],
            ['conventionne', 'conventionné.html', 'Transport Conventionné', 'Taxi Conventionné CPAM - Taxi Julien Martigues', 'Transport conventionné CPAM à Martigues.'],
            ['aeroports-gares', 'aeroports-gares.html', 'Aéroports & Gares', 'Transferts Aéroports Gares - Taxi Julien', 'Transferts aéroports et gares depuis Martigues.'],
            ['longues-distances', 'longues-distances.html', 'Longues Distances', 'Taxi Longues Distances - Taxi Julien', 'Service de taxi longues distances.'],
            ['courses-classiques', 'courses-classiques.html', 'Courses Classiques', 'Courses Classiques - Taxi Julien', 'Courses de taxi classiques à Martigues.'],
            ['mise-a-disposition', 'mise-a-disposition.html', 'Mise à Disposition', 'Mise à Disposition - Taxi Julien', 'Service de mise à disposition.'],
            ['a-propos', 'a-propos.html', 'À Propos', 'À Propos - Taxi Julien Martigues', 'Découvrez Taxi Julien.'],
            ['contact', 'contact.html', 'Contact', 'Contact - Taxi Julien Martigues', 'Contactez Taxi Julien.'],
            ['blog', 'blog.html', 'Blog', 'Blog - Taxi Julien', 'Actualités et conseils taxi.'],
            ['reservation', 'reservation.html', 'Réservation', 'Réserver un Taxi - Taxi Julien', 'Réservez votre taxi en ligne.'],
            ['simulateur', 'simulateur.html', 'Simulateur', 'Simulateur de Prix - Taxi Julien', 'Estimez le prix de votre course.'],
        ];
        $stmt = $db->prepare("INSERT INTO pages (slug, filename, title, meta_title, meta_description) VALUES (?, ?, ?, ?, ?)");
        foreach ($pages as $page) {
            $stmt->execute($page);
        }
    }

    // Settings par défaut
    $stmt = $db->query("SELECT COUNT(*) FROM settings");
    if ($stmt->fetchColumn() == 0) {
        $settings = [
            ['site_name', 'Taxi Julien'],
            ['phone', '01 23 45 67 89'],
            ['email', 'contact@taxijulien.fr'],
            ['address', 'Martigues, Bouches-du-Rhône (13)'],
            ['google_analytics', ''],
            ['facebook_pixel', ''],
        ];
        $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)");
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
    }
}

// Vérifier connexion
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Échapper HTML
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Messages flash
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Récupérer un setting
function getSetting($key, $default = '') {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM settings WHERE key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

// Sauvegarder un setting
function setSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES (?, ?, datetime('now'))");
    $stmt->execute([$key, $value]);
}
