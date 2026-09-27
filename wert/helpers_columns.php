<?php
/**
 * INSTALLATION: → /wert/helpers_columns.php (ÜBERSCHREIBEN!)
 * 
 * Helper-Funktionen für flexibles Spalten-System
 * VERSION: OHNE users/documents Tabellen
 */

function getAvailableColumns() {
    global $AVAILABLE_COLUMNS;
    return $AVAILABLE_COLUMNS ?? [];
}

function initColumnSession() {
    if (!isset($_SESSION['spalten_auswahl'])) {
        $_SESSION['spalten_auswahl'] = [];
        
        foreach (getAvailableColumns() as $key => $config) {
            $_SESSION['spalten_auswahl'][$key] = $config['default'] ? '1' : '';
        }
    }
}

function isColumnActive($column_key) {
    $columns = getAvailableColumns();
    
    if (!isset($columns[$column_key])) {
        return false;
    }
    
    if (isset($_SESSION['spalten_auswahl'][$column_key])) {
        return !empty($_SESSION['spalten_auswahl'][$column_key]);
    }
    
    return $columns[$column_key]['default'] ?? false;
}

function getActiveColumns() {
    $active = [];
    
    foreach (getAvailableColumns() as $key => $config) {
        if (isColumnActive($key)) {
            $active[$key] = $config;
        }
    }
    
    return $active;
}

function formatColumnValue($column_key, $value, $item = null) {
    $columns = getAvailableColumns();
    
    if (!isset($columns[$column_key])) {
        return htmlspecialchars($value ?? '');
    }
    
    $config = $columns[$column_key];
    
    if (empty($value) && $value !== '0') {
        return '-';
    }
    
    if (isset($config['truncate']) && strlen($value) > $config['truncate']) {
        $value = substr($value, 0, $config['truncate']) . '...';
    }
    
    switch ($column_key) {
        case 'preis':
            return number_format((float)$value, 2, ',', '.') . ' €';
            
        case 'kaufdatum':
            if ($value && $value != '0000-00-00') {
                return date('d.m.Y', strtotime($value));
            }
            return '-';
            
        case 'notizen':
            $value = str_replace(["\r\n", "\n", "\r"], ' ', $value);
            if (isset($config['truncate']) && strlen($value) > $config['truncate']) {
                $value = substr($value, 0, $config['truncate']) . '...';
            }
            return htmlspecialchars($value);
            
        default:
            return htmlspecialchars($value);
    }
}

function saveColumnSelection() {
    if (!isset($_SESSION['spalten_auswahl'])) {
        $_SESSION['spalten_auswahl'] = [];
    }
    
    foreach (getAvailableColumns() as $key => $config) {
        $post_key = 'spalte_' . $key;
        $_SESSION['spalten_auswahl'][$key] = isset($_POST[$post_key]) ? '1' : '';
    }
    
    return true;
}

/**
 * Generiere SELECT-Felder für SQL (OHNE users Tabelle!)
 */
function getColumnsSQLFields() {
    $fields = ['w.id', 'w.name'];
    
    foreach (getActiveColumns() as $key => $config) {
        if (!empty($config['db_field'])) {
            $fields[] = 'w.' . $config['db_field'];
        }
    }
    
    return implode(', ', array_unique($fields));
}
