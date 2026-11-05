<?php
/**
 * QR-Etiketten auf A4-Boegen (4.8). Rechnet aus, welches Etikett auf welcher
 * Seite an welcher Stelle steht; qr_generator.php zeichnet es.
 *
 * Alle Formate sind randlos in der Breite (3 x 70 = 2 x 105 = 210 mm) und
 * senkrecht mittig - so liegen die gaengigen Boegen der Hersteller. Die
 * Beispiel-Nummern sind nur Hinweise; massgeblich sind die Masse.
 */

if (!function_exists('vsEtikettFormate')) {
    /** Formate: Breite/Hoehe je Etikett in mm, Spalten, Zeilen, Beispiel. */
    function vsEtikettFormate(): array {
        return [
            '70x37'  => ['breite' => 70,  'hoehe' => 37,   'spalten' => 3, 'zeilen' => 8, 'beispiel' => 'Zweckform 3474'],
            '70x42'  => ['breite' => 70,  'hoehe' => 42.3, 'spalten' => 3, 'zeilen' => 7, 'beispiel' => 'Zweckform 3652'],
            '105x48' => ['breite' => 105, 'hoehe' => 48,   'spalten' => 2, 'zeilen' => 6, 'beispiel' => 'Zweckform 3424'],
        ];
    }
}

if (!function_exists('vsEtikettFormat')) {
    /** Format zu einem Schluessel oder null (dann Bildschirm-Raster). */
    function vsEtikettFormat(?string $schluessel): ?array {
        $formate = vsEtikettFormate();
        return ($schluessel !== null && isset($formate[$schluessel])) ? $formate[$schluessel] : null;
    }
}

if (!function_exists('vsEtikettSeiten')) {
    /**
     * Verteilt Etiketten auf Seiten. $start (1-basiert) ueberspringt auf der
     * ersten Seite bereits verbrauchte Etiketten eines angebrochenen Bogens.
     * Rueckgabe: Liste von Seiten; jede Seite hat genau spalten*zeilen
     * Felder, leere Felder sind null.
     */
    function vsEtikettSeiten(array $etiketten, array $format, int $start = 1): array {
        $proSeite = $format['spalten'] * $format['zeilen'];
        $start = max(1, min($start, $proSeite));
        $felder = array_merge(array_fill(0, $start - 1, null), array_values($etiketten));
        if (!$etiketten) {
            return [];
        }
        $seiten = array_chunk($felder, $proSeite);
        $letzte = count($seiten) - 1;
        $seiten[$letzte] = array_pad($seiten[$letzte], $proSeite, null);
        return $seiten;
    }
}

if (!function_exists('vsEtikettPosition')) {
    /** Linke obere Ecke von Feld $i (0-basiert) einer Seite, in mm. */
    function vsEtikettPosition(int $i, array $format): array {
        $oben = (297 - $format['zeilen'] * $format['hoehe']) / 2;
        $links = (210 - $format['spalten'] * $format['breite']) / 2;
        return [
            'links' => round($links + ($i % $format['spalten']) * $format['breite'], 2),
            'oben'  => round($oben + intdiv($i, $format['spalten']) * $format['hoehe'], 2),
        ];
    }
}
