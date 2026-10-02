<?php
/**
 * Backend Layout - Sidebar Navigation
 * WordPress-Style Menü
 */

$currentPage = basename($_SERVER['PHP_SELF']);

// Menü-Struktur
$menu = [
    'dashboard' => [
        'title' => 'Dashboard',
        'icon' => '📊',
        'url' => '/backend/index.php',
        'active' => ($currentPage === 'index.php')
    ],
    'content' => [
        'title' => 'Inhalte',
        'icon' => '📦',
        'submenu' => [
            [
                'title' => 'Alle Gegenstände',
                'url' => '/index.php',
                'active' => false
            ],
            [
                'title' => 'Bild-Galerie',
                'url' => '/backend/gallery.php',
                'active' => ($currentPage === 'gallery.php')
            ],
            [
                'title' => 'Kategorien',
                'url' => '/backend/categories.php',
                'active' => ($currentPage === 'categories.php')
            ],
            [
                'title' => 'Orte',
                'url' => '/backend/locations.php',
                'active' => ($currentPage === 'locations.php')
            ]
        ]
    ],
    'users' => [
        'title' => 'Benutzer',
        'icon' => '👥',
        'submenu' => array_merge(
            isSuperAdmin() ? [[
                'title' => 'Alle Benutzer',
                'url' => '/backend/users.php',
                'active' => ($currentPage === 'users.php')
            ]] : [],
            [[
                'title' => 'Berechtigungen',
                'url' => '/backend/permissions.php',
                'active' => ($currentPage === 'permissions.php')
            ]]
        )
    ],
    'stats' => [
        'title' => 'Statistiken',
        'icon' => '📈',
        'url' => '/backend/stats.php',
        'active' => ($currentPage === 'stats.php')
    ],
    'insurance' => [
        'title' => 'Versicherungen',
        'icon' => '🛡️',
        'url' => '/backend/insurance.php',
        'active' => ($currentPage === 'insurance.php')
    ],
    'public_link' => [
        'title' => 'Öffentlicher Link',
        'icon' => '🔗',
        'url' => '/backend/public_settings.php',
        'active' => ($currentPage === 'public_settings.php')
    ],
    'system' => [
        'title' => 'System',
        'icon' => '⚙️',
        'submenu' => [
            [
                'title' => 'System-Info',
                'url' => '/backend/system.php',
                'active' => ($currentPage === 'system.php')
            ],
            [
                'title' => 'System-Logs',
                'url' => '/backend/logs.php',
                'active' => ($currentPage === 'logs.php')
            ],
            [
                'title' => 'Login-Security',
                'url' => '/backend/login_security.php',
                'active' => ($currentPage === 'login_security.php')
            ],
            [
                'title' => '🔐 2FA-Einrichtung',
                'url' => '/backend/2fa_setup.php',
                'active' => ($currentPage === '2fa_setup.php')
            ],
            [
                'title' => '🩺 Health Check',
                'url' => '/backend/health_check.php',
                'active' => ($currentPage === 'health_check.php')
            ],
            [
                'title' => 'Diagnose-Tools',
                'url' => '/Tools/diagnose_tools.php',
                'active' => ($currentPage === 'diagnose_tools.php')
            ],
            [
                'title' => 'Backup',
                'url' => '/backend/backup.php',
                'active' => ($currentPage === 'backup.php')
            ],
            [
                'title' => 'Restore',
                'url' => '/backend/restore.php',
                'active' => ($currentPage === 'restore.php')
            ],
            ...(isSuperAdmin() ? [[
                'title' => '🔑 SuperAdmin',
                'url' => '/backend/superadmin.php',
                'active' => ($currentPage === 'superadmin.php')
            ]] : [])
        ]
    ]
];

// Tools/ liefert paket_bauen.sh bewusst nicht aus - ohne den Ordner waere
// "Diagnose-Tools" ein toter Link. Nur zeigen, was es auf dieser Instanz gibt.
if (!is_dir(dirname(__DIR__, 2) . '/Tools')) {
    foreach ($menu as $_k => $_item) {
        if (!empty($_item['submenu'])) {
            $menu[$_k]['submenu'] = array_values(array_filter(
                $_item['submenu'],
                fn($_s) => strpos($_s['url'] ?? '', '/Tools/') !== 0
            ));
        }
    }
    unset($_k, $_item);
}
?>

<!-- Sidebar -->
<aside class="backend-sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <?php foreach ($menu as $key => $item): ?>
            <?php if (isset($item['submenu'])): ?>
                <!-- Menu mit Submenu -->
                <div class="menu-item has-submenu <?php echo $item['expanded'] ?? false ? 'expanded' : ''; ?>">
                    <a href="#" class="menu-link" onclick="toggleSubmenu(event, '<?php echo $key; ?>')">
                        <span class="menu-icon"><?php echo $item['icon']; ?></span>
                        <span class="menu-text"><?php echo $item['title']; ?></span>
                        <span class="submenu-arrow">▼</span>
                    </a>
                    <div class="submenu" id="submenu-<?php echo $key; ?>">
                        <?php foreach ($item['submenu'] as $subitem): ?>
                            <a href="<?php echo $subitem['url']; ?>" 
                               class="submenu-link <?php echo $subitem['active'] ? 'active' : ''; ?>">
                                <span class="submenu-bullet">•</span>
                                <?php echo $subitem['title']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Einfacher Menu-Punkt -->
                <a href="<?php echo $item['url']; ?>" 
                   class="menu-link <?php echo $item['active'] ? 'active' : ''; ?>">
                    <span class="menu-icon"><?php echo $item['icon']; ?></span>
                    <span class="menu-text"><?php echo $item['title']; ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>

<script>
// Sidebar Toggle für Mobile
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('sidebar-open');
    document.querySelector('.backend-main').classList.toggle('sidebar-open');
});

// Submenu Toggle
function toggleSubmenu(event, menuKey) {
    event.preventDefault();
    const submenu = document.getElementById('submenu-' + menuKey);
    const menuItem = submenu.parentElement;
    
    // Toggle
    if (submenu.style.display === 'block') {
        submenu.style.display = 'none';
        menuItem.classList.remove('expanded');
    } else {
        // Alle anderen schließen
        document.querySelectorAll('.submenu').forEach(sm => {
            sm.style.display = 'none';
            sm.parentElement.classList.remove('expanded');
        });
        
        // Dieses öffnen
        submenu.style.display = 'block';
        menuItem.classList.add('expanded');
    }
}

// Auto-expand aktives Submenu
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.submenu-link.active').forEach(link => {
        const submenu = link.closest('.submenu');
        if (submenu) {
            submenu.style.display = 'block';
            submenu.parentElement.classList.add('expanded');
        }
    });
});

// Sidebar bei Click außerhalb schließen (Mobile)
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    
    if (window.innerWidth < 768 && 
        sidebar.classList.contains('sidebar-open') &&
        !sidebar.contains(event.target) &&
        !toggle.contains(event.target)) {
        sidebar.classList.remove('sidebar-open');
        document.querySelector('.backend-main').classList.remove('sidebar-open');
    }
});
</script>
