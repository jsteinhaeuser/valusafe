<?php
/**
 * System-Information - ANGEPASST FÜR NEUES LAYOUT
 */

require_once 'config.php';
requireBackendAccess();

$pageTitle = t('sysi_title');

// System-Statistiken laden
$stats = getSystemStats();

// PHP Extensions prüfen
// Gemessen am 19.09.2026 gegen den Quelltext: ValuSafe benutzt acht
// Erweiterungen. Sechs davon sind tragend — ohne sie laeuft die Anwendung
// nicht. Die letzten beiden sind optional und beide mit extension_loaded()
// abgesichert, betreffen aber die HEIC-Umwandlung von iPhone-Fotos:
// fehlen sie, scheitert ein HEIC-Upload STILL, Wochen nach der Ursache.
// Deshalb stehen sie hier — eine Seite, die nur das Tragende zeigt, macht
// genau die Verluste unsichtbar, die man nicht bemerkt.
// Wo stuende die Fehlermeldung? — siehe fehlerprotokollStatus() in helpers.php
if (!function_exists('fehlerprotokollStatus')) {
    require_once __DIR__ . '/../helpers.php';
}
$fp = fehlerprotokollStatus(dirname(__DIR__));

$extensions = ['pdo', 'pdo_mysql', 'gd', 'fileinfo', 'mbstring', 'zip', 'json', 'curl',
               'exif', 'imagick'];
$extStatus = [];
foreach ($extensions as $ext) {
    $extStatus[$ext] = extension_loaded($ext);
}

// Konstanten aus der config.php pruefen
// Dazugekommen am 20.09.2026, nach zwei Tagen Fehlersuche: In der config.php
// des NAS hiess ALLOWED_EXTENSIONS seit Monaten ALLOWED_IMAGE_EXTENSIONS —
// umbenannt beim Beheben eines Namenskonflikts mit sync_agent.php, und zwar
// auf der falschen Seite. Der Code erwartete weiter den alten Namen. Es blieb
// folgenlos, weil auf dem NAS nie jemand ein Bild direkt hochgeladen hat;
// beim ersten Versuch gab es HTTP 500 ohne jede Meldung.
//
// Die config.php ist die EINZIGE Datei, die auf jeder Instanz absichtlich
// anders ist — und damit die einzige, die der Versions-Vergleich prinzipiell
// nicht pruefen kann. Diese Tabelle ist der Ersatz dafuer.
//
// Werte werden NICHT angezeigt, auch nicht gekuerzt. Nur ob die Konstante da
// ist. Drei davon sind Geheimnisse, und eine Seite, die ein Geheimnis zeigt,
// um seine Anwesenheit zu belegen, hat den Zweck verfehlt.
$konstanten = [
    'UPLOAD_DIR'            => t('sysi_c_upload'),
    'BACKUP_DIR'            => t('sysi_c_backup'),
    'MAX_FILE_SIZE'         => t('sysi_c_maxfile'),
    'ALLOWED_IMAGE_TYPES'   => t('sysi_c_mime'),
    'ALLOWED_EXTENSIONS'    => t('sysi_c_ext'),
    'CSRF_TOKEN_NAME'       => t('sysi_c_csrf'),
    'SESSION_LIFETIME'      => t('sysi_c_session'),
    'APP_LANG'              => t('sysi_c_lang'),
    'BACKUP_RETENTION_DAYS' => t('sysi_c_retention'),
    'SUPERADMIN_USERNAME'   => t('sysi_c_superadmin'),
    'APP_URL'               => t('sysi_c_appurl'),
    'BACKUP_TOKEN'          => t('sysi_c_backuptoken'),
    'TOOLS_TOKEN'           => t('sysi_c_toolstoken'),
    'FILE_SYNC_TOKEN'       => t('sysi_c_synctoken'),
];
// Zwei Konstanten haben im Code einen Standardwert (header_next*.php: 'de',
// cron_backup.php/backup.php: 7 Tage). Fehlen sie - aeltere config.php vor
// setup.php 4.3 -, laeuft alles; rot "FEHLT" war irrefuehrend (07.11.2026).
$konstStandard = [
    'APP_LANG'              => t('sysi_default_lang'),
    'BACKUP_RETENTION_DAYS' => t('sysi_default_retention'),
];
$konstStatus = [];
foreach ($konstanten as $k => $zweck) {
    $konstStatus[$k] = ['da' => defined($k), 'zweck' => $zweck, 'standard' => $konstStandard[$k] ?? null];
}

// Verzeichnis-Größen
$uploadDir = __DIR__ . '/../upload/';
$docsDir = __DIR__ . '/../documents/';
$uploadSize = 0;
$docsSize = 0;
$uploadCount = 0;
$docsCount = 0;

if (is_dir($uploadDir)) {
    $files = glob($uploadDir . '*');
    if (is_array($files)) {
        foreach ($files as $file) {
            if (is_file($file)) {
                $uploadSize += filesize($file);
                $uploadCount++;
            }
        }
    }
}

if (is_dir($docsDir)) {
    $files = glob($docsDir . '*');
    if (is_array($files)) {
        foreach ($files as $file) {
            if (is_file($file)) {
                $docsSize += filesize($file);
                $docsCount++;
            }
        }
    }
}

// Layout laden
include 'layout/header_next_page.php';
?>

<!-- Main Content -->
<main class="backend-main">
    
    <!-- PHP Information -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2>🐘 <?php echo htmlspecialchars(t('sysi_h_php')); ?></h2>
        
        <div class="dashboard-grid" style="margin-top: 20px;">
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 28px; color: var(--vs-accent);">
                            <?php echo $stats['php_version']; ?>
                        </div>
                        <div class="widget-label"><?php echo htmlspecialchars(t('sysi_php_version')); ?></div>
                    </div>
                    <div class="widget-icon">🐘</div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 28px; color: var(--vs-warning);">
                            <?php echo ini_get('memory_limit'); ?>
                        </div>
                        <div class="widget-label"><?php echo htmlspecialchars(t('sysi_memory')); ?></div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-device-floppy"></i></div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 28px; color: var(--vs-accent);">
                            <?php echo ini_get('upload_max_filesize'); ?>
                        </div>
                        <div class="widget-label"><?php echo htmlspecialchars(t('sysi_max_upload')); ?></div>
                    </div>
                    <div class="widget-icon">📤</div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 28px; color: var(--vs-danger);">
                            <?php echo ini_get('max_execution_time'); ?>s
                        </div>
                        <div class="widget-label"><?php echo htmlspecialchars(t('sysi_max_exec')); ?></div>
                    </div>
                    <div class="widget-icon">⏱️</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Konstanten der config.php -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2>🧩 <?php echo htmlspecialchars(t('sysi_h_config')); ?></h2>

        <table class="backend-table">
            <thead>
                <tr>
                    <th><?php echo htmlspecialchars(t('sysi_constant')); ?></th>
                    <th><?php echo htmlspecialchars(t('sysi_purpose')); ?></th>
                    <th style="width: 150px;"><?php echo htmlspecialchars(t('sysi_status')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($konstStatus as $name => $info): ?>
                    <tr>
                        <td>
                            <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px; font-family: 'Courier New', monospace;">
                                <?php echo htmlspecialchars($name); ?>
                            </code>
                        </td>
                        <td><?php echo htmlspecialchars($info['zweck']); ?></td>
                        <td>
                            <?php if ($info['da']): ?>
                                <span class="badge badge-success"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> <?php echo htmlspecialchars(t('sysi_set')); ?></span>
                            <?php elseif ($info['standard'] !== null): ?>
                                <span class="badge badge-secondary"><i class="ti ti-info-circle" aria-hidden="true"></i> <?php echo htmlspecialchars($info['standard']); ?></span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> <?php echo htmlspecialchars(t('sysi_missing_caps')); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Fehlerprotokoll -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2>🩺 <?php echo htmlspecialchars(t('sysi_h_errlog')); ?></h2>
        <p style="color: var(--vs-text-muted); margin: -6px 0 14px;">
            <?php echo htmlspecialchars(t('sysi_errlog_intro')); ?>
        </p>

        <table class="backend-table">
            <tbody>
                <tr>
                    <td style="width: 38%;"><?php echo htmlspecialchars(t('sysi_sapi')); ?></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($fp['sapi']); ?></code>
                    </td>
                </tr>
                <tr>
                    <td><?php echo htmlspecialchars(t('sysi_userini')); ?></td>
                    <td>
                        <?php // Seit 4.3.29 wird keine .user.ini mehr ausgeliefert: fehlerziel.php
                              // setzt Anzeige und Ziel zur Laufzeit, in jeder Betriebsart. Eine
                              // noch vorhandene Datei ist ein Rest - die bis 4.3.28 mitgelieferte
                              // schaltete display_errors EIN. ?>
                        <?php if ($fp['userini_da']): ?>
                            <span class="badge badge-warning"><i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i> <?php echo htmlspecialchars(t('sysi_userini_left')); ?></span>
                        <?php else: ?>
                            <span class="badge badge-success"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> <?php echo htmlspecialchars(t('sysi_userini_none')); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php echo htmlspecialchars(t('sysi_log_errors')); ?></td>
                    <td>
                        <?php if ($fp['protokollieren']): ?>
                            <span class="badge badge-success"><?php echo htmlspecialchars(t('sysi_on')); ?></span>
                        <?php else: ?>
                            <span class="badge badge-danger"><?php echo htmlspecialchars(t('sysi_log_off')); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php echo htmlspecialchars(t('sysi_display_errors')); ?></td>
                    <td>
                        <?php if ($fp['anzeigen']): ?>
                            <span class="badge badge-warning"><?php echo htmlspecialchars(t('sysi_display_on')); ?></span>
                        <?php else: ?>
                            <span class="badge badge-success"><?php echo htmlspecialchars(t('sysi_off')); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php echo htmlspecialchars(t('sysi_reporting')); ?></td>
                    <td>
                        <?php if ($fp['stufe_alle']): ?>
                            <span class="badge badge-success"><?php echo htmlspecialchars(t('sysi_reporting_all')); ?></span>
                        <?php else: ?>
                            <span class="badge badge-warning"><?php echo htmlspecialchars(str_replace('%s', (int)$fp['stufe'], t('sysi_reporting_some'))); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($GLOBALS['fehlerziel']['grund'])): ?>
                <tr>
                    <td><?php echo htmlspecialchars(t('sysi_own_file')); ?></td>
                    <td>
                        <span class="badge badge-danger"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i>
                            <?php echo htmlspecialchars(str_replace('%s', $GLOBALS['fehlerziel']['grund'], t('sysi_not_possible'))); ?></span>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td><?php echo htmlspecialchars(t('sysi_target')); ?></td>
                    <td>
                        <?php if ($fp['ziel_art'] === 'server'): ?>
                            <span class="badge badge-warning"><?php echo htmlspecialchars(t('sysi_target_webserver')); ?></span>
                        <?php elseif ($fp['ziel_art'] === 'syslog'): ?>
                            <span class="badge badge-warning"><?php echo htmlspecialchars(t('sysi_target_syslog')); ?></span>
                        <?php else: ?>
                            <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px; word-break: break-all;"><?php echo htmlspecialchars($fp['ziel']); ?></code>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($fp['ziel_art'] === 'datei'): ?>
                <tr>
                    <td><?php echo htmlspecialchars(t('sysi_writable')); ?></td>
                    <td>
                        <?php if ($fp['ziel_schreibbar']): ?>
                            <span class="badge badge-success"><?php echo htmlspecialchars(t('sysi_yes')); ?></span>
                        <?php else: ?>
                            <span class="badge badge-danger"><?php echo htmlspecialchars(t('sysi_not_writable')); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php echo htmlspecialchars(t('sysi_file_state')); ?></td>
                    <td>
                        <?php if ($fp['ziel_da']): ?>
                            <?php echo number_format($fp['ziel_groesse'], 0, ',', '.'); ?> Bytes,
                            <?php echo htmlspecialchars(t('sysi_last_change')); ?> <?php echo date('d.m.Y H:i', $fp['ziel_datum']); ?>
                        <?php else: ?>
                            <span style="color: var(--vs-text-muted);"><?php echo htmlspecialchars(t('sysi_not_created')); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PHP Extensions -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2>🔌 <?php echo htmlspecialchars(t('sysi_h_ext')); ?></h2>
        
        <table class="backend-table">
            <thead>
                <tr>
                    <th><?php echo htmlspecialchars(t('sysi_extension')); ?></th>
                    <th style="width: 150px;"><?php echo htmlspecialchars(t('sysi_status')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($extStatus as $ext => $loaded): ?>
                    <tr>
                        <td>
                            <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px; font-family: 'Courier New', monospace;">
                                <?php echo $ext; ?>
                            </code>
                        </td>
                        <td>
                            <?php if ($loaded): ?>
                                <span class="badge badge-success"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> <?php echo htmlspecialchars(t('sysi_loaded')); ?></span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> <?php echo htmlspecialchars(t('sysi_not_found')); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Datenbank-Information -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2>🗄️ <?php echo htmlspecialchars(t('sysi_h_db')); ?></h2>
        
        <div class="dashboard-grid" style="margin-top: 20px;">
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 24px; color: var(--vs-accent);">
                            <?php echo explode('-', $stats['mysql_version'])[0]; ?>
                        </div>
                        <div class="widget-label">🐬 <?php echo htmlspecialchars(t('sysi_mysql_version')); ?></div>
                    </div>
                    <div class="widget-icon">🐬</div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 24px; color: var(--vs-success);">
                            <?php echo formatBytes($stats['db_size']); ?>
                        </div>
                        <div class="widget-label">💽 <?php echo htmlspecialchars(t('sysi_db_size')); ?></div>
                    </div>
                    <div class="widget-icon">💽</div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 18px; color: var(--vs-text-muted);">
                            <?php echo DB_NAME; ?>
                        </div>
                        <div class="widget-label"><i class="ti ti-chart-bar"></i> <?php echo htmlspecialchars(t('sysi_db_name')); ?></div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-chart-bar"></i></div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 18px; color: var(--vs-accent);">
                            <?php echo DB_HOST; ?>
                        </div>
                        <div class="widget-label"><i class="ti ti-world"></i> <?php echo htmlspecialchars(t('sysi_db_host')); ?></div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-world"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Speicher-Nutzung -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2><i class="ti ti-device-floppy"></i> <?php echo htmlspecialchars(t('sysi_h_storage')); ?></h2>
        
        <div class="dashboard-grid" style="margin-top: 20px;">
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 28px; color: var(--vs-success);">
                            <?php echo formatBytes($uploadSize); ?>
                        </div>
                        <div class="widget-label"><i class="ti ti-photo"></i> <?php echo htmlspecialchars(t('sysi_upload_dir')); ?></div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-photo"></i></div>
                </div>
                <div class="widget-footer">
                    <div style="font-size: 12px; color: var(--vs-text-muted);">
                        <?php echo number_format($uploadCount, 0, ',', '.'); ?> <?php echo htmlspecialchars(t('sysi_files')); ?>
                    </div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 28px; color: var(--vs-accent);">
                            <?php echo formatBytes($docsSize); ?>
                        </div>
                        <div class="widget-label">📄 <?php echo htmlspecialchars(t('sysi_documents')); ?></div>
                    </div>
                    <div class="widget-icon">📄</div>
                </div>
                <div class="widget-footer">
                    <div style="font-size: 12px; color: var(--vs-text-muted);">
                        <?php echo number_format($docsCount, 0, ',', '.'); ?> <?php echo htmlspecialchars(t('sysi_files')); ?>
                    </div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 28px; color: var(--vs-warning);">
                            <?php echo formatBytes($uploadSize + $docsSize + $stats['db_size']); ?>
                        </div>
                        <div class="widget-label">💿 <?php echo htmlspecialchars(t('sysi_total')); ?></div>
                    </div>
                    <div class="widget-icon">💿</div>
                </div>
                <div class="widget-footer">
                    <div style="font-size: 12px; color: var(--vs-text-muted);">
                        <?php echo htmlspecialchars(t('sysi_total_parts')); ?>
                    </div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 28px; color: var(--vs-text-muted);">
                            <?php echo number_format($uploadCount + $docsCount, 0, ',', '.'); ?>
                        </div>
                        <div class="widget-label"><i class="ti ti-folder"></i> <?php echo htmlspecialchars(t('sysi_files')); ?></div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-folder"></i></div>
                </div>
                <div class="widget-footer">
                    <div style="font-size: 12px; color: var(--vs-text-muted);">
                        <?php echo htmlspecialchars(t('sysi_total_system')); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Server-Information -->
    <div class="activity-timeline">
        <h2><i class="ti ti-device-desktop"></i> <?php echo htmlspecialchars(t('sysi_h_server')); ?></h2>
        
        <table class="backend-table">
            <tbody>
                <tr>
                    <td style="width: 250px;"><strong><?php echo htmlspecialchars(t('sysi_srv_software')); ?></strong></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px;">
                            <?php echo $_SERVER['SERVER_SOFTWARE'] ?? t('sysi_unknown'); ?>
                        </code>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php echo htmlspecialchars(t('sysi_srv_name')); ?></strong></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px;">
                            <?php echo $_SERVER['SERVER_NAME'] ?? t('sysi_unknown'); ?>
                        </code>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php echo htmlspecialchars(t('sysi_srv_addr')); ?></strong></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px;">
                            <?php echo $_SERVER['SERVER_ADDR'] ?? t('sysi_unknown'); ?>
                        </code>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php echo htmlspecialchars(t('sysi_srv_port')); ?></strong></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px;">
                            <?php echo $_SERVER['SERVER_PORT'] ?? t('sysi_unknown'); ?>
                        </code>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php echo htmlspecialchars(t('sysi_srv_time')); ?></strong></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px;">
                            <?php echo date('d.m.Y H:i:s'); ?>
                        </code>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php echo htmlspecialchars(t('sysi_timezone')); ?></strong></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px;">
                            <?php echo date_default_timezone_get(); ?>
                        </code>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php echo htmlspecialchars(t('sysi_docroot')); ?></strong></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            <?php echo $_SERVER['DOCUMENT_ROOT'] ?? t('sysi_unknown'); ?>
                        </code>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
<!-- Security Headers Status -->
<div class="activity-timeline" style="margin-bottom: 30px;">
    <h2><i class="ti ti-lock"></i> <?php echo htmlspecialchars(t('sysi_h_headers')); ?></h2>
    
    <table class="backend-table" style="margin-left: 0;">
        <tbody>
            <?php
            // Security Headers testen
            $securityHeaders = SecurityHeaders::getSetHeaders();
            
            $expectedHeaders = [
                'Content-Security-Policy' => 'XSS Protection',
                'X-Frame-Options' => 'Clickjacking Protection',
                'X-Content-Type-Options' => 'MIME-Sniffing Protection',
                'X-XSS-Protection' => 'Legacy XSS Filter',
                'Referrer-Policy' => 'Privacy Protection',
                'Permissions-Policy' => 'Feature Control'
            ];
            
            // HSTS nur bei HTTPS erwarten
            $isHTTPS = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                       $_SERVER['SERVER_PORT'] == 443 ||
                       (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');
            
            if ($isHTTPS) {
                $expectedHeaders['Strict-Transport-Security'] = 'HTTPS Enforcement';
            }
            
            $setHeaderNames = array_map(function($h) {
                return trim(explode(':', $h, 2)[0]);
            }, $securityHeaders);
            
            $allOK = true;
            foreach ($expectedHeaders as $name => $description) {
                $isSet = in_array($name, $setHeaderNames);
                if (!$isSet) $allOK = false;
                
                $icon = $isSet ? '<i class="ti ti-circle-check" style="color:var(--vs-success);"></i>' : '<i class="ti ti-circle-x" style="color:var(--vs-danger);"></i>';
                $badge = $isSet ? 
                    '<span class="badge badge-success"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> ' . htmlspecialchars(t('sysi_active')) . '</span>' : 
                    '<span class="badge badge-danger"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> ' . htmlspecialchars(t('sysi_missing')) . '</span>';
                
                echo '<tr>';
                echo '<td style="width: 250px;"><strong>' . htmlspecialchars($name) . '</strong><br><small style="color: #666;">' . htmlspecialchars($description) . '</small></td>';
                echo '<td>' . $badge . '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
    
    <div style="margin-top: 15px; padding: 10px; background: <?php echo $allOK ? '#d4edda' : '#fff3cd'; ?>; border-radius: 4px;">
        <?php if ($allOK): ?>
            <strong style="color: var(--vs-success-text);">✓ <?php echo htmlspecialchars(t('sysi_headers_ok')); ?></strong>
        <?php else: ?>
            <strong style="color: var(--vs-warning-text);"><i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i> <?php echo htmlspecialchars(t('sysi_headers_missing')); ?></strong>
            <br><small><?php echo htmlspecialchars(t('sysi_headers_check')); ?></small>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 10px;">
    </div>
</div>

</main>

<?php include 'layout/footer_next_page.php'; ?>
