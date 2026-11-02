<?php
/**
 * update_helpers.php — was ValuSafe rund um update_kern.php braucht (ab 4.7)
 *
 * update_kern.php ist mit Valu-Basic bytegleich und kennt weder Netz noch
 * Einstellungen. Hier steht der ValuSafe-eigene Teil:
 *   - darf diese Installation nach neuen Versionen fragen, darf sie selbst
 *     aktualisieren (Hub-Flotte und Docker nicht)?
 *   - Versionsabfrage bei palindrom.de, hoechstens einmal am Tag, mit
 *     Zwischenspeicher in backups/update/stand.json
 *   - Update-Paket holen, mit Groessengrenze
 *   - Wartungsschalter fuer die Sekunden des Austauschs (db.php fragt ihn ab)
 *
 * Entschieden 30.10.2026 (Joerg, TODO-Liste Abschnitt U):
 *   Versionsabfrage voreingestellt AN, aus mit
 *       define('VALUSAFE_UPDATE_PRUEFEN', false);
 *   Kein Knopf auf Instanzen mit FILE_SYNC_TOKEN (Hub verteilt) und im
 *   Docker-Image (ENV VALUSAFE_DOCKER=1, das Image wird neu gezogen); dazu
 *   per Hand abschaltbar mit define('VALUSAFE_UPDATE_KNOPF', false) und mit
 *   define('VALUSAFE_UPDATE_KNOPF', true) trotz Flotte/Docker erlaubt.
 *   Das Paket laesst sich auch hochladen, falls der Server nicht nach
 *   aussen darf.
 *
 * Die Anfrage an palindrom.de traegt keine Version und keine Kennung; was
 * sie preisgibt, ist die IP-Adresse des Servers (Datenschutzerklaerung).
 */

require_once __DIR__ . '/update_kern.php';

const VS_UPD_INFO_URL      = 'https://palindrom.de/valusafe/update_info.php';
const VS_UPD_PRUEF_ABSTAND = 86400;   // Sekunden zwischen zwei Abfragen
const VS_UPD_FEHL_ABSTAND  = 21600;   // nach einer gescheiterten Abfrage
const VS_UPD_WARTUNG_MAX   = 900;     // aelter = vergessener Schalter, gilt nicht mehr

/** Was ein Update in ValuSafe nie anfasst, unabhaengig vom Paket. */
const VS_UPD_GESCHUETZT = ['config.php', '.htaccess', 'upload/', 'documents/', 'backups/', 'logs/'];

/** Installierte Version laut wert-update/version.json, '0' wenn unlesbar. */
function vsUpdInstalliert(string $wurzel): string
{
    $v = json_decode((string)@file_get_contents($wurzel . '/wert-update/version.json'), true)['version'] ?? '';
    return is_string($v) && vsUpdVersionGueltig($v) ? $v : '0';
}

/** Darf diese Installation bei palindrom.de nach neuen Versionen fragen? */
function vsUpdAbfrageErlaubt(): bool
{
    return !defined('VALUSAFE_UPDATE_PRUEFEN') || VALUSAFE_UPDATE_PRUEFEN !== false;
}

/**
 * Warum hier kein Update eingespielt werden darf, oder null.
 *   'hub'    Instanz der eigenen Flotte, Dateien kommen per Hub-Sync
 *   'docker' Docker-Image; ein neues Image ersetzt die Anwendung
 *   'config' in config.php abgeschaltet
 */
function vsUpdKnopfSperre(): ?string
{
    // Ausdruecklich erlaubt geht vor - etwa fuer die Tests auf Instanzen der
    // Flotte (all-inkl, lima-city, NAS), die sonst als 'hub' gesperrt waeren.
    if (defined('VALUSAFE_UPDATE_KNOPF') && VALUSAFE_UPDATE_KNOPF === true) return null;
    // Docker vor 'config': das Image schreibt zusaetzlich VALUSAFE_UPDATE_KNOPF
    // false in config.php, die Meldung soll aber "neues Image ziehen" sagen.
    if (getenv('VALUSAFE_DOCKER') === '1') return 'docker';
    if (defined('FILE_SYNC_TOKEN') && (string)FILE_SYNC_TOKEN !== '') return 'hub';
    if (defined('VALUSAFE_UPDATE_KNOPF') && VALUSAFE_UPDATE_KNOPF === false) return 'config';
    return null;
}

/**
 * Antwort von update_info.php auswerten. Nur was hier durchkommt, landet im
 * Zwischenspeicher und spaeter in der Oberflaeche. Wirft VsUpdFehler.
 */
function vsUpdInfoAuswerten(string $json): array
{
    $d = json_decode($json, true);
    if (!is_array($d) || ($d['format'] ?? null) !== 1) throw new VsUpdFehler('Antwort der Versionsabfrage unlesbar.');
    $version = $d['version'] ?? null;
    if (!is_string($version) || !vsUpdVersionGueltig($version)) throw new VsUpdFehler('Antwort ohne gueltige Version.');
    $datum = is_string($d['datum'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['datum']) ? $d['datum'] : null;
    $changelog = is_string($d['changelog'] ?? null) && str_starts_with($d['changelog'], 'https://') ? $d['changelog'] : null;

    $paket = null;
    if (is_array($d['paket'] ?? null)) {
        $p = $d['paket'];
        if (!is_string($p['url'] ?? null) || !str_starts_with($p['url'], 'https://')
            || !is_int($p['bytes'] ?? null) || $p['bytes'] < 1 || $p['bytes'] > VS_UPD_MAX_BYTES
            || !is_string($p['sha256'] ?? null) || !preg_match('/^[0-9a-f]{64}$/', $p['sha256'])) {
            throw new VsUpdFehler('Antwort mit ungueltiger Paketangabe.');
        }
        $paket = ['url' => $p['url'], 'bytes' => $p['bytes'], 'sha256' => $p['sha256']];
    }
    return ['version' => $version, 'datum' => $datum, 'paket' => $paket, 'changelog' => $changelog];
}

/**
 * Letzter bekannter Stand der Versionsabfrage:
 *   ['geprueft' => Zeit, 'info' => vsUpdInfoAuswerten()|null, 'fehler' => Text|null]
 * Fragt neu, wenn der Stand aelter als ein Tag ist (nach einem Fehler: sechs
 * Stunden) oder $erzwingen gilt; $nurLesen fragt nie, liefert nur den Speicher.
 * null, wenn die Abfrage abgeschaltet ist oder noch nie lief ($nurLesen).
 */
function vsUpdStand(string $arbeit, bool $erzwingen = false, bool $nurLesen = false): ?array
{
    if (!vsUpdAbfrageErlaubt()) return null;
    $datei = $arbeit . 'stand.json';
    $stand = json_decode((string)@file_get_contents($datei), true);
    if (!is_array($stand) || !is_int($stand['geprueft'] ?? null)) $stand = null;
    if ($nurLesen) return $stand;

    $abstand = ($stand['fehler'] ?? null) ? VS_UPD_FEHL_ABSTAND : VS_UPD_PRUEF_ABSTAND;
    if (!$erzwingen && $stand !== null && time() - $stand['geprueft'] < $abstand) return $stand;

    $neu = ['geprueft' => time(), 'info' => $stand['info'] ?? null, 'fehler' => null];
    try {
        $url = defined('VALUSAFE_UPDATE_INFO_URL') ? (string)VALUSAFE_UPDATE_INFO_URL : VS_UPD_INFO_URL;
        $neu['info'] = vsUpdInfoAuswerten(vsUpdHttpHolen($url, 65536, null, 5));
    } catch (Throwable $e) {
        $neu['fehler'] = $e instanceof VsUpdFehler ? $e->getMessage() : 'Versionsabfrage fehlgeschlagen.';
    }
    if (!is_dir($arbeit)) @mkdir($arbeit, 0755, true);
    @file_put_contents($datei, json_encode($neu, JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $neu;
}

/** Gibt es laut $stand eine neuere Version als $installiert? */
function vsUpdNeuer(?array $stand, string $installiert): bool
{
    $v = $stand['info']['version'] ?? null;
    return is_string($v) && version_compare($v, $installiert, '>');
}

/**
 * Laedt das Paket aus $info nach $datei und gleicht Groesse und SHA-256 mit
 * der Versionsabfrage ab. Echt ist es damit noch nicht - das entscheidet
 * vsUpdPaketPruefen() ueber die Signatur.
 */
function vsUpdPaketHolen(array $info, string $datei): void
{
    $p = $info['paket'] ?? null;
    if (!is_array($p)) throw new VsUpdFehler('Fuer diese Version liegt noch kein Update-Paket bereit.');
    try {
        vsUpdHttpHolen($p['url'], $p['bytes'], $datei, 120);
        if (filesize($datei) !== $p['bytes'] || !hash_equals($p['sha256'], (string)hash_file('sha256', $datei))) {
            throw new VsUpdFehler('Das Paket kam unvollstaendig oder veraendert an.');
        }
    } catch (Throwable $e) {
        @unlink($datei);
        throw $e;
    }
}

/**
 * HTTPS-Abruf mit Groessengrenze. Ohne $datei kommt der Inhalt zurueck,
 * sonst wird nach $datei geschrieben (die es noch nicht geben darf).
 * Nimmt curl, sonst PHP-Streams; geht beides nicht, VsUpdFehler mit
 * Hinweis auf das Hochladen.
 */
function vsUpdHttpHolen(string $url, int $max, ?string $datei, int $zeit): string
{
    if (!str_starts_with($url, 'https://')) throw new VsUpdFehler('Nur HTTPS-Adressen.');
    $aus = $datei !== null ? @fopen($datei, 'xb') : null;
    if ($aus === false) throw new VsUpdFehler('Zieldatei fuer den Download laesst sich nicht anlegen.');
    $inhalt = '';
    $n = 0;
    $schreiben = function (string $stueck) use (&$inhalt, &$n, $max, $aus): void {
        $n += strlen($stueck);
        if ($n > $max) throw new VsUpdFehler('Antwort groesser als angekuendigt.');
        if ($aus !== null) {
            if (fwrite($aus, $stueck) !== strlen($stueck)) throw new VsUpdFehler('Download laesst sich nicht speichern.');
        } else {
            $inhalt .= $stueck;
        }
    };
    $ua = 'ValuSafe-Update/1';
    try {
        if (function_exists('curl_init')) {
            $fehler = null;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => $zeit,
                CURLOPT_USERAGENT      => $ua,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_WRITEFUNCTION  => function ($ch, string $stueck) use ($schreiben, &$fehler): int {
                    try {
                        $schreiben($stueck);
                        return strlen($stueck);
                    } catch (VsUpdFehler $e) {
                        $fehler = $e;
                        return 0;   // bricht den Abruf ab
                    }
                },
            ]);
            $ok = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            unset($ch);   // kein curl_close(): wirkungslos seit PHP 8.0, ab 8.5 "deprecated" im Fehlerprotokoll
            if ($fehler) throw $fehler;
            if ($ok === false) throw new VsUpdFehler('palindrom.de nicht erreichbar.');
            if ($code !== 200) throw new VsUpdFehler("palindrom.de antwortet mit Status $code.");
        } elseif (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
            $ctx = stream_context_create([
                'http' => ['timeout' => $zeit, 'user_agent' => $ua, 'follow_location' => 1, 'max_redirects' => 3],
                'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
            ]);
            $ein = @fopen($url, 'rb', false, $ctx);
            if ($ein === false) throw new VsUpdFehler('palindrom.de nicht erreichbar.');
            try {
                $kopf = stream_get_meta_data($ein)['wrapper_data'] ?? [];
                $status = 0;
                foreach ((array)$kopf as $zeile) {
                    if (preg_match('#^HTTP/\S+\s+(\d{3})#', (string)$zeile, $m)) $status = (int)$m[1];   // letzte gilt (Umleitung)
                }
                if ($status !== 200) throw new VsUpdFehler("palindrom.de antwortet mit Status $status.");
                while (!feof($ein)) {
                    $stueck = fread($ein, 65536);
                    if ($stueck === false) throw new VsUpdFehler('Download abgebrochen.');
                    $schreiben($stueck);
                }
            } finally {
                fclose($ein);
            }
        } else {
            throw new VsUpdFehler('Dieser Server darf keine Verbindungen nach aussen aufbauen (weder curl noch allow_url_fopen). Das Update-Paket laesst sich aber hochladen.');
        }
    } catch (Throwable $e) {
        if ($aus !== null) {
            fclose($aus);
            @unlink($datei);
        }
        throw $e;
    }
    if ($aus !== null) fclose($aus);
    return $inhalt;
}

// ── Wartung ─────────────────────────────────────────────────────────────────
// Waehrend des Austauschs (Sekunden) und der Migrationen beantwortet jede
// andere Seite mit 503 - kein Speichern gegen halb getauschte Dateien.
// Abgefragt wird der Schalter in db.php, dort ohne diese Datei einzubinden
// (jeder Seitenaufruf). Er verfaellt nach VS_UPD_WARTUNG_MAX von selbst,
// falls der Lauf hart abbrach; den Rest erledigt das Zurueckholen in
// backend/update.php.

function vsUpdWartungDatei(): string
{
    return rtrim((string)BACKUP_DIR, '/') . '/update/wartung.json';
}

function vsUpdWartungAn(string $version): void
{
    $d = vsUpdWartungDatei();
    if (!is_dir(dirname($d))) @mkdir(dirname($d), 0755, true);
    if (file_put_contents($d, json_encode(['seit' => time(), 'version' => $version])) === false) {
        throw new VsUpdFehler('Wartungsschalter laesst sich nicht setzen - nichts geaendert.');
    }
}

function vsUpdWartungAus(): void
{
    @unlink(vsUpdWartungDatei());
}
