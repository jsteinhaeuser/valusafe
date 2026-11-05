<?php
/**
 * Migration 016 — Hersteller, Modell, Erhaltungszustand (4.8)
 *
 * Drei neue Felder je Gegenstand (Wunsch aus dem alternativeto-Vergleich,
 * 02.11.2026). Bisher stand das in den Notizen oder im Namen
 * ("Laptop, 14 Zoll"); fuer Polizeiliste und Schadensmeldung und fuer die
 * Suche ist ein eigenes Feld besser.
 *
 *   hersteller          frei, z. B. "Yamaha", "Apple"
 *   modell              frei, z. B. "C3X", "MacBook Air M2"
 *   erhaltungszustand   feste Auswahl: neu | sehr_gut | gut | gebraucht | defekt
 *                       (Anzeige uebersetzt, cond_*). NICHT "zustand": so heisst
 *                       schon schadensfall_gegenstaende.zustand (gestohlen/...),
 *                       und schadenfall_dokument.php liest "w.*, g.zustand".
 *
 * VARCHAR statt ENUM: neue Stufen spaeter ohne Migration; die Anwendung
 * laesst nur die bekannten Werte durch (vsErhaltungszustaende(), helpers.php).
 * Ohne AFTER-Klausel wie 007; migrate.php toleriert 1060 (Spalte vorhanden).
 */
return [
    'description' => 'wertsachen: hersteller, modell, erhaltungszustand',
    'statements'  => [
        "ALTER TABLE `wertsachen` ADD COLUMN `hersteller` varchar(100) DEFAULT NULL",
        "ALTER TABLE `wertsachen` ADD COLUMN `modell` varchar(100) DEFAULT NULL",
        "ALTER TABLE `wertsachen` ADD COLUMN `erhaltungszustand` varchar(20) DEFAULT NULL",
    ],
];
