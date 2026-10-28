<?php
/**
 * rechte_helpers.php - Rollen und Rechte (isAdmin, hasPermission ...)
 *
 * Bis 4.6 standen diese Funktionen in db.php. db.php baut beim Einbinden die
 * MySQL-Verbindung auf und startet die Sitzung - so liessen sich die Rechte
 * nicht automatisch pruefen. Unveraendert hierher verschoben, damit
 * tests/RechteTest.php sie ohne Datenbankserver einbinden kann. db.php bindet
 * die Datei an der alten Stelle ein; global $pdo und $_SESSION gelten wie
 * bisher.
 */


/**
 * Prüft ob der aktuelle User in der EFFEKTIVEN Rolle Admin ist.
 *
 * Wichtig: Beim Rollenwechsel (switch_role.php) zaehlt die gewechselte Rolle,
 * nicht die urspruengliche. Frueher wurde hier original_role gelesen — dadurch
 * behielt ein Admin, der zum Testen auf "read" wechselte, saemtliche Rechte,
 * weil hasPermission() bei isAdmin() sofort mit true aussteigt. Der Wechsel
 * war damit rein kosmetisch und als Testwerkzeug unbrauchbar.
 *
 * Wer die urspruengliche Rolle braucht — etwa um den Zurueck-Schalter
 * anzuzeigen oder switch_role.php selbst zu schuetzen — nutzt isRealAdmin().
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Prüft die URSPRUENGLICHE Rolle, unabhaengig von einem aktiven Rollenwechsel.
 * Nur fuer Funktionen verwenden, die waehrend eines Wechsels erreichbar
 * bleiben muessen — sonst sperrt sich der Admin selbst aus.
 */
function isRealAdmin(): bool {
    $rolle = $_SESSION['original_role'] ?? $_SESSION['role'] ?? '';
    return $rolle === 'admin';
}

// Rechte, die die Rechteverwaltung nicht mehr anbietet (OWASP-A01-006, 4.6):
// Sie liessen sich schalten, wirkten aber nirgends. Eine Leserrolle ohne
// Leserecht ergibt in ValuSafe keinen Sinn, und die Suche zu sperren schuetzt
// nichts, was die Liste nicht ohnehin zeigt. Die Zeilen in permissions
// bleiben liegen (keine Migration noetig).
if (!defined('VS_RECHTE_AUSGEBLENDET')) {
    define('VS_RECHTE_AUSGEBLENDET', ['items_view', 'items_search']);
}

// -------------------------------------------------------
// Permission-Cache (pro Request)
// -------------------------------------------------------
$_permissionCache = null;

function _loadPermissions() {
    global $pdo, $_permissionCache;
    if ($_permissionCache !== null) return $_permissionCache;
    try {
        $rows = $pdo->query("SELECT action_key, admin_can, edit_can, read_can FROM permissions")->fetchAll(PDO::FETCH_ASSOC);
        $_permissionCache = [];
        foreach ($rows as $r) {
            $_permissionCache[$r['action_key']] = $r;
        }
    } catch (PDOException $e) {
        // Tabelle noch nicht vorhanden → leeres Array → Fallback greift
        $_permissionCache = [];
    }
    return $_permissionCache;
}

/**
 * hasPermission('items_delete') → true/false je nach Rolle der Session.
 * Fallback: wenn DB-Tabelle fehlt, gelten hardcodierte Defaults.
 */
function hasPermission(string $key): bool {
    if (isAdmin()) return true;

    $role  = $_SESSION['role'] ?? 'read';
    $perms = _loadPermissions();

    if (empty($perms)) {
        return _permissionFallback($key, $role);
    }

    // Zeile fehlt (z. B. neues Recht, Migration noch nicht gelaufen): Vorgabe
    // aus _permissionFallback() statt still "verboten". Unbekannte Schluessel
    // bleiben dort weiter false.
    if (!isset($perms[$key])) return _permissionFallback($key, $role);

    $col = match(true) {
        in_array($role, ['editor', 'edit', 'editieren']) => 'edit_can',
        default                                           => 'read_can',
    };
    return (bool)($perms[$key][$col] ?? false);
}

function requirePermission(string $key): void {
    if (!hasPermission($key)) {
        http_response_code(403);
        $msg = 'Sie haben keine Berechtigung für diesen Bereich.';
        if (function_exists('t') && t('user_no_permission') !== 'user_no_permission') {
            $msg = t('user_no_permission');
        }
        if (function_exists('showPermissionDenied')) {
            showPermissionDenied($msg);
        } else {
            echo '<div style="padding:40px; text-align:center; color:#dc2626;">🚫 ' . htmlspecialchars($msg) . '</div>';
        }
        exit;
    }
}

/** Fallback wenn DB-Tabelle noch nicht existiert */
function _permissionFallback(string $key, string $role): bool {
    $isEdit = in_array($role, ['editor', 'edit', 'editieren']);
    $defaults = [
        'items_view'        => ['edit' => true,  'read' => true],
        'items_search'      => ['edit' => true,  'read' => true],
        'items_add'         => ['edit' => true,  'read' => false],
        'items_edit'        => ['edit' => true,  'read' => false],
        'items_delete'      => ['edit' => false, 'read' => false],
        'items_hide'        => ['edit' => true,  'read' => false],
        'items_value'       => ['edit' => true,  'read' => false],
        'bulk_delete'       => ['edit' => false, 'read' => false],
        'bulk_hide'         => ['edit' => true,  'read' => false],
        'bulk_update'       => ['edit' => true,  'read' => false],
        'media_view'        => ['edit' => true,  'read' => true],
        'media_upload'      => ['edit' => true,  'read' => false],
        'media_delete'      => ['edit' => true,  'read' => false],
        'docs_view'         => ['edit' => true,  'read' => true],
        'docs_upload'       => ['edit' => true,  'read' => false],
        'docs_delete'       => ['edit' => true,  'read' => false],
        'export_pdf'        => ['edit' => true,  'read' => true],
        'export_csv'        => ['edit' => true,  'read' => true],
        'export_html'       => ['edit' => true,  'read' => true],
        'export_insurance'  => ['edit' => true,  'read' => true],
        'export_qr'         => ['edit' => true,  'read' => true],
        'settings_theme'    => ['edit' => true,  'read' => true],
        'settings_lang'     => ['edit' => true,  'read' => true],
        'settings_columns'  => ['edit' => true,  'read' => true],
        'settings_password' => ['edit' => true,  'read' => true],
        'settings_delete_account' => ['edit' => true,  'read' => true],
        // Schadensfall-Assistent (4.4, Migration 014)
        'claims_view'       => ['edit' => true,  'read' => true],
        'claims_manage'     => ['edit' => true,  'read' => false],
    ];
    if (!isset($defaults[$key])) return false;
    return $isEdit ? $defaults[$key]['edit'] : $defaults[$key]['read'];
}

/**
 * Prüft ob aktueller User bearbeiten darf (Admin oder Editor)
 */
function canEdit(): bool {
    if (isAdmin()) return true;
    return hasPermission('items_edit');
}

/**
 * Prüft ob aktueller User löschen darf
 */
function canDelete(): bool {
    if (isAdmin()) return true;
    return hasPermission('items_delete');
}
