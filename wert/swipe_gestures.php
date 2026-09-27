<!--
 Mobile Swipe-Gestures Component v4
 Einfache Overlay-Lösung - FUNKTIONIERT GARANTIERT
 Version: 4.0 - 2026-02-16
-->

<style>
@media (max-width: 768px) {
    /* Card Container für Swipe */
    .mobile-item-card {
        position: relative;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .mobile-item-card.swiping {
        transition: none;
    }
    
    /* Swipe Action Overlay (erscheint beim Swipen) */
    .swipe-action-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        z-index: 9999;
        display: none;
    }
    
    .swipe-action-overlay.active {
        display: block;
    }
    
    /* Action Indicator */
    .swipe-indicator {
        position: fixed;
        top: 50%;
        transform: translateY(-50%);
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        opacity: 0;
        transition: opacity 0.2s;
        pointer-events: auto;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    
    .swipe-indicator.show {
        opacity: 1;
    }
    
    .swipe-indicator.delete {
        left: 20px;
        background: #e74c3c;
    }
    
    .swipe-indicator.edit {
        right: 20px;
        background: #667eea;
    }
}
</style>

<script>
/**
 * Swipe-Gestures v4 - Overlay-Methode
 */
(function() {
    'use strict';
    
    if (window.innerWidth > 768) {
        console.log('Swipe v4: Desktop - skipping');
        return;
    }
    
    console.log('Swipe v4: Initializing...');
    
    // Erstelle Overlay
    const overlay = document.createElement('div');
    overlay.className = 'swipe-action-overlay';
    
    const deleteIndicator = document.createElement('div');
    deleteIndicator.className = 'swipe-indicator delete';
    deleteIndicator.innerHTML = '🗑️';
    
    const editIndicator = document.createElement('div');
    editIndicator.className = 'swipe-indicator edit';
    editIndicator.innerHTML = '✏️';
    
    overlay.appendChild(deleteIndicator);
    overlay.appendChild(editIndicator);
    document.body.appendChild(overlay);
    
    console.log('Swipe v4: Overlay created');
    
    // Setup Cards
    const itemCards = document.querySelectorAll('.mobile-item-card');
    console.log('Swipe v4: Found', itemCards.length, 'cards');
    
    let activeCard = null;
    let swipeAction = null;
    
    itemCards.forEach((card) => {
        let startX = 0;
        let currentX = 0;
        let deltaX = 0;
        let isSwiping = false;
        
        card.addEventListener('touchstart', function(e) {
            // Ignore checkbox, links, buttons
            if (e.target.closest('.mobile-card-checkbox') || 
                e.target.closest('a') || 
                e.target.closest('button')) {
                return;
            }
            
            startX = e.touches[0].clientX;
            currentX = startX;
            isSwiping = false;
            activeCard = card;
            swipeAction = null;
            
            console.log('Touch start on card');
        }, { passive: true });
        
        card.addEventListener('touchmove', function(e) {
            if (!activeCard) return;
            
            currentX = e.touches[0].clientX;
            deltaX = currentX - startX;
            
            if (Math.abs(deltaX) > 15) {
                if (!isSwiping) {
                    isSwiping = true;
                    card.classList.add('swiping');
                    overlay.classList.add('active');
                    console.log('Swipe started');
                }
                
                // Bestimme Action
                if (deltaX < -30) {
                    swipeAction = 'delete';
                    deleteIndicator.classList.add('show');
                    editIndicator.classList.remove('show');
                } else if (deltaX > 30) {
                    swipeAction = 'edit';
                    editIndicator.classList.add('show');
                    deleteIndicator.classList.remove('show');
                } else {
                    deleteIndicator.classList.remove('show');
                    editIndicator.classList.remove('show');
                }
                
                // Bewege Card
                const maxSwipe = 60;
                const limitedDelta = Math.max(-maxSwipe, Math.min(maxSwipe, deltaX));
                card.style.transform = `translateX(${limitedDelta}px)`;
            }
        }, { passive: true });
        
        card.addEventListener('touchend', function(e) {
            if (!isSwiping) {
                activeCard = null;
                return;
            }
            
            console.log('Touch end, delta:', deltaX, 'action:', swipeAction);
            
            card.classList.remove('swiping');
            card.style.transform = 'translateX(0)';
            
            const threshold = 50;
            
            if (Math.abs(deltaX) > threshold && swipeAction) {
                console.log('Action triggered:', swipeAction);
                
                // Vibration
                if (navigator.vibrate) {
                    navigator.vibrate(50);
                }
                
                const itemId = card.getAttribute('data-item-id');
                
                if (swipeAction === 'delete') {
                    handleDelete(itemId);
                } else if (swipeAction === 'edit') {
                    handleEdit(itemId);
                }
            }
            
            // Cleanup
            setTimeout(() => {
                overlay.classList.remove('active');
                deleteIndicator.classList.remove('show');
                editIndicator.classList.remove('show');
            }, 200);
            
            activeCard = null;
            isSwiping = false;
            swipeAction = null;
            deltaX = 0;
        }, { passive: true });
    });
    
    // Tap auf Indicators
    deleteIndicator.addEventListener('click', function() {
        if (activeCard) {
            const itemId = activeCard.getAttribute('data-item-id');
            handleDelete(itemId);
        }
    });
    
    editIndicator.addEventListener('click', function() {
        if (activeCard) {
            const itemId = activeCard.getAttribute('data-item-id');
            handleEdit(itemId);
        }
    });
    
    function handleDelete(itemId) {
        console.log('Delete:', itemId);
        overlay.classList.remove('active');
        
        vsConfirm(<?php echo json_encode(t('bulk_confirm_delete')); ?>).then(function (ja) {
            if (ja) { window.location.href = `delete.php?id=${itemId}`; }
        });
    }
    
    function handleEdit(itemId) {
        console.log('Edit:', itemId);
        overlay.classList.remove('active');
        window.location.href = `edit.php?id=${itemId}`;
    }
    
    console.log('Swipe v4: Initialized!');
})();
</script>
