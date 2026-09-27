<?php
/**
 * set_language.php — AJAX-Endpunkt für Sprachwechsel
 * POST: lang=de|en|fr|..., csrf_token=...
 */
require_once 'db.php';
requireLogin();

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// CSRF prüfen
$token = $_POST['csrf_token'] ?? '';
if (!Security::validateCSRFToken($token)) {
    echo json_encode(['success' => false, 'error' => 'CSRF invalid']);
    exit;
}

$allowed = ['de', 'en', 'fr', 'es', 'it', 'nl', 'pl', 'pt', 'tr'];
$lang    = $_POST['lang'] ?? '';

if (!in_array($lang, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid language']);
    exit;
}

$_SESSION['lang'] = $lang;

try {
    $db->execute(
        "UPDATE users SET lang = ? WHERE username = ?",
        [$lang, $_SESSION['username']]
    );
} catch (Exception $e) {
    // Spalte existiert noch nicht — Session-Wert reicht
}

echo json_encode(['success' => true, 'lang' => $lang]);
