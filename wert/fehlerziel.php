<?php
/**
 * ValuSafe — Ziel fuer Fehlermeldungen festlegen
 *
 * Entstanden am 23.09.2026. Vorgeschichte: Am 18./19.09. kostete es zwei Tage,
 * einen HTTP 500 auf dem NAS zu SEHEN; die Behebung dauerte zehn Minuten.
 * Bei lima-city ist das Protokoll des Webservers fuer den Kunden nicht
 * erreichbar — eine Meldung wird dort festgehalten und ist trotzdem weg.
 *
 * Warum hier und nicht in einer .user.ini:
 *   - Der Pfad ist auf jeder Instanz anders. __DIR__ liefert ihn von selbst;
 *     eine .user.ini braeuchte elf verschiedene Dateien und elf FTP-Sitzungen.
 *   - Eine .user.ini wird NUR bei CGI/FastCGI/FPM gelesen. Laeuft PHP als
 *     Apache-Modul, liegt sie da und tut nichts. ini_set wirkt immer.
 *   - Diese Datei wird synchronisiert und vom Versions-Vergleich gesehen.
 *     Die .user.ini nicht: 'ini' steht in keiner der Erweiterungslisten.
 *
 * Die wichtigste Regel steht unten: Das Ziel wird nur dann umgestellt, wenn
 * dort auch geschrieben werden kann. Ein error_log auf einen unbeschreibbaren
 * Pfad ist schlimmer als keines — die Meldung verschwindet dann vollstaendig,
 * statt wenigstens im Protokoll des Webservers zu landen.
 *
 * display_errors wird aktiv AUSgeschaltet. Gemessen am 23.09.2026: Eine Instanz
 * hatte log_errors=An/display_errors=Aus, eine andere genau umgekehrt — beim
 * selben Anbieter. Dort wurde also nichts festgehalten und alles dem Besucher
 * gezeigt, Dateipfade inklusive, auf einer Instanz mit einem echten Nutzer.
 * Die Vorgabe des Anbieters unberuehrt zu lassen heisst, sich auf etwas zu
 * verlassen, das je Konto anders ist. Wer die Anzeige auf einer Testinstanz
 * will, setzt FEHLER_ANZEIGEN in deren config.php — Instanzzustand gehoert in
 * die config.php, nicht in den Quelltext.
 */

if (!function_exists('fehlerzielEinrichten')) {
    /**
     * @param string $appDir   Verzeichnis der Anwendung
     * @param int    $maxBytes Ab dieser Groesse wird die Datei einmal beiseite gelegt
     * @return array Was getan wurde — fuer die Anzeige in backend/system.php
     */
    function fehlerzielEinrichten(string $appDir, int $maxBytes = 2097152): array
    {
        $verz  = rtrim($appDir, '/') . '/logs';
        $datei = $verz . '/php_errors.log';
        $stand = ['verzeichnis' => $verz, 'datei' => $datei,
                  'gesetzt' => false, 'rotiert' => false, 'grund' => ''];

        error_reporting(E_ALL);
        ini_set('log_errors', '1');

        ini_set('display_errors', (defined('FEHLER_ANZEIGEN') && FEHLER_ANZEIGEN) ? '1' : '0');

        if (!is_dir($verz) && !@mkdir($verz, 0755, true) && !is_dir($verz)) {
            $stand['grund'] = 'Verzeichnis logs/ liess sich nicht anlegen';
            return $stand;   // Ziel bleibt das Protokoll des Webservers
        }

        // Nicht unbegrenzt wachsen lassen: eine Vorgaengerdatei genuegt.
        if (is_file($datei) && filesize($datei) > $maxBytes) {
            $stand['rotiert'] = @rename($datei, $datei . '.1');
        }

        $schreibbar = is_file($datei) ? is_writable($datei) : is_writable($verz);
        if (!$schreibbar) {
            $stand['grund'] = 'logs/ ist nicht beschreibbar';
            return $stand;   // bewusst NICHT umstellen
        }

        ini_set('error_log', $datei);
        $stand['gesetzt'] = true;
        return $stand;
    }
}

// Beim Einbinden sofort wirksam. Der Testlauf setzt FEHLERZIEL_KEIN_AUTOSTART,
// um die Funktion einzeln pruefen zu koennen.
if (!defined('FEHLERZIEL_KEIN_AUTOSTART')) {
    $GLOBALS['fehlerziel'] = fehlerzielEinrichten(__DIR__);
}
