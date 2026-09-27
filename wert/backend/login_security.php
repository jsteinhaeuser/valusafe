<?php
/**
 * Login-Security Verwaltung - ANGEPASST FÜR NEUES LAYOUT
 * Übersicht gesperrter Accounts und Login-Statistiken
 */

require_once __DIR__ . '/config.php';
requireBackendAccess();

require_once __DIR__ . '/../LoginAttempts.php';

$pageTitle = 'Login-Security';

$loginAttempts = new LoginAttempts($pdo);
$message = '';
$error = '';

// Account entsperren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_account'])) {
    $username = $_POST['username'] ?? '';
    if (!empty($username)) {
        try {
            $loginAttempts->clearAttempts($username, '');
            $message = "Account '$username' wurde erfolgreich entsperrt.";
        } catch (Exception $e) {
            $error = 'Fehler beim Entsperren: ' . $e->getMessage();
        }
    }
}

// Statistiken abrufen - VERWENDE NEUE FUNKTION!
try {
    $stats = $loginAttempts->getSecurityStats();
    $lockedAccounts = $stats['locked_list'] ?? [];
} catch (Exception $e) {
    $stats = [
        'locked_accounts' => 0,
        'locked_list' => [],
        'failed_24h' => 0,
        'success_24h' => 0,
        'top_ips' => [],
        'top_users' => []
    ];
    $lockedAccounts = [];
    $error = 'Fehler beim Laden der Statistiken: ' . $e->getMessage();
}

// Erfolgsrate berechnen
$total_attempts = ($stats['failed_24h'] ?? 0) + ($stats['success_24h'] ?? 0);
$success_rate = $total_attempts > 0 ? round((($stats['success_24h'] ?? 0) / $total_attempts) * 100) : 100;

// Layout laden
include 'layout/header_next_page.php';
?>

<!-- Main Content -->
<main class="backend-main">
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <strong><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Erfolg!</strong> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <strong><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> Fehler!</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistik-Karten -->
    <div class="dashboard-grid" style="margin-bottom: 30px;">
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value" style="color: var(--vs-danger);"><?php echo $stats['locked_accounts'] ?? 0; ?></div>
                    <div class="widget-label"><i class="ti ti-lock"></i> Gesperrte Accounts</div>
                </div>
                <div class="widget-icon"><i class="ti ti-lock"></i></div>
            </div>
        </div>
        
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value" style="color: var(--vs-danger);"><?php echo number_format($stats['failed_24h'] ?? 0, 0, ',', '.'); ?></div>
                    <div class="widget-label"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i> Fehlversuche (24h)</div>
                </div>
                <div class="widget-icon"><i class="ti ti-circle-x" style="color:var(--vs-danger);"></i></div>
            </div>
        </div>
        
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value" style="color: var(--vs-success);"><?php echo number_format($stats['success_24h'] ?? 0, 0, ',', '.'); ?></div>
                    <div class="widget-label"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i> Erfolgreiche Logins (24h)</div>
                </div>
                <div class="widget-icon"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i></div>
            </div>
        </div>
        
        <div class="widget-card">
            <div class="widget-header">
                <div>
                    <div class="widget-value" style="color: <?php echo $success_rate >= 90 ? 'var(--vs-success)' : ($success_rate >= 70 ? 'var(--vs-warning)' : 'var(--vs-danger)'); ?>;">
                        <?php echo $success_rate; ?>%
                    </div>
                    <div class="widget-label"><i class="ti ti-chart-bar"></i> Erfolgsrate (24h)</div>
                </div>
                <div class="widget-icon"><i class="ti ti-chart-bar"></i></div>
            </div>
        </div>
    </div>
    
    <!-- Gesperrte Accounts -->
    <div class="activity-timeline" style="margin-bottom: 30px;">
        <h2><i class="ti ti-lock"></i> Gesperrte Accounts</h2>
        
        <?php if (count($lockedAccounts) > 0): ?>
            <table class="backend-table">
                <thead>
                    <tr>
                        <th>Benutzername</th>
                        <th style="width: 150px;">IP-Adresse</th>
                        <th style="width: 120px;">Fehlversuche</th>
                        <th style="width: 180px;">Letzter Versuch</th>
                        <th style="width: 150px;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lockedAccounts as $account): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($account['username']); ?></strong>
                            </td>
                            <td>
                                <code style="background: var(--vs-surface-2); padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                                    <?php echo htmlspecialchars($account['ip_address']); ?>
                                </code>
                            </td>
                            <td>
                                <span class="badge badge-danger" style="font-size: 13px; padding: 6px 12px;">
                                    <?php echo $account['attempt_count']; ?>x
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 13px;">
                                    <?php echo date('d.m.Y H:i', strtotime($account['last_attempt'])); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($account['username']); ?>">
                                    <button type="submit" name="unlock_account" class="vs-btn vs-btn-sm vs-btn-primary">
                                        <i class="ti ti-lock-open"></i> Entsperren
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 15px;"><i class="ti ti-circle-check" style="color:var(--vs-success);"></i></div>
                <p>Keine gesperrten Accounts</p>
                <p style="font-size: 13px; color: #999; margin-top: 5px;">
                    Alle Accounts sind aktiv und nicht gesperrt.
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Top IPs mit Fehlversuchen -->
    <?php if (isset($stats['top_ips']) && count($stats['top_ips']) > 0): ?>
        <div class="activity-timeline" style="margin-bottom: 30px;">
            <h2><i class="ti ti-world"></i> Top IP-Adressen mit Fehlversuchen (24h)</h2>
            
            <table class="backend-table">
                <thead>
                    <tr>
                        <th>IP-Adresse</th>
                        <th style="width: 150px;">Fehlversuche</th>
                        <th style="width: 180px;">Letzter Versuch</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['top_ips'] as $ip): ?>
                        <tr>
                            <td>
                                <code style="background: var(--vs-surface-2); padding: 4px 8px; border-radius: 3px; font-family: 'Courier New', monospace;">
                                    <?php echo htmlspecialchars($ip['ip_address']); ?>
                                </code>
                            </td>
                            <td>
                                <span class="badge badge-danger" style="font-size: 13px; padding: 6px 12px;">
                                    <?php echo $ip['attempt_count']; ?>x
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 13px;">
                                    <?php echo date('d.m.Y H:i', strtotime($ip['last_attempt'])); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <!-- Sicherheits-Einstellungen -->
    <div class="activity-timeline">
        <h2>ℹ️ Sicherheits-Einstellungen</h2>
        
        <div style="padding: 20px 0;">
            <table class="settings-table">
                <tr>
                    <td style="width: 300px; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                        <strong>Maximale Fehlversuche</strong>
                    </td>
                    <td style="padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                        <span class="badge badge-info">5 Versuche</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                        <strong>Sperrdauer</strong>
                    </td>
                    <td style="padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                        <span class="badge badge-info">15 Minuten</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                        <strong>Session-Timeout</strong>
                    </td>
                    <td style="padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                        <span class="badge badge-info">60 Minuten Inaktivität</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                        <strong>Session-Warnung</strong>
                    </td>
                    <td style="padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                        <span class="badge badge-info">5 Minuten vor Ablauf</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 0;">
                        <strong>Automatische Bereinigung</strong>
                    </td>
                    <td style="padding: 12px 0;">
                        <span class="badge badge-info">Einträge älter als 24 Stunden</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
</main>

<style>
/* Alert Styles */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid;
}

.alert-success {
    background: var(--vs-success-light);
    border-color: var(--vs-success);
    color: var(--vs-success-text);
}

.alert-danger {
    background: var(--vs-danger-light);
    border-color: var(--vs-danger);
    color: var(--vs-danger-text);
}

/* Table Styles */
.backend-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.backend-table thead tr {
    background: var(--bg-color);
    border-bottom: 2px solid var(--border-color);
}

.backend-table th,
.backend-table td {
    padding: 12px;
    text-align: left;
}

.backend-table tbody tr {
    border-bottom: 1px solid var(--border-color);
    transition: background 0.2s;
}

.backend-table tbody tr:hover {
    background: var(--bg-color);
}

.settings-table {
    width: 100%;
}

/* Button Styles */
.btn-small {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: var(--transition);
    background: none;
}

.btn-success {
    background: var(--vs-success);
    color: var(--vs-surface);
}

.btn-success:hover {
    background: var(--vs-success);
}

/* Badge Styles */
.badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.badge-success {
    background: var(--vs-success-light);
    color: var(--vs-success-text);
}

.badge-danger {
    background: var(--vs-danger-light);
    color: var(--vs-danger-text);
}

.badge-info {
    background: var(--vs-accent-light);
    color: var(--vs-accent);
}
</style>

<?php include 'layout/footer_next_page.php'; ?>
