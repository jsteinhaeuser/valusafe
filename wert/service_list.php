<?php
// service_list.php – Gibt die Liste der .md/.pdf/.php Dateien im /service/ Verzeichnis als JSON zurück
require_once 'db.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$serviceDir = __DIR__ . '/service/';

if (!is_dir($serviceDir)) {
    echo json_encode([]);
    exit;
}

// Icons anhand von Dateinamen-Schlüsselwörtern vergeben
function guessIcon(string $name, string $ext): string {
    if ($ext === 'pdf') return '📕';
    if ($ext === 'php') return '🔄';
    $name = strtolower($name);
    if (strpos($name, 'manual') !== false || strpos($name, 'handbuch') !== false) return '📖';
    if (strpos($name, 'fact') !== false || strpos($name, 'feature') !== false) return '⭐';
    if (strpos($name, 'roadmap') !== false || strpos($name, 'todo') !== false || strpos($name, 'plan') !== false) return '🗓️';
    if (strpos($name, 'about') !== false || strpos($name, 'ueber') !== false) return 'ℹ️';
    if (strpos($name, 'changelog') !== false || strpos($name, 'update') !== false) return '🔄';
    if (strpos($name, 'faq') !== false) return '❓';
    if (strpos($name, 'sicher') !== false || strpos($name, 'security') !== false) return '🔐';
    return '📄';
}

// Anzeigename aus Dateinamen ableiten
function makeLabel(string $filename): string {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = str_replace(['-', '_'], ' ', $name);
    return ucwords($name);
}

$mdFiles  = glob($serviceDir . '*.md')  ?: [];
$pdfFiles = glob($serviceDir . '*.pdf') ?: [];
$phpFiles = glob($serviceDir . '*.php') ?: [];
$files = array_merge($mdFiles, $pdfFiles, $phpFiles);

if (!$files) {
    echo json_encode([]);
    exit;
}

// Sortierung: alphabetisch, about.md immer zuletzt
usort($files, function($a, $b) {
    $aName = strtolower(basename($a));
    $bName = strtolower(basename($b));
    $aAbout = strpos($aName, 'about') !== false;
    $bAbout = strpos($bName, 'about') !== false;
    if ($aAbout && !$bAbout) return 1;
    if (!$aAbout && $bAbout) return -1;
    return strcmp($aName, $bName);
});

$result = [];
foreach ($files as $filepath) {
    $filename = basename($filepath);
    $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $result[] = [
        'file'  => $filename,
        'label' => makeLabel($filename),
        'icon'  => guessIcon($filename, $ext),
        'type'  => $ext,
    ];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
