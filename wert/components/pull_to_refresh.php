<!--
 Pull-to-Refresh Component
 Ziehe nach unten um die Seite neu zu laden (nur Mobile)
 Version: 1.0 - 2026-02-15
-->

<style>
/* Pull-to-Refresh Container */
.ptr-container {
    position: relative;
    overflow: hidden;
}

/* Pull-to-Refresh Indicator */
.ptr-indicator {
    position: fixed;
    top: -60px;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: top 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 9999;
    pointer-events: none;
}

.ptr-indicator.visible {
    top: 20px;
}

.ptr-indicator.loading {
    animation: ptr-spin 1s linear infinite;
}

@keyframes ptr-spin {
    from { transform: translateX(-50%) rotate(0deg); }
    to { transform: translateX(-50%) rotate(360deg); }
}

.ptr-indicator.success {
    background: #4caf50;
    color: white;
}

/* Pull Visual Feedback */
.ptr-pulling {
    /* Leichter Widerstand beim Ziehen */
}

@media (max-width: 768px) {
    /* Nur auf Mobile aktiv */
    body.ptr-enabled {
        overscroll-behavior-y: contain;
    }
}
</style>

<!-- Pull-to-Refresh Indicator -->
<div class="ptr-indicator" id="ptrIndicator">
    <span id="ptrIcon">↓</span>
</div>

<script>
/**
 * Pull-to-Refresh Implementation
 * Nur auf Mobile < 768px aktiv
 */
(function() {
    // Nur auf Mobile aktivieren
    if (window.innerWidth > 768) return;
    
    let startY = 0;
    let currentY = 0;
    let pulling = false;
    let threshold = 80; // Mindest-Distanz zum Triggern
    let maxPull = 120;   // Maximale Pull-Distanz
    
    const indicator = document.getElementById('ptrIndicator');
    const icon = document.getElementById('ptrIcon');
    
    // Touch Start
    document.addEventListener('touchstart', function(e) {
        // Nur wenn ganz oben gescrollt
        if (window.scrollY === 0) {
            startY = e.touches[0].clientY;
            pulling = true;
        }
    }, { passive: true });
    
    // Touch Move
    document.addEventListener('touchmove', function(e) {
        if (!pulling) return;
        
        currentY = e.touches[0].clientY;
        const pullDistance = Math.min(currentY - startY, maxPull);
        
        // Nur nach unten ziehen erlauben
        if (pullDistance > 0) {
            // Visual Feedback
            const progress = Math.min(pullDistance / threshold, 1);
            const indicatorTop = -60 + (pullDistance * 0.5); // Halbe Distanz
            
            indicator.style.top = indicatorTop + 'px';
            
            // Icon anpassen
            if (pullDistance >= threshold) {
                icon.textContent = '↻'; // Refresh-Symbol
                indicator.classList.add('visible');
            } else {
                icon.textContent = '↓';
                indicator.classList.remove('visible');
            }
            
            // Rotation basierend auf Pull-Distanz
            const rotation = progress * 180;
            icon.style.transform = `rotate(${rotation}deg)`;
        }
    }, { passive: true });
    
    // Touch End
    document.addEventListener('touchend', function(e) {
        if (!pulling) return;
        
        const pullDistance = currentY - startY;
        
        // Refresh triggern wenn über Threshold
        if (pullDistance >= threshold) {
            triggerRefresh();
        } else {
            // Zurücksetzen
            resetIndicator();
        }
        
        pulling = false;
        startY = 0;
        currentY = 0;
    }, { passive: true });
    
    /**
     * Refresh triggern
     */
    function triggerRefresh() {
        // Indicator auf "Loading" setzen
        indicator.classList.add('visible', 'loading');
        icon.textContent = '⟳';
        
        // Haptic Feedback (falls verfügbar)
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
        
        // Seite neu laden
        setTimeout(() => {
            window.location.reload();
        }, 300);
    }
    
    /**
     * Indicator zurücksetzen
     */
    function resetIndicator() {
        indicator.style.top = '-60px';
        indicator.classList.remove('visible', 'loading', 'success');
        icon.textContent = '↓';
        icon.style.transform = 'rotate(0deg)';
    }
    
    // Body-Klasse für CSS
    document.body.classList.add('ptr-enabled');
    
    // Cleanup bei Resize (wenn > 768px)
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                document.body.classList.remove('ptr-enabled');
            } else {
                document.body.classList.add('ptr-enabled');
            }
        }, 250);
    });
})();
</script>
