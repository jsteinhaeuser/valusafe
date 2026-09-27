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
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($host && strpos($referer, $host) === false) {
    $referer = 'index.php';
}

// Redirect
header('Location: ' . $referer);
exit;
