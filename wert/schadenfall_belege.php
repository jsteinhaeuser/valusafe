<?php
/**
 * schadenfall_belege.php — Fotos und Belege eines Schadensfalls als ZIP (4.4.1)
 *
 *   ?id=…   ein Ordner je betroffenem Gegenstand mit Fotos/ und Belege/,
 *           dazu Uebersicht.txt und Uebersicht.csv in der Benutzersprache
 *
 * Rechte wie schadenfall_dokument.php: claims_view, bei "nur eigene" nur
 * eigene Faelle und Gegenstaende. Belege zusaetzlich nur mit docs_view und
 * eingeschalteter Dokumente-Funktion (wie download_document.php) - sonst
 * enthaelt die ZIP nur die Fotos, und die Uebersicht sagt es.
 *
 * Fotos und PDFs werden ohne erneute Kompression gepackt (bringt nichts und
 * kostet Zeit). Die ZIP entsteht in einer Temp-Datei und wird nach dem
 * Senden geloescht. Fehler fuehren zurueck zu Schritt 3 mit Meldung.
 */
require_once __DIR__ . '/db.php';
if (!function_exists('correctImageOrientation')) require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/helpers_schadenfall_zip.php';
requireLogin();
requirePermission('claims_view');

const VS_ZIP_MAX_BYTES = 200 * 1024 * 1024;

if (!vsMigration014Da()) {
    header('Location: schadenfall.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$zurueck = function (string $code) use ($id) {
    header('Location: schadenfall.php?id=' . $id . '&schritt=3&zipfehler=' . rawurlencode($code));
    exit;
};

$fall = $db->selectOne("SELECT * FROM schadensfaelle WHERE id = ?", [$id]);
if ($fall && userSeesOnlyOwnItems()
    && strcasecmp(rtrim((string)$fall['erstellt_von']), rtrim($_SESSION['username'] ?? '')) !== 0) {
    $fall = null;
}
if (!$fall) {
    http_response_code(404);
    echo htmlspecialchars(t('claims_not_found'));
    exit;
}
if (!class_exists('ZipArchive')) {
    $zurueck('failed');
}

$vers = $fall['versicherung_id']
    ? $db->selectOne("SELECT * FROM versicherungen WHERE id = ?", [(int)$fall['versicherung_id']])
    : null;

$zeilen = $db->select("SELECT w.*, g.zustand
                       FROM schadensfall_gegenstaende g
                       JOIN wertsachen w ON w.id = g.wertsache_id
                       LEFT JOIN raeume r ON r.id = w.raum_id
                       WHERE g.fall_id = ? ORDER BY r.name, w.name", [$id]);
$gegenstaende = array_values(array_filter($zeilen, 'darfGegenstand'));
if (!$gegenstaende) {
    $zurueck('empty');
}

// Belege nur mit Recht und eingeschalteter Funktion (wie download_document.php)
$mitBelegen = false;
if (hasPermission('docs_view')) {
    $feature = $db->selectOne("SELECT dokumente_aktiv FROM users WHERE username = ?", [$_SESSION['username'] ?? '']);
    $mitBelegen = !empty($feature['dokumente_aktiv']);
}

$uploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : __DIR__ . '/upload/';
$docDir    = __DIR__ . '/documents/';

$fotosVon = function (array $g) use ($db, $uploadDir): array {
    $namen = array_column(
        $db->select("SELECT filename FROM item_images WHERE item_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC", [(int)$g['id']]),
        'filename'
    );
    if (!$namen && !empty($g['bild'])) {
        $namen = [$g['bild']];                     // aeltere Eintraege: nur wertsachen.bild
    }
    // basename: Dateinamen kommen aus der Datenbank, nie einen Pfad daraus bauen
    return array_map(fn($n) => $uploadDir . basename((string)$n), $namen);
};
$belegeVon = function (array $g) use ($db, $docDir): array {
    $out = [];
    foreach ($db->select("SELECT dateiname, original_name FROM dokumente WHERE wertsache_id = ? ORDER BY id", [(int)$g['id']]) as $d) {
        $out[] = ['pfad' => $docDir . basename((string)$d['dateiname']), 'name' => (string)$d['original_name']];
    }
    return $out;
};

$inhalt = vsSchadenfallZipInhalt($gegenstaende, $fotosVon, $belegeVon, $mitBelegen,
                                 t('claims_zip_photos'), t('claims_receipts'));
if (!$inhalt['dateien']) {
    $zurueck('empty');
}
if ($inhalt['bytes'] > VS_ZIP_MAX_BYTES) {
    $zurueck('too_large');
}

// ── Uebersicht ──────────────────────────────────────────────────────────────
$lang   = preg_match('/^[a-z]{2}$/', $_SESSION['lang'] ?? '') ? $_SESSION['lang'] : 'de';
$preis  = fn($p) => function_exists('formatPriceLocalized') ? formatPriceLocalized((float)$p) : number_format((float)$p, 2, ',', '.') . ' €';
$zahl   = fn($p) => number_format((float)$p, 2, $lang === 'en' ? '.' : ',', '');
$datum  = fn($d) => $d ? date('d.m.Y', strtotime($d)) : '–';

$txt  = t('claims_zip_title') . "\r\n" . str_repeat('=', 60) . "\r\n\r\n";
$txt .= t('claims_kind') . ': ' . t('claims_art_' . $fall['art']) . "\r\n";
$txt .= t('claims_date') . ': ' . $datum($fall['datum']) . "\r\n";
if (!empty($fall['aktenzeichen'])) $txt .= t('claims_file_number') . ': ' . $fall['aktenzeichen'] . "\r\n";
if ($vers) {
    $txt .= t('claims_insurance') . ': ' . trim(($vers['name'] ?? '') . ' ' . ($vers['anbieter'] ?? '')) . "\r\n";
    if (!empty($vers['vertragsnummer'])) $txt .= t('claims_contract_number') . ': ' . $vers['vertragsnummer'] . "\r\n";
}
$txt .= "\r\n";

$kopf = ['#', t('claims_item'), t('claims_condition'), t('field_serial'), t('claims_purchase_date'),
         t('claims_purchase_price'), t('claims_current_value'), t('claims_zip_photos'), t('claims_receipts'), t('claims_zip_folder')];
$csvZeilen = [];
$sumPreis = 0.0; $sumZeit = 0.0;
foreach ($gegenstaende as $i => $g) {
    $je = $inhalt['je'][$g['id']];
    $zeit = (float)($g['aktueller_wert'] ?: $g['preis']);
    $sumPreis += (float)$g['preis'];
    $sumZeit  += $zeit;
    $txt .= ($i + 1) . '. ' . $g['name'] . "\r\n"
          . '   ' . t('claims_condition') . ': ' . t('claims_cond_' . $g['zustand']) . "\r\n"
          . (!empty($g['seriennummer']) ? '   ' . t('field_serial') . ': ' . $g['seriennummer'] . "\r\n" : '')
          . '   ' . t('claims_purchase_date') . ': ' . $datum($g['kaufdatum'] ?? null)
          . ' · ' . t('claims_purchase_price') . ': ' . $preis($g['preis'])
          . ' · ' . t('claims_current_value') . ': ' . $preis($zeit) . "\r\n"
          . '   ' . sprintf(t('claims_evidence_count'), $je['belege'], $je['fotos'])
          . ' – ' . t('claims_zip_folder') . ': ' . $je['ordner'] . "\r\n\r\n";
    $csvZeilen[] = [$i + 1, $g['name'], t('claims_cond_' . $g['zustand']), $g['seriennummer'] ?? '',
                    $datum($g['kaufdatum'] ?? null), $zahl($g['preis']), $zahl($zeit), $je['fotos'], $je['belege'], $je['ordner']];
}
$txt .= t('claims_sum_purchase') . ': ' . $preis($sumPreis) . "\r\n";
$txt .= t('claims_sum_current') . ': ' . $preis($sumZeit) . "\r\n";
if (!$mitBelegen) $txt .= "\r\n" . t('claims_zip_no_docs') . "\r\n";
$txt .= "\r\n" . sprintf(t('claims_created_with'), date('d.m.Y H:i')) . "\r\n";
$csvZeilen[] = ['', t('claims_total'), '', '', '', $zahl($sumPreis), $zahl($sumZeit), '', '', ''];

// ── ZIP bauen ───────────────────────────────────────────────────────────────
$tmp = tempnam(sys_get_temp_dir(), 'vsz');
$zip = new ZipArchive();
if ($tmp === false || $zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
    if ($tmp) @unlink($tmp);
    $zurueck('failed');
}
$uebersicht = vsZipTeil(t('claims_zip_overview'), 40, 'Uebersicht');
$zip->addFromString($uebersicht . '.txt', $txt);
$zip->addFromString($uebersicht . '.csv', vsSchadenfallCsv($kopf, $csvZeilen));
$schonGepackt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif', 'pdf', 'zip', 'docx', 'xlsx'];
foreach ($inhalt['dateien'] as $ziel => $quelle) {
    $zip->addFile($quelle, $ziel);
    if (in_array(strtolower(pathinfo($ziel, PATHINFO_EXTENSION)), $schonGepackt, true)) {
        $zip->setCompressionName($ziel, ZipArchive::CM_STORE);
    }
}
if (!$zip->close() || !is_file($tmp)) {
    @unlink($tmp);
    $zurueck('failed');
}

Security::logSecurityEvent('claims_zip_downloaded', [
    'fall_id' => $id,
    'dateien' => count($inhalt['dateien']),
    'belege'  => $mitBelegen,
]);

// ── Senden ──────────────────────────────────────────────────────────────────
$name = 'Schadensfall_' . ($fall['datum'] ?: date('Y-m-d'))
      . (!empty($fall['aktenzeichen']) ? '_' . vsZipTeil((string)$fall['aktenzeichen'], 40, '') : '') . '.zip';
$ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);

session_write_close();                 // lange Downloads sperren die Sitzung nicht
while (ob_get_level() > 0) ob_end_clean();
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));
header('Content-Length: ' . filesize($tmp));
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
readfile($tmp);
@unlink($tmp);
exit;
