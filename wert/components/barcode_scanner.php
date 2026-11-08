<?php
/**
 * components/barcode_scanner.php — Scanner-Fenster mit Kamera (seit 4.9)
 *
 * Zuerst fuer quick_add.php: auf dem Handy oeffnet der Plus-Knopf die
 * Schnellerfassung, die bis 4.8 kein Barcode-Feld hatte. Erkennung wie in
 * add.php/edit.php: BarcodeDetector (Chrome/Android), sonst ZXing
 * (iPhone/iPad, Safari, Firefox), ohne Kamera die Handeingabe.
 * add.php/edit.php haben noch ihre eigene Kopie; beim naechsten Anfassen
 * auf diese Komponente umstellen.
 *
 * Die Seite stellt bereit:
 *   - einen Knopf mit onclick="vsScannerOeffnen()"
 *   - window.vsBarcodeGefunden(code) - wird mit dem gelesenen Text gerufen
 *
 * Barrierearm: role="dialog" mit Beschriftung, Fokus auf "Abbrechen" und
 * nach dem Schliessen zurueck auf den ausloesenden Knopf, Escape schliesst.
 */
?>
<div id="vsScanner" class="vs-scanner" role="dialog" aria-modal="true" aria-labelledby="vsScannerTitel" hidden>
    <div class="vs-scanner-box">
        <h2 id="vsScannerTitel" class="vs-scanner-titel"><i class="ti ti-barcode" aria-hidden="true"></i> <?php echo htmlspecialchars(t('barcode_scan_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="vs-scanner-bild">
            <video id="vsScannerVideo" autoplay playsinline muted></video>
            <div class="vs-scanner-linie" aria-hidden="true"></div>
        </div>
        <p class="vs-scanner-hinweis"><?php echo htmlspecialchars(t('barcode_in_frame'), ENT_QUOTES, 'UTF-8'); ?></p>
        <button type="button" id="vsScannerAbbrechen" class="vs-scanner-abbrechen" onclick="vsScannerSchliessen()">
            <span aria-hidden="true">✕</span> <?php echo htmlspecialchars(t('btn_cancel'), ENT_QUOTES, 'UTF-8'); ?>
        </button>
    </div>
</div>
<style>
.vs-scanner { position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.85);
              display: flex; align-items: center; justify-content: center; padding: 16px; }
.vs-scanner[hidden] { display: none; }
.vs-scanner-box { background: var(--vs-surface, #fff); color: var(--vs-text, #1f2937); border-radius: 12px;
                  padding: 20px; width: 100%; max-width: 420px; text-align: center; }
.vs-scanner-titel { font-size: 18px; margin: 0 0 12px; }
.vs-scanner-bild { position: relative; border-radius: 8px; overflow: hidden; background: #000; }
.vs-scanner-bild video { width: 100%; display: block; }
.vs-scanner-linie { position: absolute; top: 50%; left: 10%; right: 10%; height: 2px;
                    background: rgba(52,152,219,0.8); pointer-events: none; }
.vs-scanner-hinweis { margin: 10px 0 0; font-size: 14px; color: var(--vs-text-muted, #595959); }
.vs-scanner-abbrechen { margin-top: 12px; padding: 10px 24px; min-height: 44px; border: none; border-radius: 6px;
                        background: var(--vs-danger, #c0392b); color: #fff; font-size: 15px; cursor: pointer; }
.vs-scanner-abbrechen:focus-visible { outline: 3px solid var(--vs-accent, #1a6fb5); outline-offset: 2px; }
</style>
<script src="js/zxing-browser.min.js"></script>
<script>
(function () {
    let stream = null, animFrame = null, zxingReader = null, ausloeser = null;
    const HANDEINGABE = <?php echo json_encode(t('barcode_manual_prompt')); ?>;

    function fenster(offen) {
        const el = document.getElementById('vsScanner');
        el.hidden = !offen;
        if (offen) document.getElementById('vsScannerAbbrechen').focus();
    }

    function gefunden(code) {
        vsScannerSchliessen();
        if (code && typeof window.vsBarcodeGefunden === 'function') window.vsBarcodeGefunden(String(code).trim());
    }

    function handeingabe() {
        const frage = typeof window.vsPrompt === 'function'
            ? window.vsPrompt(HANDEINGABE, '')
            : Promise.resolve(window.prompt(HANDEINGABE));
        frage.then(function (code) { if (code && code.trim()) gefunden(code); });
    }

    async function nativ() {
        fenster(true);
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
            });
            const video = document.getElementById('vsScannerVideo');
            video.srcObject = stream;
            await video.play();
            const detector = new BarcodeDetector({
                formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'qr_code', 'upc_a', 'upc_e']
            });
            const scan = async () => {
                if (!stream) return;
                try {
                    const codes = await detector.detect(video);
                    if (codes.length > 0) { gefunden(codes[0].rawValue); return; }
                } catch (e) {}
                animFrame = requestAnimationFrame(scan);
            };
            animFrame = requestAnimationFrame(scan);
        } catch (e) {
            if (document.getElementById('vsScanner').hidden) return;   // schon abgebrochen
            vsScannerSchliessen();
            handeingabe();
        }
    }

    async function zxing() {
        fenster(true);
        const F = ZXing.BarcodeFormat;
        const hints = new Map();
        hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS,
                  [F.EAN_13, F.EAN_8, F.UPC_A, F.UPC_E, F.CODE_128, F.CODE_39, F.QR_CODE]);
        hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
        const reader = new ZXing.BrowserMultiFormatReader(hints);
        zxingReader = reader;
        try {
            await reader.decodeFromConstraints(
                { video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } } },
                document.getElementById('vsScannerVideo'),
                function (result) { if (result && zxingReader === reader) gefunden(result.getText()); }
            );
            // Abgebrochen, waehrend iOS noch nach der Kamera fragte
            if (zxingReader !== reader) { reader.stopContinuousDecode(); reader.reset(); }
        } catch (e) {
            if (zxingReader !== reader) return;
            vsScannerSchliessen();
            handeingabe();
        }
    }

    window.vsScannerOeffnen = function () {
        ausloeser = document.activeElement;
        const kamera = navigator.mediaDevices && navigator.mediaDevices.getUserMedia;
        if (kamera && 'BarcodeDetector' in window) { nativ(); return; }
        if (kamera && window.ZXing) { zxing(); return; }
        handeingabe();
    };

    window.vsScannerSchliessen = function () {
        if (animFrame)   { cancelAnimationFrame(animFrame); animFrame = null; }
        if (stream)      { stream.getTracks().forEach(t => t.stop()); stream = null; }
        if (zxingReader) { try { zxingReader.stopContinuousDecode(); zxingReader.reset(); } catch (e) {} zxingReader = null; }
        const video = document.getElementById('vsScannerVideo');
        if (video) video.srcObject = null;
        const war = !document.getElementById('vsScanner').hidden;
        fenster(false);
        if (war && ausloeser && typeof ausloeser.focus === 'function') ausloeser.focus();
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !document.getElementById('vsScanner').hidden) vsScannerSchliessen();
    });
})();
</script>
