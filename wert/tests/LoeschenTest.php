<?php

namespace Tests;

/**
 * Einen Gegenstand loeschen (delete.php; Ablauf seit 4.6 in
 * helpers_loeschen.php).
 *
 * Mit dem Gegenstand muessen seine Bilder (item_images samt Dateien),
 * Dokumente und die Werthistorie verschwinden - und nichts von anderen
 * Gegenstaenden. In 4.3.33 lief deleteAllItemImages() beim Mehrfach-Loeschen
 * nie, weil index.php helpers_images.php nicht einband.
 *
 * Hinweis: Wie delete.php schreibt der Ablauf ins Sicherheitsprotokoll
 * wert/logs/security.log (per .gitignore ausgeschlossen). Bilddateien liegen
 * nur im Testordner UPLOAD_DIR (bootstrap.php).
 */
class LoeschenTest extends GeschaeftskernTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0700, true);
        }
        foreach (['ring_1.jpg', 'ring_2.jpg', 'uhr_1.jpg'] as $datei) {
            file_put_contents(UPLOAD_DIR . $datei, 'JPEG');
        }
        // Dateinamen, die es in documents/ nicht gibt: delete.php loescht
        // dort echte Dateien, der Test soll keine anfassen.
        $this->pdo->exec("INSERT INTO dokumente (id, wertsache_id, dateiname, original_name, dateityp) VALUES
                          (1, 1, 'vs-test-gibt-es-nicht-1.pdf', 'Rechnung Ring.pdf', 'pdf'),
                          (2, 2, 'vs-test-gibt-es-nicht-2.pdf', 'Rechnung Uhr.pdf', 'pdf')");
        $this->pdo->exec("INSERT INTO schadensfall_gegenstaende (fall_id, wertsache_id, zustand) VALUES (1, 1, 'gestohlen')");
        $this->anmelden('lea');
        $_SERVER['REMOTE_ADDR'] = '192.0.2.7';
    }

    protected function tearDown(): void
    {
        foreach (glob(UPLOAD_DIR . '*') ?: [] as $datei) {
            unlink($datei);
        }
        unset($_SERVER['REMOTE_ADDR']);
        parent::tearDown();
    }

    private function loescheRing(): void
    {
        $item = $this->db->selectOne("SELECT * FROM wertsachen WHERE id = ?", [1]);
        $this->assertTrue(darfGegenstand($item), 'Vorbedingung wie delete.php');
        vsGegenstandLoeschen($this->db, $item, 1);
    }

    public function testLoeschenNimmtBilderUndDokumenteMit(): void
    {
        $this->loescheRing();

        $this->assertSame(0, $this->zahl("SELECT COUNT(*) FROM wertsachen WHERE id = 1"));
        $this->assertSame(0, $this->zahl("SELECT COUNT(*) FROM item_images WHERE item_id = 1"));
        $this->assertFileDoesNotExist(UPLOAD_DIR . 'ring_1.jpg');
        $this->assertFileDoesNotExist(UPLOAD_DIR . 'ring_2.jpg');
        $this->assertSame(0, $this->zahl("SELECT COUNT(*) FROM dokumente WHERE wertsache_id = 1"));

        // Aktivitaetsprotokoll mit den alten Werten
        $log = $this->pdo->query("SELECT * FROM activity_log")->fetchAll();
        $this->assertCount(1, $log);
        $this->assertSame('deleted', $log[0]['aktion']);
        $this->assertSame('Ring', $log[0]['bezeichnung']);
        $this->assertSame(3, (int)$log[0]['user_id']);
        $this->assertSame(500, json_decode($log[0]['alt_wert'], true)['preis']);
        $this->assertNull($log[0]['neu_wert']);
    }

    public function testLoeschenLaesstFremdeDatenStehen(): void
    {
        $this->loescheRing();

        $this->assertSame(6, $this->zahl("SELECT COUNT(*) FROM wertsachen"));
        $this->assertSame(1, $this->zahl("SELECT COUNT(*) FROM item_images WHERE item_id = 2"));
        $this->assertFileExists(UPLOAD_DIR . 'uhr_1.jpg');
        $this->assertSame(1, $this->zahl("SELECT COUNT(*) FROM dokumente WHERE wertsache_id = 2"));
        $this->assertSame(1, $this->zahl("SELECT COUNT(*) FROM wert_historie WHERE wertsache_id = 2"));
        $this->assertSame(1, $this->zahl("SELECT COUNT(*) FROM item_images WHERE id = 3 AND is_primary = 1"));
    }

    public function testEinzelnesBildLoeschenMachtNaechstesZumHauptbild(): void
    {
        // deleteItemImage() (image_manager.php): Hauptbild weg -> naechstes
        $this->assertTrue(deleteItemImage(1));
        $this->assertFileDoesNotExist(UPLOAD_DIR . 'ring_1.jpg');
        $this->assertSame(1, $this->zahl("SELECT is_primary FROM item_images WHERE id = 2"));
        $this->assertSame(1, (int)countItemImages(1));
        $this->assertSame(1, (int)countItemImages(2));
        $this->assertFalse(deleteItemImage(99));
    }

    public function testFehlerRolltAllesZurueck(): void
    {
        // DELETE auf dokumente schlaegt fehl (execute() wirft seit 4.3.18):
        // der Gegenstand und seine Bilder muessen bleiben.
        $this->pdo->exec("DROP TABLE dokumente");
        $item = $this->db->selectOne("SELECT * FROM wertsachen WHERE id = 1");

        try {
            vsGegenstandLoeschen($this->db, $item, 1);
            $this->fail('Ausnahme erwartet');
        } catch (\PDOException $e) {
            // erwartet, delete.php faengt sie und kehrt zur Liste zurueck
        }
        $this->assertSame(1, $this->zahl("SELECT COUNT(*) FROM wertsachen WHERE id = 1"));
        $this->assertSame(2, $this->zahl("SELECT COUNT(*) FROM item_images WHERE item_id = 1"));
        $this->assertFileExists(UPLOAD_DIR . 'ring_1.jpg');
        $this->assertSame(0, $this->zahl("SELECT COUNT(*) FROM activity_log"));
    }

    /**
     * Werthistorie und Schadensfall-Zuordnung verschwinden mit dem
     * Gegenstand. Bis 4.6 (FUND-05) loeschte kein Code sie selbst; das tat nur
     * der Fremdschluessel wert_historie_ibfk_1 ON DELETE CASCADE - und der
     * fehlt laut Messung vom 27.10.2026 auf zwei aelteren Instanzen. SQLite hat hier keine Fremdschluessel, zeigt
     * also, was der Code selbst tut.
     */
    public function testWerthistorieWirdMitgeloescht(): void
    {
        $this->loescheRing();

        $this->assertSame(0, $this->zahl("SELECT COUNT(*) FROM wert_historie WHERE wertsache_id = 1"));
        $this->assertSame(0, $this->zahl("SELECT COUNT(*) FROM schadensfall_gegenstaende WHERE wertsache_id = 1"));
        // Fremde Historie bleibt (Uhr)
        $this->assertSame(1, $this->zahl("SELECT COUNT(*) FROM wert_historie WHERE wertsache_id = 2"));
    }

    /**
     * Bis 4.6 (FUND-08) band delete.php helpers_images.php nicht ein. Der
     * Aufruf deleteAllItemImages() steht hinter function_exists() und lief
     * deshalb beim Einzel-Loeschen nie: Die Bilddateien blieben in upload/.
     * Derselbe Fehler war in 4.3.33 im Mehrfach-Loeschen (index.php). Die
     * Tests oben binden helpers_images.php ueber bootstrap.php ohnehin ein -
     * deshalb hier die Einbindungskette der Seite selbst.
     * Mehrfach-Loeschen (index.php) bindet die Datei direkt ein.
     */
    public function testDeletePhpBindetDieBilderfunktionenEin(): void
    {
        $eingebunden = self::eingebundeneDateien('delete.php');
        $this->assertContains('helpers_loeschen.php', $eingebunden);
        $this->assertContains('helpers.php', $eingebunden);

        $this->assertContains('helpers_images.php', $eingebunden);
        $this->assertContains('helpers_images.php', self::eingebundeneDateien('index.php'));
    }

    /**
     * Alle Dateien, die $datei per require/include einbindet, rekursiv, als
     * Pfade relativ zu wert/. Dateien, die es nicht gibt (config.php),
     * werden uebergangen.
     */
    private static function eingebundeneDateien(string $datei, array &$gesehen = []): array
    {
        $wurzel = realpath(__DIR__ . '/..');
        $quelle = @file_get_contents($wurzel . '/' . $datei);
        if ($quelle === false || in_array($datei, $gesehen, true)) {
            return $gesehen;
        }
        $gesehen[] = $datei;
        preg_match_all("#(?:require|include)(?:_once)?\s*\(?\s*(?:__DIR__\s*\.\s*)?'/?([\w/.-]+\.php)'#", $quelle, $t);
        foreach ($t[1] as $ziel) {
            $ziel = ltrim(dirname($datei) === '.' ? $ziel : dirname($datei) . '/' . $ziel, '/');
            self::eingebundeneDateien($ziel, $gesehen);
        }
        return $gesehen;
    }
}
