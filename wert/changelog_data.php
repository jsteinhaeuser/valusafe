<?php
/**
 * changelog_data.php — Auslieferungsfassung
 *
 * Erzeugt am 24.10.2026 17:01 von bin/changelog_export.php.
 * NICHT von Hand aendern — Aenderungen gehoeren in die Projektchronik,
 * aus der diese Datei bei jedem Paketbau neu entsteht.
 *
 * Enthaelt die 5 juengsten Versionen mit anwenderrelevanten
 * Aenderungen. Die vollstaendige Liste steht unter
 * https://palindrom.de/valusafe/
 */

return [
    [
        'version' => '4.5.2',
        'date' => '2026-10-24',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Sicherheit, Zwei-Faktor-Anmeldung: Der Code aus der Authenticator-App lässt sich nicht mehr beliebig oft durchprobieren. Nach 5 falschen Codes von einem Gerät ist die Eingabe 15 Minuten gesperrt, nach 10 falschen Codes für ein Konto innerhalb von 24 Stunden eine Stunde lang – auch von anderen Geräten aus. Ein Code gilt nur noch 30 Sekunden vor und nach seinem Zeitfenster.',
                'text_en' => 'Security, two-factor sign-in: the code from the authenticator app can no longer be guessed an unlimited number of times. After 5 wrong codes from one device, entry is blocked for 15 minutes; after 10 wrong codes for one account within 24 hours, for one hour – from any device. A code is now only accepted 30 seconds before and after its time step.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Sicherheit, Buchcover: Die Cover-Abfrage für Bücher (ISBN) gibt nur noch echte Bilder (JPEG, PNG, GIF, WebP) weiter. Bisher konnte eine präparierte Datei von einer der erlaubten Bildquellen als Seite unter der Adresse von ValuSafe erscheinen.',
                'text_en' => 'Security, book covers: the cover lookup for books (ISBN) now only passes on real images (JPEG, PNG, GIF, WebP). Previously a crafted file from one of the permitted image sources could appear as a page under the ValuSafe address.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Sicherheit, Belege: Ein Beleg lässt sich nur noch über den Löschen-Knopf entfernen. Bisher genügte ein präparierter Link, den ein angemeldeter Benutzer anklickt.',
                'text_en' => 'Security, receipts: a receipt can now only be removed with the delete button. Previously a crafted link clicked by a signed-in user was enough.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Sicherheit, Installationspaket: Die Ordner für Sicherungen (backups) und Protokolle (logs) bringen jetzt eine Sperre mit, die den Abruf über das Web verhindert – auch im Docker-Betrieb. Der Gesundheitscheck prüft diese Sperre direkt.',
                'text_en' => 'Security, installation package: the folders for backups and logs now ship with a lock that prevents access via the web – in Docker as well. The health check tests this lock directly.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Sicherheit, Passwort vergessen: Der Link in der Mail zum Zurücksetzen des Passworts verwendet jetzt eine fest eingestellte Adresse (APP_URL in config.php). Bisher kam sie aus der Anfrage, und ein Angreifer konnte einen Link auf seinen eigenen Server in die echte Mail schmuggeln. Ohne APP_URL bietet die Seite das Zurücksetzen per Mail nicht an. Der Setup-Assistent trägt die Adresse bei neuen Installationen ein; bei Docker steht sie in der .env.',
                'text_en' => 'Security, forgotten password: the link in the password reset e-mail now uses a fixed address (APP_URL in config.php). Previously it was taken from the request, so an attacker could slip a link to their own server into the genuine e-mail. Without APP_URL the page does not offer resetting by e-mail. The setup wizard enters the address on new installations; with Docker it is set in the .env file.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Raumverwaltung: Räume, Standorte und Positionen mit Apostroph im Namen (z. B. „Kinder\'s Zimmer“) lassen sich wieder löschen – der Papierkorb-Knopf tat bei solchen Namen nichts. Die Fenster lassen sich per Tastatur bedienen: Der Fokus springt beim Öffnen ins erste Feld, Esc schließt, danach steht der Fokus wieder auf dem auslösenden Knopf. Schließen-, Bearbeiten- und Löschen-Knöpfe werden von Bildschirmlesern benannt.',
                'text_en' => 'Room management: rooms, locations and positions with an apostrophe in their name (e.g. "Children\'s room") can be deleted again – the bin button did nothing for such names. The dialogs can be used with the keyboard: focus moves to the first field on opening, Esc closes, and focus then returns to the button that opened it. Close, edit and delete buttons are named for screen readers.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Rückfragen vor dem Löschen: Der Knopf „Abbrechen“ hat jetzt den Fokus. Ein versehentlich gedrücktes Enter bricht ab, statt einen Gegenstand, Beleg oder eine Sicherung zu löschen.',
                'text_en' => 'Confirmation before deleting: the Cancel button now has the focus. Pressing Enter by mistake cancels instead of deleting an item, receipt or backup.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Übersetzungen: Die Hinweise zur Schadenfall-Bereitschaft im Dashboard („Inventar vervollständigen“ und zwei weitere) und „PDF-Dokument“ im Hilfefenster waren in jeder Sprache deutsch. Sie erscheinen jetzt in allen zehn Sprachen.',
                'text_en' => 'Translations: the claim-readiness hints on the dashboard ("Complete your inventory" and two more) and "PDF document" in the help window were German in every language. They now appear in all ten languages.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Programmname: Seitentitel, PDF-Exporte, Versicherungs-Export und die öffentliche Ansicht nennen jetzt „ValuSafe“ statt des alten Namens „Wertsachen-Inventarverwaltung“.',
                'text_en' => 'Product name: page titles, PDF exports, the insurance export and the public view now say "ValuSafe" instead of the old name "Wertsachen-Inventarverwaltung".'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Handbücher auf Stand 4.5: Benutzerhandbuch, Erweitertes Handbuch und Facts & Features beschreiben jetzt Schadensfall-Assistent, Seriennummer, Zuschneiden und Drehen, Fotos und Belege als ZIP, Zwei-Faktor-Anmeldung für alle, die neu gegliederten Einstellungen, zehn Sprachen und die Datenübernahme aus einer früheren Version. Der Abschnitt Barrierefreiheit beschreibt ehrlich den heutigen Stand – volle WCAG 2.1 AA ist für Version 5.0 geplant.',
                'text_en' => 'Manuals updated to 4.5: the user manual, the extended manual and Facts & Features now cover the claims assistant, serial numbers, cropping and rotating, photos and receipts as a ZIP, two-factor sign-in for everyone, the reorganised settings, ten languages and data takeover from an earlier version. The accessibility section now describes the current state honestly – full WCAG 2.1 AA is planned for version 5.0.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Farbschema Midnight: Die Fläche zum Hochladen von Bildern beim Bearbeiten war fest weiß, die helle Schrift darauf kaum lesbar. Im Schema Glass sind die blauen Zahlen der Statistikleiste etwas dunkler und damit besser lesbar.',
                'text_en' => 'Midnight colour scheme: the image upload area when editing was fixed white, so the light text on it was hard to read. In the Glass scheme the blue figures in the statistics bar are slightly darker and easier to read.'
            ]
        ]
    ],
    [
        'version' => '4.5.1',
        'date' => '2026-10-17',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Vorsorge für Sprachen mit Apostroph (z. B. Französisch, Italienisch, Irisch): Ein Apostroph in einer Übersetzung konnte einzelne Knöpfe und Rückfragen außer Kraft setzen – beim Löschen von Gegenständen, Dokumenten, Benutzern und Sicherungen, beim Hochladen von Bildern und in der Statistik. Die Texte werden jetzt so eingebunden, dass jedes Zeichen sicher ist. In den derzeitigen Übersetzungen trat der Fehler noch nicht auf.',
                'text_en' => 'Safeguard for languages with apostrophes (e.g. French, Italian, Irish): an apostrophe in a translation could disable individual buttons and confirmation prompts – when deleting items, documents, users and backups, when uploading images and in the statistics. The texts are now embedded so that every character is safe. The current translations were not yet affected.'
            ]
        ]
    ],
    [
        'version' => '4.5',
        'date' => '2026-10-16',
        'entries' => [
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Neue Sprache: Irisch (Gaeilge), vorerst als Beta – die Übersetzung wird gerade von Muttersprachlern gegengelesen. ValuSafe spricht jetzt zehn Sprachen – auswählbar auf der Anmeldeseite und in den Einstellungen.',
                'text_en' => 'New language: Irish (Gaeilge), for now as a beta – native speakers are currently reviewing the translation. ValuSafe now speaks ten languages – selectable on the sign-in page and in the settings.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Einstellungen: Die Sprache wählen Sie jetzt in einer Auswahlliste in der Kachel „Darstellung“ zusammen mit dem Farbschema. Die eigene Sprachkachel mit zehn Knöpfen entfällt, die Seite ist dadurch deutlich kompakter. Jede Sprache steht in ihrer eigenen Schreibweise in der Liste; gewechselt wird mit „Anwenden“.',
                'text_en' => 'Settings: you now choose the language from a drop-down list in the "Appearance" card, together with the colour scheme. The separate language card with ten buttons is gone, which makes the page considerably more compact. Each language is listed in its own spelling; click "Apply" to switch.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Einstellungen neu geordnet: vier Abschnitte mit Überschrift – Darstellung, Mein Konto, Meine Daten und Administration. Profilbild, Standard-Ersteller und Sitzung stehen in der Kachel „Profil“, Passwort und Zwei-Faktor-Anmeldung in „Anmeldung“. Die Farbschemata stehen in einer Reihe, Kacheln einer Reihe sind gleich hoch.',
                'text_en' => 'Settings reorganised into four sections with headings: Appearance, My account, My data and Administration. Profile picture, default creator and session are in the "Profile" card, password and two-factor sign-in in "Sign-in". The colour schemes sit in one row, and cards in a row have the same height.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Tabellenansicht: Ein Klick auf eine Zeile oder den Namen zeigt den Gegenstand jetzt rechts im Detailbereich, wie in der Kartenansicht. Bisher stand dort nur „Gegenstand auswählen“. Auch per Tastatur (Name anwählen, Enter).',
                'text_en' => 'Table view: clicking a row or the name now shows the item in the detail panel on the right, as in the card view. Until now it only said "Select item". Also works with the keyboard (focus the name, press Enter).'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Daten aus einer früheren ValuSafe-Version übernehmen: Neuer Menüpunkt unter Restore. Sicherung der alten Version (Datenbank und optional Bilder/Belege) auswählen oder hochladen, prüfen lassen, Vorschau ansehen, mit einem Klick übernehmen. Danach wird die Datenbank automatisch auf den neuen Stand gebracht. Möglich mit Sicherungen ab ValuSafe 4.4.',
                'text_en' => 'Import data from an earlier ValuSafe version: new menu item under Restore. Select or upload the backup of the old version (database and optionally photos/receipts), have it checked, look at the preview and import it with one click. The database is then brought up to date automatically. Works with backups from ValuSafe 4.4 onwards.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Wiederherstellen: Datensätze mit Hochkomma im Text (z. B. „Kinder\'s Rad“) gingen beim Einspielen einer Datenbanksicherung still verloren. Jetzt wird alles vollständig eingespielt, Fehler werden angezeigt, und nach dem Einspielen laufen die Datenbank-Aktualisierungen.',
                'text_en' => 'Restore: records with an apostrophe in the text (e.g. "Children\'s bike") were silently lost when restoring a database backup. Everything is now restored completely, errors are shown, and database updates run afterwards.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Sicherung: Die Dateien-Sicherung enthält jetzt auch die Profilbilder der Benutzer. Bisher fehlten sie und gingen beim Wiederherstellen oder Umziehen verloren.',
                'text_en' => 'Backup: the files backup now also contains the users\' profile pictures. Until now they were missing and got lost when restoring or moving.'
            ]
        ]
    ],
    [
        'version' => '4.4.1',
        'date' => '2026-10-13',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Barrierefreiheit: Alle Formularfelder sind jetzt mit ihrer Beschriftung verbunden – Bildschirmleser nennen bei jedem Feld, wofür es ist (vor allem im Verwaltungsbereich: Versicherungen, Räume, Benutzer, Rechteverwaltung). Die Farbschemata in den Einstellungen lassen sich per Tastatur auswählen (Tab, Pfeiltasten).',
                'text_en' => 'Accessibility: all form fields are now linked to their labels – screen readers announce what each field is for (especially in the admin area: insurance, rooms, users, permissions). The themes in the settings can be chosen with the keyboard (Tab, arrow keys).'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Barrierefreiheit: Bessere Kontraste in den Farbschemata Cloud, Ocean, Midnight, Mint, Glass, Navy und Emerald – Hinweistexte und Akzentfarben sind besser lesbar, Eingabefelder haben einen deutlich sichtbaren Rahmen.',
                'text_en' => 'Accessibility: better contrast in the Cloud, Ocean, Midnight, Mint, Glass, Navy and Emerald themes – hint texts and accent colours are easier to read, input fields have a clearly visible border.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Bilder zuschneiden und drehen: Beim Anlegen, in der Schnellerfassung und beim Bearbeiten gibt es unter dem Foto den Knopf „Zuschneiden / drehen“. Rahmen frei oder im Format 1:1 bzw. 4:3 setzen, in 90-Grad-Schritten drehen – auch per Tastatur. Beim Bearbeiten lassen sich so auch schon gespeicherte Bilder nachträglich gerade rücken.',
                'text_en' => 'Crop and rotate images: when adding, in quick add and when editing there is a "Crop / rotate" button below the photo. Set the frame freely or as 1:1 or 4:3 and rotate in 90-degree steps – also with the keyboard. When editing, images that are already saved can be straightened afterwards, too.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Schadensfall: Neuer Knopf „Fotos und Belege (ZIP)“ in der Zusammenfassung. Er lädt alle Fotos und Rechnungen der betroffenen Gegenstände in einer Datei herunter – ein Ordner je Gegenstand, dazu eine Übersicht als Text und als Tabelle für die Versicherung.',
                'text_en' => 'Damage claim: new "Photos and receipts (ZIP)" button in the summary. It downloads all photos and receipts of the affected items in one file – one folder per item, plus an overview as text and as a spreadsheet for the insurer.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Schadensfall löschen: Die Rückfrage erschien in manchen Browsern nicht, der Fall ließ sich dann nicht löschen. Sie kommt jetzt als eigenes Fenster der App.',
                'text_en' => 'Delete damage claim: in some browsers the confirmation did not appear and the claim could not be deleted. It now shows as the app\'s own dialog.'
            ]
        ]
    ],
    [
        'version' => '4.4',
        'date' => '2026-10-12',
        'entries' => [
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Schadensfall-Assistent: Nach einem Einbruch, Brand oder Wasserschaden legen Sie in drei Schritten einen Schadensfall an – was ist passiert, welche Gegenstände sind betroffen (gestohlen, zerstört, beschädigt), Zusammenfassung mit Kauf- und Zeitwert, Versicherungssumme und Selbstbeteiligung. Daraus entstehen eine Liste für die Polizei (mit Fotos, Seriennummern und Merkmalen) und eine Schadensmeldung für die Versicherung mit anpassbarem Begleitschreiben, beides druckbar oder als PDF. Neues Symbol in der linken Leiste.',
                'text_en' => 'Damage claim assistant: after a burglary, fire or water damage you create a damage claim in three steps – what happened, which items are affected (stolen, destroyed, damaged), and a summary with purchase and current value, sum insured and deductible. From this you get a list for the police (with photos, serial numbers and features) and a claim report for your insurer with an editable cover letter, both printable or as PDF. New icon in the left sidebar.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Neues Feld „Seriennummer“ beim Anlegen und Bearbeiten – für die Polizei nach einem Diebstahl das wichtigste Merkmal. Die Detailansicht zeigt sie an.',
                'text_en' => 'New "Serial number" field when adding and editing items – the most important detail for the police after a theft. The detail view shows it.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Gegenstand hinzufügen: Der Barcode wurde beim Anlegen nicht gespeichert (nur beim Bearbeiten).',
                'text_en' => 'Add item: the barcode was not saved when creating an item (only when editing).'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Datenbank: Nach dem Update bitte migrate.php aufrufen (Migration 014: Schadensfälle, Seriennummer, zwei neue Rechte). Bis dahin bleiben Seriennummer und Schadensfall ausgeblendet, alles andere läuft normal.',
                'text_en' => 'Database: after updating, please run migrate.php (migration 014: damage claims, serial number, two new permissions). Until then the serial number and damage claims stay hidden; everything else works normally.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Barrierefreiheit, Grundrahmen: Jede Seite hat einen Link „Zum Inhalt springen“ (erscheint mit der ersten Tab-Taste) und einen gekennzeichneten Hauptbereich. Alle Bedienelemente zeigen einen deutlichen Rahmen, wenn sie per Tastatur den Fokus haben – das Suchfeld oben und die Formularfelder zeigten bisher keinen.',
                'text_en' => 'Accessibility, basic framework: every page has a "Skip to content" link (appears with the first Tab key) and a marked main area. All controls show a clear outline when they have keyboard focus – the search field at the top and the form fields showed none until now.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Ältere Änderungen: die vollständige Liste steht unter https://palindrom.de/valusafe/'
            ]
        ]
    ]
];
