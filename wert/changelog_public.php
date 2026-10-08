<?php
/**
 * changelog.php - Öffentlicher Changelog für User
 * Eingebunden in /wert – zeigt nur Features und wichtige Fixes
 */
require_once 'db.php';
require_once 'helpers.php';
requireLogin();

// Changelog-Daten laden – Pfad anpassen falls nötig!
// Option A: Datei liegt direkt im /wert Verzeichnis
$changelogFile = __DIR__ . '/changelog_data.php';
// Option B: Datei liegt auf dem Hub (dann per URL laden)
// → In diesem Fall changelog_data.php nach /wert kopieren

if (!file_exists($changelogFile)) {
    die('Changelog-Datei nicht gefunden.');
}

$changelog = require $changelogFile;

// Nur öffentliche Einträge (feature + fix mit public=true)
$publicTypes = ['feature', 'fix'];
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Changelog · Inventarverwaltung</title>
<style>
.changelog-wrap {
    max-width: 760px;
    margin: 30px auto;
    padding: 0 20px;
    font-family: inherit;
}

.changelog-header {
    margin-bottom: 28px;
}

.changelog-header h1 {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
}

.changelog-header p {
    font-size: 13px;
    color: #64748b;
}

.version-block {
    margin-bottom: 32px;
}

.version-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e2e8f0;
}

.version-tag {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    font-family: monospace;
}

.version-tag.latest { color: #0284c7; }

.latest-badge {
    background: linear-gradient(135deg, #0284c7, #06b6d4);
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
}

.version-date {
    font-size: 12px;
    color: #94a3b8;
    margin-left: auto;
}

.entry-list {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.entry {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.entry-badge {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 5px;
    white-space: nowrap;
    flex-shrink: 0;
    margin-top: 1px;
}

.badge-feature { background: #dcfce7; color: #15803d; }
.badge-fix     { background: #fef3c7; color: #b45309; }

.entry-text {
    font-size: 13px;
    color: #334155;
    line-height: 1.5;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #0284c7;
    text-decoration: none;
    font-size: 13px;
    margin-bottom: 20px;
}

.back-link:hover { text-decoration: underline; }

@media (max-width: 600px) {
    .changelog-wrap { padding: 0 12px; }
    .version-tag { font-size: 14px; }
}
</style>
</head>
<body>
<div class="changelog-wrap">

    <a href="index.php" class="back-link"><?php echo t('btn_back_to_overview'); ?></a>

    <div class="changelog-header">
        <h1>📋 Changelog</h1>
        <p><?php echo t('changelog_public_subtitle'); ?></p>
    </div>

    <?php foreach ($changelog as $i => $version):
        // Nur öffentliche Einträge filtern
        $publicEntries = array_filter($version['entries'], function($e) use ($publicTypes) {
            return in_array($e['type'], $publicTypes) && $e['public'] === true;
        });

        if (empty($publicEntries)) continue;
    ?>
    <div class="version-block">
        <div class="version-header">
            <span class="version-tag <?php echo $i === 0 ? 'latest' : ''; ?>">
                v<?php echo $version['version']; ?>
            </span>
            <?php if ($i === 0): ?>
                <span class="latest-badge">NEU</span>
            <?php endif; ?>
            <span class="version-date"><?php echo date('d.m.Y', strtotime($version['date'])); ?></span>
        </div>

        <div class="entry-list">
            <?php foreach ($publicEntries as $entry): ?>
            <div class="entry">
                <span class="entry-badge <?php echo $entry['type'] === 'feature' ? 'badge-feature' : 'badge-fix'; ?>">
                    <?php echo $entry['type'] === 'feature' ? '✨ Neu' : '🔧 Fix'; ?>
                </span>
                <span class="entry-text"><?php echo htmlspecialchars($entry['text']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

</div>
</body>
</html>
