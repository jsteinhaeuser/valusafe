<?php
/**
 * service_helpers.php — Menue "Hilfe & Information" (Ordner service/)
 * Seit ValuSafe 4.9 / Valu-Basic 2.7, in beiden Programmen gleich.
 *
 * Bis 4.8 / 2.6 stand der Block dreimal (footer_next.php, footer_next_page.php,
 * backend/layout/footer_next_page.php), und die Beschriftung kam aus dem
 * Dateinamen ("Erste Schritte", "Rueckmeldung") - in jeder Sprache deutsch.
 *
 *   vsServiceDateien()  Liste fuer das Menue: Beschriftung ueber t() fuer die
 *                       bekannten Dateien, andere weiter aus dem Namen.
 *                       Sprachfassungen (name_en.md) erscheinen nicht einzeln.
 *   vsServiceMdDatei()  welche .md service_doc.php ausliefert: eigene Sprache,
 *                       sonst Englisch, sonst die deutsche Grunddatei.
 */

/** Aktive Oberflaechensprache (Sitzung), nur aus der Liste des Programms. */
if (!function_exists('vsServiceSprache')) {
    function vsServiceSprache(): string {
        $liste = defined('VS_SPRACHEN') ? VS_SPRACHEN : (defined('VB_SPRACHEN') ? VB_SPRACHEN : ['de', 'en']);
        $lang  = $_SESSION['lang'] ?? 'de';
        return in_array($lang, $liste, true) ? $lang : 'de';
    }
}

/** Sprachfassung einer .md? "erste_schritte_en.md" -> true */
if (!function_exists('vsServiceIstSprachfassung')) {
    function vsServiceIstSprachfassung(string $dateiname): bool {
        return (bool)preg_match('/_(en|fr|es|it|nl|pl|pt|tr|ga)\.md$/i', $dateiname);
    }
}

if (!function_exists('vsServiceDateien')) {
    function vsServiceDateien(string $serviceDir): array {
        if (!is_dir($serviceDir)) return [];
        $lang = vsServiceSprache();

        // Dateiname ohne Endung, klein -> Sprachschluessel
        $schluessel = [
            'erste_schritte'                              => 'svc_getting_started',
            'rueckmeldung'                                => 'svc_feedback',
            'about'                                       => 'svc_about',
            'lizenz'                                      => 'svc_license',
            'faq'                                         => 'svc_faq',
            'changelog_public'                            => 'svc_changelog',
            'vergleich'                                   => 'svc_comparison',
            'benutzerhandbuch'                            => 'svc_manual',
            'benutzerhandbuch_valu-basic'                 => 'svc_manual',
            'benutzerhandbuch_erweitert'                  => 'svc_manual_advanced',
            'facts_features'                              => 'svc_facts_features',
            'installationsanleitung_usb-stick_valu-basic' => 'svc_install_guide',
        ];

        $icon = function (string $n, string $e): string {
            if ($e === 'pdf') return '📕';
            $n = strtolower($n);
            if (strpos($n, 'erste') !== false || strpos($n, 'start') !== false) return '🚀';
            if (strpos($n, 'changelog') !== false) return '🦎';
            if ($e === 'php') return '🔄';
            if (strpos($n, 'about') !== false) return 'ℹ️';
            if (strpos($n, 'faq') !== false) return '❓';
            if (strpos($n, 'fact') !== false || strpos($n, 'feature') !== false) return '⭐';
            if (strpos($n, 'manual') !== false || strpos($n, 'handbuch') !== false) return '📖';
            return '📄';
        };

        $alle = array_merge(
            glob($serviceDir . '*.php') ?: [],
            glob($serviceDir . '*.pdf') ?: [],
            glob($serviceDir . '*.md')  ?: []
        );
        // about immer zuletzt, sonst nach Dateiname (Reihenfolge wie bis 4.8)
        usort($alle, function ($a, $b) {
            $aA = strpos(strtolower(basename($a)), 'about') !== false;
            $bA = strpos(strtolower(basename($b)), 'about') !== false;
            if ($aA && !$bA) return 1;
            if (!$aA && $bA) return -1;
            return strcmp(strtolower(basename($a)), strtolower(basename($b)));
        });

        $dateien = [];
        foreach ($alle as $pfad) {
            $fn  = basename($pfad);
            $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
            if ($ext === 'md' && vsServiceIstSprachfassung($fn)) continue;

            $name  = strtolower(pathinfo($fn, PATHINFO_FILENAME));
            $key   = $schluessel[$name] ?? null;
            $label = ($key && function_exists('t')) ? t($key) : null;
            if (!$label || $label === $key) {
                $label = ucwords(str_replace(['-', '_'], ' ', pathinfo($fn, PATHINFO_FILENAME)));
            }
            // Die PDFs gibt es nur auf Deutsch - das soll man vor dem Oeffnen sehen
            if ($ext === 'pdf' && $lang !== 'de' && function_exists('t')) {
                $label .= ' (' . t('svc_in_german') . ')';
            }
            $dateien[] = ['file' => $fn, 'label' => $label, 'icon' => $icon($fn, $ext), 'type' => $ext];
        }
        return $dateien;
    }
}

/**
 * Pfad der auszuliefernden .md: name_<lang>.md, fuer andere Sprachen als
 * Deutsch dann name_en.md, zuletzt die (deutsche) Grunddatei.
 */
if (!function_exists('vsServiceMdDatei')) {
    function vsServiceMdDatei(string $grundPfad): string {
        $lang = vsServiceSprache();
        if ($lang === 'de') return $grundPfad;
        $basis = substr($grundPfad, 0, -3);   // ohne ".md"
        foreach (array_unique([$lang, 'en']) as $l) {
            if (is_file($basis . '_' . $l . '.md')) return $basis . '_' . $l . '.md';
        }
        return $grundPfad;
    }
}
