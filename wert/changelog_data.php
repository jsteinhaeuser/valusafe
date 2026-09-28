<?php
/**
 * changelog_data.php — Auslieferungsfassung
 *
 * Erzeugt am 28.09.2026 17:36 von bin/changelog_export.php.
 * NICHT von Hand aendern — Aenderungen gehoeren in die Projektchronik,
 * aus der diese Datei bei jedem Paketbau neu entsteht.
 *
 * Enthaelt die 5 juengsten Versionen mit anwenderrelevanten
 * Aenderungen. Die vollstaendige Liste steht unter
 * https://palindrom.de/valusafe/
 */

return [
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
            ]
        ]
    ],
    [
        'version' => '4.3.26',
        'date' => '2026-09-27',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Wer ValuSafe als App auf dem Handy oder Rechner installiert hat, bekommt nach dem Update wieder die aktuellen Darstellungs- und Programmdateien. Die App haelt diese Dateien in einem Zwischenspeicher vor und erneuert ihn nur, wenn sich dessen Versionsname aendert - und der war seit Version 4.3.23 nicht mehr mitgezaehlt worden. Wer seitdem Merkwuerdigkeiten in der Darstellung sah, sollte sie jetzt los sein. Zu tun ist nichts; im Zweifel die App einmal schliessen und neu oeffnen.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Zwei Fehlermeldungen erscheinen jetzt als lesbarer Satz. Scheiterte das Speichern einer neuen Reihenfolge in der Liste oder eine Aktion in der Bildverwaltung, stand statt eines Hinweises der interne Bezeichner "error_generic" auf dem Bildschirm - der Text dazu fehlte in allen neun Sprachen. Zu tun ist nichts.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Eine alte Hilfsdatei ist entfernt, die Fotos an einen fremden Dienst geschickt haette. Sie las Barcodes aus Bildern, indem sie das Bild an zxing.org uebertrug. Keine Seite von ValuSafe hat sie seit langem benutzt - das Scannen geschieht im Browser, ohne dass ein Bild den Server verlaesst -, aber sie lag noch bei und war fuer angemeldete Benutzer aufrufbar. Aufgefallen ist sie, weil die Beschreibung versprach, ausser der Barcode-Suche gehe nichts nach aussen. Zu tun ist nichts.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Der Filter "Orte" im Aktivitaetsprotokoll findet jetzt etwas. Das Protokoll fuehrt Aenderungen an Raeumen unter einem anderen Namen, als der Filter suchte - er blieb deshalb immer leer. Zu tun ist nichts.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Die Installationsanleitung in der README war falsch. Sie sagte, setup.php aus dem Unterordner install/ heraus aufzurufen. Der Assistent schreibt die Konfiguration aber in seinen eigenen Ordner und muss deshalb neben index.php liegen; aus install/ heraus entstand eine Installation, die nicht lief. Das fertige Installationspaket war nie betroffen, es ist richtig aufgebaut. Wer ValuSafe aus dem Quellcode installiert, folgt jetzt der korrigierten Anleitung.'
            ]
        ]
    ],
    [
        'version' => '4.3.25',
        'date' => '2026-09-26',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Der Standort eines Gegenstandes wird jetzt gespeichert. Bisher diente das Auswahlfeld "Standort" beim Anlegen und Bearbeiten nur dazu, die Liste der Positionen einzugrenzen. Wer einen Standort waehlte, aber keine Position, verlor die Wahl beim Speichern ohne jede Meldung. Jetzt bleibt der Standort auch ohne Position erhalten und erscheint in der Liste. Ausserdem laesst sich ein Standort nicht mehr loeschen, solange noch Gegenstaende daran haengen. Zu tun ist nichts.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Nach einer Neuinstallation war das Auswahlfeld "Raum" leer. Die vier Startraeume, die der Installationsassistent anlegt, landeten in einer alten Tabelle, die ValuSafe laengst nicht mehr liest. Wer ValuSafe frisch installiert hat, musste seine Raeume also erst selbst anlegen, bevor er einen Gegenstand einordnen konnte. Die Startraeume kommen jetzt dort an, wo ValuSafe sie sucht. Bestehende Installationen sind nicht betroffen; wer vor diesem Release neu installiert hat, legt die fehlenden Raeume einfach in der Verwaltung an.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Docker: Eine frische Installation fuehrt beim ersten Aufruf der Datenbank-Aktualisierung keine Migrationen mehr nach, die ihr Schema laengst enthaelt. Der Installationsassistent fuer Webhosting trug schon bisher alle mitgelieferten Migrationen als erledigt ein, der Docker-Start tat das nicht. Die ueberfluessigen Laeufe waren bisher harmlos, mit diesem Release waeren sie es nicht mehr gewesen: Eine der neuen Migrationen leert eine Spalte, in der ab jetzt echte Standorte stehen. Zu tun ist nichts.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Drei alte Tabellen fuer Raeume und Standorte sind entfernt: orte, standort_raeume und standort_positionen, dazu die Spalte wertsachen.ort_id. Sie stammten aus frueheren Ausbaustufen, der Programmcode las sie nicht mehr, und eine Neuinstallation legte sie trotzdem an. Bestehende Datenbanken raeumt die Migration 011 auf, und sie loescht die alten Tabellen samt Inhalt. Wer ValuSafe selbst betreibt, legt nach dem Aktualisieren zuerst eine Sicherung der Datenbank an und ruft dann einmal migrate.php auf.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Ältere Änderungen: die vollständige Liste steht unter https://palindrom.de/valusafe/'
            ]
        ]
    ]
];
