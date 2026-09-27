<?php
/**
 * Backend - Versicherungsverwaltung
 * Version 3.14
 */

require_once 'config.php';
requireBackendAccess();

$pageTitle = 'Versicherungen';
$message = '';
$messageType = '';

// Nach einem fehlgeschlagenen Zuordnen leitet der POST-Zweig mit ?fehler=1
// hierher zurueck - ein Redirect kann die Meldung nicht mittragen.
if (isset($_GET['fehler'])) {
    $message     = 'Zuordnung fehlgeschlagen - die Datenbank hat die Aenderung abgelehnt. '
                 . 'Einzelheiten stehen im PHP-Fehlerlog.';
    $messageType = 'error';
}

// --- Aktionen ---
$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// POST: Gegenstand zuweisen — VOR dem Haupt-POST-Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_item'])) {
    validateRequest();
    $versId = (int)($_POST['versicherung_id'] ?? 0);
    try {
        if (!empty($_POST['item_ids']) && is_array($_POST['item_ids'])) {
            foreach ($_POST['item_ids'] as $itemId) {
                $itemId = (int)$itemId;
                if ($itemId && $versId) {
                    $db->execute("UPDATE wertsachen SET versicherung_id=? WHERE id=?", [$versId, $itemId]);
                }
            }
        }
        if (!empty($_POST['item_id'])) {
            $itemId = (int)$_POST['item_id'];
            $db->execute("UPDATE wertsachen SET versicherung_id=NULL WHERE id=?", [$itemId]);
        }
    } catch (PDOException $e) {
        error_log('insurance assign_item: ' . $e->getMessage());
        header('Location: insurance.php?action=items&id=' . $versId . '&fehler=1');
        exit;
    }
    header('Location: insurance.php?action=items&id=' . $versId);
    exit;
}

// POST: Speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateRequest();

    $name               = trim($_POST['name'] ?? '');
    $anbieter           = trim($_POST['anbieter'] ?? '');
    $vertragsnummer     = trim($_POST['vertragsnummer'] ?? '');
    $praemie            = !empty($_POST['praemie']) ? floatval(str_replace(',', '.', $_POST['praemie'])) : null;
    $laufzeit_bis       = !empty($_POST['laufzeit_bis']) ? $_POST['laufzeit_bis'] : null;
    $notiz              = trim($_POST['notiz'] ?? '');
    // Neue Vertragsdaten
    $versicherungssumme = !empty($_POST['versicherungssumme']) ? floatval(str_replace(',', '.', $_POST['versicherungssumme'])) : null;
    $selbstbeteiligung  = !empty($_POST['selbstbeteiligung']) ? floatval(str_replace(',', '.', $_POST['selbstbeteiligung'])) : null;
    $vertragsbeginn     = !empty($_POST['vertragsbeginn']) ? $_POST['vertragsbeginn'] : null;
    $kuendigungsfrist   = trim($_POST['kuendigungsfrist'] ?? '');
    $zahlungsweise      = !empty($_POST['zahlungsweise']) ? $_POST['zahlungsweise'] : null;
    // Objekt
    $adresse            = trim($_POST['adresse'] ?? '');
    $plz                = trim($_POST['plz'] ?? '');
    $wohnort            = trim($_POST['wohnort'] ?? '');
    $wohnflaeche_qm     = !empty($_POST['wohnflaeche_qm']) ? floatval(str_replace(',', '.', $_POST['wohnflaeche_qm'])) : null;
    $anzahl_zimmer      = !empty($_POST['anzahl_zimmer']) ? intval($_POST['anzahl_zimmer']) : null;
    $gebaeudeart        = !empty($_POST['gebaeudeart']) ? $_POST['gebaeudeart'] : null;
    $etage              = isset($_POST['etage']) && $_POST['etage'] !== '' ? intval($_POST['etage']) : null;
    $keller             = isset($_POST['keller']) ? 1 : 0;
    // Zusatzbausteine
    $fahrraddiebstahl   = isset($_POST['fahrraddiebstahl']) ? 1 : 0;
    $glasbruch          = isset($_POST['glasbruch']) ? 1 : 0;
    $elementarschaeden  = isset($_POST['elementarschaeden']) ? 1 : 0;
    $ueberspannung      = isset($_POST['ueberspannung']) ? 1 : 0;

    if (empty($name)) {
        $message = 'Bitte einen Namen angeben.';
        $messageType = 'error';
    } else {
        // ACHTUNG: Database::execute() faengt PDOException selbst ab, schreibt
        // ins PHP-Fehlerlog und liefert false. Beim Aufrufer kommt nie eine
        // Ausnahme an - das try/catch hier lief jahrelang ins Leere, und die
        // Seite meldete "Versicherung aktualisiert." auch dann, wenn die
        // Datenbank die Anweisung abgelehnt hatte. Deshalb wird jetzt der
        // Rueckgabewert ausgewertet. Das try/catch bleibt fuer alles andere.
        $gespeichert = false;
        try {
            if (!empty($_POST['edit_id'])) {
                // Update
                $gespeichert = $db->execute(
                    "UPDATE versicherungen SET name=?, anbieter=?, vertragsnummer=?, praemie=?, laufzeit_bis=?, notiz=?,
                     versicherungssumme=?, selbstbeteiligung=?, vertragsbeginn=?, kuendigungsfrist=?, zahlungsweise=?,
                     adresse=?, plz=?, wohnort=?, wohnflaeche_qm=?, anzahl_zimmer=?, gebaeudeart=?, etage=?, keller=?,
                     fahrraddiebstahl=?, glasbruch=?, elementarschaeden=?, ueberspannung=?
                     WHERE id=?",
                    [$name, $anbieter, $vertragsnummer, $praemie, $laufzeit_bis, $notiz,
                     $versicherungssumme, $selbstbeteiligung, $vertragsbeginn, $kuendigungsfrist, $zahlungsweise,
                     $adresse, $plz, $wohnort, $wohnflaeche_qm, $anzahl_zimmer, $gebaeudeart, $etage, $keller,
                     $fahrraddiebstahl, $glasbruch, $elementarschaeden, $ueberspannung,
                     (int)$_POST['edit_id']]
                );
                $message = $gespeichert
                    ? 'Versicherung aktualisiert.'
                    : 'Speichern fehlgeschlagen - die Datenbank hat die Aenderung abgelehnt. Einzelheiten stehen im PHP-Fehlerlog.';
            } else {
                // Insert
                $gespeichert = $db->execute(
                    "INSERT INTO versicherungen (name, anbieter, vertragsnummer, praemie, laufzeit_bis, notiz,
                     versicherungssumme, selbstbeteiligung, vertragsbeginn, kuendigungsfrist, zahlungsweise,
                     adresse, plz, wohnort, wohnflaeche_qm, anzahl_zimmer, gebaeudeart, etage, keller,
                     fahrraddiebstahl, glasbruch, elementarschaeden, ueberspannung)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$name, $anbieter, $vertragsnummer, $praemie, $laufzeit_bis, $notiz,
                     $versicherungssumme, $selbstbeteiligung, $vertragsbeginn, $kuendigungsfrist, $zahlungsweise,
                     $adresse, $plz, $wohnort, $wohnflaeche_qm, $anzahl_zimmer, $gebaeudeart, $etage, $keller,
                     $fahrraddiebstahl, $glasbruch, $elementarschaeden, $ueberspannung]
                );
                $message = $gespeichert
                    ? 'Versicherung angelegt.'
                    : 'Anlegen fehlgeschlagen - die Datenbank hat die Anweisung abgelehnt. Einzelheiten stehen im PHP-Fehlerlog.';
            }
            $messageType = $gespeichert ? 'success' : 'error';
            if ($gespeichert) {
                $action = 'list';
            }
        } catch (Exception $e) {
            $message = 'Fehler: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Löschen
if ($action === 'delete' && $editId) {
    try {
        $db->execute("UPDATE wertsachen SET versicherung_id=NULL WHERE versicherung_id=?", [$editId]);
        $db->execute("DELETE FROM versicherungen WHERE id=?", [$editId]);
        $message = 'Versicherung gelöscht.';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Fehler beim Löschen.';
        $messageType = 'error';
    }
    $action = 'list';
}

// --- Daten laden ---
$versicherungen = $db->select("
    SELECT v.id, v.name, v.anbieter, v.vertragsnummer, v.praemie, v.laufzeit_bis, v.notiz, v.erstellt_am,
           COUNT(w.id) as anzahl_items,
           COALESCE(SUM(w.aktueller_wert), 0) as gesamtwert
    FROM versicherungen v
    LEFT JOIN wertsachen w ON w.versicherung_id = v.id
    GROUP BY v.id, v.name, v.anbieter, v.vertragsnummer, v.praemie, v.laufzeit_bis, v.notiz, v.erstellt_am
    ORDER BY v.name ASC
");

$editData = null;
if ($action === 'edit' && $editId) {
    $rows = $db->select("SELECT * FROM versicherungen WHERE id=?", [$editId]);
    $editData = $rows[0] ?? null;
}

// Items einer Versicherung
$versItems = [];
$versInfo = null;
$unassignedItems = [];
if ($action === 'items' && $editId) {
    $rows = $db->select("SELECT * FROM versicherungen WHERE id=?", [$editId]);
    $versInfo = $rows[0] ?? null;
    $versItems = $db->select("
        SELECT w.*, k.name as kategorie
        FROM wertsachen w
        LEFT JOIN kategorien k ON w.kategorie_id = k.id
        WHERE w.versicherung_id = ?
        ORDER BY w.name ASC
    ", [$editId]);
    $unassignedItems = $db->select("
        SELECT w.*, k.name as kategorie
        FROM wertsachen w
        LEFT JOIN kategorien k ON w.kategorie_id = k.id
        WHERE w.versicherung_id IS NULL
        ORDER BY w.name ASC
    ");
}

include 'layout/header_next_page.php';
?>

<main class="backend-main">

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>" style="margin-bottom:20px; padding:12px 18px; border-radius:8px; background:<?php echo $messageType === 'success' ? '#d4edda' : '#f8d7da'; ?>; color:<?php echo $messageType === 'success' ? '#155724' : '#721c24'; ?>; border:1px solid <?php echo $messageType === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>

    <!-- Übersicht -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h2 style="margin:0;"><i class="ti ti-shield"></i> Versicherungen</h2>
        <a href="insurance.php?action=new" class="vs-btn vs-btn-primary">+ Neue Versicherung</a>
    </div>

    <?php if (empty($versicherungen)): ?>
        <div class="activity-timeline" style="text-align:center; padding:60px; color:#999;">
            <div style="font-size:56px; margin-bottom:16px;"><i class="ti ti-shield"></i></div>
            <p style="font-size:16px;">Noch keine Versicherungen angelegt.</p>
            <a href="insurance.php?action=new" class="vs-btn vs-btn-primary" style="margin-top:12px; display:inline-block;">Erste Versicherung anlegen</a>
        </div>
    <?php else: ?>
        <div class="dashboard-grid" style="margin-bottom:30px;">
            <?php
            $gesamtPraemie = array_sum(array_column($versicherungen, 'praemie'));
            $gesamtItems   = array_sum(array_column($versicherungen, 'anzahl_items'));
            $gesamtWert    = array_sum(array_column($versicherungen, 'gesamtwert'));
            ?>
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value"><?php echo count($versicherungen); ?></div>
                        <div class="widget-label"><i class="ti ti-shield"></i> Verträge</div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-shield"></i></div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value"><?php echo $gesamtItems; ?></div>
                        <div class="widget-label"><i class="ti ti-package"></i> Versicherte Gegenstände</div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-package"></i></div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size:22px;"><?php echo formatPrice($gesamtWert); ?></div>
                        <div class="widget-label"><i class="ti ti-currency-euro"></i> Versicherter Wert</div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-currency-euro"></i></div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-header">
                    <div>
                        <div class="widget-value" style="font-size:22px;"><?php echo formatPrice($gesamtPraemie); ?></div>
                        <div class="widget-label"><i class="ti ti-clipboard-list"></i> Prämien/Jahr gesamt</div>
                    </div>
                    <div class="widget-icon"><i class="ti ti-clipboard-list"></i></div>
                </div>
            </div>
        </div>

        <div class="activity-timeline">
            <table class="backend-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Anbieter</th>
                        <th style="width:130px;">Prämie/Jahr</th>
                        <th style="width:130px;">Läuft bis</th>
                        <th style="width:100px;">Gegenstände</th>
                        <th style="width:140px;">Vers. Wert</th>
                        <th style="width:160px;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($versicherungen as $v): ?>
                        <?php
                        $abgelaufen = !empty($v['laufzeit_bis']) && strtotime($v['laufzeit_bis']) < time();
                        $bald = !empty($v['laufzeit_bis']) && strtotime($v['laufzeit_bis']) < strtotime('+30 days') && !$abgelaufen;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($v['name']); ?></strong>
                                <?php if (!empty($v['vertragsnummer'])): ?>
                                    <br><small style="color:#999;">Nr: <?php echo htmlspecialchars($v['vertragsnummer']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($v['anbieter'] ?? '—'); ?></td>
                            <td><?php echo $v['praemie'] ? formatPrice($v['praemie']) : '—'; ?></td>
                            <td>
                                <?php if (!empty($v['laufzeit_bis'])): ?>
                                    <span style="color:<?php echo $abgelaufen ? 'var(--vs-danger)' : ($bald ? 'var(--vs-warning)' : 'inherit'); ?>; font-weight:<?php echo ($abgelaufen || $bald) ? 'bold' : 'normal'; ?>">
                                        <?php echo date('d.m.Y', strtotime($v['laufzeit_bis'])); ?>
                                        <?php if ($abgelaufen): ?> <i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i><?php elseif ($bald): ?> ⏰<?php endif; ?>
                                    </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <a href="insurance.php?action=items&id=<?php echo $v['id']; ?>" style="text-decoration:none;">
                                    <span class="badge badge-info"><?php echo $v['anzahl_items']; ?> Stück</span>
                                </a>
                            </td>
                            <td><strong style="color:var(--vs-success);"><?php echo $v['gesamtwert'] ? formatPrice($v['gesamtwert']) : '—'; ?></strong></td>
                            <td>
                                <a href="insurance.php?action=items&id=<?php echo $v['id']; ?>" class="vs-btn vs-btn-sm" style="background:var(--vs-success);color:var(--vs-surface);margin-right:4px;"><i class="ti ti-package"></i> Items</a>
                                <a href="insurance.php?action=edit&id=<?php echo $v['id']; ?>" class="vs-btn vs-btn-sm"><i class="ti ti-pencil"></i></a>
                                <a href="insurance.php?action=delete&id=<?php echo $v['id']; ?>" class="vs-btn vs-btn-sm" style="background:var(--vs-danger);color:var(--vs-surface);" onclick="return vsConfirmLink(event, <?php echo htmlspecialchars(json_encode(t('ins_confirm_delete')), ENT_QUOTES, 'UTF-8'); ?>);"><i class="ti ti-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

<?php elseif ($action === 'new' || $action === 'edit'): ?>

    <!-- Formular anlegen/bearbeiten -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h2 style="margin:0;"><?php echo $action === 'new' ? '+ Neue Versicherung' : '<i class="ti ti-pencil"></i> Versicherung bearbeiten'; ?></h2>
        <a href="insurance.php" style="color:#666; text-decoration:none;">← Zurück</a>
    </div>

    <div class="activity-timeline" style="position:relative; z-index:10;">
        <form method="POST" action="insurance.php" style="position:relative; z-index:10;">
            <?php echo Security::getCSRFInput(); ?>
            <?php if ($editData): ?>
                <input type="hidden" name="edit_id" value="<?php echo $editData['id']; ?>">
            <?php endif; ?>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; position:relative; z-index:10;">
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Name *</label>
                    <input type="text" name="name" required
                           value="<?php echo htmlspecialchars($editData['name'] ?? ''); ?>"
                           placeholder="z.B. Hausrat Allianz"
                           style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Anbieter</label>
                    <input type="text" name="anbieter"
                           value="<?php echo htmlspecialchars($editData['anbieter'] ?? ''); ?>"
                           placeholder="z.B. Allianz, ERGO, HUK..."
                           style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Vertragsnummer</label>
                    <input type="text" name="vertragsnummer"
                           value="<?php echo htmlspecialchars($editData['vertragsnummer'] ?? ''); ?>"
                           placeholder="z.B. HH-123456-78"
                           style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Jahresprämie (€)</label>
                    <input type="text" name="praemie"
                           value="<?php echo $editData['praemie'] ?? ''; ?>"
                           placeholder="z.B. 249.50"
                           style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Vertragsbeginn</label>
                    <input type="date" name="vertragsbeginn"
                           value="<?php echo $editData['vertragsbeginn'] ?? ''; ?>"
                           style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Laufzeit bis</label>
                    <input type="date" name="laufzeit_bis"
                           value="<?php echo $editData['laufzeit_bis'] ?? ''; ?>"
                           style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Versicherungssumme (€)</label>
                    <input type="text" name="versicherungssumme"
                           value="<?php echo $editData['versicherungssumme'] ?? ''; ?>"
                           placeholder="z.B. 50000"
                           style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Selbstbeteiligung (€)</label>
                    <input type="text" name="selbstbeteiligung"
                           value="<?php echo $editData['selbstbeteiligung'] ?? ''; ?>"
                           placeholder="z.B. 150"
                           style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Zahlungsweise</label>
                    <select name="zahlungsweise" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                        <option value="">— bitte wählen —</option>
                        <?php foreach (['monatlich','vierteljaehrlich','halbjaehrlich','jaehrlich'] as $zw): ?>
                        <option value="<?php echo $zw; ?>" <?php echo ($editData['zahlungsweise'] ?? '') === $zw ? 'selected' : ''; ?>>
                            <?php echo ucfirst($zw); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Kündigungsfrist</label>
                    <input type="text" name="kuendigungsfrist"
                           value="<?php echo htmlspecialchars($editData['kuendigungsfrist'] ?? ''); ?>"
                           placeholder="z.B. 3 Monate zum Jahresende"
                           style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                </div>
                <div style="grid-column:1/-1;">
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Notiz</label>
                    <textarea name="notiz" rows="2"
                              placeholder="Zusätzliche Informationen..."
                              style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px; resize:vertical;"><?php echo htmlspecialchars($editData['notiz'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Bereich 2: Objekt (aufklappbar) -->
            <details style="margin-top:24px;" <?php echo !empty($editData['adresse']) || !empty($editData['wohnflaeche_qm']) ? 'open' : ''; ?>>
                <summary style="cursor:pointer; font-weight:700; font-size:15px; padding:12px 16px; background:#f0f4ff; border-radius:8px; border:1px solid #d0d9ff; list-style:none; display:flex; align-items:center; gap:8px;">
                    <i class="ti ti-home"></i> Objekt-Daten <span style="font-size:12px; font-weight:400; color:#888;">(optional)</span>
                </summary>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:16px; padding:16px; background:#f8f9ff; border-radius:8px; border:1px solid #e0e8ff;">
                    <div style="grid-column:1/-1;">
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Straße und Hausnummer</label>
                        <input type="text" name="adresse"
                               value="<?php echo htmlspecialchars($editData['adresse'] ?? ''); ?>"
                               placeholder="z.B. Musterstraße 12"
                               style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-weight:600;">PLZ</label>
                        <input type="text" name="plz"
                               value="<?php echo htmlspecialchars($editData['plz'] ?? ''); ?>"
                               placeholder="z.B. 50181"
                               maxlength="10"
                               style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Ort</label>
                        <input type="text" name="wohnort"
                               value="<?php echo htmlspecialchars($editData['wohnort'] ?? ''); ?>"
                               placeholder="z.B. Musterstadt"
                               style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Gebäudeart</label>
                        <select name="gebaeudeart" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                            <option value="">— bitte wählen —</option>
                            <?php foreach (['Wohnung','Haus','Gewerbe','Sonstiges'] as $ga): ?>
                            <option value="<?php echo $ga; ?>" <?php echo ($editData['gebaeudeart'] ?? '') === $ga ? 'selected' : ''; ?>>
                                <?php echo $ga; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Wohnfläche (m²)</label>
                        <input type="text" name="wohnflaeche_qm"
                               value="<?php echo $editData['wohnflaeche_qm'] ?? ''; ?>"
                               placeholder="z.B. 85"
                               style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Anzahl Zimmer</label>
                        <input type="number" name="anzahl_zimmer" min="1" max="20"
                               value="<?php echo $editData['anzahl_zimmer'] ?? ''; ?>"
                               placeholder="z.B. 4"
                               style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Etage</label>
                        <input type="number" name="etage" min="0" max="50"
                               value="<?php echo $editData['etage'] ?? ''; ?>"
                               placeholder="0 = Erdgeschoss"
                               style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; background:var(--vs-surface); color:#333; font-size:14px;">
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; padding-top:28px;">
                        <input type="checkbox" name="keller" id="keller" value="1"
                               <?php echo !empty($editData['keller']) ? 'checked' : ''; ?>
                               style="width:18px; height:18px; cursor:pointer; accent-color:var(--vs-accent);">
                        <label for="keller" style="font-weight:600; cursor:pointer;">Keller vorhanden</label>
                    </div>
                </div>
            </details>

            <!-- Bereich 3: Zusatzbausteine -->
            <details style="margin-top:16px;" <?php echo (!empty($editData['fahrraddiebstahl']) || !empty($editData['glasbruch']) || !empty($editData['elementarschaeden']) || !empty($editData['ueberspannung'])) ? 'open' : ''; ?>>
                <summary style="cursor:pointer; font-weight:700; font-size:15px; padding:12px 16px; background:#f0fff4; border-radius:8px; border:1px solid #c3e6cb; list-style:none; display:flex; align-items:center; gap:8px;">
                    <i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Zusatzbausteine <span style="font-size:12px; font-weight:400; color:#888;">(optional)</span>
                </summary>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:16px; padding:16px; background:#f0fff4; border-radius:8px; border:1px solid #c3e6cb;">
                    <?php
                    $bausteine = [
                        'fahrraddiebstahl' => '🚲 Fahrraddiebstahl',
                        'glasbruch'        => '🪟 Glasbruch',
                        'elementarschaeden'=> '🌊 Elementarschäden',
                        'ueberspannung'    => '⚡ Überspannung',
                    ];
                    foreach ($bausteine as $field => $label):
                    ?>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" name="<?php echo $field; ?>" id="<?php echo $field; ?>" value="1"
                               <?php echo !empty($editData[$field]) ? 'checked' : ''; ?>
                               style="width:18px; height:18px; cursor:pointer; accent-color:var(--vs-success);">
                        <label for="<?php echo $field; ?>" style="font-weight:600; cursor:pointer;"><?php echo $label; ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </details>

            <div style="margin-top:24px; display:flex; gap:12px;">
                <button type="submit" style="padding:10px 28px; background: var(--vs-accent); color:var(--vs-surface); border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:15px;">
                    <i class="ti ti-device-floppy"></i> Speichern
                </button>
                <a href="insurance.php" style="padding:10px 20px; background:var(--vs-border); color:#333; border-radius:6px; text-decoration:none; font-weight:600;">Abbrechen</a>
            </div>
        </form>
    </div>

<?php elseif ($action === 'items' && $versInfo): ?>

    <!-- Items einer Versicherung -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 style="margin:0;"><i class="ti ti-shield"></i> <?php echo htmlspecialchars($versInfo['name']); ?></h2>
            <p style="margin:4px 0 0; color:#666;">
                <?php if ($versInfo['anbieter']): ?><?php echo htmlspecialchars($versInfo['anbieter']); ?> · <?php endif; ?>
                <?php if ($versInfo['vertragsnummer']): ?>Nr. <?php echo htmlspecialchars($versInfo['vertragsnummer']); ?> · <?php endif; ?>
                <?php if ($versInfo['praemie']): ?>Prämie: <?php echo formatPrice($versInfo['praemie']); ?>/Jahr<?php endif; ?>
            </p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="../insurance.php?v=<?php echo $editId; ?>" target="_blank" class="vs-btn vs-btn-primary" style="background: var(--vs-accent);">📄 PDF-Export</a>
            <a href="insurance.php" style="padding:8px 16px; background:var(--vs-border); color:#333; border-radius:6px; text-decoration:none; font-weight:600;">← Zurück</a>
        </div>
    </div>

    <!-- Zugeordnete Items -->
    <div class="activity-timeline" style="margin-bottom:24px;">
        <h3 style="margin-top:0;"><i class="ti ti-package"></i> Zugeordnete Gegenstände (<?php echo count($versItems); ?>)</h3>

        <?php if (empty($versItems)): ?>
            <p style="color:#999; padding:20px 0;">Noch keine Gegenstände zugeordnet.</p>
        <?php else: ?>
            <?php
            $summe = array_sum(array_column($versItems, 'aktueller_wert'));
            ?>
            <table class="backend-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Kategorie</th>
                        <th style="width:160px;">Wert</th>
                        <th style="width:100px;">Entfernen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($versItems as $item): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td><span class="badge badge-secondary"><?php echo htmlspecialchars($item['kategorie'] ?? '—'); ?></span></td>
                            <td><strong style="color:var(--vs-success);"><?php echo formatPrice($item['aktueller_wert'] ?: $item['preis']); ?></strong></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <?php echo Security::getCSRFInput(); ?>
                                    <input type="hidden" name="assign_item" value="1">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="versicherung_id" value="0">
                                    <button type="submit" class="vs-btn vs-btn-sm" style="background:var(--vs-danger);color:var(--vs-surface);" onclick="return vsConfirmSubmit(event, <?php echo htmlspecialchars(json_encode(t('ins_confirm_unassign')), ENT_QUOTES, 'UTF-8'); ?>);">✕</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background:var(--bg-color); font-weight:bold;">
                        <td colspan="2">Gesamtwert versicherte Gegenstände</td>
                        <td style="color:var(--vs-success); font-size:16px;"><?php echo formatPrice($summe); ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Nicht zugeordnete Items -->
    <?php if (!empty($unassignedItems)): ?>
    <div class="activity-timeline">
        <h3 style="margin-top:0;"><i class="ti ti-plus"></i> Gegenstände zuordnen</h3>
        <form method="POST">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="assign_item" value="1">
            <input type="hidden" name="versicherung_id" value="<?php echo $editId; ?>">

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                <label style="font-size:13px; color:#666; cursor:pointer;">
                    <input type="checkbox" id="selectAll" style="margin-right:6px;">
                    Alle auswählen
                </label>
                <button type="submit" class="vs-btn vs-btn-primary" style="padding:8px 18px;">
                    <i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Ausgewählte zuordnen
                </button>
            </div>

            <div style="max-height:400px; overflow-y:auto;">
                <table class="backend-table">
                    <thead>
                        <tr>
                            <th style="width:40px;"></th>
                            <th>Name</th>
                            <th>Kategorie</th>
                            <th style="width:160px;">Wert</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unassignedItems as $item): ?>
                            <tr class="item-row" style="cursor:pointer;" onclick="toggleCheck(this)">
                                <td onclick="event.stopPropagation()">
                                    <input type="checkbox" name="item_ids[]" value="<?php echo $item['id']; ?>"
                                           class="item-checkbox" style="width:16px; height:16px; cursor:pointer;">
                                </td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><span class="badge badge-secondary"><?php echo htmlspecialchars($item['kategorie'] ?? '—'); ?></span></td>
                                <td><?php echo formatPrice($item['aktueller_wert'] ?: $item['preis']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
    <?php endif; ?>

<script>
function toggleCheck(row) {
    const cb = row.querySelector('.item-checkbox');
    if (cb) {
        cb.checked = !cb.checked;
        row.style.background = cb.checked ? '#eef0fb' : '';
    }
}
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.checked = this.checked;
        cb.closest('tr').style.background = this.checked ? '#eef0fb' : '';
    });
});
document.querySelectorAll('.item-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        this.closest('tr').style.background = this.checked ? '#eef0fb' : '';
    });
});
</script>

<?php endif; ?>

</main>

<style>
/* Form-Reset: explizite Farben gegen Theme-Konflikte */
.activity-timeline input[type="text"],
.activity-timeline input[type="date"],
.activity-timeline textarea {
    background: var(--vs-surface) !important;
    color: #333333 !important;
    border: 1px solid #cccccc !important;
    font-size: 14px !important;
    position: relative !important;
    z-index: 20 !important;
    pointer-events: auto !important;
}
.activity-timeline label {
    color: var(--text-color, #333) !important;
    position: relative !important;
    z-index: 20 !important;
}
.btn-primary-action {
    padding: 9px 20px;
    background: var(--vs-accent);
    color: var(--vs-surface);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    font-size: 14px;
}
.backend-table { width:100%; border-collapse:collapse; margin-top:12px; }
.backend-table thead tr { background:var(--bg-color); border-bottom:2px solid var(--border-color); }
.backend-table th, .backend-table td { padding:12px; text-align:left; }
.backend-table tbody tr { border-bottom:1px solid var(--border-color); transition:background 0.2s; }
.backend-table tbody tr:hover { background:var(--bg-color); }
.badge { padding:4px 10px; border-radius:12px; font-size:11px; font-weight:600; display:inline-block; }
.badge-info { background:var(--vs-accent-light); color:var(--vs-accent); }
.badge-secondary { background:var(--vs-border); color:#666; }
.btn-small { padding:6px 12px; border:none; border-radius:4px; cursor:pointer; font-size:12px; text-decoration:none; display:inline-block; transition:var(--transition); }
.btn-view { background:var(--vs-accent); color:var(--vs-surface); }
</style>

<?php include 'layout/footer_next_page.php'; ?>
