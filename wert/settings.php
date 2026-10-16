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
define('PAGE_TITLE', t('nav_settings') . ' - ' . t('app_title'));

// Eigenes Konto (4.3.33): auf einer oeffentlichen Demo teilen sich alle
// Besucher ein Konto - Passwort aendern oder Konto loeschen sperrte dort alle
// anderen aus. Beides ist jetzt ein Recht (backend/permissions.php), Standard
// wie bisher erlaubt.
$darfPasswort      = hasPermission('settings_password');
$darfKontoLoeschen = hasPermission('settings_delete_account');

$message = '';
$error = '';

$allowed_themes = ['cloud', 'ocean', 'forest', 'sunset', 'midnight', 'cherry', 'lavender', 'mint', 'sand', 'glass', 'navy', 'emerald'];

// ── Avatar-Upload-Funktion ─────────────────────────────────────────────────
if (!function_exists('handleAvatarUpload')) {
    function handleAvatarUpload(array $file, ?string $oldAvatar, int $userId): array {
        $avatarDir = UPLOAD_DIR . 'avatars/';
        if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);

        if (!isset($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['filename' => $oldAvatar];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => sprintf(t('profile_avatar_upload_error'), $file['error'])];
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['error' => t('profile_avatar_too_big')];
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['heic', 'heif'])) $mimeType = 'image/heic';

        $allowed = ['image/jpeg','image/png','image/gif','image/webp','image/heic','image/heif'];
        if (!in_array($mimeType, $allowed)) {
            return ['error' => sprintf(t('profile_avatar_bad_type'), $mimeType)];
        }

        $src = match($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png'  => @imagecreatefrompng($file['tmp_name']),
            'image/gif'  => @imagecreatefromgif($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
            default      => false,
        };
        if (!$src) return ['error' => t('profile_avatar_unreadable')];

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
            return ['error' => t('profile_avatar_store_failed')];
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
            $error = t('settings_export_failed');
        }
    }

    // ── Account-Löschung (DSGVO Art. 17) ─────────────────────────────────
    if (isset($_POST['delete_account'])) {
        $confirmText = trim($_POST['confirm_delete'] ?? '');
        $userId      = $_SESSION['user_id'];
        $username    = $_SESSION['username'];

        if (!$darfKontoLoeschen) {
            $error = t('settings_delete_disabled');
        } elseif ($confirmText !== $username) {
            $error = t('settings_delete_confirm_failed');
        // isRealAdmin(): ein Admin-Konto bleibt ein Admin-Konto, auch waehrend
        // eines Rollenwechsels. Zuvor griff der Schutz im Testmodus nicht.
        } elseif (isRealAdmin()) {
            $error = t('settings_admin_undeletable');
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
                $error = t('settings_delete_failed');
            }
        }
    }

    if (isset($_POST['theme'])) {
        $theme = $_POST['theme'];
        if (in_array($theme, $allowed_themes)) {
            try {
                $db->execute("UPDATE users SET theme = ? WHERE username = ?", [$theme, $_SESSION['username']]);
                $_SESSION['theme'] = $theme;
                $message = t('settings_theme_saved');
                Security::logSecurityEvent('theme_changed', ['theme' => $theme]);
            } catch (PDOException $e) {
                // Kein SQL-Text in die Oberflaeche (wie bei den uebrigen Meldungen)
                error_log('settings theme: ' . $e->getMessage());
                $error = t('settings_error_theme_save');
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
                $message = t('profile_avatar_saved');
            } catch (PDOException $e) {
                error_log('settings avatar: ' . $e->getMessage());
                $error = t('profile_avatar_save_failed');
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
            $message = t('profile_avatar_removed');
        } catch (PDOException $e) {
            error_log('settings avatar delete: ' . $e->getMessage());
            $error = t('profile_avatar_remove_failed');
        }
    }

    // ── Passwort ändern ──────────────────────────────────────────────────
    if (($_POST['action'] ?? '') === 'change_password') {
        $userId = $_SESSION['user_id'];
        $oldPw  = $_POST['old_password']  ?? '';
        $newPw  = $_POST['new_password']  ?? '';
        $newPw2 = $_POST['new_password2'] ?? '';
        $pwUser = $db->selectOne("SELECT password FROM users WHERE id = ?", [$userId]);
        if (!$darfPasswort) {
            $error = t('profile_pw_disabled');
        } elseif (!$pwUser || !password_verify($oldPw, $pwUser['password'])) {
            $error = t('profile_pw_wrong');
        } elseif (strlen($newPw) < 8) {
            $error = t('profile_pw_too_short');
        } elseif ($newPw !== $newPw2) {
            $error = t('profile_pw_mismatch');
        } else {
            // Bis 4.3.17 wurde der Erfolg gemeldet und
            // protokolliert, ohne dass feststand, ob das UPDATE durchging.
            try {
                $hash = password_hash($newPw, PASSWORD_ARGON2ID);
                $db->execute("UPDATE users SET password = ? WHERE id = ?", [$hash, $userId]);
                Security::logSecurityEvent('password_changed', ['user_id' => $userId, 'username' => $_SESSION['username']]);
                $message = t('profile_pw_changed');
            } catch (PDOException $e) {
                error_log('settings password: ' . $e->getMessage());
                $error = t('profile_pw_save_failed');
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
    [$ownSql, $ownParams] = nurEigeneSql('');
    $stats = [
        'wertsachen' => $db->selectOne("SELECT COUNT(*) as count FROM wertsachen WHERE 1=1" . $ownSql, $ownParams)['count'],
        'raeume'     => $db->selectOne("SELECT COUNT(*) as count FROM raeume")['count'],
        'kategorien' => $db->selectOne("SELECT COUNT(*) as count FROM kategorien")['count'],
        'gesamtwert' => $db->selectOne("SELECT SUM(preis) as sum FROM wertsachen WHERE 1=1" . $ownSql, $ownParams)['sum'] ?? 0
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
    /* Farben aus dem Theme: halbweiss (rgba 255/.6) wurde in Midnight grau,
       die helle Midnight-Schrift darauf 2,02:1 (WAVE 13.10.2026) */
    background: var(--vs-surface);
    color: var(--vs-text);
    border: 1px solid var(--vs-border);
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

/* ── Abschnitte (4.5) ──────────────────────────────────────── */
.settings-grid { grid-template-columns: 1fr; gap: 28px; }
.settings-section { min-width: 0; }
.settings-section-title { display: flex; align-items: center; gap: 8px; font-size: 1.05rem; font-weight: 600; margin: 0 0 12px; padding: 0 0 8px; border-bottom: 1px solid var(--vs-border); color: var(--vs-text); }
.settings-section__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; align-items: stretch; } /* gleich hohe Kacheln je Reihe */
.settings-card h4.card-title { margin: 0 0 16px; font-size: 1rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.settings-part { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--vs-border); }
h5.settings-subtitle { margin: 0 0 8px; }
.privacy-split { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; }
.role-buttons--row { flex-direction: row; flex-wrap: wrap; }
.role-buttons--row form { flex: 1 1 220px; }
@media (max-width: 900px) {
    .settings-section__grid, .privacy-split { grid-template-columns: 1fr; }
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
    color: var(--vs-accent-text);
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


/* ── Theme Kacheln ─────────────────────────────────────────── */
.theme-grid {
    display: grid;
    /* sieben Themes in einer Reihe - mit auto-fill rutschte Emerald bei
       900 px allein in eine zweite Reihe (Testinstanz 15.10.) */
    grid-template-columns: repeat(7, minmax(0, 1fr));
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
.theme-tile__name { font-size: 11px; font-weight: 600; color: #1f2937; } /* Streifen ist immer weiss - --text der Midnight-Kachel war hell */
.theme-tile__check { position: absolute; top: 4px; right: 4px; background: #3D3833; color: white; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; }

/* ── Darstellung: Unterueberschriften + Sprachauswahl ─────── */
.settings-subtitle { display: block; font-size: 13px; font-weight: 600; color: var(--vs-text-muted); margin: 0 0 8px; }
.lang-form { border-top: 1px solid var(--vs-border); padding-top: 14px; }
.lang-form__row { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
.lang-form__row select { min-width: 200px; max-width: 100%; padding: 8px 10px; font-size: 14px; font-family: inherit; color: var(--vs-text); background: var(--vs-surface); border: 1px solid var(--vs-border-strong); border-radius: 8px; }
.lang-form__row select:focus-visible { outline: 3px solid var(--vs-accent); outline-offset: 2px; } /* globale Regel in next.css erfasst select nicht */
.lang-form__status { font-size: 13px; color: var(--vs-success); }

/* ── Stat Kacheln ──────────────────────────────────────────── */
.stat-mini-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.stat-mini { background: var(--vs-surface-2); border-radius: 10px; padding: 12px; text-align: center; }
.stat-mini-value { font-size: 22px; font-weight: 700; }
.stat-mini-label { font-size: 11px; color: var(--vs-text-muted); margin-top: 2px; }

/* ── Role Switch ───────────────────────────────────────────── */
.role-buttons { display: flex; flex-direction: column; gap: 8px; }
.role-btn { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 10px; border: 1.5px solid var(--vs-border); background: var(--vs-surface-2); cursor: pointer; font-size: 14px; font-family: inherit; font-weight: 500; transition: all 0.18s; text-decoration: none; color: inherit; }
.role-btn:hover { background: var(--vs-surface); border-color: var(--vs-border-strong); transform: translateX(3px); }
.role-btn--active { background: rgba(109,40,217,0.08); border-color: rgba(109,40,217,0.30); }

@media (max-width: 760px) {
    .theme-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
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
    <span><i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i> <?php echo sprintf(htmlspecialchars(t('settings_testmode'), ENT_QUOTES, 'UTF-8'), '<strong>' . esc($_SESSION['role']) . '</strong>'); ?></span>
    <form method="POST" action="switch_role.php" style="margin:0;">
        <?php echo Security::getCSRFInput(); ?>
        <input type="hidden" name="restore" value="1">
        <button type="submit" class="vs-btn vs-btn-primary vs-btn-sm"><i class="ti ti-arrow-back-up"></i> <?php echo t('settings_testmode_back'); ?></button>
    </form>
</div>
<?php endif; ?>

<div class="settings-grid">

    <?php /* Aufbau seit 4.5 (Joerg 15.10.): vier Abschnitte mit Ueberschrift
             (Darstellung, Mein Konto, Meine Daten, Administration) statt
             eines Rasters ungleich hoher Kacheln. Abschnitte = h3, Kacheln = h4. */ ?>
    <section class="settings-section" aria-labelledby="sec-darstellung">
    <h3 class="settings-section-title" id="sec-darstellung"><i class="ti ti-palette" aria-hidden="true"></i> <?php echo t('settings_appearance_title'); ?></h3>
    <div class="settings-card">
        <h4 class="settings-subtitle" id="theme-subtitle"><?php echo t('settings_theme_title'); ?></h4>
        <div id="theme-status" role="status" aria-live="polite" style="font-size:13px; color:var(--vs-success); min-height:20px; margin-bottom:8px;"></div>
        <form method="POST" action="" id="theme-form">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="theme" id="theme-hidden" value="<?php echo esc($current_theme); ?>">
            <?php // Kacheln als Auswahlgruppe: per Tab erreichbar, Pfeiltasten wechseln,
                  // Enter/Leertaste waehlen (WCAG 2.1.1, 4.1.2; bis 4.4 nur per Maus). ?>
            <div class="theme-grid" role="radiogroup" aria-label="<?php echo esc(t('settings_theme_title')); ?>">
                <?php $_tileAktivDa = array_key_exists($current_theme, $theme_tiles); $_tileErste = true; ?>
                <?php foreach ($theme_tiles as $value => [$label, $emoji, $bg1, $bg2, $text, $accent]): ?>
                    <?php $active = ($current_theme === $value);
                          // Per Tab erreichbar: die gewaehlte Kachel, oder - wenn das gespeicherte
                          // Schema keine Kachel hat (Altbestand, z. B. light) - die erste.
                          $_tabbar = $active || (!$_tileAktivDa && $_tileErste); $_tileErste = false; ?>
                    <label class="theme-tile <?php echo $active ? 'theme-tile--active' : ''; ?>"
                           data-theme="<?php echo $value; ?>"
                           role="radio" aria-checked="<?php echo $active ? 'true' : 'false'; ?>" tabindex="<?php echo $_tabbar ? '0' : '-1'; ?>"
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
        (function () {
            var tiles = Array.prototype.slice.call(document.querySelectorAll('.theme-tile'));
            tiles.forEach(function (tile, i) {
                tile.addEventListener('keydown', function (e) {
                    var ziel = null;
                    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') ziel = tiles[(i + 1) % tiles.length];
                    else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') ziel = tiles[(i - 1 + tiles.length) % tiles.length];
                    else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); tile.click(); return; }
                    if (ziel) { e.preventDefault(); ziel.focus(); ziel.click(); }
                });
            });
        })();
        document.querySelectorAll('.theme-tile').forEach(function(tile) {
            tile.addEventListener('click', function() {
                var theme = this.dataset.theme;
                var csrf  = document.querySelector('#theme-form input[name=csrf_token]').value;
                var status = document.getElementById('theme-status');

                // Sofort visuell umschalten
                document.querySelectorAll('.theme-tile').forEach(function(t) {
                    t.classList.remove('theme-tile--active');
                    t.setAttribute('aria-checked', 'false');
                    t.setAttribute('tabindex', '-1');
                    document.getElementById('check-' + t.dataset.theme).style.display = 'none';
                });
                this.classList.add('theme-tile--active');
                this.setAttribute('aria-checked', 'true');
                this.setAttribute('tabindex', '0');
                var checkEl = document.getElementById('check-' + theme); checkEl.style.display = 'flex'; checkEl.style.background = '#3D3833';
                status.textContent = '⏳ ' + <?php echo json_encode(t('settings_theme_saving')); ?>;

                // AJAX-Save
                var fd = new FormData();
                fd.append('theme', theme);
                fd.append('csrf_token', csrf);

                fetch('settings.php', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            status.textContent = '✅ ' + <?php echo json_encode(t('settings_theme_saved_reload')); ?>;
                            var link = document.getElementById('theme-css');
                            if (link) {
                                link.href = link.href.replace(/\/css\/[^?]+\.css(\?.*)?$/, '/css/' + theme + '.css?v=' + Date.now());
                            }
                            setTimeout(function() { window.location.reload(); }, 2000);
                        } else {
                            status.textContent = '❌ ' + <?php echo json_encode(t('settings_theme_error')); ?>;
                        }
                    })
                    .catch(function() {
                        status.textContent = '❌ ' + <?php echo json_encode(t('error_network')); ?>;
                    });
            });
        });
        </script>

        <?php
        // Sprachauswahl: natives Auswahlfeld statt zehn Knoepfen. Jede Sprache in
        // ihrer eigenen Schreibweise (mit lang-Attribut fuer Screenreader), ohne
        // Flaggen (Windows zeigt keine Flaggen-Emoji; Flagge = Land, nicht Sprache).
        // Wechsel erst per Knopf, nicht beim Auswaehlen - die Seite laedt danach
        // neu, und das darf nicht schon beim Blättern mit den Pfeiltasten
        // passieren (WCAG 3.2.2).
        $currentLang = $_SESSION['lang'] ?? 'de';
        $langOptions = [
            'de' => 'Deutsch',
            'en' => 'English',
            'fr' => 'Français',
            'es' => 'Español',
            'it' => 'Italiano',
            'nl' => 'Nederlands',
            'pl' => 'Polski',
            'pt' => 'Português',
            'tr' => 'Türkçe',
            'ga' => 'Gaeilge (beta)',   // bis die Gegenlesung aus Irland eingearbeitet ist
        ];
        ?>
        <form id="lang-form" class="lang-form" action="" method="post">
            <label for="lang-select" class="settings-subtitle"><?php echo t('settings_language_title'); ?></label>
            <div class="lang-form__row">
                <select id="lang-select" name="lang">
                    <?php foreach ($langOptions as $code => $label): ?>
                    <option value="<?php echo $code; ?>" lang="<?php echo $code; ?>"<?php echo $currentLang === $code ? ' selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary"><?php echo t('btn_apply'); ?></button>
                <span id="lang-status" role="status" aria-live="polite" class="lang-form__status"></span>
            </div>
        </form>
        <script>
        (function() {
            var form   = document.getElementById('lang-form');
            var select = document.getElementById('lang-select');
            var status = document.getElementById('lang-status');
            var aktuell = <?php echo json_encode($currentLang); ?>;
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var lang = select.value;
                if (lang === aktuell) return;
                status.style.color = '';
                status.textContent = '⏳ ' + <?php echo json_encode(t('settings_theme_saving')); ?>;
                var fd = new FormData();
                fd.append('lang', lang);
                fd.append('csrf_token', document.querySelector('#theme-form input[name=csrf_token]').value);
                fetch('set_language.php', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            status.textContent = '✅ ' + <?php echo json_encode(t('settings_theme_saved_reload')); ?>;
                            setTimeout(function() { window.location.reload(); }, 800);
                        } else {
                            status.style.color = 'var(--vs-wcag-danger, #b91c1c)';
                            status.textContent = '❌ ' + <?php echo json_encode(t('settings_language_error')); ?>;
                        }
                    })
                    .catch(function() {
                        status.style.color = 'var(--vs-wcag-danger, #b91c1c)';
                        status.textContent = '❌ ' + <?php echo json_encode(t('error_network')); ?>;
                    });
            });
        })();
        </script>
    </div>
    </section>

    <section class="settings-section" aria-labelledby="sec-konto">
    <h3 class="settings-section-title" id="sec-konto"><i class="ti ti-user" aria-hidden="true"></i> <?php echo t('settings_section_account'); ?></h3>
    <div class="settings-section__grid">

    <!-- Profil: Avatar, Standard-Ersteller, Sitzung -->
    <div class="settings-card">
        <h4 class="card-title"><i class="ti ti-user-circle" aria-hidden="true"></i> <?php echo t('nav_profile'); ?></h4>

        <!-- Avatar-Upload (unsichtbares Input, per Klick auf Avatar aktiviert) -->
        <form method="POST" enctype="multipart/form-data" id="avatarForm">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="action" value="upload_avatar">
            <input type="file" name="avatar" id="avatarInput" aria-label="<?php echo htmlspecialchars(t('profile_avatar_change'), ENT_QUOTES, 'UTF-8'); ?>"
                   accept="image/jpeg,image/png,image/gif,image/webp,.heic,.heif"
                   style="display:none;"
                   onchange="document.getElementById('avatarForm').submit();">
        </form>

        <div class="profil-avatar-wrap"
             onclick="document.getElementById('avatarInput').click()"
             title="<?php echo htmlspecialchars(t('profile_avatar_change'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="profil-avatar">
                <?php if ($avatarUrl): ?>
                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>"
                         alt="<?php echo htmlspecialchars(sprintf(t('profile_avatar_alt'), $_SESSION['username']), ENT_QUOTES, 'UTF-8'); ?>">
                <?php else: ?>
                    <?php echo htmlspecialchars(mb_strtoupper(mb_substr($_SESSION['username'], 0, 1))); ?>
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
                        title="<?php echo htmlspecialchars(t('profile_avatar_remove'), ENT_QUOTES, 'UTF-8'); ?>">✕</button>
            </form>
            <?php endif; ?>
        </div>
        <p style="font-size:12px; color:var(--vs-text-muted); text-align:center; margin:0 0 18px;">
            <?php echo t('profile_avatar_hint'); ?>
        </p>

        <div class="settings-part">
        <h5 class="settings-subtitle"><?php echo t('settings_default_creator_title'); ?></h5>
        <p style="font-size:13px; color:var(--vs-text-muted); margin-bottom:14px;"><?php echo t('settings_default_creator_description'); ?></p>
        <form method="POST" action="">
            <?php echo Security::getCSRFInput(); ?>
            <div class="form-group">
                <label for="standard_ersteller"><?php echo t('field_name'); ?>:</label>
                <input type="text" id="standard_ersteller" name="standard_ersteller"
                       value="<?php echo esc($user['standard_ersteller'] ?? $_SESSION['username']); ?>"
                       required maxlength="100">
            </div>
            <button type="submit" class="btn btn-primary"><?php echo t('btn_save'); ?></button>
        </form>
        </div>
        <div class="settings-part">
        <h5 class="settings-subtitle"><?php echo t('settings_security_session'); ?></h5>
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
    </div>

    <!-- Anmeldung: Passwort + Zwei-Faktor -->
    <div class="settings-card">
        <h4 class="card-title"><i class="ti ti-lock" aria-hidden="true"></i> <?php echo t('settings_signin_title'); ?></h4>
        <!-- Passwort ändern -->
        <?php if (!$darfPasswort): ?>
        <p style="font-size:13px; color:var(--vs-text-muted); margin:0;">
            <i class="ti ti-lock" aria-hidden="true"></i> <?php echo t('profile_pw_disabled'); ?>
        </p>
        <?php else: ?>
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
                <label for="old_password"><?php echo t('profile_pw_current'); ?></label>
                <input type="password" name="old_password" id="old_password"
                       placeholder="••••••••" autocomplete="current-password" required>
            </div>
            <div class="form-group">
                <label for="new_password"><?php echo t('profile_pw_new'); ?> <small style="color:var(--vs-text-muted);font-weight:400;"><?php echo t('profile_pw_min'); ?></small></label>
                <input type="password" name="new_password" id="new_password"
                       placeholder="••••••••" autocomplete="new-password" required minlength="8">
            </div>
            <div class="form-group">
                <label for="new_password2"><?php echo t('profile_pw_confirm'); ?></label>
                <input type="password" name="new_password2" id="new_password2"
                       placeholder="••••••••" autocomplete="new-password" required minlength="8">
            </div>
            <button type="submit" class="btn btn-primary"><?php echo t('profile_pw_submit'); ?></button>
        </form>
        <?php endif; ?>

        <?php
        // Zwei-Faktor-Anmeldung (4.3.34: fuer alle Rollen, Einrichtung in 2fa_setup.php)
        $_2faRow = $db->selectOne("SELECT totp_enabled FROM users WHERE id = ?", [$_SESSION['user_id']]);
        $_2faAn  = !empty($_2faRow['totp_enabled']);
        ?>
        <div style="margin-top:20px; padding-top:16px; border-top:1px solid var(--vs-border);">
            <h5 style="font-size:14px; margin:0 0 6px;"><i class="ti ti-shield-check" aria-hidden="true"></i> <?php echo t('2fa_page_title'); ?></h5>
            <p style="font-size:13px; color:var(--vs-text-muted); margin:0 0 10px;">
                <?php echo $_2faAn ? t('2fa_status_active') : t('2fa_status_inactive'); ?>
            </p>
            <a href="2fa_setup.php" class="btn btn-secondary"><?php echo t('2fa_btn_manage'); ?></a>
        </div>
        <?php unset($_2faRow, $_2faAn); ?>
    </div>

    </div>
    </section>

    <section class="settings-section" aria-labelledby="sec-daten">
    <h3 class="settings-section-title" id="sec-daten"><i class="ti ti-database" aria-hidden="true"></i> <?php echo t('settings_section_data'); ?></h3>
    <div class="settings-section__grid">
    <!-- Statistiken -->
    <div class="settings-card">
        <h4 class="card-title"><i class="ti ti-chart-bar"></i> <?php echo t('nav_stats'); ?></h4>
        <?php if ($stats): ?>
        <div class="stat-mini-grid">
            <div class="stat-mini">
                <div class="stat-mini-value"><?php echo (int)$stats['wertsachen']; ?></div>
                <div class="stat-mini-label"><?php echo t('stats_items'); ?></div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value"><?php echo (int)$stats['raeume']; ?></div>
                <?php /* bis 4.3.30 "Orte" (stats_locations) - gezaehlt werden Raeume */ ?>
                <div class="stat-mini-label"><?php echo t('loc_tab_rooms'); ?></div>
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
            <p style="color:var(--vs-text-muted);"><?php echo t('error_loading'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Backup -->
    <div class="settings-card">
        <h4 class="card-title"><i class="ti ti-device-floppy"></i> Backup</h4>
        <p style="font-size:13px; color:var(--vs-text-muted); margin-bottom:16px;"><?php echo t('settings_backup_description'); ?></p>
        <a href="backend/backup.php" class="vs-btn vs-btn-secondary"><?php echo t('settings_backup_link'); ?></a>
    </div>

    <!-- DSGVO: Datenschutz & Meine Daten -->
    <div class="settings-card settings-card--wide">
        <h4 class="card-title"><i class="ti ti-shield-lock"></i> <?php echo t('settings_privacy_title'); ?></h4>
        <p style="font-size:13px; color:var(--vs-text-muted); margin-bottom:20px;">
            <?php echo t('settings_privacy_desc'); ?>
        </p>

        <div class="privacy-split">
        <!-- Datenexport -->
        <div class="privacy-part">
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

        <div class="privacy-part">
        <!-- Account-Löschung -->
        <?php if (!$darfKontoLoeschen): ?>
        <div>
            <strong style="font-size:14px;"><i class="ti ti-trash"></i> <?php echo t('settings_delete_account_title'); ?></strong>
            <p style="font-size:13px; color:var(--vs-text-muted); margin:6px 0 0;">
                <i class="ti ti-lock" aria-hidden="true"></i> <?php echo t('settings_delete_disabled'); ?>
            </p>
        </div>
        <?php else: ?>
        <div>
            <strong style="font-size:14px; color:var(--vs-wcag-danger);"><i class="ti ti-trash"></i> <?php echo t('settings_delete_account_title'); ?></strong>
            <p style="font-size:13px; color:var(--vs-text-muted); margin:6px 0 12px;">
                <?php echo t('settings_delete_account_desc'); ?>
            </p>
            <button type="button" class="btn btn-danger" style="font-size:14px;"
                    onclick="document.getElementById('deleteAccountModal').style.display='flex'">
                <i class="ti ti-trash"></i> <?php echo t('settings_delete_account_btn'); ?>
            </button>
        </div>
        <?php endif; ?>
        </div>
        </div>
    </div>
    </div>
    </section>

    <!-- Modal: Account löschen (nur mit Recht - sonst stand es bis 4.3.33
         ohne Ausloeser im HTML) -->
    <?php if ($darfKontoLoeschen): ?>
    <div id="deleteAccountModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:20px;">
        <div style="background:var(--vs-surface); border-radius:16px; padding:32px; max-width:460px; width:100%; box-shadow:0 16px 48px rgba(0,0,0,0.2);">
            <h3 style="margin:0 0 12px; color:var(--vs-wcag-danger);"><i class="ti ti-alert-triangle"></i> <?php echo t('settings_delete_confirm_title'); ?></h3>
            <p style="font-size:14px; color:var(--vs-text-muted); margin-bottom:20px;">
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
    <?php endif; ?>

    <?php if (isAdmin() && !isset($_SESSION['original_role'])): ?>
    <section class="settings-section" aria-labelledby="sec-admin">
    <h3 class="settings-section-title" id="sec-admin"><i class="ti ti-tool" aria-hidden="true"></i> <?php echo t('settings_section_admin'); ?></h3>
    <div class="settings-card">
        <h4 class="card-title"><i class="ti ti-tool"></i> <?php echo t('settings_role_switch'); ?></h4>
        <p style="font-size:13px; color:var(--vs-text-muted); margin-bottom:14px;"><?php echo t('settings_role_switch_desc'); ?></p>
        <div class="role-buttons role-buttons--row">
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
    </section>
    <?php endif; ?>

</div>

<div class="form-actions">
    <a href="index.php" class="btn"><?php echo t('btn_back_to_overview') ?: '← Back to overview'; ?></a>
</div>

<?php include 'footer_next_page.php'; ?>
