<?php
// logout.php
require_once 'db.php';
require_once 'security.php';

// Logout loggen (vor Session-Zerstörung)
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    logSecurityEvent($pdo, 'logout', $_SESSION['user_id'], 'Benutzer: ' . $_SESSION['username']);
}

session_destroy();
header('Location: login.php');
exit;
?>