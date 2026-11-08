<?php
/**
 * changelog_data.php — Auslieferungsfassung
 *
 * Erzeugt am 08.11.2026 15:58 von bin/changelog_export.php.
 * NICHT von Hand aendern — Aenderungen gehoeren in die Projektchronik,
 * aus der diese Datei bei jedem Paketbau neu entsteht.
 *
 * Enthaelt die 5 juengsten Versionen mit anwenderrelevanten
 * Aenderungen. Die vollstaendige Liste steht unter
 * https://palindrom.de/valusafe/
 */

return [
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
            ]
        ]
    ],
    [
        'version' => '4.7.1',
        'date' => '2026-11-05',
        'entries' => [
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Schnellerfassung auch am Desktop: Der Knopf „Schnellerfassung“ steht neben „Hinzufügen“ – Foto wählen, Namen eingeben, speichern; Raum und Kategorie bleiben für den nächsten Gegenstand eingestellt.',
                'text_en' => 'Quick entry on the desktop too: the “Quick entry” button sits next to “Add” – choose a photo, enter a name, save; room and category stay selected for the next item.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Einstellungen: Die Administration ist zweispaltig, mit einem Schnellzugriff auf Verwaltung, Benutzer und Rechte, Sicherung, Systemprüfung und Update.',
                'text_en' => 'Settings: the administration section has two columns, with quick access to administration, users and permissions, backup, system check and update.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Sicherheit: Eine Versicherung lässt sich nur noch über den Löschen-Knopf entfernen. Bisher genügte ein Link – ein präparierter Link, den ein angemeldeter Administrator anklickt, hätte den Vertrag samt Zuordnungen gelöscht.',
                'text_en' => 'Security: an insurance policy can now only be removed with the delete button. Previously a link was enough – a crafted link clicked by a logged-in administrator could have deleted the policy and its assignments.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Übersetzungen: Die Kachel „Backup-Status“ in der Verwaltung, Zahlungsweise und Gebäudeart in der Versicherungsübersicht sowie die Anzahl „1 Gegenstand“ waren in anderen Sprachen noch deutsch bzw. falsch.',
                'text_en' => 'Translations: the “Backup status” tile in the administration, payment mode and building type in the insurance overview, and the count “1 item” were still in German or wrong in other languages.'
            ]
        ]
    ],
    [
        'version' => '4.7',
        'date' => '2026-11-02',
        'entries' => [
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Updates per Knopfdruck: Unter Verwaltung > Update zeigt ValuSafe, ob es eine neue Version gibt, lädt das Update-Paket auf Wunsch herunter, prüft seine Signatur und spielt es ein - mit Sicherung der Datenbank vorher und Rückkehr zur bisherigen Version, falls etwas schiefgeht. Wer keinen Internetzugang vom Server aus hat, kann das Paket auch hochladen. Gilt ab dem Update nach 4.7; 4.7 selbst wird noch von Hand installiert.',
                'text_en' => 'One-click updates: under Administration > Update, ValuSafe shows whether a new version is available, downloads the update package on request, verifies its signature and installs it - backing up the database first and returning to the previous version if anything goes wrong. Servers without internet access can upload the package instead. Works from the update after 4.7; 4.7 itself is still installed by hand.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Versionsabfrage: Höchstens einmal am Tag fragt ValuSafe bei palindrom.de nach einer neuen Version, nur wenn ein Administrator die Verwaltung öffnet. Übertragen wird nichts außer der IP-Adresse des Servers. Abschaltbar mit define(\'VALUSAFE_UPDATE_PRUEFEN\', false) in config.php; steht in der Vorlage der Datenschutzerklärung.',
                'text_en' => 'Version check: at most once a day, and only when an administrator opens the administration, ValuSafe asks palindrom.de whether a new version exists. Nothing is transmitted except the server\'s IP address. Can be switched off with define(\'VALUSAFE_UPDATE_PRUEFEN\', false) in config.php; covered in the privacy policy template.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Ältere Änderungen: die vollständige Liste steht unter https://palindrom.de/valusafe/'
            ]
        ]
    ]
];
