<?php
/**
 * backend/health_check.php
 *
 * Interner Selbsttest der eigenen Instanz — admin-geschützt.
 * Führt dieselben Prüfungen wie das externe Health-Check-Tool auf
 * dem Hub aus (siehe backend/health_checks_lib.php), aber
 * direkt gegen die eigene Domain, ohne dass der Hub erreichbar
 * sein muss.
 */

require_once 'config.php';
requireBackendAccess();
require_once __DIR__ . '/health_checks_lib.php';

$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];

// ============================================================================
// AJAX — einzelner Check
// ============================================================================
if (($_GET['ajax'] ?? '') === 'single') {
    header('Content-Type: application/json');
    $checkId = $_GET['check'] ?? '';
    echo json_encode(hc_run_check($checkId, $baseUrl));
    exit;
}

$pageTitle = 'Health Check';
include 'layout/header_next_page.php';
?>

<main class="backend-main">

    <div class="activity-timeline" style="margin-bottom: 24px;">
        <h2><i class="ti ti-stethoscope"></i> Health Check</h2>
        <p style="color:#888; margin-bottom: 16px;">
            Automatisierter Test der wichtigsten Funktionen und Dateien dieser Instanz
            (<code style="background: var(--vs-surface-2); padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($baseUrl); ?></code>).
            Hilft, Server-Fehler oder fehlende bzw. beschädigte Dateien schnell zu erkennen.
        </p>

        <div style="display:flex; gap:10px; margin-bottom: 20px; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" id="btnRun" onclick="runAllChecks()">
                <i class="ti ti-player-play"></i> Alle Checks starten
            </button>
            <button type="button" class="btn" onclick="resetChecks()">
                <i class="ti ti-refresh"></i> Zurücksetzen
            </button>
        </div>

        <div style="display:flex; gap:24px; margin-bottom:20px;">
            <div><strong id="hcOk" style="color: var(--vs-success, #10b981); font-size:22px;">0</strong> <span style="color:#888; font-size:12px;">OK</span></div>
            <div><strong id="hcErr" style="color: var(--vs-danger, #ef4444); font-size:22px;">0</strong> <span style="color:#888; font-size:12px;">Fehler</span></div>
            <div><strong id="hcTot" style="font-size:22px;"><?php echo count(hc_check_list()); ?></strong> <span style="color:#888; font-size:12px;">Gesamt</span></div>
        </div>

        <table class="backend-table" style="margin-left:0; width:100%;">
            <thead>
                <tr>
                    <th style="width:36px;"></th>
                    <th>Check</th>
                    <th>Detail</th>
                    <th style="width:70px; text-align:right;">Zeit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (hc_check_list() as $c): ?>
                <tr id="hc-row-<?php echo $c['id']; ?>">
                    <td><span id="hc-icon-<?php echo $c['id']; ?>" style="display:inline-block; width:14px; height:14px; border-radius:50%; background:#ddd;"></span></td>
                    <td>
                        <?php echo htmlspecialchars($c['label']); ?>
                        <span style="font-size:10px; font-weight:700; padding:1px 6px; border-radius:4px; margin-left:8px;
                            <?php
                            echo match($c['tag']) {
                                'crit' => 'background:rgba(239,68,68,.15); color:#ef4444;',
                                'sec'  => 'background:rgba(59,130,246,.15); color:#2563eb;',
                                default=> 'background:rgba(16,185,129,.15); color:#059669;',
                            };
                            ?>"><?php echo ['crit'=>'kritisch','sec'=>'sicherheit','func'=>'funktion'][$c['tag']]; ?></span>
                    </td>
                    <td id="hc-detail-<?php echo $c['id']; ?>" style="color:#888; font-size:12px;">–</td>
                    <td id="hc-ms-<?php echo $c['id']; ?>" style="text-align:right; color:#888; font-size:12px;">–</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</main>

<script>
const HC_CHECKS = <?php echo json_encode(array_column(hc_check_list(), 'id')); ?>;
let hcRunning = false;

function hcSetRow(id, ok, detail, ms) {
    const icon = document.getElementById('hc-icon-' + id);
    const det  = document.getElementById('hc-detail-' + id);
    const msEl = document.getElementById('hc-ms-' + id);
    if (icon) icon.style.background = ok ? '#10b981' : '#ef4444';
    if (det)  det.textContent = detail || '';
    if (msEl) msEl.textContent = ms ? ms + 'ms' : '';
}

function hcUpdateCounters() {
    let ok = 0, err = 0;
    HC_CHECKS.forEach(id => {
        const icon = document.getElementById('hc-icon-' + id);
        if (!icon) return;
        const bg = icon.style.background;
        if (bg === 'rgb(16, 185, 129)') ok++;
        else if (bg === 'rgb(239, 68, 68)') err++;
    });
    document.getElementById('hcOk').textContent = ok;
    document.getElementById('hcErr').textContent = err;
}

async function hcRunOne(id) {
    const icon = document.getElementById('hc-icon-' + id);
    if (icon) icon.style.background = '#f59e0b';
    try {
        const res = await fetch('health_check.php?ajax=single&check=' + encodeURIComponent(id));
        const data = await res.json();
        hcSetRow(id, data.ok, data.detail, data.ms);
    } catch (e) {
        hcSetRow(id, false, 'Fehler: ' + e.message, null);
    }
    hcUpdateCounters();
}

async function runAllChecks() {
    if (hcRunning) return;
    hcRunning = true;
    document.getElementById('btnRun').disabled = true;
    for (const id of HC_CHECKS) {
        await hcRunOne(id);
    }
    hcRunning = false;
    document.getElementById('btnRun').disabled = false;
}

function resetChecks() {
    HC_CHECKS.forEach(id => hcSetRow(id, null, '–', null));
    HC_CHECKS.forEach(id => {
        const icon = document.getElementById('hc-icon-' + id);
        if (icon) icon.style.background = '#ddd';
    });
    hcUpdateCounters();
}
</script>

<?php include 'layout/footer_next_page.php'; ?>
