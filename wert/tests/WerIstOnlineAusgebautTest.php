<?php

namespace Tests;

/**
 * "Wer ist online" ausgebaut (27.10.2026, Entscheidung Joerg).
 *
 * Die Anzeige war tot: user_activity.php fuellte die Tabelle, aber seit dem
 * Wechsel von header.php auf header_next*.php band sie keine Seite mehr ein.
 * Statt IP-Adresse und aktuelle Seite bei jedem Aufruf wieder zu speichern,
 * ist sie entfernt. Die Tabelle bleibt (dsgvoKontoLoeschen() loescht darin),
 * Migration 015 leert sie.
 */
class WerIstOnlineAusgebautTest extends GeschaeftskernTestCase
{
    public function testNiemandSchreibtOderLiestUserActivityMehr(): void
    {
        $this->assertFileDoesNotExist(__DIR__ . '/../user_activity.php');

        $wurzel = realpath(__DIR__ . '/..');
        $treffer = [];
        $dateien = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($wurzel, \FilesystemIterator::SKIP_DOTS));
        foreach ($dateien as $datei) {
            $pfad = str_replace('\\', '/', substr($datei->getPathname(), strlen($wurzel) + 1));
            // changelog_data.php ist Text (die Chronik nennt die Tabelle), kein Code
            if ($datei->getExtension() !== 'php' || preg_match('#^(vendor|tests|backend/migrations|ValuSafe_Pakete)/#', $pfad) || $pfad === 'changelog_data.php') {
                continue;
            }
            $quelle = file_get_contents($datei->getPathname());
            if (preg_match('/(INSERT\s+INTO|FROM|UPDATE)\s+`?user_activity`?/i', $quelle, $m)
                && !($pfad === 'dsgvo_helpers.php' && stripos($m[0], 'FROM') !== false)) {
                $treffer[] = $pfad . ': ' . $m[0];
            }
            if (preg_match('/(require|include)(_once)?[^;]*user_activity\.php/', $quelle)) {
                $treffer[] = $pfad . ': bindet user_activity.php ein';
            }
        }
        // Erlaubt bleibt nur DELETE FROM user_activity in der DSGVO-Loeschung
        $this->assertSame([], $treffer);
        $this->assertStringContainsString('DELETE FROM user_activity WHERE user_id = ?',
            file_get_contents($wurzel . '/dsgvo_helpers.php'));
    }

    public function testMigration015LeertDieTabelleUndLaesstSieStehen(): void
    {
        $this->pdo->exec("INSERT INTO user_activity (user_id, username, role, last_seen, current_page, ip_address)
                          VALUES (1, 'admin', 'admin', '2026-01-01 10:00:00', 'index.php', '192.0.2.1'),
                                 (3, 'lea', 'read', '2026-01-02 10:00:00', 'edit.php', '192.0.2.7')");
        $migration = require __DIR__ . '/../backend/migrations/015_user_activity_leeren.php';

        $this->assertSame(['DELETE FROM `user_activity`'], $migration['statements']);
        foreach ($migration['statements'] as $sql) {
            $this->pdo->exec($sql);
        }
        $this->assertSame(0, $this->zahl("SELECT COUNT(*) FROM user_activity"));

        // Kontoloeschung braucht die Tabelle weiter
        $this->assertSame(null, dsgvoKontoLoeschen($this->pdo, 3));
        $this->assertSame(0, $this->zahl("SELECT COUNT(*) FROM users WHERE id = 3"));
    }
}
