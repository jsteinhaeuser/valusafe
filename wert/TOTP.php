<?php
/**
 * TOTP.php — Time-based One-Time Password (RFC 6238)
 * Eigene Implementierung ohne externe Dependencies
 * Kompatibel mit Google Authenticator, Authy, etc.
 */

class TOTP {
    const DIGITS    = 6;
    const PERIOD    = 30;  // Sekunden
    const ALGORITHM = 'sha1';
    const WINDOW    = 1;   // ±1 Periode (30 s Toleranz, RFC 6238); bis 4.5.1 ±2 = 5 gueltige Codes

    /**
     * Generiert ein zufälliges Base32-Secret (160 Bit = 32 Zeichen)
     */
    public static function generateSecret(): string {
        $bytes = random_bytes(20);
        return self::base32Encode($bytes);
    }

    /**
     * Generiert den aktuellen TOTP-Code
     */
    public static function getCode(string $secret, ?int $timestamp = null): string {
        $timestamp = $timestamp ?? time();
        $counter   = intdiv($timestamp, self::PERIOD);
        return self::hotp($secret, $counter);
    }

    /**
     * Validiert einen TOTP-Code (mit Zeitfenster-Toleranz)
     */
    public static function verify(string $secret, string $code): bool {
        $code = trim(strtoupper($code));
        // Nur 6-stellige TOTP-Codes hier prüfen (Backup-Codes separat)
        if (!preg_match('/^\d{6}$/', $code)) return false;

        $timestamp = time();
        $counter   = intdiv($timestamp, self::PERIOD);

        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            if (hash_equals(self::hotp($secret, $counter + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generiert otpauth:// URL für QR-Code
     */
    public static function getOtpauthUrl(string $secret, string $account, string $issuer = 'ValuSafe'): string {
        $account = rawurlencode($account);
        $issuer  = rawurlencode($issuer);
        return "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Generiert QR-Code als SVG (via API-freier Implementierung)
     * Nutzt Google Charts API falls verfügbar, sonst URL anzeigen
     */
    public static function getQrCodeUrl(string $otpauthUrl): string {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($otpauthUrl);
    }

    /**
     * Generiert Backup-Codes (8 Stück, je 8 Zeichen)
     */
    public static function generateBackupCodes(int $count = 8): array {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }

    /**
     * Ersatzcodes zum Speichern hashen (OWASP-A06-004, 4.6). Bis 4.5.2 lagen
     * sie im Klartext in users.totp_backup_codes: Wer eine Datenbanksicherung
     * las, konnte die zweite Stufe umgehen.
     */
    public static function hashBackupCodes(array $codes): array {
        return array_map(static fn($c) => password_hash(strtoupper((string)$c), PASSWORD_DEFAULT), array_values($codes));
    }

    /**
     * Sucht einen eingegebenen Ersatzcode in der gespeicherten Liste und gibt
     * dessen Index zurueck, sonst false. Kennt gehashte Eintraege und - fuer
     * Listen von vor 4.6 - Klartext.
     */
    public static function findBackupCode(array $gespeichert, string $code): int|false {
        $code = strtoupper(trim($code));
        if ($code === '' || !preg_match('/^[0-9A-F]{8}$/', $code)) {
            return false;
        }
        foreach (array_values($gespeichert) as $i => $eintrag) {
            $eintrag = (string)$eintrag;
            if (self::istGehashterCode($eintrag)) {
                if (password_verify($code, $eintrag)) return $i;
            } elseif (hash_equals(strtoupper($eintrag), $code)) {
                return $i;
            }
        }
        return false;
    }

    /** Liste mit Klartext-Eintraegen (von vor 4.6) auf Hashes umstellen. */
    public static function backupCodesUmstellen(array $gespeichert): array {
        return array_map(static fn($e) => self::istGehashterCode((string)$e)
            ? (string)$e : password_hash(strtoupper((string)$e), PASSWORD_DEFAULT), array_values($gespeichert));
    }

    public static function brauchtUmstellung(array $gespeichert): bool {
        foreach ($gespeichert as $e) {
            if (!self::istGehashterCode((string)$e)) return true;
        }
        return false;
    }

    private static function istGehashterCode(string $eintrag): bool {
        return str_starts_with($eintrag, '$');
    }

    // ── Interne Hilfsmethoden ──────────────────────────────────────────────

    private static function hotp(string $secret, int $counter): string {
        $key     = self::base32Decode($secret);
        $msg     = pack('N*', 0) . pack('N*', $counter);
        $hash    = hash_hmac(self::ALGORITHM, $msg, $key, true);
        $offset  = ord($hash[19]) & 0x0F;
        $code    = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
            ((ord($hash[$offset + 3]) & 0xFF))
        ) % (10 ** self::DIGITS);
        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output   = '';
        $buffer   = 0;
        $bitsLeft = 0;

        foreach (str_split($data) as $char) {
            $buffer   = ($buffer << 8) | ord($char);
            $bitsLeft += 8;
            while ($bitsLeft >= 5) {
                $output  .= $alphabet[($buffer >> ($bitsLeft - 5)) & 31];
                $bitsLeft -= 5;
            }
        }
        if ($bitsLeft > 0) {
            $output .= $alphabet[($buffer << (5 - $bitsLeft)) & 31];
        }
        return $output;
    }

    private static function base32Decode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data     = strtoupper($data);
        $output   = '';
        $buffer   = 0;
        $bitsLeft = 0;

        foreach (str_split($data) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) continue;
            $buffer   = ($buffer << 5) | $pos;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $output  .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
                $bitsLeft -= 8;
            }
        }
        return $output;
    }
}
