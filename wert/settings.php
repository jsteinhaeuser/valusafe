<?php
// settings.php
require_once 'db.php';
if (!function_exists('correctImageOrientation')) require_once 'helpers.php';
require_once __DIR__ . '/dsgvo_helpers.php';

// tn()-Fallback falls nicht durch Next-Interface-Header definiert
if (!function_exists('tn')) {
    function tn(string $key, string $fallback): string {
        $v = function_exists('t') ? t($key) : $key;
        return ($v === null || $v === false || $v === '' || $v === $key) ? $fallback : $v;
    }
}
requireLogin();
define('PAGE_TITLE', t('nav_settings') . ' - Wertsachen-Inventar');

$message = '';
$error = '';

$allowed_themes = ['cloud', 'ocean', 'forest', 'sunset', 'midnight', 'cherry', 'lavender', 'mint', 'sand', 'glass', 'navy', 'emerald'];

// ── Avatar-Upload-Funktion (aus profil.php übernommen) ─────────────────────
if (!function_exists('handleAvatarUpload')) {
    function handleAvatarUpload(array $file, ?string $oldAvatar, int $userId): array {
        $avatarDir = UPLOAD_DIR . 'avatars/';
        if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);

        if (!isset($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['filename' => $oldAvatar];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Upload-Fehler (Code ' . $file['error'] . ')'];
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['error' => 'Datei zu groß (max. 5 MB)'];
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['heic', 'heif'])) $mimeType = 'image/heic';

        $allowed = ['image/jpeg','image/png','image/gif','image/webp','image/heic','image/heif'];
        if (!in_array($mimeType, $allowed)) {
            return ['error' => 'Ungültiger Dateityp (' . $mimeType . ')'];
        }

        $src = match($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png'  => @imagecreatefrompng($file['tmp_name']),
            'image/gif'  => @imagecreatefromgif($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
            default      => false,
        };
        if (!$src) return ['error' => 'Bild konnte nicht verarbeitet werden'];

        if (function_exists('correctImageOrientation')) {
            $src = correctImageOrientation($src, $file['tmp_name']);
        }

        // Center-Crop auf Quadrat, dann auf 300×300 skalieren
        $w  = imagesx($src);
        $h  = imagesy($src);
        $sq = min($w, $h);
        $x  = (int)(($w - $sq) / 2);
        $y  = (int)(($h - $sq) / 2);

        $dest = imagecreatetruecolor(300, 300);
        imagecopyresampled($dest, $src, 0, 0, $x, $y, 300, 300, $sq, $sq);

        $filename = 'avatar_' . $userId . '_' . time() . '.jpg';
        $path     = $avatarDir . $filename;
        if (!imagejpeg($dest, $path, 88)) {
            return ['error' => 'Speichern fehlgeschlagen'];
        }
        imagedestroy($src);
        imagedestroy($dest);

        if ($oldAvatar && file_exists($avatarDir . $oldAvatar)) {
            unlink($avatarDir . $oldAvatar);
        }

        return ['filename' => $filename];
    }
}

// ── Theme-AJAX (vor validateRequest, früher Exit mit JSON) ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme'])
    && !isset($_POST['export_my_data'], $_POST['standard_ersteller'], $_POST['change_password'])) {

    // Alle bisherigen Ausgaben verwerfen damit JSON-Header gesetzt werden kann
    if (ob_get_level()) ob_end_clean();

    header('Content-Type: application/json; charset=UTF-8');
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCSRFToken($token)) {
        echo json_encode(['success' => false, 'error' => 'csrf']);
        exit;
    }
    $theme = $_POST['theme'];
    if (!in_array($theme, $allowed_themes)) {
        echo json_encode(['success' => false, 'error' => 'invalid_theme: ' . $theme]);
        exit;
    }
    try {
        $db->execute(
            "UPDATE users SET theme = ? WHERE username = ?",
            [$theme, $_SESSION['username']]
        );
        $_SESSION['theme']      = $theme;
        $_SESSION['user_theme'] = $theme;
        echo json_encode(['success' => true, 'rows' => 1, 'user' => $_SESSION['username']]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateRequest();

    // ── Nutzerbezogener Datenexport (DSGVO Art. 20) ──────────────────────
    if (isset($_POST['export_my_data'])) {
        try {
            $userId   = $_SESSION['user_id'];
            $username = $_SESSION['username'];

            // Abfragen in dsgvo_helpers.php, direkt ueber PDO: $db->select()
            // schluckt Fehler und haette einen leeren Export geliefert - bis
            // 4.3.29 genau das, wegen falscher Spaltennamen.
            $export = dsgvoDatenSammeln($pdo, (int)$userId, (string)$username);

            $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $filename = 'meine-daten-' . date('Y-m-d') . '.json';

            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($json));
            echo $json;

            Security::logSecurityEvent('data_export', ['user_id' => $userId]);
            exit;

        } catch (PDOException $e) {
            error_log('Datenexport: ' . $e->getMessage());
            $error = 'Datenexport fehlgeschlagen. Bitte wende dich an den Administrator.';
        }
    }

    // ── Account-Löschung (DSGVO Art. 17) ─────────────────────────────────
    if (isset($_POST['delete_account'])) {
        $confirmText = trim($_POST['confirm_delete'] ?? '');
        $userId      = $_SESSION['user_id'];
        $username    = $_SESSION['username'];

        if ($confirmText !== $username) {
            $error = 'Bestätigung fehlgeschlagen. Bitte gib deinen Benutzernamen exakt ein.';
        // isRealAdmin(): ein Admin-Konto bleibt ein Admin-Konto, auch waehrend
        // eines Rollenwechsels. Zuvor griff der Schutz im Testmodus nicht.
        } elseif (isRealAdmin()) {
            $error = 'Das Admin-Konto kann nicht gelöscht werden. Bitte zuerst einen anderen Admin ernennen.';
        } else {
            try {
                // Konto loeschen, Protokolle davon loesen (dsgvo_helpers.php).
                // Bis 4.3.29 scheiterte das hier an der Spalte benutzer_id,
                // die es in activity_log nicht gibt - bei jedem Konto.
                $avatar = dsgvoKontoLoeschen($pdo, (int)$userId);
                if ($avatar !== null && basename($avatar) === $avatar
                    && is_file(UPLOAD_DIR . 'avatars/' . $avatar)) {
                    @unlink(UPLOAD_DIR . 'avatars/' . $avatar);
                }

                Security::logSecurityEvent('account_deleted', ['username' => $username]);

                // Session beenden
                session_destroy();
                header('Location: login.php?msg=account_deleted');
                exit;

            } catch (PDOException $e) {
                error_log('Kontoloeschung: ' . $e->getMessage());
                $error = 'Account-Löschung fehlgeschlagen. Bitte wende dich an den Administrator.';
            }
        }
    }

    if (isset($_POST['theme'])) {
        $theme = $_POST['theme'];
        if (in_array($theme, $allowed_themes)) {
            try {
                $db->execute("UPDATE users SET theme = ? WHERE username = ?", [$theme, $_SESSION['username']]);
                $_SESSION['theme'] = $theme;
                $message = 'Theme erfolgreich gespeichert';
                Security::logSecurityEvent('theme_changed', ['theme' => $theme]);
            } catch (PDOException $e) {
                $error = t('settings_error_theme_save') . ': ' . $e->getMessage();
            }
        } else {
            $error = t('settings_error_invalid_theme');
        }
    }

    // ── Avatar hochladen ──────────────────────────────────────────────────
    if (($_POST['action'] ?? '') === 'upload_avatar') {
        $userId = $_SESSION['user_id'];
        $currentUser = $db->selectOne("SELECT avatar FROM users WHERE id = ?", [$userId]);
        $result = handleAvatarUpload($_FILES['avatar'] ?? [], $currentUser['avatar'] ?? null, $userId);
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            try {
                $db->execute("UPDATE users SET avatar = ? WHERE id = ?", [$result['filename'], $userId]);
                $message = 'Profilbild gespeichert';
            } catch (PDOException $e) {
                error_log('settings avatar: ' . $e->getMessage());
                $error = 'Profilbild konnte nicht gespeichert werden.';
            }
        }
    }

    // ── Avatar löschen ───────────────────────────────────────────────────
    if (($_POST['action'] ?? '') === 'delete_avatar') {
        $userId = $_SESSION['user_id'];
        $currentUser = $db->selectOne("SELECT avatar FROM users WHERE id = ?", [$userId]);
        $old = $currentUser['avatar'] ?? null;
        if ($old && file_exists(UPLOAD_DIR . 'avatars/' . $old)) {
            unlink(UPLOAD_DIR . 'avatars/' . $old);
        }
        try {
            $db->execute("UPDATE users SET avatar = NULL WHERE id = ?", [$userId]);
            $message = 'Profilbild entfernt';
        } catch (PDOException $e) {
            error_log('settings avatar delete: ' . $e->getMessage());
            $error = 'Profilbild konnte nicht entfernt werden.';
        }
    }

    // ── Passwort ändern ──────────────────────────────────────────────────
    if (($_POST['action'] ?? '') === 'change_password') {
        $userId = $_SESSION['user_id'];
        $oldPw  = $_POST['old_password']  ?? '';
        $newPw  = $_POST['new_password']  ?? '';
        $newPw2 = $_POST['new_password2'] ?? '';
        $pwUser = $db->selectOne("SELECT password FROM users WHERE id = ?", [$userId]);
        if (!$pwUser || !password_verify($oldPw, $pwUser['password'])) {
            $error = 'Aktuelles Passwort ist falsch.';
        } elseif (strlen($newPw) < 8) {
            $error = 'Neues Passwort muss mindestens 8 Zeichen haben.';
        } elseif ($newPw !== $newPw2) {
            $error = 'Die neuen Passwörter stimmen nicht überein.';
        } else {
            // Siehe profil.php: bis 4.3.17 wurde der Erfolg gemeldet und
            // protokolliert, ohne dass feststand, ob das UPDATE durchging.
            try {
                $hash = password_hash($newPw, PASSWORD_ARGON2ID);
                $db->execute("UPDATE users SET password = ? WHERE id = ?", [$hash, $userId]);
                Security::logSecurityEvent('password_changed', ['user_id' => $userId, 'username' => $_SESSION['username']]);
                $message = 'Passwort erfolgreich geändert';
            } catch (PDOException $e) {
                error_log('settings password: ' . $e->getMessage());
                $error = 'Das Passwort konnte nicht gespeichert werden. Das bisherige gilt weiter.';
            }
        }
    }

    if (isset($_POST['standard_ersteller'])) {
        $standard_ersteller = trim($_POST['standard_ersteller']);
        if (empty($standard_ersteller)) {
            $error = t('settings_error_creator_empty');
        } elseif (strlen($standard_ersteller) > 100) {
            $error = t('settings_error_creator_long');
        } else {
            try {
                $db->execute("UPDATE users SET standard_ersteller = ? WHERE username = ?",
                            [$standard_ersteller, $_SESSION['username']]);
                $_SESSION['standard_ersteller'] = $standard_ersteller;
                $message = t('settings_success_creator');
            } catch (PDOException $e) {
                $error = t('settings_error_save') . ': ' . $e->getMessage();
            }
        }
    }
}

try {
    $user = $db->selectOne("SELECT theme, standard_ersteller, avatar FROM users WHERE username = ?", [$_SESSION['username']]);
    if (!$user) $user = ['theme' => 'cloud', 'standard_ersteller' => $_SESSION['username'], 'avatar' => null];
    $_SESSION['theme'] = $user['theme'] ?? 'cloud';
} catch (PDOException $e) {
    $user = ['theme' => 'cloud', 'standard_ersteller' => $_SESSION['username'], 'avatar' => null];
}

$current_theme = $user['theme'] ?? 'cloud';
$avatarFile    = $user['avatar'] ?? null;
$avatarUrl     = $avatarFile ? (defined('ASSET_BASE') ? ASSET_BASE : '') . 'upload/avatars/' . rawurlencode($avatarFile) : null;

// Sichtbare Themes in der Auswahl (5 Favoriten)
// Versteckte Themes (forest, sunset, cherry, lavender, sand) bleiben in allowed_themes
// damit bestehende Nutzer ihr gespeichertes Theme behalten
$theme_tiles = [
    'cloud'    => ['Cloud',    '☁️',  '#F8F6F2', '#F0EDE5', '#3D3833', '#8B8378'],
    'ocean'    => ['Ocean',    '🌊',  '#e0f7fa', '#b2ebf2', '#263238', '#00acc1'],
    'midnight' => ['Midnight', '🌙',  '#0f0f1a', '#1a1a2e', '#e0e0f0', '#a78bfa'],
    'mint'     => ['Mint',     '🌿',  '#f0fdf4', '#dcfce7', '#14342a', '#059669'],
    'glass'    => ['Glass',    '✨',  '#f0ebff', '#e8f0ff', '#2e1f6e', '#7c3aed'],
    'navy'     => ['Navy',     '🔷',  '#e6f1fb', '#b5d4f4', '#0c1f3d', '#185fa5'],
    'emerald'  => ['Emerald',  '💚',  '#ecfdf5', '#a7f3d0', '#022c22', '#047857'],
];

// Statistiken vorab laden
try {
    $stats = [
        'wertsachen' => $db->selectOne("SELECT COUNT(*) as count FROM wertsachen")['count'],
        'orte'       => $db->selectOne("SELECT COUNT(*) as count FROM raeume")['count'],
        'kategorien' => $db->selectOne("SELECT COUNT(*) as count FROM kategorien")['count'],
        'gesamtwert' => $db->selectOne("SELECT SUM(preis) as sum FROM wertsachen")['sum'] ?? 0
    ];
} catch (PDOException $e) {
    $stats = null;
}

include 'header_next_page.php';
?>

<style>
/* ── Settings Kacheln ─────────────────────────────────────── */
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.settings-card {
    background: rgba(255,255,255,0.6);
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    transition: box-shadow 0.2s;
}

.settings-card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.10);
}

.settings-card h3 {
    margin: 0 0 16px 0;
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    border: none;
    padding: 0;
}

.settings-card--wide {
    grid-column: 1 / -1;
}

/* ── Profil-Kachel: Avatar ─────────────────────────────────── */
.profil-avatar-wrap {
    position: relative;
    width: 76px; height: 76px;
    margin: 0 auto 10px;
    cursor: pointer;
}
.profil-avatar-wrap:hover .profil-avatar-overlay { opacity: 1; }
.profil-avatar {
    width: 76px; height: 76px;
    border-radius: 50%;
    background: var(--vs-accent, #185fa5);
    color: #fff;
    font-size: 32px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    overflow: hidden;
    user-select: none;
}
.profil-avatar img {
    width: 100%; height: 100%;
    object-fit: cover;
    border-radius: 50%;
}
.profil-avatar-overlay {
    position: absolute; inset: 0;
    border-radius: 50%;
    background: rgba(0,0,0,0.48);
    display: flex; align-items: center; justify-content: center;
    opacity: 0;
    transition: opacity 0.18s;
    color: #fff;
    font-size: 18px;
}
.profil-avatar-delete {
    position: absolute;
    top: -3px; right: -3px;
    width: 20px; height: 20px;
    border-radius: 50%;
    background: #e74c3c;
    color: #fff;
    border: 2px solid #fff;
    font-size: 11px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    line-height: 1;
    transition: background 0.15s;
}
.profil-avatar-delete:hover { background: #c0392b; }

@media (max-width: 900px) {
    .settings-grid > div[style*="grid-template-columns:1fr 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}

/* ── Theme Kacheln ─────────────────────────────────────────── */
.theme-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
    margin-bottom: 16px;
}

.theme-tile {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    cursor: pointer;
    border: 3px solid transparent;
    transition: all 0.22s ease;
    background: linear-gradient(135deg, var(--bg1), var(--bg2));
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}
.theme-tile:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 18px rgba(0,0,0,0.14);
    border-color: var(--accent);
}
.theme-tile--active {
    border-color: var(--accent) !important;
    box-shadow: 0 0 0 3px var(--accent), 0 4px 14px rgba(0,0,0,0.14) !important;
}
.theme-tile input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
.theme-tile__preview { height: 64px; padding: 5px; display: flex; flex-direction: column; gap: 3px; }
.theme-tile__header { height: 12px; border-radius: 3px; opacity: 0.9; }
.theme-tile__nav { display: flex; gap: 2px; height: 8px; }
.theme-tile__btn { height: 8px; border-radius: 2px; flex: 1; }
.theme-tile__row { height: 7px; border-radius: 2px; width: 100%; }
.theme-tile__label { padding: 5px 6px 7px; display: flex; align-items: center; gap: 4px; background: rgba(255,255,255,0.95); border-top: 1px solid rgba(0,0,0,0.06); }
.theme-tile__emoji { font-size: 12px; }
.theme-tile__name { font-size: 11px; font-weight: 600; color: var(--text); }
.theme-tile__check { position: absolute; top: 4px; right: 4px; background: #3D3833; color: white; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; }

/* ── Stat Kacheln ──────────────────────────────────────────── */
.stat-mini-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.stat-mini { background: rgba(0,0,0,0.03); border-radius: 10px; padding: 12px; text-align: center; }
.stat-mini-value { font-size: 22px; font-weight: 700; }
.stat-mini-label { font-size: 11px; color: #888; margin-top: 2px; }

/* ── Role Switch ───────────────────────────────────────────── */
.role-buttons { display: flex; flex-direction: column; gap: 8px; }
.role-btn { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 10px; border: 1.5px solid rgba(0,0,0,0.10); background: rgba(255,255,255,0.5); cursor: pointer; font-size: 14px; font-family: inherit; font-weight: 500; transition: all 0.18s; text-decoration: none; color: inherit; }
.role-btn:hover { background: rgba(255,255,255,0.9); border-color: rgba(0,0,0,0.20); transform: translateX(3px); }
.role-btn--active { background: rgba(109,40,217,0.08); border-color: rgba(109,40,217,0.30); }

@media (max-width: 700px) {
    .settings-grid { grid-template-columns: 1fr; }
    .theme-grid { grid-template-columns: repeat(3, 1fr); }
}
</style>

<h2><i class="ti ti-settings"></i> <?php echo t('settings_title'); ?></h2>

<?php if ($message): ?>
    <div class="success-message"><?php echo esc($message); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="error-message"><?php echo esc($error); ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['original_role'])): ?>
<div style="background:rgba(243,156,18,0.12); border:1.5px solid rgba(243,156,18,0.4); padding:14px 18px; border-radius:12px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
    <span><i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i> Testmodus: Sie sind als <strong><?php echo esc($_SESSION['role']); ?></strong> angemeldet (Original: Admin)</span>
    <form method="POST" action="switch_role.php" style="margin:0;">
        <?php echo Security::getCSRFInput(); ?>
        <input type="hidden" name="restore" value="1">
        <button type="submit" class="vs-btn vs-btn-primary vs-btn-sm"><i class="ti ti-arrow-back-up"></i> Zurück zu Admin</button>
    </form>
</div>
<?php endif; ?>

<div class="settings-grid">

    <!-- Theme + Sprache nebeneinander -->
    <div style="grid-column:1 / -1; display:grid; grid-template-columns:1fr 1fr 1fr; grid-template-rows:auto auto; gap:20px; align-items:start;">

    <!-- Theme-Kachel: 2 Spalten breit -->
    <div class="settings-card" style="grid-column:1 / 3; grid-row:1;">
        <h3><i class="ti ti-palette"></i> <?php echo t('settings_theme_title'); ?></h3>
        <div id="theme-status" style="font-size:13px; color:var(--vs-success); min-height:20px; margin-bottom:8px;"></div>
        <form method="POST" action="" id="theme-form">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="theme" id="theme-hidden" value="<?php echo esc($current_theme); ?>">
            <div class="theme-grid">
                <?php foreach ($theme_tiles as $value => [$label, $emoji, $bg1, $bg2, $text, $accent]): ?>
                    <?php $active = ($current_theme === $value); ?>
                    <label class="theme-tile <?php echo $active ? 'theme-tile--active' : ''; ?>"
                           data-theme="<?php echo $value; ?>"
                           style="--bg1:<?php echo $bg1; ?>; --bg2:<?php echo $bg2; ?>; --text:<?php echo $text; ?>; --accent:<?php echo $accent; ?>; cursor:pointer;">
                        <div class="theme-tile__preview" aria-hidden="true">
                            <div class="theme-tile__header" style="background:<?php echo $accent; ?>;"></div>
                            <div class="theme-tile__nav">
                                <div class="theme-tile__btn" style="background:<?php echo $accent; ?>; opacity:.8;"></div>
                                <div class="theme-tile__btn" style="background:<?php echo $accent; ?>; opacity:.5;"></div>
                                <div class="theme-tile__btn" style="background:<?php echo $accent; ?>; opacity:.3;"></div>
                            </div>
                            <div class="theme-tile__row" style="background:<?php echo $accent; ?>22;"></div>
                            <div class="theme-tile__row" style="width:70%; background:<?php echo $accent; ?>15;"></div>
                            <div class="theme-tile__row" style="width:85%; background:<?php echo $accent; ?>15;"></div>
                        </div>
                        <div class="theme-tile__label">
                            <span class="theme-tile__emoji"><?php echo $emoji; ?></span>
                            <span class="theme-tile__name"><?php echo $label; ?></span>
                        </div>
                        <?php if ($active): ?><div class="theme-tile__check" id="check-<?php echo $value; ?>" style="background:#3D3833;" aria-hidden="true">✓</div><?php endif; ?>
                        <?php if (!$active): ?><div class="theme-tile__check" id="check-<?php echo $value; ?>" style="display:none;" aria-hidden="true">✓</div><?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </form>
        <script>
        document.querySelectorAll('.theme-tile').forEach(function(tile) {
            tile.addEventListener('click', function() {
                var theme = this.dataset.theme;
                var csrf  = document.querySelector('#theme-form input[name=csrf_token]').value;
                var status = document.getElementById('theme-status');

                // Sofort visuell umschalten
                document.querySelectorAll('.theme-tile').forEach(function(t) {
                    t.classList.remove('theme-tile--active');
                    document.getElementById('check-' + t.dataset.theme).style.display = 'none';
                });
                this.classList.add('theme-tile--active');
                var checkEl = document.getElementById('check-' + theme); checkEl.style.display = 'flex'; checkEl.style.background = '#3D3833';
                status.textContent = '⏳ Speichere…';

                // AJAX-Save
                var fd = new FormData();
                fd.append('theme', theme);
                fd.append('csrf_token', csrf);

                fetch('settings.php', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            status.textContent = '✅ Gespeichert (rows:' + data.rows + ' user:' + data.user + ')';
                            var link = document.getElementById('theme-css');
                            if (link) {
                                link.href = link.href.replace(/\/css\/[^?]+\.css(\?.*)?$/, '/css/' + theme + '.css?v=' + Date.now());
                            }
                            setTimeout(function() { window.location.reload(); }, 2000);
                        } else {
                            status.textContent = '❌ Fehler: ' + (data.error || 'unbekannt') + ' user:' + (data.user || '?');
                        }
                    })
                    .catch(function() {
                        status.textContent = '❌ Netzwerkfehler';
                    });
            });
        });
        </script>
    </div>

    <!-- Sprach-Karte -->
    <?php
    $currentLang = $_SESSION['lang'] ?? 'de';
    $langOptions = [
        'de' => ['flag' => '🇩🇪', 'label' => 'Deutsch'],
        'en' => ['flag' => '🇬🇧', 'label' => 'English'],
        'fr' => ['flag' => '🇫🇷', 'label' => 'Français'],
        'es' => ['flag' => '🇪🇸', 'label' => 'Español'],
        'it' => ['flag' => '🇮🇹', 'label' => 'Italiano'],
        'nl' => ['flag' => '🇳🇱', 'label' => 'Nederlands'],
        'pl' => ['flag' => '🇵🇱', 'label' => 'Polski'],
        'pt' => ['flag' => '🇵🇹', 'label' => 'Português'],
        'tr' => ['flag' => '🇹🇷', 'label' => 'Türkçe'],
    ];
    ?>
    <div class="settings-card" style="grid-column:3; grid-row:1 / 3; align-self:start;">
        <h3><i class="ti ti-world"></i> <?php echo tn('settings_language_title', 'Sprache'); ?></h3>
        <div id="lang-status" style="font-size:13px; color:var(--vs-success); min-height:20px; margin-bottom:10px;"></div>
        <div style="display:flex; flex-direction:column; gap:6px;">
            <?php foreach ($langOptions as $code => $opt):
                $active = ($currentLang === $code); ?>
            <button class="lang-btn" data-lang="<?php echo $code; ?>"
                    style="display:flex; align-items:center; gap:10px; padding:9px 14px;
                           border-radius:8px; cursor:pointer; font-size:13px; width:100%;
                           text-align:left; font-family:inherit; transition:all .15s;
                           <?php if ($active): ?>
                               border:2px solid var(--vs-accent,#185fa5);
                               background:var(--vs-bg,#e6f1fb); font-weight:600;
                           <?php else: ?>
                               border:2px solid var(--vs-border,#e2e8f0);
                               background:var(--vs-surface);
                           <?php endif; ?>">
                <span style="font-size:18px"><?php echo $opt['flag']; ?></span>
                <?php echo $opt['label']; ?>
                <?php if ($active): ?><span style="margin-left:auto">✓</span><?php endif; ?>
            </button>
            <?php endforeach; ?>
        </div>
        <script>
        (function() {
            var csrf = document.querySelector('#theme-form input[name=csrf_token]').value;
            document.querySelectorAll('.lang-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var lang = this.dataset.lang;
                    var status = document.getElementById('lang-status');
                    status.textContent = '⏳ Speichere…';

                    var fd = new FormData();
                    fd.append('lang', lang);
                    fd.append('csrf_token', csrf);

                    fetch('set_language.php', { method: 'POST', body: fd })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success) {
                                status.textContent = '✅ Sprache gespeichert';
                                setTimeout(function() { window.location.reload(); }, 800);
                            } else {
                                status.textContent = '❌ ' + (data.error || 'Fehler');
                            }
                        })
                        .catch(function() { status.textContent = '❌ Netzwerkfehler'; });
                });
            });
        })();
        </script>
    </div>

    <!-- Zeile 2: Standard-Ersteller, Sicherheit & Session, Profil — 3 schmalere Kacheln nebeneinander -->
    <div style="grid-column:1 / 3; grid-row:2; display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; align-items:start;">

    <!-- Standard-Ersteller -->
    <div class="settings-card">
        <h3><i class="ti ti-pencil"></i> <?php echo t('settings_default_creator_title'); ?></h3>
        <p style="font-size:13px; color:#888; margin-bottom:14px;"><?php echo t('settings_default_creator_description'); ?></p>
        <form method="POST" action="">
            <?php echo Security::getCSRFInput(); ?>
            <div class="form-group">
                <label for="standard_ersteller">Name:</label>
                <input type="text" id="standard_ersteller" name="standard_ersteller"
                       value="<?php echo esc($user['standard_ersteller'] ?? $_SESSION['username']); ?>"
                       required maxlength="100">
            </div>
            <button type="submit" class="btn btn-primary"><?php echo t('btn_save'); ?></button>
        </form>
    </div>

    <!-- Sicherheit & Session -->
    <div class="settings-card">
        <h3><i class="ti ti-lock"></i> <?php echo t('settings_security_session'); ?></h3>
        <div style="font-size:14px; line-height:2;">
            <div><i class="ti ti-user"></i> <strong><?php echo esc($_SESSION['username']); ?></strong>
                <?php echo getRoleBadge(getUserRole()); ?>
            </div>
            <div><i class="ti ti-clock"></i> <?php echo t('settings_last_activity'); ?>: <?php echo date('H:i:s', $_SESSION['last_activity']); ?></div>
            <div><i class="ti ti-clock"></i> <?php echo t('settings_expires_in'); ?>: <?php echo ceil((SESSION_LIFETIME - (time() - $_SESSION['last_activity'])) / 60); ?> Min.</div>
        </div>
        <div style="margin-top:16px;">
            <a href="logout.php" class="btn btn-danger"><?php echo t('nav_logout'); ?></a>
        </div>
    </div>

    <!-- Profil: Avatar + Passwort ändern -->
    <div class="settings-card">
        <h3><i class="ti ti-user-circle"></i> Profil</h3>

        <!-- Avatar-Upload (unsichtbares Input, per Klick auf Avatar aktiviert) -->
        <form method="POST" enctype="multipart/form-data" id="avatarForm">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="action" value="upload_avatar">
            <input type="file" name="avatar" id="avatarInput"
                   accept="image/jpeg,image/png,image/gif,image/webp,.heic,.heif"
                   style="display:none;"
                   onchange="document.getElementById('avatarForm').submit();">
        </form>

        <div class="profil-avatar-wrap"
             onclick="document.getElementById('avatarInput').click()"
             title="Profilbild ändern">
            <div class="profil-avatar">
                <?php if ($avatarUrl): ?>
                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>"
                         alt="Profilbild von <?php echo htmlspecialchars($_SESSION['username']); ?>">
                <?php else: ?>
                    <?php echo mb_strtoupper(mb_substr($_SESSION['username'], 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div class="profil-avatar-overlay">📷</div>

            <?php if ($avatarUrl): ?>
            <form method="POST" style="display:inline;"
                  onsubmit="return vsConfirmForm(event, <?php echo htmlspecialchars(json_encode(t('confirm_remove_avatar')), ENT_QUOTES, 'UTF-8'); ?>);">
                <?php echo Security::getCSRFInput(); ?>
                <input type="hidden" name="action" value="delete_avatar">
                <button type="submit"
                        class="profil-avatar-delete"
                        onclick="event.stopPropagation();"
                        title="Profilbild entfernen">✕</button>
            </form>
            <?php endif; ?>
        </div>
        <p style="font-size:12px; color:#aaa; text-align:center; margin:0 0 18px;">
            Klick auf den Avatar zum Ändern
        </p>

        <!-- Passwort ändern -->
        <form method="POST" autocomplete="off">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="action" value="change_password">
            <?php
            // Passwortverwaltungen brauchen ein Benutzername-Feld, um das neue
            // Passwort dem richtigen Konto zuzuordnen; ohne das speichern sie es
            // gar nicht oder unter dem falschen Eintrag. Bewusst ohne name, damit
            // beim Absenden kein zusaetzliches Feld ankommt.
            ?>
            <input type="text" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>"
                   autocomplete="username" readonly hidden aria-hidden="true" tabindex="-1">
            <div class="form-group">
                <label for="old_password">Aktuelles Passwort</label>
                <input type="password" name="old_password" id="old_password"
                       placeholder="••••••••" autocomplete="current-password" required>
            </div>
            <div class="form-group">
                <label for="new_password">Neues Passwort <small style="color:#999;font-weight:400;">(min. 8 Zeichen)</small></label>
                <input type="password" name="new_password" id="new_password"
                       placeholder="••••••••" autocomplete="new-password" required minlength="8">
            </div>
            <div class="form-group">
                <label for="new_password2">Neues Passwort bestätigen</label>
                <input type="password" name="new_password2" id="new_password2"
                       placeholder="••••••••" autocomplete="new-password" required minlength="8">
            </div>
            <button type="submit" class="btn btn-primary">Passwort ändern</button>
        </form>
    </div>

    </div><!-- Ende Zeile-2-Wrapper -->

    </div><!-- Ende 3-Spalten Wrapper -->
    <!-- Statistiken -->
    <div class="settings-card">
        <h3><i class="ti ti-chart-bar"></i> <?php echo t('nav_stats'); ?></h3>
        <?php if ($stats): ?>
        <div class="stat-mini-grid">
            <div class="stat-mini">
                <div class="stat-mini-value"><?php echo (int)$stats['wertsachen']; ?></div>
                <div class="stat-mini-label"><?php echo t('stats_items'); ?></div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value"><?php echo (int)$stats['orte']; ?></div>
                <div class="stat-mini-label"><?php echo t('stats_locations'); ?></div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value"><?php echo (int)$stats['kategorien']; ?></div>
                <div class="stat-mini-label"><?php echo t('stats_categories'); ?></div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value" style="font-size:16px;"><?php echo number_format((float)$stats['gesamtwert'], 0, ',', '.'); ?> €</div>
                <div class="stat-mini-label"><?php echo t('stats_total_value'); ?></div>
            </div>
        </div>
        <?php else: ?>
            <p style="color:#888;"><?php echo t('error_loading'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Backup -->
    <div class="settings-card">
        <h3><i class="ti ti-device-floppy"></i> Backup</h3>
        <p style="font-size:13px; color:#888; margin-bottom:16px;"><?php echo t('settings_backup_description'); ?></p>
        <a href="backend/backup.php" class="vs-btn vs-btn-secondary">Zum Backup-Dashboard →</a>
    </div>

    <!-- DSGVO: Datenschutz & Meine Daten -->
    <div class="settings-card">
        <h3><i class="ti ti-shield-lock"></i> <?php echo t('settings_privacy_title'); ?></h3>
        <p style="font-size:13px; color:var(--vs-text-muted); margin-bottom:20px;">
            <?php echo t('settings_privacy_desc'); ?>
        </p>

        <!-- Datenexport -->
        <div style="margin-bottom:20px; padding-bottom:20px; border-bottom:1px solid rgba(0,0,0,0.08);">
            <strong style="font-size:14px;"><i class="ti ti-package"></i> <?php echo t('settings_export_title'); ?></strong>
            <p style="font-size:13px; color:var(--vs-text-muted); margin:6px 0 12px;">
                <?php echo t('settings_export_desc'); ?>
            </p>
            <form method="POST" action="">
                <?php echo Security::getCSRFInput(); ?>
                <button type="submit" name="export_my_data" class="btn btn-secondary"
                        style="font-size:14px;">
                    <i class="ti ti-download"></i> <?php echo t('settings_export_btn'); ?>
                </button>
            </form>
        </div>

        <!-- Account-Löschung -->
        <div>
            <strong style="font-size:14px; color:var(--vs-danger);"><i class="ti ti-trash"></i> <?php echo t('settings_delete_account_title'); ?></strong>
            <p style="font-size:13px; color:var(--vs-text-muted); margin:6px 0 12px;">
                <?php echo t('settings_delete_account_desc'); ?>
            </p>
            <button type="button" class="btn btn-danger" style="font-size:14px;"
                    onclick="document.getElementById('deleteAccountModal').style.display='flex'">
                <i class="ti ti-trash"></i> <?php echo t('settings_delete_account_btn'); ?>
            </button>
        </div>
    </div>

    <!-- Modal: Account löschen -->
    <div id="deleteAccountModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:20px;">
        <div style="background:var(--vs-surface); border-radius:16px; padding:32px; max-width:460px; width:100%; box-shadow:0 16px 48px rgba(0,0,0,0.2);">
            <h3 style="margin:0 0 12px; color:var(--vs-danger);"><i class="ti ti-alert-triangle"></i> <?php echo t('settings_delete_confirm_title'); ?></h3>
            <p style="font-size:14px; color:#555; margin-bottom:20px;">
                <?php echo t('settings_delete_confirm_desc'); ?>
                <strong><?php echo esc($_SESSION['username']); ?></strong>
            </p>
            <form method="POST" action="">
                <?php echo Security::getCSRFInput(); ?>
                <div class="form-group" style="margin-bottom:16px;">
                    <label for="confirm_delete_input" class="sr-only"><?php echo t('settings_delete_confirm_desc'); ?></label>
                    <input type="text" name="confirm_delete"
                           id="confirm_delete_input"
                           placeholder="<?php echo esc($_SESSION['username']); ?>"
                           aria-label="<?php echo htmlspecialchars(t('settings_delete_confirm_desc')); ?>"
                           style="width:100%; padding:10px; border:2px solid var(--vs-danger); border-radius:8px; font-size:15px;"
                           autocomplete="off">
                </div>
                <div style="display:flex; gap:12px;">
                    <button type="submit" name="delete_account"
                            class="btn btn-danger" style="flex:1;">
                        <i class="ti ti-trash"></i> <?php echo t('settings_delete_confirm_btn'); ?>
                    </button>
                    <button type="button" class="btn" style="flex:1;"
                            onclick="document.getElementById('deleteAccountModal').style.display='none'">
                        <?php echo t('form_cancel'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isAdmin() && !isset($_SESSION['original_role'])): ?>
    <div class="settings-card">
        <h3><i class="ti ti-tool"></i> <?php echo t('settings_role_switch'); ?></h3>
        <p style="font-size:13px; color:#888; margin-bottom:14px;"><?php echo t('settings_role_switch_desc'); ?></p>
        <div class="role-buttons">
            <form method="POST" action="switch_role.php" style="margin:0;">
                <?php echo Security::getCSRFInput(); ?>
                <input type="hidden" name="role" value="editor">
                <button type="submit" class="role-btn" style="width:100%;">
                    <i class="ti ti-pencil"></i> <span><?php echo t('settings_login_as_editor'); ?></span>
                </button>
            </form>
            <form method="POST" action="switch_role.php" style="margin:0;">
                <?php echo Security::getCSRFInput(); ?>
                <input type="hidden" name="role" value="read">
                <button type="submit" class="role-btn" style="width:100%;">
                    <i class="ti ti-eye"></i> <span><?php echo t('settings_login_as_viewer'); ?></span>
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>

<div class="form-actions">
    <a href="index.php" class="btn"><?php echo t('btn_back_to_overview') ?: '← Back to overview'; ?></a>
</div>

<?php include 'footer_next_page.php'; ?>
