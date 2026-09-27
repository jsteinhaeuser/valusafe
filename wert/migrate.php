<?php
/**
 * migrate.php — ValuSafe Migrationssystem
 *
 * Ersetzt manuelle ALTER-TABLE-Läufe / db_check.php-Copy&Paste-SQL durch
 * versionierte, nachvollziehbare Migrationen.
 *
 * Web-Aufruf:  migrate.php            (Admin-Login erforderlich)
 *              migrate.php?dry=1      (Trockenlauf, nichts wird ausgeführt)
 * CLI-Aufruf:  php migrate.php
 *              php migrate.php --dry-run
 *
 * Migrationsdateien liegen in backend/migrations/, Format:
 *   <?php return ['description' => '...', 'statements' => ['SQL', ...]];
 * Dateiname bestimmt die Ausführungsreihenfolge (alphabetisch/numerisch).
 *
 * Bereits ausgeführte Migrationen werden in der Tabelle
 * `schema_migrations` festgehalten und nicht erneut ausgeführt.
 *
 * Sicherheit: Statements, die auf "existiert schon" hindeuten (doppelte
 * Spalte/Tabelle/Index), werden als harmlos übersprungen — das macht die
 * Migrationen über Instanzen mit unterschiedlichem Vorstand hinweg
 * idempotent. Jeder andere Fehler bricht sofort ab, ohne die Migration
 * als erledigt zu markieren.
 */

$isCli = (php_sapi_name() === 'cli');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (!$isCli) {
    requireAdmin();
}

$dryRun = $isCli
    ? in_array('--dry-run', $argv ?? [], true)
    : !empty($_GET['dry']);

global $pdo;

// ============================================================================
// Ausfuehren
// ============================================================================
// Die Schleife selbst steht seit 4.3.24 in backend/migration_runner.php. Sie
// wird von zwei Stellen gebraucht: von hier (Admin-Anmeldung, HTML-Ausgabe)
// und von migration_agent.php (Token, JSON) - und sie liess sich vorher, fest
// zwischen requireAdmin() und einem <style>-Block eingeklemmt, nicht isoliert
// pruefen.
require_once __DIR__ . '/backend/migration_runner.php';

$ergebnis     = vsMigrationenAusfuehren($pdo, __DIR__ . '/backend/migrations', $dryRun);
$report       = $ergebnis['report'];
$counts       = $ergebnis['counts'];
$stoppedEarly = $ergebnis['stoppedEarly'];

// ============================================================================
// Ausgabe
// ============================================================================
if ($isCli) {
    echo "ValuSafe Migrationssystem" . ($dryRun ? " (Trockenlauf)" : "") . "\n";
    echo str_repeat('=', 60) . "\n";
    foreach ($report as $r) {
        printf("[%-8s] %s %s\n", strtoupper($r['status']), $r['name'], $r['description'] ? '— ' . $r['description'] : '');
        foreach ($r['statements'] as $s) {
            if ($s['result'] === 'error') {
                echo "    FEHLER: {$s['sql']}\n    -> {$s['msg']}\n";
            } elseif ($s['result'] === 'skip') {
                echo "    (übersprungen, existiert bereits): {$s['sql']}\n";
            }
        }
    }
    echo str_repeat('-', 60) . "\n";
    echo "bereits angewendet: {$counts['already']} | neu angewendet: {$counts['applied']} | dry-run: {$counts['dry-run']} | Fehler: {$counts['error']} | ausstehend: {$counts['pending']}\n";
    if ($stoppedEarly) {
        echo "\nABGEBROCHEN: Eine Migration ist fehlgeschlagen, nachfolgende wurden nicht ausgeführt.\n";
        exit(1);
    }
    exit(0);
}

header('Content-Type: text/html; charset=UTF-8');
$instance = defined('APP_NAME') ? APP_NAME : gethostname();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>ValuSafe Migrationssystem</title>
<style>
  body { font-family: monospace; background: #111; color: #eee; padding: 2rem; max-width: 900px; margin: 0 auto; }
  h1 { color: #f0a500; }
  .ok      { color: #4caf50; }
  .warn    { color: #f0a500; }
  .err     { color: #f44336; }
  .muted   { color: #888; }
  li { margin: .35rem 0; }
  pre { background: #1e1e1e; border: 1px solid #444; padding: .6rem; border-radius: 6px; overflow-x: auto; color: #7ec8e3; font-size: .8rem; margin: .3rem 0; }
  .badge { display: inline-block; padding: .1rem .5rem; border-radius: 4px; font-size: .75rem; margin-right: .5rem; }
  .badge-already  { background: #333; color: #aaa; }
  .badge-applied  { background: #1b5e20; color: #a5d6a7; }
  .badge-dry-run  { background: #4a3800; color: #ffe082; }
  .badge-error    { background: #5f0000; color: #ef9a9a; }
  .badge-pending  { background: #222; color: #666; }
  .summary { display: flex; gap: 1rem; margin: 1rem 0; flex-wrap: wrap; }
  .summary div { padding: .6rem 1.2rem; border-radius: 8px; font-size: 1rem; font-weight: bold; }
  a.btn { display: inline-block; background: #333; color: #eee; border: 1px solid #555; padding: .4rem 1rem; border-radius: 4px; text-decoration: none; margin-top: 1rem; }
  a.btn:hover { background: #555; }
</style>
</head>
<body>
<h1>🔧 ValuSafe Migrationssystem<?= $dryRun ? ' — Trockenlauf' : '' ?></h1>
<p>Instanz: <strong><?= htmlspecialchars($instance) ?></strong> &nbsp;|&nbsp; <?= date('d.m.Y H:i:s') ?></p>

<div class="summary">
  <div style="background:#222">⏭️ <?= $counts['already'] ?> bereits angewendet</div>
  <div style="background:#1b5e20">✅ <?= $counts['applied'] ?> neu angewendet</div>
  <div style="background:#4a3800">🧪 <?= $counts['dry-run'] ?> Trockenlauf</div>
  <div style="background:#5f0000">❌ <?= $counts['error'] ?> Fehler</div>
  <div style="background:#222">⏸️ <?= $counts['pending'] ?> ausstehend</div>
</div>

<?php if ($stoppedEarly): ?>
<p class="err"><strong>Abgebrochen:</strong> Eine Migration ist fehlgeschlagen. Nachfolgende Migrationen wurden nicht ausgeführt, bis der Fehler behoben ist.</p>
<?php endif; ?>

<?php if (!$dryRun): ?>
<p><a class="btn" href="?dry=1">🧪 Nächsten Lauf als Trockenlauf ansehen</a></p>
<?php else: ?>
<p class="muted">Trockenlauf — es wurde nichts verändert. <a href="migrate.php" style="color:#7ec8e3">Echten Lauf starten</a></p>
<?php endif; ?>

<h2>Migrationen</h2>
<ul style="list-style:none; padding-left:0;">
<?php foreach ($report as $r): ?>
  <li>
    <span class="badge badge-<?= $r['status'] ?>"><?= strtoupper($r['status']) ?></span>
    <strong><?= htmlspecialchars($r['name']) ?></strong>
    <?php if ($r['description']): ?> — <span class="muted"><?= htmlspecialchars($r['description']) ?></span><?php endif; ?>
    <?php foreach ($r['statements'] as $s): ?>
      <?php if ($s['result'] === 'error'): ?>
        <pre class="err"><?= htmlspecialchars($s['sql']) ?>
-&gt; <?= htmlspecialchars($s['msg']) ?></pre>
      <?php elseif ($s['result'] === 'skip'): ?>
        <pre class="warn">(übersprungen, existiert bereits) <?= htmlspecialchars($s['sql']) ?></pre>
      <?php endif; ?>
    <?php endforeach; ?>
  </li>
<?php endforeach; ?>
</ul>

<hr style="border-color:#333; margin-top:2rem">
<p class="muted" style="font-size:.8rem">Dieses Script kann dauerhaft installiert bleiben (Admin-Login-geschützt). Neue Migrationen einfach als weitere Datei in <code>backend/migrations/</code> ablegen.</p>
</body>
</html>
