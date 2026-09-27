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
    const WINDOW    = 2;   // ±2 Periods akzeptieren (60s Toleranz)

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
