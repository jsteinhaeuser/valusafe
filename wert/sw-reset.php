<?php
/**
 * sw-reset.php – Service Worker zurücksetzen
 * Einmalig aufrufen, danach wieder löschen
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SW Reset</title>
    <style>
        body { font-family: sans-serif; padding: 40px; text-align: center; background: #f0fdf4; }
        .box { max-width: 400px; margin: 0 auto; background: white; border-radius: 12px;
               padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,.1); }
        h2 { color: #2e7d32; }
        #status { margin-top: 20px; font-size: 15px; color: #333; line-height: 1.8; }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 28px;
               background: #2e7d32; color: white; border-radius: 8px;
               text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="box">
    <h2>🔄 Service Worker Reset</h2>
    <p>Alle Service Worker werden deregistriert und Caches geleert.</p>
    <div id="status">⏳ Läuft…</div>
    <a href="index.php" class="btn" id="homeBtn" style="display:none">🏠 Zur App</a>
</div>
<script>
async function reset() {
    const log = document.getElementById('status');
    const lines = [];

    // 1. Alle Service Worker deregistrieren
    if ('serviceWorker' in navigator) {
        const regs = await navigator.serviceWorker.getRegistrations();
        for (const reg of regs) {
            await reg.unregister();
            lines.push('✅ SW deregistriert: ' + reg.scope);
        }
        if (regs.length === 0) lines.push('ℹ️ Kein SW aktiv');
    } else {
        lines.push('ℹ️ Service Worker nicht unterstützt');
    }

    // 2. Alle Caches löschen
    if ('caches' in window) {
        const keys = await caches.keys();
        for (const key of keys) {
            await caches.delete(key);
            lines.push('🗑️ Cache gelöscht: ' + key);
        }
        if (keys.length === 0) lines.push('ℹ️ Kein Cache vorhanden');
    }

    lines.push('');
    lines.push('✅ Fertig – bitte zur App navigieren.');
    log.innerHTML = lines.join('<br>');
    document.getElementById('homeBtn').style.display = 'inline-block';
}

reset();
</script>
</body>
</html>
