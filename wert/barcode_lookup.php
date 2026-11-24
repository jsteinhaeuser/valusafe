<?php
/**
 * barcode_lookup.php
 * Universeller Barcode-Lookup: ISBN (Bücher), EAN/UPC (Musik, Produkte)
 *
 * GET-Parameter: ?code=<barcode>
 *            oder ?katnr=<Katalognummer> (seit 4.9.3, nur Musik/MusicBrainz)
 *
 * Rückgabe JSON:
 * {
 *   found: true/false,
 *   type: 'book' | 'music' | 'product' | 'unknown',
 *   name: string,
 *   description: string|null,
 *   brand: string|null,
 *   category: string|null,
 *   year: string|null,
 *   cover: string|null,    (URL zum Produktbild)
 *   source: string         (API-Quelle)
 * }
 */

require_once 'db.php';
require_once 'helpers.php';   // vsNfc()
require_once __DIR__ . '/musik_helpers.php';   // seit 4.9.3

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Nur für eingeloggte Benutzer
if (!isLoggedIn()) {  // volle Anmeldung inkl. Fingerabdruck (OWASP-A07-005)
    http_response_code(401);
    echo json_encode(['found' => false, 'error' => 'Nicht angemeldet']);
    exit;
}

// Katalognummer (seit 4.9.3): eigener Weg, nur MusicBrainz. Die Funktionen
// stehen weiter unten; ausgewertet wird erst nach ihrer Definition.
$katNr = null;
if (isset($_GET['katnr'])) {
    $katNr = vsKatNrBereinigen((string)$_GET['katnr']);
    if ($katNr === null) {
        echo json_encode(['found' => false, 'error' => 'Ungültige Katalognummer']);
        exit;
    }
}

$code = trim($_GET['code'] ?? '');
$code = preg_replace('/[^0-9X]/', '', strtoupper($code));

if ($katNr === null && (strlen($code) < 8 || strlen($code) > 14)) {
    echo json_encode(['found' => false, 'error' => 'Ungültiger Code']);
    exit;
}

// ── Typ erkennen ──────────────────────────────────────────────────────────
// ISBN-10: 10 Zeichen (Ziffern + ggf. X am Ende)
// ISBN-13: 13 Zeichen, beginnt mit 978 oder 979
// EAN-8:   8 Ziffern
// EAN-13:  13 Ziffern (nicht 978/979)
// UPC-A:   12 Ziffern
// UPC-E:   8 Ziffern

function detectType(string $code): string {
    $len = strlen($code);
    if ($len === 10) return 'isbn';
    if ($len === 13 && (str_starts_with($code, '978') || str_starts_with($code, '979'))) return 'isbn';
    if ($len === 12 || $len === 13 || $len === 8) return 'ean';
    return 'unknown';
}

$type = detectType($code);

// ── HTTP-Helper ───────────────────────────────────────────────────────────
function fetchJson(string $url, string $userAgent = 'Wertsachen-Inventarverwaltung/3.16 (barcode lookup)'): ?array {
    $ctx = stream_context_create(['http' => [
        'timeout'        => 6,
        'user_agent'     => $userAgent,
        'ignore_errors'  => true,
    ]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

// ── ISBN → Deutsche Nationalbibliothek (seit 4.9) ─────────────────────────
// Gemessen am 07.11.2026 mit 21 deutschen ISBNs: Google Books antwortete
// ohne API-Schluessel nur mit 429 (Kontingent je IP, auf geteilten
// Webspaces schnell verbraucht), Open Library fand 3, die DNB alle 21.
// SRU-Schnittstelle ohne Schluessel, Metadaten CC0. Die DNB hat keine
// Cover; das von Open Library wird nur genommen, wenn es eines gibt.

function isbnZu13(string $isbn): string {
    if (strlen($isbn) !== 10) return $isbn;
    $kern = '978' . substr($isbn, 0, 9);
    $summe = 0;
    for ($i = 0; $i < 12; $i++) $summe += (int)$kern[$i] * ($i % 2 ? 3 : 1);
    return $kern . ((10 - $summe % 10) % 10);
}

function fetchText(string $url, int $timeout = 6): ?string {
    $ctx = stream_context_create(['http' => [
        'timeout'        => $timeout,
        'user_agent'     => 'Wertsachen-Inventarverwaltung/4.9 (barcode lookup)',
        'ignore_errors'  => true,
    ]]);
    $raw = @file_get_contents($url, false, $ctx);
    return $raw === false ? null : $raw;
}

/**
 * Ladenpreis aus MARC 020 $c, z. B. "Broschur : circa EUR 22.00 (DE), circa
 * EUR 22.70 (AT)" oder bei aelteren Buechern "kart. : DM 14.80, sfr 14.00".
 * Bevorzugt der deutsche Euro-Preis, sonst der erste Euro-Preis, sonst D-Mark
 * zum amtlichen Kurs. Subskriptionspreise zaehlen nicht.
 * Rueckgabe [Betrag in Euro, Originaltext fuer die Notiz] oder null.
 */
function dnbLadenpreis(?string $text): ?array {
    if (!$text) return null;
    $treffer = [];
    preg_match_all('/(EUR|DM)\s*(\d+(?:[.,]\d{1,2})?)\s*(\([^)]*\))?/u', $text, $m, PREG_SET_ORDER);
    foreach ($m as $t) {
        $zusatz = $t[3] ?? '';
        if (stripos($zusatz, 'subskr') !== false) continue;
        $treffer[] = ['waehrung' => $t[1], 'betrag' => (float)str_replace(',', '.', $t[2]),
                      'de' => stripos($zusatz, '(DE)') !== false, 'text' => trim($t[0])];
    }
    $wahl = null;
    foreach ($treffer as $t) if ($t['waehrung'] === 'EUR' && $t['de']) { $wahl = $t; break; }
    if (!$wahl) foreach ($treffer as $t) if ($t['waehrung'] === 'EUR') { $wahl = $t; break; }
    if (!$wahl) foreach ($treffer as $t) if ($t['waehrung'] === 'DM')  { $wahl = $t; break; }
    if (!$wahl || $wahl['betrag'] <= 0) return null;
    $euro = $wahl['waehrung'] === 'DM' ? round($wahl['betrag'] / 1.95583, 2) : $wahl['betrag'];
    return [$euro, $wahl['text']];
}

function lookupDNB(string $isbn13): ?array {
    if (!class_exists('DOMDocument')) return null;
    $xml = fetchText('https://services.dnb.de/sru/dnb?version=1.1&operation=searchRetrieve'
                   . '&recordSchema=MARC21-xml&maximumRecords=1&query=' . rawurlencode('num=' . $isbn13));
    if (!$xml || strpos($xml, '<record') === false) return null;

    $dom = new DOMDocument();
    if (!@$dom->loadXML($xml, LIBXML_NONET)) return null;
    $xp = new DOMXPath($dom);
    $xp->registerNamespace('m', 'http://www.loc.gov/MARC21/slim');
    $rec = $xp->query('//m:record')->item(0);
    if (!$rec) return null;

    // Erstes Unterfeld; die DNB markiert Artikel fuer die Sortierung mit
    // U+0098/U+009C ("<<Die>> Welt") - die Zeichen muessen weg.
    $feld = function (string $tags, string $code) use ($xp, $rec): ?string {
        foreach (explode(',', $tags) as $tag) {
            $n = $xp->query("m:datafield[@tag='$tag']/m:subfield[@code='$code']", $rec)->item(0);
            if ($n) {
                $t = trim(preg_replace('/[\x{0098}\x{009C}]/u', '', $n->textContent), " \t\n/:;,");
                if ($t !== '') return $t;
            }
        }
        return null;
    };

    $title = $feld('245', 'a');
    if (!$title) return null;
    $subtitle = $feld('245', 'b');

    // Personen: "Hanke, Johann-Christian" -> "Johann-Christian Hanke".
    // 100 ist die Hauptperson; aus 700 nur Mitverfasser ($4 aut), nicht
    // Uebersetzer (trl), Herausgeber (edt) oder Illustratoren (ill).
    $autoren = [];
    foreach ($xp->query("m:datafield[@tag='100']/m:subfield[@code='a'] | m:datafield[@tag='700'][m:subfield[@code='4']='aut']/m:subfield[@code='a']", $rec) as $n) {
        $name = trim(preg_replace('/[\x{0098}\x{009C}]/u', '', $n->textContent));
        if (preg_match('/^([^,]+),\s*(.+)$/u', $name, $m)) $name = $m[2] . ' ' . $m[1];
        if ($name !== '' && !in_array($name, $autoren, true)) $autoren[] = $name;
        if (count($autoren) >= 3) break;
    }
    $authors   = $autoren ? implode(', ', $autoren) : null;
    $publisher = $feld('264,260', 'b');
    $jahr      = $feld('264,260', 'c');
    $year      = ($jahr && preg_match('/\d{4}/', $jahr, $m)) ? $m[0] : null;
    $auflage   = $feld('250', 'a');
    // Preis steht nur am 020 der jeweiligen ISBN; das erste 020 mit $c genuegt
    $preis = null;
    foreach ($xp->query("m:datafield[@tag='020']/m:subfield[@code='c']", $rec) as $n) {
        if ($preis = dnbLadenpreis($n->textContent)) break;
    }

    $cover = "https://covers.openlibrary.org/b/isbn/{$isbn13}-L.jpg?default=false";
    $kopf  = @get_headers($cover, false, stream_context_create(['http' => ['method' => 'HEAD', 'timeout' => 3]]));
    if (!$kopf || !preg_grep('#^HTTP/\S+ 200#', $kopf)) $cover = null;

    $notes = [];
    if ($authors)   $notes[] = "Autor: {$authors}";
    if ($publisher) $notes[] = "Verlag: {$publisher}";
    if ($year)      $notes[] = "Erschienen: {$year}";
    if ($auflage)   $notes[] = $auflage;
    if ($preis)     $notes[] = "Ladenpreis: {$preis[1]}";

    return [
        'found'       => true,
        'type'        => 'book',
        'name'        => $subtitle ? "{$title}: {$subtitle}" : $title,
        'description' => $notes ? implode("\n", $notes) : null,
        'brand'       => $authors,
        'category'    => 'Buch',
        'year'        => $year,
        'cover'       => $cover,
        'source'      => 'Deutsche Nationalbibliothek',
        'price'       => $preis ? $preis[0] : null,   // seit 4.9: Preisfeld vorbelegen
    ];
}

/**
 * Buecher aus dem deutschen Sprachraum (978-3) zuerst bei der DNB, alle
 * anderen erst bei Google Books / Open Library und die DNB als Rueckfall
 * (sie fuehrt auch viele fremdsprachige Titel).
 */
function lookupISBN(string $isbn): array {
    $isbn13  = isbnZu13($isbn);
    $deutsch = str_starts_with($isbn13, '9783');
    if ($deutsch && ($r = lookupDNB($isbn13))) return $r;
    $r = lookupISBNWeltweit($isbn);
    if ($r['found'] || $deutsch) return $r;
    return lookupDNB($isbn13) ?? $r;
}

// ── ISBN → Google Books, Open Library (bis 4.8 die einzigen Quellen) ──────
function lookupISBNWeltweit(string $isbn): array {

    // ── 1. Google Books (beste Abdeckung, kein API-Key nötig) ─────────────
    $gbUrl  = "https://www.googleapis.com/books/v1/volumes?q=isbn:{$isbn}&maxResults=1&fields=items(volumeInfo)";
    $gbData = fetchJson($gbUrl);

    if (!empty($gbData['items'][0]['volumeInfo'])) {
        $vi        = $gbData['items'][0]['volumeInfo'];
        $title     = $vi['title'] ?? null;
        $subtitle  = $vi['subtitle'] ?? null;
        $fullTitle = $subtitle ? "{$title}: {$subtitle}" : $title;
        $authors   = isset($vi['authors']) ? implode(', ', $vi['authors']) : null;
        $year      = isset($vi['publishedDate']) ? substr($vi['publishedDate'], 0, 4) : null;
        $publisher = $vi['publisher'] ?? null;
        $desc      = $vi['description'] ?? null;
        // Cover: zoom=1 = Thumbnail, zoom=3 = Large
        $coverId   = $vi['imageLinks']['thumbnail']
                  ?? $vi['imageLinks']['smallThumbnail']
                  ?? null;
        // https-Version erzwingen und zoom erhöhen
        $cover = $coverId
            ? preg_replace('/^http:/', 'https:', str_replace('zoom=1', 'zoom=3', $coverId))
            : null;

        if ($title) {
            $notes = [];
            if ($authors)   $notes[] = "Autor: {$authors}";
            if ($publisher) $notes[] = "Verlag: {$publisher}";
            if ($year)      $notes[] = "Erschienen: {$year}";
            // Kurze Beschreibung als letzten Eintrag (max 300 Zeichen)
            if ($desc && !$notes) $notes[] = mb_substr($desc, 0, 300);

            return [
                'found'       => true,
                'type'        => 'book',
                'name'        => $fullTitle,
                'description' => implode("\n", $notes),
                'brand'       => $authors,
                'category'    => 'Buch',
                'year'        => $year,
                'cover'       => $cover,
                'source'      => 'Google Books',
            ];
        }
    }

    // ── 2. Open Library Books API ─────────────────────────────────────────
    $url  = "https://openlibrary.org/api/books?bibkeys=ISBN:{$isbn}&format=json&jscmd=data";
    $data = fetchJson($url);

    if (!empty($data["ISBN:{$isbn}"])) {
        $book      = $data["ISBN:{$isbn}"];
        $title     = $book['title'] ?? null;
        $authors   = isset($book['authors'])
            ? implode(', ', array_column($book['authors'], 'name'))
            : null;
        $year      = $book['publish_date'] ?? null;
        $publisher = isset($book['publishers'])
            ? implode(', ', array_column($book['publishers'], 'name'))
            : null;
        $cover     = $book['cover']['large'] ?? $book['cover']['medium'] ?? null;

        if ($title) {
            $notes = [];
            if ($authors)   $notes[] = "Autor: {$authors}";
            if ($publisher) $notes[] = "Verlag: {$publisher}";
            if ($year)      $notes[] = "Erschienen: {$year}";
            return [
                'found'       => true,
                'type'        => 'book',
                'name'        => $title,
                'description' => implode("\n", $notes) ?: null,
                'brand'       => $authors,
                'category'    => 'Buch',
                'year'        => $year,
                'cover'       => $cover,
                'source'      => 'Open Library',
            ];
        }
    }

    // ── 3. Open Library Search API (letzter Fallback) ─────────────────────
    $url3  = "https://openlibrary.org/search.json?isbn={$isbn}&fields=title,author_name,first_publish_year,publisher,cover_i&limit=1";
    $data3 = fetchJson($url3);
    if (!empty($data3['docs'][0])) {
        $doc     = $data3['docs'][0];
        $title   = $doc['title'] ?? null;
        $authors = isset($doc['author_name']) ? implode(', ', (array)$doc['author_name']) : null;
        $year    = $doc['first_publish_year'] ?? null;
        $cover   = isset($doc['cover_i'])
            ? "https://covers.openlibrary.org/b/id/{$doc['cover_i']}-L.jpg"
            : null;
        if ($title) {
            return [
                'found'       => true,
                'type'        => 'book',
                'name'        => $title,
                'description' => $authors ? "Autor: {$authors}" : null,
                'brand'       => $authors,
                'category'    => 'Buch',
                'year'        => $year ? (string)$year : null,
                'cover'       => $cover,
                'source'      => 'Open Library',
            ];
        }
    }

    return ['found' => false, 'type' => 'book'];
}

// ── EAN/UPC → MusicBrainz (CDs, LPs) ──────────────────────────────────────
// Ohne Schluessel, Kerndaten CC0. MusicBrainz verlangt eine aussagekraeftige
// Absenderkennung und hoechstens eine Abfrage je Sekunde. Keine Preise.
const VS_MB_UA = 'ValuSafe/4 ( https://palindrom.de/valusafe/ )';

/** Cover nur anbieten, wenn es eines gibt: erst die Pressung, dann das Album. */
function musikCover(string $releaseId, string $gruppenId): ?string {
    foreach ([['release', $releaseId], ['release-group', $gruppenId]] as [$art, $id]) {
        if (!preg_match('/^[0-9a-f-]{36}$/', $id)) continue;
        $url = "https://coverartarchive.org/{$art}/{$id}/front-500";
        $ctx = stream_context_create(['http' => [
            'method'          => 'HEAD',
            'timeout'         => 3,
            'user_agent'      => VS_MB_UA,
            'ignore_errors'   => true,
            'follow_location' => false,
            'max_redirects'   => 0,
        ]]);
        $fp = @fopen($url, 'rb', false, $ctx);
        if ($fp === false) continue;
        $status = stream_get_meta_data($fp)['wrapper_data'][0] ?? '';
        fclose($fp);
        if (preg_match('/HTTP\/[\d.]+\s+(200|30[1278])/', $status)) return $url;
    }
    return null;
}

/**
 * Eine Suchanfrage an MusicBrainz, hoechstens eine je Sekunde und Sitzung.
 * Antwortet der Dienst mit einem Fehler ("busy", 503), einmal nachfassen.
 * Liefert die Antwort, oder null, wenn er auch dann nicht antwortet.
 */
function musicBrainzSuche(string $abfrage): ?array {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $warten = ($_SESSION['vs_mb_letzte'] ?? 0) + 1.1 - microtime(true);
        if ($warten > 0) usleep((int)($warten * 1000000));
        $_SESSION['vs_mb_letzte'] = microtime(true);
    }

    $url  = 'https://musicbrainz.org/ws/2/release/?fmt=json&limit=25&query=' . rawurlencode($abfrage);
    $data = fetchJson($url, VS_MB_UA);
    // Gemessen 09.11. und 22.11.2026: 503 auch bei mehreren Sekunden Abstand.
    // Bei Zeitueberschreitung nicht nachfassen, sonst wartet der Scan doppelt.
    if (is_array($data) && !isset($data['releases'])) {
        usleep(1500000);
        $data = fetchJson($url, VS_MB_UA);
    }
    return (is_array($data) && isset($data['releases'])) ? $data : null;
}

function lookupMusicBrainz(string $ean): ?array {
    // Dieselbe Ware kann als UPC-A (12) oder mit fuehrender 0 (13) gelesen
    // werden und in MusicBrainz in beiden Schreibweisen stehen.
    $kern      = ltrim($ean, '0');
    $varianten = [$kern];
    if (strlen($kern) < 13) $varianten[] = '0' . $kern;
    $data = musicBrainzSuche(implode(' OR ', array_map(fn($v) => 'barcode:' . $v, $varianten)));

    // Ein Barcode passt oft auf mehrere Eintraege derselben Pressung:
    // den vollstaendigsten nehmen, bei Gleichstand den ersten.
    $beste = vsMusikBeste($data['releases'] ?? [],
        fn($r) => ltrim((string)($r['barcode'] ?? ''), '0') === $kern);
    if ($beste === null) return null;

    return vsMusikErgebnis($beste,
        musikCover((string)($beste['id'] ?? ''), (string)($beste['release-group']['id'] ?? '')));
}

/**
 * Katalognummer -> MusicBrainz (seit 4.9.3, fuer aeltere LPs ohne Barcode).
 * Schreibweisen und Auswahl: musik_helpers.php.
 */
function lookupKatNr(string $katNr): array {
    $data = musicBrainzSuche(vsKatNrAbfrage($katNr));
    if ($data === null) {
        // Nicht "nichts gefunden": die Platte kann durchaus drin sein.
        return ['found' => false, 'type' => 'music', 'busy' => true];
    }
    $beste = vsMusikBeste($data['releases'],
        fn($r) => vsKatNrDesEintrags($r, $katNr) !== null, true);
    if ($beste === null) return ['found' => false, 'type' => 'music'];

    return vsMusikErgebnis($beste,
        musikCover((string)($beste['id'] ?? ''), (string)($beste['release-group']['id'] ?? '')),
        $katNr);
}

// ── EAN/UPC → MusicBrainz, Open Food Facts, Open Beauty Facts, UPCitemdb ──
function lookupEAN(string $ean): array {

    // Musik zuerst: sonst warten CDs auf zwei Lebensmittel-Datenbanken
    $musik = lookupMusicBrainz($ean);
    if ($musik !== null) return $musik;

    // 1. Open Food Facts (Lebensmittel)
    $url  = "https://world.openfoodfacts.org/api/v0/product/{$ean}.json";
    $data = fetchJson($url);

    if (!empty($data['status']) && $data['status'] === 1 && !empty($data['product'])) {
        $p     = $data['product'];
        $name  = $p['product_name'] ?? $p['product_name_de'] ?? $p['product_name_en'] ?? null;
        $brand = $p['brands'] ?? null;
        $cat   = $p['categories_tags'][0] ?? null;
        if ($cat) $cat = ucfirst(str_replace(['en:', 'de:', '-'], ['', '', ' '], $cat));
        $image = $p['image_front_url'] ?? $p['image_url'] ?? null;
        $qty   = $p['quantity'] ?? null;
        $desc  = array_filter([
            $brand ? "Marke: {$brand}" : null,
            $qty   ? "Menge: {$qty}"   : null,
        ]);

        if ($name) {
            return [
                'found'       => true,
                'type'        => 'product',
                'name'        => $name . ($brand ? " ({$brand})" : ''),
                'description' => implode("\n", $desc) ?: null,
                'brand'       => $brand,
                'category'    => $cat ?: 'Lebensmittel',
                'year'        => null,
                'cover'       => $image,
                'source'      => 'Open Food Facts',
            ];
        }
    }

    // 2. Open Beauty Facts (Kosmetik)
    $url2  = "https://world.openbeautyfacts.org/api/v0/product/{$ean}.json";
    $data2 = fetchJson($url2);

    if (!empty($data2['status']) && $data2['status'] === 1 && !empty($data2['product'])) {
        $p     = $data2['product'];
        $name  = $p['product_name'] ?? null;
        $brand = $p['brands'] ?? null;
        $image = $p['image_front_url'] ?? $p['image_url'] ?? null;
        if ($name) {
            return [
                'found'       => true,
                'type'        => 'product',
                'name'        => $name . ($brand ? " ({$brand})" : ''),
                'description' => $brand ? "Marke: {$brand}" : null,
                'brand'       => $brand,
                'category'    => 'Kosmetik',
                'year'        => null,
                'cover'       => $image,
                'source'      => 'Open Beauty Facts',
            ];
        }
    }

    // 3. UPCitemdb (allgemeine Produkte, kostenlose Tier: 100 req/Tag)
    $url3  = "https://api.upcitemdb.com/prod/trial/lookup?upc={$ean}";
    $data3 = fetchJson($url3);

    if (!empty($data3['items'][0])) {
        $item  = $data3['items'][0];
        $name  = $item['title'] ?? null;
        $brand = $item['brand'] ?? null;
        $cat   = $item['category'] ?? null;
        $desc  = $item['description'] ?? null;
        $image = $item['images'][0] ?? null;
        if ($name) {
            return [
                'found'       => true,
                'type'        => 'product',
                'name'        => $name,
                'description' => $desc ?: ($brand ? "Marke: {$brand}" : null),
                'brand'       => $brand,
                'category'    => $cat,
                'year'        => null,
                'cover'       => $image,
                'source'      => 'UPCitemdb',
            ];
        }
    }

    return ['found' => false, 'type' => 'product'];
}

// ── Routing ───────────────────────────────────────────────────────────────
$result = $katNr !== null ? lookupKatNr($katNr) : match($type) {
    'isbn'  => lookupISBN($code),
    'ean'   => lookupEAN($code),
    default => ['found' => false, 'type' => 'unknown'],
};

// Umlaute aus den APIs zusammensetzen (NFD -> NFC), seit 4.9
echo json_encode(vsNfc($result), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
