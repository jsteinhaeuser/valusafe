<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Abgleich der Sitzung mit der Datenbank (sitzung_helpers.php, OWASP-A07-003).
 */
class SitzungHelpersTest extends TestCase
{
    private const JETZT = 1_800_000_000;
    private const DAUER = 12 * 3600;

    private function zeile(string $rolle = 'admin', string $hash = 'HASH-1', string $name = 'anna'): array
    {
        return ['username' => $name, 'role' => $rolle, 'password' => $hash];
    }

    private function sitzung(string $rolle = 'admin', string $hash = 'HASH-1'): array
    {
        return [
            'user_id'    => 7,
            'username'   => 'anna',
            'role'       => $rolle,
            'login_time' => self::JETZT - 60,
            'pw_kennung' => vsSitzungKennung($hash),
        ];
    }

    public function testUnveraenderteSitzungBleibt(): void
    {
        $s = $this->sitzung();
        $this->assertSame('ok', vsSitzungPruefen($s, $this->zeile(), self::JETZT, self::DAUER));
        $this->assertSame('admin', $s['role']);
    }

    public function testGeloeschtesKontoEndet(): void
    {
        $s = $this->sitzung();
        $this->assertSame('geloescht', vsSitzungPruefen($s, null, self::JETZT, self::DAUER));
    }

    public function testHerabgestufterAdminVerliertRechteSofort(): void
    {
        $s = $this->sitzung('admin');
        $this->assertSame('ok', vsSitzungPruefen($s, $this->zeile('read'), self::JETZT, self::DAUER));
        $this->assertSame('read', $s['role']);
    }

    public function testHochgestuftWirktSofort(): void
    {
        $s = $this->sitzung('read');
        vsSitzungPruefen($s, $this->zeile('editor'), self::JETZT, self::DAUER);
        $this->assertSame('editor', $s['role']);
    }

    public function testPasswortwechselBeendetAndereSitzungen(): void
    {
        $s = $this->sitzung('admin', 'HASH-ALT');
        $this->assertSame('passwort', vsSitzungPruefen($s, $this->zeile('admin', 'HASH-NEU'), self::JETZT, self::DAUER));
    }

    public function testAlteSitzungOhneKennungWirdUebernommen(): void
    {
        $s = $this->sitzung();
        unset($s['pw_kennung']);
        $this->assertSame('ok', vsSitzungPruefen($s, $this->zeile(), self::JETZT, self::DAUER));
        $this->assertSame(vsSitzungKennung('HASH-1'), $s['pw_kennung']);
    }

    public function testHoechstdauerUeberschritten(): void
    {
        $s = $this->sitzung();
        $s['login_time'] = self::JETZT - self::DAUER - 1;
        $this->assertSame('abgelaufen', vsSitzungPruefen($s, $this->zeile(), self::JETZT, self::DAUER));
    }

    public function testHoechstdauerGenauErreichtGiltNoch(): void
    {
        $s = $this->sitzung();
        $s['login_time'] = self::JETZT - self::DAUER;
        $this->assertSame('ok', vsSitzungPruefen($s, $this->zeile(), self::JETZT, self::DAUER));
    }

    public function testUmbenennungFolgtDerDatenbank(): void
    {
        // isSuperAdmin() haengt am Benutzernamen in der Sitzung.
        $s = $this->sitzung();
        vsSitzungPruefen($s, $this->zeile('admin', 'HASH-1', 'berta'), self::JETZT, self::DAUER);
        $this->assertSame('berta', $s['username']);
    }

    public function testRollenwechselBleibtFuerEchtenAdmin(): void
    {
        $s = $this->sitzung('read');
        $s['original_role'] = 'admin';
        vsSitzungPruefen($s, $this->zeile('admin'), self::JETZT, self::DAUER);
        $this->assertSame('read', $s['role']);
        $this->assertSame('admin', $s['original_role']);
    }

    public function testRollenwechselEndetWennKeinAdminMehr(): void
    {
        $s = $this->sitzung('editor');
        $s['original_role'] = 'admin';
        vsSitzungPruefen($s, $this->zeile('read'), self::JETZT, self::DAUER);
        $this->assertSame('read', $s['role']);
        $this->assertArrayNotHasKey('original_role', $s);
    }

    public function testKennungEnthaeltDenHashNicht(): void
    {
        $hash = '$argon2id$v=19$m=65536,t=4,p=3$abc$def';
        $k = vsSitzungKennung($hash);
        $this->assertSame(32, strlen($k));
        $this->assertStringNotContainsString('argon2id', $k);
    }

    public function testHoechstdauerVorgabe(): void
    {
        $this->assertSame(12 * 3600, vsSitzungHoechstdauer());
    }

    // ── Cookie-Merkmale (OWASP-A02-001) ─────────────────────────────────────

    private const PHP_VORGABE = ['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false, 'samesite' => ''];

    public function testCookieOhneServerEinstellungen(): void
    {
        $m = vsSitzungsCookieMerkmale(self::PHP_VORGABE, true);
        $this->assertTrue($m['httponly']);
        $this->assertTrue($m['secure']);
        $this->assertSame('Lax', $m['samesite']);
        $this->assertSame('/', $m['path']);
    }

    public function testCookieOhneHttpsNichtSecure(): void
    {
        // Sonst kaeme das Cookie bei http:// gar nicht an - keine Anmeldung moeglich
        $this->assertFalse(vsSitzungsCookieMerkmale(self::PHP_VORGABE, false)['secure']);
    }

    public function testStrengereServerEinstellungBleibt(): void
    {
        $bisher = ['secure' => true, 'samesite' => 'Strict'] + self::PHP_VORGABE;
        $m = vsSitzungsCookieMerkmale($bisher, false);
        $this->assertTrue($m['secure']);
        $this->assertSame('Strict', $m['samesite']);
    }
}
