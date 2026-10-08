<?php
/**
 * backend/public_settings.php
 * Einstellungen für den öffentlichen Ansichts-Link
 * v3.15
 */

require_once __DIR__ . '/config.php';
requireBackendAccess();

$pageTitle = t('dash_public_link');
$message = '';
$messageIcon = '';
$error   = '';

// App-Settings laden
function getPublicSettings(object $db): array {
    $rows = $db->select("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE 'public_%' OR setting_key = 'only_own_items'");
    $s = [];
    foreach ($rows as $r) $s[$r['setting_key']] = $r['setting_value'];
    return $s;
}

function savePublicSetting(object $db, string $key, ?string $value): void {
    $db->execute(
        "INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
        [$key, $value]
    );
}

// POST-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateRequest();

    $action = $_POST['action'] ?? '';

    try {
    switch ($action) {

        case 'generate_token':
            $token = bin2hex(random_bytes(24)); // 48 Zeichen, kryptografisch sicher
            savePublicSetting($db, 'public_token', $token);
            $messageIcon = 'ti-circle-check';
            $message     = 'Neuer Link generiert.';
            break;

        case 'revoke_token':
            savePublicSetting($db, 'public_token', null);
            $messageIcon = 'ti-lock';
            $message     = 'Öffentlicher Link deaktiviert.';
            break;

        case 'save_settings':
            savePublicSetting($db, 'public_show_price',    isset($_POST['show_price'])    ? '1' : '0');
            savePublicSetting($db, 'public_show_location', isset($_POST['show_location']) ? '1' : '0');
            savePublicSetting($db, 'public_title',         trim($_POST['page_title'] ?? '') ?: null);
            savePublicSetting($db, 'public_description',   trim($_POST['page_description'] ?? '') ?: null);
            savePublicSetting($db, 'only_own_items',       isset($_POST['only_own_items']) ? '1' : '0');
            $messageIcon = 'ti-circle-check';
            $message     = 'Einstellungen gespeichert.';
            break;
    }
    } catch (PDOException $e) {
        // savePublicSetting() schreibt in app_settings; scheitert das, stand
        // hier bis 4.3.17 trotzdem "gespeichert".
        // Nur $error setzen: $message wird gruen ausgegeben, $error rot.
        // Beides zugleich zeigte den Fehlschlag zweimal, einmal davon in
        // Erfolgsfarbe.
        error_log('public_settings: ' . $e->getMessage());
        $messageIcon = '';
        $message     = '';
        $error       = 'Speichern fehlgeschlagen - Einzelheiten stehen im PHP-Fehlerlog.';
    }
}

$settings    = getPublicSettings($db);
$token       = $settings['public_token'] ?? null;
$show_price  = ($settings['public_show_price']    ?? '0') === '1';
$show_loc    = ($settings['public_show_location'] ?? '1') === '1';
$pub_title   = $settings['public_title']       ?? '';
$pub_desc    = $settings['public_description'] ?? '';
$only_own    = ($settings['only_own_items']    ?? '0') === '1';

// Öffentliche Items zählen
$public_count = $db->selectOne("SELECT COUNT(*) as c FROM wertsachen WHERE oeffentlich = 1")['c'] ?? 0;
$total_count  = $db->selectOne("SELECT COUNT(*) as c FROM wertsachen WHERE hidden = 0 OR hidden IS NULL")['c'] ?? 0;

// Public-URL ermitteln
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$public_url = $token ? "{$protocol}://{$host}{$base_path}/public.php?token={$token}" : null;

include 'layout/header_next_page.php';
?>

<style>
@media (max-width: 768px) {
    .ps-main-grid { grid-template-columns: 1fr !important; }
    .ps-url-row   { flex-direction: column !important; }
    .ps-url-row input, .ps-url-row a, .ps-url-row button { min-width: 0 !important; width: 100% !important; box-sizing: border-box; }
    .ps-qr-col    { display: none !important; }
    .ps-hints-col { width: 100% !important; }
}
</style>

<div class="backend-content">

<div class="page-header">
    <h1><i class="ti ti-link"></i> <?php echo t('pub_page_heading'); ?></h1>
    <p class="page-subtitle"><?php echo t('pub_page_subtitle'); ?></p>
</div>

<?php if ($message): ?>
    <div class="alert alert-success" style="margin-bottom:20px;">
        <?php if ($messageIcon): ?><i class="ti <?php echo $messageIcon; ?>" style="color:var(--vs-success);"></i> <?php endif; ?>
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger" style="margin-bottom:20px;"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="ps-main-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:24px; max-width:1000px;">

    <!-- LINK STATUS -->
    <div class="admin-card" style="grid-column: 1 / -1;">
        <h2 class="card-title"><i class="ti ti-link"></i> <?php echo t('pub_link_status'); ?></h2>

        <?php if ($token): ?>
            <!-- AKTIV -->
            <div style="background:var(--vs-success-light); border:1px solid #bbf7d0; border-radius:8px; padding:16px; margin-bottom:16px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px; color:var(--vs-success); font-weight:600;">
                    <span style="font-size:1.1rem;"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i></span> <?php echo t('pub_link_active'); ?>
                </div>

                <!-- URL + QR nebeneinander -->
                <div class="ps-url-row" style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">

                    <!-- URL-Block -->
                    <div style="flex:1; min-width:200px;">
                        <div class="ps-url-row" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:8px;">
                            <input type="text" id="publicUrlInput"
                                   value="<?php echo htmlspecialchars($public_url); ?>"
                                   readonly
                                   style="flex:1; min-width:200px; padding:8px 12px; border:1px solid var(--vs-border); border-radius:6px; font-size:13px; background:var(--vs-surface); font-family:monospace;">
                            <button onclick="copyUrl()" class="vs-btn vs-btn-secondary" style="white-space:nowrap;"><i class="ti ti-clipboard-list"></i> <?php echo t('btn_copy'); ?></button>
                            <a href="<?php echo htmlspecialchars($public_url); ?>" target="_blank" class="vs-btn vs-btn-secondary" style="white-space:nowrap;"><i class="ti ti-search"></i> <?php echo t('btn_preview'); ?></a>
                        </div>
                        <div style="font-size:13px; color:var(--vs-text-muted);">
                            <?php echo $public_count; ?> <?php echo t('pub_of'); ?> <?php echo $total_count; ?> <?php echo t('pub_items_marked_public'); ?>
                        </div>
                    </div>

                    <!-- QR-Code Block -->
                    <div class="ps-qr-col" style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                        <div id="qrcode" style="background:var(--vs-surface); padding:10px; border-radius:8px; border:1px solid var(--vs-border); display:inline-block;"></div>
                        <button onclick="downloadQR()" class="vs-btn vs-btn-secondary" style="font-size:12px; padding:5px 12px; white-space:nowrap;">
                            ⬇️ <?php echo t('btn_save_qr'); ?>
                        </button>
                    </div>

                </div>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <form method="post">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="generate_token">
                    <button type="submit" class="vs-btn vs-btn-secondary"
                            onclick="return vsConfirmSubmit(event, <?php echo htmlspecialchars(json_encode(t('pub_confirm_new_link')), ENT_QUOTES, 'UTF-8'); ?>)">
                        <i class="ti ti-refresh"></i> <?php echo t('pub_generate_link'); ?>
                    </button>
                </form>
                <form method="post">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="revoke_token">
                    <button type="submit" class="vs-btn vs-btn-danger"
                            onclick="return vsConfirmSubmit(event, <?php echo htmlspecialchars(json_encode(t('pub_confirm_disable_link')), ENT_QUOTES, 'UTF-8'); ?>)">
                        <i class="ti ti-lock"></i> <?php echo t('pub_revoke_link'); ?>
                    </button>
                </form>
            </div>

        <?php else: ?>
            <!-- INAKTIV -->
            <div style="background:var(--vs-surface)7ed; border:1px solid #fed7aa; border-radius:8px; padding:16px; margin-bottom:16px;">
                <div style="display:flex; align-items:center; gap:8px; color:var(--vs-warning-text); font-weight:600;">
                    <span style="font-size:1.1rem;"><i class="ti ti-lock"></i></span> <?php echo t('pub_no_link_active'); ?>
                </div>
                <p style="margin-top:8px; font-size:13px; color:var(--vs-warning-text);">
                    <?php echo t('pub_generate_hint'); ?>
                </p>
            </div>
            <form method="post">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="generate_token">
                <button type="submit" class="vs-btn vs-btn-primary"><i class="ti ti-link"></i> <?php echo t('pub_generate_link'); ?></button>
            </form>
        <?php endif; ?>
    </div>

    <!-- EINSTELLUNGEN -->
    <div class="admin-card">
        <h2 class="card-title"><i class="ti ti-settings"></i> <?php echo t('pub_appearance'); ?></h2>
        <form method="post">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="save_settings">

            <div class="form-group" style="margin-bottom:16px;">
                <label style="font-weight:600; display:block; margin-bottom:6px;"><?php echo t('pub_page_title_label'); ?></label>
                <input type="text" name="page_title" maxlength="100"
                       value="<?php echo htmlspecialchars($pub_title); ?>"
                       placeholder="<?php echo t('pub_placeholder_title'); ?>"
                       style="width:100%; padding:8px 12px; border:1px solid var(--vs-border); border-radius:6px;">
            </div>

            <div class="form-group" style="margin-bottom:16px;">
                <label style="font-weight:600; display:block; margin-bottom:6px;"><?php echo t('pub_description_label'); ?></label>
                <textarea name="page_description" rows="3" maxlength="500"
                          placeholder="<?php echo t('pub_placeholder_desc'); ?>"
                          style="width:100%; padding:8px 12px; border:1px solid var(--vs-border); border-radius:6px; resize:vertical;"
                ><?php echo htmlspecialchars($pub_desc); ?></textarea>
            </div>

            <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:20px;">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <input type="checkbox" name="show_price" value="1" <?php echo $show_price ? 'checked' : ''; ?>
                           style="width:18px; height:18px;">
                    <span><i class="ti ti-currency-euro"></i> <?php echo t('pub_show_price'); ?></span>
                </label>
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <input type="checkbox" name="show_location" value="1" <?php echo $show_loc ? 'checked' : ''; ?>
                           style="width:18px; height:18px;">
                    <span>📍 <?php echo t('pub_show_location'); ?></span>
                </label>
            </div>

            <hr style="border:none; border-top:1px solid var(--vs-border); margin:20px 0;">

            <div style="margin-bottom:20px;">
                <label style="font-weight:600; display:block; margin-bottom:8px;"><i class="ti ti-user"></i> <?php echo t('pub_visibility_label'); ?></label>
                <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer;
                              background:var(--vs-surface-2); border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px;">
                    <input type="checkbox" name="only_own_items" value="1" <?php echo $only_own ? 'checked' : ''; ?>
                           style="width:18px; height:18px; margin-top:2px; flex-shrink:0;">
                    <span>
                        <strong><?php echo t('pub_own_items_only'); ?></strong>
                        <small style="display:block; font-weight:400; color:var(--vs-text-muted); margin-top:2px;">
                            <?php echo t('pub_own_items_desc'); ?>
                        </small>
                    </span>
                </label>
            </div>

            <button type="submit" class="vs-btn vs-btn-primary"><i class="ti ti-device-floppy"></i> <?php echo t('btn_save'); ?></button>
        </form>
    </div>

    <!-- HINWEISE -->
    <div class="admin-card">
        <h2 class="card-title"><i class="ti ti-bulb"></i> <?php echo t('pub_notes_title'); ?></h2>
        <ul style="list-style:none; padding:0; display:flex; flex-direction:column; gap:12px; font-size:14px; color:var(--vs-text-muted);">
            <li>🔐 <strong><?php echo t('pub_note_token_title'); ?></strong> — <?php echo t('pub_note_token_desc'); ?></li>
            <li>🤖 <strong>noindex</strong> — <?php echo t('pub_note_noindex'); ?></li>
            <li><i class="ti ti-pencil"></i> <strong><?php echo t('pub_note_mark_items_title'); ?></strong> — <?php echo t('pub_note_mark_items_desc'); ?></li>
            <li><i class="ti ti-refresh"></i> <strong><?php echo t('pub_note_new_link_title'); ?></strong> — <?php echo t('pub_note_new_link_desc'); ?></li>
            <li><i class="ti ti-lock"></i> <strong><?php echo t('pub_note_deactivate_title'); ?></strong> — <?php echo t('pub_note_deactivate_desc'); ?></li>
            <li><i class="ti ti-currency-euro"></i> <strong><?php echo t('pub_note_price_title'); ?></strong> — <?php echo t('pub_note_price_desc'); ?></li>
        </ul>

        <?php if ($public_count === 0 && $token): ?>
        <div style="margin-top:16px; padding:12px; background:#fef9c3; border-radius:8px; border:1px solid #fde047; font-size:13px; color:#713f12;">
            <i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i> <strong><?php echo t('pub_no_public_items'); ?></strong><br>
            <?php echo t('pub_no_public_items_hint'); ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<script src="../js/qrcode.min.js"></script>
<script>
<?php if ($token): ?>
const qrUrl   = <?php echo json_encode($public_url); ?>;
const qrTitle = <?php echo json_encode(!empty($pub_title) ? $pub_title : t('pub_placeholder_title')); ?>;

let displayCanvas = null;

window.addEventListener('load', function() {
    displayCanvas = document.createElement('canvas');
    displayCanvas.width  = 160;
    displayCanvas.height = 160;
    displayCanvas.style.display = 'block';
    document.getElementById('qrcode').appendChild(displayCanvas);

    const qrHidden = document.createElement('div');
    qrHidden.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';
    document.body.appendChild(qrHidden);

    new QRCode(qrHidden, {
        text:         qrUrl,
        width:        160,
        height:       160,
        colorDark:    '#1e293b',
        colorLight:   '#ffffff',
        correctLevel: 1  // M = 1 (L=0, M=1, Q=2, H=3)
    });

    setTimeout(function() {
        const srcCanvas = qrHidden.querySelector('canvas');
        const srcImg    = qrHidden.querySelector('img');
        const ctx       = displayCanvas.getContext('2d');

        if (srcCanvas) {
            ctx.drawImage(srcCanvas, 0, 0);
            document.body.removeChild(qrHidden);
        } else if (srcImg) {
            function draw() {
                ctx.drawImage(srcImg, 0, 0, 160, 160);
                document.body.removeChild(qrHidden);
            }
            srcImg.complete ? draw() : (srcImg.onload = draw);
        }
    }, 200);
});

function downloadQR() {
    if (!displayCanvas) { vsAlert(<?php echo json_encode(t('pub_qr_not_ready')); ?>); return; }

    const padding = 16;
    const titleH  = 32;
    const size    = 160;

    const out = document.createElement('canvas');
    out.width  = size + padding * 2;
    out.height = size + padding * 2 + titleH;

    const ctx = out.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, out.width, out.height);
    ctx.drawImage(displayCanvas, padding, padding + titleH);
    ctx.fillStyle = '#1e293b';
    ctx.font = 'bold 13px -apple-system, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(qrTitle, out.width / 2, padding + titleH - 10);

    const a = document.createElement('a');
    a.download = 'sammlung-qr.png';
    a.href = out.toDataURL('image/png');
    a.click();
}
<?php endif; ?>

function copyUrl() {
    const input = document.getElementById('publicUrlInput');
    input.select();
    input.setSelectionRange(0, 99999);
    if (navigator.clipboard) {
        navigator.clipboard.writeText(input.value).then(() => {
            const btn = event.target;
            const orig = btn.textContent;
            btn.textContent = '<i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Kopiert!';
            setTimeout(() => btn.textContent = orig, 2000);
        });
    } else {
        document.execCommand('copy');
    }
}
</script>

<?php
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . Security::generateCSRFToken() . '">';
}
?>
</div><!-- /.backend-content -->
<?php include 'layout/footer_next_page.php'; ?>
