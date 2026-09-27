<?php
/**
 * Automatisches Backup Script
 * Wird täglich via Cron Job ausgeführt
 *
 * Cron Beispiel (täglich um 3 Uhr nachts):
 * 0 3 * * * /usr/bin/php /pfad/zu/wert/cron_backup.php >> /pfad/zu/wert/backups/cron.log 2>&1
 */

if (php_sapi_name() !== 'cli' && !isset($_GET['run_backup'])) {
    die('This script can only be run from command line or with ?run_backup parameter');
}

// Config laden (wird für BACKUP_TOKEN und DB benötigt)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/backup_rotation.php';

// Token-Schutz für HTTP-Aufrufe (CLI-Aufruf ist immer erlaubt)
if (php_sapi_name() !== 'cli') {
    $backupToken    = defined('BACKUP_TOKEN') ? BACKUP_TOKEN : 'changeme_set_in_config';
    $providedToken  = $_GET['token'] ?? '';
    if (!hash_equals($backupToken, $providedToken)) {
        header('HTTP/1.1 403 Forbidden');
        die('Access Denied');
    }
}

if (!defined('BACKUP_RETENTION_DAYS')) define('BACKUP_RETENTION_DAYS', 7);

// ── Backup-Konfiguration aus backup_config.json laden ──────
$configFile   = BACKUP_DIR . 'backup_config.json';
$backupConfig = ['db' => true, 'images' => true, 'phpfiles' => false];
if (file_exists($configFile)) {
    $loaded = json_decode(file_get_contents($configFile), true);
    if (is_array($loaded)) {
        $backupConfig = array_merge($backupConfig, $loaded);
    }
}

// Logging
$logFile  = BACKUP_DIR . 'cron.log';
$emailLog = [];

function logMessage($message, $isError = false) {
    global $logFile, $emailLog;
    $timestamp = date('Y-m-d H:i:s');
    $prefix    = $isError ? 'ERROR' : 'INFO';
    $logLine   = "[$timestamp] [$prefix] $message\n";
    file_put_contents($logFile, $logLine, FILE_APPEND);
    $emailLog[] = $logLine;
    echo $logLine;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow   = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Datenbank-Backup — alle Tabellen werden dynamisch aus der DB geladen,
 * damit Schema-Änderungen (z.B. orte → raeume/standorte/positionen) keinen
 * Fehler verursachen.
 */
function createDatabaseBackup() {
    global $pdo;
    try {
        $timestamp = date('Y-m-d_H-i-s');
        $filename  = 'database_' . $timestamp . '.sql';
        $filepath  = BACKUP_DIR . $filename;

        $file = fopen($filepath, 'w');
        if (!$file) throw new Exception("Kann Backup-Datei nicht erstellen: $filepath");

        fwrite($file, "-- Wertsachen-Inventar Datenbank-Backup\n");
        fwrite($file, "-- Erstellt am: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($file, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

        // Tabellen dynamisch ermitteln — keine Hardcodierung mehr nötig
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        // Tabellen in sinnvoller Reihenfolge sortieren (Abhängigkeiten berücksichtigen)
        $order = [
            'users', 'kategorien', 'orte', 'raeume', 'standorte', 'positionen',
            'wertsachen', 'item_images', 'dokumente', 'wert_historie',
            'versicherungen', 'permissions', 'activity_log', 'security_log', 'rate_limit',
            'app_settings',
        ];
        $orderedTables = [];
        foreach ($order as $t) {
            if (in_array($t, $tables)) $orderedTables[] = $t;
        }
        // Restliche Tabellen die nicht in der Reihenfolge stehen hinten anhängen
        foreach ($tables as $t) {
            if (!in_array($t, $orderedTables)) $orderedTables[] = $t;
        }

        foreach ($orderedTables as $table) {
            try {
                $result = $pdo->query("SHOW CREATE TABLE `$table`");
                $row    = $result->fetch(PDO::FETCH_ASSOC);
                fwrite($file, "-- Tabelle `$table`\nDROP TABLE IF EXISTS `$table`;\n" . $row['Create Table'] . ";\n\n");

                $rows  = $pdo->query("SELECT * FROM `$table`");
                $count = 0;
                fwrite($file, "-- Daten `$table`\n");
                foreach ($rows as $row) {
                    if ($count === 0) {
                        fwrite($file, "INSERT INTO `$table` (`" . implode('`, `', array_keys($row)) . "`) VALUES\n");
                    }
                    $values = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($row));
                    fwrite($file, ($count > 0 ? ',' : '') . "(" . implode(', ', $values) . ")\n");
                    $count++;
                }
                if ($count > 0) fwrite($file, ";\n\n");
            } catch (PDOException $e) {
                logMessage("Tabelle `$table` konnte nicht gesichert werden: " . $e->getMessage(), true);
            }
        }

        fwrite($file, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($file);

        $size = filesize($filepath);
        logMessage("Datenbank-Backup erstellt: $filename (" . formatBytes($size) . ", " . count($orderedTables) . " Tabellen)");
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath, 'size' => $size];

    } catch (Exception $e) {
        logMessage("Datenbank-Backup fehlgeschlagen: " . $e->getMessage(), true);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Bilder & Dateien Backup (upload/ + documents/)
 */
function createFilesBackup() {
    try {
        $timestamp = date('Y-m-d_H-i-s');
        $filename  = 'files_' . $timestamp . '.zip';
        $filepath  = BACKUP_DIR . $filename;

        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE) !== true) {
            throw new Exception("Kann ZIP-Datei nicht erstellen");
        }

        $fileCount = 0;

        if (is_dir(UPLOAD_DIR)) {
            foreach (glob(UPLOAD_DIR . '*') as $file) {
                if (is_file($file)) { $zip->addFile($file, 'upload/' . basename($file)); $fileCount++; }
            }
        }

        $documentsDir = __DIR__ . '/documents/';
        if (is_dir($documentsDir)) {
            foreach (glob($documentsDir . '*') as $file) {
                if (is_file($file)) { $zip->addFile($file, 'documents/' . basename($file)); $fileCount++; }
            }
        }

        $zip->close();
        $size = filesize($filepath);
        logMessage("Dateien-Backup erstellt: $filename ($fileCount Dateien, " . formatBytes($size) . ")");
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath, 'size' => $size, 'file_count' => $fileCount];

    } catch (Exception $e) {
        logMessage("Dateien-Backup fehlgeschlagen: " . $e->getMessage(), true);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * PHP-Dateien Backup (alle .php im Projektverzeichnis)
 */
function createPhpBackup() {
    try {
        $timestamp = date('Y-m-d_H-i-s');
        $filename  = 'php_' . $timestamp . '.zip';
        $filepath  = BACKUP_DIR . $filename;

        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE) !== true) {
            throw new Exception("Kann ZIP-Datei nicht erstellen");
        }

        $fileCount  = 0;
        $projectDir = __DIR__ . '/';

        // PHP-Dateien im Hauptverzeichnis
        foreach (glob($projectDir . '*.php') as $file) {
            if (is_file($file)) { $zip->addFile($file, basename($file)); $fileCount++; }
        }

        // PHP-Dateien in Unterverzeichnissen (1 Ebene tief)
        foreach (['backend', 'lang', 'includes', 'components'] as $subdir) {
            $dir = $projectDir . $subdir . '/';
            if (is_dir($dir)) {
                foreach (glob($dir . '*.php') as $file) {
                    if (is_file($file)) { $zip->addFile($file, $subdir . '/' . basename($file)); $fileCount++; }
                }
            }
        }

        // CSS-Dateien
        $cssDir = $projectDir . 'css/';
        if (is_dir($cssDir)) {
            foreach (glob($cssDir . '*.css') as $file) {
                if (is_file($file)) { $zip->addFile($file, 'css/' . basename($file)); $fileCount++; }
            }
        }

        $zip->close();
        $size = filesize($filepath);
        logMessage("PHP-Backup erstellt: $filename ($fileCount Dateien, " . formatBytes($size) . ")");
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath, 'size' => $size, 'file_count' => $fileCount];

    } catch (Exception $e) {
        logMessage("PHP-Backup fehlgeschlagen: " . $e->getMessage(), true);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Alte Backups rotieren
 */
function rotateBackups() {
    $geloescht = sicherungenAufraeumen(BACKUP_DIR, (int)BACKUP_RETENTION_DAYS);
    foreach ($geloescht as [$name, $alter]) {
        logMessage("Altes Backup gelöscht: $name (Alter: $alter Tage)");
    }
    if ($geloescht) logMessage("Backup-Rotation: " . count($geloescht) . " Backups gelöscht");
    return count($geloescht);
}

/**
 * Email-Benachrichtigung
 */
function sendEmailNotification($results, $deleted) {
    global $emailLog;

    $emailFile  = BACKUP_DIR . 'admin_email.txt';
    $adminEmail = null;
    if (file_exists($emailFile)) {
        $e = trim(file_get_contents($emailFile));
        if (filter_var($e, FILTER_VALIDATE_EMAIL)) $adminEmail = $e;
    }
    if (!$adminEmail && defined('ADMIN_EMAIL') && ADMIN_EMAIL) $adminEmail = ADMIN_EMAIL;
    if (!$adminEmail) { logMessage("Keine Admin-Email konfiguriert"); return false; }

    $allSuccess = !in_array(false, array_column($results, 'success'));
    $subject    = ($allSuccess ? "✅" : "❌") . " Backup " . ($allSuccess ? "erfolgreich" : "fehlgeschlagen") . " - " . date('d.m.Y H:i');

    $body = "Backup-Bericht vom " . date('d.m.Y H:i:s') . "\n\n";
    foreach ($results as $type => $r) {
        $body .= "=== " . strtoupper($type) . " ===\n";
        if ($r['success']) {
            $body .= "✓ Erfolgreich: {$r['filename']} (" . formatBytes($r['size']) . ")\n\n";
        } else {
            $body .= "✗ Fehlgeschlagen: {$r['error']}\n\n";
        }
    }
    $body .= "Gelöschte alte Backups: $deleted\n\n=== LOG ===\n" . implode('', $emailLog);

    // Absender aus der eigenen Domain, wie in password_reset_helpers.php und
    // public.php. Bis 4.3.26 stand hier fest die Adresse des Masters - jede
    // fremde Installation haette Mails im Namen des Entwicklers verschickt,
    // die an SPF scheitern. Laeuft die Sicherung per Kommandozeile, gibt es
    // keinen HTTP_HOST; dann BASE_URL, zuletzt der Rechnername. MAIL_FROM in
    // config.php geht allem vor.
    $mailHost = preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($mailHost === '' && defined('BASE_URL')) $mailHost = (string)parse_url(BASE_URL, PHP_URL_HOST);
    if ($mailHost === '') $mailHost = gethostname() ?: 'localhost';
    $mailFrom = (defined('MAIL_FROM') && MAIL_FROM) ? MAIL_FROM : 'noreply@' . $mailHost;
    $headers  = "From: ValuSafe <" . $mailFrom . ">\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    if (mail($adminEmail, $subject, $body, $headers)) {
        logMessage("Email gesendet an: $adminEmail");
        return true;
    }
    logMessage("Email konnte nicht gesendet werden", true);
    return false;
}

// ============================================================
// HAUPTPROGRAMM
// ============================================================

// Sicherheitsprüfung: Nicht öfter als einmal pro Stunde automatisch backuppen
$lockFile = BACKUP_DIR . 'cron.lock';
$lockAge  = file_exists($lockFile) ? (time() - filemtime($lockFile)) : 9999;

if (php_sapi_name() !== 'cli' && $lockAge < 3600) {
    header('Content-Type: text/plain');
    echo "Backup bereits in letzter Stunde ausgeführt (vor " . round($lockAge / 60) . " Minuten). Übersprungen.";
    exit(0);
}

// Lock-Datei aktualisieren
file_put_contents($lockFile, date('Y-m-d H:i:s'));

logMessage("=== AUTOMATISCHES BACKUP GESTARTET ===");
logMessage("Konfiguration: DB=" . ($backupConfig['db'] ? 'ja' : 'nein') .
           " | Bilder=" . ($backupConfig['images'] ? 'ja' : 'nein') .
           " | PHP=" . ($backupConfig['phpfiles'] ? 'ja' : 'nein'));

if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
    logMessage("Backup-Verzeichnis erstellt: " . BACKUP_DIR);
}

$results = [];

if ($backupConfig['db']) {
    logMessage("Erstelle Datenbank-Backup...");
    $results['Datenbank'] = createDatabaseBackup();
} else {
    logMessage("Datenbank-Backup übersprungen (deaktiviert)");
}

if ($backupConfig['images']) {
    logMessage("Erstelle Dateien-Backup...");
    $results['Dateien'] = createFilesBackup();
} else {
    logMessage("Dateien-Backup übersprungen (deaktiviert)");
}

if ($backupConfig['phpfiles']) {
    logMessage("Erstelle PHP-Backup...");
    $results['PHP'] = createPhpBackup();
} else {
    logMessage("PHP-Backup übersprungen (deaktiviert)");
}

logMessage("Rotiere alte Backups...");
$deleted = rotateBackups();

logMessage("Sende Email-Benachrichtigung...");
sendEmailNotification($results, $deleted);

if (empty($results)) logMessage("Keine Sicherungsart aktiviert - nichts gesichert", true);
$success = !empty($results) && !in_array(false, array_column($results, 'success'));
if ($success) {
    logMessage("=== BACKUP ERFOLGREICH ABGESCHLOSSEN ===");
    exit(0);
} else {
    logMessage("=== BACKUP MIT FEHLERN ABGESCHLOSSEN ===", true);
    exit(1);
}
