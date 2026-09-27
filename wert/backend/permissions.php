<?php
/**
 * backend/permissions.php
 * Dynamische Berechtigungsverwaltung – Admin only.
 * Liest aus DB-Tabelle 'permissions', speichert per AJAX.
 */
require_once '../db.php';
require_once '../helpers.php';
requireAdmin();

define('PAGE_TITLE', t('perm_page_title'));
$currentPage = basename(__FILE__);
include 'layout/header_next_page.php';

// Berechtigungen aus DB laden
$permsBySection = [];
$dbOk = false;
try {
    $rows = $pdo->query("SELECT * FROM permissions ORDER BY sort_order ASC")->fetchAll();
    foreach ($rows as $r) {
        $permsBySection[$r['section']][] = $r;
    }
    $dbOk = true;
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

$sectionIcons = [
    'items'    => ['icon' => '<i class="ti ti-package"></i>',  'label' => 'perm_group_items'],
    'bulk'     => ['icon' => '<i class="ti ti-checkbox"></i>', 'label' => 'perm_group_bulk'],
    'media'    => ['icon' => '<i class="ti ti-camera"></i>',   'label' => 'perm_group_media'],
    'export'   => ['icon' => '<i class="ti ti-upload"></i>',   'label' => 'perm_group_export'],
    'settings' => ['icon' => '<i class="ti ti-settings"></i>', 'label' => 'perm_group_settings'],
    'admin'    => ['icon' => '<i class="ti ti-key"></i>',      'label' => 'perm_group_admin'],
];

// Übersetzungs-Map: action_key → lang-Key
$actionLabelMap = [
    'items_view'       => 'perm_act_items_view',
    'items_search'     => 'perm_act_items_search',
    'items_add'        => 'perm_act_items_add',
    'items_edit'       => 'perm_act_items_edit',
    'items_delete'     => 'perm_act_items_delete',
    'items_hide'       => 'perm_act_items_hide',
    'items_value'      => 'perm_act_items_value',
    'bulk_delete'      => 'perm_act_bulk_delete',
    'bulk_hide'        => 'perm_act_bulk_hide',
    'bulk_update'      => 'perm_act_bulk_update',
    'media_view'       => 'perm_act_media_view',
    'media_upload'     => 'perm_act_media_upload',
    'media_delete'     => 'perm_act_media_delete',
    'docs_view'        => 'perm_act_docs_view',
    'docs_upload'      => 'perm_act_docs_upload',
    'docs_delete'      => 'perm_act_docs_delete',
    'export_pdf'       => 'perm_act_export_pdf',
    'export_csv'       => 'perm_act_export_csv',
    'export_html'      => 'perm_act_export_html',
    'export_insurance' => 'perm_act_export_insurance',
    'export_qr'        => 'perm_act_export_qr',
    'settings_theme'   => 'perm_act_settings_theme',
    'settings_lang'    => 'perm_act_settings_lang',
    'settings_columns' => 'perm_act_settings_columns',
    'settings_password'=> 'perm_act_settings_password',
    'admin_users'      => 'perm_act_admin_users',
    'admin_categories' => 'perm_act_admin_categories',
    'admin_locations'  => 'perm_act_admin_locations',
    'admin_logs'       => 'perm_act_admin_logs',
    'admin_backup'     => 'perm_act_admin_backup',
    'admin_permissions'=> 'perm_act_admin_permissions',
    'admin_restore'    => 'perm_act_admin_restore',
];

$csrfToken = Security::generateCSRFToken();
?>

<main class="backend-main">
<div class="backend-content">

    <div class="vs-page-header" style="margin-bottom:var(--vs-sp-6);">
        <h1><i class="ti ti-shield-lock"></i> <?php echo t('perm_page_heading'); ?></h1>
        <p style="color:#666; margin-top:4px;"><?php echo t('perm_checkbox_hint'); ?> <?php echo t('perm_admin_fixed_note'); ?></p>
    </div>

    <style>
        .perm-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background: var(--vs-surface);
            border-radius: var(--vs-r-lg);
            overflow: hidden;
            box-shadow: var(--vs-shadow-resting);
        }
        .perm-table thead tr {
            background: var(--vs-accent);
            color: var(--vs-surface);
        }
        .perm-table thead th {
            padding: 14px 18px;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.3px;
        }
        .perm-table thead th:first-child { text-align: left; }
        .perm-table tbody tr {
            border-bottom: 1px solid var(--vs-border);
            transition: background 0.15s;
        }
        .perm-table tbody tr:hover { background: var(--vs-accent-light); }
        .perm-table tbody tr.section-header { background: var(--vs-accent-light); }
        .perm-table tbody tr.section-header td {
            padding: 10px 18px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--vs-accent);
        }
        .perm-table tbody td {
            padding: 11px 18px;
            vertical-align: middle;
        }
        .perm-table tbody td:not(:first-child) { text-align: center; }
        .perm-label {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .perm-icon { font-size: 16px; width: 24px; text-align: center; }

        /* Checkbox-Styling */
        .perm-check {
            appearance: none;
            -webkit-appearance: none;
            width: 22px;
            height: 22px;
            border-radius: var(--vs-r-sm);
            border: 2px solid var(--vs-border);
            cursor: pointer;
            position: relative;
            display: inline-block;
            vertical-align: middle;
            transition: all 0.15s;
        }
        .perm-check:checked {
            border-color: var(--vs-success);
            background: var(--vs-success);
        }
        .perm-check:checked::after {
            content: '';
            position: absolute;
            left: 5px;
            top: 2px;
            width: 8px;
            height: 12px;
            border: 2px solid var(--vs-surface);
            border-top: none;
            border-left: none;
            transform: rotate(45deg);
        }
        .perm-check:disabled {
            cursor: default;
            opacity: 0.6;
        }
        .perm-check.locked {
            cursor: not-allowed;
            opacity: 0.55;
        }
        .perm-check.saving {
            border-color: var(--vs-warning);
            background: var(--vs-warning);
            animation: pulse 0.6s ease-in-out infinite alternate;
        }
        @keyframes pulse { from { opacity: 1; } to { opacity: 0.5; } }
        .perm-check.error {
            border-color: var(--vs-danger);
            background: var(--vs-danger);
        }

        /* Rollen-Badges */
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: var(--vs-r-pill);
            font-weight: 600;
            font-size: 13px;
        }
        .role-admin  { background: var(--vs-danger-light); color: var(--vs-danger-text); }
        .role-editor { background: var(--vs-accent-light); color: var(--vs-accent); }
        .role-read   { background: var(--vs-success-light); color: var(--vs-success-text); }

        /* Toast */
        #perm-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: var(--vs-success);
            color: var(--vs-surface);
            padding: 12px 20px;
            border-radius: var(--vs-r-lg);
            font-size: 14px;
            font-weight: 600;
            box-shadow: var(--vs-shadow-hovered);
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s;
            z-index: 9999;
            pointer-events: none;
        }
        #perm-toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        #perm-toast.error { background: var(--vs-danger); }

        .install-hint {
            background: var(--vs-warning-light);
            border: 1px solid var(--vs-warning);
            border-radius: var(--vs-r-lg);
            padding: 16px 20px;
            margin-bottom: 24px;
            font-size: 14px;
            color: var(--vs-warning-text);
        }
        .install-hint a { color: var(--vs-warning-text); font-weight: 600; }
    </style>

    <?php if (!$dbOk): ?>
    <div class="install-hint">
        <i class="ti ti-alert-triangle" style="color:var(--vs-warning);"></i> Die Tabelle <code>permissions</code> wurde noch nicht angelegt.
        Bitte zuerst <a href="../permissions_install.php">permissions_install.php</a> ausführen.
        <br><small style="opacity:.7">Fehler: <?php echo htmlspecialchars($dbError ?? ''); ?></small>
    </div>
    <?php else: ?>

    <!-- Rollen-Erklärung -->
    <div style="display:flex; gap:12px; margin-bottom:24px; flex-wrap:wrap;">
        <div class="role-badge role-admin"><i class="ti ti-key"></i> <?php echo t('perm_admin_full_access_badge'); ?></div>
        <div class="role-badge role-editor"><i class="ti ti-pencil"></i> <?php echo t('perm_editor_badge'); ?></div>
        <div class="role-badge role-read"><i class="ti ti-eye"></i> <?php echo t('perm_reader_badge'); ?></div>
    </div>

    <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
    <table class="perm-table" style="min-width:480px;">
        <thead>
            <tr>
                <th style="width:46%; text-align:left;"><?php echo t('perm_col_action'); ?></th>
                <th style="width:18%;"><i class="ti ti-key"></i> <?php echo t('perm_col_admin'); ?></th>
                <th style="width:18%;"><i class="ti ti-pencil"></i> <?php echo t('perm_col_editor'); ?></th>
                <th style="width:18%;"><i class="ti ti-eye"></i> <?php echo t('perm_col_reader'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($permsBySection as $section => $items): ?>
            <tr class="section-header">
                <td colspan="4">
                    <?php $si = $sectionIcons[$section] ?? null; echo ($si ? $si['icon'] . ' ' . htmlspecialchars(t($si['label'])) : '• ' . htmlspecialchars(strtoupper($section))); ?>
                </td>
            </tr>
            <?php foreach ($items as $p):
                $lockedArr = array_filter(array_map('trim', explode(',', $p['locked_for'] ?? '')));
                $editLocked = in_array('edit', $lockedArr);
                $readLocked = in_array('read', $lockedArr);
            ?>
            <tr>
                <td>
                    <div class="perm-label">
                        <span class="perm-icon"><?php echo $p['icon']; ?></span>
                        <?php $lk = $actionLabelMap[$p['action_key']] ?? null; echo htmlspecialchars($lk ? t($lk) : $p['label']); ?>
                    </div>
                </td>
                <!-- Admin: immer gesetzt, immer disabled -->
                <td>
                    <input type="checkbox" class="perm-check locked" checked disabled
                           title="<?php echo t('perm_admin_full_access'); ?>">
                </td>
                <!-- Editor -->
                <td>
                    <input type="checkbox"
                           class="perm-check <?php echo $editLocked ? 'locked' : 'editable'; ?>"
                           data-key="<?php echo htmlspecialchars($p['action_key']); ?>"
                           data-role="edit"
                           <?php echo $p['edit_can'] ? 'checked' : ''; ?>
                           <?php echo $editLocked ? 'disabled title="Diese Berechtigung ist fest"' : ''; ?>>
                </td>
                <!-- Leser -->
                <td>
                    <input type="checkbox"
                           class="perm-check <?php echo $readLocked ? 'locked' : 'editable'; ?>"
                           data-key="<?php echo htmlspecialchars($p['action_key']); ?>"
                           data-role="read"
                           <?php echo $p['read_can'] ? 'checked' : ''; ?>
                           <?php echo $readLocked ? 'disabled title="Diese Berechtigung ist fest"' : ''; ?>>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div><!-- /.overflow-x:auto -->

    <?php endif; ?>

</div>
</main>

<div id="perm-toast"></div>

<script>
(function() {
    const CSRF = <?php echo json_encode($csrfToken); ?>;
    const toast = document.getElementById('perm-toast');
    let toastTimer;

    function showToast(msg, isError = false) {
        clearTimeout(toastTimer);
        toast.textContent = msg;
        toast.className = 'show' + (isError ? ' error' : '');
        toastTimer = setTimeout(() => { toast.className = ''; }, 2500);
    }

    document.querySelectorAll('.perm-check.editable').forEach(cb => {
        cb.addEventListener('change', function() {
            const key   = this.dataset.key;
            const role  = this.dataset.role;
            const value = this.checked ? 1 : 0;
            const orig  = !this.checked; // vorheriger Zustand

            // Visuell: saving-State
            this.classList.add('saving');
            this.disabled = true;

            const fd = new FormData();
            fd.append('csrf_token', CSRF);
            fd.append('action_key', key);
            fd.append('role',       role);
            fd.append('value',      value);

            fetch('../ajax_permissions.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    this.classList.remove('saving');
                    this.disabled = false;
                    if (data.ok) {
                        showToast('<i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Berechtigung gespeichert');
                    } else {
                        // Rollback
                        this.checked = orig;
                        this.classList.add('error');
                        setTimeout(() => this.classList.remove('error'), 1200);
                        showToast('<i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> ' + (data.error || 'Fehler'), true);
                    }
                })
                .catch(() => {
                    this.classList.remove('saving');
                    this.disabled = false;
                    this.checked = orig;
                    showToast('<i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> Verbindungsfehler', true);
                });
        });
    });
})();
</script>

<?php include 'layout/footer_next_page.php'; ?>
