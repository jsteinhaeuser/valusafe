<?php
/**
 * Migration 006 — Lizenz-Zeilen aus app_settings entfernen
 *
 * Mit 4.3.16 ist die Lizenzmechanik entfallen: ValuSafe steht unter der
 * MIT-Lizenz und fragt nicht mehr bei licence.palindrom.de nach, ob es
 * laufen darf. Migration 004 hat die fuenf Zwischenspeicher-Zeilen als
 * Vorgabewerte angelegt; sie werden von niemandem mehr gelesen.
 *
 * 004 bleibt unveraendert — eine bereits angewendete Migration wird nicht
 * nachtraeglich umgeschrieben. Bei einer frischen Installation legt 004 die
 * Zeilen an und 006 raeumt sie wieder weg; unterm Strich bleibt nichts.
 *
 * Idempotent: DELETE trifft beim zweiten Lauf nichts mehr.
 */
return [
    'description' => 'app_settings: licence_*-Zeilen entfernen (Lizenzmechanik entfallen)',
    'statements'  => [
        "DELETE FROM `app_settings` WHERE `setting_key` IN "
        . "('licence_valid','licence_tier','licence_expires','licence_days_left','licence_last_check')",
    ],
];
