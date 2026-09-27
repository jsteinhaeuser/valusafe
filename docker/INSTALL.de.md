# ValuSafe mit Docker installieren

Diese Anleitung führt von einem leeren System zu einer laufenden ValuSafe-Installation.
Sie setzt keine Docker-Kenntnisse voraus, aber einen Rechner, auf dem Docker läuft.

**Stand:** 29.08.2026 · gilt ab Version 4.3.3 · auf einer Synology durchlaufen

---

## 1. Voraussetzungen

**Docker mit Compose v2.** Prüfen:

```
docker version --format '{{.Server.Version}}'
docker compose version
```

Beide Befehle müssen eine Versionsnummer ausgeben. Meldet der zweite
`'compose' is not a docker command`, ist die Docker-Installation zu alt —
ValuSafe braucht Compose v2.

**Ein Container-Netz mit Verbindung nach außen.** Das ist keine
Selbstverständlichkeit und die häufigste Ursache für einen fehlgeschlagenen
Aufbau. Prüfen, bevor irgendetwas anderes passiert:

```
docker run --rm alpine sh -c "ping -c1 -W3 deb.debian.org >/dev/null && echo OK"
```

Kommt `OK`, ist alles gut. Kommt nichts oder ein Fehler, lies zuerst
Abschnitt 7 — der Aufbau würde sonst nach mehreren Minuten mit
`Temporary failure resolving 'deb.debian.org'` abbrechen.

**Etwa 2 GB Plattenplatz** für die beiden Basis-Abbilder (PHP und MariaDB)
und das gebaute ValuSafe-Abbild.

---

## 2. Projektdateien holen

Entweder aus dem Repository klonen:

```
git clone <Repository-Adresse> valusafe
cd valusafe
```

Oder ein Archiv entpacken. Danach muss die Verzeichnisstruktur so aussehen:

```
valusafe/
├── .dockerignore
├── wert/                     ← die Anwendung
├── docker/
│   ├── Dockerfile
│   ├── docker-compose.yml
│   ├── .env.example
│   └── docker/
│       └── entrypoint.sh
└── install/
    └── schema.sql           ← Datenbankschema
```

Fehlt `install/schema.sql`, ist das Archiv unvollständig — ohne diese
Datei entsteht eine leere Datenbank und die Anwendung startet nicht.
Die Datei liegt bewusst außerhalb von `docker/`: sie ist der Bauplan der
Datenbank für alle Installationsarten, nicht nur für Docker.

---

## 3. Konfiguration anlegen

```
cd docker
cp .env.example .env
```

In der `.env` sind **drei** Werte zu setzen. Alle anderen können bleiben.

### DB_PASS und DB_ROOT_PASS

Zwei verschiedene Datenbank-Passwörter. Sie werden nie von Hand eingegeben —
niemand muss sie sich merken. Deshalb ist es am einfachsten und sichersten,
sie erzeugen zu lassen:

```
DBP=$(openssl rand -hex 24)
DBR=$(openssl rand -hex 24)
grep -v -E '^(DB_PASS|DB_ROOT_PASS|APP_PORT)=' .env > .env.tmp
printf 'DB_PASS=%s\nDB_ROOT_PASS=%s\nAPP_PORT=8080\n' "$DBP" "$DBR" >> .env.tmp
mv .env.tmp .env
unset DBP DBR
```

Der Port steht hier auf `8080`; ist er belegt, vor dem Ausfuehren im Befehl
aendern (siehe Abschnitt 3, APP_PORT). `printf` reicht die Werte unveraendert
durch — anders als `sed` interpretiert es nichts.

Wer die Passwörter lieber selbst wählt: `sed` eignet sich dafür schlecht, weil
Sonderzeichen wie `|` oder `/` als Trennzeichen missverstanden werden. Dann die
`.env` in einem Editor bearbeiten.

Bleiben `DB_PASS` oder `DB_ROOT_PASS` leer, bricht `docker compose` mit einer
Meldung ab. Das ist Absicht — früher wich die Konfiguration still auf das
Passwort `changeme` aus.

### ADMIN_USER

Der Anmeldename des ersten Administrators, zum Beispiel `admin`. Dieser Name
wird zugleich als `SUPERADMIN_USERNAME` gesetzt; **nur dieses Konto erreicht die
Benutzerverwaltung** im Backend.

`ADMIN_PASS` bleibt leer. Dann erzeugt der Container beim ersten Start ein
Zufallspasswort und zeigt es einmalig im Log an (siehe Abschnitt 5).

### APP_PORT

Vorgabe ist `8080`. Auf einem NAS oder einem Server mit anderen Diensten ist
dieser Port häufig belegt — dann etwa `8090` eintragen.

### Kontrolle

```
awk -F= '/^(DB_NAME|DB_USER|ADMIN_USER|ADMIN_PASS|APP_PORT)=/{print} \
         /^(DB_PASS|DB_ROOT_PASS)=/{print $1 "=<" length($2) " Zeichen>"}' .env
```

Erwartet werden zwei gefüllte Passwortzeilen, ein gesetzter `ADMIN_USER`, ein
leerer `ADMIN_PASS` und der gewünschte Port.

---

## 4. Bauen und starten

```
docker compose build
docker compose up -d
```

Der erste Aufbau lädt die Basis-Abbilder und übersetzt die PHP-Erweiterungen.
Je nach Leitung und Rechner dauert das fünf bis fünfzehn Minuten. Spätere
Aufbauten sind deutlich schneller.

---

## 5. Zugangsdaten abholen

```
docker compose logs app
```

Im Log steht ein Kasten:

```
  ============================================================
   ValuSafe — Administratorkonto angelegt

     Benutzer:  admin
     Passwort:  malVOXeZ02HBtimrI6XEjy7M

   Dieses Passwort wird nur EINMAL angezeigt. Notiere es jetzt
   oder aendere es nach der ersten Anmeldung unter Profil.
  ============================================================
```

Das Passwort erscheint **nur bei diesem einen Start**. Bei jedem weiteren Start
meldet der Container nur noch, dass die Benutzertabelle nicht leer ist.

Geht es verloren, hilft nur ein vollständiger Neuanfang (Abschnitt 7) — oder
das Zurücksetzen über die Datenbank.

Wurde `ADMIN_PASS` in der `.env` gesetzt, erscheint das Passwort nicht im Log;
es gilt dann das dort eingetragene.

---

## 6. Erste Anmeldung und Abnahme

Im Browser `http://localhost:8080` aufrufen — beziehungsweise die IP des
Servers und den in `APP_PORT` gesetzten Port.

Vier Dinge nach der Anmeldung prüfen:

1. **Anmeldung** mit Benutzername und Passwort aus dem Log.
2. **Backend → Benutzer** ist erreichbar. Ist es das nicht, stimmt `ADMIN_USER`
   in der `.env` nicht mit dem angemeldeten Konto überein.
3. **Einen Gegenstand anlegen**, mit Raum und Standort. Damit ist bestätigt,
   dass das vollständige Schema eingespielt wurde.
4. **Eigenes Passwort setzen** unter Profil, falls es erzeugt wurde.

Danach empfiehlt sich ein Blick in **Einstellungen**: Instanzname, Sprache,
Themenauswahl.

---

## 7. Wenn etwas nicht funktioniert

### „Temporary failure resolving 'deb.debian.org'" beim Aufbau

Die Container haben keine Verbindung nach außen. Der Aufbau kann nicht
gelingen, solange das so ist. Eingrenzen:

```
docker run --rm alpine ping -c1 -W3 1.1.1.1
```

Schlägt schon das fehl, ist es keine Namensauflösung, sondern die
Weiterleitung. Dann prüfen:

```
cat /proc/sys/net/ipv4/ip_forward          # muss 1 sein
iptables -t nat -L POSTROUTING -n -v | grep -i masq
```

Stehen die MASQUERADE-Zähler auf 0, erreichen die Pakete diese Regeln gar
nicht — es blockiert eine Firewall davor.

**Auf Synology-Geräten** ist fast immer die DSM-Firewall die Ursache. Sie legt
eine Kette `FORWARD_FIREWALL` an, die *vor* den Docker-Regeln läuft und am Ende
alles verwirft, was sie nicht ausdrücklich erlaubt hat:

```
iptables -S FORWARD_FIREWALL | tail -5
```

Typischerweise steht dort eine Ausnahme für den eigenen LAN-Bereich, eine für
einige Ports, und danach `-j DROP`. Container senden aus `172.17.0.x` an
beliebige Ports — sie treffen keine der Ausnahmen und laufen in das `DROP`.
Die Docker-Regeln dahinter werden nie erreicht; daran erkennt man es auch an
den MASQUERADE-Zählern, die auf 0 stehen bleiben.

Behoben wird das in DSM unter **Systemsteuerung → Sicherheit → Firewall**, mit
einer zusätzlichen Regel:

| Feld | Wert |
|---|---|
| Ports | Alle |
| Quell-IP | Subnetz `172.16.0.0` / `255.240.0.0` |
| Aktion | Zulassen |

Die Maske deckt `172.16.x` bis `172.31.x` ab, also sämtliche Docker-Netze. Die
Regel muss **oberhalb** der abschließenden Verweigern-Regel stehen.

Nicht auf der Kommandozeile mit `iptables` nachhelfen: Synology baut die Ketten
bei jedem Neustart und bei jeder Änderung in der Oberfläche neu auf, von Hand
gesetzte Regeln verschwinden dabei.

### Anmeldung mit keinem Passwort möglich

Meist ist das Schema nicht eingespielt worden, weil das Datenbank-Volume
bereits Daten enthielt — Docker führt `init.sql` **nur bei leerem Volume** aus.
Der Entrypoint erkennt das inzwischen und bricht mit einer Meldung ab; ältere
Fassungen starteten stumm ohne Konto. Prüfen:

```
docker compose logs app | tail -30
docker compose logs db  | tail -30
```

### Port bereits belegt

```
Error starting userland proxy: listen tcp4 0.0.0.0:8080: bind: address already in use
```

In der `.env` einen anderen `APP_PORT` setzen, dann `docker compose up -d`.

### Vollständiger Neuanfang

**Löscht alle Daten dieser Installation** — Gegenstände, Bilder, Benutzer:

```
docker compose down -v
docker compose up -d
```

Das `-v` entfernt die Volumes. Ohne `-v` bleibt die Datenbank erhalten, und
`init.sql` läuft erneut nicht.

---

## 8. Datensicherung

Drei Dinge sind zu sichern:

| Was | Wo |
|---|---|
| Datenbank | Volume `db_data` |
| Bilder | Volume `uploads` |
| Dokumente | Volume `documents` |

Die Anwendung bringt unter **Backend → Backup** eine eigene Sicherung mit, die
Datenbank und Dateien in ein Archiv schreibt. Für automatische Sicherungen gibt
es `cron_backup.php`; der dafür nötige Token liegt nach dem ersten Start unter
`backups/.backup_token` im gleichnamigen Volume.

---

## 9. Was diese Anleitung nicht abdeckt

- **Betrieb über HTTPS.** Die Beschreibung endet bei einer Installation im
  eigenen Netz. Wer ValuSafe aus dem Internet erreichbar macht, braucht einen
  vorgeschalteten Reverse Proxy mit Zertifikat. Der Entrypoint deaktiviert die
  HTTPS-Umleitung in `.htaccess` bereits, damit ein solcher Proxy funktioniert.
- **phpMyAdmin** ist in `docker-compose.yml` enthalten, startet aber nur mit
  `docker compose --profile dev up -d`. Für den regulären Betrieb nicht
  verwenden.
- **Aktualisierung auf eine neue ValuSafe-Version.** Bisher nicht beschrieben.

---

## Anhang: Prüfstand dieser Anleitung

Diese Anleitung wurde am 29.08.2026 vollständig durchlaufen — von einem leeren
Verzeichnis bis zur angemeldeten Anwendung. Testumgebung: Synology NAS,
Container Manager (Docker 24.0.2, Compose 2.20.1), btrfs.

**Bestätigt:** Aufbau des Abbilds, Erstinitialisierung der Datenbank mit allen
22 Tabellen, Anlage des Administratorkontos mit Passwortausgabe im Log,
Anmeldung im Browser, Zugriff auf die Benutzerverwaltung, Anlegen eines
Gegenstands mit Raum und Standort, Passwortwechsel im Profil.

Drei Punkte der Anleitung entstanden erst aus diesem Durchlauf: die Netzprobe
in Abschnitt 1 (die Container hatten wegen einer Firewall-Regel keine
Verbindung nach außen), die Wartezeit für die Erstinitialisierung, und der
Hinweis auf die DSM-Firewall in Abschnitt 7.

**Weiterhin nicht geprüft** ist alles, was Abschnitt 9 aufzählt: der Betrieb
hinter einem Reverse Proxy mit HTTPS, und die Aktualisierung einer bestehenden
Installation auf eine neuere ValuSafe-Version.
