<?php
require_once 'db.php';
// index.php - Übersicht mit Spalten-Auswahl - MIT ÜBERSETZUNGEN
require_once 'db.php';
require_once 'helpers.php';
require_once 'helpers_pagination.php';
require_once 'helpers_search.php';
requireLogin();


// ── AJAX: Drag & Drop Reihenfolge speichern ──────────────────
if (isset($_POST['action']) && $_POST['action'] === 'save_order' && canEdit()) {
    while (ob_get_level()) ob_end_clean(); // Sauberer Output
    header('Content-Type: application/json; charset=utf-8');
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCSRFToken($token)) {
        echo json_encode(['success' => false, 'error' => 'CSRF']);
        exit;
    }
    $ids = array_map('intval', $_POST['ids'] ?? []);
    try {
        foreach ($ids as $pos => $id) {
            if ($id > 0) {
                $db->execute("UPDATE wertsachen SET sort_order = ? WHERE id = ?", [$pos + 1, $id]);
            }
        }
    } catch (PDOException $e) {
        // Bis 4.3.17 meldete diese Stelle unabhaengig vom Ergebnis
        // success:true - die Oberflaeche behielt die neue Reihenfolge bei,
        // die Datenbank nicht.
        error_log('save_order: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => t('error_generic')]);
        exit;
    }
    echo json_encode(['success' => true, 'count' => count($ids)]);
    exit;
}

define('PAGE_TITLE', t('page_overview') . ' - ' . t('app_title'));

// Pagination Parameter
$items_per_page = filter_var($_GET['per_page'] ?? 20, FILTER_VALIDATE_INT) ?: 20;
$items_per_page = max(10, min(100, $items_per_page)); // Min 10, Max 100
$current_page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;

// Spalten-Auswahl laden
$spaltenAuswahl = getUserSpaltenAuswahl();
$verfuegbareSpalten = getAvailableSpalten();
$presets = getSpaltenPresets();

// ERWEITERTE SUCHE: Filter aus Request holen
$currentFilters = getFiltersFromRequest();

// Sortierung
if (isset($_GET['sort'])) $_SESSION['sort_column'] = $_GET['sort'];
if (isset($_GET['order'])) $_SESSION['sort_order'] = $_GET['order'];
$sort_column = $_SESSION['sort_column'] ?? 'sort_order';
$sort_order  = $_SESSION['sort_order']  ?? 'ASC';
$is_manual_sort = ($sort_column === 'sort_order');

// Validierung der Sortierung
$allowed_columns = ['name', 'raum_name', 'kategorie_name', 'kaufdatum', 'preis', 'erstellt_von'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'name';
}
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'ASC';
}

// Ansichts-Modus: table | room
if (isset($_GET['view'])) {
    $_SESSION['index_view_mode'] = $_GET['view'];
}
$view_mode = $_SESSION['index_view_mode'] ?? 'masonry';

// SQL-Query mit ERWEITERTEN FILTERN aufbauen
$filter_raum     = (int)($_GET['filter_raum']     ?? 0);
$filter_position = (int)($_GET['filter_position'] ?? 0);

// Return-URL für edit.php aufbauen (erhält aktive Filter nach dem Speichern)
$_returnParams = array_filter([
    'search'      => $_GET['search']   ?? '',
    'ort'         => $_GET['ort']      ?? '',
    'price_min'   => $_GET['price_min'] ?? '',
    'price_max'   => $_GET['price_max'] ?? '',
    'date_from'   => $_GET['date_from'] ?? '',
    'date_to'     => $_GET['date_to']   ?? '',
    'has_image'   => !empty($_GET['has_image'])  ? '1' : '',
    'has_docs'    => !empty($_GET['has_docs'])   ? '1' : '',
    'filter_raum' => $filter_raum ?: '',
    'filter_position' => $filter_position ?: '',
    'sort'        => $_GET['sort']     ?? '',
    'order'       => $_GET['order']    ?? '',
    'per_page'    => ($_GET['per_page'] ?? 20) != 20 ? ($_GET['per_page'] ?? '') : '',
    'page'        => ($current_page > 1) ? $current_page : '',
], fn($v) => $v !== '' && $v !== null && $v !== false);
// Kategorie-Array separat (http_build_query unterstützt Arrays)
$_katIds = array_filter(array_map('intval', (array)($_GET['kategorie'] ?? [])));
$_returnUrl = 'index.php';
if (!empty($_returnParams) || !empty($_katIds)) {
    $qParts = http_build_query($_returnParams);
    foreach ($_katIds as $kid) { $qParts .= ($qParts ? '&' : '') . 'kategorie[]=' . $kid; }
    $_returnUrl .= '?' . $qParts;
}
$editReturnParam = '&return_url=' . urlencode($_returnUrl);

// Only-own-items prüfen
$onlyOwnItems = false;
try {
    $ownSetting = $db->selectOne("SELECT setting_value FROM app_settings WHERE setting_key = 'only_own_items'");
    if (($ownSetting['setting_value'] ?? '0') === '1') {
        $currentUserOwn = $db->selectOne("SELECT role, sieht_alle FROM users WHERE username = ?", [$_SESSION['username'] ?? '']);
        if ($currentUserOwn && $currentUserOwn['role'] !== 'admin' && !$currentUserOwn['sieht_alle']) {
            $onlyOwnItems = true;
        }
    }
} catch (Exception $e) {}

$sql = "SELECT w.*, o.name as raum_name, k.name as kategorie_name,
               sp.name AS standort_position_name, sr.name AS standort_name
        FROM wertsachen w 
        LEFT JOIN raeume o ON w.raum_id = o.id 
        LEFT JOIN kategorien k ON w.kategorie_id = k.id
        LEFT JOIN positionen sp ON w.position_id = sp.id
        LEFT JOIN standorte sr ON sr.id = COALESCE(sp.raum_id, w.standort_id)
        WHERE 1=1";

// Filter für verborgene Items (Standard: nur sichtbare anzeigen)
$showHidden = isset($_GET['show_hidden']) && $_GET['show_hidden'] === '1';
if (!$showHidden) {
    $sql .= " AND (w.hidden = 0 OR w.hidden IS NULL)";
}

$params = [];

// Only-own-items Filter
if ($onlyOwnItems) {
    $sql .= " AND w.erstellt_von = ?";
    $params[] = $_SESSION['username'] ?? '';
}

// Erweiterte Filter hinzufügen
$searchConditions = buildSearchQuery($currentFilters, $params);
foreach ($searchConditions as $condition) {
    $sql .= " AND " . $condition;
}

// Raum-Filter
if ($filter_raum) {
    $sql .= " AND w.raum_id = ?";
    $params[] = $filter_raum;
}
if ($filter_position) {
    $sql .= " AND w.position_id = ?";
    $params[] = $filter_position;
}

// Sortierung hinzufügen
if ($sort_column === 'sort_order') {
    $sql .= " ORDER BY w.sort_order ASC";
} else {
    $sql .= " ORDER BY " . ($sort_column === 'name' ? 'w.name' : $sort_column) . " " . $sort_order;
}

try {
    // ERST: Gesamtzahl für Pagination berechnen (mit erweiterten Filtern)
    $count_sql = "SELECT COUNT(*) as total FROM wertsachen w
                  LEFT JOIN positionen sp ON w.position_id = sp.id
                  LEFT JOIN standorte sr ON sr.id = COALESCE(sp.raum_id, w.standort_id)
                  WHERE 1=1";
    
    // Hidden-Filter auch für Count
    if (!$showHidden) {
        $count_sql .= " AND (w.hidden = 0 OR w.hidden IS NULL)";
    }

    // Only-own Filter auch für Count
    if ($onlyOwnItems) {
        $count_sql .= " AND w.erstellt_von = ?";
    }
    
    $count_params = $onlyOwnItems ? [$_SESSION['username'] ?? ''] : [];
    
    // Erweiterte Filter auch für Count
    $countConditions = buildSearchQuery($currentFilters, $count_params);
    foreach ($countConditions as $condition) {
        $count_sql .= " AND " . $condition;
    }

    // Standort-Filter auch für Count
    if ($filter_raum) {
        $count_sql .= " AND w.raum_id = ?";
        $count_params[] = $filter_raum;
    }
    if ($filter_position) {
        $count_sql .= " AND w.position_id = ?";
        $count_params[] = $filter_position;
    }
    
    $total_items = $db->selectOne($count_sql, $count_params)['total'] ?? 0;
    
    // Pagination berechnen
    $pagination = calculatePagination($total_items, $items_per_page, $current_page);

    // paginationParams hier aufbauen — VOR erster Verwendung in renderPagination
    $paginationParams = [];
    if (!empty($currentFilters['search'])) $paginationParams['search'] = $currentFilters['search'];
    if (!empty($currentFilters['ort'])) $paginationParams['ort'] = $currentFilters['ort'];
    if (!empty($currentFilters['kategorie'])) {
        foreach ($currentFilters['kategorie'] as $kid) {
            $paginationParams['kategorie'][] = $kid;
        }
    }
    if (!empty($currentFilters['price_min'])) $paginationParams['price_min'] = $currentFilters['price_min'];
    if (!empty($currentFilters['price_max'])) $paginationParams['price_max'] = $currentFilters['price_max'];
    if (!empty($currentFilters['date_from'])) $paginationParams['date_from'] = $currentFilters['date_from'];
    if (!empty($currentFilters['date_to'])) $paginationParams['date_to'] = $currentFilters['date_to'];
    if ($currentFilters['has_image']) $paginationParams['has_image'] = 1;
    if ($currentFilters['has_docs']) $paginationParams['has_docs'] = 1;
    if ($sort_column !== 'name') $paginationParams['sort'] = $sort_column;
    if ($sort_order !== 'ASC') $paginationParams['order'] = $sort_order;
    if ($items_per_page != 20) $paginationParams['per_page'] = $items_per_page;
    if ($filter_raum)     $paginationParams['filter_raum']     = $filter_raum;
    if ($filter_position) $paginationParams['filter_position'] = $filter_position;

    // Räume für das Filter-Dropdown. Hier stand bis 4.3.28 zusätzlich eine
    // Positionsliste "WHERE raum_id = $filter_raum" - falsch, weil
    // positionen.raum_id die STANDORT-Nummer traegt (backend/locations.php),
    // und ohnehin tot: keine Auswahl zeigte sie an. filter_position aus der
    // Adresse wirkt weiter, direkt auf w.position_id.
    $raeume_filter = $db->select("SELECT id, name FROM raeume ORDER BY name");

    // JETZT: Daten mit LIMIT laden
    $sql .= " LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}";
    $wertsachen = $db->select($sql, $params);
    
    // Statistiken berechnen (basierend auf allen Ergebnissen mit Filtern)
    $total_count = $total_items;
    $total_value = 0;
    $avg_value = 0;
    $max_value = 0;
    
    if ($total_count > 0) {
        // Für Statistiken ALLE gefilterten Werte holen (ohne LIMIT)
        $stats_sql = "SELECT preis FROM wertsachen w WHERE 1=1";
        
        // Hidden-Filter auch für Stats
        if (!$showHidden) {
            $stats_sql .= " AND (w.hidden = 0 OR w.hidden IS NULL)";
        }

        // Only-own Filter auch für Stats
        if ($onlyOwnItems) {
            $stats_sql .= " AND w.erstellt_von = ?";
        }
        
        $stats_params = $onlyOwnItems ? [$_SESSION['username'] ?? ''] : [];
        
        // Erweiterte Filter auch für Stats
        $statsConditions = buildSearchQuery($currentFilters, $stats_params);
        foreach ($statsConditions as $condition) {
            $stats_sql .= " AND " . $condition;
        }
        
        $all_items = $db->select($stats_sql, $stats_params);
        foreach ($all_items as $item) {
            $total_value += floatval($item['preis']);
        }
        $avg_value = $total_value / $total_count;
        $max_value = max(array_column($all_items, 'preis'));
    }
    
    // Dokumente-Anzahl für jeden Gegenstand laden (wenn Feature aktiv)
    $user = $db->selectOne("SELECT dokumente_aktiv FROM users WHERE username = ?", [$_SESSION['username']]);
    $dokumente_aktiv = $user['dokumente_aktiv'] ?? 0;

    if ($dokumente_aktiv) {
        foreach ($wertsachen as &$item) {
            $doc_count = $db->selectOne("SELECT COUNT(*) as count FROM dokumente WHERE wertsache_id = ?", [$item['id']]);
            $item['dokumente_anzahl'] = $doc_count['count'] ?? 0;
        }
        unset($item);
    }

    // Bilder-Anzahl aus item_images — unabhängig vom Dokumente-Feature
    foreach ($wertsachen as &$item) {
        $img_count = $db->selectOne("SELECT COUNT(*) as count FROM item_images WHERE item_id = ?", [$item['id']]);
        $item['bilder_anzahl'] = $img_count['count'] ?? 0;
    }
    unset($item);

    $raeume = $db->select("SELECT * FROM raeume ORDER BY name");
    $kategorien = $db->select("SELECT * FROM kategorien ORDER BY name");
    
    // Kategorie-Counts für Schnellfilter (nur sichtbare Items)
    $kategorie_counts_raw = $db->select(
        "SELECT kategorie_id, COUNT(*) as cnt FROM wertsachen 
         WHERE (hidden = 0 OR hidden IS NULL) 
         GROUP BY kategorie_id"
    );
    $kategorie_counts = [];
    foreach ($kategorie_counts_raw as $row) {
        $kategorie_counts[$row['kategorie_id']] = $row['cnt'];
    }
} catch (PDOException $e) {
    Security::logSecurityEvent('data_load_error', ['error' => $e->getMessage()]);
    die(t('error_loading_data'));
}

// Bulk-Aktionen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    validateRequest();
    
    $action = $_POST['bulk_action'];
    $selected_ids = $_POST['selected_items'] ?? [];
    
    if (!empty($selected_ids) && is_array($selected_ids)) {
        $success_count = 0;
        
        try {
            switch ($action) {
                case 'delete':
                    if (canEdit()) {
                        foreach ($selected_ids as $id) {
                            $id = filter_var($id, FILTER_VALIDATE_INT);
                            if ($id) {
                                $item = $db->selectOne("SELECT bild FROM wertsachen WHERE id = ?", [$id]);
                                if ($item) {
                                    // Bild löschen
                                    if ($item['bild'] && file_exists(UPLOAD_DIR . $item['bild'])) {
                                        unlink(UPLOAD_DIR . $item['bild']);
                                    }
                                    // Dokumente löschen
                                    $docs = $db->select("SELECT dateiname FROM dokumente WHERE wertsache_id = ?", [$id]);
                                    foreach ($docs as $doc) {
                                        $docPath = __DIR__ . '/documents/' . $doc['dateiname'];
                                        if (file_exists($docPath)) {
                                            unlink($docPath);
                                        }
                                    }
                                    $db->execute("DELETE FROM dokumente WHERE wertsache_id = ?", [$id]);
                                    // Eintrag löschen
                                    $db->execute("DELETE FROM wertsachen WHERE id = ?", [$id]);
                                    $success_count++;
                                }
                            }
                        }
                        redirectWithMessage('index.php', sprintf(t('index_success_deleted'), $success_count));
                    }
                    break;
                    
                case 'change_category':
                    if (canEdit() && isset($_POST['new_category'])) {
                        $new_category = filter_var($_POST['new_category'], FILTER_VALIDATE_INT);
                        if ($new_category) {
                            foreach ($selected_ids as $id) {
                                $id = filter_var($id, FILTER_VALIDATE_INT);
                                if ($id) {
                                    $db->execute("UPDATE wertsachen SET kategorie_id = ? WHERE id = ?", [$new_category, $id]);
                                    $success_count++;
                                }
                            }
                            redirectWithMessage('index.php', sprintf(t('index_success_updated'), $success_count));
                        }
                    }
                    break;
                    
                case 'change_location':
                    if (canEdit() && isset($_POST['new_location'])) {
                        $new_location = filter_var($_POST['new_location'], FILTER_VALIDATE_INT);
                        if ($new_location) {
                            foreach ($selected_ids as $id) {
                                $id = filter_var($id, FILTER_VALIDATE_INT);
                                if ($id) {
                                    $db->execute("UPDATE wertsachen SET raum_id = ? WHERE id = ?", [$new_location, $id]);
                                    $success_count++;
                                }
                            }
                            redirectWithMessage('index.php', sprintf(t('index_success_updated'), $success_count));
                        }
                    }
                    break;

                case 'hide':
                    if (canEdit()) {
                        foreach ($selected_ids as $id) {
                            $id = filter_var($id, FILTER_VALIDATE_INT);
                            if ($id) {
                                $db->execute("UPDATE wertsachen SET hidden = 1 WHERE id = ?", [$id]);
                                $success_count++;
                            }
                        }
                        redirectWithMessage('index.php', sprintf(t('index_success_hidden'), $success_count));
                    }
                    break;

                case 'unhide':
                    if (canEdit()) {
                        foreach ($selected_ids as $id) {
                            $id = filter_var($id, FILTER_VALIDATE_INT);
                            if ($id) {
                                $db->execute("UPDATE wertsachen SET hidden = 0 WHERE id = ?", [$id]);
                                $success_count++;
                            }
                        }
                        redirectWithMessage('index.php', sprintf(t('index_success_visible'), $success_count));
                    }
                    break;
            }
        } catch (PDOException $e) {
            Security::logSecurityEvent('bulk_action_error', ['error' => $e->getMessage()]);
            redirectWithMessage('index.php', t('index_error_bulk_action'), 'error');
        }
    }
}


include 'header_next.php';
?>

<?php
// ── Inline-Styles für Legacy-Komponenten (Dropdown, Bulk-Bar, etc.) ──────
// Diese Styles stammen aus der Original-index.php und werden unverändert
// beibehalten, damit Bulk-Aktionen und Masonry korrekt funktionieren.
// Der alte Spalten-Waehler stand bis 4.3.10 ebenfalls hier; er ist
// entfallen, seit components/column_manager.php die Aufgabe uebernimmt.
?>
<style>
.bulk-actions-bar {
    position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
    background: var(--vs-accent); color: var(--vs-accent-text); padding: 12px 20px;
    border-radius: 12px; display: flex; align-items: center; gap: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 1000;
    min-width: 400px; flex-wrap: wrap;
}
.bulk-info { font-size: 14px; font-weight: 600; }
.bulk-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.bulk-actions select, .bulk-actions button { padding: 8px 14px; border-radius: 6px; font-size: 13px; border: none; cursor: pointer; }
.bulk-actions select { background: rgba(255,255,255,0.15); color: white; }
.bulk-actions button { background: white; color: var(--vs-accent); font-weight: 600; }
.bulk-actions button:hover { background: var(--vs-accent-light); }
.bulk-close { background: none !important; border: 1px solid rgba(255,255,255,0.4) !important; color: white !important; }
.sortable { cursor: pointer; user-select: none; }
.sortable:hover { background: rgba(255,255,255,0.1); }
.sorted-asc::after  { content: ' ↑'; }
.sorted-desc::after { content: ' ↓'; }
.icon-action-btn { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 6px; text-decoration: none; font-size: 14px; transition: background 0.15s; }
.icon-action-edit   { background: var(--vs-accent-light); }
.icon-action-delete { background: #fef2f2; }
.icon-action-edit:hover   { background: #b5d4f4; }
.icon-action-delete:hover { background: #fecaca; }
.drag-handle { cursor: grab; color: #999; font-size: 18px; display: inline-block; padding: 0 4px; }
.drag-row.dragging { opacity: 0.5; }
.drag-row.drag-over { border-top: 3px solid var(--vs-accent, #185fa5); }
.checkbox-cell { width: 40px; }
.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
</style>

<?php echo showFlashMessage(); ?>

<?php include 'components/components_search_filters.php'; ?>

<?php
// ── Kategorie-Chips ──────────────────────────────────────────────────────
$activeKatIds = $currentFilters['kategorie'] ?? [];
$chipBaseParams = $paginationParams;
unset($chipBaseParams['kategorie'], $chipBaseParams['page']);
?>
<div class="vs-chips" role="navigation" aria-label="Kategoriefilter">
    <a href="index.php?<?php echo http_build_query($chipBaseParams); ?>"
       class="vs-chip <?php echo empty($activeKatIds) ? 'vs-chip-on' : ''; ?>">
        <?php echo tn('filter_all', 'Alle'); ?>
        <span style="opacity:.7">(<?php echo $total_items; ?>)</span>
    </a>
    <?php foreach ($kategorien as $kat):
        $cnt = $kategorie_counts[$kat['id']] ?? 0;
        if ($cnt === 0) continue;
        $isOn   = in_array($kat['id'], $activeKatIds);
        $params = array_merge($chipBaseParams, ['kategorie' => [$kat['id']]]);
    ?>
    <a href="index.php?<?php echo http_build_query($params); ?>"
       class="vs-chip <?php echo $isOn ? 'vs-chip-on' : ''; ?>">
        <?php echo htmlspecialchars($kat['name']); ?>
        <span style="opacity:.7">(<?php echo $cnt; ?>)</span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Toolbar: View-Toggle + Sortierung + Optionen ─────────────────────────── -->
<div class="vs-gtoolbar">
    <div class="vs-vbtns" role="group" aria-label="Ansicht wählen">
        <a href="index.php?view=masonry<?php echo !empty($chipBaseParams) ? '&' . http_build_query($chipBaseParams) : ''; ?>"
           class="vs-vbtn <?php echo $view_mode === 'masonry' ? 'vs-vbtn-on' : ''; ?>"
           title="Kachelansicht" aria-label="Kachelansicht">
            <i class="ti ti-layout-cards" aria-hidden="true"></i>
        </a>
        <a href="index.php?view=table<?php echo !empty($chipBaseParams) ? '&' . http_build_query($chipBaseParams) : ''; ?>"
           class="vs-vbtn <?php echo $view_mode === 'table' ? 'vs-vbtn-on' : ''; ?>"
           title="Tabellenansicht" aria-label="Tabellenansicht">
            <i class="ti ti-table" aria-hidden="true"></i>
        </a>
        <a href="index.php?view=room<?php echo !empty($chipBaseParams) ? '&' . http_build_query($chipBaseParams) : ''; ?>"
           class="vs-vbtn <?php echo $view_mode === 'room' ? 'vs-vbtn-on' : ''; ?>"
           title="Raumansicht" aria-label="Raumansicht">
            <i class="ti ti-home" aria-hidden="true"></i>
        </a>
    </div>

    <?php if ($view_mode === 'table' && canEdit()): ?>
    <button type="button" id="columnsSelectorBtn" class="btn btn-secondary"
            style="font-size:13px; padding:6px 12px;">
        <i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>
        <?php echo tn('index_columns', 'Spalten'); ?>
    </button>
    <?php if ($is_manual_sort): ?>
    <a href="index.php?sort=name&order=ASC" class="btn btn-primary" style="font-size:13px; padding:6px 12px;">
        ↕ <?php echo tn('sort_manual_active', '↕ Manuell aktiv — alphabetisch'); ?>
    </a>
    <?php else: ?>
    <a href="index.php?sort=sort_order&order=ASC" class="btn btn-secondary" style="font-size:13px; padding:6px 12px;">
        ↕ <?php echo tn('sort_manual', '↕ Manuell sortieren'); ?>
    </a>
    <?php endif; ?>
    <?php endif; ?>

    <div class="vs-gtoolbar-r">
        <?php
        // Kategorie-Auswahl fuer kleine Schirme. Die Chips oben brauchen dort
        // vier Zeilen; per CSS wird unter 600px dieses Feld statt der Chips
        // gezeigt. Die Werte sind dieselben Adressen wie in den Chip-Links,
        // damit beide Wege exakt gleich filtern.
        ?>
        <?php if (!empty($kategorien)): ?>
        <select class="vs-catselect" onchange="if(this.value)window.location.href=this.value;"
                aria-label="<?php echo tn('index_filter_category', 'Kategorie filtern'); ?>"
                style="padding:6px 10px; border-radius:6px; border:1px solid var(--vs-border,#e2e8f0); font-size:13px; background:white;">
            <option value="index.php?<?php echo htmlspecialchars(http_build_query($chipBaseParams)); ?>"
                    <?php echo empty($activeKatIds) ? 'selected' : ''; ?>>
                <?php echo tn('filter_all', 'Alle'); ?> (<?php echo $total_items; ?>)
            </option>
            <?php foreach ($kategorien as $kat):
                $cnt = $kategorie_counts[$kat['id']] ?? 0;
                if ($cnt === 0) continue;
                $katUrl = 'index.php?' . http_build_query(array_merge($chipBaseParams, ['kategorie' => [$kat['id']]]));
            ?>
            <option value="<?php echo htmlspecialchars($katUrl); ?>"
                    <?php echo in_array($kat['id'], $activeKatIds) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($kat['name']); ?> (<?php echo $cnt; ?>)
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <?php if (!empty($raeume_filter)): ?>
        <form method="get" action="index.php" style="display:contents;">
            <?php foreach ($paginationParams as $k => $v):
                if ($k === 'filter_raum' || $k === 'filter_position') continue;
                if (is_array($v)):
                    foreach ($v as $vi): ?>
                        <input type="hidden" name="<?php echo htmlspecialchars($k); ?>[]" value="<?php echo htmlspecialchars($vi); ?>">
                    <?php endforeach;
                else: ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($k); ?>" value="<?php echo htmlspecialchars($v); ?>">
            <?php endif; endforeach; ?>
            <select name="filter_raum" onchange="this.form.submit()"
                    style="padding:6px 10px; border-radius:6px; border:1px solid var(--vs-border,#e2e8f0); font-size:13px; background:white;">
                <option value=""><?php echo t('alle_raeume') ?: 'Alle Räume'; ?></option>
                <?php foreach ($raeume_filter as $r): ?>
                <option value="<?php echo $r['id']; ?>" <?php echo $filter_raum == $r['id'] ? 'selected' : ''; ?>>
                    📍 <?php echo htmlspecialchars($r['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>

        <label style="display:inline-flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
            <input type="checkbox" id="showHiddenToggle" <?php echo $showHidden ? 'checked' : ''; ?>
                   onchange="window.location.href = updateURLParameter(window.location.href, 'show_hidden', this.checked ? '1' : '0');">
            <?php echo tn('index_show_hidden', 'Versteckte anzeigen'); ?>
        </label>
    </div>
</div>

<?php
// ══════════════════════════════════════════════════════════════════════
// MASONRY-ANSICHT
// ══════════════════════════════════════════════════════════════════════
if ($view_mode === 'masonry' || ($view_mode !== 'table' && $view_mode !== 'room')):
?>
<?php if (empty($wertsachen)): ?>
<div class="vs-empty" role="status">
    <i class="ti ti-package-off" aria-hidden="true"></i>
    <p><?php echo tn('index_no_entries', 'Keine Einträge gefunden.'); ?></p>
</div>
<?php else: ?>

<form id="masonryForm">
<?php if (canEdit()): ?>
<div id="bulkActionsBar" class="bulk-actions-bar" style="display:none;" aria-live="polite">
    <span class="bulk-info" id="bulkCount">0 ausgewählt</span>
    <div class="bulk-actions">
        <select id="bulkActionSelect" aria-label="Bulk-Aktion wählen">
            <option value=""><?php echo tn('index_bulk_action', 'Aktion…'); ?></option>
            <option value="hide"><?php echo tn('index_bulk_hide', 'Verbergen'); ?></option>
            <option value="unhide"><?php echo tn('index_bulk_unhide', 'Wieder anzeigen'); ?></option>
            <option value="delete"><?php echo tn('index_bulk_delete', 'Löschen'); ?></option>
        </select>
        <button type="button" onclick="executeBulkAction()" aria-label="Aktion ausführen">
            <?php echo tn('index_bulk_apply', 'Anwenden'); ?>
        </button>
        <button type="button" class="bulk-close" onclick="clearBulkSelection()" aria-label="Auswahl aufheben">✕</button>
    </div>
</div>
<?php endif; ?>

<div class="vs-masonry" id="masonryGrid">
<?php foreach ($wertsachen as $item):
    $hasPhoto   = !empty($item['bild']) && file_exists(UPLOAD_DIR . $item['bild']);
    $photoH     = 160; // Einheitliche Höhe für alle Karten
    $itemJson   = htmlspecialchars(json_encode([
        'id'          => (int)$item['id'],
        'name'        => $item['name'],
        'preis'       => (float)($item['preis'] ?? 0),
        'kategorie'   => $item['kategorie_name'] ?? '',
        'raum'        => $item['raum_name'] ?? '',
        'kaufdatum'   => $item['kaufdatum'] ?? '',
        'seriennummer'=> $item['seriennummer'] ?? '',
        'notizen'     => mb_substr($item['notizen'] ?? '', 0, 300),
        'bild'        => $hasPhoto ? 'upload/' . rawurlencode($item['bild']) : null,
        'catIcon'     => getCategoryIcon($item['kategorie_name'] ?? ''),
        'docCount'    => (int)($item['dokumente_anzahl'] ?? 0),
        'photoCount'  => (int)($item['bilder_anzahl'] ?? 0),
    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);

    $catIcon = getCategoryIcon($item['kategorie_name'] ?? '');
    $docCount = (int)($item['dokumente_anzahl'] ?? 0);
    $photoCount = (int)($item['bilder_anzahl'] ?? 0);
?>
<div class="vs-mcard mobile-item-card <?php echo ($is_manual_sort && canEdit()) ? 'drag-card' : ''; ?>"
     data-id="<?php echo $item['id']; ?>"
     data-item-id="<?php echo $item['id']; ?>"
     data-item='<?php echo $itemJson; ?>'
     onclick="vsOpenDetail(this)"
     <?php echo $is_manual_sort && canEdit() ? 'draggable="true"' : ''; ?>>

    <?php if (canEdit()): ?>
    <div style="position:absolute; top:8px; left:8px; z-index:10;" onclick="event.stopPropagation()">
        <label class="sr-only" for="mcb-<?php echo $item['id']; ?>">
            <?php echo htmlspecialchars($item['name']); ?> auswählen
        </label>
        <input type="checkbox" id="mcb-<?php echo $item['id']; ?>"
               class="item-checkbox"
               value="<?php echo $item['id']; ?>"
               data-name="<?php echo htmlspecialchars($item['name']); ?>"
               style="width:16px;height:16px;cursor:pointer;accent-color:var(--vs-accent,#185fa5);">
    </div>
    <?php endif; ?>

    <?php if (isset($item['hidden']) && $item['hidden'] == 1): ?>
    <div style="position:absolute; top:8px; right:8px; z-index:10; opacity:.6; font-size:12px;">👁️</div>
    <?php endif; ?>

    <div class="vs-mcard-photo" style="height:<?php echo $photoH; ?>px;">
        <?php if ($hasPhoto): ?>
            <img src="upload/<?php echo rawurlencode($item['bild']); ?>"
                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                 loading="lazy">
        <?php else: ?>
            <div class="vs-cat-placeholder">
                <i class="ti <?php echo $catIcon; ?>" aria-hidden="true"></i>
            </div>
        <?php endif; ?>
        <?php if ($docCount > 0): ?>
            <a href="manage_documents.php?id=<?php echo $item['id']; ?>"
               class="vs-mcard-doc-badge"
               onclick="event.stopPropagation()"
               title="<?php echo $docCount; ?> <?php echo t('tab_documents'); ?>">
                <i class="ti ti-file-text" aria-hidden="true"></i> <?php echo $docCount; ?>
            </a>
        <?php endif; ?>
        <?php if ($photoCount > 1): ?>
            <span class="vs-mcard-photo-badge" title="<?php echo $photoCount; ?> Fotos">
                <i class="ti ti-photo" aria-hidden="true"></i> <?php echo $photoCount; ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="vs-mcard-body">
        <div class="vs-mcard-name" title="<?php echo htmlspecialchars($item['name']); ?>">
            <?php echo htmlspecialchars($item['name']); ?>
        </div>
        <div class="vs-mcard-meta">
            <span class="vs-mcard-cat"><?php echo htmlspecialchars($item['kategorie_name'] ?? '—'); ?></span>
            <span class="vs-mcard-price">
                <?php echo ($item['preis'] > 0) ? formatPriceLocalized($item['preis']) : '—'; ?>
            </span>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div><!-- /.vs-masonry -->
</form>

<?php endif; /* empty check */ ?>
<?php endif; /* masonry view */ ?>

<?php
// ══════════════════════════════════════════════════════════════════════
// TABELLENANSICHT (unverändert aus Original)
// ══════════════════════════════════════════════════════════════════════
if ($view_mode === 'table'):
?>
<div class="table-responsive">
  <form id="tableForm">
    <?php if (canEdit()): ?>
    <div id="bulkActionsBar" class="bulk-actions-bar" style="display:none;" aria-live="polite">
        <span class="bulk-info" id="bulkCount">0 ausgewählt</span>
        <div class="bulk-actions">
            <select id="bulkActionSelect" aria-label="Bulk-Aktion wählen">
                <option value=""><?php echo tn('index_bulk_action', 'Aktion…'); ?></option>
                <option value="hide"><?php echo tn('index_bulk_hide', 'Verbergen'); ?></option>
                <option value="unhide"><?php echo tn('index_bulk_unhide', 'Wieder anzeigen'); ?></option>
                <option value="delete"><?php echo tn('index_bulk_delete', 'Löschen'); ?></option>
            </select>
            <button type="button" onclick="executeBulkAction()" aria-label="Aktion ausführen">
                <?php echo tn('index_bulk_apply', 'Anwenden'); ?>
            </button>
            <button type="button" onclick="clearBulkSelection()" aria-label="Auswahl aufheben"
                    style="background:none;border:none;cursor:pointer;font-size:18px;line-height:1;padding:0 4px;color:inherit;opacity:.7;">✕</button>
        </div>
    </div>
    <?php endif; ?>
    <table class="data-table">
      <thead><tr>
        <?php if (canEdit()): ?><th class="checkbox-cell" scope="col" aria-label="Alle auswählen">
          <input type="checkbox" id="selectAll" aria-label="Alle auswählen">
        </th><?php endif; ?>
        <th scope="col" class="sortable <?php echo $sort_column === 'name' ? 'sorted-' . strtolower($sort_order) : ''; ?>"
            onclick="sortTable('name')"><?php echo tn('tab_name', 'Name'); ?></th>
        <?php
        $spaltenOrder = getSpaltenOrder();
        $sortableColumns = ['kategorie'=>'kategorie_name','ort'=>'raum_name','preis'=>'preis','kaufdatum'=>'kaufdatum','ersteller'=>'erstellt_von'];
        foreach ($spaltenOrder as $spaltenId):
            if ($spaltenId === 'dokumente' && !$dokumente_aktiv) continue;
            if (!showSpalte($spaltenId)) continue;
            $sortColumn = $sortableColumns[$spaltenId] ?? null;
            $label = tn('col_' . $spaltenId, ucfirst(str_replace('_', ' ', $spaltenId)));
        ?>
        <?php if ($sortColumn): ?>
        <th scope="col" class="sortable <?php echo $sort_column === $sortColumn ? 'sorted-' . strtolower($sort_order) : ''; ?>"
            onclick="sortTable('<?php echo $sortColumn; ?>')"><?php echo htmlspecialchars($label); ?></th>
        <?php else: ?>
        <th scope="col"><?php echo htmlspecialchars($label); ?></th>
        <?php endif; ?>
        <?php endforeach; ?>
        <th scope="col"><?php echo tn('tab_actions', 'Aktionen'); ?></th>
      </tr></thead>
      <tbody>
        <?php if (empty($wertsachen)): ?>
        <tr><td colspan="99" class="text-center"><?php echo tn('index_no_entries', 'Keine Einträge gefunden.'); ?></td></tr>
        <?php else: ?>
        <?php foreach ($wertsachen as $item): ?>
        <tr <?php echo $is_manual_sort && canEdit() ? 'draggable="true" data-id="' . $item['id'] . '" class="drag-row"' : ''; ?>>
          <?php if (canEdit()): ?>
          <td class="checkbox-cell">
            <?php if ($is_manual_sort): ?><span class="drag-handle" title="Ziehen zum Sortieren">⠿</span><?php endif; ?>
            <label for="item-cb-<?php echo $item['id']; ?>" class="sr-only"><?php echo htmlspecialchars($item['name']); ?> auswählen</label>
            <input type="checkbox" class="item-checkbox" id="item-cb-<?php echo $item['id']; ?>"
                   value="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
          </td>
          <?php endif; ?>
          <td><?php
            if (isset($item['hidden']) && $item['hidden'] == 1) echo '<span style="opacity:.5">👁️</span> ';
            echo htmlspecialchars($item['name']);
            if (!empty($item['standort_name'])): ?>
              <br><small style="color:#888;font-size:11px;">📍 <?php echo htmlspecialchars($item['standort_name']);
                if (!empty($item['standort_position_name'])) echo ' › ' . htmlspecialchars($item['standort_position_name']); ?></small>
          <?php endif; ?></td>
          <?php foreach ($spaltenOrder as $spaltenId):
            if ($spaltenId === 'dokumente' && !$dokumente_aktiv) continue;
            if (!showSpalte($spaltenId)) continue;
            echo renderTableCell($spaltenId, $item, $dokumente_aktiv, $editReturnParam);
          endforeach; ?>
          <td class="td-actions">
            <?php if (canEdit()): ?>
            <a href="edit.php?id=<?php echo $item['id']; ?><?php echo $editReturnParam; ?>"
               class="icon-action-btn icon-action-edit" title="<?php echo tn('index_edit', 'Bearbeiten'); ?>"
               aria-label="<?php echo tn('index_edit', 'Bearbeiten') . ': ' . htmlspecialchars($item['name']); ?>">✏️</a>
            <a href="index.php?delete_id=<?php echo $item['id']; ?>&<?php echo http_build_query($paginationParams); ?>"
               class="icon-action-btn icon-action-delete"
               onclick="return vsConfirmLink(event, '<?php echo tn('index_confirm_delete', 'Wirklich löschen?'); ?>')"
               aria-label="<?php echo tn('index_delete', 'Löschen') . ': ' . htmlspecialchars($item['name']); ?>">🗑️</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </form>
</div>
<?php endif; /* table view */ ?>

<?php
// ══════════════════════════════════════════════════════════════════════
// RAUMANSICHT (unverändert aus Original)
// ══════════════════════════════════════════════════════════════════════
if ($view_mode === 'room'):
    $room_sql = "SELECT w.*, o.name as raum_name, k.name as kategorie_name
                 FROM wertsachen w
                 LEFT JOIN raeume o ON w.raum_id = o.id
                 LEFT JOIN kategorien k ON w.kategorie_id = k.id
                 WHERE (w.hidden = 0 OR w.hidden IS NULL)";
    $room_params = [];
    $roomConditions = buildSearchQuery($currentFilters, $room_params);
    foreach ($roomConditions as $c) { $room_sql .= " AND " . $c; }
    $room_sql .= " ORDER BY o.name ASC, w.name ASC";
    $alle_items = $db->select($room_sql, $room_params);
    $raeume = [];
    foreach ($alle_items as $item) {
        $raumName = $item['raum_name'] ?? tn('export_location_unknown', 'Unbekannt');
        $raeume[$raumName][] = $item;
    }
?>
<style>
.room-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
.room-block { background: white; border: 1px solid var(--vs-border,#e2e8f0); border-radius: 14px; overflow: hidden; }
.room-block-header { background: var(--vs-accent,#185fa5); color: white; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; }
.room-block-title { font-size: 15px; font-weight: 700; }
.room-block-count { font-size: 12px; background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 10px; }
.room-item { display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-bottom: 1px solid #f1f5f9; }
.room-item:last-child { border-bottom: none; }
.room-item:hover { background: #f8fafc; }
.room-item-img { width: 44px; height: 44px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.room-item-no-img { width: 44px; height: 44px; background: var(--vs-accent-light, #e6f1fb); border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.room-item-no-img i { font-size: 18px; color: var(--vs-accent, #185fa5); }
.room-item-info { flex: 1; min-width: 0; }
.room-item-name { font-size: 14px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.room-item-meta { font-size: 12px; color: #94a3b8; }
.room-item-price { font-size: 13px; font-weight: 700; color: var(--vs-accent,#185fa5); white-space: nowrap; }
</style>
<div class="room-grid">
<?php foreach ($raeume as $raumName => $items): ?>
<div class="room-block">
    <div class="room-block-header">
        <span class="room-block-title">📍 <?php echo htmlspecialchars($raumName); ?></span>
        <span class="room-block-count"><?php echo count($items); ?></span>
    </div>
    <?php foreach ($items as $item): ?>
    <div class="room-item">
        <?php if (!empty($item['bild']) && file_exists(UPLOAD_DIR . $item['bild'])): ?>
            <img src="upload/<?php echo rawurlencode($item['bild']); ?>" alt="" class="room-item-img">
        <?php else: ?>
            <div class="room-item-no-img"><i class="ti <?php echo getCategoryIcon($item['kategorie_name'] ?? ''); ?>" aria-hidden="true"></i></div>
        <?php endif; ?>
        <div class="room-item-info">
            <div class="room-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
            <div class="room-item-meta"><?php echo htmlspecialchars($item['kategorie_name'] ?? '—'); ?></div>
        </div>
        <span class="room-item-price"><?php echo $item['preis'] > 0 ? formatPriceLocalized($item['preis']) : '—'; ?></span>
        <?php if (canEdit()): ?>
        <a href="edit.php?id=<?php echo $item['id']; ?>&return_url=index.php?view=room"
           class="icon-action-btn icon-action-edit" aria-label="Bearbeiten">✏️</a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; /* room view */ ?>

<!-- ── Pagination ──────────────────────────────────────────────────────── -->
<?php if ($pagination['total_pages'] > 1): ?>
<?php echo renderPagination($pagination['current_page'], $pagination['total_pages'], 'index.php', $paginationParams); ?>
<?php endif; ?>
<!-- ── Bulk + Spalten-Dropdown JS ───────────────────────────────────────── -->
<?php if (canEdit()): ?>
<script>
var csrf = '<?php echo Security::generateCSRFToken(); ?>';

function updateBulkBar() {
    var checked = document.querySelectorAll('.item-checkbox:checked');
    var bar = document.getElementById('bulkActionsBar');
    var cnt = document.getElementById('bulkCount');
    if (!bar) return;
    if (checked.length > 0) {
        bar.style.display = 'flex';
        cnt.textContent = checked.length + ' ausgewählt';
    } else {
        bar.style.display = 'none';
    }
}

function clearBulkSelection() {
    document.querySelectorAll('.item-checkbox').forEach(function(cb) { cb.checked = false; });
    updateBulkBar();
}

function executeBulkAction() {
    var action = document.getElementById('bulkActionSelect').value;
    if (!action) { vsAlert(<?php echo json_encode(t('index_select_action')); ?>); return; }
    var ids = [...document.querySelectorAll('.item-checkbox:checked')].map(cb => cb.value);
    if (ids.length === 0) return;

    // Absenden erst nach der Antwort — deshalb als eigene Funktion, damit es
    // nur an einer Stelle steht und nicht doppelt ausgeloest werden kann.
    var absenden = function () {
        var form = document.createElement('form');
        form.method = 'POST'; form.action = 'index.php';
        var addField = function(n,v) { var i=document.createElement('input'); i.type='hidden'; i.name=n; i.value=v; form.appendChild(i); };
        addField('csrf_token', csrf);
        addField('bulk_action', action);
        ids.forEach(function(id) { addField('selected_items[]', id); });
        document.body.appendChild(form);
        form.submit();
    };

    if (action === 'delete') {
        vsConfirm(<?php echo json_encode(t('index_confirm_bulk_delete')); ?>.replace('%d', ids.length))
            .then(function (ja) { if (ja) { absenden(); } });
    } else {
        absenden();
    }
}

document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('item-checkbox')) updateBulkBar();
});

function updateURLParameter(url, param, value) {
    var u = new URL(url, window.location.href);
    if (value === '' || value === null) { u.searchParams.delete(param); }
    else { u.searchParams.set(param, value); }
    return u.toString();
}

function sortTable(col) {
    var url = new URL(window.location.href);
    var cur = url.searchParams.get('sort');
    var ord = url.searchParams.get('order') || 'ASC';
    url.searchParams.set('sort', col);
    url.searchParams.set('order', (cur === col && ord === 'ASC') ? 'DESC' : 'ASC');
    window.location.href = url.toString();
}

// ── Drag & Drop Reihenfolge (Tabellenansicht) ────────────────────────────────
(function() {
    var dragSrc = null;

    function initDragDrop() {
        var rows = document.querySelectorAll('.drag-row');
        if (!rows.length) return;
        rows.forEach(function(row) {
            row.addEventListener('dragstart', onDragStart);
            row.addEventListener('dragover',  onDragOver);
            row.addEventListener('dragleave', onDragLeave);
            row.addEventListener('drop',      onDrop);
            row.addEventListener('dragend',   onDragEnd);
        });
    }

    function onDragStart(e) {
        dragSrc = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', this.getAttribute('data-id'));
    }

    function onDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        if (this !== dragSrc) this.classList.add('drag-over');
        return false;
    }

    function onDragLeave() {
        this.classList.remove('drag-over');
    }

    function onDrop(e) {
        e.stopPropagation();
        e.preventDefault();
        this.classList.remove('drag-over');
        if (!dragSrc || this === dragSrc) return false;
        var tbody = this.parentNode;
        var rows  = Array.from(tbody.querySelectorAll('.drag-row'));
        var srcIdx = rows.indexOf(dragSrc);
        var tgtIdx = rows.indexOf(this);
        if (srcIdx < tgtIdx) {
            tbody.insertBefore(dragSrc, this.nextSibling);
        } else {
            tbody.insertBefore(dragSrc, this);
        }
        saveOrder();
        return false;
    }

    function onDragEnd() {
        document.querySelectorAll('.drag-row').forEach(function(r) {
            r.classList.remove('dragging', 'drag-over');
        });
        dragSrc = null;
    }

    function saveOrder() {
        var ids = Array.from(document.querySelectorAll('.drag-row'))
                       .map(function(r) { return r.getAttribute('data-id'); });
        var formData = new FormData();
        formData.append('action', 'save_order');
        formData.append('csrf_token', csrf);
        ids.forEach(function(id) { formData.append('ids[]', id); });
        fetch('index.php', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .catch(function(err) { console.error('Drag & Drop: Fehler beim Speichern:', err); });
    }

    document.addEventListener('DOMContentLoaded', initDragDrop);
})();

</script>
<?php endif; ?>

<?php include 'components/column_manager.php'; ?>

<!-- ── Bild-Lightbox ─────────────────────────────────────── -->
<div id="vsLightbox" class="vs-lightbox" onclick="vsCloseLightbox()" aria-modal="true" role="dialog" aria-label="Bildvorschau">
    <img id="vsLightboxImg" src="" alt="">
</div>
<script>
function vsOpenLightbox(src, alt) {
    var lb = document.getElementById('vsLightbox');
    var img = document.getElementById('vsLightboxImg');
    img.src = src;
    img.alt = alt || '';
    lb.classList.add('vs-lightbox-open');
}
function vsCloseLightbox() {
    document.getElementById('vsLightbox').classList.remove('vs-lightbox-open');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') vsCloseLightbox();
});
document.addEventListener('click', function(e) {
    var img = e.target.closest('.vs-mcard-photo img, .table-responsive img');
    if (img) {
        e.stopPropagation();
        vsOpenLightbox(img.src, img.alt);
    }
});
</script>

<?php include 'footer_next.php'; ?>
<?php if (isset($_SESSION['user_id'])): ?>
<style>
@media (max-width: 768px) {
    .mobile-item-card { position:relative; transition:transform 0.3s cubic-bezier(0.4,0,0.2,1); user-select:none; }
    .mobile-item-card.swiping { transition:none; }
    .swipe-action-overlay { position:fixed; top:0; left:0; right:0; bottom:0; pointer-events:none; z-index:9999; display:none; }
    .swipe-action-overlay.active { display:block; }
    .swipe-indicator { position:fixed; top:50%; transform:translateY(-50%); width:80px; height:80px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:36px; opacity:0; transition:opacity 0.15s; box-shadow:0 4px 12px rgba(0,0,0,0.3); }
    .swipe-indicator.show { opacity:1; }
    .swipe-indicator.delete { left:20px; background:#e74c3c; }
    .swipe-indicator.edit   { right:20px; background:#667eea; }
}
</style>
<script>
(function() {
    if (window.innerWidth > 768) return;
    var overlay = document.createElement('div');
    overlay.className = 'swipe-action-overlay';
    var delInd = document.createElement('div');
    delInd.className = 'swipe-indicator delete';
    delInd.innerHTML = '&#128465;';
    var editInd = document.createElement('div');
    editInd.className = 'swipe-indicator edit';
    editInd.innerHTML = '&#9999;';
    overlay.appendChild(delInd);
    overlay.appendChild(editInd);
    document.body.appendChild(overlay);
    var cards = document.querySelectorAll('.mobile-item-card');
    console.log('Swipe init: ' + cards.length + ' cards');
    cards.forEach(function(card) {
        var startX, startY, deltaX = 0, isSwiping = false, tapOk = false;
        card.addEventListener('touchstart', function(e) {
            if (e.target.closest('input,button,a')) return;
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            deltaX = 0; isSwiping = false; tapOk = true;
        }, { passive: true });
        card.addEventListener('touchmove', function(e) {
            if (startX === undefined) return;
            deltaX = e.touches[0].clientX - startX;
            var dY = Math.abs(e.touches[0].clientY - startY);
            if (!isSwiping && dY > Math.abs(deltaX)) { tapOk = false; return; }
            if (Math.abs(deltaX) > 10) {
                isSwiping = true; tapOk = false;
                card.classList.add('swiping');
                overlay.classList.add('active');
                var lim = Math.max(-70, Math.min(70, deltaX));
                card.style.transform = 'translateX(' + lim + 'px)';
                if (deltaX < -30) { delInd.classList.add('show'); editInd.classList.remove('show'); }
                else if (deltaX > 30) { editInd.classList.add('show'); delInd.classList.remove('show'); }
                else { delInd.classList.remove('show'); editInd.classList.remove('show'); }
            }
        }, { passive: true });
        card.addEventListener('touchend', function(e) {
            if (startX === undefined) return;
            card.classList.remove('swiping');
            card.style.transform = '';
            if (isSwiping && Math.abs(deltaX) > 50) {
                var id = card.getAttribute('data-item-id');
                if (deltaX < 0) {
                    setTimeout(function() {
                        vsConfirm(<?php echo json_encode(t('bulk_confirm_delete')); ?>).then(function (ja) {
                            if (ja) { window.location.href = 'delete.php?id=' + id; }
                        });
                    }, 50);
                } else {
                    window.location.href = 'edit.php?id=' + id;
                }
            } else if (tapOk && !isSwiping) {
                if (typeof vsOpenDetail === 'function') vsOpenDetail(card);
            }
            setTimeout(function() {
                overlay.classList.remove('active');
                delInd.classList.remove('show');
                editInd.classList.remove('show');
            }, 150);
            startX = undefined; isSwiping = false; tapOk = false; deltaX = 0;
        }, { passive: true });
    });
})();
</script>
<?php endif; ?>
