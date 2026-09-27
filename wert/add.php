<?php
// add.php - MIT ÜBERSETZUNGEN - CSS FIX
require_once 'db.php';
require_once 'helpers.php';
require_once 'helpers_images.php';
requireLogin();
define('PAGE_TITLE', t('form_new_item') . ' - ' . t('app_title'));

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateRequest();
    
    // Daten bereinigen
    $data = sanitizeWertsachenInput($_POST);
    
    // Validierung
    $errors = validateWertsachenData($data);
    
    // Duplikat-Prüfung (weiche Warnung — nur wenn kein hard override)
    $duplikatWarnung = false;
    if (empty($errors) && !isset($_POST['duplikat_bestaetigt'])) {
        $ersteller_check = trim($_POST['erstellt_von'] ?? '');
        if (empty($ersteller_check)) {
            $user_check = $db->selectOne("SELECT standard_ersteller FROM users WHERE username = ?", [$_SESSION['username']]);
            $ersteller_check = $user_check['standard_ersteller'] ?? $_SESSION['username'];
        }
        $existing = $db->selectOne(
            "SELECT id FROM wertsachen WHERE name = ? AND erstellt_von = ? LIMIT 1",
            [$data['name'], $ersteller_check]
        );
        if ($existing) {
            $duplikatWarnung = true;
        }
    }
    
    // Datei-Upload
    $uploadResult = handleFileUpload($_FILES['bild'] ?? null);
    if (isset($uploadResult['errors'])) {
        $errors = array_merge($errors, $uploadResult['errors']);
    }
    
    if (empty($errors) && !$duplikatWarnung) {
        try {
            // Ersteller aus Formular oder Standard
            $ersteller = trim($_POST['erstellt_von'] ?? '');
            if (empty($ersteller)) {
                $user = $db->selectOne("SELECT standard_ersteller FROM users WHERE username = ?", [$_SESSION['username']]);
                $ersteller = $user['standard_ersteller'] ?? $_SESSION['username'];
            }
            
            // Gelistet am Datum
            $gelistet_am = trim($_POST['gelistet_am'] ?? '');
            if (empty($gelistet_am)) {
                $gelistet_am = date('Y-m-d'); // Heute
            }
            
            $custom1_wert = trim($_POST['custom1_wert'] ?? '') ?: null;
            $custom1_typ  = in_array($_POST['custom1_typ'] ?? '', ['text','zahl']) ? $_POST['custom1_typ'] : 'text';
            $custom2_wert = trim($_POST['custom2_wert'] ?? '') ?: null;
            $custom2_typ  = in_array($_POST['custom2_typ'] ?? '', ['text','zahl']) ? $_POST['custom2_typ'] : 'text';
            [$standort_id, $position_id] = standortAusFormular($db, $_POST);

            $sql = "INSERT INTO wertsachen (name, raum_id, kategorie_id, kaufdatum, preis, notizen, bild, erstellt_von, gelistet_am, custom1_wert, custom1_typ, custom2_wert, custom2_typ, barcode, position_id, standort_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $newId = $db->insert($sql, [
                $data['name'],
                $data['raum_id'],
                $data['kategorie_id'],
                $data['kaufdatum'],
                $data['preis'],
                $data['notizen'],
                $uploadResult['filename'],
                $ersteller,
                $gelistet_am,
                $custom1_wert,
                $custom1_typ,
                $custom2_wert,
                $custom2_typ,
                $data['barcode'] ?? null,
                $position_id,
                $standort_id,
            ]);
            
            Security::logSecurityEvent('item_created', [
                'item_name' => $data['name'],
                'id' => $newId
            ]);
            
            // ===== ACTIVITY LOG =====
            logActivity('created', 'wertsachen', $newId, $data['name'], null, [
                'name' => $data['name'],
                'kategorie_id' => $data['kategorie_id'],
                'raum_id' => $data['raum_id'],
                'preis' => $data['preis'],
                'kaufdatum' => $data['kaufdatum']
            ]);
            // ========================
            
            // Wenn Bild hochgeladen: auch in item_images speichern
            if (!empty($uploadResult['filename'])) {
                addItemImage($newId, $uploadResult['filename'], true);
            }
            
            // Weiterleitung zu edit.php für weitere Bilder
            // ?new=1 zeigt Hinweis "Gespeichert! Weitere Bilder hochladen?"
            redirectWithMessage('edit.php?id=' . $newId . '&new=1', t('msg_item_created'));
            
        } catch (PDOException $e) {
            $errors[] = handleDatabaseError($e, 'add_item');
        }
    }
}

try {
    $raeume = $db->select("SELECT * FROM raeume ORDER BY name");
    $kategorien = $db->select("SELECT * FROM kategorien ORDER BY name");
    $standorte = $db->select("SELECT id, name FROM standorte ORDER BY name");
    
    // Standard-Ersteller laden
    $user = $db->selectOne("SELECT standard_ersteller FROM users WHERE username = ?", [$_SESSION['username']]);
    $standard_ersteller = $user['standard_ersteller'] ?? $_SESSION['username'];
} catch (PDOException $e) {
    die(t('error_loading_data'));
}

include 'header_next_page.php';
?>

<style>
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
    display: block;
    border: 3px dashed var(--vs-border);
    border-radius: 12px;
    padding: 40px 20px;
    text-align: center;
    background: var(--vs-surface-2);
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 15px;
    position: relative;
}

.upload-zone:hover {
    border-color: var(--vs-accent);
    background: var(--vs-accent-light);
}

.upload-zone.drag-over {
    border-color: var(--vs-success);
    background: var(--vs-success-light);
    transform: scale(1.02);
}

.upload-zone-icon {
    font-size: 3em;
    margin-bottom: 15px;
    color: var(--vs-accent);
}

.upload-zone-text {
    font-size: 1.1em;
    color: var(--vs-text);
    margin-bottom: 10px;
}

.upload-zone-hint {
    font-size: 0.9em;
    color: var(--vs-text-muted);
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

/* Bildvorschau */
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
    background: var(--vs-danger);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-remove-image:hover {
    background: var(--vs-accent-hover);
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
    background: var(--vs-accent);
    color: white;
}

.camera-options button:hover {
    background: var(--vs-accent-hover);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .camera-options button {
        flex: 1;
        min-width: 140px;
        padding: 15px;
        font-size: 15px;
    }
}
</style>

<h2><?php echo t('form_new_item'); ?></h2>

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
            Möchtest du ihn trotzdem speichern?
        </span>
    </div>
    <form method="POST" style="margin:0; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
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
        <a href="add.php" style="
            padding: 8px 18px;
            background: white;
            border: 1px solid var(--vs-border);
            border-radius: 7px;
            font-size: 13px;
            color: var(--vs-text);
            text-decoration: none;
        "><i class="ti ti-pencil"></i> <?php echo tn('form_change_name', 'Namen ändern'); ?></a>
    </form>
</div>
<?php endif; ?>

<style>
.form-section {
    background: var(--vs-surface);
    border-radius: var(--vs-r-lg);
    box-shadow: var(--vs-shadow-resting);
    margin-bottom: 20px;
    overflow: hidden;
}
.form-section-header {
    padding: 14px 20px;
    font-size: 15px;
    font-weight: 600;
    color: white;
    display: flex;
    align-items: center;
    gap: 8px;
}
.form-section-header.blue   { background: var(--vs-accent); }
.form-section-header.green  { background: var(--vs-success); }
.form-section-header.purple { background: var(--vs-purple); }
.form-section-header.gray   { background: var(--vs-text); }
.form-section-body {
    padding: 20px;
}

/* Label-Abstand zu Feldern */
.form-section-body .form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: var(--vs-text-sm);
    font-weight: var(--vs-weight-medium);
    color: var(--vs-text);
}
.form-section-body .form-group {
    margin-bottom: var(--vs-sp-5);
}
</style>

<form method="POST" action="" enctype="multipart/form-data" class="form-single" id="addForm">
    <?php echo Security::getCSRFInput(); ?>

    <!-- ── Sektion 1: Basisdaten ──────────────────────────────── -->
    <div class="form-section">
        <div class="form-section-header blue"><i class="ti ti-package" aria-hidden="true"></i> <?php echo t('form_section_basic'); ?></div>
        <div class="form-section-body">

    <div class="form-group">
        <label for="name"><?php echo t('form_name'); ?>: *</label>
        <input type="text" id="name" name="name" required maxlength="255"
               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
               autofocus>
    </div>
    
    <div class="form-group">
        <label for="raum_id"><?php echo t('form_location'); ?>:</label>
        <select id="raum_id" name="raum_id">
            <?php echo generateSelectOptions($raeume, $_POST['raum_id'] ?? null, t('placeholder_select')); ?>
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
                        <?php echo (isset($_POST['standort_raum']) && $_POST['standort_raum'] == $r['id']) ? 'selected' : ''; ?>>
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
            <?php echo generateSelectOptions($kategorien, $_POST['kategorie_id'] ?? null, t('placeholder_select')); ?>
        </select>
    </div>

    <div class="form-group">
        <label for="erstellt_von"><?php echo t('form_created_by'); ?>:</label>
        <input type="text" id="erstellt_von" name="erstellt_von" maxlength="100"
               value="<?php echo htmlspecialchars($_POST['erstellt_von'] ?? $standard_ersteller); ?>"
               placeholder="<?php echo htmlspecialchars($standard_ersteller); ?>">
        <small><?php echo sprintf(t('form_help_created_by_default'), htmlspecialchars($standard_ersteller)); ?></small>
    </div>

        </div>
    </div>

    <!-- ── Sektion 2: Wert & Kauf ─────────────────────────────── -->
    <div class="form-section">
        <div class="form-section-header green"><i class="ti ti-currency-euro" aria-hidden="true"></i> <?php echo t('form_section_value'); ?></div>
        <div class="form-section-body">

    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="form-group">
            <label for="kaufdatum"><?php echo t('form_purchase_date'); ?>:</label>
            <input type="date" id="kaufdatum" name="kaufdatum" 
                   value="<?php echo htmlspecialchars($_POST['kaufdatum'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="gelistet_am"><?php echo t('form_listed_date'); ?>:</label>
            <input type="date" id="gelistet_am" name="gelistet_am" 
                   value="<?php echo htmlspecialchars($_POST['gelistet_am'] ?? date('Y-m-d')); ?>">
        </div>
    </div>
    
    <style>
    @media (max-width: 768px) {
        .form-row { grid-template-columns: 1fr !important; gap: 0 !important; }
    }
    </style>
    
    <div class="form-group">
        <label for="preis"><?php echo t('form_price'); ?>:</label>
        <input type="text" 
               id="preis" 
               name="preis" 
               inputmode="decimal"
               pattern="[0-9]*[.,]?[0-9]*"
               value="<?php echo htmlspecialchars($_POST['preis'] ?? '0'); ?>"
               placeholder="0.00">
        <small style="color: #595959; font-size: 12px;"><?php echo t('price_decimal_hint'); ?></small>
    </div>

    <!-- BARCODE -->
    <div class="form-group">
        <label for="barcode"><i class="ti ti-barcode" aria-hidden="true"></i> Barcode / ISBN:</label>
        <div style="display:flex; gap:8px; align-items:center;">
            <input type="text" id="barcode" name="barcode" maxlength="100"
                   value="<?php echo htmlspecialchars($_POST['barcode'] ?? ''); ?>"
                   placeholder="<?php echo t('barcode_placeholder'); ?>"
                   style="flex:1;">
            <button type="button" id="btnScanBarcode" onclick="startBarcodeScanner()"
                    style="padding:8px 14px; background:#1a6fb5; color:white; border:none; border-radius:6px; cursor:pointer; font-size:13px; white-space:nowrap;">
                <?php echo t('barcode_scan_button'); ?>
            </button>
        </div>
        <div id="isbn-lookup-result" style="display:none; margin-top:8px; padding:10px 14px; background:#f0fdf4; border-left:4px solid #16a34a; border-radius:4px; font-size:13px; color:#166534;"></div>
    </div>

    <!-- Barcode Scanner Modal -->
    <div id="barcodeScannerModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:9999; flex-direction:column; align-items:center; justify-content:center;">
        <div style="background:white; border-radius:12px; padding:20px; max-width:420px; width:95%; text-align:center;">
            <h3 style="margin-bottom:12px;"><i class="ti ti-barcode" aria-hidden="true"></i> Barcode scannen</h3>
            <div style="position:relative; width:100%; border-radius:8px; overflow:hidden; background:#000;">
                <video id="barcodeVideo" style="width:100%; display:block;" autoplay playsinline muted></video>
                <div style="position:absolute; top:50%; left:10%; right:10%; height:2px; background:rgba(52,152,219,0.8); transform:translateY(-50%); pointer-events:none;"></div>
            </div>
            <p style="margin-top:10px; font-size:13px; color:#595959;"><?php echo t('barcode_in_frame'); ?></p>
            <button type="button" onclick="stopBarcodeScanner()"
                    style="margin-top:12px; padding:10px 24px; background:#e74c3c; color:white; border:none; border-radius:6px; cursor:pointer; font-size:14px;">
                ✕ Abbrechen
            </button>
        </div>
    </div>

        </div>
    </div>

    <!-- ── Sektion 3: Notizen ─────────────────────────────────── -->
    <div class="form-section">
        <div class="form-section-header purple"><i class="ti ti-notes" aria-hidden="true"></i> <?php echo t('form_section_notes'); ?></div>
        <div class="form-section-body">

    <div class="form-group">
        <label for="notizen"><?php echo t('form_notes'); ?>:</label>
        <textarea id="notizen" name="notizen" rows="5" maxlength="5000"><?php echo htmlspecialchars($_POST['notizen'] ?? ''); ?></textarea>
    </div>

    <!-- FREIE FELDER -->
    <?php
    $custom1_label = getSpaltenLabel('custom1');
    $custom2_label = getSpaltenLabel('custom2');
    ?>
    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="form-group">
            <label for="custom1_wert"><i class="ti ti-star" aria-hidden="true"></i> <?php echo htmlspecialchars($custom1_label); ?>:</label>
            <input type="text" id="custom1_wert" name="custom1_wert"
                   value="<?php echo htmlspecialchars($_POST['custom1_wert'] ?? ''); ?>"
                   placeholder="<?php echo t('custom_field_placeholder'); ?>">
            <fieldset style="border:none; padding:0; margin:4px 0 0 0;">
                <legend style="font-size:12px; color:#595959; font-weight:normal; padding:0; margin-bottom:2px;"><?php echo t('custom_field_type'); ?></legend>
                <label style="margin-right:12px;"><input type="radio" name="custom1_typ" value="text" checked> Text</label>
                <label><input type="radio" name="custom1_typ" value="zahl"> <?php echo t('custom_field_number'); ?></label>
            </fieldset>
        </div>
        <div class="form-group">
            <label for="custom2_wert"><i class="ti ti-star" aria-hidden="true"></i> <?php echo htmlspecialchars($custom2_label); ?>:</label>
            <input type="text" id="custom2_wert" name="custom2_wert"
                   value="<?php echo htmlspecialchars($_POST['custom2_wert'] ?? ''); ?>"
                   placeholder="<?php echo t('custom_field_placeholder'); ?>">
            <fieldset style="border:none; padding:0; margin:4px 0 0 0;">
                <legend style="font-size:12px; color:#595959; font-weight:normal; padding:0; margin-bottom:2px;"><?php echo t('custom_field_type'); ?></legend>
                <label style="margin-right:12px;"><input type="radio" name="custom2_typ" value="text" checked> Text</label>
                <label><input type="radio" name="custom2_typ" value="zahl"> <?php echo t('custom_field_number'); ?></label>
            </fieldset>
        </div>
    </div>

        </div>
    </div>

    <!-- ── Sektion 4: Bild ────────────────────────────────────── -->
    <div class="form-section">
        <div class="form-section-header gray"><i class="ti ti-photo" aria-hidden="true"></i> <?php echo t('form_image'); ?></div>
        <div class="form-section-body">
        
        <!-- Drag & Drop Zone -->
        <label class="upload-zone" id="uploadZone" for="bild" style="cursor:pointer;"
               role="button" tabindex="0"
               aria-label="<?php echo t('upload_drag_or_click'); ?>"
               onkeydown="if(event.key==='Enter'||event.key===' '){document.getElementById('bild').click();}">
            <div class="upload-zone-icon"><i class="ti ti-camera-plus"></i></div>
            <div class="upload-zone-text"><?php echo t('upload_drag_or_click'); ?></div>
            <div class="upload-zone-hint">
                <?php echo sprintf(t('upload_max_size'), (MAX_FILE_SIZE / 1024 / 1024)); ?> · <?php echo t('upload_formats'); ?>
            </div>
        </label>
        <!-- Input außerhalb der Zone — display:none, wird via label geöffnet -->
        <input type="file" id="bild" name="bild" style="display:none;">
        
        <!-- Upload Fehlermeldung -->
        <div id="uploadError" style="display:none; margin-top:10px; padding:10px 14px; background:#fdecea; border-left:4px solid #c0392b; border-radius:4px; color:#c0392b; font-size:14px;"></div>
        <div id="compressInfo" style="display:none; margin-top:10px; padding:8px 14px; background:#f0fdf4; border-left:4px solid #16a34a; border-radius:4px; color:#166534; font-size:13px;"></div>

        <!-- Mobile: Kamera/Galerie Auswahl -->
        <div class="camera-options" id="cameraOptions" style="display: none;">
            <button type="button" onclick="openCamera()"><i class="ti ti-camera" aria-hidden="true"></i> <?php echo t('upload_camera'); ?></button>
            <button type="button" onclick="openGallery()"><i class="ti ti-photo" aria-hidden="true"></i> <?php echo t('upload_gallery'); ?></button>
        </div>
        
        <!-- Bildvorschau -->
        <div class="image-preview-container" id="imagePreviewContainer">
            <img id="imagePreview" src="" alt="<?php echo t('form_image'); ?>" class="image-preview">
            <div class="image-preview-actions">
                <button type="button" class="btn-remove-image" onclick="removeImage()">
                    <i class="ti ti-trash" aria-hidden="true"></i> <?php echo t('upload_remove_image'); ?>
                </button>
            </div>
        </div>

    </div><!-- /.form-section-body -->
    </div><!-- /.form-section Bild -->

    <div class="form-actions">
        <button type="submit" class="vs-btn vs-btn-primary"><?php echo t('form_save'); ?></button>
        <a href="index.php" class="vs-btn vs-btn-secondary"><?php echo t('form_cancel'); ?></a>
    </div>
</form>

<script>
// Übersetzungen für JavaScript
const translations = {
    errorSize: '<?php echo sprintf(t("upload_error_size"), (MAX_FILE_SIZE / 1024 / 1024)); ?>',
    errorType: '<?php echo t("upload_error_type"); ?>',
    confirmCancel: '<?php echo t("confirm_cancel_unsaved"); ?>'
};

// Mobile Detection
const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

// Zeige Kamera-Optionen nur auf Mobile
if (isMobile) {
    document.getElementById('cameraOptions').style.display = 'flex';
}

// Drag & Drop Funktionalität
const uploadZone = document.getElementById('uploadZone');
const fileInput = document.getElementById('bild');
const imagePreviewContainer = document.getElementById('imagePreviewContainer');
const imagePreview = document.getElementById('imagePreview');

// Click auf Upload Zone — label öffnet den Dialog nativ (kein fileInput.click() nötig)
// Auf Mobile: label deaktivieren, stattdessen Kamera-Buttons nutzen
uploadZone.addEventListener('click', (e) => {
    if (isMobile) { e.preventDefault(); }
});

// Drag Over
uploadZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadZone.classList.add('drag-over');
});

// Drag Leave
uploadZone.addEventListener('dragleave', () => {
    uploadZone.classList.remove('drag-over');
});

// Drop
uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('drag-over');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        handleFileSelect(files[0]);
    }
});

// File Input Change
fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFileSelect(e.target.files[0]);
    }
});

// Kamera öffnen (Mobile)
function openCamera() {
    const input = document.createElement('input');
    input.type = 'file';
    // Kein Accept-Filter - alle Dateien erlauben, PHP prüft später
    input.capture = 'environment'; // Rückkamera
    input.onchange = (e) => {
        if (e.target.files.length > 0) {
            fileInput.files = e.target.files;
            handleFileSelect(e.target.files[0]);
        }
    };
    input.click();
}

// Galerie öffnen (Mobile)
function openGallery() {
    fileInput.click();
}

// Datei-Handling
// ── Bildkomprimierung ────────────────────────────────────────────────────
const COMPRESS_MAX_BYTES = 1.5 * 1024 * 1024;  // 1.5 MB
const COMPRESS_MAX_DIM   = 1920;                 // px längste Seite
const COMPRESS_QUALITY   = 0.85;
const COMPRESS_MIN_QUAL  = 0.50;

function formatBytes(bytes) {
    if (bytes < 1024)    return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(2) + ' MB';
}

async function compressImage(file) {
    return new Promise((resolve, reject) => {
        const fr = new FileReader();
        fr.onerror = () => reject(new Error('FileReader-Fehler'));
        fr.onload  = (ev) => {
            const img = new Image();
            img.onerror = () => reject(new Error('Bild konnte nicht geladen werden'));
            img.onload  = () => {
                let width  = img.naturalWidth;
                let height = img.naturalHeight;
                if (width === 0 || height === 0) { reject(new Error('Bildgröße 0')); return; }
                if (width > COMPRESS_MAX_DIM || height > COMPRESS_MAX_DIM) {
                    const ratio = Math.min(COMPRESS_MAX_DIM / width, COMPRESS_MAX_DIM / height);
                    width  = Math.round(width  * ratio);
                    height = Math.round(height * ratio);
                }
                const canvas = document.createElement('canvas');
                canvas.width  = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                if (!ctx) { reject(new Error('Canvas nicht verfügbar')); return; }
                ctx.drawImage(img, 0, 0, width, height);
                // WebP-Support prüfen (Safari-Fallback auf JPEG)
                const testData = canvas.toDataURL('image/webp');
                const format   = testData.startsWith('data:image/webp') ? 'image/webp' : 'image/jpeg';
                let quality = COMPRESS_QUALITY;
                const tryCompress = () => {
                    canvas.toBlob(blob => {
                        if (!blob) { reject(new Error('toBlob lieferte null')); return; }
                        if (blob.size <= COMPRESS_MAX_BYTES || quality <= COMPRESS_MIN_QUAL) {
                            resolve({ blob, format });
                        } else {
                            quality = Math.max(quality - 0.08, COMPRESS_MIN_QUAL);
                            tryCompress();
                        }
                    }, format, quality);
                };
                tryCompress();
            };
            img.src = ev.target.result;
        };
        fr.readAsDataURL(file);
    });
}

function injectCompressedFile(blob, originalName, format) {
    const ext     = format === 'image/webp' ? '.webp' : '.jpg';
    const newFile = new File([blob], originalName.replace(/\.[^/.]+$/, '') + ext, { type: format });
    try {
        const dt = new DataTransfer();
        dt.items.add(newFile);
        fileInput.files = dt.files;
    } catch(e) { console.warn('DataTransfer nicht unterstützt:', e); }
}

function handleFileSelect(file) {
    const allowedTypes      = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif'];
    const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'];
    const errDiv            = document.getElementById('uploadError');
    const infoDiv           = document.getElementById('compressInfo');

    errDiv.style.display  = 'none';
    infoDiv.style.display = 'none';

    const extension   = file.name.split('.').pop().toLowerCase();
    const isValidType = allowedTypes.includes(file.type) || allowedExtensions.includes(extension);

    if (!isValidType) {
        errDiv.textContent   = translations.errorType + ' (Erkannt: ' + file.type + ', Extension: .' + extension + ')';
        errDiv.style.display = 'block';
        return;
    }

    // HEIC: kein Browser-Preview
    if (extension === 'heic' || extension === 'heif') {
        imagePreview.src = 'data:image/svg+xml,%3Csvg xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22 width%3D%22200%22 height%3D%22200%22%3E%3Crect fill%3D%22%23667eea%22 width%3D%22200%22 height%3D%22200%22%2F%3E%3Ctext x%3D%2250%25%22 y%3D%2250%25%22 font-size%3D%2260%22 text-anchor%3D%22middle%22 dy%3D%22.3em%22 fill%3D%22white%22%3E%F0%9F%93%B8%3C%2Ftext%3E%3Ctext x%3D%2250%25%22 y%3D%2275%25%22 font-size%3D%2216%22 text-anchor%3D%22middle%22 fill%3D%22white%22%3EHEIC%3C%2Ftext%3E%3C%2Fsvg%3E';
        imagePreviewContainer.classList.add('active');
        uploadZone.style.display = 'none';
        return;
    }

    const originalSize  = file.size;
    const needsCompress = originalSize > COMPRESS_MAX_BYTES;
    const isGif         = (file.type === 'image/gif' || extension === 'gif');

    // GIF oder unter Limit: direkt Vorschau
    if (isGif || !needsCompress) {
        const reader = new FileReader();
        reader.onload = (ev) => {
            imagePreview.src = ev.target.result;
            imagePreviewContainer.classList.add('active');
            uploadZone.style.display = 'none';
            infoDiv.textContent   = '✓ ' + formatBytes(originalSize) + ' — keine Komprimierung nötig';
            infoDiv.style.display = 'block';
        };
        reader.readAsDataURL(file);
        return;
    }

    // Komprimierung
    infoDiv.textContent   = '… Komprimiere…';
    infoDiv.style.display = 'block';

    compressImage(file).then(({ blob, format }) => {
        injectCompressedFile(blob, file.name, format);
        const saving = Math.round((1 - blob.size / originalSize) * 100);
        const fmt    = format === 'image/webp' ? 'WebP' : 'JPEG';
        const reader = new FileReader();
        reader.onload = (ev) => {
            imagePreview.src = ev.target.result;
            imagePreviewContainer.classList.add('active');
            uploadZone.style.display = 'none';
        };
        reader.readAsDataURL(blob);
        infoDiv.innerHTML     = '🗜️ <strong>' + formatBytes(originalSize) + '</strong> → <strong>'
                               + formatBytes(blob.size) + '</strong> (−' + saving + '%, ' + fmt + ')';
        infoDiv.style.display = 'block';
    }).catch(err => {
        // Fallback: Original verwenden
        const maxSize = <?php echo MAX_FILE_SIZE; ?>;
        if (file.size > maxSize) {
            errDiv.textContent   = translations.errorSize;
            errDiv.style.display = 'block';
            return;
        }
        const reader = new FileReader();
        reader.onload = (ev) => {
            imagePreview.src = ev.target.result;
            imagePreviewContainer.classList.add('active');
            uploadZone.style.display = 'none';
        };
        reader.readAsDataURL(file);
        infoDiv.textContent   = '! Komprimierung fehlgeschlagen — Original (' + formatBytes(file.size) + ')';
        infoDiv.style.display = 'block';
    });
}

// Bild entfernen
function removeImage() {
    fileInput.value = '';
    imagePreview.src = '';
    imagePreviewContainer.classList.remove('active');
    uploadZone.style.display = 'block';
    document.getElementById('compressInfo').style.display = 'none';
    document.getElementById('uploadError').style.display  = 'none';
}

// Tastenkombinationen
document.addEventListener('keydown', (e) => {
    // Strg+S / Cmd+S zum Speichern
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('addForm').submit();
    }
    
    // ESC zum Abbrechen
    if (e.key === 'Escape') {
        vsConfirm(translations.confirmCancel).then(function (ja) {
            if (ja) { window.location.href = 'index.php'; }
        });
    }
});

// ============================================================
// AUTOSAVE – speichert Formularfelder in localStorage (onblur)
// ============================================================
(function() {
    const DRAFT_KEY = 'wert_add_draft';
    const FIELDS = ['name', 'raum_id', 'kategorie_id', 'kaufdatum', 'gelistet_am', 'preis', 'erstellt_von', 'notizen'];

    // Entwurf speichern
    function saveDraft() {
        var draft = { _saved: new Date().toLocaleString('de-DE') };
        FIELDS.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) draft[id] = el.value;
        });
        try { localStorage.setItem(DRAFT_KEY, JSON.stringify(draft)); } catch(e) {}
    }

    // Entwurf laden
    function loadDraft(draft) {
        FIELDS.forEach(function(id) {
            var el = document.getElementById(id);
            if (el && draft[id] !== undefined) el.value = draft[id];
        });
    }

    // Entwurf löschen
    function clearDraft() {
        try { localStorage.removeItem(DRAFT_KEY); } catch(e) {}
    }

    // Banner anzeigen
    function showBanner(draft) {
        var banner = document.createElement('div');
        banner.id = 'autosave-banner';
        banner.style.cssText = 'background:#fff8e1; border:1px solid #f59e0b; border-radius:10px; padding:14px 20px; margin-bottom:18px; display:flex; align-items:center; gap:14px; flex-wrap:wrap; font-size:14px;';
        banner.innerHTML = '<span>💾 <strong>Entwurf vom ' + draft._saved + '</strong> gefunden.</span>'
            + '<button id="autosave-restore" style="padding:6px 14px; background:#f59e0b; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Wiederherstellen</button>'
            + '<button id="autosave-discard" style="padding:6px 14px; background:transparent; border:1px solid #ccc; border-radius:6px; cursor:pointer;">Verwerfen</button>';
        
        var form = document.getElementById('addForm');
        form.parentNode.insertBefore(banner, form);

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
        // Prüfen ob Entwurf vorhanden
        try {
            var raw = localStorage.getItem(DRAFT_KEY);
            if (raw) {
                var draft = JSON.parse(raw);
                if (draft.name || draft.notizen) showBanner(draft);
            }
        } catch(e) {}

        // onblur auf alle Felder
        FIELDS.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('blur', saveDraft);
        });

        // Entwurf löschen wenn Formular erfolgreich abgesendet wird
        document.getElementById('addForm').addEventListener('submit', function() {
            clearDraft();
        });
    });
})();
</script>

<!-- Barcode Scanner: Native BarcodeDetector + ZXing Fallback (iOS/Safari) -->
<script src="js/zxing-browser.min.js"></script>
<script>
(function() {
    let stream     = null;
    let animFrame  = null;
    let zxingReader = null;

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
        // Desktop/Android: native BarcodeDetector (Kamera-Stream)
        if ('BarcodeDetector' in window) {
            startNativeScanner();
            return;
        }
        // iOS/Safari: manuelle Eingabe (Kamera-API zu unzuverlässig)
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
        if (animFrame)   { cancelAnimationFrame(animFrame); animFrame = null; }
        if (stream)      { stream.getTracks().forEach(t => t.stop()); stream = null; }
        if (zxingReader) { try { zxingReader.reset(); } catch(e) {} zxingReader = null; }
        const video = document.getElementById('barcodeVideo');
        if (video) video.srcObject = null;
        document.getElementById('barcodeScannerModal').style.display = 'none';
    };

    window.lookupBarcode = async function(code) {
        const clean = code.replace(/[^0-9X]/gi, '').toUpperCase();
        const res   = document.getElementById('isbn-lookup-result');
        res.style.display = 'block';
        res.textContent   = '… Produktdaten werden gesucht…';
        res.style.background = '#f0fdf4'; res.style.borderColor = '#16a34a'; res.style.color = '#166534';
        try {
            const r    = await fetch(`barcode_lookup.php?code=${clean}`);
            const data = await r.json();
            if (!data.found) {
                res.style.background = '#fef2f2'; res.style.borderColor = '#dc2626'; res.style.color = '#991b1b';
                res.textContent = '✗ Keine Produktdaten gefunden – bitte manuell eintragen.';
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
            // Notizen: description aus API verwenden
            if (data.description && !notesEl.value) {
                notesEl.value = data.description;
            } else if (!notesEl.value) {
                // Fallback: Notizen manuell zusammenbauen
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
                res.innerHTML = `<i class="ti ti-check" style="color:var(--vs-success)"></i> <strong>${data.name}</strong>${data.brand ? ' – ' + data.brand : ''}${data.year ? ' (' + data.year + ')' : ''}${src}<br>
                    <button type="button" onclick="applyISBNCover()" style="background:none; border:none; padding:0; color:#0369a1; font-size:12px; cursor:pointer; text-decoration:underline;"><i class='ti ti-camera'></i> Bild übernehmen</button>`;
            } else {
                const src = data.source ? ` <span style="font-size:12px;color:#595959;">(${data.source})</span>` : '';
                res.innerHTML = `<i class="ti ti-check" style="color:var(--vs-success)"></i> <strong>${data.name}</strong>${data.brand ? ' – ' + data.brand : ''}${data.year ? ' (' + data.year + ')' : ''}${src}`;
            }
            res.style.background = '#f0fdf4'; res.style.borderColor = '#16a34a'; res.style.color = '#166534';
        } catch(e) {
            res.style.background = '#fef2f2'; res.style.borderColor = '#dc2626'; res.style.color = '#991b1b';
            res.textContent = '! Fehler beim Abrufen: ' + e.message;
        }
    };
    window.lookupISBN = window.lookupBarcode;

    window.applyISBNCover = async function() {
        if (!window._isbnCoverUrl) return;
        try {
            // PHP-Proxy umgeht CORS-Beschränkungen externer Bild-URLs
            const r    = await fetch('cover_proxy.php?url=' + encodeURIComponent(window._isbnCoverUrl));
            if (!r.ok) throw new Error('HTTP ' + r.status);
            const blob = await r.blob();
            const file = new File([blob], 'cover.jpg', { type: blob.type });
            const dt   = new DataTransfer();
            dt.items.add(file);
            document.getElementById('bild').files = dt.files;
            document.getElementById('bild').dispatchEvent(new Event('change'));
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

    if (raumSelect.value) {
        ladePositionen(raumSelect.value, <?php echo (int)($_POST['position_id'] ?? 0); ?>);
    }
})();
</script>