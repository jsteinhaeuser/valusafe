<?php
/**
 * helpers_search.php
 * Helper-Funktionen für die erweiterte Suche
 */

/**
 * Liest alle Such-Filter aus dem Request
 */
function getFiltersFromRequest() {
    return [
        'search' => trim($_GET['search'] ?? ''),
        'kategorie' => array_values(array_filter(array_map('intval', (array)($_GET['kategorie'] ?? [])))),
        'ort' => filter_var($_GET['ort'] ?? '', FILTER_VALIDATE_INT),
        'price_min' => filter_var($_GET['price_min'] ?? '', FILTER_VALIDATE_FLOAT),
        'price_max' => filter_var($_GET['price_max'] ?? '', FILTER_VALIDATE_FLOAT),
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'has_image' => !empty($_GET['has_image']),
        'has_docs' => !empty($_GET['has_docs']),
    ];
}

/**
 * Prüft ob Filter aktiv sind
 */
function hasActiveFilters($filters) {
    return !empty($filters['search']) ||
           !empty($filters['kategorie']) ||
           !empty($filters['ort']) ||
           !empty($filters['price_min']) ||
           !empty($filters['price_max']) ||
           !empty($filters['date_from']) ||
           !empty($filters['date_to']) ||
           $filters['has_image'] ||
           $filters['has_docs'];
}

/**
 * Berechnet Statistiken basierend auf den aktuellen Filtern
 */
function getSearchStats($filters, bool $showHidden = false, int $filterRaum = 0, int $filterPosition = 0) {
    global $db;

    // SQL Query mit Filtern aufbauen. Zeitwert nach der gemeinsamen Regel
    // (helpers_werte.php); bis 4.6 ersetzte hier der Kaufpreis nur einen
    // FEHLENDEN Zeitwert, nicht einen von 0 (TESTFUNDE.md, FUND-02).
    $sql = "SELECT COUNT(*) as count, SUM(preis) as total_value, AVG(preis) as avg_value,
                   SUM(" . vsZeitwertSql('w') . ") as zeitwert
            FROM wertsachen w
            WHERE 1=1";

    // Verborgene wie in der Liste (index.php?show_hidden=1). Bis 4.6 zaehlte
    // die Suchleiste sie immer mit: "7 Eintraege gefunden" ueber einer Liste
    // mit 6 (TESTFUNDE.md, FUND-03).
    if (!$showHidden) {
        $sql .= " AND (w.hidden = 0 OR w.hidden IS NULL)";
    }

    // Only-own-items: dieselbe Regel wie Liste und Einzelzugriffe
    // (nurEigeneSql, helpers.php). Bis 4.6 pruefte diese Funktion selbst und
    // zaehlte ALLE Gegenstaende, wenn der Benutzer nicht in users stand oder
    // die Abfrage scheiterte (tests/TESTFUNDE.md, FUND-07).
    [$ownSql, $params] = nurEigeneSql('w');
    $sql .= $ownSql;

    // Textsuche
    if (!empty($filters['search'])) {
        $sql .= " AND (w.name LIKE ? OR w.notizen LIKE ?)";
        $searchParam = "%{$filters['search']}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // Kategorie (Mehrfachwahl)
    if (!empty($filters['kategorie'])) {
        $ids = array_values(array_filter(array_map('intval', (array)$filters['kategorie'])));
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql .= " AND w.kategorie_id IN ($placeholders)";
            foreach ($ids as $id) $params[] = $id;
        }
    }
    
    // Ort
    if (!empty($filters['ort'])) {
        $sql .= " AND w.raum_id = ?";
        $params[] = $filters['ort'];
    }
    
    // Preis-Range
    if (!empty($filters['price_min']) && $filters['price_min'] !== false) {
        $sql .= " AND w.preis >= ?";
        $params[] = $filters['price_min'];
    }
    if (!empty($filters['price_max']) && $filters['price_max'] !== false) {
        $sql .= " AND w.preis <= ?";
        $params[] = $filters['price_max'];
    }
    
    // Datum-Range
    if (!empty($filters['date_from'])) {
        $sql .= " AND w.kaufdatum >= ?";
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $sql .= " AND w.kaufdatum <= ?";
        $params[] = $filters['date_to'];
    }
    
    // Nur mit Bild
    if ($filters['has_image']) {
        $sql .= " AND w.bild IS NOT NULL AND w.bild != ''";
    }
    
    // Nur mit Dokumenten
    if ($filters['has_docs']) {
        $sql .= " AND EXISTS (SELECT 1 FROM dokumente d WHERE d.wertsache_id = w.id)";
    }

    // Raum- und Positions-Chips der Liste (index.php?filter_raum=, filter_position=).
    // Bis 4.6 kannte die Suchleiste nur den Suchfilter "ort" (TESTFUNDE.md, HINWEIS-06).
    if ($filterRaum) {
        $sql .= " AND w.raum_id = ?";
        $params[] = $filterRaum;
    }
    if ($filterPosition) {
        $sql .= " AND w.position_id = ?";
        $params[] = $filterPosition;
    }
    
    try {
        $result = $db->selectOne($sql, $params);
        return [
            'count' => (int)($result['count'] ?? 0),
            'total_value' => (float)($result['total_value'] ?? 0),
            'avg_value' => (float)($result['avg_value'] ?? 0),
            'zeitwert' => (float)($result['zeitwert'] ?? 0),
        ];
    } catch (Exception $e) {
        error_log("Search stats error: " . $e->getMessage());
        return [
            'count' => 0,
            'total_value' => 0,
            'avg_value' => 0,
            'zeitwert' => 0,
        ];
    }
}

/**
 * Baut SQL WHERE-Clause basierend auf Filtern
 * Wird von index.php verwendet
 */
function buildSearchQuery($filters, &$params) {
    $conditions = [];
    
    // Textsuche
    if (!empty($filters['search'])) {
        $conditions[] = "(w.name LIKE ? OR w.notizen LIKE ?)";
        $searchParam = "%{$filters['search']}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // Kategorie (Mehrfachwahl)
    if (!empty($filters['kategorie'])) {
        $ids = array_values(array_filter(array_map('intval', (array)$filters['kategorie'])));
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $conditions[] = "w.kategorie_id IN ($placeholders)";
            foreach ($ids as $id) $params[] = $id;
        }
    }
    
    // Ort
    if (!empty($filters['ort'])) {
        $conditions[] = "w.raum_id = ?";
        $params[] = $filters['ort'];
    }
    
    // Preis-Range
    if (!empty($filters['price_min']) && $filters['price_min'] !== false) {
        $conditions[] = "w.preis >= ?";
        $params[] = $filters['price_min'];
    }
    if (!empty($filters['price_max']) && $filters['price_max'] !== false) {
        $conditions[] = "w.preis <= ?";
        $params[] = $filters['price_max'];
    }
    
    // Datum-Range
    if (!empty($filters['date_from'])) {
        $conditions[] = "w.kaufdatum >= ?";
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $conditions[] = "w.kaufdatum <= ?";
        $params[] = $filters['date_to'];
    }
    
    // Nur mit Bild
    if ($filters['has_image']) {
        $conditions[] = "w.bild IS NOT NULL AND w.bild != ''";
    }
    
    // Nur mit Dokumenten
    if ($filters['has_docs']) {
        $conditions[] = "EXISTS (SELECT 1 FROM dokumente d WHERE d.wertsache_id = w.id)";
    }
    
    return $conditions;
}
?>
