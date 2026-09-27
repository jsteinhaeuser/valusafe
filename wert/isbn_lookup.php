<?php
/**
 * isbn_lookup.php – Proxy für Open Library + Google Books API
 * ?isbn=...   → Buchdaten als JSON
 * ?cover=...  → Cover-Bild serverseitig proxyen (umgeht CSP)
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

// ── Cover-Proxy ────────────────────────────────────────────────────────────
if (isset($_GET['cover'])) {
    $url  = $_GET['cover'];
    $host = parse_url($url, PHP_URL_HOST);
    $allowed = ['books.google.com', 'covers.openlibrary.org'];
    if (!in_array($host, $allowed)) { http_response_code(403); exit; }

    $data = fetchUrl($url);
    if (!$data) { http_response_code(502); exit; }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->buffer($data) ?: 'image/jpeg';
    header('Content-Type: ' . $mime);
    header('Cache-Control: max-age=86400');
    echo $data;
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
    $result['cover'] = 'isbn_lookup.php?cover=' . urlencode($result['cover']);
}

echo json_encode($result);
