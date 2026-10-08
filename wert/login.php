<?php
// login.php - Design C (Split Layout)
require_once 'db.php';
require_once 'security.php';
require_once 'RateLimiter.php';

// Bereits eingeloggt?
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Sprache vor der Anmeldung waehlen (?lang=en). Die Sprache selbst erkennt
// db.php beim ersten Aufruf (Cookie, Browser); hier nur der Umschalter.
if (isset($_GET['lang']) && in_array($_GET['lang'], VS_SPRACHEN, true)) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('user_language', $_GET['lang'],
        ['expires' => time() + 365 * 24 * 3600, 'path' => '/', 'samesite' => 'Lax']);
    header('Location: login.php' . (isset($_GET['timeout']) ? '?timeout=1' : ''));
    exit;
}

// Rate Limiter
$rateLimiter = new RateLimiter($db);
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (rand(1, 100) <= 5) { $rateLimiter->cleanup(); }

// Version aus version.json laden
$appVersion = '';
$versionFile = __DIR__ . '/wert-update/version.json';
if (file_exists($versionFile)) {
    $vd = json_decode(file_get_contents($versionFile), true);
    $appVersion = $vd['version'] ?? '';
}

$error = '';
$username = '';

// CSRF-Token generieren
if (empty($_SESSION['csrf_token_login'])) {
    $_SESSION['csrf_token_login'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token_login'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validieren
    $submittedToken = $_POST['csrf_token_login'] ?? '';
    if (!hash_equals($_SESSION['csrf_token_login'] ?? '', $submittedToken)) {
        $error = t('login_csrf');
    } elseif ($rateLimiter->isLocked($clientIP)) {
        $minutes = ceil($rateLimiter->getRemainingLockTime($clientIP) / 60);
        $error = sprintf(t('login_locked_wait'), $minutes);
    } else {
        $username = trim($_POST['username'] ?? '');
        $passwort = trim($_POST['password'] ?? '');
        if (empty($username) || empty($passwort)) {
            $error = t('login_fill_all_fields');
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                if ($user && password_verify($passwort, $user['password'])) {
                    // 2FA prüfen
                    if (!empty($user['totp_enabled']) && $user['totp_enabled'] == 1) {
                        // Passwort OK, aber 2FA nötig → temporäre Session
                        session_regenerate_id(true);
                        $_SESSION['totp_pending_user_id'] = $user['id'];
                        $_SESSION['totp_pending_username'] = $user['username'];
                        $_SESSION['totp_pending_time']    = time();
                        header('Location: login_2fa.php');
                        exit;
                    }
                    // Kein 2FA → direkt einloggen
                    session_regenerate_id(true);
                    Security::createSessionFingerprint();
                    $_SESSION['user_id']      = $user['id'];
                    $_SESSION['username']     = $user['username'];
                    $_SESSION['role']         = $user['role'];
                    $_SESSION['theme']        = $user['theme'] ?? 'cloud';
                    // Bis 4.3.32 wurde die im Konto gespeicherte Sprache (users.lang,
                    // gesetzt von set_language.php) nie geladen - jede neue Sitzung
                    // begann deutsch.
                    if (in_array($user['lang'] ?? '', VS_SPRACHEN, true)) {
                        $_SESSION['lang'] = $user['lang'];
                    }
                    $_SESSION['login_time']   = time();
                    $_SESSION['last_activity']= time();
                    $pdo->prepare("UPDATE users SET letzter_login = NOW() WHERE id = ?")->execute([$user['id']]);
                    logSecurityEvent($pdo, 'login_success', $user['id'], 'Benutzer: ' . $username);
                    $rateLimiter->reset($clientIP);
                    header('Location: index.php');
                    exit;
                } else {
                    $rateLimiter->recordAttempt($clientIP);
                    $userId = $user ? $user['id'] : null;
                    logSecurityEvent($pdo, 'login_failed', $userId, 'Benutzer: ' . $username);
                    $remaining = $rateLimiter->getRemainingAttempts($clientIP);
                    if ($remaining > 0) {
                        $error = sprintf(t('login_invalid_remaining'), $remaining);
                    } else {
                        $minutes = ceil($rateLimiter->getRemainingLockTime($clientIP) / 60);
                        $error = sprintf(t('login_locked_now'), $minutes);
                    }
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = t('login_error');
            }
        }
    }
}

$timeout_msg = isset($_GET['timeout']) ? t('login_session_expired') : '';

// App-Name und Tagline aus config.php (mit Fallback)
$appName    = defined('APP_NAME')    ? APP_NAME    : 'ValuSafe';
$appTagline = defined('APP_TAGLINE') ? APP_TAGLINE : t('login_tagline');
$loginLang  = in_array($_SESSION['lang'] ?? '', VS_SPRACHEN, true) ? $_SESSION['lang'] : 'de';
?>
<!DOCTYPE html>
<html lang="<?php echo $loginLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('login_title')); ?> – <?php echo htmlspecialchars($appName); ?></title>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0c1f3d 0%, #1a3d6b 50%, #2a5f9e 100%);
            padding: 1rem;
        }

        .login-wrap {
            width: 100%;
            max-width: 680px;
            display: flex;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
            min-height: 420px;
        }

        /* ── Linke Seite ── */
        .login-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 2.5rem 2rem;
            background: transparent;
        }

        .logo-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .logo-name {
            color: #fff;
            font-size: 20px;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .login-tagline {
            color: rgba(255,255,255,0.45);
            font-size: 13px;
            line-height: 1.65;
            margin-top: 1.5rem;
            max-width: 200px;
        }

        .version-badge {
            display: inline-block;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 11px;
            color: rgba(255,255,255,0.55);
            letter-spacing: 0.3px;
        }

        /* ── Rechte Seite ── */
        .login-right {
            width: 280px;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2.5rem 2rem;
            flex-shrink: 0;
        }

        .login-right h1 {
            font-size: 18px;
            font-weight: 500;
            color: #0c1f3d;
            margin-bottom: 4px;
        }

        .login-right .subtitle {
            font-size: 12px;
            color: #888780;
            margin-bottom: 1.75rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #444441;
            margin-bottom: 5px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap svg {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .form-group input {
            width: 100%;
            padding: 10px 11px 10px 34px;
            border: 1.5px solid #d3d1c7;
            border-radius: 8px;
            font-size: 13px;
            color: #2c2c2a;
            background: #f8f7f5;
            transition: border-color .15s, box-shadow .15s;
            outline: none;
        }

        .form-group input:focus {
            border-color: #185fa5;
            box-shadow: 0 0 0 3px rgba(24,95,165,0.12);
            background: #fff;
        }

        .pw-wrap {
            display: flex;
            align-items: center;
            border: 1.5px solid #d3d1c7;
            border-radius: 8px;
            background: #f8f7f5;
            transition: border-color .15s, box-shadow .15s;
            overflow: hidden;
        }

        .pw-wrap:focus-within {
            border-color: #185fa5;
            box-shadow: 0 0 0 3px rgba(24,95,165,0.12);
            background: #fff;
        }

        .pw-wrap input {
            flex: 1;
            min-width: 0;
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
            padding: 10px 4px 10px 8px !important;
            outline: none !important;
        }

        .pw-wrap input:focus {
            border: none !important;
            box-shadow: none !important;
            outline: none !important;
        }

        .pw-toggle {
            flex-shrink: 0;
            background: none;
            border: none;
            border-left: 1px solid #e8e7e3;
            cursor: pointer;
            padding: 0 10px;
            color: #888780;
            display: flex;
            align-items: center;
            align-self: stretch;
        }

        .pw-toggle:hover { color: #185fa5; background: #f0efe9; }

        .btn-login {
            width: 100%;
            padding: 11px;
            background: #185fa5;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background .15s, transform .1s;
            margin-top: 0.5rem;
        }

        .btn-login:hover { background: #1a6bbf; }
        .btn-login:active { transform: scale(0.98); }

        .msg-error {
            background: #fef2f2;
            border-left: 3px solid #dc2626;
            color: #991b1b;
            padding: 9px 12px;
            border-radius: 6px;
            font-size: 12px;
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .msg-info {
            background: #eff6ff;
            border-left: 3px solid #2563eb;
            color: #1d4ed8;
            padding: 9px 12px;
            border-radius: 6px;
            font-size: 12px;
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        /* Sprachwahl unter dem Formular (4.3.33) */
        .login-lang {
            margin-top: 18px;
            display: flex; flex-wrap: wrap; justify-content: center; gap: 4px 10px;
            font-size: 12px; color: #888780;
        }
        .login-lang a { color: #888780; text-decoration: none; padding: 2px 0; }
        .login-lang a:hover, .login-lang a:focus { text-decoration: underline; }
        .login-lang strong { color: #444441; }

        /* Mobile: Linke Seite ausblenden */
        @media (max-width: 520px) {
            .login-left { display: none; }
            .login-right { width: 100%; padding: 2rem 1.5rem; border-radius: 16px; }
            .login-wrap { max-width: 360px; border-radius: 16px; }
        }
    </style>
</head>
<body>

<div class="login-wrap">

    <!-- Linke Seite -->
    <div class="login-left">
        <div>
            <div class="logo-row">
                <div class="logo-icon">
                    <svg width="22" height="22" viewBox="0 0 22 22" fill="none">
                        <rect x="2" y="2" width="8" height="8" rx="2" fill="#fff"/>
                        <rect x="12" y="2" width="8" height="8" rx="2" fill="#fff" opacity="0.55"/>
                        <rect x="2" y="12" width="8" height="8" rx="2" fill="#fff" opacity="0.55"/>
                        <rect x="12" y="12" width="8" height="8" rx="2" fill="#fff" opacity="0.25"/>
                    </svg>
                </div>
                <span class="logo-name"><?php echo htmlspecialchars($appName); ?></span>
            </div>
            <p class="login-tagline"><?php echo htmlspecialchars($appTagline); ?></p>
        </div>
        <?php if ($appVersion): ?>
        <div>
            <span class="version-badge">v<?php echo htmlspecialchars($appVersion); ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Rechte Seite -->
    <div class="login-right">
        <h1><?php echo htmlspecialchars(t('login_button')); ?></h1>
        <p class="subtitle"><?php echo htmlspecialchars(t('login_subtitle')); ?></p>

        <?php if ($timeout_msg): ?>
            <div class="msg-info"><?php echo htmlspecialchars($timeout_msg); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="msg-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token_login" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <div class="form-group">
                <label for="username"><?php echo htmlspecialchars(t('login_username')); ?></label>
                <div class="input-wrap">
                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
                        <circle cx="7.5" cy="5" r="2.5" stroke="#888780" stroke-width="1.4"/>
                        <path d="M2 13c0-2.2 2.5-4 5.5-4s5.5 1.8 5.5 4" stroke="#888780" stroke-width="1.4" stroke-linecap="round"/>
                    </svg>
                    <input type="text" id="username" name="username"
                           value="<?php echo htmlspecialchars($username); ?>"
                           placeholder="<?php echo htmlspecialchars(t('login_username')); ?>" required autofocus autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="password"><?php echo htmlspecialchars(t('login_password')); ?></label>
                <div class="pw-wrap">
                    <svg style="flex-shrink:0; margin-left:10px;" width="15" height="15" viewBox="0 0 15 15" fill="none">
                        <rect x="2.5" y="6.5" width="10" height="6" rx="1.5" stroke="#888780" stroke-width="1.4"/>
                        <path d="M5 6.5V4.5a2.5 2.5 0 015 0v2" stroke="#888780" stroke-width="1.4" stroke-linecap="round"/>
                    </svg>
                    <input type="password" id="password" name="password"
                           placeholder="<?php echo htmlspecialchars(t('login_password')); ?>" required autocomplete="current-password">
                    <button type="button" class="pw-toggle" id="pwToggle" aria-label="<?php echo htmlspecialchars(t('login_show_password')); ?>">
                        <svg id="eyeIcon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z" stroke="currentColor" stroke-width="1.4"/>
                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.4"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login"><?php echo htmlspecialchars(t('login_button')); ?></button>

        </form>

        <p style="margin-top:14px; text-align:center;">
            <a href="forgot_password.php" style="color:#888780; font-size:12px; text-decoration:underline;"><?php echo htmlspecialchars(t('login_forgot')); ?></a>
        </p>

        <?php // Sprachwahl vor der Anmeldung (4.3.33) ?>
        <nav class="login-lang" aria-label="<?php echo htmlspecialchars(t('login_language')); ?>">
            <?php foreach (['de' => 'DE', 'en' => 'EN', 'fr' => 'FR', 'es' => 'ES', 'it' => 'IT', 'nl' => 'NL', 'pl' => 'PL', 'pt' => 'PT', 'tr' => 'TR'] as $code => $kurz): ?>
                <?php if ($code === $loginLang): ?>
                    <strong aria-current="true"><?php echo $kurz; ?></strong>
                <?php else: ?>
                    <a href="login.php?lang=<?php echo $code; ?><?php echo isset($_GET['timeout']) ? '&amp;timeout=1' : ''; ?>" lang="<?php echo $code; ?>" hreflang="<?php echo $code; ?>"><?php echo $kurz; ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>
</div>

<script>
const pwToggle = document.getElementById('pwToggle');
const pwInput  = document.getElementById('password');
const eyeIcon  = document.getElementById('eyeIcon');

const eyeOpen   = '<path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z" stroke="currentColor" stroke-width="1.4"/><circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.4"/>';
const eyeClosed = '<path d="M2 2l12 12M6.5 6.7A2 2 0 0110 9.5M4.2 4.4C2.8 5.5 1.8 7 1 8c1.5 2.5 4 4 7 4 1.2 0 2.3-.3 3.3-.8M7 3.1c.3 0 .7-.1 1-.1 3 0 5.5 1.5 7 4-.5.8-1.1 1.6-1.8 2.2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>';

pwToggle.addEventListener('click', function() {
    const show = pwInput.type === 'password';
    pwInput.type = show ? 'text' : 'password';
    eyeIcon.innerHTML = show ? eyeClosed : eyeOpen;
});
</script>

</body>
</html>
