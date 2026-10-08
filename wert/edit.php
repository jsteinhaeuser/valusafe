<?php
// edit.php - MIT ACTIVITY LOG
require_once 'db.php';
require_once 'helpers.php';
require_once 'helpers_images.php';
requireLogin();
// Bis 4.3.32 nur requireLogin(): ein Leser konnte per Adresse bearbeiten.
requirePermission('items_edit');
define('PAGE_TITLE', t('form_edit_item') . ' - ' . t('app_title'));

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$errors = [];

// Filter-Rücksprung: return_url aus GET oder POST übernehmen
$returnUrl = 'index.php';
$_returnCandidate = $_GET['return_url'] ?? $_POST['return_url'] ?? '';
if (!empty($_returnCandidate) && preg_match('/^index\.php/', $_returnCandidate)) {
    $returnUrl = $_returnCandidate;
}

if (!$id) {
    redirectWithMessage($returnUrl, t('error_invalid_id'), 'error');
}

try {
    $item = loadWertsachenWithDetails($db, $id);
    
    // Fremder Gegenstand bei "nur eigene": wie nicht vorhanden behandeln.
    if (!$item || !darfGegenstand($item)) {
        redirectWithMessage('index.php', t('error_item_not_found'), 'error');
    }
    
    // Dokumente-Anzahl laden (wenn Feature aktiv)
    $user = $db->selectOne("SELECT dokumente_aktiv FROM users WHERE username = ?", [$_SESSION['username']]);
    $dokumente_aktiv = $user['dokumente_aktiv'] ?? 0;
    
    if ($dokumente_aktiv) {
        $doc_count = $db->selectOne("SELECT COUNT(*) as count FROM dokumente WHERE wertsache_id = ?", [$id]);
        $item['dokumente_anzahl'] = $doc_count['count'] ?? 0;
    }
} catch (PDOException $e) {
    Security::logSecurityEvent('item_load_error', ['id' => $id, 'error' => $e->getMessage()]);
    die(t('error_loading_data'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateRequest();
    
    // ===== ACTIVITY LOG: Alte Werte speichern =====
    $oldValues = [
        'name' => $item['name'],
        'raum_id' => $item['raum_id'],
        'kategorie_id' => $item['kategorie_id'],
        'preis' => $item['preis'],
        'kaufdatum' => $item['kaufdatum'],
        'notizen' => $item['notizen']
    ];
    // ===============================================
    
    // Daten bereinigen
    $data = sanitizeWertsachenInput($_POST);
    $erstellt_von = trim($_POST['erstellt_von'] ?? '') ?: $_SESSION['username'];
    // Bei "nur eigene" bliebe ein umbenannter Gegenstand fuer den Benutzer
    // unsichtbar - Ersteller fest auf das eigene Konto.
    if (userSeesOnlyOwnItems()) {
        $erstellt_von = $_SESSION['username'];
    }
    
    // Validierung
    $errors = validateWertsachenData($data);

    // Duplikat-Prüfung (weiche Warnung — Name geändert und bereits vorhanden)
    $duplikatWarnung = false;
    if (empty($errors) && !isset($_POST['duplikat_bestaetigt'])) {
        if ($data['name'] !== $item['name']) { // Nur prüfen wenn Name geändert wurde
            $existing = $db->selectOne(
                "SELECT id FROM wertsachen WHERE name = ? AND erstellt_von = ? AND id != ? LIMIT 1",
                [$data['name'], $erstellt_von, $id]
            );
            if ($existing) {
                $duplikatWarnung = true;
            }
        }
    }
    
    // Datei-Upload
    $uploadResult = handleFileUpload($_FILES['bild'] ?? null, $item['bild']);
    if (isset($uploadResult['errors'])) {
        $errors = array_merge($errors, $uploadResult['errors']);
    }
    
    if (empty($errors) && !$duplikatWarnung) {
        try {
            // Gelistet am Datum
            $gelistet_am = trim($_POST['gelistet_am'] ?? '') ?: $item['gelistet_am'] ?? date('Y-m-d');
            
            // Neuer Wert + Custom-Felder
            $aktueller_wert = trim($_POST['aktueller_wert'] ?? '');
            $aktueller_wert = $aktueller_wert !== '' ? str_replace(',', '.', $aktueller_wert) : null;
            $aktueller_wert_datum = trim($_POST['aktueller_wert_datum'] ?? '') ?: null;
            $custom1_wert = trim($_POST['custom1_wert'] ?? '') ?: null;
            $custom1_typ  = in_array($_POST['custom1_typ'] ?? '', ['text','zahl']) ? $_POST['custom1_typ'] : 'text';
            $custom2_wert = trim($_POST['custom2_wert'] ?? '') ?: null;
            $custom2_typ  = in_array($_POST['custom2_typ'] ?? '', ['text','zahl']) ? $_POST['custom2_typ'] : 'text';
            $barcode      = trim($_POST['barcode'] ?? '') ?: null;
            $oeffentlich  = isset($_POST['oeffentlich']) ? 1 : 0;
            [$standort_id, $position_id] = standortAusFormular($db, $_POST);

            $sql = "UPDATE wertsachen 
                    SET name = ?, raum_id = ?, kategorie_id = ?, kaufdatum = ?, preis = ?, notizen = ?, bild = ?, erstellt_von = ?, gelistet_am = ?,
                        aktueller_wert = ?, aktueller_wert_datum = ?,
                        custom1_wert = ?, custom1_typ = ?, custom2_wert = ?, custom2_typ = ?, barcode = ?, oeffentlich = ?,
                        position_id = ?, standort_id = ?
                    WHERE id = ?";
            
            $db->execute($sql, [
                $data['name'],
                $data['raum_id'],
                $data['kategorie_id'],
                $data['kaufdatum'],
                $data['preis'],
                $data['notizen'],
                $uploadResult['filename'],
                $erstellt_von,
                $gelistet_am,
                $aktueller_wert,
                $aktueller_wert_datum,
                $custom1_wert,
                $custom1_typ,
                $custom2_wert,
                $custom2_typ,
                $barcode,
                $oeffentlich,
                $position_id,
                $standort_id,
                $id
            ]);
            
            // Werthistorie-Eintrag wenn aktueller_wert gesetzt und geändert
            if ($aktueller_wert !== null) {
                $prevWert = floatval($item['aktueller_wert'] ?? -1);
                if (floatval($aktueller_wert) !== $prevWert) {
                    try {
                        $db->execute(
                            "INSERT INTO wert_historie (wertsache_id, wert, datum) VALUES (?, ?, ?)",
                            [$id, $aktueller_wert, $aktueller_wert_datum ?: date('Y-m-d')]
                        );
                    } catch (Exception $e) { /* Tabelle existiert noch nicht */ }
                }
            }
            
            Security::logSecurityEvent('item_updated', [
                'item_id' => $id,
                'item_name' => $data['name']
            ]);
            
            // ===== ACTIVITY LOG =====
            $newValues = [
                'name' => $data['name'],
                'raum_id' => $data['raum_id'],
                'kategorie_id' => $data['kategorie_id'],
                'preis' => $data['preis'],
                'kaufdatum' => $data['kaufdatum'],
                'notizen' => $data['notizen']
            ];
            
            $changes = createValueDiff($oldValues, $newValues);
            if (!empty($changes)) {
                logActivity('updated', 'wertsachen', $id, $data['name'], 
                    ['changes' => $changes], 
                    null
                );
            }
            // ========================
            
            redirectWithMessage($returnUrl, t('msg_item_updated'));
            
        } catch (PDOException $e) {
            $errors[] = handleDatabaseError($e, 'update_item');
        }
    }
}

try {
    $raeume = $db->select("SELECT * FROM raeume ORDER BY name");
    $kategorien = $db->select("SELECT * FROM kategorien ORDER BY name");
    $standorte = $db->select("SELECT id, name FROM standorte ORDER BY name");
    // Standort für die Dropdown-Vorbelegung. Gespeichert in standort_id;
    // bei aelteren Eintraegen, die nur eine Position haben, aus der Position.
    $standort_raum_id = (int)($item['standort_id'] ?? 0);
    if (!$standort_raum_id && !empty($item['position_id'])) {
        $pos = $db->selectOne("SELECT raum_id FROM positionen WHERE id = ?", [$item['position_id']]);
        $standort_raum_id = $pos['raum_id'] ?? 0;
    }
} catch (PDOException $e) {
    die(t('error_loading_data'));
}

include 'header_next_page.php';
?>

<?php if (isset($_GET['new'])): ?>
<div style="background: var(--vs-success-light); border: 1px solid var(--vs-success); border-radius: var(--vs-r-lg); padding: 16px 20px; margin-bottom: 16px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
    <i class="ti ti-check" style="font-size:20px;color:var(--vs-success);"></i>
    <div style="flex: 1;">
        <strong>Gegenstand erfolgreich gespeichert!</strong><br>
        <span style="font-size: 13px; color: #555;">Weitere Bilder? Nutze den Bilder-Bereich unten.</span>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="add.php" class="vs-btn vs-btn-primary">
            <i class="ti ti-plus" aria-hidden="true"></i> <?php echo t('btn_add_another'); ?>
        </a>
        <a href="<?php echo htmlspecialchars($returnUrl); ?>" style="padding: 8px 16px; background: white; color: var(--vs-text); border: 1px solid var(--vs-border); border-radius: 8px; text-decoration: none; font-size: 14px; white-space: nowrap;">
            <i class="ti ti-layout-cards" aria-hidden="true"></i> <?php echo t('btn_back_to_overview'); ?>
        </a>
    </div>
</div>
<?php endif; ?>

<link rel="stylesheet" href="css/multi_image_upload.css">
<style>

/* Label-Abstand zu Feldern */
.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: var(--vs-text-sm);
    font-weight: var(--vs-weight-medium);
    color: var(--vs-text);
}
.form-group {
    margin-bottom: var(--vs-sp-5);
}

/* Mobile-First Optimierungen */
.form-single {
    max-width: 100%;
}

@media (min-width: 768px) {
    .form-single {
        max-width: 800px;
        margin: 0 auto;
    }
}

/* Drag & Drop Zone */
.upload-zone {
    border: 3px dashed var(--border-color, #ccc);
    border-radius: 12px;
    padding: 40px 20px;
    text-align: center;
    background: var(--background-color, #f9f9f9);
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 15px;
    position: relative;
}

.upload-zone:hover {
    border-color: var(--primary-color, #3498db);
    background: var(--hover-bg, #f0f8ff);
}

.upload-zone.drag-over {
    border-color: var(--success-color, #27ae60);
    background: var(--success-bg, #d4edda);
    transform: scale(1.02);
}

.upload-zone-icon {
    font-size: 3em;
    margin-bottom: 15px;
    color: var(--primary-color, #3498db);
}

.upload-zone-text {
    font-size: 1.1em;
    color: var(--text-color, #333);
    margin-bottom: 10px;
}

.upload-zone-hint {
    font-size: 0.9em;
    color: var(--text-muted, #666);
}

.upload-zone input[type="file"] {
    display: none;
}

/* Mobile Touch-optimiert */
@media (max-width: 768px) {
    .upload-zone {
        padding: 30px 15px;
    }
    
    .upload-zone-icon {
        font-size: 2.5em;
    }
    
    .upload-zone-text {
        font-size: 1em;
    }
}

/* Aktuelles Bild anzeigen */
.current-image {
    margin-bottom: 20px;
    text-align: center;
}

.current-image img {
    max-width: 100%;
    max-height: 300px;
    border-radius: 8px;
    box-shadow: var(--vs-shadow-hovered);
}

.current-image-placeholder {
    padding: 40px;
    background: #f5f5f5;
    border-radius: 8px;
    color: #999;
    font-size: 3em;
}

/* Bildvorschau (neues Bild) */
.image-preview-container {
    margin-top: 20px;
    text-align: center;
    display: none;
}

.image-preview-container.active {
    display: block;
}

.image-preview {
    max-width: 100%;
    max-height: 400px;
    border-radius: 8px;
    box-shadow: var(--vs-shadow-hovered);
    margin-bottom: 15px;
}

.image-preview-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-remove-image {
    background: var(--danger-color, #e74c3c);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-remove-image:hover {
    background: var(--danger-hover, #c0392b);
}

/* Große Touch-freundliche Buttons */
@media (max-width: 768px) {
    .form-actions .btn {
        padding: 15px 25px;
        font-size: 16px;
        width: 100%;
        margin: 5px 0;
    }
    
    .form-row {
        grid-template-columns: 1fr !important;
    }
}

/* Select & Input Mobile-optimiert */
@media (max-width: 768px) {
    input[type="text"],
    input[type="number"],
    input[type="date"],
    select,
    textarea {
        font-size: 16px; /* Verhindert Zoom auf iOS */
        padding: 12px;
    }
}

/* Kamera-Button für Mobile */
.camera-options {
    display: flex;
    gap: 10px;
    margin-top: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.camera-options button {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
    background: var(--primary-color, #3498db);
    color: white;
}

.camera-options button:hover {
    background: var(--primary-hover, #2980b9);
}

@media (max-width: 768px) {
    .camera-options button {
        flex: 1;
        min-width: 140px;
        padding: 15px;
        font-size: 15px;
    }
}

/* Activity History Styles */
.activity-history {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-top: 25px;
    box-shadow: var(--vs-shadow-resting);
}

.activity-history h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
}

.activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.activity-item {
    padding: 12px;
    border-bottom: 1px solid #eee;
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.activity-item-content {
    flex: 1;
}

.activity-item-meta {
    font-size: 0.9em;
    color: #666;
    margin-top: 4px;
}

.activity-action-created { background: #d4edda; color: #155724; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; font-weight: 600; }
.activity-action-updated { background: #d1ecf1; color: #0c5460; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; font-weight: 600; }
.activity-action-deleted { background: #f8d7da; color: #721c24; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; font-weight: 600; }
</style>

<h2><?php echo t('form_edit_item'); ?></h2>

<?php echo showErrorMessages($errors); ?>

<?php if (!empty($duplikatWarnung)): ?>
<div style="
    background: var(--vs-warning-light);
    border: 1px solid var(--vs-warning);
    border-left: 4px solid var(--vs-warning);
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    flex-wrap: wrap;
">
    <i class="ti ti-alert-triangle" style="font-size:22px;flex-shrink:0;color:var(--vs-warning);"></i>
    <div style="flex:1;">
        <strong style="color:var(--vs-warning-text);">Mögliches Duplikat gefunden</strong><br>
        <span style="font-size:13px; color:var(--vs-warning-text);">
            Ein Gegenstand mit dem Namen <strong>„<?php echo htmlspecialchars($data['name']); ?>"</strong>
            existiert in deiner Sammlung bereits.
            Möchtest du den Namen trotzdem so speichern?
        </span>
    </div>
    <form method="POST" action="" enctype="multipart/form-data" style="margin:0; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <?php echo Security::getCSRFInput(); ?>
        <?php foreach ($_POST as $k => $v): ?>
            <?php if ($k !== 'csrf_token' && !is_array($v)): ?>
                <input type="hidden" name="<?php echo htmlspecialchars($k); ?>"
                       value="<?php echo htmlspecialchars($v); ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <input type="hidden" name="duplikat_bestaetigt" value="1">
        <button type="submit" style="
            padding: 8px 18px;
            background: var(--vs-warning);
            color: white;
            border: none;
            border-radius: 7px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
        "><i class="ti ti-check"></i> <?php echo tn('form_save_anyway', 'Trotzdem speichern'); ?></button>
        <button type="button" onclick="history.back()" style="
            padding: 8px 18px;
            background: white;
            border: 1px solid var(--vs-border);
            border-radius: 7px;
            font-size: 13px;
            color: var(--vs-text);
            cursor: pointer;
        "><i class="ti ti-pencil"></i> <?php echo tn('form_change_name', 'Namen ändern'); ?></button>
    </form>
</div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" class="form-single" id="editForm">
    <?php echo Security::getCSRFInput(); ?>
    <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($returnUrl); ?>">
    
    <div class="form-group">
        <label for="name"><?php echo t('form_name'); ?>: *</label>
        <input type="text" id="name" name="name" required maxlength="255"
               value="<?php echo htmlspecialchars($item['name']); ?>"
               autofocus>
    </div>
    
    <div class="form-group">
        <label for="raum_id"><?php echo t('form_location'); ?>:</label>
        <select id="raum_id" name="raum_id">
            <?php echo generateSelectOptions($raeume, $item['raum_id'], t('placeholder_select')); ?>
        </select>
    </div>
    
    <!-- ── Standort ──────────────────────────────────────────── -->
    <?php if (!empty($standorte)): ?>
    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="form-group">
            <label for="standort_raum"><?php echo t('standort_raum'); ?>:</label>
            <select id="standort_raum" name="standort_raum" class="form-control">
                <option value=""><?php echo t('standort_kein'); ?></option>
                <?php foreach ($standorte as $r): ?>
                    <option value="<?php echo $r['id']; ?>"
                        <?php echo ($standort_raum_id == $r['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="position_id"><?php echo t('standort_position'); ?>:</label>
            <select id="position_id" name="position_id" class="form-control" disabled>
                <option value=""><?php echo t('standort_position_waehlen'); ?></option>
            </select>
        </div>
    </div>
    <?php endif; ?>
    <!-- ── Ende Standort ──────────────────────────────────────── -->

    <div class="form-group">
        <label for="kategorie_id"><?php echo t('form_category'); ?>:</label>
        <select id="kategorie_id" name="kategorie_id">
            <?php echo generateSelectOptions($kategorien, $item['kategorie_id'], t('placeholder_select')); ?>
        </select>
    </div>
    
    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="form-group">
            <label for="kaufdatum"><?php echo t('form_purchase_date'); ?>:</label>
            <input type="date" id="kaufdatum" name="kaufdatum" 
                   value="<?php echo htmlspecialchars($item['kaufdatum'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="gelistet_am"><?php echo t('form_listed_date'); ?>:</label>
            <input type="date" id="gelistet_am" name="gelistet_am" 
                   value="<?php echo htmlspecialchars($item['gelistet_am'] ?? date('Y-m-d')); ?>">
        </div>
    </div>
    
    <div class="form-group">
        <label for="preis"><?php echo t('form_price'); ?>:</label>
        <input type="text" 
               id="preis" 
               name="preis" 
               inputmode="decimal"
               pattern="[0-9]*[.,]?[0-9]*"
               value="<?php echo htmlspecialchars($item['preis']); ?>"
               placeholder="0.00">
        <small style="color: #666; font-size: 12px;"><?php echo t('price_decimal_hint'); ?></small>
    </div>
    
    <!-- AKTUELLER WERT -->
    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="form-group">
            <label for="aktueller_wert"><i class="ti ti-trending-up" aria-hidden="true"></i> <?php echo t('col_aktueller_wert'); ?> (€):</label>
            <input type="text" id="aktueller_wert" name="aktueller_wert"
                   inputmode="decimal" pattern="[0-9]*[.,]?[0-9]*"
                   value="<?php echo htmlspecialchars($item['aktueller_wert'] ?? ''); ?>"
                   placeholder="0.00">
            <?php 
            $kauf = floatval($item['preis'] ?? 0);
            $akt  = isset($item['aktueller_wert']) && $item['aktueller_wert'] !== null ? floatval($item['aktueller_wert']) : null;
            if ($akt !== null && $kauf > 0):
                $diff = $akt - $kauf;
                $pct  = ($diff / $kauf) * 100;
                $color = $diff > 0 ? '#1a7a40' : ($diff < 0 ? '#c0392b' : '#595959');
                $sign  = $diff > 0 ? '+' : '';
            ?>
            <small style="color:<?php echo $color; ?>; font-weight:600;">
                <?php echo $sign . number_format($diff, 2, ',', '.') . ' € (' . $sign . number_format($pct, 1, ',', '.') . ' %)'; ?>
            </small>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="aktueller_wert_datum"><i class="ti ti-calendar" aria-hidden="true"></i> <?php echo t('col_bewertungsdatum'); ?>:</label>
            <input type="date" id="aktueller_wert_datum" name="aktueller_wert_datum"
                   value="<?php echo htmlspecialchars($item['aktueller_wert_datum'] ?? ''); ?>">
        </div>
    </div>
    
    <!-- MARKTWERT-ASSISTENT -->
    <?php if (defined('MARKTWERT_ASSISTENT_ENABLED') && MARKTWERT_ASSISTENT_ENABLED && defined('ANTHROPIC_API_KEY') && ANTHROPIC_API_KEY !== ''): ?>
    <div class="form-group" id="marktwert-assistent-block" style="margin-bottom: 16px;">
        <button type="button" id="marktwert-btn"
                onclick="marktwertSchaetzen()"
                style="background: var(--vs-accent); color: var(--vs-accent-text);
                       border: none; border-radius: 8px; padding: 10px 18px;
                       font-size: 14px; font-weight: 600; cursor: pointer;
                       display: inline-flex; align-items: center; gap: 8px;">
            <i class="ti ti-sparkles" aria-hidden="true"></i> Marktwert schätzen (KI)
        </button>
        <div id="marktwert-result" style="display:none; margin-top: 12px; padding: 14px;
             background: #f0f4ff; border: 1px solid #c7d2fe; border-radius: 10px; font-size: 14px;">
        </div>
    </div>
    <script>
    function marktwertSchaetzen() {
        const btn = document.getElementById('marktwert-btn');
        const result = document.getElementById('marktwert-result');
        btn.disabled = true;
        btn.textContent = '… Schätze Marktwert…';
        result.style.display = 'none';

        const data = new FormData();
        data.append('item_id',   '<?php echo $id; ?>');
        data.append('name',      document.getElementById('name')?.value || '<?php echo addslashes($item['name'] ?? ''); ?>');
        data.append('kategorie', document.querySelector('[name="kategorie_id"] option:checked')?.text || '');
        data.append('preis',     document.querySelector('[name="preis"]')?.value || '<?php echo $item['preis'] ?? ''; ?>');
        data.append('barcode',   document.querySelector('[name="barcode"]')?.value || '');
        data.append('notizen',   document.querySelector('[name="notizen"]')?.value || '');

        fetch('<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>/ajax/marktwert_assistent.php', {
            method: 'POST', body: data
        })
        .then(r => r.json())
        .then(d => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-sparkles" aria-hidden="true"></i> Marktwert schätzen (KI)';
            result.style.display = 'block';
            if (d.error) {
                result.innerHTML = '<span style="color:var(--vs-danger);"> ' + d.error + '</span>';
                return;
            }
            const konfidenzColor = d.konfidenz === 'hoch' ? '#1a7a40' : d.konfidenz === 'mittel' ? '#e67e22' : '#c0392b';
            let html = '<strong><i class="ti ti-currency-euro"></i> Geschätzter Marktwert: ';
            html += d.geschaetzter_wert !== null
                ? '<span style="color:var(--vs-success);">' + parseFloat(d.geschaetzter_wert).toLocaleString('de-DE') + ' €</span>'
                : '<span style="color:#999;">Nicht ermittelbar</span>';
            html += '</strong>';
            html += ' &nbsp;<span style="color:' + konfidenzColor + '; font-size:12px;">(' + d.konfidenz + ')</span>';
            if (d.begruendung) html += '<br><small style="color:#555; margin-top:6px; display:block;">' + d.begruendung + '</small>';
            if (d.quellen && d.quellen.length) {
                html += '<div style="margin-top:8px; font-size:12px; color:#777;">';
                d.quellen.forEach(q => {
                    html += '• ' + q.plattform;
                    if (q.preis_von && q.preis_bis) html += ': ' + q.preis_von + '–' + q.preis_bis + ' €';
                    if (q.url && q.url !== 'https://...') html += ' <a href="' + q.url + '" target="_blank" style="color:var(--vs-accent);">→</a>';
                    html += '<br>';
                });
                html += '</div>';
            }
            // Wert ins Feld übernehmen-Button
            if (d.geschaetzter_wert !== null) {
                html += '<button type="button" onclick="document.getElementById(\'aktueller_wert\').value=\'' + d.geschaetzter_wert + '\'; document.querySelector(\'[name=aktueller_wert_datum]\').value=new Date().toISOString().split(\'T\')[0];" '
                    + 'class="vs-btn vs-btn-primary vs-btn-sm" style="margin-top:8px;">'
                    + '<i class="ti ti-check"></i> Wert übernehmen</button>';
            }
            result.innerHTML = html;
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-sparkles" aria-hidden="true"></i> Marktwert schätzen (KI)';
            result.style.display = 'block';
            result.innerHTML = '<span style="color:var(--vs-danger);"> Verbindungsfehler</span>';
        });
    }
    </script>
    <?php endif; ?>

    <!-- WERTHISTORIE -->
    <?php
    try {
        $historie = $db->select("SELECT * FROM wert_historie WHERE wertsache_id = ? ORDER BY datum DESC LIMIT 10", [$id]);
    } catch(Exception $e) { $historie = []; }
    if (!empty($historie)):
    ?>
    <div class="form-group">
        <label><i class="ti ti-chart-line" aria-hidden="true"></i> <?php echo t('edit_value_history'); ?>:</label>
        <table style="width:100%; font-size:13px; border-collapse:collapse;">
            <thead>
                <tr style="background:var(--vs-surface-2);">
                    <th style="padding:6px 10px; text-align:left;"><?php echo t('settings_date'); ?></th>
                    <th style="padding:6px 10px; text-align:right;"><?php echo t('col_wert'); ?></th>
                    <th style="padding:6px 10px; text-align:right;"><?php echo t('col_wert_differenz'); ?></th>
                    <th style="padding:6px 10px; text-align:left;"><?php echo t('ins_note'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($historie as $h): 
                $hdiff = floatval($h['wert']) - $kauf;
                $hcolor = $hdiff > 0 ? '#1a7a40' : ($hdiff < 0 ? '#c0392b' : '#595959');
                $hsign  = $hdiff > 0 ? '+' : '';
            ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px 10px;"><?php echo date('d.m.Y', strtotime($h['datum'])); ?></td>
                    <td style="padding:6px 10px; text-align:right;"><?php echo number_format($h['wert'], 2, ',', '.') . ' €'; ?></td>
                    <td style="padding:6px 10px; text-align:right; color:<?php echo $hcolor; ?>; font-weight:600;"><?php echo $hsign . number_format($hdiff, 2, ',', '.') . ' €'; ?></td>
                    <td style="padding:6px 10px; color:#666;"><?php echo htmlspecialchars($h['notiz'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- FREIE FELDER -->
    <?php
    // Labels aus Spalten-Konfiguration holen
    $custom1_label = getSpaltenLabel('custom1');
    $custom2_label = getSpaltenLabel('custom2');
    ?>
    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="form-group">
            <label for="custom1_wert"><i class="ti ti-star" aria-hidden="true"></i> <?php echo htmlspecialchars($custom1_label); ?>:</label>
            <input type="text" id="custom1_wert" name="custom1_wert"
                   value="<?php echo htmlspecialchars($item['custom1_wert'] ?? ''); ?>"
                   placeholder="<?php echo t('custom_field_placeholder'); ?>">
            <fieldset style="border:none; padding:0; margin:4px 0 0 0;">
                <legend style="font-size:var(--vs-text-xs); color:var(--vs-text-muted); font-weight:normal; padding:0; margin-bottom:2px;"><?php echo t('custom_field_type'); ?></legend>
                <label style="margin-right:12px;"><input type="radio" name="custom1_typ" value="text" <?php echo ($item['custom1_typ'] ?? 'text') === 'text' ? 'checked' : ''; ?>> Text</label>
                <label><input type="radio" name="custom1_typ" value="zahl" <?php echo ($item['custom1_typ'] ?? 'text') === 'zahl' ? 'checked' : ''; ?>> <?php echo t('custom_field_number'); ?></label>
            </fieldset>
        </div>
        <div class="form-group">
            <label for="custom2_wert"><i class="ti ti-star" aria-hidden="true"></i> <?php echo htmlspecialchars($custom2_label); ?>:</label>
            <input type="text" id="custom2_wert" name="custom2_wert"
                   value="<?php echo htmlspecialchars($item['custom2_wert'] ?? ''); ?>"
                   placeholder="<?php echo t('custom_field_placeholder'); ?>">
            <fieldset style="border:none; padding:0; margin:4px 0 0 0;">
                <legend style="font-size:var(--vs-text-xs); color:var(--vs-text-muted); font-weight:normal; padding:0; margin-bottom:2px;"><?php echo t('custom_field_type'); ?></legend>
                <label style="margin-right:12px;"><input type="radio" name="custom2_typ" value="text" <?php echo ($item['custom2_typ'] ?? 'text') === 'text' ? 'checked' : ''; ?>> Text</label>
                <label><input type="radio" name="custom2_typ" value="zahl" <?php echo ($item['custom2_typ'] ?? 'text') === 'zahl' ? 'checked' : ''; ?>> <?php echo t('custom_field_number'); ?></label>
            </fieldset>
        </div>
    </div>

    <div class="form-group">
        <label for="notizen"><?php echo t('form_notes'); ?>:</label>
        <textarea id="notizen" name="notizen" rows="5" maxlength="5000"><?php echo htmlspecialchars($item['notizen']); ?></textarea>
    </div>

    <div class="form-group">
        <label for="barcode"><i class="ti ti-barcode" aria-hidden="true"></i> Barcode / ISBN:</label>
        <div style="display:flex; gap:8px; align-items:center;">
            <input type="text" id="barcode" name="barcode" maxlength="100"
                   value="<?php echo htmlspecialchars($item['barcode'] ?? ''); ?>"
                   placeholder="<?php echo t('barcode_placeholder'); ?>"
                   style="flex:1;">
            <button type="button" id="btnScanBarcode" onclick="startBarcodeScanner()" 
                    style="padding:8px 14px; background:#2980b9; color:white; border:none; border-radius:6px; cursor:pointer; white-space:nowrap;">
                <?php echo t('barcode_scan_button'); ?>
            </button>
        </div>
        <div id="isbn-lookup-result" style="display:none; margin-top:8px; padding:10px 12px; border-left:4px solid #16a34a; border-radius:4px; font-size:13px;"></div>
    </div>
    
    <div class="form-group">
        <label for="erstellt_von"><?php echo t('form_created_by'); ?>:</label>
        <input type="text" id="erstellt_von" name="erstellt_von" maxlength="100"
               value="<?php echo htmlspecialchars($item['erstellt_von'] ?? $_SESSION['username']); ?>"
               <?php echo userSeesOnlyOwnItems() ? 'readonly' : ''; ?>>
        <small><?php echo t('form_help_created_by_empty'); ?></small>
    </div>
    
    <!-- MULTI-IMAGE UPLOAD -->
    <div class="form-group">
        <label><i class="ti ti-photo" aria-hidden="true"></i> <?php echo t('dash_storage_images'); ?>:</label>
        <div id="multi-image-container">
            <p style="color:#999; font-size:13px;"><?php echo t('loading'); ?></p>
        </div>
    </div>
    

    
    <!-- ÖFFENTLICH SICHTBAR -->
    <div class="form-group" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px 16px;">
        <label style="display:flex; align-items:center; gap:12px; cursor:pointer; user-select:none;">
            <input type="checkbox" name="oeffentlich" value="1"
                   <?php echo !empty($item['oeffentlich']) ? 'checked' : ''; ?>
                   style="width:18px; height:18px; flex-shrink:0;">
            <span>
                <i class="ti ti-link" aria-hidden="true"></i> <?php echo t('public_visible_label'); ?>
                <small style="display:block; font-weight:400; color:#6b7280; font-size:12px; margin-top:2px;">
                    <?php echo t('public_visible_hint'); ?>
                </small>
            </span>
        </label>
    </div>

    <div class="form-actions">
        <button type="submit" class="vs-btn vs-btn-primary"><?php echo t('form_save'); ?></button>
        <?php if ($dokumente_aktiv ?? false): ?>
        <a href="manage_documents.php?id=<?php echo $id; ?>" class="vs-btn vs-btn-secondary">
            📄 <?php echo t('form_documents'); ?>
            <?php if (($item['dokumente_anzahl'] ?? 0) > 0): ?>
                (<?php echo $item['dokumente_anzahl']; ?>)
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <a href="<?php echo htmlspecialchars($returnUrl); ?>" class="vs-btn vs-btn-secondary">❌ <?php echo t('form_cancel'); ?></a>
    </div>
</form>

<!-- Barcode Scanner Modal -->
<div id="barcodeScannerModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:9999; flex-direction:column; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:20px; max-width:420px; width:95%; text-align:center;">
        <h3 style="margin-bottom:12px;">📷 <?php echo t('barcode_scan_title'); ?></h3>
        <div style="position:relative; width:100%; border-radius:8px; overflow:hidden; background:#000;">
            <video id="barcodeVideo" style="width:100%; display:block;" autoplay playsinline muted></video>
            <div style="position:absolute; top:50%; left:10%; right:10%; height:2px; background:rgba(52,152,219,0.8); transform:translateY(-50%); pointer-events:none;"></div>
        </div>
        <p style="margin-top:10px; font-size:13px; color:#666;"><?php echo t('barcode_in_frame'); ?></p>
        <button type="button" onclick="stopBarcodeScanner()" 
                style="margin-top:12px; padding:10px 24px; background:#e74c3c; color:white; border:none; border-radius:6px; cursor:pointer; font-size:14px;">
            ✕ <?php echo t('btn_cancel'); ?>
        </button>
    </div>
</div>



<!-- ===== ACTIVITY HISTORY ===== -->
<?php
try {
    // Spaltennamen sind deutsch: aktion, tabelle, datensatz_id, zeitstempel,
    // alt_wert. Bis 23.09.2026 fragte diese Stelle table_name/record_id/
    // timestamp ab — englische Namen, die es nie gab. Der Verlauf blieb
    // deshalb auf JEDER Instanz leer, und die Meldung "Unknown column
    // 'table_name'" landete still im Fehlerprotokoll. Gefunden wurde sie erst,
    // als es eines gab, in das man hineinsehen kann. In helpers.php steht beim
    // Schreiben seit Monaten der Kommentar "FIXED: Deutsche Spaltennamen" —
    // beim Lesen wurde es vergessen.
    // Die Namen der Anzeige bleiben unberuehrt: sie kommen als Alias.
    $history = $db->select(
        "SELECT al.aktion AS action, al.zeitstempel AS timestamp,
                al.alt_wert AS old_values,
                COALESCE(u.username, 'unbekannt') AS username
         FROM activity_log al
         LEFT JOIN users u ON u.id = al.user_id
         WHERE al.tabelle = 'wertsachen' AND al.datensatz_id = ?
         ORDER BY al.zeitstempel DESC LIMIT 10",
        [$id]
    );
    
    if (!empty($history)):
?>
<div class="activity-history">
    <h3>📜 <?php echo t('activity_history_title'); ?></h3>
    <ul class="activity-list">
        <?php foreach ($history as $entry): ?>
            <li class="activity-item">
                <span class="activity-item-icon"><?php echo getActionIcon($entry['action']); ?></span>
                <div class="activity-item-content">
                    <div>
                        <strong><?php echo htmlspecialchars($entry['username']); ?></strong>
                        <span style="color: #666;">·</span>
                        <span class="activity-action-<?php echo $entry['action']; ?>">
                            <?php echo getActionText($entry['action']); ?>
                        </span>
                    </div>
                    <div class="activity-item-meta">
                        <?php echo formatDateTime($entry['timestamp']); ?>
                    </div>
                    <?php if ($entry['old_values']): ?>
                        <?php echo formatChanges($entry['old_values']); ?>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <a href="activity_log.php?table=wertsachen" style="display: block; text-align: center; margin-top: 15px; color: var(--primary-color);">
        <?php echo t('activity_view_full'); ?> →
    </a>
</div>
<?php 
    endif;
} catch (PDOException $e) { 
    // Tabelle existiert noch nicht - kein Problem
} 
?>
<!-- ========================= -->

<script>
// Übersetzungen für JavaScript
const translations = {
    errorSize: '<?php echo sprintf(t("upload_error_size"), (MAX_FILE_SIZE / 1024 / 1024)); ?>',
    errorType: '<?php echo t("upload_error_type"); ?>',
    confirmCancel: '<?php echo t("confirm_cancel_unsaved"); ?>'
};

// Multi-Image Upload initialisieren
document.addEventListener('DOMContentLoaded', function() {
    multiImageUpload = new MultiImageUpload(
        <?php echo $id; ?>,
        '<?php echo Security::generateCSRFToken(); ?>',
        { maxImages: 10, maxFileSize: <?php echo MAX_FILE_SIZE; ?>, errorSize: translations.errorSize }
    );
});


// ============================================================
// AUTOSAVE – speichert Formularfelder in localStorage (onblur)
// Draft-Key enthält Item-ID damit verschiedene Items nicht kollidieren
// ============================================================
(function() {
    var ITEM_ID = <?php echo $id; ?>;
    var DRAFT_KEY = 'wert_edit_draft_' + ITEM_ID;
    var FIELDS = ['name', 'raum_id', 'kategorie_id', 'kaufdatum', 'gelistet_am', 'preis', 'erstellt_von', 'notizen'];
    var INITIAL = {};

    function captureInitial() {
        FIELDS.forEach(function(id) {
            var el = document.getElementById(id);
            INITIAL[id] = el ? el.value : undefined;
        });
    }

    function hasRealChanges() {
        return FIELDS.some(function(id) {
            var el = document.getElementById(id);
            return el && el.value !== INITIAL[id];
        });
    }

    function saveDraft() {
        // Nur speichern, wenn sich gegenüber dem Ausgangszustand wirklich etwas geändert hat
        if (!hasRealChanges()) { clearDraft(); return; }
        var draft = { _saved: new Date().toLocaleString('de-DE') };
        FIELDS.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) draft[id] = el.value;
        });
        try { localStorage.setItem(DRAFT_KEY, JSON.stringify(draft)); } catch(e) {}
    }

    function loadDraft(draft) {
        FIELDS.forEach(function(id) {
            var el = document.getElementById(id);
            if (el && draft[id] !== undefined) el.value = draft[id];
        });
    }

    function clearDraft() {
        try { localStorage.removeItem(DRAFT_KEY); } catch(e) {}
    }

    function showBanner(draft) {
        var banner = document.createElement('div');
        banner.id = 'autosave-banner';
        banner.style.cssText = 'background:#fff8e1; border:1px solid #f59e0b; border-radius:10px; padding:16px 20px; margin-bottom:18px; display:flex; align-items:center; gap:14px; flex-wrap:wrap; font-size:14px;';
        banner.innerHTML = '<span style="font-size:20px;">💾</span>'
            + '<div style="flex:1;"><strong>Ungespeicherter Entwurf vom ' + draft._saved + '</strong><br>'
            + '<span style="font-size:12px;color:#666;">Möchtest du die zuletzt eingegebenen Werte wiederherstellen?</span></div>'
            + '<button id="autosave-restore" style="padding:8px 16px; background:#f59e0b; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600;">Wiederherstellen</button>'
            + '<button id="autosave-discard" style="padding:8px 16px; background:white; border:1px solid #d1d5db; border-radius:8px; cursor:pointer;">Verwerfen</button>';

        var form = document.getElementById('editForm') || document.querySelector('form');
        if (form) form.parentNode.insertBefore(banner, form);

        document.getElementById('autosave-restore').addEventListener('click', function() {
            loadDraft(draft);
            banner.remove();
        });
        document.getElementById('autosave-discard').addEventListener('click', function() {
            clearDraft();
            banner.remove();
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        captureInitial();

        // Prüfen ob Entwurf vorhanden
        try {
            var raw = localStorage.getItem(DRAFT_KEY);
            if (raw) {
                var draft = JSON.parse(raw);
                // Nur anzeigen wenn sich der Entwurf wirklich vom Ausgangszustand unterscheidet
                var draftDiffers = draft._saved && FIELDS.some(function(id) {
                    return draft[id] !== undefined && draft[id] !== INITIAL[id];
                });
                if (draftDiffers) {
                    showBanner(draft);
                } else {
                    clearDraft();
                }
            }
        } catch(e) {}

        // onblur auf alle Felder
        FIELDS.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('blur', saveDraft);
        });

        // Entwurf löschen wenn Formular gespeichert wird
        var form = document.getElementById('editForm') || document.querySelector('form');
        if (form) form.addEventListener('submit', function() { clearDraft(); });
    });
})();
</script>
<script>window.VS_I18N = { confirm_delete_image: <?php echo json_encode(t('confirm_delete_image')); ?> };</script>
<script src="js/multi_image_upload.js"></script>
<script>
// Sicherheitsnetz: Form-Submit blockieren wenn Lightbox offen ist
document.addEventListener('DOMContentLoaded', function() {
    var editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            var overlay = document.getElementById('lightboxOverlay');
            if (overlay && overlay.classList.contains('active')) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        }, true); // capture phase - läuft vor allem anderen
    }
});
</script>


<?php // Das Scanner-Fenster steht einmal, direkt nach dem Formular. Bis 4.3.32
      // stand hier eine zweite Kopie mit denselben ids (barcodeScannerModal,
      // barcodeVideo) - getElementById fand immer nur die erste. ?>
<script src="js/zxing-browser.min.js"></script>
<script>
(function() {
    let stream     = null;
    let animFrame  = null;

    function onBarcodeFound(code) {
        stopBarcodeScanner();
        document.getElementById('barcode').value = code;
        const val = code.replace(/[^0-9X]/gi, '').toUpperCase();
        if (val.length >= 8) lookupBarcode(val);
    }

    // ── Native BarcodeDetector: Desktop Chrome / Android ─────────────────
    async function startNativeScanner() {
        const modal = document.getElementById('barcodeScannerModal');
        modal.style.display = 'flex';
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
            });
            const video = document.getElementById('barcodeVideo');
            video.srcObject = stream;
            await video.play();
            const detector = new BarcodeDetector({
                formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'qr_code', 'upc_a', 'upc_e']
            });
            const scan = async () => {
                if (!stream) return;
                try {
                    const codes = await detector.detect(video);
                    if (codes.length > 0) { onBarcodeFound(codes[0].rawValue); return; }
                } catch(e) {}
                animFrame = requestAnimationFrame(scan);
            };
            animFrame = requestAnimationFrame(scan);
        } catch(e) {
            stopBarcodeScanner();
            vsAlert(<?php echo json_encode(t('error_camera_unavailable')); ?>.replace('%s', function () { return e.message; }));
        }
    }

    window.startBarcodeScanner = function() {
        if ('BarcodeDetector' in window) {
            startNativeScanner();
            return;
        }
        // iOS/Safari: manuelle Eingabe
        // Text aus der Sprachdatei; json_encode liefert die Anfuehrungszeichen
        // mit und maskiert JS-sicher (Muster wie backend/locations.php).
        vsPrompt(<?php echo json_encode(t('barcode_manual_prompt')); ?>, '').then(function (code) {
            if (code && code.trim()) {
                document.getElementById('barcode').value = code.trim();
                const val = code.trim().replace(/[^0-9X]/gi, '').toUpperCase();
                if (val.length >= 8) lookupBarcode(val);
            }
        });
    };

    window.stopBarcodeScanner = function() {
        if (animFrame) { cancelAnimationFrame(animFrame); animFrame = null; }
        if (stream)    { stream.getTracks().forEach(t => t.stop()); stream = null; }
        const video = document.getElementById('barcodeVideo');
        if (video) video.srcObject = null;
        document.getElementById('barcodeScannerModal').style.display = 'none';
    };

    window.lookupBarcode = async function(code) {
        const clean = code.replace(/[^0-9X]/gi, '').toUpperCase();
        const res   = document.getElementById('isbn-lookup-result');
        res.style.display = 'block';
        res.textContent   = '⏳ Produktdaten werden gesucht…';
        res.style.background = '#f0fdf4'; res.style.borderColor = '#16a34a'; res.style.color = '#166534';
        try {
            const r    = await fetch(`barcode_lookup.php?code=${clean}`);
            const data = await r.json();
            if (!data.found) {
                res.style.background = '#fef2f2'; res.style.borderColor = '#dc2626'; res.style.color = '#991b1b';
                res.textContent = '❌ Keine Produktdaten gefunden – bitte manuell eintragen.';
                return;
            }
            const nameEl  = document.getElementById('name');
            const notesEl = document.getElementById('notizen');
            const dateEl  = document.getElementById('kaufdatum');
            const authEl  = document.getElementById('erstellt_von');
            if (data.name && !nameEl.value) nameEl.value = data.name;
            if (data.year) {
                const y = (data.year + '').match(/\d{4}/)?.[0];
                if (y && !dateEl.value) dateEl.value = y + '-01-01';
            }
            if (data.type === 'book' && data.brand && !authEl.value) authEl.value = data.brand;
            if (data.description && !notesEl.value) {
                notesEl.value = data.description;
            } else if (!notesEl.value) {
                const lines = [];
                if (data.brand    && data.type === 'book') lines.push('Autor: ' + data.brand);
                if (data.brand    && data.type !== 'book') lines.push('Marke: ' + data.brand);
                if (data.category) lines.push('Kategorie: ' + data.category);
                if (data.year)     lines.push('Jahr: ' + data.year);
                if (lines.length)  notesEl.value = lines.join('\n');
            }
            if (data.cover) {
                window._isbnCoverUrl = data.cover;
                const src = data.source ? ` <span style="font-size:12px;color:#595959;">(${data.source})</span>` : '';
                res.innerHTML = `✅ <strong>${data.name}</strong>${data.brand ? ' – ' + data.brand : ''}${data.year ? ' (' + data.year + ')' : ''}${src}<br>
                    <button type="button" onclick="applyISBNCover()" style="background:none; border:none; padding:0; color:#0369a1; font-size:12px; cursor:pointer; text-decoration:underline;">📷 Bild übernehmen</button>`;
            } else {
                const src = data.source ? ` <span style="font-size:12px;color:#595959;">(${data.source})</span>` : '';
                res.innerHTML = `✅ <strong>${data.name}</strong>${data.brand ? ' – ' + data.brand : ''}${data.year ? ' (' + data.year + ')' : ''}${src}`;
            }
            res.style.background = '#f0fdf4'; res.style.borderColor = '#16a34a'; res.style.color = '#166534';
        } catch(e) {
            res.style.background = '#fef2f2'; res.style.borderColor = '#dc2626'; res.style.color = '#991b1b';
            res.textContent = '⚠️ Fehler beim Abrufen: ' + e.message;
        }
    };
    window.lookupISBN = window.lookupBarcode;

    window.applyISBNCover = async function() {
        if (!window._isbnCoverUrl) return;
        try {
            const r    = await fetch('cover_proxy.php?url=' + encodeURIComponent(window._isbnCoverUrl));
            if (!r.ok) throw new Error('HTTP ' + r.status);
            const blob = await r.blob();
            const file = new File([blob], 'cover.jpg', { type: blob.type });
            const dt   = new DataTransfer();
            dt.items.add(file);
            // In edit.php kein single-image input — Lookup-Ergebnis anzeigen reicht
            document.getElementById('isbn-lookup-result').innerHTML +=
                '<br><span style="color:var(--vs-success-text);font-size:12px;">✅ Bild wird nach Speichern übernommen</span>';
        } catch(e) {
            vsAlert(<?php echo json_encode(t('error_cover_load')); ?>.replace('%s', function () { return e.message; }));
        }
    };

    document.getElementById('barcode')?.addEventListener('blur', function() {
        const val = this.value.replace(/[^0-9X]/gi, '').toUpperCase();
        if (val.length >= 8) lookupBarcode(val);
    });
})();
</script>


<?php include 'footer_next_page.php'; ?>
<!-- ── Standort Cascading Dropdown ─────────────────────────────────────── -->
<script>
(function () {
    var raumSelect     = document.getElementById('standort_raum');
    var positionSelect = document.getElementById('position_id');
    if (!raumSelect || !positionSelect) return;

    var currentPositionId = <?php echo (int)($item['position_id'] ?? 0); ?>;

    function ladePositionen(raumId, preselectId) {
        positionSelect.innerHTML = '<option value="">Position wählen…</option>';
        if (!raumId) { positionSelect.disabled = true; return; }
        fetch('ajax/get_positionen.php?raum_id=' + raumId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                positionSelect.disabled = false;
                data.forEach(function(p) {
                    var opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.name;
                    if (preselectId && p.id == preselectId) opt.selected = true;
                    positionSelect.appendChild(opt);
                });
            })
            .catch(function() { positionSelect.disabled = true; });
    }

    raumSelect.addEventListener('change', function() {
        ladePositionen(this.value, null);
    });

    // Beim Laden: Positionen des gespeicherten Raums vorladen und Position vorauswählen
    if (raumSelect.value) {
        ladePositionen(raumSelect.value, currentPositionId);
    }
})();
</script>
