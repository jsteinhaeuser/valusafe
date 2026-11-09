<?php
/**
 * Untere Navigation fuer schmale Bildschirme (bis 600 px, css/next.css).
 *
 * Eingebunden aus footer_next.php, footer_next_page.php und
 * backend/layout/footer_next_page.php. $vsBnavRoot ist der Weg zur App-Wurzel:
 * '' oder '../'.
 *
 * "Mehr" oeffnet die vorhandene Icon-Leiste als Schublade. Sie steht in den drei
 * Kopfdateien samt Rechtepruefung und wird hier nicht ein viertes Mal aufgebaut.
 * Die Beschriftung in der Schublade kommt per CSS aus dem title der Eintraege.
 */
$vsBnavRoot  = $vsBnavRoot ?? '';
$vsBnavSeite = basename($_SERVER['SCRIPT_NAME'] ?? '');
$vsBnavAdmin = (function_exists('isSuperAdmin') && isSuperAdmin()) || ($_SESSION['role'] ?? '') === 'admin';

$vsBnavPunkte = [
    ['index.php', 'ti-layout-cards', tn('page_overview', 'Übersicht'), $vsBnavRoot === '' && $vsBnavSeite === 'index.php'],
];
if ($vsBnavAdmin) {
    $vsBnavPunkte[] = ['backend/stats.php', 'ti-chart-pie', tn('nav_stats', 'Statistiken'), $vsBnavSeite === 'stats.php'];
}
$vsBnavPunkte[] = ['insurance.php', 'ti-shield', tn('nav_insurance', 'Versicherungen'), $vsBnavSeite === 'insurance.php'];
$vsBnavPunkte[] = ['settings.php', 'ti-settings', tn('nav_settings', 'Einstellungen'), $vsBnavSeite === 'settings.php'];
?>
<nav class="vs-bottom-nav" aria-label="<?php echo htmlspecialchars(tn('nav_aria_mobile', 'Mobile Navigation'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php foreach ($vsBnavPunkte as [$vsBnavZiel, $vsBnavIcon, $vsBnavText, $vsBnavAktiv]): ?>
    <a class="vs-bnav-btn<?php echo $vsBnavAktiv ? ' vs-bnav-on' : ''; ?>" href="<?php echo htmlspecialchars($vsBnavRoot . $vsBnavZiel, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $vsBnavAktiv ? ' aria-current="page"' : ''; ?>>
        <i class="ti <?php echo $vsBnavIcon; ?>" aria-hidden="true"></i>
        <span class="vs-bnav-text"><?php echo htmlspecialchars($vsBnavText, ENT_QUOTES, 'UTF-8'); ?></span>
    </a>
    <?php endforeach; ?>
    <button type="button" class="vs-bnav-btn" id="vsBnavMehr" aria-expanded="false">
        <i class="ti ti-dots" aria-hidden="true"></i>
        <span class="vs-bnav-text"><?php echo htmlspecialchars(tn('nav_more', 'Mehr'), ENT_QUOTES, 'UTF-8'); ?></span>
    </button>
</nav>
<div class="vs-rail-schirm" id="vsRailSchirm"></div>
<button type="button" class="vs-rail-zu" id="vsRailZu" aria-label="<?php echo htmlspecialchars(tn('btn_close', 'Schließen'), ENT_QUOTES, 'UTF-8'); ?>">
    <i class="ti ti-x" aria-hidden="true"></i>
</button>
<script>
(function () {
    var rail = document.querySelector('.vs-rail');
    var mehr = document.getElementById('vsBnavMehr');
    var zu = document.getElementById('vsRailZu');
    var schirm = document.getElementById('vsRailSchirm');
    if (!rail || !mehr || !zu || !schirm) { return; }

    rail.insertBefore(zu, rail.firstChild);
    if (!rail.id) { rail.id = 'vsRail'; }
    mehr.setAttribute('aria-controls', rail.id);

    var offen = false;
    var gesperrt = [];

    function oeffnen() {
        // Alles ausserhalb der Schublade ist solange nicht erreichbar, auch nicht
        // mit VoiceOver oder der Tabulatortaste.
        gesperrt = [];
        Array.prototype.forEach.call(document.body.children, function (el) {
            if (el !== rail && el !== schirm && !el.inert) {
                el.inert = true;
                gesperrt.push(el);
            }
        });
        rail.classList.add('vs-rail-offen');
        schirm.classList.add('vs-rail-offen');
        mehr.setAttribute('aria-expanded', 'true');
        offen = true;
        zu.focus();
    }

    function schliessen(fokusZurueck) {
        if (!offen) { return; }
        gesperrt.forEach(function (el) { el.inert = false; });
        gesperrt = [];
        rail.classList.remove('vs-rail-offen');
        schirm.classList.remove('vs-rail-offen');
        mehr.setAttribute('aria-expanded', 'false');
        offen = false;
        if (fokusZurueck) { mehr.focus(); }
    }

    mehr.addEventListener('click', oeffnen);
    zu.addEventListener('click', function () { schliessen(true); });
    schirm.addEventListener('click', function () { schliessen(true); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { schliessen(true); }
    });
    // Ein Eintrag wurde gewaehlt, etwa "Hilfe": dessen Dialog bekommt den Fokus.
    rail.addEventListener('click', function (e) {
        if (e.target.closest('a')) { schliessen(false); }
    });
    // Beim Drehen ueber 600 px ist die Leiste wieder die normale Spalte.
    var schmal = window.matchMedia('(max-width: 600px)');
    var gedreht = function () { if (!schmal.matches) { schliessen(false); } };
    if (schmal.addEventListener) { schmal.addEventListener('change', gedreht); }
    else if (schmal.addListener) { schmal.addListener(gedreht); }
})();
</script>
