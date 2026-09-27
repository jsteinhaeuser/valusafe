<?php
/**
 * image_manager.php - AJAX Handler für Multi-Image Management
 * Aktionen: upload, delete, set_primary, reorder, get_images
 */
require_once 'db.php';
require_once 'helpers.php';
require_once 'helpers_images.php';
requireLogin();

// UPLOAD_URL berechnen wenn nicht definiert
if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', 'upload/');
}

header('Content-Type: application/json');
// Kein Caching für AJAX-Antworten — wichtig für Firefox
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// CSRF für AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCSRFToken($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => t('error_csrf_invalid')]);
        exit;
    }
}

$action = $_REQUEST['action'] ?? '';
$itemId = filter_var($_REQUEST['item_id'] ?? 0, FILTER_VALIDATE_INT);

if (!$itemId) {
    echo json_encode(['success' => false, 'error' => t('error_invalid_id')]);
    exit;
}

// Prüfe ob Item existiert und User Zugriff hat
$item = $db->selectOne("SELECT id, name FROM wertsachen WHERE id = ?", [$itemId]);
if (!$item) {
    echo json_encode(['success' => false, 'error' => 'Item nicht gefunden']);
    exit;
}

// Ein Fangnetz fuer den ganzen Verteiler: seit 4.3.18 werfen $db->execute()
// und $db->insert() weiter. Ohne dieses catch antwortete die Endstelle im
// Fehlerfall mit einer PHP-Fehlerseite statt mit JSON, und das JavaScript
// bekaeme unlesbaren Text. Die drei Stellen, an denen hier wertsachen.bild
// nachgezogen wird, sind der eigentliche Anlass: sie liefen bis 4.3.17 ins
// Leere, ohne dass es jemand erfuhr.
try {
switch ($action) {
    
    // =============================================
    // GET: Alle Bilder eines Items
    // =============================================
    case 'get_images':
        $images = getItemImages($itemId);
        $result = [];
        
        foreach ($images as $img) {
            $url = UPLOAD_URL . $img['filename'];
            $exists = file_exists(UPLOAD_DIR . $img['filename']);
            $result[] = [
                'id'         => $img['id'],
                'filename'   => $img['filename'],
                'url'        => $exists ? $url : '',
                'is_primary' => (bool)$img['is_primary'],
                'sort_order' => $img['sort_order'],
            ];
        }
        
        echo json_encode(['success' => true, 'images' => $result]);
        break;
    
    // =============================================
    // POST: Neue Bilder hochladen
    // =============================================
    case 'upload':
        if (empty($_FILES['images'])) {
            echo json_encode(['success' => false, 'error' => 'Keine Dateien']);
            exit;
        }
        
        $maxImages = 10;
        $currentCount = countItemImages($itemId);
        
        if ($currentCount >= $maxImages) {
            echo json_encode([
                'success' => false,
                'error' => "Maximum von $maxImages Bildern erreicht"
            ]);
            exit;
        }
        
        $files = $_FILES['images'];
        $uploaded = [];
        $errors = [];
        
        // Normalisiere Files Array (single oder multiple)
        $fileList = [];
        if (is_array($files['tmp_name'])) {
            for ($i = 0; $i < count($files['tmp_name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $fileList[] = [
                        'tmp_name' => $files['tmp_name'][$i],
                        'name'     => $files['name'][$i],
                        'size'     => $files['size'][$i],
                        'error'    => $files['error'][$i],
                    ];
                }
            }
        } else {
            if ($files['error'] === UPLOAD_ERR_OK) {
                $fileList[] = $files;
            }
        }
        
        foreach ($fileList as $file) {
            // Limit prüfen
            if (($currentCount + count($uploaded)) >= $maxImages) {
                $errors[] = "Maximum von $maxImages Bildern erreicht";
                break;
            }
            
            $result = uploadSingleImage($file['tmp_name'], $file['name'], $file['size']);
            
            if ($result['success']) {
                $imageId = addItemImage($itemId, $result['filename']);
                
                if ($imageId) {
                    $uploaded[] = [
                        'id'         => $imageId,
                        'filename'   => $result['filename'],
                        'url'        => UPLOAD_URL . $result['filename'],
                        'is_primary' => ($currentCount + count($uploaded) === 0),
                        'sort_order' => $currentCount + count($uploaded),
                    ];
                    
                    // Sync: Wenn erstes Bild, auch wertsachen.bild updaten
                    if ($currentCount === 0 && count($uploaded) === 1) {
                        $db->execute(
                            "UPDATE wertsachen SET bild = ? WHERE id = ?",
                            [$result['filename'], $itemId]
                        );
                    }
                } else {
                    $errors[] = "DB-Fehler bei " . $file['name'];
                    unlink(UPLOAD_DIR . $result['filename']);
                }
            } else {
                $errors[] = $result['error'];
            }
        }
        
        echo json_encode([
            'success'  => count($uploaded) > 0,
            'uploaded' => $uploaded,
            'errors'   => $errors,
            'total'    => $currentCount + count($uploaded),
        ]);
        break;
    
    // =============================================
    // POST: Bild löschen
    // =============================================
    case 'delete':
        $imageId = filter_var($_POST['image_id'] ?? 0, FILTER_VALIDATE_INT);
        
        if (!$imageId) {
            echo json_encode(['success' => false, 'error' => t('error_invalid_id')]);
            exit;
        }
        
        // Prüfe ob Bild zu diesem Item gehört
        $image = $db->selectOne(
            "SELECT * FROM item_images WHERE id = ? AND item_id = ?",
            [$imageId, $itemId]
        );
        
        if (!$image) {
            echo json_encode(['success' => false, 'error' => 'Bild nicht gefunden']);
            exit;
        }
        
        $wasPrimary = $image['is_primary'];
        $success = deleteItemImage($imageId);
        
        if ($success) {
            // Sync: wertsachen.bild updaten
            $newPrimary = getPrimaryImage($itemId);
            $db->execute(
                "UPDATE wertsachen SET bild = ? WHERE id = ?",
                [$newPrimary ? $newPrimary['filename'] : null, $itemId]
            );

            // Aktualisierte Bildliste direkt zurückgeben
            // → JS braucht keinen zweiten Request, kein Cache-Problem
            $updatedImages = getItemImages($itemId);
            $imageList = [];
            foreach ($updatedImages as $img) {
                $url    = UPLOAD_URL . $img['filename'];
                $exists = file_exists(UPLOAD_DIR . $img['filename']);
                $imageList[] = [
                    'id'         => $img['id'],
                    'filename'   => $img['filename'],
                    'url'        => $exists ? $url : '',
                    'is_primary' => (bool)$img['is_primary'],
                    'sort_order' => $img['sort_order'],
                ];
            }

            echo json_encode([
                'success'     => true,
                'new_primary' => $newPrimary ? $newPrimary['id'] : null,
                'images'      => $imageList,
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => t('error_delete_failed')]);
        }
        break;
    
    // =============================================
    // POST: Primärbild setzen
    // =============================================
    case 'set_primary':
        $imageId = filter_var($_POST['image_id'] ?? 0, FILTER_VALIDATE_INT);
        
        if (!$imageId) {
            echo json_encode(['success' => false, 'error' => t('error_invalid_id')]);
            exit;
        }
        
        // Prüfe ob Bild zu diesem Item gehört
        $image = $db->selectOne(
            "SELECT * FROM item_images WHERE id = ? AND item_id = ?",
            [$imageId, $itemId]
        );
        
        if (!$image) {
            echo json_encode(['success' => false, 'error' => 'Bild nicht gefunden']);
            exit;
        }
        
        $success = setPrimaryImage($imageId);
        
        if ($success) {
            // Sync: wertsachen.bild updaten
            $db->execute(
                "UPDATE wertsachen SET bild = ? WHERE id = ?",
                [$image['filename'], $itemId]
            );
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Fehler beim Setzen']);
        }
        break;
    
    // =============================================
    // POST: Reihenfolge ändern
    // =============================================
    case 'reorder':
        $imageIds = $_POST['image_ids'] ?? [];
        
        if (empty($imageIds) || !is_array($imageIds)) {
            echo json_encode(['success' => false, 'error' => t('error_no_ids')]);
            exit;
        }
        
        $imageIds = array_map('intval', $imageIds);
        $success = reorderItemImages($itemId, $imageIds);
        
        echo json_encode(['success' => $success]);
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Unbekannte Aktion']);
}
} catch (PDOException $e) {
    error_log('image_manager (' . $action . '): ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => t('error_generic')]);
}
