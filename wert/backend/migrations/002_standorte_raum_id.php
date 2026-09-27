<?php
/**
 * Migration 002 — standorte: raum_id nachziehen (v4.0 Standort-Hierarchie)
 */
return [
    'description' => 'standorte: raum_id',
    'statements'  => [
        "ALTER TABLE `standorte` ADD COLUMN `raum_id` int DEFAULT NULL AFTER `id`",
    ],
];
