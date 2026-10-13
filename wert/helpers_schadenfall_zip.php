<?php
/**
 * helpers_schadenfall_zip.php - Fotos und Belege eines Schadensfalls als ZIP (4.4.1)
 *
 * Reine Hilfsfunktionen ohne Datenbank, damit sie sich testen lassen
 * (tests/SchadenfallZipTest.php). Die Seite schadenfall_belege.php holt die
 * Daten, prueft die Rechte und packt die Datei.
 *
 * Aufbau der ZIP:
 *   Uebersicht.txt / Uebersicht.csv
 *   01_<Gegenstand>/Fotos/1.jpg, 2.jpg ...      (Hauptbild zuerst)
 *   01_<Gegenstand>/Belege/<Originalname>       (nur mit Belegrecht)
 */

if (!function_exists('vsZipTeil')) {
    /**
     * Ein Pfadteil fuer die ZIP: keine Schraegstriche, Steuerzeichen oder
     * Zeichen, die Windows im Dateinamen ablehnt; Umlaute bleiben (die ZIP
     * wird mit UTF-8-Namen geschrieben). Leer -> $ersatz.
     */
    function vsZipTeil(string $s, int $max = 60, string $ersatz = 'datei'): string {
        // erst Leerraum (Tab, Zeilenumbruch) zu Leerzeichen, dann die uebrigen
        // Steuerzeichen weg - sonst klebten Woerter mit Tab dazwischen zusammen
        $s = preg_replace('/\s+/u', ' ', $s) ?? '';
        $s = preg_replace('/[\x00-\x1F\x7F]/u', '', $s) ?? '';
        $s = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $s);
        $s = trim($s, " .\t");                 // Windows: kein Punkt/Leerzeichen am Ende
        if ($s === '' || $s === '..') {
            return $ersatz;
        }
        if (mb_strlen($s, 'UTF-8') > $max) {
            // Endung erhalten, wenn es eine gibt
            $punkt = mb_strrpos($s, '.', 0, 'UTF-8');
            $endung = ($punkt !== false && mb_strlen($s, 'UTF-8') - $punkt <= 8) ? mb_substr($s, $punkt, null, 'UTF-8') : '';
            $s = rtrim(mb_substr($s, 0, $max - mb_strlen($endung, 'UTF-8'), 'UTF-8'), ' .') . $endung;
        }
        return $s;
    }
}

if (!function_exists('vsZipEindeutig')) {
    /**
     * Haengt bei doppelten Namen " (2)", " (3)" ... vor der Endung an.
     * $belegt sammelt die schon vergebenen Namen (ohne Gross-/Kleinschreibung,
     * wie Windows und macOS sie vergleichen).
     */
    function vsZipEindeutig(string $name, array &$belegt): string {
        $kandidat = $name;
        $punkt = strrpos($name, '.');
        $stamm  = $punkt !== false && $punkt > 0 ? substr($name, 0, $punkt) : $name;
        $endung = $punkt !== false && $punkt > 0 ? substr($name, $punkt) : '';
        $n = 2;
        while (isset($belegt[mb_strtolower($kandidat, 'UTF-8')])) {
            $kandidat = $stamm . ' (' . $n++ . ')' . $endung;
        }
        $belegt[mb_strtolower($kandidat, 'UTF-8')] = true;
        return $kandidat;
    }
}

if (!function_exists('vsSchadenfallZipInhalt')) {
    /**
     * Welche Datei kommt wohin.
     *
     * @param array    $gegenstaende Zeilen mit id, name (Reihenfolge = Nummerierung)
     * @param callable $fotosVon     fn(array $g): string[]  absolute Pfade, Hauptbild zuerst
     * @param callable $belegeVon    fn(array $g): array     [['pfad'=>..., 'name'=>Originalname], ...]
     * @param bool     $mitBelegen   false: Belege bleiben draussen (kein Recht)
     * @param string   $ordnerFotos  uebersetzter Ordnername
     * @param string   $ordnerBelege uebersetzter Ordnername
     * @return array ['dateien' => [zipPfad => quellPfad], 'je' => [id => ['fotos'=>n,'belege'=>n]], 'bytes' => int]
     *
     * Nicht vorhandene Quelldateien werden uebersprungen (und nicht gezaehlt).
     */
    function vsSchadenfallZipInhalt(array $gegenstaende, callable $fotosVon, callable $belegeVon,
                                    bool $mitBelegen, string $ordnerFotos, string $ordnerBelege): array {
        $dateien = [];
        $je = [];
        $bytes = 0;
        $ordnerBelegt = [];
        $breite = max(2, strlen((string)count($gegenstaende)));
        $ordnerFotos  = vsZipTeil($ordnerFotos, 30, 'Fotos');
        $ordnerBelege = vsZipTeil($ordnerBelege, 30, 'Belege');

        foreach (array_values($gegenstaende) as $i => $g) {
            $nr = str_pad((string)($i + 1), $breite, '0', STR_PAD_LEFT);
            $ordner = vsZipEindeutig($nr . '_' . vsZipTeil((string)($g['name'] ?? ''), 50, 'Gegenstand'), $ordnerBelegt);
            $je[$g['id']] = ['fotos' => 0, 'belege' => 0, 'ordner' => $ordner];

            $n = 0;
            foreach ($fotosVon($g) as $pfad) {
                if (!is_file($pfad)) continue;
                $endung = strtolower(pathinfo($pfad, PATHINFO_EXTENSION)) ?: 'jpg';
                $dateien[$ordner . '/' . $ordnerFotos . '/' . (++$n) . '.' . $endung] = $pfad;
                $bytes += (int)filesize($pfad);
            }
            $je[$g['id']]['fotos'] = $n;

            if ($mitBelegen) {
                $namen = [];
                foreach ($belegeVon($g) as $b) {
                    if (empty($b['pfad']) || !is_file($b['pfad'])) continue;
                    $name = vsZipEindeutig(vsZipTeil((string)($b['name'] ?? ''), 80, 'beleg'), $namen);
                    $dateien[$ordner . '/' . $ordnerBelege . '/' . $name] = $b['pfad'];
                    $bytes += (int)filesize($b['pfad']);
                    $je[$g['id']]['belege']++;
                }
            }
        }
        return ['dateien' => $dateien, 'je' => $je, 'bytes' => $bytes];
    }
}

if (!function_exists('vsSchadenfallCsv')) {
    /**
     * Uebersicht als CSV fuer Tabellenprogramme: Semikolon, UTF-8 mit BOM
     * (sonst zeigt Excel die Umlaute falsch), Zeilenende CRLF. Werte mit
     * fuehrendem = + - @ bekommen ein Hochkomma (keine Formeln aus Namen).
     *
     * @param string[] $kopf  Spaltenueberschriften
     * @param array[]  $zeilen Werte je Zeile (gleiche Reihenfolge)
     */
    function vsSchadenfallCsv(array $kopf, array $zeilen): string {
        $feld = function ($v): string {
            $v = (string)$v;
            if ($v !== '' && strpbrk($v[0], '=+-@') !== false && !is_numeric($v)) {
                $v = "'" . $v;
            }
            if (strpbrk($v, ";\"\r\n") !== false) {
                $v = '"' . str_replace('"', '""', $v) . '"';
            }
            return $v;
        };
        $out = "\xEF\xBB\xBF" . implode(';', array_map($feld, $kopf)) . "\r\n";
        foreach ($zeilen as $z) {
            $out .= implode(';', array_map($feld, $z)) . "\r\n";
        }
        return $out;
    }
}
