<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../helpers_datenuebernahme.php';

/**
 * Daten aus einer frueheren ValuSafe-Version uebernehmen
 * (helpers_datenuebernahme.php, 4.5).
 *
 * Die Sicherung kommt per Upload und wird als SQL ausgefuehrt. Geprueft wird
 * deshalb zweierlei: dass eine echte Sicherung von backup.php vollstaendig
 * durchkommt - auch mit Hochkomma, Semikolon und Zeilenumbruch in den Werten -,
 * und dass alles andere abgelehnt wird.
 */
class DatenuebernahmeTest extends TestCase
{
    /** So quotet PDO::quote bei MySQL (mysql_real_escape_string). */
    private static function q(?string $v): string
    {
        if ($v === null) {
            return 'NULL';
        }
        return "'" . strtr($v, [
            '\\' => '\\\\', "\0" => '\\0', "\n" => '\\n', "\r" => '\\r',
            "'" => "\\'", '"' => '\\"', "\x1a" => '\\Z',
        ]) . "'";
    }

    /** Eine Tabelle so, wie backup.php sie schreibt. */
    private static function tabelle(string $name, string $create, array $zeilen): string
    {
        $s = "DROP TABLE IF EXISTS `$name`;\n" . $create . ";\n\n";
        foreach ($zeilen as $k => $z) {
            if ($k === 0) {
                $s .= "INSERT INTO `$name` (`" . implode('`,`', array_keys($z)) . "`) VALUES\n";
            }
            $s .= ($k > 0 ? ',' : '') . '(' . implode(',', array_map([self::class, 'q'], array_values($z))) . ")\n";
        }
        return $zeilen ? $s . ";\n\n" : $s;
    }

    private static function sicherung(array $migrationen = ['001_wertsachen_v4_columns', '014_schadensfaelle'],
                                      ?array $gegenstaende = null): string
    {
        $gegenstaende ??= [
            ['id' => '1', 'name' => "Kinder's Fahrrad; blau", 'beschreibung' => "Zeile 1\nZeile 2 \"Zitat\" C:\\Pfad", 'wert' => '199.00'],
            ['id' => '2', 'name' => 'Uhr (Erbstück)', 'beschreibung' => null, 'wert' => '1200.50'],
        ];
        $s = "-- Backup 2026-10-14 10:00:00\n-- ValuSafe-Version: 4.4.1\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        $s .= self::tabelle('schema_migrations',
            "CREATE TABLE `schema_migrations` (\n  `id` int NOT NULL AUTO_INCREMENT,\n  `migration` varchar(191) NOT NULL,\n  `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,\n  `note` varchar(255) DEFAULT NULL,\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4",
            array_map(static fn($m, $i) => ['id' => (string)($i + 1), 'migration' => $m, 'applied_at' => '2026-10-12 10:00:00', 'note' => null],
                $migrationen, array_keys($migrationen)));
        $s .= self::tabelle('users',
            "CREATE TABLE `users` (\n  `id` int NOT NULL AUTO_INCREMENT,\n  `username` varchar(50) NOT NULL,\n  `password` varchar(255) NOT NULL,\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            [['id' => '1', 'username' => 'admin', 'password' => '$2y$10$abc/def.ghi']]);
        $s .= self::tabelle('wertsachen',
            "CREATE TABLE `wertsachen` (\n  `id` int NOT NULL AUTO_INCREMENT,\n  `name` varchar(255) NOT NULL COMMENT 'Name; mit (Klammer)',\n  `beschreibung` text,\n  `wert` decimal(10,2) DEFAULT NULL,\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Gegenstände'",
            $gegenstaende);
        $s .= self::tabelle('login_attempts',
            "CREATE TABLE `login_attempts` (\n  `id` int NOT NULL,\n  `ip` varchar(45) NOT NULL\n) ENGINE=InnoDB",
            [['id' => '1', 'ip' => '203.0.113.9']]);
        $s .= self::tabelle('orte',
            "CREATE TABLE `orte` (\n  `id` int NOT NULL\n) ENGINE=InnoDB",
            [['id' => '1']]);
        return $s . "SET FOREIGN_KEY_CHECKS=1;\n";
    }

    private static function bekannt(): array
    {
        return array_map(static fn($f) => basename($f, '.php'),
            glob(__DIR__ . '/../backend/migrations/*.php') ?: []);
    }

    // ── Zerleger ────────────────────────────────────────────────────────────

    public function testHochkommaUndSemikolonImWertTrennenNicht(): void
    {
        $sql = "INSERT INTO `t` (`a`) VALUES ('O\\'Brien; Ring');\nINSERT INTO `t` (`a`) VALUES ('zwei');\n";
        $a = vsSqlZerlegen($sql);
        $this->assertCount(2, $a);
        $this->assertSame("INSERT INTO `t` (`a`) VALUES ('O\\'Brien; Ring')", $a[0]['sql']);
        $this->assertSame("INSERT INTO `t` (`a`) VALUES ('')", $a[0]['gerippe']);
    }

    public function testVerdoppeltesHochkommaUndBackslashAmEnde(): void
    {
        $a = vsSqlZerlegen("SELECT 'a''b;c', 'd\\\\'; SELECT 2");
        $this->assertCount(2, $a);
        $this->assertSame("SELECT '', ''", $a[0]['gerippe']);
    }

    public function testWerteEinerZeile(): void
    {
        $this->assertSame(['O\'Brien, Ring', null, '12', "a\nb", "x'y"],
            vsSqlWerte("'O\\'Brien, Ring', NULL ,12,'a\\nb', 'x''y'"));
    }

    public function testKommentareFallenWeg(): void
    {
        $a = vsSqlZerlegen("-- Kopf; mit Semikolon\nSET NAMES utf8mb4; /* a;b */ # c;d\nSET FOREIGN_KEY_CHECKS=0;");
        $this->assertCount(2, $a);
        $this->assertSame('SET NAMES utf8mb4', $a[0]['sql']);
        $this->assertSame('SET FOREIGN_KEY_CHECKS=0', $a[1]['sql']);
    }

    // ── echte Sicherung ─────────────────────────────────────────────────────

    public function testSicherungAb44WirdAngenommen(): void
    {
        $r = vsUebernahmeAnalysieren(self::sicherung(), self::bekannt());
        $this->assertTrue($r['ok'], implode(', ', $r['fehler']) . ' ' . implode(' | ', $r['unzulaessig']));
        $this->assertSame('4.4.1', $r['version']);
        $this->assertSame(2, $r['zeilen']['wertsachen']);
        $this->assertSame(1, $r['zeilen']['users']);
        $this->assertSame(['001_wertsachen_v4_columns', '014_schadensfaelle'], $r['migrationen']);
        $this->assertSame(['orte'], $r['fremd']);
        // schema_migrations, users, wertsachen: je DROP, CREATE, INSERT
        $this->assertCount(9, $r['ausfuehren']);
        $this->assertStringContainsString("Kinder\\'s Fahrrad; blau", implode("\n", $r['ausfuehren']));
    }

    /** cron_backup.php schreibt etwas anders: "`, `", ", ", "= 0", Kommentarzeilen. */
    public function testSicherungAusCronWirdAngenommen(): void
    {
        $sql = "-- Wertsachen-Inventar Datenbank-Backup\n-- Erstellt am: 2026-10-14 03:00:00\n-- ValuSafe-Version: 4.5\n\n"
             . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";
        foreach (['users', 'wertsachen', 'schema_migrations'] as $t) {
            $sql .= "-- Tabelle `$t`\nDROP TABLE IF EXISTS `$t`;\nCREATE TABLE `$t` (\n  `id` int NOT NULL,\n  `migration` varchar(191) DEFAULT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n"
                  . "-- Daten `$t`\nINSERT INTO `$t` (`id`, `migration`) VALUES\n('1', '014_schadensfaelle')\n,('2', NULL)\n;\n\n";
        }
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $r = vsUebernahmeAnalysieren($sql, self::bekannt());
        $this->assertTrue($r['ok'], implode(', ', $r['fehler']) . ' ' . implode(' | ', $r['unzulaessig']));
        $this->assertSame('4.5', $r['version']);
        $this->assertSame(2, $r['zeilen']['wertsachen']);
    }

    /** Echter Fund auf 2ix (14.10.): dokumente hat die Spalte `dateigröße`. */
    public function testSpaltennameMitUmlaut(): void
    {
        $sql = self::sicherung() . self::tabelle('dokumente',
            "CREATE TABLE `dokumente` (\n  `id` int NOT NULL,\n  `dateigröße` int DEFAULT NULL\n) ENGINE=InnoDB",
            [['id' => '1', 'dateigröße' => '2048']]);
        $r = vsUebernahmeAnalysieren($sql, self::bekannt());
        $this->assertTrue($r['ok'], implode(' | ', $r['unzulaessig']));
        $this->assertSame(1, $r['zeilen']['dokumente']);
    }

    /** Jede Tabelle von install/schema.sql, so wie SHOW CREATE TABLE sie liefert, kommt durch. */
    public function testAlleCreateTableAusSchemaSql(): void
    {
        foreach (vsSqlZerlegen((string)file_get_contents(__DIR__ . '/../../install/schema.sql')) as $a) {
            if (!preg_match('/^CREATE TABLE IF NOT EXISTS/i', $a['sql'])) {
                continue;
            }
            $a = vsSqlZerlegen(preg_replace('/^CREATE TABLE IF NOT EXISTS/i', 'CREATE TABLE', $a['sql']))[0];
            $e = vsUebernahmeAnweisung($a);
            $this->assertSame('create', $e['art'], substr($a['sql'], 0, 80));
        }
    }

    public function testFluechtigeTabellenBleibenDraussen(): void
    {
        $r = vsUebernahmeAnalysieren(self::sicherung(), self::bekannt());
        $this->assertArrayNotHasKey('login_attempts', $r['zeilen']);
        $this->assertStringNotContainsString('login_attempts', implode("\n", $r['ausfuehren']));
    }

    public function testVieleZeilenInEinemInsert(): void
    {
        $viele = [];
        for ($i = 1; $i <= 3000; $i++) {
            $viele[] = ['id' => (string)$i, 'name' => "Ding $i; 'x'", 'beschreibung' => str_repeat('a(b)', 20), 'wert' => '1.00'];
        }
        $r = vsUebernahmeAnalysieren(self::sicherung(gegenstaende: $viele), self::bekannt());
        $this->assertTrue($r['ok']);
        $this->assertSame(3000, $r['zeilen']['wertsachen']);
    }

    // ── Versionen ───────────────────────────────────────────────────────────

    public function testOhneMigration014IstZuAlt(): void
    {
        $r = vsUebernahmeAnalysieren(self::sicherung(['001_wertsachen_v4_columns']), self::bekannt());
        $this->assertFalse($r['ok']);
        $this->assertContains('zu_alt', $r['fehler']);
        $this->assertSame([], $r['ausfuehren']);
    }

    public function testOhneSchemaMigrationsKeineAussage(): void
    {
        $sql = preg_replace('/DROP TABLE IF EXISTS `schema_migrations`.*?(?=DROP TABLE IF EXISTS `users`)/s', '', self::sicherung());
        $r = vsUebernahmeAnalysieren($sql, self::bekannt());
        $this->assertContains('keine_migrationen', $r['fehler']);
    }

    public function testUnbekannteMigrationHeisstNeuereVersion(): void
    {
        $r = vsUebernahmeAnalysieren(self::sicherung(['014_schadensfaelle', '099_aus_der_zukunft']), self::bekannt());
        $this->assertContains('zu_neu', $r['fehler']);
        $this->assertSame(['099_aus_der_zukunft'], $r['zu_neu']);
    }

    // ── Abwehr ──────────────────────────────────────────────────────────────

    /** @dataProvider fremdeAnweisungen */
    public function testFremdeAnweisungLehntDieGanzeDateiAb(string $einschub): void
    {
        $r = vsUebernahmeAnalysieren(self::sicherung() . $einschub, self::bekannt());
        $this->assertFalse($r['ok']);
        $this->assertContains('unzulaessig', $r['fehler']);
        $this->assertSame([], $r['ausfuehren']);
    }

    public static function fremdeAnweisungen(): array
    {
        return [
            'UPDATE'              => ["UPDATE `users` SET `password`='x';"],
            'GRANT'               => ["GRANT ALL ON *.* TO 'x'@'%';"],
            'INTO OUTFILE'        => ["SELECT '<?php ?>' INTO OUTFILE '/var/www/x.php';"],
            'INSERT mit SELECT'   => ["INSERT INTO `users` (`id`) SELECT 1;"],
            'INSERT mit Funktion' => ["INSERT INTO `users` (`id`,`username`,`password`) VALUES (2,'b',SLEEP(5));"],
            'Spaltenzahl'         => ["INSERT INTO `users` (`id`,`username`) VALUES ('2','b','c');"],
            'ON DUPLICATE'        => ["INSERT INTO `users` (`id`) VALUES ('1') ON DUPLICATE KEY UPDATE `id`=2;"],
            'CREATE ... SELECT'   => ["CREATE TABLE `kategorien` (`id` int) SELECT * FROM users;"],
            'DATA DIRECTORY'      => ["CREATE TABLE `kategorien` (`id` int) DATA DIRECTORY='/tmp';"],
            'DROP DATABASE'       => ["DROP DATABASE `valusafe`;"],
            'DROP ohne CREATE'    => ["DROP TABLE IF EXISTS `kategorien`;"],
            'SET anderer Wert'    => ["SET GLOBAL general_log = 1;"],
            'Kommentar-Trick'     => ["/*!50000 DROP TABLE `users` */;"],
        ];
    }

    public function testFremdeTabelleWirdNurUebersprungen(): void
    {
        $r = vsUebernahmeAnalysieren(self::sicherung(), self::bekannt());
        $this->assertStringNotContainsString('`orte`', implode("\n", $r['ausfuehren']));
    }

    // ── Restore (OWASP-A06-001, 4.6) ───────────────────────────────────────

    public function testRestoreSuperAdminSpieltAllesBekannteEin(): void
    {
        $r = vsRestorePruefen(self::sicherung(), true);
        $this->assertTrue($r['ok']);
        $alles = implode("\n", $r['ausfuehren']);
        $this->assertStringContainsString('INSERT INTO `users`', $alles);
        $this->assertStringContainsString("Kinder\\'s Fahrrad", $alles);
        $this->assertStringNotContainsString('`login_attempts`', $alles);
        $this->assertStringNotContainsString('`orte`', $alles);
        $this->assertSame(['orte'], $r['uebersprungen']);
    }

    public function testRestoreAdminOhneBenutzertabelle(): void
    {
        $r = vsRestorePruefen(self::sicherung(), false);
        $this->assertTrue($r['ok']);
        $alles = implode("\n", $r['ausfuehren']);
        $this->assertStringNotContainsString('`users`', $alles);
        $this->assertStringContainsString('INSERT INTO `wertsachen`', $alles);
        $this->assertSame(['orte', 'users'], $r['uebersprungen']);
    }

    public function testRestoreAlteSicherungOhneMigrationenGehtWeiter(): void
    {
        // Eigene Sicherung von vor 4.4: keine Versionsbedingung, die
        // Migrationen laufen nach dem Einspielen.
        $r = vsRestorePruefen(self::sicherung([]), true);
        $this->assertTrue($r['ok']);
    }

    /** @dataProvider fremdeAnweisungen */
    public function testRestoreLehntFremdeAnweisungenAb(string $einschub): void
    {
        $r = vsRestorePruefen(self::sicherung() . $einschub, true);
        $this->assertFalse($r['ok']);
        $this->assertNotSame([], $r['unzulaessig']);
        $this->assertSame([], $r['ausfuehren']);
    }

    public function testRestoreAngriffAusDemAudit(): void
    {
        // Admin benennt sein Konto in das des SuperAdmins um
        $r = vsRestorePruefen("UPDATE users SET username='joerg' WHERE id=5;", false);
        $this->assertFalse($r['ok']);
        // ... oder schreibt die Benutzertabelle in Sicherungsform neu
        $r = vsRestorePruefen(self::tabelle('users',
            "CREATE TABLE `users` (\n  `id` int NOT NULL\n) ENGINE=InnoDB",
            [['id' => '5']]), false);
        $this->assertSame([], $r['ausfuehren']);
        $this->assertFalse($r['ok']);
    }

    public function testRestoreLeereDatei(): void
    {
        $this->assertFalse(vsRestorePruefen('', true)['ok']);
        $this->assertFalse(vsRestorePruefen("-- nur Kommentar\nSET NAMES utf8mb4;", true)['ok']);
    }

    public function testUebernahmeOhneSuperAdminFiltertBenutzer(): void
    {
        $analyse = vsUebernahmeAnalysieren(self::sicherung(), self::bekannt());
        $this->assertTrue($analyse['ok']);
        $f = vsSqlTabellenFiltern($analyse['ausfuehren'], VS_NUR_SUPERADMIN_TABELLEN);
        $this->assertSame(['users'], $f['uebersprungen']);
        $this->assertStringNotContainsString('`users`', implode("\n", $f['behalten']));
        $this->assertCount(count($analyse['ausfuehren']) - 3, $f['behalten']);   // DROP, CREATE, INSERT
    }

    public function testTabellenlisteEntsprichtSchemaSql(): void
    {
        $schema = file_get_contents(__DIR__ . '/../../install/schema.sql');
        preg_match_all('/CREATE TABLE IF NOT EXISTS `(\w+)`/', $schema, $m);
        $erwartet = $m[1];
        $ist = VS_UEBERNAHME_TABELLEN;
        sort($erwartet);
        sort($ist);
        $this->assertSame($erwartet, $ist, 'Neue Tabelle in schema.sql? Dann auch in VS_UEBERNAHME_TABELLEN eintragen.');
    }

    // ── Dateien ─────────────────────────────────────────────────────────────

    /** @dataProvider zipNamen */
    public function testZipZiel(string $name, ?string $erwartet): void
    {
        $this->assertSame($erwartet, vsUebernahmeZipZiel($name));
    }

    public static function zipNamen(): array
    {
        return [
            'Bild'                => ['upload/6512ab_1.jpg', 'upload/6512ab_1.jpg'],
            'Beleg'               => ['documents/rechnung 2024.pdf', 'documents/rechnung 2024.pdf'],
            'Avatar'              => ['upload/avatars/u1.png', 'upload/avatars/u1.png'],
            'Grossbuchstaben'     => ['upload/IMG_1.JPG', 'upload/IMG_1.JPG'],
            'Weg hinaus'          => ['upload/../../x.jpg', null],
            'Weg hinaus 2'        => ['upload/../config.jpg', null],
            'tiefer Ordner'       => ['upload/a/b.jpg', null],
            'Wurzel'              => ['x.jpg', null],
            'anderer Ordner'      => ['backend/x.jpg', null],
            'Skript'              => ['upload/shell.php', null],
            'Skript getarnt'      => ['upload/shell.php.jpg', null],
            'phtml'               => ['upload/a.phtml', null],
            'htaccess'            => ['upload/.htaccess', null],
            'versteckt'           => ['documents/.x.pdf', null],
            'Windows-Trenner'     => ['upload\\..\\x.jpg', null],
            'absolut'             => ['/etc/passwd.jpg', null],
        ];
    }

    public function testBackupDateiNurAusDemOrdner(): void
    {
        $dir = sys_get_temp_dir() . '/vsueb_' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents("$dir/database_2026-10-14.sql", 'x');
        file_put_contents("$dir/files_2026-10-14.zip", 'x');
        try {
            $this->assertNotNull(vsUebernahmeBackupDatei($dir, 'database_2026-10-14.sql', 'db'));
            $this->assertNotNull(vsUebernahmeBackupDatei($dir, 'files_2026-10-14.zip', 'files'));
            $this->assertNull(vsUebernahmeBackupDatei($dir, 'files_2026-10-14.zip', 'db'));
            $this->assertNull(vsUebernahmeBackupDatei($dir, '../database_x.sql', 'db'));
            $this->assertNull(vsUebernahmeBackupDatei($dir, 'database_fehlt.sql', 'db'));
        } finally {
            array_map('unlink', glob("$dir/*"));
            rmdir($dir);
        }
    }
}
