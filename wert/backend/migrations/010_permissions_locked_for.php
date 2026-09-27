<?php
/**
 * Migration 010 — permissions.locked_for zurueck auf set()
 *
 * Auf neun Instanzen steht die Spalte als varchar(50), auf dem Master und
 * im Auslieferungsschema als set('admin','edit','read'). Sie ist der letzte
 * dauerhaft rote Eintrag im Schema-Vergleich, der beide Seiten betrifft.
 *
 * EHRLICH GESAGT: das ist Kosmetik. Der Code liest die Spalte so —
 *
 *     $locked = array_filter(array_map('trim', explode(',', $row['locked_for'] ?? '')));
 *     (ajax_permissions.php:55, gleichlautend backend/permissions.php:264)
 *
 * — also als kommagetrennte Zeichenkette. Fuer varchar und set ist das
 * Verhalten identisch. permissions steht ausserdem nicht in SYNC_TABLES,
 * es wird also auch nichts zwischen Instanzen kopiert, was sich an der
 * Typdifferenz stossen koennte. Es gibt keine Funktion, die heute falsch
 * laeuft. Der Gewinn ist, dass die Datenbank die drei Werte wieder selbst
 * erzwingt und der Vergleich gruen wird.
 *
 * ANDERS ALS 008 IST DAS EINE VERENGUNG. Steht in einer Zeile ein Wert, der
 * keine Kombination aus admin/edit/read ist, bricht MySQL im strict mode mit
 * einem Fehler ab. Das ist so gewollt: migrate.php merkt diese Migration
 * dann NICHT als erledigt und faehrt sie beim naechsten Lauf erneut — es
 * geht nichts verloren, und im Bericht steht, welche Instanz betroffen ist.
 * Der Wert wird bewusst nicht automatisch geleert: locked_for sperrt eine
 * Rolle gegen Aenderungen, und eine Sperre still zu verwerfen waere die
 * schlechtere von zwei Ueberraschungen.
 *
 * Diese Migration darf also fehlschlagen, ohne dass etwas kaputtgeht. Wenn
 * das passiert, vorher nachsehen:
 *     SELECT id, action_key, locked_for FROM permissions
 *      WHERE locked_for IS NOT NULL AND locked_for <> ''
 *        AND locked_for NOT REGEXP '^(admin|edit|read)(,(admin|edit|read))*$';
 *
 * Sie steht absichtlich in einer eigenen Datei und nicht bei 009: die
 * fehlende Tabelle ist ein Defekt und soll auf jeden Fall durchgehen, auch
 * wenn diese Aufraeumarbeit irgendwo haengenbleibt.
 */
return [
    'description' => "permissions.locked_for auf set('admin','edit','read') (Kosmetik, keine Funktionsaenderung)",
    'statements'  => [
        "ALTER TABLE `permissions`
            MODIFY `locked_for` SET('admin','edit','read') DEFAULT NULL
            COMMENT 'Rollen die NICHT geändert werden dürfen'",
    ],
];
