<?php
/**
 * Restore Management
 * backend/restore.php
 *
 * Stellt ein Backup wieder her:
 * - DB-Restore: SQL-Dump einlesen und einspielen
 * - Dateien-Restore: ZIP entpacken nach /upload/ und /documents/
 *
 * Sicherheit:
 * - Nur Admin-Zugriff
 * - CSRF-Schutz
 * - Nur Dateien aus BACKUP_DIR erlaubt (Path-Traversal-Schutz)
 * - Bestätigungspflicht vor Überschreiben
 */

require_once __DIR__ . '/config.php';
requireBackendAccess();

if (!function_exists('esc')) {
    require_once __DIR__ . '/../helpers.php';
}

// Restore nur wenn SuperAdmin-Einstellung erlaubt
if (!isSuperAdmin() && !getSuperAdminSetting('sa_restore_allowed', true)) {
    header('Location: /backend/backup.php');
    exit;
}

define('PAGE_TITLE', 'Restore - Wertsachen-Inventar');

$message = '';
$error   = '';
$step    = 'select'; // 'select' | 'confirm' | 'done'

// ── Hilfsfunktionen ─────────────────────────────────────────────────────────

function validateBackupFile(string $filename): string|false {
    $filename = basename($filename); // Path-Traversal-Schutz
    $filepath = BACKUP_DIR . $filename;
    if (!file_exists($filepath)) return false;
    $real = realpath($filepath);
    $base = realpath(BACKUP_DIR);
    if (!$real || !$base || strpos($real, $base) !== 0) return false;
    return $filepath;
}

function isDbBackup(string $filename): bool {
    return str_starts_with($filename, 'database_') && str_ends_with($filename, '.sql');
}

function isFilesBackup(string $filename): bool {
    return (str_starts_with($filename, 'files_') || str_starts_with($filename, 'php_'))
        && str_ends_with($filename, '.zip');
}

// ── Schritt 2: Bestätigung empfangen → Restore ausführen ────────────────────

if (isset($_POST['do_restore'])) {
    validateRequest();

    $filename = basename($_POST['filename'] ?? '');
    $filepath = validateBackupFile($filename);

    if (!$filepath) {
        $error = 'Datei nicht gefunden oder ungültig.';
    } elseif (isDbBackup($filename)) {

        // ── DB-Restore ──────────────────────────────────────────────────────
        try {
            $sql = file_get_contents($filepath);
            if ($sql === false) throw new Exception('SQL-Datei konnte nicht gelesen werden.');

            // Einzelne Statements aufteilen (einfacher Splitter für mysqldump-Output)
            // Trennzeichen: Semikolon am Zeilenende (außer in Strings)
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            $pdo->exec('SET NAMES utf8mb4');

            // SQL in Statements aufteilen
            $statements = [];
            $current    = '';
            $inString   = false;
            $stringChar = '';

            foreach (str_split($sql) as $char) {
                if (!$inString && ($char === "'" || $char === '"')) {
                    $inString   = true;
                    $stringChar = $char;
                } elseif ($inString && $char === $stringChar) {
                    $inString = false;
                }

                $current .= $char;

                if (!$inString && $char === ';') {
                    $stmt = trim($current);
                    if ($stmt !== '' && $stmt !== ';') {
                        $statements[] = $stmt;
                    }
                    $current = '';
                }
            }

            $executed = 0;
            $skipped  = 0;
            foreach ($statements as $stmt) {
                // Kommentare und SET-Zeilen überspringen die nichts ändern
                if (preg_match('/^(--|\/\*|SET NAMES|SET FOREIGN)/', $stmt)) {
                    $skipped++;
                    continue;
                }
                try {
                    $pdo->exec($stmt);
                    $executed++;
                } catch (PDOException $e) {
                    // Einzelfehler loggen aber weitermachen
                    error_log('Restore SQL Fehler: ' . $e->getMessage() . ' → ' . substr($stmt, 0, 80));
                }
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

            Security::logSecurityEvent('db_restored', [
                'file'     => $filename,
                'executed' => $executed,
            ]);

            $message = "<i class='ti ti-circle-check' style='color:var(--vs-success);'></i> Datenbank wiederhergestellt aus <strong>" . esc($filename) . "</strong> — $executed Statements ausgeführt.";
            $step    = 'done';

        } catch (Exception $e) {
            $error = '<i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> DB-Restore fehlgeschlagen: ' . esc($e->getMessage());
        }

    } elseif (isFilesBackup($filename)) {

        // ── Dateien-Restore ─────────────────────────────────────────────────
        try {
            $zip = new ZipArchive();
            if ($zip->open($filepath) !== true) throw new Exception('ZIP konnte nicht geöffnet werden.');

            $baseDir  = realpath(__DIR__ . '/../');
            $extracted = 0;
            $skipped   = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);

                // Nur upload/ und documents/ erlaubt — kein Path-Traversal
                if (!preg_match('#^(upload|documents)/#', $name)) {
                    $skipped++;
                    continue;
                }

                $target = $baseDir . '/' . $name;
                $targetDir = dirname($target);

                // Sicherheitscheck: Ziel muss innerhalb des Projekts liegen
                if (strpos(realpath($targetDir) ?: $targetDir, $baseDir) !== 0) {
                    $skipped++;
                    continue;
                }

                if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

                $data = $zip->getFromIndex($i);
                if ($data !== false) {
                    file_put_contents($target, $data);
                    $extracted++;
                }
            }
            $zip->close();

            Security::logSecurityEvent('files_restored', [
                'file'      => $filename,
                'extracted' => $extracted,
            ]);

            $message = "<i class='ti ti-circle-check' style='color:var(--vs-success);'></i> Dateien wiederhergestellt aus <strong>" . esc($filename) . "</strong> — $extracted Dateien entpackt" . ($skipped ? ", $skipped übersprungen" : "") . ".";
            $step    = 'done';

        } catch (Exception $e) {
            $error = '<i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> Dateien-Restore fehlgeschlagen: ' . esc($e->getMessage());
        }

    } else {
        $error = '<i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> Unbekannter Backup-Typ.';
    }
}

// ── Schritt 1: Bestätigung anfordern ────────────────────────────────────────

if (isset($_POST['request_restore']) && $step === 'select') {
    validateRequest();
    $filename = basename($_POST['filename'] ?? '');
    if (validateBackupFile($filename)) {
        $step = 'confirm';
    } else {
        $error = '<i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> Datei nicht gefunden.';
    }
}

// ── Backup-Dateien auflisten ─────────────────────────────────────────────────

$backups = [];
if (is_dir(BACKUP_DIR)) {
    $ignore = ['cron.log', '.htaccess', 'admin_email.txt', 'backup_config.json'];
    foreach (glob(BACKUP_DIR . '*') as $file) {
        if (!is_file($file)) continue;
        $name = basename($file);
        if (in_array($name, $ignore)) continue;
        if (!isDbBackup($name) && !isFilesBackup($name)) continue;
        $backups[] = [
            'filename' => $name,
            'size'     => filesize($file),
            'modified' => filemtime($file),
            'age_days' => (int)floor((time() - filemtime($file)) / 86400),
        ];
    }
}
usort($backups, fn($a, $b) => $b['modified'] - $a['modified']);

$confirmFile = basename($_POST['filename'] ?? '');

$pageTitle = 'Restore';
include 'layout/header_next_page.php';
?>

<style>
.restore-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
.restore-table th { padding: 10px 12px; text-align: left; font-size: 12px; font-weight: 600;
    text-transform: uppercase; letter-spacing: .04em; border-bottom: 2px solid rgba(0,0,0,0.08); color: #666; }
.restore-table td { padding: 10px 12px; border-bottom: 1px solid rgba(0,0,0,0.05); font-size: 14px; vertical-align: middle; }
.restore-table tr:last-child td { border-bottom: none; }
.restore-table tr:hover td { background: rgba(0,0,0,0.02); }

.warning-box {
    background: var(--vs-warning-light); border: 2px solid var(--vs-warning); border-radius: 12px;
    padding: 20px 24px; margin-bottom: 24px;
}
.warning-box h3 { margin: 0 0 8px; color: var(--vs-warning-text); }
.warning-box p  { margin: 0; color: var(--vs-warning-text); font-size: 14px; }

.confirm-box {
    background: var(--vs-surface); border: 2px solid var(--vs-danger); border-radius: 16px;
    padding: 32px; max-width: 560px; margin: 0 auto;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
}
.confirm-box h3 { margin: 0 0 12px; color: var(--vs-danger); }
.confirm-box .filename {
    background: var(--vs-surface-2); border-radius: 8px; padding: 10px 14px;
    font-family: monospace; font-size: 13px; color: var(--vs-text-muted);
    margin: 12px 0 20px; word-break: break-all;
}
</style>

<main class="backend-main">
<div style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
    <a href="backup.php" class="vs-btn vs-btn-secondary">← Zum Backup</a>
    <h2 style="margin:0; border:none; padding:0;">♻️ Restore</h2>
</div>

<?php if ($message): ?>
<div style="background:var(--vs-success-light); border:1px solid var(--vs-success); border-radius:12px; padding:16px 20px; margin-bottom:20px; color:var(--vs-success-text); font-size:15px;">
    <?php echo $message; ?>
    <div style="margin-top:12px;">
        <a href="backup.php" class="vs-btn vs-btn-primary">← Zurück zum Backup</a>
        <a href="../index.php" class="vs-btn vs-btn-secondary" style="margin-left:8px;">Zur Übersicht</a>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div style="background:#fee2e2; border:1px solid #fca5a5; border-radius:12px; padding:16px 20px; margin-bottom:20px; color:var(--vs-danger); font-size:15px;">
    <?php echo $error; ?>
</div>
<?php endif; ?>

<?php if ($step === 'confirm'): ?>

<!-- ── Bestätigungsdialog ──────────────────────────────────────────────── -->
<div class="confirm-box">
    <h3><i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i> Restore bestätigen</h3>
    <p style="color:#555; font-size:14px; margin:0 0 8px;">
        Die folgende Datei wird wiederhergestellt. Dies überschreibt die aktuellen Daten
        <strong>unwiderruflich</strong>:
    </p>
    <div class="filename"><?php echo esc($confirmFile); ?></div>

    <?php if (isDbBackup($confirmFile)): ?>
    <div style="background:var(--vs-danger-light); border-radius:8px; padding:12px 14px; margin-bottom:20px; font-size:13px; color:#7f1d1d;">
        <i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i> <strong>Achtung Datenbank-Restore:</strong> Alle aktuellen Daten (Gegenstände,
        Benutzer, Kategorien, Orte) werden durch den Backup-Stand ersetzt.
        Die aktuelle Sitzung bleibt aktiv.
    </div>
    <?php elseif (isFilesBackup($confirmFile)): ?>
    <div style="background:var(--vs-danger-light); border-radius:8px; padding:12px 14px; margin-bottom:20px; font-size:13px; color:#7f1d1d;">
        <i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i> <strong>Achtung Dateien-Restore:</strong> Alle Bilder und Dokumente im
        Upload-Ordner werden durch den Backup-Stand ersetzt.
    </div>
    <?php endif; ?>

    <p style="font-size:14px; color:#555; margin:0 0 20px;">
        Empfehlung: Erstelle zuerst ein aktuelles Backup bevor du einen Restore durchführst.
    </p>

    <div style="display:flex; gap:12px;">
        <form method="POST">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="do_restore" value="1">
            <input type="hidden" name="filename" value="<?php echo esc($confirmFile); ?>">
            <button type="submit" class="vs-btn vs-btn-secondary"
                    style="background:var(--vs-danger); color:var(--vs-surface); border-color:var(--vs-danger); padding:10px 24px; font-weight:600;">
                <i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i> Jetzt wiederherstellen
            </button>
        </form>
        <a href="restore.php" class="vs-btn vs-btn-secondary" style="padding:10px 24px;">Abbrechen</a>
    </div>
</div>

<?php elseif ($step === 'select'): ?>

<!-- ── Warnhinweis ────────────────────────────────────────────────────────── -->
<div class="warning-box">
    <h3><i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i> Wichtiger Hinweis</h3>
    <p>Ein Restore überschreibt die aktuellen Daten unwiderruflich mit dem Backup-Stand.
       Erstelle vor einem Restore immer ein aktuelles Backup als Sicherheitsnetz.</p>
</div>

<!-- ── Backup-Liste ───────────────────────────────────────────────────────── -->
<div style="background:rgba(255,255,255,0.6); border:1px solid rgba(0,0,0,0.08); border-radius:16px; padding:24px; box-shadow:0 2px 12px rgba(0,0,0,0.05);">
    <h3 style="margin:0 0 16px;">Verfügbare Backups</h3>

    <?php if (empty($backups)): ?>
    <p style="text-align:center; color:var(--vs-text-muted); padding:30px;">
        Keine Backups vorhanden. <a href="backup.php">Backup erstellen →</a>
    </p>
    <?php else: ?>
    <table class="restore-table">
        <thead>
            <tr>
                <th>Dateiname</th>
                <th>Typ</th>
                <th>Größe</th>
                <th>Erstellt</th>
                <th>Alter</th>
                <th style="text-align:right;">Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($backups as $b): ?>
            <tr>
                <td style="font-size:12px; font-family:monospace; color:#555;">
                    <?php echo esc($b['filename']); ?>
                </td>
                <td>
                    <?php if (isDbBackup($b['filename'])): ?>
                        <span style="background:#dbeafe; color:var(--vs-accent); padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600;">🗄️ DB</span>
                    <?php else: ?>
                        <span style="background:var(--vs-success-light); color:var(--vs-success-text); padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600;"><i class="ti ti-photo"></i> Dateien</span>
                    <?php endif; ?>
                </td>
                <td><?php echo formatBytes($b['size']); ?></td>
                <td><?php echo date('d.m.Y H:i', $b['modified']); ?></td>
                <td style="color:var(--vs-text-muted); font-size:13px;">
                    <?php if ($b['age_days'] === 0): ?>
                        <span style="color:var(--vs-success);">Heute</span>
                    <?php elseif ($b['age_days'] === 1): ?>
                        <span style="color:var(--vs-warning);">Gestern</span>
                    <?php else: ?>
                        <?php echo $b['age_days']; ?> Tage
                    <?php endif; ?>
                </td>
                <td style="text-align:right;">
                    <form method="POST" style="display:inline;">
                        <?php echo Security::getCSRFInput(); ?>
                        <input type="hidden" name="request_restore" value="1">
                        <input type="hidden" name="filename" value="<?php echo esc($b['filename']); ?>">
                        <button type="submit" class="vs-btn vs-btn-secondary"
                                style="padding:6px 14px; font-size:13px; background:var(--vs-danger); color:var(--vs-surface); border-color:var(--vs-danger);">
                            ♻️ Restore
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php endif; ?>

</main>

<?php include 'layout/footer_next_page.php'; ?>
