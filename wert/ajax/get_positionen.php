<?php
ini_set("display_errors", 0);
error_reporting(0);
// ajax/get_positionen.php
// Gibt JSON-Array der Positionen für einen gegebenen Raum zurück.
require_once '../db.php';
require_once '../helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Session-Check ohne Redirect (AJAX darf keine HTML-Redirects machen)
if (empty($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$raum_id = (int)($_GET['raum_id'] ?? 0);

if (!$raum_id) {
    echo json_encode([]);
    exit;
}

try {
    $positionen = $db->select(
        "SELECT id, name FROM positionen WHERE raum_id = ? ORDER BY name",
        [$raum_id]
    );
    echo json_encode($positionen);
} catch (Exception $e) {
    echo json_encode([]);
}
