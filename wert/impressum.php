<?php
require_once 'config.php';
require_once 'db.php';
include 'header_next_page.php';
?>

<style>
.impressum { max-width: 600px; margin: 0 auto; }
.impressum h2 { margin-bottom: 20px; }
.impressum h3 { margin-top: 28px; margin-bottom: 8px; }
.impressum p { margin-bottom: 14px; line-height: 1.7; }
</style>

<div class="impressum">

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
<h2>Impressum</h2>

<h3>Angaben gemäß § 5 TMG</h3>
<p>
    [Ihr Name]<br>
    [Strasse und Hausnummer]<br>
    [PLZ und Ort]<br>
    [Land]
</p>

<h3>Kontakt</h3>
<p>
    Telefon: [Ihre Telefonnummer]<br>
    E-Mail: <a href="mailto:name@beispiel.de">name@beispiel.de</a>
</p>

<h3>Hinweis</h3>
<p>Diese Anwendung ist ein privates Projekt zur Verwaltung von Wertsachen und Inventar. Sie ist nicht für den allgemeinen öffentlichen Zugang bestimmt.</p>

</div>

<?php include 'footer_next.php'; ?>
