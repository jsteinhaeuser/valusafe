<?php
/**
 * Migration 011 — Standort-Altlasten austragen
 *
 * Entfernt, was install/schema.sql seit dem 26.09.2026 nicht mehr anlegt:
 *   wertsachen.ort_id samt Fremdschluessel wertsachen_ibfk_1 (-> orte)
 *   orte, standort_raeume, standort_positionen
 *
 * Keine der vier wird vom Code gelesen. orte ist das erste Raummodell, seit
 * der Umstellung auf raeume ohne Leser. standort_raeume und
 * standort_positionen gehoerten zu backend/standorte.php, einer zweiten
 * Ortsverwaltung, die nie mit wertsachen verbunden war (entfernt mit 4.3.21).
 *
 * GEMESSEN AUF DEM MASTER AM 26.09.2026
 *   - 8 Fremdschluessel, dieselben wie im alten schema.sql, darunter
 *     wertsachen_ibfk_1 und standort_positionen_ibfk_1.
 *   - orte 19, standort_raeume 23, standort_positionen 6 Zeilen.
 *   - ort_id auf 50 Gegenstaenden gesetzt, bei ALLEN 50 gleich raum_id und
 *     gleicher Raumname. Mit der Spalte geht nichts verloren.
 *   - Die 6 standort_positionen (Vitrine links, Sideboard, Schreibtisch,
 *     3x Wand) passen nicht ins Modell raeume -> standorte -> positionen,
 *     kein Gegenstand hat je auf sie verwiesen. Sie werden verworfen; wer
 *     sie braucht, legt sie in backend/locations.php als Standorte an.
 *
 * VOR DEM AUSFUEHREN - AUF JEDER INSTANZ, nicht nur auf einer
 *
 *   1. Sicherung der Datenbank. DROP TABLE ist nicht umkehrbar.
 *
 *   2. Geht mit ort_id etwas verloren? Muss LEER sein:
 *        SELECT w.id, w.name, w.ort_id, o.name, w.raum_id, r.name
 *          FROM wertsachen w
 *          LEFT JOIN orte   o ON o.id = w.ort_id
 *          LEFT JOIN raeume r ON r.id = w.raum_id
 *         WHERE w.ort_id IS NOT NULL
 *           AND (w.raum_id IS NULL OR w.raum_id <> w.ort_id
 *                OR r.name IS NULL OR o.name <> r.name);
 *      Nicht leer: diese Instanz NICHT migrieren, erst von Hand klaeren.
 *      Ein automatisches raum_id = ort_id waere nur richtig, wenn die
 *      Nummern in orte und raeume uebereinstimmen - das ist gerade die
 *      Frage.
 *
 *   3. Wie heisst der Fremdschluessel auf ort_id?
 *        SELECT TABLE_SCHEMA, TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME,
 *               REFERENCED_TABLE_NAME
 *          FROM information_schema.KEY_COLUMN_USAGE
 *         WHERE TABLE_SCHEMA NOT IN ('information_schema','mysql',
 *                                    'performance_schema','sys')
 *           AND REFERENCED_TABLE_NAME IS NOT NULL;
 *      Nicht mit TABLE_SCHEMA = DATABASE(): phpMyAdmin wechselt bei einer
 *      Abfrage auf information_schema dorthin, und die Antwort ist dann
 *      leer, obwohl es die Schluessel gibt.
 *
 * VERHALTEN BEI ABWEICHUNGEN
 *   - Kein Fremdschluessel auf ort_id: DROP FOREIGN KEY meldet 1091 und
 *     wird uebersprungen. Harmlos.
 *   - Fremdschluessel unter ANDEREM Namen: DROP FOREIGN KEY wird ebenfalls
 *     uebersprungen, DROP COLUMN bricht dann aber ab (1828). Das ist
 *     gewollt - die Migration gilt nicht als erledigt, der Bericht nennt
 *     die Instanz. Schluessel von Hand loeschen, erneut laufen lassen.
 *   - Spalte oder Tabellen schon weg: 1091 bzw. IF EXISTS, uebersprungen.
 *   - MariaDB fuehrt DDL nicht in einer Transaktion aus. Bricht die
 *     Migration mittendrin ab, bleibt das Erledigte erledigt; der naechste
 *     Lauf ueberspringt es.
 *
 * Der Index idx_ort faellt mit der Spalte weg.
 *
 * Reihenfolge ist zwingend: erst der Fremdschluessel auf orte, dann die
 * Spalte, dann standort_positionen vor standort_raeume (Fremdschluessel
 * standort_positionen_ibfk_1), dann orte.
 */
return [
    'description' => 'Standort-Altlasten: wertsachen.ort_id, orte, standort_raeume, standort_positionen entfernen',
    'statements'  => [
        "ALTER TABLE `wertsachen` DROP FOREIGN KEY `wertsachen_ibfk_1`",
        "ALTER TABLE `wertsachen` DROP COLUMN `ort_id`",
        "DROP TABLE IF EXISTS `standort_positionen`",
        "DROP TABLE IF EXISTS `standort_raeume`",
        "DROP TABLE IF EXISTS `orte`",
    ],
];
