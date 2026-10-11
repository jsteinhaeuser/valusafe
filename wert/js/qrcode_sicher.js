/**
 * qrcode_sicher.js — robuster Aufruf von QRCode.js (js/qrcode.min.js)
 *
 * QRCode.js (davidshimjs) berechnet die Groesse des Codes bei manchen Text-
 * laengen falsch und bricht dann mit "code length overflow" ab - es erscheint
 * kein QR-Code. Getroffen hat das die 2FA-Einrichtung (Test 2ix 11.10.2026,
 * Konto "testleser"): die otpauth-Adresse hatte genau so eine Laenge.
 * Gemessen: Laengen 121, 139, 157, 179, 195, 197 scheitern mit der Vorgabe;
 * mit Ausweichen auf eine andere Fehlerkorrekturstufe gelingt jede Laenge
 * von 1 bis 200.
 *
 * vsQrCode(element, optionen) - Optionen wie bei new QRCode(); liefert das
 * QRCode-Objekt oder null, wenn keine Stufe passt.
 */
function vsQrCode(el, opts) {
    var L = window.QRErrorCorrectLevel || {L: 1, M: 0, Q: 3, H: 2};
    var stufen = [];
    if (opts && opts.correctLevel !== undefined) stufen.push(opts.correctLevel);
    [L.M, L.L, L.Q, L.H].forEach(function (s) { if (stufen.indexOf(s) < 0) stufen.push(s); });
    for (var i = 0; i < stufen.length; i++) {
        el.innerHTML = '';   // ein gescheiterter Versuch hinterlaesst ein leeres Canvas
        try {
            var o = {};
            for (var k in opts) o[k] = opts[k];
            o.correctLevel = stufen[i];
            return new QRCode(el, o);
        } catch (e) { /* naechste Stufe */ }
    }
    el.innerHTML = '';
    return null;
}
