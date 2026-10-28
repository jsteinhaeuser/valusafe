<?php
/**
 * changelog_data.php — Auslieferungsfassung
 *
 * Erzeugt am 28.10.2026 17:06 von bin/changelog_export.php.
 * NICHT von Hand aendern — Aenderungen gehoeren in die Projektchronik,
 * aus der diese Datei bei jedem Paketbau neu entsteht.
 *
 * Enthaelt die 5 juengsten Versionen mit anwenderrelevanten
 * Aenderungen. Die vollstaendige Liste steht unter
 * https://palindrom.de/valusafe/
 */

return [
    [
        'version' => '4.6.1',
        'date' => '2026-10-28',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Zeitwert: Überall gilt jetzt dieselbe Regel - der aktuelle Wert, wenn einer über 0 eingetragen ist, sonst der Kaufpreis. Bisher zählte ein Zeitwert von 0 an manchen Stellen als Wert 0, an anderen als „nicht bewertet“. Betroffen waren Dashboard (Zeitwert-Kachel, höchster Zeitwert, wertvollster Gegenstand, Top 5, Kategorien), Suchleiste, Versicherung, Versicherungsexport und Schadensfall mit Dokument und Belegen.',
                'text_en' => 'Current value: the same rule now applies everywhere - the current value if one above 0 is entered, otherwise the purchase price. Previously a current value of 0 counted as 0 in some places and as “not valued” in others. This affected the dashboard (current value tile, highest current value, most valuable item, top 5, categories), the search bar, insurance, the insurance export and the damage claim with its document and receipts.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Dashboard: Der Trend vergleicht jetzt den Zeitwert heute mit dem Zeitwert zum Stichtag und steht unter der Zeitwert-Kachel. Bisher verglich er Kaufpreise mit alten Zeitwerten.',
                'text_en' => 'Dashboard: the trend now compares today\'s current value with the current value on the reference date and appears under the current value tile. Previously it compared purchase prices with old current values.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Suchleiste: Verborgene Gegenstände werden nur noch mitgezählt, wenn die Liste sie auch zeigt. Bisher stand dort z. B. „7 Einträge gefunden“ über einer Liste mit 6.',
                'text_en' => 'Search bar: hidden items are only counted when the list shows them as well. Previously it could say e.g. “7 entries found” above a list of 6.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Kopfzeile: Unter „ValuSafe“ steht jetzt klein die installierte Version.',
                'text_en' => 'Header: the installed version is now shown in small print below “ValuSafe”.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Änderungsverlauf: Leere Felder (z. B. kein Kaufdatum) werden wieder als leer angezeigt, statt eine Warnung im Fehlerprotokoll zu erzeugen.',
                'text_en' => 'Change history: empty fields (e.g. no purchase date) are shown as empty again instead of producing a warning in the error log.'
            ]
        ]
    ],
    [
        'version' => '4.6',
        'date' => '2026-10-28',
        'entries' => [
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Sicherheit, Anmeldung: Wird ein Konto gelöscht, herabgestuft oder sein Passwort geändert, gilt das sofort auch für bereits angemeldete Sitzungen. Bisher arbeitete eine offene Sitzung mit den alten Rechten weiter. Nach einem Passwortwechsel bleibt nur die Sitzung angemeldet, in der er geschah. Jede Anmeldung endet außerdem spätestens nach 12 Stunden.',
                'text_en' => 'Security, sign-in: when an account is deleted, downgraded or its password is changed, this now applies immediately to sessions that are already signed in. Previously an open session kept working with the old rights. After a password change only the session in which it was made stays signed in. Every sign-in also ends after 12 hours at the latest.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Sicherheit, Wiederherstellen: Eine Datenbanksicherung wird vor dem Einspielen geprüft und nur angenommen, wenn sie ausschließlich enthält, was ValuSafe selbst in Sicherungen schreibt. Benutzer, SuperAdmin-Einstellungen und das Sicherheitsprotokoll holt nur der SuperAdmin aus einer Sicherung zurück; ein Admin erhält sie unverändert. Abgelehnte Uploads der Datenübernahme bleiben nicht mehr im Ordner backups/ liegen.',
                'text_en' => 'Security, restore: a database backup is checked before it is restored and only accepted if it contains nothing but what ValuSafe itself writes into backups. Users, SuperAdmin settings and the security log can only be restored by the SuperAdmin; for an admin they stay as they are. Rejected uploads from the data import no longer remain in the backups/ folder.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Sicherheit, Zwei-Faktor-Anmeldung: Die Backup-Codes werden nur noch verschlüsselt (gehasht) gespeichert und einmal angezeigt, direkt nach dem Erzeugen. Wer sie nicht mehr hat, erzeugt mit einem Code aus der Authenticator-App neue; die alten werden damit ungültig. Vorhandene Codes gelten weiter.',
                'text_en' => 'Security, two-factor sign-in: backup codes are now stored only in hashed form and shown once, right after they are created. If you no longer have them, create new ones with a code from your authenticator app; the old ones then stop working. Existing codes keep working.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Sicherheit, kleinere Punkte: Das Anmelde-Cookie trägt jetzt auf jeder Installation HttpOnly, SameSite und bei HTTPS Secure. Der CSV-Export schützt vor Formeln in Zellen (z. B. ein Name, der mit = beginnt). Vom Admin vergebene Passwörter brauchen mindestens 8 Zeichen. Die Anmeldung verrät über die Antwortzeit nicht mehr, ob es einen Benutzernamen gibt.',
                'text_en' => 'Security, smaller items: the sign-in cookie now carries HttpOnly, SameSite and, with HTTPS, Secure on every installation. The CSV export guards against formulas in cells (e.g. a name starting with =). Passwords set by the admin need at least 8 characters. The sign-in no longer reveals through its response time whether a user name exists.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Rechteverwaltung: Die Schalter „Gegenstände anzeigen“ und „Erweiterte Suche & Filter“ sind entfallen - sie ließen sich umstellen, wirkten aber nicht. „Aktuellen Wert & Werthistorie pflegen“ wirkt jetzt: Ohne dieses Recht ist das Feld beim Bearbeiten schreibgeschützt. Die Bestätigung beim Umschalten zeigt wieder ein Symbol statt HTML-Text.',
                'text_en' => 'Permissions: the switches “View items” and “Advanced search & filters” have been removed - they could be changed but had no effect. “Maintain current value & value history” now takes effect: without this permission the field is read-only when editing. The confirmation when switching shows an icon again instead of HTML text.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Benutzerverwaltung: Benutzer mit Apostroph im Namen lassen sich wieder löschen, ihre Zwei-Faktor-Anmeldung zurücksetzen.',
                'text_en' => 'User management: users with an apostrophe in their name can be deleted again and their two-factor sign-in reset.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Löschen: Wird ein Gegenstand einzeln gelöscht, verschwinden jetzt auch alle seine Fotos vom Server. Bisher blieben die zusätzlichen Fotos im Upload-Ordner liegen.',
                'text_en' => 'Deleting: when a single item is deleted, all of its photos are now removed from the server as well. Previously the additional photos stayed in the upload folder.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Sicherheit, „nur eigene Gegenstände“: Kann die Einstellung wegen eines Datenbankfehlers nicht gelesen werden, bleibt die Einschränkung bestehen, statt alle Gegenstände zu zeigen. Die Statistik über der Liste zählt bei dieser Einstellung nur noch die eigenen Gegenstände.',
                'text_en' => 'Security, “own items only”: if the setting cannot be read because of a database error, the restriction stays in place instead of showing all items. The statistics above the list now only count the user\'s own items with this setting.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Liste: Mit gewähltem Raum zeigen die Gesamtsumme oben und die Statistik unter „Erweiterte Suche“ nur noch die Gegenstände dieses Raums. Bisher standen dort Anzahl und Summe aller Räume.',
                'text_en' => 'List: with a room selected, the total at the top and the statistics under “Advanced search” now only cover the items in that room. Previously they showed the count and total of all rooms.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'CSV-Export: Ein Rückstrich vor einem Anführungszeichen (z. B. C:\\"Belege") bringt in Excel und LibreOffice keine Spalten mehr durcheinander.',
                'text_en' => 'CSV export: a backslash before a quotation mark (e.g. C:\\"Receipts") no longer mixes up the columns in Excel and LibreOffice.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Benutzerverwaltung: Die Anzeige „Wer ist online“ ist entfallen. Sie zeigte seit längerem niemanden mehr an. Die dafür früher gespeicherten IP-Adressen und besuchten Seiten werden gelöscht.',
                'text_en' => 'User management: the “Who is online” display has been removed. It had not shown anyone for some time. The IP addresses and visited pages stored for it in the past are deleted.'
            ]
        ]
    ],
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
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Ältere Änderungen: die vollständige Liste steht unter https://palindrom.de/valusafe/'
            ]
        ]
    ]
];
