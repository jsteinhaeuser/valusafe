<?php
// service_doc.php – Liefert den Inhalt einer einzelnen .md Datei (nur Text, kein HTML)
require_once 'db.php';
requireLogin();

header('Content-Type: text/plain; charset=utf-8');

$serviceDir = realpath(__DIR__ . '/service');
$requested  = $_GET['file'] ?? '';

// PHP-Typ VOR Sicherheits-Check abfangen (kein Datei-Lookup nötig)
$ext = strtolower(pathinfo($requested, PATHINFO_EXTENSION));
if ($ext === 'php') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['redirect' => $requested]);
    exit;
}

// Sicherheit: nur einfache Dateinamen, keine Pfad-Traversal
if (!$requested || !preg_match('/^[\w\/\-]+\.(md|pdf|php)$/i', $requested)) {
    http_response_code(400);
    echo '# Ungültige Anfrage';
    exit;
}

$fullPath = $serviceDir . DIRECTORY_SEPARATOR . $requested;

// Sicherheit: Datei muss wirklich im service-Verzeichnis liegen
if (!$serviceDir || strpos(realpath($fullPath) ?: '', $serviceDir) !== 0) {
    http_response_code(403);
    echo '# Zugriff verweigert';
    exit;
}

if (!file_exists($fullPath)) {
    http_response_code(404);
    echo '# Datei nicht gefunden';
    exit;
}

if ($ext === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo file_get_contents($fullPath);
}
