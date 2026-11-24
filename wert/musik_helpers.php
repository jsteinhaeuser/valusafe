<?php
/**
 * musik_helpers.php - Musik-Suche ueber MusicBrainz, ohne Netz (seit 4.9.3)
 *
 * Reine Logik fuer barcode_lookup.php: Katalognummer bereinigen, Suchanfrage
 * bauen, Treffer pruefen und auswaehlen, Ergebnis aufbereiten. Die Abfragen
 * selbst stehen weiter in barcode_lookup.php; hier liegt nur, was sich ohne
 * MusicBrainz testen laesst (tests/MusikHelpersTest.php).
 *
 * WARUM KATALOGNUMMER (Rueckmeldung Joerg 22.11.2026): Die Barcode-Suche
 * findet CDs und neuere LPs, aeltere LPs aber oft nicht - viele von vor etwa
 * 1980 haben gar keinen EAN. Die Katalognummer steht dagegen auf fast jeder
 * Platte (Etikett, Ruecken), z. B. "SHVL 804" oder "6.21081 AS".
 *
 * GEMESSEN 22.11.2026 an der MusicBrainz-Suche (catno:):
 *   - Leerzeichen und Bindestriche sind egal: "SHVL804" und "shvl-804"
 *     finden "SHVL 804", "6.21138" findet "6.21 138".
 *   - Ein weggelassener Zusatz NICHT: "6.21081" findet "6.21081 AS" nicht,
 *     als Platzhalter ohne Satzzeichen aber schon: "621081*" findet
 *     "6.21081 AS", "SLE14790*" findet "SLE 14790-P" ("6.21081*" mit Punkt
 *     findet nichts).
 *   Darum fragt vsKatNrAbfrage() mehrere Schreibweisen auf einmal ab, und
 *   vsKatNrPasst() siebt die Treffer danach selbst: gleich, oder die Nummer
 *   der Platte beginnt mit der eingegebenen (fehlender Zusatz).
 *   - Der Dienst antwortet oft mit "busy" (503); barcode_lookup.php fasst
 *     einmal nach und meldet es sonst als "ausgelastet", nicht als "nichts
 *     gefunden".
 */

/**
 * Eingabe pruefen: nur Buchstaben, Ziffern, Leerzeichen, Punkt, Bindestrich,
 * Schraegstrich; 2 bis 30 Zeichen, davon mindestens eine Ziffer (reine Woerter
 * waeren eine Titelsuche). Liefert die bereinigte Nummer oder null.
 */
function vsKatNrBereinigen(string $eingabe): ?string
{
    $k = trim(preg_replace('/\s+/u', ' ', $eingabe));
    if ($k === '' || !preg_match('/^[A-Za-z0-9 .\/-]{2,30}$/', $k) || !preg_match('/\d/', $k)) {
        return null;
    }
    return $k;
}

/** Vergleichsform: gross, nur Buchstaben und Ziffern ("6.21 138" -> "621138"). */
function vsKatNrNorm(string $k): string
{
    return preg_replace('/[^A-Z0-9]/', '', strtoupper($k));
}

/**
 * Lucene-Anfrage fuer MusicBrainz: genaue Schreibweise ODER als Platzhalter
 * (fehlender Zusatz) ODER alle Teile einzeln. Erwartet eine Eingabe aus
 * vsKatNrBereinigen() - die enthaelt weder Anfuehrungszeichen noch Backslash.
 */
function vsKatNrAbfrage(string $k): string
{
    $teile = [];
    $teile[] = 'catno:"' . $k . '"';

    // Platzhalter werden nicht analysiert. MusicBrainz legt die Nummer ohne
    // Satzzeichen ab ("6.21081 AS" -> 621081as): also klein, nur Buchstaben
    // und Ziffern - mit Punkt fand "6.21081*" nichts, "621081*" schon.
    $kompakt = strtolower(vsKatNrNorm($k));
    if (strlen($kompakt) >= 4) {
        $teile[] = 'catno:' . $kompakt . '*';
    }

    $woerter = array_values(array_filter(preg_split('/[^A-Za-z0-9]+/', $k), 'strlen'));
    if (count($woerter) > 1) {
        $teile[] = 'catno:(' . implode(' AND ', $woerter) . ')';
    }
    return implode(' OR ', $teile);
}

/**
 * Passt die Nummer einer Platte zur Eingabe? Gleich, oder die Platte hat
 * einen Zusatz, den die Eingabe weglaesst ("6.21081" -> "6.21081 AS"). Die
 * Eingabe muss dafuer mindestens 4 Zeichen haben, sonst passt zu viel.
 */
function vsKatNrPasst(string $gesucht, string $kandidat): bool
{
    $g = vsKatNrNorm($gesucht);
    $c = vsKatNrNorm($kandidat);
    if ($g === '' || $c === '') return false;
    if ($g === $c) return true;
    return strlen($g) >= 4 && str_starts_with($c, $g);
}

/**
 * Den vollstaendigsten passenden Eintrag waehlen. $passt($release) entscheidet,
 * ob ein Eintrag in Frage kommt. Schallplatten zaehlen bei der Katalognummer
 * mehr ($vinylZuerst), weil sie der Grund fuer diese Suche sind.
 */
function vsMusikBeste(array $releases, callable $passt, bool $vinylZuerst = false): ?array
{
    $beste  = null;
    $punkte = -1;
    foreach ($releases as $r) {
        if (!is_array($r) || empty($r['title']) || !$passt($r)) continue;
        $format = (string)($r['media'][0]['format'] ?? '');
        $p = (!empty($r['date']) ? 2 : 0)
           + ($format !== '' ? 2 : 0)
           + (!empty($r['label-info'][0]['catalog-number']) ? 1 : 0)
           + ($vinylZuerst && stripos($format, 'vinyl') !== false ? 3 : 0)
           + (($r['status'] ?? '') === 'Official' ? 1 : 0);
        if ($p > $punkte) { $beste = $r; $punkte = $p; }
    }
    return $beste;
}

/** Erste Katalognummer eines Eintrags, die zur Eingabe passt (fuer die Notiz). */
function vsKatNrDesEintrags(array $r, ?string $gesucht = null): ?string
{
    foreach ($r['label-info'] ?? [] as $l) {
        $nr = $l['catalog-number'] ?? null;
        if (!is_string($nr) || $nr === '' || $nr === '[none]') continue;
        if ($gesucht === null || vsKatNrPasst($gesucht, $nr)) return $nr;
    }
    return null;
}

/**
 * Ergebnis fuer die Formulare aus einem MusicBrainz-Eintrag. Aufbau wie bei
 * allen Quellen in barcode_lookup.php. $gesucht: Katalognummer der Eingabe -
 * dann steht in der Notiz die passende Nummer samt ihrem Label.
 */
function vsMusikErgebnis(array $r, ?string $cover, ?string $gesucht = null): array
{
    $kuenstler = '';
    foreach ($r['artist-credit'] ?? [] as $c) {
        $kuenstler .= ($c['name'] ?? '') . ($c['joinphrase'] ?? '');
    }

    $formate = [];
    foreach ($r['media'] ?? [] as $m) {
        if (!empty($m['format'])) $formate[$m['format']] = ($formate[$m['format']] ?? 0) + 1;
    }
    $format = [];
    foreach ($formate as $f => $n) $format[] = ($n > 1 ? "{$n}×" : '') . $f;

    $jahr = substr((string)($r['date'] ?? ''), 0, 4) ?: null;

    // Label und Nummer aus demselben Eintrag: bei mehreren Labels (EMI +
    // Harvest) gehoert die gesuchte Nummer oft zum zweiten.
    $label = null;
    $katNr = null;
    foreach ($r['label-info'] ?? [] as $l) {
        $nr = $l['catalog-number'] ?? null;
        if ($gesucht !== null && !(is_string($nr) && vsKatNrPasst($gesucht, $nr))) continue;
        $label = $l['label']['name'] ?? null;
        $katNr = $nr;
        break;
    }
    if ($gesucht === null && $label === null) {
        $label = $r['label-info'][0]['label']['name'] ?? null;
        $katNr = $r['label-info'][0]['catalog-number'] ?? null;
    }
    // Platzhalter von MusicBrainz nicht in die Notizen uebernehmen
    if ($label === '[no label]') $label = null;
    if ($katNr === '[none]')     $katNr = null;

    // Sprachneutral, damit keine Sprache deutsche Beschriftungen erhaelt
    $details = array_filter([implode(' + ', $format), $jahr, $r['country'] ?? null, $label, $katNr]);

    return [
        'found'       => true,
        'type'        => 'music',
        // Kuenstler im Namen: die Doppelten-Pruefung vergleicht Name und
        // Ersteller, und "Greatest Hits" gibt es von vielen.
        'name'        => $kuenstler !== '' ? "{$kuenstler} – {$r['title']}" : (string)$r['title'],
        'description' => $details ? implode(' · ', $details) : null,
        'brand'       => null,
        'category'    => 'Musik',
        'year'        => $jahr,
        'cover'       => $cover,
        'source'      => 'MusicBrainz',
    ];
}
