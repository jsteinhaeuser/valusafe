<?php
/**
 * Migration 009 — die fehlende Tabelle password_resets nachziehen
 *
 * Gefunden mit dem Schema-Vergleich am 13.09.2026: sechs der elf Instanzen
 * melden 21 statt 22 Tabellen, und die fehlende ist immer dieselbe.
 *
 * WAS DAS NICHT IST: ein Defekt. "Passwort vergessen" funktioniert auf
 * diesen sechs Instanzen. forgot_password.php (Zeile 13) und
 * reset_password.php (Zeile 12) rufen beide ensurePasswordResetsTable()
 * auf, und die legt die Tabelle bei Bedarf selbst an. Die Tabelle fehlt
 * also schlicht, weil dort noch nie jemand sein Passwort zurueckgesetzt
 * hat. Nachgemessen: was diese Funktion erzeugt, ist Zeichen fuer Zeichen
 * dieselbe Tabelle wie install/schema.sql — auch in Kollation und
 * Indizes. Es gibt keine Divergenz, die darauf wartet zu entstehen.
 *
 * WARUM DANN DIESE MIGRATION: der Schema-Vergleich ist das Werkzeug, mit
 * dem echte Abweichungen auffallen sollen. Sechs Zeilen, die dauerhaft rot
 * stehen und immer harmlos sind, kosten genau die Aufmerksamkeit, die beim
 * siebten Eintrag gebraucht wird. Die Tabelle anzulegen ist billiger, als
 * sich jedes Mal daran zu erinnern, dass diese sechs nicht zaehlen.
 *
 * Zweiter, kleinerer Grund: die Tabelle entsteht sonst mitten in einem
 * Anmeldevorgang, bei dem der Benutzer ohnehin schon ein Problem hat. Ein
 * CREATE TABLE braucht dort Rechte, die der DB-Benutzer haben muss —
 * hat er sie nicht, scheitert es genau in dem Moment. Vorher anlegen
 * verlegt diese Frage aus dem Ernstfall in einen Wartungslauf.
 *
 * Die Definition ist wortgleich aus install/schema.sql uebernommen, damit
 * eine nachgezogene und eine frisch installierte Instanz danach dasselbe
 * Schema haben.
 *
 * CREATE TABLE IF NOT EXISTS: auf den fuenf Instanzen, die die Tabelle
 * schon haben, passiert nichts. Die Migration darf ueberall laufen.
 */
return [
    'description' => 'password_resets anlegen (fehlt auf sechs Instanzen — kein Defekt, aber Dauerrot im Schema-Vergleich)',
    'statements'  => [
        "CREATE TABLE IF NOT EXISTS `password_resets` (
            `id`         int(11) NOT NULL AUTO_INCREMENT,
            `user_id`    int(11) NOT NULL,
            `token_hash` varchar(64) NOT NULL,
            `expires_at` datetime NOT NULL,
            `used`       tinyint(1) NOT NULL DEFAULT 0,
            `created_at` datetime DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_token_hash` (`token_hash`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    ],
];
