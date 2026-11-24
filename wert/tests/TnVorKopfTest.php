<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * tn() wird in header_next*.php / footer_next*.php / settings.php definiert,
 * nicht in helpers.php. Eine Seite, die tn() aufruft, bevor sie den Kopf
 * einbindet, endet mit "Call to undefined function tn()" (HTTP 500).
 * Fund 06.11.2026: qr_generator.php in 4.8 (Etiketten vor dem Kopf, und die
 * Druckansicht bindet gar keinen Kopf ein). Vor dem Kopf: vsUebersetzt().
 */
class TnVorKopfTest extends TestCase
{
    public function testKeineSeiteRuftTnVorDemKopfAuf(): void
    {
        $wurzel = dirname(__DIR__);
        $dateien = array_merge(glob($wurzel . '/*.php'), glob($wurzel . '/backend/*.php'));
        $funde = [];
        foreach ($dateien as $datei) {
            $code = file_get_contents($datei);
            if (preg_match('/function\s+tn\s*\(/', $code)) {
                continue; // definiert tn() selbst
            }
            // Gleich lange Fassungen (Positionen bleiben vergleichbar):
            // $code ohne Kommentare fuer die Suche nach dem include,
            // $nurCode zusaetzlich ohne Zeichenketten/HTML fuer echte tn()-Aufrufe.
            $leer = static fn(string $s): string => preg_replace('/[^\n]/', ' ', $s);
            $ohneKommentar = $nurCode = '';
            foreach (token_get_all($code) as $t) {
                $text = is_array($t) ? $t[1] : $t;
                $art  = is_array($t) ? $t[0] : null;
                $kommentar = in_array($art, [T_COMMENT, T_DOC_COMMENT], true);
                $text_ = in_array($art, [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_INLINE_HTML], true);
                $ohneKommentar .= $kommentar ? $leer($text) : $text;
                $nurCode       .= ($kommentar || $text_) ? $leer($text) : $text;
            }
            if (!preg_match('/(?<![\w>$:])tn\s*\(/', $nurCode, $m, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            $ersterAufruf = $m[0][1];
            $kopf = preg_match('/(include|require)(_once)?\s*\(?\s*[^;]*(header_next|footer_next)[\w]*\.php/', $ohneKommentar, $k, PREG_OFFSET_CAPTURE)
                ? $k[0][1] : PHP_INT_MAX;
            if ($ersterAufruf < $kopf) {
                $funde[] = substr($datei, strlen($wurzel) + 1) . ':' . (substr_count(substr($nurCode, 0, $ersterAufruf), "\n") + 1);
            }
        }
        $this->assertSame([], $funde, 'tn() vor dem Einbinden des Seitenkopfs');
    }
}
