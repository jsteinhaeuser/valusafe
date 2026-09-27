<?php
/**
 * sync_helpers.php
 * Reine Hilfsfunktionen aus sync_agent.php, ausgelagert damit sie ohne
 * Token-Check / Seiteneffekte (DB, Header, exit) automatisiert getestet
 * werden können. Verhalten ist 1:1 identisch zur bisherigen Inline-Version.
 */

/**
 * Verhindert Path-Traversal bei read_file/write_file/delete_file.
 * Gibt null zurück, wenn der Pfad leer ist oder ".." enthält.
 */
function sanitizePath(string $path): ?string {
    $path = ltrim(str_replace('\\', '/', $path), '/');
    if (empty($path) || strpos($path, '..') !== false) return null;
    return $path;
}

/**
 * Listet rekursiv alle sync-fähigen Dateien unterhalb von $dir auf
 * (relativ zu $baseDir), unter Auslassung von EXCLUDED_DIRS / EXCLUDED_FILES
 * und Beschränkung auf SYNC_EXTENSIONS. Ergebnis: relativer Pfad => md5.
 */
function listFiles(string $dir, string $baseDir, array &$result): void {
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = $dir . '/' . $item;
        $relative = ltrim(str_replace($baseDir, '', $fullPath), '/');
        if (is_dir($fullPath)) {
            if (in_array(explode('/', $relative)[0], EXCLUDED_DIRS)) continue;
            listFiles($fullPath, $baseDir, $result);
        } else {
            if (in_array(basename($item), EXCLUDED_FILES)) continue;
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (!in_array($ext, SYNC_EXTENSIONS)) continue;
            $result[$relative] = md5_file($fullPath);
        }
    }
}

/**
 * Einzige, konsolidierte Prüfung für erlaubte Bild-Dateinamen bei den
 * Actions image/get_image, put_image und upload. Vorher gab es drei
 * separate Regex-Kopien in sync_agent.php; eine davon (upload/Base64-Pfad)
 * hatte einen Tippfehler ("png,gif" statt "png|gif") und lehnte dadurch
 * per Base64 hochgeladene .png- und .gif-Dateien fälschlich ab.
 * s. tests/SyncHelpersTest.php::testAcceptsPngAndGifFilenames.
 */
function isAllowedImageFilename(string $filename): bool {
    return (bool)preg_match('/\.(jpg|jpeg|png|gif|webp|heic|heif)$/i', $filename);
}

/**
 * Sammelt für alle Bilddateien im Upload-Verzeichnis den Dateinamen samt
 * MD5-Hash. Vorher dreifach identisch inline in sync_agent.php
 * (preview/status, export, images) — hier konsolidiert.
 *
 * @return array<string,string> Dateiname => md5
 */
function collectImageHashes(string $uploadDir): array {
    $images = [];
    if (is_dir($uploadDir)) {
        foreach (glob($uploadDir . '*.{jpg,jpeg,png,gif,webp,heic,heif}', GLOB_BRACE) as $f) {
            $images[basename($f)] = md5_file($f);
        }
    }
    return $images;
}

/**
 * Validiert den rohen JSON-Body der import-Action. Gibt das dekodierte
 * Payload-Array zurück, oder null wenn ungültig (kein valides JSON oder
 * fehlender 'data'-Schlüssel).
 */
function decodeImportPayload(string $raw): ?array {
    $payload = json_decode($raw, true);
    if (!$payload || !isset($payload['data'])) {
        return null;
    }
    return $payload;
}

/**
 * Baut das INSERT-Statement (mit Platzhaltern) für eine Tabelle anhand der
 * Spaltennamen der ersten Zeile. Reine String-Logik, keine DB-Anbindung —
 * das eigentliche SQL-Escaping der Spaltennamen (Backticks) bleibt gleich
 * wie im Original.
 */
function buildInsertSql(string $table, array $columns): string {
    $colList = implode(', ', array_map(fn($c) => "`$c`", $columns));
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    return "INSERT INTO `$table` ($colList) VALUES ($placeholders)";
}

/**
 * Laesst sich der Inhalt unveraendert als JSON-String uebertragen?
 *
 * Hintergrund: json_encode() verlangt gueltiges UTF-8 und gibt bei
 * Binaerdaten kommentarlos false zurueck. Die Antwort des Agenten waere
 * dann LEER — nicht etwa fehlerhaft, sondern gar nicht vorhanden. Genau
 * das ist der Grund, warum ein PDF frueher nicht durch den Datei-Sync
 * ging: die Erweiterungsliste war nur die sichtbare Huerde, die
 * eigentliche lag hier.
 *
 * mb_check_encoding braucht die mbstring-Erweiterung. Sie ist auf allen
 * elf Instanzen vorhanden, aber der Agent soll auch ohne sie antworten
 * koennen; '//u' auf ein leeres Muster ist die gleiche Pruefung in PCRE.
 */
function istTextInhalt(string $inhalt): bool {
    if ($inhalt === '') return true;
    if (function_exists('mb_check_encoding')) {
        return mb_check_encoding($inhalt, 'UTF-8');
    }
    return preg_match('//u', $inhalt) === 1;
}
