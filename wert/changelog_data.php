<?php
/**
 * changelog_data.php — Auslieferungsfassung
 *
 * Erzeugt am 16.10.2026 11:17 von bin/changelog_export.php.
 * NICHT von Hand aendern — Aenderungen gehoeren in die Projektchronik,
 * aus der diese Datei bei jedem Paketbau neu entsteht.
 *
 * Enthaelt die 5 juengsten Versionen mit anwenderrelevanten
 * Aenderungen. Die vollstaendige Liste steht unter
 * https://palindrom.de/valusafe/
 */

return [
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
            ]
        ]
    ],
    [
        'version' => '4.3.34',
        'date' => '2026-10-11',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Beim Löschen mehrerer Gegenstände über die Auswahl blieben weitere Fotos der Gegenstände auf dem Server liegen. Sie werden jetzt mit gelöscht.',
                'text_en' => 'When deleting several items via the selection, additional photos of the items stayed on the server. They are now deleted as well.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Schnellerfassung: Der runde Plus-Knopf lag auf dem Smartphone über dem Preisfeld und führte nur wieder zur Schnellerfassung. Auf den Erfassungsseiten selbst erscheint er nicht mehr.',
                'text_en' => 'Quick entry: on smartphones the round plus button covered the price field and only led back to quick entry. It no longer appears on the entry pages themselves.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Zwei-Faktor-Anmeldung für alle: Jedes Konto kann die Anmeldung mit Authenticator-App jetzt selbst einrichten (Einstellungen → Zwei-Faktor verwalten). Bisher ging das nur für Administratoren. Wer sein Handy und die Backup-Codes verloren hat, dem setzt der Administrator die Zwei-Faktor-Anmeldung in der Benutzerverwaltung zurück. Auf Demo-Konten lässt sich die Einrichtung zusammen mit „Eigenes Passwort ändern“ abschalten.',
                'text_en' => 'Two-factor sign-in for everyone: every account can now set up sign-in with an authenticator app itself (Settings → Manage two-factor). Until now only administrators could. If someone has lost their phone and backup codes, the administrator resets two-factor sign-in in user management. On demo accounts the setup can be switched off together with "Change own password".'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Benutzerverwaltung: Beim Bearbeiten eines Kontos konnte die Passwortverwaltung des Browsers das Feld „Neues Passwort“ unbemerkt mit dem eigenen gespeicherten Passwort füllen – beim Speichern bekam das bearbeitete Konto dann dieses Passwort und konnte sich nicht mehr anmelden. Das Feld wird jetzt nicht mehr automatisch ausgefüllt.',
                'text_en' => 'User management: when editing an account, the browser\'s password manager could silently fill the "New password" field with your own saved password – saving then gave the edited account that password and it could no longer sign in. The field is no longer filled automatically.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Benutzerverwaltung: Der Hinweis bei „Alle Gegenstände sehen“ sagt jetzt, dass er nur wirkt, wenn unter „Öffentlicher Link“ die Einstellung „Benutzer sehen nur eigene Gegenstände“ eingeschaltet ist. Bisher klang es, als genüge das Häkchen allein.',
                'text_en' => 'User management: the hint at "See all items" now says that it only takes effect when "Users see only their own items" is switched on under "Public link". Until now it sounded as if the checkbox alone was enough.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'QR-Codes: Bei manchen Textlängen erschien kein QR-Code – bei der Einrichtung der Zwei-Faktor-Anmeldung je nach Benutzername, beim öffentlichen Link je nach Adresse der Installation. Ursache ist ein Fehler der eingesetzten QR-Bibliothek; sie weicht jetzt bei Bedarf auf eine andere Fehlerkorrekturstufe aus.',
                'text_en' => 'QR codes: for some text lengths no QR code appeared – when setting up two-factor sign-in depending on the user name, for the public link depending on the installation\'s address. The cause is a bug in the QR library used; it now falls back to another error correction level when needed.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Öffentlicher Link: Der Hinweis „Erstellt mit …“ unten auf der öffentlichen Seite verwies auf eine Instanz des Entwicklers statt auf die Projektseite palindrom.de/valusafe.',
                'text_en' => 'Public link: the "Created with …" note at the bottom of the public page pointed to one of the developer\'s instances instead of the project page palindrom.de/valusafe.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Benutzerverwaltung: Die Seite enthielt im Quelltext zu jedem Konto den Passwort-Hash und, falls eingerichtet, das Geheimnis der Zwei-Faktor-Anmeldung samt Backup-Codes. Sichtbar war das nur für den Administrator, der die Seite öffnen darf - es gehört trotzdem nicht in den Browser. Jetzt nur noch die Angaben, die das Bearbeiten-Fenster braucht.',
                'text_en' => 'User management: the page source contained the password hash of every account and, where set up, the two-factor secret and backup codes. Only the administrator allowed to open the page could see it - it still does not belong in the browser. Now only the data the edit dialog needs.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Übersetzungen: Rund 45 Meldungen im Browser waren in jeder Sprache deutsch – die Bildverwaltung beim Bearbeiten, der Hinweis auf einen gespeicherten Entwurf, die Barcode- und ISBN-Suche und das Hilfe-Fenster. Sie erscheinen jetzt in allen neun Sprachen. Die Seite meldet Bildschirmlesern außerdem die gewählte Sprache statt immer der Sprache der Installation, und die Hilfe im Verwaltungsbereich ist auf Englisch nicht mehr deutsch.',
                'text_en' => 'Translations: about 45 messages in the browser were German in every language – image management when editing, the notice about a saved draft, barcode and ISBN lookup and the help window. They now appear in all nine languages. The page also tells screen readers the chosen language instead of always the installation language, and the help in the admin area is no longer German when English is selected.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Gegenstand bearbeiten: Nach der Barcode-Suche versprach „Bild übernehmen“, das Cover werde nach dem Speichern übernommen – es ging aber verloren. Jetzt wird es sofort zu den Bildern des Gegenstands hochgeladen.',
                'text_en' => 'Edit item: after a barcode lookup, "Use image" promised the cover would be added after saving – but it was lost. It is now uploaded to the item\'s images straight away.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Sicherheit: Die Detailansicht neben der Übersicht übernahm Name, Raum, Seriennummer und Notizen eines Gegenstands ungefiltert in die Seite. Ein Benutzer mit Bearbeitungsrecht konnte so Skriptcode in einen Gegenstandsnamen schreiben, der bei jedem ausgeführt wurde, der die Karte anklickte – auch beim Administrator. Die Werte werden jetzt als Text dargestellt.',
                'text_en' => 'Security: the detail view next to the overview inserted an item\'s name, room, serial number and notes into the page unfiltered. A user with edit rights could put script code into an item name that ran for anyone clicking the card – including the administrator. The values are now shown as plain text.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Bedienung per Tastatur: Die Karten der Übersicht ließen sich nur mit der Maus öffnen. Jetzt erreicht man sie mit der Tabulatortaste und öffnet die Detailansicht mit Enter; der Fokus springt dabei in die Detailansicht.',
                'text_en' => 'Keyboard use: the cards in the overview could only be opened with the mouse. They can now be reached with the Tab key and opened with Enter; focus then moves to the detail view.'
            ]
        ]
    ],
    [
        'version' => '4.3.33',
        'date' => '2026-10-09',
        'entries' => [
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Die Schnellerfassung ist wieder erreichbar: Auf dem Handy öffnet der runde Plus-Knopf jetzt die Schnellerfassung (Foto, Name, Speichern - Raum und Kategorie bleiben für den nächsten Gegenstand stehen), am Rechner weiter das vollständige Formular. Wer ValuSafe als App installiert hat, findet sie außerdem beim langen Druck auf das App-Symbol. Die Schnellerfassung setzte bisher heimlich das heutige Datum als Kaufdatum - das Feld bleibt jetzt leer. Bei iPhone-Fotos (HEIC), die der Browser nicht anzeigen kann, erscheint statt einer leeren Vorschau ein Hinweis; umgewandelt wird beim Speichern. Alle Texte der Seite sind übersetzt.',
                'text_en' => 'Quick entry is reachable again: on phones the round plus button now opens quick entry (photo, name, save - room and category stay selected for the next item), on computers it still opens the full form. If you installed ValuSafe as an app, you also find it by long-pressing the app icon. Quick entry used to set today\'s date as purchase date without asking - the field now stays empty. For iPhone photos (HEIC) the browser cannot display, a note appears instead of an empty preview; the photo is converted when saved. All texts on the page are translated.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Leser (Rolle "Nur lesen") konnten Gegenstände über die direkte Adresse der Seiten "Hinzufügen", "Schnellerfassung" und "Bearbeiten" anlegen und ändern - die Seiten prüften nur die Anmeldung, nicht die Rechte. Jetzt gelten die Rechte "Neuen Gegenstand anlegen" bzw. "Gegenstand bearbeiten" aus der Rechteverwaltung; die Meldung "Zugriff verweigert" ist übersetzt.',
                'text_en' => 'Viewers (role "read only") could create and change items through the direct address of the pages "Add", "Quick entry" and "Edit" - the pages only checked the login, not the permissions. Now the permissions "Create new item" and "Edit item" from the permissions settings apply; the "Access denied" message is translated.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Die Einstellung "Benutzer sehen nur eigene Gegenstände" wirkte bisher nur in der Listenansicht. Über die direkte Adresse konnten betroffene Benutzer fremde Gegenstände trotzdem öffnen, bearbeiten, löschen und deren Bilder verwalten, und Exporte, QR-Codes, Raumansicht, Dashboard und Versicherungsübersicht enthielten alle Gegenstände. Jetzt gilt die Einstellung überall. Wer nur eigene Gegenstände sieht, legt Gegenstände außerdem immer unter dem eigenen Namen an.',
                'text_en' => 'The setting "Users see only their own items" only applied to the list view so far. Through the direct address, affected users could still open, edit and delete other people\'s items and manage their images, and exports, QR codes, the room view, the dashboard and the insurance overview contained all items. The setting now applies everywhere. Users who only see their own items also always create items under their own name.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Einige Rechte aus der Rechteverwaltung wurden nicht beachtet: "Mehrere Gegenstände löschen" (Löschen über die Auswahl ging mit dem Recht zum Bearbeiten), die Export-Rechte (PDF, Excel/CSV, HTML, Versicherungsexport, QR-Codes) und die Bilder-Rechte (Hochladen, Löschen). Jetzt gelten sie; das Export-Menü zeigt nur noch erlaubte Exporte.',
                'text_en' => 'Some permissions from the permissions settings were not enforced: "Delete multiple items" (deleting via the selection only required the edit permission), the export permissions (PDF, Excel/CSV, HTML, insurance export, QR codes) and the image permissions (upload, delete). They now apply; the export menu only shows permitted exports.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Weitere Stellen folgen jetzt der eingestellten Sprache statt fest Deutsch: QR-Codes, Dokumentverwaltung eines Gegenstands, Tabellenspalte im Aktivitätsprotokoll, Feld "Aktueller Wert" beim Bearbeiten, Vorlesetexte des Suchfilters und die Abschnitte der Rechteverwaltung (nach einer Neuinstallation waren sie deutsch). Versicherungs-Export und Übergabeprotokoll sind weiterhin deutsch.',
                'text_en' => 'More places now follow the selected language instead of always German: QR codes, an item\'s document management, the table column in the activity log, the "Current value" field when editing, screen reader texts of the search filter and the sections of the permissions settings (after a fresh install they were German). The insurance export and the handover report are still German.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Spaltennamen sind jetzt überall gleich und übersetzt: Tabellenkopf, Spalten-Manager und Formular verwenden dieselben Namen. Bisher zeigte der Tabellenkopf für einige Spalten Rohnamen wie "Custom1", und Spalten, die man im Spalten-Manager umbenannt hatte, erschienen im Tabellenkopf weiter unter dem alten Namen. Der Spalten-Manager selbst, die Auswahlleiste für mehrere Gegenstände und die Vorlesetexte der Übersicht folgen jetzt ebenfalls der eingestellten Sprache.',
                'text_en' => 'Column names are now consistent and translated everywhere: table header, column manager and form use the same names. Previously the table header showed raw names such as "Custom1" for some columns, and columns renamed in the column manager kept their old name in the table header. The column manager itself, the selection bar for several items and the screen reader texts of the overview now also follow the selected language.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Die Anmeldeseite war fest deutsch. Sie erscheint jetzt in der Sprache des Browsers (eine der neun Sprachen, sonst Deutsch) und hat unten eine Sprachwahl. Außerdem merkt sich ValuSafe die in den Einstellungen gewählte Sprache jetzt wirklich: Sie wurde zwar gespeichert, beim nächsten Anmelden aber nicht geladen - jede neue Sitzung begann auf Deutsch.',
                'text_en' => 'The login page was German only. It now appears in the browser\'s language (one of the nine languages, otherwise German) and has a language choice at the bottom. In addition, ValuSafe now really remembers the language chosen in the settings: it was saved, but not loaded at the next login - every new session started in German.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Auch der Verwaltungsbereich folgt jetzt der eingestellten Sprache an Stellen, die bisher fest deutsch waren: Sicherung (Kacheln, Liste, Meldungen), Versicherungsverwaltung (Übersicht, Formular, Zuordnen von Gegenständen), Räume/Standorte/Positionen, Benutzerverwaltung, Übersichtskacheln, Seitenleiste und Hilfe-Fenster. Der Verwaltungsbereich ist wie bisher auf Deutsch und Englisch übersetzt. Datenbankfehler zeigen dort keinen technischen Text mehr an, sondern verweisen auf das Fehlerprotokoll. Außerdem führte "Versicherungen" in der unteren Leiste auf dem Handy für Editoren und Leser auf eine gesperrte Seite; der Knopf öffnet jetzt dieselbe Seite wie in der Seitenleiste. "Statistiken" und "Galerie" erscheinen in den Leisten nur noch für Administratoren - für alle anderen endeten sie bisher ebenfalls bei "Zugriff verweigert".',
                'text_en' => 'The admin area now also follows the selected language in places that were German only: backup (tiles, list, messages), insurance management (overview, form, assigning items), rooms/locations/positions, user management, overview tiles, sidebar and help window. As before, the admin area is translated into German and English. Database errors there no longer show technical text but point to the error log. In addition, "Insurance" in the bottom bar on phones led editors and viewers to a locked page; the button now opens the same page as in the sidebar. "Statistics" and "Gallery" now appear in the bars for administrators only - for everyone else they also ended at "access denied".'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'In der Rechteverwaltung lassen sich jetzt "Eigenes Passwort aendern" und das neue Recht "Eigenes Konto loeschen" fuer Editoren und Leser abschalten. Gedacht fuer Konten, die sich mehrere Personen teilen, etwa eine oeffentliche Demo: Bisher konnte dort jeder das gemeinsame Passwort aendern oder das Konto ueber "Account loeschen" ganz entfernen und damit alle anderen aussperren. Ist ein Recht abgeschaltet, zeigt die Einstellungsseite statt des Formulars einen Hinweis. Voreingestellt bleibt beides erlaubt - wer nichts umstellt, merkt keinen Unterschied. Nach dem Update einmal migrate.php aufrufen.',
                'text_en' => 'In the permissions settings, "Change own password" and the new permission "Delete own account" can now be switched off for editors and viewers. This is meant for accounts shared by several people, such as a public demo: until now anyone could change the shared password there, or remove the account entirely via "Delete account", and lock everyone else out. If a permission is switched off, the settings page shows a note instead of the form. By default both stay allowed - if you change nothing, nothing changes. Run migrate.php once after the update.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Wer eine andere Sprache als Deutsch eingestellt hat, sah an mehreren Stellen trotzdem deutsche Texte: im Export-Menue (CSV-Datei, PDF Detailliert, Uebergabeprotokoll ...), in den Hinweisen der Seitenleiste, in der unteren Leiste auf dem Handy, im Detailbereich rechts neben der Liste, im Fenster "Hilfe & Informationen" und in den Einstellungen bei Profilbild und Passwort samt allen Meldungen. Diese Texte folgen jetzt der eingestellten Sprache (alle neun). Ausserdem fuehrte "Einstellungen" in der unteren Leiste auf dem Handy auf eine Seite, die es nicht gibt; der Knopf oeffnet jetzt die Einstellungen.',
                'text_en' => 'If you use a language other than German, several places still showed German text: the export menu (CSV file, PDF detailed, handover report ...), the tooltips of the sidebar, the bottom bar on phones, the detail panel next to the list, the "Help & information" window and, in the settings, everything around profile picture and password including all messages. These texts now follow the selected language (all nine). In addition, "Settings" in the bottom bar on phones led to a page that does not exist; the button now opens the settings.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Ältere Änderungen: die vollständige Liste steht unter https://palindrom.de/valusafe/'
            ]
        ]
    ]
];
