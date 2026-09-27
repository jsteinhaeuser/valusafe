<?php
require_once 'config.php';
require_once 'db.php';
include 'header_next_page.php';
?>

<style>
.datenschutz { max-width: 800px; margin: 0 auto; }
.datenschutz h2 { margin-top: 32px; margin-bottom: 10px; }
.datenschutz h3 { margin-top: 24px; margin-bottom: 8px; color: var(--text-color, #333); }
.datenschutz p, .datenschutz ul { margin-bottom: 14px; line-height: 1.7; }
.datenschutz ul { padding-left: 20px; }
.datenschutz li { margin-bottom: 6px; }
.datenschutz .stand { font-size: 13px; color: #6b7280; margin-bottom: 28px; }
</style>

<div class="datenschutz">

<!--
    HINWEIS FUER BETREIBER DIESER INSTALLATION
    ==========================================
    Die folgenden Angaben sind Platzhalter. Wer ValuSafe oeffentlich
    erreichbar betreibt, ist in Deutschland nach Paragraph 5 TMG (Impressum)
    und nach der DSGVO (Verantwortlicher) verpflichtet, HIER die eigenen
    Angaben einzutragen. Die Platzhalter erfuellen diese Pflicht nicht.
    Wer die Anwendung nur im eigenen Netz betreibt, braucht beides nicht -
    kann diese Seiten dann aber auch stehen lassen.
-->
<h2>Datenschutzerklärung</h2>
<p class="stand">Stand: März 2026</p>

<h3>1. Verantwortlicher</h3>
<p>
    [Ihr Name]<br>
    [Strasse und Hausnummer]<br>
    [PLZ und Ort]<br>
    [Land]<br><br>
    E-Mail: <a href="mailto:name@beispiel.de">name@beispiel.de</a><br>
    Telefon: [Ihre Telefonnummer]
</p>

<h3>2. Welche Daten werden gespeichert?</h3>
<p>Diese Anwendung speichert ausschließlich Daten die Sie selbst eingeben:</p>
<ul>
    <li>Ihr Benutzername und Passwort (Passwort verschlüsselt mit Argon2id)</li>
    <li>Gegenstände, Kategorien und Orte die Sie anlegen</li>
    <li>Bilder und Dokumente die Sie hochladen</li>
    <li>Ihre Spracheinstellung und Thema-Auswahl</li>
</ul>
<p>Zusätzlich werden technisch notwendige Daten protokolliert:</p>
<ul>
    <li>Aktivitätsprotokoll (wer hat welchen Gegenstand wann bearbeitet)</li>
    <li>Fehlgeschlagene Login-Versuche (für Sicherheitszwecke, 90 Tage)</li>
</ul>

<h3>3. Wofür werden die Daten genutzt?</h3>
<p>Ausschließlich für den Betrieb der Inventarverwaltung. Ihre Daten werden nicht an Dritte weitergegeben, nicht für Werbung genutzt und nicht analysiert.</p>

<h3>4. Cookies und Sessions</h3>
<p>Die Anwendung verwendet eine Session-Cookie die für die Anmeldung technisch notwendig ist. Dieser Cookie wird beim Abmelden oder nach Ablauf der Sitzung (1 Stunde Inaktivität) automatisch gelöscht. Es werden keine Tracking-Cookies oder Analyse-Cookies gesetzt.</p>

<h3>5. Hosting</h3>
<p>Die Anwendung wird gehostet bei:</p>
<p>
    [Name des Hosters]<br>
    [Anschrift des Hosters]<br>
    <a href="https://example.org/datenschutz">[Datenschutzinformationen des Hosters]</a>
</p>
<p>Der Hoster verarbeitet Ihre Daten nur soweit dies für die Bereitstellung des Dienstes notwendig ist (Auftragsverarbeitung gemäß Art. 28 DSGVO).</p>

<h3>6. Ihre Rechte</h3>
<p>Sie haben folgende Rechte bezüglich Ihrer gespeicherten Daten:</p>
<ul>
    <li><strong>Auskunft</strong> (Art. 15 DSGVO) — Welche Daten sind gespeichert?</li>
    <li><strong>Berichtigung</strong> (Art. 16 DSGVO) — Fehlerhafte Daten korrigieren</li>
    <li><strong>Löschung</strong> (Art. 17 DSGVO) — Ihre Daten löschen lassen</li>
    <li><strong>Einschränkung</strong> (Art. 18 DSGVO) — Verarbeitung einschränken</li>
    <li><strong>Datenübertragbarkeit</strong> (Art. 20 DSGVO) — Daten in maschinenlesbarem Format erhalten</li>
    <li><strong>Widerspruch</strong> (Art. 21 DSGVO) — Der Verarbeitung widersprechen</li>
</ul>
<p>Zur Ausübung Ihrer Rechte wenden Sie sich bitte per E-Mail an: <a href="mailto:name@beispiel.de">name@beispiel.de</a></p>

<h3>7. Beschwerderecht</h3>
<p>Sie haben das Recht, sich bei einer Datenschutz-Aufsichtsbehörde zu beschweren. Die zuständige Behörde für Nordrhein-Westfalen ist:</p>
<p>
    Landesbeauftragte für Datenschutz und Informationsfreiheit NRW<br>
    Postfach 20 04 44<br>
    40102 Düsseldorf<br>
    <a href="https://www.ldi.nrw.de" target="_blank" rel="noopener">www.ldi.nrw.de</a>
</p>

<h3>8. Datensicherheit</h3>
<p>Alle Verbindungen zur Anwendung sind SSL/TLS-verschlüsselt (HTTPS). Passwörter werden mit Argon2id gehasht und niemals im Klartext gespeichert. Der Zugriff ist durch Rollen und Berechtigungen gesichert.</p>

<h3>9. Datenlöschung</h3>
<p>Aktivitätsprotokolle werden automatisch nach 90 Tagen gelöscht. Ihre Inventardaten bleiben so lange gespeichert wie Ihr Konto aktiv ist. Zur Löschung Ihres Kontos und aller zugehörigen Daten wenden Sie sich bitte an den Administrator.</p>

</div>

<?php include 'footer_next.php'; ?>
