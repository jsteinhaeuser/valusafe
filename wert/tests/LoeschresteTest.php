<?php

namespace Tests;

if (!defined('LOESCHRESTE_KEIN_AUTOSTART')) define('LOESCHRESTE_KEIN_AUTOSTART', true);
// bin/ gibt es nur im Arbeits-Repository, nicht im oeffentlichen (dort
// wird die Klasse uebersprungen, siehe setUpBeforeClass).
if (is_file(__DIR__ . '/../../bin/loeschreste_pruefen.php')) require_once __DIR__ . '/../../bin/loeschreste_pruefen.php';

/**
 * Zaehlfunktion von bin/loeschreste_pruefen.php (Gegenstelle auf jeder
 * Instanz, siehe TESTFUNDE.md FUND-05/FUND-08). Die Fremdschluessel liest sie
 * aus information_schema - das gibt es in SQLite nicht, hier abgeschaltet.
 */
class LoeschresteTest extends GeschaeftskernTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!is_file(__DIR__ . '/../../bin/loeschreste_pruefen.php')) {
            self::markTestSkipped('bin/loeschreste_pruefen.php fehlt (oeffentliches Repository ohne Flottenwerkzeuge).');
        }
    }

    private string $ordner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ordner = sys_get_temp_dir() . '/valusafe-tests-' . getmypid() . '/loeschreste/';
        @mkdir($this->ordner . 'avatars', 0700, true);
        // Verweise in den Testdaten: uhr.jpg (wertsachen.bild), ring_1/ring_2/uhr_1 (item_images)
        foreach (['uhr.jpg' => 10, 'ring_1.jpg' => 10, 'uhr_1.jpg' => 10,
                  'alt_1.jpg' => 1000, 'alt_2.webp' => 24, 'liesmich.txt' => 5] as $name => $groesse) {
            file_put_contents($this->ordner . $name, str_repeat('x', $groesse));
        }
        file_put_contents($this->ordner . 'avatars/avatar_3.jpg', 'x');
        // Reste eines frueher geloeschten Gegenstands 9
        $this->pdo->exec("INSERT INTO item_images (item_id, filename) VALUES (9, 'weg.jpg')");
        $this->pdo->exec("INSERT INTO wert_historie (wertsache_id, wert, datum) VALUES (9, 10, '2025-01-01'), (9, 12, '2025-02-01')");
        $this->pdo->exec("INSERT INTO schadensfall_gegenstaende (fall_id, wertsache_id) VALUES (1, 9)");
    }

    protected function tearDown(): void
    {
        foreach (array_merge(glob($this->ordner . 'avatars/*') ?: [], glob($this->ordner . '*') ?: []) as $f) {
            is_dir($f) ? rmdir($f) : unlink($f);
        }
        @rmdir($this->ordner);
        parent::tearDown();
    }

    public function testZaehltWaisenUndDateienOhneNamen(): void
    {
        $z = loeschresteZahlen($this->pdo, $this->ordner, false);

        $this->assertSame(['item_images' => 1, 'dokumente' => 0, 'wert_historie' => 2, 'schadensfall_gegenstaende' => 1], $z['waisen']);

        // 5 Bilder (txt und avatars/ zaehlen nicht), 2 ohne Verweis mit 1024 Bytes;
        // ring_2.jpg und weg.jpg stehen in der Datenbank, liegen aber nicht da
        $this->assertSame(['gesamt' => 5, 'ohne_verweis' => 2, 'ohne_verweis_bytes' => 1024, 'verweis_ohne_datei' => 2], $z['dateien']);

        // Abgeschaltet bzw. nicht messbar: null, nicht 'keiner'
        $this->assertSame([null], array_values(array_unique($z['fremdschluessel'])));

        // Nur Zahlen - kein Dateiname verlaesst die Instanz
        $this->assertStringNotContainsString('alt_1', json_encode($z));
    }

    public function testFehlendeTabelleIstNichtMessbarStattNull(): void
    {
        $this->pdo->exec("DROP TABLE schadensfall_gegenstaende");
        $z = loeschresteZahlen($this->pdo, $this->ordner, false);
        $this->assertNull($z['waisen']['schadensfall_gegenstaende']);
        $this->assertSame(2, $z['waisen']['wert_historie']);

        // Ohne information_schema (SQLite): nicht messbar
        $z = loeschresteZahlen($this->pdo, $this->ordner, true);
        $this->assertNull($z['fremdschluessel']['wert_historie']);

        // Ordner fehlt: Dateien nicht messbar
        $this->assertNull(loeschresteZahlen($this->pdo, '/gibt/es/nicht/', false)['dateien']['gesamt']);
    }
}
