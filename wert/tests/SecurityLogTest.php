<?php

namespace Tests;

use PDO;
use PHPUnit\Framework\TestCase;

/**
 * security_log wird beim Schreiben automatisch gekuerzt (security.php).
 * Bis 4.3.30 wuchs die Tabelle unbegrenzt.
 */
class SecurityLogTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Spalten wie in install/schema.sql
        $this->pdo->exec("CREATE TABLE security_log (
            id INTEGER PRIMARY KEY, event_type TEXT, user_id NUMERIC,
            description TEXT, ip_address TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
    }

    private function eintrag(string $vorTagen): void
    {
        $zeit = date('Y-m-d H:i:s', strtotime("-$vorTagen days"));
        $this->pdo->prepare("INSERT INTO security_log (event_type, created_at) VALUES ('login_success', ?)")
                  ->execute([$zeit]);
    }

    private function anzahl(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM security_log")->fetchColumn();
    }

    public function testLoeschtNurWasAelterAlsNeunzigTageIst(): void
    {
        $this->eintrag('200');
        $this->eintrag('91');
        $this->eintrag('89');
        $this->eintrag('1');

        $this->assertSame(2, securityLogKuerzen($this->pdo));
        $this->assertSame(2, $this->anzahl());
    }

    public function testFrischeTabelleBleibtUnberuehrt(): void
    {
        $this->eintrag('0');
        $this->assertSame(0, securityLogKuerzen($this->pdo));
        $this->assertSame(1, $this->anzahl());
    }

    public function testSchreibenRaeumtMitAuf(): void
    {
        $this->eintrag('120');
        $_SERVER['REMOTE_ADDR'] = '192.0.2.1';

        logSecurityEvent($this->pdo, 'login_success', 1, 'Test');

        // Der alte Eintrag ist weg, der neue da.
        $this->assertSame(1, $this->anzahl());
        $this->assertSame('login_success', $this->pdo->query("SELECT event_type FROM security_log")->fetchColumn());
    }

    public function testFehlendeTabelleWirftNicht(): void
    {
        $this->pdo->exec("DROP TABLE security_log");
        $this->assertSame(0, securityLogKuerzen($this->pdo));
    }
}
