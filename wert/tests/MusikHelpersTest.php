<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../musik_helpers.php';

/**
 * Katalognummer-Suche (4.9.3): Bereinigen, Anfrage, Abgleich, Auswahl,
 * Ergebnis. Ohne Netz - die Eintraege sind gekuerzte echte Antworten von
 * MusicBrainz (22.11.2026).
 */
class MusikHelpersTest extends TestCase
{
    private function eintrag(string $titel, array $labels, string $format = '12" Vinyl',
                             string $datum = '1974', string $status = 'Official'): array
    {
        return [
            'id' => '00000000-0000-0000-0000-000000000000',
            'title' => $titel, 'status' => $status, 'date' => $datum, 'country' => 'DE',
            'artist-credit' => [['name' => 'Udo Lindenberg', 'joinphrase' => ' & '], ['name' => 'Das Panikorchester']],
            'label-info' => array_map(fn($l) => ['catalog-number' => $l[1], 'label' => ['name' => $l[0]]], $labels),
            'media' => $format === '' ? [] : [['format' => $format]],
        ];
    }

    public function testBereinigenLaesstNurKatalognummernDurch(): void
    {
        $this->assertSame('SHVL 804', vsKatNrBereinigen('  SHVL   804 '));
        $this->assertSame('6.21081 AS', vsKatNrBereinigen('6.21081 AS'));
        $this->assertSame('1C 062-04/175', vsKatNrBereinigen('1C 062-04/175'));
        $this->assertNull(vsKatNrBereinigen('Abbey Road'), 'ohne Ziffer waere es eine Titelsuche');
        $this->assertNull(vsKatNrBereinigen('7'), 'zu kurz');
        $this->assertNull(vsKatNrBereinigen(str_repeat('1', 31)), 'zu lang');
        $this->assertNull(vsKatNrBereinigen('SHVL "804"'), 'Anfuehrungszeichen wuerden die Anfrage aufbrechen');
        $this->assertNull(vsKatNrBereinigen('804) OR artist:(x'), 'keine Lucene-Syntax');
        $this->assertNull(vsKatNrBereinigen('80\\4'));
    }

    public function testAnfrageFragtDreiSchreibweisenAb(): void
    {
        $this->assertSame('catno:"6.21081" OR catno:621081* OR catno:(6 AND 21081)', vsKatNrAbfrage('6.21081'));
        $this->assertSame('catno:"SLE 14790" OR catno:sle14790* OR catno:(SLE AND 14790)', vsKatNrAbfrage('SLE 14790'));
        $this->assertSame('catno:"PCS7088" OR catno:pcs7088*', vsKatNrAbfrage('PCS7088'), 'ein Wort: kein UND-Teil');
        $this->assertSame('catno:"A12"', vsKatNrAbfrage('A12'), 'zu kurz fuer einen Platzhalter');
    }

    public function testPasstGleichOderMitZusatz(): void
    {
        $this->assertTrue(vsKatNrPasst('SHVL804', 'SHVL 804'));
        $this->assertTrue(vsKatNrPasst('6.21138', '6.21 138'));
        $this->assertTrue(vsKatNrPasst('6.21081', '6.21081 AS'), 'Zusatz AS fehlt in der Eingabe');
        $this->assertTrue(vsKatNrPasst('sle 14790', 'SLE 14790-P'));
        $this->assertFalse(vsKatNrPasst('6.21081', '6.21082 AS'));
        $this->assertFalse(vsKatNrPasst('6.21081 AS', '6.21081'), 'Eingabe genauer als die Platte');
        $this->assertFalse(vsKatNrPasst('A12', 'A1234'), 'kurze Eingaben nur genau');
        $this->assertFalse(vsKatNrPasst('', 'X'));
    }

    public function testAuswahlNimmtBeiDerKatalognummerDieSchallplatte(): void
    {
        $cd    = $this->eintrag('Ball Pompös', [['Telefunken', 'SLE 14790-P']], 'CD', '1974-05-01');
        $vinyl = $this->eintrag('Ball Pompös', [['Telefunken', 'SLE 14790-P']], '12" Vinyl', '1974');
        $fremd = $this->eintrag('Etwas anderes', [['Ariola', '14790']]);
        $passt = fn($r) => vsKatNrDesEintrags($r, 'SLE 14790') !== null;

        $this->assertSame('12" Vinyl', vsMusikBeste([$cd, $vinyl, $fremd], $passt, true)['media'][0]['format']);
        $this->assertSame('CD', vsMusikBeste([$cd, $vinyl], $passt, false)['media'][0]['format'],
            'ohne Vorrang fuer Vinyl gewinnt bei Gleichstand der erste');
        $this->assertNull(vsMusikBeste([$fremd], $passt, true), 'Treffer der Suche, aber andere Nummer');
        $this->assertNull(vsMusikBeste([['title' => '']], fn() => true));
    }

    public function testErgebnisNimmtLabelUndNummerAusDemselbenEintrag(): void
    {
        // Dark Side 2011: erst EMI ohne Nummer, dann Harvest mit SHVL 804
        $r = $this->eintrag('The Dark Side of the Moon', [['EMI', null], ['Harvest', 'SHVL 804']], '12" Vinyl', '2011-11-27');
        $e = vsMusikErgebnis($r, 'https://coverartarchive.org/x', 'shvl804');

        $this->assertTrue($e['found']);
        $this->assertSame('music', $e['type']);
        $this->assertSame('Udo Lindenberg & Das Panikorchester – The Dark Side of the Moon', $e['name']);
        $this->assertSame('12" Vinyl · 2011 · DE · Harvest · SHVL 804', $e['description']);
        $this->assertSame('2011', $e['year']);
        $this->assertSame('https://coverartarchive.org/x', $e['cover']);
        $this->assertSame('MusicBrainz', $e['source']);
    }

    public function testErgebnisOhneKatalognummerWieBeimBarcode(): void
    {
        $r = $this->eintrag('Lindenberg', [['[no label]', '[none]']], '', '');
        $e = vsMusikErgebnis($r, null);
        $this->assertSame('DE', $e['description'], 'Platzhalter von MusicBrainz nicht in die Notiz');
        $this->assertNull($e['year']);
        $this->assertNull($e['cover']);
    }

    public function testBarcodeLookupNutztDieHelfer(): void
    {
        $src = file_get_contents(__DIR__ . '/../barcode_lookup.php');
        $this->assertStringContainsString("require_once __DIR__ . '/musik_helpers.php'", $src);
        $this->assertStringContainsString('vsKatNrBereinigen((string)$_GET[\'katnr\'])', $src);
        $this->assertStringContainsString("'busy' => true", $src, 'ausgelastet ist nicht dasselbe wie nichts gefunden');
        $this->assertStringContainsString('vsMusikErgebnis($beste', $src);
    }
}
