<?php
// profil.php — Persönliches Profil (alle Rollen)
define('PAGE_TITLE', 'Mein Profil');
require_once 'db.php';
if (!function_exists('sanitizeTheme')) require_once 'helpers.php';
requireLogin();
validateRequest();

$userId   = $_SESSION['user_id'];
$username = $_SESSION['username'];
$feedback = [];

// ── Avatar-Upload-Funktion ─────────────────────────────────────────────────
function handleAvatarUpload(array $file, ?string $oldAvatar): array {
    $avatarDir = UPLOAD_DIR . 'avatars/';
    if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);

    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
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

    // Bild laden
    $src = match($mimeType) {
        'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
        'image/png'  => @imagecreatefrompng($file['tmp_name']),
        'image/gif'  => @imagecreatefromgif($file['tmp_name']),
        'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        default      => false,
    };
    if (!$src) return ['error' => 'Bild konnte nicht verarbeitet werden'];

    // EXIF-Rotation korrigieren
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

    // Alten Avatar löschen
    if ($oldAvatar && file_exists($avatarDir . $oldAvatar)) {
        unlink($avatarDir . $oldAvatar);
    }

    return ['filename' => $filename];
}

// ── POST-Handling ──────────────────────────────────────────────────────────
$action = $_POST['action'] ?? '';

// Avatar hochladen
if ($action === 'upload_avatar') {
    $currentUser = $db->selectOne("SELECT avatar FROM users WHERE id = ?", [$userId]);
    $result = handleAvatarUpload($_FILES['avatar'] ?? [], $currentUser['avatar'] ?? null);
    if (isset($result['error'])) {
        $feedback = ['type' => 'error', 'msg' => '❌ ' . $result['error']];
    } else {
        try {
            $db->execute("UPDATE users SET avatar = ? WHERE id = ?", [$result['filename'], $userId]);
            $feedback = ['type' => 'success', 'msg' => '✅ Profilbild gespeichert.'];
        } catch (PDOException $e) {
            error_log('profil avatar: ' . $e->getMessage());
            $feedback = ['type' => 'error', 'msg' => '❌ Profilbild konnte nicht gespeichert werden.'];
        }
    }
}

// Avatar löschen
if ($action === 'delete_avatar') {
    $currentUser = $db->selectOne("SELECT avatar FROM users WHERE id = ?", [$userId]);
    $old = $currentUser['avatar'] ?? null;
    if ($old && file_exists(UPLOAD_DIR . 'avatars/' . $old)) {
        unlink(UPLOAD_DIR . 'avatars/' . $old);
    }
    try {
        $db->execute("UPDATE users SET avatar = NULL WHERE id = ?", [$userId]);
        $feedback = ['type' => 'success', 'msg' => '✅ Profilbild entfernt.'];
    } catch (PDOException $e) {
        error_log('profil avatar delete: ' . $e->getMessage());
        $feedback = ['type' => 'error', 'msg' => '❌ Profilbild konnte nicht entfernt werden.'];
    }
}

// Passwort ändern
if ($action === 'change_password') {
    $oldPw  = $_POST['old_password']  ?? '';
    $newPw  = $_POST['new_password']  ?? '';
    $newPw2 = $_POST['new_password2'] ?? '';
    $user = $db->selectOne("SELECT password FROM users WHERE id = ?", [$userId]);
    if (!$user || !password_verify($oldPw, $user['password'])) {
        $feedback = ['type' => 'error', 'msg' => '❌ Aktuelles Passwort ist falsch.'];
    } elseif (strlen($newPw) < 8) {
        $feedback = ['type' => 'error', 'msg' => '❌ Neues Passwort muss mindestens 8 Zeichen haben.'];
    } elseif ($newPw !== $newPw2) {
        $feedback = ['type' => 'error', 'msg' => '❌ Die neuen Passwörter stimmen nicht überein.'];
    } else {
        // Reihenfolge ist hier wesentlich: erst schreiben, dann protokollieren,
        // dann melden. Bis 4.3.17 lief alles drei bedingungslos - scheiterte das
        // UPDATE, stand "Passwort erfolgreich geaendert" auf dem Bildschirm und
        // password_changed im Sicherheitsprotokoll, waehrend das alte Passwort
        // weiter galt.
        try {
            $hash = password_hash($newPw, PASSWORD_ARGON2ID);
            $db->execute("UPDATE users SET password = ? WHERE id = ?", [$hash, $userId]);
            Security::logSecurityEvent('password_changed', ['user_id' => $userId, 'username' => $username]);
            $feedback = ['type' => 'success', 'msg' => '✅ Passwort erfolgreich geändert.'];
        } catch (PDOException $e) {
            error_log('profil password: ' . $e->getMessage());
            $feedback = ['type' => 'error', 'msg' => '❌ Das Passwort konnte nicht gespeichert werden. Das bisherige gilt weiter.'];
        }
    }
}

// Theme speichern
if ($action === 'change_theme') {
    $theme = sanitizeTheme($_POST['theme'] ?? 'cloud');
    try {
        $db->execute("UPDATE users SET theme = ? WHERE id = ?", [$theme, $userId]);
        $_SESSION['theme'] = $theme;
        $feedback = ['type' => 'success', 'msg' => '✅ Theme gespeichert.'];
    } catch (PDOException $e) {
        error_log('profil theme: ' . $e->getMessage());
        $feedback = ['type' => 'error', 'msg' => '❌ Theme konnte nicht gespeichert werden.'];
    }
}

// Sprache speichern
if ($action === 'change_lang') {
    $allowed = ['de','en','fr','tr','es','it','nl','pl','pt'];
    $lang    = $_POST['lang'] ?? 'de';
    if (in_array($lang, $allowed)) $_SESSION['lang'] = $lang;
    $feedback = ['type' => 'success', 'msg' => '✅ Sprache gespeichert.'];
}

// ── User-Daten laden ──────────────────────────────────────────────────────
$user = $db->selectOne(
    "SELECT * FROM users WHERE id = ?",
    [$userId]
);
$avatarFile = $user['avatar'] ?? null;
$avatarUrl  = $avatarFile ? (defined('ASSET_BASE') ? ASSET_BASE : '') . 'upload/avatars/' . rawurlencode($avatarFile) : null;

$stats = $db->selectOne(
    "SELECT COUNT(*) as total, SUM(preis) as total_wert
     FROM wertsachen WHERE erstellt_von = ? AND (hidden=0 OR hidden IS NULL)",
    [$username]
);

include 'header_next_page.php';
?>

<style>
.profil-grid {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 24px;
    max-width: 860px;
    margin: 24px auto;
}
@media (max-width: 700px) {
    .profil-grid { grid-template-columns: 1fr; margin: 12px; gap: 16px; }
}
.profil-card {
    background: var(--card-bg, #fff);
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    padding: 24px;
}
/* ── Avatar ── */
.profil-avatar-wrap {
    position: relative;
    width: 90px; height: 90px;
    margin: 0 auto 16px;
    cursor: pointer;
}
.profil-avatar-wrap:hover .profil-avatar-overlay { opacity: 1; }
.profil-avatar {
    width: 90px; height: 90px;
    border-radius: 50%;
    background: var(--primary-color, #3498db);
    color: #fff;
    font-size: 38px;
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
    font-size: 22px;
}
.profil-avatar-delete {
    position: absolute;
    top: -3px; right: -3px;
    width: 22px; height: 22px;
    border-radius: 50%;
    background: #e74c3c;
    color: #fff;
    border: 2px solid #fff;
    font-size: 12px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    line-height: 1;
    transition: background 0.15s;
}
.profil-avatar-delete:hover { background: #c0392b; }
/* ── Rest ── */
.profil-name { text-align:center; font-size:18px; font-weight:700; color:var(--text-color,#333); margin-bottom:6px; }
.profil-stat-row { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid var(--border-color,#eee); font-size:14px; color:var(--text-color,#555); }
.profil-stat-row:last-child { border-bottom:none; }
.profil-stat-val { font-weight:600; color:var(--primary-color,#3498db); }
.profil-section-title { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#999; margin-bottom:16px; }
.profil-feedback { padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px; font-weight:500; }
.profil-feedback.success { background:#d4edda; color:#155724; border-left:4px solid #28a745; }
.profil-feedback.error   { background:#f8d7da; color:#721c24; border-left:4px solid #dc3545; }
.profil-form-group { margin-bottom:14px; }
.profil-form-group label { display:block; font-size:13px; font-weight:600; color:var(--text-color,#444); margin-bottom:5px; }
.profil-form-group input,
.profil-form-group select { width:100%; padding:10px 12px; border:1px solid var(--border-color,#ddd); border-radius:7px; font-size:14px; background:var(--input-bg,#fff); color:var(--text-color,#333); transition:border-color 0.2s; }
.profil-form-group input:focus,
.profil-form-group select:focus { outline:none; border-color:var(--primary-color,#3498db); box-shadow:0 0 0 3px rgba(52,152,219,0.12); }
.profil-btn { width:100%; padding:10px; background:var(--primary-color,#3498db); color:#fff; border:none; border-radius:7px; font-size:14px; font-weight:600; cursor:pointer; transition:opacity 0.2s; margin-top:4px; }
.profil-btn:hover { opacity:0.88; }
.theme-preview-row { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
.theme-dot { width:28px; height:28px; border-radius:50%; cursor:pointer; border:3px solid transparent; transition:transform 0.15s, border-color 0.15s; }
.theme-dot:hover { transform:scale(1.18); }
.theme-dot.active { border-color:var(--text-color,#333); transform:scale(1.15); }
</style>

<div class="profil-grid">

    <!-- LINKE SPALTE: Steckbrief -->
    <div>
        <div class="profil-card" style="text-align:center;">

            <!-- Avatar-Upload (unsichtbares Input, per Klick auf Avatar aktiviert) -->
            <form method="POST" enctype="multipart/form-data" id="avatarForm">
                <?php echo Security::getCSRFInput(); ?>
                <input type="hidden" name="action" value="upload_avatar">
                <input type="file" name="avatar" id="avatarInput"
                       accept="image/jpeg,image/png,image/gif,image/webp,.heic,.heif"
                       style="display:none;"
                       onchange="document.getElementById('avatarForm').submit();">
            </form>

            <!-- Avatar-Kreis -->
            <div class="profil-avatar-wrap"
                 onclick="document.getElementById('avatarInput').click()"
                 title="Profilbild ändern">
                <div class="profil-avatar">
                    <?php if ($avatarUrl): ?>
                        <img src="<?php echo htmlspecialchars($avatarUrl); ?>"
                             alt="Profilbild von <?php echo htmlspecialchars($username); ?>">
                    <?php else: ?>
                        <?php echo mb_strtoupper(mb_substr($username, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="profil-avatar-overlay">📷</div>

                <?php if ($avatarUrl): ?>
                <!-- Löschen-X oben rechts -->
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

            <div class="profil-name"><?php echo htmlspecialchars($username); ?></div>
            <div style="text-align:center; margin-bottom:16px;">
                <?php echo getRoleBadge($_SESSION['role'] ?? 'read'); ?>
            </div>
            <div>
                <div class="profil-stat-row">
                    <span>📅 Dabei seit</span>
                    <span class="profil-stat-val">
                        <?php $ca = $user['created_at'] ?? $user['erstellt_am'] ?? null; echo $ca ? date('d.m.Y', strtotime($ca)) : '–'; ?>
                    </span>
                </div>
                <div class="profil-stat-row">
                    <span>🕐 Letzter Login</span>
                    <span class="profil-stat-val">
                        <?php echo $user['letzter_login'] ? date('d.m.Y H:i', strtotime($user['letzter_login'])) : '–'; ?>
                    </span>
                </div>
                <div class="profil-stat-row">
                    <span>📦 Meine Gegenstände</span>
                    <span class="profil-stat-val"><?php echo (int)($stats['total'] ?? 0); ?></span>
                </div>
                <?php if (($stats['total_wert'] ?? 0) > 0): ?>
                <div class="profil-stat-row">
                    <span>💶 Gesamtwert</span>
                    <span class="profil-stat-val">
                        <?php echo number_format((float)$stats['total_wert'], 2, ',', '.'); ?> €
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <p style="font-size:12px; color:#aaa; margin-top:12px; margin-bottom:0;">
                Klick auf den Avatar zum Ändern
            </p>
        </div>
    </div>

    <!-- RECHTE SPALTE: Einstellungen -->
    <div style="display:flex; flex-direction:column; gap:20px;">

        <?php if (!empty($feedback)): ?>
        <div class="profil-feedback <?php echo $feedback['type']; ?>">
            <?php echo htmlspecialchars($feedback['msg']); ?>
        </div>
        <?php endif; ?>

        <!-- Theme -->
        <div class="profil-card">
            <div class="profil-section-title">🎨 Erscheinungsbild</div>
            <?php
            $themes = [
                'cloud'    => ['label' => 'Cloud',    'color' => '#8B8378'],
                'ocean'    => ['label' => 'Ocean',    'color' => '#00acc1'],
                'midnight' => ['label' => 'Midnight', 'color' => '#a78bfa'],
                'mint'     => ['label' => 'Mint',     'color' => '#059669'],
                'glass'    => ['label' => 'Glass',    'color' => '#7c3aed'],
                'navy'     => ['label' => 'Navy',     'color' => '#185fa5'],
                'emerald'  => ['label' => 'Emerald',  'color' => '#047857'],
            ];
            $currentTheme = $_SESSION['theme'] ?? 'cloud';
            ?>
            <div class="theme-preview-row">
                <?php foreach ($themes as $key => $t): ?>
                <div class="theme-dot <?php echo $currentTheme === $key ? 'active' : ''; ?>"
                     style="background:<?php echo $t['color']; ?>;"
                     title="<?php echo $t['label']; ?>"
                     onclick="selectTheme('<?php echo $key; ?>', this)"
                     role="button"
                     aria-label="Theme <?php echo $t['label']; ?>"></div>
                <?php endforeach; ?>
            </div>
            <form method="POST">
                <?php echo Security::getCSRFInput(); ?>
                <input type="hidden" name="action" value="change_theme">
                <input type="hidden" name="theme" id="themeInput" value="<?php echo htmlspecialchars($currentTheme); ?>">
                <button type="submit" class="profil-btn">💾 Theme speichern</button>
            </form>
        </div>

        <!-- Sprache -->
        <div class="profil-card">
            <div class="profil-section-title">🌐 Sprache</div>
            <form method="POST">
                <?php echo Security::getCSRFInput(); ?>
                <input type="hidden" name="action" value="change_lang">
                <div class="profil-form-group">
                    <label for="lang_select">Anzeigesprache</label>
                    <select name="lang" id="lang_select">
                        <?php
                        $langs = ['de'=>'🇩🇪 Deutsch','en'=>'🇬🇧 English','fr'=>'🇫🇷 Français',
                                  'tr'=>'🇹🇷 Türkçe','es'=>'🇪🇸 Español','it'=>'🇮🇹 Italiano',
                                  'nl'=>'🇳🇱 Nederlands','pl'=>'🇵🇱 Polski','pt'=>'🇵🇹 Português'];
                        foreach ($langs as $code => $label):
                        ?>
                        <option value="<?php echo $code; ?>"
                                <?php echo ($_SESSION['lang'] ?? 'de') === $code ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="profil-btn">💾 Sprache speichern</button>
            </form>
        </div>

        <!-- Passwort -->
        <div class="profil-card">
            <div class="profil-section-title">🔒 Passwort ändern</div>
            <form method="POST" autocomplete="off">
                <?php echo Security::getCSRFInput(); ?>
                <input type="hidden" name="action" value="change_password">
                <div class="profil-form-group">
                    <label for="old_password">Aktuelles Passwort</label>
                    <input type="password" name="old_password" id="old_password"
                           placeholder="••••••••" autocomplete="current-password" required>
                </div>
                <div class="profil-form-group">
                    <label for="new_password">Neues Passwort
                        <small style="color:#999;font-weight:400;">(min. 8 Zeichen)</small>
                    </label>
                    <input type="password" name="new_password" id="new_password"
                           placeholder="••••••••" autocomplete="new-password" required minlength="8">
                </div>
                <div class="profil-form-group">
                    <label for="new_password2">Neues Passwort bestätigen</label>
                    <input type="password" name="new_password2" id="new_password2"
                           placeholder="••••••••" autocomplete="new-password" required minlength="8">
                </div>
                <button type="submit" class="profil-btn">🔑 Passwort ändern</button>
            </form>
        </div>

    </div>
</div>

<script>
function selectTheme(key, el) {
    document.getElementById('themeInput').value = key;
    document.querySelectorAll('.theme-dot').forEach(d => d.classList.remove('active'));
    el.classList.add('active');
}
</script>

<?php include 'footer_next.php'; ?>
