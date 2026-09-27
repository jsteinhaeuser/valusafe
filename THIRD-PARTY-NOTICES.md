# Fremde Bestandteile / Third-Party Notices

ValuSafe steht unter der MIT-Lizenz (siehe `LICENSE`). Es liefert die folgenden
fremden Bestandteile mit aus. Sie behalten ihre eigene Lizenz; die
Urheberrechtsvermerke sind hier wiedergegeben, wie es die jeweilige Lizenz
verlangt.

ValuSafe is licensed under the MIT License (see `LICENSE`). It bundles the
third-party components listed below. They remain under their own licenses; the
required copyright notices are reproduced here.

---

## Chart.js 4.4.1

- Datei: `wert/js/chart.umd.min.js`
- https://www.chartjs.org
- Lizenz: MIT
- Copyright (c) 2023 Chart.js Contributors

## Tabler Icons 3.44.0

- Dateien: `wert/css/tabler-icons.min.css` und `wert/css/fonts/tabler-icons.*`
- https://tabler.io
- Lizenz: MIT
- Copyright (c) 2020-2026 Paweł Kuna

## QRCode.js

- Datei: `wert/js/qrcode.min.js`
- https://github.com/davidshimjs/qrcodejs
- Lizenz: MIT
- Copyright (c) 2012 davidshimjs

## ZXing for JS (@zxing/browser)

- Datei: `wert/js/zxing-browser.min.js`
- https://github.com/zxing-js/browser
- Lizenz: MIT
- Copyright (c) 2018 ZXing for JS

Das ausgelieferte Bundle enthält zusätzlich die TypeScript-Hilfsfunktionen
(tslib) von Microsoft. Deren Lizenzblock steht im Bundle selbst:
"Copyright (c) Microsoft Corporation", lizenziert unter der Apache License,
Version 2.0 — http://www.apache.org/licenses/LICENSE-2.0

Deren Abschnitt 4 verlangt, dass Empfänger eine Kopie des Lizenztextes
erhalten. Er liegt wörtlich als `LICENSE-Apache-2.0.txt` im Wurzelverzeichnis.

---

## Lizenztext MIT

Für alle oben mit "MIT" bezeichneten Bestandteile gilt der folgende Text; der
jeweilige Urheberrechtsvermerk ist der oben genannte.

```
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## Erledigt

- Die Fußzeile beider Handbücher trägt nur noch den Urhebervermerk
  ("© 2026 Jörg Steinhäuser · ValuSafe v4.2.3"). Das frühere "Alle Rechte
  vorbehalten" ist entfallen; ein Urhebervermerk ist mit der MIT-Lizenz
  vereinbar, sie verlangt ihn sogar.
- Der Apache-2.0-Lizenztext liegt seit dem 06.09.2026 als
  `LICENSE-Apache-2.0.txt` im Wurzelverzeichnis, wörtlich von
  https://www.apache.org/licenses/LICENSE-2.0.txt übernommen. Alternativ liesse
  sich das ZXing-Bundle ohne die Microsoft-Hilfsfunktionen neu bauen; dann
  könnte die Datei entfallen.
- `wert/service/nutzungslizenz.pdf` — der proprietäre Nutzungslizenzvertrag ist
  mit 4.3.16 entfallen und durch `wert/service/lizenz.md` ersetzt.
- Die Lizenzschlüssel-Mechanik mit Rückruf an licence.palindrom.de ist mit
  4.3.16 entfernt.
