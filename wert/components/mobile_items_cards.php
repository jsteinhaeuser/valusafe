<?php
/**
 * Mobile Items Cards Component - SAFE VERSION
 * Alternative zur Tabelle auf kleinen Bildschirmen
 */

// SICHERHEITS-CHECKS
if (!isset($wertsachen)) {
    return; // Keine Daten = nichts anzeigen
}

if (!is_array($wertsachen)) {
    return; // Falsche Daten = nichts anzeigen
}

// Funktions-Checks (mit Fallbacks)
$has_showSpalte = function_exists('showSpalte');
$has_canEdit = function_exists('canEdit');
$has_formatPrice = function_exists('formatPriceLocalized');
$has_formatDate = function_exists('formatDateLocalized');
?>

<style>
/* Mobile Cards - nur auf kleinen Bildschirmen */
.mobile-items-container {
    display: none;
}

@media (max-width: 768px) {
    /* Tabelle verstecken */
    .table-responsive,
    table.data-table {
        display: none !important;
    }
    
    /* Desktop-Pagination oben verstecken */
    .pagination-top-desktop {
        display: none !important;
    }
    
    /* Cards zeigen */
    .mobile-items-container {
        display: block;
        padding: 0;
    }
}

.mobile-item-card {
    border-radius: 12px;
    margin-bottom: 12px;
    position: relative;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    -webkit-touch-callout: none;
}

/* Bulk-Selection Checkbox */
.mobile-card-checkbox {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 10;
}

.mobile-card-checkbox input[type="checkbox"] {
    width: 24px;
    height: 24px;
    cursor: pointer;
}

.mobile-card-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
}

.mobile-card-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    background: #f0f0f0;
}

.mobile-card-main {
    flex: 1;
}

.mobile-card-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin: 0 0 4px 0;
}

.mobile-card-subtitle {
    font-size: 14px;
    color: #666;
    margin: 0;
}

.mobile-card-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    margin-bottom: 12px;
}

.mobile-card-detail {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.mobile-card-detail-label {
    font-size: 11px;
    text-transform: uppercase;
    color: #999;
    font-weight: 600;
}

.mobile-card-detail-value {
    font-size: 14px;
    color: #333;
    font-weight: 500;
}

.mobile-card-detail-value.price {
    color: #667eea;
    font-weight: 600;
}

.mobile-card-action.share {
    color: #1565c0;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    padding: 6px 12px;
    border-radius: 6px;
    transition: background .15s;
    text-decoration: none;
    font-family: inherit;
}
.mobile-card-action.share:hover,
.mobile-card-action.share:active {
    background: rgba(21,101,192,.1);
}

.mobile-card-actions {
    display: flex;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}

.mobile-card-action {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.mobile-card-action .icon {
    font-size: 18px;
    line-height: 1;
}

.mobile-card-action .label {
    font-size: 13px;
}

.mobile-card-action.edit {
    background: #667eea;
    color: white;
}

.mobile-card-action.delete {
    background: #f8f9fa;
    color: #e74c3c;
    border: 1px solid #e0e0e0;
}

.mobile-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

/* Swipe-Gesten */
.mobile-item-card {
    position: relative;
    overflow: hidden;
    padding: 0;
    background: transparent;
    box-shadow: none;
}

.mobile-card-swipe-bg {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    display: flex;
    border-radius: 12px;
    overflow: hidden;
    z-index: 1;
}

.mobile-card-swipe-edit {
    background: #667eea;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 28px;
    color: white;
    font-size: 15px;
    font-weight: 600;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.15s;
}

.mobile-card-swipe-delete {
    background: #e74c3c;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding-left: 28px;
    color: white;
    font-size: 15px;
    font-weight: 600;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.15s;
}

.mobile-item-card.swipe-left .mobile-card-swipe-edit { opacity: 1; }
.mobile-item-card.swipe-right .mobile-card-swipe-delete { opacity: 1; }

.mobile-card-inner {
    position: relative;
    z-index: 2;
    background: white;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.15s ease;
    will-change: transform;
}
</style>

<!-- Mobile Cards Container -->
<div class="mobile-items-container">
    <?php if (empty($wertsachen)): ?>
        <div class="mobile-empty-state">
            <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.3;">📦</div>
            <div style="font-size: 16px;">Keine Einträge gefunden</div>
        </div>
    <?php else: ?>
        <?php foreach ($wertsachen as $item): ?>
            <?php if (!is_array($item)) continue; ?>
            
            <div class="mobile-item-card" data-item-id="<?php echo intval($item['id'] ?? 0); ?>"
                 data-edit-url="edit.php?id=<?php echo intval($item['id'] ?? 0); ?><?php echo htmlspecialchars($editReturnParam ?? ''); ?>"
                 data-delete-url="delete.php?id=<?php echo intval($item['id'] ?? 0); ?>">
                
                <!-- Swipe-Hintergrund -->
                <div class="mobile-card-swipe-bg">
                    <div class="mobile-card-swipe-edit">✏️ <?php echo t('btn_edit'); ?></div>
                    <div class="mobile-card-swipe-delete">🗑️ <?php echo t('btn_delete'); ?></div>
                </div>
                
                <!-- Karten-Inhalt -->
                <div class="mobile-card-inner">
                <!-- Bulk-Selection Checkbox -->
                <div class="mobile-card-checkbox">
                    <label for="mobile-checkbox-<?php echo intval($item['id'] ?? 0); ?>" class="sr-only"><?php echo htmlspecialchars($item['name'] ?? ''); ?> auswählen</label>
                    <input type="checkbox" 
                           class="bulk-checkbox" 
                           name="selected_items[]" 
                           value="<?php echo intval($item['id'] ?? 0); ?>"
                           id="mobile-checkbox-<?php echo intval($item['id'] ?? 0); ?>">
                </div>
                
                <div class="mobile-card-header">
                    <?php 
                    // Bild anzeigen (mit Fehlerbehandlung)
                    $has_image = false;
                    if (isset($item['bild']) && !empty($item['bild'])) {
                        $image_path = (defined('UPLOAD_DIR') ? UPLOAD_DIR : 'upload/') . $item['bild'];
                        if (file_exists($image_path)) {
                            $has_image = true;
                            $image_url = (defined('UPLOAD_URL') ? UPLOAD_URL : 'upload/') . $item['bild'];
                            ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                 alt=""
                                 aria-hidden="true"
                                 class="mobile-card-image"
                                 loading="lazy">
                            <?php
                        }
                    }
                    
                    if (!$has_image): ?>
                        <div class="mobile-card-image" style="display: flex; align-items: center; justify-content: center; font-size: 24px; color: #ccc;">
                            📦
                        </div>
                    <?php endif; ?>
                    
                    <div class="mobile-card-main">
                        <h3 class="mobile-card-title">
                            <?php echo htmlspecialchars($item['name'] ?? 'Unbenannt'); ?>
                        </h3>
                        <p class="mobile-card-subtitle">
                            <?php echo htmlspecialchars($item['kategorie_name'] ?? '-'); ?>
                        </p>
                    </div>
                </div>
                
                <div class="mobile-card-details">
                    <!-- Preis -->
                    <div class="mobile-card-detail">
                        <div class="mobile-card-detail-label">Preis</div>
                        <div class="mobile-card-detail-value price">
                            <?php 
                            if (isset($item['preis'])) {
                                if ($has_formatPrice) {
                                    echo formatPriceLocalized($item['preis']);
                                } else {
                                    echo number_format($item['preis'], 2, ',', '.') . ' €';
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Ort -->
                    <div class="mobile-card-detail">
                        <div class="mobile-card-detail-label">Ort</div>
                        <div class="mobile-card-detail-value">
                            <?php echo htmlspecialchars($item['ort_name'] ?? '-'); ?>
                        </div>
                    </div>
                </div>
                
                <div class="mobile-card-actions">
                    <a href="edit.php?id=<?php echo intval($item['id'] ?? 0); ?><?php echo htmlspecialchars($editReturnParam ?? ''); ?>" 
                       class="mobile-card-action edit">
                        <span class="icon">✏️</span>
                        <span class="label"><?php echo t('btn_edit'); ?></span>
                    </a>
                    <?php if (function_exists('canShare') || true): ?>
                    <button type="button"
                            class="mobile-card-action share"
                            onclick="shareItem(<?php echo intval($item['id'] ?? 0); ?>, '<?php echo addslashes(htmlspecialchars($item['name'] ?? '')); ?>', '<?php echo addslashes(htmlspecialchars($item['notizen'] ?? '')); ?>')"
                            style="background:none;border:none;cursor:pointer;padding:0;">
                        <span class="icon">🔗</span>
                        <span class="label"><?php echo t('btn_share'); ?></span>
                    </button>
                    <?php endif; ?>
                    <a href="delete.php?id=<?php echo intval($item['id'] ?? 0); ?>" 
                       class="mobile-card-action delete"
                       onclick="return vsConfirmLink(event, '<?php echo t('bulk_confirm_delete'); ?>')">
                        <span class="icon">🗑️</span>
                        <span class="label"><?php echo t('btn_delete'); ?></span>
                    </a>
                </div><!-- /.mobile-card-actions -->
                </div><!-- /.mobile-card-inner -->
            </div><!-- /.mobile-item-card -->
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.mobile-item-card');

    // Zuletzt gesehene Items für Offline-Seite in localStorage speichern
    (function saveRecentItems() {
        try {
            const items = [];
            cards.forEach(card => {
                const name = card.querySelector('.mobile-card-title')?.textContent?.trim();
                const cat  = card.querySelector('.mobile-card-category')?.textContent?.trim();
                if (name) items.push({ name, kategorie: cat || '' });
            });
            if (items.length) localStorage.setItem('wert_recent_items', JSON.stringify(items));
        } catch(e) {}
    })();
    const SWIPE_THRESHOLD = 60;   // px bis Aktion ausgelöst wird
    const SWIPE_MAX = 100;        // max. Verschiebung in px

    cards.forEach(card => {
        const inner = card.querySelector('.mobile-card-inner');
        const checkbox = card.querySelector('.bulk-checkbox');
        if (!inner) return;

        let startX = 0, startY = 0, currentX = 0;
        let isSwiping = false, isLongPress = false;
        let longPressTimer = null;
        let activeCard = null;

        // ---- Long-Press ----
        inner.addEventListener('touchstart', function(e) {
            if (e.target.closest('.mobile-card-checkbox') ||
                e.target.closest('a') ||
                e.target.closest('button')) return;

            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            currentX = 0;
            isSwiping = false;
            isLongPress = false;

            longPressTimer = setTimeout(() => {
                if (!isSwiping && checkbox) {
                    isLongPress = true;
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    if (navigator.vibrate) navigator.vibrate(50);
                    inner.style.transform = 'scale(0.98)';
                    setTimeout(() => { inner.style.transform = ''; }, 120);
                }
            }, 500);
        }, { passive: true });

        // ---- Swipe Move ----
        inner.addEventListener('touchmove', function(e) {
            if (isLongPress) return;
            clearTimeout(longPressTimer);

            const dx = e.touches[0].clientX - startX;
            const dy = e.touches[0].clientY - startY;

            // Nur horizontales Swipe verarbeiten
            if (!isSwiping && Math.abs(dy) > Math.abs(dx)) return;
            isSwiping = true;

            currentX = Math.max(-SWIPE_MAX, Math.min(SWIPE_MAX, dx));
            inner.style.transition = 'none';
            inner.style.transform = `translateX(${currentX}px)`;

            // Klassen für Hintergrundfarbe
            card.classList.toggle('swipe-left', currentX < -20);
            card.classList.toggle('swipe-right', currentX > 20);
            
            // Hintergrundfarbe direkt setzen
            if (currentX < -20) {
                inner.style.background = 'linear-gradient(to left, #667eea22, white 60%)';
            } else if (currentX > 20) {
                inner.style.background = 'linear-gradient(to right, #e74c3c22, white 60%)';
            } else {
                inner.style.background = 'white';
            }

            // Anderen offenen Card schließen
            if (activeCard && activeCard !== card) {
                resetCard(activeCard);
            }
            activeCard = card;
        }, { passive: true });

        // ---- Swipe End ----
        inner.addEventListener('touchend', function() {
            clearTimeout(longPressTimer);
            if (!isSwiping) return;

            inner.style.transition = 'transform 0.2s ease';

            if (currentX < -SWIPE_THRESHOLD) {
                // Links geswiped → Bearbeiten
                inner.style.transform = `translateX(-${SWIPE_MAX}px)`;
                setTimeout(() => {
                    window.location.href = card.dataset.editUrl;
                }, 200);
            } else if (currentX > SWIPE_THRESHOLD) {
                // Rechts geswiped → Löschen
                inner.style.transform = `translateX(${SWIPE_MAX}px)`;
                setTimeout(() => {
                    vsConfirm(document.querySelector('[data-confirm-delete]')?.dataset.confirmDelete || '<?php echo t('bulk_confirm_delete'); ?>').then(function (ja) {
                        if (ja) {
                            window.location.href = card.dataset.deleteUrl;
                        } else {
                            resetCard(card);
                        }
                    });
                }, 200);
            } else {
                resetCard(card);
            }

            isSwiping = false;
        }, { passive: true });

        inner.addEventListener('touchcancel', function() {
            clearTimeout(longPressTimer);
            resetCard(card);
        }, { passive: true });
    });

    // Tap außerhalb schließt offene Karte
    document.addEventListener('touchstart', function(e) {
        cards.forEach(card => {
            if (!card.contains(e.target)) resetCard(card);
        });
    }, { passive: true });

    function resetCard(card) {
        const inner = card.querySelector('.mobile-card-inner');
        if (inner) {
            inner.style.transition = 'transform 0.2s ease';
            inner.style.transform = '';
            inner.style.background = 'white';
            inner.style.boxShadow = '0 2px 8px rgba(0,0,0,0.08)';
        }
        card.classList.remove('swipe-left', 'swipe-right');
    }
});
</script>
