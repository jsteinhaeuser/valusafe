<?php
/**
 * Migration 007 — versicherungen: Spalten der Vertrags- und Objektdaten nachziehen
 *
 * Die Tabelle `versicherungen` wurde von keiner Migration je angefasst. Sie
 * sieht auf jeder Instanz so aus, wie sie beim Aufsetzen entstanden ist. Auf
 * dem Master hat sie 25 Spalten, auf einer Testinstanz fehlen welche — dort scheiterte
 * "Versicherung bearbeiten" mit einer gruenen Erfolgsmeldung, weil der UPDATE
 * 23 Spalten nennt, MySQL die ganze Anweisung ablehnt und
 * Database::execute() den Fehler still ins PHP-Fehlerlog schrieb.
 *
 * migrate.php toleriert Fehlercode 1060 (Duplicate column name). Auf einer
 * vollstaendigen Instanz meldet jede Anweisung hier deshalb "skip" und
 * bewirkt nichts. Der Bericht von migrate.php zeigt zugleich, welche Spalten
 * tatsaechlich gefehlt haben: alles mit "ok" war vorher nicht da.
 *
 * Bewusst ohne AFTER-Klauseln: die Bezugsspalte koennte auf einer Instanz
 * fehlen, und Fehlercode 1054 (Unknown column) waere nicht tolerierbar. Die
 * Reihenfolge der Spalten ist fuer die Anwendung ohne Belang.
 */
return [
    'description' => 'versicherungen: Vertrags- und Objektdaten-Spalten nachziehen',
    'statements'  => [
        "ALTER TABLE `versicherungen` ADD COLUMN `versicherungssumme` DECIMAL(10,2) DEFAULT NULL",
        "ALTER TABLE `versicherungen` ADD COLUMN `selbstbeteiligung` DECIMAL(10,2) DEFAULT NULL",
        "ALTER TABLE `versicherungen` ADD COLUMN `vertragsbeginn` DATE DEFAULT NULL",
        "ALTER TABLE `versicherungen` ADD COLUMN `kuendigungsfrist` VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE `versicherungen` ADD COLUMN `zahlungsweise` ENUM('monatlich','vierteljaehrlich','halbjaehrlich','jaehrlich') DEFAULT NULL",
        "ALTER TABLE `versicherungen` ADD COLUMN `adresse` VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE `versicherungen` ADD COLUMN `plz` VARCHAR(10) DEFAULT NULL",
        "ALTER TABLE `versicherungen` ADD COLUMN `wohnort` VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE `versicherungen` ADD COLUMN `wohnflaeche_qm` DECIMAL(6,1) DEFAULT NULL",
        "ALTER TABLE `versicherungen` ADD COLUMN `anzahl_zimmer` INT DEFAULT NULL",
        "ALTER TABLE `versicherungen` ADD COLUMN `gebaeudeart` ENUM('Wohnung','Haus','Gewerbe','Sonstiges') DEFAULT NULL",
        "ALTER TABLE `versicherungen` ADD COLUMN `etage` INT DEFAULT NULL",
        "ALTER TABLE `versicherungen` ADD COLUMN `keller` TINYINT(1) DEFAULT 0",
        "ALTER TABLE `versicherungen` ADD COLUMN `fahrraddiebstahl` TINYINT(1) DEFAULT 0",
        "ALTER TABLE `versicherungen` ADD COLUMN `glasbruch` TINYINT(1) DEFAULT 0",
        "ALTER TABLE `versicherungen` ADD COLUMN `elementarschaeden` TINYINT(1) DEFAULT 0",
        "ALTER TABLE `versicherungen` ADD COLUMN `ueberspannung` TINYINT(1) DEFAULT 0",
    ],
];
