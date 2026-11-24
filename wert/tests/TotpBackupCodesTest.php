<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use TOTP;

/**
 * 2FA-Ersatzcodes gehasht (TOTP.php, OWASP-A06-004).
 */
class TotpBackupCodesTest extends TestCase
{
    public function testGehashteCodesEnthaltenKeinenKlartext(): void
    {
        $codes = ['A1B2C3D4', 'DEADBEEF'];
        $gespeichert = TOTP::hashBackupCodes($codes);
        $this->assertCount(2, $gespeichert);
        $this->assertStringNotContainsString('A1B2C3D4', implode('|', $gespeichert));
    }

    public function testGehashterCodeWirdGefunden(): void
    {
        $gespeichert = TOTP::hashBackupCodes(['A1B2C3D4', 'DEADBEEF']);
        $this->assertSame(1, TOTP::findBackupCode($gespeichert, 'deadbeef'));
        $this->assertSame(0, TOTP::findBackupCode($gespeichert, ' A1B2C3D4 '));
        $this->assertFalse(TOTP::findBackupCode($gespeichert, '00000000'));
    }

    public function testKlartextVonVor46GiltWeiter(): void
    {
        $this->assertSame(1, TOTP::findBackupCode(['11111111', 'ABCDEF12'], 'abcdef12'));
    }

    public function testFormFremdeEingabeWirdNichtVerglichen(): void
    {
        $gespeichert = TOTP::hashBackupCodes(['A1B2C3D4']);
        $this->assertFalse(TOTP::findBackupCode($gespeichert, ''));
        $this->assertFalse(TOTP::findBackupCode($gespeichert, '123456'));      // TOTP-Code
        $this->assertFalse(TOTP::findBackupCode(['$2y$broken'], '$2y$broken'));
    }

    public function testUmstellungBehaeltGehashteUndHashtKlartext(): void
    {
        $gemischt = [TOTP::hashBackupCodes(['A1B2C3D4'])[0], 'DEADBEEF'];
        $this->assertTrue(TOTP::brauchtUmstellung($gemischt));
        $neu = TOTP::backupCodesUmstellen($gemischt);
        $this->assertSame($gemischt[0], $neu[0]);
        $this->assertFalse(TOTP::brauchtUmstellung($neu));
        $this->assertSame(1, TOTP::findBackupCode($neu, 'DEADBEEF'));
        $this->assertSame(0, TOTP::findBackupCode($neu, 'A1B2C3D4'));
    }

    public function testErzeugteCodesHabenDasPruefbareFormat(): void
    {
        foreach (TOTP::generateBackupCodes(8) as $c) {
            $this->assertMatchesRegularExpression('/^[0-9A-F]{8}$/', $c);
        }
    }
}
