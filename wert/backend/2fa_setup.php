<?php
/**
 * backend/2fa_setup.php — 2FA/TOTP Einrichtung
 * Für Admin: 2FA aktivieren, deaktivieren, Backup-Codes anzeigen
 */

require_once __DIR__ . '/config.php';
requireBackendAccess();
require_once __DIR__ . '/../TOTP.php';

$pageTitle = t('2fa_page_title');
$message = '';
$error   = '';
$step    = $_GET['step'] ?? 'overview';

// Aktuellen User laden
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();

// POST-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateRequest();
    $action = $_POST['action'] ?? '';

    // Schritt 1: Setup starten — Secret generieren
    if ($action === 'start_setup') {
        $secret = TOTP::generateSecret();
        $_SESSION['totp_pending_secret'] = $secret;
        header('Location: ?step=verify');
        exit;
    }

    // Schritt 2: Code verifizieren und 2FA aktivieren
    if ($action === 'verify_and_enable') {
        $secret = $_SESSION['totp_pending_secret'] ?? '';
        $code   = trim($_POST['totp_code'] ?? '');

        if (empty($secret)) {
            $error = t('2fa_error_session');
        } elseif (!TOTP::verify($secret, $code)) {
            $error = t('2fa_error_code_invalid');
            $step  = 'verify';
        } else {
            // Backup-Codes generieren
            $backupCodes = TOTP::generateBackupCodes(8);
            $backupJson  = json_encode($backupCodes);

            $pdo->prepare("UPDATE users SET totp_secret = ?, totp_enabled = 1, totp_backup_codes = ? WHERE id = ?")
                ->execute([$secret, $backupJson, $_SESSION['user_id']]);

            unset($_SESSION['totp_pending_secret']);
            $_SESSION['totp_verified'] = true;

            header('Location: ?step=backup_codes&new=1');
            exit;
        }
    }

    // 2FA deaktivieren
    if ($action === 'disable_2fa') {
        $code = trim($_POST['totp_code'] ?? '');
        if (!TOTP::verify($user['totp_secret'], $code)) {
            $error = t('2fa_error_disable');
        } else {
            $pdo->prepare("UPDATE users SET totp_secret = NULL, totp_enabled = 0, totp_backup_codes = NULL WHERE id = ?")
                ->execute([$_SESSION['user_id']]);
            $message = t('2fa_disabled_msg');
            header('Location: ?step=overview&disabled=1');
            exit;
        }
    }

    // Neue Backup-Codes generieren
    if ($action === 'regenerate_backup') {
        $code = trim($_POST['totp_code'] ?? '');
        if (!TOTP::verify($user['totp_secret'], $code)) {
            $error = t('2fa_error_generic');
        } else {
            $backupCodes = TOTP::generateBackupCodes(8);
            $pdo->prepare("UPDATE users SET totp_backup_codes = ? WHERE id = ?")
                ->execute([json_encode($backupCodes), $_SESSION['user_id']]);
            header('Location: ?step=backup_codes&new=1');
            exit;
        }
    }
}

// Pending Secret für QR-Code
$pendingSecret = $_SESSION['totp_pending_secret'] ?? null;
$otpauthUrl    = $pendingSecret
    ? TOTP::getOtpauthUrl($pendingSecret, $user['username'], defined('APP_NAME') ? APP_NAME : 'ValuSafe')
    : null;

// Backup Codes laden
$backupCodes = $user['totp_backup_codes'] ? json_decode($user['totp_backup_codes'], true) : [];

include __DIR__ . '/layout/header_next_page.php';
?>

<div class="vs-page-content">
<div class="vs-page-header">
    <h1>🔐 <?= t('2fa_page_title') ?></h1>
    <p class="text-muted"><?= t('2fa_page_subtitle') ?></p>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (isset($_GET['disabled'])): ?>
    <div class="alert alert-warning"><?= t('2fa_disabled_msg') ?></div>
<?php endif; ?>

<!-- ÜBERSICHT -->
<?php if ($step === 'overview'): ?>
<div class="card">
    <div class="card-body" style="padding:2rem">
        <?php if ($user['totp_enabled']): ?>
            <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
                <span style="font-size:2.5rem">✅</span>
                <div>
                    <h3 style="color:#10b981"><?= t('2fa_status_active') ?></h3>
                    <p class="text-muted"><?= t('2fa_status_active_desc') ?></p>
                </div>
            </div>
            <div style="display:flex;gap:1rem;flex-wrap:wrap">
                <a href="?step=backup_codes" class="btn btn-secondary"><?= t('2fa_btn_show_backup') ?></a>
                <a href="?step=disable" class="btn btn-danger"><?= t('2fa_btn_disable') ?></a>
            </div>
        <?php else: ?>
            <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
                <span style="font-size:2.5rem">⚠️</span>
                <div>
                    <h3><?= t('2fa_status_inactive') ?></h3>
                    <p class="text-muted"><?= t('2fa_status_inactive_desc') ?></p>
                </div>
            </div>
            <p style="margin-bottom:1.5rem"><?= t('2fa_app_hint') ?><br>
            <strong>Google Authenticator</strong>, <strong>Authy</strong> oder <strong>Microsoft Authenticator</strong>.</p>
            <form method="POST">
                <?= Security::getCSRFInput() ?>
                <input type="hidden" name="action" value="start_setup">
                <button type="submit" class="btn btn-primary"><?= t('2fa_btn_setup') ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- QR-CODE SCANNEN -->
<?php elseif ($step === 'verify' && $pendingSecret): ?>
<div class="card">
    <div class="card-header"><h3><?= t('2fa_step1_title') ?></h3></div>
    <div class="card-body" style="padding:2rem">
        <p style="margin-bottom:1.5rem"><?= t('2fa_step1_desc') ?></p>

        <div style="text-align:center;margin-bottom:1.5rem">
            <div id="qrcode" style="display:inline-block;background:white;padding:12px;border-radius:8px"></div>
            <script src="../js/qrcode.min.js"></script>
            <script>
                new QRCode(document.getElementById("qrcode"), {
                    text: <?= json_encode($otpauthUrl) ?>,
                    width: 200, height: 200,
                    colorDark: "#000000", colorLight: "#ffffff"
                });
            </script>
        </div>

        <details style="margin-bottom:1.5rem">
            <summary style="cursor:pointer;color:#888"><?= t('2fa_manual_entry') ?></summary>
            <code style="display:block;margin-top:.5rem;font-size:1rem;letter-spacing:.15em;background:#1e2030;padding:.5rem 1rem;border-radius:6px">
                <?= htmlspecialchars($pendingSecret) ?>
            </code>
        </details>

        <hr style="border-color:#333;margin:1.5rem 0">
        <h4 style="margin-bottom:1rem"><?= t('2fa_step2_title') ?></h4>
        <form method="POST">
            <?= Security::getCSRFInput() ?>
            <input type="hidden" name="action" value="verify_and_enable">
            <div class="form-group" style="max-width:200px">
                <label><?= t('2fa_code_label') ?></label>
                <input type="text" name="totp_code" class="form-control"
                       inputmode="text" maxlength="8" autocomplete="one-time-code"
                       placeholder="<?= t('2fa_code_placeholder') ?>" autofocus required
                       style="font-size:1.2rem;letter-spacing:.2em;text-align:center">
                <small style="color:#888;display:block;margin-top:.4rem"><?= t('2fa_code_hint') ?></small>
            </div>
            <div style="display:flex;gap:1rem;margin-top:1rem">
                <button type="submit" class="btn btn-primary"><?= t('2fa_btn_confirm') ?></button>
                <a href="?step=overview" class="btn btn-secondary"><?= t('2fa_btn_cancel') ?></a>
            </div>
        </form>
    </div>
</div>

<!-- BACKUP CODES -->
<?php elseif ($step === 'backup_codes'): ?>
<div class="card">
    <div class="card-header"><h3><?= t('2fa_backup_title') ?></h3></div>
    <div class="card-body" style="padding:2rem">
        <?php if (isset($_GET['new'])): ?>
            <div class="alert alert-success">
                <?= t('2fa_backup_success') ?>
            </div>
        <?php endif; ?>
        <p style="margin-bottom:1rem">
            <?= t('2fa_backup_desc') ?><br>
            <strong><?= t('2fa_backup_once') ?></strong>
        </p>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:.5rem;max-width:320px;margin-bottom:1.5rem">
            <?php foreach ($backupCodes as $code): ?>
                <code style="background:#f0f4ff;color:#1a1a2e;border:1px solid #c0c8e8;padding:.4rem .75rem;border-radius:6px;font-size:1rem;letter-spacing:.1em;text-align:center;font-weight:600">
                    <?= htmlspecialchars($code) ?>
                </code>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:1rem;flex-wrap:wrap">
            <button onclick="window.print()" class="btn btn-secondary"><?= t('2fa_btn_print') ?></button>
            <a href="?step=overview" class="btn btn-primary"><?= t('2fa_btn_done') ?></a>
        </div>
    </div>
</div>

<!-- 2FA DEAKTIVIEREN -->
<?php elseif ($step === 'disable'): ?>
<div class="card" style="border-color:#ef4444">
    <div class="card-header"><h3><?= t('2fa_disable_title') ?></h3></div>
    <div class="card-body" style="padding:2rem">
        <p style="margin-bottom:1.5rem;color:#f87171">
            <?= t('2fa_disable_warning') ?>
        </p>
        <form method="POST">
            <?= Security::getCSRFInput() ?>
            <input type="hidden" name="action" value="disable_2fa">
            <div class="form-group" style="max-width:200px">
                <label><?= t('2fa_disable_code_label') ?></label>
                <input type="text" name="totp_code" class="form-control"
                       inputmode="text" maxlength="8" autocomplete="one-time-code"
                       placeholder="<?= t('2fa_code_placeholder') ?>" autofocus required
                       style="font-size:1.2rem;letter-spacing:.2em;text-align:center">
                <small style="color:#888;display:block;margin-top:.4rem"><?= t('2fa_code_hint') ?></small>
            </div>
            <div style="display:flex;gap:1rem;margin-top:1rem">
                <button type="submit" class="btn btn-danger"><?= t('2fa_btn_disable') ?></button>
                <a href="?step=overview" class="btn btn-secondary"><?= t('2fa_btn_cancel') ?></a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

</div>

<?php include __DIR__ . '/layout/footer_next_page.php'; ?>
