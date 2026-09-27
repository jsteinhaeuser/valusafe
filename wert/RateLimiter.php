<?php
/**
 * RateLimiter Class
 * Verhindert Brute-Force Attacks durch Limitierung von Login-Versuchen
 */

class RateLimiter {
    private $db;
    private $maxAttempts = 5;        // Max Versuche
    private $lockoutTime = 900;      // Sperre-Zeit in Sekunden (15 Minuten)
    private $attemptWindow = 300;    // Zeitfenster für Versuche (5 Minuten)
    
    public function __construct($database) {
        $this->db = $database;
        $this->createTableIfNotExists();
    }
    
    /**
     * Erstellt rate_limit Tabelle falls nicht vorhanden
     */
    private function createTableIfNotExists() {
        try {
            $this->db->execute("
                CREATE TABLE IF NOT EXISTS rate_limit (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    attempts INT DEFAULT 1,
                    first_attempt DATETIME NOT NULL,
                    last_attempt DATETIME NOT NULL,
                    locked_until DATETIME NULL,
                    INDEX idx_ip_action (ip_address, action),
                    INDEX idx_locked (locked_until)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (PDOException $e) {
            error_log("RateLimiter: Konnte Tabelle nicht erstellen - " . $e->getMessage());
        }
    }
    
    /**
     * Prüft ob IP für Action gesperrt ist
     */
    public function isLocked($ipAddress, $action = 'login') {
        try {
            $record = $this->db->selectOne(
                "SELECT locked_until FROM rate_limit 
                 WHERE ip_address = ? AND action = ? AND locked_until > NOW()",
                [$ipAddress, $action]
            );
            
            return !empty($record);
        } catch (PDOException $e) {
            error_log("RateLimiter: Fehler bei isLocked - " . $e->getMessage());
            return false; // Im Fehlerfall nicht blockieren
        }
    }
    
    /**
     * Gibt verbleibende Sperrzeit in Sekunden zurück
     */
    public function getRemainingLockTime($ipAddress, $action = 'login') {
        try {
            $record = $this->db->selectOne(
                "SELECT TIMESTAMPDIFF(SECOND, NOW(), locked_until) as remaining 
                 FROM rate_limit 
                 WHERE ip_address = ? AND action = ? AND locked_until > NOW()",
                [$ipAddress, $action]
            );
            
            return $record ? max(0, intval($record['remaining'])) : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Registriert einen fehlgeschlagenen Versuch
     */
    public function recordAttempt($ipAddress, $action = 'login') {
        try {
            // Hole aktuellen Record
            $record = $this->db->selectOne(
                "SELECT * FROM rate_limit 
                 WHERE ip_address = ? AND action = ?",
                [$ipAddress, $action]
            );
            
            if (!$record) {
                // Erster Versuch
                $this->db->execute(
                    "INSERT INTO rate_limit (ip_address, action, attempts, first_attempt, last_attempt) 
                     VALUES (?, ?, 1, NOW(), NOW())",
                    [$ipAddress, $action]
                );
            } else {
                // Prüfe ob Zeitfenster abgelaufen ist
                $firstAttempt = strtotime($record['first_attempt']);
                $timePassed = time() - $firstAttempt;
                
                if ($timePassed > $this->attemptWindow) {
                    // Zeitfenster abgelaufen - Reset
                    $this->db->execute(
                        "UPDATE rate_limit 
                         SET attempts = 1, first_attempt = NOW(), last_attempt = NOW(), locked_until = NULL 
                         WHERE ip_address = ? AND action = ?",
                        [$ipAddress, $action]
                    );
                } else {
                    // Erhöhe Versuche
                    $newAttempts = $record['attempts'] + 1;
                    
                    if ($newAttempts >= $this->maxAttempts) {
                        // Sperre aktivieren
                        $this->db->execute(
                            "UPDATE rate_limit 
                             SET attempts = ?, last_attempt = NOW(), locked_until = DATE_ADD(NOW(), INTERVAL ? SECOND) 
                             WHERE ip_address = ? AND action = ?",
                            [$newAttempts, $this->lockoutTime, $ipAddress, $action]
                        );
                        
                        // Security Event loggen
                        if (class_exists('Security')) {
                            Security::logSecurityEvent('rate_limit_triggered', [
                                'ip' => $ipAddress,
                                'action' => $action,
                                'attempts' => $newAttempts
                            ]);
                        }
                    } else {
                        // Einfach erhöhen
                        $this->db->execute(
                            "UPDATE rate_limit 
                             SET attempts = ?, last_attempt = NOW() 
                             WHERE ip_address = ? AND action = ?",
                            [$newAttempts, $ipAddress, $action]
                        );
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("RateLimiter: Fehler bei recordAttempt - " . $e->getMessage());
        }
    }
    
    /**
     * Setzt Rate Limit für IP zurück (nach erfolgreichem Login)
     */
    public function reset($ipAddress, $action = 'login') {
        try {
            $this->db->execute(
                "DELETE FROM rate_limit WHERE ip_address = ? AND action = ?",
                [$ipAddress, $action]
            );
        } catch (PDOException $e) {
            error_log("RateLimiter: Fehler bei reset - " . $e->getMessage());
        }
    }
    
    /**
     * Cleanup: Alte Einträge löschen (älter als 24 Stunden)
     */
    public function cleanup() {
        try {
            $this->db->execute(
                "DELETE FROM rate_limit 
                 WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
        } catch (PDOException $e) {
            error_log("RateLimiter: Fehler bei cleanup - " . $e->getMessage());
        }
    }
    
    /**
     * Gibt verbleibende Versuche zurück
     */
    public function getRemainingAttempts($ipAddress, $action = 'login') {
        try {
            $record = $this->db->selectOne(
                "SELECT attempts, first_attempt FROM rate_limit 
                 WHERE ip_address = ? AND action = ?",
                [$ipAddress, $action]
            );
            
            if (!$record) {
                return $this->maxAttempts;
            }
            
            // Prüfe ob Zeitfenster abgelaufen
            $timePassed = time() - strtotime($record['first_attempt']);
            if ($timePassed > $this->attemptWindow) {
                return $this->maxAttempts; // Reset
            }
            
            return max(0, $this->maxAttempts - $record['attempts']);
        } catch (PDOException $e) {
            return $this->maxAttempts; // Im Fehlerfall großzügig sein
        }
    }
}
