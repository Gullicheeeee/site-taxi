<?php
/**
 * CONFIGURATION BACK-OFFICE - TAXI JULIEN
 * Connexion Supabase + Sécurité + Helpers
 */
declare(strict_types=1);

// Configuration Supabase
define('SUPABASE_URL', 'https://oujweenjltjiyaoktkzs.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im91andlZW5qbHRqaXlhb2t0a3pzIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjUzNzA1OTMsImV4cCI6MjA4MDk0NjU5M30.om9CL0LA38oFKFaBOFo1Z-Aom-to6jToWsEpPaq0d9M');
define('SUPABASE_SERVICE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im91andlZW5qbHRqaXlhb2t0a3pzIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2NTM3MDU5MywiZXhwIjoyMDgwOTQ2NTkzfQ.pEHnnf1ZyBrZDWefJd_2o7pwDOZuowB0VvnniFi8er0');

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// ============================================================================
// SÉCURITÉ - Configuration des sessions
// ============================================================================

/**
 * Démarrage sécurisé de la session
 */
function secure_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Configuration sécurisée des sessions
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        // HTTPS seulement en production
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', '1');
        }

        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', '3600'); // 1 heure

        session_start();

        // Régénération de l'ID de session pour prévenir le fixation
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
            session_regenerate_id(true);
        } elseif (time() - $_SESSION['_created'] > 1800) {
            // Régénérer toutes les 30 minutes
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }
    }
}

// Démarrer la session
secure_session_start();

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ============================================================================
// CSRF Protection
// ============================================================================

/**
 * Génère un token CSRF
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Génère un champ input hidden avec le token CSRF
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Vérifie le token CSRF
 */
function verify_csrf(): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }

    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Vérifie le CSRF et arrête si invalide
 */
function require_csrf(): void {
    if (!verify_csrf()) {
        http_response_code(403);
        die('Token CSRF invalide. Veuillez rafraîchir la page et réessayer.');
    }
}

// ============================================================================
// Rate Limiting (pour login)
// ============================================================================

/**
 * Vérifie si l'IP a dépassé le nombre de tentatives
 */
function check_rate_limit(string $action = 'login', int $maxAttempts = 5, int $decayMinutes = 15): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rate_limit_' . $action . '_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
    }

    $data = $_SESSION[$key];

    // Réinitialiser si la période est expirée
    if (time() - $data['first_attempt'] > $decayMinutes * 60) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
        return true;
    }

    // Vérifier le nombre de tentatives
    return $data['attempts'] < $maxAttempts;
}

/**
 * Incrémente le compteur de tentatives
 */
function increment_rate_limit(string $action = 'login'): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rate_limit_' . $action . '_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
    }

    $_SESSION[$key]['attempts']++;
}

/**
 * Réinitialise le rate limit après un succès
 */
function reset_rate_limit(string $action = 'login'): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rate_limit_' . $action . '_' . md5($ip);
    unset($_SESSION[$key]);
}

// ============================================================================
// Classe Supabase Client
// ============================================================================

class Supabase {
    private string $url;
    private string $key;

    public function __construct(bool $useServiceKey = true) {
        $this->url = SUPABASE_URL;
        $this->key = $useServiceKey ? SUPABASE_SERVICE_KEY : SUPABASE_ANON_KEY;
    }

    /**
     * Requête GET (SELECT)
     */
    public function select(string $table, string $query = ''): array {
        $url = $this->url . '/rest/v1/' . $table;
        if ($query) {
            $url .= '?' . $query;
        }
        return $this->request('GET', $url);
    }

    /**
     * Requête POST (INSERT)
     */
    public function insert(string $table, array $data): array {
        $url = $this->url . '/rest/v1/' . $table;
        return $this->request('POST', $url, $data, ['Prefer: return=representation']);
    }

    /**
     * Requête PATCH (UPDATE)
     */
    public function update(string $table, string $query, array $data): array {
        $url = $this->url . '/rest/v1/' . $table . '?' . $query;
        return $this->request('PATCH', $url, $data, ['Prefer: return=representation']);
    }

    /**
     * Requête DELETE
     */
    public function delete(string $table, string $query): array {
        $url = $this->url . '/rest/v1/' . $table . '?' . $query;
        return $this->request('DELETE', $url);
    }

    /**
     * Upload fichier vers Storage
     */
    public function uploadFile(string $bucket, string $path, string $file, string $contentType): array {
        $url = $this->url . '/storage/v1/object/' . $bucket . '/' . $path;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => file_get_contents($file),
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->key,
                'Authorization: Bearer ' . $this->key,
                'Content-Type: ' . $contentType,
                'x-upsert: true'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'url' => $this->url . '/storage/v1/object/public/' . $bucket . '/' . $path
            ];
        }

        return ['success' => false, 'error' => $response];
    }

    /**
     * Supprimer fichier du Storage
     */
    public function deleteFile(string $bucket, string $path): ?array {
        $url = $this->url . '/storage/v1/object/' . $bucket . '/' . $path;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->key,
                'Authorization: Bearer ' . $this->key
            ]
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Requête HTTP générique
     */
    private function request(string $method, string $url, ?array $data = null, array $extraHeaders = []): array {
        $ch = curl_init();

        $headers = [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json'
        ];

        $headers = array_merge($headers, $extraHeaders);

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 30
        ];

        if ($data !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'data' => null,
                'status' => 0,
                'success' => false,
                'error' => $error
            ];
        }

        $decoded = json_decode($response, true);

        return [
            'data' => $decoded,
            'status' => $httpCode,
            'success' => $httpCode >= 200 && $httpCode < 300
        ];
    }
}

// ============================================================================
// Instance globale Supabase
// ============================================================================

function supabase(): Supabase {
    static $instance = null;
    if ($instance === null) {
        $instance = new Supabase(true);
    }
    return $instance;
}

// ============================================================================
// Authentification
// ============================================================================

/**
 * Vérifier si l'utilisateur est connecté
 */
function isLoggedIn(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Rediriger si non connecté
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Récupérer l'utilisateur courant
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? null,
        'role' => $_SESSION['admin_role'] ?? 'editor'
    ];
}

/**
 * Vérifier si l'utilisateur a un rôle spécifique
 */
function hasRole(string $role): bool {
    $user = getCurrentUser();
    if (!$user) return false;

    $roleHierarchy = ['admin' => 4, 'seo_manager' => 3, 'editor' => 2, 'author' => 1];
    $userLevel = $roleHierarchy[$user['role']] ?? 0;
    $requiredLevel = $roleHierarchy[$role] ?? 0;

    return $userLevel >= $requiredLevel;
}

// ============================================================================
// Helpers utilitaires
// ============================================================================

/**
 * Échapper HTML
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Messages flash
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Récupérer un setting
 */
function getSetting(string $key, string $default = ''): string {
    $result = supabase()->select('settings', 'key=eq.' . urlencode($key));
    if ($result['success'] && !empty($result['data'])) {
        return $result['data'][0]['value'] ?? $default;
    }
    return $default;
}

/**
 * Sauvegarder un setting
 */
function setSetting(string $key, string $value): array {
    // Essayer update puis insert
    $result = supabase()->update('settings', 'key=eq.' . urlencode($key), [
        'value' => $value,
        'updated_at' => date('c')
    ]);

    if (!$result['success'] || empty($result['data'])) {
        $result = supabase()->insert('settings', [
            'key' => $key,
            'value' => $value
        ]);
    }

    return $result;
}

/**
 * Générer un slug
 */
function generateSlug(string $text): string {
    // Translittération si disponible
    if (function_exists('transliterator_transliterate')) {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
    } else {
        $text = strtolower($text);
        $text = preg_replace('/[àáâãäå]/u', 'a', $text);
        $text = preg_replace('/[èéêë]/u', 'e', $text);
        $text = preg_replace('/[ìíîï]/u', 'i', $text);
        $text = preg_replace('/[òóôõö]/u', 'o', $text);
        $text = preg_replace('/[ùúûü]/u', 'u', $text);
        $text = preg_replace('/[ç]/u', 'c', $text);
        $text = preg_replace('/[ñ]/u', 'n', $text);
    }

    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * URL publique Storage
 */
function storageUrl(string $path): string {
    return SUPABASE_URL . '/storage/v1/object/public/images/' . $path;
}

// ============================================================================
// Logging d'activité
// ============================================================================

/**
 * Logger une action admin
 */
function logActivity(string $action, string $entityType, ?string $entityId = null, ?array $changes = null): void {
    $user = getCurrentUser();
    if (!$user) return;

    supabase()->insert('activity_log', [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'changes' => $changes ? json_encode($changes) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'created_at' => date('c')
    ]);
}

/**
 * Récupérer les dernières activités
 */
function getRecentActivity(int $limit = 20): array {
    $result = supabase()->select('activity_log', 'order=created_at.desc&limit=' . $limit);
    return $result['success'] ? $result['data'] : [];
}
