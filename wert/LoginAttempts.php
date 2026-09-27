<?php
/**
 * Login Attempts Tracking
 * Verhindert Brute-Force-Angriffe
 */

class LoginAttempts {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Registriert fehlgeschlagenen Login-Versuch
     */
    public function recordAttempt($username, $ip) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO login_attempts (username, ip_address, attempt_time)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$username, $ip]);
        } catch (PDOException $e) {
            error_log("LoginAttempts recordAttempt error: " . $e->getMessage());
        }
    }
    
    /**
     * Prüft ob Login erlaubt ist
     */
    public function isAllowed($username, $ip) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM login_attempts
                WHERE (username = ? OR ip_address = ?)
                AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            $stmt->execute([$username, $ip]);
            $result = $stmt->fetch();
            
            return $result['count'] < 5;
        } catch (PDOException $e) {
            error_log("LoginAttempts isAllowed error: " . $e->getMessage());
            return true;
        }
    }
    
    /**
     * Löscht alte Versuche (Cleanup)
     */
    public function cleanup() {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM login_attempts
                WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("LoginAttempts cleanup error: " . $e->getMessage());
        }
    }
    
    /**
     * Löscht Versuche nach erfolgreichem Login
     */
    public function clearAttempts($username, $ip) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM login_attempts
                WHERE username = ? OR ip_address = ?
            ");
            $stmt->execute([$username, $ip]);
        } catch (PDOException $e) {
            error_log("LoginAttempts clearAttempts error: " . $e->getMessage());
        }
    }
    
    /**
     * Gibt verbleibende Zeit bis nächster Versuch zurück
     */
    public function getWaitTime($username, $ip) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT MAX(attempt_time) as last_attempt
                FROM login_attempts
                WHERE (username = ? OR ip_address = ?)
            ");
            $stmt->execute([$username, $ip]);
            $result = $stmt->fetch();
            
            if ($result && $result['last_attempt']) {
                $last = strtotime($result['last_attempt']);
                $now = time();
                $wait = 900 - ($now - $last);
                return max(0, $wait);
            }
            
            return 0;
        } catch (PDOException $e) {
            error_log("LoginAttempts getWaitTime error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Gibt Statistiken zurück
     */
    public function getStats() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_attempts,
                    COUNT(DISTINCT username) as unique_users,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    MIN(attempt_time) as first_attempt,
                    MAX(attempt_time) as last_attempt
                FROM login_attempts
                WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("LoginAttempts getStats error: " . $e->getMessage());
            return [
                'total_attempts' => 0,
                'unique_users' => 0,
                'unique_ips' => 0,
                'first_attempt' => null,
                'last_attempt' => null
            ];
        }
    }
    
    /**
     * Gibt letzte Versuche zurück
     */
    public function getRecentAttempts($limit = 50) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT username, ip_address, attempt_time
                FROM login_attempts
                ORDER BY attempt_time DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("LoginAttempts getRecentAttempts error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Gibt gesperrte Accounts zurück (5+ Versuche in 15 Min)
     */
    public function getLockedAccounts() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    username,
                    ip_address,
                    COUNT(*) as attempt_count,
                    MAX(attempt_time) as last_attempt
                FROM login_attempts
                WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                GROUP BY username, ip_address
                HAVING COUNT(*) >= 5
                ORDER BY last_attempt DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("LoginAttempts getLockedAccounts error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Gibt vollständige Security-Statistiken zurück
     */
    public function getSecurityStats() {
        try {
            // Gesperrte Accounts
            $locked = $this->getLockedAccounts();
            
            // Fehlversuche 24h
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM login_attempts
                WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            $failed = $stmt->fetch();
            
            // Erfolgreiche Logins 24h (aus activity_log)
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM activity_log
                WHERE action = 'login'
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            $success = $stmt->fetch();
            
            // Top IPs mit Fehlversuchen
            $stmt = $this->pdo->prepare("
                SELECT 
                    ip_address,
                    COUNT(*) as attempt_count,
                    MAX(attempt_time) as last_attempt
                FROM login_attempts
                WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY ip_address
                ORDER BY attempt_count DESC
                LIMIT 10
            ");
            $stmt->execute();
            $topIps = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Top Usernames mit Fehlversuchen
            $stmt = $this->pdo->prepare("
                SELECT 
                    username,
                    COUNT(*) as attempt_count,
                    MAX(attempt_time) as last_attempt
                FROM login_attempts
                WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY username
                ORDER BY attempt_count DESC
                LIMIT 10
            ");
            $stmt->execute();
            $topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'locked_accounts' => count($locked),
                'locked_list' => $locked,
                'failed_24h' => $failed['count'] ?? 0,
                'success_24h' => $success['count'] ?? 0,
                'top_ips' => $topIps,
                'top_users' => $topUsers,
                'total_attempts' => $failed['count'] ?? 0
            ];
        } catch (PDOException $e) {
            error_log("LoginAttempts getSecurityStats error: " . $e->getMessage());
            return [
                'locked_accounts' => 0,
                'locked_list' => [],
                'failed_24h' => 0,
                'success_24h' => 0,
                'top_ips' => [],
                'top_users' => [],
                'total_attempts' => 0
            ];
        }
    }
}
?>
