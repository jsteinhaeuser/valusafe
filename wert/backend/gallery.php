<?php
/**
 * Bild-Galerie - Backend
 * Zeigt alle hochgeladenen Bilder in einer Galerie-Ansicht
 */

require_once __DIR__ . '/config.php';
requireBackendAccess();

$pageTitle = t('image_gallery');

// Alle Gegenstände mit Bildern laden
try {
    $items = $db->select("
        SELECT w.id, w.name, w.bild, k.name as kategorie, o.name as ort, w.preis
        FROM wertsachen w
        LEFT JOIN kategorien k ON w.kategorie_id = k.id
        LEFT JOIN raeume o ON w.raum_id = o.id
        WHERE w.bild IS NOT NULL AND w.bild != ''
        ORDER BY w.id DESC
    ");
} catch (Exception $e) {
    $items = [];
    $error = 'Fehler beim Laden der Bilder: ' . $e->getMessage();
}

// Statistiken
$stats = [
    'total' => count($items),
    'total_size' => 0
];

$uploadDir = __DIR__ . '/../upload/';
foreach ($items as &$item) {
    if ($item['bild'] && file_exists($uploadDir . $item['bild'])) {
        $fileSize = filesize($uploadDir . $item['bild']);
        $item['filesize'] = $fileSize;
        $stats['total_size'] += $fileSize;
    } else {
        $item['filesize'] = 0;
    }
}
unset($item);

// Layout laden
include 'layout/header_next_page.php';
?>

<!-- Main Content -->
<main class="backend-main">
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <strong><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> Fehler!</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistik-Karten -->
    <div class="dashboard-grid" style="margin-bottom: 30px;">
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value"><?php echo $stats['total']; ?></div>
                    <div class="widget-label"><i class="ti ti-photo"></i> <?php echo t('gallery_total_images'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-photo"></i></div>
            </div>
        </div>
        
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value" style="font-size: 24px;">
                        <?php echo number_format($stats['total_size'] / 1024 / 1024, 2); ?> MB
                    </div>
                    <div class="widget-label"><i class="ti ti-device-floppy"></i> <?php echo t('gallery_total_size'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-device-floppy"></i></div>
            </div>
        </div>
        
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value" style="font-size: 24px;">
                        <?php echo $stats['total'] > 0 ? number_format($stats['total_size'] / $stats['total'] / 1024, 0) : 0; ?> KB
                    </div>
                    <div class="widget-label"><i class="ti ti-chart-bar"></i> <?php echo t('gallery_avg_size'); ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-chart-bar"></i></div>
            </div>
        </div>
    </div>
    
    <!-- Filter & Ansicht-Optionen -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2><i class="ti ti-photo"></i> <?php echo t('image_gallery'); ?></h2>
            <div style="display: flex; gap: 10px;">
                <button onclick="changeView('grid')" class="vs-btn vs-btn-sm vs-btn-primary" id="viewGrid">
                    <?php echo t('view_grid'); ?>
                </button>
                <button onclick="changeView('list')" class="vs-btn vs-btn-sm" id="viewList">
                    <?php echo t('view_list'); ?>
                </button>
            </div>
        </div>
        
        <?php if (count($items) === 0): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 15px;"><i class="ti ti-photo"></i></div>
                <p>Keine Bilder gefunden</p>
                <p style="font-size: 13px; color: #999; margin-top: 5px;">
                    Laden Sie Bilder hoch, indem Sie Gegenstände mit Bildern erstellen.
                </p>
            </div>
        <?php else: ?>
            <!-- Grid-Ansicht (Standard) -->
            <div class="gallery-grid" id="galleryGrid">
                <?php foreach ($items as $item): ?>
                    <div class="gallery-item">
                        <div class="gallery-image-wrapper">
                            <img src="../upload/<?php echo htmlspecialchars($item['bild']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 class="gallery-image"
                                 data-lightbox="../upload/<?php echo htmlspecialchars($item['bild']); ?>"
                                 data-title="<?php echo htmlspecialchars($item['name']); ?>"
                                 loading="lazy">
                            <div class="gallery-overlay">
                                <div class="gallery-info">
                                    <div class="gallery-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="gallery-meta">
                                        <?php if ($item['kategorie']): ?>
                                            <span><i class="ti ti-tag"></i> <?php echo htmlspecialchars($item['kategorie']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($item['ort']): ?>
                                            <span>📍 <?php echo htmlspecialchars($item['ort']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="gallery-actions">
                                    <a href="../edit.php?id=<?php echo $item['id']; ?>" class="gallery-btn" target="_blank">
                                        <i class="ti ti-pencil"></i> <?php echo t('btn_edit'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="gallery-footer">
                            <div style="font-weight: 600; font-size: 13px;">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </div>
                            <div style="font-size: 11px; color: #999;">
                                <?php echo number_format($item['filesize'] / 1024, 0); ?> KB
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Listen-Ansicht -->
            <div class="gallery-list" id="galleryList" style="display: none;">
                <table class="backend-table">
                    <thead>
                        <tr>
                            <th style="width: 100px;"><?php echo t('gallery_col_preview'); ?></th>
                            <th>Name</th>
                            <th style="width: 150px;"><?php echo t('col_kategorie'); ?></th>
                            <th style="width: 150px;"><?php echo t('gallery_col_location'); ?></th>
                            <th style="width: 100px;"><?php echo t('gallery_col_size'); ?></th>
                            <th style="width: 120px;"><?php echo t('tab_actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <img src="../upload/<?php echo htmlspecialchars($item['bild']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="list-thumbnail"
                                         data-lightbox="../upload/<?php echo htmlspecialchars($item['bild']); ?>"
                                         data-title="<?php echo htmlspecialchars($item['name']); ?>"
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; cursor: pointer;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                </td>
                                <td>
                                    <?php if ($item['kategorie']): ?>
                                        <span class="badge badge-secondary">
                                            <?php echo htmlspecialchars($item['kategorie']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($item['ort']): ?>
                                        <span class="badge badge-info">
                                            <?php echo htmlspecialchars($item['ort']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo number_format($item['filesize'] / 1024, 0); ?> KB
                                </td>
                                <td>
                                    <a href="../edit.php?id=<?php echo $item['id']; ?>" class="vs-btn vs-btn-sm" target="_blank">
                                        <i class="ti ti-pencil"></i> <?php echo t('btn_edit'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
</main>

<style>
/* Gallery Grid */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.gallery-item {
    background: var(--vs-surface);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.gallery-item:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-4px);
}

.gallery-image-wrapper {
    position: relative;
    width: 100%;
    padding-top: 75%; /* 4:3 Aspect Ratio */
    overflow: hidden;
    background: var(--vs-surface-2);
}

.gallery-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.3s;
}

.gallery-image:hover {
    transform: scale(1.1);
}

.gallery-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.7) 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 15px;
    opacity: 0;
    transition: opacity 0.3s;
}

.gallery-item:hover .gallery-overlay {
    opacity: 1;
}

.gallery-info {
    color: var(--vs-surface);
    margin-bottom: 10px;
}

.gallery-name {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.gallery-meta {
    font-size: 11px;
    opacity: 0.9;
    display: flex;
    gap: 10px;
}

.gallery-actions {
    display: flex;
    gap: 8px;
}

.gallery-btn {
    flex: 1;
    padding: 6px 12px;
    background: rgba(255,255,255,0.2);
    color: var(--vs-surface);
    text-decoration: none;
    border-radius: 4px;
    font-size: 12px;
    text-align: center;
    transition: var(--transition);
}

.gallery-btn:hover {
    background: rgba(255,255,255,0.3);
}

.gallery-footer {
    padding: 12px;
    background: var(--vs-surface);
    border-top: 1px solid var(--border-color);
}

/* View Buttons */
.btn-view {
    padding: 8px 16px;
    border: 2px solid var(--border-color);
    border-radius: 6px;
    background: var(--vs-surface);
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: var(--transition);
}

.btn-view:hover {
    border-color: var(--primary-color);
    background: var(--vs-accent-light);
}

.btn-view.active {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: var(--vs-surface);
}

/* Responsive */
@media (max-width: 1200px) {
    .gallery-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

@media (max-width: 768px) {
    .gallery-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .gallery-footer {
        padding: 8px;
    }
}
</style>

<script>
function changeView(view) {
    const gridView = document.getElementById('galleryGrid');
    const listView = document.getElementById('galleryList');
    const gridBtn = document.getElementById('viewGrid');
    const listBtn = document.getElementById('viewList');
    
    if (view === 'grid') {
        gridView.style.display = 'grid';
        listView.style.display = 'none';
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');
    } else {
        gridView.style.display = 'none';
        listView.style.display = 'block';
        gridBtn.classList.remove('active');
        listBtn.classList.add('active');
    }
}
</script>

<?php include 'layout/footer_next_page.php'; ?>
