<?php
// export_pdf_compact.php - Kompakte Version: 4 Gegenstände pro Seite im Querformat - MIT ÜBERSETZUNGEN
require_once 'db.php';
require_once 'helpers.php';
requireLogin();
// Export-Recht aus der Rechteverwaltung (bis 4.3.32 nicht geprueft).
requirePermission('export_pdf');
// Bei "nur eigene" nur eigene Gegenstaende exportieren.
[$ownSql, $ownParams] = nurEigeneSql('w');

try {
    $sql = "SELECT w.*, o.name as ort_name, k.name as kategorie_name 
            FROM wertsachen w 
            LEFT JOIN raeume o ON w.raum_id = o.id 
            LEFT JOIN kategorien k ON w.kategorie_id = k.id 
            WHERE 1=1" . $ownSql . "
            ORDER BY w.name";
    
    $wertsachen = $db->select($sql, $ownParams);
    
    Security::logSecurityEvent('pdf_export_compact', [
        'count' => count($wertsachen)
    ]);
    
} catch (PDOException $e) {
    Security::logSecurityEvent('pdf_export_error', [
        'error' => $e->getMessage()
    ]);
    die(t('error_loading_data'));
}

// Base URL ermitteln
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = $protocol . $host . $scriptPath;

exportCompactPDF($wertsachen, $baseUrl);

/**
 * Kompakter PDF-Export: 4 Gegenstände pro Seite im Querformat
 */
function exportCompactPDF($wertsachen, $baseUrl) {
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo getCurrentLanguage(); ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo t('export_pdf_title'); ?> (<?php echo t('nav_export_pdf_compact'); ?>)</title>
        <style>
            @media print {
                .no-print {
                    display: none !important;
                }
                body {
                    margin: 0;
                }
                @page {
                    size: A4 landscape;
                    margin: 10mm;
                }
            }
            
            body {
                font-family: 'Segoe UI', Arial, sans-serif;
                margin: 20px;
                background: white;
            }
            
            .page-header {
                text-align: center;
                padding: 15px;
                border-bottom: 3px solid #3498db;
                margin-bottom: 20px;
                page-break-after: avoid;
            }
            
            .page-header h1 {
                margin: 0 0 10px 0;
                color: #2c3e50;
                font-size: 24pt;
            }
            
            .page-info {
                color: #666;
                font-size: 10pt;
            }
            
            .grid-container {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                margin-bottom: 15px;
            }
            
            .item-card {
                border: 2px solid #ddd;
                border-radius: 8px;
                padding: 12px;
                background: white;
                page-break-inside: avoid;
                min-height: 180px;
                display: flex;
                flex-direction: column;
            }
            
            .item-header {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
                padding-bottom: 8px;
                border-bottom: 2px solid #3498db;
            }
            
            .item-image {
                flex-shrink: 0;
                margin-right: 12px;
            }
            
            .item-image img {
                width: 80px;
                height: 80px;
                border: 1px solid #ddd;
                border-radius: 4px;
                object-fit: cover;
                display: block;
            }
            
            .no-image {
                width: 80px;
                height: 80px;
                background: #f0f0f0;
                border: 1px dashed #ccc;
                border-radius: 4px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #999;
                font-size: 30px;
            }
            
            .item-title {
                flex-grow: 1;
                min-width: 0;
            }
            
            .item-title h3 {
                margin: 0 0 4px 0;
                color: #2c3e50;
                font-size: 13pt;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            
            .item-id {
                color: #999;
                font-size: 8pt;
            }
            
            .item-details {
                flex-grow: 1;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 6px;
                font-size: 9pt;
            }
            
            .detail {
                padding: 4px;
                background: #f8f9fa;
                border-radius: 3px;
            }
            
            .detail-label {
                font-weight: bold;
                color: #666;
                font-size: 8pt;
                display: block;
            }
            
            .detail-value {
                color: #2c3e50;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            
            .price-highlight {
                font-weight: bold;
                color: #27ae60;
                font-size: 11pt;
            }
            
            .notizen-preview {
                grid-column: 1 / -1;
                padding: 6px;
                background: #fff9e6;
                border-left: 3px solid #ffc107;
                border-radius: 3px;
                font-size: 8pt;
                max-height: 40px;
                overflow: hidden;
            }
            
            .page-break {
                page-break-after: always;
            }
            
            .summary {
                background: #e8f5e9;
                padding: 15px;
                border-radius: 8px;
                margin-top: 20px;
                border-left: 5px solid #4caf50;
                text-align: center;
                page-break-before: always;
            }
            
            .summary-value {
                font-size: 20pt;
                font-weight: bold;
                color: #27ae60;
                margin: 10px 0;
            }
            
            .btn-print {
                background: #3498db;
                color: white;
                padding: 15px 30px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                cursor: pointer;
                margin: 10px 5px;
            }
            
            .btn-print:hover {
                background: #2980b9;
            }
            
            .btn-back {
                background: #95a5a6;
                color: white;
                padding: 15px 30px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
            }
            
            .btn-back:hover {
                background: #7f8c8d;
            }
        </style>
    </head>
    <body>
        <div class="no-print">
            <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #0c5460;">
                <h3>📄 <?php echo t('nav_export_pdf_compact'); ?></h3>
                <p><strong><?php echo t('export_pdf_instructions'); ?>:</strong></p>
                <ol>
                    <li><?php echo t('export_pdf_step1'); ?></li>
                    <li><?php echo t('export_pdf_step3'); ?></li>
                    <li><?php echo t('export_pdf_step2'); ?></li>
                </ol>
            </div>
            
            <button onclick="window.print()" class="btn-print">🖨️ <?php echo t('export_pdf_print_button'); ?></button>
            <a href="index.php" class="btn-back">← <?php echo t('export_back_button'); ?></a>
            <a href="export_pdf.php" class="btn-back">📄 <?php echo t('nav_export_pdf'); ?></a>
            <hr style="margin: 30px 0;">
        </div>
        
        <div class="page-header">
            <h1>📦 <?php echo t('export_inventory_title'); ?></h1>
            <div class="page-info">
                <?php echo t('export_date'); ?>: <?php echo formatDateTimeLocalized(date('Y-m-d H:i:s')); ?> | 
                <?php echo t('export_user'); ?>: <?php echo htmlspecialchars($_SESSION['username']); ?> | 
                <?php echo count($wertsachen); ?> <?php echo t('export_entries'); ?>
            </div>
        </div>
        
        <?php if (empty($wertsachen)): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <?php echo t('export_no_entries'); ?>
            </div>
        <?php else: ?>
            <?php
            $chunks = array_chunk($wertsachen, 4); // 4 Gegenstände pro Seite
            $pageCount = count($chunks);
            
            foreach ($chunks as $pageIndex => $pageItems):
            ?>
                <div class="grid-container">
                    <?php foreach ($pageItems as $item): ?>
                        <div class="item-card">
                            <div class="item-header">
                                <div class="item-image">
                                    <?php if ($item['bild'] && file_exists(UPLOAD_DIR . $item['bild'])): ?>
                                        <?php
                                        $imageUrl = $baseUrl . '/upload/' . rawurlencode($item['bild']);
                                        ?>
                                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             crossorigin="anonymous">
                                    <?php else: ?>
                                        <div class="no-image">📷</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-title">
                                    <h3 title="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </h3>
                                    <div class="item-id"><?php echo t('expraum_id'); ?>: <?php echo $item['id']; ?></div>
                                </div>
                            </div>
                            
                            <div class="item-details">
                                <div class="detail">
                                    <span class="detail-label">📍 <?php echo t('export_location'); ?></span>
                                    <span class="detail-value" title="<?php echo htmlspecialchars($item['ort_name'] ?? '-'); ?>">
                                        <?php echo htmlspecialchars($item['ort_name'] ?? '-'); ?>
                                    </span>
                                </div>
                                
                                <div class="detail">
                                    <span class="detail-label">🏷️ <?php echo t('export_category'); ?></span>
                                    <span class="detail-value" title="<?php echo htmlspecialchars($item['kategorie_name'] ?? '-'); ?>">
                                        <?php echo htmlspecialchars($item['kategorie_name'] ?? '-'); ?>
                                    </span>
                                </div>
                                
                                <div class="detail">
                                    <span class="detail-label">📅 <?php echo t('export_purchase_date'); ?></span>
                                    <span class="detail-value">
                                        <?php echo $item['kaufdatum'] ? formatDateLocalized($item['kaufdatum']) : '-'; ?>
                                    </span>
                                </div>
                                
                                <div class="detail">
                                    <span class="detail-label">💰 <?php echo t('export_price'); ?></span>
                                    <span class="detail-value price-highlight">
                                        <?php echo formatPriceLocalized($item['preis']); ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($item['notizen'])): ?>
                                    <div class="notizen-preview">
                                        <strong><?php echo t('export_notes'); ?>:</strong> 
                                        <?php 
                                        $notizen = strip_tags($item['notizen']);
                                        echo htmlspecialchars(substr($notizen, 0, 60));
                                        if (strlen($notizen) > 60) echo '...';
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($pageIndex < $pageCount - 1): ?>
                    <div class="page-break"></div>
                    <div class="page-header">
                        <h1>📦 <?php echo t('export_inventory_title'); ?></h1>
                        <div class="page-info">
                            <?php echo t('export_page'); ?> <?php echo $pageIndex + 2; ?> <?php echo t('export_of'); ?> <?php echo $pageCount; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php
        $gesamtwert = array_sum(array_column($wertsachen, 'preis'));
        ?>
        
        <div class="summary">
            <h2 style="margin: 0 0 10px 0; color: #2c3e50;">💰 <?php echo t('export_total_value'); ?></h2>
            <div class="summary-value"><?php echo formatPriceLocalized($gesamtwert); ?></div>
            <div style="color: #666; font-size: 11pt;">
                <?php echo t('export_based_on'); ?> <?php echo count($wertsachen); ?> <?php echo t('export_entries'); ?>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
