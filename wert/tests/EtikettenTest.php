<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../helpers_etiketten.php';

/**
 * QR-Etiketten auf A4-Boegen (4.8): Formate, Verteilung auf Seiten mit
 * Startfeld, Lage der Felder auf dem Bogen.
 */
class EtikettenTest extends TestCase
{
    public function testFormatePassenAufA4(): void
    {
        foreach (vsEtikettFormate() as $schluessel => $f) {
            $this->assertEqualsWithDelta(210, $f['spalten'] * $f['breite'], 0.01, "$schluessel: Breite");
            $this->assertLessThanOrEqual(297, $f['zeilen'] * $f['hoehe'], "$schluessel: Hoehe");
        }
        $this->assertSame(24, vsEtikettFormat('70x37')['spalten'] * vsEtikettFormat('70x37')['zeilen']);
        $this->assertNull(vsEtikettFormat(''));
        $this->assertNull(vsEtikettFormat('../x'));
        $this->assertNull(vsEtikettFormat(null));
    }

    public function testVerteilungMitStartfeld(): void
    {
        $f = vsEtikettFormat('105x48'); // 12 pro Bogen
        $etiketten = range(1, 14);

        $seiten = vsEtikettSeiten($etiketten, $f);
        $this->assertCount(2, $seiten);
        $this->assertSame(range(1, 12), $seiten[0]);
        $this->assertSame([13, 14], array_slice($seiten[1], 0, 2));
        $this->assertCount(12, $seiten[1], 'letzte Seite aufgefuellt');
        $this->assertNull($seiten[1][2]);

        // Angebrochener Bogen: die ersten 4 Felder sind verbraucht
        $seiten = vsEtikettSeiten($etiketten, $f, 5);
        $this->assertSame([null, null, null, null, 1], array_slice($seiten[0], 0, 5));
        $this->assertSame(8, $seiten[0][11]);
        $this->assertSame([9, 10, 11, 12, 13, 14], array_slice($seiten[1], 0, 6));

        // Startfeld ausserhalb des Bogens wird begrenzt
        $this->assertSame(1, vsEtikettSeiten([1], $f, 0)[0][0]);
        $this->assertSame(1, vsEtikettSeiten([1], $f, 99)[0][11]);

        $this->assertSame([], vsEtikettSeiten([], $f, 3), 'nichts zu drucken');
    }

    public function testPositionAufDemBogen(): void
    {
        $f = vsEtikettFormat('70x37'); // 3 x 8, oben (297 - 296) / 2 = 0,5 mm
        $this->assertSame(['links' => 0.0, 'oben' => 0.5], array_map('floatval', vsEtikettPosition(0, $f)));
        $this->assertSame(['links' => 140.0, 'oben' => 0.5], array_map('floatval', vsEtikettPosition(2, $f)));
        $this->assertSame(['links' => 70.0, 'oben' => 37.5], array_map('floatval', vsEtikettPosition(4, $f)));
        $this->assertSame(['links' => 140.0, 'oben' => 259.5], array_map('floatval', vsEtikettPosition(23, $f)));
    }
}
