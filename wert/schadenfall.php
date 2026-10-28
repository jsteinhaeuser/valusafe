<?php
/**
 * schadenfall.php — Schadensfall-Assistent (seit 4.4)
 *
 * Nach einem Einbruch, Brand oder Wasserschaden: Fall anlegen (Schritt 1),
 * betroffene Gegenstaende auswaehlen (Schritt 2), Zusammenfassung mit Summen
 * und den beiden Dokumenten (Schritt 3, schadenfall_dokument.php):
 * Liste fuer die Polizei und Schadensmeldung fuer die Versicherung.
 *
 * Tabellen aus Migration 014. Rechte: claims_view (ansehen, Dokumente),
 * claims_manage (anlegen, aendern, loeschen). Bei "nur eigene Gegenstaende"
 * sieht ein Benutzer nur seine eigenen Faelle und waehlt nur eigene
 * Gegenstaende aus.
 */
require_once __DIR__ . '/db.php';
if (!function_exists('correctImageOrientation')) require_once __DIR__ . '/helpers.php';
requireLogin();
requirePermission('claims_view');

define('PAGE_TITLE', t('claims_title') . ' - ' . t('app_title'));

$darfVerwalten = hasPermission('claims_manage');
$nurEigene     = userSeesOnlyOwnItems();
$ich           = $_SESSION['username'] ?? '';
$migrationDa   = vsMigration014Da();

const VS_SCHADEN_ARTEN    = ['einbruch', 'brand', 'wasser', 'sturm', 'sonstiges'];
const VS_SCHADEN_ZUSTAENDE = ['gestohlen', 'zerstoert', 'beschaedigt'];
const VS_SCHADEN_STATUS   = ['offen', 'gemeldet', 'erledigt'];

$fehler  = '';
$meldung = '';

/** Fall laden, wenn der Benutzer ihn sehen darf. */
function vsFallLaden($db, int $id, bool $nurEigene, string $ich): ?array {
    if ($id <= 0) return null;
    $f = $db->selectOne("SELECT * FROM schadensfaelle WHERE id = ?", [$id]);
    if (!$f) return null;
    if ($nurEigene && strcasecmp(rtrim((string)$f['erstellt_von']), rtrim($ich)) !== 0) return null;
    return $f;
}

// ── POST ────────────────────────────────────────────────────────────────────
if ($migrationDa && $_SERVER['REQUEST_METHOD'] === 'POST') {
    validateRequest();
    $aktion = $_POST['aktion'] ?? '';

    if (!$darfVerwalten) {
        $fehler = t('claims_no_permission');

    } elseif ($aktion === 'fall_speichern') {
        $id   = (int)($_POST['id'] ?? 0);
        $art  = in_array($_POST['art'] ?? '', VS_SCHADEN_ARTEN, true) ? $_POST['art'] : 'sonstiges';
        $datum = trim($_POST['datum'] ?? '');
        $datum = preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum) ? $datum : null;
        $akz  = mb_substr(trim($_POST['aktenzeichen'] ?? ''), 0, 100) ?: null;
        $vid  = (int)($_POST['versicherung_id'] ?? 0) ?: null;
        $besch = mb_substr(trim($_POST['beschreibung'] ?? ''), 0, 5000) ?: null;
        try {
            if ($id > 0) {
                if (!vsFallLaden($db, $id, $nurEigene, $ich)) throw new RuntimeException('fall');
                $db->execute("UPDATE schadensfaelle SET art = ?, datum = ?, aktenzeichen = ?, versicherung_id = ?,
                              beschreibung = ?, geaendert_am = NOW() WHERE id = ?",
                             [$art, $datum, $akz, $vid, $besch, $id]);
            } else {
                $id = (int)$db->insert("INSERT INTO schadensfaelle (art, datum, aktenzeichen, versicherung_id, beschreibung, status, erstellt_von)
                                        VALUES (?, ?, ?, ?, ?, 'offen', ?)",
                                       [$art, $datum, $akz, $vid, $besch, $ich]);
                if (function_exists('logActivity')) logActivity('created', 'schadensfaelle', $id, t('claims_art_' . $art));
            }
            header('Location: schadenfall.php?id=' . $id . '&schritt=2');
            exit;
        } catch (Throwable $e) {
            error_log('schadenfall fall_speichern: ' . $e->getMessage());
            $fehler = t('claims_save_failed');
        }

    } elseif ($aktion === 'gegenstaende_speichern') {
        $id = (int)($_POST['id'] ?? 0);
        $fall = vsFallLaden($db, $id, $nurEigene, $ich);
        if (!$fall) {
            $fehler = t('claims_not_found');
        } else {
            $auswahl = array_map('intval', (array)($_POST['gegenstand'] ?? []));
            $zustand = (array)($_POST['zustand'] ?? []);
            try {
                $db->beginTransaction();
                $db->execute("DELETE FROM schadensfall_gegenstaende WHERE fall_id = ?", [$id]);
                foreach (array_unique($auswahl) as $wid) {
                    $item = $db->selectOne("SELECT id, erstellt_von FROM wertsachen WHERE id = ?", [$wid]);
                    if (!$item || !darfGegenstand($item)) continue;
                    $z = in_array($zustand[$wid] ?? '', VS_SCHADEN_ZUSTAENDE, true) ? $zustand[$wid] : 'gestohlen';
                    $db->execute("INSERT INTO schadensfall_gegenstaende (fall_id, wertsache_id, zustand) VALUES (?, ?, ?)",
                                 [$id, $wid, $z]);
                }
                $db->execute("UPDATE schadensfaelle SET geaendert_am = NOW() WHERE id = ?", [$id]);
                $db->commit();
                header('Location: schadenfall.php?id=' . $id . '&schritt=3');
                exit;
            } catch (Throwable $e) {
                if (method_exists($db, 'rollBack')) { try { $db->rollBack(); } catch (Throwable $e2) {} }
                error_log('schadenfall gegenstaende_speichern: ' . $e->getMessage());
                $fehler = t('claims_save_failed');
            }
        }

    } elseif ($aktion === 'status') {
        $id = (int)($_POST['id'] ?? 0);
        $st = in_array($_POST['status'] ?? '', VS_SCHADEN_STATUS, true) ? $_POST['status'] : 'offen';
        if (vsFallLaden($db, $id, $nurEigene, $ich)) {
            $db->execute("UPDATE schadensfaelle SET status = ?, geaendert_am = NOW() WHERE id = ?", [$st, $id]);
            header('Location: schadenfall.php?id=' . $id . '&schritt=3&gespeichert=1');
            exit;
        }
        $fehler = t('claims_not_found');

    } elseif ($aktion === 'loeschen') {
        $id = (int)($_POST['id'] ?? 0);
        if (vsFallLaden($db, $id, $nurEigene, $ich)) {
            $db->execute("DELETE FROM schadensfall_gegenstaende WHERE fall_id = ?", [$id]);
            $db->execute("DELETE FROM schadensfaelle WHERE id = ?", [$id]);
            if (function_exists('logActivity')) logActivity('deleted', 'schadensfaelle', $id, '#' . $id);
            header('Location: schadenfall.php?geloescht=1');
            exit;
        }
        $fehler = t('claims_not_found');
    }
}

// ── Anzeige vorbereiten ─────────────────────────────────────────────────────
$id      = (int)($_GET['id'] ?? 0);
$schritt = (int)($_GET['schritt'] ?? 0);
// Rueckmeldung von schadenfall_belege.php (ZIP)
$zipFehler = ['empty' => 'claims_zip_empty', 'too_large' => 'claims_zip_too_large', 'failed' => 'claims_zip_failed'];
if (isset($_GET['zipfehler'], $zipFehler[$_GET['zipfehler']])) {
    $fehler = $_GET['zipfehler'] === 'too_large' ? sprintf(t('claims_zip_too_large'), 200) : t($zipFehler[$_GET['zipfehler']]);
}
$neu     = isset($_GET['neu']);
$fall    = null;
$faelle  = [];
$versicherungen = [];

if ($migrationDa) {
    $versicherungen = $db->select("SELECT id, name, anbieter, vertragsnummer, versicherungssumme, selbstbeteiligung FROM versicherungen ORDER BY name");
    if ($id) {
        $fall = vsFallLaden($db, $id, $nurEigene, $ich);
        if (!$fall) { $fehler = $fehler ?: t('claims_not_found'); $id = 0; $schritt = 0; }
    }
    if ($neu && $darfVerwalten) { $schritt = 1; }
    if ($fall && !in_array($schritt, [1, 2, 3], true)) $schritt = 3;
    if (!$darfVerwalten && in_array($schritt, [1, 2], true)) $schritt = $fall ? 3 : 0;

    if ($schritt === 0) {
        $sql = "SELECT f.*, v.name AS versicherung_name,
                       (SELECT COUNT(*) FROM schadensfall_gegenstaende g WHERE g.fall_id = f.id) AS anzahl
                FROM schadensfaelle f LEFT JOIN versicherungen v ON v.id = f.versicherung_id";
        $par = [];
        if ($nurEigene) { $sql .= " WHERE f.erstellt_von = ?"; $par[] = $ich; }
        $sql .= " ORDER BY f.datum DESC, f.id DESC";
        $faelle = $db->select($sql, $par);
    }
}

// Gegenstaende fuer Schritt 2 / Zusammenfassung fuer Schritt 3
$gegenstaende = [];
$imFall = [];
$raumFilter = (int)($_GET['raum'] ?? 0);
$raeume = [];
if ($fall && in_array($schritt, [2, 3], true)) {
    foreach ($db->select("SELECT wertsache_id, zustand FROM schadensfall_gegenstaende WHERE fall_id = ?", [$fall['id']]) as $r) {
        $imFall[(int)$r['wertsache_id']] = $r['zustand'];
    }
}
if ($fall && $schritt === 2) {
    $raeume = $db->select("SELECT id, name FROM raeume ORDER BY name");
    [$ownSql, $ownPar] = nurEigeneSql('w');
    $sql = "SELECT w.id, w.name, w.bild, w.preis, w.aktueller_wert, r.name AS raum_name
            FROM wertsachen w LEFT JOIN raeume r ON r.id = w.raum_id WHERE 1=1" . $ownSql;
    $par = $ownPar;
    if ($raumFilter) { $sql .= " AND (w.raum_id = ? OR w.id IN (SELECT wertsache_id FROM schadensfall_gegenstaende WHERE fall_id = ?))"; $par[] = $raumFilter; $par[] = $fall['id']; }
    $sql .= " ORDER BY r.name, w.name";
    $gegenstaende = $db->select($sql, $par);
}
$summen = ['anzahl' => 0, 'preis' => 0.0, 'zeitwert' => 0.0, 'ohne_beleg' => 0, 'ohne_foto' => 0, 'dateien' => 0];
// Belege kommen nur mit Recht und eingeschalteter Dokumente-Funktion in die ZIP
$zipMitBelegen = false;
$fallVers = null;
if ($fall && $schritt === 3) {
    $zeilen = $db->select("SELECT w.id, w.name, w.bild, w.preis, w.aktueller_wert, w.erstellt_von, g.zustand,
                                  (SELECT COUNT(*) FROM dokumente d WHERE d.wertsache_id = w.id) AS belege,
                                  (SELECT COUNT(*) FROM item_images i WHERE i.item_id = w.id) AS fotos
                           FROM schadensfall_gegenstaende g JOIN wertsachen w ON w.id = g.wertsache_id
                           WHERE g.fall_id = ? ORDER BY w.name", [$fall['id']]);
    if (hasPermission('docs_view')) {
        $feature = $db->selectOne("SELECT dokumente_aktiv FROM users WHERE username = ?", [$ich]);
        $zipMitBelegen = !empty($feature['dokumente_aktiv']);
    }
    foreach ($zeilen as $z) {
        if (!darfGegenstand($z)) continue;
        $gegenstaende[] = $z;
        $summen['anzahl']++;
        $summen['preis']    += (float)$z['preis'];
        $summen['zeitwert'] += vsZeitwert($z);
        if ((int)$z['belege'] === 0) $summen['ohne_beleg']++;
        if (empty($z['bild'])) $summen['ohne_foto']++;
        $summen['dateien'] += max((int)$z['fotos'], empty($z['bild']) ? 0 : 1)
                            + ($zipMitBelegen ? (int)$z['belege'] : 0);
    }
    foreach ($versicherungen as $v) if ((int)$v['id'] === (int)$fall['versicherung_id']) $fallVers = $v;
}

// Vorschlag fuer die Versicherung: Hausrat, sonst die erste
$versVorschlag = 0;
foreach ($versicherungen as $v) {
    if (stripos($v['name'], 'hausrat') !== false || stripos($v['name'], 'contents') !== false) { $versVorschlag = (int)$v['id']; break; }
}
if (!$versVorschlag && $versicherungen) $versVorschlag = (int)$versicherungen[0]['id'];

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$preis = fn($p) => function_exists('formatPriceLocalized') ? formatPriceLocalized((float)$p) : number_format((float)$p, 2, ',', '.') . ' €';

include __DIR__ . '/header_next_page.php';
?>
<style>
.vs-claims { max-width: 960px; padding: var(--vs-sp-5, 20px); }
.vs-claims h1 { font-size: 1.5rem; margin: 0 0 6px; }
.vs-claims .lead { color: var(--vs-text-muted); margin: 0 0 20px; }
.vs-claims .box { background: var(--vs-surface); border: 1px solid var(--vs-border-strong); border-radius: 10px; padding: 20px; margin-bottom: 16px; }
.vs-claims .msg { padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; border: 1px solid var(--vs-border-strong); }
.vs-claims .msg-ok { background: var(--vs-success-bg, #dcfce7); color: var(--vs-success-text, #065f46); }
.vs-claims .msg-err { background: #fef2f2; color: #991b1b; }
.vs-claims .schritte { display: flex; gap: 8px; list-style: none; padding: 0; margin: 0 0 18px; flex-wrap: wrap; }
.vs-claims .schritte li { padding: 6px 12px; border-radius: 16px; border: 1px solid var(--vs-border-strong); font-size: 13px; color: var(--vs-text-muted); }
.vs-claims .schritte li[aria-current="step"] { background: var(--vs-accent); color: var(--vs-accent-text); border-color: var(--vs-accent); }
.vs-claims table { width: 100%; border-collapse: collapse; font-size: 14px; }
.vs-claims th, .vs-claims td { text-align: left; padding: 8px 6px; border-bottom: 1px solid var(--vs-border); vertical-align: middle; }
.vs-claims td.zahl, .vs-claims th.zahl { text-align: right; white-space: nowrap; }
.vs-claims .raster { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; }
.vs-claims .karte { display: flex; flex-direction: column; gap: 8px; border: 1px solid var(--vs-border-strong); border-radius: 8px; padding: 10px; background: var(--vs-surface); }
.vs-claims .karte:has(input[type=checkbox]:checked) { border-color: var(--vs-accent); box-shadow: 0 0 0 2px var(--vs-accent-light); }
.vs-claims .karte img, .vs-claims .karte .ph { width: 52px; height: 52px; object-fit: cover; border-radius: 6px; background: var(--vs-surface-2); flex-shrink: 0; }
.vs-claims .karte label { display: flex; gap: 10px; align-items: center; cursor: pointer; min-width: 0; text-align: left; }
.vs-claims .karte label input[type=checkbox] { width: 18px; height: 18px; flex-shrink: 0; }
.vs-claims .karte .text { min-width: 0; display: flex; flex-direction: column; }
.vs-claims .karte .name { font-weight: 600; font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.vs-claims .karte .meta { font-size: 12px; color: var(--vs-text-muted); }
.vs-claims .karte select { font-size: 13px; width: 100%; padding: 4px 6px; }
.vs-claims .aktionen { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }
.vs-claims .kennzahlen { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; margin: 6px 0 14px; }
.vs-claims .kennzahl { border: 1px solid var(--vs-border); border-radius: 8px; padding: 10px 12px; }
.vs-claims .kennzahl b { display: block; font-size: 1.2rem; }
.vs-claims .kennzahl span { font-size: 12px; color: var(--vs-text-muted); }
.vs-claims .hinweis { font-size: 13px; color: var(--vs-text-muted); }
.vs-claims .warnung { background: #fff8e1; border: 1px solid #b45309; color: #78350f; padding: 10px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 12px; }
.vs-claims input:focus-visible, .vs-claims select:focus-visible, .vs-claims textarea:focus-visible { outline: 2px solid var(--vs-accent); outline-offset: 2px; }
</style>

<div class="vs-claims">
    <h1><?php echo $e(t('claims_title')); ?></h1>
    <p class="lead"><?php echo $e(t('claims_lead')); ?></p>

    <?php if ($fehler): ?><div class="msg msg-err" role="alert"><?php echo $e($fehler); ?></div><?php endif; ?>
    <?php if (isset($_GET['geloescht'])): ?><div class="msg msg-ok" role="status"><?php echo $e(t('claims_deleted')); ?></div><?php endif; ?>
    <?php if (isset($_GET['gespeichert'])): ?><div class="msg msg-ok" role="status"><?php echo $e(t('claims_saved')); ?></div><?php endif; ?>

<?php if (!$migrationDa): ?>
    <div class="box" role="status">
        <p><?php echo $e(t('claims_migration_missing')); ?></p>
        <?php if (isAdmin()): ?><p><a class="btn btn-primary" href="migrate.php"><?php echo $e(t('claims_migration_run')); ?></a></p><?php endif; ?>
    </div>

<?php elseif ($schritt === 0): ?>
    <?php if ($darfVerwalten): ?>
    <p><a class="btn btn-primary" href="schadenfall.php?neu=1"><i class="ti ti-plus" aria-hidden="true"></i> <?php echo $e(t('claims_new')); ?></a></p>
    <?php endif; ?>
    <div class="box">
    <?php if (!$faelle): ?>
        <p class="hinweis" style="margin:0"><?php echo $e(t('claims_none')); ?></p>
    <?php else: ?>
        <table>
            <caption class="sr-only"><?php echo $e(t('claims_title')); ?></caption>
            <thead><tr>
                <th scope="col"><?php echo $e(t('claims_date')); ?></th>
                <th scope="col"><?php echo $e(t('claims_kind')); ?></th>
                <th scope="col"><?php echo $e(t('claims_insurance')); ?></th>
                <th scope="col" class="zahl"><?php echo $e(t('claims_items')); ?></th>
                <th scope="col"><?php echo $e(t('claims_status')); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($faelle as $f): ?>
                <tr>
                    <td><a href="schadenfall.php?id=<?php echo (int)$f['id']; ?>"><?php echo $f['datum'] ? $e(date('d.m.Y', strtotime($f['datum']))) : '–'; ?></a></td>
                    <td><?php echo $e(t('claims_art_' . $f['art'])); ?><?php echo $f['aktenzeichen'] ? ' · ' . $e($f['aktenzeichen']) : ''; ?></td>
                    <td><?php echo $e($f['versicherung_name'] ?? '–'); ?></td>
                    <td class="zahl"><?php echo (int)$f['anzahl']; ?></td>
                    <td><?php echo $e(t('claims_status_' . $f['status'])); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    </div>

<?php else: ?>
    <ol class="schritte" aria-label="<?php echo $e(t('claims_steps')); ?>">
        <?php foreach ([1 => 'claims_step1', 2 => 'claims_step2', 3 => 'claims_step3'] as $n => $k): ?>
        <li<?php echo $n === $schritt ? ' aria-current="step"' : ''; ?>><?php echo $n . '. ' . $e(t($k)); ?></li>
        <?php endforeach; ?>
    </ol>

    <?php if ($schritt === 1): ?>
    <form method="POST" class="box">
        <?php echo Security::getCSRFInput(); ?>
        <input type="hidden" name="aktion" value="fall_speichern">
        <input type="hidden" name="id" value="<?php echo (int)($fall['id'] ?? 0); ?>">
        <div class="form-group">
            <label for="art"><?php echo $e(t('claims_kind')); ?></label>
            <select id="art" name="art">
                <?php foreach (VS_SCHADEN_ARTEN as $a): ?>
                <option value="<?php echo $a; ?>"<?php echo ($fall['art'] ?? 'einbruch') === $a ? ' selected' : ''; ?>><?php echo $e(t('claims_art_' . $a)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="datum"><?php echo $e(t('claims_date')); ?></label>
            <input type="date" id="datum" name="datum" value="<?php echo $e($fall['datum'] ?? date('Y-m-d')); ?>">
        </div>
        <div class="form-group">
            <label for="aktenzeichen"><?php echo $e(t('claims_file_number')); ?></label>
            <input type="text" id="aktenzeichen" name="aktenzeichen" maxlength="100" value="<?php echo $e($fall['aktenzeichen'] ?? ''); ?>" aria-describedby="akz_hint">
            <small id="akz_hint"><?php echo $e(t('claims_file_number_hint')); ?></small>
        </div>
        <div class="form-group">
            <label for="versicherung_id"><?php echo $e(t('claims_insurance')); ?></label>
            <select id="versicherung_id" name="versicherung_id">
                <option value="0">–</option>
                <?php $vsel = (int)($fall['versicherung_id'] ?? $versVorschlag); foreach ($versicherungen as $v): ?>
                <option value="<?php echo (int)$v['id']; ?>"<?php echo (int)$v['id'] === $vsel ? ' selected' : ''; ?>><?php echo $e($v['name'] . ($v['anbieter'] ? ' (' . $v['anbieter'] . ')' : '')); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="beschreibung"><?php echo $e(t('claims_description')); ?></label>
            <textarea id="beschreibung" name="beschreibung" rows="4" maxlength="5000" aria-describedby="besch_hint"><?php echo $e($fall['beschreibung'] ?? ''); ?></textarea>
            <small id="besch_hint"><?php echo $e(t('claims_description_hint')); ?></small>
        </div>
        <div class="aktionen">
            <button type="submit" class="btn btn-primary"><?php echo $e(t('claims_next')); ?></button>
            <a class="btn btn-secondary" href="<?php echo $fall ? 'schadenfall.php?id=' . (int)$fall['id'] : 'schadenfall.php'; ?>"><?php echo $e(t('btn_cancel')); ?></a>
        </div>
    </form>

    <?php elseif ($schritt === 2): ?>
    <form method="GET" class="box" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        <input type="hidden" name="id" value="<?php echo (int)$fall['id']; ?>">
        <input type="hidden" name="schritt" value="2">
        <div class="form-group" style="margin:0; min-width:220px;">
            <label for="raum"><?php echo $e(t('claims_filter_room')); ?></label>
            <select id="raum" name="raum">
                <option value="0"><?php echo $e(t('loc_all_rooms')); ?></option>
                <?php foreach ($raeume as $r): ?>
                <option value="<?php echo (int)$r['id']; ?>"<?php echo $raumFilter === (int)$r['id'] ? ' selected' : ''; ?>><?php echo $e($r['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary"><?php echo $e(t('claims_filter_apply')); ?></button>
    </form>

    <form method="POST">
        <?php echo Security::getCSRFInput(); ?>
        <input type="hidden" name="aktion" value="gegenstaende_speichern">
        <input type="hidden" name="id" value="<?php echo (int)$fall['id']; ?>">
        <fieldset class="box" style="margin:0 0 16px;">
            <legend><?php echo $e(t('claims_select_items')); ?></legend>
            <?php if (!$gegenstaende): ?>
                <p class="hinweis"><?php echo $e(t('claims_no_items')); ?></p>
            <?php else: ?>
            <div class="raster">
            <?php foreach ($gegenstaende as $g): $gid = (int)$g['id']; $an = isset($imFall[$gid]); ?>
                <div class="karte">
                    <label for="g<?php echo $gid; ?>">
                        <input type="checkbox" id="g<?php echo $gid; ?>" name="gegenstand[]" value="<?php echo $gid; ?>"<?php echo $an ? ' checked' : ''; ?>>
                        <?php if (!empty($g['bild'])): ?>
                            <img src="upload/<?php echo rawurlencode($g['bild']); ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <span class="ph" aria-hidden="true"></span>
                        <?php endif; ?>
                        <span class="text">
                            <span class="name"><?php echo $e($g['name']); ?></span>
                            <span class="meta"><?php echo $e($g['raum_name'] ?? '–'); ?> · <?php echo $preis(vsZeitwert($g)); ?></span>
                        </span>
                    </label>
                    <div>
                        <label for="z<?php echo $gid; ?>" class="sr-only"><?php echo $e(sprintf(t('claims_condition_for'), $g['name'])); ?></label>
                        <select id="z<?php echo $gid; ?>" name="zustand[<?php echo $gid; ?>]">
                            <?php foreach (VS_SCHADEN_ZUSTAENDE as $z): ?>
                            <option value="<?php echo $z; ?>"<?php echo ($imFall[$gid] ?? ($fall['art'] === 'einbruch' ? 'gestohlen' : 'zerstoert')) === $z ? ' selected' : ''; ?>><?php echo $e(t('claims_cond_' . $z)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </fieldset>
        <div class="aktionen">
            <a class="btn btn-secondary" href="schadenfall.php?id=<?php echo (int)$fall['id']; ?>&amp;schritt=1"><?php echo $e(t('claims_back')); ?></a>
            <button type="submit" class="btn btn-primary"><?php echo $e(t('claims_next')); ?></button>
        </div>
    </form>

    <?php else: /* Schritt 3 */ ?>
    <div class="box">
        <h2 style="font-size:1.15rem; margin:0 0 8px;">
            <?php echo $e(t('claims_art_' . $fall['art'])); ?>
            <?php echo $fall['datum'] ? ' · ' . $e(date('d.m.Y', strtotime($fall['datum']))) : ''; ?>
        </h2>
        <p class="hinweis" style="margin:0 0 12px;">
            <?php echo $e(t('claims_file_number')); ?>: <?php echo $e($fall['aktenzeichen'] ?: '–'); ?> ·
            <?php echo $e(t('claims_insurance')); ?>: <?php echo $e($fallVers['name'] ?? '–'); ?>
        </p>

        <div class="kennzahlen">
            <div class="kennzahl"><b><?php echo (int)$summen['anzahl']; ?></b><span><?php echo $e(t('claims_items')); ?></span></div>
            <div class="kennzahl"><b><?php echo $preis($summen['preis']); ?></b><span><?php echo $e(t('claims_sum_purchase')); ?></span></div>
            <div class="kennzahl"><b><?php echo $preis($summen['zeitwert']); ?></b><span><?php echo $e(t('claims_sum_current')); ?></span></div>
            <?php if ($fallVers && (float)$fallVers['versicherungssumme'] > 0): ?>
            <div class="kennzahl"><b><?php echo $preis($fallVers['versicherungssumme']); ?></b><span><?php echo $e(t('claims_sum_insured')); ?></span></div>
            <?php endif; ?>
        </div>

        <?php if ($fallVers && (float)$fallVers['versicherungssumme'] > 0 && $summen['zeitwert'] > (float)$fallVers['versicherungssumme']): ?>
            <div class="warnung" role="note"><?php echo $e(t('claims_warn_underinsured')); ?></div>
        <?php endif; ?>
        <?php if ($fallVers && (float)$fallVers['selbstbeteiligung'] > 0): ?>
            <p class="hinweis"><?php echo $e(sprintf(t('claims_deductible'), $preis($fallVers['selbstbeteiligung']))); ?></p>
        <?php endif; ?>
        <?php if ($summen['ohne_beleg'] > 0): ?>
            <p class="hinweis"><?php echo $e(sprintf(t('claims_without_receipt'), $summen['ohne_beleg'])); ?></p>
        <?php endif; ?>
        <?php if ($summen['ohne_foto'] > 0): ?>
            <p class="hinweis"><?php echo $e(sprintf(t('claims_without_photo'), $summen['ohne_foto'])); ?></p>
        <?php endif; ?>

        <?php if ($gegenstaende): ?>
        <table>
            <caption class="sr-only"><?php echo $e(t('claims_items')); ?></caption>
            <thead><tr>
                <th scope="col"><?php echo $e(t('claims_item')); ?></th>
                <th scope="col"><?php echo $e(t('claims_condition')); ?></th>
                <th scope="col" class="zahl"><?php echo $e(t('claims_purchase_price')); ?></th>
                <th scope="col" class="zahl"><?php echo $e(t('claims_current_value')); ?></th>
                <th scope="col" class="zahl"><?php echo $e(t('claims_receipts')); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($gegenstaende as $g): ?>
                <tr>
                    <td><a href="edit.php?id=<?php echo (int)$g['id']; ?>"><?php echo $e($g['name']); ?></a></td>
                    <td><?php echo $e(t('claims_cond_' . $g['zustand'])); ?></td>
                    <td class="zahl"><?php echo $preis($g['preis']); ?></td>
                    <td class="zahl"><?php echo $preis(vsZeitwert($g)); ?></td>
                    <td class="zahl"><?php echo (int)$g['belege']; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="hinweis"><?php echo $e(t('claims_no_items_selected')); ?></p>
        <?php endif; ?>

        <div class="aktionen">
            <?php if ($gegenstaende): ?>
            <a class="btn btn-primary" href="schadenfall_dokument.php?id=<?php echo (int)$fall['id']; ?>&amp;art=polizei" target="_blank" rel="noopener"><i class="ti ti-file-text" aria-hidden="true"></i> <?php echo $e(t('claims_doc_police')); ?></a>
            <a class="btn btn-primary" href="schadenfall_dokument.php?id=<?php echo (int)$fall['id']; ?>&amp;art=versicherung" target="_blank" rel="noopener"><i class="ti ti-shield-check" aria-hidden="true"></i> <?php echo $e(t('claims_doc_insurance')); ?></a>
            <?php if ($summen['dateien'] > 0): ?>
            <a class="btn btn-primary" href="schadenfall_belege.php?id=<?php echo (int)$fall['id']; ?>" aria-describedby="zipHinweis"><i class="ti ti-file-zip" aria-hidden="true"></i> <?php echo $e(t('claims_zip')); ?> (<?php echo $e(sprintf(t('claims_zip_files'), $summen['dateien'])); ?>)</a>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ($darfVerwalten): ?>
            <a class="btn btn-secondary" href="schadenfall.php?id=<?php echo (int)$fall['id']; ?>&amp;schritt=2"><?php echo $e(t('claims_edit_items')); ?></a>
            <a class="btn btn-secondary" href="schadenfall.php?id=<?php echo (int)$fall['id']; ?>&amp;schritt=1"><?php echo $e(t('claims_edit_case')); ?></a>
            <?php endif; ?>
            <a class="btn btn-secondary" href="schadenfall.php"><?php echo $e(t('claims_all_cases')); ?></a>
        </div>
        <?php if ($gegenstaende && $summen['dateien'] > 0): ?>
        <p class="hinweis" id="zipHinweis"><?php echo $e(t('claims_zip_hint')); ?><?php if (!$zipMitBelegen): ?> <?php echo $e(t('claims_zip_no_docs')); ?><?php endif; ?></p>
        <?php endif; ?>
    </div>

    <?php if ($darfVerwalten): ?>
    <div class="box">
        <form method="POST" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="aktion" value="status">
            <input type="hidden" name="id" value="<?php echo (int)$fall['id']; ?>">
            <div class="form-group" style="margin:0; min-width:200px;">
                <label for="status"><?php echo $e(t('claims_status')); ?></label>
                <select id="status" name="status">
                    <?php foreach (VS_SCHADEN_STATUS as $s): ?>
                    <option value="<?php echo $s; ?>"<?php echo $fall['status'] === $s ? ' selected' : ''; ?>><?php echo $e(t('claims_status_' . $s)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary"><?php echo $e(t('claims_status_save')); ?></button>
        </form>
        <form method="POST" style="margin-top:14px;" onsubmit="return vsConfirmForm(event, <?php echo $e(json_encode(t('claims_delete_confirm'))); ?>);">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="aktion" value="loeschen">
            <input type="hidden" name="id" value="<?php echo (int)$fall['id']; ?>">
            <button type="submit" class="btn btn-danger"><?php echo $e(t('claims_delete')); ?></button>
            <small class="hinweis" style="display:block; margin-top:4px;"><?php echo $e(t('claims_delete_hint')); ?></small>
        </form>
    </div>
    <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
</div>

<?php include __DIR__ . '/footer_next_page.php'; ?>
