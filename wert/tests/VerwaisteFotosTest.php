<?php

namespace Tests;

if (!defined('VERWAISTE_FOTOS_KEIN_AUTOSTART')) define('VERWAISTE_FOTOS_KEIN_AUTOSTART', true);
// bin/ gibt es nur im Arbeits-Repository, nicht im oeffentlichen (dort
// wird die Klasse uebersprungen, siehe setUpBeforeClass).
if (is_file(__DIR__ . '/../../bin/verwaiste_fotos.php')) require_once __DIR__ . '/../../bin/verwaiste_fotos.php';

/**
 * Aufraeumskript bin/verwaiste_fotos.php: Es darf nur Fotos ohne Verweis
 * anfassen, und auch die nur verschieben. Ein Fehler hier loescht Fotos von
 * Anwendern - deshalb jede Ausnahme einzeln.
 */
class VerwaisteFotosTest extends GeschaeftskernTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!is_file(__DIR__ . '/../../bin/verwaiste_fotos.php')) {
            self::markTestSkipped('bin/verwaiste_fotos.php fehlt (oeffentliches Repository ohne Flottenwerkzeuge).');
        }
    }

    private string $upload;
    private string $ziel;
    private int $jetzt;

    protected function setUp(): void
    {
        parent::setUp();
        $basis = sys_get_temp_dir() . '/valusafe-tests-' . getmypid() . '/verwaist/';
        $this->upload = $basis . 'upload/';
        $this->ziel   = $basis . 'backups/verwaiste_fotos_test/';
        @mkdir($this->upload . 'avatars', 0700, true);
        $this->jetzt = time();
        $alt = $this->jetzt - 3 * 86400;

        // Verweise der Testdaten: uhr.jpg (wertsachen.bild), ring_1/ring_2/uhr_1 (item_images)
        $dateien = [
            'uhr.jpg' => $alt, 'ring_1.jpg' => $alt, 'uhr_1.jpg' => $alt,   // mit Verweis
            'alt_1.jpg' => $alt, 'ALT_2.JPEG' => $alt, 'alt_3.webp' => $alt, // verwaist
            'demo_vs_uhr.jpg' => $alt,                                      // Demo-Bild
            'frisch.jpg' => $this->jetzt - 600,                             // gerade hochgeladen
            'notiz.txt' => $alt, '.htaccess' => $alt,                       // keine Bilder
        ];
        foreach ($dateien as $name => $zeit) {
            file_put_contents($this->upload . $name, str_repeat('x', 100));
            touch($this->upload . $name, $zeit);
        }
        file_put_contents($this->upload . 'avatars/avatar_3.jpg', 'x');
        touch($this->upload . 'avatars/avatar_3.jpg', $alt);
    }

    protected function tearDown(): void
    {
        $basis = dirname($this->upload) . '/';
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basis, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        @rmdir($basis);
        parent::tearDown();
    }

    public function testFindetNurBilderOhneVerweis(): void
    {
        $liste = vsVerwaisteFotos($this->pdo, $this->upload, $this->jetzt);

        $this->assertSame(['ALT_2.JPEG', 'alt_1.jpg', 'alt_3.webp'], array_column($liste, 'name'));
        $this->assertSame(300, array_sum(array_column($liste, 'bytes')));
    }

    public function testVerweisAusItemImagesUndWertsachenSchuetzt(): void
    {
        // ring_2.jpg steht in item_images, liegt aber nicht da: kein Problem
        $this->pdo->exec("INSERT INTO item_images (item_id, filename) VALUES (3, 'alt_1.jpg')");
        $this->pdo->exec("UPDATE wertsachen SET bild = 'alt_3.webp' WHERE id = 4");
        $this->assertSame(['ALT_2.JPEG'], array_column(vsVerwaisteFotos($this->pdo, $this->upload, $this->jetzt), 'name'));

        // Anders geschrieben als die Datei: im Zweifel verwiesen, bleibt liegen
        $this->pdo->exec("INSERT INTO item_images (item_id, filename) VALUES (3, 'alt_2.jpeg ')");
        $this->assertSame([], vsVerwaisteFotos($this->pdo, $this->upload, $this->jetzt));
    }

    public function testVerschiebtGenauDieListeUndUeberschreibtNichts(): void
    {
        $liste = vsVerwaisteFotos($this->pdo, $this->upload, $this->jetzt);
        [$anzahl, $fehler] = vsVerwaisteFotosVerschieben($liste, $this->upload, $this->ziel);

        $this->assertSame(3, $anzahl);
        $this->assertSame([], $fehler);
        foreach (['alt_1.jpg', 'ALT_2.JPEG', 'alt_3.webp'] as $n) {
            $this->assertFileDoesNotExist($this->upload . $n);
            $this->assertFileExists($this->ziel . $n);
        }
        // Alles andere bleibt, wo es war
        foreach (['uhr.jpg', 'ring_1.jpg', 'uhr_1.jpg', 'demo_vs_uhr.jpg', 'frisch.jpg', 'notiz.txt', '.htaccess', 'avatars/avatar_3.jpg'] as $n) {
            $this->assertFileExists($this->upload . $n, $n);
        }
        $this->assertSame([], vsVerwaisteFotos($this->pdo, $this->upload, $this->jetzt));

        // Zweiter Lauf mit alter Liste: nichts mehr da, nichts ueberschrieben
        file_put_contents($this->upload . 'alt_1.jpg', 'neu');
        touch($this->upload . 'alt_1.jpg', $this->jetzt - 3 * 86400);
        [$anzahl, $fehler] = vsVerwaisteFotosVerschieben($liste, $this->upload, $this->ziel);
        $this->assertSame(0, $anzahl);
        $this->assertCount(3, $fehler);
        $this->assertSame(str_repeat('x', 100), file_get_contents($this->ziel . 'alt_1.jpg'), 'nicht ueberschrieben');
        $this->assertFileExists($this->upload . 'alt_1.jpg');
    }

    public function testPruefsummeAendertSichMitDerListe(): void
    {
        $liste = vsVerwaisteFotos($this->pdo, $this->upload, $this->jetzt);
        $kennung = vsVerwaisteFotosKennung($liste);
        $this->assertSame($kennung, vsVerwaisteFotosKennung(vsVerwaisteFotos($this->pdo, $this->upload, $this->jetzt)));

        // Ein Foto bekommt inzwischen einen Verweis: andere Liste, andere Pruefsumme
        $this->pdo->exec("INSERT INTO item_images (item_id, filename) VALUES (3, 'alt_1.jpg')");
        $this->assertNotSame($kennung, vsVerwaisteFotosKennung(vsVerwaisteFotos($this->pdo, $this->upload, $this->jetzt)));
    }

    public function testNamenMitPfadWerdenNichtAusserhalbGesucht(): void
    {
        // Eine manipulierte Liste mit "../" bewegt nichts ausserhalb von upload/
        file_put_contents(dirname($this->upload) . '/geheim.jpg', 'x');
        [$anzahl, $fehler] = vsVerwaisteFotosVerschieben([['name' => '../geheim.jpg', 'bytes' => 1]], $this->upload, $this->ziel);
        $this->assertSame(0, $anzahl);
        $this->assertFileExists(dirname($this->upload) . '/geheim.jpg');
    }
}
