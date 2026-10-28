<?php
/**
 * helpers_liste.php - Abfragen der Hauptliste und der Raumansicht (index.php)
 *
 * Bis 4.6 baute index.php diese Abfragen im Seitenskript zusammen. Genau dort
 * lagen die Luecken bei "nur eigene" (bis 4.3.32 galt die Einstellung in der
 * Raumansicht nicht) - und pruefen liess sich das nur von Hand auf einer
 * Testinstanz. Der Text der Abfragen ist unveraendert aus index.php
 * uebernommen; index.php ruft die Funktionen an den alten Stellen auf.
 * Getestet in tests/NurEigeneTest.php und tests/WerteTest.php.
 *
 * Braucht buildSearchQuery() aus helpers_search.php.
 */

if (!function_exists('vsListeAbfragen')) {
    /**
     * Abfragen der Hauptliste: Daten (ohne ORDER BY/LIMIT, die haengt
     * index.php an), Gesamtzahl fuer die Seitenaufteilung und Preise fuer
     * die Summe im Kopf.
     *
     * @return array{liste: array{0: string, 1: array}, anzahl: array{0: string, 1: array}, statistik: array{0: string, 1: array}}
     */
    function vsListeAbfragen(array $currentFilters, bool $showHidden, bool $onlyOwnItems, string $username, int $filter_raum, int $filter_position): array {
        $sql = "SELECT w.*, o.name as raum_name, k.name as kategorie_name,
               sp.name AS standort_position_name, sr.name AS standort_name
        FROM wertsachen w
        LEFT JOIN raeume o ON w.raum_id = o.id
        LEFT JOIN kategorien k ON w.kategorie_id = k.id
        LEFT JOIN positionen sp ON w.position_id = sp.id
        LEFT JOIN standorte sr ON sr.id = COALESCE(sp.raum_id, w.standort_id)
        WHERE 1=1";

        // Filter für verborgene Items (Standard: nur sichtbare anzeigen)
        if (!$showHidden) {
            $sql .= " AND (w.hidden = 0 OR w.hidden IS NULL)";
        }

        $params = [];

        // Only-own-items Filter
        if ($onlyOwnItems) {
            $sql .= " AND w.erstellt_von = ?";
            $params[] = $username;
        }

        // Erweiterte Filter hinzufügen
        $searchConditions = buildSearchQuery($currentFilters, $params);
        foreach ($searchConditions as $condition) {
            $sql .= " AND " . $condition;
        }

        // Raum-Filter
        if ($filter_raum) {
            $sql .= " AND w.raum_id = ?";
            $params[] = $filter_raum;
        }
        if ($filter_position) {
            $sql .= " AND w.position_id = ?";
            $params[] = $filter_position;
        }

        // Gesamtzahl für Pagination (mit erweiterten Filtern)
        $count_sql = "SELECT COUNT(*) as total FROM wertsachen w
                  LEFT JOIN positionen sp ON w.position_id = sp.id
                  LEFT JOIN standorte sr ON sr.id = COALESCE(sp.raum_id, w.standort_id)
                  WHERE 1=1";

        // Hidden-Filter auch für Count
        if (!$showHidden) {
            $count_sql .= " AND (w.hidden = 0 OR w.hidden IS NULL)";
        }

        // Only-own Filter auch für Count
        if ($onlyOwnItems) {
            $count_sql .= " AND w.erstellt_von = ?";
        }

        $count_params = $onlyOwnItems ? [$username] : [];

        // Erweiterte Filter auch für Count
        $countConditions = buildSearchQuery($currentFilters, $count_params);
        foreach ($countConditions as $condition) {
            $count_sql .= " AND " . $condition;
        }

        // Standort-Filter auch für Count
        if ($filter_raum) {
            $count_sql .= " AND w.raum_id = ?";
            $count_params[] = $filter_raum;
        }
        if ($filter_position) {
            $count_sql .= " AND w.position_id = ?";
            $count_params[] = $filter_position;
        }

        // Für Statistiken ALLE gefilterten Werte holen (ohne LIMIT)
        $stats_sql = "SELECT preis FROM wertsachen w WHERE 1=1";

        // Hidden-Filter auch für Stats
        if (!$showHidden) {
            $stats_sql .= " AND (w.hidden = 0 OR w.hidden IS NULL)";
        }

        // Only-own Filter auch für Stats
        if ($onlyOwnItems) {
            $stats_sql .= " AND w.erstellt_von = ?";
        }

        $stats_params = $onlyOwnItems ? [$username] : [];

        // Erweiterte Filter auch für Stats
        $statsConditions = buildSearchQuery($currentFilters, $stats_params);
        foreach ($statsConditions as $condition) {
            $stats_sql .= " AND " . $condition;
        }

        // Raum- und Positionsfilter wie bei Liste und Anzahl. Fehlten bis
        // 4.6: Mit Raumfilter zeigte der Kopf die Summe aller Raeume
        // (tests/TESTFUNDE.md, FUND-04).
        if ($filter_raum) {
            $stats_sql .= " AND w.raum_id = ?";
            $stats_params[] = $filter_raum;
        }
        if ($filter_position) {
            $stats_sql .= " AND w.position_id = ?";
            $stats_params[] = $filter_position;
        }

        return [
            'liste'     => [$sql, $params],
            'anzahl'    => [$count_sql, $count_params],
            'statistik' => [$stats_sql, $stats_params],
        ];
    }
}

if (!function_exists('vsListenStatistik')) {
    /**
     * Summe, Durchschnitt und Hoechstwert fuer den Kopf der Liste, aus den
     * Zeilen der Statistik-Abfrage. $total_count ist die Gesamtzahl aus der
     * Anzahl-Abfrage. Gerechnet wird allein mit dem Kaufpreis (preis).
     *
     * @return array{0: float|int, 1: float|int, 2: mixed}  [Summe, Durchschnitt, Hoechstwert]
     */
    function vsListenStatistik(array $all_items, $total_count): array {
        $total_value = 0;
        $avg_value = 0;
        $max_value = 0;
        if ($total_count > 0) {
            foreach ($all_items as $item) {
                $total_value += floatval($item['preis']);
            }
            $avg_value = $total_value / $total_count;
            $max_value = max(array_column($all_items, 'preis'));
        }
        return [$total_value, $avg_value, $max_value];
    }
}

if (!function_exists('vsRaumansichtAbfrage')) {
    /**
     * Abfrage der Raumansicht (index.php?view=room), samt Sortierung.
     *
     * @return array{0: string, 1: array}
     */
    function vsRaumansichtAbfrage(array $currentFilters, bool $onlyOwnItems, string $username): array {
        $room_sql = "SELECT w.*, o.name as raum_name, k.name as kategorie_name
                 FROM wertsachen w
                 LEFT JOIN raeume o ON w.raum_id = o.id
                 LEFT JOIN kategorien k ON w.kategorie_id = k.id
                 WHERE (w.hidden = 0 OR w.hidden IS NULL)";
        $room_params = [];
        $roomConditions = buildSearchQuery($currentFilters, $room_params);
        foreach ($roomConditions as $c) { $room_sql .= " AND " . $c; }
        // "nur eigene" galt bis 4.3.32 in der Raumansicht nicht.
        if ($onlyOwnItems) {
            $room_sql .= " AND w.erstellt_von = ?";
            $room_params[] = $username;
        }
        $room_sql .= " ORDER BY o.name ASC, w.name ASC";
        return [$room_sql, $room_params];
    }
}
