<?php
/**
 * Backend Layout - Header
 * Neues Hybrid-Design
 * KORRIGIERT: CSS-Pfade angepasst
 */

if (!defined('BACKEND_LAYOUT')) {
    define('BACKEND_LAYOUT', true);
}

$currentPage = basename($_SERVER['PHP_SELF']);

// Breadcrumb-Konfiguration
$breadcrumbMap = [
    'index.php'          => ['Dashboard',          '🏠', null],
    'stats.php'          => ['Statistiken',         '📈', 'Dashboard'],
    'insurance.php'      => ['Versicherungen',      '🛡️', 'Dashboard'],
    'public_settings.php'=> ['Öffentlicher Link',   '🔗', 'Dashboard'],
    'gallery.php'        => ['Bild-Galerie',         '🖼️', 'Inhalte'],
    'categories.php'     => ['Kategorien',           '🏷️', 'Inhalte'],
    'locations.php'      => ['Orte',                 '📍', 'Inhalte'],
    'users.php'          => ['Benutzer',             '👥', 'Benutzerverwaltung'],
    'permissions.php'    => ['Berechtigungen',       '🔐', 'Benutzerverwaltung'],
    'backup.php'         => ['Backup',               '💾', 'System'],
    'logs.php'           => ['System-Logs',          '📋', 'System'],
    'logs_extended.php'  => ['Erweiterte Logs',      '📋', 'System'],
    'login_security.php' => ['Login-Security',       '🔒', 'System'],
    'system.php'         => ['System-Info',          '⚙️', 'System'],
];
$breadcrumb = $breadcrumbMap[$currentPage] ?? [$pageTitle ?? 'Backend', '⚙️', 'Dashboard'];

// Update-Check (nur wenn konfiguriert)
$_updateCheckFile = __DIR__ . '/../update_check.php';
if (file_exists($_updateCheckFile)) {
    include $_updateCheckFile;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Backend'; ?> - Wertsachen Inventar</title>
    
    <?php
    // Dynamische Pfade je nach aktuellem Verzeichnis
    $isInBackend = (strpos($_SERVER['PHP_SELF'], '/backend/') !== false);
    $cssPrefix = $isInBackend ? '' : '../backend/';
    ?>
    
    <!-- Haupt-Styles -->
    <link rel="stylesheet" href="<?php echo $cssPrefix; ?>css/admin.css">
    <link rel="stylesheet" href="<?php echo $cssPrefix; ?>assets/css/admin-layout.css">
    <!-- WCAG 2.1 AA globale Fixes -->
    <link rel="stylesheet" href="<?php echo $isInBackend ? '../' : ''; ?>css/wcag_global.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../favicon.png">
</head>
<body class="backend-layout">
    
    <!-- Top Header -->
    <header class="backend-header">
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menü umschalten">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <h1 class="header-title">
                <span class="header-icon">🏛️</span>
                Admin Backend
            </h1>
        </div>
        
        <div class="header-right">
            <div class="user-info">
                <span class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
                </span>
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                <span class="user-role">
                    <?php 
                    $role = $_SESSION['role'] ?? 'admin';
                    $roleBadges = [
                        'admin' => '<span class="badge badge-admin">🔥 Admin</span>',
                        'edit' => '<span class="badge badge-edit">✏️ Editor</span>',
                        'read' => '<span class="badge badge-view">👁️ Viewer</span>'
                    ];
                    echo $roleBadges[$role] ?? '';
                    ?>
                </span>
            </div>
            
            <a href="../index.php" class="header-link" title="Zur Hauptanwendung">
                🏠 Hauptanwendung
            </a>
            
            <a href="../logout.php" class="header-link logout" title="Abmelden">
                🚪 Abmelden
            </a>
        </div>
    </header>
    
    <!-- Main Container -->
    <div class="backend-container">
        
        <?php 
        // Sidebar einbinden
        $sidebarPath = __DIR__ . '/sidebar.php';
        if (file_exists($sidebarPath)) {
            include $sidebarPath;
        }
        ?>
        
        <!-- Breadcrumb -->
        <nav class="backend-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo $cssPrefix ?? ''; ?>index.php" class="bc-item">🏠 Dashboard</a>
            <?php if ($breadcrumb[2]): ?>
                <span class="bc-sep">›</span>
                <span class="bc-item bc-group"><?php echo $breadcrumb[2]; ?></span>
            <?php endif; ?>
            <?php if ($currentPage !== 'index.php'): ?>
                <span class="bc-sep">›</span>
                <span class="bc-item bc-current"><?php echo $breadcrumb[0]; echo $breadcrumb[1] ? ' ' . $breadcrumb[1] : ''; ?></span>
            <?php endif; ?>
        </nav>


        <?php if (!empty($_SESSION['update_notice'])): ?>
        <?php $notice = $_SESSION['update_notice']; ?>
        <div id="update-notice" style="
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin: 12px 24px;
            padding: 12px 16px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 4px solid #2563eb;
            border-radius: 8px;
            font-size: 14px;
            color: #1e40af;
        ">
            <span>
                <?php if (($notice['typ'] ?? 'local') === 'local'): ?>
                    🆕 <strong>ValuSafe wurde auf Version <?php echo htmlspecialchars($notice['remote']); ?> aktualisiert.</strong>
                    <?php if ($notice['datum']): ?>
                        <span style="font-weight:400; color:#3b82f6; margin-left:6px;">
                            (<?php echo htmlspecialchars(date('d.m.Y', strtotime($notice['datum']))); ?>)
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    🔔 <strong>Version <?php echo htmlspecialchars($notice['remote']); ?> ist verfügbar</strong>
                    <span style="font-weight:400; color:#3b82f6; margin-left:6px;">
                        — du verwendest Version <?php echo htmlspecialchars($notice['local']); ?>
                    </span>
                <?php endif; ?>
            </span>
            <span style="display:flex; align-items:center; gap:12px;">
                <a href="<?php echo htmlspecialchars($notice['changelog_url']); ?>"
                   style="color:#2563eb; font-weight:600; text-decoration:none; font-size:13px;">
                    📋 Weitere Informationen →
                </a>
                <button type="button"
                        onclick="
                            document.getElementById('update-notice').style.display='none';
                            fetch('ajax/dismiss_update_notice.php');
                        "
                        style="background:none; border:none; cursor:pointer; font-size:16px; color:#93c5fd; line-height:1;"
                        title="Schließen">✕</button>
            </span>
        </div>
        <?php endif; ?>
