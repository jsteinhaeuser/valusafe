<?php
/**
 * Multiple Images Helper Functions
 * Funktionen für Verwaltung mehrerer Bilder pro Item
 * Version: 1.0 - 2026-02-16
 */

/**
 * Hole alle Bilder eines Items
 */
function getItemImages($itemId) {
    global $db;
    
    try {
        $images = $db->select(
            "SELECT * FROM item_images 
             WHERE item_id = ? 
             ORDER BY sort_order ASC, created_at ASC",
            [$itemId]
        );
        
        return $images ?: [];
    } catch (PDOException $e) {
        error_log("Error loading item images: " . $e->getMessage());
        return [];
    }
}

/**
 * Hole das Primärbild eines Items
 */
function getPrimaryImage($itemId) {
    global $db;
    
    try {
        // Suche nach is_primary = 1
        $image = $db->selectOne(
            "SELECT * FROM item_images 
             WHERE item_id = ? AND is_primary = 1 
             LIMIT 1",
            [$itemId]
        );
        
        // Fallback: Erstes Bild
        if (!$image) {
            $image = $db->selectOne(
                "SELECT * FROM item_images 
                 WHERE item_id = ? 
                 ORDER BY sort_order ASC 
                 LIMIT 1",
                [$itemId]
            );
        }
        
        return $image;
    } catch (PDOException $e) {
        error_log("Error loading primary image: " . $e->getMessage());
        return null;
    }
}

/**
 * Füge neues Bild zu Item hinzu
 */
function addItemImage($itemId, $filename, $isPrimary = false) {
    global $db;
    
    try {
        // Hole aktuelle maximale sort_order
        $maxOrder = $db->selectOne(
            "SELECT MAX(sort_order) as max_order FROM item_images WHERE item_id = ?",
            [$itemId]
        );
        
        $nextOrder = ($maxOrder['max_order'] ?? -1) + 1;
        
        // Wenn erstes Bild, ist es automatisch primary
        $imageCount = $db->selectOne(
            "SELECT COUNT(*) as count FROM item_images WHERE item_id = ?",
            [$itemId]
        );
        
        if ($imageCount['count'] == 0) {
            $isPrimary = true;
        }
        
        // Wenn neues Primärbild, alte primary-Flags entfernen
        if ($isPrimary) {
            $db->execute(
                "UPDATE item_images SET is_primary = 0 WHERE item_id = ?",
                [$itemId]
            );
        }
        
        // Füge Bild hinzu
        $imageId = $db->insert(
            "INSERT INTO item_images (item_id, filename, sort_order, is_primary) 
             VALUES (?, ?, ?, ?)",
            [$itemId, $filename, $nextOrder, $isPrimary ? 1 : 0]
        );
        
        return $imageId;
    } catch (PDOException $e) {
        error_log("Error adding item image: " . $e->getMessage());
        return false;
    }
}

/**
 * Lösche Bild
 */
function deleteItemImage($imageId) {
    global $db;
    
    try {
        $image = $db->selectOne("SELECT * FROM item_images WHERE id = ?", [$imageId]);
        
        if (!$image) return false;
        
        // Lösche Datei
        $filePath = UPLOAD_DIR . $image['filename'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Lösche DB-Eintrag
        $db->execute("DELETE FROM item_images WHERE id = ?", [$imageId]);
        
        // Wenn primary, setze nächstes als primary
        if ($image['is_primary']) {
            $nextImage = $db->selectOne(
                "SELECT id FROM item_images WHERE item_id = ? ORDER BY sort_order ASC LIMIT 1",
                [$image['item_id']]
            );
            
            if ($nextImage) {
                $db->execute("UPDATE item_images SET is_primary = 1 WHERE id = ?", [$nextImage['id']]);
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error deleting item image: " . $e->getMessage());
        return false;
    }
}

/**
 * Lösche alle Bilder eines Items
 */
function deleteAllItemImages($itemId) {
    global $db;
    
    try {
        $images = getItemImages($itemId);
        
        foreach ($images as $image) {
            $filePath = UPLOAD_DIR . $image['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        $db->execute("DELETE FROM item_images WHERE item_id = ?", [$itemId]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error deleting all item images: " . $e->getMessage());
        return false;
    }
}

/**
 * Setze Bild als Primärbild
 */
function setPrimaryImage($imageId) {
    global $db;
    
    try {
        $image = $db->selectOne("SELECT item_id FROM item_images WHERE id = ?", [$imageId]);
        
        if (!$image) return false;
        
        $db->execute("UPDATE item_images SET is_primary = 0 WHERE item_id = ?", [$image['item_id']]);
        $db->execute("UPDATE item_images SET is_primary = 1 WHERE id = ?", [$imageId]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error setting primary image: " . $e->getMessage());
        return false;
    }
}

/**
 * Zähle Bilder eines Items
 */
function countItemImages($itemId) {
    global $db;
    
    try {
        $result = $db->selectOne("SELECT COUNT(*) as count FROM item_images WHERE item_id = ?", [$itemId]);
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error counting images: " . $e->getMessage());
        return 0;
    }
}

/**
 * Upload einzelnes Bild
 * Nutzt optimizeAndSaveImage() aus helpers.php - inkl. HEIC→JPG Konvertierung
 */
function uploadSingleImage($tmpName, $originalName, $size) {
    $result = ['success' => false, 'filename' => '', 'error' => ''];
    
    if ($size > MAX_FILE_SIZE) {
        $result['error'] = "Datei zu groß: " . basename($originalName);
        return $result;
    }
    
    // MIME-Type bestimmen
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpName);
    finfo_close($finfo);
    
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // HEIC/HEIF Korrektur (werden oft als application/octet-stream erkannt)
    if (in_array($extension, ['heic', 'heif'])) {
        $mimeType = 'image/heic';
    }
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        $result['error'] = "Ungültiger Dateityp: " . basename($originalName);
        return $result;
    }
    
    // Immer als JPG speichern (optimizeAndSaveImage konvertiert alles)
    $filename = md5(uniqid() . $originalName) . '_' . time() . '.jpg';
    $targetPath = UPLOAD_DIR . $filename;
    
    if (optimizeAndSaveImage($tmpName, $targetPath, $mimeType)) {
        $result['success'] = true;
        $result['filename'] = $filename;
    } else {
        $result['error'] = "Fehler beim Speichern: " . basename($originalName);
    }
    
    return $result;
}

/**
 * Konvertiere HEIC zu JPG - Fallback falls optimizeAndSaveImage nicht verfügbar
 */
function convertHeicToJpg($tmpName) {
    $result = ['success' => false, 'path' => ''];
    $outPath = sys_get_temp_dir() . '/' . uniqid() . '.jpg';
    
    if (extension_loaded('imagick')) {
        try {
            $imagick = new Imagick();
            $imagick->readImage($tmpName);
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(90);
            $imagick->writeImage($outPath);
            $imagick->destroy();
            if (file_exists($outPath)) {
                $result['success'] = true;
                $result['path'] = $outPath;
            }
        } catch (Exception $e) {
            error_log("Imagick HEIC convert error: " . $e->getMessage());
        }
    }
    
    return $result;
}
