<?php
// manage_documents.php - Dokumentenverwaltung für einen Gegenstand
require_once 'db.php';
require_once 'helpers.php';
requireLogin();

// Prüfen ob Dokumente-Feature aktiviert ist
$user = $db->selectOne("SELECT dokumente_aktiv FROM users WHERE username = ?", [$_SESSION['username']]);
if (!$user || !$user['dokumente_aktiv']) {
    redirectWithMessage('index.php', 'Dokumente-Feature ist nicht aktiviert', 'error');
}

// Rechte-Gate: dieselbe Berechtigung, die backend/permissions.php anbietet.
requirePermission('docs_view');

$wertsache_id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$wertsache_id) {
    redirectWithMessage('index.php', t('doc_invalid_id'), 'error');
}

// Wertsache laden
try {
    $wertsache = $db->selectOne("SELECT * FROM wertsachen WHERE id = ?", [$wertsache_id]);
    if (!$wertsache) {
        redirectWithMessage('index.php', t('error_item_not_found'), 'error');
    }
    // Zugriffspruefung wie in index.php / download_document.php: bei aktiver
    // Einstellung "only_own_items" nur eigene Gegenstaende.
    if (!darfGegenstand($wertsache)) {
        Security::logSecurityEvent('document_access_denied', [
            'wertsache_id' => $wertsache_id,
            'reason'       => 'not_owner'
        ]);
        redirectWithMessage('index.php', t('error_item_not_found'), 'error');
    }
} catch (PDOException $e) {
    die(t('error_loading_data'));
}

define('PAGE_TITLE', t('doc_manage_title') . ' - ' . htmlspecialchars($wertsache['name']));
define('DOCUMENTS_DIR', __DIR__ . '/documents/');
define('MAX_DOCUMENT_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_DOCUMENT_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/jpeg',
    'image/png'
]);
define('ALLOWED_DOCUMENT_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']);

$message = '';
$error = '';

// Dokument hochladen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dokument'])) {
    validateRequest();
    requirePermission('docs_upload');
    
    $file = $_FILES['dokument'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileSize = $file['size'];
        $fileType = mime_content_type($file['tmp_name']);
        $originalName = basename($file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Validierung
        if ($fileSize > MAX_DOCUMENT_SIZE) {
            $error = t('doc_too_large');
        } elseif (!in_array($fileType, ALLOWED_DOCUMENT_TYPES)) {
            $error = t('doc_type_not_allowed');
        } elseif (!in_array($extension, ALLOWED_DOCUMENT_EXTENSIONS)) {
            $error = t('doc_ext_not_allowed');
        } else {
            // Verzeichnis erstellen falls nicht vorhanden
            if (!is_dir(DOCUMENTS_DIR)) {
                mkdir(DOCUMENTS_DIR, 0755, true);
            }
            
            // Eindeutigen Dateinamen generieren
            $filename = uniqid('doc_', true) . '_' . time() . '.' . $extension;
            $filepath = DOCUMENTS_DIR . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                try {
                    $db->execute(
                        "INSERT INTO dokumente (wertsache_id, dateiname, original_name, dateityp, dateigröße, hochgeladen_von) 
                         VALUES (?, ?, ?, ?, ?, ?)",
                        [$wertsache_id, $filename, $originalName, $fileType, $fileSize, $_SESSION['username']]
                    );
                    
                    $message = t('doc_uploaded');
                    Security::logSecurityEvent('document_uploaded', [
                        'wertsache_id' => $wertsache_id,
                        'filename' => $originalName
                    ]);
                } catch (PDOException $e) {
                    unlink($filepath);
                    $error = t('doc_error_save');
                }
            } else {
                $error = t('doc_error_upload');
            }
        }
    } else {
        $error = t('msg_error_upload_failed');
    }
}

// Dokument löschen
if (isset($_GET['delete'])) {
    validateRequest();
    requirePermission('docs_delete');
    
    $doc_id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    if ($doc_id) {
        try {
            $doc = $db->selectOne("SELECT * FROM dokumente WHERE id = ? AND wertsache_id = ?", [$doc_id, $wertsache_id]);
            
            if ($doc) {
                $filepath = DOCUMENTS_DIR . $doc['dateiname'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                
                $db->execute("DELETE FROM dokumente WHERE id = ?", [$doc_id]);
                $message = t('doc_deleted');
                
                Security::logSecurityEvent('document_deleted', [
                    'doc_id' => $doc_id,
                    'wertsache_id' => $wertsache_id
                ]);
            }
        } catch (PDOException $e) {
            $error = t('doc_error_delete');
        }
    }
}

// Dokumente laden
try {
    $dokumente = $db->select("SELECT * FROM dokumente WHERE wertsache_id = ? ORDER BY hochgeladen_am DESC", [$wertsache_id]);
} catch (PDOException $e) {
    $dokumente = [];
}

include 'header_next_page.php';
?>

<main class="backend-main">
<h2>📄 <?php echo t('form_documents'); ?>: <?php echo htmlspecialchars($wertsache['name']); ?></h2>



<?php if ($message): ?>
    <div class="alert alert-success"><i class="ti ti-circle-check"></i> <?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="ti ti-circle-x"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="activity-timeline" style="margin-bottom:24px;">
    <h3><?php echo t('doc_upload_new'); ?></h3>
    <form method="POST" action="" enctype="multipart/form-data">
        <?php echo Security::getCSRFInput(); ?>
        
        <div class="form-group">
            <label for="dokument"><?php echo t('doc_choose'); ?>:</label>
            <input type="file" id="dokument" name="dokument" required 
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
            <small>
                <?php echo t('doc_allowed_formats'); ?><br>
                <?php echo t('doc_max_size'); ?>
            </small>
        </div>
        
        <button type="submit" class="vs-btn vs-btn-primary">📤 <?php echo t('btn_upload'); ?></button>
    </form>
</div>

<div class="activity-timeline" style="margin-bottom:24px;">
    <h3><?php echo sprintf(t('doc_existing'), count($dokumente)); ?></h3>
    
    <?php if (empty($dokumente)): ?>
        <p style="color: #999; padding: 20px; text-align: center;"><?php echo t('doc_none'); ?></p>
    <?php else: ?>
        <table class="backend-table">
            <thead>
                <tr>
                    <th><?php echo t('doc_col_type'); ?></th>
                    <th><?php echo t('doc_col_filename'); ?></th>
                    <th><?php echo t('doc_col_size'); ?></th>
                    <th><?php echo t('doc_col_uploaded'); ?></th>
                    <th><?php echo t('doc_col_by'); ?></th>
                    <th><?php echo t('tab_actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dokumente as $doc): ?>
                    <tr>
                        <td>
                            <?php
                            $ext = strtolower(pathinfo($doc['original_name'], PATHINFO_EXTENSION));
                            $icon = '📄';
                            if ($ext === 'pdf') $icon = '📕';
                            elseif (in_array($ext, ['doc', 'docx'])) $icon = '📘';
                            elseif (in_array($ext, ['xls', 'xlsx'])) $icon = '📗';
                            elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) $icon = '🖼️';
                            echo $icon;
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($doc['original_name']); ?></td>
                        <td><?php echo number_format($doc['dateigröße'] / 1024, 0); ?> KB</td>
                        <td><?php echo date('d.m.Y H:i', strtotime($doc['hochgeladen_am'])); ?></td>
                        <td><?php echo htmlspecialchars($doc['hochgeladen_von']); ?></td>
                        <td>
                            <?php
                            // Ansicht-Button nur für PDFs und Bilder
                            $canView = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png']);
                            if ($canView):
                            ?>
                                <a href="download_document.php?id=<?php echo $doc['id']; ?>&view=1" 
                                   class="vs-btn vs-btn-sm" target="_blank" 
                                   title="<?php echo t('doc_open_browser'); ?>">👁️ <?php echo t('btn_view'); ?></a>
                            <?php endif; ?>
                            <a href="download_document.php?id=<?php echo $doc['id']; ?>" 
                               class="vs-btn vs-btn-sm vs-btn-secondary" target="_blank" 
                               title="<?php echo t('doc_download_title'); ?>">⬇️ <?php echo t('btn_download'); ?></a>
                            <a href="?id=<?php echo $wertsache_id; ?>&delete=<?php echo $doc['id']; ?>&<?php echo http_build_query(['csrf_token' => $_SESSION['csrf_token']]); ?>" 
                               class="vs-btn vs-btn-sm vs-btn-danger"
                               onclick="return vsConfirmLink(event, '<?php echo t('doc_confirm_delete'); ?>')">🗑️ <?php echo t('btn_delete'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div style="display:flex; gap:12px; margin-top:20px;">
    <a href="edit.php?id=<?php echo $wertsache_id; ?>" class="vs-btn vs-btn-secondary">← <?php echo t('doc_back_to_item'); ?></a>
    <a href="index.php" class="vs-btn vs-btn-secondary">🏠 <?php echo t('quick_to_overview'); ?></a>
</div>

</main>

<style>
.alert { padding:15px 20px; border-radius:8px; margin-bottom:20px; border-left:4px solid; }
.alert-success { background:var(--vs-success-light,#f0fdf4); border-color:var(--vs-success,#22c55e); color:#166534; }
.alert-danger  { background:var(--vs-danger-light,#fef2f2);  border-color:var(--vs-danger,#ef4444);  color:#991b1b; }
.backend-table { width:100%; border-collapse:collapse; margin-top:15px; }
.backend-table thead tr { background:var(--bg-color,#f8fafc); border-bottom:2px solid var(--border-color,#e2e8f0); }
.backend-table th, .backend-table td { padding:12px; text-align:left; font-size:14px; }
.backend-table tbody tr { border-bottom:1px solid var(--border-color,#e2e8f0); }
.backend-table tbody tr:hover { background:var(--bg-color,#f8fafc); }
</style>

<?php include 'footer_next.php'; ?>
