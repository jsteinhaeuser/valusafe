<?php
// reset_password.php - Self-Service Passwort-Reset (neues Passwort setzen), ValuSafe
require_once 'db.php';
require_once 'security.php';
require_once 'password_reset_helpers.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

ensurePasswordResetsTable($db);

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$success = false;

$userId = validatePasswordResetToken($db, $token);

if (!$userId) {
    $error = 'Dieser Link ist ungültig oder abgelaufen. Bitte fordere einen neuen Link an.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['csrf_token'] ?? '';
    $password1 = $_POST['password1'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!Security::validateCSRFToken($submitted_token)) {
        $error = 'Ungültiges Sicherheits-Token. Bitte Seite neu laden.';
    } elseif (strlen($password1) < 8) {
        $error = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
    } elseif ($password1 !== $password2) {
        $error = 'Die beiden Passwörter stimmen nicht überein.';
    } else {
        try {
            $hash = Security::hashPassword($password1);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);
            consumePasswordResetToken($db, $token);
            Security::logSecurityEvent('password_reset_completed', ['user_id' => $userId]);
            $success = true;
        } catch (PDOException $e) {
            error_log("reset_password error: " . $e->getMessage());
            $error = 'Passwort konnte nicht gespeichert werden.';
        }
    }
}

$appName = defined('APP_NAME') ? APP_NAME : 'ValuSafe';
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Passwort zurücksetzen – <?php echo htmlspecialchars($appName); ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #0c1f3d 0%, #1a3d6b 50%, #2a5f9e 100%);
    padding: 1rem;
}
.box { max-width: 420px; width: 100%; background: #fff; padding: 32px; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.35); }
h2 { font-size: 18px; margin-bottom: 4px; color: #0c1f3d; font-weight: 500; }
.subtitle { color: #888780; font-size: 12px; margin-bottom: 24px; }
label { display: block; font-size: 12px; font-weight: 500; color: #444441; margin-bottom: 5px; }
input {
    width: 100%; padding: 10px 12px; border: 1.5px solid #d3d1c7; border-radius: 8px;
    font-size: 13px; color: #2c2c2a; background: #f8f7f5; margin-bottom: 16px; outline: none;
}
input:focus { border-color: #185fa5; box-shadow: 0 0 0 3px rgba(24,95,165,0.12); background: #fff; }
button {
    width: 100%; padding: 11px; background: #185fa5; color: #fff; border: none; border-radius: 8px;
    font-size: 14px; font-weight: 500; cursor: pointer;
}
button:hover { background: #1a6bbf; }
.msg-success { background: #f0fdf4; border-left: 3px solid #27ae60; color: #166534; padding: 9px 12px; border-radius: 6px; margin-bottom: 16px; font-size: 12px; line-height: 1.4; }
.msg-error { background: #fef2f2; border-left: 3px solid #dc2626; color: #991b1b; padding: 9px 12px; border-radius: 6px; margin-bottom: 16px; font-size: 12px; line-height: 1.4; }
.back-link { display: block; text-align: center; margin-top: 16px; color: #888780; font-size: 12px; text-decoration: underline; }
.hint { font-size: 11px; color: #888780; margin: -10px 0 16px 0; }
</style>
</head>
<body>
<div class="box">
    <h2>🔑 Neues Passwort setzen</h2>
    <p class="subtitle"><?php echo htmlspecialchars($appName); ?></p>

    <?php if ($error): ?><div class="msg-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <?php if ($success): ?>
        <div class="msg-success">Dein Passwort wurde erfolgreich geändert. Du kannst dich jetzt einloggen.</div>
        <a href="login.php" class="back-link">→ Zum Login</a>
    <?php elseif ($userId): ?>
        <form method="POST" action="">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <label for="password1">Neues Passwort</label>
            <input type="password" id="password1" name="password1" required autofocus autocomplete="new-password">
            <p class="hint">Mindestens 8 Zeichen.</p>
            <label for="password2">Neues Passwort (Wiederholung)</label>
            <input type="password" id="password2" name="password2" required autocomplete="new-password">
            <button type="submit">Passwort speichern</button>
        </form>
    <?php else: ?>
        <a href="forgot_password.php" class="back-link">→ Neuen Link anfordern</a>
    <?php endif; ?>

    <?php if (!$success): ?>
    <a href="login.php" class="back-link">← Zurück zum Login</a>
    <?php endif; ?>
</div>
</body>
</html>
