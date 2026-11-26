<?php
/**
 * Nur fuer PHPStan (phpstan.neon.dist, bootstrapFiles): die Konstanten, die
 * auf einer Instanz in config.php stehen. config.php legt install/setup.php
 * bei der Einrichtung an, im Repository gibt es sie nicht - ohne diese Datei
 * meldet PHPStan jede Verwendung als "Constant not found".
 * Gleiche Namen und Typen wie in install/setup.php; die Werte sind Platzhalter.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'valusafe');
define('DB_USER', 'valusafe');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('APP_NAME', 'ValuSafe');
define('APP_TAGLINE', 'Inventarverwaltung für Wertgegenstände');
define('APP_LANG', 'de');
define('APP_URL', '');
define('SESSION_LIFETIME', 3600);
define('SESSION_NAME', 'valusafe_session');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('UPLOAD_DIR', __DIR__ . '/upload/');
define('BACKUP_DIR', __DIR__ . '/backups/');
define('UPLOAD_MAX_SIZE', 10485760);
define('MAX_FILE_SIZE', 10485760);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/jpg', 'image/heic', 'image/heif']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif']);
define('BACKUP_TOKEN', '');
define('BACKUP_RETENTION_DAYS', 7);
define('SUPERADMIN_USERNAME', 'admin');

// Fuer version_agent.php: stehen sonst in sync_agent.php, das nicht im
// oeffentlichen Repository liegt (gleiche Werte wie tests/bootstrap.php)
if (!defined('EXCLUDED_DIRS'))   define('EXCLUDED_DIRS',   ['upload', 'backup', 'documents', 'wert-update']);
if (!defined('EXCLUDED_FILES'))  define('EXCLUDED_FILES',  ['config.php', 'version_agent.php', 'SecurityHeaders.php', 'sync_agent.php', '.htaccess']);
if (!defined('SYNC_EXTENSIONS')) define('SYNC_EXTENSIONS', ['php', 'js', 'css', 'html', 'md', 'json', 'xml', 'svg']);
if (!defined('SYNC_EXCEPTIONS')) define('SYNC_EXCEPTIONS', ['wert-update/version.json', 'backend/config.php']);
