<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../update_kern.php';

/**
 * update_kern.php - Update-Pakete pruefen, auspacken, einspielen, zurueckrollen.
 * Signiert wird mit einem Wegwerf-Schluessel; die echten liegen nicht hier.
 */
class UpdateKernTest extends TestCase
{
    private string $tmp;
    private string $geheim;
    private array $schluessel;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/vsupd-test-' . bin2hex(random_bytes(6));
        mkdir($this->tmp, 0700);
        $paar = sodium_crypto_sign_keypair();
        $this->geheim = sodium_crypto_sign_secretkey($paar);
        $pk = sodium_crypto_sign_publickey($paar);
        $this->schluessel = [vsUpdKennung($pk) => base64_encode($pk)];
    }

    protected function tearDown(): void
    {
        vsUpdOrdnerLoeschen($this->tmp);
    }

    // ── Hilfen ──────────────────────────────────────────────────────────────

    private function ordner(string $name, array $dateien): string
    {
        $o = "$this->tmp/$name";
        foreach ($dateien as $pfad => $inhalt) {
            @mkdir(dirname("$o/$pfad"), 0700, true);
            file_put_contents("$o/$pfad", $inhalt);
        }
        return $o;
    }

    private function paket(array $dateien, array $extra = [], string $version = '4.7.0', array $entfallen = []): string
    {
        $quelle = $this->ordner('quelle-' . bin2hex(random_bytes(3)), $dateien);
        $m = vsUpdManifestErzeugen($quelle, 'valusafe', $version, ['config.php', 'upload/'], $entfallen, $extra['mindestens'] ?? null);
        $json = vsUpdManifestJson($m);
        $zip = "$this->tmp/paket-" . bin2hex(random_bytes(3)) . '.zip';
        vsUpdPaketSchreiben($quelle, $json, vsUpdSignieren($json, $this->geheim), $zip);
        return $zip;
    }

    /** ZIP von Hand: fuer Pakete, die vsUpdManifestErzeugen gar nicht bauen wuerde. */
    private function rohPaket(array $manifest, array $eintraege, ?string $geheim = null): string
    {
        $json = vsUpdManifestJson($manifest);
        $zip = "$this->tmp/roh-" . bin2hex(random_bytes(3)) . '.zip';
        $z = new ZipArchive();
        $z->open($zip, ZipArchive::CREATE);
        $z->addFromString('manifest.json', $json);
        $z->addFromString('manifest.sig', vsUpdSignieren($json, $geheim ?? $this->geheim));
        foreach ($eintraege as $name => $inhalt) $z->addFromString($name, $inhalt);
        $z->close();
        return $zip;
    }

    private function manifest(array $dateien, array $sonst = []): array
    {
        return array_merge([
            'format' => VS_UPD_FORMAT, 'programm' => 'valusafe', 'version' => '4.7.0',
            'mindestens' => null, 'erzeugt' => date('c'),
            'dateien' => array_map(fn($i) => hash('sha256', $i), $dateien),
            'geschuetzt' => ['config.php', 'upload/'], 'entfallen' => [],
        ], $sonst);
    }

    private function pruefen(string $zip, string $installiert = '4.6.1'): array
    {
        return vsUpdPaketPruefen($zip, $this->schluessel, 'valusafe', $installiert);
    }

    private function assertAbgelehnt(callable $f, string $teilText): void
    {
        try {
            $f();
            $this->fail("Erwartet: Ablehnung mit '$teilText'");
        } catch (VsUpdFehler $e) {
            $this->assertStringContainsString($teilText, $e->getMessage());
        }
    }

    // ── Pruefen ─────────────────────────────────────────────────────────────

    public function testOrdentlichesPaketWirdAngenommen(): void
    {
        $m = $this->pruefen($this->paket(['index.php' => '<?php // neu', 'css/a.css' => 'x{}']));
        $this->assertSame('4.7.0', $m['version']);
        $this->assertSame(['css/a.css', 'index.php'], array_keys($m['dateien']));
    }

    public function testFremderSchluesselWirdAbgelehnt(): void
    {
        $fremd = sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair());
        $zip = $this->rohPaket($this->manifest(['a.php' => 'x']), ['dateien/a.php' => 'x'], $fremd);
        $this->assertAbgelehnt(fn() => $this->pruefen($zip), 'Signatur ungueltig');
    }

    public function testVeraendertesManifestWirdAbgelehnt(): void
    {
        $zip = $this->paket(['a.php' => 'x']);
        $z = new ZipArchive();
        $z->open($zip);
        $z->addFromString('manifest.json', str_replace('4.7.0', '9.9.9', $z->getFromName('manifest.json')));
        $z->close();
        $this->assertAbgelehnt(fn() => $this->pruefen($zip), 'Signatur ungueltig');
    }

    public function testSignaturAusAnderemZusammenhangGiltNicht(): void
    {
        // Dieselbe Nachricht ohne das Format-Praefix signiert - etwa eine
        // Probe-Signatur - darf kein gueltiges Paket ergeben.
        $m = $this->manifest(['a.php' => 'x']);
        $json = vsUpdManifestJson($m);
        $zip = "$this->tmp/kontext.zip";
        $z = new ZipArchive();
        $z->open($zip, ZipArchive::CREATE);
        $z->addFromString('manifest.json', $json);
        $z->addFromString('manifest.sig', json_encode(['kennung' => 'x',
            'signatur' => base64_encode(sodium_crypto_sign_detached($json, $this->geheim))]));
        $z->addFromString('dateien/a.php', 'x');
        $z->close();
        $this->assertAbgelehnt(fn() => $this->pruefen($zip), 'Signatur ungueltig');
    }

    public function testZweiterSchluesselWirdAngenommen(): void
    {
        $paar = sodium_crypto_sign_keypair();
        $pk = sodium_crypto_sign_publickey($paar);
        $zip = $this->paket(['a.php' => 'x']);
        $beide = [vsUpdKennung($pk) => base64_encode($pk)] + $this->schluessel;
        $this->assertSame('4.7.0', vsUpdPaketPruefen($zip, $beide, 'valusafe', '4.6.1')['version']);
    }

    public function testAeltereOderGleicheVersionWirdAbgelehnt(): void
    {
        $zip = $this->paket(['a.php' => 'x'], [], '4.6.1');
        $this->assertAbgelehnt(fn() => $this->pruefen($zip, '4.6.1'), 'nichts zu tun');
        $this->assertAbgelehnt(fn() => $this->pruefen($zip, '4.10'), 'nichts zu tun');
    }

    public function testMindestversionWirdBeachtet(): void
    {
        $zip = $this->paket(['a.php' => 'x'], ['mindestens' => '4.6.1']);
        $this->assertAbgelehnt(fn() => $this->pruefen($zip, '4.5.2'), 'mindestens Version 4.6.1');
        $this->assertSame('4.7.0', $this->pruefen($zip, '4.6.1')['version']);
    }

    public function testFalschesProgrammWirdAbgelehnt(): void
    {
        $zip = $this->paket(['a.php' => 'x']);
        $this->assertAbgelehnt(fn() => vsUpdPaketPruefen($zip, $this->schluessel, 'valu-basic', '2.4'), 'nicht fuer valu-basic');
    }

    public function testPfadAusDerAnwendungHinausWirdAbgelehntAuchSigniert(): void
    {
        foreach (['../x.php', '/etc/x', 'a/../../x.php', 'a\\b.php', 'C:x.php', 'a.php.', './a.php'] as $boese) {
            $zip = $this->rohPaket($this->manifest([$boese => 'x']), ["dateien/$boese" => 'x']);
            $this->assertAbgelehnt(fn() => $this->pruefen($zip), 'Unzulaessiger Pfad');
        }
    }

    public function testGeschuetzteDateiWirdAbgelehnt(): void
    {
        foreach (['config.php', 'Config.php', 'upload/bild.jpg'] as $g) {
            $zip = $this->rohPaket($this->manifest([$g => 'x']), ["dateien/$g" => 'x']);
            $this->assertAbgelehnt(fn() => $this->pruefen($zip), 'geschuetzte Datei');
        }
        // Auch die Anwendung kann selbst etwas schuetzen, unabhaengig vom Paket.
        $zip = $this->paket(['impressum.php' => 'x']);
        $this->assertAbgelehnt(fn() => vsUpdPaketPruefen($zip, $this->schluessel, 'valusafe', '4.6.1', ['impressum.php']), 'geschuetzte Datei');
    }

    public function testPaketbauLehntGeschuetzteDateiAb(): void
    {
        $this->assertAbgelehnt(fn() => $this->paket(['config.php' => 'geheim']), 'Geschuetzte Datei im Paket');
    }

    public function testGeschuetzteDateiDarfNichtEntfallen(): void
    {
        $zip = $this->rohPaket($this->manifest(['a.php' => 'x'], ['entfallen' => ['config.php']]), ['dateien/a.php' => 'x']);
        $this->assertAbgelehnt(fn() => $this->pruefen($zip), 'geschuetzte Datei loeschen');
    }

    public function testZusaetzlicheDateiImZipWirdAbgelehnt(): void
    {
        $zip = $this->rohPaket($this->manifest(['a.php' => 'x']), ['dateien/a.php' => 'x', 'dateien/hinten.php' => 'boese']);
        $this->assertAbgelehnt(fn() => $this->pruefen($zip), 'nicht im Manifest');
        $zip = $this->rohPaket($this->manifest(['a.php' => 'x']), ['dateien/a.php' => 'x', '../ausbruch.php' => 'boese']);
        $this->assertAbgelehnt(fn() => $this->pruefen($zip), 'nicht im Manifest');
    }

    public function testFehlendeDateiWirdAbgelehnt(): void
    {
        $zip = $this->rohPaket($this->manifest(['a.php' => 'x', 'b.php' => 'y']), ['dateien/a.php' => 'x']);
        $this->assertAbgelehnt(fn() => $this->pruefen($zip), 'Im Paket fehlt: b.php');
    }

    public function testDoppelterPfadNurInGrossKleinschreibungWirdAbgelehnt(): void
    {
        $zip = $this->rohPaket($this->manifest(['a.php' => 'x', 'A.php' => 'y']), ['dateien/a.php' => 'x', 'dateien/A.php' => 'y']);
        $this->assertAbgelehnt(fn() => $this->pruefen($zip), 'doppelt');
    }

    public function testKeinZipWirdAbgelehnt(): void
    {
        file_put_contents("$this->tmp/kein.zip", 'das ist kein zip');
        $this->assertAbgelehnt(fn() => $this->pruefen("$this->tmp/kein.zip"), 'kein lesbares ZIP');
    }

    // ── Entpacken ───────────────────────────────────────────────────────────

    public function testVeraenderterInhaltWirdSchonBeimPruefenAbgelehnt(): void
    {
        // Manifest verspricht "y", im ZIP liegt etwas anderes - die Seite darf
        // danach nicht "signiert und vollstaendig" anzeigen.
        $m = $this->manifest(['a.php' => 'x', 'b.php' => 'y']);
        $zip = $this->rohPaket($m, ['dateien/a.php' => 'x', 'dateien/b.php' => 'boese']);
        $this->assertAbgelehnt(fn() => $this->pruefen($zip), 'Pruefsumme stimmt nicht: b.php');
    }

    public function testEntpackenPrueftJedeDateiNochEinmal(): void
    {
        // Zwischen Pruefen und Auspacken lag die Datei auf der Platte.
        $m = $this->manifest(['a.php' => 'x', 'b.php' => 'y']);
        $zip = $this->rohPaket($m, ['dateien/a.php' => 'x', 'dateien/b.php' => 'boese']);
        $this->assertAbgelehnt(fn() => vsUpdEntpacken($zip, $m, "$this->tmp/neu"), 'Pruefsumme stimmt nicht: b.php');
        $this->assertDirectoryDoesNotExist("$this->tmp/neu");
    }

    public function testEntpackenLegtDateienAn(): void
    {
        $zip = $this->paket(['index.php' => 'neu', 'js/x/y.js' => 'j']);
        vsUpdEntpacken($zip, $this->pruefen($zip), "$this->tmp/neu");
        $this->assertSame('neu', file_get_contents("$this->tmp/neu/index.php"));
        $this->assertSame('j', file_get_contents("$this->tmp/neu/js/x/y.js"));
    }

    // ── Einspielen und Zurueckrollen ───────────────────────────────────────

    private function installation(): string
    {
        return $this->ordner('app', [
            'index.php' => 'alt', 'config.php' => 'zugang', 'weg.php' => 'entfaellt',
            'upload/bild.jpg' => 'foto', 'css/a.css' => 'alt-css',
        ]);
    }

    private function stand(string $o): array
    {
        $r = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($o, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $f) $r[substr($f->getPathname(), strlen($o) + 1)] = $f->isDir() ? '<dir>' : file_get_contents($f->getPathname());
        ksort($r);
        return $r;
    }

    private function vorbereiten(): array
    {
        $app = $this->installation();
        $zip = $this->paket(['index.php' => 'neu', 'neu/tief/n.php' => 'n', 'css/a.css' => 'neu-css'], [], '4.7.0', ['weg.php', 'gab_es_nie.php']);
        $m = $this->pruefen($zip);
        vsUpdEntpacken($zip, $m, "$this->tmp/neu");
        return [$app, $m];
    }

    public function testEinspielenTauschtAusUndLaesstGeschuetztesInRuhe(): void
    {
        [$app, $m] = $this->vorbereiten();
        vsUpdEinspielen("$this->tmp/neu", $m, $app, "$this->tmp/sich");
        $this->assertSame([
            'config.php' => 'zugang', 'css' => '<dir>', 'css/a.css' => 'neu-css', 'index.php' => 'neu',
            'neu' => '<dir>', 'neu/tief' => '<dir>', 'neu/tief/n.php' => 'n',
            'upload' => '<dir>', 'upload/bild.jpg' => 'foto',
        ], $this->stand($app));
        $this->assertSame('fertig', vsUpdJournalZustand("$this->tmp/sich"));
    }

    public function testFehlgeschlagenerSelbsttestRolltVollstaendigZurueck(): void
    {
        [$app, $m] = $this->vorbereiten();
        $vorher = $this->stand($app);
        $gesehen = null;
        $this->assertAbgelehnt(function () use ($app, $m, &$gesehen) {
            vsUpdEinspielen("$this->tmp/neu", $m, $app, "$this->tmp/sich", function () use ($app, &$gesehen) {
                $gesehen = file_get_contents("$app/index.php");
                return false;
            });
        }, 'zurueckgerollt');
        $this->assertSame('neu', $gesehen, 'Selbsttest muss den neuen Stand sehen');
        $this->assertSame($vorher, $this->stand($app));
        $this->assertSame('zurueckgerollt', vsUpdJournalZustand("$this->tmp/sich"));
    }

    public function testAusnahmeInDerMigrationRolltZurueck(): void
    {
        [$app, $m] = $this->vorbereiten();
        $vorher = $this->stand($app);
        $this->assertAbgelehnt(fn() => vsUpdEinspielen("$this->tmp/neu", $m, $app, "$this->tmp/sich",
            function () { throw new RuntimeException('Migration 016 kaputt'); }), 'Migration 016 kaputt');
        $this->assertSame($vorher, $this->stand($app));
    }

    public function testZurueckrollenNachAbbruchMittendrin(): void
    {
        // Lauf wurde hart abgebrochen: Journal steht auf "laeuft", ein Teil
        // ist schon getauscht. vsUpdZurueckrollen holt den alten Stand.
        [$app, $m] = $this->vorbereiten();
        $vorher = $this->stand($app);
        vsUpdEinspielen("$this->tmp/neu", $m, $app, "$this->tmp/sich");
        $j = json_decode(file_get_contents("$this->tmp/sich/journal.json"), true);
        $j['zustand'] = 'laeuft';
        file_put_contents("$this->tmp/sich/journal.json", json_encode($j));
        vsUpdZurueckrollen($app, "$this->tmp/sich");
        $this->assertSame($vorher, $this->stand($app));
        vsUpdZurueckrollen($app, "$this->tmp/sich");   // ein zweites Mal schadet nicht
        $this->assertSame($vorher, $this->stand($app));
    }

    public function testVerknuepfungAufDemWegWirdNichtBeschrieben(): void
    {
        $app = $this->installation();
        $draussen = $this->ordner('draussen', ['x.txt' => 'unberuehrt']);
        symlink($draussen, "$app/js");
        $zip = $this->paket(['js/x.txt' => 'boese']);
        $m = $this->pruefen($zip);
        vsUpdEntpacken($zip, $m, "$this->tmp/neu");
        $this->assertAbgelehnt(fn() => vsUpdEinspielen("$this->tmp/neu", $m, $app, "$this->tmp/sich"), 'Verknuepfung');
        $this->assertSame('unberuehrt', file_get_contents("$draussen/x.txt"));
    }

    public function testSelbsttestFindetSyntaxfehlerUndRolltZurueck(): void
    {
        $app = $this->installation();
        $vorher = $this->stand($app);
        $zip = $this->paket(['index.php' => "<?php\nif (true) {\n", 'wert-update/version.json' => '{"version":"4.7.0"}']);
        $m = $this->pruefen($zip);
        vsUpdEntpacken($zip, $m, "$this->tmp/neu");
        $this->assertAbgelehnt(fn() => vsUpdEinspielen("$this->tmp/neu", $m, $app, "$this->tmp/sich",
            fn() => vsUpdSelbsttest($app, $m)), 'index.php hat einen Syntaxfehler');
        $this->assertSame($vorher, $this->stand($app));
    }

    public function testSelbsttestPrueftVersionJson(): void
    {
        $app = $this->installation();
        $zip = $this->paket(['index.php' => '<?php echo 1;', 'wert-update/version.json' => '{"version":"4.6.9"}']);
        $m = $this->pruefen($zip);
        vsUpdEntpacken($zip, $m, "$this->tmp/neu");
        $this->assertAbgelehnt(fn() => vsUpdEinspielen("$this->tmp/neu", $m, $app, "$this->tmp/sich",
            fn() => vsUpdSelbsttest($app, $m)), 'version.json passt nicht');
    }

    public function testSelbsttestBestehtBeiOrdentlichemPaket(): void
    {
        $app = $this->installation();
        $zip = $this->paket(['index.php' => '<?php echo 1;', 'wert-update/version.json' => '{"version":"4.7.0"}']);
        $m = $this->pruefen($zip);
        vsUpdEntpacken($zip, $m, "$this->tmp/neu");
        vsUpdEinspielen("$this->tmp/neu", $m, $app, "$this->tmp/sich", fn() => vsUpdSelbsttest($app, $m));
        $this->assertSame('fertig', vsUpdJournalZustand("$this->tmp/sich"));
    }

    public function testVorhandenerSicherungsordnerWirdNichtUeberschrieben(): void
    {
        [$app, $m] = $this->vorbereiten();
        mkdir("$this->tmp/sich");
        $this->assertAbgelehnt(fn() => vsUpdEinspielen("$this->tmp/neu", $m, $app, "$this->tmp/sich"), 'gibt es schon');
    }

    /** Zip mit beliebigen Eintraegen, ohne Manifest. */
    private function zipOhneManifest(array $namen): string
    {
        $zip = "$this->tmp/ohne-" . bin2hex(random_bytes(3)) . '.zip';
        $z = new ZipArchive();
        $z->open($zip, ZipArchive::CREATE);
        foreach ($namen as $n) $z->addFromString($n, 'x');
        $z->close();
        return $zip;
    }

    private function fehlerVon(string $zip): VsUpdFehler
    {
        try {
            $this->pruefen($zip);
        } catch (VsUpdFehler $e) {
            return $e;
        }
        $this->fail('Paket wurde angenommen');
    }

    /** Fund 05.11.2026: vollstaendiges Paket statt Update-Paket gewaehlt. */
    public function testVollstaendigesPaketWirdErkannt(): void
    {
        $vs = $this->zipOhneManifest(['ValuSafe_Install_v4.8.1/index.php', 'ValuSafe_Install_v4.8.1/db.php', 'ValuSafe_Install_v4.8.1/lang/de.php']);
        $this->assertSame(VS_UPD_FEHLER_VOLLPAKET, $this->fehlerVon($vs)->getCode());

        $vb = $this->zipOhneManifest(['ValuBasic_USB/LICENSE', 'ValuBasic_USB/valu-basic/index.php', 'ValuBasic_USB/php/php.exe']);
        $this->assertSame(VS_UPD_FEHLER_VOLLPAKET, $this->fehlerVon($vb)->getCode());

        // Irgendein anderes ZIP: weiter die allgemeine Meldung
        $anders = $this->zipOhneManifest(['fotos/a.jpg', 'fotos/b.jpg', 'liste.csv']);
        $e = $this->fehlerVon($anders);
        $this->assertSame(0, $e->getCode());
        $this->assertStringContainsString('manifest.json', $e->getMessage());
    }
}
