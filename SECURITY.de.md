# Sicherheitsrichtlinie

ValuSafe ist eine selbst gehostete Inventarverwaltung für Wertsachen,
geschrieben und gepflegt von einer einzelnen Person. Diese Datei sagt, wohin
eine Sicherheitsmeldung gehört und was danach passiert — auch die Teile, die
unbequemer sind als in einer Firmenrichtlinie.

## Eine Schwachstelle melden

Eine E-Mail an **valusafe@palindrom.de**. Deutsch oder Englisch, beides ist recht.

Bitte kein öffentliches Issue und keine Veröffentlichung der Einzelheiten,
bevor das Problem behoben ist.

Hilfreich in einer Meldung:

- worum es geht, in ein oder zwei Sätzen
- die Datei und, falls vorhanden, die Zeile
- die Schritte, mit denen es sich nachstellen lässt
- die ValuSafe-Version, die Sie angesehen haben (`wert-update/version.json`)
- was ein Angreifer damit anfangen könnte — Ihre Einschätzung genügt, auch
  wenn sie grob ist

Wenn Sie verschlüsselt schreiben möchten, schicken Sie zuerst eine leere Mail
mit diesem Wunsch; dann verabreden wir einen Schlüssel. Es gibt bewusst keinen
veröffentlichten PGP-Schlüssel — einer, den nie jemand benutzt hat, ist
schlechter als keiner.

## Was Sie erwarten dürfen

Das hier ist keine Firma. Es gibt keine Rufbereitschaft und kein Ticketsystem.

- Empfangsbestätigung: in der Regel innerhalb weniger Tage.
- Einschätzung, ob es ein echtes Problem ist: etwa zwei Wochen.
- Behebung: so schnell, wie die Schwere es verlangt — aber ehrlich gesagt
  können eine Reise oder eine schlechte Woche das verzögern. Dann bekommen
  Sie Bescheid, statt zu warten.

Wenn drei Wochen lang gar nichts kommt, gehen Sie davon aus, dass die Mail
verlorengegangen ist, und schicken Sie sie noch einmal.

## Geltungsbereich

**Dazu gehört** der Quelltext aus dem Auslieferungspaket: die Anwendung im
Wurzelverzeichnis, `backend/`, `ajax/`, `components/`, `install/setup.php`
und `install/schema.sql`.

**Nicht dazu gehören:**

- Instanzen, die andere Leute betreiben. Das sind fremde Server.
- Die eigenen Instanzen des Entwicklers und der interne Verwaltungs-Hub.
  Ein Fund dort ist trotzdem willkommen — aber melden Sie ihn, testen Sie
  nicht dagegen.
- Die öffentliche Demo auf `valusafe.2ix.de`. Ihre Zugangsdaten stehen mit
  Absicht auf der Projektseite, damit jeder die Anwendung ausprobieren kann.
  Dass man sich dort mit bekannten Zugangsdaten anmelden kann, ist kein Fund.
- Fehlende Ratenbegrenzung oder Härtung auf einer absichtlich offenen Demo.
- Alles, was physischen Zugriff auf den Server voraussetzt, oder einen
  Administrator, der gegen die eigene Installation arbeitet.

## Unterstützte Versionen

Nur die jeweils neueste Fassung bekommt Korrekturen. Es gibt keine
Rückportierung in ältere Versionen — bei einem einzigen Entwickler hieße ein
zweiter gepflegter Zweig, dass keiner von beiden die nötige Aufmerksamkeit
bekommt.

Die aktuell veröffentlichte Version steht in
[`wert/wert-update/version.json`](wert/wert-update/version.json).

## Nach einer Meldung

Eine bestätigte Schwachstelle wird behoben, veröffentlicht und in der Chronik
vermerkt. Der Eintrag beschreibt, was falsch war und was sich geändert hat; er
nennt eine Sicherheitskorrektur nicht stillschweigend eine „Verbesserung".

Sie werden namentlich genannt, wenn Sie das möchten, und nicht, wenn Sie das
nicht möchten. Bitte schreiben Sie dazu, was Ihnen lieber ist.

Es gibt kein Kopfgeld. Für eine Meldung wird nichts bezahlt. Das steht hier,
damit niemand einen Abend in ValuSafe steckt und etwas anderes erwartet.

## Was diese Datei nicht ist

ValuSafe ist derzeit bei keiner CVE-Vergabestelle registriert, und das
Quelltext-Repository ist noch nicht öffentlich. Eine Meldung wird deshalb
behoben und dokumentiert, bekommt aber keine CVE-Nummer. Das kann sich
ändern; bis dahin scheint es besser, das klar zu sagen, als ein Verfahren
anzudeuten, das es nicht gibt.

---

English version: [SECURITY.md](SECURITY.md)
