<?php
ini_set("display_errors", 0);
error_reporting(0);
// ajax_save_columns.php
require_once 'db.php';
require_once 'helpers.php';
requireLogin();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception(t('error_invalid_data'));
    }
    
    $order = $input['order'] ?? [];
    $visibility = $input['visibility'] ?? [];
    $labels = $input['labels'] ?? [];
    
    // In Session speichern
    $_SESSION['spalten_order'] = $order;
    $_SESSION['spalten_labels'] = $labels;
    $_SESSION['spalten_auswahl'] = $visibility;
    
    // In DB speichern
    $spaltenData = [
        'order' => $order,
        'labels' => $labels,
        'visibility' => $visibility
    ];
    
    $spaltenJson = json_encode($spaltenData);
    
    $stmt = $pdo->prepare("UPDATE users SET spalten_auswahl = ? WHERE username = ?");
    $stmt->execute([$spaltenJson, $_SESSION['username']]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
