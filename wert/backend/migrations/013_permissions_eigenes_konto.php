<?php
/**
 * Migration 013 — eigenes Konto: Passwort und Loeschen abschaltbar
 *
 * Auf einer oeffentlichen Demo teilen sich alle Besucher ein Editor-Konto.
 * Bis 4.3.32 konnte jeder Besucher
 *   - dessen Passwort aendern (settings.php, profil.php) und damit alle
 *     anderen aussperren, und
 *   - es ueber "Account loeschen" (DSGVO) ganz loeschen.
 * "Eigenes Passwort aendern" stand zwar in der Rechteverwaltung, war aber
 * fuer alle Rollen gesperrt (locked_for 'admin,edit,read') und wurde im Code
 * nirgends geprueft.
 *
 * Jetzt:
 *   - settings_password ist fuer Editor und Leser abschaltbar (nur Admin
 *     bleibt fest) und wird in settings.php und profil.php geprueft;
 *   - neues Recht settings_delete_account, Standard wie bisher: alle duerfen.
 *
 * An den Rechten aendert sich durch die Migration nichts - wer nichts
 * umstellt, merkt keinen Unterschied. Auf der Demo-Instanz schaltet der Admin
 * beides fuer Editor ab.
 *
 * Fehlt die neue Zeile (Migration noch nicht gelaufen), faellt hasPermission()
 * auf _permissionFallback() in db.php zurueck - dort steht derselbe Standard.
 */
return [
    'description' => 'Rechte: eigenes Passwort fuer Editor/Leser abschaltbar, neues Recht "Eigenes Konto loeschen"',
    'statements'  => [
        "UPDATE `permissions` SET `locked_for` = 'admin'
          WHERE `action_key` = 'settings_password'",
        "INSERT IGNORE INTO `permissions`
            (`action_key`, `section`, `icon`, `label`, `sort_order`,
             `admin_can`, `edit_can`, `read_can`, `locked_for`)
         VALUES ('settings_delete_account', 'Einstellungen', '🗑️',
                 'Eigenes Konto löschen', 25, 1, 1, 1, 'admin')",
    ],
];
