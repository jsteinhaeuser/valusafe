<?php
/**
 * cover_proxy.php
 * Lädt externe Cover-Bilder serverseitig und leitet sie weiter.
 * Umgeht CORS-Beschränkungen bei Google Books / Open Library.
 *
 * SSRF-Schutz:
 * - Nur HTTPS-URLs erlaubt
 * - Domain-Whitelist
 * - Aufgelöste IP wird auf private Ranges geprüft
 * - Redirects werden manuell verfolgt (max. 3), jede Ziel-URL erneut geprüft
 */

require_once 'db.php';

// Nur für eingeloggte Benutzer
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Nicht angemeldet');
}

// Hilfsfunktion: prüft ob eine IP in privaten/lokalen Ranges liegt
function isPrivateIP(string $ip): bool {
    $isIPv4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    $isIPv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

    if (!$isIPv4 && !$isIPv6) return true; // Ungültige IP → ablehnen

    if ($isIPv4) {
        $notPrivate = filter_var($ip, FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        return $notPrivate === false;
    }

    // IPv6
    if ($ip === '::1') return true;
    if (str_starts_with($ip, 'fc') || str_starts_with($ip, 'fd')) return true;
    if (str_starts_with($ip, 'fe80')) return true;

    return false;
}

// Hilfsfunktion: URL gegen Whitelist + private IP prüfen
function validateProxyURL(string $url, array $allowedDomains): ?string {
    $parsed = parse_url($url);

    // Nur HTTPS
    $scheme = strtolower($parsed['scheme'] ?? '');
    if ($scheme !== 'https') return 'Nur HTTPS-URLs erlaubt';

    $host = strtolower($parsed['host'] ?? '');
    if (empty($host)) return 'Ungültige URL';

    // Domain-Whitelist
    $allowed = false;
    foreach ($allowedDomains as $domain) {
        if ($host === $domain || str_ends_with($host, '.' . $domain)) {
            $allowed = true;
            break;
        }
    }
    if (!$allowed) return 'Domain nicht erlaubt: ' . htmlspecialchars($host);

    // Private IP-Check
    $resolvedIP = gethostbyname($host);
    if ($resolvedIP === $host) return 'Host konnte nicht aufgelöst werden';
    if (isPrivateIP($resolvedIP)) return 'Interne IP-Adressen nicht erlaubt';

    return null; // OK
}

$url = trim($_GET['url'] ?? '');

// Nur bekannte vertrauenswürdige Domains erlauben
$allowedDomains = [
    // Google Books
    'books.google.com',
    'books.google.de',
    'books.google.co.uk',
    'lh3.googleusercontent.com',
    'lh4.googleusercontent.com',
    'lh5.googleusercontent.com',
    'lh6.googleusercontent.com',
    'encrypted-tbn0.gstatic.com',
    'encrypted-tbn1.gstatic.com',
    'encrypted-tbn2.gstatic.com',
    'encrypted-tbn3.gstatic.com',
    // Open Library (leitet auf archive.org CDN weiter)
    'covers.openlibrary.org',
    'archive.org',
    // Amazon
    'images-na.ssl-images-amazon.com',
    'm.media-amazon.com',
    'images.amazon.com',
    // Sonstige
    'images.isbndb.com',
    'assets.thalia.media',
    // Walmart/UPCitemdb
    'i5.walmartimages.com',
    'fi5.walmartimages.com',
    'i.walmartimages.com',
];

if (empty($url)) {
    http_response_code(400);
    exit('Keine URL angegeben');
}

// Erste URL prüfen
$error = validateProxyURL($url, $allowedDomains);
if ($error !== null) {
    http_response_code(403);
    exit($error);
}

// Bild abrufen mit manuellem Redirect-Handling (max. 3 Hops)
$ctx = stream_context_create(['http' => [
    'timeout'         => 8,
    'user_agent'      => 'Wertsachen-Inventarverwaltung/3.16 (cover proxy)',
    'ignore_errors'   => true,
    'follow_location' => false,
    'max_redirects'   => 0,
]]);

$data       = false;
$currentUrl = $url;
$maxHops    = 3;

for ($hop = 0; $hop <= $maxHops; $hop++) {
    $data = @file_get_contents($currentUrl, false, $ctx);
    $statusLine = $http_response_header[0] ?? '';

    // Redirect?
    if (preg_match('/HTTP\/[\d.]+\s+(3\d\d)/', $statusLine, $m)) {
        if ($hop === $maxHops) {
            http_response_code(502);
            exit('Zu viele Redirects');
        }
        // Location-Header extrahieren
        $location = null;
        foreach ($http_response_header as $h) {
            if (stripos($h, 'location:') === 0) {
                $location = trim(substr($h, 9));
                break;
            }
        }
        if (empty($location)) {
            http_response_code(502);
            exit('Redirect ohne Location-Header');
        }
        // Relative URLs auflösen
        if (str_starts_with($location, '/')) {
            $p = parse_url($currentUrl);
            $location = $p['scheme'] . '://' . $p['host'] . $location;
        }
        // Redirect-Ziel erneut vollständig prüfen
        $error = validateProxyURL($location, $allowedDomains);
        if ($error !== null) {
            http_response_code(403);
            exit('Redirect-Ziel abgelehnt: ' . $error);
        }
        $currentUrl = $location;
        continue;
    }

    // Kein Redirect → fertig
    break;
}

if ($data === false || strlen($data) < 100) {
    http_response_code(502);
    exit('Bild konnte nicht geladen werden');
}

// Content-Type aus den HTTP-Headern ermitteln
$contentType = 'image/jpeg';
foreach ($http_response_header ?? [] as $h) {
    if (stripos($h, 'content-type:') === 0) {
        $contentType = trim(explode(':', $h, 2)[1]);
        break;
    }
}

// Nur Bilder durchleiten
if (!str_starts_with($contentType, 'image/')) {
    http_response_code(415);
    exit('Kein Bild');
}

header('Content-Type: ' . $contentType);
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
echo $data;
