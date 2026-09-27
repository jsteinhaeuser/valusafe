<?php
/**
 * System-Logs - KORRIGIERT für deutsche Spaltennamen
 * Backend Logs mit korrektem Spalten-Mapping
 */

require_once __DIR__ . '/config.php';
requireBackendAccess();

$pageTitle = t('syslog_title');

// Tab auswählen
$activeTab = $_GET['tab'] ?? 'activity';

// Limit
$limit = intval($_GET['limit'] ?? 50);

$logs = [];
$stats = [];

try {
    if ($activeTab === 'activity') {
        // FIXED: users Tabelle statt benutzer, user_id statt benutzer_id
        $logs = $db->select("
            SELECT al.*, u.username
            FROM activity_log al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.zeitstempel DESC
            LIMIT ?
        ", [$limit]);
        
        // Statistiken
        $stats['total'] = $db->selectOne("SELECT COUNT(*) as count FROM activity_log")['count'];
        $stats['created'] = $db->selectOne("SELECT COUNT(*) as count FROM activity_log WHERE aktion = 'created'")['count'];
        $stats['updated'] = $db->selectOne("SELECT COUNT(*) as count FROM activity_log WHERE aktion = 'updated'")['count'];
        $stats['deleted'] = $db->selectOne("SELECT COUNT(*) as count FROM activity_log WHERE aktion = 'deleted'")['count'];
        
    } else { // security
        $logs = $db->select("
            SELECT sl.*, u.username
            FROM security_log sl
            LEFT JOIN users u ON sl.user_id = u.id
            ORDER BY sl.created_at DESC
            LIMIT ?
        ", [$limit]);
        
        // Statistiken (24h)
        $stats['total'] = $db->selectOne("SELECT COUNT(*) as count FROM security_log")['count'];
        $stats['success_24h'] = $db->selectOne("SELECT COUNT(*) as count FROM security_log WHERE event_type = 'login_success' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'];
        $stats['failed_24h'] = $db->selectOne("SELECT COUNT(*) as count FROM security_log WHERE event_type = 'login_failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'];
        
        $totalLogins = $stats['success_24h'] + $stats['failed_24h'];
        $stats['success_rate'] = $totalLogins > 0 ? round(($stats['success_24h'] / $totalLogins) * 100, 1) : 0;
    }
    
} catch (Exception $e) {
    $error = 'Fehler beim Laden der Logs: ' . $e->getMessage();
    error_log("Logs Error: " . $e->getMessage());
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
    
    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <a href="?tab=activity&limit=<?php echo $limit; ?>" class="tab-link <?php echo $activeTab === 'activity' ? 'active' : ''; ?>">
            <i class="ti ti-chart-bar"></i> <?php echo t('syslog_tab_activity'); ?>
        </a>
        <a href="?tab=security&limit=<?php echo $limit; ?>" class="tab-link <?php echo $activeTab === 'security' ? 'active' : ''; ?>">
            <i class="ti ti-lock"></i> <?php echo t('syslog_tab_security'); ?>
        </a>
    </div>

    <!-- Diese Seite zeigt nur an. Geloescht wird in activity_log.php. -->
    <div style="margin: 14px 0 20px; font-size: 13px; color: var(--vs-text-muted);">
        <i class="ti ti-info-circle"></i>
        <?php echo t('syslog_view_only'); ?>
        <a href="activity_log.php" style="color: var(--vs-accent);"><?php echo t('syslog_cleanup_link'); ?></a>
    </div>
    
    <!-- Statistik-Karten -->
    <?php if (!empty($stats)): ?>
    <div class="dashboard-grid" style="margin-bottom: 30px;">
        <?php if ($activeTab === 'activity'): ?>
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value"><?php echo number_format($stats['total'], 0, ',', '.'); ?></div>
                        <div class="widget-label"><i class="ti ti-chart-bar"></i> <?php echo t('syslog_total'); ?></div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-chart-bar"></i></div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="color: var(--vs-success);"><?php echo number_format($stats['created'], 0, ',', '.'); ?></div>
                        <div class="widget-label"><i class="ti ti-plus"></i> <?php echo t('action_created'); ?></div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-plus"></i></div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="color: var(--vs-accent);"><?php echo number_format($stats['updated'], 0, ',', '.'); ?></div>
                        <div class="widget-label"><i class="ti ti-pencil"></i> <?php echo t('action_updated'); ?></div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-pencil"></i></div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="color: var(--vs-danger);"><?php echo number_format($stats['deleted'], 0, ',', '.'); ?></div>
                        <div class="widget-label"><i class="ti ti-trash"></i> <?php echo t('action_deleted'); ?></div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-trash"></i></div>
                </div>
            </div>
        <?php else: ?>
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value"><?php echo number_format($stats['total'], 0, ',', '.'); ?></div>
                        <div class="widget-label"><i class="ti ti-chart-bar"></i> <?php echo t('syslog_total_all_time'); ?></div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-chart-bar"></i></div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="color: var(--vs-success);"><?php echo number_format($stats['success_24h'], 0, ',', '.'); ?></div>
                        <div class="widget-label"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> <?php echo t('syslog_success_24h'); ?></div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i></div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="color: var(--vs-danger);"><?php echo number_format($stats['failed_24h'], 0, ',', '.'); ?></div>
                        <div class="widget-label"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> <?php echo t('syslog_errors_24h'); ?></div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i></div>
                </div>
            </div>
            
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size: 32px;"><?php echo $stats['success_rate']; ?>%</div>
                        <div class="widget-label"><i class="ti ti-trending-up"></i> <?php echo t('syslog_success_rate_24h'); ?></div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-trending-up"></i></div>
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
                    <?php foreach ([25, 50, 100, 250] as $_n): ?>
                        <option value="<?php echo $_n; ?>" <?php echo $limit == $_n ? 'selected' : ''; ?>><?php echo sprintf(t('syslog_entries_n'), $_n); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <?php if (count($logs) === 0): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 15px;"><i class="ti ti-chart-bar"></i></div>
                <p><?php echo t('syslog_none_found'); ?></p>
                <?php if ($activeTab === 'security' && $stats['total'] == 0): ?>
                    <?php /* Der Verweis auf migrate_security_logs.php ist entfallen:
                         die Datei gibt es weder hier noch im Wurzelverzeichnis. */ ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="backend-table">
                <thead>
                    <tr>
                        <th style="width: 180px;"><?php echo t('act_col_time'); ?></th>
                        <th style="width: 120px;"><?php echo t('act_col_user'); ?></th>
                        <?php if ($activeTab === 'activity'): ?>
                            <th style="width: 100px;"><?php echo t('act_col_action'); ?></th>
                            <th style="width: 120px;"><?php echo t('syslog_col_table'); ?></th>
                            <th><?php echo t('col_bezeichnung'); ?></th>
                            <th style="width: 150px;"><?php echo t('online_col_ip'); ?></th>
                        <?php else: ?>
                            <th style="width: 150px;"><?php echo t('syslog_col_event'); ?></th>
                            <th><?php echo t('field_description'); ?></th>
                            <th style="width: 150px;"><?php echo t('online_col_ip'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <?php 
                                $time = $activeTab === 'security' ? $log['created_at'] : $log['zeitstempel'];
                                echo '<span title="' . date('d.m.Y H:i:s', strtotime($time)) . '">' . timeAgo($time) . '</span>';
                                ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></strong>
                            </td>
                            
                            <?php if ($activeTab === 'activity'): ?>
                                <td>
                                    <?php 
                                    $badges = [
                                        'created' => '<span class="badge badge-success"><i class="ti ti-plus"></i> ' . t('action_created') . '</span>',
                                        'updated' => '<span class="badge badge-info"><i class="ti ti-pencil"></i> ' . t('action_updated') . '</span>',
                                        'deleted' => '<span class="badge badge-danger"><i class="ti ti-trash"></i> ' . t('action_deleted') . '</span>',
                                    ];
                                    echo $badges[$log['aktion']] ?? '<span class="badge">' . htmlspecialchars($log['aktion']) . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <code style="background: var(--vs-surface-2); padding: 2px 8px; border-radius: 3px; font-size: 13px;">
                                        <?php echo htmlspecialchars($log['tabelle']); ?>
                                    </code>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['bezeichnung'] ?? '-'); ?></strong>
                                </td>
                                <td>
                                    <code style="background: var(--vs-surface-2); padding: 2px 8px; border-radius: 3px; font-size: 12px;">
                                        <?php echo htmlspecialchars($log['ip_adresse'] ?? '-'); ?>
                                    </code>
                                </td>
                            <?php else: ?>
                                <td>
                                    <?php 
                                    $badges = [
                                        'login_success' => '<span class="badge badge-success"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> ' . t('syslog_event_login_ok') . '</span>',
                                        'login_failed' => '<span class="badge badge-danger"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> ' . t('syslog_event_login_failed') . '</span>',
                                        'logout' => '<span class="badge badge-secondary">🚪 ' . t('syslog_event_logout') . '</span>',
                                        'password_change' => '<span class="badge badge-info"><i class="ti ti-key"></i> ' . t('syslog_event_password') . '</span>',
                                    ];
                                    echo $badges[$log['event_type']] ?? '<span class="badge">' . htmlspecialchars($log['event_type']) . '</span>';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['description'] ?? '-'); ?></td>
                                <td>
                                    <code style="background: var(--vs-surface-2); padding: 2px 8px; border-radius: 3px; font-size: 12px;">
                                        <?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?>
                                    </code>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
</main>

<script>
function changeLimit() {
    const limit = document.getElementById('limitSelect').value;
    const currentTab = '<?php echo $activeTab; ?>';
    window.location.href = '?tab=' + currentTab + '&limit=' + limit;
}
</script>

<?php include 'layout/footer_next_page.php'; ?>
