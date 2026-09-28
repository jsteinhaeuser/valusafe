<?php
/**
 * header_next.php — Frontend-Header für das ValuSafe Next-Interface
 * Nachfolger des alten header.php
 */

// Hilfsfunktion: t() mit zuverlässigem Fallback
// t() gibt den Key zurück wenn nicht gefunden (truthy) → ?: funktioniert nicht
if (!function_exists('tn')) {
    function tn(string $key, string $fallback): string {
        $v = function_exists('t') ? t($key) : $key;
        return ($v === null || $v === false || $v === '' || $v === $key) ? $fallback : $v;
    }
}

// Theme für Legacy-Komponenten ermitteln
// Theme immer frisch aus DB lesen — Session kann stale sein
$_allowedThemes = ['navy','cloud','sand','forest','ocean','midnight','mint','lavender','emerald','glass','cherry','sunset','dark'];
$_themeFile = 'css/navy.css'; // Fallback

if (isset($db) && isset($_SESSION['username'])) {
    try {
        $_themeRow = $db->selectOne("SELECT theme FROM users WHERE username = ?", [$_SESSION['username']]);
        if (!empty($_themeRow['theme']) && in_array($_themeRow['theme'], $_allowedThemes)) {
            $_themeFile = 'css/' . $_themeRow['theme'] . '.css';
            $_SESSION['theme'] = $_themeRow['theme'];
            $_SESSION['user_theme'] = $_themeRow['theme'];
        }
    } catch (Exception $_e) {}
} elseif (!empty($_SESSION['user_theme']) && in_array($_SESSION['user_theme'], $_allowedThemes)) {
    $_themeFile = 'css/' . $_SESSION['user_theme'] . '.css';
} elseif (!empty($_SESSION['theme']) && in_array($_SESSION['theme'], $_allowedThemes)) {
    $_themeFile = 'css/' . $_SESSION['theme'] . '.css';
}
unset($_allowedThemes, $_themeRow, $_e);

// App-Name
// helpers.php laden falls noch nicht geschehen
if (!function_exists('esc') && file_exists(__DIR__ . '/helpers.php')) {
    require_once __DIR__ . '/helpers.php';
}
if (!function_exists('esc')) {
    function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$_appName = defined('APP_NAME') ? APP_NAME : 'ValuSafe';
$_pageTitle = defined('PAGE_TITLE') ? PAGE_TITLE : $_appName;

// Username + Initialen für Rail-Avatar
$_username   = $_SESSION['username'] ?? '';
$_initial    = strtoupper(substr($_username, 0, 1)) ?: 'U';

// Profilbild für Topstrip-Avatar (aus DB, mit Session-Cache wie beim Theme)
$_avatarUrl = null;
if (isset($db) && isset($_SESSION['username'])) {
    try {
        $_avatarRow = $db->selectOne("SELECT avatar FROM users WHERE username = ?", [$_username]);
        $_SESSION['avatar'] = $_avatarRow['avatar'] ?? null;
    } catch (Exception $_e) {}
    unset($_avatarRow, $_e);
}
if (!empty($_SESSION['avatar'])) {
    $_avatarUrl = (defined('ASSET_BASE') ? ASSET_BASE : '') . 'upload/avatars/' . rawurlencode($_SESSION['avatar']);
}

// Theme laden — $_SESSION['theme'] setzen falls noch nicht vorhanden
if (empty($_SESSION['theme']) && isset($db)) {
    try {
        $_themeRow = $db->selectOne("SELECT theme FROM users WHERE username = ?", [$_username]);
        $_SESSION['theme'] = (!empty($_themeRow['theme'])) ? $_themeRow['theme'] : 'navy';
    } catch (Exception $e) {
        $_SESSION['theme'] = 'navy';
    }
}
if (empty($_SESSION['theme'])) { $_SESSION['theme'] = 'navy'; }

// Gesamtwert für Topstrip (aus globalen Variablen falls verfügbar)
$_totalItems = $total_items ?? 0;
$_totalValue = isset($total_value) && $total_value > 0
    ? (function_exists('formatPriceLocalized') ? formatPriceLocalized($total_value) : number_format($total_value, 0, ',', '.') . ' €')
    : null;

// Suchbegriff für Topstrip-Suchfeld
$_searchVal = htmlspecialchars($currentFilters['search'] ?? $_GET['search'] ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="<?php echo defined('APP_LANG') ? APP_LANG : 'de'; ?>">
<head>
    <meta charset="UTF-8">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#185fa5">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($_pageTitle); ?></title>

    <!-- Tabler Icons (lokal gehostet) -->
    <link rel="stylesheet" href="css/tabler-icons.min.css">

    <!-- Token-Basis (immer vor next.css laden) -->
    <link rel="stylesheet" href="css/tokens.css">

    <!-- Next Interface CSS -->
    <link rel="stylesheet" href="css/next.css">

    <!-- Theme CSS danach (überschreibt --vs-* Variablen) -->
    <link rel="stylesheet" href="<?php echo $_themeFile; ?>">

    <!-- WCAG globale Fixes -->
    <link rel="stylesheet" href="css/wcag_global.css">

    <link rel="icon" type="image/png" href="favicon.png">
</head>
<body class="vs-next">

<!-- ── Icon Rail ──────────────────────────────────────────── -->
<aside class="vs-rail" aria-label="Hauptnavigation">
    <div class="vs-rail-logo" aria-hidden="true">
        <i class="ti ti-shield-check"></i>
    </div>

    <nav class="vs-rail-nav" aria-label="Hauptmenü">
        <a class="vs-rail-btn vs-rail-on" href="index.php"
           title="Übersicht" aria-label="Übersicht">
            <i class="ti ti-layout-cards" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn" href="dashboard.php"
           title="Dashboard" aria-label="Dashboard">
            <i class="ti ti-chart-pie" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn" href="insurance.php"
           title="Versicherung" aria-label="Versicherung">
            <i class="ti ti-shield" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn" href="backend/stats.php"
           title="Statistiken" aria-label="Statistiken">
            <i class="ti ti-chart-bar" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn" href="backend/gallery.php"
           title="Galerie" aria-label="Galerie">
            <i class="ti ti-photo" aria-hidden="true"></i>
        </a>
    </nav>

    <div class="vs-rail-spacer"></div>

    <div class="vs-rail-foot">
        <?php if (function_exists('isSuperAdmin') && isSuperAdmin()): ?>
        <a class="vs-rail-btn" href="backend/superadmin.php"
           title="SuperAdmin" aria-label="SuperAdmin">
            <i class="ti ti-crown" aria-hidden="true"></i>
        </a>
        <?php endif; ?>
        <?php if ((function_exists('isSuperAdmin') && isSuperAdmin()) || ($_SESSION['role'] ?? '') === 'admin'): ?>
        <a class="vs-rail-btn" href="backend/index.php"
           title="<?php echo tn('nav_admin', 'Admin'); ?>" aria-label="Admin">
            <i class="ti ti-settings-2" aria-hidden="true"></i>
        </a>
        <?php endif; ?>
        <a class="vs-rail-btn" href="#" onclick="openServiceModal();return false;"
           title="<?php echo htmlspecialchars(tn('nav_help', 'Hilfe & Informationen'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Hilfe">
            <i class="ti ti-info-circle" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn" href="settings.php"
           title="Einstellungen" aria-label="Einstellungen">
            <i class="ti ti-settings" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn" href="logout.php"
           title="Abmelden (<?php echo htmlspecialchars($_username); ?>)"
           aria-label="Abmelden">
            <i class="ti ti-logout" aria-hidden="true"></i>
        </a>
    </div>
</aside>

<!-- ── Haupt-Bereich ──────────────────────────────────────── -->
<div class="vs-main">

    <!-- Top Strip -->
    <div class="vs-topstrip">
        <span class="vs-context"><?php echo htmlspecialchars($_appName); ?></span>

        <!-- Schnellsuche -->
        <form method="GET" action="index.php" class="vs-search" role="search" aria-label="Schnellsuche">
            <i class="ti ti-search" aria-hidden="true"></i>
            <input type="text" name="search"
                   value="<?php echo $_searchVal; ?>"
                   placeholder="<?php echo tn('search_placeholder', 'Suchen…'); ?>"
                   aria-label="Gegenstände suchen">
        </form>

        <a href="settings.php" class="vs-topstrip-avatar" title="<?php echo tn('nav_profile', 'Profil'); ?> (<?php echo htmlspecialchars($_username); ?>)" aria-label="Profil">
            <?php if ($_avatarUrl): ?>
                <img src="<?php echo htmlspecialchars($_avatarUrl); ?>" alt="">
            <?php else: ?>
                <span><?php echo htmlspecialchars($_initial); ?></span>
            <?php endif; ?>
        </a>

        <div class="vs-strip-right">
            <?php if ($_totalItems > 0): ?>
            <span class="vs-stat-pill">
                <b><?php echo $_totalItems; ?></b>&nbsp;<?php echo tn('nav_items', 'Objekte'); ?>
            </span>
            <?php endif; ?>
            <?php if ($_totalValue): ?>
            <span class="vs-stat-pill">
                <b><?php echo $_totalValue; ?></b>
            </span>
            <?php endif; ?>

            <!-- Export-Dropdown -->
            <div style="position:relative; display:inline-block;">
                <button id="vs-export-btn" onclick="vsToggleExport(event)" aria-haspopup="true"
                        style="display:flex;align-items:center;gap:6px;padding:7px 13px;font-size:13px;font-weight:600;
                               background:var(--vs-surface);color:var(--vs-text);border:1px solid var(--vs-border);
                               border-radius:6px;cursor:pointer;transition:border-color .15s;">
                    <i class="ti ti-package" style="font-size:16px;"></i>
                    <?php echo tn('nav_export', 'Export'); ?> <i class="ti ti-chevron-down" style="font-size:13px;margin-left:2px;"></i>
                </button>
            </div>

            <?php if (function_exists('canEdit') && canEdit()): ?>
            <a href="add.php" class="vs-add-btn" aria-label="Neuen Gegenstand hinzufügen">
                <i class="ti ti-plus" aria-hidden="true"></i>
                <?php echo tn('nav_add', 'Hinzufügen'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div><!-- /.vs-topstrip -->

    <!-- Workspace -->
    <div class="vs-workspace">

        <!-- Gallery (scrollbarer Inhaltsbereich) -->
        <div class="vs-gallery" id="vsGallery">
