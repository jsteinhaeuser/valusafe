<?php
/**
 * ValuSafe Setup Wizard
 *
 * Fuehrt durch die Installation auf einem gewoehnlichen Webhosting-Paket.
 * Braucht neben sich die Datei schema.sql — denselben Bauplan, aus dem auch
 * die Docker-Installation ihre Datenbank aufbaut.
 *
 * SICHERHEIT: Der Assistent versucht am Ende, sich selbst und schema.sql zu
 * loeschen. Gelingt das nicht, sagt er es und die beiden Dateien muessen von
 * Hand entfernt werden.
 *
 * Die Installation ist eigenstaendig — sie meldet sich nirgends an und wird
 * von nirgends verwaltet.
 */

session_start();

// Bereits installiert?
//
// Zwei Bedingungen, und die Verknuepfung ist mit Bedacht gewaehlt.
//
// Beide Dateien zu VERLANGEN waere zu wenig: fehlt .setup_complete —
// versehentlich geloescht, beim Umzug verloren, vom FTP-Programm als
// Punktdatei uebersprungen —, liefe der Assistent auf einer fertigen
// Installation wieder an und liesse sich eine neue Datenbank und ein neues
// Administratorkonto verdrahten.
//
// Ein schlichtes ODER waere zu viel: config.php entsteht in Schritt 3, die
// Schritte 4 und 5 kommen danach — der Assistent sperrte sich selbst aus.
//
// Also: .setup_complete sperrt immer. Eine config.php ohne .setup_complete
// sperrt nur, wenn KEIN Setup laeuft. Wer die Seite frisch aufruft, hat kein
// setup_step in der Sitzung und kommt keinen Schritt weit; die Pruefung steht
// vor jeder Verarbeitung von Eingaben.
$setupLaeuft = isset($_SESSION['setup_step']) && (int)$_SESSION['setup_step'] > 0;
if (file_exists(__DIR__ . '/.setup_complete')
    || (file_exists(__DIR__ . '/config.php') && !$setupLaeuft)) {
    die('<div style="font-family:sans-serif;max-width:500px;margin:80px auto;padding:32px;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.1);text-align:center;">
        <div style="font-size:48px;margin-bottom:16px;">🔒</div>
        <h2 style="color:#dc2626;margin:0 0 12px;">Setup bereits abgeschlossen</h2>
        <p style="color:#555;">ValuSafe ist bereits installiert. Bitte lösche <code>setup.php</code> vom Server.</p>
        <a href="index.php" style="display:inline-block;margin-top:20px;padding:10px 24px;background:#185fa5;color:#fff;border-radius:8px;text-decoration:none;">→ Zum Login</a>
    </div>');
}

// ── Sprachen ────────────────────────────────────────────────────────────────
$lang = $_SESSION['setup_lang'] ?? 'de';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'])) {
    $lang = $_GET['lang'];
    $_SESSION['setup_lang'] = $lang;
}

$t = [
    'de' => [
        'title'         => 'ValuSafe Installation',
        'step_lang'     => 'Sprache',
        'step_check'    => 'Systemprüfung',
        'step_db'       => 'Datenbank',
        'step_install'  => 'Installation',
        'step_admin'    => 'Admin-Konto',
        'step_done'     => 'Fertig',
        'next'          => 'Weiter →',
        'back'          => '← Zurück',
        'required'      => 'Pflichtfeld',
        'error'         => 'Fehler',
        'success'       => 'Erfolgreich',
        // Step 0
        's0_title'      => 'Willkommen bei ValuSafe',
        's0_subtitle'   => 'Dieser Assistent führt dich in wenigen Schritten durch die Installation.',
        's0_choose'     => 'Bitte wähle deine Sprache:',
        // Step 1
        's1_title'      => 'Systemprüfung',
        's1_subtitle'   => 'Dein Webserver wird auf die Mindestanforderungen geprüft.',
        's1_php'        => 'PHP-Version',
        's1_php_ok'     => 'PHP %s — OK',
        's1_php_fail'   => 'PHP %s — mindestens PHP 8.2 erforderlich',
        's1_pdo'        => 'PDO MySQL',
        's1_pdo_ok'     => 'Verfügbar',
        's1_pdo_fail'   => 'Nicht verfügbar — bitte beim Hoster aktivieren',
        's1_gd'         => 'GD Bildbibliothek',
        's1_gd_ok'      => 'Verfügbar',
        's1_gd_fail'    => 'Nicht verfügbar — Bildverarbeitung eingeschränkt',
        's1_zip'        => 'ZIP-Erweiterung',
        's1_zip_ok'     => 'Verfügbar',
        's1_zip_fail'   => 'Nicht verfügbar — Backup-Funktion eingeschränkt',
        's1_write'      => 'Schreibrechte Hauptordner',
        's1_write_ok'   => 'Schreibzugriff vorhanden',
        's1_write_fail' => 'Kein Schreibzugriff — config.php manuell erstellen',
        's1_upload'     => 'Upload-Ordner',
        's1_upload_ok'  => 'Vorhanden und beschreibbar',
        's1_upload_fail'=> 'Ordner fehlt oder nicht beschreibbar',
        's1_all_ok'     => 'Alle Prüfungen bestanden — weiter zur Datenbank-Konfiguration.',
        's1_warn'       => 'Warnungen vorhanden — du kannst trotzdem fortfahren, einige Funktionen könnten eingeschränkt sein.',
        's1_fatal'      => 'Kritische Fehler — bitte behebe die oben markierten Punkte bevor du fortfährst.',
        // Step 2
        's2_title'      => 'Datenbank-Konfiguration',
        's2_subtitle'   => 'Trage die Zugangsdaten deiner MySQL/MariaDB-Datenbank ein.',
        's2_host'       => 'Datenbankhost',
        's2_host_hint'  => 'Bei den meisten Hostern: <strong>localhost</strong>. Den genauen Wert findest du in deinem Hosting-Control-Panel unter "MySQL" oder "Datenbanken".',
        's2_name'       => 'Datenbankname',
        's2_name_hint'  => 'Name der Datenbank die du für ValuSafe angelegt hast. Lege sie vorher im Control-Panel deines Hosters an.',
        's2_user'       => 'Datenbankbenutzer',
        's2_user_hint'  => 'Der MySQL-Benutzer mit Vollzugriff auf die oben genannte Datenbank.',
        's2_pass'       => 'Datenbankpasswort',
        's2_pass_hint'  => 'Das Passwort des Datenbankbenutzers. Leer lassen wenn kein Passwort gesetzt.',
        's2_test'       => '🔌 Verbindung testen',
        's2_test_ok'    => '✅ Verbindung erfolgreich!',
        's2_test_fail'  => '❌ Verbindung fehlgeschlagen: ',
        // Step 3
        's3_title'      => 'Datenbank-Installation',
        's3_subtitle'   => 'Die Datenbanktabellen werden jetzt angelegt.',
        's3_running'    => 'Tabellen werden erstellt…',
        's3_ok'         => '✅ Alle Tabellen erfolgreich angelegt.',
        's3_fail'       => '❌ Fehler beim Anlegen der Tabellen: ',
        's3_config_ok'  => '✅ config.php wurde automatisch erstellt.',
        's3_config_manual'  => '⚠️ config.php konnte nicht automatisch erstellt werden.',
        's3_config_copy'    => 'Kopiere den folgenden Inhalt, erstelle eine Datei <code>config.php</code> im Hauptverzeichnis und lade sie per FTP hoch. Klicke danach auf "Weiter".',
        's3_config_copied'  => 'Ich habe config.php hochgeladen →',
        's3_schema_missing' => 'Die Datei schema.sql fehlt oder ist unlesbar. Sie gehört neben setup.php und enthält den Bauplan der Datenbank — ohne sie lässt sich nichts anlegen.',
        's3_migrations'     => 'Migrationsstand vermerkt: %d Migrationen sind im Schema bereits enthalten.',
        // Step 4
        's4_title'      => 'Admin-Konto anlegen',
        's4_subtitle'   => 'Erstelle deinen Administrator-Account für ValuSafe.',
        's4_user'       => 'Benutzername',
        's4_user_hint'  => 'Dein Login-Name. Nur Buchstaben, Zahlen und Unterstriche.',
        's4_email'      => 'E-Mail-Adresse',
        's4_email_hint' => 'Für zukünftige Benachrichtigungen (optional).',
        's4_pass'       => 'Passwort',
        's4_pass_hint'  => 'Mindestens 8 Zeichen.',
        's4_pass2'      => 'Passwort bestätigen',
        's4_pass_mismatch' => 'Die Passwörter stimmen nicht überein.',
        's4_pass_short' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',
        's4_user_invalid' => 'Benutzername darf nur Buchstaben, Zahlen und _ enthalten.',
        // Step 5
        's5_title'      => '🎉 Installation abgeschlossen!',
        's5_subtitle'   => 'ValuSafe ist einsatzbereit.',
        's5_delete'     => '⚠️ Wichtig: Bitte lösche jetzt <code>setup.php</code> und <code>schema.sql</code> vom Server!',
        's5_delete_hint'=> 'Solange setup.php vorhanden ist, könnte jemand die Installation überschreiben. schema.sql verrät den Aufbau deiner Datenbank.',
        's5_deleted'    => '✅ <code>setup.php</code> und <code>schema.sql</code> wurden automatisch entfernt. Es ist nichts weiter zu tun.',
        's5_delete_rest'=> '⚠️ Diese Dateien ließen sich nicht löschen und müssen von Hand entfernt werden: ',
        's5_superadmin' => 'Trage bitte noch diese Zeile in deine <code>config.php</code> ein — sie macht dich zum SuperAdmin, der die geschützten Vorgänge freigibt:',
        's5_login'      => '→ Jetzt anmelden',
        's5_credentials'=> 'Deine Zugangsdaten:',
        's5_user'       => 'Benutzername',
        's5_url'        => 'URL',
        // Instance Name
        's2_instance'   => 'Instanz-Name',
        's2_instance_hint' => 'Name dieser ValuSafe-Installation, z.B. "Meine Wertsachen". Wird oben in der App angezeigt.',
    ],
    'en' => [
        'title'         => 'ValuSafe Installation',
        'step_lang'     => 'Language',
        'step_check'    => 'System Check',
        'step_db'       => 'Database',
        'step_install'  => 'Install',
        'step_admin'    => 'Admin Account',
        'step_done'     => 'Done',
        'next'          => 'Continue →',
        'back'          => '← Back',
        'required'      => 'Required',
        'error'         => 'Error',
        'success'       => 'Success',
        's0_title'      => 'Welcome to ValuSafe',
        's0_subtitle'   => 'This wizard will guide you through the installation in a few steps.',
        's0_choose'     => 'Please choose your language:',
        's1_title'      => 'System Check',
        's1_subtitle'   => 'Your web server will be checked for minimum requirements.',
        's1_php'        => 'PHP Version',
        's1_php_ok'     => 'PHP %s — OK',
        's1_php_fail'   => 'PHP %s — PHP 8.2 or higher required',
        's1_pdo'        => 'PDO MySQL',
        's1_pdo_ok'     => 'Available',
        's1_pdo_fail'   => 'Not available — please enable at your hosting provider',
        's1_gd'         => 'GD Image Library',
        's1_gd_ok'      => 'Available',
        's1_gd_fail'    => 'Not available — image processing limited',
        's1_zip'        => 'ZIP Extension',
        's1_zip_ok'     => 'Available',
        's1_zip_fail'   => 'Not available — backup function limited',
        's1_write'      => 'Write permissions (root folder)',
        's1_write_ok'   => 'Write access available',
        's1_write_fail' => 'No write access — create config.php manually',
        's1_upload'     => 'Upload folder',
        's1_upload_ok'  => 'Exists and writable',
        's1_upload_fail'=> 'Folder missing or not writable',
        's1_all_ok'     => 'All checks passed — proceed to database configuration.',
        's1_warn'       => 'Warnings present — you can continue, but some features may be limited.',
        's1_fatal'      => 'Critical errors — please fix the issues above before continuing.',
        's2_title'      => 'Database Configuration',
        's2_subtitle'   => 'Enter your MySQL/MariaDB database credentials.',
        's2_host'       => 'Database Host',
        's2_host_hint'  => 'Usually <strong>localhost</strong> at most hosting providers. Check your hosting control panel under "MySQL" or "Databases".',
        's2_name'       => 'Database Name',
        's2_name_hint'  => 'The name of the database you created for ValuSafe. Create it in your hosting control panel first.',
        's2_user'       => 'Database User',
        's2_user_hint'  => 'The MySQL user with full access to the database above.',
        's2_pass'       => 'Database Password',
        's2_pass_hint'  => 'The database user\'s password. Leave empty if no password is set.',
        's2_test'       => '🔌 Test Connection',
        's2_test_ok'    => '✅ Connection successful!',
        's2_test_fail'  => '❌ Connection failed: ',
        's3_title'      => 'Database Installation',
        's3_subtitle'   => 'The database tables will be created now.',
        's3_running'    => 'Creating tables…',
        's3_ok'         => '✅ All tables created successfully.',
        's3_fail'       => '❌ Error creating tables: ',
        's3_config_ok'  => '✅ config.php was created automatically.',
        's3_config_manual'  => '⚠️ config.php could not be created automatically.',
        's3_config_copy'    => 'Copy the content below, create a file <code>config.php</code> in the root directory and upload it via FTP. Then click "Continue".',
        's3_config_copied'  => 'I have uploaded config.php →',
        's3_schema_missing' => 'The file schema.sql is missing or unreadable. It belongs next to setup.php and holds the database blueprint — without it nothing can be created.',
        's3_migrations'     => 'Migration state recorded: %d migrations are already contained in the schema.',
        's4_title'      => 'Create Admin Account',
        's4_subtitle'   => 'Create your administrator account for ValuSafe.',
        's4_user'       => 'Username',
        's4_user_hint'  => 'Your login name. Only letters, numbers and underscores.',
        's4_email'      => 'Email Address',
        's4_email_hint' => 'For future notifications (optional).',
        's4_pass'       => 'Password',
        's4_pass_hint'  => 'At least 8 characters.',
        's4_pass2'      => 'Confirm Password',
        's4_pass_mismatch' => 'Passwords do not match.',
        's4_pass_short' => 'Password must be at least 8 characters.',
        's4_user_invalid' => 'Username may only contain letters, numbers and _.',
        's5_title'      => '🎉 Installation Complete!',
        's5_subtitle'   => 'ValuSafe is ready to use.',
        's5_delete'     => '⚠️ Important: Please delete <code>setup.php</code> and <code>schema.sql</code> from the server now!',
        's5_delete_hint'=> 'As long as setup.php exists, someone could overwrite the installation. schema.sql reveals the layout of your database.',
        's5_deleted'    => '✅ <code>setup.php</code> and <code>schema.sql</code> were removed automatically. Nothing further to do.',
        's5_delete_rest'=> '⚠️ These files could not be deleted and must be removed by hand: ',
        's5_superadmin' => 'Please add this line to your <code>config.php</code> — it makes you the SuperAdmin who unlocks the protected operations:',
        's5_login'      => '→ Login Now',
        's5_credentials'=> 'Your credentials:',
        's5_user'       => 'Username',
        's5_url'        => 'URL',
        's2_instance'   => 'Instance Name',
        's2_instance_hint' => 'Name of this ValuSafe installation, e.g. "My Valuables". Shown at the top of the app.',
    ],
];
$T = $t[$lang];

/**
 * Zerlegt eine SQL-Datei in einzelne Anweisungen.
 *
 * Ein schlichtes explode(';', $sql) genuegt dafuer nicht: schema.sql enthaelt
 * Kommentare, Zeichenketten mit Satzzeichen und die bedingten Kommentare von
 * MySQL. An jedem Semikolon darin zerrisse es eine Anweisung mitten im Wort.
 *
 * Beruecksichtigt:
 *   'zeichenkette'      auch mit verdoppeltem oder maskiertem Hochkomma
 *   "zeichenkette"      dito
 *   `bezeichner`        Backticks
 *   -- Kommentar        bis Zeilenende (nur mit folgendem Leerzeichen,
 *                       damit "a--b" nicht als Kommentar gilt)
 *   # Kommentar         bis Zeilenende
 *   /* … *\/            mehrzeilig; die bedingte Form /*! … *\/ BLEIBT
 *                       erhalten, MySQL wertet sie als Anweisung aus
 */
function zerlegeSql(string $sql): array
{
    $anweisungen = [];
    $akt   = '';
    $laenge = strlen($sql);
    $i = 0;

    while ($i < $laenge) {
        $c = $sql[$i];

        // Zeichenketten und Bezeichner am Stueck uebernehmen
        if ($c === "'" || $c === '"' || $c === '`') {
            $ende = $c;
            $akt .= $c;
            $i++;
            while ($i < $laenge) {
                $z = $sql[$i];
                if ($z === '\\' && $ende !== '`' && $i + 1 < $laenge) {
                    $akt .= $z . $sql[$i + 1];
                    $i += 2;
                    continue;
                }
                if ($z === $ende) {
                    // Verdoppeltes Zeichen bedeutet: gehoert dazu
                    if ($i + 1 < $laenge && $sql[$i + 1] === $ende) {
                        $akt .= $z . $z;
                        $i += 2;
                        continue;
                    }
                    $akt .= $z;
                    $i++;
                    break;
                }
                $akt .= $z;
                $i++;
            }
            continue;
        }

        // Zeilenkommentar. MySQL verlangt nach den zwei Strichen ein
        // Leerzeichen ODER das Zeilenende — eine nackte Zeile "--" ist also
        // ebenfalls ein Kommentar. Genau die hat die erste Fassung dieser
        // Funktion durchgelassen: schema.sql enthaelt vier davon, und sie
        // landeten am Anfang der jeweils naechsten Anweisung. Ausgefuehrt hat
        // MySQL das trotzdem richtig, weil es den Kommentar selbst noch einmal
        // erkennt — aber verlassen sollte man sich darauf nicht.
        if ($c === '#'
            || ($c === '-' && $i + 1 < $laenge && $sql[$i + 1] === '-'
                && ($i + 2 >= $laenge || strpos(" \t\r\n", $sql[$i + 2]) !== false))) {
            while ($i < $laenge && $sql[$i] !== "\n") $i++;
            continue;
        }

        // Blockkommentar — die bedingte Form /*! bleibt stehen
        if ($c === '/' && $i + 1 < $laenge && $sql[$i + 1] === '*') {
            if ($i + 2 < $laenge && $sql[$i + 2] === '!') {
                $akt .= $c;
                $i++;
                continue;
            }
            $ende = strpos($sql, '*/', $i + 2);
            $i = $ende === false ? $laenge : $ende + 2;
            continue;
        }

        if ($c === ';') {
            if (trim($akt) !== '') $anweisungen[] = trim($akt);
            $akt = '';
            $i++;
            continue;
        }

        $akt .= $c;
        $i++;
    }

    if (trim($akt) !== '') $anweisungen[] = trim($akt);
    return $anweisungen;
}

// ── Step-Verwaltung ──────────────────────────────────────────────────────────
$step = intval($_SESSION['setup_step'] ?? 0);
$error = '';
$info  = '';

// ── POST-Handler ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Verbindungstest (AJAX)
    if ($action === 'test_db') {
        header('Content-Type: application/json');
        try {
            $dsn = 'mysql:host=' . $_POST['db_host'] . ';dbname=' . $_POST['db_name'] . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $_POST['db_user'], $_POST['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            echo json_encode(['ok' => true, 'msg' => $T['s2_test_ok']]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $T['s2_test_fail'] . $e->getMessage()]);
        }
        exit;
    }

    // Step 0 → 1: Sprache gesetzt
    if ($action === 'set_lang') {
        $_SESSION['setup_lang'] = $_POST['lang'] ?? 'de';
        $lang = $_SESSION['setup_lang'];
        $T = $t[$lang];
        $_SESSION['setup_step'] = 1;
        $step = 1;
    }

    // Step 1 → 2: Systemcheck bestanden
    if ($action === 'step1_next') {
        $_SESSION['setup_step'] = 2;
        $step = 2;
    }

    // Step 2 → 3: DB-Daten speichern
    if ($action === 'step2_next') {
        $_SESSION['db_host']     = trim($_POST['db_host'] ?? 'localhost');
        $_SESSION['db_name']     = trim($_POST['db_name'] ?? '');
        $_SESSION['db_user']     = trim($_POST['db_user'] ?? '');
        $_SESSION['db_pass']     = $_POST['db_pass'] ?? '';
        $_SESSION['instance_name'] = trim($_POST['instance_name'] ?? 'ValuSafe');

        if (empty($_SESSION['db_name']) || empty($_SESSION['db_user'])) {
            $error = $lang === 'de' ? 'Bitte alle Pflichtfelder ausfüllen.' : 'Please fill in all required fields.';
            $step = 2;
        } else {
            // Verbindung testen
            try {
                $dsn = 'mysql:host=' . $_SESSION['db_host'] . ';dbname=' . $_SESSION['db_name'] . ';charset=utf8mb4';
                new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $_SESSION['setup_step'] = 3;
                $step = 3;
            } catch (Exception $e) {
                $error = $T['s2_test_fail'] . $e->getMessage();
                $step = 2;
            }
        }
    }

    // Step 3: Installation durchführen
    if ($action === 'step3_install') {
        $dbHost   = $_SESSION['db_host']   ?? '';
        $dbName   = $_SESSION['db_name']   ?? '';
        $dbUser   = $_SESSION['db_user']   ?? '';
        $dbPass   = $_SESSION['db_pass']   ?? '';
        $instName = $_SESSION['instance_name'] ?? 'ValuSafe';

        try {
            $dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            // ── Schema einspielen ────────────────────────────────────────
            //
            // Der Bauplan der Datenbank steht in schema.sql und NUR dort —
            // dieselbe Datei, aus der auch die Docker-Installation ihre
            // Datenbank aufbaut. Eine zweite Fassung des Schemas im
            // Assistenten mitzufuehren hiesse, sie bei jeder Aenderung an der
            // Datenbank ein zweites Mal nachziehen zu muessen; genau daran
            // laufen solche Fassungen auseinander.
            $schemaDatei = __DIR__ . '/schema.sql';
            if (!is_file($schemaDatei) || !is_readable($schemaDatei)) {
                throw new RuntimeException($T['s3_schema_missing']);
            }
            $schema = (string)file_get_contents($schemaDatei);
            if (trim($schema) === '') {
                throw new RuntimeException($T['s3_schema_missing']);
            }

            $anweisungen = zerlegeSql($schema);
            if (count($anweisungen) < 20) {
                throw new RuntimeException($T['s3_schema_missing']);
            }
            foreach ($anweisungen as $stmt) {
                $pdo->exec($stmt);
            }

            // ── Instanzname ──────────────────────────────────────────────
            //
            // Vorbereitet, nicht eingesetzt. Der Name kommt aus einem
            // Formularfeld; im SQL interpoliert wuerde schon ein Hochkomma die
            // ganze Installation zum Abbruch bringen — ein Name wie
            // "Annas Sammlung" reichte dafuer.
            $pdo->prepare("UPDATE `app_settings` SET `setting_value` = ? WHERE `setting_key` = 'instance_name'")
                ->execute([$instName]);

            // ── Migrationsstand vorbelegen ───────────────────────────────
            //
            // schema.sql ist der aktuelle Stand der Datenbank; alles, was die
            // vorhandenen Migrationen bewirken, steckt bereits darin. Ohne diesen
            // Eintrag liefe migrate.php sie beim ersten Aufruf trotzdem
            // durch. Gefaehrlich waere das nicht — nachgemessen: null Fehler,
            // sechsundzwanzigmal "uebersprungen" —, aber Migration 004 legt
            // sechs Lizenzschluessel an, die Migration 006 unmittelbar danach
            // wieder loescht, und der Bericht waere fuer jemanden, der ValuSafe
            // gerade erst aufsetzt, nicht zu deuten.
            //
            // Gelesen wird das Verzeichnis, nicht eine Liste: so gilt der
            // Eintrag auch fuer Migration 009, ohne dass jemand daran denken
            // muss. Der Name ist derselbe, den migrate.php vergibt —
            // basename ohne Endung.
            $migrationen = glob(__DIR__ . '/backend/migrations/*.php') ?: [];
            sort($migrationen, SORT_STRING);
            $merken = $pdo->prepare(
                "INSERT IGNORE INTO `schema_migrations` (`migration`, `note`) VALUES (?, ?)"
            );
            foreach ($migrationen as $datei) {
                $merken->execute([basename($datei, '.php'), 'beim Aufsetzen bereits im Schema enthalten']);
            }

            $_SESSION['db_installed'] = true;
            $_SESSION['migrationen']  = count($migrationen);

            // ── config.php schreiben ─────────────────────────────────────
            //
            // BACKUP_TOKEN wird ZUFAELLIG erzeugt und aus nichts abgeleitet.
            // Ein Token, das sich aus den Zugangsdaten errechnen laesst, ist
            // keines: wer die Eingangswerte kennt, kommt an den Token, ohne
            // ihn je gesehen zu haben — und wer den Token sieht, haelt eine
            // Ableitung der Zugangsdaten in der Hand.
            //
            // Absichtlich NICHT geschrieben werden die Konstanten fuer eine
            // zentral verwaltete Aufstellung mehrerer Installationen. Diese
            // hier steht fuer sich; was sie nicht kennt, kann sie auch nicht
            // preisgeben.
            $configContent = "<?php\n" .
                "/**\n" .
                " * config.php — angelegt vom Setup-Assistenten am " . date('d.m.Y H:i') . "\n" .
                " *\n" .
                " * Diese Datei enthaelt Zugangsdaten. Sie gehoert nicht in ein\n" .
                " * Repository und nicht in eine Sicherung, die jemand anders liest.\n" .
                " */\n\n" .
                "define('DB_HOST', " . var_export($dbHost, true) . ");\n" .
                "define('DB_NAME', " . var_export($dbName, true) . ");\n" .
                "define('DB_USER', " . var_export($dbUser, true) . ");\n" .
                "define('DB_PASS', " . var_export($dbPass, true) . ");\n" .
                "define('DB_CHARSET', 'utf8mb4');\n\n" .
                "define('APP_NAME', " . var_export($instName, true) . ");\n" .
                "define('APP_TAGLINE', 'Inventarverwaltung für Wertgegenstände');\n" .
                "define('APP_LANG', " . var_export($lang, true) . ");\n\n" .
                "define('SESSION_LIFETIME', 3600);\n" .
                "define('SESSION_NAME', 'valusafe_session');\n" .
                "define('CSRF_TOKEN_NAME', 'csrf_token');\n\n" .
                "define('UPLOAD_DIR', __DIR__ . '/upload/');\n" .
                "define('BACKUP_DIR', __DIR__ . '/backups/');\n" .
                "define('UPLOAD_MAX_SIZE', 10485760);\n" .
                "define('MAX_FILE_SIZE', 10485760);\n" .
                "define('ALLOWED_IMAGE_TYPES', ['image/jpeg','image/png','image/webp','image/gif','image/jpg','image/heic','image/heif']);\n" .
                "define('ALLOWED_EXTENSIONS', ['jpg','jpeg','png','webp','gif','heic','heif']);\n\n" .
                "// Token fuer cron_backup.php. Zufaellig erzeugt, nicht aus den\n" .
                "// Zugangsdaten abgeleitet.\n" .
                "define('BACKUP_TOKEN', " . var_export(bin2hex(random_bytes(24)), true) . ");\n" .
                "define('BACKUP_RETENTION_DAYS', 7);\n";

            $configWritten = false;
            if (is_writable(__DIR__)) {
                if (file_put_contents(__DIR__ . '/config.php', $configContent) !== false) {
                    $configWritten = true;
                    @chmod(__DIR__ . '/config.php', 0640);
                }
            }

            $_SESSION['config_written']  = $configWritten;
            $_SESSION['config_content']  = $configContent;
            $_SESSION['setup_step']      = 4;
            $step = 4;

        } catch (Exception $e) {
            $error = $T['s3_fail'] . $e->getMessage();
            $step = 3;
        }
    }

    // Step 3 → 4: Manuell config hochgeladen
    if ($action === 'step3_manual_next') {
        // Config prüfen ob sie inzwischen da ist
        if (file_exists(__DIR__ . '/config.php')) {
            $_SESSION['setup_step'] = 4;
            $step = 4;
        } else {
            $error = $lang === 'de'
                ? 'config.php wurde noch nicht gefunden. Bitte lade die Datei zuerst hoch.'
                : 'config.php was not found yet. Please upload the file first.';
            $step = 3;
        }
    }

    // Step 4: Admin anlegen
    if ($action === 'step4_next') {
        $adminUser  = trim($_POST['admin_user'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPass  = $_POST['admin_pass'] ?? '';
        $adminPass2 = $_POST['admin_pass2'] ?? '';

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $adminUser)) {
            $error = $T['s4_user_invalid'];
            $step = 4;
        } elseif (strlen($adminPass) < 8) {
            $error = $T['s4_pass_short'];
            $step = 4;
        } elseif ($adminPass !== $adminPass2) {
            $error = $T['s4_pass_mismatch'];
            $step = 4;
        } else {
            try {
                require_once __DIR__ . '/config.php';
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $hash = password_hash($adminPass, PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')")
                    ->execute([$adminUser, $hash, $adminEmail ?: null]);

                // ── SuperAdmin eintragen ─────────────────────────────────
                //
                // Ohne SUPERADMIN_USERNAME besteht niemand isSuperAdmin().
                // Seit hatSuperAdmin() faellt die Pruefung dann auf Admin
                // zurueck, es ist also nichts kaputt — aber die hoehere Stufe
                // gaebe es auf dieser Installation gar nicht. Der Name steht
                // erst hier fest, config.php wurde einen Schritt frueher
                // geschrieben; deshalb angehaengt statt eingebaut.
                $saZeile = "\ndefine('SUPERADMIN_USERNAME', " . var_export($adminUser, true) . ");\n";
                $saGeschrieben = false;
                $cfg = __DIR__ . '/config.php';
                if (is_writable($cfg) && strpos((string)@file_get_contents($cfg), 'SUPERADMIN_USERNAME') === false) {
                    $saGeschrieben = @file_put_contents($cfg, $saZeile, FILE_APPEND) !== false;
                }
                $_SESSION['superadmin_zeile'] = $saGeschrieben ? '' : trim($saZeile);

                // .setup_complete erstellen
                file_put_contents(__DIR__ . '/.setup_complete', date('Y-m-d H:i:s'));

                $_SESSION['admin_user']  = $adminUser;
                $_SESSION['setup_step'] = 5;
                $step = 5;
            } catch (Exception $e) {
                $error = $T['error'] . ': ' . $e->getMessage();
                $step = 4;
            }
        }
    }

    // Zurück
    if ($action === 'go_back') {
        $backTo = max(0, $step - 1);
        $_SESSION['setup_step'] = $backTo;
        $step = $backTo;
    }
}

// ── Systemcheck ──────────────────────────────────────────────────────────────
function runChecks($T) {
    $checks = [];
    $fatal  = false;

    // PHP Version
    // 8.2, nicht 7.4. Der Code benutzt match(true) und str_contains; auf 7.4
    // startet ValuSafe gar nicht. Der Assistent haette eine Installation
    // durchgewinkt, die anschliessend mit einem Parse-Fehler stehenbleibt.
    $phpOk = version_compare(PHP_VERSION, '8.2.0', '>=');
    $checks[] = ['label' => $T['s1_php'], 'ok' => $phpOk, 'fatal' => !$phpOk,
        'msg' => $phpOk ? sprintf($T['s1_php_ok'], PHP_VERSION) : sprintf($T['s1_php_fail'], PHP_VERSION)];
    if (!$phpOk) $fatal = true;

    // PDO MySQL
    $pdoOk = extension_loaded('pdo_mysql');
    $checks[] = ['label' => $T['s1_pdo'], 'ok' => $pdoOk, 'fatal' => !$pdoOk,
        'msg' => $pdoOk ? $T['s1_pdo_ok'] : $T['s1_pdo_fail']];
    if (!$pdoOk) $fatal = true;

    // GD
    $gdOk = extension_loaded('gd');
    $checks[] = ['label' => $T['s1_gd'], 'ok' => $gdOk, 'fatal' => false,
        'msg' => $gdOk ? $T['s1_gd_ok'] : $T['s1_gd_fail']];

    // ZIP
    $zipOk = extension_loaded('zip');
    $checks[] = ['label' => $T['s1_zip'], 'ok' => $zipOk, 'fatal' => false,
        'msg' => $zipOk ? $T['s1_zip_ok'] : $T['s1_zip_fail']];

    // Schreibrechte
    $writeOk = is_writable(__DIR__);
    $checks[] = ['label' => $T['s1_write'], 'ok' => $writeOk, 'fatal' => false,
        'msg' => $writeOk ? $T['s1_write_ok'] : $T['s1_write_fail']];

    // Upload-Ordner
    $uploadDir = __DIR__ . '/upload';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
    $uploadOk = is_dir($uploadDir) && is_writable($uploadDir);
    $checks[] = ['label' => $T['s1_upload'], 'ok' => $uploadOk, 'fatal' => false,
        'msg' => $uploadOk ? $T['s1_upload_ok'] : $T['s1_upload_fail']];

    return ['checks' => $checks, 'fatal' => $fatal];
}

$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST']
    . str_replace('setup.php', '', $_SERVER['REQUEST_URI']);

// ── Selbstentschaerfung ─────────────────────────────────────────────────────
//
// "Bitte per FTP loeschen" einem Menschen als letzten Schritt zu ueberlassen,
// war die schwaechste Stelle der alten Anleitung. Der Assistent raeumt sich
// jetzt selbst weg, sobald die Installation steht — und sagt es deutlich,
// wenn ihm das Dateisystem dabei in die Quere kommt.
$geloescht = [];
$verblieben = [];
if ($step === 5) {
    foreach ([__DIR__ . '/schema.sql', __FILE__] as $weg) {
        if (!file_exists($weg)) continue;
        if (@unlink($weg)) { $geloescht[] = basename($weg); }
        else               { $verblieben[] = basename($weg); }
    }
}

$steps = [$T['step_lang'], $T['step_check'], $T['step_db'], $T['step_install'], $T['step_admin'], $T['step_done']];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($T['title']) ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    min-height: 100vh;
    background: linear-gradient(135deg, #0c1f3d 0%, #1a3d6b 50%, #2a5f9e 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.wizard {
    width: 100%;
    max-width: 620px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.3);
    overflow: hidden;
}

/* Header */
.wizard-header {
    background: linear-gradient(135deg, #0c1f3d, #185fa5);
    padding: 24px 28px 20px;
    color: #fff;
}

.wizard-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.logo-icon {
    width: 32px; height: 32px;
    background: rgba(255,255,255,0.15);
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
}

.logo-name { font-size: 18px; font-weight: 600; letter-spacing: 0.3px; }

/* Steps */
.steps {
    display: flex;
    gap: 0;
    overflow-x: auto;
    padding-bottom: 2px;
}

.step-item {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    position: relative;
    padding: 0 4px;
}

.step-item::after {
    content: '';
    position: absolute;
    top: 12px;
    left: calc(50% + 14px);
    right: calc(-50% + 14px);
    height: 2px;
    background: rgba(255,255,255,0.2);
}

.step-item:last-child::after { display: none; }

.step-dot {
    width: 26px; height: 26px;
    border-radius: 50%;
    background: rgba(255,255,255,0.15);
    border: 2px solid rgba(255,255,255,0.3);
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700;
    color: rgba(255,255,255,0.6);
    position: relative; z-index: 1;
    transition: all 0.2s;
    flex-shrink: 0;
}

.step-item.active .step-dot {
    background: #fff;
    border-color: #fff;
    color: #185fa5;
}

.step-item.done .step-dot {
    background: rgba(255,255,255,0.9);
    border-color: rgba(255,255,255,0.9);
    color: #185fa5;
}

.step-item.done::after { background: rgba(255,255,255,0.5); }

.step-label {
    font-size: 10px;
    color: rgba(255,255,255,0.45);
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 72px;
}

.step-item.active .step-label { color: rgba(255,255,255,0.9); }
.step-item.done  .step-label  { color: rgba(255,255,255,0.7); }

/* Body */
.wizard-body {
    padding: 28px;
}

.step-title {
    font-size: 20px;
    font-weight: 700;
    color: #0c1f3d;
    margin-bottom: 6px;
}

.step-subtitle {
    font-size: 13px;
    color: #666;
    margin-bottom: 24px;
    line-height: 1.5;
}

/* Form elements */
.form-group {
    margin-bottom: 18px;
}

.form-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.form-label .req { color: #dc2626; margin-left: 2px; }

.form-input {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid #d3d1c7;
    border-radius: 7px;
    font-size: 14px;
    color: #222;
    background: #f8f7f5;
    transition: border-color .15s, box-shadow .15s;
    outline: none;
}

.form-input:focus {
    border-color: #185fa5;
    box-shadow: 0 0 0 3px rgba(24,95,165,0.1);
    background: #fff;
}

.form-hint {
    font-size: 11.5px;
    color: #888;
    margin-top: 4px;
    line-height: 1.4;
}

/* Checks */
.check-list { list-style: none; }

.check-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    margin-bottom: 6px;
    font-size: 13px;
}

.check-item.ok    { background: #f0fdf4; }
.check-item.warn  { background: #fffbeb; }
.check-item.fatal { background: #fef2f2; }

.check-icon { font-size: 15px; flex-shrink: 0; margin-top: 1px; }
.check-label { font-weight: 600; color: #333; }
.check-msg   { color: #555; font-size: 12px; margin-top: 1px; }

/* Alerts */
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 18px;
    line-height: 1.5;
}
.alert-error   { background: #fef2f2; border-left: 4px solid #dc2626; color: #991b1b; }
.alert-success { background: #f0fdf4; border-left: 4px solid #16a34a; color: #166534; }
.alert-warn    { background: #fffbeb; border-left: 4px solid #d97706; color: #92400e; }
.alert-info    { background: #eff6ff; border-left: 4px solid #2563eb; color: #1e40af; }

/* Buttons */
.btn-row {
    display: flex;
    gap: 10px;
    margin-top: 24px;
    justify-content: flex-end;
}

.btn {
    padding: 10px 22px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background .15s, transform .1s;
}

.btn:active { transform: scale(0.98); }

.btn-primary   { background: #185fa5; color: #fff; }
.btn-primary:hover { background: #1a6bbf; }
.btn-secondary { background: #f0f0f0; color: #444; }
.btn-secondary:hover { background: #e0e0e0; }
.btn-success   { background: #16a34a; color: #fff; }
.btn-success:hover { background: #15803d; }

.btn-lang {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    background: #fff;
    cursor: pointer;
    font-size: 15px;
    font-weight: 500;
    color: #222;
    width: 100%;
    margin-bottom: 10px;
    transition: all .15s;
}

.btn-lang:hover { border-color: #185fa5; background: #f0f7ff; }

.btn-lang .lang-flag { font-size: 24px; }

/* Code-Box */
.code-box {
    background: #1e1e2e;
    color: #cdd6f4;
    border-radius: 8px;
    padding: 14px 16px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.6;
    max-height: 200px;
    overflow-y: auto;
    margin: 12px 0;
    white-space: pre;
}

/* Test-Button */
.test-row {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-top: 16px;
}

#testResult {
    font-size: 13px;
    flex: 1;
}

/* Step 5 */
.done-box {
    text-align: center;
    padding: 8px 0 16px;
}

.done-icon { font-size: 56px; margin-bottom: 12px; }

.credential-box {
    background: #f8f7f5;
    border: 1px solid #e0ddd5;
    border-radius: 8px;
    padding: 14px 18px;
    margin: 16px 0;
    text-align: left;
    font-size: 13px;
}

.credential-row {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
}

.credential-label { color: #666; }
.credential-value { font-weight: 600; color: #222; font-family: monospace; }

/* Responsive */
@media (max-width: 480px) {
    .wizard-body { padding: 20px; }
    .step-label  { display: none; }
    .btn-row { flex-direction: column-reverse; }
    .btn { width: 100%; text-align: center; }
}
</style>
</head>
<body>

<div class="wizard">

    <!-- Header -->
    <div class="wizard-header">
        <div class="wizard-logo">
            <div class="logo-icon">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <rect x="2" y="2" width="7" height="7" rx="1.5" fill="#fff"/>
                    <rect x="11" y="2" width="7" height="7" rx="1.5" fill="#fff" opacity=".5"/>
                    <rect x="2" y="11" width="7" height="7" rx="1.5" fill="#fff" opacity=".5"/>
                    <rect x="11" y="11" width="7" height="7" rx="1.5" fill="#fff" opacity=".25"/>
                </svg>
            </div>
            <span class="logo-name">ValuSafe Setup</span>
        </div>

        <div class="steps">
            <?php foreach ($steps as $i => $label): ?>
            <div class="step-item <?= $i < $step ? 'done' : ($i === $step ? 'active' : '') ?>">
                <div class="step-dot"><?= $i < $step ? '✓' : ($i + 1) ?></div>
                <span class="step-label"><?= htmlspecialchars($label) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Body -->
    <div class="wizard-body">

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($info): ?>
        <div class="alert alert-info"><?= $info ?></div>
    <?php endif; ?>


    <!-- ── STEP 0: Sprache ───────────────────────────────────────── -->
    <?php if ($step === 0): ?>
        <div class="step-title"><?= $T['s0_title'] ?></div>
        <div class="step-subtitle"><?= $T['s0_subtitle'] ?></div>
        <p style="font-size:13px;font-weight:600;color:#444;margin-bottom:14px;"><?= $T['s0_choose'] ?></p>
        <form method="POST">
            <input type="hidden" name="action" value="set_lang">
            <button type="submit" name="lang" value="de" class="btn-lang">
                <span class="lang-flag">🇩🇪</span>
                <div>
                    <div style="font-weight:700;">Deutsch</div>
                    <div style="font-size:12px;color:#888;">Deutsche Benutzeroberfläche</div>
                </div>
            </button>
            <button type="submit" name="lang" value="en" class="btn-lang">
                <span class="lang-flag">🇬🇧</span>
                <div>
                    <div style="font-weight:700;">English</div>
                    <div style="font-size:12px;color:#888;">English user interface</div>
                </div>
            </button>
        </form>


    <!-- ── STEP 1: Systemcheck ──────────────────────────────────── -->
    <?php elseif ($step === 1): ?>
        <?php $result = runChecks($T); ?>
        <div class="step-title"><?= $T['s1_title'] ?></div>
        <div class="step-subtitle"><?= $T['s1_subtitle'] ?></div>

        <ul class="check-list">
        <?php foreach ($result['checks'] as $c): ?>
            <li class="check-item <?= $c['ok'] ? 'ok' : ($c['fatal'] ? 'fatal' : 'warn') ?>">
                <span class="check-icon"><?= $c['ok'] ? '✅' : ($c['fatal'] ? '❌' : '⚠️') ?></span>
                <div>
                    <div class="check-label"><?= $c['label'] ?></div>
                    <div class="check-msg"><?= $c['msg'] ?></div>
                </div>
            </li>
        <?php endforeach; ?>
        </ul>

        <div class="alert <?= $result['fatal'] ? 'alert-error' : (array_sum(array_column($result['checks'], 'ok')) < count($result['checks']) ? 'alert-warn' : 'alert-success') ?>" style="margin-top:16px;margin-bottom:0;">
            <?= $result['fatal'] ? $T['s1_fatal'] : (array_sum(array_column($result['checks'], 'ok')) < count($result['checks']) ? $T['s1_warn'] : $T['s1_all_ok']) ?>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="step1_next">
            <div class="btn-row">
                <?php if (!$result['fatal']): ?>
                <button type="submit" class="btn btn-primary"><?= $T['next'] ?></button>
                <?php endif; ?>
            </div>
        </form>


    <!-- ── STEP 2: Datenbank ────────────────────────────────────── -->
    <?php elseif ($step === 2): ?>
        <div class="step-title"><?= $T['s2_title'] ?></div>
        <div class="step-subtitle"><?= $T['s2_subtitle'] ?></div>

        <form method="POST" id="dbForm">
            <input type="hidden" name="action" value="step2_next">

            <div class="form-group">
                <label class="form-label"><?= $T['s2_instance'] ?> <span class="req">*</span></label>
                <input type="text" name="instance_name" class="form-input"
                    value="<?= htmlspecialchars($_SESSION['instance_name'] ?? 'ValuSafe') ?>"
                    placeholder="ValuSafe" required>
                <div class="form-hint"><?= $T['s2_instance_hint'] ?></div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= $T['s2_host'] ?> <span class="req">*</span></label>
                <input type="text" name="db_host" id="db_host" class="form-input"
                    value="<?= htmlspecialchars($_SESSION['db_host'] ?? 'localhost') ?>"
                    placeholder="localhost" required>
                <div class="form-hint"><?= $T['s2_host_hint'] ?></div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= $T['s2_name'] ?> <span class="req">*</span></label>
                <input type="text" name="db_name" id="db_name" class="form-input"
                    value="<?= htmlspecialchars($_SESSION['db_name'] ?? '') ?>"
                    placeholder="valusafe" required>
                <div class="form-hint"><?= $T['s2_name_hint'] ?></div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= $T['s2_user'] ?> <span class="req">*</span></label>
                <input type="text" name="db_user" id="db_user" class="form-input"
                    value="<?= htmlspecialchars($_SESSION['db_user'] ?? '') ?>"
                    placeholder="valusafe_user" required>
                <div class="form-hint"><?= $T['s2_user_hint'] ?></div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= $T['s2_pass'] ?></label>
                <input type="password" name="db_pass" id="db_pass" class="form-input"
                    value="<?= htmlspecialchars($_SESSION['db_pass'] ?? '') ?>">
                <div class="form-hint"><?= $T['s2_pass_hint'] ?></div>
            </div>

            <div class="test-row">
                <button type="button" class="btn btn-secondary" onclick="testConnection()">
                    <?= $T['s2_test'] ?>
                </button>
                <span id="testResult"></span>
            </div>

            <div class="btn-row">
                <button type="button" class="btn btn-secondary" onclick="goBack()"><?= $T['back'] ?></button>
                <button type="submit" class="btn btn-primary"><?= $T['next'] ?></button>
            </div>
        </form>


    <!-- ── STEP 3: Installation ─────────────────────────────────── -->
    <?php elseif ($step === 3): ?>
        <div class="step-title"><?= $T['s3_title'] ?></div>
        <div class="step-subtitle"><?= $T['s3_subtitle'] ?></div>

        <?php if (!empty($_SESSION['db_installed'])): ?>
            <div class="alert alert-success"><?= $T['s3_ok'] ?></div>

            <?php if ($_SESSION['config_written']): ?>
                <div class="alert alert-success"><?= $T['s3_config_ok'] ?></div>
                <form method="POST">
                    <input type="hidden" name="action" value="step3_manual_next">
                    <div class="btn-row">
                        <button type="submit" class="btn btn-primary"><?= $T['next'] ?></button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warn"><?= $T['s3_config_manual'] ?></div>
                <p style="font-size:13px;color:#555;margin-bottom:8px;"><?= $T['s3_config_copy'] ?></p>
                <div class="code-box"><?= htmlspecialchars($_SESSION['config_content'] ?? '') ?></div>
                <form method="POST">
                    <input type="hidden" name="action" value="step3_manual_next">
                    <div class="btn-row">
                        <button type="submit" class="btn btn-primary"><?= $T['s3_config_copied'] ?></button>
                    </div>
                </form>
            <?php endif; ?>

        <?php else: ?>
            <p style="font-size:13px;color:#555;margin-bottom:20px;"><?= $T['s3_running'] ?></p>
            <form method="POST" id="installForm">
                <input type="hidden" name="action" value="step3_install">
                <div class="btn-row">
                    <button type="button" class="btn btn-secondary" onclick="goBack()"><?= $T['back'] ?></button>
                    <button type="submit" class="btn btn-primary" id="installBtn">
                        <?= $lang === 'de' ? '⚡ Jetzt installieren' : '⚡ Install Now' ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>


    <!-- ── STEP 4: Admin-Konto ──────────────────────────────────── -->
    <?php elseif ($step === 4): ?>
        <div class="step-title"><?= $T['s4_title'] ?></div>
        <div class="step-subtitle"><?= $T['s4_subtitle'] ?></div>

        <form method="POST">
            <input type="hidden" name="action" value="step4_next">

            <div class="form-group">
                <label class="form-label"><?= $T['s4_user'] ?> <span class="req">*</span></label>
                <input type="text" name="admin_user" class="form-input"
                    value="<?= htmlspecialchars($_POST['admin_user'] ?? '') ?>"
                    placeholder="admin" required autofocus>
                <div class="form-hint"><?= $T['s4_user_hint'] ?></div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= $T['s4_email'] ?></label>
                <input type="email" name="admin_email" class="form-input"
                    value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>"
                    placeholder="admin@example.com">
                <div class="form-hint"><?= $T['s4_email_hint'] ?></div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= $T['s4_pass'] ?> <span class="req">*</span></label>
                <input type="password" name="admin_pass" class="form-input"
                    placeholder="••••••••" required>
                <div class="form-hint"><?= $T['s4_pass_hint'] ?></div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= $T['s4_pass2'] ?> <span class="req">*</span></label>
                <input type="password" name="admin_pass2" class="form-input"
                    placeholder="••••••••" required>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-primary"><?= $T['next'] ?></button>
            </div>
        </form>


    <!-- ── STEP 5: Fertig ───────────────────────────────────────── -->
    <?php elseif ($step === 5): ?>
        <div class="done-box">
            <div class="done-icon">🎉</div>
            <div class="step-title"><?= $T['s5_title'] ?></div>
            <div class="step-subtitle" style="margin-bottom:0;"><?= $T['s5_subtitle'] ?></div>
        </div>

        <div class="credential-box">
            <div style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;"><?= $T['s5_credentials'] ?></div>
            <div class="credential-row">
                <span class="credential-label"><?= $T['s5_user'] ?></span>
                <span class="credential-value"><?= htmlspecialchars($_SESSION['admin_user'] ?? 'admin') ?></span>
            </div>
            <div class="credential-row">
                <span class="credential-label"><?= $T['s5_url'] ?></span>
                <span class="credential-value" style="font-size:11px;"><?= htmlspecialchars($currentUrl) ?></span>
            </div>
        </div>

        <?php if (!empty($_SESSION['superadmin_zeile'])): ?>
        <div class="alert alert-warn">
            <?= $T['s5_superadmin'] ?>
            <pre style="margin-top:8px;padding:8px;background:#fff;border-radius:6px;font-size:12px;overflow-x:auto;"><?= htmlspecialchars($_SESSION['superadmin_zeile']) ?></pre>
        </div>
        <?php endif; ?>

        <?php if (empty($verblieben)): ?>
        <div class="alert alert-success">
            <?= $T['s5_deleted'] ?>
        </div>
        <?php else: ?>
        <div class="alert alert-warn">
            <?= $T['s5_delete'] ?>
            <div style="margin-top:6px;font-size:12px;color:#92400e;"><?= $T['s5_delete_hint'] ?></div>
            <div style="margin-top:6px;font-size:12px;color:#92400e;"><?= $T['s5_delete_rest'] ?><code><?= htmlspecialchars(implode(', ', $verblieben)) ?></code></div>
        </div>
        <?php endif; ?>

        <div class="btn-row" style="justify-content:center;">
            <a href="index.php" class="btn btn-success"><?= $T['s5_login'] ?></a>
        </div>

    <?php endif; ?>

    </div><!-- /.wizard-body -->
</div><!-- /.wizard -->

<p style="color:rgba(255,255,255,0.35);font-size:11px;margin-top:16px;text-align:center;">
    ValuSafe Setup Wizard — <?= date('Y') ?>
</p>

<form method="POST" id="backForm">
    <input type="hidden" name="action" value="go_back">
</form>

<script>
function goBack() {
    document.getElementById('backForm').submit();
}

function testConnection() {
    const host = document.getElementById('db_host').value;
    const name = document.getElementById('db_name').value;
    const user = document.getElementById('db_user').value;
    const pass = document.getElementById('db_pass').value;
    const res  = document.getElementById('testResult');

    res.textContent = '<?= $lang === "de" ? "Teste Verbindung…" : "Testing connection…" ?>';
    res.style.color = '#888';

    const fd = new FormData();
    fd.append('action', 'test_db');
    fd.append('db_host', host);
    fd.append('db_name', name);
    fd.append('db_user', user);
    fd.append('db_pass', pass);

    fetch('setup.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            res.textContent = d.msg;
            res.style.color = d.ok ? '#16a34a' : '#dc2626';
        })
        .catch(() => {
            res.textContent = '<?= $lang === "de" ? "Verbindungsfehler" : "Connection error" ?>';
            res.style.color = '#dc2626';
        });
}

// Installations-Button Feedback
const installBtn = document.getElementById('installBtn');
if (installBtn) {
    document.getElementById('installForm').addEventListener('submit', function() {
        installBtn.textContent = '<?= $lang === "de" ? "⏳ Wird installiert…" : "⏳ Installing…" ?>';
        installBtn.disabled = true;
    });
}
</script>
</body>
</html>
