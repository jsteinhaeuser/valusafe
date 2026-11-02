<?php
/**
 * helpers_sicherung.php — SQL-Sicherung der Datenbank als Funktion
 *
 * Herausgeloest aus backend/backup.php (manuelle Sicherung), als das
 * Update (backend/update.php, 4.7) dieselbe Sicherung vor dem Austausch
 * brauchte. Format unveraendert: Kopfzeilen mit Versionszeile, alle
 * BASE TABLEs alphabetisch, DROP + CREATE + INSERT. Die Dateien heissen
 * database_<zeit>.sql und sind damit in restore.php und der
 * Datenuebernahme waehlbar.
 *
 * cron_backup.php hat noch eine eigene, gleichwertige Schleife.
 */

if (!function_exists('vsDatenbankSichern')) {
    /**
     * Schreibt die Sicherung nach $datei. $log bekommt (Text, istFehler) fuer
     * jede Tabelle, die nicht gesichert werden konnte. Gibt die Zahl der
     * Tabellen zurueck; wirft, wenn die Datei nicht entsteht.
     */
    function vsDatenbankSichern(PDO $pdo, string $datei, ?callable $log = null): int
    {
        $f = fopen($datei, 'x');
        if (!$f) throw new RuntimeException('Kann Datei nicht erstellen: ' . basename($datei));
        // Versionszeile: die Datenuebernahme (datenuebernahme.php) zeigt
        // sie in der Vorschau an. Entscheidend fuer die Pruefung ist aber
        // schema_migrations - Sicherungen bis 4.4.1 haben die Zeile nicht.
        $vsVersion = json_decode((string)@file_get_contents(__DIR__ . '/wert-update/version.json'), true)['version'] ?? '';
        fwrite($f, "-- Backup " . date('Y-m-d H:i:s') . "\n"
            . ($vsVersion !== '' ? "-- ValuSafe-Version: " . preg_replace('/[^0-9A-Za-z.\-]/', '', (string)$vsVersion) . "\n" : '')
            . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
        // Alle Tabellen der Datenbank statt einer gepflegten Liste - bis zum
        // 06.09.2026 fehlte so die Haelfte (Changelog 4.3.19).
        $tables = [];
        foreach ($pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'") as $zeile) {
            $tables[] = array_values($zeile)[0];
        }
        sort($tables);
        foreach ($tables as $table) {
            try {
                $row = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
                fwrite($f, "DROP TABLE IF EXISTS `$table`;\n" . $row['Create Table'] . ";\n\n");
                $rows2 = $pdo->query("SELECT * FROM `$table`");
                $cnt = 0;
                foreach ($rows2 as $row2) {
                    if ($cnt == 0) fwrite($f, "INSERT INTO `$table` (`" . implode('`,`', array_keys($row2)) . "`) VALUES\n");
                    $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($row2));
                    fwrite($f, ($cnt > 0 ? ',' : '') . "(" . implode(',', $vals) . ")\n");
                    $cnt++;
                }
                if ($cnt > 0) fwrite($f, ";\n\n");
            } catch (Exception $e) {
                if ($log) $log("Tabelle $table: " . $e->getMessage(), true);
            }
        }
        fwrite($f, "SET FOREIGN_KEY_CHECKS=1;\n");
        if (!fclose($f) || !is_file($datei)) throw new RuntimeException('Sicherung liess sich nicht schreiben.');
        return count($tables);
    }
}
