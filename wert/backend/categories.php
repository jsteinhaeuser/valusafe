<?php
/**
 * Kategorien-Verwaltung - ANGEPASST FÜR NEUES LAYOUT
 */

require_once __DIR__ . '/config.php';
requireBackendAccess();

$pageTitle = 'Kategorien';

$message = '';
$error = '';

// POST-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Schutz: alle state-aendernden Aktionen dieser Seite laufen
    // ueber diesen einen POST-Block (s. backend/backup.php als Vorbild).
    validateRequest();
    
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
            $error = t('cat_name_empty') ?: 'Kategorie-Name darf nicht leer sein';
        } else {
            try {
                $existing = $db->selectOne("SELECT id FROM kategorien WHERE name = ?", [$name]);
                
                if ($existing) {
                    $error = t('cat_already_exists') ?: 'Diese Kategorie existiert bereits';
                } else {
                    $newId = $db->insert("INSERT INTO kategorien (name) VALUES (?)", [$name]);
                    
                    // Activity loggen
                    logActivity('created', 'kategorien', $newId, $name);
                    
                    $message = t('cat_added_success') ?: 'Kategorie erfolgreich hinzugefügt';
                }
            } catch (PDOException $e) {
                $error = t('error_generic') ?: 'Fehler: ' . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
            $error = t('cat_name_empty') ?: 'Kategorie-Name darf nicht leer sein';
        } elseif ($id <= 0) {
            $error = t('error_invalid_id') ?: 'Ungültige ID';
        } else {
            try {
                // Alte Werte für Log holen
                $oldCategory = $db->selectOne("SELECT name FROM kategorien WHERE id = ?", [$id]);
                
                $db->execute("UPDATE kategorien SET name = ? WHERE id = ?", [$name, $id]);
                
                // Activity loggen
                if ($oldCategory) {
                    logActivity('updated', 'kategorien', $id, $name, 
                        ['name' => $oldCategory['name']], 
                        ['name' => $name]
                    );
                }
                
                $message = t('cat_updated_success') ?: 'Kategorie erfolgreich aktualisiert';
            } catch (PDOException $e) {
                $error = t('error_generic') ?: 'Fehler: ' . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $error = t('error_invalid_id') ?: 'Ungültige ID';
        } else {
            try {
                // Prüfen ob Kategorie verwendet wird
                $usage = $db->selectOne("SELECT COUNT(*) as count FROM wertsachen WHERE kategorie_id = ?", [$id]);
                
                if ($usage['count'] > 0) {
                    $error = sprintf(t('cat_in_use') ?: 'Diese Kategorie wird noch von %d Gegenständen verwendet und kann nicht gelöscht werden', $usage['count']);
                } else {
                    // Kategorie-Name für Log holen
                    $category = $db->selectOne("SELECT name FROM kategorien WHERE id = ?", [$id]);
                    
                    $db->execute("DELETE FROM kategorien WHERE id = ?", [$id]);
                    
                    // Activity loggen
                    if ($category) {
                        logActivity('deleted', 'kategorien', $id, $category['name']);
                    }
                    
                    $message = t('cat_deleted_success') ?: 'Kategorie erfolgreich gelöscht';
                }
            } catch (PDOException $e) {
                $error = t('error_generic') ?: 'Fehler: ' . $e->getMessage();
            }
        }
    }
}

// Kategorien laden
try {
    $kategorien = $db->select("
        SELECT k.*, COUNT(w.id) as anzahl_gegenstände
        FROM kategorien k
        LEFT JOIN wertsachen w ON k.id = w.kategorie_id
        GROUP BY k.id
        ORDER BY k.name
    ");
} catch (PDOException $e) {
    $error = t('error_loading') ?: 'Fehler beim Laden: ' . $e->getMessage();
    $kategorien = [];
}

// Layout laden
include 'layout/header_next_page.php';
?>

<main class="backend-main">
    <div style="margin-bottom: 30px;">
        <h1 style="font-size: 28px; color: var(--vs-text); margin-bottom: 10px;">
            <i class="ti ti-tag"></i> <?php echo t('cat_management') ?: 'Kategorien-Verwaltung'; ?>
        </h1>
        <p style="color: var(--vs-text-muted);">
            <?php echo t('cat_management_desc') ?: 'Verwalten Sie hier die Kategorien für Ihre Gegenstände'; ?>
        </p>
    </div>

    <?php if ($message): ?>
        <div style="background: var(--vs-success-light); border-left: 4px solid #28a745; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <strong style="color: var(--vs-success-text);">✓ <?php echo htmlspecialchars($message); ?></strong>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: var(--vs-danger-light); border-left: 4px solid #dc3545; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <strong style="color: var(--vs-danger-text);">✗ <?php echo htmlspecialchars($error); ?></strong>
        </div>
    <?php endif; ?>

    <!-- Neue Kategorie hinzufügen -->
    <div style="background: var(--vs-surface); padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="font-size: 18px; margin-bottom: 20px; color: #333;">
            <i class="ti ti-plus"></i> <?php echo t('cat_add_new') ?: 'Neue Kategorie hinzufügen'; ?>
        </h2>
        
        <form method="POST" style="display: flex; gap: 15px; align-items: flex-end;">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="action" value="add">
            
            <div style="flex: 1;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #555;">
                    <?php echo t('cat_name_label') ?: 'Kategorie-Name:'; ?>
                </label>
                <input type="text" 
                       name="name" 
                       required 
                       placeholder="<?php echo t('cat_placeholder') ?: 'z.B. Elektronik, Möbel, ...'; ?>"
                       style="width: 100%; padding: 12px; border: 2px solid var(--vs-border); border-radius: 6px; font-size: 14px;">
            </div>
            
            <button type="submit" 
                    style="padding: 12px 30px; background: var(--vs-accent); color: var(--vs-surface); border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px;">
                <?php echo t('btn_add') ?: '<i class="ti ti-plus"></i> Hinzufügen'; ?>
            </button>
        </form>
    </div>

    <!-- Kategorien-Liste -->
    <div style="background: var(--vs-surface); padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h2 style="font-size: 18px; margin-bottom: 20px; color: #333;">
            <i class="ti ti-clipboard-list"></i> <?php echo t('cat_all') ?: 'Alle Kategorien'; ?> (<?php echo count($kategorien); ?>)
        </h2>
        
        <?php if (count($kategorien) === 0): ?>
            <p style="text-align: center; color: #999; padding: 40px;">
                <?php echo t('cat_none') ?: 'Noch keine Kategorien vorhanden. Fügen Sie oben eine neue Kategorie hinzu!'; ?>
            </p>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--vs-surface-2); border-bottom: 2px solid var(--vs-border);">
                        <th style="padding: 15px; text-align: left; font-weight: 600;"><?php echo t('cat_col_name') ?: 'Kategorie'; ?></th>
                        <th style="padding: 15px; text-align: center; font-weight: 600;"><?php echo t('stats_items') ?: 'Gegenstände'; ?></th>
                        <th style="padding: 15px; text-align: right; font-weight: 600;"><?php echo t('tab_actions') ?: 'Aktionen'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kategorien as $kategorie): ?>
                        <tr style="border-bottom: 1px solid var(--vs-border);" id="cat-<?php echo $kategorie['id']; ?>">
                            <td style="padding: 15px;">
                                <strong style="color: #333; font-size: 15px;">
                                    <?php echo htmlspecialchars($kategorie['name']); ?>
                                </strong>
                            </td>
                            <td style="padding: 15px; text-align: center;">
                                <span style="background: var(--vs-border); padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: 600;">
                                    <?php echo $kategorie['anzahl_gegenstände']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px; text-align: right;">
                                <button onclick="editCategory(<?php echo $kategorie['id']; ?>, '<?php echo htmlspecialchars($kategorie['name'], ENT_QUOTES); ?>')"
                                        style="padding: 8px 10px; background: var(--vs-accent); color: var(--vs-surface); border: none; border-radius: 4px; cursor: pointer; margin-right: 5px; font-size: 16px;" title="<?php echo t('btn_edit') ?: 'Bearbeiten'; ?>">
                                    <i class="ti ti-pencil"></i>
                                </button>
                                
                                <?php if ($kategorie['anzahl_gegenstände'] == 0): ?>
                                    <button type="button"
                                            onclick="confirmDelete(<?php echo $kategorie['id']; ?>, '<?php echo htmlspecialchars($kategorie['name'], ENT_QUOTES); ?>')"
                                            style="padding: 8px 10px; background: var(--vs-danger); color: var(--vs-surface); border: none; border-radius: 4px; cursor: pointer; font-size: 16px;" title="<?php echo t('btn_delete') ?: 'Löschen'; ?>">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <button disabled
                                            title="<?php echo t('cat_in_use_title') ?: 'Kategorie wird noch verwendet'; ?>"
                                            style="padding: 8px 10px; background: #ccc; color: #666; border: none; border-radius: 4px; cursor: not-allowed; font-size: 16px;" title="<?php echo t('cat_in_use_title') ?: 'Kategorie wird noch verwendet'; ?>">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Edit Modal (versteckt) -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: var(--vs-surface); padding: 30px; border-radius: 12px; max-width: 500px; width: 90%;">
            <h3 style="margin-bottom: 20px; color: #333;"><?php echo t('cat_edit_title') ?: 'Kategorie bearbeiten'; ?></h3>
            
            <form method="POST">
                <?php echo Security::getCSRFInput(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;"><?php echo t('cat_name_label') ?: 'Name:'; ?></label>
                    <input type="text" 
                           name="name" 
                           id="editName" 
                           required
                           style="width: 100%; padding: 12px; border: 2px solid var(--vs-border); border-radius: 6px; font-size: 14px;">
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" 
                            onclick="closeEditModal()"
                            style="padding: 10px 20px; background: var(--vs-text-muted); color: var(--vs-surface); border: none; border-radius: 6px; cursor: pointer;">
                        <?php echo t('btn_cancel') ?: 'Abbrechen'; ?>
                    </button>
                    <button type="submit"
                            style="padding: 10px 20px; background: var(--vs-success); color: var(--vs-surface); border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        <i class="ti ti-device-floppy"></i> <?php echo t('btn_save') ?: 'Speichern'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- Lösch-Bestätigungs-Modal (Mobile-kompatibel, kein confirm()) -->
<div id="deleteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
     z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:var(--vs-surface); border-radius:16px; padding:28px; max-width:360px; width:100%;
                box-shadow:0 16px 48px rgba(0,0,0,0.2); text-align:center;">
        <div style="font-size:36px; margin-bottom:12px;"><i class="ti ti-trash"></i></div>
        <h3 style="margin:0 0 8px;"><?php echo t('cat_delete_title') ?: 'Kategorie löschen?'; ?></h3>
        <p id="deleteModalName" style="color:var(--vs-text-muted); margin:0 0 24px; font-size:14px;"></p>
        <div style="display:flex; gap:10px; justify-content:center;">
            <button onclick="closeDeleteModal()" class="vs-btn vs-btn-secondary"
                    style="padding:10px 24px;"><?php echo t('btn_cancel') ?: 'Abbrechen'; ?></button>
            <form id="deleteForm" method="POST" style="margin:0;">
                <?php echo Security::getCSRFInput(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId" value="">
                <button type="submit"
                        style="padding:10px 24px; background:var(--vs-danger); color:var(--vs-surface); border:none;
                               border-radius:8px; cursor:pointer; font-weight:600; font-size:14px;">
                    <?php echo t('btn_delete') ?: 'Löschen'; ?>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModalName').textContent = '"' + name + '"';
    document.getElementById('deleteModal').style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

function editCategory(id, name) {
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Modal schließen bei Klick außerhalb
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

// ESC-Taste schließt Modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
    }
});
</script>

<?php include 'layout/footer_next_page.php'; ?>
