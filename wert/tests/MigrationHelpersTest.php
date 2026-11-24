<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Deckt die Bausteine des Migrationssystems ab (Import/Export/Bild-Transfer
 * in sync_agent.php), die der Hub (Flottenverwaltung) nutzt.
 */
class MigrationHelpersTest extends TestCase
{
    // ── isAllowedImageFilename ──────────────────────────────────────────
    // Regression: die Base64-Upload-Action hatte vorher einen eigenen,
    // fehlerhaften Regex ("png,gif" statt "png|gif"), der .png/.gif
    // fälschlich ablehnte. Jetzt läuft alles über diese eine Funktion.

    /** @dataProvider allowedImageFilenames */
    public function testAcceptsCommonImageExtensions(string $filename): void
    {
        $this->assertTrue(isAllowedImageFilename($filename));
    }

    public static function allowedImageFilenames(): array
    {
        return [
            ['foto.jpg'], ['foto.JPG'], ['foto.jpeg'],
            ['foto.png'], ['foto.PNG'],
            ['foto.gif'],
            ['foto.webp'], ['foto.heic'], ['foto.heif'],
        ];
    }

    public function testRejectsNonImageExtension(): void
    {
        $this->assertFalse(isAllowedImageFilename('shell.php'));
        $this->assertFalse(isAllowedImageFilename('archiv.zip'));
        $this->assertFalse(isAllowedImageFilename('ohne_endung'));
    }

    // ── decodeImportPayload ─────────────────────────────────────────────

    public function testDecodesValidPayload(): void
    {
        $json = json_encode(['data' => ['kategorien' => [['id' => 1, 'name' => 'Test']]]]);
        $payload = decodeImportPayload($json);

        $this->assertIsArray($payload);
        $this->assertSame('Test', $payload['data']['kategorien'][0]['name']);
    }

    public function testRejectsInvalidJson(): void
    {
        $this->assertNull(decodeImportPayload('{das ist kein json'));
    }

    public function testRejectsJsonWithoutDataKey(): void
    {
        $this->assertNull(decodeImportPayload(json_encode(['foo' => 'bar'])));
    }

    public function testRejectsEmptyBody(): void
    {
        $this->assertNull(decodeImportPayload(''));
    }

    // ── buildInsertSql ───────────────────────────────────────────────────

    public function testBuildsInsertStatementWithBacktickedColumns(): void
    {
        $sql = buildInsertSql('wertsachen', ['id', 'titel', 'wert']);

        $this->assertSame(
            'INSERT INTO `wertsachen` (`id`, `titel`, `wert`) VALUES (?, ?, ?)',
            $sql
        );
    }

    public function testPlaceholderCountMatchesColumnCount(): void
    {
        $sql = buildInsertSql('orte', ['id']);
        $this->assertSame('INSERT INTO `orte` (`id`) VALUES (?)', $sql);
    }

    public function testUmlautImSpaltennamenIstErlaubt(): void
    {
        $this->assertSame('INSERT INTO `dokumente` (`dateigröße`) VALUES (?)',
            buildInsertSql('dokumente', ['dateigröße']));
    }

    /** @dataProvider boeseSpaltennamen */
    public function testBoeseSpaltennamenWerdenAbgelehnt(string $name): void
    {
        // OWASP-A05-004
        $this->expectException(\InvalidArgumentException::class);
        buildInsertSql('wertsachen', ['id', $name]);
    }

    public static function boeseSpaltennamen(): array
    {
        return [
            'Backtick'    => ['name`) VALUES (1); DROP TABLE users; -- '],
            'Leerzeichen' => ['a b'],
            'Leer'        => [''],
            'Punkt'       => ['users.password'],
            'zu lang'     => [str_repeat('a', 65)],
        ];
    }

    public function testOhneSpaltenWirdAbgelehnt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        buildInsertSql('wertsachen', []);
    }

    // ── collectImageHashes ───────────────────────────────────────────────

    private string $uploadDir;

    protected function setUp(): void
    {
        $this->uploadDir = sys_get_temp_dir() . '/valusafe_uploadtest_' . uniqid() . '/';
        mkdir($this->uploadDir);
        file_put_contents($this->uploadDir . 'a.jpg', 'bild-inhalt-1');
        file_put_contents($this->uploadDir . 'b.png', 'bild-inhalt-2');
        file_put_contents($this->uploadDir . 'notizen.txt', 'kein bild');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->uploadDir . '*') as $f) {
            unlink($f);
        }
        rmdir($this->uploadDir);
    }

    public function testCollectsOnlyImageFilesWithMd5(): void
    {
        $images = collectImageHashes($this->uploadDir);

        $this->assertArrayHasKey('a.jpg', $images);
        $this->assertArrayHasKey('b.png', $images);
        $this->assertArrayNotHasKey('notizen.txt', $images);
        $this->assertSame(md5('bild-inhalt-1'), $images['a.jpg']);
    }

    public function testReturnsEmptyArrayForMissingDirectory(): void
    {
        $this->assertSame([], collectImageHashes($this->uploadDir . 'gibt-es-nicht/'));
    }
}
