<?php
/**
 * Pagination Helpers
 */

function renderPagination($current_page, $total_pages, $base_url = '', $params = []) {
    if ($total_pages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    if ($current_page > 1) {
        $prev_url = buildPaginationUrl($base_url, $current_page - 1, $params);
        $lblPrev = function_exists('t') ? t('pagination_prev') : '« Previous';
        $html .= '<a href="' . htmlspecialchars($prev_url) . '" class="pagination-link">' . htmlspecialchars($lblPrev) . '</a>';
    }
    
    $range = 2;
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)) {
            $page_url = buildPaginationUrl($base_url, $i, $params);
            $active = ($i == $current_page) ? ' pagination-active' : '';
            $html .= '<a href="' . htmlspecialchars($page_url) . '" class="pagination-link' . $active . '">' . $i . '</a>';
        } elseif ($i == $current_page - $range - 1 || $i == $current_page + $range + 1) {
            $html .= '<span class="pagination-dots">...</span>';
        }
    }
    
    if ($current_page < $total_pages) {
        $next_url = buildPaginationUrl($base_url, $current_page + 1, $params);
        $lblNext = function_exists('t') ? t('pagination_next') : 'Next »';
        $html .= '<a href="' . htmlspecialchars($next_url) . '" class="pagination-link">' . htmlspecialchars($lblNext) . '</a>';
    }
    
    $html .= '</div>';
    return $html;
}

function buildPaginationUrl($base_url, $page, $params = []) {
    $params['page'] = $page;
    if (empty($base_url)) $base_url = $_SERVER['PHP_SELF'];
    $query_string = http_build_query($params);
    return $base_url . ($query_string ? '?' . $query_string : '');
}

function calculatePagination($total_items, $items_per_page = 20, $current_page = 1) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'limit' => $items_per_page,
        'total_items' => $total_items,
        'from_item' => $offset + 1,
        'to_item' => min($offset + $items_per_page, $total_items)
    ];
}

function getPaginationInfo($pagination) {
    if ($pagination['total_items'] == 0) return 'Keine Einträge';
    return sprintf('Einträge %d-%d von %d', $pagination['from_item'], $pagination['to_item'], $pagination['total_items']);
}
?>
