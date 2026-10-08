<?php
/**
 * bulk_delete.php - STILLGELEGT seit 4.3.33
 *
 * Wurde von der Oberflaeche nicht mehr aufgerufen (Mehrfach-Loeschen laeuft
 * ueber index.php, bulk_action=delete). Die alte Fassung hatte keinen
 * CSRF-Schutz, pruefte weder "Mehrere loeschen" noch "nur eigene" und
 * scheiterte nur zufaellig an der nicht vorhandenen Spalte "bezeichnung"
 * (mit SQL-Text in der Antwort). Die Datei kann vom Server entfernt werden.
 */
header('Content-Type: application/json');
http_response_code(410);
echo json_encode(['success' => false, 'error' => 'Gone']);
