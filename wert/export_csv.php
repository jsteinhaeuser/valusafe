<?php
// export_csv.php
require_once 'db.php';
require_once 'helpers.php';
requireLogin();
// Export-Recht aus der Rechteverwaltung (bis 4.3.32 nicht geprueft).
requirePermission('export_csv');
// Bei "nur eigene" nur eigene Gegenstaende exportieren.
[$ownSql, $ownParams] = nurEigeneSql('w');

// Fehlerausgabe unterdrücken für sauberen CSV-Export
ini_set('display_errors', 0);
error_reporting(0);

try {
    $sql = "SELECT w.*, o.name as ort_name, k.name as kategorie_name 
            FROM wertsachen w 
            LEFT JOIN raeume o ON w.raum_id = o.id 
            LEFT JOIN kategorien k ON w.kategorie_id = k.id 
            WHERE 1=1" . $ownSql . "
            ORDER BY w.name";
    
    $wertsachen = $db->select($sql, $ownParams);
    
    Security::logSecurityEvent('csv_export', [
        'count' => count($wertsachen)
    ]);
    
    // Alle vorherigen Ausgaben löschen
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Header für CSV-Download setzen
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="wertsachen_export_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output-Stream öffnen
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM für Excel-Kompatibilität
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header-Zeile - Tab als Trennzeichen
    fputcsv($output, [
        t('csv_header_id'),
        t('csv_header_name'),
        t('csv_header_location'),
        t('csv_header_category'),
        t('csv_header_purchase_date'),
        t('csv_header_price'),
        t('csv_header_notes'),
        t('csv_header_image'),
        t('csv_header_created_by'),
        t('csv_header_listed_on'),
        t('csv_header_created_at')
    ], "\t", '"', '\\');
    
    // Datenzeilen
    foreach ($wertsachen as $item) {
        fputcsv($output, [
            $item['id'],
            $item['name'],
            $item['ort_name'] ?? '',
            $item['kategorie_name'] ?? '',
            formatDateLocalized($item['kaufdatum']),
            formatPriceLocalized($item['preis']),
            $item['notizen'] ?? '',
            $item['bild'] ?? '',
            $item['erstellt_von'] ?? '',
            formatDateLocalized($item['gelistet_am'] ?? null),
            formatDateTimeLocalized($item['erstellt_am'] ?? null)
        ], "\t", '"', '\\');
    }
    
    fclose($output);
    
} catch (PDOException $e) {
    Security::logSecurityEvent('csv_export_error', [
        'error' => $e->getMessage()
    ]);
    die(t('error_export'));
}

exit;
?>
