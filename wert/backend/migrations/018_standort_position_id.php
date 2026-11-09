<?php
/**
 * Migration 018 — Altlast wertsachen.standort_position_id entfernen
 *
 * Die Spalte gibt es nur auf dem NAS und auf Gitarre 1, install/schema.sql
 * legt sie nicht an und kein Code liest sie. wertsachen steht in SYNC_TABLES:
 * ein DB-Sync von einer dieser beiden Instanzen aus scheitert auf den anderen.
 *
 * VOR DEM AUSFUEHREN auf NAS und Gitarre 1 pruefen, ob etwas verloren geht
 * (Anleitung: Dokumente/ANLEITUNG_Schema_angleichen_2026-11.txt).
 *
 * Auf den uebrigen Instanzen fehlt die Spalte: migration_runner.php toleriert
 * 1091, die Anweisung wird uebersprungen. Ein Index faellt mit der Spalte weg.
 */
return [
    'description' => 'wertsachen: Altlast standort_position_id entfernen',
    'statements'  => [
        "ALTER TABLE `wertsachen` DROP COLUMN `standort_position_id`",
    ],
];
