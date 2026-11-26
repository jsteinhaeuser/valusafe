# ValuSafe

[![Tests](https://github.com/jsteinhaeuser/valusafe/actions/workflows/tests.yml/badge.svg)](https://github.com/jsteinhaeuser/valusafe/actions/workflows/tests.yml) [![OpenSSF Best Practices](https://www.bestpractices.dev/projects/17134/badge)](https://www.bestpractices.dev/projects/17134)

Selbst gehostete Inventarverwaltung für Wertsachen. Sie erfassen, was Sie besitzen — mit Foto, Kaufpreis und Standort — und ValuSafe macht daraus Übersichten, Auswertungen und die Unterlagen, nach denen eine Versicherung nach einem Einbruch, einem Brand oder einem Wasserschaden fragt.

**Erst ausprobieren:** [Live-Demo unter valusafe.palindrom.de](https://valusafe.palindrom.de) — mit Beispieldaten, stündlich zurückgesetzt. Die Zugangsdaten stehen auf [palindrom.de/valusafe/online-test.php](https://www.palindrom.de/valusafe/online-test.php).

Dasselbe taugt für Sammlungen — Gitarren, Uhren, Bücher, Kunst — und für die Wohnungsübergabe: Das Übergabeprotokoll listet alles Raum für Raum auf, mit Angaben zu Mieter und Vermieter, zum Ausdrucken und Unterschreiben.

Es läuft auf gewöhnlichem Webhosting. Kein Framework, kein Composer, kein Node, keine fremden Server: Dateien hochladen, Assistenten durchklicken, fertig.

**Stand:** 11.2026 · *(English version: [README.md](README.md))*

![Die Hauptliste als Kachelansicht](doku/screenshots/01-liste.png)

## Warum es das gibt

Nach einem Schaden stellt die Versicherung drei Fragen: Was hatten Sie, was war es wert, und können Sie es belegen. Wer das Wochen später aus dem Gedächtnis beantwortet, bekommt am Ende zu wenig. ValuSafe gibt es, damit die Antwort schon geschrieben ist — und damit das, was Sie einreichen, wie ein Dokument aussieht und nicht wie ein Schuhkarton voller Kassenzettel.

## Was es kann

- **Gegenstände** mit Foto, Kaufdatum, Kaufpreis, aktuellem Wert, Standort, Notizen und zwei freien Feldern
- **Mehrere Bilder und Dokumente** je Gegenstand — Rechnungen, Zertifikate, Gutachten; Fotos lassen sich vor dem Speichern im Browser zuschneiden und drehen
- **Wertverlauf** — der aktuelle Wert wird über die Zeit geführt, nicht überschrieben
- **Standorte** als Räume, Orte und Positionen, damit „wo ist es" eine Antwort hat
- **Versicherungen** mit zugeordneten Gegenständen
- **Barcode-Scan** im Browser, mit Nachschlagen für Bücher und Medien
- **Schadensfall-Assistent**: nach Einbruch, Brand oder Wasserschaden betroffene Gegenstände auswählen und daraus eine Liste für die Polizei und eine Schadensmeldung für die Versicherung drucken, dazu alle Fotos und Belege als eine ZIP-Datei
- **Exporte**: PDF (darunter ein Versicherungsbericht und ein Übergabeprotokoll), CSV, HTML, QR-Codes zum Aufkleben
- **Übersichtsseite** mit Auswertungen nach Kategorie, Raum und Wert
- **Benutzer und Rollen** (admin, edit, read) samt feiner Rechtematrix
- **Zwei-Faktor-Anmeldung** (TOTP nach RFC 6238) für alle Konten
- **Datensicherung** von Datenbank, Bildern und Programmdateien, mit Wiederherstellung — auch zum Umzug in eine neue Installation (Sicherungen ab Version 4.4)
- **Zehn Sprachen**: Deutsch, Englisch, Französisch, Spanisch, Italienisch, Niederländisch, Polnisch, Portugiesisch, Türkisch, Irisch (Beta)
- **Sieben Farbschemata** zur Auswahl, darunter ein dunkles
- **Bedienung per Tastatur**: Sprunglink zum Inhalt, sichtbarer Fokus, Karten mit Enter öffnen; vollständige WCAG-2.1-AA-Konformität ist für Version 5.0 geplant
- **Änderungsverlauf** — wer hat was wann geändert

## Ein paar Ansichten

Ein Gegenstand mit Wertverlauf, eigenen Feldern und angehängten Bildern:

<img src="doku/screenshots/02-gegenstand.png" alt="Ein Gegenstand im Bearbeiten-Formular" width="420">

Versicherungen, mit den zugeordneten Gegenständen und der Summe je Vertrag:

![Die Versicherungsübersicht](doku/screenshots/03-versicherungen.png)

Farbschema und Sprache wählt jeder Benutzer für sich:

![Die Einstellungen mit Farbschemata und Sprachauswahl](doku/screenshots/04-einstellungen.png)

Was sich aus einer Auswahl herausholen lässt:

<img src="doku/screenshots/05-export.png" alt="Das Export-Menü" width="260">

*Die Bilder stammen aus dem Demo-Inventar; die Gegenstände und ihre Abbildungen sind erfunden.*

## Voraussetzungen

- PHP 8.2 oder neuer
- MySQL 8 oder MariaDB
- Apache mit `.htaccess`, oder ein gleichwertiger Regelsatz auf einem anderen Webserver
- Etwa 50 MB Platz, dazu Platz für Ihre Fotos

Kein Shell-Zugang nötig. Kein Composer, kein Bauschritt, kein Hintergrundprozess.

## Installation

**Auf einem Webspace:** Den Inhalt von `wert/` in das Webverzeichnis hochladen und `setup.php` und `schema.sql` aus `install/` daneben legen — in dasselbe Verzeichnis wie `index.php`, nicht in einen Unterordner. Dann `setup.php` im Browser aufrufen und dem Assistenten folgen. Er prüft PHP-Version und Erweiterungen, legt die Datenbank an und schreibt die `config.php`. Am Ende löscht er sich selbst und `schema.sql`; gelingt ihm das nicht, sagt er es, und Sie entfernen beides von Hand.

Das fertige Paket von [palindrom.de/valusafe](https://palindrom.de/valusafe/) ist bereits so aufgebaut und enthält zusätzlich die Handbücher.

**Mit Docker:** fertige Abbilder für amd64 und arm64 unter `ghcr.io/jsteinhaeuser/valusafe`. Siehe [docker/INSTALL.de.md](docker/INSTALL.de.md) — `docker compose up`, nachdem eine `.env` ausgefüllt ist. Die Anleitung behandelt auch die Fälle, in denen es schiefgeht.

## Ihre Daten bleiben bei Ihnen

ValuSafe hat keine Telemetrie, meldet nichts nach Hause und zählt keine Zugriffe. Chart.js, die Barcode-Bibliothek, das Zuschneide-Werkzeug und die Symbolschrift liegen auf Ihrem eigenen Server, nicht bei einem Anbieter.

Es gibt genau eine Funktion, die nach außen geht, und sie ist freiwillig: Wenn Sie einen Barcode oder eine ISBN nachschlagen lassen, fragt der Server öffentliche Produktdatenbanken ab — Deutsche Nationalbibliothek, Open Library, Google Books, MusicBrainz, Cover Art Archive, UPCitemdb, Open Food Facts, Open Beauty Facts — und zwar mit dem Code, den Sie gescannt haben. Mehr wird nicht übermittelt, und wer diesen Knopf nie benutzt, bei dem verlassen keine Inventardaten den eigenen Server. Das Scannen selbst geschieht im Browser. Sonst geht nur E-Mail hinaus, und nur, wenn Sie die Funktionen benutzen, die sie verschicken: Passwort zurücksetzen, das Kontaktformular der öffentlichen Ansicht, Hinweise zur Sicherung. Alles andere funktioniert auch auf einem Rechner ganz ohne Internetverbindung.

## Sicherheit

Passwörter werden mit Argon2id gehasht. Jede Datenbankabfrage benutzt vorbereitete Anweisungen ohne Emulation. Formulare tragen CSRF-Token, die Sitzung wird bei der Anmeldung neu vergeben und trägt einen Fingerabdruck, Anmeldeversuche werden begrenzt und sperren zeitweise, Uploads werden nach MIME-Typ geprüft und Bilder neu kodiert, und die HTTP-Kopfzeilen enthalten HSTS und eine Content Security Policy, die keine fremden Server zulässt. Eingebettete Skripte und Stile erlaubt sie noch; die Seiten sind darauf angewiesen.

Wenn Sie ein Sicherheitsproblem finden: In [SECURITY.de.md](SECURITY.de.md) steht, wohin damit und was Sie erwarten dürfen — und, ehrlicherweise, was nicht.

## Unterlagen

| Datei | Was drinsteht |
|---|---|
| [ARCHITECTURE.de.md](ARCHITECTURE.de.md) | Wie der Code aufgebaut ist, aus dem Quelltext rekonstruiert und nicht aus der Erinnerung |
| [SECURITY.de.md](SECURITY.de.md) | Sicherheitsmeldungen, Geltungsbereich, was ausdrücklich nicht dazugehört |
| [docker/INSTALL.de.md](docker/INSTALL.de.md) | Docker-Installation, Schritt für Schritt |
| [changelog_vollstaendig.txt](changelog_vollstaendig.txt) | Alle Änderungen, die Anwender betreffen |
| [CHANGELOG.md](CHANGELOG.md) | Dasselbe auf Englisch, ab Version 4.3.28 |
| [doku/Benutzerhandbuch.pdf](doku/Benutzerhandbuch.pdf) | Benutzerhandbuch — Stand 4.3.33 |
| [doku/Benutzerhandbuch_Erweitert.pdf](doku/Benutzerhandbuch_Erweitert.pdf) | Erweitertes Handbuch, Stand 4.3.33 |
| [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md) | Mitgelieferte fremde Bestandteile und ihre Lizenzen |

## Im Vergleich

Wer ein allgemeines Haushaltsinventar sucht — was liegt in welcher Kiste, wann muss die Heizung zur Wartung —, sollte sich zuerst [Homebox](https://github.com/sysadminsmedia/homebox) ansehen. Es ist das bekanntere Projekt, hat eine aktive Gemeinschaft, läuft als kleiner Docker-Container mit SQLite, unterstützt Anmeldung über bestehende Konten (OIDC), importiert CSV und hat eine API. Die letzten drei Punkte hat ValuSafe nicht, und es wird von einer Person gepflegt.

ValuSafe ist enger gefasst. Es dreht sich um Wert und Versicherung: Verträge mit den Gegenständen, die sie abdecken, ein Wertverlauf je Gegenstand, ein Versicherungsbericht und ein Schadensfall-Assistent, der aus einem Einbruch oder Brand eine Liste für die Polizei, eine Schadensmeldung und ein ZIP mit allen Fotos und Belegen macht. Es hat Rollen mit Rechte-Tabelle, eingebaute Zwei-Faktor-Anmeldung und läuft auf gewöhnlichem PHP-Webhosting ohne Docker.

Für andere Aufgaben gibt es andere Werkzeuge: [Grocy](https://github.com/grocy/grocy) für Vorräte und Haushaltsaufgaben, [Snipe-IT](https://github.com/grokability/snipe-it) für IT-Geräte in Organisationen, [InvenTree](https://github.com/inventree/InvenTree) für Bauteile und Lager.

## Stand, und was Sie erwarten können

ValuSafe wird von einer einzelnen Person geschrieben und gepflegt, einem pensionierten Informatiklehrer, in seiner Freizeit. Die Entwicklung begann Ende 2025; elf Installationen sind im täglichen Einsatz.

Was das für Sie bedeutet, ohne Beschönigung:

- **Es wird gepflegt, aber es gibt keine Zusage auf Unterstützung.** Kein Servicevertrag, keine Rufbereitschaft, keine zugesagte Antwortzeit.
- **Korrekturen gibt es nur für die aktuelle Fassung.** Ältere Versionen werden nicht nachgepflegt.
- **Es gibt Altlasten.** Der Code ist über ein Jahr gewachsen, und das sieht man — [ARCHITECTURE.de.md](ARCHITECTURE.de.md) führt die bekannten Schwachstellen ehrlich auf. Die größte Altlast, drei konkurrierende Modelle für Räume und Standorte, wurde im September 2026 bereinigt (Versionen 4.3.24 und 4.3.25).
- **Den Verzeichnisschutz macht die `.htaccess`.** Auf einem Webserver, der sie nicht liest — etwa Nginx — brauchen Sie gleichwertige Regeln. Eine Vorlage für Nginx liegt unter `install/nginx-valusafe.conf.example`. Sie wurde im November 2026 mit Nginx 1.31 und PHP-FPM erprobt; prüfen Sie nach dem Einrichten die oben in der Datei genannten Adressen.
- **Es gibt automatische Tests.** Rund 300 PHPUnit-Tests in `wert/tests` prüfen Rechte, Werte, Löschen, Exporte, Datenübernahme und das signierte Update-Paket. GitHub führt sie bei jedem Push unter PHP 8.2 bis 8.5 aus; eine Datenbank brauchen sie nicht. Composer braucht man nur für die Tests (`cd wert && composer install && vendor/bin/phpunit`), nicht für den Betrieb von ValuSafe.

Fehlermeldungen und Fragen sind willkommen. Beiträge ebenfalls — nur kann ein einzelner Entwickler nicht versprechen, wann er dazu kommt. Wie man beiträgt und was eine Änderung braucht: [CONTRIBUTING.de.md](CONTRIBUTING.de.md).

## Lizenz

MIT — siehe [LICENSE](LICENSE). Mitgelieferte fremde Bestandteile behalten ihre eigene Lizenz, siehe [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).
