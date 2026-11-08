<?php
// export_pdf_handover.php — Übergabeprotokoll
require_once 'db.php';
require_once 'helpers.php';
requireLogin();
// Export-Recht aus der Rechteverwaltung (bis 4.3.32 nicht geprueft).
requirePermission('export_pdf');
// Bei "nur eigene" nur eigene Gegenstaende exportieren.
[$ownSql, $ownParams] = nurEigeneSql('w');

// Alle Gegenstände gruppiert nach Ort laden
$protokoll_items = $db->select(
    "SELECT w.*, o.name as ort_name, k.name as kategorie_name
     FROM wertsachen w
     LEFT JOIN raeume o ON w.raum_id = o.id
     LEFT JOIN kategorien k ON w.kategorie_id = k.id
     WHERE (w.hidden = 0 OR w.hidden IS NULL)" . $ownSql . "
     ORDER BY o.name ASC, w.name ASC",
    $ownParams
);

$raum_gruppen = [];
foreach ($protokoll_items as $item) {
    $ort = $item['ort_name'] ?? '— ' . t('insx_no_location') . ' —';
    $raum_gruppen[$ort][] = $item;
}

$gesamtwert = array_sum(array_column($protokoll_items, 'preis'));

Security::logSecurityEvent('pdf_export_handover', [
    'count' => count($protokoll_items)
]);

// Formular-Daten
$show_print = isset($_POST['generate']);
$adresse    = trim($_POST['adresse']    ?? '');
$lage       = trim($_POST['lage']       ?? '');
$mieter     = trim($_POST['mieter']     ?? '');
$vermieter  = trim($_POST['vermieter']  ?? '');
$datum      = trim($_POST['datum']      ?? date('d.m.Y'));
$typ        = trim($_POST['typ']        ?? 'Übergabe');
$schlussel  = trim($_POST['schlussel']  ?? '');
$strom      = trim($_POST['strom']      ?? '');
$wasser     = trim($_POST['wasser']     ?? '');
$gas        = trim($_POST['gas']        ?? '');
$bemerkung  = trim($_POST['bemerkung']  ?? '');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$baseUrl  = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);

// Protokollart: Wert bleibt deutsch (Formular), Anzeige uebersetzt (4.9 / 2.7)
$hoTypLabel = function ($typ) {
    $k = ['Übergabe' => 'hox_type_uebergabe', 'Übernahme' => 'hox_type_uebernahme', 'Zwischenübergabe' => 'hox_type_zwischen'];
    return isset($k[$typ]) ? t($k[$typ]) : $typ;
};
$hoLang = $_SESSION['lang'] ?? 'de';

// Wenn Druckansicht: direkt ausgeben
if ($show_print):
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($hoLang); ?>">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars(t('hox_title')); ?> — <?php echo htmlspecialchars($adresse); ?></title>
<style>
@media print {
    .no-print { display: none !important; }
    @page { size: A4 portrait; margin: 12mm 12mm 15mm 12mm; }
    .page-break { page-break-after: always; }
    .avoid-break { page-break-inside: avoid; }
}

* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 10pt; color: #1a1a1a; background: white; }

/* ── Druckbuttons ── */
.no-print-bar {
    background: #f5f5f5; padding: 12px 15px;
    display: flex; gap: 10px; flex-wrap: wrap;
}
.btn-druck {
    background: #3498db; color: white; padding: 10px 22px;
    border: none; border-radius: 8px; font-size: 14px; cursor: pointer;
    text-decoration: none; display: inline-block;
}
.btn-druck.back { background: #95a5a6; }

/* ── Deckblatt ── */
.cover { padding: 10mm 0; }
.cover-header {
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 3px solid #2c3e50; padding-bottom: 8px; margin-bottom: 14px;
}
.cover-logo { font-size: 18pt; font-weight: 700; color: #2c3e50; }
.cover-logo span { font-size: 22pt; }
.cover-typ { font-size: 22pt; font-weight: 700; color: #2c3e50; text-align: center; margin: 10px 0 18px; }

.info-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
.info-table th {
    background: #2c3e50; color: white; padding: 6px 10px;
    font-size: 9pt; text-align: left; width: 30%;
}
.info-table td { padding: 6px 10px; border: 1px solid #ccc; font-size: 10pt; }
.info-table tr:nth-child(even) td { background: #f8f9fa; }

.section-title {
    background: #2c3e50; color: white; padding: 6px 12px;
    font-size: 10pt; font-weight: 700; margin: 14px 0 0 0;
}

.zaehler-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
.zaehler-table th { background: #ecf0f1; padding: 5px 10px; font-size: 9pt; border: 1px solid #ccc; }
.zaehler-table td { padding: 5px 10px; border: 1px solid #ccc; font-size: 10pt; }

.gesamt-box {
    background: #e8f5e9; border-left: 5px solid #27ae60;
    padding: 10px 15px; margin: 14px 0; border-radius: 4px;
    display: flex; justify-content: space-between; align-items: center;
}
.gesamt-value { font-size: 16pt; font-weight: 700; color: #27ae60; }

.bem-box {
    border: 1px solid #ccc; padding: 10px; min-height: 60px;
    border-radius: 4px; font-size: 10pt;
    background: <?php echo $bemerkung ? '#fffbf0' : 'white'; ?>;
}

/* ── Unterschriften ── */
.sig-section { margin-top: 20px; }
.sig-row { display: flex; gap: 24px; margin-top: 14px; }
.sig-box { flex: 1; }
.sig-name-line { border-bottom: 1px solid #aaa; height: 18px; margin-bottom: 4px; }
.sig-name-label { font-size: 7.5pt; color: #888; margin-bottom: 10px; display: block; }
.sig-line { border-bottom: 1.5px solid #333; margin-top: 36px; margin-bottom: 4px; }
.sig-label { font-size: 8pt; color: #666; }
.sig-date-row { display: flex; gap: 12px; margin-top: 10px; }
.sig-date-field { flex: 1; }
.sig-date-line { border-bottom: 1px solid #aaa; height: 18px; }
.sig-date-label { font-size: 7.5pt; color: #888; }

/* ── Inventar pro Raum ── */
.raum-section { margin-bottom: 16px; }
.raum-title {
    background: #34495e; color: white; padding: 7px 12px;
    font-size: 11pt; font-weight: 700; margin-bottom: 0;
    display: flex; justify-content: space-between; align-items: center;
}
.raum-title .raum-summe { font-size: 9pt; opacity: 0.85; font-weight: 400; }

.item-table { width: 100%; border-collapse: collapse; }
.item-table th {
    background: #ecf0f1; padding: 5px 8px; font-size: 8pt;
    text-align: left; border: 1px solid #ccc; font-weight: 700;
}
.item-table td { padding: 6px 8px; border: 1px solid #ddd; vertical-align: middle; font-size: 9pt; }
.item-table tr:nth-child(even) td { background: #fafafa; }

.item-img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; display: block; }
.item-no-img {
    width: 60px; height: 60px; background: #f0f0f0; border: 1px dashed #ccc;
    border-radius: 4px; display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #ccc;
}

/* ── Zustand-Checkboxen ── */
.zustand-boxes { display: flex; flex-direction: column; gap: 4px; }
.zustand-item { display: flex; align-items: center; gap: 5px; font-size: 8.5pt; }
.check-box {
    display: inline-block; width: 13px; height: 13px;
    border: 1.5px solid #444; border-radius: 2px; flex-shrink: 0;
}

.price { color: #27ae60; font-weight: 700; white-space: nowrap; }

.remark-lines { display: flex; flex-direction: column; gap: 7px; padding-top: 2px; }
.remark-line { border-bottom: 1px solid #bbb; height: 13px; }

/* ── Abschluss-Unterschriften ── */
.final-sig { margin-top: 30px; border-top: 2px solid #2c3e50; padding-top: 14px; }
.final-sig-title { font-size: 10pt; color: #2c3e50; font-weight: 700; margin-bottom: 6px; }
.final-sig-text { font-size: 9pt; color: #555; margin-bottom: 18px; }

.doc-footer {
    text-align: center; margin-top: 20px; font-size: 8pt; color: #aaa;
    border-top: 1px solid #eee; padding-top: 8px;
}
</style>
</head>
<body>

<div class="no-print no-print-bar">
    <button onclick="window.print()" class="btn-druck">🖨️ <?php echo htmlspecialchars(t('insx_print')); ?></button>
    <a href="export_pdf_handover.php" class="btn-druck back"><?php echo htmlspecialchars(t('hox_back_form')); ?></a>
    <a href="index.php" class="btn-druck back">📋 <?php echo htmlspecialchars(t('hox_to_inventory')); ?></a>
</div>

<!-- ══ DECKBLATT ══════════════════════════════════════════════ -->
<div class="cover page-break">
    <div class="cover-header">
        <div class="cover-logo"><span>📦</span> ValuSafe</div>
        <div style="font-size:9pt; color:#666;"><?php echo htmlspecialchars(t('hox_created')); ?> <?php echo date('d.m.Y H:i'); ?></div>
    </div>

    <div class="cover-typ">📋 <?php echo htmlspecialchars(t('hox_title')); ?> — <?php echo htmlspecialchars($hoTypLabel($typ)); ?></div>

    <div class="section-title">🏠 <?php echo htmlspecialchars(t('hox_sec_property')); ?></div>
    <table class="info-table">
        <tr><th><?php echo htmlspecialchars(t('hox_address')); ?></th><td><?php echo nl2br(htmlspecialchars($adresse)); ?></td></tr>
        <tr><th><?php echo htmlspecialchars(t('hox_floor')); ?></th><td><?php echo htmlspecialchars($lage); ?></td></tr>
        <tr><th><?php echo htmlspecialchars(t('hox_date')); ?></th><td><strong><?php echo htmlspecialchars($datum); ?></strong></td></tr>
        <tr><th><?php echo htmlspecialchars(t('hox_kind')); ?></th><td><?php echo htmlspecialchars($hoTypLabel($typ)); ?></td></tr>
    </table>

    <div class="section-title">👥 <?php echo htmlspecialchars(t('hox_sec_parties')); ?></div>
    <table class="info-table">
        <tr><th><?php echo htmlspecialchars(t('hox_tenant')); ?></th><td><?php echo nl2br(htmlspecialchars($mieter)); ?></td></tr>
        <tr><th><?php echo htmlspecialchars(t('hox_landlord')); ?></th><td><?php echo nl2br(htmlspecialchars($vermieter)); ?></td></tr>
    </table>

    <div class="section-title">🔑 <?php echo htmlspecialchars(t('hox_sec_keys')); ?></div>
    <table class="info-table">
        <tr><th><?php echo htmlspecialchars(t('hox_keys')); ?></th><td><?php echo nl2br(htmlspecialchars($schlussel ?: '—')); ?></td></tr>
    </table>

    <div class="section-title">⚡ <?php echo htmlspecialchars(t('hox_sec_meters')); ?></div>
    <table class="zaehler-table">
        <thead><tr><th><?php echo htmlspecialchars(t('hox_meter_type')); ?></th><th><?php echo htmlspecialchars(t('hox_meter_reading')); ?></th><th><?php echo htmlspecialchars(t('hox_remark')); ?></th></tr></thead>
        <tbody>
            <tr><td>⚡ <?php echo htmlspecialchars(t('hox_electricity')); ?></td><td><?php echo htmlspecialchars($strom ?: '—'); ?></td><td></td></tr>
            <tr><td>💧 <?php echo htmlspecialchars(t('hox_water')); ?></td><td><?php echo htmlspecialchars($wasser ?: '—'); ?></td><td></td></tr>
            <tr><td>🔥 <?php echo htmlspecialchars(t('hox_gas')); ?></td><td><?php echo htmlspecialchars($gas ?: '—'); ?></td><td></td></tr>
        </tbody>
    </table>

    <div class="gesamt-box">
        <span style="font-size:10pt; color:#2c3e50;">
            <strong><?php echo htmlspecialchars(t('hox_inventory_total')); ?></strong>
            <?php echo htmlspecialchars(strtr(t('hox_items_in_rooms'), ['{n}' => count($protokoll_items), '{r}' => count($raum_gruppen)])); ?>
        </span>
        <span class="gesamt-value"><?php echo formatPriceLocalized($gesamtwert); ?></span>
    </div>

    <?php if ($bemerkung): ?>
    <div class="section-title">📝 <?php echo htmlspecialchars(t('hox_sec_remarks')); ?></div>
    <div class="bem-box"><?php echo nl2br(htmlspecialchars($bemerkung)); ?></div>
    <?php endif; ?>

    <!-- Unterschriften Deckblatt -->
    <div class="sig-section">
        <div class="section-title">✍️ <?php echo htmlspecialchars(t('hox_sec_signatures')); ?></div>
        <div class="sig-row">
            <?php foreach ([t('hox_tenant'), t('hox_landlord'), t('hox_witness')] as $partei): ?>
            <div class="sig-box">
                <div class="sig-name-line"></div>
                <span class="sig-name-label"><?php echo htmlspecialchars(t('hox_name_print')); ?></span>
                <div class="sig-line"></div>
                <div class="sig-label"><?php echo htmlspecialchars(str_replace('%s', $partei, t('hox_signature'))); ?></div>
                <div class="sig-date-row">
                    <div class="sig-date-field">
                        <div class="sig-date-line"></div>
                        <div class="sig-date-label"><?php echo htmlspecialchars(t('hox_place')); ?></div>
                    </div>
                    <div class="sig-date-field">
                        <div class="sig-date-line"></div>
                        <div class="sig-date-label"><?php echo htmlspecialchars(t('hox_date')); ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ══ INVENTAR PRO RAUM ══════════════════════════════════════ -->
<?php foreach ($raum_gruppen as $ort => $items):
    $summe = array_sum(array_column($items, 'preis'));
?>
<div class="raum-section avoid-break">
    <div class="raum-title">
        <span>📍 <?php echo htmlspecialchars($ort); ?></span>
        <span class="raum-summe">
            <?php echo htmlspecialchars(count($items) === 1 ? t('hox_one_item') : str_replace('%s', count($items), t('insx_n_items'))); ?>
            <?php if ($summe > 0): ?> · <?php echo formatPriceLocalized($summe); ?><?php endif; ?>
        </span>
    </div>
    <table class="item-table">
        <thead>
            <tr>
                <th style="width:68px;"><?php echo htmlspecialchars(t('insx_col_photo')); ?></th>
                <th style="width:24%;"><?php echo htmlspecialchars(t('hox_col_name')); ?></th>
                <th style="width:11%;"><?php echo htmlspecialchars(t('hox_col_category')); ?></th>
                <th style="width:9%;"><?php echo htmlspecialchars(t('insx_col_price')); ?></th>
                <th style="width:105px;"><?php echo htmlspecialchars(t('hox_col_condition')); ?></th>
                <th><?php echo htmlspecialchars(t('hox_remark')); ?></th>
                <th style="width:38px; text-align:center;"><?php echo htmlspecialchars(t('hox_col_ok')); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td style="text-align:center;">
                    <?php if ($item['bild'] && file_exists(UPLOAD_DIR . $item['bild'])): ?>
                        <img src="<?php echo htmlspecialchars($baseUrl . '/upload/' . rawurlencode($item['bild'])); ?>"
                             alt="" class="item-img">
                    <?php else: ?>
                        <div class="item-no-img">📷</div>
                    <?php endif; ?>
                </td>
                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($item['kategorie_name'] ?? '—'); ?></td>
                <td class="price"><?php echo $item['preis'] > 0 ? formatPriceLocalized($item['preis']) : '—'; ?></td>
                <td>
                    <div class="zustand-boxes">
                        <div class="zustand-item"><span class="check-box"></span> <?php echo htmlspecialchars(t('hox_cond_new')); ?></div>
                        <div class="zustand-item"><span class="check-box"></span> <?php echo htmlspecialchars(t('hox_cond_good')); ?></div>
                        <div class="zustand-item"><span class="check-box"></span> <?php echo htmlspecialchars(t('hox_cond_worn')); ?></div>
                        <div class="zustand-item"><span class="check-box"></span> <?php echo htmlspecialchars(t('hox_cond_damaged')); ?></div>
                    </div>
                </td>
                <td>
                    <?php if ($item['notizen']): ?>
                        <span style="color:#555; font-size:8.5pt;">
                            <?php echo htmlspecialchars(mb_substr($item['notizen'], 0, 100));
                            echo mb_strlen($item['notizen']) > 100 ? '…' : ''; ?>
                        </span>
                    <?php else: ?>
                        <div class="remark-lines">
                            <div class="remark-line"></div>
                            <div class="remark-line"></div>
                        </div>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><span class="check-box"></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

<!-- ══ ABSCHLUSS-UNTERSCHRIFTEN ═══════════════════════════════ -->
<div class="final-sig avoid-break">
    <div class="final-sig-title">✅ <?php echo htmlspecialchars(t('hox_final_title')); ?></div>
    <p class="final-sig-text">
        <?php echo htmlspecialchars(t('hox_final_text')); ?>
    </p>
    <div class="sig-row">
        <?php foreach ([t('hox_tenant'), t('hox_landlord'), t('hox_witness')] as $partei): ?>
        <div class="sig-box">
            <div class="sig-name-line"></div>
            <span class="sig-name-label"><?php echo htmlspecialchars(t('hox_name_print')); ?></span>
            <div class="sig-line"></div>
            <div class="sig-label"><?php echo htmlspecialchars(str_replace('%s', $partei, t('hox_signature'))); ?></div>
            <div class="sig-date-row">
                <div class="sig-date-field">
                    <div class="sig-date-line"></div>
                    <div class="sig-date-label"><?php echo htmlspecialchars(t('hox_place')); ?></div>
                </div>
                <div class="sig-date-field">
                    <div class="sig-date-line"></div>
                    <div class="sig-date-label"><?php echo htmlspecialchars(t('hox_date')); ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="doc-footer">
    <?php echo htmlspecialchars(str_replace('%s', 'ValuSafe', t('hox_created_with'))); ?> · <?php echo date('d.m.Y H:i'); ?> · <?php echo htmlspecialchars($_SESSION['username']); ?>
</div>

</body>
</html>
<?php
exit;
endif;

// ══ FORMULAR-SEITE ═══════════════════════════════════════════
// Ohne PAGE_TITLE zeigten Fenster und Kopfzeile nur den Programmnamen (07.11.2026)
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', t('hox_title') . ' - ' . t('app_title'));
include 'header_next_page.php';
?>

<style>
.handover-wrap { max-width: 680px; margin: 0 auto; padding-bottom: 60px; }
.handover-card {
    background: rgba(255,255,255,0.85);
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}
.handover-card h3 {
    font-size: 15px; font-weight: 700; color: #2c3e50;
    margin-bottom: 16px; padding-bottom: 8px;
    border-bottom: 2px solid var(--primary-color, #3498db);
    display: flex; align-items: center; gap: 8px;
}
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 5px; }
.form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: 10px 12px; border: 1.5px solid #d1d5db;
    border-radius: 8px; font-size: 14px; font-family: inherit;
    background: white; outline: none; transition: border-color 0.15s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: var(--primary-color, #3498db);
    box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
}
.form-group textarea { resize: vertical; min-height: 70px; }
.form-group small { font-size: 12px; color: #888; margin-top: 4px; display: block; }
.btn-generate {
    width: 100%; padding: 14px; background: linear-gradient(135deg, #2c3e50, #34495e);
    color: white; border: none; border-radius: 10px; font-size: 16px;
    font-weight: 700; cursor: pointer; font-family: inherit; transition: opacity 0.2s;
}
.btn-generate:hover { opacity: 0.9; }
.preview-info {
    background: rgba(52,152,219,0.06); border: 1px solid rgba(52,152,219,0.2);
    border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; font-size: 14px;
    color: #1e3a5f;
}
</style>

<div class="handover-wrap">
    <h2 style="margin-bottom:6px;">📋 <?php echo htmlspecialchars(t('hox_title')); ?></h2>
    <p style="font-size:13px; color:#888; margin-bottom:20px;">
        <?php echo htmlspecialchars(str_replace('%s', 'ValuSafe', t('hox_intro'))); ?>
    </p>

    <div class="preview-info">
        📦 <?php echo htmlspecialchars(strtr(t('hox_preview'), ['{n}' => count($protokoll_items), '{r}' => count($raum_gruppen)])); ?>
        <?php echo htmlspecialchars(t('hox_total_value')); ?> <strong><?php echo formatPriceLocalized($gesamtwert); ?></strong>
    </div>

    <form method="POST" action="">
        <input type="hidden" name="generate" value="1">

        <!-- Objekt -->
        <div class="handover-card">
            <h3>🏠 <?php echo htmlspecialchars(t('hox_sec_property')); ?></h3>
            <div class="form-group">
                <label for="lbl_adresse"><?php echo htmlspecialchars(t('hox_address_label')); ?></label>
                <textarea id="lbl_adresse" name="adresse" placeholder="<?php echo htmlspecialchars(t('hox_address_ph')); ?>" rows="2"><?php echo htmlspecialchars($adresse); ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="lbl_lage"><?php echo htmlspecialchars(t('hox_floor')); ?></label>
                    <input type="text" id="lbl_lage" name="lage" placeholder="<?php echo htmlspecialchars(t('hox_floor_ph')); ?>" value="<?php echo htmlspecialchars($lage); ?>">
                </div>
                <div class="form-group">
                    <label for="lbl_datum"><?php echo htmlspecialchars(t('hox_date_label')); ?></label>
                    <input type="text" id="lbl_datum" name="datum" value="<?php echo htmlspecialchars($datum); ?>" placeholder="<?php echo htmlspecialchars(t('hox_date_ph')); ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="lbl_typ"><?php echo htmlspecialchars(t('hox_kind_label')); ?></label>
                <select id="lbl_typ" name="typ">
                    <option value="Übergabe"        <?php echo $typ === 'Übergabe'        ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('hox_type_uebergabe')); ?></option>
                    <option value="Übernahme"        <?php echo $typ === 'Übernahme'        ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('hox_type_uebernahme')); ?></option>
                    <option value="Zwischenübergabe" <?php echo $typ === 'Zwischenübergabe' ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('hox_type_zwischen')); ?></option>
                </select>
            </div>
        </div>

        <!-- Parteien -->
        <div class="handover-card">
            <h3>👥 <?php echo htmlspecialchars(t('hox_sec_parties')); ?></h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="lbl_mieter"><?php echo htmlspecialchars(t('hox_tenant')); ?></label>
                    <textarea id="lbl_mieter" name="mieter" placeholder="<?php echo htmlspecialchars(t('hox_name_addr_ph')); ?>" rows="2"><?php echo htmlspecialchars($mieter); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="lbl_vermieter"><?php echo htmlspecialchars(t('hox_landlord')); ?></label>
                    <textarea id="lbl_vermieter" name="vermieter" placeholder="<?php echo htmlspecialchars(t('hox_name_addr_ph')); ?>" rows="2"><?php echo htmlspecialchars($vermieter); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Schlüssel -->
        <div class="handover-card">
            <h3>🔑 <?php echo htmlspecialchars(t('hox_keys')); ?></h3>
            <div class="form-group">
                <label for="lbl_schlussel"><?php echo htmlspecialchars(t('hox_keys_label')); ?></label>
                <textarea id="lbl_schlussel" name="schlussel" placeholder="<?php echo htmlspecialchars(t('hox_keys_ph')); ?>" rows="2"><?php echo htmlspecialchars($schlussel); ?></textarea>
            </div>
        </div>

        <!-- Zählerstände -->
        <div class="handover-card">
            <h3>⚡ <?php echo htmlspecialchars(t('hox_sec_meters')); ?></h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="lbl_strom"><?php echo htmlspecialchars(t('hox_electricity')); ?></label>
                    <input type="text" id="lbl_strom" name="strom" placeholder="<?php echo htmlspecialchars(t('hox_elec_ph')); ?>" value="<?php echo htmlspecialchars($strom); ?>">
                </div>
                <div class="form-group">
                    <label for="lbl_wasser"><?php echo htmlspecialchars(t('hox_water')); ?></label>
                    <input type="text" id="lbl_wasser" name="wasser" placeholder="<?php echo htmlspecialchars(t('hox_water_ph')); ?>" value="<?php echo htmlspecialchars($wasser); ?>">
                </div>
            </div>
            <div class="form-group" style="max-width:50%;">
                <label for="lbl_gas"><?php echo htmlspecialchars(t('hox_gas_label')); ?></label>
                <input type="text" id="lbl_gas" name="gas" placeholder="<?php echo htmlspecialchars(t('hox_gas_ph')); ?>" value="<?php echo htmlspecialchars($gas); ?>">
            </div>
        </div>

        <!-- Bemerkungen -->
        <div class="handover-card">
            <h3>📝 <?php echo htmlspecialchars(t('hox_sec_remarks')); ?></h3>
            <div class="form-group">
                <label for="lbl_bemerkung"><?php echo htmlspecialchars(t('hox_other_remarks')); ?> <span style="font-weight:400; color:#aaa;"><?php echo htmlspecialchars(t('hox_optional')); ?></span></label>
                <textarea id="lbl_bemerkung" name="bemerkung" placeholder="<?php echo htmlspecialchars(t('hox_remarks_ph')); ?>" rows="3"><?php echo htmlspecialchars($bemerkung); ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn-generate">
            📋 <?php echo htmlspecialchars(t('hox_generate')); ?>
        </button>
    </form>
</div>

<?php include 'footer_next.php'; ?>
