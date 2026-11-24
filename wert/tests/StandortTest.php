<?php

namespace Tests;

/**
 * Raum, Standort und Position eines Gegenstands.
 *
 * Drei Tabellen mit verwirrenden Namen: wertsachen.raum_id zeigt auf
 * raeume, positionen.raum_id aber auf STANDORTE (backend/locations.php).
 * Bis 4.3.28 filterte index.php deshalb Positionen gegen die falsche
 * Tabelle; bis 4.4 ging ein Standort ohne Position beim Speichern verloren
 * (standortAusFormular(), helpers.php).
 *
 * Testdaten: Ring hat Position "Vitrine" (Standort Erdgeschoss), Laptop nur
 * den Standort Obergeschoss, Kette keinen Raum.
 */
class StandortTest extends GeschaeftskernTestCase
{
    /** Eine Zeile der Hauptliste (index.php) per ID. */
    private function listenZeile(int $id): array
    {
        $leer = ['search' => '', 'kategorie' => [], 'ort' => false, 'price_min' => false, 'price_max' => false,
                 'date_from' => '', 'date_to' => '', 'has_image' => false, 'has_docs' => false];
        [$sql, $params] = vsListeAbfragen($leer, true, false, 'admin', 0, 0)['liste'];
        foreach ($this->db->select($sql, $params) as $zeile) {
            if ((int)$zeile['id'] === $id) {
                return $zeile;
            }
        }
        $this->fail("Gegenstand $id fehlt in der Liste");
    }

    public function testListeZeigtRaumUndStandortDerPosition(): void
    {
        $ring = $this->listenZeile(1);
        $this->assertSame('Wohnzimmer', $ring['raum_name']);
        $this->assertSame('Vitrine', $ring['standort_position_name']);
        $this->assertSame('Erdgeschoss', $ring['standort_name']);
        $this->assertSame('Schmuck', $ring['kategorie_name']);
    }

    public function testStandortOhnePosition(): void
    {
        $laptop = $this->listenZeile(3);
        $this->assertSame('Büro', $laptop['raum_name']);
        $this->assertNull($laptop['standort_position_name']);
        $this->assertSame('Obergeschoss', $laptop['standort_name']);

        // Ohne Raum und ohne Standort: alles leer, Zeile bleibt in der Liste
        $kette = $this->listenZeile(6);
        $this->assertNull($kette['raum_name']);
        $this->assertNull($kette['standort_name']);
    }

    public function testPositionGehtVorGespeichertemStandort(): void
    {
        // Von Hand widerspruechlich gespeichert: Position im Erdgeschoss,
        // standort_id Obergeschoss. Die Liste folgt der Position.
        $this->pdo->exec("UPDATE wertsachen SET standort_id = 2 WHERE id = 1");
        $this->assertSame('Erdgeschoss', $this->listenZeile(1)['standort_name']);

        // Position geloescht: dann gilt der gespeicherte Standort
        $this->pdo->exec("DELETE FROM positionen WHERE id = 1");
        $ring = $this->listenZeile(1);
        $this->assertNull($ring['standort_position_name']);
        $this->assertSame('Obergeschoss', $ring['standort_name']);
    }

    public function testRaumfilterUndPositionsfilter(): void
    {
        $leer = ['search' => '', 'kategorie' => [], 'ort' => false, 'price_min' => false, 'price_max' => false,
                 'date_from' => '', 'date_to' => '', 'has_image' => false, 'has_docs' => false];
        [$sql, $params] = vsListeAbfragen($leer, false, false, 'admin', 2, 0)['liste'];
        $this->assertSame(['Kamera', 'Laptop'], self::namen($this->db->select($sql . ' ORDER BY w.name', $params)));

        [$sql, $params] = vsListeAbfragen($leer, false, false, 'admin', 0, 1)['liste'];
        $this->assertSame(['Ring'], self::namen($this->db->select($sql, $params)));

        // Suchfilter "ort" (Auswahl in der Suchkarte) wirkt auf denselben Raum
        [$sql, $params] = vsListeAbfragen(['ort' => 1] + $leer, false, false, 'admin', 0, 0)['liste'];
        $this->assertSame(['Ring', 'Uhr'], self::namen($this->db->select($sql . ' ORDER BY w.name', $params)));
    }

    public function testEinzelansichtLaedtRaumUndKategorie(): void
    {
        // loadWertsachenWithDetails(): edit.php und ajax_handler.php
        $ring = loadWertsachenWithDetails($this->db, 1);
        $this->assertSame('Ring', $ring['name']);
        $this->assertSame('Wohnzimmer', $ring['ort_name']);
        $this->assertSame('Schmuck', $ring['kategorie_name']);
        $this->assertSame(1, (int)$ring['position_id']);

        $bild = loadWertsachenWithDetails($this->db, 7);
        $this->assertSame('Keller', $bild['ort_name']);
        $this->assertNull($bild['kategorie_name']);

        // Ohne ID: alle, nach Name (auch verborgene)
        $alle = loadWertsachenWithDetails($this->db);
        $this->assertSame(['Bild', 'Fernseher', 'Kamera', 'Kette', 'Laptop', 'Ring', 'Uhr'], self::namen($alle));
    }

    public function testStandortAusFormular(): void
    {
        // Position gewaehlt: ihr Standort gilt, auch gegen die Auswahl
        $this->assertSame([1, 1], standortAusFormular($this->db, ['standort_raum' => '2', 'position_id' => '1']));
        $this->assertSame([2, 2], standortAusFormular($this->db, ['position_id' => '2']));

        // Nur Standort, keine Position (bis 4.4 ging er verloren)
        $this->assertSame([2, null], standortAusFormular($this->db, ['standort_raum' => '2', 'position_id' => '']));

        // Unbekannte Position faellt weg, der Standort bleibt
        $this->assertSame([1, null], standortAusFormular($this->db, ['standort_raum' => '1', 'position_id' => '99']));

        // Unbekannter Standort faellt weg
        $this->assertSame([null, null], standortAusFormular($this->db, ['standort_raum' => '99']));

        // Nichts gewaehlt, Unsinn im Formular
        $this->assertSame([null, null], standortAusFormular($this->db, []));
        $this->assertSame([null, null], standortAusFormular($this->db, ['standort_raum' => '0', 'position_id' => 'abc']));
    }

    public function testPositionMitGeloeschtemStandort(): void
    {
        // Standort geloescht, Position noch da: die Position bleibt, der
        // Standort faellt weg (kein Verweis auf eine fehlende Zeile).
        $this->pdo->exec("DELETE FROM standorte WHERE id = 1");
        $this->assertSame([null, 1], standortAusFormular($this->db, ['position_id' => '1']));
    }
}
