<?php
/**
 * bulk_delete.php
 * Löscht mehrere Einträge auf einmal
 */

// Fehlerausgabe deaktivieren – diese Datei gibt nur JSON zurück
ini_set('display_errors', 0);
error_reporting(0);

ob_start();
require_once 'db.php';
require_once 'helpers.php';

// JSON-Header sofort setzen – vor jedem möglichen Output
header('Content-Type: application/json');

requireLogin();

// Nur Admins dürfen löschen
if (!canDelete()) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? [];
    
    if (empty($ids) || !is_array($ids)) {
        echo json_encode(['success' => false, 'error' => 'Keine IDs angegeben']);
        exit;
    }
    
    // IDs validieren (nur Zahlen)
    $ids = array_filter($ids, 'is_numeric');
    $ids = array_map('intval', $ids);
    
    if (empty($ids)) {
        echo json_encode(['success' => false, 'error' => t('bulk_invalid_ids')]);
        exit;
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Bilder + Bezeichnung vor dem Löschen holen (für Activity Log)
    $stmt = $pdo->prepare("SELECT id, bild, bezeichnung FROM wertsachen WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $itemsToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $bilder = array_column($itemsToDelete, 'bild');
    $namenById = array_column($itemsToDelete, 'bezeichnung', 'id');

    // Einträge löschen
    $stmt = $pdo->prepare("DELETE FROM wertsachen WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $affected = $stmt->rowCount();

    // Bilder löschen
    foreach ($bilder as $bild) {
        if ($bild && file_exists("upload/$bild")) {
            @unlink("upload/$bild");
        }
    }

    // Activity Log — 'deleted' (nicht 'delete'!), damit Filter/Anzeige im
    // Aktivitäts-Log identisch zum Einzel-Löschen (delete.php) funktionieren.
    foreach ($ids as $id) {
        $name = $namenById[$id] ?? ('Gegenstand (ID: ' . $id . ')');
        logActivity('deleted', 'wertsachen', $id, $name);
    }
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'affected' => $affected,
        'message' => sprintf(t('bulk_entries_deleted'), $affected)
    ]);
    
} catch (Exception $e) {
    error_log("Bulk Delete Error: " . $e->getMessage());
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler: ' . $e->getMessage()]);
}
