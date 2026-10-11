<?php
/**
 * 2fa_setup.php — Zwei-Faktor-Anmeldung (TOTP) fuer das eigene Konto
 *
 * Seit 4.3.34 fuer alle Rollen (vorher backend/2fa_setup.php, nur Admin; das
 * Handbuch 4.2.3 versprach "jeder Benutzer"). login.php fragte den zweiten
 * Faktor schon immer rollenunabhaengig ab - nur die Einrichtung fehlte.
 *
 * Recht: haengt an settings_password ("Eigenes Passwort aendern"). Auf einem
 * gemeinsamen Demo-Konto koennte ein Besucher sonst 2FA einschalten und alle
 * anderen aussperren. Fuer Admins ist das Recht fest an.
 * Hier zaehlt nur der 6-stellige App-Code (TOTP::verify); Backup-Codes gelten nur
 * bei der Anmeldung (login_2fa.php). Der alte Hinweis "oder Backup-Code" war hier falsch.
 * Verlorenes Handy + Backup-Codes: Admin setzt die 2FA in backend/users.php zurueck.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/TOTP.php';
requireLogin();

define('PAGE_TITLE', t('2fa_page_title') . ' - ' . t('app_title'));

$darf2fa = hasPermission('settings_password');
$message = '';
$error   = '';
$step    = $_GET['step'] ?? 'overview';
if (!in_array($step, ['overview', 'verify', 'backup_codes', 'disable'], true)) {
    $step = 'overview';
}

$stmt = $pdo->prepare("SELECT id, username, totp_secret, totp_enabled, totp_backup_codes FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: logout.php');
    exit;
}

if (!$darf2fa) {
    $step = 'overview';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateRequest();
    $action = $_POST['action'] ?? '';

    if (!$darf2fa) {
        $error = t('2fa_change_disabled');

    // Schritt 1: Setup starten - Secret erzeugen
    } elseif ($action === 'start_setup' && empty($user['totp_enabled'])) {
        $_SESSION['totp_pending_secret'] = TOTP::generateSecret();
        header('Location: ?step=verify');
        exit;

    // Schritt 2: Code pruefen und aktivieren
    } elseif ($action === 'verify_and_enable') {
        $secret = $_SESSION['totp_pending_secret'] ?? '';
        $code   = trim($_POST['totp_code'] ?? '');

        if ($secret === '') {
            $error = t('2fa_error_session');
        } elseif (!TOTP::verify($secret, $code)) {
            $error = t('2fa_error_code_invalid');
            $step  = 'verify';
        } else {
            $backupCodes = TOTP::generateBackupCodes(8);
            $pdo->prepare("UPDATE users SET totp_secret = ?, totp_enabled = 1, totp_backup_codes = ? WHERE id = ?")
                ->execute([$secret, json_encode($backupCodes), $_SESSION['user_id']]);
            unset($_SESSION['totp_pending_secret']);
            $_SESSION['totp_verified'] = true;
            header('Location: ?step=backup_codes&new=1');
            exit;
        }

    // 2FA abschalten (nur mit gueltigem Code)
    } elseif ($action === 'disable_2fa' && !empty($user['totp_enabled'])) {
        $code = trim($_POST['totp_code'] ?? '');
        if (!TOTP::verify((string)$user['totp_secret'], $code)) {
            $error = t('2fa_error_disable');
            $step  = 'disable';
        } else {
            $pdo->prepare("UPDATE users SET totp_secret = NULL, totp_enabled = 0, totp_backup_codes = NULL WHERE id = ?")
                ->execute([$_SESSION['user_id']]);
            header('Location: ?step=overview&disabled=1');
            exit;
        }
    }
}

$pendingSecret = $_SESSION['totp_pending_secret'] ?? null;
if ($step === 'verify' && !$pendingSecret) {
    $step = 'overview';
}
if ($step === 'backup_codes' && empty($user['totp_enabled'])) {
    $step = 'overview';
}
$otpauthUrl = $pendingSecret
    ? TOTP::getOtpauthUrl($pendingSecret, $user['username'], defined('APP_NAME') ? APP_NAME : 'ValuSafe')
    : null;
$backupCodes = !empty($user['totp_backup_codes']) ? (json_decode($user['totp_backup_codes'], true) ?: []) : [];

include __DIR__ . '/header_next_page.php';
?>
<div class="vs-page-content" style="max-width:760px">
<div class="vs-page-header">
    <h1><?php echo t('2fa_page_title'); ?></h1>
    <p class="text-muted"><?php echo t('2fa_page_subtitle'); ?></p>
</div>

<?php if ($message): ?>
    <div class="alert alert-success" role="status"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if (isset($_GET['disabled'])): ?>
    <div class="alert alert-warning" role="status"><?php echo t('2fa_disabled_msg'); ?></div>
<?php endif; ?>

<?php if ($step === 'overview'): ?>
<div class="card">
    <div class="card-body" style="padding:2rem">
        <?php if (!empty($user['totp_enabled'])): ?>
            <h2 style="font-size:1.2rem;margin:0 0 .4rem;color:var(--vs-success)"><?php echo t('2fa_status_active'); ?></h2>
            <p class="text-muted" style="margin-bottom:1.5rem"><?php echo t('2fa_status_active_desc'); ?></p>
            <?php if ($darf2fa): ?>
            <div style="display:flex;gap:1rem;flex-wrap:wrap">
                <a href="?step=backup_codes" class="btn btn-secondary"><?php echo t('2fa_btn_show_backup'); ?></a>
                <a href="?step=disable" class="btn btn-danger"><?php echo t('2fa_btn_disable'); ?></a>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <h2 style="font-size:1.2rem;margin:0 0 .4rem"><?php echo t('2fa_status_inactive'); ?></h2>
            <p class="text-muted" style="margin-bottom:1.5rem"><?php echo t('2fa_status_inactive_desc'); ?></p>
            <?php if ($darf2fa): ?>
            <p style="margin-bottom:1.5rem"><?php echo t('2fa_app_hint'); ?><br>
            <strong>Google Authenticator</strong>, <strong>Authy</strong>, <strong>Microsoft Authenticator</strong>.</p>
            <form method="POST">
                <?php echo Security::getCSRFInput(); ?>
                <input type="hidden" name="action" value="start_setup">
                <button type="submit" class="btn btn-primary"><?php echo t('2fa_btn_setup'); ?></button>
            </form>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (!$darf2fa): ?>
            <p class="text-muted" style="margin:0"><?php echo t('2fa_change_disabled'); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($step === 'verify'): ?>
<div class="card">
    <div class="card-header"><h2 style="font-size:1.1rem;margin:0"><?php echo t('2fa_step1_title'); ?></h2></div>
    <div class="card-body" style="padding:2rem">
        <p style="margin-bottom:1.5rem"><?php echo t('2fa_step1_desc'); ?></p>
        <div style="text-align:center;margin-bottom:1.5rem">
            <div id="qrcode" role="img" aria-label="<?php echo htmlspecialchars(t('2fa_step1_title'), ENT_QUOTES, 'UTF-8'); ?>"
                 style="display:inline-block;background:#fff;padding:12px;border-radius:8px"></div>
            <script src="js/qrcode.min.js"></script>
            <script src="js/qrcode_sicher.js"></script>
            <script>
                vsQrCode(document.getElementById("qrcode"), {
                    text: <?php echo json_encode($otpauthUrl); ?>,
                    width: 200, height: 200,
                    colorDark: "#000000", colorLight: "#ffffff"
                });
            </script>
        </div>
        <details style="margin-bottom:1.5rem">
            <summary style="cursor:pointer;color:var(--vs-text-muted)"><?php echo t('2fa_manual_entry'); ?></summary>
            <code style="display:block;margin-top:.5rem;font-size:1rem;letter-spacing:.15em;background:var(--vs-surface-2);color:var(--vs-text);padding:.5rem 1rem;border-radius:6px;word-break:break-all">
                <?php echo htmlspecialchars($pendingSecret); ?>
            </code>
        </details>
        <h3 style="font-size:1rem;margin:1.5rem 0 1rem"><?php echo t('2fa_step2_title'); ?></h3>
        <form method="POST">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="action" value="verify_and_enable">
            <div class="form-group" style="max-width:240px">
                <label for="totp_code"><?php echo t('2fa_code_label'); ?></label>
                <input type="text" id="totp_code" name="totp_code" class="form-control"
                       inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
                       autofocus required
                       style="font-size:1.2rem;letter-spacing:.2em;text-align:center">
            </div>
            <div style="display:flex;gap:1rem;margin-top:1rem;flex-wrap:wrap">
                <button type="submit" class="btn btn-primary"><?php echo t('2fa_btn_confirm'); ?></button>
                <a href="?step=overview" class="btn btn-secondary"><?php echo t('2fa_btn_cancel'); ?></a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($step === 'backup_codes'): ?>
<div class="card">
    <div class="card-header"><h2 style="font-size:1.1rem;margin:0"><?php echo t('2fa_backup_title'); ?></h2></div>
    <div class="card-body" style="padding:2rem">
        <?php if (isset($_GET['new'])): ?>
            <div class="alert alert-success" role="status"><?php echo t('2fa_backup_success'); ?></div>
        <?php endif; ?>
        <p style="margin-bottom:1rem">
            <?php echo t('2fa_backup_desc'); ?><br>
            <strong><?php echo t('2fa_backup_once'); ?></strong>
        </p>
        <ul style="list-style:none;padding:0;display:grid;grid-template-columns:repeat(2,1fr);gap:.5rem;max-width:320px;margin:0 0 1.5rem">
            <?php foreach ($backupCodes as $code): ?>
                <li><code style="display:block;background:#f0f4ff;color:#1a1a2e;border:1px solid #c0c8e8;padding:.4rem .75rem;border-radius:6px;font-size:1rem;letter-spacing:.1em;text-align:center;font-weight:600"><?php echo htmlspecialchars($code); ?></code></li>
            <?php endforeach; ?>
        </ul>
        <div style="display:flex;gap:1rem;flex-wrap:wrap">
            <button type="button" onclick="window.print()" class="btn btn-secondary"><?php echo t('2fa_btn_print'); ?></button>
            <a href="?step=overview" class="btn btn-primary"><?php echo t('2fa_btn_done'); ?></a>
        </div>
    </div>
</div>

<?php elseif ($step === 'disable'): ?>
<div class="card" style="border-color:var(--vs-danger)">
    <div class="card-header"><h2 style="font-size:1.1rem;margin:0"><?php echo t('2fa_disable_title'); ?></h2></div>
    <div class="card-body" style="padding:2rem">
        <p style="margin-bottom:1.5rem"><?php echo t('2fa_disable_warning'); ?></p>
        <form method="POST">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="action" value="disable_2fa">
            <div class="form-group" style="max-width:240px">
                <label for="totp_code_off"><?php echo t('2fa_disable_code_label'); ?></label>
                <input type="text" id="totp_code_off" name="totp_code" class="form-control"
                       inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
                       autofocus required
                       style="font-size:1.2rem;letter-spacing:.2em;text-align:center">
            </div>
            <div style="display:flex;gap:1rem;margin-top:1rem;flex-wrap:wrap">
                <button type="submit" class="btn btn-danger"><?php echo t('2fa_btn_disable'); ?></button>
                <a href="?step=overview" class="btn btn-secondary"><?php echo t('2fa_btn_cancel'); ?></a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<p style="margin-top:1.5rem"><a href="settings.php"><?php echo t('nav_settings'); ?></a></p>
</div>

<?php include __DIR__ . '/footer_next_page.php'; ?>
