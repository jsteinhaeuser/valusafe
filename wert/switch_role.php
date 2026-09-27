<?php
// switch_role.php - Rollenwechsel für Admin-Tests
require_once 'db.php';
requireLogin();
requireAdmin();
validateRequest();

if (isset($_POST['restore'])) {
    // Zurück zur Original-Rolle
    if (isset($_SESSION['original_role'])) {
        $_SESSION['role'] = $_SESSION['original_role'];
        unset($_SESSION['original_role']);
    }
    header('Location: settings.php?msg=role_restored');
    exit;
}

if (isset($_POST['role'])) {
    $allowed = ['editor', 'read'];
    $newRole = $_POST['role'];

    if (in_array($newRole, $allowed)) {
        // Original-Rolle merken (falls noch nicht gesetzt)
        if (!isset($_SESSION['original_role'])) {
            $_SESSION['original_role'] = $_SESSION['role'];
        }
        $_SESSION['role'] = $newRole;
        Security::logSecurityEvent('role_switched', ['from' => $_SESSION['original_role'], 'to' => $newRole]);
    }
}

header('Location: settings.php');
exit;
