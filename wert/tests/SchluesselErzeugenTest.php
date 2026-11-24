<?php
use PHPUnit\Framework\TestCase;

define('SCHLUESSEL_KEIN_AUTOSTART', true);
// bin/ gibt es nur im Arbeits-Repository, nicht im oeffentlichen (dort
// wird die Klasse uebersprungen, siehe setUpBeforeClass).
if (is_file(__DIR__ . '/../../bin/schluessel_erzeugen.php')) require_once __DIR__ . '/../../bin/schluessel_erzeugen.php';

/**
 * bin/schluessel_erzeugen.php - Signaturschluessel fuer die Update-Pakete.
 * Mit den schnellen Argon2id-Kosten; das Skript selbst nimmt SENSITIVE.
 */
class SchluesselErzeugenTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!is_file(__DIR__ . '/../../bin/schluessel_erzeugen.php')) {
            self::markTestSkipped('bin/schluessel_erzeugen.php fehlt (oeffentliches Repository ohne Flottenwerkzeuge).');
        }
    }

    private const OPS = SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE;
    private const MEM = SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE;
    private const PASS = 'eine lange Probe-Passphrase fuer den Test';

    public function testRichtigePassphraseLiefertPassendenSchluessel(): void
    {
        $datei = vsSchluesselpaarErzeugen('haupt', self::PASS, self::OPS, self::MEM);
        $geheim = vsSchluesselEntschluesseln($datei, self::PASS);
        $this->assertNotNull($geheim);

        $sig = sodium_crypto_sign_detached('Paket', $geheim);
        $this->assertTrue(sodium_crypto_sign_verify_detached($sig, 'Paket', base64_decode($datei['oeffentlich'])));
    }

    public function testFalschePassphraseLiefertNichts(): void
    {
        $datei = vsSchluesselpaarErzeugen('haupt', self::PASS, self::OPS, self::MEM);
        $this->assertNull(vsSchluesselEntschluesseln($datei, self::PASS . 'x'));
        $this->assertNull(vsSchluesselEntschluesseln($datei, ''));   // Enter ohne Eingabe
    }

    public function testDateiEnthaeltDenGeheimenSchluesselNichtImKlartext(): void
    {
        $datei = vsSchluesselpaarErzeugen('haupt', self::PASS, self::OPS, self::MEM);
        $geheim = vsSchluesselEntschluesseln($datei, self::PASS);
        $json = json_encode($datei);
        $this->assertStringNotContainsString(base64_encode($geheim), $json);
        $this->assertStringNotContainsString(bin2hex($geheim), $json);
        $this->assertStringNotContainsString(self::PASS, $json);
    }

    public function testVertauschterOeffentlicherSchluesselWirdErkannt(): void
    {
        // Eine Datei, deren oeffentlicher Schluessel nicht zum geheimen passt,
        // wuerde Pakete signieren, die keine Anwendung annimmt.
        $a = vsSchluesselpaarErzeugen('haupt', self::PASS, self::OPS, self::MEM);
        $b = vsSchluesselpaarErzeugen('reserve', self::PASS, self::OPS, self::MEM);
        $a['oeffentlich'] = $b['oeffentlich'];
        $this->assertNull(vsSchluesselEntschluesseln($a, self::PASS));
    }

    public function testVeraendertesChiffratWirdAbgelehnt(): void
    {
        $datei = vsSchluesselpaarErzeugen('haupt', self::PASS, self::OPS, self::MEM);
        $roh = base64_decode($datei['verschluesselt']['chiffrat']);
        $roh[5] = chr(ord($roh[5]) ^ 1);
        $datei['verschluesselt']['chiffrat'] = base64_encode($roh);
        $this->assertNull(vsSchluesselEntschluesseln($datei, self::PASS));
    }

    public function testKennungIstStabilUndKurz(): void
    {
        $datei = vsSchluesselpaarErzeugen('haupt', self::PASS, self::OPS, self::MEM);
        $this->assertSame(16, strlen($datei['kennung']));
        $this->assertSame($datei['kennung'], vsSchluesselKennung(base64_decode($datei['oeffentlich'])));
    }
}
