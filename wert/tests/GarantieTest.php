<?php

namespace Tests;

/**
 * Garantieende (4.8, Migration 017): Formularwert, Status, Anzeige,
 * Listenfilter, Formularfeld, Spalte.
 */
class GarantieTest extends GeschaeftskernTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['translations'] = require __DIR__ . '/../lang/de.php';
        $this->anmelden('admin');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['translations']);
        parent::tearDown();
    }

    public function testMigrationLegtGenauDieSchemaSpalteAn(): void
    {
        $m = require __DIR__ . '/../backend/migrations/017_garantie_bis.php';
        $this->assertSame(["ALTER TABLE `wertsachen` ADD COLUMN `garantie_bis` date DEFAULT NULL"], $m['statements']);
        $schema = file_get_contents(__DIR__ . '/../../install/schema.sql');
        $this->assertStringContainsString('`garantie_bis` date DEFAULT NULL', $schema);
        $this->assertTrue(vsMigration017Da());
    }

    public function testFormularwertNurAlsGueltigesDatum(): void
    {
        $this->assertSame('2027-03-01', vsGarantieAusPost(['garantie_bis' => ' 2027-03-01 ']));
        $this->assertNull(vsGarantieAusPost(['garantie_bis' => '2027-02-30']), 'kein 30. Februar');
        $this->assertNull(vsGarantieAusPost(['garantie_bis' => '01.03.2027']));
        $this->assertNull(vsGarantieAusPost(['garantie_bis' => "2027-03-01' OR 1=1"]));
        $this->assertNull(vsGarantieAusPost([]));
    }

    public function testStatusUndAnzeige(): void
    {
        $heute = '2026-11-05';
        $this->assertSame('', vsGarantieStatus(null, $heute));
        $this->assertSame('', vsGarantieStatus('0000-00-00', $heute));
        $this->assertSame('abgelaufen', vsGarantieStatus('2026-11-04', $heute));
        $this->assertSame('bald', vsGarantieStatus('2026-11-05', $heute), 'heute zaehlt noch');
        $this->assertSame('bald', vsGarantieStatus('2026-12-05', $heute), '30. Tag zaehlt noch');
        $this->assertSame('laeuft', vsGarantieStatus('2026-12-06', $heute));

        $this->assertSame('05.12.2026 (läuft bald ab)', vsGarantieText('2026-12-05', $heute));
        $this->assertSame('04.11.2026 (abgelaufen)', vsGarantieText('2026-11-04', $heute));
        $this->assertSame('01.03.2028', vsGarantieText('2028-03-01', $heute));
        $this->assertSame('', vsGarantieText(null, $heute));

        $GLOBALS['translations'] = require __DIR__ . '/../lang/en.php';
        $this->assertStringEndsWith('(expired)', vsGarantieText('2026-11-04', $heute));
    }

    public function testListenfilterNurDieNaechsten30Tage(): void
    {
        $heute = date('Y-m-d');
        $tag = fn(int $n) => date('Y-m-d', strtotime("$heute $n days"));
        $this->pdo->exec("UPDATE wertsachen SET garantie_bis = '" . $tag(10) . "' WHERE id = 1"); // Ring: bald
        $this->pdo->exec("UPDATE wertsachen SET garantie_bis = '" . $tag(-1) . "' WHERE id = 2"); // Uhr: abgelaufen
        $this->pdo->exec("UPDATE wertsachen SET garantie_bis = '" . $tag(31) . "' WHERE id = 3"); // Laptop: spaeter
        $this->pdo->exec("UPDATE wertsachen SET garantie_bis = '" . $heute . "' WHERE id = 4");   // Kamera: heute

        [$sql, $params] = vsGarantieBaldBedingung('w');
        $namen = array_column($this->db->select(
            "SELECT w.name FROM wertsachen w WHERE $sql ORDER BY w.id", $params), 'name');
        $this->assertSame(['Ring', 'Kamera'], $namen);

        $filter = ['search' => '', 'kategorie' => [], 'ort' => false, 'price_min' => false, 'price_max' => false,
                   'date_from' => '', 'date_to' => '', 'has_image' => false, 'has_docs' => false,
                   'warranty_soon' => true];
        $this->assertTrue(hasActiveFilters($filter));
        $p = [];
        $bedingungen = buildSearchQuery($filter, $p);
        $namen = array_column($this->db->select(
            "SELECT w.name FROM wertsachen w WHERE " . implode(' AND ', $bedingungen) . " ORDER BY w.id", $p), 'name');
        $this->assertSame(['Ring', 'Kamera'], $namen);
    }

    public function testFormularfeldMaskiertMitKnopf(): void
    {
        $html = vsGarantieFeld('2027-03-01');
        $this->assertStringContainsString('type="date" id="garantie_bis" name="garantie_bis" value="2027-03-01"', $html);
        $this->assertStringContainsString('2 Jahre ab Kaufdatum', $html);
        $this->assertStringContainsString('type="button"', $html, 'Knopf sendet das Formular nicht ab');

        $boese = vsGarantieFeld('"><script>x</script>');
        $this->assertStringNotContainsString('<script>', $boese);
    }

    public function testSpalteStehtZurAuswahl(): void
    {
        $this->assertSame('Garantie bis', getAvailableSpalten()['garantie_bis']['label']);
    }
}
