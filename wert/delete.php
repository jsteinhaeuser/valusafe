<?php
// delete.php - KORRIGIERTE VERSION
// WICHTIG: Keine Ausgabe vor session_start()!

require_once 'db.php';
require_once 'helpers.php';
requireLogin();
if (!function_exists('canDelete') || !canDelete()) {
    if (function_exists('redirectWithMessage')) {
        redirectWithMessage('index.php', 'Löschen ist mit diesem Benutzerkonto nicht möglich.', 'error');
    }
    header('Location: index.php');
    exit;
}

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php');
    exit;
}

// CSRF-Schutz: Bei GET-Requests Bestätigungsseite anzeigen
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    try {
        $item = $db->selectOne("SELECT * FROM wertsachen WHERE id = ?", [$id]);
        
        if (!$item) {
            header('Location: index.php');
            exit;
        }
        
        define('PAGE_TITLE', t('confirm_delete_item') . ' - ' . t('app_title'));
        include 'header_next_page.php';
        ?>
        
        <h2><?php echo t('confirm_delete_item'); ?></h2>
        
        <div class="error-message">
            <p><strong><?php echo t('confirm_delete'); ?></strong></p>
            <p><strong><?php echo t('field_name'); ?>:</strong> <?php echo htmlspecialchars($item['name']); ?></p>
        </div>
        
        <?php if ($item['bild'] && file_exists(UPLOAD_DIR . $item['bild'])): ?>
            <div class="current-image" style="margin: 20px 0;">
                <img src="upload/<?php echo htmlspecialchars($item['bild']); ?>" alt="Bild" style="max-width: 200px; height: auto;">
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" style="margin-top: 20px;">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="confirm_delete" value="1">
            <button type="submit" class="btn btn-danger">✓ <?php echo t('btn_confirm'); ?></button>
            <a href="index.php" class="btn">✗ <?php echo t('btn_cancel'); ?></a>
        </form>
        
        <?php
        include 'footer_next.php';
        exit;
    } catch (PDOException $e) {
        Security::logSecurityEvent('item_load_error', [
            'id' => $id,
            'error' => $e->getMessage()
        ]);
        header('Location: index.php');
        exit;
    }
}

// POST-Request: Löschen durchführen
// Direkter CSRF-Check (robuster als validateRequest())
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_post    = $_POST['csrf_token'] ?? '';
    $csrf_session = $_SESSION[CSRF_TOKEN_NAME] ?? '';
    if (empty($csrf_post) || empty($csrf_session) || !hash_equals($csrf_session, $csrf_post)) {
        http_response_code(403);
        die('Ungültiges Sicherheits-Token. <a href="index.php">Zurück</a>');
    }
}

if (!isset($_POST['confirm_delete'])) {
    header('Location: index.php');
    exit;
}

try {
    $item = $db->selectOne("SELECT * FROM wertsachen WHERE id = ?", [$id]);
    
    if ($item) {
        // Daten vor Löschung für Activity Log speichern
        $deletedData = [
            'name' => $item['name'],
            'kategorie_id' => $item['kategorie_id'],
            'raum_id' => $item['raum_id'],
            'preis' => $item['preis'],
            'kaufdatum' => $item['kaufdatum']
        ];
        
        // Transaktion starten
        $db->beginTransaction();
        
        try {
            // Dokumente löschen (falls vorhanden)
            $docs = $db->select("SELECT dateiname FROM dokumente WHERE wertsache_id = ?", [$id]);
            foreach ($docs as $doc) {
                $docPath = __DIR__ . '/documents/' . $doc['dateiname'];
                if (file_exists($docPath)) {
                    unlink($docPath);
                }
            }
            $db->execute("DELETE FROM dokumente WHERE wertsache_id = ?", [$id]);
            
            // Multi-Images löschen (falls vorhanden)
            if (function_exists('deleteAllItemImages')) {
                deleteAllItemImages($id);
            }
            
            // Bild löschen
            if ($item['bild'] && file_exists(UPLOAD_DIR . $item['bild'])) {
                if (unlink(UPLOAD_DIR . $item['bild'])) {
                    Security::logSecurityEvent('file_deleted', [
                        'filename' => $item['bild'],
                        'item_id' => $id
                    ]);
                }
            }
            
            // Datenbank-Eintrag löschen
            $db->execute("DELETE FROM wertsachen WHERE id = ?", [$id]);
            
            // Activity Log schreiben BEVOR Transaktion bestätigt wird
            logActivity('deleted', 'wertsachen', $id, $deletedData['name'], $deletedData, null);
            
            // Transaktion bestätigen
            $db->commit();
            
            Security::logSecurityEvent('item_deleted', [
                'item_id' => $id,
                'item_name' => $item['name']
            ]);
            
        } catch (Exception $e) {
            // Transaktion rückgängig machen
            $db->rollback();
            
            // Fehler loggen
            Security::logSecurityEvent('item_delete_error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    } else {
        // Item existiert nicht mehr - trotzdem loggen
        logActivity('deleted', 'wertsachen', $id, 'Unbekannter Gegenstand (ID: ' . $id . ')', ['id' => $id], null);
    }
} catch (PDOException $e) {
    Security::logSecurityEvent('item_delete_error', [
        'id' => $id,
        'error' => $e->getMessage()
    ]);
}

header('Location: index.php');
exit;
?>
