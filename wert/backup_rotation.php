<?php
/**
 * ValuSafe — Aufbewahrung der Sicherungen
 *
 * Gemeinsam benutzt von backend/backup.php (manuelle Sicherung) und
 * cron_backup.php (automatische Sicherung). Bis 4.3.24 hatten beide eine
 * eigene Schleife, die auseinandergelaufen war: die manuelle loeschte auch
 * cron.lock und ignorierte BACKUP_RETENTION_DAYS.
 *
 * Regeln:
 *   1. Angefasst werden nur Sicherungsdateien:
 *        database_JJJJ-MM-TT_hh-mm-ss.sql
 *        files_JJJJ-MM-TT_hh-mm-ss.zip
 *        php_JJJJ-MM-TT_hh-mm-ss.zip
 *      Alles andere im Ordner bleibt unberuehrt.
 *   2. Je Art bleiben die neuesten $mindestens IMMER erhalten, egal wie alt.
 *      Sonst loescht eine Sicherung ohne Datenbank (oder eine Woche mit
 *      ausgefallenem Cron) die letzte gute Datenbank-Sicherung.
 *   3. Von den uebrigen wird geloescht, was aelter als $tage ist.
 *
 * Rueckgabe: Liste der geloeschten Dateien als [Name, Alter in Tagen].
 */

if (!function_exists('sicherungenAufraeumen')) {
    function sicherungenAufraeumen(string $dir, int $tage, int $mindestens = 3): array
    {
        $muster = '/^(database|files|php)_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.(sql|zip)$/';
        $jeArt  = [];
        foreach (glob(rtrim($dir, '/') . '/*') ?: [] as $pfad) {
            if (!is_file($pfad)) continue;
            if (!preg_match($muster, basename($pfad), $m)) continue;
            $jeArt[$m[1]][] = $pfad;
        }

        $geloescht = [];
        $jetzt     = time();
        foreach ($jeArt as $liste) {
            // Neueste zuerst: der Zeitstempel im Namen sortiert als Text richtig
            rsort($liste, SORT_STRING);
            foreach (array_slice($liste, max(0, $mindestens)) as $pfad) {
                $alter = (int)floor(($jetzt - filemtime($pfad)) / 86400);
                if ($alter > $tage && @unlink($pfad)) {
                    $geloescht[] = [basename($pfad), $alter];
                }
            }
        }
        return $geloescht;
    }
}
