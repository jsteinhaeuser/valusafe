<?php
/**
 * backend/update_check.php
 * Wird von backend/layout/header.php eingebunden.
 *
 * Zwei Fälle für die Update-Notice:
 * 1. Lokale Version wurde aktualisiert (nach Sync) — verglichen mit
 *    der zuletzt in der DB gespeicherten "gesehenen" Version
 * 2. Eine neuere Version liegt auf dem Master (all-inkl) — nur wenn
 *    VALSAFE_UPDATE_CHECK_URL definiert ist
 */

if (!empty($_SESSION['update_notice_checked'])) {
    return;
}
$_SESSION['update_notice_checked'] = true;

try {
    $changelogFile = __DIR__ . '/../changelog_data.php';
    if (!file_exists($changelogFile)) return;
    $changelog    = require $changelogFile;
    $localVersion = $changelog[0]['version'] ?? null;
    if (!$localVersion) return;

    // ── Fall 1: Lokale Version nach Sync höher als zuletzt gesehen ──────────
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId) {
        try {
            // Spalte anlegen falls noch nicht vorhanden
            try {
                $db->execute("ALTER TABLE users ADD COLUMN last_seen_version VARCHAR(20) NULL", []);
            } catch (Exception $e) { /* existiert bereits */ }

            $user     = $db->selectOne("SELECT last_seen_version FROM users WHERE id = ?", [$userId]);
            $lastSeen = $user['last_seen_version'] ?? null;

            if ($lastSeen === null || version_compare($localVersion, $lastSeen, '>')) {
                if ($lastSeen !== null) {
                    // Nur anzeigen wenn vorher schon eine Version bekannt war
                    $_SESSION['update_notice'] = [
                        'local'         => $lastSeen,
                        'remote'        => $localVersion,
                        'datum'         => $changelog[0]['date'] ?? '',
                        'changelog_url' => '/changelog_public.php',
                        'typ'           => 'local',
                    ];
                }
                // Aktuelle Version merken
                $db->execute("UPDATE users SET last_seen_version = ? WHERE id = ?", [$localVersion, $userId]);
            }
        } catch (Exception $e) { /* still ignorieren */ }
    }

    // ── Fall 2: Neuere Version auf Master verfügbar ──────────────────────────
    if (empty($_SESSION['update_notice']) && defined('VALSAFE_UPDATE_CHECK_URL')) {
        try {
            $ctx  = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
            $json = @file_get_contents(VALSAFE_UPDATE_CHECK_URL, false, $ctx);
            if ($json) {
                $remote = json_decode($json, true);
                if ($remote && !empty($remote['version'])) {
                    if (version_compare($remote['version'], $localVersion, '>')) {
                        $_SESSION['update_notice'] = [
                            'local'         => $localVersion,
                            'remote'        => $remote['version'],
                            'datum'         => $remote['datum']         ?? '',
                            'changelog_url' => $remote['changelog_url'] ?? '/changelog_public.php',
                            'typ'           => 'remote',
                        ];
                    }
                }
            }
        } catch (Exception $e) { /* still ignorieren */ }
    }

} catch (Exception $e) { /* still ignorieren */ }
