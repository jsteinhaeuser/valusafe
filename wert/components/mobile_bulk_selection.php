<!--
 Mobile Bulk-Selection Enhancement
 Größere Touch-Targets, besseres visuelles Feedback
 Tap & Hold für Multi-Select Mode
 Version: 1.0 - 2026-02-15
-->

<style>
/* Mobile Bulk-Selection Optimierungen */
@media (max-width: 768px) {
    
    /* WICHTIG: Verhindere Text-Selection auf Mobile Cards */
    .mobile-item-card,
    .mobile-item-card * {
        -webkit-user-select: none !important;
        -moz-user-select: none !important;
        -ms-user-select: none !important;
        user-select: none !important;
        -webkit-touch-callout: none !important; /* iOS Context-Menu verhindern */
    }
    
    /* Größere Checkboxen auf Mobile */
    .mobile-item-card input[type="checkbox"],
    .bulk-checkbox {
        width: 28px !important;
        height: 28px !important;
        min-width: 28px !important;
        min-height: 28px !important;
        cursor: pointer;
        margin: 0;
        flex-shrink: 0;
    }
    
    /* Größerer Touch-Bereich um Checkbox */
    .bulk-select-container {
        padding: 8px;
        margin: -8px;
        min-width: 44px;
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Visual Feedback bei Selection */
    .mobile-item-card.selected {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%) !important;
        border: 2px solid #2196f3 !important;
        transform: scale(0.98);
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3) !important;
    }
    
    /* Checkbox checked State */
    .mobile-item-card input[type="checkbox"]:checked {
        background: #2196f3;
        border-color: #2196f3;
    }
    
    /* Tap & Hold Feedback */
    .mobile-item-card.holding {
        background: #f5f5f5;
        transform: scale(0.95);
        transition: transform 0.2s, background 0.2s;
    }
    
    /* Multi-Select Mode aktiv */
    body.multi-select-mode .mobile-item-card {
        cursor: pointer;
    }
    
    body.multi-select-mode .bulk-select-container {
        opacity: 1 !important;
        pointer-events: all !important;
    }
    
    /* Selection Counter (oben fixiert) */
    .selection-counter {
        position: fixed;
        top: calc(env(safe-area-inset-top) + 10px);
        left: 50%;
        transform: translateX(-50%);
        background: #2196f3;
        color: white;
        padding: 8px 20px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
        z-index: 9998;
        display: none;
        animation: slideDown 0.3s ease-out;
    }
    
    body.multi-select-mode .selection-counter {
        display: block;
    }
    
    @keyframes slideDown {
        from {
            transform: translateX(-50%) translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
    }
    
    /* Bulk Actions Bar angepasst */
    .bulk-actions-bar {
        padding: 12px !important;
        gap: 8px !important;
    }
    
    .bulk-actions-bar button,
    .bulk-actions-bar .btn {
        min-height: 44px !important;
        padding: 10px 16px !important;
        font-size: 14px !important;
    }
    
    /* Exit Multi-Select Button */
    .exit-multiselect-btn {
        position: fixed;
        top: calc(env(safe-area-inset-top) + 50px);
        right: 16px;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #f44336;
        color: white;
        border: none;
        font-size: 20px;
        display: none;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
        z-index: 9998;
        cursor: pointer;
    }
    
    body.multi-select-mode .exit-multiselect-btn {
        display: flex;
        animation: slideDown 0.3s ease-out;
    }
    
    .exit-multiselect-btn:active {
        transform: scale(0.9);
    }
}
</style>

<!-- Selection Counter -->
<div class="selection-counter" id="selectionCounter">
    <span id="selectedCount">0</span> ausgewählt
</div>

<!-- Exit Multi-Select Button -->
<button type="button" class="exit-multiselect-btn" id="exitMultiSelectBtn" title="Auswahl beenden">
    ✕
</button>

<script>
/**
 * Mobile Bulk-Selection Enhancement
 */
(function() {
    // Nur auf Mobile
    if (window.innerWidth > 768) return;
    
    let longPressTimer = null;
    let isLongPress = false;
    const longPressDuration = 500; // 500ms
    
    // Alle Item-Cards
    const itemCards = document.querySelectorAll('.mobile-item-card');
    const selectionCounter = document.getElementById('selectionCounter');
    const selectedCount = document.getElementById('selectedCount');
    const exitBtn = document.getElementById('exitMultiSelectBtn');
    
    /**
     * Multi-Select Mode aktivieren
     */
    function enableMultiSelectMode() {
        document.body.classList.add('multi-select-mode');
        
        // Haptic Feedback
        if (navigator.vibrate) {
            navigator.vibrate([50, 30, 50]); // Doppel-Vibration
        }
        
        updateSelectionCounter();
    }
    
    /**
     * Multi-Select Mode deaktivieren
     */
    function disableMultiSelectMode() {
        document.body.classList.remove('multi-select-mode');
        
        // Alle Checkboxen deaktivieren
        document.querySelectorAll('.bulk-checkbox:checked').forEach(cb => {
            cb.checked = false;
        });
        
        // Alle selected Klassen entfernen
        itemCards.forEach(card => {
            card.classList.remove('selected');
        });
        
        updateSelectionCounter();
    }
    
    /**
     * Selection Counter aktualisieren
     */
    function updateSelectionCounter() {
        const checked = document.querySelectorAll('.bulk-checkbox:checked').length;
        selectedCount.textContent = checked;
        
        // WICHTIG: Auch den Bulk Actions Bar Counter aktualisieren!
        const bulkActionsCount = document.getElementById('selectedCount');
        if (bulkActionsCount) {
            bulkActionsCount.textContent = checked;
        }
        
        // Bulk Actions Bar zeigen/verstecken
        const bulkBar = document.querySelector('.bulk-actions-bar');
        if (bulkBar) {
            if (checked > 0) {
                bulkBar.classList.add('active');
                bulkBar.style.display = 'block';
            } else {
                bulkBar.classList.remove('active');
                bulkBar.style.display = 'none';
            }
        }
    }
    
    /**
     * Card Visual State aktualisieren
     */
    function updateCardVisualState(card) {
        const checkbox = card.querySelector('.bulk-checkbox');
        if (checkbox && checkbox.checked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    }
    
    /**
     * Context-Menu (Long Press Menu) verhindern
     */
    itemCards.forEach(card => {
        card.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
    });
    
    /**
     * Tap & Hold auf Cards
     */
    itemCards.forEach(card => {
        let startX, startY;
        
        // Touch Start
        card.addEventListener('touchstart', function(e) {
            // Ignore wenn direkt auf Checkbox geklickt
            if (e.target.closest('.mobile-card-checkbox')) {
                return;
            }
            
            isLongPress = false;
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            
            // Long Press Timer
            longPressTimer = setTimeout(() => {
                isLongPress = true;
                
                // Multi-Select Mode aktivieren
                if (!document.body.classList.contains('multi-select-mode')) {
                    enableMultiSelectMode();
                }
                
                // Diese Card auswählen
                const checkbox = card.querySelector('.bulk-checkbox');
                console.log('Tap & Hold triggered!');
                console.log('Checkbox gefunden:', checkbox);
                
                if (checkbox) {
                    console.log('Checkbox vor Toggle:', checkbox.checked);
                    checkbox.checked = !checkbox.checked;
                    console.log('Checkbox nach Toggle:', checkbox.checked);
                    
                    // WICHTIG: Change-Event triggern damit BulkActions es mitbekommt!
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    
                    updateCardVisualState(card);
                    updateSelectionCounter();
                } else {
                    console.error('FEHLER: Checkbox nicht gefunden in Card!');
                }
                
                // Visual Feedback
                card.classList.add('holding');
                setTimeout(() => card.classList.remove('holding'), 200);
                
            }, longPressDuration);
        }, { passive: false }); // passive: false damit wir später preventDefault nutzen können
        
        // Touch Move - Cancel long press wenn zu weit bewegt
        card.addEventListener('touchmove', function(e) {
            const moveX = Math.abs(e.touches[0].clientX - startX);
            const moveY = Math.abs(e.touches[0].clientY - startY);
            
            if (moveX > 10 || moveY > 10) {
                clearTimeout(longPressTimer);
            }
        }, { passive: true });
        
        // Touch End
        card.addEventListener('touchend', function(e) {
            clearTimeout(longPressTimer);
            
            // Wenn im Multi-Select Mode: Card togglen
            if (document.body.classList.contains('multi-select-mode') && !isLongPress) {
                const checkbox = card.querySelector('.bulk-checkbox');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    updateCardVisualState(card);
                    updateSelectionCounter();
                    
                    // Haptic Feedback
                    if (navigator.vibrate) {
                        navigator.vibrate(30);
                    }
                }
                
                e.preventDefault();
            }
        }, { passive: false });
        
        // Checkbox Change Event
        const checkbox = card.querySelector('.bulk-checkbox');
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                updateCardVisualState(card);
                updateSelectionCounter();
            });
        }
    });
    
    /**
     * Exit Button
     */
    if (exitBtn) {
        exitBtn.addEventListener('click', function() {
            disableMultiSelectMode();
            
            // Haptic Feedback
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }
        });
    }
    
    /**
     * "Alle auswählen" Button anpassen
     */
    const selectAllBtn = document.getElementById('selectAllItems');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            // Multi-Select Mode aktivieren
            if (!document.body.classList.contains('multi-select-mode')) {
                enableMultiSelectMode();
            }
            
            // Alle Cards visuell aktualisieren
            setTimeout(() => {
                itemCards.forEach(card => {
                    updateCardVisualState(card);
                });
                updateSelectionCounter();
            }, 50);
        });
    }
    
    /**
     * "Auswahl aufheben" Button anpassen
     */
    const deselectAllBtn = document.getElementById('deselectAllItems');
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            itemCards.forEach(card => {
                card.classList.remove('selected');
            });
            updateSelectionCounter();
        });
    }
})();
</script>
