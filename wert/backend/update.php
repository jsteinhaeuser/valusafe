<?php
/**
 * Update einspielen - ValuSafe
 * backend/update.php (seit 4.7, TODO-Liste Abschnitt U)
 *
 * Ablauf:
 * 1. Versionsabfrage bei palindrom.de (update_helpers.php, hoechstens einmal
 *    am Tag, abschaltbar). Gibt es eine neuere Version, holt "Herunterladen
 *    und pruefen" das signierte Update-Paket - oder der Admin laedt es selbst
 *    hoch, wenn der Server nicht nach aussen darf.
 * 2. Pruefen (update_kern.php): Signatur gegen update_schluessel.php,
 *    Programm, Version neuer als die installierte, jede Pruefsumme. Noch wird
 *    nichts veraendert.
 * 3. Bestaetigen: Sicherung der Datenbank (backups/database_*.sql, im
 *    Restore waehlbar), Auspacken, Wartungsschalter (db.php: jede andere
 *    Seite 503), Austausch mit Sicherung der alten Dateien, Selbsttest und
 *    Migrationen. Schlaegt etwas fehl, rollt der Kern die Dateien zurueck.
 *    Schon gelaufene Migrationen bleiben - sie fuegen nur hinzu, und die
 *    Datenbanksicherung von vorher liegt bereit.
 * 4. Brach ein Lauf hart ab, meldet die Seite das und holt auf Knopfdruck
 *    den alten Stand.
 *
 * Kein Knopf auf Instanzen der eigenen Flotte (FILE_SYNC_TOKEN, Hub-Sync)
 * und im Docker-Image - dort zeigt die Seite nur, was es Neues gibt.
 *
 * Arbeitsordner: backups/update/ mit stand.json (letzte Abfrage),
 * eingang_*.zip (geprueftes Paket bis zur Bestaetigung) und je Lauf
 * <version>_<zeit>/ mit neu/ (wird danach geloescht) und sicherung/ (alte
 * Dateien + journal.json; die letzten beiden Laeufe bleiben liegen).
 *
 * Sicherheit: nur SuperAdmin (bzw. Admin ohne SuperAdmin), CSRF bei jedem
 * POST, Pfade nur aus dem eigenen Arbeitsordner.
 */

require_once __DIR__ . '/config.php';
requireBackendAccess();
if (!darfSuperAdminAktionen()) {
    header('Location: index.php');
    exit;
}

if (!function_exists('esc')) {
    require_once __DIR__ . '/../helpers.php';
}
require_once dirname(__DIR__) . '/update_helpers.php';
require_once dirname(__DIR__) . '/helpers_sicherung.php';
require_once __DIR__ . '/migration_runner.php';

$wurzel      = dirname(__DIR__);
$arbeit      = rtrim((string)BACKUP_DIR, '/') . '/update/';
$schluessel  = require $wurzel . '/update_schluessel.php';
$installiert = vsUpdInstalliert($wurzel);
$sperre      = vsUpdKnopfSperre();

$message  = '';
$error    = '';
$step     = 'select';   // select | confirm | done
$manifest = null;

/** Eingangs-ZIP aus der Sitzung, nur aus dem Arbeitsordner. */
function vsUpdEingang(string $arbeit): ?string {
    $name = (string)($_SESSION['vs_update_eingang'] ?? '');
    if (!preg_match('/^eingang_[0-9_-]+\.zip$/', $name)) return null;
    return is_file($arbeit . $name) ? $arbeit . $name : null;
}

/** Neuer Name fuer ein Eingangs-ZIP; ein frueheres, nicht eingespieltes wird ersetzt. */
function vsUpdEingangNeu(string $arbeit): string {
    if (!is_dir($arbeit) && !mkdir($arbeit, 0755, true)) throw new VsUpdFehler('Arbeitsordner backups/update laesst sich nicht anlegen.');
    foreach (glob($arbeit . 'eingang_*.zip') ?: [] as $alt) @unlink($alt);
    return 'eingang_' . date('Y-m-d_H-i-s') . '.zip';
}

/** Laeufe, deren Journal nicht "fertig" oder "zurueckgerollt" sagt. */
function vsUpdOffeneLaeufe(string $arbeit): array {
    $offen = [];
    foreach (glob($arbeit . '*/sicherung/journal.json') ?: [] as $j) {
        $z = vsUpdJournalZustand(dirname($j));
        if (!in_array($z, ['fertig', 'zurueckgerollt'], true)) $offen[] = basename(dirname($j, 2));
    }
    return $offen;
}

// ── Abgebrochenen Lauf zurueckholen ─────────────────────────────────────────

if (isset($_POST['rollback'])) {
    validateRequest();
    $lauf = (string)($_POST['lauf'] ?? '');
    if (!preg_match('/^[0-9.]+_[0-9_-]+$/', $lauf) || !in_array($lauf, vsUpdOffeneLaeufe($arbeit), true)) {
        $error = esc(t('upd_err_unknown_run'));
    } else {
        try {
            vsUpdZurueckrollen($wurzel, $arbeit . $lauf . '/sicherung');
            vsUpdWartungAus();
            Security::logSecurityEvent('app_update_rollback', ['lauf' => $lauf]);
            $message = esc(t('upd_msg_rolled_back'));
            $step = 'done';
            $installiert = vsUpdInstalliert($wurzel);
        } catch (Throwable $e) {
            $error = esc(t('upd_err_rollback')) . ' ' . esc($e->getMessage());
        }
    }
}

// ── Versionsabfrage von Hand ────────────────────────────────────────────────

if (isset($_POST['jetzt_pruefen'])) {
    validateRequest();
    vsUpdStand($arbeit, true);
}

// ── Schritt 1: holen oder hochladen, dann pruefen ───────────────────────────

if ($sperre === null && (isset($_POST['holen']) || isset($_POST['upload_update']))) {
    validateRequest();
    $name = null;
    try {
        $name = vsUpdEingangNeu($arbeit);
        if (isset($_POST['holen'])) {
            $stand = vsUpdStand($arbeit, false, true);
            if (!vsUpdNeuer($stand, $installiert)) throw new VsUpdFehler(t('upd_err_nothing_new'));
            @set_time_limit(180);
            vsUpdPaketHolen($stand['info'], $arbeit . $name);
        } else {
            $f = $_FILES['update_zip'] ?? null;
            if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
                $code = $f['error'] ?? UPLOAD_ERR_NO_FILE;
                throw new VsUpdFehler(in_array($code, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                    ? sprintf(t('upd_err_too_large'), ini_get('upload_max_filesize'))
                    : t('upd_err_no_file'));
            }
            if (!move_uploaded_file($f['tmp_name'], $arbeit . $name)) throw new VsUpdFehler(t('upd_err_store'));
        }
        $manifest = vsUpdPaketPruefen($arbeit . $name, $schluessel, 'valusafe', $installiert, VS_UPD_GESCHUETZT);
        $_SESSION['vs_update_eingang'] = $name;
        $step = 'confirm';
    } catch (VsUpdFehler $e) {
        if ($name !== null) @unlink($arbeit . $name);
        $error = esc(t('upd_err_rejected')) . ' ' . esc($e->getMessage());
    }
}

// ── Schritt 2: einspielen ───────────────────────────────────────────────────

if ($sperre === null && isset($_POST['do_update'])) {
    validateRequest();
    $zip = vsUpdEingang($arbeit);
    if ($zip === null) {
        $error = esc(t('upd_err_no_checked'));
    } else {
        @set_time_limit(300);
        ignore_user_abort(true);   // Fenster zu heisst nicht: halb getauscht stehen lassen
        $lauf = null;
        $wartung = false;
        try {
            // Noch einmal pruefen: zwischen den Schritten lag die Datei auf der Platte.
            $manifest = vsUpdPaketPruefen($zip, $schluessel, 'valusafe', $installiert, VS_UPD_GESCHUETZT);
            $lauf = $manifest['version'] . '_' . date('Y-m-d_H-i-s');

            vsUpdEntpacken($zip, $manifest, $arbeit . $lauf . '/neu');

            // Datenbank sichern - die Migrationen der neuen Version aendern sie.
            $kopie = 'database_' . date('Y-m-d_H-i-s') . '_vor_update_' . $installiert . '.sql';
            try {
                vsDatenbankSichern($pdo, BACKUP_DIR . $kopie);
            } catch (Throwable $e) {
                throw new VsUpdFehler(t('upd_err_db_backup'));
            }

            vsUpdWartungAn($manifest['version']);
            $wartung = true;
            $migriert = 0;
            vsUpdEinspielen($arbeit . $lauf . '/neu', $manifest, $wurzel, $arbeit . $lauf . '/sicherung',
                function () use ($wurzel, $manifest, $pdo, &$migriert) {
                    vsUpdSelbsttest($wurzel, $manifest);
                    $m = vsMigrationenAusfuehren($pdo, $wurzel . '/backend/migrations', false);
                    if ($m['stoppedEarly']) {
                        $fehl = array_values(array_filter($m['report'], fn($r) => $r['status'] === 'error'));
                        throw new VsUpdFehler('Migration ' . ($fehl[0]['name'] ?? '?') . ' fehlgeschlagen.');
                    }
                    $migriert = $m['counts']['applied'] ?? 0;
                    return true;
                });

            Security::logSecurityEvent('app_updated', [
                'von' => $installiert, 'auf' => $manifest['version'],
                'db_sicherung' => $kopie, 'migrationen' => $migriert,
            ]);
            $message = sprintf(esc(t('upd_msg_done')), '<strong>' . esc($manifest['version']) . '</strong>')
                     . '<br>' . sprintf(esc(t('upd_msg_db_copy')), '<strong>' . esc($kopie) . '</strong>');
            $step = 'done';
            $installiert = vsUpdInstalliert($wurzel);
        } catch (Throwable $e) {
            $error = esc(t('upd_err_not_applied')) . ' ' . esc($e->getMessage())
                   . '<br>' . esc(t('upd_err_old_running'));
        } finally {
            if ($wartung) vsUpdWartungAus();
            @unlink($zip);
            unset($_SESSION['vs_update_eingang']);
            if ($lauf !== null) {
                vsUpdOrdnerLoeschen($arbeit . $lauf . '/neu');
                @rmdir($arbeit . $lauf);   // nur leer: ohne Sicherung gab es nichts zurueckzuholen
            }
        }

        // Aeltere Laeufe aufraeumen: die letzten zwei bleiben fuer den Notfall.
        $laeufe = glob($arbeit . '*', GLOB_ONLYDIR) ?: [];
        usort($laeufe, fn($a, $b) => filemtime($a) <=> filemtime($b));
        foreach (array_slice($laeufe, 0, max(0, count($laeufe) - 2)) as $alt) {
            if (!in_array(basename($alt), vsUpdOffeneLaeufe($arbeit), true)) vsUpdOrdnerLoeschen($alt);
        }
    }
}

if (isset($_POST['abbrechen'])) {
    validateRequest();
    if ($z = vsUpdEingang($arbeit)) @unlink($z);
    unset($_SESSION['vs_update_eingang']);
}

$offeneLaeufe = vsUpdOffeneLaeufe($arbeit);
$technikFehlt = !extension_loaded('sodium') ? 'sodium' : (!class_exists('ZipArchive') ? 'zip' : '');
$stand        = vsUpdStand($arbeit);   // fragt nur, wenn der letzte Stand alt ist
$neuer        = vsUpdNeuer($stand, $installiert);
$info         = $stand['info'] ?? null;

$pageTitle = t('upd_title');
include 'layout/header_next_page.php';
?>

<style>
.update-card {
    background: var(--vs-surface); border: 1px solid var(--vs-border); border-radius: 16px;
    padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); margin-bottom: 20px; max-width: 760px;
}
.update-card h2 { margin: 0 0 8px; font-size: var(--vs-text-lg, 18px); border: none; padding: 0; }
.update-card p  { font-size: 14px; color: var(--vs-text-muted); }
.update-card.update-neu { border: 2px solid var(--vs-accent); }
.update-hinweis {
    background: var(--vs-warning-light); border: 2px solid var(--vs-warning); border-radius: 12px;
    padding: 16px 20px; margin-bottom: 20px; color: var(--vs-warning-text); font-size: 14px; max-width: 760px;
}
.update-ok {
    background: var(--vs-success-light); border: 1px solid var(--vs-success); border-radius: 12px;
    padding: 16px 20px; margin-bottom: 20px; color: var(--vs-success-text); font-size: 15px; max-width: 760px;
}
.update-fehler {
    background: var(--vs-danger-light, #fee2e2); border: 1px solid var(--vs-danger); border-radius: 12px;
    padding: 16px 20px; margin-bottom: 20px; color: var(--vs-danger); font-size: 15px; max-width: 760px;
}
.update-version { font-size: 15px; }
.update-version strong { font-family: monospace; font-size: 16px; }
.update-knoepfe { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
</style>

<main class="backend-main">
<div style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
    <a href="index.php" class="vs-btn vs-btn-secondary">← Admin</a>
    <h1 style="margin:0; font-size:var(--vs-text-xl, 22px);"><i class="ti ti-download" aria-hidden="true"></i> <?php echo esc(t('upd_title')); ?></h1>
</div>

<?php if ($message): ?>
<div class="update-ok" role="status">
    <i class="ti ti-circle-check" aria-hidden="true"></i> <?php echo $message; ?>
    <div style="margin-top:12px;">
        <a href="index.php" class="vs-btn vs-btn-primary"><?php echo esc(t('upd_btn_to_admin')); ?></a>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="update-fehler" role="alert">
    <i class="ti ti-circle-x" aria-hidden="true"></i> <?php echo $error; ?>
</div>
<?php endif; ?>

<?php foreach ($offeneLaeufe as $lauf): ?>
<div class="update-hinweis" role="alert">
    <strong><i class="ti ti-alert-triangle" aria-hidden="true"></i> <?php echo esc(t('upd_aborted_title')); ?></strong>
    (<?php echo esc($lauf); ?>). <?php echo esc(t('upd_aborted_text')); ?>
    <form method="POST" style="margin-top:12px;">
        <?php echo Security::getCSRFInput(); ?>
        <input type="hidden" name="rollback" value="1">
        <input type="hidden" name="lauf" value="<?php echo esc($lauf); ?>">
        <button type="submit" class="vs-btn vs-btn-primary"><?php echo esc(t('upd_btn_restore_previous')); ?></button>
    </form>
</div>
<?php endforeach; ?>

<?php if ($step === 'confirm' && $manifest): ?>

<div class="update-card update-neu">
    <h2><?php echo esc(t('upd_confirm_title')); ?></h2>
    <p class="update-version">
        <?php echo esc(t('upd_installed')); ?> <strong><?php echo esc($installiert); ?></strong>
        &nbsp;→&nbsp; <?php echo esc(t('upd_new')); ?> <strong><?php echo esc($manifest['version']); ?></strong>
    </p>
    <p><i class="ti ti-shield-check" style="color:var(--vs-success);" aria-hidden="true"></i>
       <?php echo esc(sprintf(t('upd_signed_complete'), count($manifest['dateien']))); ?>
       <?php if ($manifest['entfallen']) echo esc(count($manifest['entfallen']) === 1 ? t('upd_files_removed_one') : sprintf(t('upd_files_removed'), count($manifest['entfallen']))); ?></p>
    <p><?php echo esc(t('upd_confirm_text')); ?></p>
    <?php if (!empty($info['changelog']) && ($info['version'] ?? '') === $manifest['version']): ?>
    <p><a href="<?php echo esc($info['changelog']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc(t('upd_whats_new')); ?></a></p>
    <?php endif; ?>
    <div class="update-knoepfe">
        <form method="POST">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="do_update" value="1">
            <button type="submit" class="vs-btn vs-btn-primary" style="padding:10px 24px; font-weight:600;"
                    data-laeuft="<?php echo esc(t('upd_btn_running')); ?>"
                    onclick="this.disabled=true; this.textContent=this.dataset.laeuft; this.form.submit();">
                <?php echo esc(t('upd_btn_apply')); ?>
            </button>
        </form>
        <form method="POST">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="abbrechen" value="1">
            <button type="submit" class="vs-btn vs-btn-secondary" style="padding:10px 24px;"><?php echo esc(t('btn_cancel')); ?></button>
        </form>
    </div>
</div>

<?php elseif ($step === 'select'): ?>

<div class="update-card<?php echo $neuer ? ' update-neu' : ''; ?>">
    <p class="update-version"><?php echo esc(t('upd_installed_version')); ?> <strong><?php echo esc($installiert); ?></strong></p>
    <?php if ($stand === null): ?>
        <p><?php echo esc(t('upd_check_off')); ?></p>
    <?php elseif ($neuer): ?>
        <p class="update-version" role="status"><i class="ti ti-sparkles" aria-hidden="true"></i>
           <?php echo esc(t('upd_available')); ?> <strong><?php echo esc($info['version']); ?></strong>
           <?php if (!empty($info['datum'])) echo '(' . esc(date('d.m.Y', strtotime($info['datum']))) . ')'; ?></p>
        <?php if (!empty($info['changelog'])): ?>
        <p><a href="<?php echo esc($info['changelog']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc(t('upd_whats_new')); ?></a></p>
        <?php endif; ?>
    <?php else: ?>
        <p><i class="ti ti-circle-check" style="color:var(--vs-success);" aria-hidden="true"></i> <?php echo esc(t('upd_up_to_date')); ?></p>
    <?php endif; ?>
    <?php if ($stand !== null): ?>
    <p style="font-size:13px;">
        <?php echo esc(sprintf(t('upd_last_check'), date('d.m.Y H:i', $stand['geprueft']))); ?>
        <?php if (!empty($stand['fehler'])): ?><br><span style="color:var(--vs-danger);"><?php echo esc($stand['fehler']); ?></span><?php endif; ?>
    </p>
    <div class="update-knoepfe">
        <?php if ($neuer && $sperre === null && !$technikFehlt && !empty($info['paket'])): ?>
        <form method="POST">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="holen" value="1">
            <button type="submit" class="vs-btn vs-btn-primary" style="padding:10px 24px; font-weight:600;"
                    data-laeuft="<?php echo esc(t('upd_btn_fetching')); ?>"
                    onclick="this.disabled=true; this.textContent=this.dataset.laeuft; this.form.submit();">
                <?php echo esc(t('upd_btn_fetch')); ?>
            </button>
        </form>
        <?php endif; ?>
        <form method="POST">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="jetzt_pruefen" value="1">
            <button type="submit" class="vs-btn vs-btn-secondary"><?php echo esc(t('upd_btn_check_now')); ?></button>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php if ($sperre !== null): ?>
<div class="update-card">
    <p><i class="ti ti-info-circle" aria-hidden="true"></i> <?php echo esc(t('upd_locked_' . $sperre)); ?></p>
</div>
<?php elseif ($technikFehlt): ?>
<div class="update-hinweis" role="alert">
    <?php echo esc(sprintf(t('upd_missing_ext'), $technikFehlt)); ?>
</div>
<?php else: ?>
<div class="update-card">
    <h2 id="update-upload-titel"><?php echo esc(t('upd_upload_title')); ?></h2>
    <p><?php echo esc(t('upd_upload_text')); ?></p>
    <form method="POST" enctype="multipart/form-data" class="update-knoepfe">
        <?php echo Security::getCSRFInput(); ?>
        <input type="hidden" name="upload_update" value="1">
        <input type="file" name="update_zip" accept=".zip" required aria-labelledby="update-upload-titel">
        <button type="submit" class="vs-btn vs-btn-secondary"><?php echo esc(t('upd_btn_check')); ?> →</button>
    </form>
</div>
<?php endif; ?>

<?php endif; ?>
</main>

<?php include 'layout/footer_next_page.php'; ?>
