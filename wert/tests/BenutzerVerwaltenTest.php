<?php

namespace Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

/**
 * Wer darf Benutzer verwalten (backend/users.php)? (4.9.2)
 *
 * Meldung 11.2026: "Ein Administrator kann keinen Ersteller anlegen", und in
 * der Rechte-Uebersicht stand beim Admin trotzdem ein Haken bei "Benutzer
 * verwalten". users.php fragte seit 4.2.2 isSuperAdmin() ohne den Rueckfall
 * auf Admin. Auf Instanzen ohne SUPERADMIN_USERNAME konnte damit niemand ein
 * Konto anlegen. Auf Instanzen mit SuperAdmin ist die Sperre fuer den Admin
 * gewollt, die Tabelle muss das dann aber auch zeigen.
 *
 * SUPERADMIN_USERNAME ist eine Konstante. Jeder Fall mit eigener Belegung
 * laeuft deshalb in einem eigenen Prozess.
 */
class BenutzerVerwaltenTest extends TestCase
{
    private const WERT = __DIR__ . '/..';

    private function als(string $name, string $rolle, ?string $original = null): void
    {
        $_SESSION = ['user_id' => 1, 'username' => $name, 'role' => $rolle, 'login_time' => time()];
        if ($original !== null) {
            $_SESSION['original_role'] = $original;
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testOhneSuperAdminVerwaltetDerAdmin(): void
    {
        $this->assertFalse(defined('SUPERADMIN_USERNAME'));
        $this->als('anna', 'admin');
        $this->assertFalse(hatSuperAdmin());
        $this->assertTrue(darfBenutzerVerwalten(), 'Ohne SuperAdmin muss der Admin Konten anlegen koennen');

        // Waehrend des Rollenwechsels zum Testen bleibt der echte Admin Admin.
        $this->als('anna', 'read', 'admin');
        $this->assertTrue(darfBenutzerVerwalten());

        $this->als('edith', 'edit');
        $this->assertFalse(darfBenutzerVerwalten());
        $this->als('lea', 'read');
        $this->assertFalse(darfBenutzerVerwalten());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLeereKonstanteGiltAlsKeinSuperAdmin(): void
    {
        define('SUPERADMIN_USERNAME', ' , ');
        $this->als('anna', 'admin');
        $this->assertFalse(hatSuperAdmin());
        $this->assertTrue(darfBenutzerVerwalten());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testMitSuperAdminNurDieser(): void
    {
        define('SUPERADMIN_USERNAME', 'joerg, chef2');
        $this->assertTrue(hatSuperAdmin());

        $this->als('joerg', 'admin');
        $this->assertTrue(darfBenutzerVerwalten());
        $this->als('chef2', 'admin');
        $this->assertTrue(darfBenutzerVerwalten());

        // Gewollt seit 4.2.2: ohne Freigabe kommt der Admin nicht hinein.
        $this->als('anna', 'admin');
        $this->assertFalse(darfBenutzerVerwalten(false));
        $this->assertFalse(darfBenutzerVerwalten(), 'ohne Einstellung gilt AUS');
        $this->als('edith', 'edit');
        $this->assertFalse(darfBenutzerVerwalten(false));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testFreigabeDurchSuperAdminGiltNurFuerAdmins(): void
    {
        define('SUPERADMIN_USERNAME', 'joerg, chef2');

        $this->als('anna', 'admin');
        $this->assertTrue(darfBenutzerVerwalten(true));
        // Rollenwechsel zum Testen: der echte Admin bleibt freigegeben.
        $this->als('anna', 'read', 'admin');
        $this->assertTrue(darfBenutzerVerwalten(true));

        // Editor und Leser nie, auch nicht mit Freigabe.
        $this->als('edith', 'edit');
        $this->assertFalse(darfBenutzerVerwalten(true));
        $this->als('lea', 'read');
        $this->assertFalse(darfBenutzerVerwalten(true));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSuperAdminKontenBleibenGeschuetzt(): void
    {
        define('SUPERADMIN_USERNAME', 'joerg, chef2');

        // Freigegebener Admin: fremde Konten ja, SuperAdmin-Konten nein -
        // auch ein noch fehlendes (chef2) und in anderer Schreibweise.
        $this->als('anna', 'admin');
        $this->assertTrue(darfKontoAnfassen('edith'));
        $this->assertTrue(darfKontoAnfassen('anna'));
        $this->assertFalse(darfKontoAnfassen('joerg'));
        $this->assertFalse(darfKontoAnfassen('chef2'));
        $this->assertFalse(darfKontoAnfassen(' JOERG '));
        $this->assertFalse(darfKontoAnfassen('Chef2'));
        $this->assertTrue(darfKontoAnfassen('joerg2'));
        $this->assertTrue(darfKontoAnfassen(''), 'leerer Name ist kein SuperAdmin (users.php meldet ihn selbst)');

        // Der SuperAdmin selbst darf alles.
        $this->als('joerg', 'admin');
        $this->assertTrue(darfKontoAnfassen('chef2'));
        $this->assertTrue(darfKontoAnfassen('joerg'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testOhneSuperAdminIstKeinKontoGeschuetzt(): void
    {
        $this->als('anna', 'admin');
        $this->assertFalse(istSuperAdminName('joerg'));
        $this->assertTrue(darfKontoAnfassen('joerg'));
    }

    public function testUsersPhpSchuetztSuperAdminKonten(): void
    {
        $code = file_get_contents(self::WERT . '/backend/users.php');
        // Pruefung im POST-Block vor allen Aktionen, fuer neuen und alten Namen.
        $post = substr($code, strpos($code, 'validateRequest();'), 2000);
        $this->assertStringContainsString("['add', 'edit']", $post);
        $this->assertStringContainsString("['edit', 'delete', 'reset_2fa']", $post);
        $this->assertStringContainsString('if (!darfKontoAnfassen($_name))', $post);
        $this->assertLessThan(strpos($code, "\$_POST['action'] === 'add'"), strpos($code, 'darfKontoAnfassen($_name)'));
        // Liste: keine Knoepfe bei SuperAdmin-Konten.
        $this->assertStringContainsString("<?php if (!darfKontoAnfassen((string)\$user['username'])): ?>", $code);
        foreach (['de', 'en'] as $l) {
            $texte = include self::WERT . "/backend/lang/$l.php";
            $this->assertNotEmpty($texte['usr_superadmin_protected'] ?? '', "backend/lang/$l.php");
        }
    }

    public function testSchalterAufSuperAdminSeite(): void
    {
        $code = file_get_contents(self::WERT . '/backend/superadmin.php');
        $this->assertStringContainsString("'sa_users_allowed'", $code);
        $this->assertStringContainsString("\$vorgabe = ['sa_users_allowed' => '0'];", $code, 'Vorgabe AUS');
        $this->assertStringContainsString("isset(\$_POST['sa_users_allowed'])", $code);
        $this->assertStringContainsString('for="sa_users_allowed"', $code);
        $perm = file_get_contents(self::WERT . '/backend/permissions.php');
        $this->assertStringContainsString("getSuperAdminSetting('sa_users_allowed', false)", $perm);
        $this->assertStringContainsString("getSuperAdminSetting('sa_users_allowed', false)",
            file_get_contents(self::WERT . '/rechte_helpers.php'));
    }

    public function testUsersPhpPrueftMitRueckfall(): void
    {
        $code = file_get_contents(self::WERT . '/backend/users.php');
        $this->assertStringContainsString('if (!darfBenutzerVerwalten())', $code);
        $this->assertStringNotContainsString('if (!isSuperAdmin())', $code,
            'users.php ohne Rueckfall sperrt Instanzen ohne SuperAdmin komplett aus');
    }

    public function testLinksFolgenDerselbenRegel(): void
    {
        // Ein Link, der in der Umleitung endet, oder ein fehlender Link, wo die
        // Seite offen waere - beides war bis 4.9.1 moeglich.
        foreach (['backend/layout/sidebar.php', 'settings.php', 'backend/index.php'] as $datei) {
            $code = file_get_contents(self::WERT . '/' . $datei);
            $this->assertMatchesRegularExpression('/darfBenutzerVerwalten\(\)/', $code, $datei);
        }
        $sidebar = file_get_contents(self::WERT . '/backend/layout/sidebar.php');
        $this->assertDoesNotMatchRegularExpression("/isSuperAdmin\(\) \? \[\[\s*'title' => 'Alle Benutzer'/", $sidebar);
    }

    public function testRechteTabelleZeigtSuperAdminSperre(): void
    {
        $code = file_get_contents(self::WERT . '/backend/permissions.php');
        $this->assertStringContainsString("\$p['action_key'] === 'admin_users' && hatSuperAdmin()", $code);
        $this->assertStringContainsString("t('perm_admin_superadmin_only')", $code);
        $this->assertStringContainsString("t('perm_admin_area_note')", $code);
        $this->assertStringNotContainsString('Diese Berechtigung ist fest', $code, 'Titel ueber t(perm_locked)');
    }

    public function testNeueTexteInAllenSprachen(): void
    {
        $dateien = glob(self::WERT . '/lang/[a-z][a-z].php');
        $this->assertCount(10, $dateien);
        foreach ($dateien as $datei) {
            $texte = include $datei;
            $this->assertIsArray($texte, basename($datei));
            foreach (['perm_admin_superadmin_only', 'perm_admin_area_note', 'perm_locked'] as $schluessel) {
                $this->assertNotEmpty($texte[$schluessel] ?? '', basename($datei) . ": $schluessel");
            }
        }
    }

    public function testAdminZeilenSindFuerEditorUndLeserGesperrt(): void
    {
        // Gewollte Schutzlogik, kein Fehler: requireBackendAccess() laesst nur
        // Admins in den Verwaltungsbereich. Ein Haken bei Editor/Leser wuerde
        // etwas versprechen, das nirgends wirkt. Deshalb locked_for edit,read.
        $schema = file_get_contents(self::WERT . '/../install/schema.sql');
        preg_match_all("/\('(admin_[a-z_]+)', 'Admin-Bereich',[^\n]*?\b(\d), (\d), (\d), '([^']*)'\)[,;]?\s*$/mu", $schema, $z, PREG_SET_ORDER);
        $this->assertCount(7, $z, 'sieben Zeilen im Admin-Bereich');
        foreach ($z as [, $schluessel, $admin, $edit, $read, $gesperrt]) {
            $this->assertSame(['1', '0', '0'], [$admin, $edit, $read], $schluessel);
            $rollen = explode(',', $gesperrt);
            $this->assertContains('edit', $rollen, $schluessel);
            $this->assertContains('read', $rollen, $schluessel);
        }
    }
}
