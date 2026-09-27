<?php
/**
 * Migration 005 — Datenmigration: standort_id aus raum_id befüllen
 *
 * Muss nach 001 (standort_id existiert) laufen. Idempotent: die WHERE-
 * Bedingung greift nur bei Datensätzen, die noch nicht migriert wurden.
 */
return [
    'description' => 'wertsachen: standort_id aus raum_id befüllen (v4.0-Datenmigration)',
    'statements'  => [
        "UPDATE `wertsachen` SET `standort_id` = `raum_id` WHERE `raum_id` IS NOT NULL AND `standort_id` IS NULL",
    ],
];
