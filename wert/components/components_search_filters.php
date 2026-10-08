<?php
/**
 * Erweiterte Such-Filter Component
 * Wiederverwendbare Filterleiste für Wertsachen-Suche
 */

// Filter aus Request holen
$currentFilters = getFiltersFromRequest();

// Kategorien und Orte laden
$kategorien = $db->select("SELECT id, name FROM kategorien ORDER BY name");
$raeume = $db->select("SELECT id, name FROM raeume ORDER BY name");

// Statistik berechnen
$stats = getSearchStats($currentFilters);
$hasFilters = hasActiveFilters($currentFilters);
?>

<div class="search-filters-card">
    <!-- Header mit Toggle -->
    <div class="search-filters-header">
        <h3 class="search-filters-title">
            🔍 <?php echo t('search_advanced'); ?>
            <?php if ($hasFilters): ?>
                <span class="filter-badge"><?php echo count(array_filter($currentFilters)); ?> aktiv</span>
            <?php endif; ?>
        </h3>
        <button type="button" class="toggle-filters-btn collapsed" id="toggleFilters" aria-label="<?php echo t('filter_toggle'); ?>">
            <span class="toggle-icon">▼</span>
        </button>
    </div>
    
    <!-- Filter-Formular (Default eingeklappt) -->
    <form method="GET" action="" id="searchForm" class="search-filters-body collapsed">
        
        <!-- Reihe 1: Basis-Filter -->
        <div class="filter-row">
            <!-- Suchfeld -->
            <div class="filter-group">
                <label for="search" class="filter-label">🔍 <?php echo t('filter_label_search'); ?></label>
                <input 
                    type="text" 
                    id="search" 
                    name="search" 
                    class="filter-input"
                    placeholder="<?php echo t('filter_placeholder_search'); ?>"
                    value="<?php echo htmlspecialchars($currentFilters['search']); ?>">
            </div>
            
            <!-- Kategorie -->
            <div class="filter-group">
                <label for="kategorie" class="filter-label">🏷️ <?php echo t('filter_label_category'); ?></label>
                <select 
                    id="kategorie" 
                    name="kategorie[]" 
                    class="filter-select"
                    multiple
                    size="4"
                    style="height:auto;">
                    <?php foreach ($kategorien as $kat): ?>
                        <option 
                            value="<?php echo $kat['id']; ?>"
                            <?php echo in_array($kat['id'], (array)($currentFilters['kategorie'] ?? [])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($kat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Ort -->
            <div class="filter-group">
                <label for="ort" class="filter-label">📍 <?php echo t('filter_label_location'); ?></label>
                <select 
                    id="ort" 
                    name="ort" 
                    class="filter-select">
                    <option value=""><?php echo t('placeholder_all_locations'); ?></option>
                    <?php foreach ($raeume as $ort): ?>
                        <option 
                            value="<?php echo $ort['id']; ?>"
                            <?php echo ($currentFilters['ort'] == $ort['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ort['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- Reihe 2: Range-Filter -->
        <div class="filter-row">
            <!-- Preis-Range -->
            <div class="filter-group-range">
                <label for="price_min" class="filter-label">💰 <?php echo t('filter_label_price'); ?></label>
                <div class="range-inputs">
                    <input 
                        type="number" 
                        id="price_min" 
                        name="price_min" 
                        class="filter-input filter-input-small"
                        placeholder="<?php echo t('filter_price_from'); ?> €"
                        min="0"
                        step="0.01"
                        value="<?php echo htmlspecialchars($currentFilters['price_min']); ?>">
                    <span class="range-separator" aria-hidden="true">-</span>
                    <input 
                        type="number" 
                        id="price_max" 
                        name="price_max" 
                        aria-label="<?php echo t('filter_label_price'); ?> <?php echo t('filter_price_to'); ?>"
                        class="filter-input filter-input-small"
                        placeholder="<?php echo t('filter_price_to'); ?> €"
                        min="0"
                        step="0.01"
                        value="<?php echo htmlspecialchars($currentFilters['price_max']); ?>">
                </div>
            </div>
            
            <!-- Datum-Range -->
            <div class="filter-group-range">
                <label for="date_from" class="filter-label">📅 <?php echo t('filter_label_date'); ?></label>
                <div class="range-inputs">
                    <input 
                        type="date" 
                        id="date_from" 
                        name="date_from" 
                        aria-label="<?php echo t('filter_label_date') . ' ' . t('filter_from'); ?>"
                        class="filter-input filter-input-small"
                        value="<?php echo htmlspecialchars($currentFilters['date_from']); ?>">
                    <span class="range-separator" aria-hidden="true">-</span>
                    <input 
                        type="date" 
                        id="date_to" 
                        name="date_to" 
                        aria-label="<?php echo t('filter_label_date') . ' ' . t('filter_to'); ?>"
                        class="filter-input filter-input-small"
                        value="<?php echo htmlspecialchars($currentFilters['date_to']); ?>">
                </div>
            </div>
        </div>
        
        <!-- Reihe 3: Checkbox-Filter -->
        <div class="filter-row">
            <div class="filter-checkboxes">
                <label class="filter-checkbox">
                    <input 
                        type="checkbox" 
                        id="has_image" 
                        name="has_image" 
                        value="1"
                        <?php echo !empty($currentFilters['has_image']) ? 'checked' : ''; ?>>
                    <span class="checkbox-label">📸 <?php echo t('filter_only_with_image'); ?></span>
                </label>
                
                <label class="filter-checkbox">
                    <input 
                        type="checkbox" 
                        id="has_docs" 
                        name="has_docs" 
                        value="1"
                        <?php echo !empty($currentFilters['has_docs']) ? 'checked' : ''; ?>>
                    <span class="checkbox-label">📄 <?php echo t('filter_only_with_docs'); ?></span>
                </label>
            </div>
        </div>
        
        <!-- Action-Buttons -->
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">
                🔍 <?php echo t('btn_search'); ?>
            </button>
            <button type="button" class="btn btn-secondary" id="resetFilters">
                🔄 <?php echo t('btn_reset'); ?>
            </button>

        </div>
    </form>
    
    <!-- Such-Statistik -->
    <div class="search-stats">
        <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
            <div class="stats-item">
                <span class="stats-icon">📊</span>
                <strong><?php echo number_format($stats['count'], 0, ',', '.'); ?></strong>
                <span class="stats-label">
                    <?php echo ($stats['count'] == 1) ? t('stats_entry_found') : t('stats_entries_found'); ?>
                </span>
            </div>

            <?php if ($stats['count'] > 0): ?>
                <span class="stats-divider">|</span>

                <div class="stats-item">
                    <span class="stats-label"><?php echo t('stats_total_label'); ?>:</span>
                    <strong><?php echo number_format($stats['total_value'], 2, ',', '.'); ?> €</strong>
                </div>

                <span class="stats-divider">|</span>

                <div class="stats-item">
                    <span class="stats-label"><?php echo t('dashboard_zeitwert'); ?>:</span>
                    <strong><?php echo number_format($stats['zeitwert'], 2, ',', '.'); ?> €</strong>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination rechtsbündig in gleicher Zeile -->
        <div class="stats-pagination" style="margin-left:auto; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <?php echo renderPagination($pagination['current_page'], $pagination['total_pages'], 'index.php', $paginationParams); ?>
            <?php endif; ?>
            <?php if (isset($items_per_page)): ?>
            <div style="display:flex; align-items:center; gap:5px;">
                <label for="per_page_stats" style="font-size:13px; white-space:nowrap; color:#595959;"><?php echo t('per_page'); ?>:</label>
                <select id="per_page_stats" onchange="changePerPage(this.value)"
                        style="padding:4px 8px; border-radius:4px; border:1px solid #ddd; font-size:13px;">
                    <option value="10"  <?php echo $items_per_page == 10  ? 'selected' : ''; ?>>10</option>
                    <option value="20"  <?php echo $items_per_page == 20  ? 'selected' : ''; ?>>20</option>
                    <option value="50"  <?php echo $items_per_page == 50  ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $items_per_page == 100 ? 'selected' : ''; ?>>100</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* ============================================
   ERWEITERTE SUCH-FILTER STYLES
   ============================================ */

.search-filters-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}

/* Header */
.search-filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    cursor: pointer;
}

.search-filters-header:hover {
    background: linear-gradient(135deg, #5568d3 0%, #6a4091 100%);
}

.search-filters-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-badge {
    background: rgba(255,255,255,0.3);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.toggle-filters-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    border-radius: 6px;
    padding: 8px 12px;
    color: white;
    cursor: pointer;
    transition: all 0.2s;
}

.toggle-filters-btn:hover {
    background: rgba(255,255,255,0.3);
}

.toggle-icon {
    display: inline-block;
    transition: transform 0.3s;
}

.toggle-filters-btn.collapsed .toggle-icon {
    transform: rotate(-90deg);
}

/* Body */
.search-filters-body {
    padding: 20px;
    display: block;
    transition: all 0.3s ease;
}

.search-filters-body.collapsed {
    display: none;
}

/* Filter Rows */
.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.filter-row:last-of-type {
    margin-bottom: 20px;
}

/* Filter Groups */
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-label {
    font-size: 13px;
    font-weight: 600;
    color: #222;
}

.filter-input,
.filter-select {
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Range Inputs */
.filter-group-range {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 13px; /* explizit gleich wie filter-group */
}

.range-inputs {
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-input-small {
    flex: 1;
    min-width: 0; /* verhindert Überlauf auf Mobile */
}

.range-separator {
    color: #595959; /* 7.00:1 ✅ war #999 = 2.85:1 */
    font-weight: 500;
}

/* Checkboxes */
.filter-checkboxes {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.filter-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}

.filter-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-label {
    font-size: 14px;
    color: #333;
}

/* Action Buttons */
.filter-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    padding-top: 16px;
    border-top: 1px solid #eee;
}

.btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-primary {
    background: #4f46e5; /* 6.29:1 ✅ war #667eea = 3.15:1 */
    color: white;
}

.btn-primary:hover {
    background: #3730a3;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.btn-secondary {
    background: #e9ecef;
    color: #495057;
}

.btn-secondary:hover {
    background: #dee2e6;
}

.btn-tertiary {
    background: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
}

.btn-tertiary:hover {
    background: #e9ecef;
}

/* Statistik */
.search-stats {
    padding: 10px 20px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    font-size: 14px;
}

.stats-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.stats-icon {
    font-size: 16px;
}

.stats-label {
    color: #595959; /* 7.00:1 ✅ war #6c757d = 4.48:1 */
}

.stats-divider {
    color: #767676; /* 4.54:1 ✅ */
}

.search-stats strong {
    color: #1a6fb5; /* 5.27:1 ✅ war #667eea = 3.15:1 */
    font-weight: 700;
}

/* Responsive */
@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
    }

    .filter-actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }

    .search-stats {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .stats-divider {
        display: none;
    }

    /* Labels vereinheitlichen */
    .filter-label {
        font-size: 12px !important;
    }

    .filter-group-range .filter-label {
        font-size: 12px !important;
        font-weight: 600 !important;
    }

    /* Eingabefelder kompakter */
    .filter-input,
    .filter-select {
        font-size: 13px !important;
        padding: 8px 10px !important;
    }

    .range-inputs {
        gap: 4px !important;
    }

    .filter-input-small {
        font-size: 13px !important;
        padding: 8px 6px !important;
        min-width: 0 !important;
    }

    input[type="date"].filter-input-small {
        font-size: 12px !important;
        padding: 8px 4px !important;
    }

    .range-separator {
        flex-shrink: 0;
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .search-filters-header {
        padding: 12px 16px;
    }

    .search-filters-title {
        font-size: 15px !important;
    }

    .search-filters-body {
        padding: 14px;
    }

    .filter-label {
        font-size: 12px !important;
    }

    .filter-group-range .filter-label {
        font-size: 12px !important;
    }

    .filter-input,
    .filter-select {
        font-size: 13px !important;
        padding: 8px 8px !important;
    }

    .filter-input-small {
        font-size: 12px !important;
        padding: 8px 4px !important;
    }

    input[type="date"].filter-input-small {
        font-size: 11px !important;
        padding: 8px 2px !important;
    }

    .filter-actions {
        gap: 8px;
        padding-top: 12px;
    }

    .btn {
        padding: 9px 16px !important;
        font-size: 13px !important;
    }
}
</style>

<script>
// Toggle Filter-Panel (Button UND Header klickbar)
function toggleFilters() {
    const body = document.querySelector('.search-filters-body');
    const button = document.getElementById('toggleFilters');
    
    body.classList.toggle('collapsed');
    button.classList.toggle('collapsed');
    
    // Zustand in LocalStorage speichern
    const isCollapsed = body.classList.contains('collapsed');
    localStorage.setItem('searchFiltersCollapsed', isCollapsed ? 'true' : 'false');
}

// Zustand beim Laden wiederherstellen
document.addEventListener('DOMContentLoaded', function() {
    const savedState = localStorage.getItem('searchFiltersCollapsed');
    
    // Wenn gespeicherter Zustand = "false" (aufgeklappt), dann aufklappen
    if (savedState === 'false') {
        const body = document.querySelector('.search-filters-body');
        const button = document.getElementById('toggleFilters');
        
        body.classList.remove('collapsed');
        button.classList.remove('collapsed');
    }
});

document.getElementById('toggleFilters').addEventListener('click', function(e) {
    e.stopPropagation();
    toggleFilters();
});

document.querySelector('.search-filters-header').addEventListener('click', function() {
    toggleFilters();
});

// Reset-Button
document.getElementById('resetFilters').addEventListener('click', function() {
    window.location.href = window.location.pathname;
});

// Spalten-Button (öffnet neuen Column Manager)
document.getElementById('columnSettings')?.addEventListener('click', function() {
    // Wartet bis Column Manager geladen ist
    if (window.columnManager) {
        window.columnManager.open();
    }
});

// Auto-Submit bei Select-Änderung (optional)
document.querySelectorAll('.filter-select').forEach(select => {
    select.addEventListener('change', function() {
        // Uncomment für Auto-Submit:
        // document.getElementById('searchForm').submit();
    });
});

function changePerPage(value) {
    var u = new URL(window.location.href);
    if (value == 20) {
        u.searchParams.delete('per_page'); // Default nicht in URL
    } else {
        u.searchParams.set('per_page', value);
    }
    u.searchParams.delete('page'); // Zurück auf Seite 1
    window.location.href = u.toString();
}
</script>
