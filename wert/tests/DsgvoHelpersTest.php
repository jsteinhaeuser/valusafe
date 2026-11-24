<?php

namespace Tests;

use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Datenauskunft und Kontoloeschung (dsgvo_helpers.php).
 *
 * Die Tabellen entstehen aus install/schema.sql — nur Tabellen- und
 * Spaltennamen, in SQLite. Genau daran sind beide Funktionen bis 4.3.29
 * gescheitert: Sie fragten Spalten ab, die es nicht gibt. Nennt eine Abfrage
 * eine falsche Spalte, bricht sie hier ab wie auf dem Server.
 */
class DsgvoHelpersTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        foreach (self::tabellenAusSchema() as $sql) {
            $this->pdo->exec($sql);
        }

        $this->pdo->exec("INSERT INTO users (id, username, email, password, role, avatar, totp_secret, erstellt_am)
                          VALUES (1, 'admin', 'a@example.org', 'HASH-A', 'admin', NULL, NULL, '2026-01-01 10:00:00'),
                                 (2, 'erika', 'e@example.org', 'HASH-E', 'read', 'avatar_2.jpg', 'GEHEIM', '2026-02-01 10:00:00')");
        $this->pdo->exec("INSERT INTO raeume (id, name) VALUES (1, 'Wohnzimmer')");
        $this->pdo->exec("INSERT INTO kategorien (id, name) VALUES (1, 'Schmuck')");
        $this->pdo->exec("INSERT INTO wertsachen (id, name, kategorie_id, raum_id, erstellt_von)
                          VALUES (1, 'Ring', 1, 1, 'erika'), (2, 'Uhr', 1, 1, 'admin')");
        $this->pdo->exec("INSERT INTO activity_log (id, user_id, aktion, tabelle, datensatz_id, bezeichnung, zeitstempel, ip_adresse)
                          VALUES (1, 2, 'created', 'wertsachen', 1, 'Ring', '2026-03-01 10:00:00', '192.0.2.7'),
                                 (2, 1, 'created', 'wertsachen', 2, 'Uhr',  '2026-03-02 10:00:00', '192.0.2.1')");
        $this->pdo->exec("INSERT INTO security_log (id, event_type, user_id, ip_address) VALUES (1, 'login_success', 2, '192.0.2.7')");
        $this->pdo->exec("INSERT INTO password_resets (id, user_id, token_hash, expires_at) VALUES (1, 2, 'x', '2026-12-31 00:00:00')");
        $this->pdo->exec("INSERT INTO user_activity (user_id, username, last_seen) VALUES (2, 'erika', '2026-03-01 10:00:00')");
        $this->pdo->exec("INSERT INTO login_attempts (id, username, ip_address) VALUES (1, 'erika', '192.0.2.7'), (2, 'admin', '192.0.2.1')");
    }

    /** CREATE TABLE je Tabelle aus install/schema.sql, nur mit Spaltennamen. */
    private static function tabellenAusSchema(): array
    {
        $schema = file_get_contents(__DIR__ . '/../../install/schema.sql');
        preg_match_all('/CREATE TABLE IF NOT EXISTS `(\w+)` \((.*?)\n\) ENGINE/s', $schema, $treffer, PREG_SET_ORDER);
        $tabellen = [];
        foreach ($treffer as [, $name, $rumpf]) {
            $spalten = [];
            foreach (explode("\n", $rumpf) as $zeile) {
                if (preg_match('/^\s*`([^`]+)` (\w+)/', $zeile, $s)) {
                    // Zahlenspalten brauchen den Typ: sonst vergleicht SQLite
                    // die gespeicherte 2 nicht mit dem gebundenen "2".
                    $typ = preg_match('/^(int|tinyint|decimal)$/', $s[2]) ? 'NUMERIC' : 'TEXT';
                    $spalten[] = $s[1] === 'id' ? 'id INTEGER PRIMARY KEY' : '"' . $s[1] . '" ' . $typ;
                }
            }
            $tabellen[] = "CREATE TABLE $name (" . implode(', ', $spalten) . ")";
        }
        return $tabellen;
    }

    private function zahl(string $sql): int
    {
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    public function testSchemaWurdeGelesen(): void
    {
        // Schutz gegen einen stillen Leerlauf des Nachbaus.
        // 19 bis 4.3.34; 4.4 (Migration 014) brachte schadensfaelle und
        // schadensfall_gegenstaende.
        $this->assertSame(21, count(self::tabellenAusSchema()));
    }

    // ── Auskunft ────────────────────────────────────────────────────────

    public function testAuskunftEnthaeltKontoGegenstaendeUndAktivitaeten(): void
    {
        $daten = dsgvoDatenSammeln($this->pdo, 2, 'erika');

        $this->assertSame('erika', $daten['benutzer']['username']);
        $this->assertSame('read', $daten['benutzer']['role']);
        $this->assertSame('2026-02-01 10:00:00', $daten['benutzer']['erstellt_am']);

        $this->assertCount(1, $daten['gegenstaende']);
        $this->assertSame('Ring', $daten['gegenstaende'][0]['name']);
        $this->assertSame('Wohnzimmer', $daten['gegenstaende'][0]['raum']);
        $this->assertSame('Schmuck', $daten['gegenstaende'][0]['kategorie']);

        $this->assertCount(1, $daten['aktivitaeten']);
        $this->assertSame('Ring', $daten['aktivitaeten'][0]['bezeichnung']);
    }

    public function testAuskunftEnthaeltKeineZugangsdaten(): void
    {
        $benutzer = dsgvoDatenSammeln($this->pdo, 2, 'erika')['benutzer'];

        $this->assertArrayNotHasKey('password', $benutzer);
        $this->assertArrayNotHasKey('totp_secret', $benutzer);
        $this->assertArrayNotHasKey('totp_backup_codes', $benutzer);
    }

    // ── Loeschung ───────────────────────────────────────────────────────

    public function testLoeschungEntferntKontoUndLoestProtokolle(): void
    {
        $avatar = dsgvoKontoLoeschen($this->pdo, 2);

        $this->assertSame('avatar_2.jpg', $avatar);
        $this->assertSame(0, $this->zahl("SELECT COUNT(*) FROM users WHERE id = 2"));

        // Protokolleintraege bleiben, aber ohne Konto; Aktivitaeten ohne IP.
        $this->assertSame(0, $this->zahl("SELECT COUNT(*) FROM activity_log WHERE user_id = 2"));
        $this->assertSame(1, $this->zahl("SELECT COUNT(*) FROM activity_log WHERE id = 1 AND user_id IS NULL AND ip_adresse IS NULL"));
        $this->assertSame(1, $this->zahl("SELECT COUNT(*) FROM security_log WHERE id = 1 AND user_id IS NULL"));

        $this->assertSame(0, $this->zahl("SELECT COUNT(*) FROM password_resets WHERE user_id = 2"));
        $this->assertSame(0, $this->zahl("SELECT COUNT(*) FROM user_activity WHERE user_id = 2"));
        $this->assertSame(0, $this->zahl("SELECT COUNT(*) FROM login_attempts WHERE username = 'erika'"));

        // Gegenstaende bleiben — so steht es auf der Seite.
        $this->assertSame(1, $this->zahl("SELECT COUNT(*) FROM wertsachen WHERE id = 1"));
    }

    public function testLoeschungLaesstAndereKontenUnberuehrt(): void
    {
        dsgvoKontoLoeschen($this->pdo, 2);

        $this->assertSame(1, $this->zahl("SELECT COUNT(*) FROM users WHERE id = 1"));
        $this->assertSame(1, $this->zahl("SELECT COUNT(*) FROM activity_log WHERE id = 2 AND user_id = 1 AND ip_adresse = '192.0.2.1'"));
        $this->assertSame(1, $this->zahl("SELECT COUNT(*) FROM login_attempts WHERE username = 'admin'"));
    }

    public function testUnbekanntesKontoAendertNichts(): void
    {
        $this->assertNull(dsgvoKontoLoeschen($this->pdo, 99));
        $this->assertSame(2, $this->zahl("SELECT COUNT(*) FROM users"));
    }

    public function testFehlerMittendrinRolltAllesZurueck(): void
    {
        // Ein Schritt nach den ersten Aenderungen schlaegt fehl: dann darf
        // weder das Konto fehlen noch ein Protokolleintrag schon geloest sein.
        $this->pdo->exec("DROP TABLE user_activity");

        try {
            dsgvoKontoLoeschen($this->pdo, 2);
            $this->fail('PDOException erwartet');
        } catch (PDOException $e) {
            // erwartet
        }

        $this->assertSame(1, $this->zahl("SELECT COUNT(*) FROM users WHERE id = 2"));
        $this->assertSame(1, $this->zahl("SELECT COUNT(*) FROM activity_log WHERE id = 1 AND user_id = 2"));
    }
}
