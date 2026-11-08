<?php
// export_insurance.php - Versicherungs-Export
require_once 'db.php';
require_once 'helpers.php';
requireLogin();
// Export-Recht aus der Rechteverwaltung (bis 4.3.32 nicht geprueft).
requirePermission('export_insurance');
// Bei "nur eigene" nur eigene Gegenstaende exportieren.
[$ownSql, $ownParams] = nurEigeneSql('w');

try {
    $sql = "SELECT w.*, o.name as ort_name, k.name as kategorie_name,
                   w.aktueller_wert, w.aktueller_wert_datum,
                   w.custom1_wert, w.custom1_typ,
                   w.custom2_wert, w.custom2_typ
            FROM wertsachen w 
            LEFT JOIN raeume o ON w.raum_id = o.id 
            LEFT JOIN kategorien k ON w.kategorie_id = k.id 
            WHERE 1=1" . $ownSql . "
            ORDER BY COALESCE(o.name, 'zzz'), w.name";
    
    $wertsachen = $db->select($sql, $ownParams);
    $gesamtwert = array_sum(array_column($wertsachen, 'preis'));
    $anzahl     = count($wertsachen);
    
    // Aktuellen Gesamtwert berechnen (Regel aus helpers_werte.php: Zeitwert
    // > 0, sonst Kaufpreis). Bis 4.6 !empty() - "0.00" aus MySQL ist nicht
    // leer und zaehlte als gepflegter Zeitwert von 0.
    $gesamtwert_aktuell = 0;
    $hat_aktuelle_werte = false;
    foreach ($wertsachen as $item) {
        $gesamtwert_aktuell += vsZeitwert($item);
        if (vsHatZeitwert($item)) {
            $hat_aktuelle_werte = true;
        }
    }

    // Nach Orten gruppieren
    $byLocation = [];
    foreach ($wertsachen as $item) {
        $byLocation[$item['ort_name'] ?? t('insx_no_location')][] = $item;
    }

    // Versicherungs-Kategorien (angelehnt an Wertermittlungsbogen)
    $versKategorien = [
        'Einrichtung'              => ['Möbel','Einrichtung','Teppich','Beleuchtung','Vorhang','Gardine','Bett','Wohnzimmer','Schlafzimmer','Kinderzimmer','Küche',
                                       'Furniture','Furnishing','Carpet','Rug','Lamp','Lighting','Curtain','Bed','Sofa'],
        'Elektro & Technik'        => ['Elektronik','Elektro','TV','Fernseher','Computer','Laptop','Foto','Kamera','Audio','Musik','Telefon','Smartphone',
                                       'Electronic','Electric','Television','Camera','Photo','Music','Phone','Tablet','Console'],
        'Schmuck & Edelmetalle'    => ['Schmuck','Gold','Silber','Platin','Perlen','Edelstein','Uhren','Uhr',
                                       'Jewel','Silver','Platinum','Pearl','Gem','Watch'],
        'Kunst & Antiquitäten'     => ['Kunst','Gemälde','Grafik','Aquarell','Skulptur','Antiquität','Porzellan','Keramik','Kristall',
                                       'Painting','Print','Watercolour','Sculpture','Antique','Porcelain','Ceramic','Crystal','Artwork'],
        'Sammlungen'               => ['Sammlung','Münzen','Briefmarken','Bücher','Vinyl','Schallplatten','Wein',
                                       'Collection','Coin','Stamp','Book','Record','Wine'],
        'Kleidung & Accessoires'   => ['Kleidung','Bekleidung','Schuhe','Tasche','Pelz',
                                       'Clothing','Clothes','Shoe','Bag','Fur'],
        'Sport & Freizeit'         => ['Sport','Fahrrad','Ski','Camping','Instrument',
                                       'Bicycle','Bike'],
        'Sonstiges'                => [],
    ];

    // Jeden Gegenstand einer Versicherungskategorie zuordnen
    foreach ($wertsachen as &$item) {
        $assigned = 'Sonstiges';
        $haystack = strtolower(($item['kategorie_name'] ?? '') . ' ' . ($item['name'] ?? ''));
        foreach ($versKategorien as $vkat => $keywords) {
            foreach ($keywords as $kw) {
                if (stripos($haystack, $kw) !== false) {
                    $assigned = $vkat;
                    break 2;
                }
            }
        }
        $item['vers_kategorie'] = $assigned;
    }
    unset($item);

    // Anzeigenamen der Versicherungskategorien (seit 4.9 / 2.7 uebersetzt; die
    // Schluessel oben bleiben deutsch, sie sind nur interne Kennung)
    $vkatName = [
        'Einrichtung' => 'insx_vk_furnishing', 'Elektro & Technik' => 'insx_vk_electronics',
        'Schmuck & Edelmetalle' => 'insx_vk_jewellery', 'Kunst & Antiquitäten' => 'insx_vk_art',
        'Sammlungen' => 'insx_vk_collections', 'Kleidung & Accessoires' => 'insx_vk_clothing',
        'Sport & Freizeit' => 'insx_vk_sport', 'Sonstiges' => 'insx_vk_other',
    ];
    $vkatLabel = function ($vkat) use ($vkatName) { return htmlspecialchars(isset($vkatName[$vkat]) ? t($vkatName[$vkat]) : $vkat); };

    // Nach Versicherungskategorie gruppieren
    $byVersKat = [];
    foreach ($wertsachen as $item) {
        $byVersKat[$item['vers_kategorie']][] = $item;
    }

    Security::logSecurityEvent('insurance_export', ['count' => $anzahl]);

} catch (PDOException $e) {
    die('Fehler: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'de'); ?>">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars(t('insx_cover_title')); ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: Arial, sans-serif;
    font-size: 9.5pt;
    color: #111;
    background: white;
    margin: 20px;
}

@media print {
    .no-print { display: none !important; }
    body { margin: 0; font-size: 8.5pt; }
    @page { size: A4 portrait; margin: 10mm 8mm; }
    .page-break { page-break-before: always; }
    tr { page-break-inside: avoid; }
    thead { display: table-header-group; }
    .section { page-break-inside: avoid; }
}

/* Toolbar */
.toolbar {
    background: #1e3a5f; color: white;
    padding: 14px 20px; border-radius: 8px;
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
}
.toolbar h2 { flex: 1; font-size: 13pt; }
.btn { padding: 9px 18px; border: none; border-radius: 5px; font-size: 10pt; cursor: pointer; text-decoration: none; display: inline-block; font-weight: 600; }
.btn-print { background: #f0a500; color: #111; }
.btn-back  { background: rgba(255,255,255,0.2); color: white; }

/* Deckblatt */
.cover {
    border: 2px solid #1e3a5f; border-radius: 8px;
    padding: 24px 28px; margin-bottom: 28px;
}
.cover-title { font-size: 18pt; font-weight: bold; color: #1e3a5f; margin-bottom: 4px; }
.cover-sub   { font-size: 10pt; color: #555; margin-bottom: 18px; }
.cover-grid  { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; margin-bottom: 18px; }
.cover-box   { background: #f0f4f8; border-radius: 6px; padding: 12px; text-align: center; }
.cover-box-val { font-size: 16pt; font-weight: bold; color: #1e3a5f; }
.cover-box-lbl { font-size: 8pt; color: #666; margin-top: 3px; }
.cover-meta  { font-size: 8.5pt; color: #555; border-top: 1px solid #ddd; padding-top: 12px; }
.cover-meta table { width: 100%; }
.cover-meta td { padding: 2px 6px; }
.cover-meta td:first-child { font-weight: bold; color: #333; width: 150px; }

/* Inhaltsverzeichnis */
.toc { margin-bottom: 24px; }
.toc h3 { color: #1e3a5f; font-size: 11pt; margin-bottom: 8px; border-bottom: 2px solid #1e3a5f; padding-bottom: 4px; }
.toc table { width: 100%; font-size: 9pt; }
.toc td { padding: 4px 8px; border-bottom: 1px solid #eee; }
.toc td:last-child { text-align: right; font-weight: bold; color: #1a7a3c; }
.toc .toc-total td { font-weight: bold; border-top: 2px solid #1e3a5f; padding-top: 6px; color: #1e3a5f; }

/* Abschnitt-Header */
.section-header {
    background: #1e3a5f; color: white;
    padding: 7px 12px; border-radius: 5px;
    margin: 20px 0 8px 0;
    display: flex; justify-content: space-between; align-items: center;
    font-weight: bold; font-size: 10pt;
}
.section-badge {
    background: rgba(255,255,255,0.2);
    padding: 2px 8px; border-radius: 10px; font-size: 8pt;
}

/* Ort-Subheader */
.loc-header {
    background: #e8eef5; color: #1e3a5f;
    padding: 4px 10px; margin: 8px 0 4px 0;
    font-size: 8.5pt; font-weight: bold;
    border-left: 3px solid #1e3a5f;
}

/* Tabelle */
table.inv { width: 100%; border-collapse: collapse; margin-bottom: 4px; font-size: 8.5pt; }
table.inv thead th {
    background: #f0f4f8; color: #1e3a5f;
    padding: 5px 6px; text-align: left;
    font-size: 7.5pt; text-transform: uppercase;
    border-bottom: 1.5px solid #1e3a5f;
}
table.inv tbody td { padding: 5px 6px; border-bottom: 1px solid #eee; vertical-align: middle; }
table.inv tbody tr:nth-child(even) td { background: #fafbfc; }

/* Spaltenbreiten */
.ci  { width: 44px; }
.cn  { width: 17%; }
.cor { width: 9%;  }
.cm  { width: 11%; }
.cd  { width: 7%;  text-align: center; }
.cp  { width: 8%;  text-align: right; }
.cnw { width: 8%;  text-align: right; }
.cs  { width: 9%;  }
.cb  { width: 6%;  text-align: center; }
.cno { }

/* Bild */
.item-img { width: 40px; height: 40px; object-fit: cover; border-radius: 3px; border: 1px solid #ddd; display: block; }
.no-img   { width: 40px; height: 40px; background: #f0f0f0; border: 1px dashed #ccc; border-radius: 3px; display: flex; align-items: center; justify-content: center; font-size: 14pt; color: #ccc; }

.price  { font-weight: bold; color: #1a7a3c; }
.nwert  { border-bottom: 1px solid #aaa; min-width: 50px; display: inline-block; color: transparent; font-size: 7pt; }

.beleg-dok  { color: #1e3a5f; font-weight: bold; font-size: 8pt; }
.beleg-foto { color: #1a7a3c; font-weight: bold; font-size: 8pt; }
.beleg-nein { color: #bbb; font-size: 8pt; }

.notiz-text { font-size: 7.5pt; color: #666; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Abschnitt-Summe */
.section-total { text-align: right; font-size: 8.5pt; color: #555; padding: 3px 6px 10px; }
.section-total strong { color: #1e3a5f; }

/* Zusammenfassung */
.summary {
    border: 2px solid #1e3a5f; border-radius: 8px;
    margin-top: 24px; overflow: hidden;
}
.summary-head {
    background: #1e3a5f; color: white;
    padding: 10px 16px; font-weight: bold; font-size: 11pt;
}
.summary table { width: 100%; font-size: 9.5pt; }
.summary td { padding: 6px 16px; border-bottom: 1px solid #eee; }
.summary td:last-child { text-align: right; font-weight: bold; }
.summary .grand td { background: #f0f4f8; font-weight: bold; font-size: 11pt; color: #1e3a5f; }

/* Hinweise */
.notice {
    margin-top: 20px; border-radius: 6px;
    padding: 12px 16px; font-size: 8pt; color: #555;
    background: #fffdf0; border-left: 4px solid #f0a500;
}
.notice h4 { color: #333; margin-bottom: 6px; font-size: 9pt; }
.notice ul  { padding-left: 16px; line-height: 1.8; }

.doc-footer { margin-top: 16px; font-size: 7.5pt; color: #aaa; text-align: center; border-top: 1px solid #eee; padding-top: 8px; }
</style>
</head>
<body>

<!-- Toolbar -->
<div class="no-print toolbar">
    <h2>🛡️ <?php echo htmlspecialchars(t('nav_export_insurance')); ?></h2>
    <a href="index.php" class="btn btn-back"><?php echo htmlspecialchars(t('insx_back')); ?></a>
    <button onclick="window.print()" class="btn btn-print">🖨️ <?php echo htmlspecialchars(t('insx_print')); ?></button>
</div>

<!-- Deckblatt -->
<div class="cover">
    <div class="cover-title">🏠 <?php echo htmlspecialchars(t('insx_cover_title')); ?></div>
    <div class="cover-sub"><?php echo htmlspecialchars(t('insx_cover_sub')); ?></div>

    <div class="cover-grid">
        <div class="cover-box">
            <div class="cover-box-val"><?php echo $anzahl; ?></div>
            <div class="cover-box-lbl"><?php echo htmlspecialchars(t('insx_items_total')); ?></div>
        </div>
        <div class="cover-box">
            <div class="cover-box-val"><?php echo count($byLocation); ?></div>
            <div class="cover-box-lbl"><?php echo htmlspecialchars(t('insx_areas_rooms')); ?></div>
        </div>
        <div class="cover-box">
            <div class="cover-box-val"><?php echo number_format($gesamtwert, 0, ',', '.'); ?> €</div>
            <div class="cover-box-lbl"><?php echo htmlspecialchars(t('insx_total_purchase')); ?></div>
        </div>
        <?php if ($hat_aktuelle_werte): ?>
        <div class="cover-box" style="border: 2px solid #1a7a3c;">
            <div class="cover-box-val" style="color: #1a7a3c;"><?php echo number_format($gesamtwert_aktuell, 0, ',', '.'); ?> €</div>
            <div class="cover-box-lbl"><?php echo htmlspecialchars(t('insx_total_current')); ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="cover-meta">
        <table>
            <tr><td><?php echo htmlspecialchars(t('insx_created_on')); ?></td><td><?php echo date('d.m.Y H:i'); ?></td></tr>
            <tr><td><?php echo htmlspecialchars(t('insx_created_by')); ?></td><td><?php echo htmlspecialchars($_SESSION['username']); ?></td></tr>
            <tr><td><?php echo htmlspecialchars(t('insx_note_newvalue_lbl')); ?></td><td><?php echo htmlspecialchars(t('insx_note_newvalue')); ?></td></tr>
            <tr><td><?php echo htmlspecialchars(t('insx_note_serial_lbl')); ?></td><td><?php echo htmlspecialchars(t('insx_note_serial')); ?></td></tr>
        </table>
    </div>
</div>

<!-- Inhaltsverzeichnis -->
<div class="toc">
    <h3>📋 <?php echo htmlspecialchars(t('insx_toc_title')); ?></h3>
    <table>
        <?php
        $vkatIcons = [
            'Einrichtung'            => '🛋️',
            'Elektro & Technik'      => '📺',
            'Schmuck & Edelmetalle'  => '💍',
            'Kunst & Antiquitäten'   => '🖼️',
            'Sammlungen'             => '📚',
            'Kleidung & Accessoires' => '👔',
            'Sport & Freizeit'       => '🚴',
            'Sonstiges'              => '📦',
        ];
        $tocTotal = 0;
        foreach ($versKategorien as $vkat => $kws):
            if (!isset($byVersKat[$vkat])) continue;
            $vkatWert = array_sum(array_column($byVersKat[$vkat], 'preis'));
            $tocTotal += $vkatWert;
        ?>
        <tr>
            <td><?php echo ($vkatIcons[$vkat] ?? '📦') . ' ' . $vkatLabel($vkat); ?></td>
            <td><?php echo htmlspecialchars(count($byVersKat[$vkat]) === 1 ? t('hox_one_item') : str_replace('%s', count($byVersKat[$vkat]), t('insx_n_items'))); ?></td>
            <td><?php echo number_format($vkatWert, 2, ',', '.'); ?> €</td>
        </tr>
        <?php endforeach; ?>
        <tr class="toc-total">
            <td colspan="2"><?php echo htmlspecialchars(t('insx_total')); ?></td>
            <td><?php echo number_format($gesamtwert, 2, ',', '.'); ?> €</td>
        </tr>
    </table>
</div>

<div class="page-break"></div>

<!-- Inhalt nach Versicherungskategorien -->
<?php foreach ($versKategorien as $vkat => $kws):
    if (!isset($byVersKat[$vkat])) continue;
    $items    = $byVersKat[$vkat];
    $vkatWert = array_sum(array_column($items, 'preis'));

    // Nach Ort sub-gruppieren
    $subByLoc = [];
    foreach ($items as $item) {
        $subByLoc[$item['ort_name'] ?? t('insx_no_location')][] = $item;
    }
?>
<div class="section">
    <div class="section-header">
        <span><?php echo ($vkatIcons[$vkat] ?? '📦') . ' ' . $vkatLabel($vkat); ?></span>
        <span class="section-badge"><?php echo htmlspecialchars(count($items) === 1 ? t('hox_one_item') : str_replace('%s', count($items), t('insx_n_items'))); ?> · <?php echo number_format($vkatWert, 2, ',', '.'); ?> €</span>
    </div>

    <?php foreach ($subByLoc as $ort => $ortItems): ?>
        <?php if (count($subByLoc) > 1): ?>
        <div class="loc-header">📍 <?php echo htmlspecialchars($ort); ?></div>
        <?php endif; ?>

        <table class="inv">
            <thead>
                <tr>
                    <th class="ci"><?php echo htmlspecialchars(t('insx_col_photo')); ?></th>
                    <th class="cn"><?php echo htmlspecialchars(t('insx_col_item')); ?></th>
                    <?php if (count($subByLoc) === 1): ?><th class="cor"><?php echo htmlspecialchars(t('insx_col_room')); ?></th><?php endif; ?>
                    <th class="cm"><?php echo htmlspecialchars(t('insx_col_brand')); ?></th>
                    <th class="cd"><?php echo htmlspecialchars(t('insx_col_bought')); ?></th>
                    <th class="cp"><?php echo htmlspecialchars(t('insx_col_price')); ?></th>
                    <th class="cnw"><?php echo htmlspecialchars(t('insx_col_newvalue')); ?></th>
                    <th class="cs"><?php echo htmlspecialchars(t('insx_col_serial')); ?></th>
                    <th class="cb"><?php echo htmlspecialchars(t('insx_col_receipt')); ?></th>
                    <th class="cno"><?php echo htmlspecialchars(t('insx_col_notes')); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ortItems as $item):
                // Beleg-Status
                $hasDoc = false;
                try {
                    $dc = $db->selectOne("SELECT COUNT(*) as c FROM dokumente WHERE wertsache_id = ?", [$item['id']]);
                    $hasDoc = ($dc['c'] ?? 0) > 0;
                } catch(Exception $e) {}

                $hasBild = $item['bild'] && file_exists(UPLOAD_DIR . $item['bild']);

                if ($hasDoc)      $belegHtml = '<span class="beleg-dok">📄 ' . htmlspecialchars(t('insx_receipt_doc')) . '</span>';
                elseif ($hasBild) $belegHtml = '<span class="beleg-foto">📷 ' . htmlspecialchars(t('insx_col_photo')) . '</span>';
                else              $belegHtml = '<span class="beleg-nein">–</span>';

                // Marke + Seriennummer aus Notizen
                $notizen = $item['notizen'] ?? '';
                $marke = $serial = '';
                if (preg_match('/(?:Marke|Brand|Hersteller|Modell)[:\s]+([^\n,;]{1,30})/i', $notizen, $m)) $marke  = trim($m[1]);
                if (preg_match('/(?:Serien(?:nummer)?|S\/N|Serial|SN)[:\s#]+([^\n,;\s]{1,20})/i',   $notizen, $m)) $serial = trim($m[1]);
            ?>
            <tr>
                <td class="ci">
                    <?php if ($hasBild):
                        $imgData = base64_encode(file_get_contents(UPLOAD_DIR . $item['bild']));
                        $imgInfo = @getimagesize(UPLOAD_DIR . $item['bild']);
                        $mime    = $imgInfo['mime'] ?? 'image/jpeg';
                    ?>
                        <img class="item-img" src="data:<?php echo $mime; ?>;base64,<?php echo $imgData; ?>" alt="">
                    <?php else: ?>
                        <div class="no-img">📷</div>
                    <?php endif; ?>
                </td>
                <td class="cn"><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                <?php if (count($subByLoc) === 1): ?>
                <td class="cor"><?php echo htmlspecialchars($item['ort_name'] ?? '–'); ?></td>
                <?php endif; ?>
                <td class="cm"><?php echo htmlspecialchars($marke ?: '–'); ?></td>
                <td class="cd"><?php echo $item['kaufdatum'] ? date('m/Y', strtotime($item['kaufdatum'])) : '–'; ?></td>
                <td class="cp"><span class="price"><?php echo number_format($item['preis'], 2, ',', '.'); ?> €</span></td>
                <td class="cnw\">
                    <?php if (vsHatZeitwert($item)): ?>
                        <span class="price"><?php echo number_format($item['aktueller_wert'], 2, ',', '.'); ?> €</span>
                        <?php if (!empty($item['aktueller_wert_datum'])): ?>
                            <br><span style="font-size:7pt; color:#999;"><?php echo date('m/Y', strtotime($item['aktueller_wert_datum'])); ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="nwert">_________</span>
                    <?php endif; ?>
                </td>
                <td class="cs"><?php echo htmlspecialchars($serial ?: '–'); ?></td>
                <td class="cb"><?php echo $belegHtml; ?></td>
                <td class="cno">
                    <?php 
                    // Custom-Felder anzeigen
                    $extras = [];
                    // 4.8: Hersteller/Modell und Zustand vorneweg
                    if (vsHerstellerModell($item) !== '') {
                        $extras[] = vsHerstellerModell($item);
                    }
                    if (vsErhaltungszustandText($item['erhaltungszustand'] ?? null) !== '') {
                        $extras[] = vsUebersetzt('field_condition', 'Zustand') . ': ' . vsErhaltungszustandText($item['erhaltungszustand']);
                    }
                    if (!empty($item['custom1_typ']) && !empty($item['custom1_wert'])) {
                        $extras[] = $item['custom1_typ'] . ': ' . $item['custom1_wert'];
                    }
                    if (!empty($item['custom2_typ']) && !empty($item['custom2_wert'])) {
                        $extras[] = $item['custom2_typ'] . ': ' . $item['custom2_wert'];
                    }
                    if ($extras): ?>
                        <span class="notiz-text" style="color:#1e3a5f; font-weight:bold;">
                            <?php echo htmlspecialchars(implode(' · ', $extras)); ?>
                        </span><br>
                    <?php endif; ?>
                    <?php if ($notizen): ?>
                        <span class="notiz-text" title="<?php echo htmlspecialchars($notizen); ?>">
                            <?php echo htmlspecialchars(mb_substr(strip_tags($notizen), 0, 55)); ?><?php echo mb_strlen($notizen) > 55 ? '…' : ''; ?>
                        </span>
                    <?php else: ?><span style="color:#ddd;">–</span><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <div class="section-total">
        <?php echo str_replace('%s', $vkatLabel($vkat), t('insx_sum_of')); ?> <strong><?php echo number_format($vkatWert, 2, ',', '.'); ?> €</strong>
    </div>
</div>
<?php endforeach; ?>

<!-- Zusammenfassung -->
<div class="summary page-break">
    <div class="summary-head">💰 <?php echo htmlspecialchars(t('insx_summary_title')); ?></div>
    <table>
        <?php foreach ($versKategorien as $vkat => $kws):
            if (!isset($byVersKat[$vkat])) continue;
            $vw = array_sum(array_column($byVersKat[$vkat], 'preis'));
        ?>
        <tr>
            <td><?php echo ($vkatIcons[$vkat] ?? '') . ' ' . $vkatLabel($vkat); ?></td>
            <td><?php echo number_format($vw, 2, ',', '.'); ?> €</td>
        </tr>
        <?php endforeach; ?>
        <tr class="grand">
            <td><?php echo htmlspecialchars(t('insx_grand_purchase')); ?></td>
            <td><?php echo number_format($gesamtwert, 2, ',', '.'); ?> €</td>
        </tr>
        <?php if ($hat_aktuelle_werte): ?>
        <tr class="grand" style="color: #1a7a3c;">
            <td><?php echo htmlspecialchars(t('insx_grand_current')); ?></td>
            <td><?php echo number_format($gesamtwert_aktuell, 2, ',', '.'); ?> €</td>
        </tr>
        <?php endif; ?>
        <tr class="grand">
            <td><?php echo htmlspecialchars(t('insx_grand_new')); ?></td>
            <td>_____________ €</td>
        </tr>
    </table>
</div>

<!-- Hinweise -->
<div class="notice">
    <h4>⚠️ <?php echo htmlspecialchars(t('insx_notes_title')); ?></h4>
    <ul>
        <li><strong><?php echo htmlspecialchars(t('insx_hint1_lbl')); ?></strong> <?php echo htmlspecialchars(t('insx_hint1')); ?></li>
        <li><strong><?php echo htmlspecialchars(t('insx_hint2_lbl')); ?></strong> <?php echo htmlspecialchars(t('insx_hint2')); ?></li>
        <li><strong><?php echo htmlspecialchars(t('insx_hint3_lbl')); ?></strong> <?php echo htmlspecialchars(t('insx_hint3')); ?></li>
        <li><strong><?php echo htmlspecialchars(t('insx_hint4_lbl')); ?></strong> <?php echo htmlspecialchars(t('insx_hint4')); ?></li>
        <li><strong><?php echo htmlspecialchars(t('insx_hint5_lbl')); ?></strong> <?php echo htmlspecialchars(t('insx_hint5')); ?></li>
    </ul>
</div>

<div class="doc-footer">
    ValuSafe &nbsp;·&nbsp; <?php echo htmlspecialchars(t('insx_confidential')); ?> &nbsp;·&nbsp; <?php echo date('d.m.Y H:i'); ?>
</div>

</body>
</html>
