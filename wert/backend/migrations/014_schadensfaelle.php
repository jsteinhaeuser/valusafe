<?php
/**
 * Migration 014 — Schadensfall-Assistent (4.4) und Seriennummer
 *
 * Nach einem Einbruch, Brand oder Wasserschaden markiert man die betroffenen
 * Gegenstaende in einem Schadensfall; daraus entstehen eine Liste fuer die
 * Polizei und eine Schadensmeldung fuer die Versicherung (schadenfall.php).
 *
 *   schadensfaelle             ein Fall: Art, Datum, Aktenzeichen, Versicherung,
 *                              Beschreibung, Status
 *   schadensfall_gegenstaende  welche Gegenstaende betroffen sind, mit Zustand
 *                              (gestohlen / zerstoert / beschaedigt) und Notiz
 *
 * Die alte Spalte wertsachen.schadenfall (Migration 001) wurde nie benutzt und
 * bleibt unangetastet - ein Gegenstand kann in mehreren Faellen vorkommen,
 * dafuer reicht ein Merker nicht.
 *
 * Dazu wertsachen.seriennummer: die Detailansicht fragte das Feld seit
 * Monaten ab, die Spalte gab es nie. Fuer die Polizei ist sie das wichtigste
 * Merkmal.
 *
 * Rechte: claims_view (Faelle und Dokumente ansehen), claims_manage (anlegen,
 * aendern, loeschen). Fehlen die Zeilen, gilt _permissionFallback() in db.php
 * mit demselben Standard.
 */
return [
    'description' => 'Schadensfaelle (2 Tabellen), wertsachen.seriennummer, Rechte claims_view/claims_manage',
    'statements'  => [
        "CREATE TABLE IF NOT EXISTS `schadensfaelle` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `art` varchar(20) NOT NULL DEFAULT 'einbruch',
            `datum` date DEFAULT NULL,
            `aktenzeichen` varchar(100) DEFAULT NULL,
            `versicherung_id` int(11) DEFAULT NULL,
            `beschreibung` text DEFAULT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'offen',
            `erstellt_von` varchar(100) DEFAULT NULL,
            `erstellt_am` datetime DEFAULT current_timestamp(),
            `geaendert_am` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "CREATE TABLE IF NOT EXISTS `schadensfall_gegenstaende` (
            `fall_id` int(11) NOT NULL,
            `wertsache_id` int(11) NOT NULL,
            `zustand` varchar(20) NOT NULL DEFAULT 'gestohlen',
            `notiz` text DEFAULT NULL,
            PRIMARY KEY (`fall_id`, `wertsache_id`),
            KEY `idx_wertsache` (`wertsache_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "ALTER TABLE `wertsachen` ADD COLUMN `seriennummer` varchar(100) DEFAULT NULL AFTER `barcode`",
        "INSERT IGNORE INTO `permissions`
            (`action_key`, `section`, `icon`, `label`, `sort_order`,
             `admin_can`, `edit_can`, `read_can`, `locked_for`)
         VALUES ('claims_view', 'Schadensfall', '🚨', 'Schadensfälle ansehen', 26, 1, 1, 1, 'admin'),
                ('claims_manage', 'Schadensfall', '📝', 'Schadensfälle anlegen und bearbeiten', 27, 1, 1, 0, 'admin')",
    ],
];
