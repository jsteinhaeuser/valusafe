<?php
/**
 * Backup Management Dashboard
 * backend/backup.php
 */

require_once __DIR__ . '/config.php'; // lädt /../config.php + /../db.php korrekt
requireBackendAccess(); // aus backend/config.php - prüft Admin-Zugriff

// esc() und andere Helper sicherstellen
if (!function_exists('esc')) {
    require_once __DIR__ . '/../helpers.php';
}

// Permission prüfen: admin_backup
requirePermission('admin_backup');

define('PAGE_TITLE', 'Backup Management - Wertsachen-Inventar');

// formatBytes() bereits in backend/config.php definiert

$message = '';
$error = '';

// SuperAdmin-Einstellungen laden
$sa_db_allowed       = isSuperAdmin() || getSuperAdminSetting('sa_backup_db_allowed', true);
$sa_php_allowed      = isSuperAdmin() || getSuperAdminSetting('sa_backup_php_allowed', true);
$sa_images_allowed   = isSuperAdmin() || getSuperAdminSetting('sa_backup_images_allowed', true);
$sa_download_allowed = isSuperAdmin() || getSuperAdminSetting('sa_backup_download_allowed', true);
$sa_restore_allowed  = isSuperAdmin() || getSuperAdminSetting('sa_restore_allowed', true);

// Backup-Konfiguration laden/speichern
$configFile = BACKUP_DIR . 'backup_config.json';
$defaultConfig = ['db' => true, 'images' => true, 'phpfiles' => false];
$config = $defaultConfig;
if (file_exists($configFile)) {
    $loaded = json_decode(file_get_contents($configFile), true);
    if (is_array($loaded)) $config = array_merge($defaultConfig, $loaded);
}

// PHP-Backup erzwingen deaktiviert wenn nicht erlaubt
if (!$sa_db_allowed)      $config['db']       = false;
if (!$sa_php_allowed)     $config['phpfiles'] = false;
if (!$sa_images_allowed)  $config['images']   = false;

// Konfiguration speichern
if (isset($_POST['save_config'])) {
    validateRequest();
    $config = [
        'db'       => isset($_POST['backup_db']),
        'images'   => isset($_POST['backup_images']) && $sa_images_allowed,
        'phpfiles' => isset($_POST['backup_phpfiles']) && $sa_php_allowed,
    ];
    file_put_contents($configFile, json_encode($config));
    $message = t('backup_config_saved') ?: '<i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Backup-Konfiguration gespeichert.';
}

// Manuelles Backup - Backup-Logik direkt hier ausführen
if (isset($_POST['create_backup'])) {
    validateRequest();
    
    $backupResults = [];
    $backupErrors = [];
    
    if (!is_dir(BACKUP_DIR)) mkdir(BACKUP_DIR, 0755, true);
    
    $logFile = BACKUP_DIR . 'cron.log';
    $ts = date('Y-m-d_H-i-s');
    
    $appendLog = function($msg, $err = false) use ($logFile) {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] [" . ($err ? 'ERROR' : 'INFO') . "] $msg\n", FILE_APPEND);
    };
    
    $fmtBytes = function($b) {
        $u = ['B','KB','MB','GB'];
        $p = floor(($b ? log($b) : 0) / log(1024));
        $p = min($p, 3);
        return round($b / pow(1024, $p), 2) . ' ' . $u[$p];
    };
    
    @set_time_limit(120); // 2 Minuten für Backup
    @ini_set('display_errors', 0); // Fehler nicht ausgeben, nur loggen
    
    $appendLog("=== MANUELLES BACKUP GESTARTET ===");
    $appendLog("Config: db=" . ($config['db'] ? 'ja' : 'nein') . " images=" . ($config['images'] ? 'ja' : 'nein') . " php=" . ($config['phpfiles'] ? 'ja' : 'nein'));
    $appendLog("BACKUP_DIR=" . BACKUP_DIR);
    $appendLog("UPLOAD_DIR=" . UPLOAD_DIR);
    $appendLog("is_writable=" . (is_writable(BACKUP_DIR) ? 'ja' : 'nein'));
    
    // DB-Backup
    if ($config['db']) {
        try {
            $filename = 'database_' . $ts . '.sql';
            $filepath = BACKUP_DIR . $filename;
            $f = fopen($filepath, 'w');
            if (!$f) throw new Exception("Kann Datei nicht erstellen: $filepath");
            fwrite($f, "-- Backup " . date('Y-m-d H:i:s') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
            // Alle Tabellen der Datenbank sichern statt einer gepflegten Liste.
            //
            // Bis zum 06.09.2026 stand hier eine feste Aufzaehlung von sechs
            // Tabellen plus drei bedingten. Die Datenbank des Masters hat 22.
            // Nicht gesichert waren unter anderem versicherungen (saemtliche
            // Vertraege), app_settings, schema_migrations sowie die gesamte
            // Standort-Hierarchie: standorte, standort_raeume,
            // standort_positionen, raeume, positionen. Dazu permissions,
            // user_activity, wert_historie, login_attempts, password_resets.
            //
            // Eine Sicherung, die schweigend die Haelfte weglaesst, ist keine
            // Sicherung. Jede neue Tabelle war ausserdem so lange nicht
            // gesichert, bis jemand daran dachte, sie hier nachzutragen.
            $tables = [];
            foreach ($pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'") as $zeile) {
                $tables[] = array_values($zeile)[0];
            }
            sort($tables);
            $appendLog('DB-Backup: ' . count($tables) . ' Tabellen gefunden');
            foreach ($tables as $table) {
                try {
                    $row = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
                    fwrite($f, "DROP TABLE IF EXISTS `$table`;\n" . $row['Create Table'] . ";\n\n");
                    $rows2 = $pdo->query("SELECT * FROM `$table`");
                    $cnt = 0;
                    foreach ($rows2 as $row2) {
                        if ($cnt == 0) fwrite($f, "INSERT INTO `$table` (`" . implode('`,`', array_keys($row2)) . "`) VALUES\n");
                        $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($row2));
                        fwrite($f, ($cnt > 0 ? ',' : '') . "(" . implode(',', $vals) . ")\n");
                        $cnt++;
                    }
                    if ($cnt > 0) fwrite($f, ";\n\n");
                } catch(Exception $e) { $appendLog("Tabelle $table: " . $e->getMessage(), true); }
            }
            fwrite($f, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($f);
            $sz = filesize($filepath);
            $appendLog("DB-Backup: $filename (" . $fmtBytes($sz) . ")");
            $backupResults[] = "DB: $filename";
        } catch(Exception $e) {
            $appendLog("DB-Backup Fehler: " . $e->getMessage(), true);
            $backupErrors[] = "DB: " . $e->getMessage();
        }
    }
    
    // Dateien-Backup
    if ($config['images']) {
        try {
            $filename = 'files_' . $ts . '.zip';
            $filepath = BACKUP_DIR . $filename;
            $zip = new ZipArchive();
            if ($zip->open($filepath, ZipArchive::CREATE) !== true) throw new Exception("ZIP nicht erstellbar");
            $cnt = 0;
            if (is_dir(UPLOAD_DIR)) {
                foreach (glob(UPLOAD_DIR . '*') as $uf) {
                    if (is_file($uf)) { $zip->addFile($uf, 'upload/' . basename($uf)); $cnt++; }
                }
            }
            $docsDir = __DIR__ . '/../documents/';
            if (is_dir($docsDir)) {
                foreach (glob($docsDir . '*') as $uf) {
                    if (is_file($uf)) { $zip->addFile($uf, 'documents/' . basename($uf)); $cnt++; }
                }
            }
            $zip->close();
            $sz = filesize($filepath);
            $appendLog("Dateien-Backup: $filename ($cnt Dateien, " . $fmtBytes($sz) . ")");
            $backupResults[] = "Dateien: $filename";
        } catch(Exception $e) {
            $appendLog("Dateien-Backup Fehler: " . $e->getMessage(), true);
            $backupErrors[] = "Dateien: " . $e->getMessage();
        }
    }
    
    // PHP-Backup
    if ($config['phpfiles']) {
        try {
            $filename = 'php_' . $ts . '.zip';
            $filepath = BACKUP_DIR . $filename;
            $zip = new ZipArchive();
            if ($zip->open($filepath, ZipArchive::CREATE) !== true) throw new Exception("ZIP nicht erstellbar");
            $cnt = 0;
            $root = __DIR__ . '/../';
            foreach (glob($root . '*.php') as $uf) { if (is_file($uf)) { $zip->addFile($uf, basename($uf)); $cnt++; } }
            foreach (['backend','lang','includes'] as $sub) {
                if (is_dir($root . $sub)) {
                    foreach (glob($root . $sub . '/*.php') as $uf) { $zip->addFile($uf, $sub . '/' . basename($uf)); $cnt++; }
                }
            }
            if (is_dir($root . 'css')) {
                foreach (glob($root . 'css/*.css') as $uf) { $zip->addFile($uf, 'css/' . basename($uf)); $cnt++; }
            }
            $zip->close();
            $sz = filesize($filepath);
            $appendLog("PHP-Backup: $filename ($cnt Dateien, " . $fmtBytes($sz) . ")");
            $backupResults[] = "PHP: $filename";
        } catch(Exception $e) {
            $appendLog("PHP-Backup Fehler: " . $e->getMessage(), true);
            $backupErrors[] = "PHP: " . $e->getMessage();
        }
    }
    
    // Aufbewahrung (backup_rotation.php) - nur wenn etwas Neues entstanden ist
    if (!empty($backupResults)) {
        require_once __DIR__ . '/../backup_rotation.php';
        $tage = defined('BACKUP_RETENTION_DAYS') ? (int)BACKUP_RETENTION_DAYS : 7;
        foreach (sicherungenAufraeumen(BACKUP_DIR, $tage) as [$name, $alter]) {
            $appendLog("Gelöscht: $name (Alter: $alter Tage)");
        }
    }

    if (empty($backupResults) && empty($backupErrors)) {
        $appendLog("=== NICHTS GESICHERT: keine Sicherungsart ausgewählt ===", true);
    } else {
        $appendLog("=== BACKUP ABGESCHLOSSEN ===");
    }
    
    if (empty($backupErrors) && !empty($backupResults)) {
        $message = (t('backup_created') ?: '<i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Backup erstellt: ') . implode(', ', $backupResults);
        Security::logSecurityEvent('backup_created', ['method' => 'manual']);
    } elseif (!empty($backupErrors)) {
        $error = '<i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> ' . t('bk_error_prefix') . implode('; ', $backupErrors);
    } else {
        $error = t('backup_no_types') ?: '<i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> Keine Backup-Typen aktiviert. Bitte Konfiguration prüfen.';
    }
}

// Email speichern
if (isset($_POST['save_email'])) {
    validateRequest();
    $email = trim($_POST['admin_email'] ?? '');
    $emailFile = BACKUP_DIR . 'admin_email.txt';
    if (empty($email)) {
        if (file_exists($emailFile)) unlink($emailFile);
        $message = t('backup_email_disabled') ?: '<i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Email-Benachrichtigungen deaktiviert.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        file_put_contents($emailFile, $email);
        $message = t('backup_email_saved') ?: '<i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Email-Adresse gespeichert.';
    } else {
        $error = t('backup_email_invalid') ?: '<i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> Ungültige Email-Adresse.';
    }
}

// Backup löschen
if (isset($_POST['delete_backup'])) {
    validateRequest();
    $filename = basename($_POST['filename'] ?? '');
    $filepath = BACKUP_DIR . $filename;
    if ($filename && file_exists($filepath) && strpos(realpath($filepath), realpath(BACKUP_DIR)) === 0) {
        unlink($filepath);
        $message = (t('backup_deleted') ?: '<i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Backup gelöscht: ') . esc($filename);
        Security::logSecurityEvent('backup_deleted', ['file' => $filename]);
    } else {
        $error = '<i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> ' . t('bk_file_not_found');
    }
}

// Bulk-Delete
if (isset($_POST['bulk_delete_backups'])) {
    validateRequest();
    $filenames = $_POST['selected_backups'] ?? [];
    $deleted = 0;
    $errors = 0;
    foreach ($filenames as $filename) {
        $filename = basename($filename);
        $filepath = BACKUP_DIR . $filename;
        if ($filename && file_exists($filepath) && strpos(realpath($filepath), realpath(BACKUP_DIR)) === 0) {
            unlink($filepath);
            Security::logSecurityEvent('backup_deleted', ['file' => $filename]);
            $deleted++;
        } else {
            $errors++;
        }
    }
    if ($deleted > 0) {
        $message = sprintf(t('backup_deleted_count') ?: '<i class="ti ti-circle-check" style="color:var(--vs-success);"></i> %d Backup(s) gelöscht.', $deleted);
    }
    if ($errors > 0) {
        $error = sprintf(t('backup_delete_error') ?: '<i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> %d Datei(en) konnten nicht gelöscht werden.', $errors);
    }
    if (empty($filenames)) {
        $error = t('backup_none_selected') ?: '<i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> Keine Backups ausgewählt.';
    }
}

// Email laden
$savedEmail = '';
$emailFile = BACKUP_DIR . 'admin_email.txt';
if (file_exists($emailFile)) $savedEmail = trim(file_get_contents($emailFile));

// Backup-Dateien auflisten
$backups = [];
if (is_dir(BACKUP_DIR)) {
    foreach (glob(BACKUP_DIR . '*') as $file) {
        if (!is_file($file)) continue;
        $name = basename($file);
        if (in_array($name, ['cron.log', '.htaccess', 'admin_email.txt', 'backup_config.json'])) continue;
        $backups[] = [
            'filename' => $name,
            'size'     => filesize($file),
            'modified' => filemtime($file),
            'age_days' => (int)floor((time() - filemtime($file)) / 86400),
            'date_str' => date('Y-m-d', filemtime($file)),
        ];
    }
}
usort($backups, fn($a, $b) => $b['modified'] - $a['modified']);

// Cron Log
$cronLog = '';
$cronLogFile = BACKUP_DIR . 'cron.log';
if (file_exists($cronLogFile)) {
    $lines = file($cronLogFile);
    $cronLog = implode('', array_slice($lines, -60));
}

$totalSize  = array_sum(array_column($backups, 'size'));
$lastBackup = $backups[0] ?? null;

// ── DOWNLOAD-HANDLER (server-seitige Prüfung) ────────────────────────────
// WICHTIG: Direktzugriff auf ../backups/ ist damit nicht mehr nötig.
// Der Download läuft durch diesen Handler — sa_download_allowed wird erzwungen.
if (isset($_GET['download'])) {
    if (!$sa_download_allowed) {
        http_response_code(403);
        exit('Download wurde vom Betreiber deaktiviert.');
    }
    $filename = basename($_GET['download'] ?? '');
    $filepath = BACKUP_DIR . $filename;
    // Path-Traversal-Schutz
    if (!$filename || !file_exists($filepath) ||
        strpos(realpath($filepath), realpath(BACKUP_DIR)) !== 0) {
        http_response_code(404);
        exit(t('bk_file_not_found'));
    }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache');
    readfile($filepath);
    exit;
}

// Pfad-Korrektur für Backend-Unterverzeichnis
$pageTitle = 'Backup Management';
include 'layout/header_next_page.php';
?>

<style>
.backup-config-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 6px;
}
.backup-config-tile {
    border-radius: 14px;
    border: 2.5px solid transparent;
    padding: 20px 16px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background: rgba(255,255,255,0.55);
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    position: relative;
}
.backup-config-tile input[type="checkbox"] {
    position: absolute; opacity: 0; width: 0; height: 0;
}
.backup-config-tile:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.10); }
.backup-config-tile.tile-active { border-color: var(--tile-color); background: var(--tile-bg); }
.backup-config-tile .tile-icon { font-size: 32px; margin-bottom: 8px; }
.backup-config-tile .tile-label { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
.backup-config-tile .tile-desc { font-size: 11px; color: #888; }
.backup-config-tile .tile-check {
    position: absolute; top: 8px; right: 8px;
    width: 20px; height: 20px; border-radius: 50%;
    background: var(--tile-color); color: var(--vs-surface);
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700;
    opacity: 0; transition: opacity 0.2s;
}
.backup-config-tile.tile-active .tile-check { opacity: 1; }

.stat-strip { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px; }
.stat-box { background: rgba(255,255,255,0.6); border: 1px solid rgba(0,0,0,0.07); border-radius: 12px; padding: 16px; text-align: center; }
.stat-box-value { font-size: 24px; font-weight: 700; }
.stat-box-label { font-size: 12px; color: #888; margin-top: 3px; }

.backup-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
.backup-table th { padding: 10px 12px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; font-weight: 600; border-bottom: 2px solid rgba(0,0,0,0.08); color: #666; }
.backup-table td { padding: 10px 12px; border-bottom: 1px solid rgba(0,0,0,0.05); font-size: 14px; vertical-align: middle; }
.backup-table tr:last-child td { border-bottom: none; }
.backup-table tr:hover td { background: rgba(0,0,0,0.02); }

.icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.10); background: rgba(255,255,255,0.7); cursor: pointer; font-size: 16px; text-decoration: none; transition: all 0.18s; }
.icon-btn:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,0.12); }
.icon-btn-danger { border-color: rgba(220,38,38,0.25); color: var(--vs-danger); }
.icon-btn-danger:hover { background: var(--vs-danger-light); border-color: var(--vs-danger); }
.icon-btn-download { border-color: rgba(37,99,235,0.25); color: var(--vs-accent); }
.icon-btn-download:hover { background: var(--vs-accent-light); border-color: var(--vs-accent); }

@media (max-width: 768px) {
    .stat-strip { grid-template-columns: repeat(2, 1fr); }
    .stat-box { padding: 10px 8px; }
    .stat-box-value { font-size: 20px; }
    .stat-box-label { font-size: 11px; }
    .backup-config-grid { grid-template-columns: repeat(3, 1fr); }
    .backup-config-tile { padding: 12px 8px; }
    .backup-config-tile .tile-icon { font-size: 22px; margin-bottom: 4px; }
    .backup-config-tile .tile-label { font-size: 12px; }
    .backup-config-tile .tile-desc { font-size: 10px; }
}
@media (max-width: 480px) {
    .backup-config-grid { grid-template-columns: repeat(3, 1fr); }
    .backup-config-tile { padding: 10px 6px; }
    .backup-config-tile .tile-icon { font-size: 20px; margin-bottom: 3px; }
    .backup-config-tile .tile-label { font-size: 11px; }
    .backup-config-tile .tile-desc { display: none; }
    .stat-strip { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 360px) {
    .backup-config-grid { grid-template-columns: 1fr; }
    .stat-strip { grid-template-columns: repeat(2, 1fr); }
}

/* Bulk-Select */
.bulk-checkbox { width: 16px; height: 16px; cursor: pointer; accent-color: var(--vs-danger); }
.bulk-bar { display: none; align-items: center; gap: 12px; background: var(--vs-danger-light); border: 1px solid rgba(220,38,38,0.2); border-radius: 10px; padding: 10px 16px; margin-bottom: 14px; }
.bulk-bar.visible { display: flex; }
.bulk-bar-count { font-size: 14px; font-weight: 600; color: var(--vs-danger); }
.bulk-bar-info { font-size: 13px; color: #666; }
</style>

        <!-- Main Content -->
        <main class="backend-main">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
    <a href="../index.php" class="vs-btn vs-btn-secondary">← <?php echo t('btn_back') ?: 'Zurück'; ?></a>
    <h2 style="margin:0; border:none; padding:0;"><i class="ti ti-device-floppy"></i> <?php echo t('backup_management') ?: 'Backup Management'; ?></h2>
</div>

<?php if ($message): ?>
<div id="toast-success" style="
    position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
    background: var(--vs-success); color: var(--vs-surface); padding: 16px 32px;
    border-radius: 12px; font-size: 16px; font-weight: 600;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2); z-index: 99999;
    display: flex; align-items: center; gap: 10px; min-width: 320px;
    animation: slideDown 0.3s ease;">
    <i class="ti ti-circle-check" style="color:var(--vs-success);"></i> <?php echo $message; ?>
</div>
<script>
setTimeout(() => {
    const t = document.getElementById('toast-success');
    if (t) { t.style.transition = 'opacity 0.5s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 500); }
}, 5000);
</script>
<?php endif; ?>
<?php if ($error): ?>
<div id="toast-error" style="
    position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
    background: var(--vs-danger); color: var(--vs-surface); padding: 16px 32px;
    border-radius: 12px; font-size: 16px; font-weight: 600;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2); z-index: 99999;
    display: flex; align-items: center; gap: 10px; min-width: 320px;
    cursor: pointer;" onclick="this.remove()">
    <i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> <?php echo $error; ?> <span style="margin-left:auto; opacity:0.7;">✕</span>
</div>
<?php endif; ?>
<style>
@keyframes slideDown {
    from { top: -60px; opacity: 0; }
    to   { top: 20px;  opacity: 1; }
}
</style>

<!-- Statistiken -->
<div class="stat-strip">
    <div class="stat-box">
        <div class="stat-box-value" style="color:var(--vs-success);"><?php echo count($backups); ?></div>
        <div class="stat-box-label"><?php echo t('backup_total') ?: '<i class="ti ti-device-floppy"></i> Backups gesamt'; ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-box-value" style="color:var(--vs-accent-hover);"><?php echo formatBytes($totalSize); ?></div>
        <div class="stat-box-label"><?php echo t('backup_total_size') ?: '💿 Gesamtgröße'; ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-box-value" style="font-size:16px; color:<?php echo $lastBackup ? 'var(--vs-success)' : 'var(--vs-danger)'; ?>;">
            <?php echo $lastBackup ? date('d.m.Y H:i', $lastBackup['modified']) : '—'; ?>
        </div>
        <div class="stat-box-label"><?php echo t('backup_last') ?: '🕐 Letztes Backup'; ?><?php if ($lastBackup && $lastBackup['age_days'] > 0): ?> (<?php echo sprintf(t('backup_days_ago') ?: 'vor %d Tagen', $lastBackup['age_days']); ?>)<?php endif; ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-box-value" style="color:<?php echo $savedEmail ? 'var(--vs-success)' : 'var(--vs-danger)'; ?>;">
            <?php echo $savedEmail ? '✓' : '✗'; ?>
        </div>
        <div class="stat-box-label"><i class="ti ti-mail"></i> <?php echo $savedEmail ? esc($savedEmail) : (t('backup_no_email') ?: 'Kein Email'); ?></div>
    </div>
</div>

<!-- Backup Inhalt konfigurieren -->
<div style="background:rgba(255,255,255,0.6); border:1px solid rgba(0,0,0,0.08); border-radius:16px; padding:24px; margin-bottom:20px; box-shadow:0 2px 12px rgba(0,0,0,0.05);">
    <h3 style="margin:0 0 16px;"><i class="ti ti-package"></i> <?php echo t('backup_content') ?: 'Backup-Inhalt'; ?></h3>
    <form method="POST" id="configForm">
        <?php echo Security::getCSRFInput(); ?>
        <div class="backup-config-grid">

            <label class="backup-config-tile <?php echo $config['db'] ? 'tile-active' : ''; ?>"
                   style="--tile-color:var(--vs-accent); --tile-bg:rgba(37,99,235,0.07);"
                   onclick="toggleTile(this, 'backup_db')">
                <input type="checkbox" name="backup_db" id="backup_db" aria-label="<?php echo esc(t('bk_tile_db_aria')); ?>" <?php echo $config['db'] ? 'checked' : ''; ?>>
                <div class="tile-check">✓</div>
                <div class="tile-icon">🗄️</div>
                <div class="tile-label"><?php echo t('bk_tile_db'); ?></div>
                <div class="tile-desc"><?php echo t('bk_tile_db_desc'); ?></div>
            </label>

            <label class="backup-config-tile <?php echo $config['images'] ? 'tile-active' : ''; ?> <?php echo !$sa_images_allowed ? 'tile-disabled' : ''; ?>"
                   style="--tile-color:#059669; --tile-bg:rgba(5,150,105,0.07); <?php echo !$sa_images_allowed ? 'opacity:0.4; pointer-events:none;' : ''; ?>"
                   onclick="<?php echo $sa_images_allowed ? "toggleTile(this, 'backup_images')" : ''; ?>">
                <input type="checkbox" name="backup_images" id="backup_images" aria-label="<?php echo esc(t('bk_tile_files_aria')); ?>"
                       <?php echo $config['images'] ? 'checked' : ''; ?>
                       <?php echo !$sa_images_allowed ? 'disabled' : ''; ?>>
                <div class="tile-check">✓</div>
                <div class="tile-icon"><i class="ti ti-photo"></i></div>
                <div class="tile-label"><?php echo esc(t('bk_tile_files')); ?></div>
                <div class="tile-desc"><?php echo esc($sa_images_allowed ? t('bk_tile_files_desc') : t('bk_disabled_by_operator')); ?></div>
            </label>

            <label class="backup-config-tile <?php echo $config['phpfiles'] ? 'tile-active' : ''; ?> <?php echo !$sa_php_allowed ? 'tile-disabled' : ''; ?>"
                   style="--tile-color:#7c3aed; --tile-bg:rgba(124,58,237,0.07); <?php echo !$sa_php_allowed ? 'opacity:0.4; pointer-events:none;' : ''; ?>"
                   onclick="<?php echo $sa_php_allowed ? "toggleTile(this, 'backup_phpfiles')" : ''; ?>">
                <input type="checkbox" name="backup_phpfiles" id="backup_phpfiles" aria-label="<?php echo esc(t('bk_tile_php_aria')); ?>"
                       <?php echo $config['phpfiles'] ? 'checked' : ''; ?>
                       <?php echo !$sa_php_allowed ? 'disabled' : ''; ?>>
                <div class="tile-check">✓</div>
                <div class="tile-icon">📄</div>
                <div class="tile-label"><?php echo t('bk_tile_php'); ?></div>
                <div class="tile-desc"><?php echo esc($sa_php_allowed ? t('bk_tile_php_desc') : t('bk_disabled_by_operator')); ?></div>
            </label>

        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px; flex-wrap:wrap; gap:10px;">
            <button type="submit" name="save_config" class="vs-btn vs-btn-primary"><?php echo t('backup_save_config') ?: '<i class="ti ti-device-floppy"></i> Konfiguration speichern'; ?></button>
            <a href="restore.php" class="vs-btn vs-btn-secondary" style="background:var(--vs-danger); color:var(--vs-surface); border-color:var(--vs-danger);">♻️ <?php echo t('backup_restore') ?: 'Backup wiederherstellen'; ?></a>
        </div>
    </form>
</div>

<!-- Aktionen -->
<div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px;">
    <form method="POST" style="margin:0;">
        <?php echo Security::getCSRFInput(); ?>
        <input type="hidden" name="create_backup" value="1">
        <button type="submit" class="vs-btn vs-btn-primary">
            <?php echo t('backup_create_now') ?: '<i class="ti ti-device-floppy"></i> Backup jetzt erstellen'; ?>
        </button>
    </form>
    <button class="vs-btn vs-btn-secondary" onclick="document.getElementById('emailModal').style.display='flex'">
        <?php echo t('backup_email_configure') ?: '<i class="ti ti-mail"></i> Email konfigurieren'; ?>
    </button>
    <button class="vs-btn vs-btn-secondary" onclick="document.getElementById('logModal').style.display='flex'">
        <i class="ti ti-clipboard-list"></i> Cron Log
    </button>
</div>

<!-- Backup-Tabelle -->
<div style="background:rgba(255,255,255,0.6); border:1px solid rgba(0,0,0,0.08); border-radius:16px; padding:24px; box-shadow:0 2px 12px rgba(0,0,0,0.05);">
    <h3 style="margin:0 0 16px;"><i class="ti ti-package"></i> <?php echo t('backup_files') ?: 'Backup-Dateien'; ?></h3>

    <?php if (empty($backups)): ?>
        <p style="text-align:center; color:var(--vs-text-muted); padding:30px;"><?php echo t('bk_none_yet'); ?></p>
    <?php else: ?>

    <form method="POST" id="bulkForm">
        <?php echo Security::getCSRFInput(); ?>

        <!-- Bulk-Aktionsleiste -->
        <div class="bulk-bar" id="bulkBar">
            <span class="bulk-bar-count" id="bulkCount">0 <?php echo t('backup_selected'); ?></span>
            <span class="bulk-bar-info">|</span>
            <button type="button" class="vs-btn vs-btn-secondary" style="padding:5px 12px; font-size:13px;"
                    onclick="selectAll(true)"><?php echo t('bk_select_all'); ?></button>
            <button type="button" class="vs-btn vs-btn-secondary" style="padding:5px 12px; font-size:13px;"
                    onclick="selectAll(false)"><?php echo t('bk_select_none'); ?></button>
            <button type="submit" name="bulk_delete_backups" value="1"
                    class="vs-btn vs-btn-secondary" style="padding:5px 14px; font-size:13px; background:var(--vs-danger); color:var(--vs-surface); border-color:var(--vs-danger); margin-left:auto;"
                    onclick="return confirmBulkDelete(event)">
                🗑 <?php echo t('bk_delete_selected'); ?>
            </button>
        </div>

        <table class="backup-table">
            <thead>
                <tr>
                    <th style="width:36px;">
                        <input type="checkbox" class="bulk-checkbox" id="selectAllCb"
                               onchange="selectAll(this.checked)"
                               aria-label="<?php echo esc(t('bk_select_all_aria')); ?>">
                    </th>
                    <th><?php echo t('backup_col_filename') ?: 'Dateiname'; ?></th>
                    <th><?php echo t('backup_col_type') ?: 'Typ'; ?></th>
                    <th><?php echo t('backup_size') ?: 'Größe'; ?></th>
                    <th><?php echo t('backup_col_created') ?: 'Erstellt'; ?></th>
                    <th><?php echo t('backup_col_age') ?: 'Alter'; ?></th>
                    <th style="text-align:right;"><?php echo t('tab_actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backups as $b): ?>
                <tr>
                    <td>
                        <input type="checkbox" class="bulk-checkbox row-cb"
                               name="selected_backups[]"
                               value="<?php echo esc($b['filename']); ?>"
                               aria-label="<?php echo esc(sprintf(t('bk_select_one_aria'), $b['filename'])); ?>"
                               onchange="updateBulkBar()">
                    </td>
                    <td style="font-size:12px; font-family:monospace; color:#555;"><?php echo esc($b['filename']); ?></td>
                    <td>
                        <?php if (str_starts_with($b['filename'], 'database_')): ?>
                            <span style="background:#dbeafe; color:var(--vs-accent); padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600;"><?php echo t('backup_type_db') ?: '🗄️ DB'; ?></span>
                        <?php elseif (str_starts_with($b['filename'], 'files_')): ?>
                            <span style="background:#dcfce7; color:#166534; padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600;"><?php echo t('backup_type_files') ?: '<i class="ti ti-photo"></i> Dateien'; ?></span>
                        <?php elseif (str_starts_with($b['filename'], 'php_')): ?>
                            <span style="background:#ede9fe; color:#5b21b6; padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600;"><?php echo t('backup_type_php') ?: '📄 PHP'; ?></span>
                        <?php else: ?>
                            <span style="background:#f3f4f6; color:var(--vs-text-muted); padding:2px 8px; border-radius:4px; font-size:12px;"><i class="ti ti-package"></i></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo formatBytes($b['size']); ?></td>
                    <td><?php echo date('d.m.Y H:i', $b['modified']); ?></td>
                    <td style="color:var(--vs-text-muted); font-size:13px;">
                        <?php
                        $today = date('Y-m-d');
                        $yesterday = date('Y-m-d', strtotime('-1 day'));
                        if ($b['date_str'] === $today): ?>
                            <span style="color:var(--vs-success);"><?php echo t('bk_today'); ?></span>
                        <?php elseif ($b['date_str'] === $yesterday): ?>
                            <span style="color:var(--vs-warning);"><?php echo t('bk_yesterday'); ?></span>
                        <?php else: ?>
                            <?php echo sprintf(t('bk_days'), (int)$b['age_days']); ?>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right; white-space:nowrap;">
                        <?php if ($sa_download_allowed): ?>
                        <a href="?download=<?php echo urlencode($b['filename']); ?>"
                           title="Download"
                           class="icon-btn icon-btn-download">⬇</a>
                        <?php else: ?>
                        <span class="icon-btn" title="Download vom Betreiber deaktiviert"
                              style="opacity:0.3; cursor:not-allowed;">⬇</span>
                        <?php endif; ?>
                        <?php if ($sa_restore_allowed && (str_starts_with($b['filename'], 'database_') || str_starts_with($b['filename'], 'files_'))): ?>
                        <a href="restore.php" title="Restore"
                           class="icon-btn" style="border-color:rgba(220,38,38,0.25); color:var(--vs-danger); margin-left:5px;"
                           onclick="event.preventDefault(); document.getElementById('restoreFile').value='<?php echo esc($b['filename']); ?>'; document.getElementById('restoreForm').submit();">♻️</a>
                        <?php endif; ?>
                        <button type="submit" name="delete_backup" value="1"
                                title="<?php echo esc(t('btn_delete')); ?>" class="icon-btn icon-btn-danger"
                                style="border:none; margin-left:5px;"
                                onclick="setSingleDelete('<?php echo esc($b['filename']); ?>'); return vsConfirmSubmit(event, '<?php echo t('backup_confirm_delete') ?: 'Backup löschen?'; ?>')">🗑</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Hidden field für Einzel-Löschen innerhalb des Bulk-Forms -->
        <input type="hidden" name="filename" id="singleDeleteFilename" value="">
    </form>

    <?php endif; ?>
</div>

<!-- Email Modal -->
<div id="emailModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:var(--vs-surface); border-radius:16px; padding:32px; max-width:460px; width:100%; box-shadow:0 16px 48px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0;"><i class="ti ti-mail"></i> <?php echo t('backup_email_notification') ?: 'Email-Benachrichtigung'; ?></h3>
        <form method="POST">
            <?php echo Security::getCSRFInput(); ?>
            <div class="form-group">
                <label><?php echo t('bk_email_label'); ?></label>
                <input type="email" name="admin_email" value="<?php echo esc($savedEmail); ?>" placeholder="admin@example.com">
                <small><?php echo t('bk_email_hint'); ?></small>
            </div>
            <div class="form-actions">
                <button type="submit" name="save_email" class="vs-btn vs-btn-primary"><?php echo t('btn_save') ?: '<i class="ti ti-device-floppy"></i> Speichern'; ?></button>
                <button type="button" class="vs-btn vs-btn-secondary" onclick="document.getElementById('emailModal').style.display='none'"><?php echo t('btn_cancel'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Log Modal -->
<div id="logModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:var(--vs-surface); border-radius:16px; padding:32px; max-width:860px; width:100%; box-shadow:0 16px 48px rgba(0,0,0,0.2); max-height:80vh; display:flex; flex-direction:column;">
        <h3 style="margin-top:0; flex-shrink:0;"><i class="ti ti-clipboard-list"></i> <?php echo t('backup_cron_log') ?: 'Cron Log'; ?></h3>
        <pre style="flex:1; overflow:auto; background:#1e2837; color:#a8d8a8; padding:20px; border-radius:8px; font-size:12px; line-height:1.7; margin:0;"><?php echo esc($cronLog ?: t('bk_no_log')); ?></pre>
        <button type="button" class="vs-btn vs-btn-secondary" style="margin-top:16px; flex-shrink:0;"
                onclick="document.getElementById('logModal').style.display='none'"><?php echo t('btn_close') ?: '✖ Schließen'; ?></button>
    </div>
</div>

<script>
function toggleTile(label, checkboxId) {
    event.preventDefault();
    const cb = document.getElementById(checkboxId);
    cb.checked = !cb.checked;
    label.classList.toggle('tile-active', cb.checked);
}
['emailModal','logModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});

// Bulk-Select
function updateBulkBar() {
    const cbs = document.querySelectorAll('.row-cb');
    const checked = document.querySelectorAll('.row-cb:checked');
    const bar = document.getElementById('bulkBar');
    const countEl = document.getElementById('bulkCount');
    const allCb = document.getElementById('selectAllCb');

    if (countEl) countEl.textContent = checked.length + ' <?php echo t("backup_selected") ?: "ausgewählt"; ?>';
    if (bar) bar.classList.toggle('visible', checked.length > 0);
    if (allCb) allCb.indeterminate = checked.length > 0 && checked.length < cbs.length;
    if (allCb) allCb.checked = cbs.length > 0 && checked.length === cbs.length;
}

function selectAll(state) {
    document.querySelectorAll('.row-cb').forEach(cb => cb.checked = state);
    const allCb = document.getElementById('selectAllCb');
    if (allCb) allCb.checked = state;
    updateBulkBar();
}

function setSingleDelete(filename) {
    // Beim Einzel-Löschen alle Checkboxen deaktivieren damit nur filename gilt
    document.querySelectorAll('.row-cb').forEach(cb => cb.checked = false);
    document.getElementById('singleDeleteFilename').value = filename;
}

function confirmBulkDelete(ereignis) {
    const count = document.querySelectorAll('.row-cb:checked').length;
    if (count === 0) { vsAlert(<?php echo json_encode(t('backup_select_at_least_one')); ?>); return false; }
    return vsConfirmSubmit(ereignis, count + ' <?php echo t('backup_confirm_bulk_delete') ?: 'Backup(s) unwiderruflich löschen?'; ?>');
}
</script>

        </div> <!-- /.backend-main -->
<!-- Verstecktes Formular für Restore-Weiterleitung -->
<form method="POST" action="restore.php" id="restoreForm" style="display:none;">
    <?php echo Security::getCSRFInput(); ?>
    <input type="hidden" name="request_restore" value="1">
    <input type="hidden" name="filename" id="restoreFile" value="">
</form>

<?php
// layout/footer.php lädt lightbox - suppress 404 durch leere Dateien-Prüfung
include 'layout/footer_next_page.php';
?>
