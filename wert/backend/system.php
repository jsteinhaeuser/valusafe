<?php
/**
 * System-Information - ANGEPASST FÜR NEUES LAYOUT
 */

require_once 'config.php';
requireBackendAccess();

$pageTitle = 'System-Information';

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
    'UPLOAD_DIR'            => 'Ablage der Bilder',
    'BACKUP_DIR'            => 'Ablage der Sicherungen',
    'MAX_FILE_SIZE'         => 'Obergrenze je Bild',
    'ALLOWED_IMAGE_TYPES'   => 'erlaubte MIME-Typen',
    'ALLOWED_EXTENSIONS'    => 'erlaubte Dateiendungen',
    'CSRF_TOKEN_NAME'       => 'Name des Formular-Tokens',
    'SESSION_LIFETIME'      => 'Sitzungsdauer',
    'APP_LANG'              => 'Vorgabesprache',
    'BACKUP_RETENTION_DAYS' => 'Aufbewahrung der Sicherungen',
    'SUPERADMIN_USERNAME'   => 'Name des Superadministrators',
    'BACKUP_TOKEN'          => 'fuer cron_backup.php (Wert bleibt verborgen)',
    'TOOLS_TOKEN'           => 'fuer version_agent und schema_agent (Wert bleibt verborgen)',
    'FILE_SYNC_TOKEN'       => 'fuer sync_agent.php (Wert bleibt verborgen)',
];
$konstStatus = [];
foreach ($konstanten as $k => $zweck) {
    $konstStatus[$k] = ['da' => defined($k), 'zweck' => $zweck];
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
        <h2>🐘 PHP-Information</h2>
        
        <div class="dashboard-grid" style="margin-top: 20px;">
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 28px; color: var(--vs-accent);">
                            <?php echo $stats['php_version']; ?>
                        </div>
                        <div class="widget-label">PHP Version</div>
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
                        <div class="widget-label">Memory Limit</div>
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
                        <div class="widget-label">Max Upload</div>
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
                        <div class="widget-label">Max Execution</div>
                    </div>
                    <div class="widget-icon">⏱️</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Konstanten der config.php -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2>🧩 Konfiguration (config.php)</h2>

        <table class="backend-table">
            <thead>
                <tr>
                    <th>Konstante</th>
                    <th>Wofür</th>
                    <th style="width: 150px;">Status</th>
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
                                <span class="badge badge-success"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Gesetzt</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> FEHLT</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Fehlerprotokoll -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2>🩺 Fehlerprotokoll — wo stünde die Meldung?</h2>
        <p style="color: var(--vs-text-muted); margin: -6px 0 14px;">
            Diese Angaben sind auf jeder Instanz anders. Am 18./19.09.2026 kostete es zwei Tage,
            einen Fehler überhaupt zu <em>sehen</em>; behoben war er in zehn Minuten.
        </p>

        <table class="backend-table">
            <tbody>
                <tr>
                    <td style="width: 38%;">PHP-Betriebsart</td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($fp['sapi']); ?></code>
                    </td>
                </tr>
                <tr>
                    <td>.user.ini im Anwendungsverzeichnis</td>
                    <td>
                        <?php // Seit 4.3.29 wird keine .user.ini mehr ausgeliefert: fehlerziel.php
                              // setzt Anzeige und Ziel zur Laufzeit, in jeder Betriebsart. Eine
                              // noch vorhandene Datei ist ein Rest - die bis 4.3.28 mitgelieferte
                              // schaltete display_errors EIN. ?>
                        <?php if ($fp['userini_da']): ?>
                            <span class="badge badge-warning"><i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i> liegt noch da — seit 4.3.29 überflüssig, bitte entfernen</span>
                        <?php else: ?>
                            <span class="badge badge-success"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> keine — nicht nötig, fehlerziel.php regelt das</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Fehler protokollieren (log_errors)</td>
                    <td>
                        <?php if ($fp['protokollieren']): ?>
                            <span class="badge badge-success">An</span>
                        <?php else: ?>
                            <span class="badge badge-danger">AUS — keine Meldung wird festgehalten</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Fehler im Browser anzeigen (display_errors)</td>
                    <td>
                        <?php if ($fp['anzeigen']): ?>
                            <span class="badge badge-warning">An — auf einer öffentlich erreichbaren Instanz unerwünscht</span>
                        <?php else: ?>
                            <span class="badge badge-success">Aus</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Meldungsstufe (error_reporting)</td>
                    <td>
                        <?php if ($fp['stufe_alle']): ?>
                            <span class="badge badge-success">alle Meldungen (E_ALL)</span>
                        <?php else: ?>
                            <span class="badge badge-warning">nicht alle — Wert <?php echo (int)$fp['stufe']; ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($GLOBALS['fehlerziel']['grund'])): ?>
                <tr>
                    <td>Umstellung auf eine eigene Datei</td>
                    <td>
                        <span class="badge badge-danger"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i>
                            nicht möglich — <?php echo htmlspecialchars($GLOBALS['fehlerziel']['grund']); ?></span>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td>Ziel</td>
                    <td>
                        <?php if ($fp['ziel_art'] === 'server'): ?>
                            <span class="badge badge-warning">Protokoll des Webservers — kein eigener Pfad gesetzt</span>
                        <?php elseif ($fp['ziel_art'] === 'syslog'): ?>
                            <span class="badge badge-warning">syslog des Systems</span>
                        <?php else: ?>
                            <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px; word-break: break-all;"><?php echo htmlspecialchars($fp['ziel']); ?></code>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($fp['ziel_art'] === 'datei'): ?>
                <tr>
                    <td>Datei beschreibbar</td>
                    <td>
                        <?php if ($fp['ziel_schreibbar']): ?>
                            <span class="badge badge-success">ja</span>
                        <?php else: ?>
                            <span class="badge badge-danger">NEIN — es kann nichts hineingeschrieben werden</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Stand der Datei</td>
                    <td>
                        <?php if ($fp['ziel_da']): ?>
                            <?php echo number_format($fp['ziel_groesse'], 0, ',', '.'); ?> Bytes,
                            letzte Änderung <?php echo date('d.m.Y H:i', $fp['ziel_datum']); ?>
                        <?php else: ?>
                            <span style="color: var(--vs-text-muted);">noch nicht angelegt — bisher gab es keine Meldung, oder sie ging anderswohin</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PHP Extensions -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2>🔌 PHP Extensions</h2>
        
        <table class="backend-table">
            <thead>
                <tr>
                    <th>Extension</th>
                    <th style="width: 150px;">Status</th>
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
                                <span class="badge badge-success"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Geladen</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> Nicht gefunden</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Datenbank-Information -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2>🗄️ Datenbank-Information</h2>
        
        <div class="dashboard-grid" style="margin-top: 20px;">
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 24px; color: var(--vs-accent);">
                            <?php echo explode('-', $stats['mysql_version'])[0]; ?>
                        </div>
                        <div class="widget-label">🐬 MySQL Version</div>
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
                        <div class="widget-label">💽 DB-Größe</div>
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
                        <div class="widget-label"><i class="ti ti-chart-bar"></i> DB-Name</div>
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
                        <div class="widget-label"><i class="ti ti-world"></i> DB-Host</div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-world"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Speicher-Nutzung -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2><i class="ti ti-device-floppy"></i> Speicher-Nutzung</h2>
        
        <div class="dashboard-grid" style="margin-top: 20px;">
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 28px; color: var(--vs-success);">
                            <?php echo formatBytes($uploadSize); ?>
                        </div>
                        <div class="widget-label"><i class="ti ti-photo"></i> Upload-Verzeichnis</div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-photo"></i></div>
                </div>
                <div class="widget-footer">
                    <div style="font-size: 12px; color: var(--vs-text-muted);">
                        <?php echo number_format($uploadCount, 0, ',', '.'); ?> Dateien
                    </div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 28px; color: var(--vs-accent);">
                            <?php echo formatBytes($docsSize); ?>
                        </div>
                        <div class="widget-label">📄 Dokumente</div>
                    </div>
                    <div class="widget-icon">📄</div>
                </div>
                <div class="widget-footer">
                    <div style="font-size: 12px; color: var(--vs-text-muted);">
                        <?php echo number_format($docsCount, 0, ',', '.'); ?> Dateien
                    </div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 28px; color: var(--vs-warning);">
                            <?php echo formatBytes($uploadSize + $docsSize + $stats['db_size']); ?>
                        </div>
                        <div class="widget-label">💿 Gesamt</div>
                    </div>
                    <div class="widget-icon">💿</div>
                </div>
                <div class="widget-footer">
                    <div style="font-size: 12px; color: var(--vs-text-muted);">
                        Uploads + Dokumente + Datenbank
                    </div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 28px; color: var(--vs-text-muted);">
                            <?php echo number_format($uploadCount + $docsCount, 0, ',', '.'); ?>
                        </div>
                        <div class="widget-label"><i class="ti ti-folder"></i> Dateien</div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-folder"></i></div>
                </div>
                <div class="widget-footer">
                    <div style="font-size: 12px; color: var(--vs-text-muted);">
                        Gesamt im System
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Server-Information -->
    <div class="activity-timeline">
        <h2><i class="ti ti-device-desktop"></i> Server-Information</h2>
        
        <table class="backend-table">
            <tbody>
                <tr>
                    <td style="width: 250px;"><strong>Server Software</strong></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px;">
                            <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
                        </code>
                    </td>
                </tr>
                <tr>
                    <td><strong>Server Name</strong></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px;">
                            <?php echo $_SERVER['SERVER_NAME'] ?? 'Unknown'; ?>
                        </code>
                    </td>
                </tr>
                <tr>
                    <td><strong>Server Adresse</strong></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px;">
                            <?php echo $_SERVER['SERVER_ADDR'] ?? 'Unknown'; ?>
                        </code>
                    </td>
                </tr>
                <tr>
                    <td><strong>Server Port</strong></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px;">
                            <?php echo $_SERVER['SERVER_PORT'] ?? 'Unknown'; ?>
                        </code>
                    </td>
                </tr>
                <tr>
                    <td><strong>Server Zeit</strong></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px;">
                            <?php echo date('d.m.Y H:i:s'); ?>
                        </code>
                    </td>
                </tr>
                <tr>
                    <td><strong>Zeitzone</strong></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px;">
                            <?php echo date_default_timezone_get(); ?>
                        </code>
                    </td>
                </tr>
                <tr>
                    <td><strong>Document Root</strong></td>
                    <td>
                        <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?>
                        </code>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
<!-- Security Headers Status -->
<div class="activity-timeline" style="margin-bottom: 30px;">
    <h2><i class="ti ti-lock"></i> Security Headers Status</h2>
    
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
                    '<span class="badge badge-success"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Aktiv</span>' : 
                    '<span class="badge badge-danger"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> Fehlt</span>';
                
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
            <strong style="color: var(--vs-success-text);">✓ Alle Security Headers aktiv!</strong>
        <?php else: ?>
            <strong style="color: var(--vs-warning-text);"><i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i> Einige Security Headers fehlen</strong>
            <br><small>Prüfe die SecurityHeaders.php Konfiguration</small>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 10px;">
    </div>
</div>

</main>

<?php include 'layout/footer_next_page.php'; ?>
