<?php
/**
 * CONFIGURATION BACK-OFFICE - TAXI JULIEN
 * Connexion Supabase
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration Supabase
define('SUPABASE_URL', 'https://oujweenjltjiyaoktkzs.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im91andlZW5qbHRqaXlhb2t0a3pzIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjUzNzA1OTMsImV4cCI6MjA4MDk0NjU5M30.om9CL0LA38oFKFaBOFo1Z-Aom-to6jToWsEpPaq0d9M');
define('SUPABASE_SERVICE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im91andlZW5qbHRqaXlhb2t0a3pzIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2NTM3MDU5MywiZXhwIjoyMDgwOTQ2NTkzfQ.pEHnnf1ZyBrZDWefJd_2o7pwDOZuowB0VvnniFi8er0');

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

/**
 * Classe Supabase Client
 */
class Supabase {
    private $url;
    private $key;

    public function __construct($useServiceKey = true) {
        $this->url = SUPABASE_URL;
        $this->key = $useServiceKey ? SUPABASE_SERVICE_KEY : SUPABASE_ANON_KEY;
    }

    /**
     * Requête GET (SELECT)
     */
    public function select($table, $query = '') {
        $url = $this->url . '/rest/v1/' . $table;
        if ($query) {
            $url .= '?' . $query;
        }
        return $this->request('GET', $url);
    }

    /**
     * Requête POST (INSERT)
     */
    public function insert($table, $data) {
        $url = $this->url . '/rest/v1/' . $table;
        return $this->request('POST', $url, $data, ['Prefer: return=representation']);
    }

    /**
     * Requête PATCH (UPDATE)
     */
    public function update($table, $query, $data) {
        $url = $this->url . '/rest/v1/' . $table . '?' . $query;
        return $this->request('PATCH', $url, $data, ['Prefer: return=representation']);
    }

    /**
     * Requête DELETE
     */
    public function delete($table, $query) {
        $url = $this->url . '/rest/v1/' . $table . '?' . $query;
        return $this->request('DELETE', $url);
    }

    /**
     * Upload fichier vers Storage
     */
    public function uploadFile($bucket, $path, $file, $contentType) {
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
    public function deleteFile($bucket, $path) {
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

        return json_decode($response, true);
    }

    /**
     * Requête HTTP générique
     */
    private function request($method, $url, $data = null, $extraHeaders = []) {
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
            CURLOPT_CUSTOMREQUEST => $method
        ];

        if ($data !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $decoded = json_decode($response, true);

        return [
            'data' => $decoded,
            'status' => $httpCode,
            'success' => $httpCode >= 200 && $httpCode < 300
        ];
    }
}

/**
 * Instance globale Supabase
 */
function supabase() {
    static $instance = null;
    if ($instance === null) {
        $instance = new Supabase(true);
    }
    return $instance;
}

/**
 * Vérifier connexion admin
 */
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Rediriger si non connecté
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Échapper HTML
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Messages flash
 */
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

/**
 * Récupérer un setting
 */
function getSetting($key, $default = '') {
    $result = supabase()->select('settings', 'key=eq.' . urlencode($key));
    if ($result['success'] && !empty($result['data'])) {
        return $result['data'][0]['value'] ?? $default;
    }
    return $default;
}

/**
 * Sauvegarder un setting
 */
function setSetting($key, $value) {
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
function generateSlug($text) {
    $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * URL publique Storage
 */
function storageUrl($path) {
    return SUPABASE_URL . '/storage/v1/object/public/images/' . $path;
}
