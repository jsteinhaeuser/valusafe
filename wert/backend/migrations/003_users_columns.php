<?php
/**
 * Migration 003 — users: lang, sieht_alle, avatar, last_seen_version
 *
 * last_seen_version ersetzt die Laufzeit-ALTER-TABLE-Versuche, die bisher
 * bei jedem Seitenaufruf in backend/update_check.php ausgeführt wurden.
 */
return [
    'description' => 'users: lang, sieht_alle, avatar, last_seen_version',
    'statements'  => [
        "ALTER TABLE `users` ADD COLUMN `lang` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'de' AFTER `theme`",
        "ALTER TABLE `users` ADD COLUMN `sieht_alle` TINYINT(1) NOT NULL DEFAULT 0 AFTER `role`",
        "ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(255) NULL AFTER `lang`",
        "ALTER TABLE `users` ADD COLUMN `last_seen_version` VARCHAR(20) NULL AFTER `avatar`",
    ],
];
