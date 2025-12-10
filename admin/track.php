<?php
/**
 * Endpoint de tracking des visites
 * À appeler via JavaScript depuis les pages du site
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

// Récupérer les données
$input = json_decode(file_get_contents('php://input'), true);

$page = $input['page'] ?? $_SERVER['HTTP_REFERER'] ?? '';
$referer = $input['referer'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Nettoyer la page
$page = parse_url($page, PHP_URL_PATH);
$page = str_replace(['/', '.html', '.php'], '', $page);
if (empty($page)) $page = 'index';

// Éviter les bots connus
$bots = ['bot', 'crawler', 'spider', 'curl', 'wget'];
$isBot = false;
foreach ($bots as $bot) {
    if (stripos($userAgent, $bot) !== false) {
        $isBot = true;
        break;
    }
}

if (!$isBot) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO visits (page, ip, user_agent, referer) VALUES (?, ?, ?, ?)");
        $stmt->execute([$page, $ip, $userAgent, $referer]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Bot detected']);
}
