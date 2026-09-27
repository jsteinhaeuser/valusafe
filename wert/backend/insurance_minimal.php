<?php
require_once 'config.php';
requireBackendAccess();
echo "SCHRITT 1 OK - Auth funktioniert<br>";

$versicherungen = $db->select("SELECT id, name FROM versicherungen ORDER BY name ASC");
echo "SCHRITT 2 OK - Query funktioniert, " . count($versicherungen) . " Einträge<br>";

$pageTitle = 'Versicherungen Test';
include 'layout/header_next_page.php';
echo "SCHRITT 3 OK - Header geladen<br>";
?>
<main class="backend-main">
    <h2>Test OK</h2>
    <?php foreach ($versicherungen as $v): ?>
        <p><?php echo htmlspecialchars($v['name']); ?></p>
    <?php endforeach; ?>
</main>
<?php include 'layout/footer_next_page.php'; ?>
