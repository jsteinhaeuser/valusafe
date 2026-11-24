<?php

namespace Tests;

/**
 * Kaufpreis, Zeitwert (aktueller_wert) und Summen.
 *
 * Seit 4.6 gilt EINE Regel (helpers_werte.php, Entscheidung 26.10.2026):
 * Zeitwert = aktueller_wert, wenn groesser als 0, sonst Kaufpreis. Bis dahin
 * zeigte dieselbe Beschriftung "Zeitwert" im Dashboard 1.550 EUR, in der
 * Suchleiste der Liste 2.900 EUR (TESTFUNDE.md, FUND-02); der Trend zog alte
 * Zeitwerte von Kaufpreisen ab (FUND-09); die Suchleiste zaehlte verborgene
 * Gegenstaende mit, die Liste nicht (FUND-03).
 *
 * Alle Werte als Admin (sieht alles). Zeitwerte der Testdaten:
 *   Ring 650, Uhr 1200 (Kaufpreis), Laptop 400, Kamera 300 (Zeitwert 0 ->
 *   Kaufpreis), Fernseher 500 (verborgen), Kette 0, Bild 150
 *   Summe 3200, ohne verborgenen Fernseher 2700
 *   Kaufpreise alle 7: 3850, ohne Fernseher 3050
 */
class WerteTest extends GeschaeftskernTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->anmelden('admin');
    }

    private function leer(): array
    {
        return ['search' => '', 'kategorie' => [], 'ort' => false, 'price_min' => false,
                'price_max' => false, 'date_from' => '', 'date_to' => '',
                'has_image' => false, 'has_docs' => false];
    }

    /** Kopf der Liste wie index.php: [Anzahl, Summe, Durchschnitt, Hoechstwert]. */
    private function listenKopf(int $filterRaum = 0): array
    {
        $abfragen = vsListeAbfragen($this->leer(), false, false, 'admin', $filterRaum, 0);
        $anzahl = (int)$this->db->selectOne(...$abfragen['anzahl'])['total'];
        [$summe, $schnitt, $max] = vsListenStatistik($this->db->select(...$abfragen['statistik']), $anzahl);
        return [$anzahl, $summe, $schnitt, $max];
    }

    public function testZeitwertRegel(): void
    {
        // So kommen die Werte aus MySQL: decimal als Zeichenkette
        $this->assertSame(650.0, vsZeitwert(['aktueller_wert' => '650.00', 'preis' => '500.00']));
        $this->assertSame(300.0, vsZeitwert(['aktueller_wert' => '0.00', 'preis' => '300.00']), '"0.00" ist in PHP wahr');
        $this->assertSame(1200.0, vsZeitwert(['aktueller_wert' => null, 'preis' => '1200.00']));
        $this->assertSame(80.0, vsZeitwert(['aktueller_wert' => '-5.00', 'preis' => '80.00']));
        $this->assertSame(0.0, vsZeitwert(['aktueller_wert' => null, 'preis' => null]));
        $this->assertSame(0.0, vsZeitwert([]));

        $this->assertTrue(vsHatZeitwert(['aktueller_wert' => '650.00']));
        $this->assertFalse(vsHatZeitwert(['aktueller_wert' => '0.00']));
        $this->assertFalse(vsHatZeitwert(['aktueller_wert' => null]));

        $this->assertSame('(CASE WHEN w.aktueller_wert > 0 THEN w.aktueller_wert ELSE COALESCE(w.preis, 0) END)', vsZeitwertSql('w'));
    }

    public function testSqlUndPhpRechnenGleich(): void
    {
        $this->pdo->exec("INSERT INTO wertsachen (id, name, preis, aktueller_wert) VALUES (8, 'Ohne alles', NULL, NULL), (9, 'Negativ', 80, -5)");
        $zeilen = $this->pdo->query("SELECT id, preis, aktueller_wert, " . vsZeitwertSql() . " AS zw FROM wertsachen ORDER BY id")->fetchAll();
        $this->assertCount(9, $zeilen);
        foreach ($zeilen as $z) {
            $this->assertEqualsWithDelta(vsZeitwert($z), (float)$z['zw'], 0.001, 'id ' . $z['id']);
        }
    }

    public function testDashboardKennzahlen(): void
    {
        $k = vsDashboardKennzahlen($this->pdo, '', []);

        $this->assertSame(7, (int)$k['total_items'], 'verborgene zaehlen im Dashboard mit');
        $this->assertEqualsWithDelta(3850, (float)$k['total_value'], 0.001);
        $this->assertEqualsWithDelta(3850 / 7, (float)$k['avg_price'], 0.001);
        $this->assertEqualsWithDelta(3200, (float)$k['total_zeitwert'], 0.001);
        $this->assertEqualsWithDelta(1200, (float)$k['max_zeitwert'], 0.001, 'Uhr: Kaufpreis als Zeitwert');
        // "3 von 7 bewertet": ein Zeitwert von 0 zaehlt als nicht bewertet
        $this->assertSame(3, (int)$k['items_bewertet']);
        $this->assertSame('3.850,00 €', formatPriceLocalized($k['total_value']));
    }

    public function testKategoriewertNachDerselbenRegel(): void
    {
        $kategorien = vsDashboardKategorien($this->pdo, '', []);

        // Sortiert nach Wert, absteigend; leere Kategorie bleibt mit 0 stehen
        $this->assertSame(['Schmuck', 'Elektronik', 'Möbel'], array_column($kategorien, 'kategorie'));
        $wert = array_map('floatval', array_column($kategorien, 'wert', 'kategorie'));
        $this->assertEqualsWithDelta(1850, $wert['Schmuck'], 0.001, 'Ring 650 + Uhr 1200 + Kette 0');
        $this->assertEqualsWithDelta(1200, $wert['Elektronik'], 0.001, 'Laptop 400 + Kamera 300 + Fernseher 500');
        $this->assertEqualsWithDelta(0, $wert['Möbel'], 0.001);
        // Zusammen mit dem Bild ohne Kategorie (150) = Kachel
        $this->assertEqualsWithDelta(3200, array_sum($wert) + 150, 0.001);
    }

    public function testListeRechnetMitKaufpreisOhneVerborgene(): void
    {
        [$anzahl, $summe, $schnitt, $max] = $this->listenKopf();

        $this->assertSame(6, $anzahl);
        $this->assertEqualsWithDelta(3050, $summe, 0.001);
        $this->assertEqualsWithDelta(3050 / 6, $schnitt, 0.001);
        $this->assertEqualsWithDelta(1200, (float)$max, 0.001);

        // Leere Liste: alles 0, keine Division durch 0
        $this->assertSame([0, 0, 0], vsListenStatistik([], 0));
    }

    public function testSuchleisteWieListeUndDashboard(): void
    {
        // Ohne verborgene, wie die Liste (FUND-03)
        [$anzahlListe, $summeListe] = $this->listenKopf();
        $stats = getSearchStats($this->leer());
        $this->assertSame($anzahlListe, $stats['count']);
        $this->assertEqualsWithDelta($summeListe, $stats['total_value'], 0.001);
        $this->assertEqualsWithDelta(2700, $stats['zeitwert'], 0.001);

        // Mit show_hidden=1 alle - dann gleich wie die Dashboard-Kachel (FUND-02)
        $alle = getSearchStats($this->leer(), true);
        $this->assertSame(7, $alle['count']);
        $this->assertEqualsWithDelta(3850, $alle['total_value'], 0.001);
        $this->assertEqualsWithDelta((float)vsDashboardKennzahlen($this->pdo, '', [])['total_zeitwert'], $alle['zeitwert'], 0.001);

        // Filter Elektronik: Laptop 400 + Kamera 300 (Fernseher verborgen)
        $stats = getSearchStats(['kategorie' => [2]] + $this->leer());
        $this->assertSame(2, $stats['count']);
        $this->assertEqualsWithDelta(700, $stats['zeitwert'], 0.001);
    }

    public function testTrendVergleichtZeitwertMitZeitwert(): void
    {
        // Historie: Ring 600 (2025-06-01) und 650 (2026-01-01), Uhr 1100 (2026-01-01)
        // Stichtag nach beiden: Ring 650 -> 650, Uhr 1100 -> 1200 (Kaufpreis)
        [$diff, $pct] = vsDashboardTrend($this->pdo, '', [], '2026-09-26', 3200.0);
        $this->assertEqualsWithDelta(100, $diff, 0.001);
        $this->assertSame(round(100 / 3100 * 100, 1), $pct);

        // Stichtag dazwischen: nur der Ring, damals 600
        [$diff, $pct] = vsDashboardTrend($this->pdo, '', [], '2025-12-31', 3200.0);
        $this->assertEqualsWithDelta(50, $diff, 0.001);
        $this->assertSame(round(50 / 3150 * 100, 1), $pct);

        // Vor jeder Historie: kein Trend (die Seite zeigt dann nichts an)
        $this->assertSame([0.0, null], vsDashboardTrend($this->pdo, '', [], '2025-01-01', 3200.0));

        // Zwei Eintraege am selben Tag zaehlen einmal (der hoehere)
        $this->pdo->exec("INSERT INTO wert_historie (wertsache_id, wert, datum) VALUES (2, 1000, '2026-01-01')");
        [$diff] = vsDashboardTrend($this->pdo, '', [], '2026-09-26', 3200.0);
        $this->assertEqualsWithDelta(100, $diff, 0.001);

        // "nur eigene": lea hat nur den Ring, unveraendert seit Januar
        $this->anmelden('lea');
        [$ownSql, $ownParams] = nurEigeneSql('w');
        $this->assertSame([0.0, 0.0], vsDashboardTrend($this->pdo, $ownSql, $ownParams, '2026-09-26', 1050.0));
    }

    public function testKeineSeiteRechnetDenZeitwertSelbst(): void
    {
        // Rueckfall-Schutz: Jede Rechnung mit aktueller_wert geht ueber
        // helpers_werte.php. Gesucht werden die Muster von vor 4.6.
        $muster = [
            "/aktueller_wert'\\]\\s*\\?:/",                          // $i['aktueller_wert'] ?: ...
            "/aktueller_wert\"\\]\\s*\\?\\?/",                        // $item["aktueller_wert"] ?? ...
            '/COALESCE\(\s*(\w+\.)?aktueller_wert\s*,/i',            // COALESCE(w.aktueller_wert, w.preis)
            '/SUM\(\s*(\w+\.)?aktueller_wert\s*\)/i',                // SUM(aktueller_wert)
            "/!empty\\(\\\$\\w+\\['aktueller_wert'\\]\\)/",          // !empty($item['aktueller_wert'])
            "/array_column\\([^)]*'aktueller_wert'/",                // array_sum(array_column(..., 'aktueller_wert'))
            '/CASE WHEN (\w+\.)?aktueller_wert IS NOT NULL THEN/i',  // getSearchStats bis 4.6
        ];
        $wurzel = realpath(__DIR__ . '/..');
        $treffer = [];
        $dateien = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($wurzel, \FilesystemIterator::SKIP_DOTS));
        foreach ($dateien as $datei) {
            $pfad = str_replace('\\', '/', substr($datei->getPathname(), strlen($wurzel) + 1));
            if ($datei->getExtension() !== 'php' || preg_match('#^(vendor|tests|ValuSafe_Pakete)/#', $pfad) || $pfad === 'changelog_data.php') {
                continue;
            }
            foreach (file($datei->getPathname()) as $nr => $zeile) {
                foreach ($muster as $m) {
                    if (preg_match($m, $zeile)) {
                        $treffer[] = $pfad . ':' . ($nr + 1);
                    }
                }
            }
        }
        $this->assertSame([], $treffer);
    }

    /**
     * Mit Raumfilter (index.php?filter_raum=1) gilt die Summe im Kopf
     * (header_next.php) fuer dieselben Gegenstaende wie die Anzahl. Bis 4.6
     * (FUND-04) fehlten der Statistik-Abfrage filter_raum und
     * filter_position - der Kopf zeigte die Summe aller Raeume.
     */
    public function testRaumfilterGiltAuchFuerDieSumme(): void
    {
        [$anzahl, $summe, $schnitt] = $this->listenKopf(1);
        $this->assertSame(2, $anzahl, 'Ring und Uhr im Wohnzimmer');
        $this->assertEqualsWithDelta(1700, $summe, 0.001);
        $this->assertEqualsWithDelta(850, $schnitt, 0.001);

        // Positionsfilter ebenso: nur der Ring (Vitrine)
        $abfragen = vsListeAbfragen($this->leer(), false, false, 'admin', 0, 1);
        [$sql, $params] = $abfragen['statistik'];
        $this->assertEqualsWithDelta(500, array_sum(array_column($this->db->select($sql, $params), 'preis')), 0.001);

        // Die Suchleiste darueber genauso (bis 4.6 ohne Raum-Chips, HINWEIS-06)
        $stats = getSearchStats($this->leer(), false, 1);
        $this->assertSame(2, $stats['count']);
        $this->assertEqualsWithDelta(1700, $stats['total_value'], 0.001);
        $this->assertEqualsWithDelta(1850, $stats['zeitwert'], 0.001, 'Ring 650 + Uhr 1200');
        $this->assertSame(1, getSearchStats($this->leer(), false, 0, 1)['count']);
        // Mit Suchbegriff: Parameter in der richtigen Reihenfolge
        $this->assertSame(1, getSearchStats(['search' => 'Uhr'] + $this->leer(), false, 1)['count']);
    }

    public function testPreisformat(): void
    {
        $this->assertSame('1.234,50 €', formatPriceLocalized('1234.5'));
        $this->assertSame('0,00 €', formatPriceLocalized(0));
        $this->assertSame('-', formatPriceLocalized(null));
        $this->assertSame('-', formatPriceLocalized(''));
        $this->assertSame('-12,50 €', formatPriceLocalized(-12.5));
    }
}
