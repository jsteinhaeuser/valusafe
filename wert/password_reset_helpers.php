<?php
/**
 * password_reset_helpers.php
 * Gemeinsame Funktionen für Self-Service-Passwort-Reset (ValuSafe / MySQL).
 * Wird von forgot_password.php und reset_password.php genutzt.
 */

if (!function_exists('ensurePasswordResetsTable')) {
    function ensurePasswordResetsTable($db) {
        try {
            $db->execute("
                CREATE TABLE IF NOT EXISTS password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token_hash VARCHAR(64) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    used TINYINT(1) NOT NULL DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_token_hash (token_hash),
                    INDEX idx_user_id (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (Exception $e) {
            if (class_exists('Security')) {
                Security::logSecurityEvent('password_reset_table_error', ['error' => $e->getMessage()]);
            }
        }
    }
}

/**
 * Erzeugt einen neuen Reset-Token für einen Benutzer.
 * Gibt den KLARTEXT-Token zurück (nur einmal sichtbar, wird nur gehasht gespeichert).
 */
if (!function_exists('createPasswordResetToken')) {
    function createPasswordResetToken($db, int $userId, int $lifetimeMinutes = 60): string {
        // Alte, noch gültige Tokens für diesen Nutzer entwerten
        $db->execute("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0", [$userId]);

        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $db->execute(
            "INSERT INTO password_resets (user_id, token_hash, expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))",
            [$userId, $tokenHash, $lifetimeMinutes]
        );

        return $token;
    }
}

/**
 * Prüft einen Reset-Token. Gibt die user_id zurück, wenn gültig, sonst null.
 */
if (!function_exists('validatePasswordResetToken')) {
    function validatePasswordResetToken($db, string $token): ?int {
        if ($token === '') return null;
        $tokenHash = hash('sha256', $token);

        $row = $db->selectOne(
            "SELECT user_id FROM password_resets
             WHERE token_hash = ? AND used = 0 AND expires_at > NOW()",
            [$tokenHash]
        );

        return $row ? (int)$row['user_id'] : null;
    }
}

if (!function_exists('consumePasswordResetToken')) {
    function consumePasswordResetToken($db, string $token): void {
        $tokenHash = hash('sha256', $token);
        $db->execute("UPDATE password_resets SET used = 1 WHERE token_hash = ?", [$tokenHash]);
    }
}

/**
 * Sendet die Reset-E-Mail. Gibt true/false zurück (Erfolg von PHP mail()).
 */
if (!function_exists('sendPasswordResetEmail')) {
    function sendPasswordResetEmail(string $toEmail, string $resetLink, string $appName): bool {
        $subject = $appName . ': Passwort zurücksetzen';
        $body  = "Hallo,\n\n";
        $body .= "für dein $appName-Konto wurde ein Passwort-Reset angefordert.\n";
        $body .= "Falls du das nicht warst, kannst du diese E-Mail ignorieren.\n\n";
        $body .= "Zum Zurücksetzen des Passworts hier klicken (1 Stunde gültig):\n";
        $body .= $resetLink . "\n\n";
        $body .= "Viele Grüße\n$appName";

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $headers  = "From: noreply@" . $host . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        return @mail($toEmail, $subject, $body, $headers);
    }
}
