<?php
/**
 * changelog_data.php — Auslieferungsfassung
 *
 * Erzeugt am 01.10.2026 08:03 von bin/changelog_export.php.
 * NICHT von Hand aendern — Aenderungen gehoeren in die Projektchronik,
 * aus der diese Datei bei jedem Paketbau neu entsteht.
 *
 * Enthaelt die 5 juengsten Versionen mit anwenderrelevanten
 * Aenderungen. Die vollstaendige Liste steht unter
 * https://palindrom.de/valusafe/
 */

return [
    [
        'version' => '4.3.31',
        'date' => '2026-10-01',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Die Uebersicht im Verwaltungsbereich, die Einstellungen und die Statistikseite zeigen die Zahl der Raeume jetzt als "Raeume" an. Bisher stand dort "Orte", und die kleinere Zahl daneben hiess "Raeume", obwohl sie die Standorte zaehlte - etwa "6 Orte · 1 Raeume · 1 Pos." bei sechs Raeumen und einem Standort. Jetzt: "6 Raeume · Standorte: 1 · Pos.: 1". Die Zahlen selbst waren immer richtig, nur falsch beschriftet. Zu tun ist nichts.'
            ]
        ]
    ],
    [
        'version' => '4.3.30',
        'date' => '2026-09-30',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Das Docker-Image beruht jetzt auf PHP 8.4 statt 8.2. PHP 8.2 erhaelt ab dem 31.12.2026 keine Sicherheitskorrekturen mehr. Wer ValuSafe mit Docker betreibt, baut das Image neu (im Ordner docker: docker compose build --pull, danach docker compose up -d). Datenbank, Fotos, Belege und Sicherungen liegen in eigenen Volumes und bleiben erhalten; die Zugangsdaten kommen wie bisher aus der .env. Wer ValuSafe bei einem Hoster betreibt, ist nicht betroffen - dort bestimmt der Hoster die PHP-Version; ValuSafe laeuft mit PHP 8.2 bis 8.4.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Unter Einstellungen -> "Datenschutz & meine Daten" funktionieren beide Knoepfe jetzt so, wie sie beschrieben sind. "Account loeschen" schlug bisher bei jedem Konto mit einer technischen Fehlermeldung fehl - das Konto blieb bestehen. "Daten herunterladen" lieferte zwar eine Datei, darin fehlten aber die Kontodaten und das Aktivitaetsprotokoll; nur die Gegenstaende waren enthalten. Jetzt enthaelt die Datei Konto, Gegenstaende und Aktivitaeten (ohne Passwort und Zugangscodes), und die Loeschung entfernt das Konto samt Profilbild, offenen Links zum Zuruecksetzen des Passworts und den gespeicherten Anmeldeversuchen. Gegenstaende bleiben erhalten und tragen weiter den Namen, der unter "Erstellt von" steht; Protokolleintraege bleiben ebenfalls, verweisen aber nicht mehr auf das Konto. Der Hinweis unter dem Knopf versprach bisher, die Gegenstaende wuerden keinem Benutzer mehr zugeordnet - das stimmte nicht und ist berichtigt (alle neun Sprachen). Dasselbe gilt jetzt auch, wenn ein Administrator einen Benutzer in der Benutzerverwaltung loescht. Wer frueher vergeblich versucht hat, sein Konto zu loeschen, kann es jetzt erneut tun.'
            ]
        ]
    ],
    [
        'version' => '4.3.29',
        'date' => '2026-09-28',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Das Installationspaket enthielt eine Datei .user.ini, die PHP-Fehlermeldungen im Browser anzeigen liess - mit Dateipfaden und Ausschnitten aus Datenbankabfragen. Sie war fuer die Entwicklung gedacht und haette nie ausgeliefert werden duerfen. Seit Version 4.3.25 schaltet ValuSafe diese Anzeige auf fast allen Seiten selbst ab und schreibt Fehler stattdessen in logs/php_errors.log; die Datei ist jetzt entfernt. Wer ValuSafe aus dem Paket installiert hat: bitte die Datei .user.ini im Installationsordner loeschen (sie ist im FTP-Programm oft erst sichtbar, wenn versteckte Dateien eingeblendet werden). Die Systemuebersicht im Verwaltungsbereich zeigt an, ob sie noch da ist.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Der Einrichtungsassistent prueft jetzt alle PHP-Erweiterungen, die ValuSafe braucht. Bisher fragte er nur nach drei; fehlte mbstring oder fileinfo beim Hoster, meldete er trotzdem alles gruen - danach brach die Liste mit einem Fehler ab oder jeder Bild-Upload schlug fehl. Beide sind jetzt Voraussetzung, cURL und EXIF werden als Empfehlung angezeigt. Bestehende Installationen betrifft das nicht: wo ValuSafe laeuft, sind beide vorhanden.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Solange das Fenster "Hilfe & Informationen" offen ist, ist in der Seitenleiste jetzt das (i) hervorgehoben. Bisher blieb das Symbol der Seite darunter markiert, etwa das Zahnrad der Einstellungen - es sah aus, als haette der Klick nicht gewirkt. Beim Schliessen kehrt die Markierung zurueck.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'ValuSafe laesst sich jetzt auch in einem Unterordner betreiben, etwa unter beispiel.de/valusafe/, ohne dass die App-Funktion leidet. Bisher gingen Service Worker, App-Manifest, der QR-Code auf der Seite "Oeffentlicher Link" und zwei Links davon aus, dass ValuSafe direkt im Wurzelverzeichnis der Domain liegt; in einem Unterordner liess sich ValuSafe nicht als App installieren, und der Zwischenspeicher haette Fotos und Belege mit erfasst. Wer im Wurzelverzeichnis installiert hat, merkt keinen Unterschied.'
            ]
        ]
    ],
    [
        'version' => '4.3.28',
        'date' => '2026-09-28',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Die Systemuebersicht im Verwaltungsbereich zeigt kein "Sicherheitszertifikat" mehr. Die Karte mit fuenf Sternen, "10/10 OWASP" und "Production Ready" und das verlinkte Dokument vom Juni 2026 beruhten auf keiner unabhaengigen Pruefung - sie waren selbst erstellt, und spaetere Funde haben mehrere der Aussagen widerlegt. Beides ist entfernt. Zu tun ist nichts.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Im Suchfilter der Liste heisst die erste Auswahl im Feld "Raum" jetzt "Alle Raeume" statt "Alle Orte" - passend zu dem, was das Feld seit der Umstellung auf Raeume auflistet. In allen neun Sprachen. Zu tun ist nichts.'
            ]
        ]
    ],
    [
        'version' => '4.3.27',
        'date' => '2026-09-27',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'WICHTIG fuer alle, die ValuSafe aus dem Installationspaket eingerichtet haben: Impressum und Datenschutzerklaerung enthielten bis Version 4.3.26 die Angaben des Entwicklers - Name, Anschrift, Telefon, E-Mail und Hoster - statt Platzhaltern. Wer diese beiden Seiten nach der Installation nicht selbst angepasst hat, nennt dort den Entwickler als Verantwortlichen. Bitte oeffnen Sie impressum.php und datenschutz.php und tragen Sie Ihre eigenen Angaben ein; neue Pakete enthalten ab jetzt Platzhalter in eckigen Klammern. Wer ValuSafe nur fuer sich selbst betreibt und die Seiten nicht oeffentlich zeigt, kann sie auch einfach leeren.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Die E-Mail nach einer automatischen Sicherung kommt jetzt von der eigenen Domain. Bisher stand als Absender fest die Adresse des Entwicklers darin, auch auf fremden Installationen - solche Mails landen meist im Spam oder werden abgewiesen. Jetzt wird der Absender aus der Adresse der eigenen Installation gebildet; wer einen bestimmten Absender braucht, setzt MAIL_FROM in der config.php. Sonst ist nichts zu tun.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Ältere Änderungen: die vollständige Liste steht unter https://palindrom.de/valusafe/'
            ]
        ]
    ]
];
