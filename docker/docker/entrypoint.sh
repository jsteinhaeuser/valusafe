#!/bin/bash
set -e

# =============================================================================
# ValuSafe — Docker-Entrypoint
#
# Laeuft bei JEDEM Start des Anwendungscontainers. Erzeugt die config.php neu,
# legt beim allerersten Start das Administratorkonto an und haelt den
# BACKUP_TOKEN ueber Neustarts hinweg stabil.
# =============================================================================

ADMIN_USER="${ADMIN_USER:-admin}"
TOKEN_FILE="/var/www/html/backups/.backup_token"

# ── Warte auf Datenbank UND auf das eingespielte Schema ───────────────────────
# Es genuegt nicht, auf eine Verbindung zu warten: init.sql laeuft im
# Datenbankcontainer und ist unter Umstaenden noch nicht durch, wenn der Server
# bereits Verbindungen annimmt. Wer hier nur die Verbindung prueft, startet
# gelegentlich eine Anwendung ohne Tabellen — die Kontoanlage weiter unten
# scheitert dann still, und es existiert kein Konto, mit dem man sich anmelden
# koennte. Nach einem Neustart geht es, weil das Schema dann steht. Genau dieses
# Verhalten ist in der Praxis schon aufgetreten.
# Deshalb wird auf die Tabelle users gewartet, nicht auf den Server.
echo "[Entrypoint] Warte auf Datenbank und Schema..."
WAIT_MAX=90
WAIT_N=0
until php -r "
    try {
        \$pdo = new PDO('mysql:host=${DB_HOST};dbname=${DB_NAME}', '${DB_USER}', '${DB_PASS}');
        \$pdo->query('SELECT 1 FROM users LIMIT 1');
        echo 'OK';
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null | grep -q OK; do
    WAIT_N=$((WAIT_N + 1))
    if [ "$WAIT_N" -ge "$WAIT_MAX" ]; then
        echo ""
        echo "[Entrypoint] FEHLER: Nach $((WAIT_MAX * 2)) Sekunden ist die Tabelle 'users'"
        echo "             immer noch nicht vorhanden."
        echo ""
        echo "  Moegliche Ursachen:"
        echo "   - init.sql wurde nicht ausgefuehrt, weil das Datenbank-Volume bereits"
        echo "     Daten enthielt. Docker spielt das Schema nur bei LEEREM Volume ein."
        echo "     Pruefen mit:  docker compose logs db"
        echo "   - Die Zugangsdaten in der .env passen nicht zur bestehenden Datenbank."
        echo ""
        echo "  Vollstaendiger Neuanfang (loescht ALLE Daten dieser Installation):"
        echo "     docker compose down -v && docker compose up -d"
        echo ""
        exit 1
    fi
    if [ $((WAIT_N % 5)) -eq 1 ]; then
        echo "[Entrypoint] Noch nicht bereit (Versuch ${WAIT_N}/${WAIT_MAX})..."
    fi
    sleep 2
done
echo "[Entrypoint] Datenbank erreichbar, Schema vorhanden."

# ── BACKUP_TOKEN ──────────────────────────────────────────────────────────────
# Der Token schuetzt cron_backup.php, also den vollstaendigen Datenbank-Export.
# Frueher wurde er als md5(DB_PASS . DB_NAME) abgeleitet — mit den Vorgabewerten
# aus docker-compose.yml war er damit oeffentlich ausrechenbar. Jetzt: entweder
# aus der .env, oder beim ersten Start zufaellig erzeugt und im backups-Volume
# abgelegt, damit er einen Neustart ueberlebt (sonst braechen eingerichtete
# Cron-Aufrufe bei jedem Neustart).
mkdir -p "$(dirname "$TOKEN_FILE")"
if [ -n "${BACKUP_TOKEN:-}" ]; then
    echo "[Entrypoint] BACKUP_TOKEN aus der Umgebung uebernommen."
elif [ -s "$TOKEN_FILE" ]; then
    BACKUP_TOKEN="$(cat "$TOKEN_FILE")"
    echo "[Entrypoint] BACKUP_TOKEN aus $TOKEN_FILE gelesen."
else
    BACKUP_TOKEN="bk_$(head -c 18 /dev/urandom | od -An -tx1 | tr -d ' \n')"
    printf '%s' "$BACKUP_TOKEN" > "$TOKEN_FILE"
    chmod 600 "$TOKEN_FILE"
    chown www-data:www-data "$TOKEN_FILE"
    echo "[Entrypoint] Neuen BACKUP_TOKEN erzeugt und in $TOKEN_FILE abgelegt."
fi

# ── .htaccess HTTPS-Redirect deaktivieren (hinter Reverse Proxy) ──────────────
if [ -f /var/www/html/.htaccess ]; then
    sed -i 's/^RewriteCond %{HTTPS} off/#RewriteCond %{HTTPS} off/' /var/www/html/.htaccess
    sed -i '/RewriteRule.*https:\/\//s/^/# /' /var/www/html/.htaccess
    echo "[Entrypoint] .htaccess HTTPS-Redirect deaktiviert."
fi

# ── SecurityHeaders.php: X-Forwarded-Proto deaktivieren ───────────────────────
if [ -f /var/www/html/SecurityHeaders.php ]; then
    sed -i "s/(!empty(\$_SERVER\['HTTP_X_FORWARDED_PROTO'\]) && \$_SERVER\['HTTP_X_FORWARDED_PROTO'\] == 'https')/false/" /var/www/html/SecurityHeaders.php
    echo "[Entrypoint] SecurityHeaders.php X-Forwarded-Proto deaktiviert."
fi

# ── config.php generieren ─────────────────────────────────────────────────────
echo "[Entrypoint] Erstelle config.php..."
cat > /var/www/html/config.php << EOF
<?php
// config.php — automatisch generiert beim Docker-Start
// Nicht manuell bearbeiten — Werte kommen aus der .env Datei!

define('DB_HOST',    '${DB_HOST}');
define('DB_NAME',    '${DB_NAME}');
define('DB_USER',    '${DB_USER}');
define('DB_PASS',    '${DB_PASS}');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME',    '${APP_NAME:-ValuSafe}');
define('APP_TAGLINE', 'Inventarverwaltung für Wertgegenstände');

// Ohne diese Konstante liefert isSuperAdmin() immer false und die
// Benutzerverwaltung unter backend/users.php ist fuer niemanden erreichbar.
define('SUPERADMIN_USERNAME', '${ADMIN_USER}');

define('CSRF_TOKEN_NAME',    'csrf_token');
define('SESSION_NAME',       'valusafe_session');
define('SESSION_LIFETIME',   3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

define('UPLOAD_DIR',    __DIR__ . '/upload/');
define('BACKUP_DIR',    __DIR__ . '/backups/');
define('UPLOAD_MAX_SIZE', 20 * 1024 * 1024);
define('MAX_FILE_SIZE', 20 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'image/heic', 'image/heif', 'application/octet-stream'
]);
define('ALLOWED_EXTENSIONS', ['jpg','jpeg','png','gif','webp','heic','heif']);

define('BACKUP_TOKEN', '${BACKUP_TOKEN}');

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure',   isset(\$_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
ini_set('display_errors', 0);
ini_set('log_errors',     1);
error_reporting(E_ALL);

require_once __DIR__ . '/lang/language.php';
EOF

chown www-data:www-data /var/www/html/config.php
echo "[Entrypoint] config.php erstellt."

# ── Administratorkonto beim ersten Start anlegen ──────────────────────────────
# init.sql legt bewusst keine Benutzer an. Frueher standen dort drei Demokonten
# mit fest eingebauten Hashes, darunter der allgemein bekannte Hash von
# "password" fuer das Administratorkonto.
ADMIN_RESULT="$(ADMIN_USER="$ADMIN_USER" ADMIN_PASS="${ADMIN_PASS:-}" php -r '
$user = getenv("ADMIN_USER");
$pass = getenv("ADMIN_PASS");
$generiert = false;

try {
    $pdo = new PDO(
        "mysql:host=" . getenv("DB_HOST") . ";dbname=" . getenv("DB_NAME") . ";charset=utf8mb4",
        getenv("DB_USER"), getenv("DB_PASS"),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    fwrite(STDERR, "FEHLER: " . $e->getMessage() . "\n");
    exit(1);
}

try {
    $anzahl = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
} catch (Exception $e) {
    fwrite(STDERR, "FEHLER beim Lesen der Benutzertabelle: " . $e->getMessage() . "\n");
    exit(1);
}
if ($anzahl > 0) {
    echo "VORHANDEN\n";
    exit(0);
}

if ($pass === "" || $pass === false) {
    // 18 Byte -> 24 Zeichen Base64, ohne Sonderzeichen die in Shells stoeren
    $pass = rtrim(strtr(base64_encode(random_bytes(18)), "+/", "Xy"), "=");
    $generiert = true;
}

// Die Anwendung hasht mit PASSWORD_ARGON2ID (backend/users.php, profil.php).
// Fehlt Argon2 in dieser PHP-Fassung, faellt password_hash sonst mit einem
// Fehler aus — deshalb der ausdrueckliche Rueckfall auf das Standardverfahren.
$algo = defined("PASSWORD_ARGON2ID") ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
try {
    $hash = password_hash($pass, $algo);
} catch (Throwable $e) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $algo = PASSWORD_DEFAULT;
}

$stmt = $pdo->prepare(
    "INSERT INTO users (username, password, role, sieht_alle, dokumente_aktiv, lang)
     VALUES (?, ?, \"admin\", 1, 1, ?)"
);
$stmt->execute([$user, $hash, substr(getenv("APP_LANG") ?: "de", 0, 5)]);

// Migrationsstand vorbelegen, wie es setup.php auch tut. init.sql IST der
// aktuelle Stand; ohne diesen Eintrag holt migrate.php beim ersten Aufruf
// alle Migrationen nach. Bis 26.09.2026 fehlte das hier. Harmlos war es nur,
// solange keine Migration Daten veraenderte, die die Anwendung liest:
// 005 fuellt standort_id mit Raumnummern, 012 leert die Spalte wieder -
// nachgeholt auf einer benutzten Installation loescht 012 echte Standorte.
// Leere Benutzertabelle heisst: erster Start, frisch eingespieltes Schema.
$merken = $pdo->prepare(
    "INSERT IGNORE INTO schema_migrations (migration, note) VALUES (?, ?)"
);
foreach (glob("/var/www/html/backend/migrations/*.php") ?: [] as $datei) {
    $merken->execute([basename($datei, ".php"), "beim Aufsetzen bereits im Schema enthalten"]);
}

printf("ANGELEGT\t%s\t%s\t%s\n", $user, $generiert ? $pass : "(aus ADMIN_PASS)",
       is_string($algo) ? $algo : "bcrypt");
' 2>&1)"

case "$ADMIN_RESULT" in
    VORHANDEN*)
        echo "[Entrypoint] Benutzertabelle ist nicht leer — kein Konto angelegt."
        ;;
    ANGELEGT*)
        A_USER="$(printf '%s' "$ADMIN_RESULT" | cut -f2)"
        A_PASS="$(printf '%s' "$ADMIN_RESULT" | cut -f3)"
        echo ""
        echo "  ============================================================"
        echo "   ValuSafe — Administratorkonto angelegt"
        echo ""
        echo "     Benutzer:  ${A_USER}"
        echo "     Passwort:  ${A_PASS}"
        echo ""
        echo "   Dieses Passwort wird nur EINMAL angezeigt. Notiere es jetzt"
        echo "   oder aendere es nach der ersten Anmeldung unter Profil."
        echo "  ============================================================"
        echo ""
        ;;
    *)
        # Ohne Konto ist die Anwendung nicht bedienbar. Lieber hier abbrechen —
        # der Container startet dann laut docker-compose.yml neu und versucht es
        # erneut — als eine Anmeldemaske auszuliefern, an der niemand vorbeikommt.
        echo ""
        echo "[Entrypoint] FEHLER: Das Administratorkonto konnte weder geprueft"
        echo "             noch angelegt werden. Ausgabe des Pruefschritts:"
        echo "$ADMIN_RESULT"
        echo ""
        exit 1
        ;;
esac

# ── Verzeichnis-Berechtigungen ────────────────────────────────────────────────
chown -R www-data:www-data \
    /var/www/html/upload \
    /var/www/html/documents \
    /var/www/html/backups \
    /var/www/html/logs

echo "[Entrypoint] Starte Apache..."
exec "$@"
