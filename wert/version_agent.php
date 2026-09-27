<?php
/**
 * version_agent.php - Versions-Agent
 * Läuft auf jeder Instanz (NAS, Lima-city etc.)
 * Liefert MD5-Hashes aller relevanten Dateien als JSON
 *
 * SICHERHEIT: Nur mit gültigem Token abrufbar
 *
 * AUSNAHMEN: Dateien in $localOverrides werden vom Vergleich
 * ausgeschlossen – sie dürfen auf dieser Instanz bewusst abweichen.
 * Die Ausnahmeliste wird im JSON mitgeliefert (Schlüssel: "overrides")
 * und im Versionsvergleich des Hubs mit 🔒 angezeigt.
 */

// Token prüfen — Wert kommt ausschliesslich aus config.php (Konstante TOOLS_TOKEN).
// Muss mit instanceToken($inst, 'tools') auf dem Hub uebereinstimmen.
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Woher das Token kommen darf — in dieser Reihenfolge.
 *
 * Die Kopfzeile steht zuerst, weil sie die einzige Stelle ist, die in keinem
 * Zugriffsprotokoll landet: Apache und nginx schreiben Anfragezeile, Referer
 * und Kennung, keine beliebigen Kopfzeilen. Ein Token als GET-Parameter stand
 * dagegen im Klartext in jedem Protokoll — gefunden am 16.09.2026 bei
 * all-inkl, elfmal, bei drei Hostern.
 *
 * GET bleibt BEWUSST erhalten. Waehrend des Umbaus schicken die Hub-Seiten
 * noch die alte Form; faellt GET weg, bevor der Hub umgestellt ist, steht die
 * ganze Flotte auf 403. Entfernt werden darf es erst, wenn keine Stelle mehr
 * so aufruft — und das ist eine eigene Entscheidung, kein Nebeneffekt.
 */
$token      = $_SERVER['HTTP_X_VALUSAFE_TOKEN'] ?? $_POST['token'] ?? $_GET['token'] ?? '';
$validToken = defined('TOOLS_TOKEN') ? (string)TOOLS_TOKEN : '';

if ($validToken === '') {
    http_response_code(500);
    echo json_encode(['error' => 'TOOLS_TOKEN nicht konfiguriert']);
    exit;
}

// hash_equals statt !== : laufzeitkonstanter Vergleich.
if ($token === '' || !hash_equals($validToken, $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ============================================================================
// INSTANZ-SPEZIFISCHE AUSNAHMEN
// Dateien, die auf DIESER Instanz bewusst vom Master abweichen duerfen.
// Pfade relativ zum Wurzelverzeichnis, Schraegstrich als Trenner.
//
// Seit dem 16.09.2026 kommt die Liste aus der config.php dieser Instanz:
//
//     define('VERSION_OVERRIDES', ['config.php', 'noch_eine.php']);
//
// Fehlt die Konstante, gilt die Vorgabe unten. Vorher stand die Liste HIER —
// und weil sie je Instanz verschieden sein durfte, war die Datei nicht
// synchronisierbar und musste von Hand gepflegt werden. Genau so ist die
// Kopie im Repository fuenf Monate lang veraltet, ohne dass es auffiel.
// Der Instanzzustand gehoert in die config.php, nicht in den Quelltext.
//
// 'version_agent.php' steht BEWUSST NICHT MEHR in der Vorgabe. Solange sie
// sich selbst ausnahm, konnte der Versions-Vergleich die eine Datei nicht
// sehen, die ihn durchfuehrt: eine veraltete Fassung auf einer Instanz fiel
// nirgends auf. Sie bleibt in EXCLUDED_FILES des Sync-Agenten, wird also
// weiterhin per FTP verteilt — sichtbar ja, selbstverteilend nein. Wer die
// Pruefinstanz mit einem Klick auf elf Instanzen ueberschreiben kann,
// verliert im Fehlerfall genau das Werkzeug, mit dem er es merken wuerde.
// ============================================================================
$localOverrides = defined('VERSION_OVERRIDES') && is_array(VERSION_OVERRIDES)
    ? VERSION_OVERRIDES
    : ['config.php'];   // config.php traegt die Zugangsdaten dieser Instanz

// ============================================================================
// Konfiguration
// ============================================================================
$baseDir     = __DIR__;
// Dieselbe Liste wie SYNC_EXTENSIONS in sync_agent.php. Lief sie
// auseinander, wurde ein Dateityp zwar uebertragen, aber nie verglichen —
// eine veraltete Datei auf einer Instanz waere dann nirgends aufgefallen.
// 'xml' fehlte hier (15.09.2026).
$extensions  = ['php', 'js', 'css', 'html', 'md', 'json', 'xml', 'svg', 'pdf'];
$excludeDirs = ['upload', 'backups', 'backup', 'documents', 'vendor', '.git', 'node_modules', 'logs'];

// Alle Dateien rekursiv sammeln
function scanFiles($dir, $baseDir, $extensions, $excludeDirs, $overrides) {
    $result = [];

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                function($file, $key, $iterator) use ($excludeDirs) {
                    if ($iterator->hasChildren()) {
                        return !in_array($file->getFilename(), $excludeDirs);
                    }
                    return true;
                }
            )
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, $extensions)) continue;

            // Relativer Pfad
            $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath);

            // Ausnahmen überspringen
            if (in_array($relativePath, $overrides)) continue;

            $result[$relativePath] = [
                'md5'      => md5_file($file->getPathname()),
                'size'     => $file->getSize(),
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }
    } catch (Exception $e) {
        // Fehler ignorieren
    }

    ksort($result);
    return $result;
}

$files = scanFiles($baseDir, $baseDir, $extensions, $excludeDirs, $localOverrides);

header('Content-Type: application/json');
echo json_encode([
    'instance'  => gethostname(),
    'timestamp' => date('Y-m-d H:i:s'),
    'baseDir'   => basename($baseDir),
    'fileCount' => count($files),
    'files'     => $files,
    'overrides' => $localOverrides,
]);
