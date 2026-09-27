<?php
/**
 * user_activity.php — "Who is online?" Tracking
 * Wird von header.php eingebunden, NACHDEM db.php und Sprache geladen sind.
 * Vollständig isoliert: Fehler hier brechen nie die Hauptseite.
 */

// Nur bei eingeloggten Usern, nicht bei AJAX/POST/sync
if (!isset($_SESSION['user_id']))             return;
if (defined('SKIP_ACTIVITY_TRACKING'))        return;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') return;
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) return;

// Sicher ausführen — kein Fehler darf nach oben propagieren
set_error_handler(function() { return true; }); // Warnings unterdrücken
try {
    $__page = basename($_SERVER['PHP_SELF'] ?? 'unknown');
    $__ip   = $_SERVER['REMOTE_ADDR'] ?? null;
    $__role = $_SESSION['role'] ?? 'read';
    $__uid  = (int)$_SESSION['user_id'];
    $__user = $_SESSION['username'] ?? '';

    $pdo->prepare("
        INSERT INTO user_activity (user_id, username, role, last_seen, current_page, ip_address)
        VALUES (?, ?, ?, NOW(), ?, ?)
        ON DUPLICATE KEY UPDATE
            last_seen    = NOW(),
            current_page = VALUES(current_page),
            ip_address   = VALUES(ip_address),
            role         = VALUES(role)
    ")->execute([$__uid, $__user, $__role, $__page, $__ip]);
} catch (\Throwable $e) {
    // Tabelle fehlt, DB-Problem, alles → still ignorieren
}
restore_error_handler();
unset($__page, $__ip, $__role, $__uid, $__user);
