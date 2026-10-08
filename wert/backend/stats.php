<?php
/**
 * Backend Statistiken - ANGEPASST FÜR NEUES LAYOUT
 */

require_once 'config.php';
requireBackendAccess();

$pageTitle = t('nav_stats');

// Top Kategorien laden
try {
    $topKategorien = $db->select("
        SELECT k.name, COUNT(w.id) as anzahl, SUM(w.preis) as wert
        FROM kategorien k
        LEFT JOIN wertsachen w ON k.id = w.kategorie_id
        GROUP BY k.id
        HAVING anzahl > 0
        ORDER BY anzahl DESC
        LIMIT 10
    ");
} catch (Exception $e) {
    $topKategorien = [];
}

// Top Orte laden
try {
    $topOrte = $db->select("
        SELECT o.name, COUNT(w.id) as anzahl, SUM(w.preis) as wert
        FROM raeume o
        LEFT JOIN wertsachen w ON o.id = w.raum_id
        GROUP BY o.id
        HAVING anzahl > 0
        ORDER BY anzahl DESC
        LIMIT 10
    ");
} catch (Exception $e) {
    $topOrte = [];
}

// Top Wertvollste Gegenstände laden
try {
    $topItems = $db->select("
        SELECT w.*, k.name as kategorie
        FROM wertsachen w
        LEFT JOIN kategorien k ON w.kategorie_id = k.id
        ORDER BY w.preis DESC
        LIMIT 10
    ");
} catch (Exception $e) {
    $topItems = [];
}

// Statistiken berechnen
$stats = [
    'kategorien_total' => count($topKategorien),
    'kategorien_items' => array_sum(array_column($topKategorien, 'anzahl')),
    'orte_total' => count($topOrte),
    'orte_items' => array_sum(array_column($topOrte, 'anzahl')),
    'items_total' => count($topItems),
    'items_value' => array_sum(array_column($topItems, 'preis'))
];

// --- Wertentwicklungs-Chart ---
$chartLabels = [];
$chartValues = [];

try {
    // Alle History-Einträge chronologisch (Spalten: wertsache_id, wert, datum)
    $allHistory = $db->select("
        SELECT wertsache_id, wert, DATE_FORMAT(datum, '%Y-%m') as monat
        FROM wert_historie
        ORDER BY datum ASC, erstellt_am ASC
    ");

    // Alle Items mit Basispreis
    $alleItems = $db->select("SELECT id, COALESCE(preis, 0) as preis FROM wertsachen");
    $basePriceMap = [];
    foreach ($alleItems as $item) {
        $basePriceMap[$item['id']] = floatval($item['preis']);
    }
    $gesamtBasis = array_sum($basePriceMap);

    // Pro Monat: letzten bekannten Wert je Item merken, Summe berechnen
    $currentWertMap = []; // wertsache_id => aktueller historischer Wert
    $monthlyData = [];    // 'YYYY-MM' => Gesamtvermögen

    foreach ($allHistory as $entry) {
        $currentWertMap[$entry['wertsache_id']] = floatval($entry['wert']);
        // Gesamtvermögen = Basis aller Items, überschrieben durch History-Werte
        $gesamt = $gesamtBasis;
        foreach ($currentWertMap as $itemId => $histWert) {
            $gesamt += $histWert - ($basePriceMap[$itemId] ?? 0);
        }
        $monthlyData[$entry['monat']] = round($gesamt, 2);
    }

    ksort($monthlyData);

    // Heutigen Monat als letzten Punkt anfügen (falls noch nicht vorhanden)
    $heute = date('Y-m');
    if (!empty($monthlyData) && !isset($monthlyData[$heute])) {
        $gesamt = $gesamtBasis;
        foreach ($currentWertMap as $itemId => $histWert) {
            $gesamt += $histWert - ($basePriceMap[$itemId] ?? 0);
        }
        $monthlyData[$heute] = round($gesamt, 2);
    }

    $months = t('stats_months') ?: ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];
    $monatNamen = array_combine(['01','02','03','04','05','06','07','08','09','10','11','12'], $months);
    foreach ($monthlyData as $monat => $wert) {
        [$jahr, $mon] = explode('-', $monat);
        $chartLabels[] = ($monatNamen[$mon] ?? $mon) . ' ' . $jahr;
        $chartValues[] = $wert;
    }
} catch (Exception $e) {
    $chartLabels = [];
    $chartValues = [];
}

// --- Kategorie-Donut-Daten ---
$kategorieLabels = [];
$kategorieValues = [];
$kategorieColors = [
    '#667eea','#f39c12','#27ae60','#e74c3c','#3498db',
    '#9b59b6','#1abc9c','#e67e22','#764ba2','#95a5a6'
];
foreach ($topKategorien as $kat) {
    if (floatval($kat['wert']) > 0) {
        $kategorieLabels[] = $kat['name'];
        $kategorieValues[] = round(floatval($kat['wert']), 2);
    }
}

// Layout laden
include 'layout/header_next_page.php';
?>

<!-- Main Content -->
<main class="backend-main">
    
    <!-- Statistik-Karten -->
    <div class="dashboard-grid" style="margin-bottom: 30px;">
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value"><?php echo $stats['kategorien_total']; ?></div>
                    <div class="widget-label"><i class="ti ti-tag"></i> <?php echo t('stats_top_categories'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-tag"></i></div>
            </div>
            <div class="widget-footer">
                <div style="font-size: 12px; color: var(--vs-text-muted);">
                    <?php echo number_format($stats['kategorien_items'], 0, ',', '.'); ?> <?php echo t('stats_items'); ?>
                </div>
            </div>
        </div>
        
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value"><?php echo $stats['orte_total']; ?></div>
                    <div class="widget-label">📍 <?php echo t('loc_tab_rooms'); ?></div>
                </div>
                <div class="widget-icon">📍</div>
            </div>
            <div class="widget-footer">
                <div style="font-size: 12px; color: var(--vs-text-muted);">
                    <?php echo number_format($stats['orte_items'], 0, ',', '.'); ?> <?php echo t('stats_items'); ?>
                </div>
            </div>
        </div>
        
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value"><?php echo $stats['items_total']; ?></div>
                    <div class="widget-label">💎 <?php echo t('stats_top_items'); ?></div>
                </div>
                <div class="widget-icon">💎</div>
            </div>
            <div class="widget-footer">
                <div style="font-size: 12px; color: var(--vs-text-muted);">
                    <?php echo t('stats_most_valuable'); ?>
                </div>
            </div>
        </div>
        
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value" style="font-size: 24px;"><?php echo formatPrice($stats['items_value']); ?></div>
                    <div class="widget-label"><i class="ti ti-currency-euro"></i> <?php echo t('stats_top10_value'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-currency-euro"></i></div>
            </div>
            <div class="widget-footer">
                <div style="font-size: 12px; color: var(--vs-text-muted);">
                    <?php echo t('stats_top10_total_value'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top 10 Kategorien -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2><i class="ti ti-tag"></i> <?php echo t('stats_top10_categories'); ?></h2>
        
        <?php if (count($topKategorien) === 0): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 15px;"><i class="ti ti-tag"></i></div>
                <p><?php echo t('stats_no_categories'); ?></p>
            </div>
        <?php else: ?>
            <table class="backend-table">
                <thead>
                    <tr>
                        <th style="width: 80px;"><?php echo t('col_rang'); ?></th>
                        <th><?php echo t('col_kategorie'); ?></th>
                        <th style="width: 150px;"><?php echo t('col_anzahl'); ?></th>
                        <th style="width: 180px;"><?php echo t('col_gesamtwert'); ?></th>
                        <th style="width: 180px;"><?php echo t('stats_avg_value'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; foreach ($topKategorien as $kat): ?>
                        <tr>
                            <td>
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--vs-accent); color: var(--vs-surface); display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                    <?php echo $rank++; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($kat['name']); ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-info" style="font-size: 14px; padding: 6px 14px;">
                                    <?php echo number_format($kat['anzahl'], 0, ',', '.'); ?> <?php echo t('stats_pieces'); ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: var(--vs-success); font-size: 16px;">
                                    <?php echo formatPrice($kat['wert']); ?>
                                </strong>
                            </td>
                            <td>
                                <span style="color: #666;">
                                    <?php echo formatPrice($kat['anzahl'] > 0 ? $kat['wert'] / $kat['anzahl'] : 0); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Top 10 Raeume (bis 4.3.30 "Top 10 Orte" beschriftet; gezaehlt wird raeume) -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2>📍 Top 10 <?php echo t('loc_tab_rooms'); ?></h2>
        
        <?php if (count($topOrte) === 0): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 15px;">📍</div>
                <p><?php echo t('stats_no_locations'); ?></p>
            </div>
        <?php else: ?>
            <table class="backend-table">
                <thead>
                    <tr>
                        <th style="width: 80px;"><?php echo t('col_rang'); ?></th>
                        <th>Ort</th>
                        <th style="width: 150px;"><?php echo t('col_anzahl'); ?></th>
                        <th style="width: 180px;"><?php echo t('col_gesamtwert'); ?></th>
                        <th style="width: 180px;"><?php echo t('stats_avg_value'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; foreach ($topOrte as $ort): ?>
                        <tr>
                            <td>
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--vs-accent); color: var(--vs-surface); display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                    <?php echo $rank++; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($ort['name']); ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-info" style="font-size: 14px; padding: 6px 14px;">
                                    <?php echo number_format($ort['anzahl'], 0, ',', '.'); ?> <?php echo t('stats_pieces'); ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: var(--vs-success); font-size: 16px;">
                                    <?php echo formatPrice($ort['wert']); ?>
                                </strong>
                            </td>
                            <td>
                                <span style="color: #666;">
                                    <?php echo formatPrice($ort['anzahl'] > 0 ? $ort['wert'] / $ort['anzahl'] : 0); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Top 10 Wertvollste Gegenstände -->
    <div class="activity-timeline">
        <h2>💎 <?php echo t('stats_top10_valuable'); ?></h2>
        
        <?php if (count($topItems) === 0): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 15px;">💎</div>
                <p><?php echo t('stats_no_items'); ?></p>
            </div>
        <?php else: ?>
            <table class="backend-table">
                <thead>
                    <tr>
                        <th style="width: 80px;"><?php echo t('col_rang'); ?></th>
                        <th>Name</th>
                        <th style="width: 200px;"><?php echo t('col_kategorie'); ?></th>
                        <th style="width: 180px;"><?php echo t('col_preis'); ?></th>
                        <th style="width: 100px;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; foreach ($topItems as $item): ?>
                        <tr>
                            <td>
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--vs-accent); color: var(--vs-surface); display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                    <?php echo $rank++; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <?php if (!empty($item['beschreibung'])): ?>
                                    <br><small style="color: #999;"><?php echo htmlspecialchars(mb_substr($item['beschreibung'], 0, 60)) . (mb_strlen($item['beschreibung']) > 60 ? '...' : ''); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-secondary">
                                    <?php echo htmlspecialchars($item['kategorie'] ?? 'Keine'); ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: var(--vs-success); font-size: 18px;">
                                    <?php echo formatPrice($item['preis']); ?>
                                </strong>
                            </td>
                            <td>
                                <a href="../edit.php?id=<?php echo $item['id']; ?>" class="vs-btn vs-btn-sm" target="_blank">
                                    👁️ Ansehen
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Wertentwicklung über Zeit -->
    <div class="activity-timeline" style="margin-top: 30px; margin-bottom: 30px;">
        <h2><i class="ti ti-trending-up"></i> <?php echo t('stats_value_trend'); ?></h2>
        <?php if (empty($chartLabels)): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 15px;"><i class="ti ti-trending-up"></i></div>
                <p><?php echo t('stats_no_history'); ?></p>
            </div>
        <?php else: ?>
            <div style="position: relative; height: 300px; padding: 10px 0;">
                <canvas id="wertChart"></canvas>
            </div>
        <?php endif; ?>
    </div>

    <!-- Vermögen nach Kategorie -->
    <?php if (!empty($kategorieValues)): ?>
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2>🥧 <?php echo t('stats_wealth_by_category'); ?></h2>
        <div style="display: flex; align-items: center; gap: 40px; flex-wrap: wrap; padding: 10px 0;">
            <div style="position: relative; height: 280px; width: 280px; flex-shrink: 0;">
                <canvas id="kategorieChart"></canvas>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <?php foreach ($kategorieLabels as $i => $label): ?>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <div style="width: 14px; height: 14px; border-radius: 3px; background: <?php echo $kategorieColors[$i % count($kategorieColors)]; ?>; flex-shrink: 0;"></div>
                        <span style="flex: 1; font-size: 14px;"><?php echo htmlspecialchars($label); ?></span>
                        <strong style="font-size: 14px; color: var(--vs-success);"><?php echo formatPrice($kategorieValues[$i]); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</main>

<script src="../js/chart.umd.min.js"></script>
<script>
<?php if (!empty($chartLabels)): ?>
(function() {
    const ctx = document.getElementById('wertChart');
    if (!ctx) return;

    const labels = <?php echo json_encode($chartLabels); ?>;
    const values = <?php echo json_encode($chartValues); ?>;

    // Gradient
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '<?php echo t("stats_total_wealth") ?: "Gesamtvermögen"; ?>',
                data: values,
                borderColor: '#667eea',
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const {ctx: c, chartArea} = chart;
                    if (!chartArea) return 'rgba(102,126,234,0.1)';
                    const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(102,126,234,0.3)');
                    gradient.addColorStop(1, 'rgba(102,126,234,0.0)');
                    return gradient;
                },
                borderWidth: 2.5,
                pointRadius: labels.length <= 12 ? 5 : 3,
                pointHoverRadius: 7,
                pointBackgroundColor: '#667eea',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            const val = ctx.parsed.y;
                            return ' ' + val.toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxRotation: 45 }
                },
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(val) {
                            return val.toLocaleString('de-DE', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' €';
                        }
                    }
                }
            }
        }
    });
})();
<?php endif; ?>

<?php if (!empty($kategorieValues)): ?>
(function() {
    const ctx = document.getElementById('kategorieChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($kategorieLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($kategorieValues); ?>,
                backgroundColor: <?php echo json_encode(array_slice($kategorieColors, 0, count($kategorieValues))); ?>,
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            const val = ctx.parsed;
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = ((val / total) * 100).toFixed(1);
                            return ' ' + val.toLocaleString('de-DE', {minimumFractionDigits: 2}) + ' € (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });
})();
<?php endif; ?>
</script>

<style>
/* Table Styles */
.backend-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.backend-table thead tr {
    background: var(--bg-color);
    border-bottom: 2px solid var(--border-color);
}

.backend-table th,
.backend-table td {
    padding: 12px;
    text-align: left;
}

.backend-table tbody tr {
    border-bottom: 1px solid var(--border-color);
    transition: background 0.2s;
}

.backend-table tbody tr:hover {
    background: var(--bg-color);
}

/* Badge Styles */
.badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.badge-info {
    background: var(--vs-accent-light);
    color: var(--vs-accent);
}

.badge-secondary {
    background: var(--vs-border);
    color: #666;
}

/* Button Styles */
.btn-small {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    text-decoration: none;
    display: inline-block;
    transition: var(--transition);
}

.btn-view {
    background: var(--vs-accent);
    color: var(--vs-surface);
}

.btn-view:hover {
    background: var(--vs-accent-hover);
}
</style>

<?php include 'layout/footer_next_page.php'; ?>
