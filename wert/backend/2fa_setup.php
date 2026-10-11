<?php
/**
 * backend/2fa_setup.php — seit 4.3.34 nur noch Weiterleitung.
 * Die Einrichtung liegt jetzt in ../2fa_setup.php und steht allen Rollen offen
 * (vorher hier hinter requireBackendAccess, also nur Admin). Die Datei bleibt,
 * damit Lesezeichen, Seitenleiste und Verwaltungs-Kachel weiter funktionieren.
 */
header('Location: ../2fa_setup.php', true, 302);
exit;
