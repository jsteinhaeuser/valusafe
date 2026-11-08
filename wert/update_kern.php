<?php
/**
 * update_kern.php — gemeinsamer Update-Kern fuer ValuSafe und Valu-Basic
 *
 * In ValuSafe (wert/) und Valu-Basic (usb/valu-basic/) BYTEGLEICH; das
 * Paketskript von Valu-Basic bricht ab, wenn die beiden auseinanderlaufen.
 * Entschieden 19.10.2026 (TODO-Liste Abschnitt U), gebaut ab 29.10.2026.
 *
 * Der Kern weiss nichts von Datenbank, Anmeldung oder Oberflaeche. Er prueft
 * ein Update-Paket, packt es in einen Zwischenordner aus und tauscht die
 * Dateien so aus, dass sie sich wieder zurueckholen lassen. Sicherung der
 * Datenbank, Wartungsseite, Migrationen und Selbsttest sind Sache der
 * Anwendung; Migrationen und Selbsttest uebergibt sie als $pruefung an
 * vsUpdEinspielen(), damit ein Fehlschlag die Dateien zurueckrollt.
 *
 * PAKETFORMAT (ZIP)
 *   manifest.json   was das Paket ist und welche Dateien es enthaelt:
 *                   format, programm, version, mindestens (optional),
 *                   erzeugt, dateien {pfad: sha256}, geschuetzt [..],
 *                   entfallen [..]
 *   manifest.sig    {"kennung": .., "signatur": Base64} - Ed25519 ueber
 *                   VS_UPD_FORMAT . ":" . <Bytes von manifest.json>
 *   dateien/<pfad>  die neuen Dateien
 *
 * Die Signatur deckt nur das Manifest; jede Datei ist ueber ihren SHA-256 im
 * Manifest gebunden. Was nicht im Manifest steht, darf nicht im ZIP liegen.
 *
 * WAS GEPRUEFT WIRD, BEVOR IRGENDETWAS AUF DIE PLATTE KOMMT
 *   - Signatur gegen einen der Schluessel aus update_schluessel.php
 *   - richtiges Programm, Version NEUER als die installierte (sonst liesse
 *     sich ein altes, ordentlich signiertes Paket mit bekannter Luecke
 *     wieder einspielen), installierte Version nicht aelter als "mindestens"
 *   - jeder Pfad relativ, ohne "..", ohne Backslash, nur unbedenkliche
 *     Zeichen - auch im signierten Manifest, ein Fehler beim Paketbau soll
 *     nicht ausserhalb der Anwendung schreiben koennen
 *   - kein Pfad unter "geschuetzt" (config.php, Bilder, Datenbank ...)
 *   - Anzahl und entpackte Groesse begrenzt (ZIP-Bombe)
 *
 * GESCHUETZT: Eintrag "datei.php" schuetzt genau diese Datei, Eintrag
 * "ordner/" alles darunter. Die Anwendung kann eigene Eintraege dazugeben.
 *
 * Fehler kommen als VsUpdFehler mit deutschem Text, der so dem Admin
 * angezeigt werden darf: er nennt Pfade innerhalb des Pakets, keine des
 * Servers.
 */

const VS_UPD_FORMAT        = 'valusafe-update-1';
const VS_UPD_MAX_DATEIEN   = 5000;
const VS_UPD_MAX_BYTES     = 150 * 1024 * 1024;   // entpackt, alle Dateien zusammen
const VS_UPD_MAX_MANIFEST  = 2 * 1024 * 1024;

// Fehlercode: jemand hat das vollstaendige Installations-/USB-Paket statt des
// Update-Pakets gewaehlt (Fund 05.11.2026, Update-Test Valu-Basic). Die
// Anwendung uebersetzt diesen Fall selbst und nennt die richtige Datei.
const VS_UPD_FEHLER_VOLLPAKET = 2;

class VsUpdFehler extends RuntimeException {}

/** Kennung eines oeffentlichen Schluessels (wie bin/schluessel_erzeugen.php). */
function vsUpdKennung(string $oeffentlich): string
{
    return substr(hash('sha256', $oeffentlich), 0, 16);
}

/**
 * Ist $pfad ein harmloser relativer Pfad innerhalb der Anwendung?
 * Bewusst eng: Buchstaben, Ziffern, Punkt, Bindestrich, Unterstrich,
 * Leerzeichen, Klammern, Plus. Kein Segment "." oder "..", kein
 * fuehrender Punkt-Ordner ausser .htaccess-artigen Dateien.
 */
function vsUpdPfadGueltig(string $pfad): bool
{
    if ($pfad === '' || strlen($pfad) > 255) return false;
    if (!preg_match('#^[A-Za-z0-9._ ()+-]+(/[A-Za-z0-9._ ()+-]+)*$#', $pfad)) return false;
    foreach (explode('/', $pfad) as $teil) {
        // Windows schneidet Punkte und Leerzeichen am Ende ab: "a.php." waere "a.php".
        if ($teil === '.' || $teil === '..' || trim($teil) !== $teil || str_ends_with($teil, '.')) return false;
    }
    return true;
}

/** Faellt $pfad unter einen der geschuetzten Eintraege? */
function vsUpdGeschuetzt(string $pfad, array $geschuetzt): bool
{
    foreach ($geschuetzt as $g) {
        if (str_ends_with($g, '/')) {
            if (str_starts_with($pfad . '/', $g)) return true;
        } elseif (strcasecmp($pfad, $g) === 0) {
            // Ohne Ruecksicht auf Gross/Klein: auf Windows und macOS waere
            // Config.php dieselbe Datei wie config.php.
            return true;
        }
    }
    return false;
}

/** Versionsnummer in der Form 4.6.1 oder 2.4. */
function vsUpdVersionGueltig(string $v): bool
{
    return (bool)preg_match('/^\d{1,4}(\.\d{1,4}){1,3}$/', $v);
}

// ── Paket bauen (nur bin/paket_signieren.php auf dem Mac) ───────────────────

/**
 * Manifest fuer den Ordner $ordner. Liegt dort etwas Geschuetztes, ist der
 * Paketbau falsch eingestellt - dann Abbruch statt stillem Weglassen.
 */
function vsUpdManifestErzeugen(string $ordner, string $programm, string $version,
                               array $geschuetzt, array $entfallen, ?string $mindestens = null): array
{
    if (!vsUpdVersionGueltig($version)) throw new VsUpdFehler("Ungueltige Version: $version");
    if ($mindestens !== null && !vsUpdVersionGueltig($mindestens)) {
        throw new VsUpdFehler("Ungueltige Mindestversion: $mindestens");
    }
    $basis = rtrim($ordner, '/');
    $dateien = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($basis, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $datei) {
        if (!$datei->isFile()) continue;
        $pfad = substr($datei->getPathname(), strlen($basis) + 1);
        if ($datei->isLink()) throw new VsUpdFehler("Verknuepfung im Paket: $pfad");
        if (!vsUpdPfadGueltig($pfad)) throw new VsUpdFehler("Unzulaessiger Dateiname im Paket: $pfad");
        if (vsUpdGeschuetzt($pfad, $geschuetzt)) throw new VsUpdFehler("Geschuetzte Datei im Paket: $pfad");
        $dateien[$pfad] = hash_file('sha256', $datei->getPathname());
    }
    if (!$dateien) throw new VsUpdFehler('Keine Dateien im Paket.');
    ksort($dateien, SORT_STRING);
    $manifest = [
        'format'     => VS_UPD_FORMAT,
        'programm'   => $programm,
        'version'    => $version,
        'mindestens' => $mindestens,
        'erzeugt'    => date('c'),
        'dateien'    => $dateien,
        'geschuetzt' => array_values($geschuetzt),
        'entfallen'  => array_values($entfallen),
    ];
    vsUpdManifestPruefen($manifest);   // dieselben Regeln wie beim Einspielen
    return $manifest;
}

function vsUpdManifestJson(array $manifest): string
{
    return json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

/** Signatur ueber das Manifest, als Inhalt fuer manifest.sig. */
function vsUpdSignieren(string $manifestJson, string $geheim): string
{
    $sig = sodium_crypto_sign_detached(VS_UPD_FORMAT . ':' . $manifestJson, $geheim);
    return json_encode([
        'kennung'  => vsUpdKennung(sodium_crypto_sign_publickey_from_secretkey($geheim)),
        'signatur' => base64_encode($sig),
    ], JSON_UNESCAPED_SLASHES) . "\n";
}

/** Schreibt das ZIP aus Ordner, Manifest und Signatur. */
function vsUpdPaketSchreiben(string $ordner, string $manifestJson, string $sigJson, string $zipPfad): void
{
    $manifest = json_decode($manifestJson, true);
    $zip = new ZipArchive();
    if ($zip->open($zipPfad, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
        throw new VsUpdFehler("$zipPfad laesst sich nicht anlegen (gibt es ihn schon?).");
    }
    $zip->addFromString('manifest.json', $manifestJson);
    $zip->addFromString('manifest.sig', $sigJson);
    foreach (array_keys($manifest['dateien']) as $pfad) {
        $zip->addFile(rtrim($ordner, '/') . '/' . $pfad, 'dateien/' . $pfad);
    }
    if (!$zip->close()) throw new VsUpdFehler("$zipPfad liess sich nicht schreiben.");
}

// ── Paket pruefen ───────────────────────────────────────────────────────────

/** Aufbau des Manifests; wirft bei jedem Verstoss. Ohne Signatur und Version. */
function vsUpdManifestPruefen(array $m): void
{
    if (($m['format'] ?? null) !== VS_UPD_FORMAT) throw new VsUpdFehler('Unbekanntes Paketformat.');
    foreach (['programm', 'version'] as $f) {
        if (!is_string($m[$f] ?? null)) throw new VsUpdFehler("Manifest ohne $f.");
    }
    if (!vsUpdVersionGueltig($m['version'])) throw new VsUpdFehler('Ungueltige Version im Paket.');
    if (isset($m['mindestens']) && (!is_string($m['mindestens']) || !vsUpdVersionGueltig($m['mindestens']))) {
        throw new VsUpdFehler('Ungueltige Mindestversion im Paket.');
    }
    foreach (['dateien', 'geschuetzt', 'entfallen'] as $f) {
        if (!is_array($m[$f] ?? null)) throw new VsUpdFehler("Manifest ohne $f.");
    }
    if (!$m['dateien'] || count($m['dateien']) > VS_UPD_MAX_DATEIEN) {
        throw new VsUpdFehler('Zahl der Dateien im Paket unzulaessig.');
    }
    foreach ($m['geschuetzt'] as $g) {
        $ohne = is_string($g) ? rtrim($g, '/') : '';
        if (!vsUpdPfadGueltig($ohne)) throw new VsUpdFehler('Ungueltiger geschuetzter Eintrag im Paket.');
    }
    $gesehen = [];
    foreach ($m['dateien'] as $pfad => $hash) {
        $pfad = (string)$pfad;
        if (!vsUpdPfadGueltig($pfad)) throw new VsUpdFehler("Unzulaessiger Pfad im Paket: $pfad");
        if (!is_string($hash) || !preg_match('/^[0-9a-f]{64}$/', $hash)) {
            throw new VsUpdFehler("Pruefsumme fehlt: $pfad");
        }
        if (vsUpdGeschuetzt($pfad, $m['geschuetzt'])) throw new VsUpdFehler("Paket will geschuetzte Datei ersetzen: $pfad");
        // Auf Windows/macOS waeren a.php und A.php ein und dieselbe Datei.
        $klein = strtolower($pfad);
        if (isset($gesehen[$klein])) throw new VsUpdFehler("Pfad doppelt im Paket: $pfad");
        $gesehen[$klein] = true;
    }
    foreach ($m['entfallen'] as $e) {
        if (!is_string($e) || !vsUpdPfadGueltig($e)) throw new VsUpdFehler('Ungueltiger Eintrag unter entfallen.');
        if (isset($gesehen[strtolower($e)])) throw new VsUpdFehler("Datei ist zugleich neu und entfallen: $e");
        if (vsUpdGeschuetzt($e, $m['geschuetzt'])) throw new VsUpdFehler("Paket will geschuetzte Datei loeschen: $e");
    }
}

/**
 * Prueft das ZIP vollstaendig - Signatur, Manifest, jede Pruefsumme -, ohne
 * etwas auszupacken, und gibt das Manifest
 * zurueck. $schluessel: [kennung => Base64-Schluessel] (update_schluessel.php).
 * $geschuetzt: zusaetzliche Eintraege der Anwendung.
 */
function vsUpdPaketPruefen(string $zipPfad, array $schluessel, string $programm,
                           string $installiert, array $geschuetzt = []): array
{
    if (!extension_loaded('sodium')) throw new VsUpdFehler('PHP ohne sodium: Signaturen lassen sich nicht pruefen.');
    if (!class_exists('ZipArchive')) throw new VsUpdFehler('PHP ohne zip: Pakete lassen sich nicht oeffnen.');

    $zip = new ZipArchive();
    if ($zip->open($zipPfad, ZipArchive::RDONLY) !== true) throw new VsUpdFehler('Datei ist kein lesbares ZIP.');
    try {
        $n = $zip->numFiles;
        if ($n < 3 || $n > VS_UPD_MAX_DATEIEN + 2) throw new VsUpdFehler('Zahl der Eintraege im Paket unzulaessig.');

        $mStat = $zip->statName('manifest.json');
        $sStat = $zip->statName('manifest.sig');
        if (!$mStat || !$sStat) {
            // ValuSafe_Install_v4.8/... bzw. ValuBasic_USB/... - das ganze Paket
            for ($i = 0; $i < min($n, 50); $i++) {
                if (preg_match('#^(ValuSafe_Install_v[0-9.]+|ValuBasic_USB)/#', (string)$zip->getNameIndex($i))) {
                    throw new VsUpdFehler('Vollstaendiges Installationspaket, kein Update-Paket.', VS_UPD_FEHLER_VOLLPAKET);
                }
            }
            throw new VsUpdFehler('Kein Update-Paket (manifest.json oder manifest.sig fehlt).');
        }
        if ($mStat['size'] > VS_UPD_MAX_MANIFEST || $sStat['size'] > 4096) throw new VsUpdFehler('Manifest zu gross.');
        // Laenge begrenzt: die Groesse im ZIP-Verzeichnis kann gelogen sein.
        $manifestJson = $zip->getFromName('manifest.json', VS_UPD_MAX_MANIFEST + 1);
        $sigJson = $zip->getFromName('manifest.sig', 4097);
        if (!is_string($manifestJson) || !is_string($sigJson)) throw new VsUpdFehler('Manifest nicht lesbar.');
        if (strlen($manifestJson) > VS_UPD_MAX_MANIFEST || strlen($sigJson) > 4096) throw new VsUpdFehler('Manifest zu gross.');

        // Erst die Signatur, dann wird das Manifest ueberhaupt ausgewertet.
        $sig = json_decode($sigJson, true);
        $roh = is_array($sig) ? base64_decode((string)($sig['signatur'] ?? ''), true) : false;
        if ($roh === false || strlen($roh) !== SODIUM_CRYPTO_SIGN_BYTES) throw new VsUpdFehler('Signatur unlesbar.');
        $echt = false;
        foreach ($schluessel as $oeff) {
            $pk = base64_decode((string)$oeff, true);
            if ($pk === false || strlen($pk) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) continue;
            if (sodium_crypto_sign_verify_detached($roh, VS_UPD_FORMAT . ':' . $manifestJson, $pk)) { $echt = true; break; }
        }
        if (!$echt) throw new VsUpdFehler('Signatur ungueltig: das Paket stammt nicht vom Herausgeber oder wurde veraendert.');

        $m = json_decode($manifestJson, true);
        if (!is_array($m)) throw new VsUpdFehler('Manifest unlesbar.');
        vsUpdManifestPruefen($m);
        if ($m['programm'] !== $programm) {
            throw new VsUpdFehler("Paket ist fuer {$m['programm']}, nicht fuer $programm.");
        }
        if (!version_compare($m['version'], $installiert, '>')) {
            throw new VsUpdFehler("Paket hat Version {$m['version']}, installiert ist $installiert - nichts zu tun.");
        }
        if (!empty($m['mindestens']) && version_compare($installiert, $m['mindestens'], '<')) {
            throw new VsUpdFehler("Dieses Update setzt mindestens Version {$m['mindestens']} voraus, installiert ist $installiert.");
        }
        foreach (array_keys($m['dateien']) as $pfad) {
            if (vsUpdGeschuetzt((string)$pfad, $geschuetzt)) throw new VsUpdFehler("Paket will geschuetzte Datei ersetzen: $pfad");
        }
        foreach ($m['entfallen'] as $e) {
            if (vsUpdGeschuetzt($e, $geschuetzt)) throw new VsUpdFehler("Paket will geschuetzte Datei loeschen: $e");
        }

        // Jeder Eintrag des ZIP muss im Manifest stehen und umgekehrt.
        $offen = $m['dateien'];
        $summe = 0;
        for ($i = 0; $i < $n; $i++) {
            $st = $zip->statIndex($i);
            $name = (string)$st['name'];
            if ($name === 'manifest.json' || $name === 'manifest.sig') continue;
            if (str_ends_with($name, '/') && str_starts_with($name, 'dateien/')) continue;   // Ordnereintrag
            $pfad = str_starts_with($name, 'dateien/') ? substr($name, 8) : null;
            if ($pfad === null || !array_key_exists($pfad, $offen)) {
                throw new VsUpdFehler('Datei im Paket, die nicht im Manifest steht: ' . $name);
            }
            unset($offen[$pfad]);
            $summe += (int)$st['size'];
        }
        if ($offen) throw new VsUpdFehler('Im Paket fehlt: ' . array_key_first($offen));
        if ($summe > VS_UPD_MAX_BYTES) throw new VsUpdFehler('Paket entpackt zu gross.');

        // Und jeden Inhalt gegen seine Pruefsumme - ohne etwas zu schreiben.
        // Erst danach darf die Anwendung "signiert und vollstaendig" melden.
        $summe = 0;
        foreach ($m['dateien'] as $pfad => $soll) {
            vsUpdEintragLesen($zip, (string)$pfad, $soll, $summe);
        }
        return $m;
    } finally {
        $zip->close();
    }
}

/**
 * Packt die Dateien nach $ziel aus und prueft dabei jede Pruefsumme noch
 * einmal (zwischen Pruefen und Auspacken lag das ZIP auf der Platte). $ziel
 * darf es noch nicht geben. Bei einem Fehler bleibt $ziel nicht liegen.
 */
function vsUpdEntpacken(string $zipPfad, array $manifest, string $ziel): void
{
    if (file_exists($ziel)) throw new VsUpdFehler('Zwischenordner gibt es schon.');
    if (!mkdir($ziel, 0700, true)) throw new VsUpdFehler('Zwischenordner laesst sich nicht anlegen.');
    $zip = new ZipArchive();
    $offen = false;
    try {
        if ($zip->open($zipPfad, ZipArchive::RDONLY) !== true) throw new VsUpdFehler('Paket nicht lesbar.');
        $offen = true;
        $summe = 0;
        foreach ($manifest['dateien'] as $pfad => $soll) {
            $pfad = (string)$pfad;
            $dateiZiel = "$ziel/$pfad";
            if (!is_dir(dirname($dateiZiel)) && !mkdir(dirname($dateiZiel), 0700, true)) {
                throw new VsUpdFehler("Ordner fuer $pfad laesst sich nicht anlegen.");
            }
            $aus = fopen($dateiZiel, 'xb');
            if ($aus === false) throw new VsUpdFehler("$pfad laesst sich nicht schreiben.");
            try {
                vsUpdEintragLesen($zip, $pfad, $soll, $summe, $aus);
            } finally {
                fclose($aus);
            }
        }
        $zip->close();
        $offen = false;
    } catch (Throwable $e) {
        if ($offen) $zip->close();
        vsUpdOrdnerLoeschen($ziel);
        throw $e;
    }
}

/**
 * Liest dateien/<pfad> aus dem ZIP, zaehlt die Bytes (die Groesse im ZIP-
 * Verzeichnis kann gelogen sein), schreibt sie nach $aus, falls angegeben,
 * und vergleicht die Pruefsumme.
 */
function vsUpdEintragLesen(ZipArchive $zip, string $pfad, string $soll, int &$summe, $aus = null): void
{
    $ein = $zip->getStream('dateien/' . $pfad);
    if ($ein === false) throw new VsUpdFehler("Im Paket fehlt: $pfad");
    $h = hash_init('sha256');
    try {
        while (!feof($ein)) {
            $stueck = fread($ein, 65536);
            if ($stueck === false) throw new VsUpdFehler("$pfad nicht lesbar.");
            $summe += strlen($stueck);
            if ($summe > VS_UPD_MAX_BYTES) throw new VsUpdFehler('Paket entpackt zu gross.');
            hash_update($h, $stueck);
            if ($aus !== null && fwrite($aus, $stueck) !== strlen($stueck)) throw new VsUpdFehler("$pfad laesst sich nicht schreiben.");
        }
    } finally {
        fclose($ein);
    }
    if (!hash_equals($soll, hash_final($h))) throw new VsUpdFehler("Pruefsumme stimmt nicht: $pfad");
}

// ── Einspielen und Zurueckrollen ────────────────────────────────────────────

/**
 * Tauscht die Dateien aus $neu (von vsUpdEntpacken) in $wurzel aus.
 *
 * Vorher wird jede Datei, die ersetzt oder geloescht wird, nach
 * $sicherung/alt/ kopiert und ein Journal geschrieben ($sicherung darf es
 * noch nicht geben). Danach laeuft $pruefung (Migrationen, Selbsttest); wirft
 * sie oder gibt sie false zurueck, wird alles zurueckgerollt und der Fehler
 * weitergereicht. Bricht der Lauf hart ab (Zeitlimit, Stromausfall), steht
 * im Journal "laeuft" - dann holt vsUpdZurueckrollen() den alten Stand.
 */
function vsUpdEinspielen(string $neu, array $manifest, string $wurzel, string $sicherung,
                         ?callable $pruefung = null): void
{
    $wurzelEcht = realpath($wurzel);
    if ($wurzelEcht === false) throw new VsUpdFehler('Anwendungsordner nicht gefunden.');
    if (file_exists($sicherung)) throw new VsUpdFehler('Sicherungsordner gibt es schon.');

    // Bestandsaufnahme, noch ohne etwas zu veraendern.
    $journal = ['version' => $manifest['version'], 'zustand' => 'laeuft',
                'ersetzt' => [], 'neu' => [], 'entfernt' => [], 'ordner' => []];
    foreach (array_keys($manifest['dateien']) as $pfad) {
        $pfad = (string)$pfad;
        vsUpdZielPruefen($wurzelEcht, $pfad);
        if (file_exists("$wurzelEcht/$pfad")) $journal['ersetzt'][] = $pfad;
        else $journal['neu'][] = $pfad;
    }
    foreach ($manifest['entfallen'] as $e) {
        vsUpdZielPruefen($wurzelEcht, $e);
        if (is_file("$wurzelEcht/$e")) $journal['entfernt'][] = $e;
    }

    // Sicherung der alten Dateien, dann das Journal - erst danach wird getauscht.
    if (!mkdir("$sicherung/alt", 0700, true)) throw new VsUpdFehler('Sicherungsordner laesst sich nicht anlegen.');
    foreach (array_merge($journal['ersetzt'], $journal['entfernt']) as $pfad) {
        $z = "$sicherung/alt/$pfad";
        if (!is_dir(dirname($z)) && !mkdir(dirname($z), 0700, true)) throw new VsUpdFehler("Sicherung von $pfad fehlgeschlagen.");
        if (!copy("$wurzelEcht/$pfad", $z)) throw new VsUpdFehler("Sicherung von $pfad fehlgeschlagen.");
    }
    vsUpdJournalSchreiben($sicherung, $journal);

    try {
        foreach (array_keys($manifest['dateien']) as $pfad) {
            $pfad = (string)$pfad;
            $ordner = dirname("$wurzelEcht/$pfad");
            if (!is_dir($ordner)) {
                // Jeden neu angelegten Ordner vermerken, damit das Zurueckrollen ihn wieder entfernt.
                $fehlend = [];
                for ($o = $ordner; !is_dir($o); $o = dirname($o)) $fehlend[] = $o;
                foreach (array_reverse($fehlend) as $o) {
                    if (!mkdir($o, 0755)) throw new VsUpdFehler("Ordner fuer $pfad laesst sich nicht anlegen.");
                    $journal['ordner'][] = substr($o, strlen($wurzelEcht) + 1);
                }
                vsUpdJournalSchreiben($sicherung, $journal);
            }
            vsUpdDateiSetzen("$neu/$pfad", "$wurzelEcht/$pfad");
        }
        foreach ($journal['entfernt'] as $e) {
            if (!unlink("$wurzelEcht/$e")) throw new VsUpdFehler("$e laesst sich nicht loeschen.");
        }
        $journal['zustand'] = 'getauscht';
        vsUpdJournalSchreiben($sicherung, $journal);

        if ($pruefung !== null && $pruefung() === false) {
            throw new VsUpdFehler('Selbsttest nach dem Update fehlgeschlagen.');
        }
        $journal['zustand'] = 'fertig';
        vsUpdJournalSchreiben($sicherung, $journal);
    } catch (Throwable $e) {
        vsUpdZurueckrollen($wurzelEcht, $sicherung);
        throw new VsUpdFehler('Update zurueckgerollt: ' . $e->getMessage(), 0, $e);
    }
}

/**
 * Selbsttest nach dem Tausch, fuer $pruefung: jede Datei des Pakets liegt mit
 * der richtigen Pruefsumme da, jede PHP-Datei laesst sich parsen (ohne sie
 * auszufuehren), und wert-update/version.json nennt die Version des Pakets.
 * Wirft beim ersten Fund, sonst true.
 */
function vsUpdSelbsttest(string $wurzel, array $manifest): bool
{
    foreach ($manifest['dateien'] as $pfad => $soll) {
        $pfad = (string)$pfad;
        $voll = "$wurzel/$pfad";
        if (!is_file($voll) || !hash_equals($soll, (string)hash_file('sha256', $voll))) {
            throw new VsUpdFehler("Selbsttest: $pfad fehlt oder ist unvollstaendig.");
        }
        if (str_ends_with($pfad, '.php') && class_exists('PhpToken')) {
            try {
                PhpToken::tokenize((string)file_get_contents($voll), TOKEN_PARSE);
            } catch (ParseError $e) {
                throw new VsUpdFehler("Selbsttest: $pfad hat einen Syntaxfehler (Zeile {$e->getLine()}).");
            }
        }
    }
    if (isset($manifest['dateien']['wert-update/version.json'])) {
        $v = json_decode((string)file_get_contents("$wurzel/wert-update/version.json"), true)['version'] ?? null;
        if ($v !== $manifest['version']) throw new VsUpdFehler('Selbsttest: version.json passt nicht zum Paket.');
    }
    return true;
}

/**
 * Stellt den Stand vor dem Update wieder her: ersetzte und geloeschte Dateien
 * zurueck, neue Dateien und Ordner weg. Darf mehrfach laufen.
 */
function vsUpdZurueckrollen(string $wurzel, string $sicherung): void
{
    $journal = json_decode((string)@file_get_contents("$sicherung/journal.json"), true);
    if (!is_array($journal)) throw new VsUpdFehler('Journal der Sicherung fehlt - Zurueckrollen nicht moeglich.');
    $fehler = [];
    foreach (array_merge($journal['ersetzt'], $journal['entfernt']) as $pfad) {
        try {
            vsUpdDateiSetzen("$sicherung/alt/$pfad", "$wurzel/$pfad");
        } catch (Throwable $e) {
            $fehler[] = $pfad;
        }
    }
    foreach ($journal['neu'] as $pfad) {
        if (is_file("$wurzel/$pfad") && !unlink("$wurzel/$pfad")) $fehler[] = $pfad;
    }
    foreach (array_reverse($journal['ordner']) as $o) {
        if (is_dir("$wurzel/$o")) @rmdir("$wurzel/$o");   // nur wenn leer
    }
    $journal['zustand'] = $fehler ? 'zurueckrollen_unvollstaendig' : 'zurueckgerollt';
    vsUpdJournalSchreiben($sicherung, $journal);
    if ($fehler) throw new VsUpdFehler('Zurueckrollen unvollstaendig: ' . implode(', ', $fehler));
}

/** Zustand eines frueheren Laufs: laeuft, getauscht, fertig, zurueckgerollt ... oder null. */
function vsUpdJournalZustand(string $sicherung): ?string
{
    $j = json_decode((string)@file_get_contents("$sicherung/journal.json"), true);
    return is_array($j) ? (string)($j['zustand'] ?? '') : null;
}

// ── Hilfen ──────────────────────────────────────────────────────────────────

/**
 * Das Ziel muss innerhalb der Anwendung bleiben, auch wenn ein Ordner auf dem
 * Weg eine Verknuepfung ist, und darf selbst keine Verknuepfung sein.
 */
function vsUpdZielPruefen(string $wurzelEcht, string $pfad): void
{
    $voll = "$wurzelEcht/$pfad";
    if (is_link($voll)) throw new VsUpdFehler("Ziel ist eine Verknuepfung: $pfad");
    for ($o = dirname($voll); strlen($o) > strlen($wurzelEcht); $o = dirname($o)) {
        if (is_link($o)) throw new VsUpdFehler("Ordner auf dem Weg ist eine Verknuepfung: $pfad");
        if (file_exists($o) && !is_dir($o)) throw new VsUpdFehler("Auf dem Weg liegt eine Datei statt eines Ordners: $pfad");
    }
    if (file_exists($voll) && !is_file($voll)) throw new VsUpdFehler("Ziel ist keine Datei: $pfad");
}

/**
 * Datei ersetzen: erst daneben schreiben, dann umbenennen. Ein Abbruch
 * mittendrin hinterlaesst so nie eine halb geschriebene PHP-Datei.
 */
function vsUpdDateiSetzen(string $quelle, string $ziel): void
{
    $tmp = $ziel . '.vsupd-' . bin2hex(random_bytes(4));
    if (!copy($quelle, $tmp)) throw new VsUpdFehler('Datei laesst sich nicht schreiben: ' . basename($ziel));
    if (!rename($tmp, $ziel)) {
        @unlink($tmp);
        throw new VsUpdFehler('Datei laesst sich nicht ersetzen: ' . basename($ziel));
    }
    if (function_exists('opcache_invalidate') && str_ends_with($ziel, '.php')) {
        @opcache_invalidate($ziel, true);
    }
}

function vsUpdJournalSchreiben(string $sicherung, array $journal): void
{
    $tmp = "$sicherung/journal.json.tmp";
    if (file_put_contents($tmp, json_encode($journal, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false
        || !rename($tmp, "$sicherung/journal.json")) {
        throw new VsUpdFehler('Journal laesst sich nicht schreiben.');
    }
}

/** Ordner samt Inhalt loeschen, ohne Verknuepfungen zu folgen. */
function vsUpdOrdnerLoeschen(string $ordner): void
{
    if (!is_dir($ordner) || is_link($ordner)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($ordner, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        if ($f->isDir() && !$f->isLink()) @rmdir($f->getPathname());
        else @unlink($f->getPathname());
    }
    @rmdir($ordner);
}
