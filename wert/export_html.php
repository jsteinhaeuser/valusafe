<?php
// export_html.php - HTML-Export mit eingebetteten Bildern (Excel-kompatibel)
require_once 'db.php';
require_once 'helpers.php';
requireLogin();
// Export-Recht aus der Rechteverwaltung (bis 4.3.32 nicht geprueft).
requirePermission('export_html');
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
    
    Security::logSecurityEvent('html_export', [
        'count' => count($wertsachen)
    ]);
    
} catch (PDOException $e) {
    Security::logSecurityEvent('html_export_error', [
        'error' => $e->getMessage()
    ]);
    die(t('error_loading_data'));
}

// Base URL ermitteln
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = $protocol . $host . $scriptPath;

// Header für HTML-Download setzen
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="wertsachen_export_' . date('Y-m-d_H-i-s') . '.html"');
header('Pragma: no-cache');
header('Expires: 0');

?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('html_export_title'); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 20px;
            background: #fff;
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
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        th {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        tr:hover {
            background: #e8f4f8;
        }
        
        img {
            max-width: 100px;
            max-height: 100px;
            border-radius: 4px;
            border: 1px solid #ddd;
            display: block;
            margin: 0 auto;
        }
        
        .no-image {
            width: 100px;
            height: 80px;
            background: #f0f0f0;
            border: 1px dashed #ccc;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 12px;
            margin: 0 auto;
        }
        
        .price {
            text-align: right;
            font-weight: 600;
            color: #27ae60;
        }
        
        .summary {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            border-left: 5px solid #4caf50;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #27ae60;
            margin: 10px 0;
        }
        
        .text-center {
            text-align: center;
        }
        
        .notizen {
            max-width: 300px;
            word-wrap: break-word;
            white-space: pre-wrap;
        }
        
        @media print {
            tr {
                page-break-inside: avoid;
            }
            
            thead {
                display: table-header-group;
            }
        }
    </style>
</head>
<body>
    <h1>📦 <?php echo t('html_export_title'); ?></h1>
    
    <div class="info">
        <p><strong><?php echo t('export_date'); ?>:</strong> <?php echo date('d.m.Y H:i:s'); ?></p>
        <p><strong><?php echo t('export_user'); ?>:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        <p><strong><?php echo t('export_count'); ?>:</strong> <?php echo count($wertsachen); ?></p>
        <p><strong><?php echo t('html_format'); ?>:</strong> <?php echo t('html_format_desc'); ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th><?php echo t('html_table_image'); ?></th>
                <th><?php echo t('csv_header_id'); ?></th>
                <th><?php echo t('csv_header_name'); ?></th>
                <th><?php echo t('csv_header_location'); ?></th>
                <th><?php echo t('csv_header_category'); ?></th>
                <th><?php echo t('csv_header_purchase_date'); ?></th>
                <th><?php echo t('csv_header_price'); ?></th>
                <th><?php echo t('csv_header_notes'); ?></th>
                <th><?php echo t('csv_header_created_by'); ?></th>
                <th><?php echo t('csv_header_listed_on'); ?></th>
                <th><?php echo t('csv_header_created_at'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($wertsachen)): ?>
                <tr>
                    <td colspan=\"11\" class=\"text-center\"><?php echo t('export_no_entries'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($wertsachen as $item): ?>
                    <tr>
                        <td class="text-center">
                            <?php if ($item['bild'] && file_exists(UPLOAD_DIR . $item['bild'])): ?>
                                <?php
                                // Bild als Base64 einbetten
                                $imagePath = UPLOAD_DIR . $item['bild'];
                                $imageData = base64_encode(file_get_contents($imagePath));
                                $imageInfo = getimagesize($imagePath);
                                $mimeType = $imageInfo['mime'] ?? 'image/jpeg';
                                ?>
                                <img src="data:<?php echo $mimeType; ?>;base64,<?php echo $imageData; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php else: ?>
                                <div class=\"no-image\">📷<br><?php echo t('export_no_image'); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['ort_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($item['kategorie_name'] ?? '-'); ?></td>
                        <td><?php echo formatDateLocalized($item['kaufdatum']); ?></td>
                        <td class="price"><?php echo number_format($item['preis'], 2, ',', '.'); ?> €</td>
                        <td class="notizen"><?php echo htmlspecialchars($item['notizen'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($item['erstellt_von'] ?? '-'); ?></td>
                        <td><?php echo formatDateLocalized($item['gelistet_am'] ?? null); ?></td>
                        <td><?php echo formatDateTimeLocalized($item['erstellt_am'] ?? null); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php
    $gesamtwert = array_sum(array_column($wertsachen, 'preis'));
    ?>
    
    <div class="summary">
        <h2 style=\"margin: 0 0 10px 0; color: #2c3e50;\">💰 <?php echo t('html_summary'); ?></h2>
        <p><strong><?php echo t('html_total_count'); ?>:</strong> <?php echo count($wertsachen); ?> <?php echo t('html_items_count'); ?></p>
        <p><strong><?php echo t('html_total_value'); ?>:</strong></p>
        <div class="summary-value"><?php echo number_format($gesamtwert, 2, ',', '.'); ?> €</div>
    </div>
    
    <div class="info" style="margin-top: 30px;">
        <p style="font-size: 12px; color: #666; margin: 0;">
            <?php echo t('html_footer_created'); ?> | 
            Export: <?php echo date('d.m.Y H:i:s'); ?> | 
            <?php echo t('html_footer_text'); ?>
        </p>
    </div>
</body>
</html>
