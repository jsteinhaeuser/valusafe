<?php
/**
 * changelog_data.php — Auslieferungsfassung
 *
 * Erzeugt am 05.11.2026 16:09 von bin/changelog_export.php.
 * NICHT von Hand aendern — Aenderungen gehoeren in die Projektchronik,
 * aus der diese Datei bei jedem Paketbau neu entsteht.
 *
 * Enthaelt die 5 juengsten Versionen mit anwenderrelevanten
 * Aenderungen. Die vollstaendige Liste steht unter
 * https://palindrom.de/valusafe/
 */

return [
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
            ]
        ]
    ],
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
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Ältere Änderungen: die vollständige Liste steht unter https://palindrom.de/valusafe/'
            ]
        ]
    ]
];
