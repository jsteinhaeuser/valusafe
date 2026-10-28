<?php
// export_csv.php
require_once 'db.php';
require_once 'helpers.php';
require_once __DIR__ . '/helpers_csv.php';
requireLogin();
// Export-Recht aus der Rechteverwaltung (bis 4.3.32 nicht geprueft).
requirePermission('export_csv');
// Bei "nur eigene" nur eigene Gegenstaende exportieren.
[$ownSql, $ownParams] = nurEigeneSql('w');

// Fehlerausgabe unterdrücken für sauberen CSV-Export
ini_set('display_errors', 0);
error_reporting(0);

try {
    // Laden und Schreiben seit 4.6 in helpers_csv.php (tests/CsvExportTest.php)
    $wertsachen = vsCsvExportLaden($db, $ownSql, $ownParams);
    
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
    
    vsCsvExportSchreiben($output, $wertsachen);
    
    fclose($output);
    
} catch (PDOException $e) {
    Security::logSecurityEvent('csv_export_error', [
        'error' => $e->getMessage()
    ]);
    die(t('error_export'));
}

exit;
?>
