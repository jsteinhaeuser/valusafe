<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../update_helpers.php';

// Versionsabfrage gegen eine http-Adresse: vsUpdHttpHolen lehnt sie ab, ohne
// ins Netz zu gehen - so laesst sich der Fehlerweg von vsUpdStand pruefen.
if (!defined('VALUSAFE_UPDATE_INFO_URL')) define('VALUSAFE_UPDATE_INFO_URL', 'http://127.0.0.1/update_info.php');

/**
 * update_helpers.php (4.7): Antwort der Versionsabfrage, Zwischenspeicher,
 * Sperren, Paket holen - ohne Netz. Dazu: jeder Text, den die Update-Seite
 * und der Hinweis im Kopf benutzen, steht in backend/lang de und en, und
 * palindrom_Valusafe/update_info.php liefert, was die Installation annimmt.
 */
class UpdateHelpersTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/vsupdh-test-' . bin2hex(random_bytes(6)) . '/';
        mkdir($this->tmp, 0700);
    }

    protected function tearDown(): void
    {
        putenv('VALUSAFE_DOCKER');
        vsUpdOrdnerLoeschen($this->tmp);
    }

    private function info(array $sonst = []): string
    {
        return json_encode(array_replace([
            'format' => 1, 'version' => '4.7', 'datum' => '2026-11-06',
            'paket' => ['url' => 'https://palindrom.de/valusafe/update_paket.php?v=4.7', 'bytes' => 1234, 'sha256' => str_repeat('ab', 32)],
            'changelog' => 'https://palindrom.de/valusafe/docs/changelog_vollstaendig.txt',
        ], $sonst));
    }

    // ── Antwort auswerten ───────────────────────────────────────────────────

    public function testGueltigeAntwort(): void
    {
        $i = vsUpdInfoAuswerten($this->info());
        $this->assertSame('4.7', $i['version']);
        $this->assertSame('2026-11-06', $i['datum']);
        $this->assertSame(1234, $i['paket']['bytes']);
    }

    public function testOhnePaketNurHinweis(): void
    {
        $this->assertNull(vsUpdInfoAuswerten($this->info(['paket' => null]))['paket']);
    }

    public function testChronikNurHttps(): void
    {
        $this->assertNull(vsUpdInfoAuswerten($this->info(['changelog' => 'javascript:alert(1)']))['changelog']);
    }

    /** @dataProvider kaputteAntworten */
    public function testKaputteAntwortWirdAbgelehnt(string $json): void
    {
        $this->expectException(VsUpdFehler::class);
        vsUpdInfoAuswerten($json);
    }

    public static function kaputteAntworten(): array
    {
        $gut = ['format' => 1, 'version' => '4.7', 'paket' => ['url' => 'https://x/p', 'bytes' => 5, 'sha256' => str_repeat('0', 64)]];
        $mit = fn(array $a) => json_encode(array_replace_recursive($gut, $a));
        return [
            'kein JSON'           => ['<html>'],
            'falsches Format'     => [$mit(['format' => 2])],
            'Version mit Text'    => [$mit(['version' => '4.7<script>'])],
            'Paket ueber http'    => [$mit(['paket' => ['url' => 'http://x/p']])],
            'Pruefsumme kaputt'   => [$mit(['paket' => ['sha256' => 'abc']])],
            'Paket zu gross'      => [$mit(['paket' => ['bytes' => VS_UPD_MAX_BYTES + 1]])],
            'Groesse als Text'    => [$mit(['paket' => ['bytes' => '5']])],
        ];
    }

    // ── Zwischenspeicher ────────────────────────────────────────────────────

    public function testFrischerStandWirdNichtNeuAbgefragt(): void
    {
        $stand = ['geprueft' => time() - 60, 'info' => vsUpdInfoAuswerten($this->info()), 'fehler' => null];
        file_put_contents($this->tmp . 'stand.json', json_encode($stand));
        $this->assertSame($stand, vsUpdStand($this->tmp));
    }

    public function testAlterStandFragtNeuUndBehaeltInfoBeiFehler(): void
    {
        $info = vsUpdInfoAuswerten($this->info());
        file_put_contents($this->tmp . 'stand.json', json_encode(['geprueft' => time() - 2 * 86400, 'info' => $info, 'fehler' => null]));
        $s = vsUpdStand($this->tmp);
        $this->assertSame('Nur HTTPS-Adressen.', $s['fehler']);
        $this->assertSame($info, $s['info'], 'die letzte bekannte Version bleibt stehen');
        $this->assertGreaterThan(time() - 5, $s['geprueft']);
        // Gespeichert: der naechste Aufruf fragt nicht gleich wieder.
        $this->assertSame($s, vsUpdStand($this->tmp));
    }

    public function testNurLesenFragtNie(): void
    {
        $this->assertNull(vsUpdStand($this->tmp, false, true));
        $this->assertFileDoesNotExist($this->tmp . 'stand.json');
    }

    public function testNeuer(): void
    {
        $s = ['info' => ['version' => '4.7']];
        $this->assertTrue(vsUpdNeuer($s, '4.6.1'));
        $this->assertFalse(vsUpdNeuer($s, '4.7'));
        $this->assertFalse(vsUpdNeuer($s, '4.7.1'));
        $this->assertFalse(vsUpdNeuer(null, '4.6.1'));
        $this->assertFalse(vsUpdNeuer(['info' => null], '4.6.1'));
    }

    // ── Sperren, Version, Holen ─────────────────────────────────────────────

    public function testDockerSperrt(): void
    {
        if (defined('FILE_SYNC_TOKEN') || defined('VALUSAFE_UPDATE_KNOPF')) $this->markTestSkipped('Konstanten gesetzt');
        $this->assertNull(vsUpdKnopfSperre());
        putenv('VALUSAFE_DOCKER=1');
        $this->assertSame('docker', vsUpdKnopfSperre());
    }

    public function testInstallierteVersion(): void
    {
        mkdir($this->tmp . 'wert-update');
        $this->assertSame('0', vsUpdInstalliert(rtrim($this->tmp, '/')));
        file_put_contents($this->tmp . 'wert-update/version.json', '{"version":"4.7"}');
        $this->assertSame('4.7', vsUpdInstalliert(rtrim($this->tmp, '/')));
        file_put_contents($this->tmp . 'wert-update/version.json', '{"version":"4.7 beta"}');
        $this->assertSame('0', vsUpdInstalliert(rtrim($this->tmp, '/')));
    }

    public function testHolenOhnePaketOderUeberHttp(): void
    {
        try {
            vsUpdPaketHolen(['paket' => null], $this->tmp . 'a.zip');
            $this->fail('ohne Paket');
        } catch (VsUpdFehler $e) {
            $this->assertStringContainsString('kein Update-Paket', $e->getMessage());
        }
        try {
            vsUpdPaketHolen(['paket' => ['url' => 'http://x/p', 'bytes' => 1, 'sha256' => str_repeat('0', 64)]], $this->tmp . 'b.zip');
            $this->fail('http');
        } catch (VsUpdFehler $e) {
            $this->assertFileDoesNotExist($this->tmp . 'b.zip');
        }
    }

    // ── Texte ───────────────────────────────────────────────────────────────

    public function testAlleTexteInDeUndEn(): void
    {
        $de = require __DIR__ . '/../backend/lang/de.php';
        $en = require __DIR__ . '/../backend/lang/en.php';
        $quelltext = file_get_contents(__DIR__ . '/../backend/update.php')
                   . file_get_contents(__DIR__ . '/../backend/layout/header_next_page.php')
                   . file_get_contents(__DIR__ . '/../backend/index.php');
        preg_match_all("/t\('(upd_[a-z_]+)'\)/", $quelltext, $m);
        $schluessel = array_unique(array_merge($m[1], ['upd_locked_hub', 'upd_locked_docker', 'upd_locked_config', 'btn_cancel']));
        $this->assertGreaterThan(30, count($schluessel));
        foreach ($schluessel as $k) {
            $this->assertArrayHasKey($k, $de, "de: $k");
            $this->assertArrayHasKey($k, $en, "en: $k");
            $this->assertSame(substr_count($de[$k], '%'), substr_count($en[$k], '%'), "Platzhalter: $k");
        }
    }

    // ── Meldungen uebersetzen (4.9.5) ───────────────────────────────────────

    public function testMeldungsSchluesselInDeUndEn(): void
    {
        $de = require __DIR__ . '/../backend/lang/de.php';
        $en = require __DIR__ . '/../backend/lang/en.php';
        foreach (array_merge(array_values(VS_UPD_MELDUNGEN), ['upd_e_rolled_back']) as $k) {
            $this->assertArrayHasKey($k, $de, "de: $k");
            $this->assertArrayHasKey($k, $en, "en: $k");
            $this->assertSame(substr_count($de[$k], '%'), substr_count($en[$k], '%'), "Platzhalter: $k");
        }
    }

    /** Jede feste Meldung aus update_helpers.php findet ihren Schluessel. */
    public function testAlleHelferMeldungenWerdenUebersetzt(): void
    {
        $alt = $GLOBALS['translations'] ?? null;
        $GLOBALS['translations'] = require __DIR__ . '/../backend/lang/en.php';
        try {
            preg_match_all("/new VsUpdFehler\\('([^'$]+)'\\)/", file_get_contents(__DIR__ . '/../update_helpers.php'), $m);
            $this->assertGreaterThan(10, count($m[1]));
            foreach ($m[1] as $text) {
                $this->assertNotSame($text, vsUpdMeldung($text), "nicht uebersetzt: $text");
            }
            // mit Platzhaltern, und der Grund hinter "zurueckgerollt"
            $this->assertStringContainsString('403', vsUpdMeldung('palindrom.de antwortet mit Status 403.'));
            $this->assertStringStartsWith('palindrom.de answers', vsUpdMeldung('palindrom.de antwortet mit Status 403.'));
            $this->assertSame('Update rolled back: The self-test after the update failed.',
                vsUpdMeldung('Update zurueckgerollt: Selbsttest nach dem Update fehlgeschlagen.'));
            // Unbekanntes bleibt, wie es ist
            $this->assertSame('Etwas ganz anderes.', vsUpdMeldung('Etwas ganz anderes.'));
        } finally {
            $GLOBALS['translations'] = $alt;
        }
    }

    // ── palindrom.de ────────────────────────────────────────────────────────

    public function testUpdateInfoVonPalindromWirdAngenommen(): void
    {
        $quelle = __DIR__ . '/../../palindrom_Valusafe';
        if (!is_file("$quelle/update_info.php")) $this->markTestSkipped('palindrom_Valusafe nicht neben wert/');
        $site = $this->tmp . 'site';
        mkdir("$site/downloads", 0700, true);
        mkdir("$site/data", 0700);
        copy("$quelle/update_info.php", "$site/update_info.php");
        copy("$quelle/valusafe_release.php", "$site/valusafe_release.php");
        $rel = require "$site/valusafe_release.php";

        // Ohne Update-Paket: nur der Hinweis
        $i = vsUpdInfoAuswerten((string)shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$site/update_info.php")));
        $this->assertSame($rel['version'], $i['version']);
        $this->assertNull($i['paket']);

        file_put_contents("$site/downloads/" . $rel['update_name'], 'paketinhalt');
        $i = vsUpdInfoAuswerten((string)shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$site/update_info.php")));
        $this->assertSame(hash('sha256', 'paketinhalt'), $i['paket']['sha256']);
        $this->assertSame(11, $i['paket']['bytes']);
        $this->assertStringEndsWith('update_paket.php?v=' . rawurlencode($rel['version']), $i['paket']['url']);
        $this->assertFileExists("$site/data/update_pruefsumme.json");
    }
}
