<?php
/**
 * helpers_dashboard.php - Kennzahlen des Dashboards (dashboard.php)
 *
 * Bis 4.6 standen die Abfragen im Seitenskript. Dort zeigte die Seite bis
 * 4.3.32 Summen ALLER Gegenstaende trotz "nur eigene", und Zeitwert und
 * Kategoriewert rechneten nach unterschiedlichen Regeln. Seit 4.6 gilt
 * ueberall vsZeitwertSql() (helpers_werte.php). Geprueft in
 * tests/WerteTest.php und tests/NurEigeneTest.php.
 * $ownSql/$ownParams kommen aus nurEigeneSql().
 *
 * Verborgene Gegenstaende zaehlen hier bewusst mit: Das Dashboard zeigt den
 * Inventarwert, und verborgen heisst nicht weg (Entscheidung 26.10.2026).
 */

require_once __DIR__ . '/helpers_werte.php';

if (!function_exists('vsDashboardKennzahlen')) {
    /**
     * Gesamtwert und Anzahl: total_items, total_value (Kaufpreise), avg_price,
     * total_zeitwert (Zeitwert nach vsZeitwertSql), items_bewertet
     * (aktueller_wert > 0), max_zeitwert (hoechster Zeitwert).
     * $ownSqlT ist der Zusatz ohne Tabellenkuerzel (nurEigeneSql('')).
     */
    function vsDashboardKennzahlen(PDO $pdo, string $ownSqlT, array $ownParamsT) {
        $zeitwert = vsZeitwertSql();
        $st = $pdo->prepare("
        SELECT
            COUNT(*) as total_items,
            COALESCE(SUM(preis), 0) as total_value,
            COALESCE(AVG(preis), 0) as avg_price,
            COALESCE(SUM($zeitwert), 0) as total_zeitwert,
            COUNT(CASE WHEN aktueller_wert IS NOT NULL AND aktueller_wert > 0 THEN 1 END) as items_bewertet,
            MAX($zeitwert) as max_zeitwert
        FROM wertsachen
        WHERE 1=1" . $ownSqlT . "
    ");
        $st->execute($ownParamsT);
        return $st->fetch();
    }
}

if (!function_exists('vsDashboardKategorien')) {
    /** Wert pro Kategorie (Zeitwert nach vsZeitwertSql). $ownSql mit Kuerzel w. */
    function vsDashboardKategorien(PDO $pdo, string $ownSql, array $ownParams): array {
        $zeitwert = vsZeitwertSql('w');
        $st = $pdo->prepare("
        SELECT
            k.name as kategorie,
            COUNT(w.id) as anzahl,
            COALESCE(SUM($zeitwert), 0) as wert
        FROM kategorien k
        LEFT JOIN wertsachen w ON k.id = w.kategorie_id" . $ownSql . "
        GROUP BY k.id, k.name
        ORDER BY wert DESC
    ");
        $st->execute($ownParams);
        return $st->fetchAll();
    }
}

if (!function_exists('vsDashboardTrend')) {
    /**
     * Veraenderung des Zeitwerts gegenueber $stichtag (Y-m-d, vor 30 Tagen).
     *
     * Bis 4.6 (FUND-09) zog die Seite den Zeitwert aus wert_historie von der
     * Summe der KAUFPREISE ab - das war kein Verlauf. Jetzt: fuer jeden
     * Gegenstand mit einem Historieneintrag bis zum Stichtag der heutige
     * Zeitwert gegen den damaligen. Gegenstaende ohne Historie gelten als
     * unveraendert; neu angelegte zaehlen damit nicht als Wertzuwachs.
     * Prozent bezogen auf den Gesamtzeitwert am Stichtag
     * ($zeitwertJetzt - Differenz). Kein Eintrag bis zum Stichtag: [0.0, null].
     *
     * Der Stichtag kommt von aussen (statt DATE_SUB/CURDATE in MySQL), damit
     * die Abfrage in SQLite pruefbar ist. $ownSql mit Kuerzel w.
     *
     * @return array{0: float, 1: ?float}  [Differenz in EUR, Prozent oder null]
     */
    function vsDashboardTrend(PDO $pdo, string $ownSql, array $ownParams, string $stichtag, float $zeitwertJetzt): array {
        $zeitwert = vsZeitwertSql('w');
        // Je Gegenstand der letzte Eintrag bis zum Stichtag; MAX(h.wert),
        // falls an einem Tag zwei Eintraege stehen (sonst doppelt gezaehlt).
        $st = $pdo->prepare("
            SELECT COALESCE(SUM($zeitwert), 0) AS jetzt, COALESCE(SUM(d.wert), 0) AS damals, COUNT(*) AS anzahl
            FROM (
                SELECT h.wertsache_id, MAX(h.wert) AS wert
                FROM wert_historie h
                JOIN (
                    SELECT wertsache_id, MAX(datum) AS max_datum
                    FROM wert_historie
                    WHERE datum <= ?
                    GROUP BY wertsache_id
                ) l ON l.wertsache_id = h.wertsache_id AND l.max_datum = h.datum
                GROUP BY h.wertsache_id
            ) d
            JOIN wertsachen w ON w.id = d.wertsache_id
            WHERE 1=1" . $ownSql . "
        ");
        $st->execute(array_merge([$stichtag], $ownParams));
        $z = $st->fetch(PDO::FETCH_ASSOC);
        if (!$z || (int)$z['anzahl'] === 0) {
            return [0.0, null];
        }
        $diff   = (float)$z['jetzt'] - (float)$z['damals'];
        $basis  = $zeitwertJetzt - $diff;
        return [$diff, $basis > 0 ? round($diff / $basis * 100, 1) : null];
    }
}
