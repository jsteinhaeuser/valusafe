<?php
/**
 * Backend Configuration - COMPLETE VERSION
 * Alle Funktionen enthalten!
 */

// WICHTIG: Erst die Haupt-config.php laden (hat DB-Konstanten)
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

// Dann db.php laden (braucht die DB-Konstanten von oben)
try {
    if (file_exists(__DIR__ . '/../db.php')) {
        require_once __DIR__ . '/../db.php';
    }
} catch (Exception $e) {
    error_log("Backend: Fehler beim Laden von db.php: " . $e->getMessage());
}

// helpers_permissions.php laden
try {
    if (file_exists(__DIR__ . '/../helpers_permissions.php')) {
        require_once __DIR__ . '/../helpers_permissions.php';
    }
} catch (Exception $e) {
    error_log("Backend: Fehler beim Laden von helpers_permissions.php: " . $e->getMessage());
}

// Backend-Sprachdateien laden (ergänzt die Frontend-Translations)
if (!isset($translations)) $translations = [];
$_backendLang = $_SESSION['lang'] ?? 'de';
$_backendLangFile = __DIR__ . '/lang/' . $_backendLang . '.php';
// Fallback auf EN wenn Sprache nicht verfügbar.
// backend/lang/ existiert bewusst nur in de und en: Bei Administratoren wird
// Englisch vorausgesetzt, statt das Backend in alle neun Sprachen zu übersetzen.
// Der Fallback stand bis 4.3.1 auf de.php und lieferte damit ausgerechnet das
// Gegenteil — ein spanischer Admin sah ein deutsches Backend.
if (!file_exists($_backendLangFile)) {
    $_backendLangFile = __DIR__ . '/lang/en.php';
}
// Zweite Stufe: fehlt auch en.php, greift de.php. Ohne diese Stufe laedt die
// Bedingung darunter gar nichts und das Backend zeigt nur noch Schluesselnamen —
// schlechter als jede falsche Sprache. Zaehlt besonders fuer Installationen,
// die jemand selbst aufsetzt und bei denen lang/ unvollstaendig sein kann.
if (!file_exists($_backendLangFile)) {
    $_backendLangFile = __DIR__ . '/lang/de.php';
}
if (file_exists($_backendLangFile)) {
    $translations = array_merge($translations, require $_backendLangFile);
}
unset($_backendLang, $_backendLangFile);

// Backend-Konstanten
if (!defined('BACKEND_VERSION')) {
    define('BACKEND_VERSION', '1.0');
}
if (!defined('BACKEND_TITLE')) {
    define('BACKEND_TITLE', 'Admin-Backend');
}

// Pruefe Admin-Zugriff
if (!function_exists('requireBackendAccess')) {
function requireBackendAccess() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        die('<h1>Zugriff verweigert</h1>
             <p>Dieses Backend ist nur fuer Administratoren zugaenglich.</p>
             <p>Deine Rolle: ' . ($_SESSION['role'] ?? 'unbekannt') . '</p>
             <p><a href="../index.php">Zurueck zur Anwendung</a></p>');
    }
}
}

if (!function_exists('getBackendNav')) {
function getBackendNav() {
    return [
        'index.php' => ['icon' => '📊', 'label' => 'Dashboard'],
        'users.php' => ['icon' => '👥', 'label' => 'Benutzer'],
        'categories.php' => ['icon' => '🏷️', 'label' => 'Kategorien'],
        'locations.php' => ['icon' => '📍', 'label' => 'Orte'],
        'login_security.php' => ['icon' => '🔒', 'label' => 'Login-Security'],
        'logs.php' => ['icon' => '📋', 'label' => 'Logs'],
        'stats.php' => ['icon' => '📈', 'label' => 'Statistiken'],
        'system.php' => ['icon' => '⚙️', 'label' => 'System'],
    ];
}
}

if (!function_exists('isActivePage')) {
function isActivePage($page) {
    return basename($_SERVER['PHP_SELF']) === $page;
}
}

if (!function_exists('getSystemStats')) {
function getSystemStats() {
    global $db, $pdo;
    
    $stats = [
        'users_total' => 0,
        'items_total' => 0,
        'total_value' => 0,
        'categories_total' => 0,
        'locations_total' => 0,
        'documents' => 0,
        'activities_24h' => 0,
        'db_size' => 0,
        'upload_size' => 0,
        'php_version' => PHP_VERSION,
        'mysql_version' => 'unknown'
    ];
    
    if (!isset($db)) return $stats;
    
    try {
        $result = $db->selectOne("SELECT COUNT(*) as count FROM users");
        $stats['users_total'] = $result['count'] ?? 0;
        
        $result = $db->selectOne("SELECT COUNT(*) as count FROM wertsachen");
        $stats['items_total'] = $result['count'] ?? 0;
        
        $result = $db->selectOne("SELECT SUM(preis) as total FROM wertsachen");
        $stats['total_value'] = $result['total'] ?? 0;
        
        $result = $db->selectOne("SELECT COUNT(*) as count FROM kategorien");
        $stats['categories_total'] = $result['count'] ?? 0;
        
        $result = $db->selectOne("SELECT COUNT(*) as count FROM raeume");
        $stats['locations_total'] = $result['count'] ?? 0;
        
        try {
            $result = $db->selectOne("SELECT COUNT(*) as count FROM dokumente");
            $stats['documents'] = $result['count'] ?? 0;
        } catch (Exception $e) {}
        
        try {
            $result = $db->selectOne("SELECT COUNT(*) as count FROM activity_log WHERE zeitstempel > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $stats['activities_24h'] = $result['count'] ?? 0;
        } catch (Exception $e) {}
        
        if (isset($pdo)) {
            try {
                $result = $pdo->query("SELECT SUM(data_length + index_length) as size FROM information_schema.TABLES WHERE table_schema = DATABASE()")->fetch();
                $stats['db_size'] = $result['size'] ?? 0;
            } catch (Exception $e) {}
            
            try {
                $result = $pdo->query("SELECT VERSION() as version")->fetch();
                $stats['mysql_version'] = $result['version'] ?? 'unknown';
            } catch (Exception $e) {}
        }
        
        $uploadDir = __DIR__ . '/../upload/';
        if (is_dir($uploadDir)) {
            $size = 0;
            $files = glob($uploadDir . '*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file)) $size += filesize($file);
                }
            }
            $stats['upload_size'] = $size;
        }
        
    } catch (Exception $e) {
        error_log("Backend getSystemStats error: " . $e->getMessage());
    }
    
    return $stats;
}
}

// ALLE HELPER-FUNKTIONEN:

if (!function_exists('formatBytes')) {
function formatBytes($bytes, $precision = 2) {
    if ($bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
}

if (!function_exists('formatFileSize')) {
function formatFileSize($bytes) {
    return formatBytes($bytes, 2);
}
}

if (!function_exists('formatPrice')) {
function formatPrice($price, $decimals = 2) {
    return number_format($price, $decimals, ',', '.') . ' €';
}
}

if (!function_exists('formatNumber')) {
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}
}

if (!function_exists('getActionClass')) {
function getActionClass($action) {
    $classes = [
        'created' => 'success',
        'updated' => 'info',
        'deleted' => 'danger',
        'login' => 'success',
        'logout' => 'warning'
    ];
    return $classes[$action] ?? 'default';
}
}

if (!function_exists('getActionIcon')) {
function getActionIcon($action) {
    $icons = [
        'created' => '➕',
        'updated' => '✏️',
        'deleted' => '🗑️',
        'login' => '🔓',
        'logout' => '🔒'
    ];
    return $icons[$action] ?? '📝';
}
}

if (!function_exists('getActionText')) {
function getActionText($action) {
    $texts = [
        'created' => t('act_created') ?: 'created',
        'updated' => t('act_updated') ?: 'edited',
        'deleted' => t('act_deleted') ?: 'deleted',
        'login'   => t('act_login')   ?: 'logged in',
        'logout'  => t('act_logout')  ?: 'logged out',
    ];
    return $texts[$action] ?? $action;
}
}

if (!function_exists('timeAgo')) {
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60)      return t('time_just_now')   ?: 'just now';
    if ($diff < 3600)    return floor($diff / 60)    . ' ' . (t('time_min')   ?: 'min');
    if ($diff < 86400)   return floor($diff / 3600)  . ' ' . (t('time_hours') ?: 'h');
    if ($diff < 604800)  return floor($diff / 86400) . ' ' . (t('time_days')  ?: 'd');
    if ($diff < 2592000) return floor($diff / 604800). ' ' . (t('time_weeks') ?: 'w');
    
    return date('d.m.Y', $timestamp);
}
}

if (!function_exists('percentage')) {
function percentage($value, $total) {
    if ($total == 0) return 0;
    return round(($value / $total) * 100, 1);
}
}

if (!function_exists('safeOutput')) {
function safeOutput($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
}

/**
 * Activity Logging Funktion
 * Loggt Aktionen ins activity_log
 */
if (!function_exists('logActivity')) {
function logActivity($action, $table, $recordId = null, $recordName = null, $oldValues = null, $newValues = null) {
    global $db;
    
    // Nur loggen wenn User eingeloggt ist
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    try {
        $db->execute(
            "INSERT INTO activity_log 
             (user_id, aktion, tabelle, datensatz_id, bezeichnung, alt_wert, neu_wert, ip_adresse) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $_SESSION['user_id'],
                $action,
                $table,
                $recordId,
                $recordName,
                $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]
        );
    } catch (PDOException $e) {
        // Log-Fehler sollen den normalen Ablauf nicht stören
        error_log("Activity Log Error: " . $e->getMessage());
    }
}
}

/*
 * Hier stand bis 4.3.16 die Lizenzmechanik: getLicenceStatus() fragte
 * einmal taeglich licence.palindrom.de nach Gueltigkeit, Stufe und
 * Restlaufzeit und legte die Antwort in app_settings ab; resetLicenceCache()
 * leerte diesen Zwischenspeicher.
 *
 * Sie ist entfallen, weil ValuSafe unter der MIT-Lizenz veroeffentlicht
 * wird. Eine Software, die andere selbst betreiben, darf nicht bei ihrem
 * Urheber anfragen, ob sie laufen darf — und eine Lizenz, die Nutzung,
 * Aenderung und Weitergabe ausdruecklich erlaubt, hat nichts zu pruefen.
 * Nebenbei verschwindet damit ein Rueckkanal, der bei jedem Aufruf die
 * Domain der Instanz uebertrug.
 *
 * Eine etwaige Konstante LICENCE_KEY in der config.php einer Instanz wird
 * nicht mehr gelesen und kann dort entfallen. Migration 006 raeumt die
 * fuenf licence_*-Zeilen aus app_settings.
 */

