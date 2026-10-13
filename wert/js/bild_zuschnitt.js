/**
 * bild_zuschnitt.js - Bild zuschneiden und drehen vor dem Speichern (4.4.1)
 *
 * Ein Dialog fuer add.php, quick_add.php und edit.php. Baut auf Cropper.js
 * 1.6.2 auf (js/cropper.min.js, css/cropper.min.css - MIT, siehe
 * THIRD-PARTY-NOTICES.md) und ergaenzt, was Cropper nicht mitbringt:
 * Dialog mit Fokusfalle, beschriftete Knoepfe und Tastaturbedienung.
 *
 * AUFRUF
 *   vsBildZuschnitt.oeffnen(quelle).then(function (blob) { ... });
 *   quelle: File (frisch ausgewaehlt) oder URL eines gespeicherten Bildes
 *           (gleicher Ursprung, z. B. "upload/abc.jpg").
 *   blob:   JPEG-Blob mit dem Ergebnis, oder null bei Abbrechen.
 *
 *   vsBildZuschnitt.moeglich(file) - false fuer GIF (Animation ginge
 *   verloren) und fuer HEIC/HEIF ausserhalb von Safari (kein Browser-Bild).
 *
 * TASTATUR (im Dialog)
 *   Pfeiltasten  Rahmen verschieben (mit Umschalt in kleinen Schritten)
 *   + / -        zoomen
 *   R            nach rechts drehen, Umschalt+R nach links
 *   Enter        uebernehmen (ausser auf einem Knopf - der loest sich selbst aus)
 *   Esc          abbrechen
 *
 * Bilder werden als data:-URL geladen, nicht als blob: - die CSP erlaubt
 * img-src nur 'self' und data: (SecurityHeaders.php).
 * checkOrientation ist aus: alle aktuellen Browser drehen Fotos selbst nach
 * den EXIF-Daten; mit Cropper zusammen wurden Hochkantfotos sonst doppelt
 * gedreht.
 */
(function () {
    'use strict';
    if (window.vsBildZuschnitt) { return; }

    function T(key, rueckfall) {
        var args = Array.prototype.slice.call(arguments);
        return typeof window.vsT === 'function' ? window.vsT.apply(null, args) : rueckfall;
    }

    var VERHAELTNISSE = [
        { wert: NaN,   text: function () { return T('js_crop_ratio_free', 'Frei'); } },
        { wert: 1,     text: function () { return '1:1'; } },
        { wert: 4 / 3, text: function () { return '4:3'; } }
    ];

    var dialog = null;       // Hintergrund-Element
    var kasten, bild, status, knoepfeVerh;
    var cropper = null;
    var loesen = null;
    var fokusVorher = null;
    var drehung = 0;

    function el(tag, attrs, kinder) {
        var e = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function (k) {
            if (k === 'text') { e.textContent = attrs[k]; }
            else if (k === 'html') { e.innerHTML = attrs[k]; }
            else { e.setAttribute(k, attrs[k]); }
        });
        (kinder || []).forEach(function (c) { e.appendChild(c); });
        return e;
    }

    function knopf(klasse, symbol, text, aktion, nurSymbol) {
        var b = el('button', { type: 'button', 'class': 'vs-crop-btn ' + klasse });
        if (symbol) { b.appendChild(el('i', { 'class': 'ti ' + symbol, 'aria-hidden': 'true' })); }
        if (nurSymbol) {
            b.setAttribute('aria-label', text);
            b.setAttribute('title', text);
        } else {
            b.appendChild(document.createTextNode(' ' + text));
        }
        b.addEventListener('click', aktion);
        return b;
    }

    function aufbauen() {
        var titel = el('h2', { 'class': 'vs-crop-title', id: 'vsCropTitel', text: T('js_crop_title', 'Bild zuschneiden und drehen') });
        var hilfe = el('p', { 'class': 'vs-crop-help', id: 'vsCropHilfe', text: T('js_crop_help', 'Rahmen mit der Maus oder den Pfeiltasten verschieben, + und − zum Zoomen, R zum Drehen.') });
        bild = el('img', { 'class': 'vs-crop-img', alt: T('js_crop_image', 'Bild zum Zuschneiden') });
        var buehne = el('div', { 'class': 'vs-crop-stage', tabindex: '0', 'aria-describedby': 'vsCropHilfe' }, [bild]);

        var werkzeuge = el('div', { 'class': 'vs-crop-tools' });
        werkzeuge.appendChild(knopf('', 'ti-rotate-2', T('js_crop_rotate_left', 'Nach links drehen'), function () { drehen(-90); }, true));
        werkzeuge.appendChild(knopf('', 'ti-rotate-clockwise-2', T('js_crop_rotate_right', 'Nach rechts drehen'), function () { drehen(90); }, true));

        var gruppe = el('div', { 'class': 'vs-crop-ratios', role: 'group', 'aria-label': T('js_crop_ratio', 'Seitenverhältnis') });
        knoepfeVerh = VERHAELTNISSE.map(function (v, i) {
            var b = el('button', { type: 'button', 'class': 'vs-crop-btn vs-crop-ratio', 'aria-pressed': i === 0 ? 'true' : 'false', text: v.text() });
            b.addEventListener('click', function () { verhaeltnis(i); });
            gruppe.appendChild(b);
            return b;
        });
        werkzeuge.appendChild(gruppe);
        werkzeuge.appendChild(knopf('', 'ti-arrow-back-up', T('js_crop_reset', 'Zurücksetzen'), zuruecksetzen, false));

        status = el('p', { 'class': 'vs-crop-status', role: 'status', 'aria-live': 'polite' });

        var aktionen = el('div', { 'class': 'vs-crop-actions' }, [
            knopf('vs-crop-btn-secondary', '', T('js_crop_cancel', 'Abbrechen'), function () { schliessen(null); }, false),
            knopf('vs-crop-btn-primary', 'ti-check', T('js_crop_apply', 'Übernehmen'), uebernehmen, false)
        ]);

        kasten = el('div', { 'class': 'vs-crop-dialog', role: 'dialog', 'aria-modal': 'true', 'aria-labelledby': 'vsCropTitel' },
            [titel, hilfe, buehne, werkzeuge, status, aktionen]);
        dialog = el('div', { 'class': 'vs-crop-backdrop', hidden: '' }, [kasten]);
        document.body.appendChild(dialog);
        kasten.addEventListener('keydown', tasten);
    }

    function verhaeltnis(i) {
        knoepfeVerh.forEach(function (b, j) { b.setAttribute('aria-pressed', i === j ? 'true' : 'false'); });
        if (cropper) { cropper.setAspectRatio(VERHAELTNISSE[i].wert); }
    }

    function drehen(grad) {
        if (!cropper) { return; }
        cropper.rotate(grad);
        drehung = ((drehung + grad) % 360 + 360) % 360;
        // Cropper passt die Bildflaeche beim Drehen nicht neu ein: ein Querformat
        // ragte hochkant gedreht ueber die Buehne hinaus (Testinstanz 13.10.2026). Also
        // die Flaeche wieder einpassen und den Rahmen auf das ganze Bild setzen.
        // Mit viewMode 1 darf die Flaeche nicht kleiner als der Rahmen werden -
        // deshalb erst den Rahmen entfernen (clear), dann einpassen, dann crop().
        cropper.clear();
        var box = cropper.getContainerData();
        var c = cropper.getCanvasData();
        var faktor = Math.min(box.width / c.width, box.height / c.height);
        var b = c.width * faktor, h = c.height * faktor;
        cropper.setCanvasData({ left: (box.width - b) / 2, top: (box.height - h) / 2, width: b, height: h });
        cropper.crop();
        c = cropper.getCanvasData();
        cropper.setCropBoxData({ left: c.left, top: c.top, width: c.width, height: c.height });
        status.textContent = T('js_crop_rotated', 'Gedreht auf %s°', drehung);
    }

    function zuruecksetzen() {
        if (!cropper) { return; }
        cropper.reset();
        drehung = 0;
        verhaeltnis(0);
        status.textContent = '';
    }

    function verschieben(dx, dy) {
        var b = cropper.getCropBoxData();
        cropper.setCropBoxData({ left: b.left + dx, top: b.top + dy });
    }

    function fokusZiele() {
        return Array.prototype.filter.call(
            kasten.querySelectorAll('button, [tabindex="0"]'),
            function (e) { return !e.disabled && e.offsetParent !== null; }
        );
    }

    function tasten(ev) {
        if (!cropper) { return; }
        var schritt = ev.shiftKey ? 1 : 10;
        var aufKnopf = ev.target && ev.target.tagName === 'BUTTON';
        switch (ev.key) {
            case 'Escape': ev.preventDefault(); schliessen(null); return;
            case 'Enter':  if (!aufKnopf) { ev.preventDefault(); uebernehmen(); } return;
            case 'Tab': {
                var z = fokusZiele(), i = z.indexOf(document.activeElement);
                if (!z.length) { return; }
                ev.preventDefault();
                var n = ev.shiftKey ? i - 1 : i + 1;
                if (n < 0) { n = z.length - 1; }
                if (n >= z.length) { n = 0; }
                z[n].focus();
                return;
            }
        }
        if (aufKnopf) { return; }   // Pfeile/R auf Knoepfen nicht abfangen
        switch (ev.key) {
            case 'ArrowLeft':  ev.preventDefault(); verschieben(-schritt, 0); break;
            case 'ArrowRight': ev.preventDefault(); verschieben(schritt, 0);  break;
            case 'ArrowUp':    ev.preventDefault(); verschieben(0, -schritt); break;
            case 'ArrowDown':  ev.preventDefault(); verschieben(0, schritt);  break;
            case '+': case '=': ev.preventDefault(); cropper.zoom(0.1);  break;
            case '-': case '_': ev.preventDefault(); cropper.zoom(-0.1); break;
            case 'r': ev.preventDefault(); drehen(90);  break;
            case 'R': ev.preventDefault(); drehen(-90); break;
        }
    }

    function uebernehmen() {
        if (!cropper) { return; }
        var leinwand = cropper.getCroppedCanvas({
            maxWidth: 4096, maxHeight: 4096,
            fillColor: '#ffffff',              // transparente PNG -> weiss statt schwarz im JPEG
            imageSmoothingQuality: 'high'
        });
        if (!leinwand) { schliessen(null); return; }
        leinwand.toBlob(function (blob) { schliessen(blob || null); }, 'image/jpeg', 0.92);
    }

    function schliessen(ergebnis) {
        if (cropper) { cropper.destroy(); cropper = null; }
        bild.removeAttribute('src');
        dialog.hidden = true;
        document.documentElement.classList.remove('vs-crop-offen');
        if (fokusVorher && typeof fokusVorher.focus === 'function') {
            try { fokusVorher.focus(); } catch (e) {}
        }
        var l = loesen;
        loesen = null;
        if (l) { l(ergebnis); }
    }

    function laden(quelle) {
        return new Promise(function (ok, fehler) {
            if (typeof quelle === 'string') { ok(quelle); return; }
            var r = new FileReader();
            r.onload = function (e) { ok(e.target.result); };
            r.onerror = function () { fehler(new Error('FileReader')); };
            r.readAsDataURL(quelle);
        });
    }

    function oeffnen(quelle) {
        if (typeof window.Cropper !== 'function') {
            return Promise.reject(new Error('Cropper.js fehlt'));
        }
        if (loesen) { return Promise.resolve(null); }   // schon offen
        if (!dialog) { aufbauen(); }
        fokusVorher = document.activeElement;
        drehung = 0;
        status.textContent = '';
        return laden(quelle).then(function (src) {
            return new Promise(function (ok) {
                loesen = ok;
                verhaeltnis(0);
                dialog.hidden = false;
                document.documentElement.classList.add('vs-crop-offen');
                bild.onload = function () {
                    bild.onload = null;
                    cropper = new window.Cropper(bild, {
                        viewMode: 1,
                        autoCropArea: 1,
                        checkOrientation: false,
                        checkCrossOrigin: false,
                        background: true,
                        responsive: true,
                        toggleDragModeOnDblclick: false,
                        ready: function () {
                            var stage = kasten.querySelector('.vs-crop-stage');
                            if (stage) { stage.focus(); }
                        }
                    });
                };
                bild.onerror = function () {
                    status.textContent = T('js_crop_failed', 'Zuschnitt fehlgeschlagen: %s', 'Bild');
                };
                bild.src = src;
            });
        });
    }

    function moeglich(file) {
        if (!file) { return false; }
        var name = (file.name || '').toLowerCase();
        var typ = file.type || '';
        if (typ === 'image/gif' || /\.gif$/.test(name)) { return false; }
        if (/\.(heic|heif)$/.test(name) || /hei[cf]/.test(typ)) {
            return /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
        }
        return /^image\//.test(typ) || /\.(jpe?g|png|webp)$/.test(name);
    }

    window.vsBildZuschnitt = { oeffnen: oeffnen, moeglich: moeglich };
})();
