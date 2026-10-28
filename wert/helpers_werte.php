<?php
/**
 * helpers_werte.php - EINE Regel fuer den Zeitwert eines Gegenstands.
 *
 * Bis 4.6 rechnete jede Seite selbst (tests/TESTFUNDE.md, FUND-02): die
 * Zeitwert-Kachel des Dashboards nur mit gepflegten Zeitwerten, Suchleiste,
 * Kategorien, Versicherung und Schadensfall mit dem Kaufpreis als Ersatz -
 * und einen Zeitwert von 0 zaehlte "x von y bewertet" als nicht bewertet,
 * waehrend die Summen ihn als 0 EUR uebernahmen. Unter derselben
 * Beschriftung standen so verschiedene Betraege.
 *
 * Regel (Entscheidung Joerg, 26.10.2026): Zeitwert = aktueller_wert, wenn er
 * groesser als 0 ist, sonst der Kaufpreis. 0 heisst "nicht bewertet" - wie in
 * der Kachel "x von y bewertet".
 *
 * Achtung bei eigenen Vergleichen: MySQL liefert decimal als Zeichenkette.
 * "0.00" ist in PHP WAHR - `aktueller_wert ?: preis` behielt deshalb auf dem
 * Server einen Zeitwert von 0. Darum hier ausdruecklich (float) > 0.
 *
 * Ohne Abhaengigkeiten, damit auch backend/ (laedt helpers.php nicht) sie
 * einbinden kann. Getestet in tests/WerteTest.php.
 */

if (!function_exists('vsZeitwert')) {
    /** Zeitwert eines Gegenstands (Zeile mit aktueller_wert und preis). */
    function vsZeitwert(array $item): float {
        $aktuell = (float)($item['aktueller_wert'] ?? 0);
        return $aktuell > 0 ? $aktuell : (float)($item['preis'] ?? 0);
    }
}

if (!function_exists('vsHatZeitwert')) {
    /** Ist ein Zeitwert gepflegt ("bewertet")? */
    function vsHatZeitwert(array $item): bool {
        return (float)($item['aktueller_wert'] ?? 0) > 0;
    }
}

if (!function_exists('vsZeitwertSql')) {
    /**
     * Dieselbe Regel als SQL-Ausdruck, fuer SUM(), MAX() und ORDER BY.
     * $alias = Tabellenkuerzel von wertsachen ('' fuer ohne).
     */
    function vsZeitwertSql(string $alias = ''): string {
        $a = $alias !== '' ? $alias . '.' : '';
        return "(CASE WHEN {$a}aktueller_wert > 0 THEN {$a}aktueller_wert ELSE COALESCE({$a}preis, 0) END)";
    }
}
