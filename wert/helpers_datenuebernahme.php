<?php
/**
 * helpers_datenuebernahme.php - Daten aus einer frueheren ValuSafe-Version
 * uebernehmen (4.5)
 *
 * Reine Hilfsfunktionen ohne Datenbank, damit sie sich testen lassen
 * (tests/DatenuebernahmeTest.php). Die Seite backend/datenuebernahme.php
 * waehlt die Dateien, zeigt die Vorschau und fuehrt aus.
 *
 * WAS UEBERNOMMEN WIRD
 *
 * Eine Datenbanksicherung (database_*.sql aus backend/backup.php) einer
 * ValuSafe ab 4.4. Sie wird vollstaendig eingespielt - Gegenstaende, Raeume,
 * Benutzer samt Passwoertern -, danach laufen die Migrationen, die seit der
 * alten Version dazugekommen sind. Dazu optional die Dateien-Sicherung
 * (files_*.zip) mit Bildern und Belegen.
 *
 * Erkennungsmerkmal ist die Tabelle schema_migrations in der Sicherung: Ab 4.4
 * enthaelt sie 014_schadensfaelle. Fehlt sie, ist die Sicherung zu alt;
 * nennt sie eine Migration, die diese Installation nicht kennt, ist sie neuer.
 *
 * WARUM SO STRENG
 *
 * Die Datei kommt per Upload und wird als SQL ausgefuehrt. Zugelassen ist
 * deshalb nur, was backup.php selbst schreibt: SET NAMES, SET
 * FOREIGN_KEY_CHECKS, DROP TABLE IF EXISTS, CREATE TABLE (...) und INSERT INTO
 * ... VALUES mit reinen Literalen - jeweils nur fuer Tabellen, die ValuSafe
 * kennt. Eine einzige andere Anweisung, und die ganze Datei wird abgelehnt.
 */

if (!defined('VS_UEBERNAHME_TABELLEN')) {
    /** Die Tabellen von install/schema.sql (der Test prueft die Liste dagegen). */
    define('VS_UEBERNAHME_TABELLEN', [
        'activity_log', 'app_settings', 'dokumente', 'item_images', 'kategorien',
        'login_attempts', 'password_resets', 'permissions', 'positionen', 'raeume',
        'rate_limit', 'schadensfaelle', 'schadensfall_gegenstaende',
        'schema_migrations', 'security_log', 'standorte', 'users', 'user_activity',
        'versicherungen', 'wertsachen', 'wert_historie',
    ]);
}

if (!defined('VS_UEBERNAHME_FLUECHTIG')) {
    /**
     * Bleiben wie sie sind: Sperren nach Fehlanmeldungen und offene
     * Passwort-Links gehoeren zur alten Installation, nicht zu den Daten.
     */
    define('VS_UEBERNAHME_FLUECHTIG', ['login_attempts', 'password_resets', 'rate_limit']);
}

if (!defined('VS_UEBERNAHME_MINDEST_MIGRATION')) {
    /** Erste Migration, die jede Sicherung ab 4.4 enthaelt. */
    define('VS_UEBERNAHME_MINDEST_MIGRATION', '014_schadensfaelle');
}

if (!defined('VS_UEBERNAHME_DATEIENDUNGEN')) {
    /** Was aus der Dateien-Sicherung nach upload/ und documents/ darf. */
    define('VS_UEBERNAHME_DATEIENDUNGEN', [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif',
        'pdf', 'doc', 'docx', 'xls', 'xlsx',
    ]);
}

if (!function_exists('vsSqlZerlegen')) {
    /**
     * Zerlegt einen SQL-Text in Anweisungen.
     *
     * Kennt Zeichenketten in '...' und "..." mit Backslash-Escape (so schreibt
     * PDO::quote ein Hochkomma: 'O\'Brien') und verdoppeltem Anfuehrungszeichen,
     * Bezeichner in `...`, Kommentare mit -- , # und /* ... * /. Ein Semikolon
     * trennt nur ausserhalb davon.
     *
     * Bis 4.4.1 zaehlte restore.php jedes Hochkomma als Beginn oder Ende einer
     * Zeichenkette. Ein Wert wie "Kinder\'s Rad" verschob damit alle folgenden
     * Grenzen, mehrere INSERTs scheiterten, und der Fehler stand nur im Log.
     *
     * @return array<int, array{sql: string, gerippe: string}>
     *   sql     die Anweisung wie im Text, ohne Kommentare, getrimmt
     *   gerippe dieselbe Anweisung, jede Zeichenkette durch '' ersetzt - zum
     *           Pruefen der Form, ohne dass Inhalte stoeren
     */
    function vsSqlZerlegen(string $text): array {
        $ergebnis = [];
        $sql = '';
        $gerippe = '';
        $n = strlen($text);
        $i = 0;
        while ($i < $n) {
            $c = $text[$i];
            $naechstes = $i + 1 < $n ? $text[$i + 1] : '';

            // Kommentare: fallen weg, wirken aber wie ein Leerzeichen
            if (($c === '-' && $naechstes === '-' && ($i + 2 >= $n || ctype_space($text[$i + 2])))
                || $c === '#') {
                $ende = strpos($text, "\n", $i);
                $i = $ende === false ? $n : $ende + 1;
                $sql .= ' ';
                $gerippe .= ' ';
                continue;
            }
            // /*! ... */ fuehrt MySQL aus - das bleibt stehen und faellt
            // damit bei der Pruefung als unbekannte Anweisung auf
            if ($c === '/' && $naechstes === '*' && !in_array($text[$i + 2] ?? '', ['!', '+'], true)) {
                $ende = strpos($text, '*/', $i + 2);
                $i = $ende === false ? $n : $ende + 2;
                $sql .= ' ';
                $gerippe .= ' ';
                continue;
            }

            // Zeichenketten und Bezeichner
            if ($c === "'" || $c === '"' || $c === '`') {
                $start = $i;
                $i++;
                while ($i < $n) {
                    if ($c !== '`' && $text[$i] === '\\') {
                        $i += 2;
                        continue;
                    }
                    if ($text[$i] === $c) {
                        if ($i + 1 < $n && $text[$i + 1] === $c) {
                            $i += 2;            // '' oder `` im Inneren
                            continue;
                        }
                        break;
                    }
                    $i++;
                }
                $i = min($i + 1, $n);
                $stueck = substr($text, $start, $i - $start);
                $sql .= $stueck;
                $gerippe .= $c === '`' ? $stueck : "''";
                continue;
            }

            if ($c === ';') {
                $s = trim($sql);
                if ($s !== '') {
                    $ergebnis[] = ['sql' => $s, 'gerippe' => trim($gerippe)];
                }
                $sql = '';
                $gerippe = '';
                $i++;
                continue;
            }

            $sql .= $c;
            $gerippe .= $c;
            $i++;
        }
        $s = trim($sql);
        if ($s !== '') {
            $ergebnis[] = ['sql' => $s, 'gerippe' => trim($gerippe)];
        }
        return $ergebnis;
    }
}

if (!function_exists('vsSqlNurLiterale')) {
    /**
     * Prueft den Teil nach VALUES: nur Zeilen ( Wert , Wert ... ), getrennt
     * durch Kommas. Ein Wert ist '' (eine Zeichenkette im Gerippe), NULL oder
     * eine Zahl. Gibt die Zahl der Zeilen zurueck oder -1.
     *
     * Ohne regulaeren Ausdruck: Eine Sicherung schreibt alle Zeilen einer
     * Tabelle in ein einziges INSERT, das Gerippe wird schnell einige MB lang.
     */
    function vsSqlNurLiterale(string $werte, int $spalten): int {
        $n = strlen($werte);
        $i = 0;
        $zeilen = 0;
        $leer = static function () use ($werte, $n, &$i): void {
            while ($i < $n && ctype_space($werte[$i])) {
                $i++;
            }
        };
        while (true) {
            $leer();
            if ($i >= $n || $werte[$i] !== '(') {
                return -1;
            }
            $i++;
            $anzahl = 0;
            while (true) {
                $leer();
                if (substr($werte, $i, 2) === "''") {
                    $i += 2;
                } elseif (strncasecmp(substr($werte, $i, 4), 'NULL', 4) === 0) {
                    $i += 4;
                } elseif (preg_match('/\G-?\d+(\.\d+)?([eE][+-]?\d+)?/', $werte, $m, 0, $i)) {
                    $i += strlen($m[0]);
                } else {
                    return -1;
                }
                $anzahl++;
                $leer();
                if ($i < $n && $werte[$i] === ',') {
                    $i++;
                    continue;
                }
                if ($i < $n && $werte[$i] === ')') {
                    $i++;
                    break;
                }
                return -1;
            }
            if ($anzahl !== $spalten) {
                return -1;
            }
            $zeilen++;
            $leer();
            if ($i >= $n) {
                return $zeilen;
            }
            if ($werte[$i] !== ',') {
                return -1;
            }
            $i++;
        }
    }
}

if (!function_exists('vsUebernahmeAnweisung')) {
    /**
     * Ordnet eine Anweisung ein.
     *
     * @return array{art: string, tabelle?: string, zeilen?: int, werte?: string[]}
     *   art: 'set' | 'drop' | 'create' | 'insert' | 'unzulaessig'
     *   werte: bei INSERT INTO schema_migrations die Namen der Migrationen
     */
    function vsUebernahmeAnweisung(array $anweisung): array {
        $g = $anweisung['gerippe'];

        if (preg_match('/^SET\s+(NAMES\s+\w+|FOREIGN_KEY_CHECKS\s*=\s*[01])$/i', $g)) {
            return ['art' => 'set'];
        }
        if (preg_match('/^DROP\s+TABLE\s+IF\s+EXISTS\s+`(\w+)`$/i', $g, $m)) {
            return ['art' => 'drop', 'tabelle' => $m[1]];
        }
        if (preg_match('/^CREATE\s+TABLE\s+`(\w+)`\s*\(/i', $g, $m)) {
            // Hinter der letzten schliessenden Klammer nur Tabellenoptionen
            // wie ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT=''. Nichts,
            // was Dateien anlegt oder Daten von woanders holt.
            // Bezeichner in `...` zaehlen dabei nicht (eine Spalte darf
            // `connection` heissen).
            $ohneNamen = preg_replace('/`[^`]*`/', '``', $g) ?? '';
            $zu = strrpos($ohneNamen, ')');
            $optionen = $zu === false ? '' : substr($ohneNamen, $zu + 1);
            if ($zu !== false
                && preg_match('/^[\w\s=\',]*$/', $optionen)
                && !preg_match('/\b(SELECT|DIRECTORY|CONNECTION|UNION|ENCRYPTION|ENGINE\s*=\s*(FEDERATED|CONNECT|MERGE))\b/i', $ohneNamen)) {
                return ['art' => 'create', 'tabelle' => $m[1]];
            }
            return ['art' => 'unzulaessig'];
        }
        // Spaltennamen beliebig in `...` (dokumente hat `dateigröße`),
        // Tabellennamen nur \w - sie werden ohnehin mit der Liste verglichen
        if (preg_match('/^INSERT\s+INTO\s+`(\w+)`\s*\(\s*(`[^`]+`(\s*,\s*`[^`]+`)*)\s*\)\s*VALUES\s*/i', $g, $m)) {
            $spalten = substr_count($m[2], ',') + 1;
            $zeilen = vsSqlNurLiterale(substr($g, strlen($m[0])), $spalten);
            if ($zeilen < 1) {
                return ['art' => 'unzulaessig'];
            }
            $ergebnis = ['art' => 'insert', 'tabelle' => $m[1], 'zeilen' => $zeilen];
            if ($m[1] === 'schema_migrations') {
                $ergebnis['werte'] = vsUebernahmeMigrationsnamen($anweisung['sql'], $m[2]);
            }
            return $ergebnis;
        }
        return ['art' => 'unzulaessig'];
    }
}

if (!function_exists('vsUebernahmeMigrationsnamen')) {
    /**
     * Liest aus INSERT INTO schema_migrations die Spalte migration. Die Namen
     * bestehen nur aus Ziffern, Buchstaben und Unterstrich; alles andere wird
     * ignoriert (und faellt dann als unbekannte Migration auf).
     */
    function vsUebernahmeMigrationsnamen(string $sql, string $spaltenliste): array {
        $spalten = array_map(static fn($s) => trim($s, " `\t\n\r"), explode(',', $spaltenliste));
        $pos = array_search('migration', $spalten, true);
        if ($pos === false) {
            return [];
        }
        $werteTeil = substr($sql, (int)stripos($sql, 'VALUES') + 6);
        $namen = [];
        foreach (vsSqlZeilen($werteTeil) as $zeile) {
            $werte = vsSqlWerte($zeile);
            $wert = $werte[$pos] ?? null;
            if ($wert !== null && preg_match('/^\w{1,191}$/', $wert)) {
                $namen[] = $wert;
            }
        }
        return $namen;
    }
}

if (!function_exists('vsSqlWerte')) {
    /**
     * Zerlegt eine Zeile "'a', NULL, '1'" in ['a', null, '1']. Zeichenketten
     * werden entschluesselt (\' \\ \n ... und ''), NULL wird null.
     */
    function vsSqlWerte(string $zeile): array {
        $werte = [];
        $n = strlen($zeile);
        $i = 0;
        while ($i < $n) {
            while ($i < $n && ctype_space($zeile[$i])) $i++;
            if ($i >= $n) break;
            if ($zeile[$i] === "'" || $zeile[$i] === '"') {
                $q = $zeile[$i++];
                $w = '';
                while ($i < $n) {
                    $c = $zeile[$i];
                    if ($c === '\\' && $i + 1 < $n) {
                        $e = $zeile[$i + 1];
                        $w .= ['n' => "\n", 'r' => "\r", '0' => "\0", 'Z' => "\x1a", 't' => "\t"][$e] ?? $e;
                        $i += 2;
                        continue;
                    }
                    if ($c === $q) {
                        if ($i + 1 < $n && $zeile[$i + 1] === $q) { $w .= $q; $i += 2; continue; }
                        $i++;
                        break;
                    }
                    $w .= $c;
                    $i++;
                }
                $werte[] = $w;
            } else {
                $start = $i;
                while ($i < $n && $zeile[$i] !== ',') $i++;
                $roh = trim(substr($zeile, $start, $i - $start));
                $werte[] = strcasecmp($roh, 'NULL') === 0 ? null : $roh;
            }
            while ($i < $n && ctype_space($zeile[$i])) $i++;
            if ($i < $n && $zeile[$i] === ',') $i++;
        }
        return $werte;
    }
}

if (!function_exists('vsSqlZeilen')) {
    /** Zerlegt "(a,b),(c,d)" in ["a,b", "c,d"] - Klammern in Zeichenketten zaehlen nicht. */
    function vsSqlZeilen(string $werte): array {
        $zeilen = [];
        $tiefe = 0;
        $start = 0;
        $n = strlen($werte);
        for ($i = 0; $i < $n; $i++) {
            $c = $werte[$i];
            if ($c === "'" || $c === '"') {
                for ($i++; $i < $n; $i++) {
                    if ($werte[$i] === '\\') { $i++; continue; }
                    if ($werte[$i] === $c) {
                        if ($i + 1 < $n && $werte[$i + 1] === $c) { $i++; continue; }
                        break;
                    }
                }
                continue;
            }
            if ($c === '(') {
                if ($tiefe++ === 0) {
                    $start = $i + 1;
                }
            } elseif ($c === ')' && $tiefe > 0 && --$tiefe === 0) {
                $zeilen[] = substr($werte, $start, $i - $start);
            }
        }
        return $zeilen;
    }
}

if (!function_exists('vsUebernahmeAnalysieren')) {
    /**
     * Prueft eine Datenbanksicherung, ohne etwas auszufuehren.
     *
     * @param string   $sqlText             Inhalt der database_*.sql
     * @param string[] $bekannteMigrationen Namen aus backend/migrations/ (ohne .php)
     * @return array{
     *   ok: bool,
     *   fehler: string[],               Schluessel: unzulaessig, keine_migrationen,
     *                                   zu_alt, zu_neu, keine_gegenstaende, leer
     *   unzulaessig: string[],          die ersten abgelehnten Anweisungen (gekuerzt)
     *   zu_neu: string[],               Migrationen, die diese Installation nicht kennt
     *   migrationen: string[],
     *   zeilen: array<string,int>,      Tabelle => Zahl der Datensaetze
     *   fremd: string[],                Tabellen, die ValuSafe nicht (mehr) kennt
     *   ausfuehren: string[],           die Anweisungen, die eingespielt werden
     *   version: string|null            aus der Kopfzeile, wenn vorhanden
     * }
     */
    function vsUebernahmeAnalysieren(string $sqlText, array $bekannteMigrationen): array {
        $r = [
            'ok' => false, 'fehler' => [], 'unzulaessig' => [], 'zu_neu' => [],
            'migrationen' => [], 'zeilen' => [], 'fremd' => [], 'ausfuehren' => [],
            'version' => null,
        ];
        if (preg_match('/^--\s*ValuSafe-Version:\s*([0-9][0-9A-Za-z.\-]{0,19})\s*$/m', substr($sqlText, 0, 2000), $m)) {
            $r['version'] = $m[1];
        }

        $anweisungen = vsSqlZerlegen($sqlText);
        if (!$anweisungen) {
            $r['fehler'][] = 'leer';
            return $r;
        }

        $tabellenMitCreate = [];
        $tabellenMitDrop = [];
        foreach ($anweisungen as $a) {
            $e = vsUebernahmeAnweisung($a);
            if ($e['art'] === 'unzulaessig') {
                if (count($r['unzulaessig']) < 3) {
                    $r['unzulaessig'][] = mb_strimwidth($a['sql'], 0, 120, '…', 'UTF-8');
                }
                continue;
            }
            if ($e['art'] === 'set') {
                continue;       // setzt die Seite selbst
            }
            $t = $e['tabelle'];
            if (!in_array($t, VS_UEBERNAHME_TABELLEN, true)) {
                $r['fremd'][$t] = true;
                continue;
            }
            if (in_array($t, VS_UEBERNAHME_FLUECHTIG, true)) {
                continue;
            }
            if ($e['art'] === 'create') {
                $tabellenMitCreate[$t] = true;
            }
            if ($e['art'] === 'drop') {
                $tabellenMitDrop[$t] = true;
            }
            if ($e['art'] === 'insert') {
                $r['zeilen'][$t] = ($r['zeilen'][$t] ?? 0) + $e['zeilen'];
                if ($t === 'schema_migrations') {
                    $r['migrationen'] = array_merge($r['migrationen'], $e['werte'] ?? []);
                }
            }
            $r['ausfuehren'][] = $a['sql'];
        }
        $r['fremd'] = array_keys($r['fremd']);
        sort($r['fremd']);

        // Eine Tabelle loeschen, ohne sie neu anzulegen, schreibt backup.php nie
        foreach (array_keys(array_diff_key($tabellenMitDrop, $tabellenMitCreate)) as $t) {
            if (count($r['unzulaessig']) < 3) {
                $r['unzulaessig'][] = 'DROP TABLE IF EXISTS `' . $t . '` (ohne CREATE TABLE)';
            }
        }
        if ($r['unzulaessig']) {
            $r['fehler'][] = 'unzulaessig';
        }
        if (!isset($tabellenMitCreate['schema_migrations']) || !$r['migrationen']) {
            $r['fehler'][] = 'keine_migrationen';
        } elseif (!in_array(VS_UEBERNAHME_MINDEST_MIGRATION, $r['migrationen'], true)) {
            $r['fehler'][] = 'zu_alt';
        }
        $r['zu_neu'] = array_values(array_diff($r['migrationen'], $bekannteMigrationen));
        if ($r['zu_neu']) {
            $r['fehler'][] = 'zu_neu';
        }
        if (!isset($tabellenMitCreate['wertsachen']) || !isset($tabellenMitCreate['users'])) {
            $r['fehler'][] = 'keine_gegenstaende';
        }

        $r['ok'] = !$r['fehler'];
        if (!$r['ok']) {
            $r['ausfuehren'] = [];
        }
        return $r;
    }
}

if (!function_exists('vsUebernahmeZipZiel')) {
    /**
     * Wohin eine Datei aus der Dateien-Sicherung darf, relativ zum
     * Anwendungsordner - oder null.
     *
     * backup.php legt Dateien flach unter upload/ und documents/ ab. Erlaubt
     * ist genau das, dazu upload/avatars/: keine weiteren Ordner, kein "..",
     * keine versteckten Dateien, nur Bild- und Belegendungen. Damit kann
     * eine praeparierte ZIP weder ausserhalb schreiben noch ein Skript
     * ablegen.
     */
    function vsUebernahmeZipZiel(string $name): ?string {
        $name = str_replace('\\', '/', $name);
        if (!preg_match('#^(upload|upload/avatars|documents)/([^/]+)$#', $name, $m)) {
            return null;
        }
        $datei = $m[2];
        if ($datei[0] === '.' || preg_match('/[\x00-\x1F\x7F]/', $datei)) {
            return null;
        }
        $endung = strtolower(pathinfo($datei, PATHINFO_EXTENSION));
        if (!in_array($endung, VS_UEBERNAHME_DATEIENDUNGEN, true)) {
            return null;
        }
        // Doppelte Endungen wie bild.php.jpg sind harmlos fuer den Webserver,
        // solange die letzte zaehlt - aber .phtml/.phar mitten im Namen will
        // niemand im Upload-Ordner haben.
        if (preg_match('/\.(php\d?|phtml|phar|htaccess|cgi|pl|sh)(\.|$)/i', $datei)) {
            return null;
        }
        return $m[1] . '/' . $datei;
    }
}

if (!function_exists('vsUebernahmeBackupDatei')) {
    /**
     * Pfad einer Datei in $ordner, wenn sie dort wirklich liegt (kein Weg
     * hinaus ueber ../ oder Verknuepfungen) und zur erwarteten Art passt.
     *
     * @param string $art 'db' (database_*.sql) oder 'files' (files_*.zip)
     */
    function vsUebernahmeBackupDatei(string $ordner, string $name, string $art): ?string {
        $name = basename($name);
        $muster = $art === 'db' ? '/^database_[\w.-]+\.sql$/' : '/^files_[\w.-]+\.zip$/';
        if (!preg_match($muster, $name)) {
            return null;
        }
        $basis = realpath($ordner);
        $pfad = realpath(rtrim($ordner, '/') . '/' . $name);
        if ($basis === false || $pfad === false || !is_file($pfad)
            || strpos($pfad, $basis . DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }
        return $pfad;
    }
}

if (!defined('VS_NUR_SUPERADMIN_TABELLEN')) {
    /**
     * Tabellen, die nur der SuperAdmin aus einer Sicherung zurueckholen darf
     * (OWASP-A06-001, 4.6). Benutzer verwaltet nur er (backend/users.php),
     * app_settings traegt seine sa_*-Schalter, security_log ist das Protokoll,
     * das er liest. Ein Admin, der eine Sicherung einspielt, bekaeme sonst
     * mit einer selbst geschriebenen Datei ein Konto unter dem Namen des
     * SuperAdmins.
     */
    define('VS_NUR_SUPERADMIN_TABELLEN', ['users', 'app_settings', 'security_log']);
}

if (!function_exists('vsSqlTabellenFiltern')) {
    /**
     * Nimmt aus einer Liste einzelner, bereits gepruefter Anweisungen alle
     * heraus, die eine der genannten Tabellen betreffen.
     *
     * @param  string[] $anweisungen
     * @param  string[] $tabellen
     * @return array{behalten: string[], uebersprungen: string[]}
     */
    function vsSqlTabellenFiltern(array $anweisungen, array $tabellen): array {
        $behalten = [];
        $weg = [];
        foreach ($anweisungen as $sql) {
            $teile = vsSqlZerlegen($sql);
            $e = $teile ? vsUebernahmeAnweisung($teile[0]) : ['art' => 'unzulaessig'];
            if (isset($e['tabelle']) && in_array($e['tabelle'], $tabellen, true)) {
                $weg[$e['tabelle']] = true;
                continue;
            }
            $behalten[] = $sql;
        }
        $weg = array_keys($weg);
        sort($weg);
        return ['behalten' => $behalten, 'uebersprungen' => $weg];
    }
}

if (!function_exists('vsRestorePruefen')) {
    /**
     * Prueft eine Datenbanksicherung, bevor restore.php sie einspielt
     * (OWASP-A06-001, 4.6).
     *
     * Bis 4.5.2 fuehrte restore.php jede Anweisung jeder database_*.sql aus.
     * Ueber die Datenuebernahme liess sich eine solche Datei hochladen - auch
     * wenn deren Pruefung sie ablehnte, blieb sie liegen. Jetzt gilt dieselbe
     * Formregel wie bei der Uebernahme (nur, was backup.php schreibt), aber
     * ohne deren Versionsbedingungen: Eine eigene Sicherung von vor 4.4 laesst
     * sich weiter zurueckspielen, die Migrationen laufen danach.
     *
     * Tabellen, die ValuSafe nicht kennt, und die fluechtigen (Sperren,
     * Passwort-Links) werden uebersprungen. Ohne SuperAdmin auch
     * VS_NUR_SUPERADMIN_TABELLEN.
     *
     * @return array{ok: bool, unzulaessig: string[], ausfuehren: string[], uebersprungen: string[]}
     */
    function vsRestorePruefen(string $sqlText, bool $istSuperAdmin): array {
        $r = ['ok' => false, 'unzulaessig' => [], 'ausfuehren' => [], 'uebersprungen' => []];
        $gesperrt = $istSuperAdmin ? [] : VS_NUR_SUPERADMIN_TABELLEN;
        $weg = [];
        $mitCreate = [];
        $mitDrop = [];
        foreach (vsSqlZerlegen($sqlText) as $a) {
            $e = vsUebernahmeAnweisung($a);
            if ($e['art'] === 'unzulaessig') {
                if (count($r['unzulaessig']) < 3) {
                    $r['unzulaessig'][] = mb_strimwidth($a['sql'], 0, 120, '…', 'UTF-8');
                }
                continue;
            }
            if ($e['art'] === 'set') {
                continue;
            }
            $t = $e['tabelle'];
            if (in_array($t, VS_UEBERNAHME_FLUECHTIG, true)) {
                continue;       // gehoeren zur laufenden Installation, keine Meldung
            }
            if (!in_array($t, VS_UEBERNAHME_TABELLEN, true) || in_array($t, $gesperrt, true)) {
                $weg[$t] = true;
                continue;
            }
            if ($e['art'] === 'create') $mitCreate[$t] = true;
            if ($e['art'] === 'drop')   $mitDrop[$t] = true;
            $r['ausfuehren'][] = $a['sql'];
        }
        foreach (array_keys(array_diff_key($mitDrop, $mitCreate)) as $t) {
            if (count($r['unzulaessig']) < 3) {
                $r['unzulaessig'][] = 'DROP TABLE IF EXISTS `' . $t . '` (ohne CREATE TABLE)';
            }
        }
        $r['uebersprungen'] = array_keys($weg);
        sort($r['uebersprungen']);
        $r['ok'] = !$r['unzulaessig'] && $r['ausfuehren'];
        if (!$r['ok']) {
            $r['ausfuehren'] = [];
        }
        return $r;
    }
}
