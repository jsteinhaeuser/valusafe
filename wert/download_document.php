<?php
// download_document.php - Dokument herunterladen
require_once 'db.php';
require_once 'helpers.php';
requireLogin();

// Rechte-Gate: dieselbe Berechtigung, die backend/permissions.php anbietet.
requirePermission('docs_view');

define('DOCUMENTS_DIR', __DIR__ . '/documents/');

$doc_id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$doc_id) {
    die('Ungültige ID');
}

// Dokumente-Feature muss fuer den Benutzer freigeschaltet sein
// (gleiche Pruefung wie in manage_documents.php).
$feature = $db->selectOne(
    "SELECT dokumente_aktiv FROM users WHERE username = ?",
    [$_SESSION['username'] ?? '']
);
if (!$feature || !$feature['dokumente_aktiv']) {
    Security::logSecurityEvent('document_access_denied', ['doc_id' => $doc_id, 'reason' => 'feature_off']);
    http_response_code(403);
    die('Dokumente-Feature ist nicht aktiviert');
}

try {
    // Ueber die zugehoerige Wertsache laden - sie entscheidet ueber den Zugriff.
    $doc = $db->selectOne(
        "SELECT d.*, w.erstellt_von
         FROM dokumente d
         JOIN wertsachen w ON d.wertsache_id = w.id
         WHERE d.id = ?",
        [$doc_id]
    );

    if (!$doc) {
        die('Dokument nicht gefunden');
    }

    // Sichtbarkeitsregel wie in index.php / helpers_search.php
    // (darfGegenstand: Vergleich wie MySQL, ohne Gross-/Kleinschreibung)
    if (!darfGegenstand($doc)) {
        Security::logSecurityEvent('document_access_denied', ['doc_id' => $doc_id, 'reason' => 'not_owner']);
        http_response_code(403);
        die('Kein Zugriff auf dieses Dokument');
    }
    
    $filepath = DOCUMENTS_DIR . $doc['dateiname'];
    
    if (!file_exists($filepath)) {
        die('Datei nicht gefunden');
    }
    
    // Prüfe ob Ansicht oder Download
    $view = isset($_GET['view']) && $_GET['view'] == 1;
    
    Security::logSecurityEvent($view ? 'document_viewed' : 'document_downloaded', [
        'doc_id' => $doc_id,
        'filename' => $doc['original_name']
    ]);
    
    // Header setzen - Dateiname stammt aus dem Upload, deshalb bereinigen:
    // Anfuehrungszeichen und Zeilenumbrueche wuerden den Header aufbrechen.
    $downloadName = str_replace(['"', "\r", "\n"], '', (string)($doc['original_name'] ?? 'dokument'));
    if ($downloadName === '') {
        $downloadName = 'dokument';
    }
    $mime = (string)($doc['dateityp'] ?? '');
    if (!preg_match('#^[a-z0-9][a-z0-9.+-]*/[a-z0-9][a-z0-9.+-]*$#i', $mime)) {
        $mime = 'application/octet-stream';
    }
    header('Content-Type: ' . $mime);

    if ($view) {
        // Ansicht im Browser (inline)
        header('Content-Disposition: inline; filename="' . $downloadName . '"');
    } else {
        // Download
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    }
    
    header('Content-Length: ' . filesize($filepath));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($filepath);
    exit;
    
} catch (PDOException $e) {
    Security::logSecurityEvent('document_download_error', [
        'doc_id' => $doc_id,
        'error' => $e->getMessage()
    ]);
    die('Fehler beim Laden');
}
?>
