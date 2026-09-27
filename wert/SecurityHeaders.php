<?php
/**
 * SecurityHeaders Class
 * Setzt Security-relevante HTTP Headers
 */

class SecurityHeaders {
    
    /**
     * Setzt alle Security Headers
     */
    public static function setHeaders() {
        // Nur setzen wenn Headers noch nicht gesendet wurden
        if (headers_sent()) {
            return;
        }
        
        // 1. Content Security Policy (CSP)
        // Verhindert XSS durch Whitelist von erlaubten Quellen
        self::setCSP();
        
        // 2. HTTP Strict Transport Security (HSTS)
        // Erzwingt HTTPS für 1 Jahr (nur wenn bereits HTTPS)
        if (self::isHTTPS()) {
            self::setHSTS();
        }
        
        // 3. X-Frame-Options
        // Verhindert Clickjacking durch Einbettung in iframes
        self::setFrameOptions();
        
        // 4. X-Content-Type-Options
        // Verhindert MIME-Sniffing
        self::setContentTypeOptions();
        
        // 5. X-XSS-Protection (für ältere Browser)
        self::setXSSProtection();
        
        // 6. Referrer Policy
        // Kontrolliert welche Referrer-Informationen gesendet werden
        self::setReferrerPolicy();
        
        // 7. Permissions Policy (früher Feature-Policy)
        // Kontrolliert Browser-Features
        self::setPermissionsPolicy();
    }
    
    /**
     * Content Security Policy
     */
    private static function setCSP() {
        $csp = [
            "default-src 'self'",
            // 'unsafe-inline' nötig für inline <script> und <style> in header.php / Seiten
            // Externe CDNs entfernt: Chart.js lokal (npm), marked.js inline, ZXing lokal
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            // data: für FileReader-Previews; blob: entfernt (iOS-Probleme, FileReader als Ersatz)
            // Externe Cover-Domains entfernt: cover_proxy.php lädt server-seitig
            "img-src 'self' data:",
            "font-src 'self'",
            // Alle fetch()-Calls (barcode_lookup.php, cover_proxy.php, etc.) sind same-origin
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        // upgrade-insecure-requests NUR bei bereits gesicherter Verbindung.
        //
        // Die Direktive hebt jede http-Adresse, die AUS dem Dokument heraus
        // aufgerufen wird, auf https — auch das Abschicken von Formularen.
        // Auf einer reinen HTTP-Installation (Heimnetz, Docker ohne Reverse
        // Proxy) geht der Anmelde-POST damit an einen Port, der kein TLS
        // spricht: der Browser meldet einen Verbindungsfehler, der Server
        // sieht die Anfrage nie und protokolliert nichts.
        //
        // Die Anmeldeseite selbst laedt weiterhin, weil die Direktive die
        // Navigation, die das Dokument geladen hat, nicht betrifft. Der Fehler
        // tritt erst beim Absenden auf und sieht deshalb nach einem
        // Browserproblem aus. Bis 4.3.3 wurde die Direktive bedingungslos
        // gesetzt; auf HTTPS-Instanzen fiel es nie auf.
        if (self::isHTTPS()) {
            $csp[] = "upgrade-insecure-requests";
        }
        
        header("Content-Security-Policy: " . implode('; ', $csp));
    }
    
    /**
     * HTTP Strict Transport Security
     */
    private static function setHSTS() {
        // Kein includeSubDomains: Lima-city-Instanzen laufen auf *.2ix.de (nicht eigene Domain)
        // Kein preload: nur setzen wenn Domain aktiv in HSTS Preload List eingetragen wird
        header("Strict-Transport-Security: max-age=31536000");
    }
    
    /**
     * X-Frame-Options
     */
    private static function setFrameOptions() {
        // DENY: App ist nicht für iframe-Einbettung gedacht
        // Konsistent mit frame-ancestors 'none' in CSP (doppelte Absicherung für ältere Browser)
        header("X-Frame-Options: DENY");
    }
    
    /**
     * X-Content-Type-Options
     */
    private static function setContentTypeOptions() {
        // nosniff = Browser darf MIME-Type nicht erraten
        header("X-Content-Type-Options: nosniff");
    }
    
    /**
     * X-XSS-Protection (Legacy, aber hilft älteren Browsern)
     */
    private static function setXSSProtection() {
        // 1 = XSS Filter aktiviert
        // mode=block = Seite blockieren bei XSS-Versuch
        header("X-XSS-Protection: 1; mode=block");
    }
    
    /**
     * Referrer Policy
     */
    private static function setReferrerPolicy() {
        // strict-origin-when-cross-origin = 
        // - Same-Origin: voller Referrer
        // - Cross-Origin HTTPS→HTTPS: nur Origin
        // - Cross-Origin HTTPS→HTTP: kein Referrer
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }
    
    /**
     * Permissions Policy (Feature-Policy)
     */
    private static function setPermissionsPolicy() {
        $policies = [
            "geolocation=()",
            "microphone=()",
            "camera=(self)",
            "payment=()",
            "usb=()",
            "magnetometer=()",
            "gyroscope=()",
            "accelerometer=()"
        ];
        
        header("Permissions-Policy: " . implode(', ', $policies));
    }
    
    /**
     * Prüft ob Verbindung über HTTPS läuft
     */
    private static function isHTTPS() {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            $_SERVER['SERVER_PORT'] == 443 ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
        );
    }
    
    /**
     * Gibt aktuell gesetzte Security Headers zurück (für Testing)
     */
    public static function getSetHeaders() {
        if (function_exists('headers_list')) {
            $headers = headers_list();
            $securityHeaders = [];
            
            $relevantHeaders = [
                'Content-Security-Policy',
                'Strict-Transport-Security',
                'X-Frame-Options',
                'X-Content-Type-Options',
                'X-XSS-Protection',
                'Referrer-Policy',
                'Permissions-Policy'
            ];
            
            foreach ($headers as $header) {
                foreach ($relevantHeaders as $relevant) {
                    if (stripos($header, $relevant) === 0) {
                        $securityHeaders[] = $header;
                    }
                }
            }
            
            return $securityHeaders;
        }
        
        return [];
    }
}
