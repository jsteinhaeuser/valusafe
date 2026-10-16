<?php
/**
 * backend/datenuebernahme.php - Daten aus einer frueheren ValuSafe-Version
 * uebernehmen (4.5)
 *
 * Fuer den Weg "neue Version frisch installieren, alte Daten mitnehmen":
 * In der alten ValuSafe (ab 4.4) unter Backup die Datenbank und die Dateien
 * sichern, hier hochladen (oder per FTP nach backups/ legen), pruefen lassen,
 * uebernehmen. Danach laufen die Migrationen, die seit der alten Version
 * dazugekommen sind, und die Bilder und Belege werden entpackt.
 *
 * Uebernommen wird ALLES, auch die Benutzer samt Passwoertern (Joerg,
 * 14.10.2026): Besitzer, Raeume, Schadensfaelle und Rechte haengen ueber IDs
 * aneinander, ein teilweises Zusammenfuehren wuerde sie zerreissen. Wer die
 * Uebernahme startet, wird am Ende abgemeldet und meldet sich mit den
 * Zugangsdaten der alten Version an.
 *
 * Schritte: auswahl -> vorschau (prueft, fuehrt nichts aus) -> fertig.
 * Die Pruefung selbst steht in helpers_datenuebernahme.php.
 */

require_once __DIR__ . '/config.php';
requireBackendAccess();

if (!function_exists('esc')) {
    require_once __DIR__ . '/../helpers.php';
}
require_once __DIR__ . '/../helpers_datenuebernahme.php';
require_once __DIR__ . '/migration_runner.php';

// Wie restore.php: nur, wenn der Betreiber Wiederherstellen erlaubt
if (!isSuperAdmin() && !getSuperAdminSetting('sa_restore_allowed', true)) {
    header('Location: /backend/backup.php');
    exit;
}

$schritt = 'auswahl';     // 'auswahl' | 'vorschau' | 'fertig'
$fehler  = [];
$analyse = null;
$zipInfo = null;
$bericht = null;
$dbDatei = '';
$zipDatei = '';

$bekannteMigrationen = array_map(static fn($f) => basename($f, '.php'),
    glob(__DIR__ . '/migrations/*.php') ?: []);

/** Zaehlt in einer Dateien-Sicherung, was entpackt wuerde. */
function vsUebernahmeZipZaehlen(string $pfad): ?array {
    $zip = new ZipArchive();
    if ($zip->open($pfad) !== true) {
        return null;
    }
    $erg = ['bilder' => 0, 'belege' => 0, 'ignoriert' => 0];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = (string)$zip->getNameIndex($i);
        if (str_ends_with($name, '/')) {
            continue;                       // Ordnereintrag
        }
        $ziel = vsUebernahmeZipZiel($name);
        if ($ziel === null) {
            $erg['ignoriert']++;
        } elseif (str_starts_with($ziel, 'documents/')) {
            $erg['belege']++;
        } else {
            $erg['bilder']++;
        }
    }
    $zip->close();
    return $erg;
}

/** Speichert einen Upload in backups/ unter einem Namen, den restore.php auch kennt. */
function vsUebernahmeUpload(string $feld, string $art, array &$fehler): string {
    $f = $_FILES[$feld] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        $fehler[] = t('takeover_err_too_big', [ini_get('upload_max_filesize')]);
        return '';
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        $fehler[] = t('takeover_err_upload');
        return '';
    }
    $endung = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (($art === 'db' && $endung !== 'sql') || ($art === 'files' && $endung !== 'zip')) {
        $fehler[] = t($art === 'db' ? 'takeover_err_not_sql' : 'takeover_err_not_zip');
        return '';
    }
    $name = ($art === 'db' ? 'database_uebernahme_' : 'files_uebernahme_') . date('Y-m-d_H-i-s')
          . ($art === 'db' ? '.sql' : '.zip');
    if (!move_uploaded_file($f['tmp_name'], BACKUP_DIR . $name)) {
        $fehler[] = t('takeover_err_upload');
        return '';
    }
    return $name;
}

/** Zahlen der jetzigen Installation fuer die Vorschau. */
function vsUebernahmeJetzt(PDO $pdo): array {
    $z = [];
    foreach (['wertsachen', 'users'] as $t) {
        try {
            $z[$t] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        } catch (PDOException $e) {
            $z[$t] = 0;
        }
    }
    return $z;
}

// ── Formular zu gross fuer post_max_size: PHP liefert dann gar nichts ──────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $fehler[] = t('takeover_err_too_big', [ini_get('post_max_size')]);
}

// ── Schritt 1 -> 2: pruefen ────────────────────────────────────────────────
if (isset($_POST['takeover_check'])) {
    validateRequest();

    $dbDatei  = vsUebernahmeUpload('db_upload', 'db', $fehler) ?: basename((string)($_POST['db_datei'] ?? ''));
    $zipDatei = vsUebernahmeUpload('files_upload', 'files', $fehler) ?: basename((string)($_POST['files_datei'] ?? ''));

    if (!$fehler) {
        $dbPfad = $dbDatei !== '' ? vsUebernahmeBackupDatei(BACKUP_DIR, $dbDatei, 'db') : null;
        if ($dbPfad === null) {
            $fehler[] = t('takeover_err_no_db');
        } else {
            $analyse = vsUebernahmeAnalysieren((string)file_get_contents($dbPfad), $bekannteMigrationen);
        }
        if ($zipDatei !== '') {
            $zipPfad = vsUebernahmeBackupDatei(BACKUP_DIR, $zipDatei, 'files');
            $zipInfo = $zipPfad ? vsUebernahmeZipZaehlen($zipPfad) : null;
            if ($zipInfo === null) {
                $fehler[] = t('takeover_err_zip');
            }
        }
    }
    if (!$fehler && $analyse) {
        $schritt = 'vorschau';
    }
}

// ── Schritt 2 -> 3: uebernehmen ────────────────────────────────────────────
if (isset($_POST['takeover_run'])) {
    validateRequest();

    $dbDatei  = basename((string)($_POST['db_datei'] ?? ''));
    $zipDatei = basename((string)($_POST['files_datei'] ?? ''));
    $dbPfad   = vsUebernahmeBackupDatei(BACKUP_DIR, $dbDatei, 'db');
    $zipPfad  = $zipDatei !== '' ? vsUebernahmeBackupDatei(BACKUP_DIR, $zipDatei, 'files') : null;

    // Noch einmal pruefen - zwischen Vorschau und Klick kann die Datei
    // ausgetauscht worden sein
    $analyse = $dbPfad ? vsUebernahmeAnalysieren((string)file_get_contents($dbPfad), $bekannteMigrationen) : null;

    if (!$analyse || !$analyse['ok']) {
        $fehler[] = t('takeover_err_no_db');
    } elseif (empty($_POST['bestaetigt'])) {
        $fehler[] = t('takeover_err_confirm');
        $zipInfo = $zipPfad ? vsUebernahmeZipZaehlen($zipPfad) : null;
        $schritt = 'vorschau';
    } else {
        @set_time_limit(600);
        $bericht = ['anweisungen' => 0, 'fehler' => [], 'migrationen' => 0, 'migration_fehler' => '',
                    'dateien' => 0, 'dateien_fehler' => 0];

        // 1. Datenbank
        $pdo->exec('SET NAMES utf8mb4');
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($analyse['ausfuehren'] as $sql) {
            try {
                $pdo->exec($sql);
                $bericht['anweisungen']++;
            } catch (PDOException $e) {
                // MySQL 8 schreibt utf8mb4_0900_ai_ci, MariaDB kennt das nicht.
                // Beim Umzug zwischen beiden die naechstliegende Sortierung nehmen.
                if (($e->errorInfo[1] ?? 0) === 1273 && str_contains($sql, '_0900_')) {
                    try {
                        $pdo->exec(preg_replace('/utf8mb4_0900_\w+/', 'utf8mb4_unicode_ci', $sql));
                        $bericht['anweisungen']++;
                        continue;
                    } catch (PDOException $e2) {
                        $e = $e2;
                    }
                }
                $bericht['fehler'][] = mb_strimwidth($e->getMessage(), 0, 200, '…', 'UTF-8');
                error_log('Datenuebernahme SQL: ' . $e->getMessage() . ' -> ' . substr($sql, 0, 80));
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        // 2. Migrationen seit der alten Version - nur auf vollstaendigen Daten
        if (!$bericht['fehler']) {
            try {
                $lauf = vsMigrationenAusfuehren($pdo, __DIR__ . '/migrations', false);
                foreach ($lauf['report'] as $m) {
                    if ($m['status'] === 'applied') {
                        $bericht['migrationen']++;
                    } elseif ($m['status'] === 'error') {
                        $bericht['migration_fehler'] = $m['name'];
                    }
                }
            } catch (Throwable $e) {
                // z. B. schema_migrations nicht lesbar - Bericht statt weisser Seite
                $bericht['migration_fehler'] = 'schema_migrations';
                error_log('Datenuebernahme Migrationen: ' . $e->getMessage());
            }
        }

        // 3. Bilder und Belege
        if ($zipPfad && !$bericht['fehler']) {
            $zip = new ZipArchive();
            if ($zip->open($zipPfad) === true) {
                $basis = realpath(__DIR__ . '/..');
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $ziel = vsUebernahmeZipZiel((string)$zip->getNameIndex($i));
                    if ($ziel === null) {
                        continue;
                    }
                    $zielPfad = $basis . '/' . $ziel;
                    if (!is_dir(dirname($zielPfad)) && !mkdir(dirname($zielPfad), 0755, true)) {
                        $bericht['dateien_fehler']++;
                        continue;
                    }
                    $daten = $zip->getFromIndex($i);
                    if ($daten !== false && file_put_contents($zielPfad, $daten) !== false) {
                        $bericht['dateien']++;
                    } else {
                        $bericht['dateien_fehler']++;
                    }
                }
                $zip->close();
            } else {
                $bericht['dateien_fehler']++;
            }
        }

        Security::logSecurityEvent('data_takeover', [
            'db'          => $dbDatei,
            'files'       => $zipDatei,
            'version'     => $analyse['version'] ?? '',
            'items'       => $analyse['zeilen']['wertsachen'] ?? 0,
            'errors'      => count($bericht['fehler']),
            'migrations'  => $bericht['migrationen'],
            'file_count'  => $bericht['dateien'],
        ]);
        // Auch in die Tabelle, damit es unter Logs -> Security Logs steht
        // (die Klassenmethode oben schreibt nur logs/security.log). Ohne
        // user_id: das Konto der Sitzung gibt es nach der Uebernahme so nicht mehr.
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent($pdo, 'data_takeover', null, sprintf(
                'Datenübernahme aus %s%s: %d Gegenstände, %d Migrationen, %d Dateien, %d Fehler',
                $dbDatei, !empty($analyse['version']) ? ' (ValuSafe ' . $analyse['version'] . ')' : '',
                (int)($analyse['zeilen']['wertsachen'] ?? 0), $bericht['migrationen'], $bericht['dateien'],
                count($bericht['fehler'])
            ));
        }
        $schritt = 'fertig';
    }
}

// ── Auswahllisten ──────────────────────────────────────────────────────────
$sicherungen = ['db' => [], 'files' => []];
if ($schritt === 'auswahl' && is_dir(BACKUP_DIR)) {
    foreach (glob(BACKUP_DIR . '*') ?: [] as $f) {
        $n = basename($f);
        foreach (['db', 'files'] as $art) {
            if (vsUebernahmeBackupDatei(BACKUP_DIR, $n, $art)) {
                $sicherungen[$art][$n] = filemtime($f);
            }
        }
    }
    arsort($sicherungen['db']);
    arsort($sicherungen['files']);
}

$jetzt = $schritt === 'vorschau' ? vsUebernahmeJetzt($pdo) : [];

$pageTitle = t('takeover_title');
include 'layout/header_next_page.php';
?>

<style>
.ueb-box { background: var(--vs-surface); border: 1px solid var(--vs-border); border-radius: var(--vs-r-lg);
    padding: 24px; margin-bottom: 20px; max-width: 760px; }
.ueb-box h3 { margin: 0 0 12px; }
.ueb-box p, .ueb-box li { color: var(--vs-text); font-size: 14px; line-height: var(--vs-leading-base); }
.ueb-box .ueb-leise { color: var(--vs-text-muted); font-size: 13px; }
.ueb-box fieldset { border: 1px solid var(--vs-border); border-radius: var(--vs-r); padding: 14px 16px 16px; margin: 0 0 16px; }
.ueb-box legend { font-weight: 600; padding: 0 6px; color: var(--vs-text); }
.ueb-box label { display: block; font-size: 14px; color: var(--vs-text); margin: 8px 0 4px; }
.ueb-box select, .ueb-box input[type=file] { width: 100%; max-width: 100%; font-size: 14px; }
.ueb-box select { padding: 8px; border: 1px solid var(--vs-border-strong); border-radius: var(--vs-r-sm);
    background: var(--vs-surface); color: var(--vs-text); }
.ueb-schritte { list-style: decimal; padding-left: 22px; margin: 8px 0 0; }
.ueb-meldung { border-radius: var(--vs-r); padding: 14px 18px; margin-bottom: 16px; max-width: 760px; font-size: 14px; }
.ueb-fehler { background: var(--vs-danger-light); border: 1px solid var(--vs-danger); color: var(--vs-danger-text); }
.ueb-ok { background: var(--vs-success-light); border: 1px solid var(--vs-success); color: var(--vs-success-text); }
.ueb-warn { background: var(--vs-warning-light); border: 1px solid var(--vs-warning); color: var(--vs-warning-text); }
.ueb-meldung ul { margin: 6px 0 0; padding-left: 20px; }
.ueb-tabelle { border-collapse: collapse; width: 100%; margin: 8px 0 4px; font-size: 14px; }
.ueb-tabelle th, .ueb-tabelle td { text-align: left; padding: 6px 10px; border-bottom: 1px solid var(--vs-border); color: var(--vs-text); }
.ueb-tabelle td.zahl, .ueb-tabelle th.zahl { text-align: right; font-variant-numeric: tabular-nums; }
.ueb-check { display: flex; gap: 10px; align-items: flex-start; margin: 16px 0; }
.ueb-check input { margin-top: 3px; width: 18px; height: 18px; flex: none; }
.ueb-check label { margin: 0; }
.ueb-knoepfe { display: flex; gap: 12px; flex-wrap: wrap; }
code.ueb-datei { background: var(--vs-surface-2); padding: 2px 6px; border-radius: var(--vs-r-sm); word-break: break-all; }
</style>

<main class="backend-main">
<div style="display:flex; align-items:center; gap:12px; margin-bottom:24px; flex-wrap:wrap;">
    <a href="restore.php" class="vs-btn vs-btn-secondary">← <?php echo t('takeover_back'); ?></a>
    <h2 style="margin:0; border:none; padding:0;"><?php echo t('takeover_title'); ?></h2>
</div>

<?php if ($fehler): ?>
<div class="ueb-meldung ueb-fehler" role="alert">
    <ul><?php foreach ($fehler as $f): ?><li><?php echo esc($f); ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<?php if ($schritt === 'auswahl'): ?>

<div class="ueb-box">
    <p><?php echo t('takeover_intro'); ?></p>
    <ol class="ueb-schritte">
        <li><?php echo t('takeover_step_backup'); ?></li>
        <li><?php echo t('takeover_step_install'); ?></li>
        <li><?php echo t('takeover_step_here'); ?></li>
    </ol>
    <p class="ueb-leise"><?php echo t('takeover_min_version'); ?></p>
</div>

<form method="POST" enctype="multipart/form-data" class="ueb-box">
    <?php echo Security::getCSRFInput(); ?>
    <input type="hidden" name="takeover_check" value="1">

    <fieldset>
        <legend><?php echo t('takeover_db_legend'); ?></legend>
        <label for="ueb-db-datei"><?php echo t('takeover_choose_existing'); ?></label>
        <select id="ueb-db-datei" name="db_datei">
            <option value=""><?php echo t('takeover_none_selected'); ?></option>
            <?php foreach ($sicherungen['db'] as $n => $zeit): ?>
            <option value="<?php echo esc($n); ?>"><?php echo esc($n); ?> (<?php echo date('d.m.Y H:i', $zeit); ?>)</option>
            <?php endforeach; ?>
        </select>
        <label for="ueb-db-upload"><?php echo t('takeover_or_upload_sql'); ?></label>
        <input type="file" id="ueb-db-upload" name="db_upload" accept=".sql">
    </fieldset>

    <fieldset>
        <legend><?php echo t('takeover_files_legend'); ?></legend>
        <label for="ueb-files-datei"><?php echo t('takeover_choose_existing'); ?></label>
        <select id="ueb-files-datei" name="files_datei">
            <option value=""><?php echo t('takeover_files_none'); ?></option>
            <?php foreach ($sicherungen['files'] as $n => $zeit): ?>
            <option value="<?php echo esc($n); ?>"><?php echo esc($n); ?> (<?php echo date('d.m.Y H:i', $zeit); ?>)</option>
            <?php endforeach; ?>
        </select>
        <label for="ueb-files-upload"><?php echo t('takeover_or_upload_zip'); ?></label>
        <input type="file" id="ueb-files-upload" name="files_upload" accept=".zip">
    </fieldset>

    <p class="ueb-leise"><?php echo esc(t('takeover_upload_limit', [ini_get('upload_max_filesize')])); ?></p>

    <button type="submit" class="vs-btn vs-btn-primary"><?php echo t('takeover_btn_check'); ?></button>
</form>

<?php elseif ($schritt === 'vorschau' && $analyse): ?>

<?php if (!$analyse['ok']): ?>
<div class="ueb-meldung ueb-fehler" role="alert">
    <strong><?php echo t('takeover_rejected'); ?></strong>
    <ul>
        <?php foreach ($analyse['fehler'] as $code): ?>
        <li><?php echo esc(t('takeover_reason_' . $code)); ?></li>
        <?php endforeach; ?>
        <?php foreach ($analyse['zu_neu'] as $m): ?>
        <li><code class="ueb-datei"><?php echo esc($m); ?></code></li>
        <?php endforeach; ?>
        <?php foreach ($analyse['unzulaessig'] as $s): ?>
        <li><code class="ueb-datei"><?php echo esc($s); ?></code></li>
        <?php endforeach; ?>
    </ul>
</div>
<p><a href="datenuebernahme.php" class="vs-btn vs-btn-secondary"><?php echo t('takeover_btn_other'); ?></a></p>

<?php else: ?>

<div class="ueb-box">
    <h3><?php echo t('takeover_preview_title'); ?></h3>
    <p><?php echo t('takeover_file'); ?> <code class="ueb-datei"><?php echo esc($dbDatei); ?></code>
       <?php if ($analyse['version']): ?> · <?php echo esc(t('takeover_from_version', [$analyse['version']])); ?><?php endif; ?></p>

    <table class="ueb-tabelle">
        <caption class="ueb-leise" style="text-align:left;"><?php echo t('takeover_table_caption'); ?></caption>
        <thead><tr><th scope="col"><?php echo t('takeover_col_what'); ?></th><th scope="col" class="zahl"><?php echo t('takeover_col_count'); ?></th></tr></thead>
        <tbody>
        <?php
        $zeilenNamen = [
            'wertsachen' => 'takeover_row_items', 'item_images' => 'takeover_row_images',
            'dokumente' => 'takeover_row_documents', 'raeume' => 'takeover_row_rooms',
            'kategorien' => 'takeover_row_categories', 'versicherungen' => 'takeover_row_insurances',
            'schadensfaelle' => 'takeover_row_claims', 'users' => 'takeover_row_users',
        ];
        foreach ($zeilenNamen as $tab => $key): ?>
            <tr><th scope="row" style="font-weight:normal;"><?php echo t($key); ?></th>
                <td class="zahl"><?php echo (int)($analyse['zeilen'][$tab] ?? 0); ?></td></tr>
        <?php endforeach; ?>
        <?php if ($zipInfo): ?>
            <tr><th scope="row" style="font-weight:normal;"><?php echo t('takeover_row_files'); ?></th>
                <td class="zahl"><?php echo $zipInfo['bilder'] + $zipInfo['belege']; ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php if ($zipInfo && $zipInfo['ignoriert']): ?>
    <p class="ueb-leise"><?php echo esc(t('takeover_files_ignored', [$zipInfo['ignoriert']])); ?></p>
    <?php endif; ?>
    <?php if (!$zipInfo): ?>
    <p class="ueb-leise"><?php echo t('takeover_no_files_hint'); ?></p>
    <?php endif; ?>
    <?php if ($analyse['fremd']): ?>
    <p class="ueb-leise"><?php echo esc(t('takeover_foreign_tables', [implode(', ', $analyse['fremd'])])); ?></p>
    <?php endif; ?>
</div>

<div class="ueb-meldung ueb-warn">
    <strong><?php echo t('takeover_warn_title'); ?></strong>
    <ul>
        <li><?php echo t('takeover_warn_replace'); ?></li>
        <li><?php echo t('takeover_warn_login'); ?></li>
        <?php if (($jetzt['wertsachen'] ?? 0) > 0): ?>
        <li><strong><?php echo esc(t('takeover_warn_existing', [$jetzt['wertsachen']])); ?></strong></li>
        <?php endif; ?>
    </ul>
</div>

<form method="POST" class="ueb-box">
    <?php echo Security::getCSRFInput(); ?>
    <input type="hidden" name="takeover_run" value="1">
    <input type="hidden" name="db_datei" value="<?php echo esc($dbDatei); ?>">
    <input type="hidden" name="files_datei" value="<?php echo esc($zipInfo ? $zipDatei : ''); ?>">
    <div class="ueb-check">
        <input type="checkbox" id="ueb-bestaetigt" name="bestaetigt" value="1" required>
        <label for="ueb-bestaetigt"><?php echo t('takeover_confirm_label'); ?></label>
    </div>
    <div class="ueb-knoepfe">
        <button type="submit" class="vs-btn vs-btn-primary"><?php echo t('takeover_btn_run'); ?></button>
        <a href="datenuebernahme.php" class="vs-btn vs-btn-secondary"><?php echo t('takeover_btn_cancel'); ?></a>
    </div>
</form>

<?php endif; ?>

<?php elseif ($schritt === 'fertig' && $bericht): ?>

<?php if ($bericht['fehler'] || $bericht['migration_fehler']): ?>
<div class="ueb-meldung ueb-fehler" role="alert">
    <strong><?php echo t('takeover_done_errors'); ?></strong>
    <ul>
        <?php foreach (array_slice($bericht['fehler'], 0, 5) as $f): ?><li><?php echo esc($f); ?></li><?php endforeach; ?>
        <?php if ($bericht['migration_fehler']): ?>
        <li><?php echo esc(t('takeover_done_migration_error', [$bericht['migration_fehler']])); ?></li>
        <?php endif; ?>
    </ul>
    <p style="margin:8px 0 0;"><?php echo t('takeover_done_errors_hint'); ?></p>
</div>
<?php else: ?>
<div class="ueb-meldung ueb-ok" role="status">
    <strong><?php echo t('takeover_done_ok'); ?></strong>
    <ul>
        <li><?php echo esc(t('takeover_done_items', [(int)($analyse['zeilen']['wertsachen'] ?? 0)])); ?></li>
        <li><?php echo esc(t('takeover_done_migrations', [$bericht['migrationen']])); ?></li>
        <?php if ($zipDatei !== ''): ?>
        <li><?php echo esc(t('takeover_done_files', [$bericht['dateien']])); ?><?php if ($bericht['dateien_fehler']): ?> · <?php echo esc(t('takeover_done_files_failed', [$bericht['dateien_fehler']])); ?><?php endif; ?></li>
        <?php endif; ?>
    </ul>
</div>
<?php endif; ?>

<div class="ueb-box">
    <p><?php echo t('takeover_done_logout'); ?></p>
    <a href="../login.php" class="vs-btn vs-btn-primary"><?php echo t('takeover_btn_login'); ?></a>
</div>

<?php endif; ?>

</main>

<?php
include 'layout/footer_next_page.php';

// Die Benutzer sind jetzt die der alten Version - die Sitzung gehoert zu
// einem Konto, das es so nicht mehr gibt. Erst nach der Ausgabe beenden,
// damit Kopf und Fuss der Seite noch normal erscheinen.
if ($schritt === 'fertig') {
    $_SESSION = [];
    session_destroy();
}
