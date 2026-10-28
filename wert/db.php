<?php
// db.php - FIXED nach DB-Migration
require_once 'config.php';
require_once __DIR__ . '/fehlerziel.php';   // Ziel fuer Fehlermeldungen, muss vor allem anderen stehen
require_once 'security.php';
require_once 'SecurityHeaders.php';

// Security Headers setzen (bevor irgendwas ausgegeben wird)
if (!headers_sent()) {
    SecurityHeaders::setHeaders();
}

// Session nur starten wenn Headers noch nicht gesendet - mit HttpOnly,
// Secure (bei HTTPS) und SameSite (OWASP-A02-001, sitzung_helpers.php)
require_once __DIR__ . '/sitzung_helpers.php';
vsSitzungStarten();

// Session-Timeout prüfen
if (isset($_SESSION['user_id'])) {
    if (!Security::checkSessionTimeout()) {
        if (!headers_sent()) {
            header('Location: /login.php?timeout=1');
            exit;
        }
    }
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch(PDOException $e) {
    Security::logSecurityEvent('database_error', ['message' => $e->getMessage()]);
    die("Datenbankverbindung fehlgeschlagen.");
}

// Sitzung bei jeder Anfrage gegen users abgleichen (OWASP-A07-003, 4.6):
// geloeschtes Konto, geaendertes Passwort oder Hoechstdauer -> abmelden;
// Rolle und Benutzername kommen aus der Datenbank. Siehe sitzung_helpers.php.
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT username, role, password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $zeile = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $sitzungStand = vsSitzungPruefen($_SESSION, $zeile, time(), vsSitzungHoechstdauer());
    } catch (PDOException $e) {
        // Lieber abmelden als mit ungeprueften Rechten weiterarbeiten.
        error_log('Sitzungsabgleich: ' . $e->getMessage());
        $sitzungStand = 'fehler';
    }
    if ($sitzungStand !== 'ok') {
        Security::logSecurityEvent('session_ended', [
            'grund'    => $sitzungStand,
            'user_id'  => (int)$_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
        ]);
        session_unset();
        session_destroy();
        if (!headers_sent()) {
            header('Location: /login.php?timeout=1');
        }
        exit;
    }
    unset($stmt, $zeile, $sitzungStand);
}

// Hier stand bis 4.3.3 eine zweite, per mysqli aufgebaute Datenbankverbindung
// ("fuer mysqli-Kompatibilitaet"). Sie wurde von keiner einzigen Stelle der
// Anwendung genutzt, oeffnete aber bei JEDEM Seitenaufruf eine zusaetzliche
// Verbindung — auf allen Instanzen. Im Fehlerfall gab sie zudem die rohe
// Verbindungsmeldung per die() an den Browser aus.
// Die Docker-Installationsanleitung 3.22 fuehrte sie als ersten von vier
// kritischen Eingriffen: im Container stoerte sie den Anmeldevorgang.

if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'cloud';
}

function isLoggedIn() {
    if (!isset($_SESSION['user_id']) ||
        !isset($_SESSION['username']) ||
        !isset($_SESSION['login_time'])) {
        return false;
    }
    // Session-Fingerprint prüfen (Session Hijacking-Schutz)
    if (!Security::validateSessionFingerprint()) {
        session_unset();
        session_destroy();
        if (!headers_sent()) {
            header('Location: /login.php?error=session');
        }
        exit;
    }
    return true;
}

function syncUserTheme() {
    global $pdo;
    if (isLoggedIn()) {
        try {
            $stmt = $pdo->prepare("SELECT theme FROM users WHERE username = ?");
            $stmt->execute([$_SESSION['username']]);
            $user = $stmt->fetch();
            
            if ($user && !empty($user['theme'])) {
                $_SESSION['theme'] = $user['theme'];
            }
        } catch (PDOException $e) {
            // Fehler ignorieren
        }
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        if (!headers_sent()) {
            header('Location: /login.php');
            exit;
        } else {
            // Wenn Headers schon gesendet (z.B. AJAX), JSON-Error
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
                (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false)) {
                echo json_encode(['success' => false, 'error' => 'Not logged in']);
                exit;
            }
            exit('Not logged in');
        }
    }
    
    // Role aus DB nachladen falls nicht in Session (PDO)
    if (!isset($_SESSION['role']) && isset($_SESSION['user_id'])) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $_SESSION['role'] = $row['role'];
                if (!isset($_SESSION['username'])) {
                    $_SESSION['username'] = $row['username'];
                }
            }
        } catch (Exception $e) {
            // Fehler still loggen, kein Output
            error_log("requireLogin role lookup failed: " . $e->getMessage());
        }
    }
}

// isAdmin(), isRealAdmin(), hasPermission() und die uebrigen Rechte-
// funktionen stehen seit 4.6 in rechte_helpers.php (ohne Seiteneffekte,
// damit die Tests sie einbinden koennen). Verhalten unveraendert.
require_once __DIR__ . '/rechte_helpers.php';

function isSuperAdmin(): bool {
    if (!isset($_SESSION['username'])) return false;
    if (!defined('SUPERADMIN_USERNAME')) return false;
    // Unterstützt einzelnen String oder kommagetrennte Liste
    $admins = array_map('trim', explode(',', SUPERADMIN_USERNAME));
    return in_array($_SESSION['username'], $admins, true);
}

/**
 * Prüft ob eine SuperAdmin-Einstellung aktiv ist.
 * Fallback: true (erlaubt) wenn Einstellung nicht gesetzt.
 */
function getSuperAdminSetting(string $key, bool $default = true): bool {
    global $db;
    try {
        $row = $db->selectOne(
            "SELECT setting_value FROM app_settings WHERE setting_key = ?",
            [$key]
        );
        if (!$row) return $default;
        return $row['setting_value'] === '1';
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Fordert Admin-Berechtigung
 */
function requireAdmin() {
    requireLogin();
    // isRealAdmin(): waehrend eines Rollenwechsels muss der Admin
    // switch_role.php weiterhin erreichen, um zurueckzuwechseln.
    if (!isRealAdmin()) {
        die('Zugriff verweigert. Admin-Berechtigung erforderlich.');
    }
}

/**
 * Gibt es auf dieser Instanz ueberhaupt einen SuperAdmin?
 *
 * SUPERADMIN_USERNAME steht in der config.php der jeweiligen Instanz. Fehlt
 * sie oder ist sie leer, besteht niemand die Pruefung isSuperAdmin() — dann
 * ist der Admin die oberste Stufe, die es dort gibt.
 */
function hatSuperAdmin(): bool {
    if (!defined('SUPERADMIN_USERNAME')) return false;
    foreach (explode(',', SUPERADMIN_USERNAME) as $name) {
        if (trim($name) !== '') return true;
    }
    return false;
}

/**
 * Wie requireAdmin(), nur eine Stufe hoeher. Fuer Vorgaenge, die sich nicht
 * rueckgaengig machen lassen und den Nachweis betreffen, wer was getan hat.
 *
 * Auf Instanzen ohne eingerichteten SuperAdmin faellt die Pruefung auf Admin
 * zurueck. Der Sinn der hoeheren Stufe ist, dass eine NIEDRIGERE Rolle nicht
 * kann, was die hoehere schuetzt. Gibt es die hoehere gar nicht, ist der
 * Admin die oberste Instanz — dann waere die Pruefung keine Absicherung,
 * sondern wuerde die Funktion nur fuer alle sperren.
 */
function requireSuperAdmin() {
    requireLogin();
    if (isSuperAdmin()) return;
    if (!hatSuperAdmin() && isRealAdmin()) return;
    die('Zugriff verweigert. SuperAdmin-Berechtigung erforderlich.');
}

/**
 * Fuer die Anzeige: darf dieser Benutzer die geschuetzten Vorgaenge sehen?
 * Dieselbe Regel wie requireSuperAdmin(), nur ohne Abbruch — damit Knoepfe
 * dort erscheinen, wo sie auch funktionieren.
 */
function darfSuperAdminAktionen(): bool {
    return isSuperAdmin() || (!hatSuperAdmin() && isRealAdmin());
}

/**
 * Alias für Rückwärtskompatibilität — entspricht canEdit()-Prüfung
 */
function requireEditPermission() {
    requireLogin();
    if (!function_exists('canEdit') || !canEdit()) {
        header('Location: index.php');
        exit;
    }
}

function validateRequest() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!Security::validateCSRFToken($token)) {
            die('Ungültiges Sicherheits-Token.');
        }
    }
}

if (file_exists(__DIR__ . '/lang/language.php')) {
    require_once __DIR__ . '/lang/language.php';
}

// Die zehn Sprachen der Anwendung (wert/lang/*.php; ga = Irisch seit 4.5)
const VS_SPRACHEN = ['de', 'en', 'fr', 'es', 'it', 'nl', 'pl', 'pt', 'tr', 'ga'];

/** Sprache ohne Sitzung: Cookie user_language, dann Accept-Language, dann 'de'. */
function vsErkannteSprache(): string {
    $cookie = $_COOKIE['user_language'] ?? '';
    if (in_array($cookie, VS_SPRACHEN, true)) return $cookie;
    // "en-GB,en;q=0.9,de;q=0.8" - Reihenfolge der Angaben genuegt, die
    // Browser schreiben sie ohnehin nach Gewichtung absteigend.
    foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') as $teil) {
        $code = strtolower(substr(trim($teil), 0, 2));
        if (in_array($code, VS_SPRACHEN, true)) return $code;
    }
    return 'de';
}

if (!function_exists('loadLanguage')) {
    function loadLanguage() {
        global $translations;
        
        if (!isset($_SESSION['lang'])) {
            // Noch keine Sitzungssprache (z. B. Anmeldeseite): bis 4.3.32 immer
            // 'de' - englische Besucher der Demo sahen zuerst Deutsch. Jetzt:
            // gemerktes Cookie, sonst Browsersprache, sonst Deutsch. Nach der
            // Anmeldung gilt die im Konto gespeicherte Sprache (login.php).
            $_SESSION['lang'] = vsErkannteSprache();
        }
        
        $currentLang = $_SESSION['lang'];
        $langFile = __DIR__ . '/lang/' . $currentLang . '.php';
        
        if (file_exists($langFile)) {
            $translations = require $langFile;
        }
        
        return $currentLang;
    }
}

if (!function_exists('t')) {
    function t($key) {
        global $translations;
        return $translations[$key] ?? $key;
    }
}

$currentLang = loadLanguage();

function getLanguageFlags() {
    return ['de' => '🇩🇪', 'en' => '🇬🇧'];
}

function getLanguageNames() {
    return ['de' => 'Deutsch', 'en' => 'English'];
}

// FIXED: Verwende 'role' statt 'berechtigung'

function getUserRole() {
    return $_SESSION['role'] ?? 'read';
}

function getRoleBadge($role) {
    $badges = [
        'admin' => '<span class="role-badge role-admin">🔥 Admin</span>',
        'edit' => '<span class="role-badge role-editor">✏️ Editor</span>',
        'read' => '<span class="role-badge role-readonly">👁️ Viewer</span>'
    ];
    return $badges[$role] ?? '';
}

// Klasse Database steht seit 4.6 in Database.php (siehe dort).
require_once __DIR__ . '/Database.php';

// WICHTIG: $db initialisieren!
$db = new Database($pdo);

// Activity-Tracking ("Wer ist online", user_activity.php) am 27.10.2026 ausgebaut:
// war seit dem Wechsel auf header_next*.php ohne Einbindung und damit tot.
?>
