<?php
/**
 * Versicherungsübersicht & PDF-Export (Frontend)
 * Version 3.14
 */

require_once 'db.php';
require_once 'helpers.php';
requireLogin();

header('Content-Type: text/html; charset=UTF-8');

$versId = isset($_GET['v']) ? (int)$_GET['v'] : 0;
$printMode = isset($_GET['print']);

// Alle Versicherungen oder eine bestimmte laden
if ($versId) {
    $rows = $db->select("SELECT * FROM versicherungen WHERE id=?", [$versId]);
    $versicherung = $rows[0] ?? null;
    if (!$versicherung) {
        header('Location: insurance.php');
        exit;
    }
    $versicherungen = [$versicherung];
} else {
    $versicherungen = $db->select("SELECT * FROM versicherungen ORDER BY name ASC");
}

// Items je Versicherung laden - bei "nur eigene" nur eigene (bis 4.3.32
// listete die Seite alle zugeordneten Gegenstaende jedes Benutzers).
[$ownSql, $ownParams] = nurEigeneSql('w');
foreach ($versicherungen as &$v) {
    $v['items'] = $db->select("
        SELECT w.*, k.name as kategorie, o.name as ort
        FROM wertsachen w
        LEFT JOIN kategorien k ON w.kategorie_id = k.id
        LEFT JOIN raeume o ON w.raum_id = o.id
        WHERE w.versicherung_id = ?" . $ownSql . "
        ORDER BY w.name ASC
    ", array_merge([$v['id']], $ownParams));
    $v['gesamtwert'] = array_sum(array_map(fn($i) => $i['aktueller_wert'] ?: $i['preis'], $v['items']));
    // Gegenstaende ohne aktueller_wert UND ohne preis gehen mit 0 in die Summe
    // ein. Der erfasste Gesamtwert ist damit eine Untergrenze, und ein gruener
    // Deckungsgrad liest sich sonst als Sicherheit, die die Daten nicht
    // hergeben. Deshalb wird ihre Anzahl mitgefuehrt und angezeigt.
    $v['ohne_wert'] = count(array_filter(
        $v['items'],
        fn($i) => !($i['aktueller_wert'] ?: $i['preis'])
    ));
}
unset($v);

/**
 * Bewertet die Deckung eines Vertrags: Versicherungssumme gegen den erfassten
 * Gesamtwert der zugeordneten Gegenstaende.
 *
 * Rueckgabe: ['stufe' => 'ohne_summe'|'unter'|'knapp'|'gedeckt',
 *             'prozent' => float|null, 'luecke' => float|null]
 * oder null, wenn es nichts zu vergleichen gibt (kein erfasster Wert).
 *
 * Verglichen wird auf dem GERUNDETEN Prozentwert, nicht auf dem Bruch. Sonst
 * koennte neben der Anzeige "90,0 %" eine rote Warnung stehen, weil der
 * ungerundete Wert 89,996 % betraegt - Text und Farbe wuerden sich
 * widersprechen.
 */
function deckungsBefund(array $v): ?array
{
    $summe = (float)($v['versicherungssumme'] ?? 0);
    if ($summe <= 0) {
        return ['stufe' => 'ohne_summe', 'prozent' => null, 'luecke' => null];
    }
    if (($v['gesamtwert'] ?? 0) <= 0) {
        return null; // Ohne erfassten Wert gibt es kein Verhaeltnis.
    }
    $prozent = round($summe / $v['gesamtwert'] * 100, 1);
    return [
        'stufe'   => $prozent < 90 ? 'unter' : ($prozent < 100 ? 'knapp' : 'gedeckt'),
        'prozent' => $prozent,
        'luecke'  => max(0, $v['gesamtwert'] - $summe),
    ];
}

/**
 * Der Deckungshinweis als HTML, fuer Bildschirm und Druckansicht.
 *
 * Feste Farbwerte statt der var(--vs-*)-Variablen: die Druckansicht baut ihr
 * eigenes Dokument ohne Stylesheet der Anwendung, dort waeren die Variablen
 * nicht definiert.
 */
function deckungsHinweis(array $v, bool $druck = false): string
{
    $befund   = deckungsBefund($v);
    $ohneWert = (int)($v['ohne_wert'] ?? 0);
    if ($befund === null && $ohneWert === 0) {
        return '';
    }

    $stufe = $befund['stufe'] ?? 'ohne_summe';
    $stile = [
        'ohne_summe' => ['#4b5563', '#f3f4f6', '#d1d5db'],
        'unter'      => ['#991b1b', '#fee2e2', '#fca5a5'],
        'knapp'      => ['#92400e', '#fef3c7', '#fcd34d'],
        'gedeckt'    => ['#166534', '#dcfce7', '#86efac'],
    ];
    if ($befund === null) {
        $stufe = 'ohne_summe';
    }
    [$vordergrund, $hintergrund, $rand] = $stile[$stufe];

    $zeilen = [];
    if ($befund !== null && $stufe === 'ohne_summe') {
        $zeilen[] = t('ins_cov_no_sum');
    } elseif ($befund !== null) {
        $schluessel = ['unter' => 'ins_cov_under', 'knapp' => 'ins_cov_tight', 'gedeckt' => 'ins_cov_ok'];
        $text = sprintf(t($schluessel[$stufe]), number_format($befund['prozent'], 1, ',', '.'));
        if ($befund['luecke'] > 0) {
            $text .= ' · ' . sprintf(t('ins_cov_gap'), formatPrice($befund['luecke']));
        }
        $zeilen[] = $text;
    }
    if ($ohneWert > 0) {
        $zeilen[] = sprintf(t('ins_cov_unvalued'), $ohneWert, count($v['items']));
    }
    if (!$zeilen) {
        return '';
    }

    $html = '<div style="margin:' . ($druck ? '8px 0' : '0 0 16px') . '; padding:'
          . ($druck ? '8px 10px' : '10px 14px') . '; border-radius:6px; background:' . $hintergrund
          . '; border:1px solid ' . $rand . '; color:' . $vordergrund . '; font-size:'
          . ($druck ? '11px' : '13px') . '; line-height:1.5;">';
    foreach ($zeilen as $i => $zeile) {
        $html .= '<div' . ($i ? ' style="margin-top:3px; opacity:.85;"' : '') . '>';
        if ($i === 0 && $befund !== null) {
            $html .= '<strong>' . htmlspecialchars(t('ins_cov_title')) . ':</strong> ';
        }
        $html .= htmlspecialchars($zeile) . '</div>';
    }
    return $html . '</div>';
}

$pageTitle = t('insurance_overview');
$exportDatum = date('d.m.Y');

if (!$printMode) {
    include 'header_next_page.php';
}
?>

<?php if ($printMode): ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?php echo t('insurance_overview'); ?> – <?php echo $exportDatum; ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; background: white; padding: 20px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .meta { color: #666; font-size: 11px; margin-bottom: 24px; }
        .vertrag { margin-bottom: 32px; page-break-inside: avoid; }
        .vertrag-header { background: var(--vs-surface-2); padding: 10px 14px; border-left: 4px solid var(--vs-accent); margin-bottom: 8px; }
        .vertrag-header h2 { font-size: 15px; margin-bottom: 2px; }
        .vertrag-header .meta2 { font-size: 11px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: var(--vs-accent); color: var(--vs-accent-text); padding: 7px 10px; text-align: left; font-size: 11px; }
        td { padding: 6px 10px; border-bottom: 1px solid #ddd; font-size: 11px; }
        tr:nth-child(even) td { background: var(--vs-surface-2); }
        .summe td { font-weight: bold; background: var(--vs-accent-light); }
        .gesamt { margin-top: 24px; padding: 12px 16px; background: var(--vs-accent-light); border-radius: 6px; font-size: 13px; }
        @media print {
            body { padding: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:20px;">
        <button onclick="window.print()" style="padding:8px 20px; background:var(--vs-accent); color:var(--vs-accent-text); border:none; border-radius:6px; cursor:pointer; font-size:14px;"><i class="ti ti-printer" aria-hidden="true"></i> <?php echo t('ins_print'); ?></button>
        <a href="javascript:history.back()" style="margin-left:12px; color:#666; text-decoration:none; font-size:13px;">← <?php echo t('btn_back'); ?></a>
    </div>

    <h1>🛡️ <?php echo t('insurance_overview'); ?></h1>
    <p class="meta"><?php echo sprintf(t('ins_created_on'), $exportDatum); ?> · <?php echo t('app_title'); ?></p>

<?php else: ?>

<main class="backend-main">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <h2 style="margin:0;">🛡️ <?php echo t('insurance_overview'); ?></h2>
        <div style="display:flex; gap:10px;">
            <a href="insurance.php?print=1<?php echo $versId ? '&v='.$versId : ''; ?>" target="_blank"
               style="padding:9px 18px; background:var(--vs-danger); color:white; border-radius:6px; text-decoration:none; font-weight:600;"><i class="ti ti-file-type-pdf" aria-hidden="true"></i> <?php echo t('btn_export_pdf'); ?></a>
            <?php if (hasPermission('admin')): ?>
            <a href="backend/insurance.php" style="padding:9px 18px; background:var(--vs-accent); color:white; border-radius:6px; text-decoration:none; font-weight:600;"><i class="ti ti-settings" aria-hidden="true"></i> <?php echo t('btn_manage'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($versicherungen)): ?>
        <div class="activity-timeline" style="text-align:center; padding:60px; color:#999;">
            <div style="font-size:56px; margin-bottom:16px;">🛡️</div>
            <p><?php echo t('ins_none_yet'); ?></p>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php
$gesamtAllerWert = 0;
foreach ($versicherungen as $v):
    $gesamtAllerWert += $v['gesamtwert'];
?>

<?php if ($printMode): ?>
    <div class="vertrag">
        <div class="vertrag-header">
            <h2>🛡️ <?php echo htmlspecialchars($v['name']); ?></h2>
            <div class="meta2">
                <?php if ($v['anbieter']): ?><?php echo t('ins_provider'); ?>: <?php echo htmlspecialchars($v['anbieter']); ?><?php endif; ?>
                <?php if ($v['vertragsnummer']): ?> · <?php echo t('ins_contract_no'); ?> <?php echo htmlspecialchars($v['vertragsnummer']); ?><?php endif; ?>
                <?php if ($v['praemie']): ?> · <?php echo t('ins_premium'); ?>: <?php echo sprintf(t('ins_per_year'), number_format($v['praemie'], 2, ',', '.') . ' €'); ?><?php endif; ?>
                <?php if ($v['vertragsbeginn']): ?> · <?php echo t('ins_start'); ?>: <?php echo date('d.m.Y', strtotime($v['vertragsbeginn'])); ?><?php endif; ?>
                <?php if ($v['laufzeit_bis']): ?> · <?php echo t('ins_until'); ?>: <?php echo date('d.m.Y', strtotime($v['laufzeit_bis'])); ?><?php endif; ?>
            </div>
        </div>

        <?php echo deckungsHinweis($v, true); ?>

        <?php
        // Vertragsdaten
        $vertragsDetails = [];
        if ($v['versicherungssumme']) $vertragsDetails[] = [t('ins_sum'), number_format($v['versicherungssumme'], 2, ',', '.') . ' €'];
        if ($v['selbstbeteiligung'])  $vertragsDetails[] = [t('ins_deductible'),  number_format($v['selbstbeteiligung'],  2, ',', '.') . ' €'];
        if ($v['zahlungsweise'])      $vertragsDetails[] = [t('ins_payment_mode'),      ucfirst($v['zahlungsweise'])];
        if ($v['kuendigungsfrist'])   $vertragsDetails[] = [t('ins_notice_period'),    htmlspecialchars($v['kuendigungsfrist'])];
        if (!empty($vertragsDetails)):
        ?>
        <table style="margin-bottom:8px;">
            <thead><tr><th colspan="2" style="background:var(--vs-accent-light);color:var(--vs-text);font-size:10px;letter-spacing:.5px;text-transform:uppercase;"><?php echo t('ins_contract_data'); ?></th></tr></thead>
            <tbody>
                <?php foreach ($vertragsDetails as $d): ?>
                <tr><td style="width:40%;color:#666;"><?php echo $d[0]; ?></td><td><?php echo $d[1]; ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php
        // Objekt-Daten
        $objektDetails = [];
        $adr = trim(($v['adresse'] ?? '') . ($v['plz'] || $v['wohnort'] ? ', ' . trim(($v['plz'] ?? '') . ' ' . ($v['wohnort'] ?? '')) : ''));
        if ($adr)                 $objektDetails[] = [t('ins_address'),      htmlspecialchars(trim($adr, ', '))];
        if ($v['gebaeudeart'])    $objektDetails[] = [t('ins_building_type'),   htmlspecialchars($v['gebaeudeart'])];
        if ($v['wohnflaeche_qm']) $objektDetails[] = [t('ins_living_area'),   number_format($v['wohnflaeche_qm'], 1, ',', '.') . ' m²'];
        if ($v['anzahl_zimmer'])  $objektDetails[] = [t('ins_rooms'),       $v['anzahl_zimmer']];
        if ($v['etage'] !== null && $v['etage'] !== '') $objektDetails[] = [t('ins_floor'), $v['etage'] == 0 ? t('ins_ground_floor') : sprintf(t('ins_floor_nth'), $v['etage'])];
        $objektDetails[]          =                  [t('ins_cellar'), !empty($v['keller']) ? t('ins_yes') : t('ins_no')];
        if (!empty($objektDetails)):
        ?>
        <table style="margin-bottom:8px;">
            <thead><tr><th colspan="2" style="background:var(--vs-success-light);color:var(--vs-text);font-size:10px;letter-spacing:.5px;text-transform:uppercase;"><?php echo t('ins_object'); ?></th></tr></thead>
            <tbody>
                <?php foreach ($objektDetails as $d): ?>
                <tr><td style="width:40%;color:#666;"><?php echo $d[0]; ?></td><td><?php echo $d[1]; ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php
        // Zusatzbausteine
        $bausteine = [];
        if (!empty($v['fahrraddiebstahl']))  $bausteine[] = '🚲 ' . t('ins_module_bike');
        if (!empty($v['glasbruch']))          $bausteine[] = '🪟 ' . t('ins_module_glass');
        if (!empty($v['elementarschaeden'])) $bausteine[] = '🌊 ' . t('ins_module_elemental');
        if (!empty($v['ueberspannung']))      $bausteine[] = '⚡ ' . t('ins_module_surge');
        if (!empty($bausteine)):
        ?>
        <table style="margin-bottom:8px;">
            <thead><tr><th style="background:var(--vs-warning-light);color:var(--vs-text);font-size:10px;letter-spacing:.5px;text-transform:uppercase;"><?php echo t('ins_modules'); ?></th></tr></thead>
            <tbody><tr><td><?php echo implode(' &nbsp;·&nbsp; ', $bausteine); ?></td></tr></tbody>
        </table>
        <?php endif; ?>

        <?php if (empty($v['items'])): ?>
            <p style="color:#999; padding:10px 0;"><?php echo t('ins_no_items'); ?></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php echo t('col_bezeichnung'); ?></th>
                        <th><?php echo t('col_kategorie'); ?></th>
                        <th><?php echo t('col_aufbewahrungsort'); ?></th>
                        <th style="text-align:right;"><?php echo t('ins_value_eur'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $nr = 1; foreach ($v['items'] as $item): ?>
                    <tr>
                        <td><?php echo $nr++; ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['kategorie'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($item['ort'] ?? '—'); ?></td>
                        <td style="text-align:right;"><?php echo number_format($item['aktueller_wert'] ?: $item['preis'], 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="summe">
                        <td colspan="4" style="text-align:right;"><?php echo t('col_gesamtwert'); ?></td>
                        <td style="text-align:right;"><?php echo number_format($v['gesamtwert'], 2, ',', '.'); ?> €</td>
                    </tr>
                </tbody>
            </table>
            <?php if ($v['notiz']): ?>
                <p style="font-size:11px; color:#666; margin-top:6px;"><?php echo t('ins_note'); ?>: <?php echo htmlspecialchars($v['notiz']); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

<?php else: ?>

    <div class="activity-timeline" style="margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px; margin-bottom:16px;">
            <div>
                <h3 style="margin:0;"><?php echo htmlspecialchars($v['name']); ?></h3>
                <p style="margin:4px 0 0; color:#666; font-size:13px;">
                    <?php if ($v['anbieter']): ?><?php echo htmlspecialchars($v['anbieter']); ?> · <?php endif; ?>
                    <?php if ($v['vertragsnummer']): ?><?php echo t('ins_contract_no'); ?> <?php echo htmlspecialchars($v['vertragsnummer']); ?> · <?php endif; ?>
                    <?php if ($v['praemie']): ?><?php echo t('ins_premium'); ?>: <?php echo sprintf(t('ins_per_year'), formatPrice($v['praemie'])); ?> · <?php endif; ?>
                    <?php if ($v['versicherungssumme']): ?><?php echo t('ins_sum_short'); ?>: <?php echo formatPrice($v['versicherungssumme']); ?> · <?php endif; ?>
                    <?php if ($v['laufzeit_bis']): ?>
                        <?php $abgelaufen = strtotime($v['laufzeit_bis']) < time(); ?>
                        <span style="color:<?php echo $abgelaufen ? 'var(--vs-danger)' : 'inherit'; ?>">
                            <?php echo t('ins_until'); ?>: <?php echo date('d.m.Y', strtotime($v['laufzeit_bis'])); ?>
                            <?php if ($abgelaufen): ?>⚠️ <?php echo t('ins_expired'); ?><?php endif; ?>
                        </span>
                    <?php endif; ?>
                </p>
                <?php
                $bausteine = [];
                if (!empty($v['fahrraddiebstahl']))  $bausteine[] = '🚲 ' . t('ins_module_bike');
                if (!empty($v['glasbruch']))          $bausteine[] = '🪟 ' . t('ins_module_glass');
                if (!empty($v['elementarschaeden'])) $bausteine[] = '🌊 ' . t('ins_module_elemental');
                if (!empty($v['ueberspannung']))      $bausteine[] = '⚡ ' . t('ins_module_surge');
                if (!empty($bausteine)):
                ?>
                <p style="margin:4px 0 0; font-size:12px; color:#888;"><?php echo implode(' · ', $bausteine); ?></p>
                <?php endif; ?>
                <?php if ($v['adresse'] || $v['wohnflaeche_qm']): ?>
                <p style="margin:4px 0 0; font-size:12px; color:#888;">
                    <?php if ($v['adresse']): ?>📍 <?php echo htmlspecialchars($v['adresse']); ?><?php if ($v['plz'] || $v['wohnort']): ?>, <?php echo trim($v['plz'] . ' ' . $v['wohnort']); ?><?php endif; ?><?php endif; ?>
                    <?php if ($v['wohnflaeche_qm']): ?> · <?php echo number_format($v['wohnflaeche_qm'], 1, ',', '.'); ?> m²<?php endif; ?>
                    <?php if ($v['anzahl_zimmer']): ?> · <?php echo $v['anzahl_zimmer']; ?> <?php echo t('ins_rooms'); ?><?php endif; ?>
                </p>
                <?php endif; ?>
            </div>
            <div style="text-align:right;">
                <div style="font-size:22px; font-weight:bold; color:var(--vs-success);"><?php echo formatPrice($v['gesamtwert']); ?></div>
                <div style="font-size:12px; color:#999;"><?php echo sprintf(t('ins_items_count'), count($v['items'])); ?></div>
            </div>
        </div>

        <?php echo deckungsHinweis($v); ?>

        <?php if (empty($v['items'])): ?>
            <p style="color:#999;"><?php echo t('ins_no_items'); ?></p>
        <?php else: ?>
            <table class="backend-table">
                <thead>
                    <tr>
                        <th><?php echo t('col_bezeichnung'); ?></th>
                        <th><?php echo t('col_kategorie'); ?></th>
                        <th><?php echo t('col_aufbewahrungsort'); ?></th>
                        <th style="width:160px;"><?php echo t('col_wert'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($v['items'] as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                        <td><span class="badge badge-secondary"><?php echo htmlspecialchars($item['kategorie'] ?? '—'); ?></span></td>
                        <td><?php echo htmlspecialchars($item['ort'] ?? '—'); ?></td>
                        <td><strong style="color:var(--vs-success);"><?php echo formatPrice($item['aktueller_wert'] ?: $item['preis']); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background:var(--bg-color); font-weight:bold;">
                        <td colspan="3"><?php echo t('col_gesamtwert'); ?></td>
                        <td style="color:var(--vs-success); font-size:16px;"><?php echo formatPrice($v['gesamtwert']); ?></td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php endif; ?>
<?php endforeach; ?>

<?php if ($printMode): ?>
    <?php if (count($versicherungen) > 1): ?>
    <div class="gesamt">
        <strong><?php echo t('ins_total_all'); ?>: <?php echo number_format($gesamtAllerWert, 2, ',', '.'); ?> €</strong>
    </div>
    <?php endif; ?>
    <p style="margin-top:30px; font-size:10px; color:#aaa; border-top:1px solid #eee; padding-top:8px;">
        <?php echo sprintf(t('ins_doc_created_on'), $exportDatum); ?> · <?php echo t('app_title'); ?>
    </p>
</body>
</html>

<?php else: ?>
</main>

<style>
.backend-table { width:100%; border-collapse:collapse; margin-top:12px; }
.backend-table thead tr { background:var(--bg-color); border-bottom:2px solid var(--border-color); }
.backend-table th, .backend-table td { padding:12px; text-align:left; }
.backend-table tbody tr { border-bottom:1px solid var(--border-color); transition:background 0.2s; }
.backend-table tbody tr:hover { background:var(--bg-color); }
.badge { padding:4px 10px; border-radius:12px; font-size:11px; font-weight:600; display:inline-block; }
.badge-secondary { background:var(--vs-surface-2); color:var(--vs-text-muted); }
</style>

<?php include 'footer_next_page.php'; ?>
<?php endif; ?>
