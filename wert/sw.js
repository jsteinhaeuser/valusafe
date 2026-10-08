/*
 * ValuSafe Service Worker
 *
 * Strategie:
 *   Cache First  — statische Assets (CSS, JS, Icons, Fonts)
 *   Network Only — alle PHP-Seiten, Backend, Login, AJAX, API
 *
 * Cache-Invalidierung: CACHE_VERSION bei jedem Deployment hochzählen.
 */

// Bei jedem Release erhoehen. Stand 4.3.5 stand hier noch v4.1: CSS und JS
// laufen ueber Cache First, bestehende Nutzer bekamen deshalb seit Monaten die
// Dateien von v4.1 — auch das neue js/multi_image_upload.js haette sie nie
// erreicht.
const CACHE_VERSION = 'valusafe-v4.3.33';

// RELATIVE Pfade: sie gelten ab dem Ort dieser Datei. Bis 4.3.28 standen hier
// "/css/..." usw. - das setzte eine Installation im Wurzelverzeichnis der
// Domain voraus. Im Wurzelverzeichnis ergibt beides dieselbe Adresse.
const STATIC_ASSETS = [
    'css/cloud.css',
    'css/dark.css',
    'css/sand.css',
    'css/navy.css',
    'css/forest.css',
    'css/emerald.css',
    'css/lavender.css',
    'css/midnight.css',
    'css/mint.css',
    'css/ocean.css',
    'css/cherry.css',
    'css/sunset.css',
    'css/glass.css',
    'css/next.css',
    'css/tokens.css',
    'css/backend.css',
    'css/tabler-icons.min.css',
    'js/context_help.js',
    'icon-192.png',
    'icon-512.png',
    'apple-touch-icon.png',
    'manifest.json',
];

// ── Install ──────────────────────────────────────────────────
//
// WARUM HIER 'reload' STEHT (gefunden am 07.09.2026)
//   cache.addAll() holt jede Datei mit einem gewoehnlichen fetch, und der
//   darf aus dem HTTP-Cache des Browsers bedient werden. Die .htaccess gibt
//   CSS und JS ein "access plus 1 month" mit - der Service Worker kopierte
//   also eine bis zu einen Monat alte Fassung in seinen frischen Cache und
//   meldete Erfolg. Das Hochzaehlen von CACHE_VERSION wirkte dadurch auf der
//   falschen Ebene: es legte einen neuen Cache an und fuellte ihn mit dem
//   alten Inhalt.
//
//   Aufgefallen an css/next.css auf einer Testinstanz: der Browser bekam 23.642 Bytes
//   aus dem Service Worker, der Server lieferte 26.875. Die Formatierung der
//   Dialogfenster fehlte dadurch seit 4.3.11 - vier Wochen und sechs
//   Releases lang, ohne dass ein Versionssprung etwas daran geaendert haette.
//
//   cache: 'reload' erzwingt den Netzweg und umgeht den HTTP-Cache.
//
// Zusaetzlich einzeln statt per addAll(): addAll bricht beim ersten Fehler
// die gesamte Installation ab. Eine einzelne nicht erreichbare Datei sollte
// nicht dazu fuehren, dass der Service Worker gar nicht erst aktiv wird.
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then(async cache => {
                const ergebnisse = await Promise.allSettled(
                    STATIC_ASSETS
                        .filter(url => !url.endsWith('.php'))
                        .map(async pfad => {
                            const antwort = await fetch(new Request(pfad, { cache: 'reload' }));
                            if (!antwort.ok) {
                                throw new Error(pfad + ' -> HTTP ' + antwort.status);
                            }
                            return cache.put(pfad, antwort);
                        })
                );
                const fehler = ergebnisse.filter(e => e.status === 'rejected');
                if (fehler.length) {
                    console.warn('[SW] ' + fehler.length + ' Datei(en) nicht vorgeladen:',
                        fehler.map(e => String(e.reason)));
                }
            })
            .then(() => self.skipWaiting())
    );
});

// ── Activate: alte Caches löschen ───────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys
                    .filter(key => key !== CACHE_VERSION)
                    .map(key => {
                        console.log('[SW] Alter Cache gelöscht:', key);
                        return caches.delete(key);
                    })
            ))
            .then(() => self.clients.claim())
    );
});

// ── Fetch ────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Nur eigene Origin behandeln
    if (url.origin !== self.location.origin) return;

    // Nur GET behandeln
    if (event.request.method !== 'GET') return;

    // Pfad RELATIV zum Ort dieses Service Workers, mit fuehrendem '/'.
    // Die Pruefungen unten ('/upload/', '/backend/' ...) gingen bis 4.3.28
    // gegen den vollen Pfad. In einem Unterordner (/valusafe/upload/foto.jpg)
    // griff dadurch keine Ausnahme, und Fotos und Belege der Nutzer waeren
    // als "statische Datei" im Cache gelandet. Ausserhalb des eigenen
    // Verzeichnisses fasst der Service Worker nichts an.
    const basis = new URL('./', self.location).pathname;
    if (!url.pathname.startsWith(basis)) return;
    const path = '/' + url.pathname.slice(basis.length);

    // PHP-Dateien und dynamische Routen: SW komplett umgehen
    // (kein event.respondWith → Browser handhabt den Request normal)
    if (
        path.endsWith('.php') ||
        path === '/' ||
        path === '' ||
        path.startsWith('/backend/') ||
        path.startsWith('/ajax/') ||
        path.startsWith('/backups/') ||
        path.startsWith('/upload/') ||
        path.startsWith('/documents/')
    ) {
        return; // Browser übernimmt — kein SW-Eingriff
    }

    // Statische Assets: Cache First
    if (isStaticAsset(path)) {
        event.respondWith(cacheFirst(event.request));
    }

    // Alles andere: Browser übernimmt (kein return mit respondWith)
});

// ── Cache First Strategie ────────────────────────────────────
async function cacheFirst(request) {
    try {
        // Mit cacheName: caches.match() ohne Namen durchsucht ALLE Caches,
        // geschrieben wird aber nur in den aktuellen. Ueberlebt ein alter
        // Cache das Aufraeumen beim activate, gewinnt sonst seine Kopie auf
        // Dauer - und das Hochzaehlen von CACHE_VERSION bliebe wirkungslos.
        const cached = await caches.match(request, { cacheName: CACHE_VERSION });
        if (cached) return cached;

        // Auch hier den HTTP-Cache umgehen, aus demselben Grund wie beim
        // Install: sonst landet eine veraltete Kopie im frischen Cache.
        const response = await fetch(new Request(request.url, { cache: 'reload' }));
        if (response.ok) {
            const cache = await caches.open(CACHE_VERSION);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        return new Response('Asset nicht verfügbar', { status: 503 });
    }
}

function isStaticAsset(path) {
    return (
        path.startsWith('/css/') ||
        path.startsWith('/js/') ||
        path.endsWith('.css') ||
        path.endsWith('.js') ||
        path.endsWith('.png') ||
        path.endsWith('.jpg') ||
        path.endsWith('.webp') ||
        path.endsWith('.svg') ||
        path.endsWith('.woff') ||
        path.endsWith('.woff2') ||
        path.endsWith('.ico') ||
        path === '/manifest.json'
    );
}
