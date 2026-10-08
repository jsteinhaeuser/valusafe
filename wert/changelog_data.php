<?php
/**
 * changelog_data.php — Auslieferungsfassung
 *
 * Erzeugt am 08.10.2026 08:19 von bin/changelog_export.php.
 * NICHT von Hand aendern — Aenderungen gehoeren in die Projektchronik,
 * aus der diese Datei bei jedem Paketbau neu entsteht.
 *
 * Enthaelt die 5 juengsten Versionen mit anwenderrelevanten
 * Aenderungen. Die vollstaendige Liste steht unter
 * https://palindrom.de/valusafe/
 */

return [
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
            ]
        ]
    ],
    [
        'version' => '4.3.32',
        'date' => '2026-10-03',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Im persoenlichen Profil liessen sich die Farbthemen "Navy" und "Emerald" zwar anklicken, gespeichert wurde aber "Cloud". Die Pruefung der erlaubten Themen kannte die beiden juengsten nicht. In den Einstellungen war es schon immer richtig.',
                'text_en' => 'In the personal profile, the colour themes "Navy" and "Emerald" could be selected, but "Cloud" was saved instead. The check for allowed themes did not know the two newest ones. The settings page had always handled them correctly.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Die automatische Sicherung (cron_backup.php) nimmt Aufrufe ueber das Web nur noch mit dem BACKUP_TOKEN aus der config.php an. Fehlte er, galt bisher ein fest eingebauter Ersatzwert, der im Quellcode nachzulesen war. Der Setup-Assistent legt den Token immer an; wer seine config.php von Hand gebaut hat, sollte nachsehen, ob BACKUP_TOKEN darin steht.',
                'text_en' => 'The automatic backup (cron_backup.php) now accepts web requests only with the BACKUP_TOKEN from config.php. If the token was missing, a built-in fallback value applied that could be read in the source code. The setup wizard always creates the token; if you wrote your config.php by hand, check that it contains BACKUP_TOKEN.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Der Menuepunkt "Diagnose-Tools" im Verwaltungsbereich erscheint nur noch, wenn der Ordner Tools vorhanden ist. Das Installationspaket liefert ihn bewusst nicht aus, der Punkt fuehrte dort ins Leere.',
                'text_en' => 'The menu item "Diagnostic tools" in the admin area now appears only if the Tools folder exists. The installation package deliberately does not include it, so the item led nowhere.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Die Auswahl "Aktion" in der Uebersicht (Verbergen, Wieder anzeigen, Loeschen) zeigte in manchen Browsern, etwa Edge unter Windows, nur einen Eintrag - die uebrigen standen weiss auf weiss. Gefunden beim Windows-Test von Valu-Basic.',
                'text_en' => 'In some browsers, such as Edge on Windows, the "Action" drop-down in the overview (Hide, Show again, Delete) showed only one option - the others were white on white. Found while testing Valu-Basic on Windows.'
            ]
        ]
    ],
    [
        'version' => '4.3.31',
        'date' => '2026-10-01',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Die Uebersicht im Verwaltungsbereich, die Einstellungen und die Statistikseite zeigen die Zahl der Raeume jetzt als "Raeume" an. Bisher stand dort "Orte", und die kleinere Zahl daneben hiess "Raeume", obwohl sie die Standorte zaehlte - etwa "6 Orte · 1 Raeume · 1 Pos." bei sechs Raeumen und einem Standort. Jetzt: "6 Raeume · Standorte: 1 · Pos.: 1". Die Zahlen selbst waren immer richtig, nur falsch beschriftet. Zu tun ist nichts.',
                'text_en' => 'The admin overview, the settings and the statistics page now label the number of rooms as "Rooms". Previously it said "Locations", and the smaller number next to it was called "Rooms" although it counted the locations within rooms - for example "6 Locations · 1 Rooms · 1 Pos." for six rooms and one location. Now: "6 Rooms · Locations: 1 · Pos.: 1". The numbers themselves were always right, only the labels were wrong. No action needed.'
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
                'text' => 'Das Docker-Image beruht jetzt auf PHP 8.4 statt 8.2. PHP 8.2 erhaelt ab dem 31.12.2026 keine Sicherheitskorrekturen mehr. Wer ValuSafe mit Docker betreibt, baut das Image neu (im Ordner docker: docker compose build --pull, danach docker compose up -d). Datenbank, Fotos, Belege und Sicherungen liegen in eigenen Volumes und bleiben erhalten; die Zugangsdaten kommen wie bisher aus der .env. Wer ValuSafe bei einem Hoster betreibt, ist nicht betroffen - dort bestimmt der Hoster die PHP-Version; ValuSafe laeuft mit PHP 8.2 bis 8.4.',
                'text_en' => 'The Docker image is now based on PHP 8.4 instead of 8.2. PHP 8.2 receives no security fixes after 31 December 2026. If you run ValuSafe with Docker, rebuild the image (in the docker folder: docker compose build --pull, then docker compose up -d). Database, photos, receipts and backups live in their own volumes and are kept; credentials still come from the .env file. If ValuSafe runs at a web host, you are not affected - the host decides the PHP version; ValuSafe runs on PHP 8.2 to 8.4.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Unter Einstellungen -> "Datenschutz & meine Daten" funktionieren beide Knoepfe jetzt so, wie sie beschrieben sind. "Account loeschen" schlug bisher bei jedem Konto mit einer technischen Fehlermeldung fehl - das Konto blieb bestehen. "Daten herunterladen" lieferte zwar eine Datei, darin fehlten aber die Kontodaten und das Aktivitaetsprotokoll; nur die Gegenstaende waren enthalten. Jetzt enthaelt die Datei Konto, Gegenstaende und Aktivitaeten (ohne Passwort und Zugangscodes), und die Loeschung entfernt das Konto samt Profilbild, offenen Links zum Zuruecksetzen des Passworts und den gespeicherten Anmeldeversuchen. Gegenstaende bleiben erhalten und tragen weiter den Namen, der unter "Erstellt von" steht; Protokolleintraege bleiben ebenfalls, verweisen aber nicht mehr auf das Konto. Der Hinweis unter dem Knopf versprach bisher, die Gegenstaende wuerden keinem Benutzer mehr zugeordnet - das stimmte nicht und ist berichtigt (alle neun Sprachen). Dasselbe gilt jetzt auch, wenn ein Administrator einen Benutzer in der Benutzerverwaltung loescht. Wer frueher vergeblich versucht hat, sein Konto zu loeschen, kann es jetzt erneut tun.',
                'text_en' => 'Under Settings -> "Privacy & my data", both buttons now work as described. "Delete account" used to fail for every account with a technical error message - the account remained. "Download data" did produce a file, but it lacked the account data and the activity log; only the items were included. Now the file contains account, items and activities (without password and access codes), and deletion removes the account together with its profile picture, open password-reset links and stored login attempts. Items are kept and still carry the name shown under "Created by"; log entries are kept as well but no longer point to the account. The note below the button used to promise that items would no longer be assigned to any user - that was not true and has been corrected (all nine languages). The same now applies when an administrator deletes a user in user management. If you tried to delete your account before without success, you can now try again.'
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
                'text' => 'Das Installationspaket enthielt eine Datei .user.ini, die PHP-Fehlermeldungen im Browser anzeigen liess - mit Dateipfaden und Ausschnitten aus Datenbankabfragen. Sie war fuer die Entwicklung gedacht und haette nie ausgeliefert werden duerfen. Seit Version 4.3.25 schaltet ValuSafe diese Anzeige auf fast allen Seiten selbst ab und schreibt Fehler stattdessen in logs/php_errors.log; die Datei ist jetzt entfernt. Wer ValuSafe aus dem Paket installiert hat: bitte die Datei .user.ini im Installationsordner loeschen (sie ist im FTP-Programm oft erst sichtbar, wenn versteckte Dateien eingeblendet werden). Die Systemuebersicht im Verwaltungsbereich zeigt an, ob sie noch da ist.',
                'text_en' => 'The installation package contained a .user.ini file that made PHP show error messages in the browser - including file paths and fragments of database queries. It was meant for development and should never have been shipped. Since version 4.3.25 ValuSafe switches this display off by itself on almost all pages and writes errors to logs/php_errors.log instead; the file has now been removed. If you installed ValuSafe from the package, please delete the .user.ini file in the installation folder (FTP programs often show it only when hidden files are displayed). The system overview in the admin area shows whether it is still there.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Der Einrichtungsassistent prueft jetzt alle PHP-Erweiterungen, die ValuSafe braucht. Bisher fragte er nur nach drei; fehlte mbstring oder fileinfo beim Hoster, meldete er trotzdem alles gruen - danach brach die Liste mit einem Fehler ab oder jeder Bild-Upload schlug fehl. Beide sind jetzt Voraussetzung, cURL und EXIF werden als Empfehlung angezeigt. Bestehende Installationen betrifft das nicht: wo ValuSafe laeuft, sind beide vorhanden.',
                'text_en' => 'The setup wizard now checks every PHP extension ValuSafe needs. Previously it asked for only three; if mbstring or fileinfo was missing at the host, it still showed everything green - afterwards the list stopped with an error or every image upload failed. Both are now required; cURL and EXIF are shown as recommended. Existing installations are not affected: wherever ValuSafe runs, both are present.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Solange das Fenster "Hilfe & Informationen" offen ist, ist in der Seitenleiste jetzt das (i) hervorgehoben. Bisher blieb das Symbol der Seite darunter markiert, etwa das Zahnrad der Einstellungen - es sah aus, als haette der Klick nicht gewirkt. Beim Schliessen kehrt die Markierung zurueck.',
                'text_en' => 'While the "Help & information" window is open, the (i) in the sidebar is now highlighted. Previously the icon of the page underneath stayed highlighted, for example the gear of the settings - it looked as if the click had not worked. When the window closes, the highlight returns.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'ValuSafe laesst sich jetzt auch in einem Unterordner betreiben, etwa unter beispiel.de/valusafe/, ohne dass die App-Funktion leidet. Bisher gingen Service Worker, App-Manifest, der QR-Code auf der Seite "Oeffentlicher Link" und zwei Links davon aus, dass ValuSafe direkt im Wurzelverzeichnis der Domain liegt; in einem Unterordner liess sich ValuSafe nicht als App installieren, und der Zwischenspeicher haette Fotos und Belege mit erfasst. Wer im Wurzelverzeichnis installiert hat, merkt keinen Unterschied.',
                'text_en' => 'ValuSafe can now run in a subfolder, such as example.com/valusafe/, without losing its app features. Previously the service worker, the app manifest, the QR code on the "Public link" page and two links assumed that ValuSafe sits in the root of the domain; in a subfolder ValuSafe could not be installed as an app, and the cache would have picked up photos and receipts as well. If you installed in the root, nothing changes for you.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Ältere Änderungen: die vollständige Liste steht unter https://palindrom.de/valusafe/'
            ]
        ]
    ]
];
