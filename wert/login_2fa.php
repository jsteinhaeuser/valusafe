<?php
/**
 * login_2fa.php — Zweiter Login-Schritt: TOTP-Code eingeben
 */
require_once 'db.php';
require_once 'TOTP.php';

// Bereits eingeloggt?
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Kein pending 2FA?
if (empty($_SESSION['totp_pending_user_id'])) {
    header('Location: login.php');
    exit;
}

// Timeout: 5 Minuten für 2FA-Eingabe
if (time() - ($_SESSION['totp_pending_time'] ?? 0) > 300) {
    session_unset();
    header('Location: login.php?error=timeout');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['totp_code'] ?? '');

    // User laden
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['totp_pending_user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        session_unset();
        header('Location: login.php');
        exit;
    }

    $verified = false;

    // Normaler TOTP-Code
    if (TOTP::verify($user['totp_secret'], $code)) {
        $verified = true;
    }

    // Backup-Code prüfen
    if (!$verified && !empty($user['totp_backup_codes'])) {
        $backupCodes = json_decode($user['totp_backup_codes'], true);
        $codeUpper   = strtoupper(trim($code));
        $key = array_search($codeUpper, $backupCodes);
        if ($key !== false) {
            // Backup-Code einmalig verwenden → aus Liste entfernen
            unset($backupCodes[$key]);
            $pdo->prepare("UPDATE users SET totp_backup_codes = ? WHERE id = ?")
                ->execute([json_encode(array_values($backupCodes)), $user['id']]);
            $verified = true;
        }
    }

    if ($verified) {
        // Vollständig einloggen
        $pending_id = $_SESSION['totp_pending_user_id'];
        session_regenerate_id(true);
        Security::createSessionFingerprint();
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['theme']         = $user['theme'] ?? 'cloud';
        // Im Konto gespeicherte Sprache (wie login.php, seit 4.3.33)
        if (in_array($user['lang'] ?? '', VS_SPRACHEN, true)) {
            $_SESSION['lang'] = $user['lang'];
        }
        $_SESSION['login_time']    = time();
        $_SESSION['last_activity'] = time();
        unset($_SESSION['totp_pending_user_id'],
              $_SESSION['totp_pending_username'],
              $_SESSION['totp_pending_time']);

        $pdo->prepare("UPDATE users SET letzter_login = NOW() WHERE id = ?")
            ->execute([$user['id']]);
        logSecurityEvent($pdo, 'login_success_2fa', $user['id'], '2FA: ' . $user['username']);

        header('Location: index.php');
        exit;
    } else {
        logSecurityEvent($pdo, 'login_failed_2fa', $user['id'], '2FA fehlgeschlagen: ' . $user['username']);
        $error = 'Ungültiger Code. Bitte nochmal versuchen.';
    }
}

$username = $_SESSION['totp_pending_username'] ?? '';
$theme    = $_SESSION['theme'] ?? 'navy';
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>2FA — <?= defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'ValuSafe' ?></title>
<link rel="stylesheet" href="css/tokens.css">
<link rel="stylesheet" href="css/next.css">
<link rel="stylesheet" href="css/<?= htmlspecialchars($theme) ?>.css">
<link rel="stylesheet" href="css/wcag_global.css">
<style>
.login-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; background:var(--vs-bg); }
.login-card { background:var(--vs-surface); border:1px solid var(--vs-border); border-radius:16px; padding:2.5rem; width:100%; max-width:380px; box-shadow:0 8px 32px rgba(0,0,0,.15); }
.login-icon { font-size:3rem; text-align:center; margin-bottom:1rem; }
h1 { text-align:center; font-size:1.4rem; margin-bottom:.25rem; color:var(--vs-text); }
.sub { text-align:center; color:var(--vs-text-muted); font-size:.9rem; margin-bottom:2rem; }
.form-group { margin-bottom:1.25rem; }
label { display:block; font-size:.85rem; color:var(--vs-text-muted); margin-bottom:.4rem; }
.code-input { width:100%; padding:.75rem 1rem; font-size:2rem; letter-spacing:.4em; text-align:center; border:2px solid var(--vs-border); border-radius:10px; background:var(--vs-bg); color:var(--vs-text); outline:none; transition:border-color .2s; }
.code-input:focus { border-color:var(--vs-accent); }
.btn-primary { width:100%; padding:.75rem; font-size:1rem; background:var(--vs-accent); color:#fff; border:none; border-radius:10px; cursor:pointer; font-weight:600; margin-top:.5rem; }
.btn-primary:hover { background:var(--vs-accent-hover); }
.alert-danger { background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.3); color:#f87171; padding:.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:.9rem; }
.back { text-align:center; margin-top:1.5rem; }
.back a { color:var(--vs-text-muted); font-size:.85rem; text-decoration:none; }
.back a:hover { color:var(--vs-accent); }
.hint { font-size:.8rem; color:var(--vs-text-muted); text-align:center; margin-top:1rem; }
</style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-icon">🔐</div>
        <h1>Zwei-Faktor-Authentifizierung</h1>
        <p class="sub">Angemeldet als <strong><?= htmlspecialchars($username) ?></strong></p>

        <?php if ($error): ?>
            <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= Security::getCSRFInput() ?>
            <div class="form-group">
                <label>Code aus der Authenticator-App</label>
                <input type="text" name="totp_code" class="code-input"
                       inputmode="text" maxlength="8"
                       placeholder="000000" autofocus autocomplete="one-time-code" required>
            </div>
            <button type="submit" class="btn-primary">Bestätigen →</button>
        </form>

        <p class="hint">Oder gib einen Backup-Code ein (XXXXXXXX)</p>

        <div class="back">
            <a href="login.php">← Zurück zum Login</a>
        </div>
    </div>
</div>
</body>
</html>
