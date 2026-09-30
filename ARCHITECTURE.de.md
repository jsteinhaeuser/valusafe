# ValuSafe — Architektur

**Stand:** 30. September 2026 · Version 4.3.30 (alle Abschnitte vor der Veröffentlichung gegen den Quellcode geprüft)
**Verfasser:** rekonstruiert aus dem Quellcode, nicht aus der Erinnerung
**Zielgruppe:** Entwickler, die den Code lesen, prüfen oder erweitern wollen

---

## 1. Was ValuSafe ist

Eine selbst gehostete Webanwendung zur Inventarisierung von Wertgegenständen im privaten Haushalt. Nutzer erfassen Gegenstände mit Foto, Kaufpreis, Kaufdatum und Standort. Daraus entstehen Übersichten, Auswertungen und Exporte — vor allem für den Schadensfall gegenüber einer Hausratversicherung.

Es ist **kein SaaS**. Jede Installation läuft auf dem Webspace ihres Betreibers, mit eigener Datenbank und eigenen Zugangsdaten. Was die Anwendung ungewöhnlich macht, ist weniger die Fachlichkeit als die **Flottenverwaltung**: elf produktive Installationen werden von einer zentralen Stelle aus verglichen und mit Code versorgt.

## 2. Umfang

| Bereich | Dateien | Zeilen |
|---|---:|---:|
| Frontend-Wurzel | 72 | 20.111 |
| Backend (Admin) | 25 | 10.687 |
| Komponenten | 6 | 2.642 |
| Sprachdateien (9 Sprachen) | 10 | 10.371 |
| JavaScript (eigenes; dazu drei mitgelieferte Bibliotheken) | 2 | 1.065 |
| CSS (13 Theme-Dateien, davon 7 auswählbar) | 17 | 3.030 |

Rund 48.000 Zeilen Anwendungscode, gezählt im veröffentlichten Repository am 27. September 2026. Alleiniger Autor. Entwicklung von Ende 2025 bis heute, unter erheblichem Einsatz von KI-Assistenz.

## 3. Technische Grundlagen

**PHP 8.2 bis 8.4** (Docker-Image: 8.4), MariaDB oder MySQL 8, vanilla JavaScript. **Kein Framework**, **keine Produktionsabhängigkeiten**. Die Tests benutzen PHPUnit als Entwicklungsabhängigkeit; Tests und `composer.json` liegen im Entwicklungs-Repository und gehören nicht zum veröffentlichten.

Das ist keine Nachlässigkeit, sondern eine Randbedingung: Die Anwendung muss auf einfachem Shared Hosting laufen, ohne Composer, ohne Node, ohne Shell-Zugang. Ein Installationsvorgang besteht aus „Dateien hochladen, `config.php` anlegen, Schema einspielen".

**Keine externen CDNs.** Chart.js, ZXing, die QR-Bibliothek und Tabler Icons liegen lokal. Die Content-Security-Policy erlaubt keine fremden Hosts. Das war eine bewusste Entscheidung gegen Drittanbieter-Tracking und für Offline-Fähigkeit.

**Konsequenz für Leser:** Es gibt keinen Router, keinen Controller, keinen DI-Container. Jede `.php`-Datei im Wurzelverzeichnis ist ein Einstiegspunkt, den der Webserver direkt ausführt.

## 4. Der Lebenszyklus einer Anfrage

Es gibt keinen Front Controller. Eine typische Seite sieht so aus:

```php
require_once 'db.php';        // <- der eigentliche Bootstrap
require_once 'helpers.php';
requireLogin();
// ... Seitenlogik ...
```

`db.php` ist der Dreh- und Angelpunkt und tut beim Einbinden Folgendes, in dieser Reihenfolge:

1. `config.php` laden (Zugangsdaten, Konstanten, Session-Parameter)
2. `security.php` und `SecurityHeaders.php` laden
3. Security-Header setzen — CSP, HSTS, X-Frame-Options, Referrer-Policy
4. Session starten
5. Session-Timeout prüfen
6. PDO-Verbindung aufbauen (`ERRMODE_EXCEPTION`, `EMULATE_PREPARES = false`)
7. Sprachdatei laden, `$translations` füllen
8. Die globale `$db`-Instanz erzeugen

Danach stehen `$pdo` (roh) und `$db` (Wrapper) sowie sämtliche Auth- und Berechtigungsfunktionen als globale Funktionen bereit.

**`config.php` ist nicht im Repository.** Sie ist instanzspezifisch, wird vom Setup-Assistenten geschrieben und steht in der `.gitignore`. `db.php` liegt im Repository, und wer den Code liest, muss wissen: Die zentralen Funktionen `requireLogin()`, `hasPermission()`, `canEdit()`, `isAdmin()`, `isSuperAdmin()`, `validateRequest()` und `t()` stehen alle in `db.php`.

## 5. Verzeichnisstruktur

```
wert/                       Anwendungswurzel, jede .php ist ein Einstiegspunkt
├── db.php                  Bootstrap, Auth, Berechtigungen, DB-Wrapper
├── config.php              Zugangsdaten und Konstanten                  [nicht in Git]
├── security.php            CSRF, Session-Fingerprint, Upload-Prüfung, Passwort-Hashing
├── SecurityHeaders.php     CSP, HSTS und die übrigen HTTP-Header
├── helpers*.php            Formatierung, Bilder, Suche, Pagination, Spaltenverwaltung
├── index.php               Hauptliste — Filter, Sortierung, Pagination, Massenaktionen
├── add.php / edit.php      Erfassung und Bearbeitung
├── export_*.php            PDF, CSV, HTML, Versicherungs- und Übergabeprotokoll
├── version_agent.php       meldet Prüfsummen an den Flotten-Hub   [siehe Abschnitt 9]
├── ajax/                   Kleine JSON-Endpunkte
├── backend/                Admin-Oberfläche, eigener Zugangs-Guard
│   ├── config.php          lädt ../config.php und ../db.php; Programmcode, keine Zugangsdaten
│   ├── layout/             der aktuell verwendete Seitenrahmen
│   ├── users.php           Benutzer und Rollen
│   ├── permissions.php     Rechtematrix
│   ├── backup.php          Sicherung (DB, Bilder, PHP-Dateien)
│   └── restore.php         Wiederherstellung
├── components/             wiederverwendete UI-Blöcke
├── lang/                   9 Sprachdateien, je ~980 Schlüssel
├── css/                    13 Theme-Dateien (7 auswählbar) plus Tokens und WCAG-Regeln
├── js/                     eigenes JS plus lokal gehostete Bibliotheken
├── upload/                 Nutzerfotos       [durch .htaccess geschützt]
└── documents/              Nutzerdokumente   [durch .htaccess geschützt]
```

## 6. Datenmodell

Kerntabelle ist `wertsachen`. Relevante Spalten:

`id`, `name`, `kategorie_id`, `raum_id`, `standort_id`, `position_id`, `kaufdatum`, `preis`, `aktueller_wert`, `aktueller_wert_datum`, `notizen`, `bild`, `barcode`, `versicherung_id`, `hidden`, `schadenfall`, `oeffentlich`, `public_token`, `sort_order`, `erstellt_von`, `erstellt_am`, `geaendert_am`, `gelistet_am`, sowie zwei frei belegbare Felder (`custom1_wert`/`custom1_typ`, `custom2_wert`/`custom2_typ`).

Weitere Tabellen: `users`, `kategorien`, `raeume`, `standorte`, `positionen`, `dokumente`, `item_images`, `wert_historie`, `versicherungen`, `app_settings`, `activity_log`, `user_activity`, `security_log`, `login_attempts`, `rate_limit`, `permissions`, `password_resets`, `schema_migrations` — insgesamt 19.

**Standorte — im September 2026 bereinigt.** Bis dahin bestanden drei Modelle
nebeneinander, und `wertsachen.raum_id` wurde je nach Stelle gegen zwei
verschiedene Tabellen aufgelöst: gegen `raeume` in der Liste und in den
Eingabemasken, gegen `orte` in allen Exporten, im Dashboard und im
Schnellerfassen. Wo die beiden Tabellen voneinander abwichen, konnte im
Versicherungs-PDF ein anderer Raum stehen als am Bildschirm.

| Modell | Tabellen | Stand |
|---|---|---|
| Alt | `orte` | Lesestellen in 4.3.24 auf `raeume` umgestellt; Tabelle in 4.3.25 entfernt |
| Aktuell | `raeume`, `standorte`, `positionen` | das einzige Modell |
| Aufgegeben | `standort_raeume`, `standort_positionen` | nie von Code gelesen; Raumnamen nach `raeume` übernommen; in 4.3.25 entfernt |

Alle 22 Lesestellen lösen jetzt gegen `raeume` auf. Vor der Umstellung wurde über
alle elf Installationen gemessen, dass jede benutzte `raum_id` in `raeume`
existiert — es konnte also kein Raumname verlorengehen. Zu beachten:
`positionen` gehören zu `standorte`, nicht zu Räumen. Die Spalte heißt `raum_id`,
trägt aber eine `standort_id` — ein alter Fehlname, kein Fehler.

Seit 4.3.25 wird der Standort eines Gegenstands in `wertsachen.standort_id`
gespeichert, auch ohne Position; ist eine Position gewählt, folgt der Standort
aus ihr. Die alten Tabellen und `wertsachen.ort_id` entfernt Migration 011 aus
bestehenden Datenbanken; in `install/schema.sql` stehen sie nicht mehr.

**Das Schema liegt im Repository.** `install/schema.sql` ist die Vorlage, aus der
sowohl der Setup-Assistent als auch der Docker-Aufbau die Datenbank anlegen.

## 7. Berechtigungsmodell

Drei Rollen: `admin`, `edit` (auch `editor`), `read`.

Darüber liegt eine feingranulare Rechtematrix in der Tabelle `permissions`, mit Schlüsseln wie `items_edit`, `items_delete`, `bulk_hide`, `docs_view`, `export_pdf`. Die Auswertung:

```php
hasPermission('items_delete')
  → isAdmin() ? true                                  // Admin darf alles
  : permissions-Tabelle vorhanden ? Spalte edit_can/read_can je nach Rolle
  : _permissionFallback()                             // hartkodierte Standardwerte
```

Der Fallback greift, wenn die Tabelle fehlt — eine Installation ohne Migration bleibt damit benutzbar statt komplett gesperrt.

Zwei Besonderheiten:

**`sieht_alle`** — ein Flag pro Benutzer. Ist die Einstellung `only_own_items` aktiv, sehen Nicht-Admins ohne dieses Flag nur Datensätze, deren `erstellt_von` ihrem Benutzernamen entspricht.

**`SUPERADMIN_USERNAME`** — eine Konstante in `config.php`, kommagetrennte Liste. SuperAdmins können bestimmte Funktionen für normale Admins sperren (Backup, Restore, Datei-Download).

## 8. Sicherheitsschichten

| Schicht | Umsetzung |
|---|---|
| Passwörter | Argon2id über `password_hash()` |
| SQL | ausnahmslos Prepared Statements, `EMULATE_PREPARES = false` |
| CSRF | `Security::getCSRFInput()` im Formular, `validateRequest()` im POST-Zweig |
| Session | `session_regenerate_id(true)` beim Login, Timeout, Fingerprint aus Netzpräfix und User-Agent |
| Brute Force | `RateLimiter` — 5 Fehlversuche je IP-Adresse, dann 15 Minuten Sperre |
| 2FA | eigene `TOTP`-Klasse nach RFC 6238, ohne Fremdbibliothek |
| Uploads | MIME-Prüfung über `finfo`, Endungs-Whitelist, Neucodierung als JPEG |
| HTTP-Header | CSP ohne fremde Hosts (aber mit `'unsafe-inline'` für Skripte und Stile), HSTS, `frame-ancestors 'none'`, `form-action 'self'` |
| Verzeichnisse | `.htaccess` sperrt `upload/`, `documents/`, Konfigurationsdateien |

**Einschränkung:** Die `.htaccess`-Ebene existiert auf der NAS-Instanz nicht, weil dort Nginx läuft. Ein gleichwertiges Nginx-Regelwerk fehlt noch.

Der Session-Fingerprint kürzt die IP bewusst auf ihr Netz — IPv4 auf /24, IPv6 auf /64 —, damit ein Adresswechsel innerhalb desselben Providernetzes nicht abmeldet, ein Wechsel in ein fremdes Netz aber schon.

## 9. Die Flotte

*(Die Hostnamen sind in der veröffentlichten Fassung dieses Dokuments durch Beispielnamen ersetzt.)*

Der eigentlich interessante Teil. Elf produktive Installationen bei drei verschiedenen Hostern plus einem NAS werden von `hub.example` aus verwaltet.

```
                    hub.example
                   (Steuerzentrale, kein ValuSafe)
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
  version_check.php    file_sync.php        sync.php
   Versionen             Code-Verteilung     DB-/Bild-Sync
        │                   │                   │
        └──────────┬────────┴─────────┬─────────┘
                   ▼                  ▼
          version_agent.php     sync_agent.php
          (je Instanz)          (je Instanz)
                   │                  │
        ┌──────────┴──────────────────┴──────────┐
        ▼                                        ▼
   master.example          10 weitere Instanzen
   all-inkl · MariaDB                  Lima-city, Synology NAS
```

**`version_agent.php`** liefert MD5, Größe und mtime aller Dateien mit einer von neun Endungen (`.php`, `.js`, `.css`, `.html`, `.md`, `.json`, `.xml`, `.svg`, `.pdf`) als JSON; Inhaltsverzeichnisse wie `upload/` und `vendor/` bleiben außen vor. Dateien, die auf einer Instanz bewusst abweichen, stehen in `VERSION_OVERRIDES` in deren `config.php`. Er ist die einzige Flottendatei im veröffentlichten Repository; Sync-Agent und Hub gehören nicht dazu.

**`sync_agent.php`** ist der Arbeitspferd-Endpunkt: `ping`, `list_files`, `read_file`, `write_file`, `delete_file` für den Code-Sync, dazu `status`, `export`, `import` für die Datenbank und `image`/`upload` für Bilder. Er kennt zwei getrennte Tokens — eines für Datei-, eines für Datenbankoperationen.

**Ablauf einer Code-Verteilung:** `file_sync.php` holt die Dateiliste des Masters, vergleicht sie mit der Zielinstanz, holt geänderte Dateien per `read_file` vom Master und schreibt sie per `write_file` auf das Ziel. Nach dem Schreiben wird die MD5 gegengeprüft — eine Datei gilt nur als übertragen, wenn sie übereinstimmt.

**Absicherung:** Jede Instanz hat seit dem 26. August 2026 eigene, zufällige Tokens, die ausschließlich aus ihrer `config.php` kommen. Vorher lag ein gemeinsames Token fest verdrahtet im Quellcode. Der Agent verweigert den Dienst, wenn die Konstanten fehlen, statt auf einen Standardwert zurückzufallen. Seit September 2026 schickt der Hub die Tokens in einer Kopfzeile statt in der Adresse, wo jeder Webserver sie protokolliert hätte.

**Zwei Eigenheiten, die man kennen muss:**

`sync_agent.php` weigert sich, sich selbst, `version_agent.php` oder eine `config.php` zu überschreiben — diese Dateien gehen per FTP auf jede Instanz.

Lima-city betreibt eine WAF, die POST-Bodies mit sicherheitsnahen Schlüsselwörtern blockiert. Deshalb überträgt der Sync für eine Liste sensibler Dateien (`security.php`, `LoginAttempts.php`, …) den Inhalt Base64-kodiert.

## 10. Frontend

Serverseitig gerendertes HTML, ergänzt um punktuelles JavaScript. Kein Build-Schritt, kein Bundler, keine Transpilierung — was im Repository steht, läuft im Browser.

**Zwei Oberflächen-Generationen.** Die ältere („klassisch") und die neuere („Next Interface" — 52-Pixel-Icon-Leiste, Masonry-Raster, geteilte Detailansicht, mobile Bottom-Navigation). Erkennbar an den eingebundenen Rahmen: 37 Dateien nutzen `header_next_page.php`, nur noch eine — `index.php` — den alten `header_next.php`. Im Backend nutzen alle 21 Seiten `layout/header_next_page.php`.

**Internationalisierung.** Neun Sprachen (DE, EN, FR, TR, ES, IT, NL, PL, PT), je etwa 980 Schlüssel. Zugriff über `t('schluessel')`. Fehlt ein Schlüssel, gibt `t()` den Schlüssel selbst zurück — fehlende Übersetzungen erscheinen also als roher Bezeichner im UI statt als leerer Text. Stand 27. September 2026 fehlt in keiner Sprache ein Schlüssel; `bin/check_lang.py` im Entwicklungs-Repository prüft das.

**PWA.** Manifest, Offline-Seite, Service Worker mit Cache-First für statische Dateien und ohne Caching für PHP.

Bis 4.3.5 gab es hier *zwei* Service Worker: `footer_next.php` und `footer_next_page.php` registrieren `/sw.js` — den echten; `js/main.js` registrierte zusätzlich `/service-worker.js`, einen 299 Byte großen Stub, der alle Requests durchreichte. Beide beanspruchten denselben Scope `/`. Da `js/main.js` seit dem Next Interface von keiner Seite mehr eingebunden wurde, sind beide Dateien in 4.3.5 entfallen; es bleibt `/sw.js`.

Seit 4.3.29 steht die Registrierung an einer Stelle, `components/sw_registrierung.php`. Bis dahin schloss `footer_next.php` seine Kopie in `if (!headers_sent())` ein — am Seitenende immer falsch, sie registrierte also nie; das taten nur Seiten mit `footer_next_page.php`. Service Worker, Manifest und eine Handvoll Links nutzten absolute Pfade (`/sw.js`, `/css/…`, `/index.php`) und setzten eine Installation im Wurzelverzeichnis der Domain voraus; sie sind jetzt relativ, und `sw.js` prüft Pfade relativ zum eigenen Ort — eine Installation im Unterordner funktioniert, und Uploads der Nutzer bleiben dort aus dem Cache.

## 11. Tests, Build, Deployment

**Tests:** PHPUnit 10, drei Dateien, 38 Testmethoden, im Entwicklungs-Repository. Sie decken reine Logikfunktionen ab — Pfad-Normalisierung im Sync, Rate-Limiter, Migrationshelfer. **Kein Test berührt Authentifizierung, Datenbank oder einen Controller.** Die Abdeckung des Geschäftskerns liegt bei null. Das wird gerade angegangen.

**Build:** keiner. `composer install` wird nur für die Tests gebraucht.

**Deployment:** Dateien per FileZilla auf den Master, von dort per `file_sync.php` auf die Flotte. Datenbankänderungen sind versionierte Migrationen in `backend/migrations/`. Seit September 2026 führt der Hub sie über `migration_agent.php` auf jeder Instanz aus, mit Trockenlauf vorab; eine einzelne Installation führt sie mit `migrate.php` selbst aus.

## 12. Bekannte Baustellen

Ehrlich heißt aktuell: Jeder Punkt wurde am 25.09.2026 am Quelltext
nachgeprüft, und was behoben ist, steht durchgestrichen da statt stillschweigend
zu verschwinden. Eine Liste bekannter Schwächen, die niemand abarbeitet, ist
eine Liste offener Türen mit Wegbeschreibung — Punkt 12 hat das vier Wochen
lang bewiesen. Am 27. September 2026 wurde die Liste erneut geprüft.

**Weiterhin offen**

1. **Keine Testabdeckung des Geschäftskerns.** Drei Testdateien decken Migrationshelfer, die Versuchssperre und die Sync-Helfer ab. Gegenstände, Berechtigungen und Exporte sind ungeprüft. Die Tests gehören nicht zum veröffentlichten Repository.
2. **Keine `.htaccess`-Entsprechung auf der NAS-Instanz** (Nginx). Der Verzeichnisschutz hängt dort an der Serverkonfiguration, nicht an mitgelieferten Dateien.

**Seit der ersten Fassung dieses Dokuments behoben**

5. ~~Drei Waisen-Tabellen.~~ `orte`, `standort_raeume` und `standort_positionen` wurden in 4.3.25 entfernt — aus `install/schema.sql` und per Migration aus allen elf Installationen.

6. ~~Drei Standort-Modelle nebeneinander.~~ Seit dem 24.09.2026 lösen alle Lesestellen `wertsachen.raum_id` gegen `raeume` auf. Die Messung davor: Auf keiner der elf Installationen zeigte ein Gegenstand auf einen Raum, den `raeume` nicht kannte.
7. ~~Schema nicht in der Versionskontrolle.~~ `install/schema.sql` ist versioniert und die Vorlage, aus der sowohl der Setup-Assistent als auch der Docker-Aufbau die Datenbank anlegen.
8. ~~Rollenwechsel ist wirkungslos.~~ `isAdmin()` liest die aktuelle Rolle; für die wenigen Stellen, die während eines Wechsels erreichbar bleiben müssen, gibt es eine eigene Funktion.
9. ~~Fehlgeschlagener Master-Abruf im Datei-Sync wird als „alles aktuell" angezeigt.~~ Behoben am 16.09.2026: Ein Verbindungsfehler wird als solcher gemeldet, nicht als Urteil.
10. ~~TLS-Zertifikatsprüfung projektweit deaktiviert.~~ Behoben am 15.09.2026 an sechzehn Stellen. Die Begründung für das Abschalten — ein selbstsigniertes Zertifikat auf dem NAS — erwies sich beim Messen als falsch.
11. ~~Ungenutzte zweite Datenbankverbindung.~~ In 4.3.3 entfernt.
12. ~~`public.php` versendet E-Mails ohne Rate-Limit, der POST-Zweig läuft vor der Token-Prüfung.~~ Behoben am 25.09.2026 — vier Wochen nachdem es hier aufgeschrieben wurde, und nur, weil dieses Dokument für die Veröffentlichung durchgesehen wurde. `bin/public_reihenfolge_pruefen.php` wacht jetzt über die Reihenfolge.
13. ~~Kein CSRF-Schutz auf dem Hub, „abgemildert durch `SameSite=Strict`“.~~ Die Abmilderung gab es nicht: Der Hub setzte die Cookie-Merkmale per `php_value` in der `.htaccess`, und sein Server befolgt das nicht — gemessen am 28. September 2026 kam das Cookie ohne `Secure`, `HttpOnly` und `SameSite`. Seit 4.3.29 setzt der Hub sie im Code, bevor eine Sitzung beginnt, und weist verändernde Anfragen ab, die der Browser als `Sec-Fetch-Site: cross-site` kennzeichnet. Der Hub gehört nicht zum veröffentlichten Paket.
14. ~~Zwei Registrierungsstellen für einen Service Worker.~~ Seit 4.3.29 eine, siehe Abschnitt 10.

## 13. Wo man mit dem Lesen anfängt

Für einen ersten Überblick in dieser Reihenfolge:

1. **`db.php`** — Bootstrap, Auth, Berechtigungen, DB-Wrapper. Wer diese Datei versteht, versteht 80 Prozent der Konventionen.
2. **`index.php`** — die komplexeste Seite: Filter, Sortierung, Pagination, Massenaktionen, Sichtbarkeitsregeln.
3. **`migrate.php`** mit **`backend/migration_runner.php`** — wie eine Datenbankänderung angewendet wird, von einer Installation selbst oder über die ganze Flotte.
4. **`version_agent.php`** — kurz und in sich geschlossen; die Instanzseite des Versionsvergleichs. Sync-Agent und Hub gehören nicht zum veröffentlichten Repository.
5. **`security.php`** — CSRF, Fingerprint, Upload-Prüfung.

Für einen kritischen Blick lohnen sich vor allem `helpers.php` (1.516 Zeilen, gewachsen).

---

*Dieses Dokument beschreibt den Stand vom 30. September 2026, Version 4.3.30. Es wurde aus dem Quellcode rekonstruiert und vor der Veröffentlichung dagegen geprüft; wo Aussagen nicht überprüfbar waren — insbesondere zu `config.php`, die nicht im Repository liegt — ist das kenntlich gemacht.*
