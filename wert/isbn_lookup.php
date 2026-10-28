<?php
/**
 * isbn_lookup.php – Proxy für Open Library + Google Books API
 * ?isbn=...   → Buchdaten als JSON
 * ?cover=...  → Weiterleitung auf cover_proxy.php (seit 4.6)
 */
require_once __DIR__ . '/db.php';
requireLogin();

function fetchUrl(string $url): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'WertsachenIV/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $out = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        return ($out !== false && !$err) ? $out : null;
    }
    $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true,
        'header' => "User-Agent: WertsachenIV/1.0\r\n"]]);
    $out = @file_get_contents($url, false, $ctx);
    return $out !== false ? $out : null;
}

// ── Cover ──────────────────────────────────────────────────────────────────
// Seit 4.6 kein eigener Abruf mehr (OWASP-A01-007): fetchUrl() folgte
// Weiterleitungen ungeprueft, also auch auf interne Adressen. cover_proxy.php
// macht dieselbe Aufgabe abgesichert (nur HTTPS, Domainliste, private IPs
// gesperrt, jede Weiterleitung einzeln geprueft, nur Rasterbilder).
// Die Seite selbst ruft isbn_lookup.php nicht mehr auf (add/edit nutzen
// barcode_lookup.php); der Zweig bleibt fuer alte Links als Weiterleitung.
function isbnCoverUeberProxy(string $url): string {
    return 'cover_proxy.php?url=' . urlencode(preg_replace('#^http://#i', 'https://', $url));
}
if (isset($_GET['cover'])) {
    header('Location: ' . isbnCoverUeberProxy((string)$_GET['cover']), true, 302);
    exit;
}

// ── ISBN-Lookup ────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');

$isbn = preg_replace('/[^0-9X]/', '', strtoupper($_GET['isbn'] ?? ''));
if (!$isbn || !preg_match('/^\d{10}(\d{3})?$/', $isbn)) {
    echo json_encode(['error' => 'Ungültige ISBN']); exit;
}

$result = ['found' => false, 'title' => '', 'authors' => '', 'year' => '', 'publisher' => '', 'cover' => ''];

// 1. Open Library
$raw = fetchUrl("https://openlibrary.org/api/books?bibkeys=ISBN:{$isbn}&format=json&jscmd=data");
if ($raw) {
    $data = json_decode($raw, true);
    $book = $data["ISBN:{$isbn}"] ?? null;
    if ($book) {
        $result['found']     = true;
        $result['title']     = $book['title'] ?? '';
        $result['authors']   = implode(', ', array_map(fn($a) => $a['name'], $book['authors']   ?? []));
        $result['year']      = $book['publish_date'] ?? '';
        $result['publisher'] = implode(', ', array_map(fn($p) => $p['name'], $book['publishers'] ?? []));
        $result['cover']     = $book['cover']['medium'] ?? $book['cover']['small'] ?? '';
    }
}

// 2. Google Books (Ergänzung/Fallback)
if (!$result['found'] || !$result['authors'] || !$result['year'] || !$result['publisher']) {
    $graw = fetchUrl("https://www.googleapis.com/books/v1/volumes?q=isbn:{$isbn}");
    if ($graw) {
        $gdata = json_decode($graw, true);
        $gbook = $gdata['items'][0]['volumeInfo'] ?? null;
        if ($gbook) {
            $result['found'] = true;
            if (!$result['title'])     $result['title']     = $gbook['title'] ?? '';
            if (!$result['authors'])   $result['authors']   = implode(', ', $gbook['authors'] ?? []);
            if (!$result['publisher']) $result['publisher'] = $gbook['publisher'] ?? '';
            if (!$result['year'])      $result['year']      = substr($gbook['publishedDate'] ?? '', 0, 4);
            if (!$result['cover'])     $result['cover']     = $gbook['imageLinks']['thumbnail'] ?? '';
            $result['cover'] = str_replace('http://', 'https://', $result['cover']);
        }
    }
}

// Cover-URL durch lokalen Proxy ersetzen damit CSP kein Problem ist
if ($result['cover']) {
    $result['cover'] = isbnCoverUeberProxy($result['cover']);
}

echo json_encode($result);
