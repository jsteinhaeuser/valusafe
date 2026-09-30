<?php
/**
 * dsgvo_helpers.php — Datenauskunft (Art. 15/20) und Kontoloeschung (Art. 17)
 *
 * Bis 4.3.29 standen beide Abfragen direkt in settings.php, mit Spaltennamen,
 * die es nicht gibt (users.rolle/created_at, activity_log.benutzer_id/
 * datensatz_name/erstellt_am). Der Export lieferte deshalb still ein leeres
 * Konto und keine Aktivitaeten, die Loeschung scheiterte bei jedem Konto mit
 * einem SQL-Fehler. Hier stehen sie mit den Spalten aus install/schema.sql;
 * tests/DsgvoHelpersTest.php prueft sie gegen eine nachgebaute Datenbank.
 *
 * Beide Funktionen nehmen ein PDO-Objekt und werfen bei Fehlern (PDOException)
 * — ein halber Export oder eine halbe Loeschung soll nicht still durchgehen.
 */

if (!function_exists('dsgvoDatenSammeln')) {
    /**
     * Alles, was ValuSafe zu einem Konto gespeichert hat, fuer den Download.
     * Passwort-Hash, TOTP-Geheimnis und Wiederherstellungscodes gehoeren nicht
     * hinein: Sie sind Zugangsdaten, keine Auskunft.
     */
    function dsgvoDatenSammeln(PDO $pdo, int $userId, string $username): array
    {
        $stmt = $pdo->prepare(
            "SELECT id, username, email, role, theme, lang, standard_ersteller,
                    erstellt_am, letzter_login, totp_enabled
             FROM users WHERE id = ?"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $stmt = $pdo->prepare(
            "SELECT w.*, k.name AS kategorie, r.name AS raum
             FROM wertsachen w
             LEFT JOIN kategorien k ON w.kategorie_id = k.id
             LEFT JOIN raeume r ON w.raum_id = r.id
             WHERE w.erstellt_von = ?
             ORDER BY w.name"
        );
        $stmt->execute([$username]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare(
            "SELECT aktion, tabelle, datensatz_id, bezeichnung, zeitstempel, ip_adresse
             FROM activity_log WHERE user_id = ?
             ORDER BY zeitstempel DESC LIMIT 500"
        );
        $stmt->execute([$userId]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'export_datum' => date('Y-m-d H:i:s'),
            'benutzer'     => $user,
            'gegenstaende' => $items,
            'aktivitaeten' => $activities,
        ];
    }
}

if (!function_exists('dsgvoKontoLoeschen')) {
    /**
     * Loescht ein Konto und loest es von allem, was bleibt.
     *
     * Gegenstaende bleiben erhalten (so steht es auf der Seite), ebenso die
     * Eintraege im Aktivitaets- und Sicherheitsprotokoll — dort aber ohne
     * Verweis auf das Konto; im Aktivitaetsprotokoll faellt auch die IP weg.
     * Das Sicherheitsprotokoll behaelt seine IPs: Es dient der Abwehr von
     * Angriffen und wird nach 90 Tagen automatisch geleert (seit 4.3.31,
     * securityLogKuerzen() in security.php).
     *
     * Rueckgabe: Dateiname des Profilbilds oder null — die Datei loescht der
     * Aufrufer, weil nur er das Upload-Verzeichnis kennt.
     */
    function dsgvoKontoLoeschen(PDO $pdo, int $userId): ?string
    {
        $stmt = $pdo->prepare("SELECT username, avatar FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $konto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$konto) {
            return null;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE activity_log SET user_id = NULL, ip_adresse = NULL WHERE user_id = ?")
                ->execute([$userId]);
            $pdo->prepare("UPDATE security_log SET user_id = NULL WHERE user_id = ?")
                ->execute([$userId]);
            $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")
                ->execute([$userId]);
            $pdo->prepare("DELETE FROM user_activity WHERE user_id = ?")
                ->execute([$userId]);
            $pdo->prepare("DELETE FROM login_attempts WHERE username = ?")
                ->execute([$konto['username']]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")
                ->execute([$userId]);
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $konto['avatar'] ?: null;
    }
}
