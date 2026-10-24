<?php
/**
 * components/js_texte.php — Texte fuer JavaScript in der Sprache des Benutzers
 *
 * Seit 4.3.34. Vorher standen rund 45 Meldungen fest deutsch im JavaScript
 * (Bildverwaltung, Entwurf-Hinweis, Barcode-Suche, Hilfe-Fenster). Eingebunden
 * von header_next.php, header_next_page.php und backend/layout/header_next_page.php,
 * damit window.VS_T auf jeder Seite vor den Skripten steht.
 *
 * Aufruf im JavaScript: vsT('js_img_max', 'Maximum von %s Bildern erreicht', 10)
 * Der zweite Wert ist der Rueckfall, falls die Seite die Texte nicht geladen hat;
 * jedes %s wird der Reihe nach durch die weiteren Werte ersetzt.
 */
if (!empty($GLOBALS['_vsJsTexteAusgegeben'])) {
    return;
}
$GLOBALS['_vsJsTexteAusgegeben'] = true;

$_vsJsSchluessel = [
    'js_draft_found', 'js_draft_unsaved', 'js_draft_question', 'js_restore', 'js_discard',
    'js_lookup_searching', 'js_lookup_none', 'js_lookup_error', 'js_lookup_take_image',
    'js_note_author', 'js_note_brand', 'js_note_category', 'js_note_year',
    'js_position_choose', 'js_compress_none', 'js_compress_failed', 'js_detected_type',
    'js_img_count', 'js_img_none', 'js_img_upload', 'js_img_hint', 'js_img_limits',
    'js_img_select', 'js_img_camera', 'js_img_gallery', 'js_img_uploading',
    'js_img_uploading_n', 'js_img_processing', 'js_img_done', 'js_img_primary',
    'js_img_alt', 'js_img_set_primary', 'js_img_delete', 'js_img_max',
    'js_img_upload_failed', 'js_img_delete_failed', 'js_img_primary_failed',
    'js_img_compressing', 'js_error_prefix', 'js_close', 'js_prev', 'js_next',
    'js_pdf_open', 'js_opens_new_tab', 'js_doc_open', 'js_pdf_document',
    // Zuschnitt (js/bild_zuschnitt.js, 4.4.1)
    'js_crop_button', 'js_crop_title', 'js_crop_help', 'js_crop_image', 'js_crop_rotate_left', 'js_crop_rotate_right', 'js_crop_ratio',
    'js_crop_ratio_free', 'js_crop_reset', 'js_crop_cancel', 'js_crop_apply', 'js_crop_rotated', 'js_crop_saving', 'js_crop_failed',
];
$_vsJsTexte = [];
if (function_exists('t')) {
    foreach ($_vsJsSchluessel as $_k) {
        $_v = t($_k);
        if (is_string($_v) && $_v !== '' && $_v !== $_k) {
            $_vsJsTexte[$_k] = $_v;
        }
    }
}
// Feste Obergrenze fuer Bilder in den Hinweis einsetzen (bis 4.3.33 stand dort
// "Max. 5MB", unabhaengig von der Einstellung).
if (isset($_vsJsTexte['js_img_limits']) && defined('MAX_FILE_SIZE')) {
    $_vsJsTexte['js_img_limits'] = sprintf($_vsJsTexte['js_img_limits'], (string)round(MAX_FILE_SIZE / 1048576));
}
?>
<script>
window.VS_T = <?php echo json_encode($_vsJsTexte, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
window.vsT = function (key, fallback) {
    var s = (window.VS_T && window.VS_T[key]) || fallback || key;
    var args = Array.prototype.slice.call(arguments, 2);
    var i = 0;
    return s.replace(/%s/g, function () { return i < args.length ? String(args[i++]) : '%s'; });
};
window.vsEsc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
        return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
    });
};
</script>
<?php unset($_vsJsSchluessel, $_vsJsTexte, $_k, $_v); ?>
