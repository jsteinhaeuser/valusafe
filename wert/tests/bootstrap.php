<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../sync_helpers.php';
require_once __DIR__ . '/../RateLimiter.php';

// sync_agent.php definiert diese Konstanten nur zur Laufzeit eines echten
// Requests (nach Token-Prüfung). Für Tests bilden wir die produktiven
// Default-Werte hier nach — bei Änderungen an den Defaults in sync_agent.php
// bitte auch hier anpassen, sonst laufen die Tests gegen veraltete Regeln.
//
// Achtung: wert.palindrom.de weicht hier bewusst von valusafe.palindrom.de
// ab — kein 'db_check.php' in EXCLUDED_FILES (dort gibt es diese Datei
// nicht). Nicht angleichen, ohne vorher zu prüfen, ob db_check.php auf
// dieser Instanz existiert.
if (!defined('EXCLUDED_DIRS'))   define('EXCLUDED_DIRS',   ['upload', 'backup', 'documents', 'wert-update']);
if (!defined('EXCLUDED_FILES'))  define('EXCLUDED_FILES',  ['config.php', 'version_agent.php', 'SecurityHeaders.php', 'sync_agent.php', '.htaccess']);
if (!defined('SYNC_EXTENSIONS')) define('SYNC_EXTENSIONS', ['php', 'js', 'css', 'html', 'md', 'json', 'xml', 'svg']);
if (!defined('SYNC_EXCEPTIONS')) define('SYNC_EXCEPTIONS', ['wert-update/version.json', 'backend/config.php']);
require_once __DIR__ . '/../dsgvo_helpers.php';
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../sitzung_helpers.php';
require_once __DIR__ . '/../TOTP.php';

// Geschaeftskern (tests/GeschaeftskernTestCase.php und die Tests dazu).
// db.php selbst nicht: es baut beim Einbinden die MySQL-Verbindung auf.
// Was die Tests daraus brauchen, steht in rechte_helpers.php und Database.php.
// Bilder landen in einem eigenen Ordner unter dem Systemverzeichnis fuer
// temporaere Dateien, nie im echten upload/.
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', sys_get_temp_dir() . '/valusafe-tests-' . getmypid() . '/upload/');
require_once __DIR__ . '/../rechte_helpers.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../helpers_search.php';
require_once __DIR__ . '/../helpers_images.php';
require_once __DIR__ . '/../helpers_csv.php';
require_once __DIR__ . '/../helpers_liste.php';
require_once __DIR__ . '/../helpers_dashboard.php';
require_once __DIR__ . '/../helpers_loeschen.php';

// t() wie in db.php (Rueckfall auf den Schluessel); die Tests setzen
// $GLOBALS['translations'] selbst, wo es auf den Wortlaut ankommt.
if (!function_exists('t')) {
    function t($key) {
        global $translations;
        return $translations[$key] ?? $key;
    }
}
