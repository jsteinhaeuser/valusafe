<?php
/**
 * backend/migration_runner.php
 *
 * Die Ausfuehrungsschleife des Migrationssystems, herausgeloest aus
 * migrate.php. Reine Logik: keine Sitzung, keine Rechtepruefung, keine
 * Ausgabe, kein exit. Sie bekommt eine fertige Datenbankverbindung und gibt
 * einen Bericht zurueck.
 *
 * WARUM AUSGELAGERT
 *
 * Bis 4.3.23 stand diese Schleife zwischen requireAdmin() und einem
 * <style>-Block. Damit war sie nur ueber eine angemeldete Browsersitzung
 * erreichbar und liess sich nicht isoliert pruefen. Beides wurde zum Problem,
 * als elf Instanzen dieselbe Migration brauchten: elfmal anmelden, elfmal
 * zwei Links anklicken.
 *
 * Aufrufer sind jetzt zwei:
 *   migrate.php        wie bisher, mit Admin-Anmeldung und HTML-Ausgabe
 *   migration_agent.php  vom Hub gesteuert, token-geschuetzt, Antwort als JSON
 *
 * Das Verhalten ist unveraendert gegenueber der bisherigen Inline-Fassung —
 * gleiche Reihenfolge, gleiche Statuswerte, gleiche Behandlung von Fehlern.
 */

/**
 * Fehlercodes, die "existiert bereits" bedeuten und deshalb harmlos sind.
 *
 * Sie machen die Migrationen ueber Instanzen mit unterschiedlichem Vorstand
 * hinweg wiederholbar: wo eine Spalte schon da ist, wird die Anweisung
 * uebersprungen statt den Lauf abzubrechen.
 */
const VS_MIGRATION_HARMLOSE_CODES = [
    1050, // Table already exists
    1060, // Duplicate column name
    1061, // Duplicate key name
    1826, // Duplicate foreign key constraint name
    1091, // Can't DROP; check that it exists
];

if (!function_exists('isBenignError')) {
    function isBenignError(PDOException $e, array $benignCodes): bool
    {
        $code = $e->errorInfo[1] ?? 0;
        if (in_array($code, $benignCodes, true)) return true;
        $msg = strtolower($e->getMessage());
        return (strpos($msg, 'duplicate') !== false) || (strpos($msg, 'already exists') !== false);
    }
}

/**
 * Arbeitet alle Migrationsdateien in $ordner ab.
 *
 * @param PDO    $pdo          offene Verbindung zur Instanz-Datenbank
 * @param string $ordner       Verzeichnis mit den Migrationsdateien
 * @param bool   $trockenlauf  true: nichts ausfuehren, nichts vermerken
 *
 * @return array{report: array, counts: array<string,int>, stoppedEarly: bool}
 */
function vsMigrationenAusfuehren(PDO $pdo, string $ordner, bool $trockenlauf): array
{
    // Die Buchfuehrungstabelle selbst wird auch im Trockenlauf angelegt.
    // Ohne sie liesse sich nicht feststellen, was bereits angewendet ist —
    // der Trockenlauf haette dann gar keine Aussage. Sie anzulegen veraendert
    // keine Nutzdaten.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `schema_migrations` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `migration`  VARCHAR(191) NOT NULL,
            `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `note`       VARCHAR(255) NULL,
            UNIQUE KEY `uniq_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $angewendet = array_flip(
        $pdo->query("SELECT migration FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN)
    );

    $dateien = glob(rtrim($ordner, '/') . '/*.php') ?: [];
    sort($dateien, SORT_STRING);

    $report       = [];
    $stoppedEarly = false;

    foreach ($dateien as $datei) {
        $name = basename($datei, '.php');

        if (isset($angewendet[$name])) {
            $report[] = ['name' => $name, 'status' => 'already', 'description' => '', 'statements' => []];
            continue;
        }

        // Nach einem Fehlschlag wird nichts weiter ausgefuehrt: Migrationen
        // bauen aufeinander auf, und die naechste auf einem halben Stand
        // laufen zu lassen macht den Schaden groesser statt kleiner.
        if ($stoppedEarly) {
            $report[] = ['name' => $name, 'status' => 'pending', 'description' => '', 'statements' => []];
            continue;
        }

        $def         = require $datei;
        $description = $def['description'] ?? '';
        $statements  = $def['statements'] ?? [];

        $stmtResults = [];
        $hasFatal    = false;

        foreach ($statements as $sql) {
            if ($trockenlauf) {
                $stmtResults[] = ['sql' => $sql, 'result' => 'dry-run', 'msg' => ''];
                continue;
            }
            try {
                $pdo->exec($sql);
                $stmtResults[] = ['sql' => $sql, 'result' => 'ok', 'msg' => ''];
            } catch (PDOException $e) {
                if (isBenignError($e, VS_MIGRATION_HARMLOSE_CODES)) {
                    $stmtResults[] = ['sql' => $sql, 'result' => 'skip', 'msg' => $e->getMessage()];
                } else {
                    $stmtResults[] = ['sql' => $sql, 'result' => 'error', 'msg' => $e->getMessage()];
                    $hasFatal = true;
                    break;
                }
            }
        }

        if ($hasFatal) {
            $report[] = ['name' => $name, 'status' => 'error', 'description' => $description, 'statements' => $stmtResults];
            $stoppedEarly = true;
            continue;
        }

        $report[] = [
            'name'        => $name,
            'status'      => $trockenlauf ? 'dry-run' : 'applied',
            'description' => $description,
            'statements'  => $stmtResults,
        ];

        // Erst vermerken, wenn wirklich alles durchlief. Eine Migration, die
        // abgebrochen ist, bleibt offen und wird beim naechsten Lauf erneut
        // versucht — siehe 010_permissions_locked_for.php, die genau darauf
        // baut.
        if (!$trockenlauf) {
            $pdo->prepare("INSERT INTO schema_migrations (migration, note) VALUES (?, ?)")
                ->execute([$name, $description]);
        }
    }

    $counts = ['already' => 0, 'applied' => 0, 'dry-run' => 0, 'error' => 0, 'pending' => 0];
    foreach ($report as $r) {
        $counts[$r['status']] = ($counts[$r['status']] ?? 0) + 1;
    }

    return ['report' => $report, 'counts' => $counts, 'stoppedEarly' => $stoppedEarly];
}
