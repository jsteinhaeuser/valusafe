<?php
/**
 * Orte-Verwaltung — Räume · Standorte · Positionen
 * Räume: raeume-Tabelle (Wohnzimmer, Küche...)
 * Standorte: standorte-Tabelle (Schrank, Regal...)
 * Positionen: positionen-Tabelle (Oberes Fach...)
 */

/*
 * ZU DEN TABELLEN standort_raeume UND standort_positionen
 *
 * Seit 4.3.25 gibt es sie nicht mehr (Migration 011, install/schema.sql).
 * Sie gehoerten zu backend/standorte.php, einer zweiten Ortsverwaltung, die
 * parallel zu dieser hier lief und mit dem Rest der Anwendung nie verbunden
 * wurde: wertsachen zeigte ueber position_id auf positionen, nie auf
 * standort_positionen. Was dort eingetragen wurde, ist nirgends erschienen.
 *
 * Die Seite ist mit 4.3.21 entfernt. Die Tabellen blieben zunaechst stehen,
 * weil in standort_raeume 23 sorgfaeltig benannte Raeume standen; die Namen
 * wurden am 24.09.2026 nach raeume uebernommen, danach waren die Tabellen
 * leerer Ballast.
 *
 * Schon einmal geloescht: mit 4.2 (21.07.2026) wurde standorte.php auf allen
 * zehn Instanzen entfernt — und kam zurueck, weil die Datei im Repository
 * und damit auf dem Master stehen blieb und der naechste Datei-Sync sie
 * verteilte. Einen Monat spaeter hat sie in 4.3.0 sogar CSRF-Schutz
 * bekommen. Merke: loeschen heisst zuerst im Repository, dann auf dem
 * Master, dann per cleanup.php auf den Instanzen.
 */


require_once __DIR__ . '/config.php';
requireBackendAccess();

$pageTitle = t('loc_page_title');
$message   = $_SESSION['vs_flash'] ?? '';
unset($_SESSION['vs_flash']);
$error     = '';

$activeTab = $_GET['tab'] ?? 'raeume';
if (!in_array($activeTab, ['raeume', 'standorte', 'positionen'])) $activeTab = 'raeume';

// ── POST ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Schutz: alle state-aendernden Aktionen dieser Seite laufen
    // ueber diesen einen POST-Block (s. backend/backup.php als Vorbild).
    validateRequest();
    $action = $_POST['action'] ?? '';

    // RÄUME (raeume-Tabelle)
    if ($action === 'add_raum') {
        $name              = trim($_POST['name'] ?? '');
        $standort_name     = trim($_POST['standort_name'] ?? '');
        $position_name     = trim($_POST['position_name'] ?? '');
        $position_beschr   = trim($_POST['position_beschreibung'] ?? '');
        if (empty($name)) { $error = t('lc_room_name_empty'); }
        else {
            try {
                if ($db->selectOne("SELECT id FROM raeume WHERE name = ?", [$name])) {
                    $error = t('lc_room_exists');
                } else {
                    $raum_id = $db->insert("INSERT INTO raeume (name) VALUES (?)", [$name]);
                    logActivity('created', 'raeume', $raum_id, $name);
                    $msg = sprintf(t('lc_room_created'), $name);
                    // Optionaler Standort
                    if (!empty($standort_name)) {
                        $standort_id = $db->insert("INSERT INTO standorte (name) VALUES (?)", [$standort_name]);
                        logActivity('created', 'standorte', $standort_id, $standort_name);
                        $msg .= sprintf(t('lc_and_location_created'), $standort_name);
                        // Optionale Position
                        if (!empty($position_name)) {
                            $pos_id = $db->insert(
                                "INSERT INTO positionen (raum_id, name, beschreibung) VALUES (?, ?, ?)",
                                [$standort_id, $position_name, $position_beschr ?: null]
                            );
                            logActivity('created', 'positionen', $pos_id, $position_name);
                            $msg .= sprintf(t('lc_and_position_created'), $position_name);
                        }
                    }
                    $_SESSION['vs_flash'] = $msg;
                    header('Location: locations.php?tab=raeume'); exit;
                }
            } catch (PDOException $e) { error_log('locations.php: ' . $e->getMessage()); $error = t('be_save_failed'); }
        }
    }
    if ($action === 'edit_raum') {
        $id = intval($_POST['id'] ?? 0); $name = trim($_POST['name'] ?? '');
        if (empty($name) || $id <= 0) { $error = t('be_invalid_input'); }
        else {
            try {
                $old = $db->selectOne("SELECT name FROM raeume WHERE id = ?", [$id]);
                $db->execute("UPDATE raeume SET name = ? WHERE id = ?", [$name, $id]);
                if ($old) logActivity('updated', 'raeume', $id, $name, ['name' => $old['name']], ['name' => $name]);
                $_SESSION['vs_flash'] = t('lc_room_updated');
                header('Location: locations.php?tab=raeume'); exit;
            } catch (PDOException $e) { error_log('locations.php: ' . $e->getMessage()); $error = t('be_save_failed'); }
        }
    }
    if ($action === 'delete_raum') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) { $error = t('loc_invalid_id'); }
        else {
            try {
                $inUse = $db->selectOne("SELECT COUNT(*) as c FROM wertsachen WHERE raum_id = ?", [$id]);
                if ($inUse['c'] > 0) {
                    $error = sprintf(t('loc_err_room_items_used'), $inUse['c']);
                } else {
                    $row = $db->selectOne("SELECT name FROM raeume WHERE id = ?", [$id]);
                    $db->execute("DELETE FROM raeume WHERE id = ?", [$id]);
                    if ($row) logActivity('deleted', 'raeume', $id, $row['name']);
                    $_SESSION['vs_flash'] = t('lc_room_deleted');
                    header('Location: locations.php?tab=raeume'); exit;
                }
            } catch (PDOException $e) { error_log('locations.php: ' . $e->getMessage()); $error = t('be_save_failed'); }
        }
    }

    // STANDORTE (standorte-Tabelle)
    if ($action === 'add_standort') {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) { $error = t('lc_loc_name_empty'); }
        else {
            try {
                if ($db->selectOne("SELECT id FROM standorte WHERE name = ?", [$name])) {
                    $error = t('lc_loc_exists');
                } else {
                    $id = $db->insert("INSERT INTO standorte (name) VALUES (?)", [$name]);
                    logActivity('created', 'standorte', $id, $name);
                    $_SESSION['vs_flash'] = t('lc_loc_added');
                    header('Location: locations.php?tab=standorte'); exit;
                }
            } catch (PDOException $e) { error_log('locations.php: ' . $e->getMessage()); $error = t('be_save_failed'); }
        }
    }
    if ($action === 'edit_standort') {
        $id = intval($_POST['id'] ?? 0); $name = trim($_POST['name'] ?? '');
        if (empty($name) || $id <= 0) { $error = t('be_invalid_input'); }
        else {
            try {
                $old = $db->selectOne("SELECT name FROM standorte WHERE id = ?", [$id]);
                $db->execute("UPDATE standorte SET name = ? WHERE id = ?", [$name, $id]);
                if ($old) logActivity('updated', 'standorte', $id, $name, ['name' => $old['name']], ['name' => $name]);
                $_SESSION['vs_flash'] = t('lc_loc_updated');
                header('Location: locations.php?tab=standorte'); exit;
            } catch (PDOException $e) { error_log('locations.php: ' . $e->getMessage()); $error = t('be_save_failed'); }
        }
    }
    if ($action === 'delete_standort') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) { $error = t('loc_invalid_id'); }
        else {
            try {
                $inUse = $db->selectOne("SELECT COUNT(*) as c FROM positionen WHERE raum_id = ?", [$id]);
                $items = $db->selectOne("SELECT COUNT(*) as c FROM wertsachen WHERE standort_id = ?", [$id]);
                if ($inUse['c'] > 0) {
                    $error = sprintf(t('lc_loc_has_positions'), $inUse['c']);
                } elseif ($items['c'] > 0) {
                    $error = sprintf(t('lc_loc_has_items'), $items['c']);
                } else {
                    $row = $db->selectOne("SELECT name FROM standorte WHERE id = ?", [$id]);
                    $db->execute("DELETE FROM standorte WHERE id = ?", [$id]);
                    if ($row) logActivity('deleted', 'standorte', $id, $row['name']);
                    $_SESSION['vs_flash'] = t('lc_loc_deleted');
                    header('Location: locations.php?tab=standorte'); exit;
                }
            } catch (PDOException $e) { error_log('locations.php: ' . $e->getMessage()); $error = t('be_save_failed'); }
        }
    }

    // POSITIONEN (positionen-Tabelle)
    if ($action === 'add_position') {
        $standort_id  = intval($_POST['standort_id'] ?? 0);
        $name         = trim($_POST['name'] ?? '');
        $beschreibung = trim($_POST['beschreibung'] ?? '');
        if (empty($name) || $standort_id <= 0) { $error = t('lc_pos_required'); }
        else {
            try {
                if ($db->selectOne("SELECT id FROM positionen WHERE raum_id = ? AND name = ?", [$standort_id, $name])) {
                    $error = t('lc_pos_exists');
                } else {
                    $id = $db->insert("INSERT INTO positionen (raum_id, name, beschreibung) VALUES (?, ?, ?)", [$standort_id, $name, $beschreibung ?: null]);
                    logActivity('created', 'positionen', $id, $name);
                    $_SESSION['vs_flash'] = t('lc_pos_added');
                    header('Location: locations.php?tab=positionen'); exit;
                }
            } catch (PDOException $e) { error_log('locations.php: ' . $e->getMessage()); $error = t('be_save_failed'); }
        }
    }
    if ($action === 'edit_position') {
        $id           = intval($_POST['id'] ?? 0);
        $standort_id  = intval($_POST['standort_id'] ?? 0);
        $name         = trim($_POST['name'] ?? '');
        $beschreibung = trim($_POST['beschreibung'] ?? '');
        if (empty($name) || $standort_id <= 0 || $id <= 0) { $error = t('be_invalid_input'); }
        else {
            try {
                $old = $db->selectOne("SELECT name FROM positionen WHERE id = ?", [$id]);
                $db->execute("UPDATE positionen SET raum_id = ?, name = ?, beschreibung = ? WHERE id = ?", [$standort_id, $name, $beschreibung ?: null, $id]);
                if ($old) logActivity('updated', 'positionen', $id, $name, ['name' => $old['name']], ['name' => $name]);
                $_SESSION['vs_flash'] = t('lc_pos_updated');
                header('Location: locations.php?tab=positionen'); exit;
            } catch (PDOException $e) { error_log('locations.php: ' . $e->getMessage()); $error = t('be_save_failed'); }
        }
    }
    if ($action === 'delete_position') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) { $error = t('loc_invalid_id'); }
        else {
            try {
                $inUse = $db->selectOne("SELECT COUNT(*) as c FROM wertsachen WHERE position_id = ?", [$id]);
                if ($inUse['c'] > 0) {
                    $error = sprintf(t('loc_err_position_items_used'), $inUse['c']);
                } else {
                    $row = $db->selectOne("SELECT name FROM positionen WHERE id = ?", [$id]);
                    $db->execute("DELETE FROM positionen WHERE id = ?", [$id]);
                    if ($row) logActivity('deleted', 'positionen', $id, $row['name']);
                    $_SESSION['vs_flash'] = t('lc_pos_deleted');
                    header('Location: locations.php?tab=positionen'); exit;
                }
            } catch (PDOException $e) { error_log('locations.php: ' . $e->getMessage()); $error = t('be_save_failed'); }
        }
    }
}

// ── Daten laden ───────────────────────────────────────────────────────────────
try {
    $alle_raeume = $db->select("
        SELECT r.*, COUNT(w.id) as usage_count, COALESCE(SUM(w.preis),0) as total_value
        FROM raeume r
        LEFT JOIN wertsachen w ON r.id = w.raum_id
        GROUP BY r.id ORDER BY r.name ASC
    ");
} catch (Exception $e) { $alle_raeume = []; }

$standort_tabellen_vorhanden = false;
try {
    $db->selectOne("SELECT 1 FROM standorte LIMIT 1");
    $standort_tabellen_vorhanden = true;
} catch (Exception $e) {}

$alle_standorte = []; $alle_positionen = [];
if ($standort_tabellen_vorhanden) {
    try {
        $alle_standorte = $db->select("
            SELECT s.*, COUNT(p.id) as position_count
            FROM standorte s
            LEFT JOIN positionen p ON s.id = p.raum_id
            GROUP BY s.id ORDER BY s.name ASC
        ");
    } catch (Exception $e) {}
    try {
        $alle_positionen = $db->select("
            SELECT p.*, s.name as standort_name, COUNT(w.id) as usage_count
            FROM positionen p
            LEFT JOIN standorte s ON p.raum_id = s.id
            LEFT JOIN wertsachen w ON w.position_id = p.id
            GROUP BY p.id ORDER BY s.name ASC, p.name ASC
        ");
    } catch (Exception $e) {}
}

$stats = [
    'raeume'       => count($alle_raeume),
    'raeume_used'  => count(array_filter($alle_raeume,   fn($r) => $r['usage_count'] > 0)),
    'standorte'    => $standort_tabellen_vorhanden ? count($alle_standorte)   : '—',
    'positionen'   => $standort_tabellen_vorhanden ? count($alle_positionen)  : '—',
    'pos_used'     => $standort_tabellen_vorhanden ? count(array_filter($alle_positionen, fn($p) => $p['usage_count'] > 0)) : 0,
];

include 'layout/header_next_page.php';
?>

<main class="backend-main">

<?php if ($message): ?>
    <div class="alert alert-success"><strong><i class="ti ti-circle-check"></i> <?php echo t('be_success_title'); ?></strong> <?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><strong><i class="ti ti-circle-x"></i> <?php echo t('usr_error_title'); ?></strong> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Statistik -->
<div class="dashboard-grid" style="margin-bottom:24px;">
    <div class="widget-card">
        <div class="widget-header"><div>
            <div class="widget-value"><?php echo $stats['raeume']; ?></div>
            <div class="widget-label">🚪 <?php echo t('loc_rooms_total'); ?></div>
        </div><div class="widget-icon">🚪</div></div>
    </div>
    <div class="widget-card">
        <div class="widget-header"><div>
            <div class="widget-value" style="color:var(--vs-success);"><?php echo $stats['raeume_used']; ?></div>
            <div class="widget-label"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> <?php echo t('loc_rooms_in_use'); ?></div>
        </div><div class="widget-icon"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i></div></div>
    </div>
    <div class="widget-card">
        <div class="widget-header"><div>
            <div class="widget-value"><?php echo $stats['standorte']; ?></div>
            <div class="widget-label">📦 <?php echo t('loc_locations_label'); ?></div>
        </div><div class="widget-icon">📦</div></div>
    </div>
    <div class="widget-card">
        <div class="widget-header"><div>
            <div class="widget-value"><?php echo $stats['positionen']; ?></div>
            <div class="widget-label">📌 <?php echo t('loc_positions_label'); ?> <small style="color:var(--vs-muted);font-size:11px;"><?php echo sprintf(t('lc_used_count'), (int)$stats['pos_used']); ?></small></div>
        </div><div class="widget-icon">📌</div></div>
    </div>
</div>

<!-- Tabs -->
<div class="loc-tabs">
    <a href="?tab=raeume"     class="loc-tab <?php echo $activeTab === 'raeume'     ? 'active' : ''; ?>"><?php echo '🚪 ' . (t('loc_tab_rooms')); ?></a>
    <?php if ($standort_tabellen_vorhanden): ?>
    <a href="?tab=standorte"  class="loc-tab <?php echo $activeTab === 'standorte'  ? 'active' : ''; ?>"><?php echo '📦 ' . (t('loc_tab_locations')); ?></a>
    <a href="?tab=positionen" class="loc-tab <?php echo $activeTab === 'positionen' ? 'active' : ''; ?>"><?php echo '📌 ' . (t('loc_tab_positions')); ?></a>
    <?php endif; ?>
</div>

<!-- ── Tab: Räume ─────────────────────────────────────────────────────────── -->
<?php if ($activeTab === 'raeume'): ?>
<div class="activity-timeline">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>🚪 <?php echo t('loc_all_rooms'); ?></h2>
        <button onclick="showModal('addRaumModal')" class="vs-btn vs-btn-primary"><i class="ti ti-plus"></i> <?php echo t('loc_new_room'); ?></button>
    </div>
    <?php if (empty($alle_raeume)): ?>
        <div style="text-align:center; padding:40px; color:#999;"><div style="font-size:48px;">🚪</div><p><?php echo t('loc_no_rooms'); ?></p></div>
    <?php else: ?>
    <table class="backend-table">
        <thead><tr><th>ID</th><th><?php echo t('loc_room_name'); ?></th><th><?php echo t('loc_usage'); ?></th><th><?php echo t('col_gesamtwert'); ?></th><th style="width:120px;"><?php echo t('tab_actions'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($alle_raeume as $r): ?>
            <tr>
                <td><strong>#<?php echo $r['id']; ?></strong></td>
                <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                <td><?php if ($r['usage_count'] > 0): ?><span class="badge badge-success"><?php echo $r['usage_count']; ?> <?php echo t('loc_items_count'); ?></span><?php else: ?><span class="badge badge-secondary"><?php echo t('loc_not_used'); ?></span><?php endif; ?></td>
                <td><?php if ($r['total_value'] > 0): ?><strong style="color:var(--vs-success);"><?php echo formatPrice($r['total_value']); ?></strong><?php else: ?><span style="color:#999;">—</span><?php endif; ?></td>
                <td>
                    <button onclick='editRaum(<?php echo json_encode(['id'=>$r['id'],'name'=>$r['name']]); ?>)' class="vs-btn vs-btn-sm" style="padding:8px 10px; font-size:16px;"><i class="ti ti-pencil"></i></button>
                    <button onclick="deleteItem('raum', <?php echo $r['id']; ?>, '<?php echo htmlspecialchars($r['name'], ENT_QUOTES); ?>', <?php echo $r['usage_count']; ?>)" class="vs-btn vs-btn-sm vs-btn-danger" style="padding:8px 10px; font-size:16px;"><i class="ti ti-trash"></i></button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ── Tab: Standorte ────────────────────────────────────────────────────── -->
<?php elseif ($activeTab === 'standorte'): ?>
<div class="activity-timeline">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>📦 <?php echo t('loc_all_locations'); ?></h2>
        <button onclick="showModal('addStandortModal')" class="vs-btn vs-btn-primary"><i class="ti ti-plus"></i> <?php echo t('loc_new_location'); ?></button>
    </div>
    <?php if (empty($alle_standorte)): ?>
        <div style="text-align:center; padding:40px; color:#999;"><div style="font-size:48px;">📦</div><p><?php echo t('loc_no_locations'); ?></p><p style="font-size:13px; margin-top:5px;"><?php echo esc(t('lc_loc_hint')); ?></p></div>
    <?php else: ?>
    <table class="backend-table">
        <thead><tr><th>ID</th><th><?php echo t('loc_location_name'); ?></th><th><?php echo t('loc_positions'); ?></th><th style="width:120px;"><?php echo t('tab_actions'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($alle_standorte as $s): ?>
            <tr>
                <td><strong>#<?php echo $s['id']; ?></strong></td>
                <td><strong><?php echo htmlspecialchars($s['name']); ?></strong></td>
                <td><?php if ($s['position_count'] > 0): ?><span class="badge badge-success"><?php echo $s['position_count']; ?> <?php echo t('loc_positions_label'); ?></span><?php else: ?><span class="badge badge-secondary"><?php echo t('loc_none'); ?></span><?php endif; ?></td>
                <td>
                    <button onclick='editStandort(<?php echo json_encode(['id'=>$s['id'],'name'=>$s['name']]); ?>)' class="vs-btn vs-btn-sm" style="padding:8px 10px; font-size:16px;"><i class="ti ti-pencil"></i></button>
                    <button onclick="deleteItem('standort', <?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['name'], ENT_QUOTES); ?>', <?php echo $s['position_count']; ?>)" class="vs-btn vs-btn-sm vs-btn-danger" style="padding:8px 10px; font-size:16px;"><i class="ti ti-trash"></i></button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ── Tab: Positionen ───────────────────────────────────────────────────── -->
<?php elseif ($activeTab === 'positionen'): ?>
<div class="activity-timeline">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>📌 <?php echo t('loc_all_positions'); ?></h2>
        <button onclick="showModal('addPositionModal')" class="vs-btn vs-btn-primary" <?php echo empty($alle_standorte) ? 'disabled title="Bitte zuerst einen Standort anlegen"' : ''; ?>><i class="ti ti-plus"></i> <?php echo t('loc_new_position'); ?></button>
    </div>
    <?php if (empty($alle_positionen)): ?>
        <div style="text-align:center; padding:40px; color:#999;"><div style="font-size:48px;">📌</div><p><?php echo t('loc_no_positions'); ?></p><p style="font-size:13px; margin-top:5px;"><?php echo esc(t('lc_pos_hint')); ?></p></div>
    <?php else: ?>
    <table class="backend-table">
        <thead><tr><th>ID</th><th><?php echo t('loc_location'); ?></th><th><?php echo t('loc_position_name'); ?></th><th><?php echo t('field_notes'); ?></th><th><?php echo t('loc_usage'); ?></th><th style="width:120px;"><?php echo t('tab_actions'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($alle_positionen as $p): ?>
            <tr>
                <td><strong>#<?php echo $p['id']; ?></strong></td>
                <td><span class="badge badge-secondary"><?php echo htmlspecialchars($p['standort_name'] ?? '—'); ?></span></td>
                <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                <td style="color:#888; font-size:13px;"><?php echo htmlspecialchars($p['beschreibung'] ?? '—'); ?></td>
                <td><?php if ($p['usage_count'] > 0): ?><span class="badge badge-success"><?php echo $p['usage_count']; ?> <?php echo t('loc_items_count'); ?></span><?php else: ?><span class="badge badge-secondary"><?php echo t('loc_not_used'); ?></span><?php endif; ?></td>
                <td>
                    <button onclick='editPosition(<?php echo json_encode(['id'=>$p['id'],'standort_id'=>$p['raum_id'],'name'=>$p['name'],'beschreibung'=>$p['beschreibung']]); ?>)' class="vs-btn vs-btn-sm" style="padding:8px 10px; font-size:16px;"><i class="ti ti-pencil"></i></button>
                    <button onclick="deleteItem('position', <?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>', <?php echo $p['usage_count']; ?>)" class="vs-btn vs-btn-sm vs-btn-danger" style="padding:8px 10px; font-size:16px;"><i class="ti ti-trash"></i></button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>

</main>

<!-- ── Modal: Neuer Raum ──────────────────────────────────────────────────── -->
<div id="addRaumModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3><i class="ti ti-plus"></i><?php echo t('loc_new_room'); ?></h3><span class="modal-close" onclick="closeModal('addRaumModal')">&times;</span></div>
        <form method="POST" class="modal-body">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="action" value="add_raum">
            <input type="hidden" name="tab" value="raeume">
            <div class="form-group">
                <label><?php echo t('loc_room_name'); ?> *</label>
                <input type="text" name="name" required class="form-control" placeholder="<?php echo esc(t('lc_room_ph')); ?>">
            </div>

            <!-- Optionaler Standort -->
            <div style="border-top:1px solid var(--vs-border,#e2e8f0); margin:16px 0 0; padding-top:14px;">
                <button type="button" onclick="toggleOptional(this)"
                        style="background:none; border:none; cursor:pointer; color:var(--vs-accent,#185fa5); font-size:13px; font-weight:600; padding:0; display:flex; align-items:center; gap:6px;">
                    <i class="ti ti-plus" id="optionalIcon"></i> <?php echo t('lc_add_location_now'); ?> <span style="color:#999; font-weight:400;"><?php echo t('be_optional'); ?></span>
                </button>
                <div id="optionalFields" style="display:none; margin-top:14px;">
                    <div class="form-group">
                        <label><?php echo t('loc_location_name'); ?> <span style="color:#999;"><?php echo t('be_optional'); ?></span></label>
                        <input type="text" name="standort_name" class="form-control" placeholder="<?php echo esc(t('lc_loc_ph')); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php echo t('loc_position_name'); ?> <span style="color:#999;"><?php echo t('lc_optional_with_loc'); ?></span></label>
                        <input type="text" name="position_name" class="form-control" placeholder="<?php echo esc(t('lc_pos_ph')); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php echo t('lc_pos_desc_label'); ?> <span style="color:#999;"><?php echo t('be_optional'); ?></span></label>
                        <input type="text" name="position_beschreibung" class="form-control" placeholder="<?php echo esc(t('lc_pos_desc_ph')); ?>">
                    </div>
                </div>
            </div>

            <div class="modal-footer"><button type="button" onclick="closeModal('addRaumModal')" class="vs-btn vs-btn-secondary"><?php echo t('btn_cancel'); ?></button><button type="submit" class="vs-btn vs-btn-primary"><?php echo t('btn_create'); ?></button></div>
        </form>
    </div>
</div>

<!-- ── Modal: Raum bearbeiten ─────────────────────────────────────────────── -->
<div id="editRaumModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3><i class="ti ti-pencil"></i> <?php echo t('lc_edit_room'); ?></h3><span class="modal-close" onclick="closeModal('editRaumModal')">&times;</span></div>
        <form method="POST" class="modal-body">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="action" value="edit_raum">
            <input type="hidden" name="tab" value="raeume">
            <input type="hidden" name="id" id="edit_raum_id">
            <div class="form-group"><label><?php echo t('loc_room_name'); ?> *</label><input type="text" name="name" id="edit_raum_name" required class="form-control"></div>
            <div class="modal-footer"><button type="button" onclick="closeModal('editRaumModal')" class="vs-btn vs-btn-secondary"><?php echo t('btn_cancel'); ?></button><button type="submit" class="vs-btn vs-btn-primary"><?php echo t('btn_save'); ?></button></div>
        </form>
    </div>
</div>

<!-- ── Modal: Neuer Standort ─────────────────────────────────────────────── -->
<div id="addStandortModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3><i class="ti ti-plus"></i><?php echo t('loc_new_location'); ?></h3><span class="modal-close" onclick="closeModal('addStandortModal')">&times;</span></div>
        <form method="POST" class="modal-body">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="action" value="add_standort">
            <input type="hidden" name="tab" value="standorte">
            <div class="form-group"><label><?php echo t('loc_location_name'); ?> *</label><input type="text" name="name" required class="form-control" placeholder="<?php echo esc(t('lc_loc_ph2')); ?>"></div>
            <div class="modal-footer"><button type="button" onclick="closeModal('addStandortModal')" class="vs-btn vs-btn-secondary"><?php echo t('btn_cancel'); ?></button><button type="submit" class="vs-btn vs-btn-primary"><?php echo t('btn_create'); ?></button></div>
        </form>
    </div>
</div>

<!-- ── Modal: Standort bearbeiten ────────────────────────────────────────── -->
<div id="editStandortModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3><i class="ti ti-pencil"></i> <?php echo t('lc_edit_loc'); ?></h3><span class="modal-close" onclick="closeModal('editStandortModal')">&times;</span></div>
        <form method="POST" class="modal-body">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="action" value="edit_standort">
            <input type="hidden" name="tab" value="standorte">
            <input type="hidden" name="id" id="edit_standort_id">
            <div class="form-group"><label><?php echo t('loc_location_name'); ?> *</label><input type="text" name="name" id="edit_standort_name" required class="form-control"></div>
            <div class="modal-footer"><button type="button" onclick="closeModal('editStandortModal')" class="vs-btn vs-btn-secondary"><?php echo t('btn_cancel'); ?></button><button type="submit" class="vs-btn vs-btn-primary"><?php echo t('btn_save'); ?></button></div>
        </form>
    </div>
</div>

<!-- ── Modal: Neue Position ───────────────────────────────────────────────── -->
<div id="addPositionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3><i class="ti ti-plus"></i><?php echo t('loc_new_position'); ?></h3><span class="modal-close" onclick="closeModal('addPositionModal')">&times;</span></div>
        <form method="POST" class="modal-body">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="action" value="add_position">
            <input type="hidden" name="tab" value="positionen">
            <div class="form-group">
                <label><?php echo t('loc_location'); ?> *</label>
                <select name="standort_id" required class="form-control">
                    <option value=""><?php echo t('lc_choose_loc'); ?></option>
                    <?php foreach ($alle_standorte as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label><?php echo t('loc_position_name'); ?> *</label><input type="text" name="name" required class="form-control" placeholder="<?php echo esc(t('lc_pos_ph')); ?>"></div>
            <div class="form-group"><label><?php echo t('be_description'); ?> <span style="color:#999;"><?php echo t('be_optional'); ?></span></label><input type="text" name="beschreibung" class="form-control" placeholder="<?php echo esc(t('lc_pos_desc_ph2')); ?>"></div>
            <div class="modal-footer"><button type="button" onclick="closeModal('addPositionModal')" class="vs-btn vs-btn-secondary"><?php echo t('btn_cancel'); ?></button><button type="submit" class="vs-btn vs-btn-primary"><?php echo t('btn_create'); ?></button></div>
        </form>
    </div>
</div>

<!-- ── Modal: Position bearbeiten ────────────────────────────────────────── -->
<div id="editPositionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3><i class="ti ti-pencil"></i> <?php echo t('lc_edit_pos'); ?></h3><span class="modal-close" onclick="closeModal('editPositionModal')">&times;</span></div>
        <form method="POST" class="modal-body">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="action" value="edit_position">
            <input type="hidden" name="tab" value="positionen">
            <input type="hidden" name="id" id="edit_pos_id">
            <div class="form-group">
                <label><?php echo t('loc_location'); ?> *</label>
                <select name="standort_id" id="edit_pos_standort_id" required class="form-control">
                    <?php foreach ($alle_standorte as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label><?php echo t('loc_position_name'); ?> *</label><input type="text" name="name" id="edit_pos_name" required class="form-control"></div>
            <div class="form-group"><label><?php echo t('be_description'); ?> <span style="color:#999;"><?php echo t('be_optional'); ?></span></label><input type="text" name="beschreibung" id="edit_pos_beschreibung" class="form-control"></div>
            <div class="modal-footer"><button type="button" onclick="closeModal('editPositionModal')" class="vs-btn vs-btn-secondary"><?php echo t('btn_cancel'); ?></button><button type="submit" class="vs-btn vs-btn-primary"><?php echo t('btn_save'); ?></button></div>
        </form>
    </div>
</div>

<!-- ── Lösch-Formulare ────────────────────────────────────────────────────── -->
<form id="deleteRaumForm"     method="POST" style="display:none;"><?php echo Security::getCSRFInput(); ?><input type="hidden" name="action" value="delete_raum">    <input type="hidden" name="tab" value="raeume">    <input type="hidden" name="id" id="del_raum_id"></form>
<form id="deleteStandortForm" method="POST" style="display:none;"><?php echo Security::getCSRFInput(); ?><input type="hidden" name="action" value="delete_standort"><input type="hidden" name="tab" value="standorte"> <input type="hidden" name="id" id="del_standort_id"></form>
<form id="deletePositionForm" method="POST" style="display:none;"><?php echo Security::getCSRFInput(); ?><input type="hidden" name="action" value="delete_position"><input type="hidden" name="tab" value="positionen"><input type="hidden" name="id" id="del_position_id"></form>

<!-- ── Bestätigungs-Modal ─────────────────────────────────────────────────── -->
<div id="confirmModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:var(--vs-surface); border-radius:16px; padding:28px; max-width:360px; width:100%; box-shadow:0 16px 48px rgba(0,0,0,0.2); text-align:center;">
        <div style="font-size:36px; margin-bottom:12px;"><i class="ti ti-trash"></i></div>
        <h3 style="margin:0 0 8px;" id="confirmTitle"><?php echo t('be_delete_q'); ?></h3>
        <p id="confirmName" style="color:var(--vs-text-muted); margin:0 0 24px; font-size:14px;"></p>
        <div style="display:flex; gap:10px; justify-content:center;">
            <button onclick="closeConfirm()" class="vs-btn vs-btn-secondary" style="padding:10px 24px;"><?php echo t('btn_cancel'); ?></button>
            <button id="confirmBtn" style="padding:10px 24px; background:var(--vs-danger); color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:14px;"><?php echo t('btn_delete'); ?></button>
        </div>
    </div>
</div>

<style>
.alert { padding:15px 20px; border-radius:8px; margin-bottom:20px; border-left:4px solid; }
.alert-success { background:var(--vs-success-light); border-color:var(--vs-success); color:var(--vs-success-text); }
.alert-danger  { background:var(--vs-danger-light);  border-color:var(--vs-danger);  color:var(--vs-danger-text); }
.loc-tabs { display:flex; gap:4px; margin-bottom:20px; border-bottom:2px solid var(--vs-border,#e2e8f0); }
.loc-tab { padding:10px 20px; border-radius:6px 6px 0 0; text-decoration:none; font-size:14px; font-weight:500; color:var(--vs-text-muted,#64748b); border:2px solid transparent; border-bottom:none; margin-bottom:-2px; transition:all .15s; }
.loc-tab:hover { color:var(--vs-accent,#185fa5); background:var(--vs-bg,#f1f5f9); }
.loc-tab.active { color:var(--vs-accent,#185fa5); background:var(--vs-surface,#fff); border-color:var(--vs-border,#e2e8f0); font-weight:600; }
.backend-table { width:100%; border-collapse:collapse; margin-top:15px; }
.backend-table thead tr { background:var(--bg-color); border-bottom:2px solid var(--border-color); }
.backend-table th, .backend-table td { padding:12px; text-align:left; }
.backend-table tbody tr { border-bottom:1px solid var(--border-color); transition:background 0.2s; }
.backend-table tbody tr:hover { background:var(--bg-color); }
.badge { padding:4px 10px; border-radius:12px; font-size:11px; font-weight:600; display:inline-block; }
.badge-success   { background:var(--vs-success-light); color:var(--vs-success-text); }
.badge-secondary { background:var(--vs-border); color:#666; }
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; align-items:center; justify-content:center; }
.modal.active { display:flex; }
.modal-content { background:var(--vs-surface); border-radius:12px; width:90%; max-width:500px; max-height:90vh; overflow-y:auto; box-shadow:0 10px 40px rgba(0,0,0,0.3); }
.modal-header { padding:20px 25px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; }
.modal-header h3 { margin:0; }
.modal-close { font-size:28px; cursor:pointer; color:var(--text-muted); line-height:1; }
.modal-body { padding:25px; }
.modal-footer { padding:15px 25px; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:10px; }
.form-group { margin-bottom:20px; }
.form-group label { display:block; margin-bottom:8px; font-weight:600; }
.form-control { width:100%; padding:10px 12px; border:1px solid var(--border-color); border-radius:6px; font-size:14px; }
.form-control:focus { outline:none; border-color:var(--primary-color); box-shadow:0 0 0 3px rgba(102,126,234,0.1); }
</style>

<script>
function showModal(id)  { document.getElementById(id).classList.add('active'); }
function toggleOptional(btn) {
    var fields = document.getElementById('optionalFields');
    var icon   = document.getElementById('optionalIcon');
    var open   = fields.style.display === 'none';
    fields.style.display = open ? 'block' : 'none';
    icon.className = open ? 'ti ti-minus' : 'ti ti-plus';
}
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function closeConfirm() { document.getElementById('confirmModal').style.display = 'none'; }

function editRaum(r) {
    document.getElementById('edit_raum_id').value   = r.id;
    document.getElementById('edit_raum_name').value = r.name;
    showModal('editRaumModal');
}
function editStandort(s) {
    document.getElementById('edit_standort_id').value   = s.id;
    document.getElementById('edit_standort_name').value = s.name;
    showModal('editStandortModal');
}
function editPosition(p) {
    document.getElementById('edit_pos_id').value           = p.id;
    document.getElementById('edit_pos_standort_id').value  = p.standort_id;
    document.getElementById('edit_pos_name').value         = p.name;
    document.getElementById('edit_pos_beschreibung').value = p.beschreibung || '';
    showModal('editPositionModal');
}

function deleteItem(type, id, name, count) {
    var limits = { raum: <?php echo json_encode(t('loc_err_room_in_use') ?: 'Dieser Raum wird noch verwendet'); ?>, standort: <?php echo json_encode(t('loc_err_location_has_positions') ?: 'Dieser Standort enthält noch Positionen'); ?>, position: <?php echo json_encode(t('loc_err_position_in_use') ?: 'Diese Position wird noch verwendet'); ?> };
    if (count > 0) { vsAlert(<?php echo json_encode(t('loc_err_cannot_delete')); ?>.replace('%s', function () { return limits[type]; })); return; }
    var titles = <?php echo json_encode(['raum' => t('lc_del_room'), 'standort' => t('lc_del_loc'), 'position' => t('lc_del_pos')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var forms  = { raum: 'deleteRaumForm', standort: 'deleteStandortForm', position: 'deletePositionForm' };
    var ids    = { raum: 'del_raum_id', standort: 'del_standort_id', position: 'del_position_id' };
    document.getElementById('confirmTitle').textContent = titles[type];
    document.getElementById('confirmName').textContent  = '"' + name + '"';
    document.getElementById('confirmBtn').onclick = function() {
        document.getElementById(ids[type]).value = id;
        document.getElementById(forms[type]).submit();
    };
    document.getElementById('confirmModal').style.display = 'flex';
}

window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) e.target.classList.remove('active');
    if (e.target.id === 'confirmModal') closeConfirm();
});
</script>

<?php include 'layout/footer_next_page.php'; ?>
