<?php
// helpers.php

/**
 * NULL-safe htmlspecialchars für PHP 8.4+
 * Verhindert Deprecated Warnings wenn NULL übergeben wird
 */
if (!function_exists('esc')) {
    function esc($value, $default = '') {
        if ($value === null || $value === '') {
            return htmlspecialchars($default, ENT_QUOTES, 'UTF-8');
        }
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Validiert Theme-Namen (XSS & Path Traversal Prevention)
 */
if (!function_exists('sanitizeTheme')) {
    function sanitizeTheme($theme) {
        // Whitelist erlaubter Themes
        $allowedThemes = ['cloud', 'ocean', 'forest', 'sunset', 'midnight', 'cherry', 'lavender', 'mint', 'sand', 'glass'];
    
        // Default wenn nicht in Whitelist oder leer
        if (empty($theme) || !in_array($theme, $allowedThemes, true)) {
            return 'cloud';
        }
    
        return $theme;
    }
}

/**
 * Zeigt Erfolgsmeldung an
 */
if (!function_exists('showSuccessMessage')) {
    function showSuccessMessage($message) {
        return '<div class="success-message">' . htmlspecialchars($message) . '</div>';
    }
}

/**
 * Zeigt Fehlermeldung an
 */
if (!function_exists('showErrorMessage')) {
    function showErrorMessage($message) {
        return '<div class="error-message">' . htmlspecialchars($message) . '</div>';
    }
}

/**
 * Zeigt mehrere Fehlermeldungen an
 */
if (!function_exists('showErrorMessages')) {
    function showErrorMessages($errors) {
        if (empty($errors)) return '';
    
        $html = '<div class="error-message"><ul>';
        foreach ($errors as $error) {
            $html .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $html .= '</ul></div>';
        return $html;
    }
}

/**
 * Formatiert Preis für Anzeige
 */
if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        return number_format($price, 2, ',', '.') . ' €';
    }
}

/**
 * Formatiert Datum für Anzeige
 */
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd.m.Y') {
        if (empty($date)) return '-';
        return date($format, strtotime($date));
    }
}

/**
 * Formatiert Datum und Zeit für Anzeige
 */
if (!function_exists('formatDateTime')) {
    function formatDateTime($datetime, $format = 'd.m.Y H:i') {
        if (empty($datetime)) return '-';
        return date($format, strtotime($datetime));
    }
}

/**
 * Kürzt Text auf bestimmte Länge
 */
if (!function_exists('truncateText')) {
    function truncateText($text, $length = 50) {
        $text = htmlspecialchars($text);
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '...' : $text;
    }
}

/**
 * Redirect mit Nachricht
 */
if (!function_exists('redirectWithMessage')) {
    function redirectWithMessage($url, $message, $type = 'success') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
        header('Location: ' . $url);
        exit;
    }
}

/**
 * Zeigt Flash-Nachricht an und löscht sie
 */
if (!function_exists('showFlashMessage')) {
    function showFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'success';
        
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
        
            if ($type === 'success') {
                return showSuccessMessage($message);
            } else {
                return showErrorMessage($message);
            }
        }
        return '';
    }
}

/**
 * Generiert Select-Options für Dropdown
 */
if (!function_exists('generateSelectOptions')) {
    function generateSelectOptions($items, $selectedId = null, $emptyOption = '-- Bitte wählen --') {
        $html = '<option value="">' . htmlspecialchars($emptyOption) . '</option>';
        foreach ($items as $item) {
            $selected = ($selectedId == $item['id']) ? 'selected' : '';
            $html .= '<option value="' . $item['id'] . '" ' . $selected . '>';
            $html .= htmlspecialchars($item['name']);
            $html .= '</option>';
        }
        return $html;
    }
}

/**
 * Validiert Wertsachen-Daten
 */
if (!function_exists('validateWertsachenData')) {
    function validateWertsachenData($data) {
        $errors = [];
    
        if (empty($data['name'])) {
            $errors[] = 'Name ist erforderlich';
        }
    
        if (strlen($data['name']) > 255) {
            $errors[] = 'Name ist zu lang (max. 255 Zeichen)';
        }
    
        if ($data['preis'] < 0) {
            $errors[] = 'Preis darf nicht negativ sein';
        }
    
        if (!empty($data['kaufdatum'])) {
            $date = DateTime::createFromFormat('Y-m-d', $data['kaufdatum']);
            if (!$date) {
                $errors[] = 'Ungültiges Kaufdatum';
            }
        }
    
        return $errors;
    }
}

/**
 * Handelt Datei-Upload für Bilder MIT OPTIMIERUNG
 */
if (!function_exists('handleFileUpload')) {
    function handleFileUpload($file, $oldFilename = null) {
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['filename' => $oldFilename];
        }
    
        $errors = [];
    
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Fehler beim Hochladen der Datei';
            return ['errors' => $errors];
        }
    
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = 'Datei ist zu groß (max. ' . (MAX_FILE_SIZE / 1024 / 1024) . ' MB)';
            return ['errors' => $errors];
        }
    
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
        // HEIC/HEIF werden manchmal als application/octet-stream erkannt
        // → Prüfe Dateiendung als Backup
        if (in_array($extension, ['heic', 'heif'])) {
            $mimeType = 'image/heic'; // Korrigiere MIME-Type
        }
    
        if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
            $errors[] = 'Ungültiger Dateityp (erkannt: ' . $mimeType . ')';
            return ['errors' => $errors];
        }
    
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            $errors[] = 'Ungültige Dateiendung (erkannt: ' . $extension . ')';
            return ['errors' => $errors];
        }
    
        // NEUE BILDOPTIMIERUNG
        $filename = md5(uniqid() . $file['name']) . '_' . time() . '.jpg';
        $destination = UPLOAD_DIR . $filename;
    
        // Bild optimieren und speichern
        if (!optimizeAndSaveImage($file['tmp_name'], $destination, $mimeType)) {
            $errors[] = 'Fehler beim Optimieren des Bildes';
            return ['errors' => $errors];
        }
    
        // Altes Bild löschen
        if ($oldFilename && file_exists(UPLOAD_DIR . $oldFilename)) {
            unlink(UPLOAD_DIR . $oldFilename);
        }
    
        return ['filename' => $filename];
    }
}

/**
 * Optimiert Bild: EXIF-Korrektur, Skalierung, Kompression
 * 
 * @param string $sourcePath Pfad zur Quell-Datei
 * @param string $destPath Pfad zur Ziel-Datei
 * @param string $mimeType MIME-Type des Bildes
 * @return bool Erfolg
 */
if (!function_exists('optimizeAndSaveImage')) {
    function optimizeAndSaveImage($sourcePath, $destPath, $mimeType) {
        // HEIC/HEIF → JPEG Konvertierung
        if ($mimeType === 'image/heic' || $mimeType === 'image/heif') {
            return convertHeicToJpeg($sourcePath, $destPath);
        }
    
        // Bild laden basierend auf Typ
        switch ($mimeType) {
            case 'image/jpeg':
                $source = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = @imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $source = @imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                $source = @imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }
    
        if (!$source) {
            return false;
        }
    
        // EXIF-Orientierung korrigieren
        $source = correctImageOrientation($source, $sourcePath);
    
        // Originalgröße
        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);
    
        // Zielgröße berechnen (max 1920px Breite)
        $maxWidth = 1920;
        $maxHeight = 1920;
    
        if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
            // Bild ist klein genug, nur komprimieren
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        } else {
            // Bild skalieren (Seitenverhältnis beibehalten)
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            $newWidth = round($originalWidth * $ratio);
            $newHeight = round($originalHeight * $ratio);
        }
    
        // Neues Bild erstellen
        $destination = imagecreatetruecolor($newWidth, $newHeight);
    
        // Transparenz für PNG/GIF erhalten
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
        }
    
        // Bild skalieren mit bester Qualität
        imagecopyresampled(
            $destination, $source,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );
    
        // Als JPEG mit 85% Qualität speichern
        $success = imagejpeg($destination, $destPath, 85);
    
        // Speicher freigeben
        imagedestroy($source);
        imagedestroy($destination);
    
        return $success;
    }
}

/**
 * Korrigiert Bild-Orientierung basierend auf EXIF-Daten
 * 
 * @param resource $image GD-Image-Resource
 * @param string $filepath Pfad zur Original-Datei
 * @return resource Korrigiertes Image
 */
if (!function_exists('correctImageOrientation')) {
    function correctImageOrientation($image, $filepath) {
        // Prüfe ob EXIF verfügbar ist
        if (!function_exists('exif_read_data')) {
            return $image;
        }
    
        // EXIF-Daten lesen
        $exif = @exif_read_data($filepath);
    
        if (!$exif || !isset($exif['Orientation'])) {
            return $image;
        }
    
        // Bild basierend auf Orientierung drehen
        switch ($exif['Orientation']) {
            case 3:
                $image = imagerotate($image, 180, 0);
                break;
            case 6:
                $image = imagerotate($image, -90, 0);
                break;
            case 8:
                $image = imagerotate($image, 90, 0);
                break;
        }
    
        return $image;
    }
}

/**
 * Lädt Wertsachen-Daten mit Joins
 */
if (!function_exists('loadWertsachenWithDetails')) {
    function loadWertsachenWithDetails($db, $id = null) {
        $sql = "SELECT w.*, o.name as ort_name, k.name as kategorie_name 
                FROM wertsachen w 
                LEFT JOIN raeume o ON w.raum_id = o.id 
                LEFT JOIN kategorien k ON w.kategorie_id = k.id";
    
        if ($id) {
            $sql .= " WHERE w.id = ?";
            return $db->selectOne($sql, [$id]);
        } else {
            $sql .= " ORDER BY w.name";
            return $db->select($sql);
        }
    }
}

/**
 * Bereinigt und validiert POST-Daten für Wertsachen
 */
if (!function_exists('sanitizeWertsachenInput')) {
    function sanitizeWertsachenInput($post) {
        // Preis: Komma durch Punkt ersetzen für FILTER_VALIDATE_FLOAT
        $preis = str_replace(',', '.', trim($post['preis'] ?? '0'));
        $preis = filter_var($preis, FILTER_VALIDATE_FLOAT);
        if ($preis === false) {
            $preis = 0;
        }
    
        return [
            'name' => trim($post['name'] ?? ''),
            'raum_id' => filter_var($post['raum_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
            'kategorie_id' => filter_var($post['kategorie_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
            'kaufdatum' => trim($post['kaufdatum'] ?? '') ?: null,
            'preis' => $preis,
            'notizen' => trim($post['notizen'] ?? '')
        ];
    }
}

/**
 * Loggt Fehler und zeigt Benutzer-freundliche Nachricht
 */
if (!function_exists('handleDatabaseError')) {
    function handleDatabaseError($e, $context = '') {
        Security::logSecurityEvent('database_error', [
            'context' => $context,
            'error' => $e->getMessage()
        ]);
        return 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
    }
}

/**
 * Prüft ob Datei existiert (für Bilder)
 */
if (!function_exists('imageExists')) {
    function imageExists($filename) {
        return !empty($filename) && file_exists(UPLOAD_DIR . $filename);
    }
}

/**
 * Kategorie-Name -> Tabler-Icon-Klasse (ti-xxx).
 * Zentrale Zuordnung, wird von allen 4 Bild-Fallback-Stellen genutzt
 * (Masonry, Detail-Pane, Tabellenansicht, Raumansicht).
 */
if (!function_exists('getCategoryIcon')) {
    function getCategoryIcon($kategorieName) {
        static $catIcons = [
            'elektronik' => 'ti-device-laptop',
            'schmuck'    => 'ti-diamond',
            'möbel'      => 'ti-armchair',
            'kleidung'   => 'ti-shirt',
            'sport'      => 'ti-run',
            'fahrrad'    => 'ti-bike',
            'auto'       => 'ti-car',
            'buch'       => 'ti-book',
            'kunst'      => 'ti-palette',
            'uhren'      => 'ti-clock',
            'musik'      => 'ti-music',
            'kamera'     => 'ti-camera',
            'werkzeug'   => 'ti-tool',
            'garten'     => 'ti-plant',
            'küche'      => 'ti-tools-kitchen-2',
            'sammlung'   => 'ti-stack',
        ];
        $catKey = strtolower($kategorieName ?? '');
        foreach ($catIcons as $k => $v) {
            if (str_contains($catKey, $k)) return $v;
        }
        return 'ti-package';
    }
}

/**
 * Farbiger Icon-Platzhalter (Kreis in Akzentfarbe + Kategorie-Icon),
 * ersetzt fehlende/urheberrechtlich unklare Fotos konsistent an allen
 * Anzeigeorten.
 */
if (!function_exists('renderCategoryPlaceholder')) {
    function renderCategoryPlaceholder($kategorieName, $extraClass = '') {
        $icon = getCategoryIcon($kategorieName);
        return '<div class="vs-cat-placeholder ' . htmlspecialchars($extraClass) . '">'
             . '<i class="ti ' . $icon . '" aria-hidden="true"></i>'
             . '</div>';
    }
}

/**
 * Generiert Image-Tag oder kategoriebezogenen Icon-Platzhalter
 */
if (!function_exists('renderThumbnail')) {
    function renderThumbnail($filename, $alt = 'Bild', $class = 'thumbnail', $kategorieName = null) {
        if (imageExists($filename)) {
            // Mit Lightbox: Bild ist klickbar und öffnet Vergrößerung
            return '<img src="upload/' . htmlspecialchars($filename) . '"
                         alt="' . htmlspecialchars($alt) . '"
                         title="' . htmlspecialchars($alt) . ' - Zum Vergrößern klicken"
                         class="' . $class . ' lightbox-trigger"
                         data-full="upload/' . htmlspecialchars($filename) . '"
                         loading="lazy">';
        } else {
            return renderCategoryPlaceholder($kategorieName, 'vs-cat-placeholder-sm');
        }
    }
}

/* ============================================
 * AKTIVITÄTS-LOG FUNKTIONEN
 * ============================================ */

/**
 * Loggt eine Aktivität im System
 * 
 * @param string $action Art der Aktion (created, updated, deleted, etc.)
 * @param string $table Tabellenname (wertsachen, kategorien, etc.)
 * @param int|null $recordId ID des betroffenen Eintrags
 * @param string|null $recordName Name für bessere Lesbarkeit
 * @param array|null $oldValues Alte Werte (bei UPDATE)
 * @param array|null $newValues Neue Werte (bei CREATE/UPDATE)
 */
if (!function_exists('logActivity')) {
    function logActivity($action, $table, $recordId = null, $recordName = null, $oldValues = null, $newValues = null) {
        global $db;
    
        // Nur loggen wenn User eingeloggt ist
        if (!isset($_SESSION['user_id'])) {
            return;
        }
    
        try {
            // FIXED: Deutsche Spaltennamen verwenden (aktion, tabelle, datensatz_id, bezeichnung, alt_wert, neu_wert, ip_adresse)
            $db->execute(
                "INSERT INTO activity_log 
                 (user_id, aktion, tabelle, datensatz_id, bezeichnung, alt_wert, neu_wert, ip_adresse) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $_SESSION['user_id'],
                    $action,
                    $table,
                    $recordId,
                    $recordName,
                    $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                    $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]
            );
        } catch (PDOException $e) {
            // Log-Fehler sollen den normalen Ablauf nicht stören
            error_log("Activity Log Error: " . $e->getMessage());
        }
    }
}

/**
 * Gibt Icon für Aktion zurück
 */
if (!function_exists('getActionIcon')) {
    function getActionIcon($action) {
        $icons = [
            'created' => '➕',
            'updated' => '✏️',
            'deleted' => '🗑️',
            'uploaded' => '📤',
            'downloaded' => '📥',
            'exported' => '📄',
            'login' => '🔓',
            'logout' => '🔒'
        ];
        return $icons[$action] ?? '📝';
    }
}

/**
 * Gibt CSS-Klasse für Aktion zurück
 */
if (!function_exists('getActionClass')) {
    function getActionClass($action) {
        $classes = [
            'created' => 'action-created',
            'updated' => 'action-updated',
            'deleted' => 'action-deleted',
            'uploaded' => 'action-uploaded',
            'exported' => 'action-exported'
        ];
        return $classes[$action] ?? 'action-default';
    }
}

/**
 * Gibt deutschen Text für Aktion zurück
 */
if (!function_exists('getActionText')) {
    function getActionText($action) {
        $keyMap = [
            'created'    => 'action_created',
            'updated'    => 'action_updated',
            'deleted'    => 'action_deleted',
            'uploaded'   => 'action_uploaded',
            'downloaded' => 'action_downloaded',
            'exported'   => 'action_exported',
            'login'      => 'action_login',
            'logout'     => 'action_logout',
        ];
        if (isset($keyMap[$action]) && function_exists('t')) {
            return t($keyMap[$action]);
        }
        return ucfirst($action);
    }
}

/**
 * Formatiert Änderungen als HTML
 */
if (!function_exists('formatChanges')) {
    function formatChanges($oldValuesJson) {
        if (empty($oldValuesJson)) return '';
    
        $data = json_decode($oldValuesJson, true);
        if (!$data) return '';
    
        // Feld-Namen zu lesbaren Labels
        $fieldLabels = [
            'name'         => function_exists('t') ? t('field_name')        : 'Name',
            'raum_id'       => function_exists('t') ? t('field_location')    : 'Ort',
            'kategorie_id' => function_exists('t') ? t('field_category')    : 'Kategorie',
            'preis'        => function_exists('t') ? t('field_price')       : 'Preis',
            'kaufdatum'    => function_exists('t') ? t('field_purchase_date')        : 'Kaufdatum',
            'notizen'      => function_exists('t') ? t('field_notes')       : 'Notizen',
            'beschreibung' => function_exists('t') ? t('field_description') : 'Beschreibung',
            'bild'         => function_exists('t') ? t('field_image')       : 'Bild',
            'hidden'       => function_exists('t') ? t('field_hidden')      : 'Verborgen',
        ];
        $emptyLabel = function_exists('t') ? t('label_empty') : '–';
    
        // Einfache Anzeige wenn nur Werte
        if (!isset($data['changes'])) {
            $html = '<div class="activity-details"><ul>';
            foreach ($data as $key => $value) {
                if ($key === 'changes') continue;
                $label = $fieldLabels[$key] ?? ucfirst($key);
                $html .= '<li><strong>' . htmlspecialchars($label) . ':</strong> ' . htmlspecialchars($value) . '</li>';
            }
            $html .= '</ul></div>';
            return $html;
        }
    
        // Detaillierte Änderungen
        $html = '<div class="activity-details"><ul class="changes-list">';
        foreach ($data['changes'] as $field => $values) {
            $label = $fieldLabels[$field] ?? ucfirst($field);
            $html .= '<li>';
            $html .= '<strong>' . htmlspecialchars($label) . ':</strong> ';
            $html .= '<span class="old-value">' . htmlspecialchars($values['old'] ?? $emptyLabel) . '</span>';
            $html .= ' → ';
            $html .= '<span class="new-value">' . htmlspecialchars($values['new'] ?? $emptyLabel) . '</span>';
            $html .= '</li>';
        }
        $html .= '</ul></div>';
        return $html;
    }
}

/**
 * Erstellt Diff zwischen alten und neuen Werten
 */
if (!function_exists('createValueDiff')) {
    function createValueDiff($oldData, $newData) {
        $changes = [];
    
        $fieldsToCompare = ['name', 'raum_id', 'kategorie_id', 'preis', 'kaufdatum', 'notizen'];
    
        foreach ($fieldsToCompare as $field) {
            $oldValue = $oldData[$field] ?? null;
            $newValue = $newData[$field] ?? null;
        
            // Normalisiere Werte für Vergleich
            if ($oldValue != $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }
    
        return $changes;
    }
}

/**
 * Löscht alte Activity Logs (Cleanup)
 * 
 * @param int $days Tage, die behalten werden sollen
 * @return int Anzahl gelöschter Einträge
 */
if (!function_exists('cleanupOldActivityLogs')) {
    /**
     * Loescht Eintraege aus dem Aktivitaetsprotokoll.
     *
     * $days = 0 loescht alles, sonst alles aelter als $days Tage.
     *
     * Dies ist die einzige Stelle, die aus activity_log loescht. Vorher gab es
     * dieselbe Anweisung noch einmal in backend/activity_log.php — mit einer
     * anderen Rechtestufe davor. Aufrufer pruefen die Rechte selbst, der
     * Helfer setzt sie voraus.
     */
    function cleanupOldActivityLogs($days = 365) {
        global $db;

        $days = (int)$days;

        try {
            if ($days <= 0) {
                return $db->execute("DELETE FROM activity_log");
            }

            $cutoffDate = date('Y-m-d H:i:s', strtotime("-$days days"));

            return $db->execute(
                "DELETE FROM activity_log WHERE zeitstempel < ?",
                [$cutoffDate]
            );
        } catch (PDOException $e) {
            error_log("Activity Log Cleanup Error: " . $e->getMessage());
            return 0;
        }
    }
}

// ============================================================================
// SPALTEN-AUSWAHL FUNKTIONEN
// ============================================================================

/**
 * Gibt vordefinierte Spalten-Presets zurück
 */
if (!function_exists('getSpaltenPresets')) {
    function getSpaltenPresets() {
        return [
            'minimal' => [
                'name' => 'Minimal',
                'icon' => '📋',
                'spalten' => [
                    'bild' => true,
                    'kategorie' => false,
                    'ort' => false,
                    'preis' => true,
                    'kaufdatum' => false,
                    'ersteller' => false,
                    'dokumente' => false
                ]
            ],
            'standard' => [
                'name' => 'Standard',
                'icon' => '📊',
                'spalten' => [
                    'bild' => true,
                    'kategorie' => true,
                    'ort' => true,
                    'preis' => true,
                    'kaufdatum' => true,
                    'ersteller' => false,
                    'dokumente' => true
                ]
            ],
            'vollstaendig' => [
                'name' => function_exists('t') ? t('columns_preset_complete') : 'Vollständig',
                'icon' => '📈',
                'spalten' => [
                    'bild' => true,
                    'kategorie' => true,
                    'ort' => true,
                    'preis' => true,
                    'kaufdatum' => true,
                    'ersteller' => true,
                    'dokumente' => true
                ]
            ],
            'inventur' => [
                'name' => 'Inventur',
                'icon' => '🔢',
                'spalten' => [
                    'bild' => false,
                    'kategorie' => true,
                    'ort' => true,
                    'preis' => true,
                    'kaufdatum' => false,
                    'ersteller' => false,
                    'dokumente' => false
                ]
            ]
        ];
    }
}

/**
 * Lädt Spalten-Auswahl des aktuellen Benutzers
 * 
 * @return array Assoziatives Array mit Spalten und true/false
 */

/**
 * ============================================
 * SPALTEN-SYSTEM FUNKTIONEN (KORRIGIERT!)
 * ============================================
 */

/**
 * Lädt Spalten-Auswahl des aktuellen Users
 */
if (!function_exists('getUserSpaltenAuswahl')) {
    function getUserSpaltenAuswahl() {
        global $pdo;
    
        if (isset($_SESSION['spalten_auswahl']) && is_array($_SESSION['spalten_auswahl'])) {
            return $_SESSION['spalten_auswahl'];
        }
    
        try {
            $stmt = $pdo->prepare("SELECT spalten_auswahl FROM users WHERE username = ?");
            $stmt->execute([$_SESSION['username']]);
            $user = $stmt->fetch();
        
            if ($user && !empty($user['spalten_auswahl'])) {
                $data = json_decode($user['spalten_auswahl'], true);
            
                if (is_array($data)) {
                    // NEUE STRUKTUR (Phase 1): {"order": [...], "labels": {...}, "visibility": {...}}
                    if (isset($data['visibility'])) {
                        $_SESSION['spalten_auswahl'] = $data['visibility'];
                        $_SESSION['spalten_order'] = $data['order'] ?? [];
                        $_SESSION['spalten_labels'] = $data['labels'] ?? [];
                        return $data['visibility'];
                    }
                
                    // ALTE STRUKTUR (Abwärtskompatibel): {"bild": true, "kategorie": false, ...}
                    $_SESSION['spalten_auswahl'] = $data;
                    return $data;
                }
            }
        } catch (PDOException $e) {
            error_log("Fehler beim Laden der User-Spalten: " . $e->getMessage());
        }
    
        // Default
        return ['bild' => true, 'kategorie' => true, 'ort' => true, 'preis' => true];
    }
}

/**
 * Speichert Spalten-Auswahl in DB
 */
if (!function_exists('saveSpaltenAuswahl')) {
    function saveSpaltenAuswahl($spalten) {
        global $pdo;
    
        try {
            $spaltenJson = json_encode($spalten);
        
            $stmt = $pdo->prepare("UPDATE users SET spalten_auswahl = ? WHERE username = ?");
            $stmt->execute([$spaltenJson, $_SESSION['username']]);
        
            $_SESSION['spalten_auswahl'] = $spalten;
        
            return true;
        } catch (PDOException $e) {
            error_log("Fehler beim Speichern der Spalten: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Prüft ob Spalte angezeigt werden soll
 */
if (!function_exists('showSpalte')) {
    function showSpalte($spaltenName) {
        static $spalten = null;
    
        if ($spalten === null) {
            $spalten = getUserSpaltenAuswahl();
        }
    
        return isset($spalten[$spaltenName]) && $spalten[$spaltenName] === true;
    }
}

/**
 * Gibt das Label einer Spalte zurück (mit Custom-Namen falls vorhanden)
 */
if (!function_exists('getSpaltenLabel')) {
    function getSpaltenLabel($spaltenId) {
        static $labels = null;
        static $defaultLabels = null;
    
        if ($defaultLabels === null) {
            $defaultLabels = [
                'bild' => t('tab_image'),
                'kategorie' => t('tab_category'),
                'ort' => t('tab_location'),
                'preis' => t('tab_price'),
                'kaufdatum' => t('tab_purchase_date'),
                'ersteller' => t('tab_created_by'),
                'dokumente' => t('tab_documents'),
                'notizen' => t('tab_notes'),
                'aktueller_wert' => 'Akt. Wert',
                'wert_differenz' => 'Differenz',
                'wert_prozent' => '± %',
                'bewertungsdatum' => 'Bewertet am',
                'custom1' => 'Eigenes Feld 1',
                'custom2' => 'Eigenes Feld 2'
            ];
        }
    
        if ($labels === null) {
            $labels = $_SESSION['spalten_labels'] ?? [];
        }
    
        // Custom-Label verwenden oder Default
        return $labels[$spaltenId] ?? $defaultLabels[$spaltenId] ?? $spaltenId;
    }
}

/**
 * Gibt die Spalten-Reihenfolge zurück
 */
if (!function_exists('getSpaltenOrder')) {
    function getSpaltenOrder() {
        static $order = null;
    
        if ($order === null) {
            // Aus Session holen oder Default
            $order = $_SESSION['spalten_order'] ?? ['bild', 'kategorie', 'ort', 'preis', 'kaufdatum', 'ersteller', 'dokumente'];
            
            // Neue Spalten die noch nicht in der gespeicherten Reihenfolge sind, anhängen
            $allSpalten = array_keys(getAvailableSpalten());
            foreach ($allSpalten as $spalteId) {
                if (!in_array($spalteId, $order)) {
                    $order[] = $spalteId;
                }
            }
        }
    
        return $order;
    }
}

/**
 * Rendert eine Tabellenzelle für eine Spalte
 */
/**
 * Macht URLs in Text klickbar (öffnen in neuem Tab/Fenster)
 */
if (!function_exists('makeLinksClickable')) {
    function makeLinksClickable($text) {
        if (empty($text)) return '';
    
        // URL Pattern (http, https, www)
        $pattern = '/(https?:\/\/[^\s<>"]+|www\.[^\s<>"]+)/i';
    
        return preg_replace_callback($pattern, function($matches) {
            $url = $matches[0];
        
            // Wenn URL nicht mit http beginnt, http:// hinzufügen
            $href = (strpos($url, 'http') === 0) ? $url : 'http://' . $url;
        
            // Kürze die Anzeige-URL falls zu lang
            $display = (strlen($url) > 40) ? substr($url, 0, 37) . '...' : $url;
        
            // Link mit target="_blank" für neues Fenster/Tab
            return '<a href="' . htmlspecialchars($href) . '" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       style="color: #3498db; text-decoration: underline;"
                       onclick="event.stopPropagation();"
                       title="' . htmlspecialchars($url) . '">
                       🔗 ' . htmlspecialchars($display) . '
                    </a>';
        }, htmlspecialchars($text));
    }
}

if (!function_exists('renderTableCell')) {
    function renderTableCell($spaltenId, $item, $dokumente_aktiv = false, $editReturnParam = '') {
        switch ($spaltenId) {
            case 'bild':
                return '<td class="td-image">' . renderThumbnail($item['bild'], htmlspecialchars($item['name'] ?? ''), 'thumbnail', $item['kategorie_name'] ?? null) . '</td>';
            
            case 'kategorie':
                return '<td>' . esc($item['kategorie_name'], '-') . '</td>';
            
            case 'ort':
                return '<td>' . esc($item['raum_name'], '-') . '</td>';
            
            case 'preis':
                return '<td>' . formatPriceLocalized($item['preis']) . '</td>';
            
            case 'kaufdatum':
                return '<td>' . formatDateLocalized($item['kaufdatum']) . '</td>';
            
            case 'ersteller':
                return '<td>' . htmlspecialchars($item['erstellt_von'] ?? '-') . '</td>';
            
            case 'dokumente':
                if (!$dokumente_aktiv) return '';
            
                $html = '<td class="text-center" style="line-height: 1.8;">';
            
                // Dokumente Icon
                if (isset($item['dokumente_anzahl']) && $item['dokumente_anzahl'] > 0) {
                    $html .= '<a href="manage_documents.php?id=' . $item['id'] . '" 
                                 title="' . $item['dokumente_anzahl'] . ' ' . t('tab_documents') . '"
                                 style="text-decoration: none; font-weight: bold; display: block;">
                                📄 ' . $item['dokumente_anzahl'] . '
                              </a>';
                } else {
                    $html .= '<a href="manage_documents.php?id=' . $item['id'] . '" 
                                 title="' . t('add_documents') . '"
                                 style="text-decoration: none; opacity: 0.3; display: block;">
                                📄
                              </a>';
                }
            
                // Bilder Icon (nur wenn > 1, da 1 Bild der Normalfall ist)
                $bilderAnzahl = $item['bilder_anzahl'] ?? 0;
                if ($bilderAnzahl > 1) {
                    $html .= '<a href="edit.php?id=' . $item['id'] . $editReturnParam . '" 
                                 title="' . $bilderAnzahl . ' Bilder"
                                 style="text-decoration: none; font-weight: bold; display: block; color: #667eea;">
                                🖼️ ' . $bilderAnzahl . '
                              </a>';
                }
            
                $html .= '</td>';
                return $html;
            
            case 'notizen':
                $notizen = $item['notizen'] ?? '';
                // Kürze Text auf 100 Zeichen (mehr Platz für Links)
                $shortText = mb_substr($notizen, 0, 100);
                if (mb_strlen($notizen) > 100) {
                    $shortText .= '...';
                }
                // Mache URLs klickbar
                $withLinks = makeLinksClickable($shortText);
                return '<td style="max-width: 300px; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word;">' . $withLinks . '</td>';
            
            case 'aktueller_wert':
                $val = $item['aktueller_wert'] ?? null;
                return '<td>' . ($val !== null ? formatPriceLocalized($val) : '<span style="color:#ccc">–</span>') . '</td>';
            
            case 'wert_differenz':
                $kauf = floatval($item['preis'] ?? 0);
                $akt  = isset($item['aktueller_wert']) && $item['aktueller_wert'] !== null ? floatval($item['aktueller_wert']) : null;
                if ($akt === null || $kauf == 0) return '<td><span style="color:#ccc">–</span></td>';
                $diff = $akt - $kauf;
                $color = $diff > 0 ? '#27ae60' : ($diff < 0 ? '#e74c3c' : '#666');
                $sign  = $diff > 0 ? '+' : '';
                return '<td style="color:' . $color . '; font-weight:600;">' . $sign . formatPriceLocalized($diff) . '</td>';
            
            case 'wert_prozent':
                $kauf = floatval($item['preis'] ?? 0);
                $akt  = isset($item['aktueller_wert']) && $item['aktueller_wert'] !== null ? floatval($item['aktueller_wert']) : null;
                if ($akt === null || $kauf == 0) return '<td><span style="color:#ccc">–</span></td>';
                $pct = (($akt - $kauf) / $kauf) * 100;
                $color = $pct > 0 ? '#27ae60' : ($pct < 0 ? '#e74c3c' : '#666');
                $sign  = $pct > 0 ? '+' : '';
                return '<td style="color:' . $color . '; font-weight:600;">' . $sign . number_format($pct, 1, ',', '.') . ' %</td>';
            
            case 'bewertungsdatum':
                return '<td>' . formatDateLocalized($item['aktueller_wert_datum'] ?? null) . '</td>';
            
            case 'custom1':
                $val = $item['custom1_wert'] ?? null;
                if ($val === null || $val === '') return '<td><span style="color:#ccc">–</span></td>';
                if (($item['custom1_typ'] ?? 'text') === 'zahl') {
                    return '<td>' . formatPriceLocalized($val) . '</td>';
                }
                return '<td>' . htmlspecialchars($val) . '</td>';
            
            case 'custom2':
                $val = $item['custom2_wert'] ?? null;
                if ($val === null || $val === '') return '<td><span style="color:#ccc">–</span></td>';
                if (($item['custom2_typ'] ?? 'text') === 'zahl') {
                    return '<td>' . formatPriceLocalized($val) . '</td>';
                }
                return '<td>' . htmlspecialchars($val) . '</td>';
            
            default:
                return '<td>-</td>';
        }
    }
}

/**
 * Gibt alle verfügbaren Spalten mit Labels zurück
 */
if (!function_exists('getAvailableSpalten')) {
    function getAvailableSpalten() {
        return [
            'bild' => [
                'label' => 'Bild',
                'icon' => '🖼️',
                'required' => false
            ],
            'kategorie' => [
                'label' => 'Kategorie',
                'icon' => '🏷️',
                'required' => false
            ],
            'ort' => [
                'label' => 'Ort',
                'icon' => '📍',
                'required' => false
            ],
            'preis' => [
                'label' => 'Preis',
                'icon' => '💶',
                'required' => false
            ],
            'kaufdatum' => [
                'label' => 'Kaufdatum',
                'icon' => '📅',
                'required' => false
            ],
            'ersteller' => [
                'label' => 'Ersteller',
                'icon' => '👤',
                'required' => false
            ],
            'dokumente' => [
                'label' => 'Dokumente',
                'icon' => '📄',
                'required' => false
            ],
            'notizen' => [
                'label' => 'Notizen',
                'icon' => '📝',
                'required' => false
            ],
            'aktueller_wert' => [
                'label' => 'Akt. Wert',
                'icon' => '📈',
                'required' => false
            ],
            'wert_differenz' => [
                'label' => 'Differenz',
                'icon' => '↕️',
                'required' => false
            ],
            'wert_prozent' => [
                'label' => '± %',
                'icon' => '📊',
                'required' => false
            ],
            'bewertungsdatum' => [
                'label' => 'Bewertet am',
                'icon' => '🗓️',
                'required' => false
            ],
            'custom1' => [
                'label' => 'Eigenes Feld 1',
                'icon' => '⭐',
                'required' => false
            ],
            'custom2' => [
                'label' => 'Eigenes Feld 2',
                'icon' => '⭐',
                'required' => false
            ]
        ];
    }
}

/**
 * Lädt Spalten-Auswahl aus DB (Alias)
 */
if (!function_exists('getSpaltenAuswahl')) {
    function getSpaltenAuswahl() {
        return getUserSpaltenAuswahl();
    }
}

/**
 * Setzt Spalten auf Standard zurück
 */
if (!function_exists('resetSpaltenAuswahl')) {
    function resetSpaltenAuswahl() {
        global $pdo;
    
        try {
            $defaultSpalten = ['bild' => true, 'kategorie' => true, 'ort' => true, 'preis' => true];
            $spaltenJson = json_encode($defaultSpalten);
        
            $stmt = $pdo->prepare("UPDATE users SET spalten_auswahl = ? WHERE username = ?");
            $stmt->execute([$spaltenJson, $_SESSION['username']]);
        
            $_SESSION['spalten_auswahl'] = $defaultSpalten;
        
            return true;
        } catch (PDOException $e) {
            error_log("Fehler beim Zurücksetzen der Spalten: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Formatiert Preis lokalisiert
 */
if (!function_exists('formatPriceLocalized')) {
    function formatPriceLocalized($price) {
        if ($price === null || $price === '') {
            return '-';
        }
        return number_format((float)$price, 2, ',', '.') . ' €';
    }
}

/**
 * Formatiert Datum lokalisiert
 */
if (!function_exists('formatDateLocalized')) {
    function formatDateLocalized($date) {
        if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return '-';
        }
    
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return '-';
        }
    
        return date('d.m.Y', $timestamp);
    }
}

/**
 * Formatiert Datum+Zeit lokalisiert (dd.mm.yyyy HH:MM)
 */
if (!function_exists('formatDateTimeLocalized')) {
    function formatDateTimeLocalized($datetime) {
        if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
            return '-';
        }
    
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return '-';
        }
    
        return date('d.m.Y H:i', $timestamp);
    }
}

/**
 * True, wenn der aktuelle Benutzer laut Einstellung "only_own_items" nur
 * eigene Eintraege sehen darf. Admins und Benutzer mit sieht_alle = 1 sind
 * ausgenommen. Gleiche Regel wie in index.php und helpers_search.php,
 * hier einmal zentral - Aufrufer sind Zugriffspruefungen.
 *
 * Faellt bewusst RESTRIKTIV aus (true), wenn die Abfrage scheitert: ein
 * DB-Fehler darf eine Zugriffspruefung nicht stillschweigend aufheben.
 */
if (!function_exists('userSeesOnlyOwnItems')) {
    function userSeesOnlyOwnItems(): bool {
        global $db;
        try {
            $setting = $db->selectOne(
                "SELECT setting_value FROM app_settings WHERE setting_key = 'only_own_items'"
            );
            if (($setting['setting_value'] ?? '0') !== '1') {
                return false;
            }
            $user = $db->selectOne(
                "SELECT role, sieht_alle FROM users WHERE username = ?",
                [$_SESSION['username'] ?? '']
            );
            if (!$user) {
                return true;
            }
            return $user['role'] !== 'admin' && !$user['sieht_alle'];
        } catch (Exception $e) {
            error_log('userSeesOnlyOwnItems failed: ' . $e->getMessage());
            return true;
        }
    }
}

/**
 * Zeigt "Zugriff verweigert" Seite und beendet Script
 */
if (!function_exists('showPermissionDenied')) {
    function showPermissionDenied($message = 'Sie haben keine Berechtigung für diese Aktion.') {
        header('HTTP/1.1 403 Forbidden');
        $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Zugriff verweigert</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
            .error-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center; max-width: 500px; }
            .error-icon { font-size: 64px; margin-bottom: 20px; }
            h1 { color: #d32f2f; margin: 0 0 10px 0; }
            p { color: #666; margin: 0 0 30px 0; }
            a { background: #667eea; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; display: inline-block; }
            a:hover { background: #5568d3; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <div class="error-icon">🔒</div>
            <h1>Zugriff verweigert</h1>
            <p>' . htmlspecialchars($message) . '</p>
            <a href="index.php">← Zurück zur Startseite</a>
        </div>
    </body>
    </html>';
        echo $html;
        exit;
    }
}

/**
 * Konvertiert HEIC/HEIF zu JPEG mit ImageMagick
 * 
 * @param string $sourcePath Pfad zur HEIC-Datei
 * @param string $destPath Pfad zur JPEG-Ziel-Datei
 * @return bool Erfolg
 */
if (!function_exists('convertHeicToJpeg')) {
    function convertHeicToJpeg($sourcePath, $destPath) {
        try {
            // Imagick verwenden (beste Methode)
            if (extension_loaded('imagick')) {
                $imagick = new Imagick($sourcePath);
            
                // EXIF-Orientierung korrigieren
                $orientation = $imagick->getImageOrientation();
            
                switch ($orientation) {
                    case Imagick::ORIENTATION_BOTTOMRIGHT:
                        $imagick->rotateImage(new ImagickPixel('none'), 180);
                        break;
                    case Imagick::ORIENTATION_RIGHTTOP:
                        $imagick->rotateImage(new ImagickPixel('none'), 90);
                        break;
                    case Imagick::ORIENTATION_LEFTBOTTOM:
                        $imagick->rotateImage(new ImagickPixel('none'), -90);
                        break;
                }
            
                $imagick->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
            
                // Zu JPEG konvertieren
                $imagick->setImageFormat('jpeg');
                $imagick->setImageCompressionQuality(85);
            
                // Max-Breite wie bei anderen Bildern
                $width = $imagick->getImageWidth();
                $height = $imagick->getImageHeight();
            
                if ($width > 1920 || $height > 1920) {
                    $imagick->scaleImage(1920, 1920, true);
                }
            
                // Speichern
                $imagick->writeImage($destPath);
                $imagick->clear();
                $imagick->destroy();
            
                return true;
            }
        
            // Fallback: ImageMagick convert command
            $command = sprintf(
                'convert %s -auto-orient -quality 85 -resize "1920x1920>" %s 2>&1',
                escapeshellarg($sourcePath),
                escapeshellarg($destPath)
            );
        
            exec($command, $output, $returnCode);
        
            if ($returnCode === 0 && file_exists($destPath)) {
                return true;
            }
        
            error_log("HEIC conversion failed: " . implode("\n", $output));
            return false;
        
        } catch (Exception $e) {
            error_log("HEIC conversion error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('fehlerprotokollStatus')) {
    /**
     * Wo landet eine Fehlermeldung dieser Instanz?
     *
     * Entstanden am 23.09.2026. Am 18./19.09. gingen zwei Tage dafuer drauf,
     * einen HTTP 500 auf dem NAS zu SEHEN — behoben war er in zehn Minuten.
     * Die Abnahmeliste fragte an keiner Stelle, wo die Fehlermeldung stuende,
     * wenn etwas nicht funktioniert. Diese Funktion beantwortet genau das,
     * auf jeder Instanz einzeln, denn die Antwort ist auf jeder anders.
     *
     * Der Reihe nach:
     *  - PHP_SAPI, weil eine .user.ini NUR bei CGI/FastCGI/FPM gelesen wird.
     *    Laeuft PHP als Apache-Modul, ist die Datei wirkungslos, und die
     *    Einstellungen muessten in die .htaccess. Eine Datei, die da liegt
     *    und nichts tut, ist schlimmer als keine.
     *  - ob eine .user.ini im Anwendungsverzeichnis liegt (sie wird nicht
     *    synchronisiert: 'ini' steht in keiner der Erweiterungslisten)
     *  - die WIRKSAMEN Werte, nicht die erhofften
     *  - das Ziel: Pfad, beschreibbar, vorhanden, Groesse, letzte Aenderung.
     *    Ein Protokoll, in das nicht geschrieben werden kann, sieht in jeder
     *    Einstellungsanzeige richtig aus und ist trotzdem leer.
     *
     * @param string $appDir Verzeichnis der Anwendung (dort liegt die .user.ini)
     * @return array
     */
    function fehlerprotokollStatus(string $appDir): array
    {
        $anAus = function ($wert): bool {
            $w = strtolower(trim((string)$wert));
            return $w === '1' || $w === 'on' || $w === 'true' || $w === 'yes';
        };

        $sapi = PHP_SAPI;
        $iniPfad = rtrim($appDir, '/') . '/.user.ini';

        $stufe = (int)ini_get('error_reporting');
        $ziel  = trim((string)ini_get('error_log'));

        $status = [
            'sapi'            => $sapi,
            'userini_wirkt'   => !in_array($sapi, ['apache2handler', 'apache', 'cli'], true),
            'userini_pfad'    => $iniPfad,
            'userini_da'      => is_file($iniPfad),
            'anzeigen'        => $anAus(ini_get('display_errors')),
            'protokollieren'  => $anAus(ini_get('log_errors')),
            'stufe'           => $stufe,
            'stufe_alle'      => ($stufe & E_ALL) === E_ALL,
            'ziel'            => $ziel,
            'ziel_art'        => 'server',
            'ziel_da'         => false,
            'ziel_groesse'    => null,
            'ziel_datum'      => null,
            'ziel_schreibbar' => false,
        ];

        if ($ziel === '') {
            // Kein eigener Pfad: PHP schreibt ins Protokoll des Webservers.
            // Das ist nicht schlimm, aber man muss wissen, wo man nachsieht —
            // und bei geteilten Webspaces kommt man da nicht immer hin.
            return $status;
        }
        if (strtolower($ziel) === 'syslog') {
            $status['ziel_art'] = 'syslog';
            return $status;
        }

        $status['ziel_art'] = 'datei';
        if (is_file($ziel)) {
            $status['ziel_da']         = true;
            $status['ziel_groesse']    = (int)filesize($ziel);
            $status['ziel_datum']      = (int)filemtime($ziel);
            $status['ziel_schreibbar'] = is_writable($ziel);
        } else {
            // Noch keine Datei: dann entscheidet das Verzeichnis darueber, ob
            // je eine entstehen kann.
            $verz = dirname($ziel);
            $status['ziel_schreibbar'] = is_dir($verz) && is_writable($verz);
        }
        return $status;
    }
}

/**
 * Standort und Position aus dem Formular von add.php / edit.php lesen.
 *
 * Bis 26.09.2026 war das Auswahlfeld "Standort" nur ein Filter fuer die
 * Positionen: gespeichert wurde allein position_id, der Standort beim
 * naechsten Aufruf aus der Position zurueckgerechnet. Wer einen Standort
 * ohne Position waehlte, verlor ihn beim Speichern ohne jede Meldung.
 * Jetzt landet er in wertsachen.standort_id.
 *
 * Ist eine Position gewaehlt, gilt IHR Standort (positionen.raum_id - die
 * Spalte heisst raum_id, zeigt aber auf standorte). So koennen Standort und
 * Position nicht auseinanderlaufen, auch wenn jemand das Formular von Hand
 * abschickt.
 *
 * @return array{0: ?int, 1: ?int}  [standort_id, position_id]
 */
if (!function_exists('standortAusFormular')) {
    function standortAusFormular($db, array $post) {
        $standort_id = (int)($post['standort_raum'] ?? 0) ?: null;
        $position_id = (int)($post['position_id'] ?? 0) ?: null;

        if ($position_id !== null) {
            $pos = $db->selectOne("SELECT raum_id FROM positionen WHERE id = ?", [$position_id]);
            if ($pos) {
                $standort_id = (int)$pos['raum_id'] ?: null;
            } else {
                $position_id = null;
            }
        }
        if ($standort_id !== null && !$db->selectOne("SELECT id FROM standorte WHERE id = ?", [$standort_id])) {
            $standort_id = null;
        }
        return [$standort_id, $position_id];
    }
}

?>
