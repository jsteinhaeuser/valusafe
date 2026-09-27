<?php
/**
 * Migration 008 — drei Spalten verbreitern, damit die Flotte zusammenpasst
 *
 * Gefunden mit dem Schema-Vergleich am 07.09.2026. Von den 19 Abweichungen
 * zwischen den elf Instanzen sind das die drei, die Schaden anrichten
 * koennen; der Rest ist Buchhaltung.
 *
 *   wertsachen.custom1_wert   auf einer Instanz varchar(255) statt text.
 *   wertsachen.custom2_wert   255 Zeichen gegen 65.535. wertsachen steht in
 *                             SYNC_TABLES — beim Kopieren vom Master dorthin
 *                             wuerde ein laengeres Freifeld abgeschnitten
 *                             oder der Import abbrechen.
 *
 *   users.email               auf valusafe.palindrom.de varchar(100) statt
 *                             varchar(190). Die 190 sind kein Zufall: bis
 *                             dahin bleibt ein utf8mb4-Index unter der alten
 *                             767-Byte-Schranke.
 *
 * Alle drei sind VERBREITERUNGEN. Sie koennen nichts abschneiden, brauchen
 * keine Werteprüfung vorweg und sind dort, wo die Spalte schon breit genug
 * ist, ein Nichts — anders als ADD COLUMN wirft MODIFY keinen Fehler, wenn
 * die Definition bereits stimmt.
 *
 * BEWUSST NICHT DABEI: custom1_typ und custom2_typ stehen auf einer Instanz
 * als varchar(10) statt enum('text','zahl'). Die Umwandlung waere eine
 * Verengung — ein Wert ausserhalb der beiden wuerde zu ''. Sie bringt auch
 * nichts: add.php und edit.php lassen ohnehin nur 'text' und 'zahl' durch
 *     $custom1_typ = in_array($_POST['custom1_typ'] ?? '', ['text','zahl'])
 *                  ? $_POST['custom1_typ'] : 'text';
 * Das enum erzwingt also nichts, was der Code nicht schon erzwingt.
 */
return [
    'description' => 'wertsachen.custom1_wert/custom2_wert auf TEXT, users.email auf VARCHAR(190)',
    'statements'  => [
        "ALTER TABLE `wertsachen` MODIFY `custom1_wert` TEXT NULL",
        "ALTER TABLE `wertsachen` MODIFY `custom2_wert` TEXT NULL",
        "ALTER TABLE `users` MODIFY `email` VARCHAR(190) DEFAULT NULL",
    ],
];
