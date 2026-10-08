<?php
/**
 * Next Interface Header für Backend-Seiten (stats.php, gallery.php etc.)
 * Pfade relativ zu /backend/ — CSS: ../css/, Links: ../
 * CSS-Klassen identisch mit root/header_next_page.php
 */

// tn()-Helper (falls noch nicht definiert)
if (!function_exists('tn')) {
    function tn(string $key, string $fallback): string {
        $v = function_exists('t') ? t($key) : $key;
        return ($v === null || $v === false || $v === '' || $v === $key) ? $fallback : $v;
    }
}

// helpers.php laden — __DIR__ = backend/layout/, zwei Ebenen hoch = Root
if (!function_exists('esc') && file_exists(dirname(__DIR__, 2) . '/helpers.php')) {
    require_once dirname(__DIR__, 2) . '/helpers.php';
}
if (!function_exists('esc')) {
    function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// Theme ermitteln
$_themeFile = '../css/navy.css';
foreach (['user_theme', 'theme'] as $_tk) {
    if (!empty($_SESSION[$_tk])) {
        $_allowedThemes = ['navy','cloud','sand','forest','ocean','midnight','mint','lavender','emerald','glass','cherry','sunset','dark'];
        if (in_array($_SESSION[$_tk], $_allowedThemes)) {
            $_themeFile = "../css/{$_SESSION[$_tk]}.css";
        }
        break;
    }
}
unset($_tk, $_allowedThemes);

// Theme in Session laden falls nötig
$_username = $_SESSION['username'] ?? '';
if (empty($_SESSION['theme']) && isset($db)) {
    try {
        $_themeRow = $db->selectOne("SELECT theme FROM users WHERE username = ?", [$_username]);
        $_SESSION['theme'] = (!empty($_themeRow['theme'])) ? $_themeRow['theme'] : 'navy';
        $_themeFile = "../css/{$_SESSION['theme']}.css";
    } catch (Exception $e) {
        $_SESSION['theme'] = 'navy';
    }
}
if (empty($_SESSION['theme'])) {
    $_SESSION['theme'] = 'navy';
}

$_appName   = defined('APP_NAME') ? APP_NAME : 'ValuSafe';
$_pageTitle = isset($pageTitle) ? $pageTitle : $_appName;
$_currentPage = basename($_SERVER['PHP_SELF']);
?>
<?php
// Update-Check (Fragment — zeigt Notice falls neue Version verfügbar)
if (file_exists(__DIR__ . '/../update_check.php')) {
    include __DIR__ . '/../update_check.php';
}
?>
<!DOCTYPE html>
<html lang="<?php echo defined('APP_LANG') ? APP_LANG : 'de'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($_pageTitle); ?> — <?php echo htmlspecialchars($_appName); ?></title>
    <link rel="stylesheet" href="../css/tabler-icons.min.css">
    <link rel="stylesheet" href="../css/tokens.css">
    <link rel="stylesheet" href="../css/next.css">
    <link rel="stylesheet" href="<?php echo $_themeFile; ?>">
    <link rel="stylesheet" href="../css/wcag_global.css">
    <link rel="stylesheet" href="../css/backend.css">
    <link rel="icon" type="image/png" href="../favicon.png">
    
</head>
<body class="vs-next">

<!-- Icon Rail -->
<aside class="vs-rail" aria-label="<?php echo tn('nav_aria_main', 'Hauptnavigation'); ?>">
    <a href="../index.php" class="vs-rail-logo" aria-label="<?php echo tn('page_overview', 'Übersicht'); ?>" style="text-decoration:none">
        <i class="ti ti-shield-check" aria-hidden="true"></i>
    </a>
    <nav class="vs-rail-nav" aria-label="<?php echo tn('nav_aria_menu', 'Hauptmenü'); ?>">
        <a class="vs-rail-btn <?php echo $_currentPage === 'index.php' ? 'vs-rail-on' : ''; ?>"
           href="../index.php" title="<?php echo tn('page_overview', 'Übersicht'); ?>">
            <i class="ti ti-layout-cards" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn <?php echo in_array($_currentPage, ['dashboard.php']) ? 'vs-rail-on' : ''; ?>"
           href="../dashboard.php" title="<?php echo tn('nav_dashboard', 'Dashboard'); ?>">
            <i class="ti ti-chart-pie" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn <?php echo $_currentPage === 'insurance.php' ? 'vs-rail-on' : ''; ?>"
           href="../insurance.php" title="<?php echo tn('nav_insurance', 'Versicherungen'); ?>">
            <i class="ti ti-shield" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn <?php echo $_currentPage === 'stats.php' ? 'vs-rail-on' : ''; ?>"
           href="stats.php" title="<?php echo tn('nav_stats', 'Statistiken'); ?>">
            <i class="ti ti-chart-bar" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn <?php echo $_currentPage === 'gallery.php' ? 'vs-rail-on' : ''; ?>"
           href="gallery.php" title="<?php echo tn('nav_gallery', 'Galerie'); ?>">
            <i class="ti ti-photo" aria-hidden="true"></i>
        </a>
    </nav>
    <div class="vs-rail-spacer"></div>
    <div class="vs-rail-foot">
        <?php if (function_exists('isSuperAdmin') && isSuperAdmin()): ?>
        <a class="vs-rail-btn <?php echo $_currentPage === 'superadmin.php' ? 'vs-rail-on' : ''; ?>"
           href="superadmin.php" title="SuperAdmin">
            <i class="ti ti-crown" aria-hidden="true"></i>
        </a>
        <?php endif; ?>
        <a class="vs-rail-btn <?php echo in_array($_currentPage, ['index.php','users.php','categories.php','locations.php','backup.php','restore.php','logs.php','system.php','permissions.php']) ? 'vs-rail-on' : ''; ?>"
           href="index.php" title="<?php echo tn('nav_admin', 'Admin'); ?>">
            <i class="ti ti-settings-2" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn" href="#" onclick="openServiceModal();return false;"
           title="<?php echo htmlspecialchars(tn('nav_help', 'Hilfe & Informationen'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(tn('nav_help', 'Hilfe & Informationen'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="ti ti-info-circle" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn <?php echo $_currentPage === 'settings.php' ? 'vs-rail-on' : ''; ?>"
           href="../settings.php" title="<?php echo tn('nav_settings', 'Einstellungen'); ?>">
            <i class="ti ti-settings" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn" href="../logout.php" title="<?php echo tn('nav_logout', 'Abmelden'); ?> (<?php echo htmlspecialchars($_username); ?>)">
            <i class="ti ti-logout" aria-hidden="true"></i>
        </a>
    </div>
</aside>

<!-- Haupt-Bereich -->
<div class="vs-main">
    <!-- Top Strip -->
    <div class="vs-topstrip">
        <a href="../index.php" class="vs-topstrip-back" style="color:var(--vs-muted,#64748b);text-decoration:none;display:flex;align-items:center;gap:6px;font-size:13px;">
            <i class="ti ti-arrow-left" aria-hidden="true"></i>
        </a>
        <span class="vs-context"><?php echo htmlspecialchars($_pageTitle); ?></span>
        <div class="vs-strip-right">
            <?php if (function_exists('canEdit') && canEdit()): ?>
            <a href="../add.php" class="vs-add-btn">
                <i class="ti ti-plus" aria-hidden="true"></i>
                <?php echo tn('nav_add', 'Hinzufügen'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Workspace -->
    <div class="vs-workspace">
        <div class="vs-page-content">
