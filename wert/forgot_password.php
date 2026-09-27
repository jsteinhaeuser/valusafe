<?php
// forgot_password.php - Self-Service Passwort-Reset (Anfrage), ValuSafe
require_once 'db.php';
require_once 'security.php';
require_once 'RateLimiter.php';
require_once 'password_reset_helpers.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

ensurePasswordResetsTable($db);

$rateLimiter = new RateLimiter($db);
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCSRFToken($submitted_token)) {
        $error = 'Ungültiges Sicherheits-Token. Bitte Seite neu laden.';
    } elseif ($rateLimiter->isLocked($clientIP, 'password_reset')) {
        $minutes = ceil($rateLimiter->getRemainingLockTime($clientIP, 'password_reset') / 60);
        $error = "Zu viele Anfragen. Bitte in $minutes Minute(n) erneut versuchen.";
    } else {
        $usernameInput = trim($_POST['username'] ?? '');
        $rateLimiter->recordAttempt($clientIP, 'password_reset');

        // Immer dieselbe generische Meldung, unabhängig davon ob der Nutzer existiert
        // oder eine E-Mail hinterlegt hat (kein Enumeration-Leck).
        $message = 'Falls ein Konto mit diesem Benutzernamen existiert und eine E-Mail-Adresse '
                  . 'hinterlegt ist, wurde soeben ein Link zum Zurücksetzen des Passworts versendet.';

        if ($usernameInput !== '') {
            try {
                $stmt = $pdo->prepare("SELECT id, email FROM users WHERE username = ?");
                $stmt->execute([$usernameInput]);
                $user = $stmt->fetch();

                if ($user && !empty($user['email'])) {
                    $token = createPasswordResetToken($db, (int)$user['id']);
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $resetLink = $scheme . '://' . $host . '/reset_password.php?token=' . $token;

                    $appName = defined('APP_NAME') ? APP_NAME : 'ValuSafe';
                    sendPasswordResetEmail($user['email'], $resetLink, $appName);

                    Security::logSecurityEvent('password_reset_requested', ['username' => $usernameInput]);
                }
            } catch (PDOException $e) {
                error_log("forgot_password error: " . $e->getMessage());
                Security::logSecurityEvent('password_reset_error', ['error' => $e->getMessage()]);
            }
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
<title>Passwort vergessen – <?php echo htmlspecialchars($appName); ?></title>
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
</style>
</head>
<body>
<div class="box">
    <h2>🔑 Passwort vergessen</h2>
    <p class="subtitle">Gib deinen Benutzernamen ein — falls eine E-Mail-Adresse hinterlegt ist, senden wir dir einen Link zum Zurücksetzen.</p>

    <?php if ($message): ?><div class="msg-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <?php if (!$message): ?>
    <form method="POST" action="">
        <?php echo Security::getCSRFInput(); ?>
        <label for="username">Benutzername</label>
        <input type="text" id="username" name="username" required autofocus autocomplete="username">
        <button type="submit">Link anfordern</button>
    </form>
    <?php endif; ?>

    <a href="login.php" class="back-link">← Zurück zum Login</a>
</div>
</body>
</html>
