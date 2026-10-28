<?php
/**
 * Sitzung gegen die Datenbank abgleichen (OWASP-A07-003, 4.6).
 *
 * Bis 4.5.2 stand die Rolle nur in der Sitzung: Sie wurde beim Anmelden
 * einmal aus users gelesen und danach nie wieder. Ein herabgestufter oder
 * geloeschter Admin arbeitete mit seiner offenen Sitzung als Admin weiter,
 * und ein Passwortwechsel beendete keine andere Sitzung desselben Kontos.
 * Das Zeitlimit war nur gleitend - wer aktiv blieb, blieb ewig angemeldet.
 *
 * Jetzt liest db.php bei jeder Anfrage username, role und password des
 * angemeldeten Kontos und gibt sie an vsSitzungPruefen(). Ohne neue Spalte:
 * Statt einer Sitzungsversion merkt sich die Sitzung eine Kennung des
 * Passwort-Hashes. Aendert sich der Hash, passt sie nicht mehr.
 *
 * vsSitzungPruefen() und vsSitzungsCookieMerkmale() sind reine Funktionen
 * ohne Datenbank und ohne $_SESSION - getestet in tests/SitzungHelpersTest.php.
 * vsSitzungStarten() (unten) startet die Sitzung.
 */

/** Hoechstdauer einer Anmeldung in Sekunden, unabhaengig von Aktivitaet. */
function vsSitzungHoechstdauer(): int {
    if (defined('SITZUNG_HOECHSTDAUER') && (int)SITZUNG_HOECHSTDAUER > 0) {
        return (int)SITZUNG_HOECHSTDAUER;
    }
    return 12 * 3600;
}

/**
 * Kennung eines Passwort-Hashes fuer die Sitzung. Nicht der Hash selbst:
 * Sitzungsdateien liegen je nach Hoster in einem gemeinsamen Verzeichnis.
 */
function vsSitzungKennung(string $passwortHash): string {
    return substr(hash('sha256', 'valusafe-sitzung|' . $passwortHash), 0, 32);
}

/**
 * Prueft die Sitzung gegen die Zeile aus users und gleicht Rolle und
 * Benutzername an. Rueckgabe:
 *   'ok'          Sitzung gilt weiter (Rolle/Name ggf. aktualisiert)
 *   'geloescht'   Konto gibt es nicht mehr
 *   'passwort'    Passwort wurde seit der Anmeldung geaendert
 *   'abgelaufen'  Hoechstdauer seit der Anmeldung ueberschritten
 *
 * Sitzungen von vor 4.6 haben noch keine Kennung; sie bekommen sie beim
 * ersten Abgleich, statt alle Benutzer beim Update abzumelden.
 *
 * Rollenwechsel (switch_role.php): original_role folgt der Datenbank. Ist das
 * Konto kein Admin mehr, endet der Wechsel und die Datenbankrolle gilt.
 */
function vsSitzungPruefen(array &$sitzung, ?array $zeile, int $jetzt, int $hoechstdauer): string {
    if ($zeile === null) {
        return 'geloescht';
    }
    if (isset($sitzung['login_time']) && $jetzt - (int)$sitzung['login_time'] > $hoechstdauer) {
        return 'abgelaufen';
    }

    $kennung = vsSitzungKennung((string)($zeile['password'] ?? ''));
    if (!isset($sitzung['pw_kennung'])) {
        $sitzung['pw_kennung'] = $kennung;
    } elseif (!hash_equals((string)$sitzung['pw_kennung'], $kennung)) {
        return 'passwort';
    }

    $sitzung['username'] = (string)$zeile['username'];
    $dbRolle = (string)$zeile['role'];
    if (isset($sitzung['original_role'])) {
        $sitzung['original_role'] = $dbRolle;
        if ($dbRolle !== 'admin') {
            $sitzung['role'] = $dbRolle;
            unset($sitzung['original_role']);
        }
    } else {
        $sitzung['role'] = $dbRolle;
    }
    return 'ok';
}

/**
 * Merkmale des Sitzungs-Cookies vor session_start() (OWASP-A02-001, 4.6).
 *
 * Bis 4.5.2 setzte der Code keine: Ob das Cookie HttpOnly, Secure und
 * SameSite trug, hing am Hoster (php.ini) oder an Docker (entrypoint.sh).
 * Der Block in .htaccess griff nur bei mod_php7, also nie bei PHP 8 oder
 * PHP-FPM. Ohne HttpOnly wird jedes XSS zum Sitzungsdiebstahl.
 *
 * Was der Server schon strenger einstellt, bleibt: Secure bleibt an, ein
 * gesetztes SameSite (z. B. Strict) wird nicht auf Lax abgeschwaecht. Der
 * Cookie-Name bleibt, sonst waeren beim Update alle abgemeldet.
 */
function vsSitzungsCookieMerkmale(array $bisher, bool $istHttps): array {
    return [
        'lifetime' => (int)($bisher['lifetime'] ?? 0),
        'path'     => ($bisher['path'] ?? '') !== '' ? $bisher['path'] : '/',
        'domain'   => (string)($bisher['domain'] ?? ''),
        'secure'   => !empty($bisher['secure']) || $istHttps,
        'httponly' => true,
        'samesite' => ($bisher['samesite'] ?? '') !== '' ? $bisher['samesite'] : 'Lax',
    ];
}

/** Startet die Sitzung mit sicheren Cookie-Merkmalen, falls noch keine laeuft. */
function vsSitzungStarten(): void {
    if (session_status() !== PHP_SESSION_NONE || headers_sent()) {
        return;
    }
    $istHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string)($_SERVER['SERVER_PORT'] ?? '') === '443'
        || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params(vsSitzungsCookieMerkmale(session_get_cookie_params(), $istHttps));
    session_start();
}
