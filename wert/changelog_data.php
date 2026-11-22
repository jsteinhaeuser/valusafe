<?php
/**
 * changelog_data.php — Auslieferungsfassung
 *
 * Erzeugt am 22.11.2026 15:35 von bin/changelog_export.php.
 * NICHT von Hand aendern — Aenderungen gehoeren in die Projektchronik,
 * aus der diese Datei bei jedem Paketbau neu entsteht.
 *
 * Enthaelt die 5 juengsten Versionen mit anwenderrelevanten
 * Aenderungen. Die vollstaendige Liste steht unter
 * https://palindrom.de/valusafe/
 */

return [
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
            ]
        ]
    ],
    [
        'version' => '4.8.1',
        'date' => '2026-11-06',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'QR-Codes: Die Seite „QR-Codes“ und der Etikettendruck brachen in 4.8 mit einem Serverfehler ab. Behoben.',
                'text_en' => 'QR codes: the “QR codes” page and label printing failed with a server error in 4.8. Fixed.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Update: Nach „Jetzt nachsehen“ bestätigt ValuSafe, dass es gerade nachgesehen hat und ob es eine neuere Version gibt. Vorher änderte sich nur die Uhrzeit.',
                'text_en' => 'Update: after “Check now”, ValuSafe confirms that it has just checked and whether a newer version exists. Previously only the time changed.'
            ]
        ]
    ],
    [
        'version' => '4.8',
        'date' => '2026-11-05',
        'entries' => [
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Hersteller, Modell und Zustand: Jeder Gegenstand hat dafür eigene Felder (Zustand: neu, sehr gut, gut, Gebrauchsspuren, defekt). Sie erscheinen in der Detailansicht, als zuschaltbare Spalten, im CSV-Export, in der Versicherungsliste und in den Schadensfall-Unterlagen. Die Suche findet jetzt auch Hersteller, Modell und Seriennummer.',
                'text_en' => 'Manufacturer, model and condition: every item has its own fields for these (condition: new, very good, good, signs of use, defective). They appear in the detail view, as optional columns, in the CSV export, in the insurance list and in the claim documents. Search now also finds manufacturer, model and serial number.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Garantieende: Ein neues Feld „Garantie bis“ mit dem Knopf „2 Jahre ab Kaufdatum“. Das Dashboard zeigt Garantien, die in den nächsten 30 Tagen enden, die Liste hat dafür einen Filter, und die tägliche Sicherungs-Mail nennt sie ebenfalls.',
                'text_en' => 'Warranty end: a new “Warranty until” field with a “2 years from purchase date” button. The dashboard shows warranties ending within the next 30 days, the list has a filter for them, and the daily backup e-mail lists them too.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'QR-Etiketten: Die Seite „QR-Codes“ druckt auf A4-Etikettenbögen (70 × 37, 70 × 42,3 und 105 × 48 mm) mit QR-Code, Name, Nummer und Raum. Angebrochene Bögen lassen sich ab einem freien Feld weiterverwenden. Neu sind Etiketten für Räume, Kisten und Regale sowie die Sammelaktion „QR-Etiketten drucken“ in der Liste.',
                'text_en' => 'QR labels: the “QR codes” page prints on A4 label sheets (70 × 37, 70 × 42.3 and 105 × 48 mm) with QR code, name, number and room. Partly used sheets can be continued from any free label. New: labels for rooms, boxes and shelves, and the bulk action “Print QR labels” in the list.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Ältere Änderungen: die vollständige Liste steht unter https://palindrom.de/valusafe/'
            ]
        ]
    ]
];
