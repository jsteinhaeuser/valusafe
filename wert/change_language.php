<?php
/**
 * change_language.php - Sprachwechsel-Handler
 * 
 * GET-Parameter:
 * - lang: de oder en
 * 
 * Funktioniert auch ohne Login!
 */

// Session starten falls noch nicht aktiv
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Language-System laden
require_once __DIR__ . '/lang/language.php';

// Gewünschte Sprache aus GET-Parameter
$requestedLang = $_GET['lang'] ?? '';

// Validieren und setzen
if (setLanguage($requestedLang)) {
    // Erfolg: Sprache gesetzt
    $success = true;
} else {
    // Fehler: Ungültige Sprache
    $success = false;
}

// Zurück zur vorherigen Seite oder Index
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';

// Sicherstellen dass wir auf gleicher Domain bleiben
// Hostnamen vergleichen, nicht nur enthalten pruefen: bis 4.3.32 genuegte
// eine fremde Adresse, die den eigenen Namen irgendwo enthielt
// (https://fremd.example/?x=meine-instanz.de), fuer eine Weiterleitung dorthin.
$host    = $_SERVER['HTTP_HOST'] ?? '';
$refHost = parse_url($referer, PHP_URL_HOST);
$refPort = parse_url($referer, PHP_URL_PORT);
if ($refHost !== null && $refHost !== false
    && strcasecmp($refHost . ($refPort ? ':' . $refPort : ''), $host) !== 0) {
    $referer = 'index.php';
}

// Redirect
header('Location: ' . $referer);
exit;
