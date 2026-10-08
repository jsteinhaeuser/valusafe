<?php
/**
 * INSTALLATION: → /wert/backend/index.php
 * 
 * Backend Dashboard - OPTIMIERTE VERSION
 * - Kleinere Thumbnails (45x45px statt 60x60px)
 * - Kompakteres Layout (-25% Platzbedarf)
 * - Mehr Inhalt sichtbar (+50%)
 * - Scrollbar bei Bedarf
 */

require_once 'config.php';
requireBackendAccess();

$pageTitle = 'Dashboard';

// Statistiken laden
$stats = getSystemStats();

// ============================================
// DATEN FÜR WIDGETS
// ============================================

// 1. LETZTE UPLOADS (5 neueste Items mit Bild)
try {
    $latestUploads = $db->select("
        SELECT w.id, w.name, w.bild, w.preis, w.erstellt_am,
               k.name as kategorie_name
        FROM wertsachen w
        LEFT JOIN kategorien k ON w.kategorie_id = k.id
        WHERE w.bild IS NOT NULL AND w.bild != ''
        ORDER BY w.erstellt_am DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    $latestUploads = [];
}

// 2. WERTVOLLSTE ITEMS (Top 5 nach Preis)
try {
    $topItems = $db->select("
        SELECT w.id, w.name, w.bild, w.preis,
               k.name as kategorie_name,
               o.name as ort_name
        FROM wertsachen w
        LEFT JOIN kategorien k ON w.kategorie_id = k.id
        LEFT JOIN raeume o ON w.raum_id = o.id
        ORDER BY w.preis DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    $topItems = [];
}

// 3. BACKUP-STATUS
$backupDir = __DIR__ . '/../backups/';
$lastBackup = null;
$backupSize = 0;
$backupAge = null;

if (is_dir($backupDir)) {
    $backups = glob($backupDir . '*.{sql,zip}', GLOB_BRACE);
    if (!empty($backups)) {
        usort($backups, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $lastBackup = basename($backups[0]);
        $backupSize = filesize($backups[0]);
        $backupAge = time() - filemtime($backups[0]);
    }
}

// 4. SPEICHERPLATZ-NUTZUNG
$uploadDir = __DIR__ . '/../upload/';
$imageCount = 0;
$imageSize = 0;

if (is_dir($uploadDir)) {
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    $files = glob($uploadDir . '*');
    
    if (is_array($files)) {
        foreach ($files as $file) {
            if (is_file($file)) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $imageExtensions)) {
                    $imageCount++;
                    $imageSize += filesize($file);
                }
            }
        }
    }
}

// Dokumente-Statistik
$docsDir = __DIR__ . '/../documents/';
$docCount = 0;
$docSize = 0;

if (is_dir($docsDir)) {
    $docExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'odt', 'ods'];
    $files = glob($docsDir . '*');
    
    if (is_array($files)) {
        foreach ($files as $file) {
            if (is_file($file)) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $docExtensions)) {
                    $docCount++;
                    $docSize += filesize($file);
                }
            }
        }
    }
}

// Gesamter Speicherverbrauch
$totalSize = $imageSize + $docSize;
$maxSize = 1024 * 1024 * 1024; // 1 GB
$usagePercent = ($totalSize / $maxSize) * 100;

// 5. SCHNELLZUGRIFF - Häufigste Kategorien
try {
    $topCategories = $db->select("
        SELECT k.id, k.name, COUNT(w.id) as count,
               SUM(w.preis) as total_value
        FROM kategorien k
        LEFT JOIN wertsachen w ON k.id = w.kategorie_id
        GROUP BY k.id, k.name
        HAVING count > 0
        ORDER BY count DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    $topCategories = [];
}

// 6. LETZTE AKTIVITÄTEN (10 Stück)
try {
    $recentActivities = $db->select("
        SELECT al.*, u.username
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.zeitstempel DESC
        LIMIT 10
    ");
} catch (Exception $e) {
    $recentActivities = [];
}

// 7. WHO IS ONLINE (letzte 15 Minuten)
try {
    $onlineUsers = $db->select("
        SELECT username, role, last_seen, current_page, ip_address
        FROM user_activity
        WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ORDER BY last_seen DESC
    ");
} catch (Exception $e) {
    $onlineUsers = [];
}

// Layout laden (Sidebar wird automatisch in header.php included)
include 'layout/header_next_page.php';
?>

<!-- Main Content -->
<main class="backend-main">
<?php if (!empty($_GET['cleanup_ok'])): ?>
<div class="success-message" style="margin-bottom:var(--vs-sp-4);">
    <i class="ti ti-circle-check"></i> Logs erfolgreich bereinigt.
</div>
<?php elseif (!empty($_GET['cleanup_err'])): ?>
<div class="error-message" style="margin-bottom:var(--vs-sp-4);">
    <i class="ti ti-alert-triangle"></i> Fehler beim Bereinigen der Logs.
</div>
<?php endif; ?>
    
    <!-- Dashboard Widgets - Obere Reihe (Statistiken) -->
    <div class="dashboard-grid" style="margin-bottom: 30px;">
        
        <!-- Benutzer Widget -->
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value"><?php echo $stats['users_total']; ?></div>
                    <div class="widget-label"><i class="ti ti-users"></i> <?php echo t('dash_users'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-users"></i></div>
            </div>
            <div class="widget-footer">
                <a href="users.php" class="widget-link"><?php echo t('link_manage'); ?></a>
            </div>
        </div>
        
        <!-- Gegenstände Widget -->
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value"><?php echo $stats['items_total']; ?></div>
                    <div class="widget-label"><i class="ti ti-package"></i> <?php echo t('dash_items'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-package"></i></div>
            </div>
            <div class="widget-footer">
                <a href="../index.php" class="widget-link"><?php echo t('link_view'); ?></a>
            </div>
        </div>
        

        <!-- Raeume / Standorte / Positionen. Bis 4.3.30 waren die Beschriftungen
             verrutscht: die Zahl der Raeume hiess "Orte", die der Standorte
             "Raeume" ("6 Orte · 1 Raeume · 1 Pos."). -->
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <?php
                    $total_raeume = $db->select("SELECT COUNT(*) as n FROM raeume")[0]['n'] ?? 0;
                    try { $total_standorte  = $db->select("SELECT COUNT(*) as n FROM standorte")[0]['n'] ?? 0; } catch(Exception $e) { $total_standorte = '—'; }
                    try { $total_positionen = $db->select("SELECT COUNT(*) as n FROM positionen")[0]['n'] ?? 0; } catch(Exception $e) { $total_positionen = '—'; }
                    ?>
                    <div class="widget-value"><?php echo $total_raeume; ?></div>
                    <div class="widget-label"><i class="ti ti-map-pin"></i> <?php echo t('loc_tab_rooms'); ?>
                        <small style="color:var(--vs-muted);font-size:11px;">
                            · <?php echo t('loc_tab_locations'); ?>: <?php echo $total_standorte; ?> · <?php echo t('loc_tab_positions_short') ?: 'Pos.'; ?>: <?php echo $total_positionen; ?>
                        </small>
                    </div>
                </div>
                <div class="widget-icon"><i class="ti ti-map-pin"></i></div>
            </div>
            <div class="widget-footer">
                <a href="locations.php" class="widget-link"><?php echo t('link_manage'); ?></a>
            </div>
        </div>
        <!-- Gesamtwert Widget -->
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value"><?php echo number_format($stats['total_value'], 0, ',', '.'); ?> €</div>
                    <div class="widget-label"><i class="ti ti-currency-euro"></i> <?php echo t('dash_total_value'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-currency-euro"></i></div>
            </div>
            <div class="widget-footer">
                <a href="stats.php" class="widget-link"><?php echo t('link_statistics'); ?></a>
            </div>
        </div>
        
        <!-- Kategorien Widget -->
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value"><?php echo $stats['categories_total']; ?></div>
                    <div class="widget-label"><i class="ti ti-tag"></i> <?php echo t('nav_categories'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-tag"></i></div>
            </div>
            <div class="widget-footer">
                <a href="categories.php" class="widget-link"><?php echo t('btn_manage'); ?> →</a>
            </div>
        </div>

        <!-- Who is online Widget -->
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value" style="color: var(--vs-success);"><?php echo count($onlineUsers); ?></div>
                    <div class="widget-label" style="white-space:nowrap;"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> <?php echo t('online_widget_title'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-circle-check" style="opacity:0.25;"></i></div>
            </div>
            <?php if (empty($onlineUsers)): ?>
                <div style="font-size:12px; color:#999; margin: 8px 0;"><?php echo t('online_widget_none'); ?></div>
            <?php else: ?>
                <ul style="margin:8px 0 0; padding:0; list-style:none; font-size:12px;">
                <?php foreach (array_slice($onlineUsers, 0, 4) as $ou): ?>
                    <li style="padding:3px 0; color:#555;">
                        <i class="ti ti-circle-check" style="color:var(--vs-success);"></i> <strong><?php echo htmlspecialchars($ou['username']); ?></strong>
                        <span style="color:#aaa;"> · <?php echo htmlspecialchars($ou['current_page'] ?? ''); ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (count($onlineUsers) > 4): ?>
                    <li style="color:#aaa; padding:3px 0;">+ <?php echo count($onlineUsers) - 4; ?> weitere</li>
                <?php endif; ?>
                </ul>
            <?php endif; ?>
            <div class="widget-footer">
                <a href="users.php" class="widget-link"><?php echo t('online_widget_link'); ?></a>
            </div>
        </div>

        <!-- Rechtevergabe -->
        <?php if (function_exists('isSuperAdmin') && isSuperAdmin()): ?>
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value"><i class="ti ti-shield-check" style="font-size:28px;color:var(--vs-accent);"></i></div>
                    <div class="widget-label"><i class="ti ti-shield"></i> <?php echo tn('nav_permissions', 'Rechtevergabe'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-shield"></i></div>
            </div>
            <div class="widget-footer">
                <a href="permissions.php" class="widget-link"><?php echo t('perm_manage_link'); ?></a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Öffentlicher Link -->
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value"><i class="ti ti-world" style="font-size:26px;color:var(--vs-accent);"></i></div>
                    <div class="widget-label"><i class="ti ti-link"></i> <?php echo t('dash_public_link'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-link"></i></div>
            </div>
            <div class="widget-footer">
                <a href="public_settings.php" class="widget-link"><?php echo t('link_settings'); ?></a>
            </div>
        </div>

    </div>
    
    <!-- NEUE WIDGETS - Mittlere Sektion -->
    <div class="dashboard-row">
        
        <!-- NEU: Letzte Uploads Widget -->
        <div class="widget-card widget-large">
            <div class="widget-title">
                <h3><i class="ti ti-camera-plus"></i> <?php echo t('dash_recent_uploads'); ?></h3>
                <a href="gallery.php" class="widget-link-small"><?php echo t('link_view_all'); ?></a>
            </div>
            
            <?php if (empty($latestUploads)): ?>
                <div style="padding: 40px; text-align: center; color: #999;">
                    <div style="font-size: 48px; margin-bottom: 10px;"><i class="ti ti-package"></i></div>
                    <p><?php echo t('bk_no_items_with_images'); ?></p>
                </div>
            <?php else: ?>
                <div class="uploads-list">
                    <?php foreach ($latestUploads as $item): ?>
                        <div class="upload-item">
                            <a href="../edit.php?id=<?php echo $item['id']; ?>" class="upload-link">
                                <div class="upload-thumbnail">
                                    <?php if (!empty($item['bild']) && file_exists(__DIR__ . '/../upload/' . $item['bild'])): ?>
                                        <img src="../upload/<?php echo htmlspecialchars($item['bild']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div class="no-image-small"><i class="ti ti-package"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="upload-info">
                                    <div class="upload-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="upload-meta">
                                        <span class="badge badge-sm"><?php echo htmlspecialchars($item['kategorie_name'] ?? 'Ohne'); ?></span>
                                        <span class="upload-price"><?php echo number_format($item['preis'], 2, ',', '.'); ?> €</span>
                                    </div>
                                    <div class="upload-date"><?php echo timeAgo($item['erstellt_am']); ?></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- NEU: Top 5 Wertvollste Items -->
        <div class="widget-card widget-large">
            <div class="widget-title">
                <h3>💎 <?php echo t('dash_most_valuable'); ?></h3>
                <a href="../index.php?sort=preis_desc" class="widget-link-small"><?php echo t('link_view_all'); ?></a>
            </div>
            
            <?php if (empty($topItems)): ?>
                <div style="padding: 40px; text-align: center; color: #999;">
                    <div style="font-size: 48px; margin-bottom: 10px;">💎</div>
                    <p><?php echo t('bk_no_items'); ?></p>
                </div>
            <?php else: ?>
                <div class="top-items-list">
                    <?php foreach ($topItems as $index => $item): ?>
                        <div class="top-item">
                            <div class="top-rank">#<?php echo $index + 1; ?></div>
                            <div class="top-thumbnail">
                                <?php if (!empty($item['bild']) && file_exists(__DIR__ . '/../upload/' . $item['bild'])): ?>
                                    <img src="../upload/<?php echo htmlspecialchars($item['bild']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="no-image-small">💎</div>
                                <?php endif; ?>
                            </div>
                            <div class="top-info">
                                <a href="../edit.php?id=<?php echo $item['id']; ?>" class="top-name">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </a>
                                <div class="top-meta">
                                    <?php if ($item['kategorie_name']): ?>
                                        <span class="badge badge-sm"><?php echo htmlspecialchars($item['kategorie_name']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($item['ort_name']): ?>
                                        <span class="badge badge-sm badge-secondary">📍 <?php echo htmlspecialchars($item['ort_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="top-price">
                                <?php echo number_format($item['preis'], 0, ',', '.'); ?> €
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
    
    <!-- NEUE WIDGETS - Untere Sektion -->
    <div class="dashboard-row">
        
        <!-- NEU: Backup Status -->
        <div class="widget-card">
            <div class="widget-title">
                <h3><i class="ti ti-device-floppy"></i> Backup-Status</h3>
            </div>
            
            <?php if ($lastBackup): ?>
                <div class="backup-status">
                    <div class="backup-icon">
                        <?php if ($backupAge < 86400): ?>
                            <i class="ti ti-circle-check" style="color:var(--vs-success);"></i>
                        <?php elseif ($backupAge < 604800): ?>
                            <i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i>
                        <?php else: ?>
                            <i class="ti ti-circle-x" style="color:var(--vs-danger);"></i>
                        <?php endif; ?>
                    </div>
                    
                    <div class="backup-info">
                        <div class="backup-label">Letztes Backup:</div>
                        <div class="backup-value">
                            <?php 
                            if ($backupAge < 3600) {
                                echo 'vor ' . round($backupAge / 60) . ' Min';
                            } elseif ($backupAge < 86400) {
                                echo 'vor ' . round($backupAge / 3600) . ' Std';
                            } else {
                                echo 'vor ' . round($backupAge / 86400) . ' Tagen';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="backup-info">
                        <div class="backup-label"><?php echo t('backup_size'); ?>:</div>
                        <div class="backup-value"><?php echo formatFileSize($backupSize); ?></div>
                    </div>
                    
                    <div class="backup-info">
                        <div class="backup-label"><?php echo t('bk_file_label'); ?></div>
                        <div class="backup-value" style="font-size: 10px; word-break: break-all;">
                            <?php echo htmlspecialchars($lastBackup); ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; color: #999;">
                    <div style="font-size: 48px; margin-bottom: 10px;"><i class="ti ti-device-floppy"></i></div>
                    <p><?php echo t('bk_no_backup'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="widget-footer">
                <a href="backup.php" class="widget-link">Backup erstellen →</a>
            </div>
        </div>
        
        <!-- NEU: Speicherplatz -->
        <div class="widget-card">
            <div class="widget-title">
                <h3>💿 <?php echo t('dash_storage'); ?></h3>
            </div>
            
            <div class="storage-stats">
                <!-- Fortschrittsbalken -->
                <div class="storage-bar-container">
                    <div class="storage-bar" style="width: <?php echo min($usagePercent, 100); ?>%;">
                        <span class="storage-bar-text"><?php echo round($usagePercent, 1); ?>%</span>
                    </div>
                </div>
                
                <div class="storage-details">
                    <div class="storage-item">
                        <span class="storage-icon"><i class="ti ti-photo"></i></span>
                        <div class="storage-item-info">
                            <div class="storage-item-label"><?php echo t('dash_storage_images'); ?></div>
                            <div class="storage-item-value"><?php echo $imageCount; ?> <?php echo t('dash_storage_files'); ?> · <?php echo formatFileSize($imageSize); ?></div>
                        </div>
                    </div>
                    
                    <div class="storage-item">
                        <span class="storage-icon">📄</span>
                        <div class="storage-item-info">
                            <div class="storage-item-label"><?php echo t('dash_storage_documents'); ?></div>
                            <div class="storage-item-value"><?php echo $docCount; ?> <?php echo t('dash_storage_files'); ?> · <?php echo formatFileSize($docSize); ?></div>
                        </div>
                    </div>
                    
                    <div class="storage-summary">
                        <strong>Gesamt:</strong> <?php echo formatFileSize($totalSize); ?> / <?php echo formatFileSize($maxSize); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Schnellzugriff -->
        <div class="widget-card">
            <div class="widget-title">
                <h3><i class="ti ti-rocket"></i> <?php echo t('dash_quick_access'); ?></h3>
            </div>
            <?php if (empty($topCategories)): ?>
                <div style="padding: 32px; text-align: center; color: var(--vs-text-subtle);">
                    <i class="ti ti-tag" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                    <p><?php echo t('dash_no_categories'); ?></p>
                </div>
            <?php else: ?>
                <div class="quick-access-list">
                    <?php foreach ($topCategories as $category): ?>
                        <a href="../index.php?kategorie=<?php echo $category['id']; ?>" class="quick-access-item">
                            <div class="quick-access-icon"><i class="ti ti-tag"></i></div>
                            <div class="quick-access-info">
                                <div class="quick-access-name"><?php echo htmlspecialchars($category['name']); ?></div>
                                <div class="quick-access-meta">
                                    <?php echo $category['count']; ?> Items ·
                                    <?php echo number_format($category['total_value'], 0, ',', '.'); ?> €
                                </div>
                            </div>
                            <div class="quick-access-arrow">→</div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="widget-footer">
                <a href="categories.php" class="widget-link"><?php echo t('dash_all_categories'); ?></a>
            </div>
        </div>

    </div><!-- /.dashboard-grid -->


    <!-- System-Sektion -->
    <div style="margin-top: var(--vs-sp-5);">
        <h3 style="font-size:var(--vs-text-sm); font-weight:var(--vs-weight-medium);
                   color:var(--vs-text-muted); text-transform:uppercase; letter-spacing:.06em;
                   margin-bottom:var(--vs-sp-3);">
            <i class="ti ti-settings-2"></i> System
        </h3>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fill, minmax(160px,1fr)); gap:var(--vs-sp-3);">

            <a href="system.php" class="widget-card vs-system-link">
                <div class="widget-label"><i class="ti ti-info-circle"></i> <?php echo t('sys_info'); ?></div>
            </a>
            <a href="logs.php" class="widget-card vs-system-link">
                <div class="widget-label"><i class="ti ti-file-text"></i> <?php echo t('sys_logs'); ?></div>
            </a>
            <a href="login_security.php" class="widget-card vs-system-link">
                <div class="widget-label"><i class="ti ti-lock"></i> <?php echo t('sys_login_security'); ?></div>
            </a>
            <a href="2fa_setup.php" class="widget-card vs-system-link">
                <div class="widget-label"><i class="ti ti-shield-check"></i> <?php echo t('2fa_page_title'); ?></div>
            </a>
            <a href="health_check.php" class="widget-card vs-system-link">
                <div class="widget-label"><i class="ti ti-stethoscope"></i> Health Check</div>
            </a>
            <a href="backup.php" class="widget-card vs-system-link">
                <div class="widget-label"><i class="ti ti-device-floppy"></i> <?php echo t('sys_backup'); ?></div>
            </a>
            <a href="restore.php" class="widget-card vs-system-link">
                <div class="widget-label"><i class="ti ti-restore"></i> <?php echo t('sys_restore'); ?></div>
            </a>
            <?php if (function_exists('isSuperAdmin') && isSuperAdmin()): ?>
            <a href="logs_extended.php" class="widget-card vs-system-link">
                <div class="widget-label"><i class="ti ti-list-details"></i> <?php echo t('sys_extended_logs'); ?></div>
            </a>
            <?php endif; ?>

        </div>
    </div>

    <!-- Zweispaltig: Schnellzugriff-Erweiterung | Letzte Aktivitäten -->
    <div class="vs-two-col" style="margin-top: var(--vs-sp-5);">

        <!-- Letzte Aktivitäten -->
        <div class="widget-card" style="grid-column: 1 / -1;">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:var(--vs-sp-4);">
                <h3 style="margin:0; font-size:var(--vs-text-base); font-weight:var(--vs-weight-medium);">
                    <i class="ti ti-clock"></i> <?php echo t('act_recent_activity'); ?>
                </h3>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <a href="activity_log.php" class="vs-btn vs-btn-secondary vs-btn-sm">
                        <i class="ti ti-list"></i> <?php echo t('link_view_all'); ?>
                    </a>
                    <?php if (function_exists('darfSuperAdminAktionen') && darfSuperAdminAktionen()): ?>
                    <form method="POST" action="activity_log.php" style="display:flex; gap:6px; align-items:center;"
                          onsubmit="return vsConfirmForm(event, <?php echo htmlspecialchars(json_encode(t('log_confirm_delete_range')), ENT_QUOTES, 'UTF-8'); ?>);">
                        <?php echo Security::getCSRFInput(); ?>
                        <input type="hidden" name="action" value="cleanup">
                        <select name="cleanup_days" class="vs-select-sm">
                            <option value="7"><?php echo sprintf(t('act_older_than_days'), 7); ?></option>
                            <option value="30" selected><?php echo sprintf(t('act_older_than_days'), 30); ?></option>
                            <option value="90"><?php echo sprintf(t('act_older_than_days'), 90); ?></option>
                            <option value="365"><?php echo t('act_older_than_year'); ?></option>
                            <option value="0"><?php echo t('act_delete_all'); ?></option>
                        </select>
                        <button type="submit" class="vs-btn vs-btn-danger vs-btn-sm">
                            <i class="ti ti-trash"></i> <?php echo t('btn_delete'); ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($recentActivities)): ?>
                <div style="text-align:center; padding:32px; color:var(--vs-text-subtle);">
                    <i class="ti ti-chart-bar" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                    <p><?php echo t('act_none_yet'); ?></p>
                </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="data-table activity-compact">
                <thead>
                    <tr>
                        <th><?php echo t('act_col_user'); ?></th>
                        <th><?php echo t('act_col_action'); ?></th>
                        <th><?php echo t('act_col_item'); ?></th>
                        <th><?php echo t('act_col_time'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentActivities as $act): ?>
                    <tr>
                        <td>
                            <span class="activity-user">
                                <i class="ti ti-user" style="font-size:12px;"></i>
                                <?php echo htmlspecialchars($act['username'] ?? 'System'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="activity-badge activity-badge--<?php echo getActionClass($act['aktion']); ?>">
                                <?php echo getActionText($act['aktion']); ?>
                            </span>
                        </td>
                        <td style="max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            <?php echo htmlspecialchars($act['bezeichnung'] ?? $act['tabelle'] ?? '—'); ?>
                        </td>
                        <td style="white-space:nowrap; color:var(--vs-text-muted); font-size:var(--vs-text-xs);"
                            title="<?php echo date('d.m.Y H:i:s', strtotime($act['zeitstempel'])); ?>">
                            <?php echo timeAgo($act['zeitstempel']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /.vs-two-col -->
    
</main>

<!-- OPTIMIERTES CSS für Widgets -->
<style>
/* ============================================
   BACKEND MAIN LAYOUT - OPTIMIERT FÜR 2-4 SPALTEN
   ============================================ */

.backend-main {
    padding: 20px 40px 20px 10px;  /* Weniger links & rechts Padding */
    max-width: 100%;                /* Volle Breite nutzen */
}

/* Dashboard Grid - 2-4 Spalten je nach Breite */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));  /* Min 280px pro Box */
    gap: 18px;
    margin-right: 0;
}

/* Für große Bildschirme: 4 Spalten erzwingen */
@media (min-width: 1400px) {
    .dashboard-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* Für mittlere Bildschirme: 3 Spalten */
@media (min-width: 1100px) and (max-width: 1399px) {
    .dashboard-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Für kleinere Bildschirme: 2 Spalten */
@media (min-width: 768px) and (max-width: 1099px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Dashboard Rows - auch optimiert */
.dashboard-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

/* Für große Bildschirme: 2 Spalten für Row */
@media (min-width: 1200px) {
    .dashboard-row {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* ============================================
   OPTIMIERTE WIDGET STYLES
   25% kleinere Thumbnails, kompakteres Layout
   ============================================ */

/* Dashboard Row */
.dashboard-row {
    margin-bottom: 30px;
}

/* Widget Large - OPTIMIERT */
.widget-large {
    min-height: 320px;
    max-height: 420px;
    overflow-y: auto;
}

.widget-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px 12px;
    border-bottom: 1px solid #eee;
}

.widget-title h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: #333;
}

.widget-link-small {
    font-size: 12px;
    color: var(--vs-accent);
    text-decoration: none;
    transition: color 0.2s;
}

.widget-link-small:hover {
    color: var(--vs-accent-hover);
}

/* ============================================
   LETZTE UPLOADS - ULTIMATE FIX
   ============================================ */

.uploads-list {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.upload-item {
    border-radius: 6px;
    transition: background 0.2s;
}

.upload-item:hover {
    background: var(--vs-surface-2);
}

.upload-link {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    padding: 8px;
    text-decoration: none;
    color: inherit;
    gap: 12px;
}

.upload-thumbnail {
    width: 45px !important;
    height: 45px !important;
    min-width: 45px !important;
    max-width: 45px !important;
    min-height: 45px !important;
    max-height: 45px !important;
    border-radius: 6px;
    overflow: hidden !important;
    flex-shrink: 0 !important;
    flex-grow: 0 !important;
    background: var(--vs-surface-2);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.upload-thumbnail img {
    width: 45px !important;
    height: 45px !important;
    min-width: 45px !important;
    max-width: 45px !important;
    min-height: 45px !important;
    max-height: 45px !important;
    object-fit: cover !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
}

.no-image-small {
    font-size: 20px;
}

.upload-info {
    flex: 1;
    min-width: 0;
}

.upload-name {
    font-weight: 600;
    font-size: 13px;
    color: #333;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.upload-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 2px;
}

.badge-sm {
    font-size: 10px;
    padding: 2px 6px;
}

.badge-secondary {
    background: var(--vs-surface-2);
    color: var(--vs-text-muted);
}

.upload-price {
    font-weight: 600;
    font-size: 12px;
    color: var(--vs-success);
}

.upload-date {
    font-size: 11px;
    color: #999;
}

/* ============================================
   TOP ITEMS - OPTIMIERT
   ============================================ */

.top-items-list {
    padding: 12px;
}

.top-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    border-radius: 6px;
    margin-bottom: 6px;
    transition: background 0.2s;
}

.top-item:last-child {
    margin-bottom: 0;
}

.top-item:hover {
    background: var(--vs-surface-2);
}

.top-rank {
    font-size: 16px;
    font-weight: 700;
    color: var(--vs-accent);
    width: 25px;
    text-align: center;
    flex-shrink: 0;
}

.top-thumbnail {
    width: 40px !important;
    height: 40px !important;
    min-width: 40px !important;
    max-width: 40px !important;
    min-height: 40px !important;
    max-height: 40px !important;
    border-radius: 5px;
    overflow: hidden !important;
    flex-shrink: 0 !important;
    flex-grow: 0 !important;
    background: var(--vs-surface-2);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.top-thumbnail img {
    width: 40px !important;
    height: 40px !important;
    min-width: 40px !important;
    max-width: 40px !important;
    min-height: 40px !important;
    max-height: 40px !important;
    object-fit: cover !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
}

.top-info {
    flex: 1;
    min-width: 0;
}

.top-name {
    font-weight: 600;
    font-size: 13px;
    color: #333;
    text-decoration: none;
    display: block;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.top-name:hover {
    color: var(--vs-accent);
}

.top-meta {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.top-price {
    font-size: 14px;
    font-weight: 700;
    color: var(--vs-success);
    white-space: nowrap;
}

/* ============================================
   BACKUP STATUS - OPTIMIERT
   ============================================ */

.backup-status {
    padding: 15px;
}

.backup-icon {
    font-size: 40px;
    text-align: center;
    margin: 15px 0;
}

.backup-info {
    margin-bottom: 12px;
}

.backup-label {
    font-size: 11px;
    color: #999;
    margin-bottom: 3px;
}

.backup-value {
    font-size: 13px;
    font-weight: 600;
    color: #333;
}

/* ============================================
   SPEICHERPLATZ - OPTIMIERT
   ============================================ */

.storage-stats {
    padding: 15px;
}

.storage-bar-container {
    height: 26px;
    background: var(--vs-surface-2);
    border-radius: 13px;
    overflow: hidden;
    margin-bottom: 15px;
    position: relative;
}

.storage-bar {
    height: 100%;
    background: var(--vs-accent);
    transition: width 0.6s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
}

.storage-bar-text {
    color: white;
    font-weight: 600;
    font-size: 12px;
}

.storage-details {
    margin-top: 15px;
}

.storage-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.storage-item:last-of-type {
    border-bottom: none;
}

.storage-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.storage-item-info {
    flex: 1;
}

.storage-item-label {
    font-size: 12px;
    font-weight: 600;
    color: #333;
    margin-bottom: 2px;
}

.storage-item-value {
    font-size: 11px;
    color: #999;
}

.storage-summary {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 2px solid #f0f0f0;
    font-size: 13px;
    color: #333;
}

/* ============================================
   SCHNELLZUGRIFF - OPTIMIERT
   ============================================ */

.quick-access-list {
    padding: 12px;
}

.quick-access-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    border-radius: 6px;
    text-decoration: none;
    color: inherit;
    transition: background 0.2s;
    margin-bottom: 6px;
}

.quick-access-item:last-child {
    margin-bottom: 0;
}

.quick-access-item:hover {
    background: var(--vs-surface-2);
}

.quick-access-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.quick-access-info {
    flex: 1;
    min-width: 0;
}

.quick-access-name {
    font-weight: 600;
    font-size: 13px;
    color: #333;
    margin-bottom: 2px;
}

.quick-access-meta {
    font-size: 11px;
    color: #999;
}

.quick-access-arrow {
    font-size: 16px;
    color: var(--vs-accent);
    flex-shrink: 0;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1200px) {
    .dashboard-row {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 768px) {
    .widget-large {
        min-height: auto;
        max-height: none;
    }
    
    .upload-link {
        gap: 8px;
    }
    
    .upload-thumbnail {
        width: 40px;
        height: 40px;
    }
    
    .top-item {
        gap: 6px;
    }
    
    .top-rank {
        font-size: 14px;
        width: 20px;
    }
    
    .top-thumbnail {
        width: 35px;
        height: 35px;
    }
    
    .storage-icon,
    .quick-access-icon {
        font-size: 18px;
    }
}

/* ============================================
   SCROLLBAR STYLING
   ============================================ */

.widget-large::-webkit-scrollbar {
    width: 6px;
}

.widget-large::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.widget-large::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.widget-large::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<?php 
// Helper-Funktion für Dateigröße
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2, ',', '.') . ' KB';
        } else {
            return $bytes . ' Bytes';
        }
    }
}

include 'layout/footer_next_page.php'; 
?>
