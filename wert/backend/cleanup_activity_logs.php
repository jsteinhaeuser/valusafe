<?php
/**
 * backend/cleanup_activity_logs.php — Alte Eintraege aus dem
 * Aktivitaetsprotokoll loeschen.
 *
 * Lag bis 4.3.14 im Wurzelverzeichnis. Verschoben, weil die Seite seither
 * SuperAdmin-only ist: Backend-Seiten fuehrt das Projekt bewusst nur in
 * Deutsch und Englisch, und ein langer Erklaertext in neun Sprachen fuer
 * eine Seite, die nur SuperAdmins oeffnen, waere schlecht angelegt.
 */
require_once __DIR__ . '/config.php';
if (file_exists(__DIR__ . '/../helpers.php')) require_once __DIR__ . '/../helpers.php';
requireBackendAccess();
// SuperAdmin, nicht Admin: dieselbe unwiderrufliche Loeschung gab es hier
// eine Rechtestufe niedriger als im Backend. Wer die Backend-Seite nicht
// oeffnen darf, konnte dieselben Daten ueber diese Seite entfernen.
requireSuperAdmin();

$pageTitle = t('syscleanup_title');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateRequest();
    
    $days = filter_var($_POST['days'] ?? 365, FILTER_VALIDATE_INT);
    
    if (!$days || $days < 1) {
        $error = t('log_invalid_days');
    } else {
        try {
            $deleted = cleanupOldActivityLogs($days);
            $message = sprintf(t('log_entries_deleted'), $deleted, $days);
            
            // Diese Aktion selbst loggen
            logActivity('deleted', 'activity_log', null, 'Cleanup', null, [
                'deleted_count' => $deleted,
                'older_than_days' => $days
            ]);
            
        } catch (Exception $e) {
            $error = sprintf(t('syscleanup_error'), $e->getMessage());
        }
    }
}

// Statistiken laden
try {
    $stats = $db->selectOne("
        SELECT 
            COUNT(*) as total,
            MIN(zeitstempel) as oldest,
            MAX(zeitstempel) as newest,
            COUNT(CASE WHEN zeitstempel < DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as older_than_24h,
            COUNT(CASE WHEN zeitstempel < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as older_than_7days,
            COUNT(CASE WHEN zeitstempel < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as older_than_30days,
            COUNT(CASE WHEN zeitstempel < DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 1 END) as older_than_3months
        FROM activity_log
    ");
} catch (PDOException $e) {
    $stats = null;
}

include 'layout/header_next_page.php';
?>

<style>
.cleanup-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.cleanup-section h3 {
    margin-top: 0;
    color: #333;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.stat-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.stat-value {
    font-size: 2em;
    font-weight: bold;
    color: var(--primary-color, #3498db);
}

.stat-label {
    font-size: 0.9em;
    color: #666;
    margin-top: 5px;
}

.warning-box {
    background: #fff3cd;
    border: 1px solid #ffc107;
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
}

.danger-box {
    background: #f8d7da;
    border: 1px solid #dc3545;
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
}

.cleanup-options {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.cleanup-option {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border: 2px solid #ddd;
    cursor: pointer;
    transition: all 0.3s;
}

.cleanup-option:hover {
    border-color: var(--primary-color, #3498db);
    background: #e9ecef;
}

.cleanup-option input[type="radio"] {
    margin-right: 10px;
}

.cleanup-option label {
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.cleanup-option-desc {
    margin-left: 28px;
    font-size: 0.9em;
    color: #666;
    margin-top: 8px;
}
</style>

<h2>🗑️ <?php echo t('syscleanup_title'); ?></h2>

<?php if ($message): ?>
    <div class="success-message"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Statistiken -->
<?php if ($stats): ?>
<div class="cleanup-section">
    <h3>📊 <?php echo t('syscleanup_stats'); ?></h3>
    
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
            <div class="stat-label"><?php echo t('syscleanup_total'); ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo number_format($stats['older_than_24h']); ?></div>
            <div class="stat-label"><?php echo t('syscleanup_older_24h'); ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo number_format($stats['older_than_7days']); ?></div>
            <div class="stat-label"><?php echo t('syscleanup_older_7d'); ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo number_format($stats['older_than_30days']); ?></div>
            <div class="stat-label"><?php echo t('syscleanup_older_30d'); ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo number_format($stats['older_than_3months']); ?></div>
            <div class="stat-label"><?php echo t('syscleanup_older_3m'); ?></div>
        </div>
    </div>
    
    <div style="margin-top: 20px; font-size: 0.9em; color: #666;">
        <strong><?php echo t('log_oldest_entry'); ?>:</strong> <?php echo $stats['oldest'] ? formatDateTime($stats['oldest']) : t('log_no_entries'); ?><br>
        <strong><?php echo t('log_newest_entry'); ?>:</strong> <?php echo $stats['newest'] ? formatDateTime($stats['newest']) : t('log_no_entries'); ?>
    </div>
</div>
<?php endif; ?>

<!-- Bereinigung -->
<div class="cleanup-section">
    <h3>🧹 <?php echo t('syscleanup_heading'); ?></h3>
    
    <div class="warning-box">
        <strong>⚠️ <?php echo t('syscleanup_warn_title'); ?></strong> <?php echo t('syscleanup_warn_text'); ?>
    </div>
    
    <form method="POST" action="">
        <?php echo Security::getCSRFInput(); ?>
        
        <div class="cleanup-options">
            <div class="cleanup-option">
                <label>
                    <input type="radio" name="days" value="1" required>
                    <?php echo t('syscleanup_opt_24h'); ?>
                </label>
                <div class="cleanup-option-desc">
                    <?php echo sprintf(t('syscleanup_deletes_n'), number_format($stats['older_than_24h'] ?? 0)); ?> — <?php echo t('syscleanup_after_tests'); ?>
                </div>
            </div>

            <div class="cleanup-option">
                <label>
                    <input type="radio" name="days" value="7">
                    <?php echo t('syscleanup_opt_7d'); ?>
                </label>
                <div class="cleanup-option-desc">
                    <?php echo sprintf(t('syscleanup_deletes_n'), number_format($stats['older_than_7days'] ?? 0)); ?>
                </div>
            </div>

            <div class="cleanup-option">
                <label>
                    <input type="radio" name="days" value="30">
                    <?php echo t('syscleanup_opt_30d'); ?>
                </label>
                <div class="cleanup-option-desc">
                    <?php echo sprintf(t('syscleanup_deletes_n'), number_format($stats['older_than_30days'] ?? 0)); ?>
                </div>
            </div>

            <div class="cleanup-option">
                <label>
                    <input type="radio" name="days" value="90" checked>
                    <?php echo t('syscleanup_opt_3m'); ?>
                </label>
                <div class="cleanup-option-desc">
                    <?php echo sprintf(t('syscleanup_deletes_n'), number_format($stats['older_than_3months'] ?? 0)); ?>
                </div>
            </div>

            <div class="cleanup-option" id="customOption">
                <label>
                    <input type="radio" name="days" value="custom" id="customRadio">
                    <?php echo t('syscleanup_opt_custom'); ?>
                </label>
                <div class="cleanup-option-desc" style="margin-top:10px;">
                    <label style="display:flex; align-items:center; gap:8px;">
                        <?php echo t('syscleanup_older_than'); ?>
                        <input type="number" id="customDays" name="custom_days" min="1" max="3650" value="60"
                               style="width:70px; padding:4px 8px; border:1px solid #ddd; border-radius:4px;"
                               onclick="document.getElementById('customRadio').checked=true">
                        <?php echo t('syscleanup_days_delete'); ?>
                    </label>
                </div>
            </div>
        </div>
        
        <script>
        // Bei benutzerdefinierter Auswahl: Wert aus Eingabefeld übernehmen
        document.querySelector('form').addEventListener('submit', function(e) {
            const customRadio = document.getElementById('customRadio');
            if (customRadio.checked) {
                const days = parseInt(document.getElementById('customDays').value);
                if (!days || days < 1) {
                    e.preventDefault();
                    vsAlert(<?php echo json_encode(t('log_enter_valid_days')); ?>);
                    return;
                }
                customRadio.value = days;
            }

            // Rueckfrage erst, wenn die Eingabe stimmt. Die Marke laesst den
            // zweiten Durchlauf nach dem Ja durch.
            if (this.dataset.vsBestaetigt === 'ja') {
                this.dataset.vsBestaetigt = '';
                return;
            }
            e.preventDefault();
            const formular = this;
            vsConfirm(<?php echo json_encode(t('log_confirm_delete')); ?>).then(function (ja) {
                if (!ja) { return; }
                formular.dataset.vsBestaetigt = 'ja';
                if (typeof formular.requestSubmit === 'function') { formular.requestSubmit(); }
                else { formular.submit(); }
            });
        });
        </script>
        
        <div style="margin-top: 25px;">
            <button type="submit" class="btn btn-danger">
                🗑️ <?php echo t('syscleanup_btn_delete'); ?>
            </button>
            <a href="../settings.php" class="btn"><?php echo t('btn_cancel'); ?></a>
        </div>
    </form>
</div>

<!-- Info-Box -->
<div class="cleanup-section">
    <h3>ℹ️ <?php echo t('syscleanup_info'); ?></h3>

    <h4><?php echo t('syscleanup_why'); ?></h4>
    <ul>
        <li><strong><?php echo t('syscleanup_why_perf'); ?></strong> <?php echo t('syscleanup_why_perf_d'); ?></li>
        <li><strong><?php echo t('syscleanup_why_space'); ?></strong> <?php echo t('syscleanup_why_space_d'); ?></li>
        <li><strong><?php echo t('syscleanup_why_privacy'); ?></strong> <?php echo t('syscleanup_why_privacy_d'); ?></li>
        <li><strong><?php echo t('syscleanup_why_clarity'); ?></strong> <?php echo t('syscleanup_why_clarity_d'); ?></li>
    </ul>

    <h4><?php echo t('syscleanup_what'); ?></h4>
    <ul>
        <li><?php echo t('syscleanup_what_1'); ?></li>
        <li><?php echo t('syscleanup_what_2'); ?></li>
        <li><?php echo t('syscleanup_what_3'); ?></li>
        <li><?php echo t('syscleanup_what_4'); ?></li>
    </ul>

    <h4><?php echo t('syscleanup_hint'); ?></h4>
    <p><?php echo t('syscleanup_hint_text'); ?></p>
</div>

<div class="form-actions">
    <a href="activity_log.php" class="btn btn-secondary">📜 <?php echo t('syscleanup_to_logs'); ?></a>
    <a href="../settings.php" class="btn">← <?php echo t('syscleanup_back'); ?></a>
</div>

<?php include 'layout/footer_next_page.php'; ?>
