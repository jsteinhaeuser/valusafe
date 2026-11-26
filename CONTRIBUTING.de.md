# Mitarbeit an ValuSafe

ValuSafe wird von einer Person geschrieben und gepflegt. Beiträge sind
willkommen – vor allem Fehlermeldungen. Hier steht, wie man sie schickt und
was eine Änderung braucht, bevor sie in ein Release kommt.

English: [CONTRIBUTING.md](CONTRIBUTING.md)

## Fehler melden oder etwas fragen

Legen Sie ein Issue auf GitHub an: <https://github.com/jsteinhaeuser/valusafe/issues>.
Deutsch oder Englisch, beides ist in Ordnung.

Hilfreich in einer Meldung:

- was Sie getan haben, was Sie erwartet haben und was stattdessen passiert ist
- die ValuSafe-Version (unten im Admin-Bereich oder `wert/wert-update/version.json`)
- PHP-Version und Webserver (Apache, Nginx, Docker)
- die genaue Fehlermeldung oder ein Bildschirmfoto

**Sicherheitsprobleme gehören nicht in ein öffentliches Issue.** Wohin sie
gehen, steht in [SECURITY.de.md](SECURITY.de.md).

## Pull Requests

Pull Requests sind willkommen. Zwei Dinge vorab:

- **Entwickelt wird in einem privaten Repository.** Dieses öffentliche
  Repository wird mit jedem Release und bei Änderungen an Dokumentation und
  automatischen Tests aktualisiert. Ein Pull Request wird deshalb nicht direkt
  übernommen; die Änderung wird von Hand im Entwicklungs-Repository eingebaut
  und mit der nächsten Version veröffentlicht. Sie werden im Commit und im
  Changelog genannt, wenn Sie das möchten.
- **Eine Person, keine zugesagte Antwortzeit.** Bei allem, was über eine kleine
  Korrektur hinausgeht, bitte zuerst ein Issue anlegen und beschreiben, was Sie
  vorhaben – damit niemand einen Abend in eine Änderung steckt, die nicht passt.

## Was eine Änderung braucht

### Code

- Reines PHP 8.2 bis 8.5, kein Framework, kein Build-Schritt. Im Betrieb
  MySQL/MariaDB, die Tests nutzen SQLite.
- Datenbankzugriffe nur mit vorbereiteten Abfragen (`$db->select()`,
  `$db->execute()` mit Parametern). Keine Werte in SQL zusammenkleben.
- Jede Ausgabe maskiert (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` oder
  `esc()`).
- Jedes Formular und jede ändernde Anfrage prüft das CSRF-Token, jede Seite
  die Rechte (`requireLogin()`, `requirePermission()`).
- Keine externen Quellen: kein CDN, keine fremden Webfonts, keine Skripte
  Dritter. Die Content Security Policy in `wert/SecurityHeaders.php` verbietet
  sie, und ValuSafe muss ohne Internetverbindung laufen.
- Texte der Oberfläche gehören in die Sprachdateien (`wert/lang/*.php`, für den
  Admin-Bereich `wert/backend/lang/*.php`) – in **alle** Sprachen, nicht nur
  Deutsch und Englisch. Wer nicht übersetzen kann, nimmt den englischen Text
  und sagt das im Pull Request.
- Tastatur und Screenreader: Knöpfe sind `<button>`, Symbole ohne Text
  bekommen ein `aria-label`, reine Schmucksymbole `aria-hidden="true"`. Ziel
  ist WCAG 2.2 AA.
- Kommentare auf Deutsch oder Englisch. Der vorhandene Code ist überwiegend
  deutsch kommentiert; das darf so bleiben.

### Tests

**Neue Funktionen und Fehlerkorrekturen bringen einen Test mit.** Die Tests
liegen in `wert/tests` (PHPUnit, SQLite im Speicher, kein Datenbankserver
nötig):

```bash
cd wert
composer install
vendor/bin/phpunit
```

Lässt sich eine Änderung nicht sinnvoll automatisch testen (etwa eine
Layout-Korrektur), steht im Pull Request, wie sie geprüft wurde.

### Statische Analyse

PHPStan läuft bei jedem Push (`wert/phpstan.neon.dist`):

```bash
cd wert
vendor/bin/phpstan analyse
```

Es darf keine Fehler melden. Befunde, die es beim Einführen von PHPStan schon
gab, stehen in `wert/phpstan-baseline.neon`. Bitte dort nichts Neues
eintragen – den Befund beheben oder im Pull Request erklären, warum er ein
Fehlalarm ist.

### Changelog

Jede Änderung, die Anwender bemerken, bekommt einen Eintrag in
`wert/changelog_data.php` (deutscher Text und `text_en`). Technische
Einzelheiten kommen in einen eigenen Eintrag mit `'public' => false`.

## Lizenz

ValuSafe steht unter der MIT-Lizenz ([LICENSE](LICENSE)). Wer einen Beitrag
schickt, ist damit einverstanden, dass er unter derselben Lizenz
veröffentlicht wird.
