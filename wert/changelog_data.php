<?php
/**
 * changelog_data.php — Auslieferungsfassung
 *
 * Erzeugt am 27.09.2026 14:54 von bin/changelog_export.php.
 * NICHT von Hand aendern — Aenderungen gehoeren in die Projektchronik,
 * aus der diese Datei bei jedem Paketbau neu entsteht.
 *
 * Enthaelt die 5 juengsten Versionen mit anwenderrelevanten
 * Aenderungen. Die vollstaendige Liste steht unter
 * https://palindrom.de/valusafe/
 */

return [
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
            ]
        ]
    ],
    [
        'version' => '4.3.24',
        'date' => '2026-09-19',
        'entries' => [
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'ValuSafe hat jetzt eine Anlaufstelle fuer Sicherheitsmeldungen. Wer einen Fehler findet, der die Sicherheit betrifft, weiss ab sofort, wohin damit: Die Datei SECURITY.md liegt dem Download bei und nennt Anschrift, Sprachen und was in eine Meldung gehoert. Sie sagt auch ehrlich, was Sie NICHT erwarten duerfen — hier steht keine Firma mit Rufbereitschaft, sondern eine einzelne Person. Fuer Sie als Anwender aendert sich nichts; Sie muessen nichts tun.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Von allen Instanzen wurde eine alte Hilfsdatei entfernt, die dort nicht mehr hingehoerte: Sie stammte aus einer frueheren Ausbaustufe, war ohne Anmeldung erreichbar und wurde von keiner Seite mehr verlinkt. Wer ValuSafe als Paket heruntergeladen hat, war nie betroffen — die Datei war nie Teil der Auslieferung. Auf den gepflegten Instanzen ist sie weg; zu tun ist nichts.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Der Systemcheck im Verwaltungsbereich behauptet nicht mehr das Gegenteil, wenn er keine Antwort bekommt. Bisher las er nur den Antwortcode — und bei gar keiner Antwort war der 0. Eine Pruefung wie "diese Datei darf nicht mehr erreichbar sein" meldete dann eine Warnung, obwohl in Wahrheit ueberhaupt nichts gemessen worden war. Jetzt steht dort "Unbeantwortet" samt Grund. Unbeantwortet ist weder gruen noch rot, und das ist die ehrlichere Auskunft.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Alte Sicherungen werden nicht mehr zu eifrig aufgeraeumt. Bisher loeschte ValuSafe jede Sicherung, die aelter als sieben Tage war — auch dann, wenn es die letzte ihrer Art war. Wer zum Beispiel nur die Bilder sicherte, verlor dabei unbemerkt die letzte Sicherung der Datenbank. Jetzt bleiben von jeder Art (Datenbank, Bilder, Programmdateien) die drei neuesten immer erhalten, egal wie alt. Ausserdem sagt eine Sicherung, bei der keine Art ausgewaehlt war, das jetzt deutlich, statt "abgeschlossen" zu melden. Zu tun ist nichts.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'SICHERHEIT: Das Kontaktformular der oeffentlichen Sammlungsansicht laesst sich nicht mehr ohne gueltigen Link benutzen. Die oeffentliche Ansicht ist ueber einen geheimen Link erreichbar, und nur wer diesen Link hat, soll den Besitzer ueber das Formular anschreiben koennen. Tatsaechlich wurde die Anfrage bisher schon verarbeitet, BEVOR der Link geprueft wurde: Wer die Adresse der Seite kannte, konnte damit beliebig viele E-Mails an den Betreiber ausloesen, auch ohne den Link zu kennen. Jetzt wird zuerst der Link geprueft, und die Zahl der Anfragen je Absender ist begrenzt - mit derselben Sperre, die auch die Anmeldung schuetzt. Wer die oeffentliche Ansicht nicht benutzt, war nie betroffen. Zu tun ist nichts ausser der Aktualisierung.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Raumangaben stimmen jetzt ueberall ueberein. Bisher holte ValuSafe den Raum eines Gegenstandes an manchen Stellen aus einer anderen Tabelle als an anderen: die Liste und die Eingabemasken aus der einen, die PDF- und CSV-Exporte, die Auswertungen und das Schnellerfassen aus der anderen. Solange beide Tabellen denselben Inhalt hatten, fiel das nicht auf; wo sie auseinanderliefen, konnte im Export ein anderer Raum stehen als am Bildschirm — oder gar keiner. Vor der Umstellung wurde auf allen elf gepflegten Installationen nachgezaehlt: betroffen war bisher kein einzelner Gegenstand, auf einer Installation fehlte in den Exporten ein Raumname. Ab jetzt lesen alle Stellen dieselbe Tabelle. Sichtbar wird das dort, wo Raeume bisher nur in der Liste auftauchten: sie stehen nun auch in den Exporten und Auswertungen.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Eine alte Hilfsdatei im Verwaltungsbereich ist entfernt, und zwar diesmal wirklich ueberall. Sie stammte aus der Anfangszeit und war von keiner Seite eingebunden, konnte aber direkt aufgerufen werden — und dann trug sie dem Besucher die Rechte des Administrators zu, der sich zuletzt angemeldet hatte. Wer die Adresse nicht kannte, konnte sie nicht aufrufen; wer sie kannte, brauchte kein Passwort. Ein Eintrag von Ende August meldete diese Datei schon einmal als entfernt — sie war es aber nur auf den Servern, nicht in der Vorlage, aus der neue Auslieferungen entstehen. Jetzt ist sie an beiden Stellen weg. Wer ValuSafe als Paket heruntergeladen hat, sollte die Datei wert/backend/backend_session.php loeschen, falls sie dort noch liegt.'
            ],
            [
                'type' => 'fix',
                'public' => true,
                'text' => 'Der Verlauf auf der Bearbeiten-Seite zeigt jetzt tatsaechlich etwas an. Unter jedem Gegenstand sollte stehen, wer ihn wann angelegt oder geaendert hat — dieser Abschnitt blieb aber immer leer, auf allen Instanzen, seit es ihn gibt. Die Abfrage suchte Spalten unter Namen, die es in der Datenbank nie gab. Aufgefallen ist es erst, als ValuSafe anfing, Fehlermeldungen an einer Stelle festzuhalten, in die man hineinsehen kann: dort stand die Meldung schwarz auf weiss. Wer bisher wissen wollte, wer etwas geaendert hat, musste in die Verwaltung unter Aktivitaeten gehen; das geht weiter, aber jetzt steht es auch direkt am Gegenstand.'
            ]
        ]
    ],
    [
        'version' => '4.3.23',
        'date' => '2026-09-09',
        'entries' => [
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'ValuSafe lässt sich wieder mit einem Assistenten auf einem gewöhnlichen Webhosting-Paket einrichten: Sprache wählen, Systemvoraussetzungen prüfen, Datenbankverbindung testen, Tabellen anlegen, Administratorkonto — sechs Schritte, auf Deutsch oder Englisch. Der Assistent legt die Zugangsdatei selbst an, erzeugt darin zufällige Sicherheitsschlüssel und entfernt sich zum Schluss samt Schemadatei vom Server; gelingt ihm das nicht, sagt er, welche Dateien von Hand zu löschen sind. Vorausgesetzt wird PHP 8.2 oder neuer.'
            ],
            [
                'type' => 'feature',
                'public' => true,
                'text' => 'Ältere Änderungen: die vollständige Liste steht unter https://palindrom.de/valusafe/'
            ]
        ]
    ]
];
