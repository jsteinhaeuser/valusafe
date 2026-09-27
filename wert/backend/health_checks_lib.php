<?php
/**
 * backend/health_checks_lib.php
 *
 * Gemeinsame Prüf-Logik für den internen Health-Check (backend/health_check.php).
 * Adaptiert aus health_check.php auf dem Hub — dieselben Checks, aber
 * ausgeführt gegen die eigene Instanz statt gegen eine externe Instanzliste.
 *
 * Jeder Check ist bewusst ein eigener HTTP-Request an die eigene Domain
 * (nicht ein direkter PHP-Funktionsaufruf): So wird geprüft, was ein
 * echter Besucher tatsächlich sieht — inklusive .htaccess-Regeln,
 * Server-Konfiguration und Rewrite-Regeln, die ein interner Aufruf
 * umgehen würde.
 */

/**
 * TLS-Optionen fuer die Pruef-Requests.
 *
 * Bis zum 16.09.2026 stand hier CURLOPT_SSL_VERIFYPEER => false. Die
 * Gegenstelle ist zwar die eigene Instanz, aber der Weg dorthin fuehrt ueber
 * das offene Netz — ein Request an die eigene Domain laeuft nicht im Kreis,
 * er geht hinaus und kommt zurueck. Wer dazwischensitzt, konnte dem
 * Health-Check beliebige Antworten unterschieben: "Zugriffsschutz ohne
 * Login: ✓" ist dann keine Messung mehr, sondern eine Behauptung.
 *
 * Die Pruefung ist die Vorgabe. Braucht eine Instanz eine Ausnahme (eigenes
 * Zertifikat, interner Name), traegt sie sie in ihre config.php ein:
 *
 *   define('HC_TLS_AUSNAHME', ['cafile' => __DIR__ . '/nas.crt']);
 *   define('HC_TLS_AUSNAHME', ['name_egal' => true]);
 *
 * 'name_egal' schaltet nur die Namenspruefung ab; die Ausstellerkette wird
 * weiter geprueft, und die haelt jemanden in der Leitung fern.
 */
if (!function_exists('hc_tls_optionen')) {
function hc_tls_optionen(): array {
    $opt = [
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];
    $a = defined('HC_TLS_AUSNAHME') && is_array(HC_TLS_AUSNAHME) ? HC_TLS_AUSNAHME : [];
    if (!empty($a['cafile']))    $opt[CURLOPT_CAINFO]         = $a['cafile'];
    if (!empty($a['name_egal'])) $opt[CURLOPT_SSL_VERIFYHOST] = 0;
    return $opt;
}
}

/**
 * Uebersetzt einen curl-Fehler in eine Meldung, die den Grund nennt.
 *
 * Mit eingeschalteter Pruefung gibt es einen Fehlschlag, den es vorher nicht
 * gab: ein Zertifikat, dem dieses PHP nicht traut. Ohne diese Unterscheidung
 * stuende in der Oberfläche dieselbe rote Zeile wie bei einer Seite, die
 * wirklich kaputt ist — und man suchte am falschen Ende.
 */
if (!function_exists('hc_fehlertext')) {
function hc_fehlertext(int $errno, string $fehler): string {
    // Zahlen statt CURLE_*-Konstanten, und das mit Absicht: die Flotte laeuft
    // auf verschiedenen PHP-Bauten, und nicht jede kennt jeden Namen.
    // CURLE_PEER_FAILED_VERIFICATION etwa gibt es in PHP ueberhaupt nicht —
    // der erste Entwurf dieser Funktion ist daran mit einem Fatal Error
    // gescheitert, und zwar genau dann, wenn ein Zertifikat NICHT stimmt.
    // Eine Fehlerbehandlung, die im Fehlerfall selbst abstuerzt, ist schlimmer
    // als keine. Die Zahlen sind curl-Fehlercodes und seit Jahren stabil.
    if ($errno === 77) {
        // 77 = CA-Vorrat nicht lesbar. Das ist ein Befund ueber DIESE Instanz,
        // nicht ueber das Zertifikat der Gegenstelle.
        return 'Kein lesbarer Zertifikatsspeicher auf dieser Instanz: ' . $fehler;
    }
    // 60 Ausstellerkette oder Name abgelehnt (in aelteren Bauten auch 51),
    // 83 Aussteller passt nicht, 90 angehefteter Schluessel passt nicht.
    if (in_array($errno, [51, 60, 83, 90], true)) {
        return 'Zertifikatspruefung fehlgeschlagen: ' . $fehler;
    }
    return $fehler;
}
}

/**
 * Merker fuer den letzten Transportfehler innerhalb EINES Checks.
 *
 * Wozu: rund zwanzig Pruefungen lesen nur $r['code'] und schliessen daraus.
 * Kommt gar keine Antwort, ist code 0 — und dann behauptet
 *   "version_agent ohne Token"  ->  "HTTP 0 — kein Token-Schutz!"
 *   "db_check.php geloescht"    ->  "Noch erreichbar! HTTP 0"
 * also jeweils das GEGENTEIL dessen, was gemessen wurde. Vor dem 16.09.2026
 * war der Fall selten (DNS, Timeout); mit eingeschalteter
 * Zertifikatspruefung ist er erreichbar geworden.
 *
 * Statt zwanzig Zweige einzeln nachzuruesten — und den einundzwanzigsten
 * beim naechsten Mal zu vergessen — merkt sich hc_do_curl den Fehler, und
 * hc_run_check verwirft das Urteil, wenn einer vorliegt. Unbeantwortet ist
 * weder gruen noch rot, sondern unbeantwortet.
 */
if (!function_exists('hc_transportfehler')) {
function hc_transportfehler(?string $setzen = null, bool $leeren = false): string {
    static $letzter = '';
    if ($leeren) { $letzter = ''; return ''; }
    if ($setzen !== null && $setzen !== '') $letzter = $setzen;
    return $letzter;
}
}

if (!function_exists('hc_do_curl')) {
function hc_do_curl(string $url): array {
    $start = microtime(true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 2,
        CURLOPT_USERAGENT      => 'ValuSafe-HealthCheck/1.0',
        CURLOPT_ENCODING       => '',
    ] + hc_tls_optionen());
    $body   = curl_exec($ch);
    $errno  = curl_errno($ch);
    $fehler = curl_error($ch);
    $code   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $ms = round((microtime(true) - $start) * 1000);
    $meldung = $errno ? hc_fehlertext($errno, $fehler) : '';
    if ($meldung !== '') hc_transportfehler($meldung);
    return ['body' => $body ?: '', 'code' => $code, 'ms' => $ms, 'fehler' => $meldung];
}
}

if (!function_exists('hc_do_curl_headers')) {
function hc_do_curl_headers(string $url): array {
    $start = microtime(true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_NOBODY         => true,
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 2,
        CURLOPT_USERAGENT      => 'ValuSafe-HealthCheck/1.0',
    ] + hc_tls_optionen());
    $raw    = curl_exec($ch);
    $errno  = curl_errno($ch);
    $fehler = curl_error($ch);
    $code   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $ms = round((microtime(true) - $start) * 1000);
    $meldung = $errno ? hc_fehlertext($errno, $fehler) : '';
    if ($meldung !== '') hc_transportfehler($meldung);
    return ['headers' => $raw ?: '', 'code' => $code, 'ms' => $ms, 'fehler' => $meldung];
}
}

if (!function_exists('hc_is_php_error')) {
function hc_is_php_error(array $r): bool {
    return stripos($r['body'], 'Fatal error') !== false
        || stripos($r['body'], 'Parse error') !== false
        || stripos($r['body'], 'Uncaught') !== false
        || $r['code'] === 500;
}
}

if (!function_exists('hc_check_no_fatal')) {
function hc_check_no_fatal(string $url): array {
    $r   = hc_do_curl($url);
    $err = hc_is_php_error($r);
    if ($r['code'] === 0) {
        // Seit dem 16.09.2026 kann hier auch ein Zertifikat der Grund sein.
        // Stuende weiter pauschal "DNS/Timeout" da, suchte man am falschen Ende.
        return ['ok' => false, 'ms' => $r['ms'],
                'detail' => $r['fehler'] ?: 'Keine Verbindung (DNS/Timeout)'];
    }
    if ($r['code'] === 404) {
        return ['ok' => false, 'ms' => $r['ms'], 'detail' => '⚠️ Datei fehlt (HTTP 404)'];
    }
    // CURLOPT_FOLLOWLOCATION folgt bereits einem Redirect zu login.php bei
    // fehlender Anmeldung — am Ende steht dort normalerweise HTTP 200.
    // Alles andere (403, 500, ...) ist ein echtes Problem.
    $ok = !$err && $r['code'] === 200;
    return ['ok' => $ok, 'ms' => $r['ms'],
        'detail' => $err ? 'PHP-Fehler!' : ($ok ? 'HTTP 200' : 'Unerwartet: HTTP ' . $r['code'])];
}
}

if (!function_exists('hc_check_gone')) {
function hc_check_gone(string $url, string $riskNote = ''): array {
    $r = hc_do_curl($url);

    // Kam gar keine Antwort, ist die Frage "ist die Datei weg?" UNBEANTWORTET.
    // Ohne diesen Zweig lautete die Antwort "⚠️ Noch erreichbar! HTTP 0" —
    // also das Gegenteil dessen, was gemessen wurde, und es klaenge nach
    // einem Sicherheitsproblem, wo nur die Leitung stand. Seit der
    // eingeschalteten Zertifikatspruefung ist dieser Fall erreichbar.
    // Dieselbe Sorte Fehler wie am 27.08.2026, als ein Fehlschlag als
    // "0 Unterschiede, alles aktuell" erschien.
    if ($r['code'] === 0) {
        return ['ok' => false, 'ms' => $r['ms'],
            'detail' => 'Unbeantwortet — ' . ($r['fehler'] ?: 'keine Verbindung')];
    }

    $gone = in_array($r['code'], [403, 404]);
    return ['ok' => $gone, 'ms' => $r['ms'],
        'detail' => $gone ? 'HTTP ' . $r['code'] . ' ✓' : '⚠️ Noch erreichbar! HTTP ' . $r['code'] . $riskNote];
}
}

/**
 * Liste aller verfügbaren Checks (id, Label, Kategorie).
 * Wird sowohl für die UI (Zeilen) als auch als Ablaufplan genutzt.
 */
if (!function_exists('hc_check_list')) {
function hc_check_list(): array {
    return [
        // Kritisch
        ['id' => 'login',       'label' => 'Login-Seite erreichbar',            'tag' => 'crit'],
        ['id' => 'backend',     'label' => 'Backend PHP-fehlerfrei',             'tag' => 'crit'],
        // Funktion
        ['id' => 'export_csv',  'label' => 'CSV-Export erreichbar',              'tag' => 'func'],
        ['id' => 'export_pdf',  'label' => 'PDF-Export erreichbar',              'tag' => 'func'],
        ['id' => 'backup',      'label' => 'Backup-Seite erreichbar',            'tag' => 'func'],
        ['id' => 'gallery',     'label' => 'Galerie erreichbar',                 'tag' => 'func'],
        ['id' => 'locations',   'label' => 'Orte-Verwaltung erreichbar',         'tag' => 'func'],
        ['id' => 'settings',    'label' => 'Einstellungen erreichbar',           'tag' => 'func'],
        ['id' => 'twofa',       'label' => '2FA-Setup erreichbar',               'tag' => 'func'],
        ['id' => 'ajax_pos',    'label' => 'AJAX Positionen (JSON-Antwort)',     'tag' => 'func'],
        ['id' => 'manifest',    'label' => 'manifest.json valide',              'tag' => 'func'],
        ['id' => 'sw',          'label' => 'sw.js (Service Worker)',            'tag' => 'func'],
        ['id' => 'offline',     'label' => 'offline.php erreichbar',            'tag' => 'func'],
        // Sicherheit
        ['id' => 'access',                    'label' => 'Zugriffsschutz ohne Login',                'tag' => 'sec'],
        ['id' => 'token_agent',               'label' => 'version_agent ohne Token → 403',           'tag' => 'sec'],
        ['id' => 'token_sync',                'label' => 'sync_agent ohne Token → 403',              'tag' => 'sec'],
        ['id' => 'cron_backup_token',         'label' => 'cron_backup ohne Token → 403',             'tag' => 'sec'],
        ['id' => 'superadmin',                'label' => 'Superadmin ohne Login geschützt',          'tag' => 'sec'],
        ['id' => 'dbcheck',                   'label' => 'db_check.php gelöscht',                    'tag' => 'sec'],
        ['id' => 'debug_files',               'label' => 'Debug-Dateien gelöscht',                   'tag' => 'sec'],
        ['id' => 'init_sqlite_exposed',       'label' => 'init_sqlite.php nicht erreichbar',         'tag' => 'sec'],
        ['id' => 'security_headers',          'label' => 'Security-Header gesetzt',                  'tag' => 'sec'],
        ['id' => 'backup_files_exposed',      'label' => 'Backup-Verzeichnis nicht öffentlich',      'tag' => 'sec'],
        ['id' => 'diagnostic_files_exposed',  'label' => 'Keine Diagnose-Dateien (phpinfo etc.)',    'tag' => 'sec'],
        ['id' => 'config_leak',               'label' => 'config.php ohne Quellcode-Leak',           'tag' => 'sec'],
    ];
}
}

/**
 * Führt genau einen Check gegen $baseUrl aus und liefert
 * ['ok' => bool, 'ms' => int, 'detail' => string].
 */
if (!function_exists('hc_run_check')) {
function hc_run_check(string $checkId, string $baseUrl): array {
    hc_transportfehler(null, true);
    $ergebnis = hc_run_check_roh($checkId, $baseUrl);
    $fehler   = hc_transportfehler();
    if ($fehler === '') return $ergebnis;

    // Es kam keine Antwort. Was der Check daraus geschlossen hat, ist
    // gegenstandslos — auch ein gruener Haken.
    return ['ok' => false, 'ms' => $ergebnis['ms'] ?? 0,
            'detail' => 'Unbeantwortet — ' . $fehler];
}
}

if (!function_exists('hc_run_check_roh')) {
function hc_run_check_roh(string $checkId, string $baseUrl): array {
    $baseUrl = rtrim($baseUrl, '/');

    switch ($checkId) {
        case 'login':
            $r = hc_do_curl($baseUrl . '/login.php');
            $ok = $r['code'] === 200 && stripos($r['body'], 'password') !== false;
            return ['ok' => $ok, 'ms' => $r['ms'],
                'detail' => $ok ? 'HTTP 200 · Formular gefunden' : 'HTTP ' . $r['code'] . ' oder kein Formular'];

        case 'backend':
            return hc_check_no_fatal($baseUrl . '/backend/');

        case 'export_csv':
            return hc_check_no_fatal($baseUrl . '/export_csv.php');

        case 'export_pdf':
            return hc_check_no_fatal($baseUrl . '/export_pdf.php');

        case 'backup':
            return hc_check_no_fatal($baseUrl . '/backend/backup.php');

        case 'gallery':
            return hc_check_no_fatal($baseUrl . '/backend/gallery.php');

        case 'locations':
            return hc_check_no_fatal($baseUrl . '/backend/locations.php');

        case 'settings':
            return hc_check_no_fatal($baseUrl . '/settings.php');

        case 'twofa':
            return hc_check_no_fatal($baseUrl . '/backend/2fa_setup.php');

        case 'ajax_pos':
            $r = hc_do_curl($baseUrl . '/ajax/get_positionen.php');
            $d = json_decode($r['body'], true);
            $ok = $r['code'] !== 500 && $r['body'] !== '' && ($d !== null || $r['code'] === 200);
            return ['ok' => $ok, 'ms' => $r['ms'],
                'detail' => $ok ? 'JSON-Antwort OK' : 'Kein JSON oder Fehler · HTTP ' . $r['code']];

        case 'manifest':
            $r = hc_do_curl($baseUrl . '/manifest.json');
            $d = json_decode($r['body'], true);
            $ok = $r['code'] === 200 && isset($d['name']);
            return ['ok' => $ok, 'ms' => $r['ms'],
                'detail' => $ok ? 'name: ' . $d['name'] : 'HTTP ' . $r['code'] . ' oder kein JSON'];

        case 'sw':
            $r = hc_do_curl($baseUrl . '/sw.js');
            $ok = $r['code'] === 200 && strlen($r['body']) > 100;
            return ['ok' => $ok, 'ms' => $r['ms'],
                'detail' => $ok ? strlen($r['body']) . ' Bytes' : 'HTTP ' . $r['code'] . ' oder leer'];

        case 'offline':
            $r = hc_do_curl($baseUrl . '/offline.php');
            $err = hc_is_php_error($r);
            return ['ok' => !$err && $r['code'] === 200, 'ms' => $r['ms'],
                'detail' => $err ? 'PHP-Fehler!' : 'HTTP ' . $r['code']];

        case 'access':
            $r = hc_do_curl($baseUrl . '/index.php');
            $ok = stripos($r['body'], 'login') !== false || in_array($r['code'], [301, 302]);
            return ['ok' => $ok, 'ms' => $r['ms'],
                'detail' => $ok ? 'Redirect auf Login ✓' : '⚠️ Zugang ohne Login möglich! HTTP ' . $r['code']];

        case 'token_agent':
            $r = hc_do_curl($baseUrl . '/version_agent.php');
            $ok = $r['code'] === 403;
            return ['ok' => $ok, 'ms' => $r['ms'],
                'detail' => $ok ? 'HTTP 403 — Token-Schutz aktiv ✓' : '⚠️ HTTP ' . $r['code'] . ' — kein Token-Schutz!'];

        case 'token_sync':
            $r = hc_do_curl($baseUrl . '/sync_agent.php');
            $ok = $r['code'] === 403;
            return ['ok' => $ok, 'ms' => $r['ms'],
                'detail' => $ok ? 'HTTP 403 — Token-Schutz aktiv ✓' : '⚠️ HTTP ' . $r['code'] . ' — kein Token-Schutz!'];

        case 'cron_backup_token':
            $r = hc_do_curl($baseUrl . '/cron_backup.php?run_backup=1');
            $ok = $r['code'] === 403 || stripos($r['body'], 'Access Denied') !== false;
            return ['ok' => $ok, 'ms' => $r['ms'],
                'detail' => $ok ? 'Ohne Token abgelehnt ✓' : '⚠️ HTTP ' . $r['code'] . ' — Backup evtl. ohne Token auslösbar!'];

        case 'superadmin':
            $r = hc_do_curl($baseUrl . '/backend/superadmin.php');
            $ok = stripos($r['body'], 'login') !== false || in_array($r['code'], [301, 302, 403]);
            return ['ok' => $ok, 'ms' => $r['ms'],
                'detail' => $ok ? 'Zugriff verweigert ✓' : '⚠️ Möglicherweise ohne Login zugänglich!'];

        case 'dbcheck':
            return hc_check_gone($baseUrl . '/db_check.php');

        case 'debug_files':
            $r1 = hc_do_curl($baseUrl . '/debug_csrf.php');
            $r2 = hc_do_curl($baseUrl . '/debug_query.php');
            $gone = in_array($r1['code'], [403, 404]) && in_array($r2['code'], [403, 404]);
            return ['ok' => $gone, 'ms' => $r1['ms'] + $r2['ms'],
                'detail' => $gone
                    ? 'Debug-Dateien entfernt ✓'
                    : '⚠️ debug_csrf.php/debug_query.php noch erreichbar! Sofort löschen.'];

        case 'init_sqlite_exposed':
            return hc_check_gone($baseUrl . '/init_sqlite.php', ' — kann DB zurücksetzen!');

        case 'security_headers':
            $r = hc_do_curl_headers($baseUrl . '/login.php');
            $h = strtolower($r['headers']);
            $hasXFO  = strpos($h, 'x-frame-options') !== false;
            $hasXCTO = strpos($h, 'x-content-type-options') !== false;
            $ok = $hasXFO && $hasXCTO;
            $missing = [];
            if (!$hasXFO)  $missing[] = 'X-Frame-Options';
            if (!$hasXCTO) $missing[] = 'X-Content-Type-Options';
            return ['ok' => $ok, 'ms' => $r['ms'],
                'detail' => $ok ? 'Security-Header vorhanden ✓' : '⚠️ Fehlt: ' . implode(', ', $missing)];

        case 'backup_files_exposed':
            $rDir = hc_do_curl($baseUrl . '/backups/');
            $dirListing = $rDir['code'] === 200 && stripos($rDir['body'], 'Index of') !== false;
            $guessPaths = ['/backups/backup.sql', '/backups/database.sql', '/backups/dump.sql', '/backup.sql', '/db_backup.sql'];
            $foundFile = null;
            $msSum = $rDir['ms'];
            foreach ($guessPaths as $p) {
                $rg = hc_do_curl($baseUrl . $p);
                $msSum += $rg['ms'];
                if ($rg['code'] === 200 && strlen($rg['body']) > 0) { $foundFile = $p; break; }
            }
            $ok = !$dirListing && !$foundFile;
            return ['ok' => $ok, 'ms' => $msSum,
                'detail' => $ok ? 'Backup-Verzeichnis geschützt ✓'
                    : ($dirListing ? '⚠️ /backups/ listet Dateien öffentlich auf!' : '⚠️ Backup-Datei öffentlich erreichbar: ' . $foundFile)];

        case 'diagnostic_files_exposed':
            $paths = ['/phpinfo.php', '/info.php', '/test.php', '/diagnose.php'];
            $stillThere = [];
            $msSum = 0;
            foreach ($paths as $p) {
                $r = hc_do_curl($baseUrl . $p);
                $msSum += $r['ms'];
                if (!in_array($r['code'], [403, 404])) { $stillThere[] = $p; }
            }
            $ok = empty($stillThere);
            return ['ok' => $ok, 'ms' => $msSum,
                'detail' => $ok ? 'Keine Diagnose-Dateien erreichbar ✓' : '⚠️ Erreichbar: ' . implode(', ', $stillThere)];

        case 'config_leak':
            $r = hc_do_curl($baseUrl . '/config.php');
            $leak = stripos($r['body'], 'DB_PASS') !== false
                 || stripos($r['body'], '<?php') !== false
                 || stripos($r['body'], 'password_hash') !== false;
            $ok = !$leak;
            return ['ok' => $ok, 'ms' => $r['ms'],
                'detail' => $ok ? 'Kein Quellcode-Leak ✓' : '⚠️ config.php gibt Quellcode/Zugangsdaten preis!'];

        default:
            return ['ok' => false, 'ms' => 0, 'detail' => 'Unbekannter Check: ' . $checkId];
    }
}
}
