<?php
// activity_log.php - Anzeige aller Aktivitäten
require_once __DIR__ . '/config.php';
if (file_exists(__DIR__ . '/../helpers.php')) require_once __DIR__ . '/../helpers.php';
requireBackendAccess();

// ── Cleanup-Handler (POST von backend/index.php) ─────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cleanup') {
    if (!function_exists('darfSuperAdminAktionen') || !darfSuperAdminAktionen()) {
        http_response_code(403); exit('Zugriff verweigert');
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCSRFToken($token)) {
        http_response_code(403); exit('CSRF-Fehler');
    }
    $days = (int)($_POST['cleanup_days'] ?? 30);
    // Geloescht wird nur an einer Stelle: cleanupOldActivityLogs() in
    // helpers.php. 0 bedeutet dort "alles". helpers.php wird oben nur
    // eingebunden, wenn es existiert — deshalb hier die Probe, statt in einen
    // Fatal Error zu laufen.
    if (!function_exists('cleanupOldActivityLogs')) {
        header('Location: index.php?cleanup_err=1');
        exit;
    }
    try {
        cleanupOldActivityLogs($days);
        header('Location: index.php?cleanup_ok=1');
        exit;
    } catch (PDOException $e) {
        header('Location: index.php?cleanup_err=1');
        exit;
    }
}


$pageTitle = 'Aktivitäts-Log';

// Pagination
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Filter
$filterUser = filter_var($_GET['user'] ?? '', FILTER_VALIDATE_INT);
$filterAction = $_GET['action'] ?? '';
$filterTable = $_GET['table'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

// SQL Query aufbauen
$sql = "SELECT al.*, u.username 
        FROM activity_log al 
        LEFT JOIN users u ON al.user_id = u.id 
        WHERE 1=1";
$params = [];

if ($filterUser) {
    $sql .= " AND user_id = ?";
    $params[] = $filterUser;
}

if ($filterAction) {
    $sql .= " AND aktion = ?";
    $params[] = $filterAction;
}

if ($filterTable) {
    $sql .= " AND tabelle = ?";
    $params[] = $filterTable;
}

if ($filterDateFrom) {
    $sql .= " AND DATE(zeitstempel) >= ?";
    $params[] = $filterDateFrom;
}

if ($filterDateTo) {
    $sql .= " AND DATE(zeitstempel) <= ?";
    $params[] = $filterDateTo;
}

// Gesamtanzahl für Pagination - verwende gleiche Parameter wie Hauptquery
$countSql = "SELECT COUNT(*) as total FROM activity_log WHERE 1=1";
$countParams = [];

if ($filterUser) {
    $countSql .= " AND user_id = ?";
    $countParams[] = $filterUser;
}

if ($filterAction) {
    $countSql .= " AND aktion = ?";
    $countParams[] = $filterAction;
}

if ($filterTable) {
    $countSql .= " AND tabelle = ?";
    $countParams[] = $filterTable;
}

if ($filterDateFrom) {
    $countSql .= " AND DATE(zeitstempel) >= ?";
    $countParams[] = $filterDateFrom;
}

if ($filterDateTo) {
    $countSql .= " AND DATE(zeitstempel) <= ?";
    $countParams[] = $filterDateTo;
}

try {
    // Anzahl ermitteln
    $totalResult = $db->selectOne($countSql, $countParams);
    $total = $totalResult['total'] ?? 0;
    $totalPages = ceil($total / $perPage);
    
    // Logs laden
    $sql .= " ORDER BY zeitstempel DESC LIMIT $perPage OFFSET $offset";
    $logs = $db->select($sql, $params);
    
    // User für Filter laden
    $users = $db->select("SELECT DISTINCT id, username FROM users ORDER BY username");
    
} catch (PDOException $e) {
    die('Fehler beim Laden der Daten: ' . $e->getMessage());
}

include 'layout/header_next_page.php';
?>

<style>
.activity-log-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.filter-section {
    background: var(--vs-surface);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #555;
    font-size: 14px;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.activity-table {
    background: var(--vs-surface);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.activity-table table {
    width: 100%;
    border-collapse: collapse;
}

.activity-table th {
    background: var(--primary-color, #3498db);
    color: var(--vs-surface);
    padding: 15px;
    text-align: left;
    font-weight: 600;
}

.activity-table td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.activity-table tr:last-child td {
    border-bottom: none;
}

.activity-table tr:hover {
    background: var(--vs-surface-2);
}

.activity-icon {
    font-size: 20px;
    margin-right: 8px;
}

.activity-action {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.action-created {
    background: var(--vs-success-light);
    color: var(--vs-success-text);
}

.action-updated {
    background: var(--vs-accent-light);
    color: var(--vs-accent);
}

.action-deleted {
    background: var(--vs-danger-light);
    color: var(--vs-danger-text);
}

.action-uploaded,
.action-exported {
    background: var(--vs-warning-light);
    color: var(--vs-warning-text);
}

.activity-details {
    margin-top: 10px;
    padding: 10px;
    background: var(--vs-surface-2);
    border-radius: 6px;
    font-size: 13px;
}

.activity-details ul {
    margin: 0;
    padding-left: 20px;
}

.changes-list {
    list-style: none;
    padding: 0;
}

.changes-list li {
    padding: 5px 0;
}

.old-value {
    color: var(--vs-danger);
    text-decoration: line-through;
}

.new-value {
    color: var(--vs-success);
    font-weight: 600;
}

.activity-meta {
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}

.details-toggle {
    cursor: pointer;
    color: var(--primary-color, #3498db);
    text-decoration: underline;
    font-size: 13px;
    border: none;
    background: none;
    padding: 0;
}

.details-toggle:hover {
    color: var(--primary-hover, #2980b9);
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 25px;
}

.pagination a,
.pagination span {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
}

.pagination a:hover {
    background: var(--primary-color, #3498db);
    color: var(--vs-surface);
    border-color: var(--primary-color, #3498db);
}

.pagination .current {
    background: var(--primary-color, #3498db);
    color: var(--vs-surface);
    border-color: var(--primary-color, #3498db);
}

@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .activity-table {
        overflow-x: auto;
    }
    
    .activity-table table {
        min-width: 600px;
    }
}
</style>

<div class="activity-log-header">
    <h2><i class="ti ti-list"></i> <?php echo t('page_activity_log'); ?></h2>
    <div>
        <span style="color: #666;"><?php echo number_format($total); ?> <?php echo t('stats_entries'); ?></span>
    </div>
</div>

<!-- Filter -->
<div class="filter-section">
    <form method="GET" action="">
        <div class="filter-grid">
            <div class="filter-group">
                <label for="user"><?php echo t('act_col_user'); ?>:</label>
                <select name="user" id="user">
                    <option value=""><?php echo t('log_all_users'); ?></option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $filterUser == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="action"><?php echo t('act_col_action'); ?>:</label>
                <select name="action" id="action">
                    <option value=""><?php echo t('log_all_actions'); ?></option>
                    <option value="created" <?php echo $filterAction === 'created' ? 'selected' : ''; ?>><?php echo t('action_created'); ?></option>
                    <option value="updated" <?php echo $filterAction === 'updated' ? 'selected' : ''; ?>><?php echo t('action_updated'); ?></option>
                    <option value="deleted" <?php echo $filterAction === 'deleted' ? 'selected' : ''; ?>><?php echo t('action_deleted'); ?></option>
                    <option value="uploaded" <?php echo $filterAction === 'uploaded' ? 'selected' : ''; ?>><?php echo t('action_uploaded'); ?></option>
                    <option value="exported" <?php echo $filterAction === 'exported' ? 'selected' : ''; ?>><?php echo t('action_exported'); ?></option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="table"><?php echo t('log_col_area'); ?>:</label>
                <select name="table" id="table">
                    <option value=""><?php echo t('log_all_areas'); ?></option>
                    <option value="wertsachen" <?php echo $filterTable === 'wertsachen' ? 'selected' : ''; ?>><?php echo t('stats_items'); ?></option>
                    <option value="kategorien" <?php echo $filterTable === 'kategorien' ? 'selected' : ''; ?>><?php echo t('settings_categories'); ?></option>
                    <option value="raeume" <?php echo $filterTable === 'raeume' ? 'selected' : ''; ?>><?php echo t('settings_locations'); ?></option>
                    <option value="benutzer" <?php echo $filterTable === 'benutzer' ? 'selected' : ''; ?>><?php echo t('act_col_user'); ?></option>
                    <option value="dokumente" <?php echo $filterTable === 'dokumente' ? 'selected' : ''; ?>><?php echo t('form_documents'); ?></option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="date_from"><?php echo t('log_date_from'); ?>:</label>
                <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($filterDateFrom); ?>">
            </div>
            
            <div class="filter-group">
                <label for="date_to"><?php echo t('log_date_to'); ?>:</label>
                <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($filterDateTo); ?>">
            </div>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="vs-btn vs-btn-primary"><i class="ti ti-search"></i> <?php echo t('btn_filter'); ?></button>
            <a href="activity_log.php" class="vs-btn vs-btn-secondary"><i class="ti ti-refresh"></i> <?php echo t('btn_reset'); ?></a>
        </div>
    </form>
</div>

<!-- Tabelle -->
<div class="activity-table">
    <table>
        <thead>
            <tr>
                <th><?php echo t('act_col_time'); ?></th>
                <th><?php echo t('act_col_user'); ?></th>
                <th><?php echo t('act_col_action'); ?></th>
                <th><?php echo t('log_col_area'); ?></th>
                <th><?php echo t('stats_entry'); ?></th>
                <th><?php echo t('log_col_details'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                        <?php echo t('log_no_entries'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="white-space: nowrap;">
                            <?php echo formatDateTime($log['zeitstempel']); ?>
                            <div class="activity-meta">
                                <?php 
                                $diff = time() - strtotime($log['zeitstempel']);
                                if ($diff < 3600) {
                                    echo 'vor ' . round($diff / 60) . ' Min';
                                } elseif ($diff < 86400) {
                                    echo 'vor ' . round($diff / 3600) . ' Std';
                                } else {
                                    echo 'vor ' . round($diff / 86400) . ' Tagen';
                                }
                                ?>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></strong>
                        </td>
                        <td>
                            <span class="activity-action <?php echo getActionClass($log['aktion']); ?>">
                                <span class="activity-icon"><?php echo getActionIcon($log['aktion']); ?></span>
                                <?php echo getActionText($log['aktion']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars(ucfirst($log['tabelle'])); ?></td>
                        <td>
                            <?php if ($log['bezeichnung']): ?>
                                <strong><?php echo htmlspecialchars($log['bezeichnung']); ?></strong>
                                <?php if ($log['datensatz_id']): ?>
                                    <div class="activity-meta">ID: <?php echo $log['datensatz_id']; ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log['alt_wert'] || $log['neu_wert']): ?>
                                <button class="details-toggle" onclick="toggleDetails(<?php echo $log['id']; ?>)">
                                    <?php echo t('log_show_details'); ?>
                                </button>
                                <div id="details-<?php echo $log['id']; ?>" style="display: none;">
                                    <?php 
                                    if ($log['alt_wert']) {
                                        echo formatChanges($log['alt_wert']);
                                    } else {
                                        echo formatChanges($log['neu_wert']);
                                    }
                                    ?>
                                    <?php if ($log['ip_adresse']): ?>
                                        <div class="activity-meta" style="margin-top: 10px;">
                                            IP: <?php echo htmlspecialchars($log['ip_adresse']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo ($page - 1); ?><?php echo $filterUser ? '&user=' . $filterUser : ''; ?><?php echo $filterAction ? '&action=' . $filterAction : ''; ?><?php echo $filterTable ? '&table=' . $filterTable : ''; ?><?php echo $filterDateFrom ? '&date_from=' . $filterDateFrom : ''; ?><?php echo $filterDateTo ? '&date_to=' . $filterDateTo : ''; ?>">
                <?php echo t('pagination_prev'); ?>
            </a>
        <?php endif; ?>
        
        <span class="current"><?php echo t('export_page'); ?> <?php echo $page; ?> <?php echo t('export_of'); ?> <?php echo $totalPages; ?></span>
        
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo ($page + 1); ?><?php echo $filterUser ? '&user=' . $filterUser : ''; ?><?php echo $filterAction ? '&action=' . $filterAction : ''; ?><?php echo $filterTable ? '&table=' . $filterTable : ''; ?><?php echo $filterDateFrom ? '&date_from=' . $filterDateFrom : ''; ?><?php echo $filterDateTo ? '&date_to=' . $filterDateTo : ''; ?>">
                <?php echo t('pagination_next'); ?>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="form-actions" style="margin-top: 30px;">
    <a href="index.php" class="vs-btn vs-btn-secondary"><?php echo t('btn_back_to_overview'); ?></a>
    <?php if (darfSuperAdminAktionen()): ?>
        <a href="cleanup_activity_logs.php" class="vs-btn vs-btn-danger" style="background: var(--vs-danger); color: var(--vs-surface);">
            <i class="ti ti-trash"></i> <?php echo t('log_cleanup_link'); ?>
        </a>
    <?php endif; ?>
    <?php if (isAdmin()): ?>
        <a href="../settings.php?tab=tools" class="vs-btn vs-btn-secondary"><i class="ti ti-settings"></i> <?php echo t('nav_settings'); ?></a>
    <?php endif; ?>
</div>

<script>
function toggleDetails(id) {
    const details = document.getElementById('details-' + id);
    const button = details.previousElementSibling;
    
    if (details.style.display === 'none') {
        details.style.display = 'block';
        button.textContent = <?php echo json_encode(t('log_hide_details')); ?>;
    } else {
        details.style.display = 'none';
        button.textContent = <?php echo json_encode(t('log_show_details')); ?>;
    }
}
</script>

<?php include 'layout/footer_next_page.php';
