<?php
/**
 * Migration 015 — user_activity leeren ("Wer ist online" ausgebaut)
 *
 * Die Anzeige "Wer ist online" (backend/users.php, Kachel in
 * backend/index.php) wurde am 27.10.2026 entfernt. Sie war ohnehin tot:
 * user_activity.php, das die Tabelle fuellte, band seit dem Wechsel von
 * header.php auf header_next*.php keine Seite mehr ein.
 *
 * In der Tabelle stehen aber noch die alten Eintraege - je Benutzer
 * IP-Adresse, zuletzt besuchte Seite und Zeitpunkt. Die braucht niemand
 * mehr, also weg damit.
 *
 * Die TABELLE bleibt: dsgvoKontoLoeschen() (dsgvo_helpers.php) loescht darin
 * und wuerde ohne sie jede Kontoloeschung abbrechen; install/schema.sql,
 * Datensicherung und Datenuebernahme kennen sie ebenfalls.
 */
return [
    'description' => 'user_activity leeren ("Wer ist online" ausgebaut, alte IP-Adressen)',
    'statements'  => [
        "DELETE FROM `user_activity`",
    ],
];
