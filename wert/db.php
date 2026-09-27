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

// Session nur starten wenn Headers noch nicht gesendet
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

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

/**
 * Prüft ob der aktuelle User in der EFFEKTIVEN Rolle Admin ist.
 *
 * Wichtig: Beim Rollenwechsel (switch_role.php) zaehlt die gewechselte Rolle,
 * nicht die urspruengliche. Frueher wurde hier original_role gelesen — dadurch
 * behielt ein Admin, der zum Testen auf "read" wechselte, saemtliche Rechte,
 * weil hasPermission() bei isAdmin() sofort mit true aussteigt. Der Wechsel
 * war damit rein kosmetisch und als Testwerkzeug unbrauchbar.
 *
 * Wer die urspruengliche Rolle braucht — etwa um den Zurueck-Schalter
 * anzuzeigen oder switch_role.php selbst zu schuetzen — nutzt isRealAdmin().
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Prüft die URSPRUENGLICHE Rolle, unabhaengig von einem aktiven Rollenwechsel.
 * Nur fuer Funktionen verwenden, die waehrend eines Wechsels erreichbar
 * bleiben muessen — sonst sperrt sich der Admin selbst aus.
 */
function isRealAdmin(): bool {
    $rolle = $_SESSION['original_role'] ?? $_SESSION['role'] ?? '';
    return $rolle === 'admin';
}

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
// -------------------------------------------------------
// Permission-Cache (pro Request)
// -------------------------------------------------------
$_permissionCache = null;

function _loadPermissions() {
    global $pdo, $_permissionCache;
    if ($_permissionCache !== null) return $_permissionCache;
    try {
        $rows = $pdo->query("SELECT action_key, admin_can, edit_can, read_can FROM permissions")->fetchAll(PDO::FETCH_ASSOC);
        $_permissionCache = [];
        foreach ($rows as $r) {
            $_permissionCache[$r['action_key']] = $r;
        }
    } catch (PDOException $e) {
        // Tabelle noch nicht vorhanden → leeres Array → Fallback greift
        $_permissionCache = [];
    }
    return $_permissionCache;
}

/**
 * hasPermission('items_delete') → true/false je nach Rolle der Session.
 * Fallback: wenn DB-Tabelle fehlt, gelten hardcodierte Defaults.
 */
function hasPermission(string $key): bool {
    if (isAdmin()) return true;

    $role  = $_SESSION['role'] ?? 'read';
    $perms = _loadPermissions();

    if (empty($perms)) {
        return _permissionFallback($key, $role);
    }

    if (!isset($perms[$key])) return false;

    $col = match(true) {
        in_array($role, ['editor', 'edit', 'editieren']) => 'edit_can',
        default                                           => 'read_can',
    };
    return (bool)($perms[$key][$col] ?? false);
}

function requirePermission(string $key): void {
    if (!hasPermission($key)) {
        http_response_code(403);
        $msg = 'Sie haben keine Berechtigung für diesen Bereich.';
        if (function_exists('showPermissionDenied')) {
            showPermissionDenied($msg);
        } else {
            echo '<div style="padding:40px; text-align:center; color:#dc2626;">🚫 ' . htmlspecialchars($msg) . '</div>';
        }
        exit;
    }
}

/** Fallback wenn DB-Tabelle noch nicht existiert */
function _permissionFallback(string $key, string $role): bool {
    $isEdit = in_array($role, ['editor', 'edit', 'editieren']);
    $defaults = [
        'items_view'        => ['edit' => true,  'read' => true],
        'items_search'      => ['edit' => true,  'read' => true],
        'items_add'         => ['edit' => true,  'read' => false],
        'items_edit'        => ['edit' => true,  'read' => false],
        'items_delete'      => ['edit' => false, 'read' => false],
        'items_hide'        => ['edit' => true,  'read' => false],
        'items_value'       => ['edit' => true,  'read' => false],
        'bulk_delete'       => ['edit' => false, 'read' => false],
        'bulk_hide'         => ['edit' => true,  'read' => false],
        'bulk_update'       => ['edit' => true,  'read' => false],
        'media_view'        => ['edit' => true,  'read' => true],
        'media_upload'      => ['edit' => true,  'read' => false],
        'media_delete'      => ['edit' => true,  'read' => false],
        'docs_view'         => ['edit' => true,  'read' => true],
        'docs_upload'       => ['edit' => true,  'read' => false],
        'docs_delete'       => ['edit' => true,  'read' => false],
        'export_pdf'        => ['edit' => true,  'read' => true],
        'export_csv'        => ['edit' => true,  'read' => true],
        'export_html'       => ['edit' => true,  'read' => true],
        'export_insurance'  => ['edit' => true,  'read' => true],
        'export_qr'         => ['edit' => true,  'read' => true],
        'settings_theme'    => ['edit' => true,  'read' => true],
        'settings_lang'     => ['edit' => true,  'read' => true],
        'settings_columns'  => ['edit' => true,  'read' => true],
        'settings_password' => ['edit' => true,  'read' => true],
    ];
    if (!isset($defaults[$key])) return false;
    return $isEdit ? $defaults[$key]['edit'] : $defaults[$key]['read'];
}

/**
 * Prüft ob aktueller User bearbeiten darf (Admin oder Editor)
 */
function canEdit(): bool {
    if (isAdmin()) return true;
    return hasPermission('items_edit');
}

/**
 * Prüft ob aktueller User löschen darf
 */
function canDelete(): bool {
    if (isAdmin()) return true;
    return hasPermission('items_delete');
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

if (!function_exists('loadLanguage')) {
    function loadLanguage() {
        global $translations;
        
        if (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = 'de';
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

// ============================================================================
// DATABASE KLASSE - WICHTIG!
// ============================================================================

class Database {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function select($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database select error: " . $e->getMessage());
            return [];
        }
    }
    
    public function selectOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database selectOne error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * SCHREIBENDE METHODEN WERFEN WEITER (seit 4.3.18).
     *
     * Bis 4.3.17 fingen insert() und execute() die PDOException hier ab,
     * schrieben ins PHP-Fehlerlog und lieferten false. Beim Aufrufer kam nie
     * eine Ausnahme an - und weil kein einziger der damals 70 execute()- und
     * 11 insert()-Aufrufe den Rueckgabewert prueft, blieb jeder
     * fehlgeschlagene Schreibvorgang stumm. Die Seiten meldeten Erfolg.
     *
     * Aufgefallen am 06.09.2026: auf Instanzen, deren Tabelle versicherungen
     * die 2026 hinzugekommenen Spalten nicht hatte, lehnte MySQL das UPDATE
     * ab; "Versicherung aktualisiert." erschien trotzdem in Gruen.
     *
     * 51 der 70 execute()-Aufrufe und alle 11 insert()-Aufrufe stehen bereits
     * in einem try-Block. Deren catch-Zweige waren nie tot gedacht, sondern
     * nur tot: sie setzen Fehlermeldungen, machen Transaktionen rueckgaengig
     * (delete.php) und raeumen hochgeladene Dateien wieder weg
     * (manage_documents.php). Das Weiterwerfen macht sie wirksam.
     *
     * Das error_log bleibt: es nennt die SQL-Ursache, die der Aufrufer dem
     * Nutzer nicht zeigen soll.
     *
     * select() und selectOne() sind bewusst NICHT umgestellt. Ein
     * fehlgeschlagenes select() sieht zwar aus wie ein leeres Inventar, hat
     * aber 202 Aufrufstellen - eigene Entscheidung, eigenes Release.
     */
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database insert error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function update($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /** Siehe den Hinweis ueber insert(): wirft seit 4.3.18 weiter. */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database execute error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollBack();
    }
}

// WICHTIG: $db initialisieren!
$db = new Database($pdo);

// Activity-Tracking → siehe user_activity.php (separates Modul)
?>
