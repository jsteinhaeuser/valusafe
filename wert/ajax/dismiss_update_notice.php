<?php
// ajax/dismiss_update_notice.php
// Entfernt die Update-Notice aus der Session wenn der Benutzer sie schließt.
ini_set("display_errors", 0);
error_reporting(0);

require_once '../db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['ok' => false]);
    exit;
}

unset($_SESSION['update_notice']);
unset($_SESSION['update_notice_checked']);

echo json_encode(['ok' => true]);
