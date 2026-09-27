<?php
/**
 * Benutzer-Verwaltung - ANGEPASST FÜR NEUES LAYOUT
 * Vollständige CRUD-Operationen für Benutzer
 */

require_once __DIR__ . '/config.php';
requireBackendAccess();

// Nur SuperAdmin darf andere Benutzer sehen/verwalten
if (!isSuperAdmin()) {
    header('Location: /backend/index.php');
    exit;
}

$pageTitle = 'Benutzerverwaltung';

$message = '';
$error = '';

// POST-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Schutz: alle state-aendernden Aktionen dieser Seite laufen
    // ueber diesen einen POST-Block (s. backend/backup.php als Vorbild).
    validateRequest();
    
    // Benutzer hinzufügen
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? 'read');
        $email = trim($_POST['email'] ?? '');

        if (empty($username)) {
            $error = t('users_username_empty') ?: 'Benutzername darf nicht leer sein';
        } else if (empty($password)) {
            $error = 'Passwort darf nicht leer sein';
        } else if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Bitte eine gültige E-Mail-Adresse eingeben (oder das Feld leer lassen).';
        } else {
            try {
                $existing = $db->selectOne("SELECT id FROM users WHERE username = ?", [$username]);

                if ($existing) {
                    $error = 'Dieser Benutzername existiert bereits';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
                    $db->execute(
                        "INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)",
                        [$username, $hashedPassword, $role, $email !== '' ? $email : null]
                    );
                    $message = t('users_added_success') ?: 'Benutzer erfolgreich hinzugefügt';
                }
            } catch (PDOException $e) {
                $error = 'Fehler: ' . $e->getMessage();
            }
        }
    }
    
    // Benutzer bearbeiten
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $role = trim($_POST['role'] ?? 'read');
        $newPassword = trim($_POST['new_password'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($username)) {
            $error = t('users_username_empty') ?: 'Benutzername darf nicht leer sein';
        } elseif ($id <= 0) {
            $error = t('error_invalid_id') ?: 'Ungültige ID';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Bitte eine gültige E-Mail-Adresse eingeben (oder das Feld leer lassen).';
        } else {
            try {
                $sieht_alle = isset($_POST['sieht_alle']) ? 1 : 0;
                // Admins sehen immer alles
                if ($role === 'admin') $sieht_alle = 1;
                $emailValue = $email !== '' ? $email : null;

                // Passwort ändern wenn angegeben
                if (!empty($newPassword)) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
                    $db->execute(
                        "UPDATE users SET username = ?, password = ?, role = ?, sieht_alle = ?, email = ? WHERE id = ?",
                        [$username, $hashedPassword, $role, $sieht_alle, $emailValue, $id]
                    );
                } else {
                    $db->execute(
                        "UPDATE users SET username = ?, role = ?, sieht_alle = ?, email = ? WHERE id = ?",
                        [$username, $role, $sieht_alle, $emailValue, $id]
                    );
                }
                $message = t('users_updated_success') ?: 'Benutzer erfolgreich aktualisiert';
            } catch (PDOException $e) {
                $error = 'Fehler: ' . $e->getMessage();
            }
        }
    }
    
    // Benutzer löschen
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $error = t('error_invalid_id') ?: 'Ungültige ID';
        } elseif ($id == $_SESSION['user_id']) {
            $error = t('users_cannot_delete_self') ?: 'Du kannst dich nicht selbst löschen';
        } else {
            try {
                $db->execute("DELETE FROM users WHERE id = ?", [$id]);
                $message = t('users_deleted_success') ?: 'Benutzer erfolgreich gelöscht';
            } catch (PDOException $e) {
                $error = 'Fehler: ' . $e->getMessage();
            }
        }
    }
}

// Alle Benutzer laden
try {
    $users = $db->select("SELECT * FROM users ORDER BY username ASC");
} catch (Exception $e) {
    $users = [];
    $error = 'Fehler beim Laden der Benutzer: ' . $e->getMessage();
}

// Benutzer-Statistiken
$userStats = [
    'total' => count($users),
    'admins' => count(array_filter($users, fn($u) => $u['role'] === 'admin')),
    'editors' => count(array_filter($users, fn($u) => $u['role'] === 'edit')),
    'viewers' => count(array_filter($users, fn($u) => $u['role'] === 'read'))
];

// Online-User laden (letzte 15 Minuten)
try {
    $onlineNow = $db->select("
        SELECT ua.user_id, ua.last_seen, ua.current_page, ua.ip_address,
               COALESCE(ua.username, u.username, '–') AS username,
               COALESCE(ua.role, u.role, 'read') AS role
        FROM user_activity ua
        LEFT JOIN users u ON ua.user_id = u.id
        WHERE ua.last_seen >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $onlineIds = array_column($onlineNow, null, 'user_id');
} catch (Exception $e) {
    $onlineIds = [];
}

// Layout laden
include 'layout/header_next_page.php';
?>

<!-- Main Content -->
<main class="backend-main">
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <strong><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Erfolg!</strong> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <strong><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> Fehler!</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistik-Karten -->
    <div class="dashboard-grid" style="margin-bottom: 30px;">
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value"><?php echo $userStats['total']; ?></div>
                    <div class="widget-label"><i class="ti ti-users"></i> <?php echo t('users_total') ?: 'Gesamt'; ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-users"></i></div>
            </div>
        </div>
        
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value" style="color: var(--vs-danger);"><?php echo $userStats['admins']; ?></div>
                    <div class="widget-label">🔥 <?php echo t('role_admin') ?: 'Admins'; ?></div>
                </div>
                <div class="widget-icon">🔥</div>
            </div>
        </div>
        
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value" style="color: var(--vs-warning);"><?php echo $userStats['editors']; ?></div>
                    <div class="widget-label"><i class="ti ti-pencil"></i> <?php echo t('users_editors') ?: 'Editoren'; ?></div>
                </div>
                <div class="widget-icon"><i class="ti ti-pencil"></i></div>
            </div>
        </div>
        
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value" style="color: var(--vs-accent);"><?php echo $userStats['viewers']; ?></div>
                    <div class="widget-label">👁️ <?php echo t('users_viewers') ?: 'Viewer'; ?></div>
                </div>
                <div class="widget-icon">👁️</div>
            </div>
        </div>
    </div>
    
    <!-- Benutzer-Tabelle -->
    <div class="activity-timeline">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2><i class="ti ti-users"></i> <?php echo t('settings_users_title') ?: 'Alle Benutzer'; ?></h2>
            <button onclick="showAddUserModal()" class="vs-btn vs-btn-primary">
                <i class="ti ti-plus"></i> <?php echo t('users_new_user') ?: 'Neuer Benutzer'; ?>
            </button>
        </div>
        
        <?php if (count($users) === 0): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 15px;"><i class="ti ti-users"></i></div>
                <p>Keine Benutzer gefunden</p>
            </div>
        <?php else: ?>
            <table class="backend-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo t('online_col_user'); ?></th>
                        <th><?php echo t('tab_role'); ?></th>
                        <th style="text-align:center;"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i></th>
                        <th><?php echo t('tab_created'); ?></th>
                        <th style="width: 120px;"><?php echo t('tab_actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php
                            $isOnline  = isset($onlineIds[$user['id']]);
                            $lastSeen  = $isOnline ? $onlineIds[$user['id']]['last_seen'] : null;
                            $seenAgo   = $lastSeen ? round((time() - strtotime($lastSeen)) / 60) : null;
                            $seenLabel = $isOnline
                                ? ($seenAgo < 1 ? t('online_just_now') : sprintf(t('online_minutes_ago'), $seenAgo))
                                : '–';
                            $curPage   = $isOnline ? ($onlineIds[$user['id']]['current_page'] ?? '') : '';
                        ?>
                        <tr>
                            <td><strong>#<?php echo $user['id']; ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                    <span class="badge badge-info" style="margin-left: 8px;">Du</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badges = [
                                    'admin' => '<span class="badge badge-danger">🔥 Admin</span>',
                                    'edit'  => '<span class="badge badge-warning"><i class="ti ti-pencil"></i> Editor</span>',
                                    'read'  => '<span class="badge badge-info">👁️ Viewer</span>',
                                ];
                                echo $badges[$user['role']] ?? htmlspecialchars($user['role']);
                                ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($isOnline): ?>
                                    <span title="<?php echo htmlspecialchars($seenLabel . ($curPage ? ' · ' . $curPage : '')); ?>"
                                          style="font-size:16px; cursor:default;"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i></span>
                                <?php else: ?>
                                    <span style="color:#ddd; font-size:16px;">⚪</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo !empty($user['erstellt_am'] ?? '') ? date('d.m.Y H:i', strtotime($user['erstellt_am'])) : '–'; ?></td>
                            <td>
                                <button onclick='editUser(<?php echo json_encode($user); ?>)' class="vs-btn vs-btn-sm" title="Bearbeiten"><i class="ti ti-pencil"></i></button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>')" class="vs-btn vs-btn-sm vs-btn-danger" title="Löschen"><i class="ti ti-trash"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
</main>

<!-- Who is online: Detailblock -->
<div class="activity-timeline" style="margin-top: 30px;">
    <h2><?php echo t('online_section_title'); ?></h2>
    <?php if (empty($onlineIds)): ?>
        <div style="text-align:center; padding:30px; color:#999;">
            <div style="font-size:36px; margin-bottom:10px;">⚪</div>
            <p><?php echo t('online_section_none'); ?></p>
        </div>
    <?php else: ?>
        <table class="backend-table">
            <thead>
                <tr>
                    <th><?php echo t('online_col_user'); ?></th>
                    <th><?php echo t('tab_role'); ?></th>
                    <th><?php echo t('online_col_page'); ?></th>
                    <th><?php echo t('online_col_ip'); ?></th>
                    <th><?php echo t('online_col_seen'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($onlineNow as $ou):
                $ago = round((time() - strtotime($ou['last_seen'])) / 60);
                $lbl = $ago < 1 ? t('online_just_now') : sprintf(t('online_minutes_ago'), $ago);
                $role = $ou['role'] ?? 'read';
                $badges2 = [
                    'admin' => '<span class="badge badge-danger">🔥 Admin</span>',
                    'edit'  => '<span class="badge badge-warning"><i class="ti ti-pencil"></i> Editor</span>',
                    'read'  => '<span class="badge badge-info">👁️ Viewer</span>',
                ];
            ?>
                <tr>
                    <td><strong><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> <?php echo htmlspecialchars($ou['username']); ?></strong></td>
                    <td><?php echo $badges2[$role] ?? htmlspecialchars($role); ?></td>
                    <td><code style="font-size:12px;"><?php echo htmlspecialchars($ou['current_page'] ?? '–'); ?></code></td>
                    <td style="color:#999; font-size:13px;"><?php echo htmlspecialchars($ou['ip_address'] ?? '–'); ?></td>
                    <td style="color:var(--vs-success); font-size:13px;"><?php echo htmlspecialchars($lbl); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Modal: Benutzer hinzufügen -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="ti ti-plus"></i> <?php echo t('users_new_user') ?: 'Neuer Benutzer'; ?></h3>
            <span class="modal-close" onclick="closeAddUserModal()">&times;</span>
        </div>
        <form method="POST" class="modal-body">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label><?php echo t('field_username') ?: 'Benutzername'; ?> *</label>
                <input type="text" name="username" required class="form-control">
            </div>
            
            <div class="form-group">
                <label><?php echo t('field_password') ?: 'Passwort'; ?> *</label>
                <input type="password" name="password" required class="form-control">
            </div>

            <div class="form-group">
                <label>E-Mail (für Passwort-Reset)</label>
                <input type="email" name="email" class="form-control" placeholder="name@beispiel.de">
            </div>

            <div class="form-group">
                <label><?php echo t('users_permission') ?: 'Berechtigung'; ?></label>
                <select name="role" class="form-control">
                    <option value="read">👁️ <?php echo t('role_viewer') ?: 'Viewer (Nur Lesen)'; ?></option>
                    <option value="edit"><i class="ti ti-pencil"></i> <?php echo t('role_editor') ?: 'Editor (Lesen & Schreiben)'; ?></option>
                    <option value="admin">🔥 <?php echo t('role_admin') ?: 'Admin (Voller Zugriff)'; ?></option>
                </select>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeAddUserModal()" class="vs-btn vs-btn-secondary"><?php echo t('btn_cancel') ?: 'Abbrechen'; ?></button>
                <button type="submit" class="vs-btn vs-btn-primary"><?php echo t('users_create_user') ?: 'Benutzer erstellen'; ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Benutzer bearbeiten -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="ti ti-pencil"></i> <?php echo t('users_edit_user') ?: 'Benutzer bearbeiten'; ?></h3>
            <span class="modal-close" onclick="closeEditUserModal()">&times;</span>
        </div>
        <form method="POST" class="modal-body">
            <?php echo Security::getCSRFInput(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_user_id">
            
            <div class="form-group">
                <label><?php echo t('field_username') ?: 'Benutzername'; ?> *</label>
                <input type="text" name="username" id="edit_username" required class="form-control">
            </div>
            
            <div class="form-group">
                <label><?php echo t('users_new_password') ?: 'Neues Passwort (optional)'; ?></label>
                <input type="password" name="new_password" class="form-control" placeholder="<?php echo t('users_password_placeholder') ?: 'Nur ausfüllen wenn ändern'; ?>">
                <small style="color: #999;"><?php echo t('users_password_hint') ?: 'Leer lassen um Passwort beizubehalten'; ?></small>
            </div>

            <div class="form-group">
                <label>E-Mail (für Passwort-Reset)</label>
                <input type="email" name="email" id="edit_email" class="form-control" placeholder="name@beispiel.de">
            </div>

            <div class="form-group">
                <label><?php echo t('users_permission') ?: 'Berechtigung'; ?></label>
                <select name="role" id="edit_role" class="form-control" onchange="updateSiehtAlleVisibility()">
                    <option value="read">👁️ <?php echo t('role_viewer') ?: 'Viewer (Nur Lesen)'; ?></option>
                    <option value="edit"><i class="ti ti-pencil"></i> <?php echo t('role_editor') ?: 'Editor (Lesen & Schreiben)'; ?></option>
                    <option value="admin">🔥 <?php echo t('role_admin') ?: 'Admin (Voller Zugriff)'; ?></option>
                </select>
            </div>

            <div class="form-group" id="sieht_alle_group" style="background:var(--vs-surface-2); border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px;">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin:0;">
                    <input type="checkbox" name="sieht_alle" id="edit_sieht_alle" value="1" style="width:16px; height:16px;">
                    <span>
                        👁️ <?php echo t('users_see_all') ?: 'Alle Gegenstände sehen'; ?>
                        <small style="display:block; font-weight:400; color:var(--vs-text-muted); font-size:12px; margin-top:2px;">
                            <?php echo t('users_see_all_hint') ?: 'Wenn deaktiviert, sieht der Benutzer nur eigene Gegenstände (Feld „Erstellt von")'; ?>
                        </small>
                    </span>
                </label>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeEditUserModal()" class="vs-btn vs-btn-secondary"><?php echo t('btn_cancel') ?: 'Abbrechen'; ?></button>
                <button type="submit" class="vs-btn vs-btn-primary"><?php echo t('users_save_changes') ?: 'Änderungen speichern'; ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Form für Löschen (versteckt) -->
<form id="deleteUserForm" method="POST" style="display: none;">
    <?php echo Security::getCSRFInput(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_user_id">
</form>

<style>
/* Alert Styles */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid;
}

.alert-success {
    background: var(--vs-success-light);
    border-color: var(--vs-success);
    color: var(--vs-success-text);
}

.alert-danger {
    background: var(--vs-danger-light);
    border-color: var(--vs-danger);
    color: var(--vs-danger-text);
}

/* Table Styles */
.backend-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.backend-table thead tr {
    background: var(--bg-color);
    border-bottom: 2px solid var(--border-color);
}

.backend-table th,
.backend-table td {
    padding: 12px;
    text-align: left;
}

.backend-table tbody tr {
    border-bottom: 1px solid var(--border-color);
    transition: background 0.2s;
}

.backend-table tbody tr:hover {
    background: var(--bg-color);
}

/* Button Styles */
.btn-primary {
    padding: 10px 20px;
    background: var(--primary-color);
    color: var(--vs-surface);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: var(--transition);
}

.btn-primary:hover {
    background: var(--primary-dark);
}

.btn-secondary {
    padding: 10px 20px;
    background: var(--text-muted);
    color: var(--vs-surface);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: var(--transition);
}

.btn-secondary:hover {
    background: var(--vs-text-muted);
}

.btn-small {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    margin-right: 5px;
    transition: var(--transition);
}

.btn-edit {
    background: var(--vs-accent);
    color: var(--vs-surface);
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.btn-edit:hover {
    background: var(--vs-accent-hover);
}

.btn-delete {
    background: var(--vs-danger);
    color: var(--vs-surface);
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.btn-delete:hover {
    background: var(--vs-danger);
}

/* Badge Styles */
.badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.badge-danger {
    background: #fee;
    color: var(--vs-danger);
}

.badge-warning {
    background: var(--vs-warning-light);
    color: var(--vs-warning-text);
}

.badge-info {
    background: var(--vs-accent-light);
    color: var(--vs-accent);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: var(--vs-surface);
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: var(--text-color);
}

.modal-close {
    font-size: 28px;
    cursor: pointer;
    color: var(--text-muted);
    line-height: 1;
}

.modal-close:hover {
    color: var(--text-color);
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    padding: 15px 25px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text-color);
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 14px;
    transition: var(--transition);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
</style>

<script>
// Modal Funktionen
function showAddUserModal() {
    document.getElementById('addUserModal').classList.add('active');
}

function closeAddUserModal() {
    document.getElementById('addUserModal').classList.remove('active');
}

function updateSiehtAlleVisibility() {
    var role = document.getElementById('edit_role').value;
    var group = document.getElementById('sieht_alle_group');
    var cb = document.getElementById('edit_sieht_alle');
    if (role === 'admin') {
        group.style.display = 'none';
        cb.checked = true;
    } else {
        group.style.display = 'block';
    }
}

function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_sieht_alle').checked = (user.sieht_alle == 1 || user.role === 'admin');
    updateSiehtAlleVisibility();
    document.getElementById('editUserModal').classList.add('active');
}

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.remove('active');
}

function deleteUser(id, username) {
    vsConfirm('<?php echo t("users_confirm_delete") ?: "Benutzer"; ?> "' + username + '" <?php echo t("users_confirm_delete_text") ?: "wirklich löschen?\\n\\nDiese Aktion kann nicht rückgängig gemacht werden!"; ?>').then(function (ja) {
        if (!ja) return;
        document.getElementById('delete_user_id').value = id;
        document.getElementById('deleteUserForm').submit();
    });
}

// Modal schließen bei Klick außerhalb
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}
</script>

<?php include 'layout/footer_next_page.php'; ?>
