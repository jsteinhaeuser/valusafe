<?php
ini_set("display_errors", 0);
error_reporting(0);
// ajax_reset_columns.php
require_once 'db.php';
require_once 'helpers.php';
requireLogin();

header('Content-Type: application/json');

try {
    // Session zurücksetzen
    unset($_SESSION['spalten_order']);
    unset($_SESSION['spalten_labels']);
    unset($_SESSION['spalten_auswahl']);
    
    // DB zurücksetzen
    $stmt = $pdo->prepare("UPDATE users SET spalten_auswahl = NULL WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
