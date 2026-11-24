<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use RateLimiter;
use Tests\Fakes\FakeRateLimitDatabase;

/**
 * Deckt die Brute-Force-Schutzlogik ab: genau diese Klasse hat bei den
 * Login-/Token-Bugs der letzten Roadmap-Punkte mehrfach eine Rolle gespielt.
 */
class RateLimiterTest extends TestCase
{
    private function makeLimiter(): array
    {
        $db = new FakeRateLimitDatabase();
        return [new RateLimiter($db), $db];
    }

    public function testFirstFailedAttemptIsRecordedAndReducesRemaining(): void
    {
        [$limiter] = $this->makeLimiter();

        $limiter->recordAttempt('1.2.3.4');

        $this->assertSame(4, $limiter->getRemainingAttempts('1.2.3.4'));
        $this->assertFalse($limiter->isLocked('1.2.3.4'));
    }

    public function testUnknownIpHasFullAttemptsAndIsNotLocked(): void
    {
        [$limiter] = $this->makeLimiter();

        $this->assertSame(5, $limiter->getRemainingAttempts('9.9.9.9'));
        $this->assertFalse($limiter->isLocked('9.9.9.9'));
    }

    public function testFifthAttemptTriggersLock(): void
    {
        [$limiter] = $this->makeLimiter();

        for ($i = 0; $i < 5; $i++) {
            $limiter->recordAttempt('10.0.0.1');
        }

        $this->assertTrue($limiter->isLocked('10.0.0.1'));
        $remaining = $limiter->getRemainingLockTime('10.0.0.1');
        $this->assertGreaterThan(0, $remaining);
        $this->assertLessThanOrEqual(900, $remaining);
    }

    public function testFourthAttemptDoesNotYetLock(): void
    {
        [$limiter] = $this->makeLimiter();

        for ($i = 0; $i < 4; $i++) {
            $limiter->recordAttempt('10.0.0.2');
        }

        $this->assertFalse($limiter->isLocked('10.0.0.2'));
        $this->assertSame(1, $limiter->getRemainingAttempts('10.0.0.2'));
    }

    public function testResetClearsLockAfterSuccessfulLogin(): void
    {
        [$limiter] = $this->makeLimiter();

        for ($i = 0; $i < 5; $i++) {
            $limiter->recordAttempt('10.0.0.3');
        }
        $this->assertTrue($limiter->isLocked('10.0.0.3'));

        $limiter->reset('10.0.0.3');

        $this->assertFalse($limiter->isLocked('10.0.0.3'));
        $this->assertSame(5, $limiter->getRemainingAttempts('10.0.0.3'));
    }

    public function testExpiredAttemptWindowResetsCounterInsteadOfAccumulating(): void
    {
        [$limiter, $db] = $this->makeLimiter();

        // Simuliert: 4 Fehlversuche vor 10 Minuten (Zeitfenster ist 5 Minuten) —
        // ein neuer Versuch jetzt darf NICHT auf 5 hochzählen und sperren,
        // sondern muss das Fenster als abgelaufen behandeln und auf 1 zurücksetzen.
        $tenMinutesAgo = date('Y-m-d H:i:s', time() - 600);
        $db->seedRow('10.0.0.4', 'login', 4, $tenMinutesAgo, $tenMinutesAgo);

        $limiter->recordAttempt('10.0.0.4');

        $this->assertFalse($limiter->isLocked('10.0.0.4'));
        $this->assertSame(4, $limiter->getRemainingAttempts('10.0.0.4'));
    }

    public function testAttemptsWithinWindowAccumulateTowardsLock(): void
    {
        [$limiter, $db] = $this->makeLimiter();

        // 3 Versuche vor einer Minute (noch innerhalb des 5-Minuten-Fensters)
        $oneMinuteAgo = date('Y-m-d H:i:s', time() - 60);
        $db->seedRow('10.0.0.5', 'login', 3, $oneMinuteAgo, $oneMinuteAgo);

        $limiter->recordAttempt('10.0.0.5');

        $this->assertSame(1, $limiter->getRemainingAttempts('10.0.0.5'));
        $this->assertFalse($limiter->isLocked('10.0.0.5'));
    }

    public function testDifferentIpsAreTrackedIndependently(): void
    {
        [$limiter] = $this->makeLimiter();

        for ($i = 0; $i < 5; $i++) {
            $limiter->recordAttempt('11.0.0.1');
        }

        $this->assertTrue($limiter->isLocked('11.0.0.1'));
        $this->assertFalse($limiter->isLocked('11.0.0.2'));
        $this->assertSame(5, $limiter->getRemainingAttempts('11.0.0.2'));
    }

    public function testDifferentActionsForSameIpAreTrackedIndependently(): void
    {
        [$limiter] = $this->makeLimiter();

        for ($i = 0; $i < 5; $i++) {
            $limiter->recordAttempt('12.0.0.1', 'login');
        }

        $this->assertTrue($limiter->isLocked('12.0.0.1', 'login'));
        $this->assertFalse($limiter->isLocked('12.0.0.1', 'password_reset'));
    }

    /** Grenzen wie login_2fa.php je Konto (OWASP-A07-001, 4.5.2). */
    private function makeKontoLimiter(): array
    {
        $db = new FakeRateLimitDatabase();
        return [new RateLimiter($db, 10, 3600, 86400), $db];
    }

    public function testCustomLimitsLockOnlyAfterTenAttempts(): void
    {
        [$limiter] = $this->makeKontoLimiter();

        for ($i = 0; $i < 9; $i++) {
            $limiter->recordAttempt('konto:7', '2fa');
        }
        $this->assertFalse($limiter->isLocked('konto:7', '2fa'));

        $limiter->recordAttempt('konto:7', '2fa');
        $this->assertTrue($limiter->isLocked('konto:7', '2fa'));
        $this->assertGreaterThan(900, $limiter->getRemainingLockTime('konto:7', '2fa'));
    }

    public function testCustomWindowKeepsCountingAcrossHours(): void
    {
        [$limiter, $db] = $this->makeKontoLimiter();

        // 9 Fehlversuche vor drei Stunden: beim 5-Minuten-Fenster vergessen,
        // beim 24-Stunden-Fenster des Kontos zaehlen sie weiter.
        $vorDreiStunden = date('Y-m-d H:i:s', time() - 3 * 3600);
        $db->seedRow('konto:8', '2fa', 9, $vorDreiStunden, $vorDreiStunden);

        $limiter->recordAttempt('konto:8', '2fa');

        $this->assertTrue($limiter->isLocked('konto:8', '2fa'));
    }

    public function testEveryFurtherAttemptAfterExpiredLockLocksAgain(): void
    {
        [$limiter, $db] = $this->makeKontoLimiter();

        // Sperre abgelaufen, 24-Stunden-Fenster laeuft noch
        $vorZweiStunden = date('Y-m-d H:i:s', time() - 2 * 3600);
        $vorEinerMinute = date('Y-m-d H:i:s', time() - 60);
        $db->seedRow('konto:9', '2fa', 10, $vorZweiStunden, $vorZweiStunden, $vorEinerMinute);
        $this->assertFalse($limiter->isLocked('konto:9', '2fa'));

        $limiter->recordAttempt('konto:9', '2fa');

        $this->assertTrue($limiter->isLocked('konto:9', '2fa'));
    }

    public function testDefaultLimitsUnchanged(): void
    {
        [$limiter] = $this->makeLimiter();

        $this->assertSame(5, $limiter->getRemainingAttempts('13.0.0.1'));
    }
}
