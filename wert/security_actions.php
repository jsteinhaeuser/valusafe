<?php
// security_actions.php - Sicherheits-Aktionen verarbeiten
require_once 'db.php';
require_once 'helpers_permissions.php';

requireLogin();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php?tab=security');
    exit;
}

validateRequest();

$action = $_POST['action'] ?? '';
$message = '';
$error = '';

switch ($action) {
    case 'delete_all_items':
        $confirmText = trim($_POST['confirm_text'] ?? '');
        
        if ($confirmText !== 'LÖSCHEN') {
            $error = 'Bestätigung fehlgeschlagen. Bitte geben Sie exakt "LÖSCHEN" ein.';
        } else {
            try {
                // Anzahl vor Löschen
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM wertsachen");
                $count = $stmt->fetch()['count'];
                
                // Löschen
                $pdo->exec("DELETE FROM wertsachen");
                
                $message = "$count Gegenstände wurden erfolgreich gelöscht.";
                
                // Log
                Security::logSecurityEvent('all_items_deleted', [
                    'count' => $count,
                    'user' => $_SESSION['username']
                ]);
                
            } catch (PDOException $e) {
                $error = 'Fehler beim Löschen: ' . $e->getMessage();
            }
        }
        break;
        
    case 'cleanup_database':
        try {
            $results = [];
            
            // Alte Logs löschen
            if (isset($_POST['delete_old_logs'])) {
                try {
                    $stmt = $pdo->exec("DELETE FROM security_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
                    $results[] = "$stmt alte Log-Einträge gelöscht";
                } catch (PDOException $e) {
                    // Tabelle existiert nicht
                }
            }
            
            // Tabellen optimieren
            if (isset($_POST['optimize_tables'])) {
                $tables = ['users', 'wertsachen', 'kategorien', 'raeume', 'standorte', 'positionen', 'item_images', 'wert_historie'];
                foreach ($tables as $table) {
                    try {
                        $pdo->exec("OPTIMIZE TABLE $table");
                    } catch (PDOException $e) {}
                }
                $results[] = "Datenbank-Tabellen optimiert";
            }
            
            // Verwaiste Dateien
            if (isset($_POST['clean_orphaned_files'])) {
                $cleaned = 0;
                
                if (is_dir(UPLOAD_DIR)) {
                    // Alle Dateien im Upload-Verzeichnis
                    $files = glob(UPLOAD_DIR . '*');
                    
                    // Verwendete Bilder aus wertsachen (Hauptbild)
                    $stmt = $pdo->query("SELECT DISTINCT bild FROM wertsachen WHERE bild IS NOT NULL AND bild != ''");
                    $usedMain = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Verwendete Bilder aus item_images (Multi-Upload)
                    $usedMulti = [];
                    try {
                        $stmt2 = $pdo->query("SELECT DISTINCT filename FROM item_images WHERE filename IS NOT NULL AND filename != ''");
                        $usedMulti = $stmt2->fetchAll(PDO::FETCH_COLUMN);
                    } catch (PDOException $e) {
                        // Tabelle existiert möglicherweise nicht auf älteren Instanzen
                    }

                    // Verwendete Bilder aus dokumente (Anhänge)
                    $usedDocs = [];
                    try {
                        $stmt3 = $pdo->query("SELECT DISTINCT filename FROM dokumente WHERE filename IS NOT NULL AND filename != ''");
                        $usedDocs = $stmt3->fetchAll(PDO::FETCH_COLUMN);
                    } catch (PDOException $e) {}

                    // Alle verwendeten Dateien zusammenführen
                    $usedImages = array_unique(array_merge($usedMain, $usedMulti, $usedDocs));
                    
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            $filename = basename($file);
                            if (!in_array($filename, $usedImages) && $filename !== '.htaccess') {
                                unlink($file);
                                $cleaned++;
                            }
                        }
                    }
                }
                
                $results[] = "$cleaned verwaiste Dateien gelöscht";
            }
            
            $message = implode('<br>', $results);
            
            Security::logSecurityEvent('database_cleanup', [
                'actions' => array_keys($_POST)
            ]);
            
        } catch (PDOException $e) {
            $error = 'Fehler bei Bereinigung: ' . $e->getMessage();
        }
        break;
}

$_SESSION['security_message'] = $message;
$_SESSION['security_error'] = $error;

header('Location: settings.php?tab=security');
exit;
?>