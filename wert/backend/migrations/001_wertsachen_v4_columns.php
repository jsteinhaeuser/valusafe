<?php
/**
 * Migration 001 — wertsachen: Spalten aus v4.0/v4.1 nachziehen
 * (hidden, schadenfall, standort_id, public_token)
 *
 * Ersetzt: setup_hidden_column.php + den entsprechenden Teil aus
 * db_check.php ("fix_sql"-Block für wertsachen).
 */
return [
    'description' => 'wertsachen: hidden, schadenfall, standort_id, public_token',
    'statements'  => [
        "ALTER TABLE `wertsachen` ADD COLUMN `hidden` TINYINT(1) DEFAULT 0 AFTER `geaendert_am`",
        "ALTER TABLE `wertsachen` ADD COLUMN `schadenfall` TINYINT(1) DEFAULT 0 AFTER `hidden`",
        "ALTER TABLE `wertsachen` ADD COLUMN `standort_id` int DEFAULT NULL AFTER `raum_id`",
        "ALTER TABLE `wertsachen` ADD COLUMN `public_token` varchar(64) DEFAULT NULL AFTER `oeffentlich`",
    ],
];
