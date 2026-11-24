<?php

namespace Tests;

/**
 * Einstellung "nur eigene Gegenstaende" (only_own_items, helpers.php).
 *
 * Bis 4.3.32 wirkte sie nur in der Tabellenansicht: Raumansicht, Dashboard
 * und Exporte zeigten weiter alles, per Adresse kam man an fremde
 * Gegenstaende. Geprueft wird hier jede Stelle, an der die Liste Daten holt,
 * gegen dieselben Testdaten - mit echten Abfragen in SQLite.
 *
 * Leserin lea besitzt Ring (1), Laptop (3, angelegt als "Lea ") und Kette (6).
 */
class NurEigeneTest extends GeschaeftskernTestCase
{
    private const LEAS = ['Kette', 'Laptop', 'Ring'];

    private function leer(): array
    {
        return ['search' => '', 'kategorie' => [], 'ort' => false, 'price_min' => false,
                'price_max' => false, 'date_from' => '', 'date_to' => '',
                'has_image' => false, 'has_docs' => false];
    }

    /** Liste wie index.php: Abfrage aus vsListeAbfragen(), sortiert nach Name. */
    private function liste(bool $verborgene = false): array
    {
        $abfragen = vsListeAbfragen($this->leer(), $verborgene, userSeesOnlyOwnItems(), $_SESSION['username'] ?? '', 0, 0);
        [$sql, $params] = $abfragen['liste'];
        return $this->db->select($sql . " ORDER BY w.name", $params);
    }

    public function testEinstellungGiltNurFuerLeserUndEditorOhneSiehtAlle(): void
    {
        $erwartet = ['admin' => false, 'edith' => true, 'lea' => true, 'sven' => false, 'unbekannt' => true];
        foreach ($erwartet as $name => $nurEigene) {
            $_SESSION = ['username' => $name];
            $this->assertSame($nurEigene, _userSeesOnlyOwnItemsAbfrage($this->db), $name);
        }

        // Einstellung aus: niemand ist eingeschraenkt
        $this->pdo->exec("UPDATE app_settings SET setting_value = '0' WHERE setting_key = 'only_own_items'");
        $_SESSION = ['username' => 'lea'];
        $this->assertFalse(_userSeesOnlyOwnItemsAbfrage($this->db));
    }

    /**
     * Bei einem Datenbankfehler RESTRIKTIV (helpers.php, Docblock). Bis 4.6
     * (FUND-01) galt "nur eigene" dann als AUS: Database::selectOne() faengt
     * die Ausnahme selbst ab und liefert false, der catch-Zweig wurde nie
     * erreicht. Eine fehlende Zeile bedeutet dagegen weiter "aus".
     */
    public function testDatenbankfehlerSchraenktEinStattAllesZuZeigen(): void
    {
        $_SESSION = ['username' => 'lea'];
        $this->pdo->exec("DELETE FROM app_settings");
        $this->assertFalse(_userSeesOnlyOwnItemsAbfrage($this->db), 'Einstellung fehlt: aus');

        $this->pdo->exec("INSERT INTO app_settings (setting_key, setting_value) VALUES ('only_own_items', '1')");
        $this->pdo->exec("DROP TABLE users");
        $this->assertTrue(_userSeesOnlyOwnItemsAbfrage($this->db), 'users nicht lesbar');

        $this->pdo->exec("DROP TABLE app_settings");
        $this->assertTrue(_userSeesOnlyOwnItemsAbfrage($this->db), 'app_settings nicht lesbar');
        $_SESSION = ['username' => 'admin'];
        $this->assertTrue(_userSeesOnlyOwnItemsAbfrage($this->db), 'auch der Admin: Rolle nicht pruefbar');
    }

    public function testLeserSiehtInDerListeNurEigeneGegenstaende(): void
    {
        $this->anmelden('lea');

        $this->assertSame(self::LEAS, self::namen($this->liste()));

        // Gesamtzahl fuer die Seitenaufteilung passt dazu
        $abfragen = vsListeAbfragen($this->leer(), false, true, 'lea', 0, 0);
        $this->assertSame(3, (int)$this->db->selectOne(...$abfragen['anzahl'])['total']);

        // Admin sieht alle sichtbaren (Fernseher ist verborgen)
        $this->anmelden('admin');
        $this->assertSame(['Bild', 'Kamera', 'Kette', 'Laptop', 'Ring', 'Uhr'], self::namen($this->liste()));
        $this->assertCount(7, $this->liste(true));
    }

    public function testRaumansichtZeigtNurEigeneGegenstaende(): void
    {
        $this->anmelden('lea');
        [$sql, $params] = vsRaumansichtAbfrage($this->leer(), userSeesOnlyOwnItems(), 'lea');
        $zeilen = $this->db->select($sql, $params);

        // Sortiert nach Raum, dann Name; Kette hat keinen Raum (NULL zuerst)
        $this->assertSame(['Kette', 'Laptop', 'Ring'], self::namen($zeilen));
        $this->assertSame([null, 'Büro', 'Wohnzimmer'], array_column($zeilen, 'raum_name'));

        // Mit Suchbegriff bleibt die Einschraenkung bestehen (Reihenfolge der Parameter)
        $filter = ['search' => 'Uhr'] + $this->leer();
        [$sql, $params] = vsRaumansichtAbfrage($filter, true, 'lea');
        $this->assertSame([], $this->db->select($sql, $params));
        [$sql, $params] = vsRaumansichtAbfrage($filter, false, 'admin');
        $this->assertSame(['Uhr'], self::namen($this->db->select($sql, $params)));
    }

    public function testDashboardSummenZaehlenNurEigene(): void
    {
        $this->anmelden('lea');
        [$ownSqlT, $ownParamsT] = nurEigeneSql('');
        [$ownSql, $ownParams] = nurEigeneSql('w');
        $this->assertSame(' AND erstellt_von = ?', $ownSqlT);
        $this->assertSame(' AND w.erstellt_von = ?', $ownSql);
        $this->assertSame(['lea'], $ownParams);

        $kennzahlen = vsDashboardKennzahlen($this->pdo, $ownSqlT, $ownParamsT);
        $this->assertSame(3, (int)$kennzahlen['total_items']);
        $this->assertEqualsWithDelta(1400, (float)$kennzahlen['total_value'], 0.001);
        $this->assertEqualsWithDelta(1050, (float)$kennzahlen['total_zeitwert'], 0.001);
        $this->assertSame(2, (int)$kennzahlen['items_bewertet']);

        $kategorien = array_column(vsDashboardKategorien($this->pdo, $ownSql, $ownParams), null, 'kategorie');
        $this->assertSame(2, (int)$kategorien['Schmuck']['anzahl']);
        $this->assertSame(1, (int)$kategorien['Elektronik']['anzahl']);
        $this->assertSame(0, (int)$kategorien['Möbel']['anzahl']);

        // Suchleiste ueber der Liste (helpers_search.php) ebenso
        $stats = getSearchStats($this->leer());
        $this->assertSame(3, $stats['count']);
        $this->assertEqualsWithDelta(1400, $stats['total_value'], 0.001);
    }

    public function testCsvExportEnthaeltNurEigene(): void
    {
        $this->anmelden('lea');
        [$ownSql, $ownParams] = nurEigeneSql('w');
        $this->assertSame(self::LEAS, self::namen(vsCsvExportLaden($this->db, $ownSql, $ownParams)));

        $this->anmelden('sven');   // Leser mit "sieht alle"
        [$ownSql, $ownParams] = nurEigeneSql('w');
        $this->assertSame('', $ownSql);
        $this->assertCount(7, vsCsvExportLaden($this->db, $ownSql, $ownParams));
    }

    public function testFremdeIdWirdAbgewiesen(): void
    {
        // Muster aus edit.php und ajax_handler.php: laden, dann darfGegenstand()
        $this->anmelden('lea');
        $this->assertTrue(darfGegenstand(loadWertsachenWithDetails($this->db, 1)));
        $this->assertFalse(darfGegenstand(loadWertsachenWithDetails($this->db, 2)), 'Uhr gehoert admin');
        $this->assertFalse(darfGegenstand(loadWertsachenWithDetails($this->db, 7)), 'ohne Ersteller gilt als fremd');
        // Unbekannte ID: selectOne() liefert false, die Aufrufer pruefen das
        // vor darfGegenstand() (edit.php: !$item || !darfGegenstand($item))
        $this->assertFalse(loadWertsachenWithDetails($this->db, 999));
        $this->assertFalse(darfGegenstand(['id' => 1]), 'ohne erstellt_von: restriktiv');
        $this->assertFalse(darfGegenstand(null));

        // Admin und "sieht alle" duerfen fremde
        $this->anmelden('admin');
        $this->assertTrue(darfGegenstand(loadWertsachenWithDetails($this->db, 2)));
        $this->anmelden('sven');
        $this->assertTrue(darfGegenstand(loadWertsachenWithDetails($this->db, 2)));
    }

    public function testGrossKleinUndLeerzeichenAmEndeZaehlenNicht(): void
    {
        // Laptop wurde als "Lea " angelegt: Liste (SQL) und Einzelzugriff
        // (darfGegenstand in PHP) muessen sich einig sein - wie in MySQL.
        foreach (['lea', 'LEA', 'Lea  '] as $name) {
            $this->anmelden($name, 'read');
            $this->assertTrue(userSeesOnlyOwnItems(), $name);
            $this->assertContains('Laptop', self::namen($this->liste()), $name);
            $this->assertTrue(darfGegenstand(loadWertsachenWithDetails($this->db, 3)), $name);
            $this->assertTrue(darfGegenstand(loadWertsachenWithDetails($this->db, 1)), $name);
            $this->assertFalse(darfGegenstand(loadWertsachenWithDetails($this->db, 2)), $name);
        }

        // Leerzeichen am Anfang sind auch in MySQL ein anderer Name
        $this->anmelden(' lea', 'read');
        $this->assertSame([], $this->liste());
        $this->assertFalse(darfGegenstand(loadWertsachenWithDetails($this->db, 1)));
    }

    public function testMehrfachaktionVerwirftFremdeIds(): void
    {
        // Wie index.php (bulk_action): ausgewaehlte IDs gegen nurEigeneSql('')
        $this->anmelden('lea');
        $ausgewaehlt = [1, 2, 3, 5];
        [$ownSql, $ownParams] = nurEigeneSql('');
        $ph = implode(',', array_fill(0, count($ausgewaehlt), '?'));
        $erlaubt = array_map('intval', array_column(
            $this->db->select("SELECT id FROM wertsachen WHERE id IN ($ph)" . $ownSql, array_merge($ausgewaehlt, $ownParams)),
            'id'
        ));
        sort($erlaubt);
        $this->assertSame([1, 3], $erlaubt);
    }

    /**
     * Die Suchleiste ueber der Liste (getSearchStats) folgt derselben Regel
     * wie die Liste. Bis 4.6 (FUND-07) pruefte sie "nur eigene" selbst und
     * zaehlte fuer einen Benutzer, der nicht in users steht, ALLE
     * Gegenstaende statt keinen.
     */
    public function testSuchleisteFaelltBeiUnbekanntemBenutzerRestriktivAus(): void
    {
        $this->anmelden('geloescht', 'read');
        $this->assertTrue(userSeesOnlyOwnItems(), 'Zugriffspruefung: restriktiv');
        $this->assertSame([], $this->liste());

        $stats = getSearchStats($this->leer());
        $this->assertSame(0, $stats['count']);
        $this->assertEqualsWithDelta(0, $stats['total_value'], 0.001);

        // Mit Suchbegriff: Parameter in der richtigen Reihenfolge
        $this->anmelden('lea');
        // "r" trifft Ring, Uhr, Kamera, Fernseher - von lea nur den Ring
        $stats = getSearchStats(['search' => 'r'] + $this->leer());
        $this->assertSame(1, $stats['count']);
        $this->assertEqualsWithDelta(500, $stats['total_value'], 0.001);
    }
}
