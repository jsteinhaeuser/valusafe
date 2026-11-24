<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * sync_agent.php ist der Endpunkt, über den der Hub (Flottenverwaltung) Dateien auf
 * jeder Instanz lesen/schreiben/löschen kann (nach Token-Prüfung). Diese
 * Tests decken die zwei Kernschutzmechanismen ab: Path-Traversal-Schutz
 * (sanitizePath) und die Exclude-Filter beim Datei-Listing (listFiles).
 */
class SyncHelpersTest extends TestCase
{
    // ── sanitizePath ────────────────────────────────────────────────────

    public function testAcceptsNormalRelativePath(): void
    {
        $this->assertSame('foo/bar.php', sanitizePath('foo/bar.php'));
    }

    public function testStripsLeadingSlash(): void
    {
        $this->assertSame('foo/bar.php', sanitizePath('/foo/bar.php'));
    }

    public function testConvertsBackslashesToForwardSlashes(): void
    {
        $this->assertSame('foo/bar.php', sanitizePath('foo\\bar.php'));
    }

    public function testRejectsEmptyPath(): void
    {
        $this->assertNull(sanitizePath(''));
    }

    public function testRejectsPathTraversalWithDotDot(): void
    {
        $this->assertNull(sanitizePath('../config.php'));
    }

    public function testRejectsPathTraversalNestedInSubpath(): void
    {
        $this->assertNull(sanitizePath('foo/../../config.php'));
    }

    public function testRejectsPathTraversalWithBackslashes(): void
    {
        $this->assertNull(sanitizePath('..\\..\\windows\\system.ini'));
    }

    public function testRejectsPathTraversalEvenWhenNotAtStart(): void
    {
        // ".." irgendwo im String muss reichen, unabhängig von der Position
        $this->assertNull(sanitizePath('backend/insurance..php/../../etc/passwd'));
    }

    // ── listFiles ───────────────────────────────────────────────────────

    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/valusafe_synctest_' . uniqid();
        mkdir($this->tmpDir);
        mkdir($this->tmpDir . '/upload'); // EXCLUDED_DIRS
        mkdir($this->tmpDir . '/sub');

        file_put_contents($this->tmpDir . '/index.php', '<?php echo "ok";');
        file_put_contents($this->tmpDir . '/style.css', 'body{}');
        file_put_contents($this->tmpDir . '/config.php', '<?php // secret'); // EXCLUDED_FILES
        file_put_contents($this->tmpDir . '/readme.txt', 'nicht sync-fähig'); // Extension nicht erlaubt
        file_put_contents($this->tmpDir . '/upload/photo.jpg', 'binary'); // in excluded dir
        file_put_contents($this->tmpDir . '/sub/helper.php', '<?php // helper');
    }

    protected function tearDown(): void
    {
        $this->recursiveDelete($this->tmpDir);
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testListsSyncableFilesWithMd5(): void
    {
        $result = [];
        listFiles($this->tmpDir, $this->tmpDir, $result);

        $this->assertArrayHasKey('index.php', $result);
        $this->assertSame(md5_file($this->tmpDir . '/index.php'), $result['index.php']);
        $this->assertArrayHasKey('style.css', $result);
        $this->assertArrayHasKey('sub/helper.php', $result);
    }

    public function testExcludesFilesInExcludedFilesList(): void
    {
        $result = [];
        listFiles($this->tmpDir, $this->tmpDir, $result);

        $this->assertArrayNotHasKey('config.php', $result);
    }

    public function testExcludesFilesWithDisallowedExtension(): void
    {
        $result = [];
        listFiles($this->tmpDir, $this->tmpDir, $result);

        $this->assertArrayNotHasKey('readme.txt', $result);
    }

    public function testExcludesEntireExcludedDirectory(): void
    {
        $result = [];
        listFiles($this->tmpDir, $this->tmpDir, $result);

        $this->assertArrayNotHasKey('upload/photo.jpg', $result);
        foreach (array_keys($result) as $path) {
            $this->assertStringStartsNotWith('upload/', $path);
        }
    }

    public function testListsSyncExceptionsDespiteExcludes(): void
    {
        // 4.7: wert-update/version.json liegt in einem gesperrten Ordner,
        // backend/config.php traegt einen gesperrten Dateinamen - beide
        // stehen in SYNC_EXCEPTIONS und muessen in der Liste auftauchen.
        mkdir($this->tmpDir . '/wert-update');
        mkdir($this->tmpDir . '/backend');
        file_put_contents($this->tmpDir . '/wert-update/version.json', '{"version":"4.7"}');
        file_put_contents($this->tmpDir . '/wert-update/anderes.json', '{}');
        file_put_contents($this->tmpDir . '/backend/config.php', '<?php // Anwendungscode');

        $result = [];
        listFiles($this->tmpDir, $this->tmpDir, $result);

        $this->assertSame(md5_file($this->tmpDir . '/wert-update/version.json'), $result['wert-update/version.json'] ?? null);
        $this->assertArrayHasKey('backend/config.php', $result);
        $this->assertArrayNotHasKey('wert-update/anderes.json', $result, 'der uebrige Ordner bleibt zu');
        $this->assertArrayNotHasKey('config.php', $result, 'die echte config.php bleibt gesperrt');
    }

    // ── istTextInhalt ───────────────────────────────────────────────────
    //
    // Entscheidet in read_file, ob der Inhalt roh oder base64-kodiert in die
    // JSON-Antwort geht. Faellt die Entscheidung falsch aus, gibt json_encode()
    // false zurueck und der Agent antwortet mit einer leeren Seite — ohne
    // Fehlermeldung. Deshalb hier eigene Tests.

    public function testErkenntEinfachenTextAlsText(): void
    {
        $this->assertTrue(istTextInhalt('<?php echo "ok";'));
    }

    public function testErkenntUmlauteAlsText(): void
    {
        // Die gesamte Oberflaeche ist UTF-8; Umlaute duerfen nicht dazu
        // fuehren, dass PHP-Dateien ploetzlich base64-kodiert uebertragen
        // werden — sonst versteht ein aelterer Hub sie nicht mehr.
        $this->assertTrue(istTextInhalt('Wertsachen: Schmuck, Möbel, Gemälde — 100 %'));
    }

    public function testLeererInhaltGiltAlsText(): void
    {
        $this->assertTrue(istTextInhalt(''));
    }

    public function testErkenntPdfKopfAlsBinaer(): void
    {
        // Die ersten Bytes einer echten PDF-Datei. 0x80 ist in UTF-8 kein
        // gueltiges Startbyte — genau daran scheitert json_encode().
        $pdfKopf = "%PDF-1.7\n%\xE2\xE3\xCF\xD3\n1 0 obj\n<</Type/Catalog>>\n\x80\x81";
        $this->assertFalse(istTextInhalt($pdfKopf));
    }

    public function testEinzelnesUngueltigesByteGenuegt(): void
    {
        $this->assertFalse(istTextInhalt("ganz normaler Text\xFFmit einem kaputten Byte"));
    }

    public function testWasAlsTextGiltIstAuchJsonFaehig(): void
    {
        // Das ist die eigentliche Zusicherung: istTextInhalt() === true muss
        // bedeuten, dass json_encode() nicht false liefert. Sonst ist die
        // Antwort des Agenten leer.
        foreach (['schlicht', 'mit Ümläuten', '', "Zeilen\numbruch", '{"a":1}'] as $probe) {
            $this->assertTrue(istTextInhalt($probe));
            $this->assertNotFalse(json_encode(['content' => $probe]), 'json_encode scheiterte an: ' . $probe);
        }
    }

    public function testBinaerinhaltWuerdeJsonEncodeScheiternLassen(): void
    {
        // Die Gegenprobe — sie belegt, dass die Fallunterscheidung ueberhaupt
        // noetig ist und nicht bloss Vorsicht.
        $binaer = "\x89PNG\r\n\x1A\n\x00\x00\x00\rIHDR";
        $this->assertFalse(istTextInhalt($binaer));
        $this->assertFalse(json_encode(['content' => $binaer]));
        $this->assertNotFalse(json_encode(['content' => base64_encode($binaer)]));
    }
}
