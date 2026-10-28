<?php
/**
 * schadenfall_dokument.php — Dokumente zum Schadensfall (seit 4.4)
 *
 *   ?id=…&art=polizei       Liste fuer die Polizei: Foto, Bezeichnung,
 *                           Seriennummer, EAN, Merkmale, Kaufdatum, Wert
 *   ?id=…&art=versicherung  Schadensmeldung: Vertrag, Schaden, Begleitschreiben
 *                           (vor dem Drucken anpassbar), Tabelle mit Kauf-
 *                           und Zeitwert, Belegen, Summen
 *
 * Wie die uebrigen Exporte eine HTML-Seite, die der Browser als PDF druckt.
 * Recht claims_view; Gegenstaende, die der Benutzer nicht sehen darf (nur
 * eigene), fehlen in der Liste.
 */
require_once __DIR__ . '/db.php';
if (!function_exists('correctImageOrientation')) require_once __DIR__ . '/helpers.php';
requireLogin();
requirePermission('claims_view');

if (!vsMigration014Da()) {
    header('Location: schadenfall.php');
    exit;
}

$id  = (int)($_GET['id'] ?? 0);
$art = ($_GET['art'] ?? '') === 'versicherung' ? 'versicherung' : 'polizei';

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

$vers = $fall['versicherung_id']
    ? $db->selectOne("SELECT * FROM versicherungen WHERE id = ?", [(int)$fall['versicherung_id']])
    : null;

$zeilen = $db->select("SELECT w.*, k.name AS kategorie_name, r.name AS raum_name, g.zustand,
                              (SELECT COUNT(*) FROM dokumente d WHERE d.wertsache_id = w.id) AS belege,
                              (SELECT COUNT(*) FROM item_images i WHERE i.item_id = w.id) AS fotos
                       FROM schadensfall_gegenstaende g
                       JOIN wertsachen w ON w.id = g.wertsache_id
                       LEFT JOIN kategorien k ON k.id = w.kategorie_id
                       LEFT JOIN raeume r ON r.id = w.raum_id
                       WHERE g.fall_id = ? ORDER BY r.name, w.name", [$id]);
$gegenstaende = [];
$sumPreis = 0.0; $sumZeit = 0.0;
foreach ($zeilen as $z) {
    if (!darfGegenstand($z)) continue;
    $gegenstaende[] = $z;
    $sumPreis += (float)$z['preis'];
    $sumZeit  += vsZeitwert($z);
}

$e     = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$preis = fn($p) => function_exists('formatPriceLocalized') ? formatPriceLocalized((float)$p) : number_format((float)$p, 2, ',', '.') . ' €';
$datum = fn($d) => $d ? date('d.m.Y', strtotime($d)) : '–';
$lang  = preg_match('/^[a-z]{2}$/', $_SESSION['lang'] ?? '') ? $_SESSION['lang'] : (defined('APP_LANG') ? APP_LANG : 'de');
$titel = $art === 'polizei' ? t('claims_doc_police') : t('claims_doc_insurance');

// Vorschlag fuer das Begleitschreiben (wird im Browser angepasst, nicht gespeichert)
$anschreiben = sprintf(
    t('claims_letter_text'),
    $vers['vertragsnummer'] ?? '…',
    t('claims_art_' . $fall['art']),
    $datum($fall['datum']),
    count($gegenstaende),
    $preis($sumZeit)
);
if (!empty($fall['aktenzeichen'])) {
    // Satz zur Anzeige vor "Bitte bestaetigen ..." und den Gruss setzen
    // (die Vorlage hat in allen Sprachen fuenf Absaetze, der dritte nennt die Gegenstaende)
    $absaetze = explode("\n\n", $anschreiben);
    array_splice($absaetze, max(1, min(3, count($absaetze) - 1)), 0,
                 [sprintf(t('claims_letter_police'), $fall['aktenzeichen'])]);
    $anschreiben = implode("\n\n", $absaetze);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $e($lang); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title><?php echo $e($titel); ?></title>
<style>
    body { font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: #1a1a1a; background: #fff; margin: 0; padding: 24px; font-size: 13px; line-height: 1.45; }
    .seite { max-width: 980px; margin: 0 auto; }
    h1 { font-size: 20px; margin: 0 0 4px; }
    h2 { font-size: 15px; margin: 22px 0 8px; }
    .unter { color: #444; margin: 0 0 16px; }
    .leiste { display: flex; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; }
    .leiste button, .leiste a { font: inherit; padding: 8px 14px; border-radius: 6px; border: 1px solid #185fa5; background: #185fa5; color: #fff; cursor: pointer; text-decoration: none; }
    .leiste a.zweit { background: #fff; color: #185fa5; }
    .leiste :focus-visible, [contenteditable]:focus-visible { outline: 3px solid #e67700; outline-offset: 2px; }
    dl.kopf { display: grid; grid-template-columns: max-content 1fr; gap: 4px 16px; margin: 0; }
    dl.kopf dt { color: #444; }
    dl.kopf dd { margin: 0; font-weight: 600; }
    table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    th, td { border: 1px solid #999; padding: 6px; text-align: left; vertical-align: top; }
    th { background: #eef2f7; font-size: 12px; }
    td.zahl, th.zahl { text-align: right; white-space: nowrap; }
    td.foto { width: 74px; }
    td.foto img { width: 70px; height: 70px; object-fit: cover; display: block; }
    tfoot td { font-weight: 700; background: #f6f8fb; }
    .feld { border-bottom: 1px solid #555; min-height: 22px; }
    .felder { display: grid; grid-template-columns: max-content 1fr; gap: 10px 14px; margin-top: 8px; }
    .brief { white-space: pre-wrap; border: 1px dashed #888; padding: 12px; min-height: 120px; }
    .hinweis { color: #444; font-size: 12px; }
    .klein { font-size: 11px; color: #444; }
    @media print {
        body { padding: 0; }
        .leiste, .nur-bildschirm { display: none !important; }
        .brief { border: none; padding: 0; }
        tr { page-break-inside: avoid; }
    }
</style>
</head>
<body>
<main class="seite">
    <div class="leiste">
        <button type="button" onclick="window.print()"><?php echo $e(t('claims_print')); ?></button>
        <a class="zweit" href="schadenfall.php?id=<?php echo (int)$fall['id']; ?>"><?php echo $e(t('claims_back_to_case')); ?></a>
    </div>

    <h1><?php echo $e($titel); ?></h1>
    <p class="unter"><?php echo $e(t('claims_art_' . $fall['art'])); ?> · <?php echo $e($datum($fall['datum'])); ?>
        <?php echo $fall['aktenzeichen'] ? ' · ' . $e(t('claims_file_number')) . ': ' . $e($fall['aktenzeichen']) : ''; ?></p>

<?php if ($art === 'polizei'): ?>
    <h2><?php echo $e(t('claims_injured_party')); ?></h2>
    <p class="hinweis nur-bildschirm"><?php echo $e(t('claims_fill_by_hand')); ?></p>
    <div class="felder">
        <span><?php echo $e(t('claims_name')); ?></span><span class="feld"></span>
        <span><?php echo $e(t('claims_address')); ?></span><span class="feld"></span>
        <span><?php echo $e(t('claims_phone')); ?></span><span class="feld"></span>
    </div>
    <?php if (!empty($fall['beschreibung'])): ?>
    <h2><?php echo $e(t('claims_description')); ?></h2>
    <p style="white-space:pre-wrap"><?php echo $e($fall['beschreibung']); ?></p>
    <?php endif; ?>

    <h2><?php echo $e(sprintf(t('claims_stolen_items'), count($gegenstaende))); ?></h2>
    <table>
        <thead><tr>
            <th scope="col"><?php echo $e(t('claims_photo')); ?></th>
            <th scope="col"><?php echo $e(t('claims_item')); ?></th>
            <th scope="col"><?php echo $e(t('field_serial')); ?> / EAN</th>
            <th scope="col"><?php echo $e(t('claims_features')); ?></th>
            <th scope="col"><?php echo $e(t('claims_purchase_date')); ?></th>
            <th scope="col" class="zahl"><?php echo $e(t('claims_value')); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($gegenstaende as $g): ?>
            <tr>
                <td class="foto"><?php if (!empty($g['bild'])): ?><img src="upload/<?php echo rawurlencode($g['bild']); ?>" alt="<?php echo $e($g['name']); ?>"><?php endif; ?></td>
                <td><strong><?php echo $e($g['name']); ?></strong><br><span class="klein"><?php echo $e(trim(($g['kategorie_name'] ?? '') . ' · ' . ($g['raum_name'] ?? ''), ' ·')); ?> · <?php echo $e(t('claims_cond_' . $g['zustand'])); ?></span></td>
                <td><?php echo $e($g['seriennummer'] ?? ''); ?><?php echo !empty($g['barcode']) ? '<br><span class="klein">EAN ' . $e($g['barcode']) . '</span>' : ''; ?></td>
                <td style="white-space:pre-wrap"><?php echo $e(mb_substr((string)$g['notizen'], 0, 400)); ?></td>
                <td><?php echo $e($datum($g['kaufdatum'])); ?></td>
                <td class="zahl"><?php echo $preis(vsZeitwert($g)); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="5"><?php echo $e(t('claims_total')); ?></td><td class="zahl"><?php echo $preis($sumZeit); ?></td></tr></tfoot>
    </table>
    <p class="klein"><?php echo $e(t('claims_police_footer')); ?></p>

<?php else: ?>
    <h2><?php echo $e(t('claims_contract')); ?></h2>
    <dl class="kopf">
        <dt><?php echo $e(t('claims_insurance')); ?></dt><dd><?php echo $e(($vers['name'] ?? '–') . (!empty($vers['anbieter']) ? ' · ' . $vers['anbieter'] : '')); ?></dd>
        <dt><?php echo $e(t('claims_contract_number')); ?></dt><dd><?php echo $e($vers['vertragsnummer'] ?? '–'); ?></dd>
        <?php if (!empty($vers['adresse']) || !empty($vers['wohnort'])): ?>
        <dt><?php echo $e(t('claims_insured_location')); ?></dt><dd><?php echo $e(trim(($vers['adresse'] ?? '') . ', ' . ($vers['plz'] ?? '') . ' ' . ($vers['wohnort'] ?? ''), ' ,')); ?></dd>
        <?php endif; ?>
        <?php if (!empty($vers['versicherungssumme'])): ?>
        <dt><?php echo $e(t('claims_sum_insured')); ?></dt><dd><?php echo $preis($vers['versicherungssumme']); ?></dd>
        <?php endif; ?>
        <?php if (!empty($vers['selbstbeteiligung'])): ?>
        <dt><?php echo $e(t('claims_deductible_label')); ?></dt><dd><?php echo $preis($vers['selbstbeteiligung']); ?></dd>
        <?php endif; ?>
        <dt><?php echo $e(t('claims_kind')); ?></dt><dd><?php echo $e(t('claims_art_' . $fall['art'])); ?></dd>
        <dt><?php echo $e(t('claims_date')); ?></dt><dd><?php echo $e($datum($fall['datum'])); ?></dd>
        <?php if (!empty($fall['aktenzeichen'])): ?>
        <dt><?php echo $e(t('claims_file_number')); ?></dt><dd><?php echo $e($fall['aktenzeichen']); ?></dd>
        <?php endif; ?>
    </dl>

    <h2><?php echo $e(t('claims_letter')); ?></h2>
    <p class="hinweis nur-bildschirm" id="brief_hint"><?php echo $e(t('claims_letter_hint')); ?></p>
    <div class="brief" contenteditable="true" role="textbox" aria-multiline="true"
         aria-label="<?php echo $e(t('claims_letter')); ?>" aria-describedby="brief_hint"><?php echo $e($anschreiben); ?></div>

    <?php if (!empty($fall['beschreibung'])): ?>
    <h2><?php echo $e(t('claims_description')); ?></h2>
    <p style="white-space:pre-wrap"><?php echo $e($fall['beschreibung']); ?></p>
    <?php endif; ?>

    <h2><?php echo $e(sprintf(t('claims_affected_items'), count($gegenstaende))); ?></h2>
    <table>
        <thead><tr>
            <th scope="col"><?php echo $e(t('claims_photo')); ?></th>
            <th scope="col"><?php echo $e(t('claims_item')); ?></th>
            <th scope="col"><?php echo $e(t('claims_condition')); ?></th>
            <th scope="col"><?php echo $e(t('claims_purchase_date')); ?></th>
            <th scope="col" class="zahl"><?php echo $e(t('claims_purchase_price')); ?></th>
            <th scope="col" class="zahl"><?php echo $e(t('claims_current_value')); ?></th>
            <th scope="col"><?php echo $e(t('claims_evidence')); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($gegenstaende as $g): ?>
            <tr>
                <td class="foto"><?php if (!empty($g['bild'])): ?><img src="upload/<?php echo rawurlencode($g['bild']); ?>" alt="<?php echo $e($g['name']); ?>"><?php endif; ?></td>
                <td><strong><?php echo $e($g['name']); ?></strong><?php echo !empty($g['seriennummer']) ? '<br><span class="klein">' . $e(t('field_serial')) . ': ' . $e($g['seriennummer']) . '</span>' : ''; ?></td>
                <td><?php echo $e(t('claims_cond_' . $g['zustand'])); ?></td>
                <td><?php echo $e($datum($g['kaufdatum'])); ?></td>
                <td class="zahl"><?php echo $preis($g['preis']); ?></td>
                <td class="zahl"><?php echo $preis(vsZeitwert($g)); ?></td>
                <td><?php echo $e(sprintf(t('claims_evidence_count'), (int)$g['belege'], max((int)$g['fotos'], empty($g['bild']) ? 0 : 1))); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr>
            <td colspan="4"><?php echo $e(t('claims_total')); ?></td>
            <td class="zahl"><?php echo $preis($sumPreis); ?></td>
            <td class="zahl"><?php echo $preis($sumZeit); ?></td>
            <td></td>
        </tr></tfoot>
    </table>
    <p class="klein"><?php echo $e(t('claims_insurance_footer')); ?></p>
<?php endif; ?>

    <p class="klein"><?php echo $e(sprintf(t('claims_created_with'), date('d.m.Y'))); ?></p>
</main>
</body>
</html>
