<?php
/**
 * barcode_lookup.php
 * Universeller Barcode-Lookup: ISBN (Bücher), EAN/UPC (Produkte)
 *
 * GET-Parameter: ?code=<barcode>
 *
 * Rückgabe JSON:
 * {
 *   found: true/false,
 *   type: 'book' | 'product' | 'unknown',
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

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Nur für eingeloggte Benutzer
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['found' => false, 'error' => 'Nicht angemeldet']);
    exit;
}

$code = trim($_GET['code'] ?? '');
$code = preg_replace('/[^0-9X]/', '', strtoupper($code));

if (strlen($code) < 8 || strlen($code) > 14) {
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
function fetchJson(string $url): ?array {
    $ctx = stream_context_create(['http' => [
        'timeout'        => 6,
        'user_agent'     => 'Wertsachen-Inventarverwaltung/3.16 (barcode lookup)',
        'ignore_errors'  => true,
    ]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

// ── ISBN → Open Library ───────────────────────────────────────────────────
function lookupISBN(string $isbn): array {

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

// ── EAN/UPC → Open Food Facts, dann Open Beauty Facts, dann UPCitemdb ────
function lookupEAN(string $ean): array {

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
$result = match($type) {
    'isbn'  => lookupISBN($code),
    'ean'   => lookupEAN($code),
    default => ['found' => false, 'type' => 'unknown'],
};

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
