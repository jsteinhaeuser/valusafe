<?php
/**
 * update_schluessel.php — oeffentliche Schluessel, denen die Update-Funktion traut
 *
 * In ValuSafe (wert/) und Valu-Basic (usb/valu-basic/) BYTEGLEICH. Ein
 * Update-Paket wird nur eingespielt, wenn seine Signatur zu einem dieser
 * Schluessel passt (update_kern.php).
 *
 *   haupt    signiert jedes Paket (bin/paket_signieren.php).
 *   reserve  liegt nur offline. Geht der Hauptschluessel verloren oder wird
 *            er bekannt, signiert die Reserve ein Update, das DIESE Datei
 *            austauscht - ohne sie muessten alle Anwender neu installieren.
 *
 * Erzeugt am 29.10.2026 mit bin/schluessel_erzeugen.php. Die Kennung sind die
 * ersten 16 Hex-Zeichen von SHA-256 ueber den Schluessel.
 *
 * Gibt nur ein Array zurueck und druckt nichts. Nichts davon ist geheim.
 */
return [
    '9632bc9550a1a5db' => 'lj67ofWGcVkotHHdNcfZoQPItENnc2HPpzZ0eGBb1BU=',   // haupt
    '6408e9813cae79cf' => 'wgint/oZLLn/SzEQJeHVhzJK4+365LmA4RlV5aTnz/k=',   // reserve
];
