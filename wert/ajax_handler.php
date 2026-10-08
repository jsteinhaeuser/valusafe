<?php
ini_set("display_errors", 0);
error_reporting(0);
// ajax_handler.php
require_once 'db.php';
require_once 'helpers.php';
requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Wird von der Oberflaeche derzeit nicht aufgerufen, ist aber erreichbar:
// "nur eigene" gilt auch hier (bis 4.3.32 lieferte jede Aktion alle).
[$ownSql, $ownParams] = nurEigeneSql('w');

try {
    switch ($action) {
        case 'quick_search':
            $query = trim($_POST['query'] ?? '');
            
            if (strlen($query) < 2) {
                echo json_encode(['success' => false, 'message' => 'Suchbegriff zu kurz']);
                exit;
            }
            
            $sql = "SELECT w.id, w.name, o.name as ort_name, k.name as kategorie_name, w.preis, w.bild
                    FROM wertsachen w
                    LEFT JOIN raeume o ON w.raum_id = o.id
                    LEFT JOIN kategorien k ON w.kategorie_id = k.id
                    WHERE (w.name LIKE ? OR w.notizen LIKE ?)" . $ownSql . "
                    ORDER BY w.name
                    LIMIT 10";
            
            $searchParam = "%$query%";
            $results = $db->select($sql, array_merge([$searchParam, $searchParam], $ownParams));
            
            echo json_encode([
                'success' => true,
                'results' => $results,
                'count' => count($results)
            ]);
            break;
            
        case 'get_statistics':
            // Instanzweite Summen - wie die Statistikseite nur fuer Admins.
            if (!isAdmin()) {
                echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
                exit;
            }
            $stats = [
                'total_items' => $db->selectOne("SELECT COUNT(*) as count FROM wertsachen")['count'],
                'total_value' => $db->selectOne("SELECT SUM(preis) as sum FROM wertsachen")['sum'] ?? 0,
                'locations' => $db->selectOne("SELECT COUNT(*) as count FROM raeume")['count'],
                'categories' => $db->selectOne("SELECT COUNT(*) as count FROM kategorien")['count'],
                'items_with_image' => $db->selectOne("SELECT COUNT(*) as count FROM wertsachen WHERE bild IS NOT NULL AND bild != ''")['count']
            ];
            
            // Top 5 teuerste Items
            $stats['most_expensive'] = $db->select(
                "SELECT name, preis FROM wertsachen ORDER BY preis DESC LIMIT 5"
            );
            
            // Items pro Kategorie
            $stats['by_category'] = $db->select(
                "SELECT k.name, COUNT(w.id) as count 
                 FROM kategorien k 
                 LEFT JOIN wertsachen w ON k.id = w.kategorie_id 
                 GROUP BY k.id, k.name 
                 ORDER BY count DESC"
            );
            
            echo json_encode([
                'success' => true,
                'statistics' => $stats
            ]);
            break;
            
        case 'check_duplicate':
            $name = trim($_POST['name'] ?? '');
            $excludeId = intval($_POST['exclude_id'] ?? 0);
            
            $sql = "SELECT w.id, w.name FROM wertsachen w WHERE w.name LIKE ?" . $ownSql;
            $params = array_merge(["%$name%"], $ownParams);
            
            if ($excludeId > 0) {
                $sql .= " AND w.id != ?";
                $params[] = $excludeId;
            }
            
            $duplicates = $db->select($sql, $params);
            
            echo json_encode([
                'success' => true,
                'has_duplicates' => !empty($duplicates),
                'duplicates' => $duplicates
            ]);
            break;
            
        case 'validate_file':
            if (!isset($_FILES['file'])) {
                echo json_encode(['success' => false, 'message' => 'Keine Datei hochgeladen']);
                exit;
            }
            
            $validation = Security::validateFileUpload($_FILES['file']);
            
            if ($validation['valid']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Datei ist gültig',
                    'size' => $_FILES['file']['size'],
                    'type' => $validation['mime']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'errors' => $validation['errors']
                ]);
            }
            break;
            
        case 'get_item_details':
            $id = intval($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Ungültige ID']);
                exit;
            }
            
            $item = loadWertsachenWithDetails($db, $id);
            
            if ($item && darfGegenstand($item)) {
                echo json_encode([
                    'success' => true,
                    'item' => $item
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Eintrag nicht gefunden'
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Unbekannte Aktion'
            ]);
            break;
    }
    
} catch (Exception $e) {
    Security::logSecurityEvent('ajax_error', [
        'action' => $action,
        'error' => $e->getMessage()
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Ein Fehler ist aufgetreten'
    ]);
}
?>