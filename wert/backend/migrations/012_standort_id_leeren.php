<?php
/**
 * Migration 012 — wertsachen.standort_id leeren, bevor sie benutzt wird
 *
 * Ab 26.09.2026 speichern add.php und edit.php den gewaehlten Standort in
 * wertsachen.standort_id. Vorher wurde er nirgends gespeichert: das
 * Auswahlfeld war nur ein Filter fuer die Positionen, und wer einen Standort
 * ohne Position waehlte, verlor ihn beim Speichern ohne Meldung.
 *
 * Die Spalte gibt es seit Migration 001, beschrieben hat sie bis heute nur
 * Migration 005 - und zwar mit raum_id, also mit RAUM-Nummern. Die neue
 * Anzeige in index.php liest standort_id als Nummer eines STANDORTS; ohne
 * diese Migration stuende bei fast jedem Gegenstand ein zufaelliger
 * Standort, naemlich der, der zufaellig dieselbe Nummer hat wie sein Raum.
 *
 * GEMESSEN AUF DEM MASTER AM 26.09.2026
 *   52 Gegenstaende, standort_id bei 50 gesetzt, bei ALLEN 50 gleich
 *   raum_id. Nichts davon ist eine echte Standortangabe.
 *
 * Geleert wird ohne Bedingung, nicht nur WHERE standort_id = raum_id: wurde
 * der Raum eines Gegenstands nach Migration 005 geaendert, steht in
 * standort_id die alte Raumnummer - ebenfalls falsch, aber von raum_id
 * verschieden.
 *
 * REIHENFOLGE BEI DER AUSLIEFERUNG: diese Migration AUSFUEHREN, BEVOR die
 * neuen add.php/edit.php auf einer Instanz liegen. Sonst loescht sie
 * Standorte, die dort in der Zwischenzeit schon gespeichert wurden.
 *
 * NEUINSTALLATIONEN laufen sie nie: setup.php und seit 26.09.2026 auch
 * docker/docker/entrypoint.sh tragen beim Aufsetzen alle vorhandenen
 * Migrationen als erledigt ein.
 */
return [
    'description' => 'wertsachen.standort_id leeren (enthielt Raumnummern aus Migration 005) und indizieren',
    'statements'  => [
        "UPDATE `wertsachen` SET `standort_id` = NULL WHERE `standort_id` IS NOT NULL",
        "ALTER TABLE `wertsachen` ADD KEY `idx_standort` (`standort_id`)",
    ],
];
