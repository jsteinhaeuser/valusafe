<?php
/**
 * changelog_data.php — Auslieferungsfassung
 *
 * Erzeugt am 26.11.2026 12:24 von bin/changelog_export.php.
 * NICHT von Hand aendern — Aenderungen gehoeren in die Projektchronik,
 * aus der diese Datei bei jedem Paketbau neu entsteht.
 *
 * Enthaelt die 5 juengsten Versionen mit anwenderrelevanten
 * Aenderungen. Die vollstaendige Liste steht unter
 * https://palindrom.de/valusafe/
 */

return [
    [
        'version' => '4.9.5',
        'date' => '2026-11-26',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Update-Seite: Scheitert die Versionsabfrage, nennt die Meldung jetzt den Grund (etwa „palindrom.de antwortet mit Status 404“) statt „Antwort größer als angekündigt“. Die Meldungen der Update-Seite erscheinen jetzt in der eingestellten Sprache, bisher immer auf Deutsch.',
                'text_en' => 'Update page: if the version check fails, the message now names the reason (such as “palindrom.de answers with status 404”) instead of “answer larger than announced”. The messages of the update page now appear in the selected language; until now they were always in German.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Barrierefreiheit: Export-Menü und Hilfefenster lassen sich jetzt vollständig mit der Tastatur bedienen – der Fokus springt beim Öffnen hinein, die Pfeiltasten wechseln im Export-Menü den Eintrag, Esc schließt und kehrt zum Knopf zurück. Der Zurück-Pfeil oben und der Schließen-Knopf der Hilfe haben jetzt einen Namen für Screenreader, die Versicherungsseite einen eigenen Seitentitel, und eine fehlgeschlagene Anmeldung wird von Screenreadern sofort angesagt.',
                'text_en' => 'Accessibility: the export menu and the help window can now be used entirely with the keyboard – focus moves into them when they open, the arrow keys move through the export menu, Esc closes and returns to the button. The back arrow at the top and the close button of the help window now have a name for screen readers, the insurance page has its own page title, and a failed sign-in is announced by screen readers right away.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Bilder: Die Reihenfolge, die man beim Bearbeiten durch Ziehen der Bilder festlegt, wird jetzt gespeichert. Bisher sah es nur so aus – nach dem Neuladen stand wieder die alte Reihenfolge da.',
                'text_en' => 'Images: the order you set by dragging the images while editing an item is now saved. Until now it only looked that way – after reloading, the old order was back.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Barrierefreiheit: Beim Bearbeiten lassen sich die Bilder jetzt auch mit der Tastatur vergrößern; die Großansicht ist für Screenreader als Fenster erkennbar, und Esc führt zurück zum Bild. Übersicht, Neu, Bearbeiten, Einstellungen und Versicherungen haben eine Hauptüberschrift für Screenreader. Auf sehr schmalen Bildschirmen wird der Knopf „Schnell“ oben nicht mehr abgeschnitten.',
                'text_en' => 'Accessibility: while editing, images can now also be enlarged with the keyboard; the large view is recognisable as a window for screen readers, and Esc returns to the image. Overview, new item, edit, settings and insurance now have a main heading for screen readers. On very narrow screens the “Quick” button at the top is no longer cut off.'
            ]
        ]
    ],
    [
        'version' => '4.9.4',
        'date' => '2026-11-24',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Sicherheit: Zwei weitere Schutz-Header schirmen ValuSafe gegen fremde Fenster und Seiten ab. Server und Docker-Image nennen keine Versionsnummern von Apache und PHP mehr.',
                'text_en' => 'Security: two more protective headers shield ValuSafe from other windows and sites. The server and the Docker image no longer reveal the Apache and PHP version numbers.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Übersicht: Die Bilderzahl auf einer Kachel führt jetzt direkt zu den Bildern in der Bearbeitung. Bisher öffnete sie die Detailansicht, die nur das Hauptbild zeigt. Wer nur lesen darf, sieht weiter nur die Anzahl.',
                'text_en' => 'Overview: the image count on a tile now leads straight to the images on the edit page. Until now it opened the detail view, which shows only the main image. Users who may only read still see just the count.'
            ]
        ]
    ],
    [
        'version' => '4.9.3',
        'date' => '2026-11-24',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Bilder hochladen: Wer mehrere Bilder auf einmal auswählt, bekommt sie jetzt zuverlässig hochgeladen – jedes Bild wird einzeln übertragen, mit Fortschritt pro Bild. Bisher konnte der Upload bei Servern mit kleiner Upload-Grenze ganz scheitern und nur einen unverständlichen Sicherheits-Fehler zeigen. Ist ein Bild zu groß, steht jetzt dabei, welches und wie viel der Server annimmt.',
                'text_en' => 'Image upload: selecting several images at once now uploads them reliably – each image is sent separately, with progress per image. Previously the upload could fail entirely on servers with a small upload limit and show only an unclear security error. If an image is too large, the message now names it and states how much the server accepts.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Bild-Galerie (Verwaltung): Sie zeigt jetzt alle Bilder, nicht mehr nur das Hauptbild jedes Gegenstands.',
                'text_en' => 'Image gallery (admin): it now shows all images, not only the main image of each item.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Übersicht: Auf jeder Kachel steht neben dem Namen ein Bleistift. Er öffnet die Bearbeitung direkt, ohne Umweg über die Detailansicht. Er ist immer sichtbar, auch auf iPhone und iPad, und nur für Benutzer mit Bearbeitungsrecht.',
                'text_en' => 'Overview: every tile now shows a pencil next to the name. It opens the edit page directly, without going through the detail view. It is always visible, also on iPhone and iPad, and only for users who may edit.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Rechte-Übersicht: Fest vergebene Rechte sind jetzt voll grün und tragen ein kleines Schloss. Bisher waren sie nur blass und sahen aus wie „nicht gesetzt“.',
                'text_en' => 'Permissions overview: fixed permissions are now shown in full green with a small lock. Until now they were only pale and looked like “not set”.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Schallplatten: Ältere LPs ohne Barcode lassen sich jetzt über ihre Katalognummer finden (steht auf dem Etikett oder dem Rücken, z. B. „SHVL 804“). Der neue Knopf „Katalognummer“ neben „Scannen“ sucht bei MusicBrainz und füllt Name, Notiz und Cover. Ein fehlender Zusatz wie „AS“ oder „-P“ stört nicht. Bei Musik nennt die Notiz jetzt auch das Land der Pressung.',
                'text_en' => 'Records: older LPs without a barcode can now be found by their catalogue number (on the label or the spine, e.g. “SHVL 804”). The new “Catalogue no.” button next to “Scan” searches MusicBrainz and fills in name, notes and cover. A missing suffix such as “AS” or “-P” does not matter. For music, the notes now also name the country of the pressing.'
            ]
        ]
    ],
    [
        'version' => '4.9.2',
        'date' => '2026-11-22',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Benutzerverwaltung: Auf Installationen ohne eingerichteten SuperAdmin kann der Admin wieder Benutzer anlegen und bearbeiten. Die Rechte-Übersicht zeigt jetzt, dass „Benutzer verwalten“ dem SuperAdmin vorbehalten ist, wo es einen gibt, und dass der Admin-Bereich für Editor und Leser grundsätzlich gesperrt ist. Neu: Der SuperAdmin kann in seinen Einstellungen Admins die Benutzerverwaltung freigeben; SuperAdmin-Konten bleiben dabei geschützt.',
                'text_en' => 'User management: on installations without a SuperAdmin, the admin can create and edit users again. The permissions overview now shows that “Manage users” is reserved for the SuperAdmin where one exists, and that the admin area is always closed to editors and readers. New: the SuperAdmin can allow admins to manage users in the SuperAdmin settings; SuperAdmin accounts stay protected.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Docker: Der Container spielt Datenbank-Migrationen jetzt bei jedem Start selbst ein. Bisher musste nach einem neuen Image die Datenbank-Aktualisierung von Hand gestartet werden, obwohl die Hinweise zu Version 4.8 anderes sagten. Schlägt eine Migration fehl, startet der Container nicht und meldet den Fehler im Protokoll. Ausnahme: Auf Installationen von vor dem 26.09.2026, auf denen nie eine Migration lief, führt der Container nichts aus und zeigt eine Warnung; das weitere Vorgehen beschreibt die Docker-Anleitung (Abschnitt 10).',
                'text_en' => 'Docker: the container now applies database migrations itself every time it starts. Until now the database update had to be started by hand after a new image, although the notes for version 4.8 said otherwise. If a migration fails, the container does not start and reports the error in its log. Exception: on installations set up before 26 September 2026 on which no migration ever ran, the container applies nothing and shows a warning; the Docker guide (section 10) explains what to do.'
            ]
        ]
    ],
    [
        'version' => '4.9.1',
        'date' => '2026-11-09',
        'entries' => [
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'CDs und LPs: Die Barcode-Suche findet jetzt auch Musik über MusicBrainz – mit Künstler, Titel, Erscheinungsjahr und Cover.',
                'text_en' => 'CDs and LPs: the barcode lookup now also finds music via MusicBrainz, with artist, title, release year and cover.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'iPhone im Hochformat: Die untere Navigation war versehentlich ausgeblendet. Sie erscheint jetzt auf allen Seiten, „Mehr“ öffnet das vollständige Menü mit Abmelden.',
                'text_en' => 'iPhone in portrait: the bottom navigation was hidden by mistake. It now appears on every page, and “More” opens the full menu including logout.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'iPhone im Querformat: Die Icon-Leiste lässt sich scrollen, die unteren Einträge wie Abmelden sind wieder erreichbar.',
                'text_en' => 'iPhone in landscape: the icon bar now scrolls, so the lower entries such as logout can be reached again.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Installation: Die Schutzdateien für die Ordner „upload“ und „documents“ fehlten im Installationspaket. Wer aus dem Paket installiert hat, sollte sie von Hand ergänzen (siehe Release-Hinweise).',
                'text_en' => 'Installation: the protection files for the “upload” and “documents” folders were missing from the installation package. If you installed from the package, please add them by hand (see the release notes).'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Installation: Die .htaccess-Dateien funktionieren jetzt auch auf Apache 2.4 ohne mod_php und ohne mod_access_compat (z. B. Synology Web Station) und hinter einem vorgeschalteten Proxy. Bisher antwortete die Anwendung dort mit Fehler 500.',
                'text_en' => 'Installation: the .htaccess files now also work on Apache 2.4 without mod_php and mod_access_compat (e.g. Synology Web Station) and behind a reverse proxy. Until now the application answered with error 500 there.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Datenschutz: Die Erklärung nennt jetzt die Verzeichnisse, die beim Nachschlagen von Barcode oder ISBN angefragt werden.',
                'text_en' => 'Privacy: the privacy policy now names the directories that are queried when you look up a barcode or ISBN.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Ältere Änderungen: die vollständige Liste steht unter https://palindrom.de/valusafe/'
            ]
        ]
    ]
];
