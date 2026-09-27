<?php
/**
 * backend/superadmin.php
 * SuperAdmin-Einstellungen — nur für den konfigurierten SuperAdmin sichtbar.
 * Zugang wird über SUPERADMIN_USERNAME in config.php gesteuert (hardcodiert).
 */

require_once __DIR__ . '/config.php';
requireBackendAccess();

// Nur SuperAdmin darf diese Seite sehen
if (!isSuperAdmin()) {
    header('Location: /backend/index.php');
    exit;
}

$pageTitle = 'SuperAdmin-Einstellungen';
$message     = '';
$messageIcon = '';
$error       = '';

// Einstellungen laden
function getSASettings(object $db): array {
    $keys = [
        'sa_backup_db_allowed',
        'sa_backup_php_allowed',
        'sa_backup_images_allowed',
        'sa_backup_download_allowed',
        'sa_restore_allowed',
    ];
    $result = [];
    foreach ($keys as $key) {
        try {
            $row = $db->selectOne("SELECT setting_value FROM app_settings WHERE setting_key = ?", [$key]);
            $result[$key] = ($row['setting_value'] ?? '1') === '1';
        } catch (Exception $e) {
            $result[$key] = true; // Default: erlaubt
        }
    }
    return $result;
}

function saveSASetting(object $db, string $key, string $value): void {
    $db->execute(
        "INSERT INTO app_settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
        [$key, $value]
    );
}

// POST-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateRequest();

    try {
        saveSASetting($db, 'sa_backup_db_allowed',        isset($_POST['sa_backup_db_allowed'])        ? '1' : '0');
        saveSASetting($db, 'sa_backup_php_allowed',      isset($_POST['sa_backup_php_allowed'])      ? '1' : '0');
        saveSASetting($db, 'sa_backup_images_allowed',   isset($_POST['sa_backup_images_allowed'])   ? '1' : '0');
        saveSASetting($db, 'sa_backup_download_allowed', isset($_POST['sa_backup_download_allowed']) ? '1' : '0');
        saveSASetting($db, 'sa_restore_allowed',         isset($_POST['sa_restore_allowed'])         ? '1' : '0');
        $messageIcon = 'ti-circle-check';
        $message     = 'SuperAdmin-Einstellungen gespeichert.';
    } catch (Exception $e) {
        $error = 'Fehler: ' . $e->getMessage();
    }
}

$settings = getSASettings($db);

$breadcrumb = ['SuperAdmin-Einstellungen', '<i class="ti ti-key"></i>', 'System'];
require_once 'layout/header_next_page.php';
?>

<style>
.sa-card {
    background: var(--bg-white, #fff);
    border: 2px solid var(--vs-danger);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}
.sa-card-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--vs-danger);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.sa-card-desc {
    font-size: 13px;
    color: var(--vs-text-muted);
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f3f4f6;
}
.sa-toggle-row {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid #f3f4f6;
}
.sa-toggle-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.sa-toggle-row input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-top: 2px;
    flex-shrink: 0;
    accent-color: var(--vs-danger);
}
.sa-toggle-label strong {
    display: block;
    font-size: 14px;
    color: var(--vs-text);
    margin-bottom: 2px;
}
.sa-toggle-label small {
    color: var(--vs-text-muted);
    font-size: 12px;
}
.sa-warning {
    background: var(--vs-danger-light);
    border: 1px solid #fecaca;
    border-left: 4px solid var(--vs-danger);
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 24px;
    font-size: 13px;
    color: var(--vs-danger);
}
.sa-info {
    background: var(--vs-accent-light);
    border: 1px solid #bfdbfe;
    border-left: var(--vs-accent);
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 24px;
    font-size: 13px;
    color: var(--vs-accent);
}
</style>

<div class="backend-content">

<?php if ($message): ?>
    <div class="alert alert-success">
        <?php if ($messageIcon): ?><i class="ti <?php echo $messageIcon; ?>" style="color:var(--vs-success);"></i> <?php endif; ?>
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="sa-warning">
    <i class="ti ti-key"></i> <strong>SuperAdmin-Bereich</strong> — Diese Einstellungen sind nur für Sie als Betreiber sichtbar.
    Admins der jeweiligen Instanz haben keinen Zugriff auf diese Seite.
</div>

<div class="sa-info">
    ℹ️ Der SuperAdmin-Zugang ist in <code>config.php</code> hardcodiert:
    <code>define('SUPERADMIN_USERNAME', '<?php echo htmlspecialchars(SUPERADMIN_USERNAME); ?>');</code>
    Er kann nicht über die Benutzeroberfläche geändert werden.
</div>


<form method="POST">
    <?php echo Security::getCSRFInput(); ?>

    <div class="sa-card">
        <div class="sa-card-title"><i class="ti ti-device-floppy"></i> Backup-Einschränkungen</div>
        <div class="sa-card-desc">
            Steuern Sie welche Backup-Funktionen normale Admins nutzen dürfen.
            Als SuperAdmin haben Sie immer vollen Zugriff.
        </div>

        <div class="sa-toggle-row">
            <input type="checkbox" name="sa_backup_db_allowed" id="sa_backup_db_allowed"
                   <?php echo $settings['sa_backup_db_allowed'] ? 'checked' : ''; ?>>
            <label class="sa-toggle-label" for="sa_backup_db_allowed">
                <strong>Datenbank-Backup erlauben</strong>
                <small>Wenn deaktiviert, können Admins kein Datenbank-Backup erstellen.
                Verhindert Export der vollständigen Kundendaten.</small>
            </label>
        </div>

        <div class="sa-toggle-row">
            <input type="checkbox" name="sa_backup_php_allowed" id="sa_backup_php_allowed"
                   <?php echo $settings['sa_backup_php_allowed'] ? 'checked' : ''; ?>>
            <label class="sa-toggle-label" for="sa_backup_php_allowed">
                <strong>PHP-Dateien sichern erlauben</strong>
                <small>Wenn deaktiviert, können Admins kein Backup der PHP-Quelldateien erstellen.
                Schützt den Quellcode vor unberechtigtem Export.</small>
            </label>
        </div>

        <div class="sa-toggle-row">
            <input type="checkbox" name="sa_backup_images_allowed" id="sa_backup_images_allowed"
                   <?php echo $settings['sa_backup_images_allowed'] ? 'checked' : ''; ?>>
            <label class="sa-toggle-label" for="sa_backup_images_allowed">
                <strong>Bilder & Dokumente sichern erlauben</strong>
                <small>Wenn deaktiviert, werden nur Datenbank-Backups erstellt — keine Upload-Dateien.</small>
            </label>
        </div>

        <div class="sa-toggle-row">
            <input type="checkbox" name="sa_backup_download_allowed" id="sa_backup_download_allowed"
                   <?php echo $settings['sa_backup_download_allowed'] ? 'checked' : ''; ?>>
            <label class="sa-toggle-label" for="sa_backup_download_allowed">
                <strong>Backup-Download erlauben</strong>
                <small>Wenn deaktiviert, können Admins Backups sehen aber nicht herunterladen.</small>
            </label>
        </div>

        <div class="sa-toggle-row">
            <input type="checkbox" name="sa_restore_allowed" id="sa_restore_allowed"
                   <?php echo $settings['sa_restore_allowed'] ? 'checked' : ''; ?>>
            <label class="sa-toggle-label" for="sa_restore_allowed">
                <strong>Restore erlauben</strong>
                <small>Wenn deaktiviert, können Admins keine Backups wiederherstellen.</small>
            </label>
        </div>
    </div>

    <button type="submit" class="vs-btn vs-btn-primary" style="background:var(--vs-danger); border-color:var(--vs-danger);">
        <i class="ti ti-device-floppy"></i> Einstellungen speichern
    </button>
</form>

<!-- Cron-Token-Info -->
<div style="margin-top:32px; background:rgba(255,255,255,0.6); border:1px solid rgba(0,0,0,0.08); border-radius:16px; padding:24px;">
    <h3 style="margin:0 0 12px;">⏱️ Automatisches Backup (Cron-Job)</h3>
    <p style="color:#555; font-size:14px; margin:0 0 16px;">
        Trage diese URL bei deinem Cron-Dienst (z.&nbsp;B. cron-job.org) ein. Der Token wird automatisch aus den DB-Zugangsdaten dieser Instanz berechnet.
    </p>
    <?php
    $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'];
    $cronToken = defined('BACKUP_TOKEN') ? BACKUP_TOKEN : '(BACKUP_TOKEN nicht definiert)';
    $cronUrl   = $protocol . '://' . $host . '/cron_backup.php?run_backup=1&token=' . urlencode($cronToken);
    ?>
    <div style="background:var(--vs-surface-2); border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; font-family:monospace; font-size:13px; word-break:break-all; margin-bottom:12px;">
        <?php echo htmlspecialchars($cronUrl); ?>
    </div>
    <button type="button" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($cronUrl); ?>').then(()=>this.textContent='<i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Kopiert!')" class="vs-btn vs-btn-secondary" style="font-size:13px;">
        <i class="ti ti-clipboard-list"></i> URL kopieren
    </button>
</div>

</div><!-- /.backend-content -->
<?php require_once 'layout/footer_next_page.php'; ?>
