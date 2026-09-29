<?php
/**
 * Log-Viewer - Vereint DB-Logs und Datei-Logs
 * Zeigt sowohl security_log Tabelle als auch /logs/security.log an
 */

require_once __DIR__ . '/config.php';
requireBackendAccess();

$pageTitle = t('syslog_title_extended');

// Tab auswählen
$activeTab = $_GET['tab'] ?? 'database';
$source = $_GET['source'] ?? 'db'; // db oder file

// Limit
$limit = intval($_GET['limit'] ?? 50);

$logs = [];
$stats = [];

// ========================================
// DATENBANK-LOGS
// ========================================
if ($source === 'db') {
    try {
        if ($activeTab === 'security') {
            $logs = $db->select("
                SELECT sl.*, u.username AS benutzername
                FROM security_log sl
                LEFT JOIN users u ON sl.user_id = u.id
                ORDER BY sl.created_at DESC
                LIMIT ?
            ", [$limit]);
            
            $stats['total'] = $db->selectOne("SELECT COUNT(*) as count FROM security_log")['count'];
            $stats['success'] = $db->selectOne("SELECT COUNT(*) as count FROM security_log WHERE event_type = 'login_success'")['count'];
            $stats['failed'] = $db->selectOne("SELECT COUNT(*) as count FROM security_log WHERE event_type = 'login_failed'")['count'];
        }
    } catch (Exception $e) {
        $error = 'Fehler beim Laden der DB-Logs: ' . $e->getMessage();
    }
}

// ========================================
// DATEI-LOGS
// ========================================
if ($source === 'file') {
    $logFile = __DIR__ . '/../logs/security.log';
    
    if (file_exists($logFile)) {
        try {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $allLogs = [];
            
            foreach ($lines as $line) {
                $event = json_decode($line, true);
                if ($event) {
                    $allLogs[] = $event;
                }
            }
            
            // Neueste zuerst
            $allLogs = array_reverse($allLogs);
            
            // Filtern nach Tab
            if ($activeTab === 'security') {
                $securityEvents = ['login_success', 'login_failed', 'logout', 'password_change'];
                $logs = array_filter($allLogs, fn($e) => in_array($e['event'] ?? '', $securityEvents));
            } else {
                // Alle anderen Events
                $securityEvents = ['login_success', 'login_failed', 'logout', 'password_change'];
                $logs = array_filter($allLogs, fn($e) => !in_array($e['event'] ?? '', $securityEvents));
            }
            
            // Limit anwenden
            $logs = array_slice($logs, 0, $limit);
            
            // Statistiken
            $stats['total'] = count($allLogs);
            $stats['success'] = count(array_filter($allLogs, fn($e) => ($e['event'] ?? '') === 'login_success'));
            $stats['failed'] = count(array_filter($allLogs, fn($e) => ($e['event'] ?? '') === 'login_failed'));
            
        } catch (Exception $e) {
            $error = 'Fehler beim Laden der Datei-Logs: ' . $e->getMessage();
        }
    } else {
        $error = 'security.log Datei nicht gefunden: ' . $logFile;
    }
}

// Layout laden
include 'layout/header_next_page.php';
?>

<!-- Main Content -->
<main class="backend-main">
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <strong><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> <?php echo t('syslog_error'); ?></strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Quelle wählen -->
    <div class="source-selector" style="margin-bottom: 20px;">
        <div style="display: flex; gap: 15px; padding: 15px; background: var(--vs-surface-2); border-radius: 8px;">
            <strong><?php echo t('syslog_source'); ?>:</strong>
            <label style="cursor: pointer;">
                <input type="radio" name="source" value="db" <?php echo $source === 'db' ? 'checked' : ''; ?> onchange="changeSource('db')">
                🗄️ <?php echo t('syslog_source_db'); ?>
            </label>
            <label style="cursor: pointer;">
                <input type="radio" name="source" value="file" <?php echo $source === 'file' ? 'checked' : ''; ?> onchange="changeSource('file')">
                📄 <?php echo t('syslog_source_file'); ?>
            </label>
            <?php /* Der Knopf "In DB importieren" ist entfallen: er verwies auf
                 migrate_security_logs.php, und diese Datei gibt es nicht. */ ?>
        </div>
    </div>
    
    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <a href="?source=<?php echo $source; ?>&tab=activity" class="tab-link <?php echo $activeTab === 'activity' ? 'active' : ''; ?>">
            <i class="ti ti-chart-bar"></i> <?php echo t('syslog_tab_activity'); ?>
        </a>
        <a href="?source=<?php echo $source; ?>&tab=security" class="tab-link <?php echo $activeTab === 'security' ? 'active' : ''; ?>">
            <i class="ti ti-lock"></i> <?php echo t('syslog_tab_security'); ?>
        </a>
    </div>

    <!-- Diese Seite zeigt nur an. Bis 4.3.29 stand hier "Eintraege loeschen
         oder aufraeumen" mit Link auf activity_log.php - die Seite verwaltet
         aber die Tabelle activity_log, nicht die Datei und nicht security_log,
         die hier angezeigt werden. -->
    <div style="margin: 14px 0 20px; font-size: 13px; color: var(--vs-text-muted);">
        <i class="ti ti-info-circle"></i>
        <?php echo t('syslog_view_only'); ?>
        <?php echo t($source === 'file' ? 'syslog_file_note' : 'syslog_db_note'); ?>
        <a href="activity_log.php" style="color: var(--vs-accent);"><?php echo t('syslog_activity_link'); ?></a>
    </div>
    
    <!-- Statistik-Karten -->
    <?php if (!empty($stats)): ?>
    <div class="dashboard-grid" style="margin-bottom: 30px;">
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value"><?php echo number_format($stats['total'], 0, ',', '.'); ?></div>
                    <div class="widget-label"><i class="ti ti-chart-bar"></i> <?php echo t('syslog_total'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-chart-bar"></i></div>
            </div>
        </div>
        
        <?php if ($activeTab === 'security'): ?>
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value" style="color: var(--vs-success);"><?php echo number_format($stats['success'], 0, ',', '.'); ?></div>
                    <div class="widget-label"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> <?php echo t('syslog_logins_ok'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i></div>
            </div>
        </div>
        
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value" style="color: var(--vs-danger);"><?php echo number_format($stats['failed'], 0, ',', '.'); ?></div>
                    <div class="widget-label"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> <?php echo t('syslog_logins_failed'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Logs-Tabelle -->
    <div class="activity-timeline">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2><?php echo $activeTab === 'security' ? '<i class="ti ti-lock"></i> ' . t('syslog_tab_security') : '<i class="ti ti-chart-bar"></i> ' . t('syslog_tab_activity'); ?></h2>
            <div>
                <select id="limitSelect" onchange="changeLimit()" class="form-control" style="width: auto; display: inline-block;">
                    <?php foreach ([25, 50, 100, 500] as $_n): ?>
                        <option value="<?php echo $_n; ?>" <?php echo $limit == $_n ? 'selected' : ''; ?>><?php echo sprintf(t('syslog_entries_n'), $_n); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <?php if (count($logs) === 0): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 15px;"><i class="ti ti-chart-bar"></i></div>
                <p><?php echo t('syslog_none_found'); ?></p>
            </div>
        <?php else: ?>
            <table class="backend-table">
                <thead>
                    <tr>
                        <th style="width: 180px;"><?php echo t('act_col_time'); ?></th>
                        <th style="width: 150px;"><?php echo t('act_col_user'); ?></th>
                        <th style="width: 150px;"><?php echo t('syslog_col_event'); ?></th>
                        <th><?php echo t('log_col_details'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <?php 
                                if ($source === 'db') {
                                    $time = $activeTab === 'security' ? $log['created_at'] : $log['zeitstempel'];
                                    echo '<span title="' . date('d.m.Y H:i:s', strtotime($time)) . '">' . timeAgo($time) . '</span>';
                                } else {
                                    echo date('d.m.Y H:i:s', strtotime($log['timestamp']));
                                }
                                ?>
                            </td>
                            <td>
                                <strong>
                                    <?php 
                                    if ($source === 'db') {
                                        echo htmlspecialchars($log['benutzername'] ?? 'System');
                                    } else {
                                        echo htmlspecialchars($log['user'] ?? 'anonymous');
                                    }
                                    ?>
                                </strong>
                            </td>
                            <td>
                                <?php 
                                $eventType = $source === 'db' ? ($log['event_type'] ?? '') : ($log['event'] ?? '');
                                
                                $badges = [
                                    'login_success' => '<span class="badge badge-success"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> ' . t('syslog_event_login_ok') . '</span>',
                                    'login_failed' => '<span class="badge badge-danger"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> ' . t('syslog_event_login_failed') . '</span>',
                                    'logout' => '<span class="badge badge-secondary">🚪 ' . t('syslog_event_logout') . '</span>',
                                    'database_error' => '<span class="badge badge-danger">💥 ' . t('syslog_event_db_error') . '</span>',
                                    'backup_created' => '<span class="badge badge-info"><i class="ti ti-device-floppy"></i> ' . t('syslog_event_backup') . '</span>',
                                ];
                                
                                echo $badges[$eventType] ?? '<span class="badge">' . htmlspecialchars($eventType) . '</span>';
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($source === 'db') {
                                    echo '<code style="background: var(--vs-surface-2); padding: 2px 8px; border-radius: 3px; font-size: 13px;">' 
                                         . htmlspecialchars($log['ip_address'] ?? '-') 
                                         . '</code>';
                                } else {
                                    // Details aus JSON
                                    if (!empty($log['details'])) {
                                        if (is_array($log['details'])) {
                                            $details = [];
                                            foreach ($log['details'] as $key => $value) {
                                                if (is_string($value) && strlen($value) < 100) {
                                                    $details[] = "<strong>$key:</strong> " . htmlspecialchars($value);
                                                }
                                            }
                                            echo implode(' • ', array_slice($details, 0, 3));
                                        }
                                    }
                                    echo ' <small style="color: #999;">(' . htmlspecialchars($log['ip'] ?? '') . ')</small>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
</main>

<style>
/* Source Selector */
.source-selector label {
    display: flex;
    align-items: center;
    gap: 5px;
}

.source-selector input[type="radio"] {
    cursor: pointer;
}

/* Restliche Styles bereits in admin-layout.css */
</style>

<script>
function changeSource(source) {
    const currentTab = '<?php echo $activeTab; ?>';
    const currentLimit = '<?php echo $limit; ?>';
    window.location.href = '?source=' + source + '&tab=' + currentTab + '&limit=' + currentLimit;
}

function changeLimit() {
    const limit = document.getElementById('limitSelect').value;
    const currentTab = '<?php echo $activeTab; ?>';
    const currentSource = '<?php echo $source; ?>';
    window.location.href = '?source=' + currentSource + '&tab=' + currentTab + '&limit=' + limit;
}
</script>

<?php include 'layout/footer_next_page.php'; ?>
