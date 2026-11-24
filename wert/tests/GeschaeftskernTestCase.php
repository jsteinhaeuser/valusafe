<?php

namespace Tests;

use Database;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Gemeinsame Testdaten fuer den Geschaeftskern: Gegenstaende, Rechte, Werte,
 * Raeume, Exporte, Loeschen.
 *
 * Die Tabellen entstehen wie in DsgvoHelpersTest aus install/schema.sql
 * (nur Tabellen- und Spaltennamen, in SQLite). Eine Abfrage mit falscher
 * Spalte bricht hier ab wie auf dem Server.
 *
 * Unterschied zu DsgvoHelpersTest: Textspalten vergleichen wie MySQL mit
 * utf8mb4_unicode_ci - ohne Ruecksicht auf Gross-/Kleinschreibung und
 * Leerzeichen am Ende (Kollation VSMYSQL unten). Sonst fiele "Lea " in der
 * Liste von "lea" heraus, auf dem Server aber nicht; darfGegenstand() rechnet
 * bewusst genauso. Akzente (e/é) bleiben hier verschieden, in MySQL nicht.
 *
 * Wichtig: userSeesOnlyOwnItems() merkt sich das Ergebnis je Benutzername
 * fuer den ganzen Lauf (static). Deshalb ist "nur eigene" in ALLEN Tests an
 * und die Benutzer sind ueberall dieselben - sonst haengt ein Ergebnis von
 * der Reihenfolge der Tests ab. Die Einstellung selbst prueft
 * _userSeesOnlyOwnItemsAbfrage() ohne Zwischenspeicher.
 *
 * Testdaten (Ersteller ist Freitext, wertsachen.erstellt_von):
 *
 *   id  Name       Kategorie   Raum         Ersteller  Preis  Zeitwert  verborgen
 *   1   Ring       Schmuck     Wohnzimmer   lea         500     650
 *   2   Uhr        Schmuck     Wohnzimmer   admin      1200     NULL
 *   3   Laptop     Elektronik  Buero        "Lea "      900     400
 *   4   Kamera     Elektronik  Buero        edith       300       0
 *   5   Fernseher  Elektronik  Keller       edith       800     500      ja
 *   6   Kette      Schmuck     -            lea           0     NULL
 *   7   Bild       -           Keller       NULL        150     NULL
 */
abstract class GeschaeftskernTestCase extends TestCase
{
    protected PDO $pdo;
    protected Database $db;
    private string|false $altesFehlerprotokoll = false;

    protected function setUp(): void
    {
        // Database::select() schreibt Fehler per error_log(); in den Tests
        // gewollt (kaputte Tabelle), soll aber nicht die Ausgabe fuellen.
        $this->altesFehlerprotokoll = ini_set('error_log', sys_get_temp_dir() . '/valusafe-tests-' . getmypid() . '.log');
        $this->pdo = self::neueDatenbank();
        $this->db  = new Database($this->pdo);
        $GLOBALS['pdo'] = $this->pdo;
        $GLOBALS['db']  = $this->db;
        // Zwischenspeicher der Rechte (rechte_helpers.php) je Test leeren
        $GLOBALS['_permissionCache'] = null;
        $_SESSION = [];

        $this->pdo->exec("INSERT INTO app_settings (setting_key, setting_value) VALUES ('only_own_items', '1')");
        $this->pdo->exec("INSERT INTO users (id, username, role, sieht_alle) VALUES
                          (1, 'admin', 'admin', 0),
                          (2, 'edith', 'edit',  0),
                          (3, 'lea',   'read',  0),
                          (4, 'sven',  'read',  1)");
        $this->pdo->exec("INSERT INTO raeume (id, name) VALUES (1, 'Wohnzimmer'), (2, 'Büro'), (3, 'Keller')");
        // positionen.raum_id zeigt auf standorte (nicht auf raeume), siehe helpers.php
        $this->pdo->exec("INSERT INTO standorte (id, name) VALUES (1, 'Erdgeschoss'), (2, 'Obergeschoss')");
        $this->pdo->exec("INSERT INTO positionen (id, raum_id, name) VALUES (1, 1, 'Vitrine'), (2, 2, 'Schrank oben')");
        $this->pdo->exec("INSERT INTO kategorien (id, name) VALUES (1, 'Schmuck'), (2, 'Elektronik'), (3, 'Möbel')");
        $this->pdo->exec("INSERT INTO wertsachen
            (id, name, kategorie_id, raum_id, standort_id, position_id, erstellt_von, preis, aktueller_wert, hidden, kaufdatum, gelistet_am, erstellt_am, notizen, bild)
            VALUES
            (1, 'Ring',      1,    1,    NULL, 1,    'lea',   500,  650,  0, '2025-03-01', '2025-03-02', '2025-03-02 10:15:00', 'Erbstück', NULL),
            (2, 'Uhr',       1,    1,    NULL, NULL, 'admin', 1200, NULL, 0, NULL,         NULL,         NULL,                  NULL,       'uhr.jpg'),
            (3, 'Laptop',    2,    2,    2,    NULL, 'Lea ',  900,  400,  0, NULL,         NULL,         NULL,                  NULL,       NULL),
            (4, 'Kamera',    2,    2,    NULL, NULL, 'edith', 300,  0,    0, NULL,         NULL,         NULL,                  NULL,       NULL),
            (5, 'Fernseher', 2,    3,    NULL, NULL, 'edith', 800,  500,  1, NULL,         NULL,         NULL,                  NULL,       NULL),
            (6, 'Kette',     1,    NULL, NULL, NULL, 'lea',   0,    NULL, 0, NULL,         NULL,         NULL,                  NULL,       NULL),
            (7, 'Bild',      NULL, 3,    NULL, NULL, NULL,    150,  NULL, 0, NULL,         NULL,         NULL,                  NULL,       NULL)");
        $this->pdo->exec("INSERT INTO item_images (id, item_id, filename, sort_order, is_primary) VALUES
                          (1, 1, 'ring_1.jpg', 0, 1), (2, 1, 'ring_2.jpg', 1, 0), (3, 2, 'uhr_1.jpg', 0, 1)");
        $this->pdo->exec("INSERT INTO wert_historie (id, wertsache_id, wert, datum) VALUES
                          (1, 1, 600, '2025-06-01'), (2, 1, 650, '2026-01-01'), (3, 2, 1100, '2026-01-01')");
    }

    protected function tearDown(): void
    {
        if ($this->altesFehlerprotokoll !== false) {
            ini_set('error_log', $this->altesFehlerprotokoll);
        }
        $_SESSION = [];
        $GLOBALS['_permissionCache'] = null;
    }

    /** Meldet einen Benutzer der Testdaten an (Sitzung wie nach login.php). */
    protected function anmelden(string $username, ?string $rolle = null): void
    {
        $zeile = $this->pdo->query("SELECT id, role FROM users WHERE username = " . $this->pdo->quote($username))->fetch();
        $_SESSION = [
            'user_id'    => (int)($zeile['id'] ?? 99),
            'username'   => $username,
            'role'       => $rolle ?? ($zeile['role'] ?? 'read'),
            'login_time' => time(),
        ];
        $GLOBALS['_permissionCache'] = null;
    }

    protected function zahl(string $sql): int
    {
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /** Spalte 'name' der Zeilen, in der gelieferten Reihenfolge. */
    protected static function namen(array $zeilen): array
    {
        return array_column($zeilen, 'name');
    }

    /** SQLite im Speicher mit allen Tabellen aus install/schema.sql. */
    protected static function neueDatenbank(): PDO
    {
        $vergleich = static fn($a, $b) => strcmp(mb_strtolower(rtrim((string)$a)), mb_strtolower(rtrim((string)$b)));
        if (class_exists('Pdo\\Sqlite')) {
            // PHP 8.4+: sqliteCreateCollation() ist dort veraltet
            $pdo = \Pdo\Sqlite::connect('sqlite::memory:');
            $pdo->createCollation('VSMYSQL', $vergleich);
        } else {
            $pdo = new PDO('sqlite::memory:');
            $pdo->sqliteCreateCollation('VSMYSQL', $vergleich);
        }
        // Wie db.php
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        foreach (self::tabellenAusSchema() as $sql) {
            $pdo->exec($sql);
        }
        return $pdo;
    }

    /** CREATE TABLE je Tabelle aus install/schema.sql, nur mit Spaltennamen. */
    protected static function tabellenAusSchema(): array
    {
        preg_match_all('/CREATE TABLE IF NOT EXISTS `(\w+)` \((.*?)\n\) ENGINE/s', self::schema(), $treffer, PREG_SET_ORDER);
        $tabellen = [];
        foreach ($treffer as [, $name, $rumpf]) {
            $spalten = [];
            foreach (explode("\n", $rumpf) as $zeile) {
                if (preg_match('/^\s*`([^`]+)` (\w+)/', $zeile, $s)) {
                    // Zahlenspalten brauchen den Typ (siehe DsgvoHelpersTest),
                    // Textspalten vergleichen wie MySQL (siehe oben).
                    $typ = preg_match('/^(int|tinyint|decimal)$/', $s[2]) ? 'NUMERIC' : 'TEXT COLLATE VSMYSQL';
                    $spalten[] = $s[1] === 'id' ? 'id INTEGER PRIMARY KEY' : '"' . $s[1] . '" ' . $typ;
                }
            }
            $tabellen[] = "CREATE TABLE $name (" . implode(', ', $spalten) . ")";
        }
        return $tabellen;
    }

    /** Die Startdaten der Rechte aus install/schema.sql, in SQLite-Schreibweise. */
    protected static function rechteAusSchema(): string
    {
        if (!preg_match('/INSERT IGNORE INTO `permissions`.*?;\n/s', self::schema(), $t)) {
            throw new \RuntimeException('Startdaten permissions nicht gefunden');
        }
        return str_replace('INSERT IGNORE', 'INSERT OR IGNORE', $t[0]);
    }

    private static function schema(): string
    {
        return file_get_contents(__DIR__ . '/../../install/schema.sql');
    }
}
