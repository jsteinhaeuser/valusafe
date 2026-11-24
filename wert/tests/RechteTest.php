<?php

namespace Tests;

/**
 * Rollen und Rechte (rechte_helpers.php, bis 4.6 in db.php).
 *
 * Grundlage sind die 35 Startzeilen der Tabelle permissions aus
 * install/schema.sql. Bis 4.3.32 pruefte kein Export sein Recht und
 * "Mehrere loeschen" lief nur ueber canEdit() - geprueft wird deshalb die
 * ganze Matrix, die Vorgaben ohne Tabellenzeile und jeder Schluessel, den
 * der Code abfragt.
 */
class RechteTest extends GeschaeftskernTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo->exec(self::rechteAusSchema());
    }

    private function rechtFuer(string $rolle, string $schluessel): bool
    {
        $_SESSION['role'] = $rolle;
        return hasPermission($schluessel);
    }

    public function testStartdatenWurdenGelesen(): void
    {
        // Schutz gegen einen stillen Leerlauf. Der Kommentar in schema.sql
        // nennt noch 32 - dazu kamen settings_delete_account (4.3.33) und
        // claims_view/claims_manage (4.4). Siehe TESTFUNDE.md, HINWEIS-04.
        $this->assertSame(35, $this->zahl("SELECT COUNT(*) FROM permissions"));
    }

    public function testAdminDarfImmerAlles(): void
    {
        $this->anmelden('admin');
        $this->pdo->exec("UPDATE permissions SET admin_can = 0");

        $this->assertTrue(hasPermission('items_delete'));
        $this->assertTrue(hasPermission('admin_permissions'));
        $this->assertTrue(hasPermission('gibt_es_nicht'));
        $this->assertTrue(canEdit());
        $this->assertTrue(canDelete());
        $this->assertTrue(isAdmin());
    }

    public function testEditorUndLeserFolgenDerTabelle(): void
    {
        $this->anmelden('edith');
        $editor = ['items_add' => true, 'items_edit' => true, 'items_delete' => true, 'items_value' => true,
                   'bulk_delete' => true, 'export_csv' => true, 'claims_manage' => true,
                   'admin_users' => false, 'admin_backup' => false, 'admin_permissions' => false];
        foreach ($editor as $recht => $erwartet) {
            $this->assertSame($erwartet, hasPermission($recht), "edit: $recht");
        }
        $this->assertTrue(canEdit());
        $this->assertTrue(canDelete());

        $this->anmelden('lea');
        $leser = ['items_add' => false, 'items_edit' => false, 'items_delete' => false, 'items_value' => false,
                  'bulk_delete' => false, 'bulk_hide' => false, 'media_delete' => false,
                  'export_csv' => true, 'export_pdf' => true, 'export_insurance' => true,
                  'claims_view' => true, 'claims_manage' => false, 'admin_users' => false];
        foreach ($leser as $recht => $erwartet) {
            $this->assertSame($erwartet, hasPermission($recht), "read: $recht");
        }
        $this->assertFalse(canEdit());
        $this->assertFalse(canDelete());
        $this->assertFalse(isAdmin());
    }

    public function testGeaenderteZeileWirktNachNeuemLaden(): void
    {
        $this->anmelden('lea');
        $this->assertTrue(hasPermission('export_csv'));

        $this->pdo->exec("UPDATE permissions SET read_can = 0 WHERE action_key = 'export_csv'");
        // Innerhalb einer Anfrage gilt der Zwischenspeicher ...
        $this->assertTrue(hasPermission('export_csv'));
        // ... bei der naechsten Anfrage die Tabelle
        $GLOBALS['_permissionCache'] = null;
        $this->assertFalse(hasPermission('export_csv'));
        $this->assertTrue($this->rechtFuer('edit', 'export_csv'));
    }

    public function testRollennamenDesEditors(): void
    {
        $this->pdo->exec("UPDATE permissions SET edit_can = 1, read_can = 0 WHERE action_key = 'export_html'");
        foreach (['edit', 'editor', 'editieren'] as $rolle) {
            $this->assertTrue($this->rechtFuer($rolle, 'export_html'), $rolle);
        }
        // Alles andere zaehlt als Leser, auch eine fehlende Rolle
        $this->assertFalse($this->rechtFuer('read', 'export_html'));
        $this->assertFalse($this->rechtFuer('gast', 'export_html'));
        unset($_SESSION['role']);
        $this->assertFalse(hasPermission('export_html'));
    }

    public function testFehlendeZeileNimmtVorgabe(): void
    {
        $this->pdo->exec("DELETE FROM permissions WHERE action_key IN ('items_value', 'claims_view')");

        $this->assertTrue($this->rechtFuer('edit', 'items_value'));
        $this->assertFalse($this->rechtFuer('read', 'items_value'));
        $this->assertTrue($this->rechtFuer('read', 'claims_view'));

        // Leere Tabelle (Migration nie gelaufen): alles aus den Vorgaben
        $this->pdo->exec("DELETE FROM permissions");
        $GLOBALS['_permissionCache'] = null;
        $this->assertTrue($this->rechtFuer('read', 'export_csv'));
        $this->assertFalse($this->rechtFuer('read', 'items_edit'));
        $this->assertTrue($this->rechtFuer('edit', 'items_edit'));

        // Tabelle fehlt ganz: ebenso
        $this->pdo->exec("DROP TABLE permissions");
        $GLOBALS['_permissionCache'] = null;
        $this->assertTrue($this->rechtFuer('edit', 'items_add'));
        $this->assertFalse($this->rechtFuer('read', 'items_add'));
    }

    public function testUnbekannterSchluesselIstVerboten(): void
    {
        foreach (['edit', 'read'] as $rolle) {
            $this->assertFalse($this->rechtFuer($rolle, 'gibt_es_nicht'), $rolle);
            $this->assertFalse(_permissionFallback('gibt_es_nicht', $rolle), $rolle);
            $this->assertFalse($this->rechtFuer($rolle, ''), $rolle);
        }
        // Die Admin-Rechte haben keine Vorgabe: ohne Zeile verboten
        $this->pdo->exec("DELETE FROM permissions WHERE action_key = 'admin_backup'");
        $this->assertFalse($this->rechtFuer('edit', 'admin_backup'));
    }

    public function testAusgeblendeteRechteSindNichtMehrSchaltbar(): void
    {
        // OWASP-A01-006 (4.6): Die Rechteverwaltung zeigt sie nicht mehr an,
        // ajax_permissions.php lehnt Aenderungen ab.
        $this->assertSame(['items_view', 'items_search'], VS_RECHTE_AUSGEBLENDET);

        // Die Vorgaben erlauben beide jeder Rolle ...
        foreach (VS_RECHTE_AUSGEBLENDET as $recht) {
            $this->assertTrue(_permissionFallback($recht, 'edit'), $recht);
            $this->assertTrue(_permissionFallback($recht, 'read'), $recht);
        }
        // ... und keine Seite fragt sie ab - sonst wuerde eine vor 4.6
        // abgeschaltete Zeile wieder wirken, ohne dass man sie sieht.
        $treffer = [];
        foreach (self::abgefragteRechte() as [$datei, $recht]) {
            if (in_array($recht, VS_RECHTE_AUSGEBLENDET, true)) {
                $treffer[] = "$datei: $recht";
            }
        }
        $this->assertSame([], $treffer);

        // ajax_permissions.php prueft die Liste tatsaechlich
        $this->assertStringContainsString('in_array($action_key, VS_RECHTE_AUSGEBLENDET, true)',
            file_get_contents(__DIR__ . '/../ajax_permissions.php'));
    }

    public function testRollenwechselZaehltDieGewechselteRolle(): void
    {
        // switch_role.php: Admin testet als Leser
        $this->anmelden('admin');
        $_SESSION['original_role'] = 'admin';
        $_SESSION['role'] = 'read';

        $this->assertFalse(isAdmin());
        $this->assertTrue(isRealAdmin());
        $this->assertFalse(hasPermission('items_edit'));
        $this->assertFalse(canDelete());
        $this->assertTrue(hasPermission('export_csv'));
    }

    public function testJederAbgefragteSchluesselIstBekannt(): void
    {
        // Ein Tippfehler in hasPermission('...') sperrt Editor und Leser
        // still aus. Jeder Schluessel im Code muss in schema.sql oder in den
        // Vorgaben stehen. Bis 4.6 fragte insurance.php 'admin' ab
        // (TESTFUNDE.md, HINWEIS-03), jetzt isAdmin().
        $bekannt = array_column($this->pdo->query("SELECT action_key FROM permissions")->fetchAll(), 'action_key');
        $unbekannt = [];
        foreach (self::abgefragteRechte() as [$datei, $recht]) {
            if (!in_array($recht, $bekannt, true) && !_permissionFallback($recht, 'edit') && !_permissionFallback($recht, 'read')) {
                $unbekannt[] = "$datei: $recht";
            }
        }
        $this->assertSame([], $unbekannt);
        $this->assertGreaterThan(20, count(self::abgefragteRechte()), 'Suche im Code hat nichts gefunden');
    }

    /**
     * @group fund
     * HINWEIS-02: Ohne Tabellenzeile sind "Gegenstand loeschen" und "Mehrere
     * loeschen" fuer den Editor verboten, mit den Startzeilen aus schema.sql
     * erlaubt. Alle anderen Vorgaben stimmen mit schema.sql ueberein. Der
     * Test haelt den Stand fest; aendert sich eine Seite, faellt er auf.
     */
    public function testVorgabenWeichenNurBeimLoeschenVonSchemaAb(): void
    {
        $abweichungen = [];
        foreach ($this->pdo->query("SELECT action_key, edit_can, read_can FROM permissions")->fetchAll() as $z) {
            foreach (['edit' => 'edit_can', 'read' => 'read_can'] as $rolle => $spalte) {
                if (_permissionFallback($z['action_key'], $rolle) !== (bool)$z[$spalte]) {
                    $abweichungen[] = $z['action_key'] . '/' . $rolle;
                }
            }
        }
        sort($abweichungen);
        $this->assertSame(['bulk_delete/edit', 'items_delete/edit'], $abweichungen);
    }

    /**
     * Alle Aufrufe hasPermission('x') / requirePermission('x') in der
     * Anwendung (ohne vendor und tests), als [Datei, Schluessel].
     */
    private static function abgefragteRechte(): array
    {
        $wurzel = realpath(__DIR__ . '/..');
        $ergebnis = [];
        $dateien = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($wurzel, \FilesystemIterator::SKIP_DOTS));
        foreach ($dateien as $datei) {
            $pfad = substr($datei->getPathname(), strlen($wurzel) + 1);
            if ($datei->getExtension() !== 'php' || preg_match('#^(vendor|tests|ValuSafe_Pakete)/#', $pfad)) {
                continue;
            }
            preg_match_all("/(?:hasPermission|requirePermission)\(\s*'([a-z_]+)'/", file_get_contents($datei->getPathname()), $t);
            foreach ($t[1] as $recht) {
                $ergebnis[] = [str_replace('\\', '/', $pfad), $recht];
            }
        }
        return $ergebnis;
    }
}
