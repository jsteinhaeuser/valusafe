<?php
// security.php

class Security {

    /** Groesse, ab der logs/security.log rotiert wird (siehe logSecurityEvent). */
    const SECURITY_LOG_MAX_BYTES = 5242880;

    /**
     * Generiert ein CSRF-Token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Validiert CSRF-Token
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Gibt CSRF-Token HTML-Input zurück
     */
    public static function getCSRFInput() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Validiert Datei-Upload
     */
    public static function validateFileUpload($file) {
        $errors = [];
        
        // Prüfe ob Datei hochgeladen wurde
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Fehler beim Hochladen der Datei';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Prüfe Dateigröße
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = 'Datei ist zu groß. Maximum: ' . (MAX_FILE_SIZE / 1024 / 1024) . ' MB';
        }
        
        // Prüfe MIME-Type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
            $errors[] = 'Ungültiger Dateityp. Nur Bilder erlaubt.';
        }
        
        // Prüfe Dateiendung
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            $errors[] = 'Ungültige Dateiendung.';
        }
        
        // Prüfe ob es wirklich ein Bild ist
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $errors[] = 'Datei ist kein gültiges Bild.';
        }
        
        if (!empty($errors)) {
            return ['valid' => false, 'errors' => $errors];
        }
        
        return ['valid' => true, 'mime' => $mimeType, 'extension' => $extension];
    }
    
    /**
     * Hasht Passwort sicher
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Prüft Session-Timeout
     */
    public static function checkSessionTimeout() {
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
                session_unset();
                session_destroy();
                return false;
            }
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    /**
     * Kuerzt eine IP auf ihr Netz: IPv4 auf /24, IPv6 auf /64.
     * Toleriert damit Adresswechsel innerhalb desselben Providernetzes
     * (DSL-Zwangstrennung, Mobilfunk, rotierende IPv6-Praefixe), bleibt
     * aber wirksam gegen Session-Diebstahl aus einem fremden Netz.
     */
    private static function networkPrefix($ip) {
        $ip = (string)$ip;
        if ($ip === '') {
            return 'unknown';
        }
        if (strpos($ip, ':') !== false) {
            return implode(':', array_slice(explode(':', $ip), 0, 4));
        }
        $pos = strrpos($ip, '.');
        return $pos !== false ? substr($ip, 0, $pos) : $ip;
    }

    /**
     * Erstellt Session-Fingerprint nach erfolgreichem Login.
     * Aufruf: direkt nach session_regenerate_id(true) in login.php
     */
    public static function createSessionFingerprint() {
        // IP wird auf /24-Subnetz gekürzt — tolerant gegenüber mobilem IP-Wechsel,
        // aber wirksam gegen Session-Diebstahl aus anderem Netzwerk
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
        $ipPrefix = self::networkPrefix($ip);
        $_SESSION['_fp'] = hash('sha256',
            ($ipPrefix ?: 'unknown') .
            ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')
        );
    }

    /**
     * Validiert Session-Fingerprint bei jedem Request.
     * Gibt false zurück wenn Fingerprint fehlt oder nicht übereinstimmt.
     */
    public static function validateSessionFingerprint() {
        if (!isset($_SESSION['_fp'])) {
            // Kein Fingerprint gesetzt = noch nicht eingeloggt (login.php Aufruf)
            // Nur ablehnen wenn user_id vorhanden aber kein Fingerprint
            if (isset($_SESSION['user_id'])) {
                return false; // Eingeloggte Session ohne Fingerprint = verdächtig
            }
            return true; // Noch nicht eingeloggt = OK
        }
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
        $ipPrefix = self::networkPrefix($ip);
        $current = hash('sha256',
            ($ipPrefix ?: 'unknown') .
            ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')
        );
        return hash_equals($_SESSION['_fp'], $current);
    }
    
    /**
     * Loggt Sicherheitsrelevante Events
     */
    public static function logSecurityEvent($event, $details = []) {
        $logFile = __DIR__ . '/logs/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Rotation: Bis 4.3.29 wuchs die Datei unbegrenzt - loeschen liess sie
        // sich nirgends (backend/activity_log.php leert nur die Tabelle
        // activity_log). Ab 5 MB (grob 15.000 Eintraege) wird sie zu
        // security.1.log, eine vorhandene aeltere faellt weg. Die Endung .log
        // bleibt, damit die FilesMatch-Sperre der .htaccess auch sie trifft.
        if (@filesize($logFile) > self::SECURITY_LOG_MAX_BYTES) {
            @rename($logFile, $logDir . '/security.1.log');
        }

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'user' => $_SESSION['username'] ?? 'anonymous',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        file_put_contents(
            $logFile, 
            json_encode($logEntry) . PHP_EOL, 
            FILE_APPEND | LOCK_EX
        );
    }
}

/**
 * Hilfsfunktion für Sicherheitslogging mit PDO-Connection
 */
function logSecurityEvent($conn, $event_type, $user_id, $description) {
    try {
        $stmt = $conn->prepare(
            "INSERT INTO security_log (event_type, user_id, description, ip_address) 
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $event_type,
            $user_id,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        securityLogKuerzen($conn);
    } catch (PDOException $e) {
        // Fallback zu Datei-Logging wenn DB-Logging fehlschlägt
        Security::logSecurityEvent($event_type, [
            'user_id' => $user_id,
            'description' => $description
        ]);
    }
}

/**
 * Loescht Eintraege aus security_log, die aelter als $tage sind.
 *
 * Bis 4.3.30 wuchs die Tabelle unbegrenzt: Der 90-Tage-Befehl in
 * security_actions.php laeuft nur auf Knopfdruck, und keine Seite verlinkt
 * ihn. Jetzt raeumt jeder Schreibvorgang mit auf. Geschrieben wird nur bei
 * An- und Abmeldungen, und created_at hat einen Index - das kostet nichts.
 * Die Grenze wird in PHP berechnet statt mit DATE_SUB, damit der Test sie in
 * SQLite pruefen kann.
 *
 * Fehler werden nur protokolliert: Das Aufraeumen darf eine Anmeldung nie
 * scheitern lassen.
 */
function securityLogKuerzen($conn, int $tage = 90): int {
    try {
        $grenze = date('Y-m-d H:i:s', time() - $tage * 86400);
        $stmt = $conn->prepare("DELETE FROM security_log WHERE created_at < ?");
        $stmt->execute([$grenze]);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log('security_log kuerzen: ' . $e->getMessage());
        return 0;
    }
}
