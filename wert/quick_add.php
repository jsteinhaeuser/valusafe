<?php
// quick_add.php — Schnellerfassung (mobiloptimiert)
require_once 'db.php';
require_once 'helpers.php';
require_once 'helpers_images.php';
requireLogin();
// Bis 4.3.32 nur requireLogin(): ein Leser konnte per Adresse anlegen.
requirePermission('items_add');
// t(), nicht tn(): tn() definieren erst die Header-Dateien (weiter unten)
define('PAGE_TITLE', t('quick_title') . ' - ' . t('app_title'));

$success = null;
$errors  = [];

// Letzten Raum/Kategorie aus Session merken
$last_raum_id      = $_SESSION['quick_last_ort']      ?? 0;
$last_kategorie_id = $_SESSION['quick_last_kategorie'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateRequest();

    $data   = sanitizeWertsachenInput($_POST);
    $errors = validateWertsachenData($data);

    $uploadResult = handleFileUpload($_FILES['bild'] ?? null);
    if (isset($uploadResult['errors'])) {
        $errors = array_merge($errors, $uploadResult['errors']);
    }

    if (empty($errors)) {
        try {
            $user      = $db->selectOne("SELECT standard_ersteller FROM users WHERE username = ?", [$_SESSION['username']]);
            $ersteller = $user['standard_ersteller'] ?? $_SESSION['username'];
            if (userSeesOnlyOwnItems()) {
                $ersteller = $_SESSION['username'];
            }

            $newId = $db->insert(
                "INSERT INTO wertsachen (name, raum_id, kategorie_id, kaufdatum, preis, notizen, bild, erstellt_von, gelistet_am)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['name'],
                    $data['raum_id'],
                    $data['kategorie_id'],
                    $data['kaufdatum'],
                    $data['preis'],
                    $data['notizen'],
                    $uploadResult['filename'],
                    $ersteller,
                    date('Y-m-d'),
                ]
            );

            if (!empty($uploadResult['filename'])) {
                addItemImage($newId, $uploadResult['filename'], true);
            }

            Security::logSecurityEvent('item_created', ['item_name' => $data['name'], 'id' => $newId, 'source' => 'quick_add']);
            logActivity('created', 'wertsachen', $newId, $data['name'], null, [
                'name'         => $data['name'],
                'kategorie_id' => $data['kategorie_id'],
                'raum_id'       => $data['raum_id'],
                'preis'        => $data['preis'],
            ]);

            // Letzten Raum/Kategorie merken
            $_SESSION['quick_last_ort']      = (int)$data['raum_id'];
            $_SESSION['quick_last_kategorie'] = (int)$data['kategorie_id'];
            $last_raum_id       = $_SESSION['quick_last_ort'];
            $last_kategorie_id = $_SESSION['quick_last_kategorie'];

            $success = $data['name'];

        } catch (PDOException $e) {
            $errors[] = handleDatabaseError($e, 'quick_add');
        }
    }
}

try {
    $raeume      = $db->select("SELECT * FROM raeume ORDER BY name");
    $kategorien = $db->select("SELECT * FROM kategorien ORDER BY name");
} catch (PDOException $e) {
    die(t('error_loading_data'));
}

include 'header_next_page.php';
?>

<style>
.qa-wrap {
    max-width: 480px;
    margin: 0 auto;
    padding: 0 4px 80px;
}

.qa-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-color, #2c3e50);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.qa-subtitle {
    font-size: 13px;
    color: var(--text-muted, #888);
    margin-bottom: 20px;
}

/* Erfolgs-Toast */
.qa-toast {
    background: #d1fae5;
    border: 1.5px solid #6ee7b7;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 600;
    color: #065f46;
    animation: qa-fadein 0.3s ease;
}
@keyframes qa-fadein { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }

/* Fehler */
.qa-errors {
    background: #fee2e2;
    border: 1.5px solid #fca5a5;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 18px;
    font-size: 14px;
    color: #991b1b;
}
.qa-errors ul { margin: 6px 0 0 16px; padding: 0; }

/* Kamera-Upload */
.qa-photo-zone {
    position: relative;
    width: 100%;
    aspect-ratio: 4/3;
    border-radius: 16px;
    border: 3px dashed var(--border-color, #d1d5db);
    background: var(--input-bg, #f9fafb);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    overflow: hidden;
    margin-bottom: 16px;
    transition: border-color 0.2s;
}
.qa-photo-zone:hover { border-color: var(--primary-color, #3498db); }
.qa-photo-zone input[type=file] { display: none; }
.qa-photo-icon { font-size: 48px; margin-bottom: 8px; }
.qa-photo-hint { font-size: 14px; color: var(--text-muted, #888); }
.qa-photo-preview {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    border-radius: 13px;
    display: none;
}
.qa-photo-remove {
    position: absolute; top: 10px; right: 10px;
    background: rgba(0,0,0,0.6); color: white;
    border: none; border-radius: 50%;
    width: 32px; height: 32px;
    font-size: 16px; cursor: pointer;
    display: none;
    align-items: center; justify-content: center;
    z-index: 2;
}

/* Formularfelder */
.qa-group { margin-bottom: 14px; }
.qa-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-color, #374151);
    margin-bottom: 5px;
}
.qa-label .req { color: #e53e3e; }

.qa-input, .qa-select {
    width: 100%;
    padding: 13px 14px;
    border: 1.5px solid var(--border-color, #d1d5db);
    border-radius: 10px;
    font-size: 16px; /* verhindert iOS zoom */
    font-family: inherit;
    background: var(--input-bg, white);
    color: var(--text-color, #222);
    outline: none;
    transition: border-color 0.15s;
    -webkit-appearance: none;
}
.qa-input:focus, .qa-select:focus {
    border-color: var(--primary-color, #3498db);
    box-shadow: 0 0 0 3px rgba(52,152,219,0.12);
}

.qa-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

/* Speichern-Button */
.qa-submit {
    width: 100%;
    padding: 16px;
    background: var(--primary-color, #2c7be5);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 17px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    margin-top: 6px;
    transition: opacity 0.2s, transform 0.1s;
}
.qa-submit:active { transform: scale(0.98); opacity: 0.9; }

/* Link zu vollständigem Formular */
.qa-full-link {
    display: block;
    text-align: center;
    margin-top: 14px;
    font-size: 13px;
    color: var(--text-muted, #888);
}
.qa-full-link a { color: var(--primary-color, #3498db); text-decoration: none; }
</style>

<div class="qa-wrap">
    <div class="qa-title">⚡ <?php echo tn('quick_title', 'Schnellerfassung'); ?></div>
    <p class="qa-subtitle"><?php echo tn('quick_subtitle', 'Foto wählen, Name eingeben, speichern – Raum und Kategorie bleiben für den nächsten Gegenstand stehen.'); ?></p>

    <?php if ($success): ?>
    <div class="qa-toast">
        ✅ <span>„<?php echo htmlspecialchars($success); ?>“ <?php echo tn('quick_saved', 'gespeichert!'); ?></span>
        <a href="edit.php?id=<?php echo (int)$newId; ?>" style="margin-left:auto; font-size:12px; color:#065f46; text-decoration:underline;"><?php echo tn('quick_details', 'Details'); ?> →</a>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="qa-errors">
        ⚠️ <?php echo tn('quick_fix_errors', 'Bitte korrigieren:'); ?>
        <ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="qaForm" autocomplete="off">
        <?php echo Security::getCSRFInput(); ?>

        <!-- Foto -->
        <div class="qa-photo-zone" id="qaPhotoZone" onclick="document.getElementById('bild').click()">
            <input type="file" name="bild" id="bild" accept="image/*,.heic,.heif" capture="environment">
            <img class="qa-photo-preview" id="qaPreview" alt="">
            <button type="button" class="qa-photo-remove" id="qaRemove" onclick="removePhoto(event)">✕</button>
            <div id="qaPlaceholder">
                <div class="qa-photo-icon">📷</div>
                <div class="qa-photo-hint"><?php echo tn('quick_photo_hint', 'Klicken, um ein Foto zu wählen'); ?></div>
            </div>
        </div>

        <!-- Name -->
        <div class="qa-group">
            <label class="qa-label" for="name"><?php echo t('form_name'); ?> <span class="req">*</span></label>
            <input type="text" name="name" id="name" class="qa-input"
                   placeholder="<?php echo htmlspecialchars(tn('quick_name_placeholder', 'z. B. Kopfhörer, Bohrmaschine, Ring')); ?>"
                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                   required autofocus>
        </div>

        <!-- Raum + Kategorie -->
        <div class="qa-row">
            <div class="qa-group">
                <label class="qa-label" for="raum_id">📍 <?php echo t('form_location'); ?></label>
                <select name="raum_id" id="raum_id" class="qa-select">
                    <option value="">— <?php echo t('placeholder_select'); ?> —</option>
                    <?php foreach ($raeume as $ort): ?>
                        <option value="<?php echo $ort['id']; ?>"
                            <?php echo ((int)($_POST['raum_id'] ?? $last_raum_id) === (int)$ort['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ort['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="qa-group">
                <label class="qa-label" for="kategorie_id">🏷️ <?php echo t('form_category'); ?></label>
                <select name="kategorie_id" id="kategorie_id" class="qa-select">
                    <option value="">— <?php echo t('placeholder_select'); ?> —</option>
                    <?php foreach ($kategorien as $kat): ?>
                        <option value="<?php echo $kat['id']; ?>"
                            <?php echo ((int)($_POST['kategorie_id'] ?? $last_kategorie_id) === (int)$kat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($kat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Preis -->
        <div class="qa-group">
            <label class="qa-label" for="preis">💰 <?php echo t('form_price'); ?></label>
            <input type="number" name="preis" id="preis" class="qa-input"
                   placeholder="0.00" step="0.01" min="0"
                   value="<?php echo htmlspecialchars($_POST['preis'] ?? ''); ?>">
        </div>

        <!-- Notiz (optional, eingeklappt) -->
        <details style="margin-bottom:14px;">
            <summary style="font-size:13px; color:var(--text-muted,#888); cursor:pointer; user-select:none; padding:4px 0;">
                📝 <?php echo tn('quick_add_note', 'Notiz hinzufügen (optional)'); ?>
            </summary>
            <div style="margin-top:8px;">
                <textarea name="notizen" id="notizen" class="qa-input"
                          rows="3" placeholder="<?php echo t('placeholder_notes'); ?>"
                          style="resize:vertical;"><?php echo htmlspecialchars($_POST['notizen'] ?? ''); ?></textarea>
            </div>
        </details>

        <!-- Versteckte Felder mit Standardwerten -->
        <!-- Kein verstecktes Kaufdatum mehr (ValuSafe bis 4.3.32 / Valu-Basic bis 2.0.3: heute) - das Datum
             der Erfassung ist nicht das Kaufdatum. "Hier gelistet am" setzt
             das INSERT oben selbst. -->

        <button type="submit" class="qa-submit">
            ⚡ <?php echo tn('quick_submit', 'Speichern & nächster Gegenstand'); ?>
        </button>
    </form>

    <div class="qa-full-link">
        <?php echo tn('quick_more_fields', 'Mehr Felder?'); ?> <a href="add.php"><?php echo tn('quick_full_form', 'Vollständiges Formular'); ?> →</a>
        &nbsp;·&nbsp;
        <a href="index.php"><?php echo tn('quick_to_overview', 'Zur Übersicht'); ?></a>
    </div>
</div>

<script>
// Foto-Vorschau
// Seit ValuSafe 4.3.33 / Valu-Basic 2.0.4: Kann der Browser das Bild nicht darstellen (HEIC vom iPhone in
// Chrome/Firefox/Edge), blieb die Vorschau leer und es sah aus, als sei das
// Foto nicht angenommen. Jetzt steht dann Dateiname + Hinweis im Feld; die
// Umwandlung erledigt der Server beim Speichern.
const QA_HINT_DEFAULT = <?php echo json_encode(tn('quick_photo_hint', 'Klicken, um ein Foto zu wählen')); ?>;
const QA_HINT_NOPREV  = <?php echo json_encode(tn('quick_photo_no_preview', 'Keine Vorschau möglich – das Foto wird beim Speichern umgewandelt.')); ?>;

function qaKeineVorschau(file) {
    const placeholder = document.getElementById('qaPlaceholder');
    document.getElementById('qaPreview').style.display = 'none';
    placeholder.querySelector('.qa-photo-icon').textContent = '🖼️';
    placeholder.querySelector('.qa-photo-hint').textContent = file.name + ' – ' + QA_HINT_NOPREV;
    placeholder.style.display = '';
    document.getElementById('qaRemove').style.display = 'flex';
}

function qaPlatzhalterZuruecksetzen() {
    const placeholder = document.getElementById('qaPlaceholder');
    placeholder.querySelector('.qa-photo-icon').textContent = '📷';
    placeholder.querySelector('.qa-photo-hint').textContent = QA_HINT_DEFAULT;
}

document.getElementById('bild').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    if (/\.(heic|heif)$/i.test(file.name) && !/^((?!chrome|android).)*safari/i.test(navigator.userAgent)) {
        qaKeineVorschau(file);
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('qaPreview');
        const placeholder = document.getElementById('qaPlaceholder');
        const removeBtn = document.getElementById('qaRemove');
        preview.onerror = function() { qaKeineVorschau(file); };
        preview.onload  = function() {
            preview.style.display = 'block';
            placeholder.style.display = 'none';
            removeBtn.style.display = 'flex';
        };
        preview.src = e.target.result;
    };
    reader.readAsDataURL(file);
});

function removePhoto(e) {
    e.stopPropagation();
    document.getElementById('bild').value = '';
    document.getElementById('qaPreview').style.display = 'none';
    qaPlatzhalterZuruecksetzen();
    document.getElementById('qaPlaceholder').style.display = '';
    document.getElementById('qaRemove').style.display = 'none';
}

// Nach Speichern: Name-Feld fokussieren und leeren
<?php if ($success): ?>
document.addEventListener('DOMContentLoaded', function() {
    const nameField = document.getElementById('name');
    if (nameField) { nameField.value = ''; nameField.focus(); }
    // Foto zurücksetzen
    document.getElementById('qaPreview').style.display = 'none';
    document.getElementById('qaPlaceholder').style.display = '';
    document.getElementById('qaRemove').style.display = 'none';
    // Preis zurücksetzen
    document.getElementById('preis').value = '';
});
<?php endif; ?>
</script>

<?php include 'footer_next.php'; ?>
