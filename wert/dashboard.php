<?php
// dashboard.php - Hauptseite mit Statistiken und Übersicht (MIT ÜBERSETZUNGEN)
require_once 'db.php';
require_once 'helpers.php';
requireLogin();

define('PAGE_TITLE', t('dashboard_title') . ' - ' . t('app_title'));

// Statistiken laden
try {
    // Bei "nur eigene" zaehlen nur eigene Gegenstaende (bis 4.3.32 zeigte
    // die Seite Summen und Top-5 mit Namen/Bildern ALLER Gegenstaende).
    [$ownSql, $ownParams] = nurEigeneSql('w');
    [$ownSqlT, $ownParamsT] = nurEigeneSql('');
    $abfrage = function (string $sql, array $params) use ($pdo) {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st;
    };

    // Gesamtwert und Anzahl
    $stats = $abfrage("
        SELECT 
            COUNT(*) as total_items,
            COALESCE(SUM(preis), 0) as total_value,
            COALESCE(AVG(preis), 0) as avg_price,
            COALESCE(SUM(aktueller_wert), 0) as total_zeitwert,
            COUNT(CASE WHEN aktueller_wert IS NOT NULL AND aktueller_wert > 0 THEN 1 END) as items_bewertet,
            MAX(aktueller_wert) as max_zeitwert
        FROM wertsachen
        WHERE 1=1" . $ownSqlT . "
    ", $ownParamsT)->fetch();
    
    // Name des wertvollsten Gegenstands nach Zeitwert
    try {
        $maxZeitwertItem = $db->selectOne(
            "SELECT name FROM wertsachen WHERE aktueller_wert IS NOT NULL AND aktueller_wert > 0" . $ownSqlT . " ORDER BY aktueller_wert DESC LIMIT 1",
            $ownParamsT
        );
    } catch (Exception $e) { $maxZeitwertItem = null; }

    // Trend: Gesamtwert vor 30 Tagen (aus wert_historie)
    try {
        $trend = $db->selectOne("
            SELECT COALESCE(SUM(h.wert), 0) as wert_30d
            FROM (
                SELECT wertsache_id, MAX(datum) as max_datum
                FROM wert_historie
                WHERE datum <= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                  AND wertsache_id IN (SELECT id FROM wertsachen WHERE 1=1" . $ownSqlT . ")
                GROUP BY wertsache_id
            ) latest
            JOIN wert_historie h ON h.wertsache_id = latest.wertsache_id
                                 AND h.datum = latest.max_datum
        ", $ownParamsT);
        $wert_30d = floatval($trend['wert_30d'] ?? 0);
        $trend_diff = floatval($stats['total_value']) - $wert_30d;
        $trend_pct  = $wert_30d > 0 ? round(($trend_diff / $wert_30d) * 100, 1) : null;
    } catch (Exception $e) {
        $trend_diff = 0;
        $trend_pct  = null;
    }

    // Wert pro Kategorie
    $categoryStats = $abfrage("
        SELECT 
            k.name as kategorie,
            COUNT(w.id) as anzahl,
            COALESCE(SUM(COALESCE(w.aktueller_wert, w.preis)), 0) as wert
        FROM kategorien k
        LEFT JOIN wertsachen w ON k.id = w.kategorie_id" . $ownSql . "
        GROUP BY k.id, k.name
        ORDER BY wert DESC
    ", $ownParams)->fetchAll();
    
    // Anzahl pro Ort
    $locationStats = $abfrage("
        SELECT 
            o.name as ort,
            COUNT(w.id) as anzahl
        FROM raeume o
        LEFT JOIN wertsachen w ON o.id = w.raum_id" . $ownSql . "
        GROUP BY o.id, o.name
        HAVING anzahl > 0
        ORDER BY anzahl DESC
    ", $ownParams)->fetchAll();
    
    // Top 5 wertvollste Gegenstände (nach Zeitwert, Fallback auf Kaufpreis)
    $topItems = $abfrage("
        SELECT 
            w.id,
            w.name,
            w.preis,
            w.aktueller_wert,
            w.bild,
            k.name as kategorie,
            o.name as ort
        FROM wertsachen w
        LEFT JOIN kategorien k ON w.kategorie_id = k.id
        LEFT JOIN raeume o ON w.raum_id = o.id
        WHERE 1=1" . $ownSql . "
        ORDER BY COALESCE(w.aktueller_wert, w.preis) DESC
        LIMIT 5
    ", $ownParams)->fetchAll();
    
    // Zuletzt hinzugefügt
    $recentItems = $abfrage("
        SELECT 
            w.id,
            w.name,
            w.preis,
            w.bild,
            w.gelistet_am,
            k.name as kategorie
        FROM wertsachen w
        LEFT JOIN kategorien k ON w.kategorie_id = k.id
        WHERE 1=1" . $ownSql . "
        ORDER BY w.gelistet_am DESC, w.id DESC
        LIMIT 5
    ", $ownParams)->fetchAll();
    
    // Wertvollste Kategorie
    $topCategory = !empty($categoryStats) ? $categoryStats[0] : null;

    // Schadenfall-Bereitschaft
    $b_score = 0; $b_foto_pct = 0; $b_preis_pct = 0; $b_datum_pct = 0;
    try {
        $b_stmt = $abfrage("SELECT COUNT(*) as total, COUNT(CASE WHEN bild IS NOT NULL AND bild != '' THEN 1 END) as mit_foto, COUNT(CASE WHEN preis IS NOT NULL AND preis > 0 THEN 1 END) as mit_preis, COUNT(CASE WHEN kaufdatum IS NOT NULL THEN 1 END) as mit_datum FROM wertsachen WHERE 1=1" . $ownSqlT, $ownParamsT);
        if ($b_stmt) {
            $b = $b_stmt->fetch(PDO::FETCH_ASSOC);
            if ($b && (int)$b['total'] > 0) {
                $bn = (int)$b['total'];
                $b_score     = round(((int)$b['mit_foto'] + (int)$b['mit_preis'] + (int)$b['mit_datum']) / ($bn * 3) * 100);
                $b_foto_pct  = round((int)$b['mit_foto']  / $bn * 100);
                $b_preis_pct = round((int)$b['mit_preis'] / $bn * 100);
                $b_datum_pct = round((int)$b['mit_datum'] / $bn * 100);
            }
        }
    } catch (Exception $e) { /* Bereitschaft bleibt 0% */ }

} catch (PDOException $e) {
    die('Fehler beim Laden der Statistiken.');
}

include 'header_next_page.php';
?>

<style>
.dashboard {
    padding: 0;
}

.dashboard-header {
    background: var(--vs-accent);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: var(--vs-shadow-resting);
}

.dashboard-header h1 {
    margin: 0 0 10px 0;
    font-size: 2em;
}

.dashboard-header p {
    margin: 0;
    opacity: 0.9;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: var(--vs-shadow-resting);
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: none;
    box-shadow: var(--vs-shadow-hovered);
}

.stat-icon {
    font-size: 2.5em;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 2em;
    font-weight: bold;
    color: var(--primary-color, #3498db);
    margin: 10px 0;
}

.stat-label {
    color: #666;
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-sublabel {
    color: #999;
    font-size: 0.85em;
    margin-top: 5px;
}
.bereit-card { background: white; border-radius: 12px; padding: 22px 25px; box-shadow: var(--vs-shadow-resting); margin-bottom: 30px; }
.bereit-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px; }
.bereit-title { font-size: 15px; font-weight: 700; color: #333; }
.bereit-score { font-size: 28px; font-weight: 800; }
.bereit-bar-outer { height: 14px; background: #f0f0f0; border-radius: 7px; overflow: hidden; margin-bottom: 16px; }
.bereit-bar-inner { height: 100%; border-radius: 7px; }
.bereit-criteria { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.bereit-criterion { background: #f8f9fa; border-radius: 8px; padding: 10px 12px; text-align: center; }
.bereit-criterion-pct { font-size: 18px; font-weight: 700; margin-bottom: 2px; }
.bereit-criterion-label { font-size: 11px; color: #888; text-transform: uppercase; }
.bereit-tip { margin-top: 12px; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 10px; }

.content-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.dashboard-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: var(--vs-shadow-resting);
}

.dashboard-card h3 {
    margin: 0 0 20px 0;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.item-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.item-list li {
    padding: 12px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 15px;
}

.item-list li:last-child {
    border-bottom: none;
}

.item-thumb {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
}

.item-thumb-placeholder {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 24px;
    flex-shrink: 0;
}

.item-info {
    flex: 1;
}

.item-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}

.item-meta {
    font-size: 0.85em;
    color: #666;
}

.item-price {
    font-weight: bold;
    color: var(--primary-color, #3498db);
    white-space: nowrap;
}

.chart-container {
    margin-top: 20px;
    min-height: 300px;
}

.chart-bar {
    margin-bottom: 15px;
}

.chart-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    font-size: 0.9em;
}

.chart-bar-bg {
    background: #f0f0f0;
    border-radius: 8px;
    height: 30px;
    position: relative;
    overflow: hidden;
}

.chart-bar-fill {
    background: var(--vs-accent);
    height: 100%;
    border-radius: 8px;
    transition: width 0.6s ease;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 10px;
    color: white;
    font-weight: 600;
    font-size: 0.85em;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 30px;
}

.action-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    text-decoration: none;
    color: #333;
    box-shadow: var(--vs-shadow-resting);
    transition: all 0.3s;
}

.action-card:hover {
    transform: none;
    box-shadow: var(--vs-shadow-hovered);
    color: var(--primary-color, #3498db);
}

.action-icon {
    font-size: 2.5em;
    margin-bottom: 10px;
}

.action-label {
    font-weight: 600;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #999;
}

.empty-state-icon {
    font-size: 4em;
    margin-bottom: 20px;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        padding: 15px;
        border-radius: 8px;
    }
    
    .stat-icon {
        font-size: 1.8em;
        margin-bottom: 6px;
    }
    
    .stat-value {
        font-size: 1.3em;
        margin: 6px 0;
    }
    
    .stat-label {
        font-size: 0.75em;
    }
    
    .stat-sublabel {
        font-size: 0.7em;
        margin-top: 3px;
    }
    
    .dashboard-header {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .dashboard-header h1 {
        font-size: 1.5em;
    }
    
    .dashboard-header p {
        font-size: 0.9em;
    }
    
    .content-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="dashboard">
    <div class="dashboard-header">
        <h1><i class="ti ti-chart-pie" aria-hidden="true"></i> <?php echo t('dashboard_title'); ?></h1>
        <p><?php echo t('dashboard_welcome'); ?>, <?php echo htmlspecialchars($_SESSION['username']); ?>! <?php echo t('dashboard_welcome_message'); ?></p>
    </div>

    <!-- Statistik-Karten -->
    <?php
    if ($b_score >= 75) { $b_color = '#27ae60'; $b_label = 'Gut'; }
    elseif ($b_score >= 50) { $b_color = '#f39c12'; $b_label = 'Mittel'; }
    else { $b_color = '#e74c3c'; $b_label = 'Gering'; }
    ?>
    <div class="bereit-card">
        <div class="bereit-header">
            <div class="bereit-title"><?php echo t('claims_readiness'); ?></div>
            <div class="bereit-score" style="color:<?php echo $b_color; ?>"><?php echo $b_score; ?>%</div>
        </div>
        <div class="bereit-bar-outer">
            <div class="bereit-bar-inner" style="width:<?php echo $b_score; ?>%; background:<?php echo $b_color; ?>;"></div>
        </div>
        <div class="bereit-criteria">
            <div class="bereit-criterion">
                <div class="bereit-criterion-pct" style="color:<?php echo $b_foto_pct >= 75 ? '#27ae60' : ($b_foto_pct >= 50 ? '#f39c12' : '#e74c3c'); ?>"><?php echo $b_foto_pct; ?>%</div>
                <div class="bereit-criterion-label"><?php echo t('readiness_photo'); ?></div>
            </div>
            <div class="bereit-criterion">
                <div class="bereit-criterion-pct" style="color:<?php echo $b_preis_pct >= 75 ? '#27ae60' : ($b_preis_pct >= 50 ? '#f39c12' : '#e74c3c'); ?>"><?php echo $b_preis_pct; ?>%</div>
                <div class="bereit-criterion-label"><?php echo t('readiness_price'); ?></div>
            </div>
            <div class="bereit-criterion">
                <div class="bereit-criterion-pct" style="color:<?php echo $b_datum_pct >= 75 ? '#27ae60' : ($b_datum_pct >= 50 ? '#f39c12' : '#e74c3c'); ?>"><?php echo $b_datum_pct; ?>%</div>
                <div class="bereit-criterion-label"><?php echo t('readiness_purchase_date'); ?></div>
            </div>
        </div>
        <div class="bereit-tip">
            <?php if ($b_score >= 75): ?><?php echo t('readiness_perfect_msg'); ?>
            <?php elseif ($b_score >= 50): ?>Mittel — einige Felder fehlen noch.
            <?php else: ?>Bitte Fotos, Preise und Kaufdaten ergaenzen.<?php endif; ?>
            <?php if ($b_score < 100): ?> &nbsp;&#183;&nbsp;<a href="index.php" style="color:#3498db;">Inventar vervollstaendigen</a><?php endif; ?>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="ti ti-currency-euro"></i></div>
            <div class="stat-value" data-count="<?php echo round($stats['total_value']); ?>" data-type="currency"><?php echo formatPriceLocalized($stats['total_value']); ?></div>
            <div class="stat-label"><?php echo t('dashboard_total_value'); ?></div>
            <?php if ($trend_pct !== null): ?>
            <div class="stat-trend <?php echo $trend_diff >= 0 ? 'trend-up' : 'trend-down'; ?>">
                <?php echo $trend_diff >= 0 ? '↑' : '↓'; ?>
                <?php echo abs($trend_pct); ?>% <span><?php echo t('dashboard_vs_30days'); ?></span>
            </div>
            <?php else: ?>
            <div class="stat-sublabel"><?php echo t('dashboard_total_value_desc'); ?></div>
            <?php endif; ?>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="ti ti-package"></i></div>
            <div class="stat-value" data-count="<?php echo $stats['total_items']; ?>" data-type="integer"><?php echo number_format($stats['total_items'], 0, ',', '.'); ?></div>
            <div class="stat-label"><?php echo t('dashboard_total_items'); ?></div>
            <div class="stat-sublabel"><?php echo t('dashboard_total_items_desc'); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="ti ti-chart-bar"></i></div>
            <div class="stat-value" data-count="<?php echo round($stats['total_zeitwert']); ?>" data-type="currency"><?php echo formatPriceLocalized($stats['total_zeitwert']); ?></div>
            <div class="stat-label"><?php echo t('dashboard_zeitwert'); ?></div>
            <div class="stat-sublabel"><?php echo $stats['items_bewertet']; ?> <?php echo t('dashboard_zeitwert_von'); ?> <?php echo $stats['total_items']; ?> <?php echo t('dashboard_zeitwert_bewertet'); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="ti ti-diamond"></i></div>
            <div class="stat-value" data-count="<?php echo round($stats['max_zeitwert'] ?? 0); ?>" data-type="currency"><?php echo $stats['max_zeitwert'] ? formatPriceLocalized($stats['max_zeitwert']) : '—'; ?></div>
            <div class="stat-label"><?php echo t('dashboard_max_value'); ?></div>
            <div class="stat-sublabel"><?php echo $maxZeitwertItem ? htmlspecialchars($maxZeitwertItem['name']) : t('dashboard_max_value_desc'); ?></div>
        </div>
    </div>

    <!-- Wertvollste Kategorie Highlight -->
    <?php if ($topCategory && $topCategory['wert'] > 0): ?>
    <div class="dashboard-card" style="background: var(--vs-surface-2); margin-bottom: 30px;">
        <h3><i class="ti ti-trophy" aria-hidden="true"></i> <?php echo t('dashboard_top_category'); ?></h3>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 1.5em; font-weight: bold; color: #333; margin-bottom: 5px;">
                    <?php echo htmlspecialchars($topCategory['kategorie']); ?>
                </div>
                <div style="color: #666;">
                    <?php 
                    echo $topCategory['anzahl'] . ' ';
                    echo $topCategory['anzahl'] == 1 ? t('dashboard_item_singular') : t('dashboard_item_plural');
                    ?>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 2em; font-weight: bold; color: var(--primary-color, #3498db);">
                    <?php echo formatPriceLocalized($topCategory['wert']); ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Content Grid -->
    <div class="content-grid">
        <!-- Top 5 Wertvollste -->
        <div class="dashboard-card">
            <h3><i class="ti ti-diamond" aria-hidden="true"></i> <?php echo t('dashboard_top_5_valuable'); ?></h3>
            <?php if (!empty($topItems)): ?>
                <ul class="item-list">
                    <?php foreach ($topItems as $item): ?>
                        <li>
                            <?php if ($item['bild'] && file_exists(UPLOAD_DIR . $item['bild'])): ?>
                                <img src="upload/<?php echo htmlspecialchars($item['bild']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="item-thumb">
                            <?php else: ?>
                                <div class="item-thumb-placeholder"><i class="ti ti-package"></i></div>
                            <?php endif; ?>
                            
                            <div class="item-info">
                                <div class="item-name">
                                    <a href="edit.php?id=<?php echo $item['id']; ?>" style="text-decoration: none; color: inherit;">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                </div>
                                <div class="item-meta">
                                    <?php if ($item['kategorie']): ?>
                                        <i class="ti ti-folder" aria-hidden="true"></i> <?php echo htmlspecialchars($item['kategorie']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="item-price">
                                <?php echo formatPriceLocalized(floatval($item["aktueller_wert"] ?? $item["preis"])); ?>
                                <?php if (!empty($item["aktueller_wert"])): ?>
                                    <div style="font-size:11px; color:#999; font-weight:400;">EK: <?php echo formatPriceLocalized($item["preis"]); ?></div>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="ti ti-inbox"></i></div>
                    <p><?php echo t('dashboard_no_items'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Zuletzt hinzugefügt -->
        <div class="dashboard-card">
            <h3><i class="ti ti-clock" aria-hidden="true"></i> <?php echo t('dashboard_recently_added'); ?></h3>
            <?php if (!empty($recentItems)): ?>
                <ul class="item-list">
                    <?php foreach ($recentItems as $item): ?>
                        <li>
                            <?php if ($item['bild'] && file_exists(UPLOAD_DIR . $item['bild'])): ?>
                                <img src="upload/<?php echo htmlspecialchars($item['bild']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="item-thumb">
                            <?php else: ?>
                                <div class="item-thumb-placeholder"><i class="ti ti-package"></i></div>
                            <?php endif; ?>
                            
                            <div class="item-info">
                                <div class="item-name">
                                    <a href="edit.php?id=<?php echo $item['id']; ?>" style="text-decoration: none; color: inherit;">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                </div>
                                <div class="item-meta">
                                    <?php echo formatDateLocalized($item['gelistet_am']); ?>
                                    <?php if ($item['kategorie']): ?>
                                        · <?php echo htmlspecialchars($item['kategorie']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="item-price">
                                <?php echo formatPriceLocalized($item['preis']); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="ti ti-inbox"></i></div>
                    <p><?php echo t('dashboard_no_items'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Wert pro Kategorie Chart -->
    <?php if (!empty($categoryStats) && $categoryStats[0]['wert'] > 0): ?>
    <div class="dashboard-card" style="margin-bottom: 30px;">
        <h3><i class="ti ti-chart-bar" aria-hidden="true"></i> <?php echo t('dashboard_value_by_category'); ?></h3>
        <div class="chart-container">
            <?php 
            $maxValue = $categoryStats[0]['wert'];
            foreach ($categoryStats as $cat): 
                if ($cat['wert'] == 0) continue;
                $percentage = $maxValue > 0 ? ($cat['wert'] / $maxValue * 100) : 0;
            ?>
                <div class="chart-bar">
                    <div class="chart-label">
                        <span><?php echo htmlspecialchars($cat['kategorie']); ?> (<?php echo $cat['anzahl']; ?>)</span>
                        <span><?php echo formatPriceLocalized($cat['wert']); ?></span>
                    </div>
                    <div class="chart-bar-bg">
                        <div class="chart-bar-fill" style="width: <?php echo $percentage; ?>%">
                            <?php if ($percentage > 20): ?>
                                <?php echo round($percentage); ?>%
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <h3 style="margin-bottom: 15px;"><i class="ti ti-bolt" aria-hidden="true"></i> <?php echo t('dashboard_quick_actions'); ?></h3>
    <div class="quick-actions">
        <?php if (canEdit()): ?>
        <a href="add.php" class="action-card">
            <div class="action-icon"><i class="ti ti-plus"></i></div>
            <div class="action-label"><?php echo t('dashboard_new_item'); ?></div>
        </a>
        <?php endif; ?>
        
        <a href="index.php" class="action-card">
            <div class="action-icon"><i class="ti ti-clipboard-list"></i></div>
            <div class="action-label"><?php echo t('dashboard_all_items'); ?></div>
        </a>
        
        <a href="export_pdf.php" class="action-card">
            <div class="action-icon"><i class="ti ti-file-text"></i></div>
            <div class="action-label"><?php echo t('dashboard_pdf_export'); ?></div>
        </a>
        
        <a href="backend/backup.php" class="action-card">
            <div class="action-icon"><i class="ti ti-device-floppy"></i></div>
            <div class="action-label"><?php echo t('dashboard_create_backup'); ?></div>
        </a>
        
        <?php if (canEdit()): ?>
        <a href="backend/categories.php" class="action-card">
            <div class="action-icon"><i class="ti ti-tag"></i></div>
            <div class="action-label"><?php echo t('dashboard_categories'); ?></div>
        </a>
        
        <a href="backend/locations.php" class="action-card">
            <div class="action-icon"><i class="ti ti-map-pin"></i></div>
            <div class="action-label"><?php echo t('dashboard_locations'); ?></div>
        </a>
        <?php endif; ?>
        
        <a href="settings.php" class="action-card">
            <div class="action-icon"><i class="ti ti-settings"></i></div>
            <div class="action-label"><?php echo t('dashboard_settings'); ?></div>
        </a>
        
        <?php if (isAdmin()): ?>
        <a href="backend/users.php" class="action-card">
            <div class="action-icon"><i class="ti ti-users"></i></div>
            <div class="action-label"><?php echo t('dashboard_users'); ?></div>
        </a>
        <?php endif; ?>
    </div>
</div>

<?php /* Hier stand bis 4.3.29 der Kasten "Letzte Aktivitaet". Er erschien
   nie: Die Abfrage sortierte nach der Spalte timestamp (heisst zeitstempel),
   $db->select() schluckte den Fehler. Repariert haette er jedem Benutzer,
   auch Lesern, die Aenderungen ALLER Benutzer gezeigt - gegen die Einstellung
   "nur eigene Gegenstaende". Entfernt (Entscheidung 29.09.2026); das
   Aktivitaetsprotokoll steht Admins in backend/logs.php zur Verfuegung. */ ?>

<?php include 'footer_next_page.php'; ?>
