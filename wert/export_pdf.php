<?php
// export_pdf.php - MIT ÜBERSETZUNGEN
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
    
    Security::logSecurityEvent('pdf_export', [
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

// HTML-basierter PDF-Export mit Bildern
exportAsHTMLPDF($wertsachen, $baseUrl);

/**
 * HTML-basierter PDF-Export mit Bildunterstützung
 * Nutzt Browser "Drucken als PDF" Funktion
 */
function exportAsHTMLPDF($wertsachen, $baseUrl) {
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo getCurrentLanguage(); ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo t('export_pdf_title'); ?></title>
        <style>
            @media print {
                .no-print {
                    display: none !important;
                }
                body {
                    margin: 0;
                }
            }
            
            body {
                font-family: 'Segoe UI', Arial, sans-serif;
                margin: 20px;
                background: white;
            }
            
            h1 {
                color: #2c3e50;
                border-bottom: 3px solid #3498db;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            
            .info {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
            }
            
            .item-card {
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
                page-break-inside: avoid;
                background: white;
            }
            
            .item-header {
                display: flex;
                align-items: flex-start;
                margin-bottom: 15px;
                border-bottom: 2px solid #3498db;
                padding-bottom: 10px;
            }
            
            .item-image {
                flex-shrink: 0;
                margin-right: 20px;
            }
            
            .item-image img {
                max-width: 150px;
                max-height: 150px;
                border: 1px solid #ddd;
                border-radius: 4px;
                object-fit: cover;
                display: block;
            }
            
            .item-title {
                flex-grow: 1;
            }
            
            .item-title h2 {
                margin: 0 0 10px 0;
                color: #2c3e50;
                font-size: 18pt;
            }
            
            .item-meta {
                color: #666;
                font-size: 10pt;
            }
            
            .item-details {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                margin-top: 15px;
            }
            
            .detail-item {
                padding: 8px;
                background: #f8f9fa;
                border-radius: 4px;
            }
            
            .detail-label {
                font-weight: bold;
                color: #555;
                font-size: 9pt;
                display: block;
                margin-bottom: 4px;
            }
            
            .detail-value {
                color: #2c3e50;
                font-size: 11pt;
            }
            
            .notizen-box {
                grid-column: 1 / -1;
                padding: 12px;
                background: #fff9e6;
                border-left: 4px solid #ffc107;
                border-radius: 4px;
                margin-top: 10px;
            }
            
            .total {
                background: #e8f5e9;
                padding: 20px;
                border-radius: 8px;
                margin: 30px 0;
                border-left: 5px solid #4caf50;
                font-size: 14pt;
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
            
            .no-image {
                width: 150px;
                height: 150px;
                background: #f0f0f0;
                border: 1px dashed #ccc;
                border-radius: 4px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #999;
                font-size: 12pt;
            }
            
            @page {
                margin: 15mm;
            }

            /* ── Deckblatt ── */
            .cover-page {
                page-break-after: always;
                min-height: 240mm;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                padding: 20mm 10mm;
            }

            .cover-top {
                text-align: center;
            }

            .cover-logo {
                font-size: 48pt;
                margin-bottom: 10px;
            }

            .cover-appname {
                font-size: 16pt;
                color: #95a5a6;
                letter-spacing: 3px;
                text-transform: uppercase;
                margin-bottom: 30px;
            }

            .cover-title {
                font-size: 28pt;
                font-weight: 700;
                color: #2c3e50;
                border-top: 3px solid #3498db;
                border-bottom: 3px solid #3498db;
                padding: 14px 0;
                margin: 10px 0 30px;
            }

            .cover-stats {
                display: flex;
                justify-content: center;
                gap: 40px;
                margin-top: 30px;
            }

            .cover-stat {
                text-align: center;
                padding: 16px 24px;
                background: #f8f9fa;
                border-radius: 8px;
                border-top: 3px solid #3498db;
                min-width: 120px;
            }

            .cover-stat-value {
                font-size: 20pt;
                font-weight: 700;
                color: #2c3e50;
                display: block;
            }

            .cover-stat-label {
                font-size: 9pt;
                color: #888;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .cover-bottom {
                border-top: 1px solid #ddd;
                padding-top: 14px;
                font-size: 10pt;
                color: #888;
                display: flex;
                justify-content: space-between;
            }
        </style>
    </head>
    <body>
        <div class="no-print">
            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <h3 style="margin-top: 0;">📄 <?php echo t('export_pdf_instructions'); ?>:</h3>
                <ol>
                    <li><?php echo t('export_pdf_step1'); ?></li>
                    <li><?php echo t('export_pdf_step2'); ?></li>
                    <li><?php echo t('export_pdf_step3'); ?></li>
                    <li><?php echo t('export_pdf_step4'); ?></li>
                </ol>
                <p><strong><?php echo t('tip'); ?>:</strong> <?php echo t('export_pdf_tip'); ?></p>
            </div>
            
            <button onclick="window.print()" class="btn-print">🖨️ <?php echo t('export_pdf_print_button'); ?></button>
            <a href="index.php" class="btn-back">← <?php echo t('export_back_button'); ?></a>
            <hr style="margin: 30px 0;">
        </div>
        
        <!-- ══ DECKBLATT ══════════════════════════════════════════════ -->
        <?php
        $gesamtwert_cover = array_sum(array_column($wertsachen, 'preis'));
        $appName = defined('APP_NAME') ? APP_NAME : 'ValuSafe';
        ?>
        <div class="cover-page no-print-skip">
            <div class="cover-top">
                <div class="cover-logo">📦</div>
                <div class="cover-appname"><?php echo htmlspecialchars($appName); ?></div>
                <div class="cover-title"><?php echo t('export_inventory_title'); ?></div>

                <div class="cover-stats">
                    <div class="cover-stat">
                        <span class="cover-stat-value"><?php echo count($wertsachen); ?></span>
                        <span class="cover-stat-label"><?php echo t('export_entries'); ?></span>
                    </div>
                    <div class="cover-stat">
                        <span class="cover-stat-value" style="font-size:15pt;"><?php echo formatPriceLocalized($gesamtwert_cover); ?></span>
                        <span class="cover-stat-label"><?php echo t('stats_total_value'); ?></span>
                    </div>
                    <div class="cover-stat">
                        <span class="cover-stat-value" style="font-size:13pt;"><?php echo date('d.m.Y'); ?></span>
                        <span class="cover-stat-label"><?php echo t('export_date'); ?></span>
                    </div>
                </div>
            </div>

            <div class="cover-bottom">
                <span>👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <span><?php echo htmlspecialchars($appName); ?> · <?php echo formatDateTimeLocalized(date('Y-m-d H:i:s')); ?></span>
            </div>
        </div>

        <h1>📦 <?php echo t('export_inventory_title'); ?></h1>
        
        <div class="info">
            <p>
                <strong><?php echo t('export_date'); ?>:</strong> <?php echo formatDateTimeLocalized(date('Y-m-d H:i:s')); ?> | 
                <strong><?php echo t('export_user'); ?>:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?> | 
                <strong><?php echo t('export_count'); ?>:</strong> <?php echo count($wertsachen); ?> <?php echo t('export_entries'); ?>
            </p>
        </div>
        
        <?php if (empty($wertsachen)): ?>
            <div class="item-card">
                <p style="text-align: center; color: #999; padding: 40px;"><?php echo t('export_no_entries'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($wertsachen as $item): ?>
                <div class="item-card">
                    <div class="item-header">
                        <div class="item-image">
                            <?php if ($item['bild'] && file_exists(UPLOAD_DIR . $item['bild'])): ?>
                                <?php
                                // Absoluter URL für das Bild
                                $imageUrl = $baseUrl . '/upload/' . rawurlencode($item['bild']);
                                ?>
                                <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     crossorigin="anonymous">
                            <?php else: ?>
                                <div class="no-image">📷<br><?php echo t('export_no_image'); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-title">
                            <h2><?php echo htmlspecialchars($item['name']); ?></h2>
                            <div class="item-meta">
                                <?php echo t('expraum_id'); ?>: <?php echo $item['id']; ?> | 
                                <?php echo t('export_created_by'); ?>: <?php echo htmlspecialchars($item['erstellt_von'] ?? '-'); ?>
                                <?php if (isset($item['gelistet_am'])): ?>
                                    | <?php echo t('export_listed_on'); ?>: <?php echo formatDateLocalized($item['gelistet_am']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="item-details">
                        <div class="detail-item">
                            <span class="detail-label">📍 <?php echo t('export_location'); ?></span>
                            <span class="detail-value"><?php echo htmlspecialchars($item['ort_name'] ?? '-'); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">🏷️ <?php echo t('export_category'); ?></span>
                            <span class="detail-value"><?php echo htmlspecialchars($item['kategorie_name'] ?? '-'); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">📅 <?php echo t('export_purchase_date'); ?></span>
                            <span class="detail-value">
                                <?php echo $item['kaufdatum'] ? formatDateLocalized($item['kaufdatum']) : '-'; ?>
                            </span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">💰 <?php echo t('export_price'); ?></span>
                            <span class="detail-value" style="font-weight: bold; color: #27ae60;">
                                <?php echo formatPriceLocalized($item['preis']); ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($item['notizen'])): ?>
                            <div class="notizen-box">
                                <span class="detail-label">📝 <?php echo t('export_notes'); ?></span>
                                <div class="detail-value"><?php echo nl2br(htmlspecialchars($item['notizen'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php
        $gesamtwert = array_sum(array_column($wertsachen, 'preis'));
        ?>
        
        <div class="total">
            <strong>💰 <?php echo t('export_total_value'); ?>:</strong> 
            <span style="font-size: 16pt; color: #27ae60;"><?php echo formatPriceLocalized($gesamtwert); ?></span>
            <br>
            <span style="font-size: 11pt; color: #666;">
                <?php echo t('export_based_on'); ?> <?php echo count($wertsachen); ?> <?php echo t('export_entries'); ?>
            </span>
        </div>
        
        <div class="info" style="margin-top: 40px; page-break-before: avoid;">
            <p style="font-size: 10pt; color: #666;">
                📦 <?php echo t('export_footer'); ?> | 
                <?php echo t('export_date'); ?> <?php echo formatDateTimeLocalized(date('Y-m-d H:i:s')); ?> | 
                <?php echo count($wertsachen); ?> <?php echo t('export_entries'); ?> | 
                <?php echo t('stats_total_value'); ?>: <?php echo formatPriceLocalized($gesamtwert); ?>
            </p>
        </div>
    </body>
    </html>
    <?php
}
?>
