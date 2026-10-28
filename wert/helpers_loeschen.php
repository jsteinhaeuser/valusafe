<?php
/**
 * helpers_loeschen.php - einen Gegenstand loeschen (delete.php)
 *
 * Bis 4.6 stand dieser Ablauf im Seitenskript delete.php. Damit sich pruefen
 * laesst, dass Loeschen Bilder und Dokumente mitnimmt und fremde Daten stehen
 * laesst (tests/LoeschenTest.php), steht er hier - unveraendert. Rechte
 * (canDelete), CSRF und "nur eigene" (darfGegenstand) prueft delete.php
 * vorher wie bisher.
 *
 * Braucht logActivity() (helpers.php), Security (security.php) und UPLOAD_DIR.
 */

// deleteAllItemImages(). Bis 4.6 band delete.php diese Datei nicht ein: der
// Aufruf unten stand hinter function_exists() und lief nie - die Bilder aus
// item_images blieben in upload/ liegen (TESTFUNDE.md FUND-08). Derselbe
// Fehler war in 4.3.33 im Mehrfach-Loeschen (index.php).
require_once __DIR__ . '/helpers_images.php';

if (!function_exists('vsGegenstandLoeschen')) {
    /**
     * Loescht Dokumente, Bilder und den Gegenstand selbst in einer
     * Transaktion und schreibt das Aktivitaetsprotokoll. Wirft bei einem
     * Fehler weiter (nach Rollback), wie bisher in delete.php.
     */
    function vsGegenstandLoeschen($db, array $item, int $id): void {
        // Daten vor Löschung für Activity Log speichern
        $deletedData = [
            'name' => $item['name'],
            'kategorie_id' => $item['kategorie_id'],
            'raum_id' => $item['raum_id'],
            'preis' => $item['preis'],
            'kaufdatum' => $item['kaufdatum']
        ];
        
        // Transaktion starten
        $db->beginTransaction();
        
        try {
            // Dokumente löschen (falls vorhanden)
            $docs = $db->select("SELECT dateiname FROM dokumente WHERE wertsache_id = ?", [$id]);
            foreach ($docs as $doc) {
                $docPath = __DIR__ . '/documents/' . $doc['dateiname'];
                if (file_exists($docPath)) {
                    unlink($docPath);
                }
            }
            $db->execute("DELETE FROM dokumente WHERE wertsache_id = ?", [$id]);

            // Werthistorie und Schadensfall-Zuordnung selbst loeschen. Bis 4.6
            // erledigte das nur ein Fremdschluessel mit ON DELETE CASCADE - der
            // fehlt laut Messung vom 27.10.2026 auf zwei der elf Instanzen
            // (tests/TESTFUNDE.md, FUND-05). schadensfall_gegenstaende gibt es
            // erst ab Migration 014; ohne sie wuerde das DELETE die ganze
            // Loeschung zurueckrollen.
            $db->execute("DELETE FROM wert_historie WHERE wertsache_id = ?", [$id]);
            if (function_exists('vsMigration014Da') && vsMigration014Da()) {
                $db->execute("DELETE FROM schadensfall_gegenstaende WHERE wertsache_id = ?", [$id]);
            }
        
            // Multi-Images löschen (falls vorhanden)
            if (function_exists('deleteAllItemImages')) {
                deleteAllItemImages($id);
            }
        
            // Bild löschen
            if ($item['bild'] && file_exists(UPLOAD_DIR . $item['bild'])) {
                if (unlink(UPLOAD_DIR . $item['bild'])) {
                    Security::logSecurityEvent('file_deleted', [
                        'filename' => $item['bild'],
                        'item_id' => $id
                    ]);
                }
            }
        
            // Datenbank-Eintrag löschen
            $db->execute("DELETE FROM wertsachen WHERE id = ?", [$id]);
        
            // Activity Log schreiben BEVOR Transaktion bestätigt wird
            logActivity('deleted', 'wertsachen', $id, $deletedData['name'], $deletedData, null);
        
            // Transaktion bestätigen
            $db->commit();
        
            Security::logSecurityEvent('item_deleted', [
                'item_id' => $id,
                'item_name' => $item['name']
            ]);
        
        } catch (Exception $e) {
            // Transaktion rückgängig machen
            $db->rollback();
        
            // Fehler loggen
            Security::logSecurityEvent('item_delete_error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
        
            throw $e;
        }
    }
}
