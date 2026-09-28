<?php
/**
 * offline.php - Offline-Fallback-Seite
 * Wird vom Service Worker angezeigt wenn keine Verbindung besteht
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline – Wertsachen-Inventar</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #1a1a2e;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 40px 32px;
            max-width: 420px;
            width: 100%;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,.1);
        }
        .icon { font-size: 56px; margin-bottom: 20px; }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 10px; color: #2e7d32; }
        p  { font-size: 15px; line-height: 1.6; color: #546e7a; margin-bottom: 24px; }
        .btn {
            display: inline-block;
            background: #2e7d32;
            color: white;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            margin: 6px;
            text-decoration: none;
            transition: background .2s;
        }
        .btn:hover { background: #1b5e20; }
        .btn-secondary {
            background: #f5f5f5;
            color: #333;
        }
        .btn-secondary:hover { background: #e0e0e0; }

        /* Gecachte Items Liste */
        #cached-items {
            margin-top: 28px;
            text-align: left;
            border-top: 1px solid #e0e0e0;
            padding-top: 20px;
            display: none;
        }
        #cached-items h2 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #90a4ae;
            margin-bottom: 12px;
        }
        .cached-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            background: #f9fbe7;
            margin-bottom: 6px;
            font-size: 13px;
            color: #333;
        }
        .cached-item .icon-small { font-size: 18px; }
        .cached-item .name { font-weight: 600; flex: 1; }
        .cached-item .cat  { color: #90a4ae; font-size: 12px; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff3e0;
            color: #e65100;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .dot {
            width: 8px; height: 8px;
            background: #e65100;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .3; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">📦</div>
        <div class="status-badge">
            <span class="dot"></span>
            Keine Verbindung
        </div>
        <h1>Du bist offline</h1>
        <p>Die App kann gerade nicht auf den Server zugreifen. Zuletzt gespeicherte Daten werden unten angezeigt.</p>

        <button class="btn" onclick="window.location.reload()">🔄 Erneut versuchen</button>
        <a class="btn btn-secondary" href="index.php">📋 Zur Übersicht</a>

        <!-- Gecachte Daten aus IndexedDB / localStorage -->
        <div id="cached-items">
            <h2>📋 Zuletzt gesehen</h2>
            <div id="items-list"></div>
        </div>
    </div>

    <script>
    // Versuche zuletzt gesehene Items aus localStorage zu laden
    (function() {
        try {
            const raw = localStorage.getItem('wert_recent_items');
            if (!raw) return;
            const items = JSON.parse(raw);
            if (!items || !items.length) return;

            const container = document.getElementById('cached-items');
            const list      = document.getElementById('items-list');
            container.style.display = 'block';

            items.slice(0, 8).forEach(item => {
                const div = document.createElement('div');
                div.className = 'cached-item';
                div.innerHTML =
                    '<span class="icon-small">📦</span>' +
                    '<span class="name">' + escHtml(item.name) + '</span>' +
                    (item.kategorie ? '<span class="cat">' + escHtml(item.kategorie) + '</span>' : '');
                list.appendChild(div);
            });
        } catch(e) {}
    })();

    function escHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    // Auto-Retry wenn wieder online
    window.addEventListener('online', () => {
        document.querySelector('.status-badge').innerHTML =
            '<span style="width:8px;height:8px;background:#2e7d32;border-radius:50%;display:inline-block;"></span> Verbindung wiederhergestellt';
        setTimeout(() => window.location.reload(), 1000);
    });
    </script>
</body>
</html>
