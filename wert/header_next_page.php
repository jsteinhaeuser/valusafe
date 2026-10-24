<?php
/**
 * header_next_page.php
 * Wiederverwendbarer Header für alle Next-Interface-Seiten (außer index.php)
 * Einbinden statt header.php: include 'header_next_page.php';
 */

// tn()-Helfer (gleich wie in header_next.php)
if (!function_exists('tn')) {
    function tn(string $key, string $fallback): string {
        $v = function_exists('t') ? t($key) : $key;
        return ($v === null || $v === false || $v === '' || $v === $key) ? $fallback : $v;
    }
}

// Theme für Legacy-Komponenten
$_themeFile = 'css/navy.css';
if (!empty($_SESSION['user_theme'])) {
    $_allowedThemes = ['navy','cloud','sand','forest','ocean','midnight','mint','lavender','emerald','glass','cherry','sunset','dark'];
    $_themeSlug = $_SESSION['user_theme'];
    if (in_array($_themeSlug, $_allowedThemes)) {
        $_themeFile = "css/{$_themeSlug}.css";
    }
    unset($_allowedThemes, $_themeSlug);
} elseif (!empty($_SESSION['theme'])) {
    // Fallback: settings.php setzt $_SESSION['theme'] (nicht user_theme)
    $_allowedThemes = ['navy','cloud','sand','forest','ocean','midnight','mint','lavender','emerald','glass','cherry','sunset','dark'];
    $_themeSlug = $_SESSION['theme'];
    if (in_array($_themeSlug, $_allowedThemes)) {
        $_themeFile = "css/{$_themeSlug}.css";
    }
    unset($_allowedThemes, $_themeSlug);
}

// helpers.php laden falls noch nicht geschehen (esc(), canEdit() etc.)
if (!function_exists('esc') && file_exists(__DIR__ . '/helpers.php')) {
    require_once __DIR__ . '/helpers.php';
}
// Notfall-Fallback falls helpers.php fehlt
if (!function_exists('esc')) {
    function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$_appName   = defined('APP_NAME') ? APP_NAME : 'ValuSafe';
$_pageTitle = defined('PAGE_TITLE') ? PAGE_TITLE : ($_pageTitle ?? $_appName);
// Die Seiten haengen den Programmnamen selbst an (" - " . t('app_title')), der
// <title> unten nochmals - und die Kopfzeile zeigte ihn mit. Einmal abschneiden
// (Valu-Basic 2.3 / ValuSafe 4.5.2; vorher stand dort der alte Name
// "Wertsachen-Inventarverwaltung" und fiel nicht als doppelt auf).
// Auch t('app_title'): eine Instanz mit eigenem APP_NAME hat sonst
// "Dashboard - ValuSafe — Eigener Name".
foreach (array_unique([$_appName, function_exists('t') ? t('app_title') : $_appName]) as $_vsName) {
    $_pageTitle = preg_replace('/\s+-\s+' . preg_quote($_vsName, '/') . '$/u', '', $_pageTitle);
}
$_username  = $_SESSION['username'] ?? '';

// Theme laden — wie klassisches header.php
// $_SESSION['theme'] muss gesetzt sein damit Seiten wie settings.php es nutzen können
if (empty($_SESSION['theme']) && isset($db)) {
    try {
        $_themeRow = $db->selectOne(
            "SELECT theme FROM users WHERE username = ?",
            [$_username]
        );
        $_SESSION['theme'] = (!empty($_themeRow['theme'])) ? $_themeRow['theme'] : 'navy';
    } catch (Exception $e) {
        $_SESSION['theme'] = 'navy';
    }
}
if (empty($_SESSION['theme'])) {
    $_SESSION['theme'] = 'navy';
}

// Aktive Rail-Seite erkennen
$_currentPage = basename($_SERVER['PHP_SELF']);
$_railActive  = [
    'index.php'       => 'ti-layout-cards',
    'dashboard.php'   => 'ti-chart-pie',
    'stats.php'       => 'ti-chart-pie',
    'insurance.php'   => 'ti-shield',
    'gallery.php'     => 'ti-photo',
    'settings.php'    => 'ti-settings',
];
?>
<!DOCTYPE html>
<?php // lang = gewaehlte Sprache des Benutzers (WCAG 3.1.1; bis 4.3.33 immer die
      // Installationssprache APP_LANG - Bildschirmleser lasen Englisch mit deutscher Stimme). ?>
<html lang="<?php echo preg_match('/^[a-z]{2}$/', $_SESSION['lang'] ?? '') ? $_SESSION['lang'] : (defined('APP_LANG') ? APP_LANG : 'de'); ?>">
<head>
    <meta charset="UTF-8">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#185fa5">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($_pageTitle); ?> — <?php echo htmlspecialchars($_appName); ?></title>
    <link rel="stylesheet" href="css/tabler-icons.min.css">
    <link rel="stylesheet" href="css/tokens.css">
    <link rel="stylesheet" href="css/next.css">
    <link rel="stylesheet" href="<?php echo $_themeFile; ?>">
    <link rel="stylesheet" href="css/wcag_global.css">
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        /* Seiten-spezifisch: Inhaltsbereich als normale Seite */
        .vs-page-content {
            flex: 1;
            overflow-y: auto;
            padding: 24px 28px;
            min-width: 0;
        }
        .vs-page-content h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--vs-text, #0c1f3d);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--vs-border, #e2e8f0);
        }
    </style>
    <?php include __DIR__ . '/components/js_texte.php'; // JS-Texte in Benutzersprache (4.3.34) ?>
</head>
<body class="vs-next">
<?php // Sprunglink + Hauptbereich (WCAG 2.4.1, 1.3.1; 4.4). Seiten mit eigenem <main>
      // setzen vor dem Einbinden $vsEigenesMain - dann traegt der Inhaltsbereich
      // nur die Sprungmarke, keine zweite main-Rolle. ?>
<a class="vs-skip" href="#hauptinhalt"><?php echo htmlspecialchars(tn('nav_skip_content', 'Zum Inhalt springen'), ENT_QUOTES, 'UTF-8'); ?></a>

<!-- Icon Rail -->
<aside class="vs-rail" aria-label="<?php echo tn('nav_aria_main', 'Hauptnavigation'); ?>">
    <a href="index.php" class="vs-rail-logo" aria-label="<?php echo tn('page_overview', 'Übersicht'); ?>" style="text-decoration:none">
        <i class="ti ti-shield-check" aria-hidden="true"></i>
    </a>
    <nav class="vs-rail-nav" aria-label="<?php echo tn('nav_aria_menu', 'Hauptmenü'); ?>">
        <a class="vs-rail-btn <?php echo $_currentPage === 'index.php' ? 'vs-rail-on' : ''; ?>"
           href="index.php" title="<?php echo tn('page_overview', 'Übersicht'); ?>">
            <i class="ti ti-layout-cards" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn <?php echo in_array($_currentPage, ['dashboard.php','stats.php']) ? 'vs-rail-on' : ''; ?>"
           href="dashboard.php" title="<?php echo tn('nav_dashboard', 'Dashboard'); ?>">
            <i class="ti ti-chart-pie" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn <?php echo $_currentPage === 'insurance.php' ? 'vs-rail-on' : ''; ?>"
           href="insurance.php" title="<?php echo tn('nav_insurance', 'Versicherungen'); ?>">
            <i class="ti ti-shield" aria-hidden="true"></i>
        </a>
        <?php if (function_exists('hasPermission') && hasPermission('claims_view') && function_exists('vsMigration014Da') && vsMigration014Da()): ?>
        <a class="vs-rail-btn <?php echo in_array($_currentPage, ['schadenfall.php', 'schadenfall_dokument.php']) ? 'vs-rail-on' : ''; ?>"
           href="schadenfall.php" title="<?php echo htmlspecialchars(tn('claims_title', 'Schadensfall'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(tn('claims_title', 'Schadensfall'), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $_currentPage === 'schadenfall.php' ? ' aria-current="page"' : ''; ?>>
            <i class="ti ti-alert-triangle" aria-hidden="true"></i>
        </a>
        <?php endif; ?>
        <?php // Statistik und Galerie nur fuer Admins (Verwaltungsbereich), wie header_next.php ?>
        <?php if ((function_exists('isSuperAdmin') && isSuperAdmin()) || ($_SESSION['role'] ?? '') === 'admin'): ?>
        <a class="vs-rail-btn <?php echo $_currentPage === 'stats.php' ? 'vs-rail-on' : ''; ?>"
           href="backend/stats.php" title="<?php echo tn('nav_stats', 'Statistiken'); ?>">
            <i class="ti ti-chart-bar" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn <?php echo $_currentPage === 'gallery.php' ? 'vs-rail-on' : ''; ?>"
           href="backend/gallery.php" title="<?php echo tn('nav_gallery', 'Galerie'); ?>">
            <i class="ti ti-photo" aria-hidden="true"></i>
        </a>
        <?php endif; ?>
    </nav>
    <div class="vs-rail-spacer"></div>
    <div class="vs-rail-foot">
        <?php if (function_exists('isSuperAdmin') && isSuperAdmin()): ?>
        <a class="vs-rail-btn" href="backend/superadmin.php"
           title="SuperAdmin">
            <i class="ti ti-crown" aria-hidden="true"></i>
        </a>
        <?php endif; ?>
        <?php if ((function_exists('isSuperAdmin') && isSuperAdmin()) || ($_SESSION['role'] ?? '') === 'admin'): ?>
        <a class="vs-rail-btn" href="backend/index.php"
           title="<?php echo tn('nav_admin', 'Admin'); ?>">
            <i class="ti ti-settings-2" aria-hidden="true"></i>
        </a>
        <?php endif; ?>
        <a class="vs-rail-btn" href="#" onclick="openServiceModal();return false;"
           title="<?php echo htmlspecialchars(tn('nav_help', 'Hilfe & Informationen'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(tn('nav_help', 'Hilfe & Informationen'), ENT_QUOTES, 'UTF-8'); ?>">
            <i class="ti ti-info-circle" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn <?php echo $_currentPage === 'settings.php' ? 'vs-rail-on' : ''; ?>"
           href="settings.php" title="<?php echo tn('nav_settings', 'Einstellungen'); ?>">
            <i class="ti ti-settings" aria-hidden="true"></i>
        </a>
        <a class="vs-rail-btn" href="logout.php" title="<?php echo tn('nav_logout', 'Abmelden'); ?> (<?php echo htmlspecialchars($_username); ?>)">
            <i class="ti ti-logout" aria-hidden="true"></i>
        </a>
    </div>
</aside>

<!-- Haupt-Bereich -->
<div class="vs-main">
    <!-- Top Strip -->
    <div class="vs-topstrip">
        <a href="index.php" class="vs-topstrip-back" style="color:var(--vs-muted,#64748b);text-decoration:none;display:flex;align-items:center;gap:6px;font-size:13px;">
            <i class="ti ti-arrow-left" aria-hidden="true"></i>
        </a>
        <span class="vs-context"><?php echo htmlspecialchars($_pageTitle); ?></span>
        <div class="vs-strip-right">
            <?php if (function_exists('canEdit') && canEdit()): ?>
            <a href="add.php" class="vs-add-btn">
                <i class="ti ti-plus" aria-hidden="true"></i>
                <?php echo tn('nav_add', 'Hinzufügen'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Workspace: volle Breite (keine Detail-Pane) -->
    <div class="vs-workspace">
        <div class="vs-page-content" id="hauptinhalt" tabindex="-1"<?php echo empty($vsEigenesMain) ? ' role="main"' : ''; ?>>
