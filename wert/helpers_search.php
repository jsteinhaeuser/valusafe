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
function getSearchStats($filters) {
    global $db;

    // Only-own-items prüfen
    $onlyOwn = false;
    try {
        $setting = $db->selectOne("SELECT setting_value FROM app_settings WHERE setting_key = 'only_own_items'");
        if (($setting['setting_value'] ?? '0') === '1') {
            // Admins und Benutzer mit sieht_alle=1 ausgenommen
            $currentUser = $db->selectOne("SELECT role, sieht_alle FROM users WHERE username = ?", [$_SESSION['username'] ?? '']);
            if ($currentUser && $currentUser['role'] !== 'admin' && !$currentUser['sieht_alle']) {
                $onlyOwn = true;
            }
        }
    } catch (Exception $e) {}

    // SQL Query mit Filtern aufbauen
    $sql = "SELECT COUNT(*) as count, SUM(preis) as total_value, AVG(preis) as avg_value,
                   SUM(CASE WHEN aktueller_wert IS NOT NULL THEN aktueller_wert ELSE preis END) as zeitwert
            FROM wertsachen w
            WHERE 1=1";
    $params = [];

    // Only-own-items Filter
    if ($onlyOwn) {
        $sql .= " AND w.erstellt_von = ?";
        $params[] = $_SESSION['username'] ?? '';
    }

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
