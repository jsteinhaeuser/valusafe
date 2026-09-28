<?php
/**
 * Registrierung des Service Workers - die EINE Stelle dafuer.
 *
 * Bis 4.3.28 stand sie zweimal, in footer_next.php und footer_next_page.php,
 * und die beiden waren nicht gleich: footer_next.php schloss sie in
 * if (!headers_sent()) ein. Am Seitenende sind die Kopfzeilen aber immer
 * gesendet (ValuSafe puffert nicht), die Registrierung lief dort also nie -
 * auch nicht auf index.php. Sie geschah nur auf Seiten mit
 * footer_next_page.php und wirkte von dort aus auf alle.
 *
 * 'sw.js' RELATIV und ohne scope: Der Service Worker gilt dann fuer das
 * Verzeichnis, in dem er liegt. Mit '/sw.js' und scope '/' setzte das eine
 * Installation im Wurzelverzeichnis der Domain voraus; in einem Unterordner
 * lief die Registrierung ins Leere. Fuer die Seiten im Wurzelverzeichnis
 * einer Installation ergibt beides dieselbe Adresse. Eingebunden wird diese
 * Datei nur von Seiten im Wurzelverzeichnis der Installation - aus backend/
 * heraus wuerde 'sw.js' auf backend/sw.js zeigen.
 */
?>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('sw.js')
            .then(function(reg) {
                reg.addEventListener('updatefound', function() {
                    var worker = reg.installing;
                    worker.addEventListener('statechange', function() {
                        if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                            console.log('[SW] Update verfügbar — beim nächsten Laden aktiv');
                        }
                    });
                });
            })
            .catch(function(err) {
                console.warn('[SW] Registrierung fehlgeschlagen:', err);
            });
    });
}
</script>
