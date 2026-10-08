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
    $ort = $item['ort_name'] ?? '— Kein Ort —';
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

// Wenn Druckansicht: direkt ausgeben
if ($show_print):
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Übergabeprotokoll — <?php echo htmlspecialchars($adresse); ?></title>
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
    <button onclick="window.print()" class="btn-druck">🖨️ Drucken / Als PDF speichern</button>
    <a href="export_pdf_handover.php" class="btn-druck back">← Zurück zum Formular</a>
    <a href="index.php" class="btn-druck back">📋 Zur Inventarliste</a>
</div>

<!-- ══ DECKBLATT ══════════════════════════════════════════════ -->
<div class="cover page-break">
    <div class="cover-header">
        <div class="cover-logo"><span>📦</span> ValuSafe</div>
        <div style="font-size:9pt; color:#666;">Erstellt: <?php echo date('d.m.Y H:i'); ?></div>
    </div>

    <div class="cover-typ">📋 Übergabeprotokoll — <?php echo htmlspecialchars($typ); ?></div>

    <div class="section-title">🏠 Objekt</div>
    <table class="info-table">
        <tr><th>Adresse</th><td><?php echo nl2br(htmlspecialchars($adresse)); ?></td></tr>
        <tr><th>Lage / Etage</th><td><?php echo htmlspecialchars($lage); ?></td></tr>
        <tr><th>Datum</th><td><strong><?php echo htmlspecialchars($datum); ?></strong></td></tr>
        <tr><th>Art</th><td><?php echo htmlspecialchars($typ); ?></td></tr>
    </table>

    <div class="section-title">👥 Parteien</div>
    <table class="info-table">
        <tr><th>Mieter/in</th><td><?php echo nl2br(htmlspecialchars($mieter)); ?></td></tr>
        <tr><th>Vermieter/in</th><td><?php echo nl2br(htmlspecialchars($vermieter)); ?></td></tr>
    </table>

    <div class="section-title">🔑 Schlüsselübergabe</div>
    <table class="info-table">
        <tr><th>Schlüssel</th><td><?php echo nl2br(htmlspecialchars($schlussel ?: '—')); ?></td></tr>
    </table>

    <div class="section-title">⚡ Zählerstände</div>
    <table class="zaehler-table">
        <thead><tr><th>Zählerart</th><th>Stand bei Übergabe</th><th>Bemerkung</th></tr></thead>
        <tbody>
            <tr><td>⚡ Strom</td><td><?php echo htmlspecialchars($strom ?: '—'); ?></td><td></td></tr>
            <tr><td>💧 Wasser</td><td><?php echo htmlspecialchars($wasser ?: '—'); ?></td><td></td></tr>
            <tr><td>🔥 Gas</td><td><?php echo htmlspecialchars($gas ?: '—'); ?></td><td></td></tr>
        </tbody>
    </table>

    <div class="gesamt-box">
        <span style="font-size:10pt; color:#2c3e50;">
            <strong>Inventar gesamt:</strong>
            <?php echo count($protokoll_items); ?> Gegenstände in <?php echo count($raum_gruppen); ?> Räumen
        </span>
        <span class="gesamt-value"><?php echo formatPriceLocalized($gesamtwert); ?></span>
    </div>

    <?php if ($bemerkung): ?>
    <div class="section-title">📝 Allgemeine Bemerkungen</div>
    <div class="bem-box"><?php echo nl2br(htmlspecialchars($bemerkung)); ?></div>
    <?php endif; ?>

    <!-- Unterschriften Deckblatt -->
    <div class="sig-section">
        <div class="section-title">✍️ Unterschriften</div>
        <div class="sig-row">
            <?php foreach (['Mieter/in', 'Vermieter/in', 'Zeuge/Zeugin (optional)'] as $partei): ?>
            <div class="sig-box">
                <div class="sig-name-line"></div>
                <span class="sig-name-label">Name (Druckbuchstaben)</span>
                <div class="sig-line"></div>
                <div class="sig-label"><?php echo $partei; ?> — Unterschrift</div>
                <div class="sig-date-row">
                    <div class="sig-date-field">
                        <div class="sig-date-line"></div>
                        <div class="sig-date-label">Ort</div>
                    </div>
                    <div class="sig-date-field">
                        <div class="sig-date-line"></div>
                        <div class="sig-date-label">Datum</div>
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
            <?php echo count($items); ?> Gegenstand<?php echo count($items) !== 1 ? 'e' : ''; ?>
            <?php if ($summe > 0): ?> · <?php echo formatPriceLocalized($summe); ?><?php endif; ?>
        </span>
    </div>
    <table class="item-table">
        <thead>
            <tr>
                <th style="width:68px;">Foto</th>
                <th style="width:24%;">Bezeichnung</th>
                <th style="width:11%;">Kategorie</th>
                <th style="width:9%;">Kaufpreis</th>
                <th style="width:105px;">Zustand</th>
                <th>Bemerkung</th>
                <th style="width:38px; text-align:center;">i.O.</th>
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
                        <div class="zustand-item"><span class="check-box"></span> Neuwertig</div>
                        <div class="zustand-item"><span class="check-box"></span> Gut</div>
                        <div class="zustand-item"><span class="check-box"></span> Gebrauchsspuren</div>
                        <div class="zustand-item"><span class="check-box"></span> Beschädigt</div>
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
    <div class="final-sig-title">✅ Abschluss-Bestätigung</div>
    <p class="final-sig-text">
        Außer den oben vermerkten Mängeln konnten keine weiteren Mängel festgestellt werden.
        Das Protokoll wurde gemeinsam erstellt und von beiden Parteien als korrekt bestätigt.
    </p>
    <div class="sig-row">
        <?php foreach (['Mieter/in', 'Vermieter/in', 'Zeuge/Zeugin (optional)'] as $partei): ?>
        <div class="sig-box">
            <div class="sig-name-line"></div>
            <span class="sig-name-label">Name (Druckbuchstaben)</span>
            <div class="sig-line"></div>
            <div class="sig-label"><?php echo $partei; ?> — Unterschrift</div>
            <div class="sig-date-row">
                <div class="sig-date-field">
                    <div class="sig-date-line"></div>
                    <div class="sig-date-label">Ort</div>
                </div>
                <div class="sig-date-field">
                    <div class="sig-date-line"></div>
                    <div class="sig-date-label">Datum</div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="doc-footer">
    Erstellt mit ValuSafe · <?php echo date('d.m.Y H:i'); ?> · <?php echo htmlspecialchars($_SESSION['username']); ?>
</div>

</body>
</html>
<?php
exit;
endif;

// ══ FORMULAR-SEITE ═══════════════════════════════════════════
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
    <h2 style="margin-bottom:6px;">📋 Übergabeprotokoll</h2>
    <p style="font-size:13px; color:#888; margin-bottom:20px;">
        Angaben eingeben — das Protokoll wird mit allen Inventargegenständen aus ValuSafe generiert.
    </p>

    <div class="preview-info">
        📦 <strong><?php echo count($protokoll_items); ?> Gegenstände</strong> in
        <strong><?php echo count($raum_gruppen); ?> Räumen</strong> werden ins Protokoll aufgenommen.
        Gesamtwert: <strong><?php echo formatPriceLocalized($gesamtwert); ?></strong>
    </div>

    <form method="POST" action="">
        <input type="hidden" name="generate" value="1">

        <!-- Objekt -->
        <div class="handover-card">
            <h3>🏠 Objekt</h3>
            <div class="form-group">
                <label>Adresse der Wohnung *</label>
                <textarea name="adresse" placeholder="Musterstraße 12&#10;12345 Musterstadt" rows="2"><?php echo htmlspecialchars($adresse); ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Lage / Etage</label>
                    <input type="text" name="lage" placeholder="z.B. 2. OG links" value="<?php echo htmlspecialchars($lage); ?>">
                </div>
                <div class="form-group">
                    <label>Datum der Übergabe</label>
                    <input type="text" name="datum" value="<?php echo htmlspecialchars($datum); ?>" placeholder="TT.MM.JJJJ">
                </div>
            </div>
            <div class="form-group">
                <label>Art des Protokolls</label>
                <select name="typ">
                    <option value="Übergabe"        <?php echo $typ === 'Übergabe'        ? 'selected' : ''; ?>>Übergabe (Einzug)</option>
                    <option value="Übernahme"        <?php echo $typ === 'Übernahme'        ? 'selected' : ''; ?>>Übernahme (Auszug)</option>
                    <option value="Zwischenübergabe" <?php echo $typ === 'Zwischenübergabe' ? 'selected' : ''; ?>>Zwischenübergabe</option>
                </select>
            </div>
        </div>

        <!-- Parteien -->
        <div class="handover-card">
            <h3>👥 Parteien</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Mieter/in</label>
                    <textarea name="mieter" placeholder="Name&#10;Adresse" rows="2"><?php echo htmlspecialchars($mieter); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Vermieter/in</label>
                    <textarea name="vermieter" placeholder="Name&#10;Adresse" rows="2"><?php echo htmlspecialchars($vermieter); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Schlüssel -->
        <div class="handover-card">
            <h3>🔑 Schlüssel</h3>
            <div class="form-group">
                <label>Übergebene Schlüssel</label>
                <textarea name="schlussel" placeholder="z.B. 2x Haustürschlüssel, 1x Briefkastenschlüssel, 1x Kellerschlüssel" rows="2"><?php echo htmlspecialchars($schlussel); ?></textarea>
            </div>
        </div>

        <!-- Zählerstände -->
        <div class="handover-card">
            <h3>⚡ Zählerstände</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Strom</label>
                    <input type="text" name="strom" placeholder="z.B. 12345 kWh" value="<?php echo htmlspecialchars($strom); ?>">
                </div>
                <div class="form-group">
                    <label>Wasser</label>
                    <input type="text" name="wasser" placeholder="z.B. 567 m³" value="<?php echo htmlspecialchars($wasser); ?>">
                </div>
            </div>
            <div class="form-group" style="max-width:50%;">
                <label>Gas (falls vorhanden)</label>
                <input type="text" name="gas" placeholder="z.B. 890 m³" value="<?php echo htmlspecialchars($gas); ?>">
            </div>
        </div>

        <!-- Bemerkungen -->
        <div class="handover-card">
            <h3>📝 Allgemeine Bemerkungen</h3>
            <div class="form-group">
                <label>Sonstige Bemerkungen <span style="font-weight:400; color:#aaa;">(optional)</span></label>
                <textarea name="bemerkung" placeholder="Weitere Hinweise zum Zustand der Wohnung..." rows="3"><?php echo htmlspecialchars($bemerkung); ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn-generate">
            📋 Übergabeprotokoll generieren →
        </button>
    </form>
</div>

<?php include 'footer_next.php'; ?>
