<?php

namespace Tests;

/**
 * Hersteller, Modell, Erhaltungszustand (4.8, Migration 016): Formularwerte,
 * Anzeige, Suche, Formularfelder.
 */
class HerstellerModellTest extends GeschaeftskernTestCase
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

    public function testMigrationLegtGenauDieSchemaSpaltenAn(): void
    {
        $m = require __DIR__ . '/../backend/migrations/016_hersteller_modell_zustand.php';
        $this->assertCount(3, $m['statements']);
        $schema = file_get_contents(__DIR__ . '/../../install/schema.sql');
        foreach (['hersteller' => 'varchar(100)', 'modell' => 'varchar(100)', 'erhaltungszustand' => 'varchar(20)'] as $sp => $typ) {
            $this->assertStringContainsString("`$sp` $typ DEFAULT NULL", $schema);
            $this->assertMatchesRegularExpression("/ADD COLUMN `$sp` " . preg_quote($typ, '/') . " DEFAULT NULL/",
                implode("\n", $m['statements']));
        }
        $this->assertTrue(vsMigration016Da());
    }

    public function testFormularwerteWerdenGekuerztUndGeprueft(): void
    {
        $hm = vsHerstellerModellAusPost([
            'hersteller'        => '  Yamaha  ',
            'modell'            => str_repeat('x', 150),
            'erhaltungszustand' => 'kaputt',
        ]);
        $this->assertSame('Yamaha', $hm['hersteller']);
        $this->assertSame(100, mb_strlen($hm['modell']));
        $this->assertNull($hm['erhaltungszustand'], 'unbekannter Zustand wird null');

        $leer = vsHerstellerModellAusPost([]);
        $this->assertSame(['hersteller' => null, 'modell' => null, 'erhaltungszustand' => null], $leer);

        $this->assertSame('gut', vsHerstellerModellAusPost(['erhaltungszustand' => 'gut'])['erhaltungszustand']);
    }

    public function testAnzeige(): void
    {
        $this->assertSame('Yamaha C3X', vsHerstellerModell(['hersteller' => 'Yamaha', 'modell' => 'C3X']));
        $this->assertSame('C3X', vsHerstellerModell(['hersteller' => null, 'modell' => 'C3X']));
        $this->assertSame('', vsHerstellerModell([]));

        $this->assertSame('Gebrauchsspuren', vsErhaltungszustandText('gebraucht'));
        $this->assertSame('', vsErhaltungszustandText(null));
        $this->assertSame('', vsErhaltungszustandText('<script>'));

        $GLOBALS['translations'] = require __DIR__ . '/../lang/en.php';
        $this->assertSame('Signs of use', vsErhaltungszustandText('gebraucht'));
    }

    public function testSucheFindetHerstellerModellUndSeriennummer(): void
    {
        $this->pdo->exec("UPDATE wertsachen SET hersteller = 'Yamaha', modell = 'C3X' WHERE id = 2");
        $this->pdo->exec("UPDATE wertsachen SET seriennummer = 'SN-4711' WHERE id = 4");

        $finde = function (string $begriff): array {
            [$sql, $params] = vsSuchBedingung($begriff);
            return array_column($this->db->select("SELECT w.name FROM wertsachen w WHERE $sql ORDER BY w.id", $params), 'name');
        };
        $this->assertSame(['Uhr'], $finde('yamaha'));
        $this->assertSame(['Uhr'], $finde('C3X'));
        $this->assertSame(['Kamera'], $finde('4711'));
        $this->assertSame(['Ring'], $finde('Erbst'), 'Notizen weiter durchsucht');
        $this->assertSame(['Laptop'], $finde('Lapt'));
    }

    public function testFormularfelderMaskiertUndVorausgewaehlt(): void
    {
        $html = vsHerstellerModellFelder([
            'hersteller'        => '"><script>x</script>',
            'modell'            => 'C3X',
            'erhaltungszustand' => 'sehr_gut',
        ]);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);
        $this->assertStringContainsString('value="sehr_gut" selected', $html);
        $this->assertSame(1, substr_count($html, ' selected'));
        $this->assertStringContainsString('<label for="erhaltungszustand">', $html);
        $this->assertSame(6, substr_count($html, '<option'), 'keine Angabe + 5 Stufen');
    }

    public function testSpaltenStehenZurAuswahl(): void
    {
        $spalten = getAvailableSpalten();
        $this->assertSame('Hersteller / Modell', $spalten['hersteller_modell']['label']);
        $this->assertSame('Zustand', $spalten['erhaltungszustand']['label']);
    }
}
