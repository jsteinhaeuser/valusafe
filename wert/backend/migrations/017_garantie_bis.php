<?php
/**
 * Migration 017 — Garantieende (4.8)
 *
 * wertsachen.garantie_bis: letzter Tag der Garantie oder Gewaehrleistung.
 * Das Formular schlaegt Kaufdatum + 2 Jahre vor (gesetzliche
 * Gewaehrleistung in der EU); Dashboard, Listenfilter und die Mail von
 * cron_backup.php melden Garantien, die in den naechsten 30 Tagen enden.
 *
 * Ohne AFTER-Klausel wie 007/016; migrate.php toleriert 1060 (Spalte vorhanden).
 */
return [
    'description' => 'wertsachen: garantie_bis',
    'statements'  => [
        "ALTER TABLE `wertsachen` ADD COLUMN `garantie_bis` date DEFAULT NULL",
    ],
];
