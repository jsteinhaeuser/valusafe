<?php
/**
 * changelog_data.php — Auslieferungsfassung
 *
 * Erzeugt am 24.11.2026 16:29 von bin/changelog_export.php.
 * NICHT von Hand aendern — Aenderungen gehoeren in die Projektchronik,
 * aus der diese Datei bei jedem Paketbau neu entsteht.
 *
 * Enthaelt die 5 juengsten Versionen mit anwenderrelevanten
 * Aenderungen. Die vollstaendige Liste steht unter
 * https://palindrom.de/valusafe/
 */

return [
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
            ]
        ]
    ],
    [
        'version' => '4.9',
        'date' => '2026-11-08',
        'entries' => [
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Buchsuche: Bücher aus dem deutschen Sprachraum findet ValuSafe jetzt über die Deutsche Nationalbibliothek – mit Titel, Untertitel, Autor, Verlag, Jahr und Auflage. Bisher fragte die Suche nur Google Books und Open Library; Google antwortet ohne eigenen Schlüssel oft gar nicht, Open Library kennt nur wenige deutsche Bücher.',
                'text_en' => 'Book lookup: ValuSafe now finds books from German-speaking countries via the German National Library – with title, subtitle, author, publisher, year and edition. Until now the lookup only asked Google Books and Open Library; Google often does not answer without its own key, and Open Library knows only a few German books.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Ladenpreis: Liefert die Nationalbibliothek einen Preis, trägt ValuSafe ihn in ein leeres Preisfeld ein (ältere D-Mark-Preise umgerechnet) und vermerkt ihn in der Notiz.',
                'text_en' => 'Retail price: if the National Library supplies a price, ValuSafe enters it into an empty price field (older Deutsche Mark prices converted) and notes it in the notes.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Barcode-Scan auf dem iPhone und iPad: „Scannen“ öffnet jetzt auch in Safari die Kamera und liest Barcode bzw. ISBN. Bisher blieb dort nur die Eingabe von Hand.',
                'text_en' => 'Barcode scan on iPhone and iPad: “Scan” now also opens the camera in Safari and reads the barcode or ISBN. Until now only manual entry was possible there.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Schnellerfassung mit Barcode: Das Formular hinter dem Plus-Knopf hat jetzt ein Feld „Barcode / ISBN“ mit Scan-Knopf. Die Suche füllt Name, Notiz und Preis und bietet das Cover als Foto an; nach dem Speichern ist alles bereit für den nächsten Gegenstand.',
                'text_en' => 'Quick entry with barcode: the form behind the plus button now has a “Barcode / ISBN” field with a scan button. The lookup fills in name, notes and price and offers the cover as photo; after saving, everything is ready for the next item.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Sprachen: Das Menü „Hilfe & Information“ ist in allen Sprachen beschriftet; Erste Schritte, Über diese Anwendung, Lizenz und Rückmeldung gibt es auf Englisch (andere Sprachen sehen die englische Fassung). Versicherungs-Export, Übergabeprotokoll und die öffentliche Chronik sind übersetzt, die System-Information im Verwaltungsbereich gibt es auf Englisch.',
                'text_en' => 'Languages: the “Help & information” menu is labelled in all languages; Getting started, About this application, Licence and Feedback are available in English (other languages see the English version). The insurance export, the handover report and the public changelog are translated, and the system information in the admin area is available in English.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Versicherungs-Export: Gegenstände mit englischen Kategorien (z. B. „Jewellery“, „Electronics“) landen in der passenden Versicherungskategorie statt unter „Sonstiges“.',
                'text_en' => 'Insurance export: items with English categories (e.g. “Jewellery”, “Electronics”) are now sorted into the matching insurance category instead of “Other”.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Umlaute: Namen aus der Buchsuche und vom iPhone kamen teils mit zerlegten Umlauten an („fu r“ statt „für“) – die Suche fand sie dann nicht. Beim Speichern wird jetzt jeder Text vereinheitlicht.',
                'text_en' => 'Umlauts: names from the book lookup and from the iPhone sometimes arrived with decomposed umlauts (“fu r” instead of “für”) – search then did not find them. Every text is now normalised when saved.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Update: Wer im Verwaltungsbereich versehentlich das vollständige Paket statt des Update-Pakets wählt, bekommt jetzt eine verständliche Meldung statt eines technischen Fehlers.',
                'text_en' => 'Update: if you accidentally choose the full package instead of the update package in the admin area, you now get a clear message instead of a technical error.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Ältere Änderungen: die vollständige Liste steht unter https://palindrom.de/valusafe/'
            ]
        ]
    ]
];
