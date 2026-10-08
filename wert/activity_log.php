<?php
// activity_log.php - Anzeige aller Aktivitäten
require_once 'db.php';
require_once 'helpers.php';
requireLogin();

define('PAGE_TITLE', t('page_activity_log') . ' - ' . t('app_title'));

// Nicht-Admins sehen nur ihre eigene Aktivität, keine anderen Benutzer
$isLogAdmin = isAdmin();

// Pagination
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Filter
$filterUser = filter_var($_GET['user'] ?? '', FILTER_VALIDATE_INT);
if (!$isLogAdmin) {
    $filterUser = (int)($_SESSION['user_id'] ?? 0);
}
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
    
    // User für Filter laden - Nicht-Admins sehen nur sich selbst
    if ($isLogAdmin) {
        $users = $db->select("SELECT DISTINCT id, username FROM users ORDER BY username");
    } else {
        $users = $db->select("SELECT id, username FROM users WHERE id = ?", [$_SESSION['user_id'] ?? 0]);
    }

} catch (PDOException $e) {
    die('Fehler beim Laden der Daten: ' . $e->getMessage());
}

include 'header_next_page.php';
?>

<style>
.activity-log-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.filter-section {
    background: white;
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
    background: white;
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
    color: white;
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
    background: #f8f9fa;
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
    background: #d4edda;
    color: #155724;
}

.action-updated {
    background: #d1ecf1;
    color: #0c5460;
}

.action-deleted {
    background: #f8d7da;
    color: #721c24;
}

.action-uploaded,
.action-exported {
    background: #fff3cd;
    color: #856404;
}

.activity-details {
    margin-top: 10px;
    padding: 10px;
    background: #f8f9fa;
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
    color: #e74c3c;
    text-decoration: line-through;
}

.new-value {
    color: #27ae60;
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
    color: white;
    border-color: var(--primary-color, #3498db);
}

.pagination .current {
    background: var(--primary-color, #3498db);
    color: white;
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
    <h2>📜 <?php echo t('page_activity_log'); ?></h2>
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
            <button type="submit" class="btn btn-primary">🔍 <?php echo t('btn_filter'); ?></button>
            <a href="activity_log.php" class="btn">🔄 <?php echo t('btn_reset'); ?></a>
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
                                    echo sprintf(t('log_hours_ago'), round($diff / 3600));
                                } else {
                                    echo sprintf(t('log_days_ago'), round($diff / 86400));
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
                        <td><?php echo htmlspecialchars(vsTabellenName($log['tabelle'])); ?></td>
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
    <a href="index.php" class="btn"><?php echo t('btn_back_to_overview'); ?></a>
    <?php if (darfSuperAdminAktionen()): ?>
        <a href="backend/cleanup_activity_logs.php" class="btn btn-danger" style="background: #e74c3c; color: white;">
            🗑️ <?php echo t('log_cleanup_link'); ?>
        </a>
    <?php endif; ?>
    <?php if (isAdmin()): ?>
        <a href="settings.php?tab=tools" class="btn btn-secondary">⚙️ <?php echo t('nav_settings'); ?></a>
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

<?php include 'footer_next_page.php'; ?>
