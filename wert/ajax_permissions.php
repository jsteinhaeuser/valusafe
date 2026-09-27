<?php
/**
 * ajax_permissions.php
 * AJAX-Endpoint: Einzelne Berechtigung per Toggle speichern.
 * Nur Admin, CSRF-geschützt.
 */
define('AJAX_REQUEST', true);
ini_set('display_errors', 0);
ob_start();

require_once 'db.php';
require_once 'helpers.php';
require_once 'security.php';

header('Content-Type: application/json; charset=utf-8');

// Auth
if (!isAdmin()) {
    ob_end_clean();
    echo json_encode(['ok' => false, 'error' => 'Kein Zugriff']);
    exit;
}

// CSRF
$token = $_POST['csrf_token'] ?? '';
if (!Security::validateCSRFToken($token)) {
    ob_end_clean();
    echo json_encode(['ok' => false, 'error' => 'Ungültige Anfrage']);
    exit;
}

$action_key = trim($_POST['action_key'] ?? '');
$role       = trim($_POST['role']       ?? '');
$value      = (int)($_POST['value']     ?? 0);

// Validierung
$allowed_roles = ['edit', 'read']; // admin immer 1, nicht änderbar
if (!preg_match('/^[a-z_]{3,40}$/', $action_key) || !in_array($role, $allowed_roles)) {
    ob_end_clean();
    echo json_encode(['ok' => false, 'error' => 'Ungültige Parameter']);
    exit;
}

// Prüfen ob locked
$col = $role . '_can';
try {
    $stmt = $pdo->prepare("SELECT locked_for FROM permissions WHERE action_key = ?");
    $stmt->execute([$action_key]);
    $row = $stmt->fetch();
    if (!$row) {
        ob_end_clean();
        echo json_encode(['ok' => false, 'error' => 'Aktion nicht gefunden']);
        exit;
    }
    $locked = array_filter(array_map('trim', explode(',', $row['locked_for'] ?? '')));
    if (in_array($role, $locked)) {
        ob_end_clean();
        echo json_encode(['ok' => false, 'error' => 'Diese Berechtigung kann nicht geändert werden']);
        exit;
    }

    // Speichern
    $stmt = $pdo->prepare("UPDATE permissions SET {$col} = ? WHERE action_key = ?");
    $stmt->execute([$value ? 1 : 0, $action_key]);

    // Aktivitätslog
    try {
        $logMsg = "Berechtigung '{$action_key}' fuer Rolle '{$role}' auf " . ($value ? 'JA' : 'NEIN') . " gesetzt";
        $logStmt = $pdo->prepare("INSERT INTO activity_log (user_id, aktion, tabelle, datensatz_id, bezeichnung, alt_wert, neu_wert, ip_adresse) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $logStmt->execute([$_SESSION['user_id'] ?? 0, 'permission_changed', 'permissions', null, $logMsg, null, null, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (PDOException $e) { /* Log-Fehler ignorieren */ }

    ob_end_clean();
    echo json_encode(['ok' => true]);

} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(['ok' => false, 'error' => 'DB-Fehler: ' . $e->getMessage()]);
}
exit;
