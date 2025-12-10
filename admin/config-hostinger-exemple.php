<?php
/**
 * CONFIGURATION BACK-OFFICE TAXI JULIEN - HOSTINGER
 *
 * ⚠️ IMPORTANT :
 * 1. Copiez ce contenu dans config.php
 * 2. Remplacez les valeurs par vos identifiants Hostinger
 * 3. Supprimez ce fichier après configuration
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CONFIGURATION DE LA BASE DE DONNÉES
// ============================================
// ⚠️ Remplacez ces valeurs par vos identifiants Hostinger

define('DB_HOST', 'localhost');  // Généralement 'localhost' chez Hostinger
define('DB_NAME', 'u123456789_taxi');  // ⚠️ REMPLACEZ par votre nom de base (visible dans hPanel)
define('DB_USER', 'u123456789_admin');  // ⚠️ REMPLACEZ par votre nom d'utilisateur
define('DB_PASS', 'VotreMo7DeP@sse');  // ⚠️ REMPLACEZ par votre mot de passe
define('DB_CHARSET', 'utf8mb4');

// ============================================
// PARAMÈTRES DE SÉCURITÉ
// ============================================
define('ADMIN_PATH', '/admin');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', '/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// ============================================
// RESTE DU FICHIER (NE PAS MODIFIER)
// ============================================

// Connexion à la base de données
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}

// Fonction helper pour obtenir la connexion DB
function getDB() {
    return Database::getInstance()->getConnection();
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

// Fonction pour vérifier si l'utilisateur est admin
function isAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
}

// Fonction pour rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Fonction pour échapper les données HTML
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Fonction pour afficher un message flash
function setFlash($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function getFlash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = [
            'type' => $_SESSION['flash_type'] ?? 'info',
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

// Fuseau horaire
date_default_timezone_set('Europe/Paris');
